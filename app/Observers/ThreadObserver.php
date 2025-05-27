<?php

namespace App\Observers;

use App\Conversation;
use App\Thread;
use GuzzleHttp\Client;

class ThreadObserver
{
    /**
     * Thread created.
     *
     * @param Thread $thread
     */
    public function created(Thread $thread)
    {
        // Update data in conversation
        $conversation = $thread->conversation;

        if (!$conversation) {
            return;
        }

        // Fetch date & time setting.
        $use_mail_date_on_fetching = config('app.use_mail_date_on_fetching');

        if ($use_mail_date_on_fetching) {
            $now = $thread->created_at;
        }else{
            $now = date('Y-m-d H:i:s');
        }
        
        if (!in_array($thread->type, [Thread::TYPE_LINEITEM, Thread::TYPE_NOTE]) && $thread->state == Thread::STATE_PUBLISHED) {
            $conversation->threads_count++;
        }
        if (!in_array($thread->type, [Thread::TYPE_CUSTOMER])) {
            $conversation->user_updated_at = $now;
        }
        
        if ((in_array($thread->type, [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE]) 
            || ($conversation->isPhone() && in_array($thread->type, [Thread::TYPE_NOTE])))
            && $thread->state == Thread::STATE_PUBLISHED
        ) {
            // $conversation->cc = $thread->cc;
            // $conversation->bcc = $thread->bcc;
            $conversation->last_reply_at = $now;
            $conversation->last_reply_from = $thread->source_via;
        }
        if ($conversation->source_via == Conversation::PERSON_CUSTOMER) {
            $conversation->read_by_user = false;
        }

        // Update preview.
        if (in_array($thread->type, [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE, Thread::TYPE_NOTE])
            && $thread->state == Thread::STATE_PUBLISHED
            && !$thread->isForward()
            // Otherwise preview is not set when conversation is created
            // outside of the web interface.
            //&& ($conversation->threads_count > 1 || $thread->type == Thread::TYPE_NOTE)
        ) {
            $conversation->setPreview($thread->body);
        }

        $conversation->save();

        // $is_new_conversation = false;
        // if ($conversation->threads_count == 0 
        //     && in_array($thread->type, [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE, Thread::TYPE_NOTE])
        //     && $thread->state == Thread::STATE_PUBLISHED
        // ) {
        //     $is_new_conversation = true;
        // }

        // User threads are created as drafts first.
        // if ($thread->state == Thread::STATE_PUBLISHED) {
        //     \Eventy::action('thread.created', $thread);
        // }

        // Real time for user notifications is sent using events.
        if ($thread->type == Thread::TYPE_CUSTOMER 
            || ($thread->type == Thread::TYPE_MESSAGE && $thread->state == Thread::STATE_DRAFT)
        ) {
            Conversation::refreshConversations($conversation, $thread);
        }

        if(\Module::isActive('mondayintegration')) {
            try {
                $newStatus = $conversation->status;
                if($newStatus != 3) {
                    $settings = \Modules\MondayIntegration\Models\MondaySettings::all()->pluck('value', 'key')->toArray();
                    $push_on_conversation_created = !empty($settings['push_on_conversation_created']);
                    $push_on_conversation_updated = !empty($settings['push_on_conversation_updated']);
    
                    if($push_on_conversation_created || $push_on_conversation_updated) {
                        $requestData = [
                            'conversation_id' => $conversation->id,
                            'thread_id' => $thread->id
                        ];
                        $client = new Client();
                        $client->request('POST', route('monday.send.pushboard.item'), [
                            'http_errors' => false,
                            'json' => $requestData
                        ]);
                    }
                }

            } catch (\Exception $e) {
            }
        }

        \Eventy::action('thread.created', $thread);
    }

    public function deleting(Thread $thread)
    {
        \Eventy::action('thread.deleting', $thread);
    }

    public function updated(Thread $thread)
    {
        \Eventy::action('thread.updated', $thread);
    }
}
