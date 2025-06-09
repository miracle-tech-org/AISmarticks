<?php

namespace Modules\MobileNotifications\Listeners;

use App\Events\CustomerReplied;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\MobileNotifications\Services\FirebaseNotificationService;
use Modules\MobileNotifications\Models\AgentMobileNotificationPreference; // Added import
use Illuminate\Support\Facades\Log;

class NotifyAgentOnCustomerReply //_implements ShouldQueue // Add if queuing is desired later
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
     * @param  CustomerReplied  $event
     * @return void
     */
    public function handle(CustomerReplied $event)
    {
        $conversation = $event->conversation;
        $agent = $conversation->user;

        if (!$agent) {
            Log::info("NotifyAgentOnCustomerReply: No agent assigned to conversation #{$conversation->id}. Notification not sent.");
            return;
        }

        $notificationTypeKey = 'customer_replied'; // Matches key in notification_types.php

        if (!AgentMobileNotificationPreference::wantsNotification($agent->id, $notificationTypeKey)) {
            Log::info("NotifyAgentOnCustomerReply: Agent {$agent->id} opted out of '{$notificationTypeKey}' for conversation #{$conversation->id}. Notification not sent.");
            return;
        }

        $title = "New Customer Reply";
        $body = "Customer replied to conversation #{$conversation->id}";
        $dataPayload = [
            'conversation_id' => $conversation->id,
            'type' => 'customer_reply' // Consistent with listener purpose
        ];

        Log::info("NotifyAgentOnCustomerReply: Preparing notification for agent {$agent->id} for conversation #{$conversation->id} (type: {$notificationTypeKey})");
        $this->notificationService->prepareAndLogNotification($agent, $title, $body, $dataPayload);
    }
}
