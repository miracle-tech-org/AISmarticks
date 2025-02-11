<?php

namespace Modules\AIAssistant\Models;

use App\Conversation;
use App\Thread;
use Exception;
use Illuminate\Support\Facades\Log;

class Ticket
{
    public $message;
    public $conversation_id;
    public $folder_id;
    public $mailbox_id;
    public $customer_id;
    public $thread_id;

    public function getAIAgentAssignment()
    {
        $thread = Thread::latest('id')->first();
        $this->conversation_id = $thread ? $thread->conversation_id : '0';
        $this->customer_id = $thread ? $thread->customer_id : '0';
        $this->thread_id = $thread ? $thread->id : '0';

        $conversation = Conversation::find($this->conversation_id);
        $this->folder_id = $conversation ? $conversation->folder_id : '0';
        $this->mailbox_id = $conversation ? $conversation->mailbox_id : '0';

        $assignment = AIAgent::first();
        return $assignment;
    }
}
