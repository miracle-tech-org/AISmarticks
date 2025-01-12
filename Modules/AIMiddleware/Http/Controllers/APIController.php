<?php

namespace Modules\AIMiddleware\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\AIMiddleware\Services\AIService;
use Modules\AIMiddleware\Models\{AIInteractionLog, AITrainingData};

class APIController extends Controller
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Analyze a ticket using AI
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyzeTicket(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'ticket_id' => 'required|integer|exists:tickets,id',
                'content' => 'required|string|max:5000',
                'context' => 'nullable|array',
            ]);

            // Get cached analysis if available
            $cacheKey = "ticket_analysis:{$validated['ticket_id']}";
            if ($analysis = Cache::get($cacheKey)) {
                return response()->json($analysis);
            }

            // Generate analysis
            $startTime = microtime(true);
            $analysis = $this->aiService->generateResponse([
                [
                    'role' => 'user',
                    'content' => $this->buildAnalysisPrompt($validated),
                ],
            ]);

            // Log interaction
            $this->logInteraction(
                $validated['ticket_id'],
                'analyze',
                $analysis,
                microtime(true) - $startTime
            );

            // Cache analysis
            Cache::put($cacheKey, $analysis, now()->addMinutes(30));

            return response()->json($analysis);

        } catch (\Exception $e) {
            Log::error('Ticket analysis failed', [
                'error' => $e->getMessage(),
                'ticket_id' => $request->input('ticket_id'),
            ]);

            return response()->json([
                'error' => 'Analysis failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get AI-powered suggestions
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSuggestion(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'content' => 'required|string|max:5000',
                'type' => 'required|string|in:response,tag,category',
                'context' => 'nullable|array',
            ]);

            $startTime = microtime(true);
            $suggestion = $this->aiService->generateResponse([
                [
                    'role' => 'user',
                    'content' => $this->buildSuggestionPrompt($validated),
                ],
            ]);

            // Log interaction
            $this->logInteraction(
                $request->input('ticket_id'),
                'suggest',
                $suggestion,
                microtime(true) - $startTime
            );

            return response()->json($suggestion);

        } catch (\Exception $e) {
            Log::error('Suggestion generation failed', [
                'error' => $e->getMessage(),
                'type' => $request->input('type'),
            ]);

            return response()->json([
                'error' => 'Suggestion failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get module statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        try {
            $stats = Cache::remember('my_improvements_stats', 300, function () {
                return [
                    'total_interactions' => AIInteractionLog::count(),
                    'average_response_time' => AIInteractionLog::avg('response_time'),
                    'success_rate' => $this->calculateSuccessRate(),
                    'token_usage' => $this->calculateTokenUsage(),
                ];
            });

            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('Stats retrieval failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to retrieve stats'], 500);
        }
    }

    /**
     * Get performance trends
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTrends(Request $request)
    {
        try {
            $days = $request->input('days', 30);
            $cacheKey = "my_improvements_trends_{$days}";

            $trends = Cache::remember($cacheKey, 3600, function () use ($days) {
                return AIInteractionLog::selectRaw('
                    DATE(created_at) as date,
                    COUNT(*) as total,
                    AVG(response_time) as avg_response_time,
                    SUM(tokens_used) as tokens,
                    AVG(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as success_rate
                ')
                    ->where('created_at', '>=', now()->subDays($days))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
            });

            return response()->json($trends);

        } catch (\Exception $e) {
            Log::error('Trends retrieval failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to retrieve trends'], 500);
        }
    }

    /**
     * Submit user feedback
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitFeedback(Request $request)
    {
        try {
            $validated = $request->validate([
                'interaction_id' => 'required|exists:my_improvements_interaction_logs,id',
                'rating' => 'required|integer|min:1|max:5',
                'feedback_text' => 'nullable|string|max:1000',
            ]);

            // Update interaction log
            $interaction = AIInteractionLog::findOrFail($validated['interaction_id']);
            $interaction->update([
                'rating' => $validated['rating'],
            ]);

            // Store feedback for training
            AITrainingData::create([
                'interaction_id' => $validated['interaction_id'],
                'query' => $interaction->user_query,
                'response' => $interaction->ai_response,
                'rating' => $validated['rating'],
                'feedback_text' => $validated['feedback_text'] ?? null,
                'category' => 'user_feedback',
            ]);

            return response()->json(['message' => 'Feedback submitted successfully']);

        } catch (\Exception $e) {
            Log::error('Feedback submission failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to submit feedback'], 500);
        }
    }

    /**
     * Build analysis prompt
     */
    protected function buildAnalysisPrompt(array $data): string
    {
        $prompt = "Analyze the following ticket:\n\n";
        $prompt .= "Content: {$data['content']}\n\n";

        if (!empty($data['context'])) {
            $prompt .= "Context:\n";
            foreach ($data['context'] as $key => $value) {
                $prompt .= "- {$key}: {$value}\n";
            }
        }

        return $prompt;
    }

    /**
     * Build suggestion prompt
     */
    protected function buildSuggestionPrompt(array $data): string
    {
        $prompt = "Generate a {$data['type']} suggestion for:\n\n";
        $prompt .= "Content: {$data['content']}\n\n";

        if (!empty($data['context'])) {
            $prompt .= "Context:\n";
            foreach ($data['context'] as $key => $value) {
                $prompt .= "- {$key}: {$value}\n";
            }
        }

        return $prompt;
    }

    /**
     * Calculate success rate
     */
    protected function calculateSuccessRate(): float
    {
        $total = AIInteractionLog::count();
        if (!$total) {
            return 0;
        }

        $successful = AIInteractionLog::where('rating', '>=', 4)->count();
        return round(($successful / $total) * 100, 2);
    }

    /**
     * Calculate token usage
     */
    protected function calculateTokenUsage(): array
    {
        return [
            'total' => AIInteractionLog::sum('tokens_used'),
            'average' => round(AIInteractionLog::avg('tokens_used'), 2),
        ];
    }

    /**
     * Log AI interaction
     */
    protected function logInteraction(
        int $ticketId,
        string $type,
        array $response,
        float $duration
    ): void {
        AIInteractionLog::create([
            'ticket_id' => $ticketId,
            'agent_id' => config('my_improvements.default_agent_id'),
            'type' => $type,
            'response' => $response,
            'duration' => $duration,
            'tokens_used' => $response['tokens_used'] ?? 0,
        ]);
    }
}
