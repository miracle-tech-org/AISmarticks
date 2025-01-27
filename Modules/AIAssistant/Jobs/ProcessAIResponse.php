<?php

namespace Modules\AIAssistant\Jobs;

use App\ActivityLog;
use App\Jobs\SendAlert;
use DB;
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
use Modules\AIAssistant\Models\Workload;
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
        ActivityLog::create([
            'properties' => '[]',
            'log_name' => 'aiassistant_errors',
            'description' => 'Fallback to alternative AI service due to error: ' . $e->getMessage(),
        ]);
    }

    public function execute()
    {
        if (\Module::isActive('aiassistant')) {
            $assignment = $this->ticket->getAIAgentAssignment();

            $client = new Client(['base_uri' => 'https://api.openai.com/v1/']);
            try {
                $response = $client->post('chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer sk-svcacct-zxGljF843UXf9F7Y7ynj0T2tayiaK7YZfix2U0sr0QWH9blfjTU3bSWz3BeMIT3BlbkFJJz1G8KDCAkQlUzQgwaeCADe2OyXXO7S9aL1nsT4k7CXuxiupAxf5cfkmVr_sQA',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => 'gpt-4',
                        'messages' => [
                            [
                                "role" => "system",
                                "content" => "You are an AI model designed to evaluate whether a user's input is related to Smarticks.com. Smarticks.com includes the following sections:\nHome: General information about the platform.\nAbout: Details about the company, its mission, and vision.\nFeatures: Information about Smarticks.com features such as interactive tools, or user experience.\nPricing: Details about subscription plans, costs, and payment options.\nHelp Center: Information on account management, troubleshooting, deleting accounts, and customer support.\nAPI Docs: Technical documentation for developers using Smarticks.com's API.\nPerform the following:\nIf the message is a greeting (e.g., Hello, Hi there, or similar), always return 100%.\nIf the message is directly related to any of the above sections, analyze the content and return a high confidence percentage.\nIf the message is unrelated to Smarticks.com, return a low confidence percentage.\nImportant:\nResponse message can be only like this: x%"
                            ],
                            [
                                "role" => "user",
                                "content" => $this->ticket->message
                            ]
                        ],
                    ],
                    'timeout' => 15,
                ]);

                $body = json_decode($response->getBody(), true);
                $aiResponse = $body['choices'][0]['message']['content'] ?? '';

                $pro = (float)preg_replace('/[^0-9]/', '', $aiResponse);
                if($pro < 40) {
                    Workload::create([
                        'ticket_id' => $this->ticket->thread_id
                    ]);
                    return;
                }
            } catch (Exception $e) {
                ActivityLog::create([
                    'properties' => '[]',
                    'log_name' => 'aiassistant_errors',
                    'description' => 'AIMiddleware: Out of knowledge base',
                ]);
                return;
            }

            if ($assignment && $assignment->current_usage < $assignment->monthly_usage_limit * ($assignment->usage_alert_threshold / 100) &&
                $assignment->daily_usage < $assignment->daily_limit * ($assignment->daily_usage_alert / 100)) {

                try {
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
                } catch (\Throwable $th) {
                    ActivityLog::create([
                        'properties' => '[]',
                        'log_name' => 'aiassistant_errors',
                        'description' => 'ProcessAIResponse Error: AIAgent account not found',
                    ]);
                    return;
                }

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

                    $system = $aiAgent->system_prompt;
                    if ($aiAgent->faqs != "") {
                        $system .= "\nHere are faqs:\n" . $aiAgent->faqs;
                    }

                    array_unshift($messages, ["role" => "system", "content" => $system]);
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
                    ActivityLog::create([
                        'properties' => '[]',
                        'log_name' => 'aiassistant_errors',
                        'description' => 'ProcessAIResponse Error: ' . $e->getMessage(),
                    ]);

                    // Retry logic with exponential backoff
                    if ($this->attempt() < $this->maxRetryAttempts) {
                        $this->release($this->retryDelay * (2 ** $this->attempt()));
                    } else {
                        // Alternative AI fallback
                        $this->fallbackToAlternativeAI($e);
                    }
                }
            } else {
                if (!is_null($assignment)) {
                    $aiAgentName = $assignment->aiAgent->name;
                    $mailboxName = DB::table('mailboxes')
                        ->where('id', $this->ticket->mailbox_id)
                        ->value('name');

                    if ($assignment->current_usage > $assignment->monthly_usage_limit * ($assignment->usage_alert_threshold / 100)) {
                        $alert = new SendAlert("Reminder: You have reached your monthly usage limit. Please check your usage and adjust accordingly.<br>Mailbox: $mailboxName<br>AI Agent: $aiAgentName");
                        $alert->handle();

                        $assignment->update([
                            'alert_triggered' => 2,
                        ]);

                        ActivityLog::create([
                            'properties' => '[]',
                            'log_name' => 'aiassistant_errors',
                            'description' => 'Reached out monthly limit',
                        ]);
                        return;
                    }

                    if($assignment->daily_usage > $assignment->daily_limit * ($assignment->daily_usage_alert / 100)) {
                        $alert = new SendAlert("Reminder: You have reached your daily usage limit. Please check your usage and adjust accordingly.<br>Mailbox: $mailboxName<br>AI Agent: $aiAgentName");
                        $alert->handle();

                        $assignment->update([
                            'alert_triggered' => 1,
                        ]);

                        ActivityLog::create([
                            'properties' => '[]',
                            'log_name' => 'aiassistant_errors',
                            'description' => 'Reached out daily limit',
                        ]);
                        return;
                    }
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
            'daily_usage' => $assignment->daily_usage + $tokensUsed,
        ]);

        // Trigger usage alerts if necessary
        // $this->checkUsageAlerts($assignment);

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
