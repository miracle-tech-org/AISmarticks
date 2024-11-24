<?php

namespace Modules\AIAssistant\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

use Modules\AIAssistant\Services\OpenAIService;
use Modules\AIAssistant\Models\AIAgentAssignment;
use Modules\AIAssistant\Events\APIUsageLimitApproaching;
use Modules\AIAssistant\Events\APIUsageLimitReached;
use Modules\AIAssistant\Models\Ticket;

class ProcessAIResponse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $ticket;
    protected $maxRetryAttempts = 3; // Maximum retry attempts
    protected $retryDelay = 300; // Retry delay in seconds

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $assignment = $this->getAIAgentAssignment($ticket);

        if ($assignment && $assignment->current_usage < $assignment->monthly_usage_limit) {
            try {
                $aiAgent = $assignment->aiAgent;
                $openAIService = new OpenAIService($aiAgent->openai_api_key, $aiAgent->model);
                $messages = $this->buildMessages($ticket, $aiAgent->system_prompt);
                $result = $openAIService->generateResponse($messages);
    
                $aiResponse = $result['content'];
                $tokensUsed = $result['tokens_used'];
    
                if ($aiResponse) {
                    $this->handleAIResponse($aiResponse, $ticket, $assignment, $tokensUsed);
                }
            } catch (Exception $e) {
                // Retry logic with exponential backoff
                if ($this->attempt() < $this->maxRetryAttempts) {
                    $this->release($this->retryDelay * (2 ** $this->attempt()));
                } else {
                    // Alternative AI fallback
                    $this->fallbackToAlternativeAI($e);
                }
            }
        }
    }

        /**
     * Fallback to alternative AI service
     *
     * @param \Exception $e
     *
     * @return void
     */
    private function fallbackToAlternativeAI(\Exception $e)
    {
        // Implement alternative AI service logic
        // For demonstration purposes, a simple log is written
        \Log::error('Fallback to alternative AI service due to error: '. $e->getMessage());
    }

    public function execute()
    {
        $assignment = $this->ticket->getAIAgentAssignment();

        if ($assignment && $assignment->current_usage < $assignment->monthly_usage_limit) {
            $aiAgent = $assignment->aiAgent;
            try {

                $openAIService = new OpenAIService($aiAgent->openai_api_key, $aiAgent->model);

                $messages = [
                    ['role' => 'user', 'content' => $this->ticket->message],
                ];
                $result = $openAIService->generateResponse($messages);

                $aiResponse = $result['content'];
                $tokensUsed = $result['tokens_used'];

                if ($aiResponse) {
                    $this->handleAIResponse($aiResponse, $this->ticket, $assignment, $tokensUsed);

                    $conversation_id = $this->ticket->getConversationID();

                    $data = [
                        'ai_agent_id' => $assignment->id,
                        'conversation_id' => $conversation_id,
                        'input_text' => $this->ticket->message,
                        'output_text' => $aiResponse,
                        'tokens_used' => $tokensUsed,
                        'mailbox_id' => 1,
                    ];
            
                    DB::table('ai_interaction_logs')->insert($data);
            
                    $client = new Client();
                    $headers = [
                        'Accept' => 'application/json, text/javascript, */*; q=0.01',
                        'Accept-Language' => 'en-US,en;q=0.9',
                        'Connection' => 'keep-alive',
                        'Cookie' => 'dm_enabled=1; laravel_session=eyJpdiI6IkJ2UkxGeFZ3aGg0aHZUc0w3Z2lLWVE9PSIsInZhbHVlIjoiUHpEWjlmYkF3Zkw1d0dCZ2hlbUhHUVZPa0h5ZjJTOXA3ZlRGY0grQ1UwdXlHME0rb3k3TVpEckhvMThsSnFOSE52ZVNKVXNYVmNjZW42VDRjcHNmY3FQTFFmWnhEY0dkdlFZa0xjYVlNZ0VlYnd6OVpUV0RUdmlMXC92N3JKcmFKIiwibWFjIjoiNGI4OGI5MzhiOTU0ZjYzNmQ5NjdiNGQwODFlNjhkNzVkNWJiNzJkYzM0MmYwYzJhNzE3ZTQ3NzgxYzlmNGFlMyJ9',
                        'Origin' => 'https://aitest.smarticks.com',
                        'Referer' => 'https://aitest.smarticks.com/conversation/1?folder_id=11&chat_mode=1',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
                        'X-Requested-With' => 'XMLHttpRequest',
                        'sec-ch-ua' => '"Chromium";v="130", "Google Chrome";v="130", "Not?A_Brand";v="99"',
                        'sec-ch-ua-mobile' => '?0',
                        'sec-ch-ua-platform' => '"Windows"',
                    ];
            
                    $body = [
                        '_token' => 'fmQKjb5xxX4TdX9jp3wOUR8FClO8p1lEzZJaVETI',
                        'conversation_id' => $conversation_id,
                        'mailbox_id' => 1,
                        'saved_reply_id' => '',
                        'thread_id' => '',
                        'is_note' => '',
                        'subtype' => '',
                        'conv_history' => '',
                        'body' => '<div>' . $aiResponse . '</div>',
                        'status' => '2',
                        'user_id' => '4',
                        'after_send' => '2',
                        'after_send_default' => '2',
                        'action' => 'send_reply',
                    ];
            
                    $request = $client->request('POST', 'https://aitest.smarticks.com/conversation/ajax?folder_id=11', [
                        'headers' => $headers,
                        'json' => $body
                    ]);
                }
            } catch (Exception $e) {
                // Retry logic with exponential backoff
                if ($this->attempt() < $this->maxRetryAttempts) {
                    $this->release($this->retryDelay * (2 ** $this->attempt()));
                } else {
                    // Alternative AI fallback
                    $this->fallbackToAlternativeAI($e);
                }
            }
        }
    }

    /**
     * Get AI Agent Assignment for the given ticket
     *
     * @param mixed $ticket
     *
     * @return AIAgentAssignment|null
     */
    private function getAIAgentAssignment(Ticket $ticket)
    {
        // Retrieve assignment based on ticket (conversation or mailbox)
        // For demonstration purposes, assume we have a method to fetch the assignment
        return $ticket->getAIAgentAssignment();
    }

    /**
     * Build messages for the AI response generation
     *
     * @param mixed $ticket
     * @param string|null $systemPrompt
     *
     * @return array
     */
    private function buildMessages(Ticket $ticket, $systemPrompt = null)
    {
        // Construct messages array for AI response generation
        // Include system prompt (if provided) and user input (from the ticket)
        $messages = [];

        if ($systemPrompt) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }

        // Add user input from the ticket
        $messages[] = [
            'role' => 'user',
            'content' => $ticket->message,
        ];

        return $messages;
    }

    /**
     * Handle AI Response
     *
     * @param string $aiResponse
     * @param mixed $ticket
     * @param AIAgentAssignment $assignment
     * @param int $tokensUsed
     *
     * @return void
     */
    private function handleAIResponse($aiResponse, $ticket, AIAgentAssignment $assignment, $tokensUsed)
    {
        // Update assignment usage
        $assignment->update([
            'current_usage' => $assignment->current_usage + $tokensUsed,
        ]);

        // Trigger usage alerts if necessary
        $this->checkUsageAlerts($assignment);

        // Respond to the user with the AI-generated response
        // For demonstration purposes, assume we have a method to respond to the user
        // $ticket->respondToUser($aiResponse);
    }

    /**
     * Check if usage alerts should be triggered
     *
     * @param AIAgentAssignment $assignment
     *
     * @return void
     */
    private function checkUsageAlerts(AIAgentAssignment $assignment)
    {
        // Calculate usage percentage
        $usagePercentage = ($assignment->current_usage / $assignment->monthly_usage_limit) * 100;

        // Trigger alerts based on the usage threshold
        if ($usagePercentage >= $assignment->usage_alert_threshold) {
            event(new APIUsageLimitApproaching($assignment));
        } elseif ($usagePercentage >= 100) {
            event(new APIUsageLimitReached($assignment));
        }
    }
}
