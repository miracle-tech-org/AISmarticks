<?php

namespace Modules\AIAssistant\Models;

use App\Conversation;
use App\Thread;
use Exception;
use Illuminate\Support\Facades\Log;
use DB;

class Ticket
{
    public $message;
    public $conversation_id;
    public $folder_id;
    public $mailbox_id;
    public $customer_id;
    public $thread_id;
    public $userId;
    public $email;

    public function getAIAgentAssignment()
    {
        $thread = Thread::latest('id')->first();
        $this->conversation_id = $thread ? $thread->conversation_id : '0';
        $this->customer_id = $thread ? $thread->customer_id : '0';
        $this->thread_id = $thread ? $thread->id : '0';

        $conversation = Conversation::find($this->conversation_id);
        $this->folder_id = $conversation ? $conversation->folder_id : '0';
        $this->mailbox_id = $conversation ? $conversation->mailbox_id : '0';

        $data = DB::table('mailbox_user as a')
            ->leftJoin('users as b', 'a.user_id', '=', 'b.id')
            ->where('b.bot', 1)
            ->where('a.mailbox_id', $this->mailbox_id)
            ->select('b.id', 'b.email', 'b.bot')
            ->first();

        $this->userId = $data->id;
        $this->email = $data->email;

        $assignment = AIAgent::where('user_id', $this->userId)
            ->first();

        return $assignment;
    }
}
