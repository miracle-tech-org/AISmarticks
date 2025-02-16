<?php

namespace Modules\AIAssistant\Jobs;

use App\ActivityLog;
use App\Jobs\SendAlert;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Modules\AIAssistant\Models\AIAgent;
use Modules\AIAssistant\Models\AIInteractionLog;
use Modules\AIAssistant\Models\Ticket;
use Modules\AIAssistant\Models\Workload;
use Modules\AIAssistant\Services\OpenAIService;
use DB;

class ProcessAIResponse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $ticket;
    protected $maxRetryAttempts = 3;
    protected $retryDelay = 300;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    private function fallbackToAlternativeAI(\Exception $e)
    {
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
                                "content" => "You are an AI model designed to evaluate whether a user's input is related to Smarticks.com. Smarticks.com includes the following sections:\nHome: General information about the platform.\nAbout: Details about the company, its mission, and vision.\nFeatures: Information about Smarticks.com features such as interacti,ve tools, or user experience.\nPricing: Details about subscription plans, costs, and payment options.\nHelp Center: Information on account management, troubleshooting, deleting accounts, and customer support.\nAPI Docs: Technical documentation for developers using Smarticks.com's API.\nPerform the following:\nIf the message is a greeting (e.g., Hello, Hi there, or similar), always return 100%.\nIf the message is directly related to any of the above sections, analyze the content and return a high confidence percentage.\nIf the message is unrelated to Smarticks.com, return a low confidence percentage.\nImportant:\nResponse message can be only like this(json): \"{\"level\": \"x%\", \"reason\": \"xxx...\"}"
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
                $aiResponse = $body['choices'][0]['message']['content'] ?? '{"level": "0%", "reason": ""}';
                $jsonMiddleware = json_decode($aiResponse);

                $pro = (float)preg_replace('/[^0-9]/', '', $jsonMiddleware->level);
                if($pro < 40) {
                    $tag = DB::table('tags')->where('name', 'needs-human')
                        ->where('counter', 0)
                        ->where('color', 2)
                        ->first();

                    if (!$tag) {
                        $tag = DB::table('tags')->insert([
                            'name' => 'needs-human',
                            'counter' => 0,
                            'color' => 2,
                        ]);
                    }

                    $convTag = DB::table('conversation_tag')
                        ->where('conversation_id', $this->ticket->conversation_id)
                        ->where('tag_id', $tag->id)
                        ->first();

                    if (!$convTag) {
                        $tag = DB::table('conversation_tag')->insert([
                            'conversation_id' => $this->ticket->conversation_id,
                            'tag_id' => $tag->id,
                        ]);
                    }
                    
                    Workload::create([
                        'ticket_id' => $this->ticket->thread_id,
                        'review' => $aiResponse
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

            if ($assignment && 
                $assignment->monthly_usage < $assignment->monthly_limit * ($assignment->monthly_alert_threshold / 100) &&
                $assignment->daily_usage < $assignment->daily_limit * ($assignment->daily_alert_threshold / 100)) {

            try {
                    $client = new Client();
                    $response = $client->get(route('aiagents.session')."?email=".$this->ticket->email);

                    $userId = json_decode($response->getBody(), true)['userId'];

                    $strCookie = '';
                    foreach ($response->getHeaders()['Set-Cookie'] as $cookie) {
                        if (strpos($cookie, 'laravel_session=') !== false) {
                            $strCookie = $cookie;
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    ActivityLog::create([
                        'properties' => '[]',
                        'log_name' => 'aiassistant_errors',
                        'description' => 'ProcessAIResponse Error: AIAgent account not found',
                    ]);
                    return;
                }
                try {

                    $openAIService = new OpenAIService($assignment->openai_api_key, $assignment->model);

                    $messages = [
                        [
                            "role" => "user",
                            "content" => $this->ticket->message,
                        ],
                    ];

                    $system = $assignment->system_prompt;
                    if ($assignment->faqs != "") {
                        $system .= "\nHere are faqs:\n" . $assignment->faqs;
                    }

                    array_unshift($messages, ["role" => "system", "content" => $system]);
                    $result = $openAIService->generateResponse($messages);

                    $aiResponse = $result['content'];
                    $tokensUsed = $result['tokens_used'];
                    
                    if ($aiResponse) {
                        $this->handleAIResponse($aiResponse, $this->ticket, $assignment, $tokensUsed);

                        $data = [
                            'ai_agent_id' => $assignment->id,
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
                return;
                if (!is_null($assignment)) {
                    $aiAgentName = $assignment->name;
                    $mailboxName = DB::table('mailboxes')
                        ->where('id', $this->ticket->mailbox_id)
                        ->value('name');

                    if ($assignment->monthly_usage > $assignment->monthly_limit * ($assignment->monthly_alert_threshold / 100)) {
                        $alert = new SendAlert("Reminder: You have reached your monthly usage limit. Please check your usage and adjust accordingly.<br>Mailbox: $mailboxName<br>AI Agent: $aiAgentName");
                        $alert->handle();

                        ActivityLog::create([
                            'properties' => '[]',
                            'log_name' => 'aiassistant_errors',
                            'description' => 'Reached out monthly limit',
                        ]);
                        return;
                    }

                    if($assignment->daily_usage > $assignment->daily_limit * ($assignment->daily_alert_threshold / 100)) {
                        $alert = new SendAlert("Reminder: You have reached your daily usage limit. Please check your usage and adjust accordingly.<br>Mailbox: $mailboxName<br>AI Agent: $aiAgentName");
                        $alert->handle();

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

    private function handleAIResponse($aiResponse, $ticket, AIAgent $assignment, $tokensUsed)
    {
        // Update assignment usage
        $assignment->update([
            'monthly_usage' => $assignment->monthly_usage + $tokensUsed,
            'daily_usage' => $assignment->daily_usage + $tokensUsed,
        ]);
    }
}
