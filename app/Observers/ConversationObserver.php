<?php

namespace App\Observers;

use App\Conversation;
use App\Thread;
use GuzzleHttp\Client;

class ConversationObserver
{
    /**
     * On create before saving.
     *
     * @param Conversation $conversation
     */
    public function creating(Conversation $conversation)
    {
        if ($conversation->source_via == Conversation::PERSON_USER) {
            $conversation->read_by_user = true;
        }
    }

    /**
     * On create.
     *
     * @param Conversation $conversation
     */
    public function created(Conversation $conversation)
    {
        // Better to do it manually
        //$conversation->mailbox->updateFoldersCounters();
    }

    /**
     * On conversation delete.
     *
     * @param Conversation $conversation
     */
    public function deleting(Conversation $conversation)
    {
        $conversation->threads()->delete();
        $conversation->followers()->delete();

        \Eventy::action('conversation.deleting', $conversation);
    }

    public function updated(Conversation $conversation)
    {
        \Eventy::action('conversation.updated', $conversation);

        $originalStatus = $conversation->getOriginal('status');
        $newStatus = $conversation->status;

        if ($originalStatus !== $newStatus && $newStatus == 3) {
            if(\Module::isActive('mondayintegration')) {
                try {
                    $settings = \Modules\MondayIntegration\Models\MondaySettings::all()->pluck('value', 'key')->toArray();
                    $push_on_conversation_closed = !empty($settings['push_on_conversation_closed']);
                    $push_on_conversation_fully_closed = !empty($settings['push_on_conversation_fully_closed']);

                    if($push_on_conversation_closed || $push_on_conversation_fully_closed) {
                        $thread = Thread::where('conversation_id', $conversation->id)->orderByDesc('id')->first();
                        $requestData = [];
                        if($request->status == "3" && $push_on_conversation_fully_closed) {
                            $requestData = [
                                'conversation_id' => $thread->conversation_id,
                                'thread_id' => $thread->id,
                                'upload' => 'yes',
                            ];
                        } else {
                            $requestData = [
                                'conversation_id' => $thread->conversation_id,
                                'thread_id' => $thread->id,
                            ];
                        }

                        $client = new Client();
                        $client->request('POST', route('monday.send.pushboard.item'), [
                            'http_errors' => false,
                            'json' => $requestData
                        ]);
                    }
                } catch (\Exception $e) {
                }
            }
        }
    }
}