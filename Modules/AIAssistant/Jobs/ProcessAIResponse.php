<?php

namespace Modules\AIAssistant\Jobs;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\AIAssistant\Events\APIUsageLimitApproaching;
use Modules\AIAssistant\Events\APIUsageLimitReached;
use Modules\AIAssistant\Models\AIAgentAssignment;
use Modules\AIAssistant\Models\AIInteractionLog;
use Modules\AIAssistant\Models\Ticket;
use Modules\AIAssistant\Services\OpenAIService;

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
        \Log::error('Fallback to alternative AI service due to error: ' . $e->getMessage());
    }

    public function execute()
    {
        $client = new Client();
        $response = $client->get(route('aiagents.session'));
        $userId = json_decode($response->getBody(), true)['userId'];
        $strCookie = '';
        foreach ($response->getHeaders()['Set-Cookie'] as $cookie) {
            if (strpos($cookie, 'laravel_session=') !== false) {
                $strCookie = $cookie;
                break;
            }
        }

        $assignment = $this->ticket->getAIAgentAssignment();

        if ($assignment && $assignment->current_usage < $assignment->monthly_usage_limit) {
            $aiAgent = $assignment->aiAgent;
            try {

                $openAIService = new OpenAIService($aiAgent->openai_api_key, $aiAgent->model);

                // $threads = DB::select('SELECT * FROM threads WHERE customer_id = ' . $this->ticket->customer_id);
                $messages = [
                    [
                        "role" => "user",
                        "content" => $this->ticket->message,
                    ],
                ];
                // $messages = array_map(function ($thread) {
                //     return [
                //         'role' => $thread->type == '1' ? 'user' : 'assistant',
                //         'content' => $thread->body,
                //     ];
                // }, $threads);

                array_unshift($messages, ["role" => "system", "content" => $aiAgent->system_prompt]);
                $result = $openAIService->generateResponse($messages);

                $aiResponse = $result['content'];
                $tokensUsed = $result['tokens_used'];

                if ($aiResponse) {
                    $this->handleAIResponse($aiResponse, $this->ticket, $assignment, $tokensUsed);

                    $data = [
                        'ai_agent_id' => $aiAgent->id,
                        'conversation_id' => $this->ticket->conversation_id,
                        'input_text' => $this->ticket->message,
                        'output_text' => $aiResponse,
                        'tokens_used' => $tokensUsed,
                        'mailbox_id' => $this->ticket->mailbox_id,
                    ];

                    AIInteractionLog::create($data);

                    $appUrl = config("app.url");
                    $folderId = $this->ticket->folder_id;

                    $client = new Client();
                    $headers = [
                        'Accept' => 'application/json, text/javascript, */*; q=0.01',
                        'Accept-Language' => 'en-US,en;q=0.9',
                        'Connection' => 'keep-alive',
                        'Cookie' => $strCookie,
                        'Origin' => "$appUrl/conversation/ajax?folder_id=$folderId",
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
                        'X-Requested-With' => 'XMLHttpRequest',
                        'sec-ch-ua' => '"Chromium";v="130", "Google Chrome";v="130", "Not?A_Brand";v="99"',
                        'sec-ch-ua-mobile' => '?0',
                        'sec-ch-ua-platform' => '"Windows"',
                    ];

                    $body = [
                        'conversation_id' => $this->ticket->conversation_id,
                        'mailbox_id' => $this->ticket->mailbox_id,
                        'saved_reply_id' => '',
                        'thread_id' => '',
                        'is_note' => '',
                        'subtype' => '',
                        'conv_history' => '',
                        'body' => $aiResponse,
                        'status' => '2',
                        'user_id' => $userId,
                        'after_send' => '2',
                        'after_send_default' => '2',
                        'action' => 'send_reply',
                    ];

                    if ($assignment->response_mode == 'suggest') {
                        $body['action'] = 'save_draft';
                    }

                    $request = $client->request('POST', "$appUrl/conversation/ajax?folder_id=$folderId", [
                        'headers' => $headers,
                        'json' => $body,
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
