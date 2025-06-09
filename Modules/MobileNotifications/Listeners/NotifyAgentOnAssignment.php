<?php

namespace Modules\MobileNotifications\Listeners;

use App\Events\ConversationUserChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\MobileNotifications\Services\FirebaseNotificationService;
use Modules\MobileNotifications\Models\AgentMobileNotificationPreference; // Added import
use Illuminate\Support\Facades\Log;

class NotifyAgentOnAssignment //_implements ShouldQueue // Add if queuing is desired later
{
    protected $notificationService;

    /**
     * Create the event listener.
     *
     * @param  FirebaseNotificationService  $notificationService
     * @return void
     */
    public function __construct(FirebaseNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     *
     * @param  ConversationUserChanged  $event
     * @return void
     */
    public function handle(ConversationUserChanged $event)
    {
        $conversation = $event->conversation;
        $newAgent = $event->user;

        if (!$newAgent) {
            // This case should ideally not happen if a conversation is assigned (event implies a user).
            Log::info("NotifyAgentOnAssignment: No new agent specified for conversation #{$conversation->id}. Notification not sent.");
            return;
        }

        $notificationTypeKey = 'conversation_assigned'; // Matches key in notification_types.php

        if (!AgentMobileNotificationPreference::wantsNotification($newAgent->id, $notificationTypeKey)) {
            Log::info("NotifyAgentOnAssignment: Agent {$newAgent->id} opted out of '{$notificationTypeKey}' for conversation #{$conversation->id}. Notification not sent.");
            return;
        }

        $title = "Conversation Assigned";
        $body = "You have been assigned to conversation #{$conversation->id}";
        $dataPayload = [
            'conversation_id' => $conversation->id,
            'type' => 'conversation_assigned' // Consistent with listener purpose
        ];

        Log::info("NotifyAgentOnAssignment: Preparing notification for agent {$newAgent->id} for conversation #{$conversation->id} (type: {$notificationTypeKey})");
        $this->notificationService->prepareAndLogNotification($newAgent, $title, $body, $dataPayload);
    }
}
