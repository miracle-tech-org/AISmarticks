<?php
namespace App\Modules\MyImprovements\Services;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

use App\Misc\Helper;
use App\Misc\Mail;
use App\Thread;
use App\User;
use App\SendLog;
use App\Option;
use App\Logger\Logger;
use GuzzleHttp\Client;

/**
 * Core AI Service for handling OpenAI API interactions
 */
class AIService
{
    protected Client $client;
    protected string $model;
    
    /**
     * Initialize using FreeScout's option system
     */
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout'  => Option::get('my_improvements.timeout', 30.0),
        ]);
        $this->model = Option::get('my_improvements.openai_model', 'gpt-4');
    }

    /**
     * Process ticket with AI and create response
     */
    public function processTicket($ticket)
    {
        try {
            // Generate AI response
            $response = $this->generateResponse($ticket);
            
            // Create thread using FreeScout's system
            $thread = Thread::create([
                'type' => Thread::TYPE_NOTE,
                'state' => Thread::STATE_PUBLISHED,
                'status' => Thread::STATUS_ACTIVE,
                'ticket_id' => $ticket->id,
                'user_id' => User::getSystemUser()->id,
                'body' => $response['content']
            ]);

            // Log using FreeScout's system
            Logger::info('AI response generated', [
                'ticket_id' => $ticket->id,
                'thread_id' => $thread->id
            ]);

            // Process tags using FreeScout's tag system
            if (!empty($response['tags'])) {
                $ticket->addTags($response['tags']);
            }

            // Send notifications using FreeScout's system
            $this->sendNotifications($ticket, $response);

            return $thread;

        } catch (\Exception $e) {
            Logger::error('AI processing failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send notifications using FreeScout's systems
     */
    protected function sendNotifications($ticket, $response)
    {
        // Notify admins if needed
        if ($response['action'] === 'notifyTeam') {
            $admins = User::role('admin')->get();
            Mail::sendToUsers(
                $admins,
                'ai_notification',
                [
                    'ticket' => $ticket,
                    'response' => $response
                ]
            );
        }

        // Log email sending
        SendLog::log($ticket->last_thread, 
            User::getSystemUser(), 
            SendLog::MAIL_TYPE_AUTO_REPLY
        );
    }

    /**
     * Build ticket context using FreeScout's helper methods
     */
    protected function buildTicketContext($ticket): string
    {
        $context = [];
        $context[] = "Ticket #{$ticket->number}";
        $context[] = "Subject: {$ticket->subject}";
        
        // Get conversation history using FreeScout's methods
        $threads = $ticket->getThreads()
            ->take(5)
            ->reverse();
            
        foreach ($threads as $thread) {
            $context[] = sprintf(
                "%s: %s",
                Helper::formatDateTime($thread->created_at),
                Helper::sanitizeHTML($thread->body)
            );
        }

        return implode("\n\n", $context);
    }

    /**
     * Process response using FreeScout's formatting
     */
    protected function processResponse($response)
    {
        return [
            'content' => Helper::sanitizeHTML($response['content']),
            'tokens_used' => $response['tokens_used'],
            'model' => $response['model']
        ];
    }
}
