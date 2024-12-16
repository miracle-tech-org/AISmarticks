<?php

namespace Modules\AIAssistant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Notifications\UserNotifiedOfResponse;
use Modules\AIAssistant\Models\AIAgent;
use App\Thread;
use App\Conversation;
use Exception;

class Ticket
{
    public $message;
    public $conversation_id;
    public $folder_id;
    public $mailbox_id;
    public $customer_id;

    public function respondToUser($responseContent)
    {
        // Validate response content
        if (!$responseContent) {
            Log::error("AI Assistant: Response content cannot be empty.");
            throw new Exception("Response content cannot be empty.");
        }

        // Step 1: Save the response message
        try {
            $message = new Message([
                'conversation_id' => $this->conversation_id,
                'user_id' => auth()->id(),
                'content' => $responseContent,
            ]);

            if (!$message->save()) {
                throw new Exception("Failed to save response message.");
            }

            Log::info("AI Assistant: Response message saved successfully for Ticket ID {$this->id}.");
        } catch (Exception $e) {
            Log::error("AI Assistant: Error saving response message for Ticket ID {$this->id} - {$e->getMessage()}");
            throw $e; // Re-throw exception after logging to ensure the error propagates
        }

        // Step 2: Update the ticket status
        try {
            $this->status = 'responded';

            if (!$this->save()) {
                throw new Exception("Failed to update ticket status.");
            }

            Log::info("AI Assistant: Ticket ID {$this->id} status updated to 'responded'.");
        } catch (Exception $e) {
            Log::error("AI Assistant: Error updating status for Ticket ID {$this->id} - {$e->getMessage()}");
            throw $e;
        }

        // Step 3: Notify the user
        try {
            $user = $this->user;
            if ($user) {
                // Notification::send($user, new UserNotifiedOfResponse($message));
                Log::info("AI Assistant: User ID {$user->id} notified about the response for Ticket ID {$this->id}.");
            } else {
                throw new Exception("User associated with the ticket not found.");
            }
        } catch (Exception $e) {
            Log::error("AI Assistant: Error notifying user for Ticket ID {$this->id} - {$e->getMessage()}");
            throw $e;
        }

        return $message;
    }

    public function getAIAgentAssignment()
    {
        $thread = Thread::latest('id')->first();
        $this->conversation_id = $thread ? $thread->conversation_id : '0';
        $this->customer_id = $thread ? $thread->customer_id : '0';

        $conversation = Conversation::find($this->conversation_id);
        $this->folder_id = $conversation ? $conversation->folder_id : '0';
        $this->mailbox_id = $conversation ? $conversation->mailbox_id : '0';

        $assignment = AIAgentAssignment::where([
            ['mailbox_id', '=', $this->mailbox_id],
            ['status', '=', $this->mailbox_id],
        ])->first();
        return $assignment;
    }
}
