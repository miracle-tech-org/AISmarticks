<?php

namespace Modules\AIMiddleware\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\AIMiddleware\Services\AIService;

class AIResponseMiddleware
{
    protected AIService $aiService;
    protected int $maxRetries = 3;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only process ticket-related requests
        if (!$request->route('ticket')) {
            return $next($request);
        }

        try {
            // Check rate limiting
            if (!$this->checkRateLimit()) {
                Log::warning('Rate limit exceeded', [
                    'user_id' => auth()->id(),
                    'ticket_id' => $request->route('ticket')->id
                ]);
                return $next($request);
            }

            $startTime = microtime(true);
            
            // Generate AI response
            $aiResponse = $this->aiService->generateResponse([
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($request)
                ]
            ]);

            $responseTime = (microtime(true) - $startTime) * 1000;

            // Process the response
            $processedResponse = $this->processAIResponse(
                $request->route('ticket'),
                $aiResponse
            );

            // Log the interaction
            $interaction = $this->logInteraction(
                $request->route('ticket'),
                $request->input('message'),
                $aiResponse,
                $responseTime
            );

            // Add AI suggestion to response
            $response = $next($request);
            return $response->setData(array_merge(
                $response->getData(true),
                ['ai_suggestion' => $processedResponse]
            ));

        } catch (\Exception $e) {
            Log::error('AI Processing Error', [
                'error' => $e->getMessage(),
                'ticket_id' => $request->route('ticket')->id,
                'trace' => $e->getTraceAsString()
            ]);
            return $next($request);
        }
    }

    /**
     * Build context-aware prompt for AI
     */
    protected function buildPrompt(Request $request): string
    {
        $ticket = $request->route('ticket');
        
        // Build comprehensive context
        $context = [];
        $context[] = "Ticket #{$ticket->id}";
        $context[] = "Subject: {$ticket->subject}";
        
        // Add previous messages for context (limited to last 5)
        $previousMessages = $ticket->messages()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->reverse();
            
        foreach ($previousMessages as $message) {
            $context[] = "{$message->user_type}: {$message->content}";
        }

        // Add current message and tags
        $context[] = "Current message: {$request->input('message')}";
        if (!empty($ticket->tags)) {
            $context[] = "Current tags: " . implode(', ', $ticket->tags);
        }

        return implode("\n\n", $context);
    }

    /**
     * Process and validate AI response
     */
    protected function processAIResponse($ticket, array $aiResponse): array
    {
        $response = json_decode($aiResponse['content'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from AI');
        }

        // Process tags if present
        if (!empty($response['tags'])) {
            $this->processTags($ticket, $response['tags']);
        }

        // Add processing metadata
        $response['processing_metadata'] = [
            'tokens_used' => $aiResponse['tokens_used'],
            'model' => $aiResponse['model'],
            'processed_at' => now()->toIso8601String()
        ];

        return $response;
    }

    /**
     * Process and update ticket tags
     */
    protected function processTags($ticket, array $tags): void
    {
        // Validate tags
        $validatedTags = array_filter($tags, function($tag) {
            return is_string($tag) && strlen(trim($tag)) > 0;
        });

        if (empty($validatedTags)) {
            return;
        }

        $existingTags = $ticket->tags ?? [];
        $newTags = array_unique(array_merge($existingTags, $validatedTags));
        
        try {
            $ticket->update(['tags' => $newTags]);
            Log::info('Tags updated', [
                'ticket_id' => $ticket->id,
                'new_tags' => array_diff($newTags, $existingTags)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update tags', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log AI interaction
     */
    protected function logInteraction($ticket, $query, $aiResponse, $responseTime)
    {
        $parsedResponse = json_decode($aiResponse['content'], true);
        
        return AIInteractionLog::create([
            'ticket_id' => $ticket->id,
            'agent_id' => Option::get('my_improvements.default_agent_id'),
            'user_query' => $query,
            'ai_response' => $aiResponse['content'],
            'tokens_used' => $aiResponse['tokens_used'],
            'tags' => $parsedResponse['tags'] ?? [],
            'sentiment' => $parsedResponse['sentiment'] ?? null,
            'confidence' => $parsedResponse['confidence'] ?? null,
            'priority' => $parsedResponse['priority'] ?? null,
            'response_time' => $responseTime
        ]);
    }
}
