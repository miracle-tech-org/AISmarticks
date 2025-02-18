<?php

namespace Modules\MondayIntegration\Services;

use Illuminate\Http\Request;
use Modules\MondayIntegration\Models\MondayWebhook;
use App\Events\SystemAlert;

class WebhookService
{
    protected $security;

    public function __construct(SecurityService $security)
    {
        $this->security = $security;
    }

    public function verifySignature(Request $request)
    {
        $signature = $request->header('X-Monday-Signature');
        $payload = $request->getContent();
        $secret = config('monday.security.webhook_secret');

        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    public function processWebhook($event, $data)
    {
        try {
            switch ($event['type']) {
                case 'item_created':
                    return $this->handleItemCreated($data);
                
                case 'item_updated':
                    return $this->handleItemUpdated($data);
                
                case 'status_changed':
                    return $this->handleStatusChanged($data);
                
                default:
                    throw new \Exception("Unknown webhook event type: {$event['type']}");
            }
        } catch (\Exception $e) {
            $this->logWebhookError($e, $event, $data);
            throw $e;
        }
    }

    protected function handleItemCreated($data)
    {
        $boardId = $data['board_id'];
        $itemId = $data['item_id'];

        // Update local records if needed
        $this->updateLocalRecords('create', $boardId, $itemId, $data);

        event(new SystemAlert(
            'Monday.com Item Created',
            'info',
            [
                'board_id' => $boardId,
                'item_id' => $itemId,
                'data' => $data
            ]
        ));
    }

    protected function handleItemUpdated($data)
    {
        $boardId = $data['board_id'];
        $itemId = $data['item_id'];
        $changes = $data['changes'] ?? [];

        // Process updates in local system
        $this->updateLocalRecords('update', $boardId, $itemId, $changes);

        event(new SystemAlert(
            'Monday.com Item Updated',
            'info',
            [
                'board_id' => $boardId,
                'item_id' => $itemId,
                'changes' => $changes
            ]
        ));
    }

    protected function handleStatusChanged($data)
    {
        $boardId = $data['board_id'];
        $itemId = $data['item_id'];
        $newStatus = $data['status'];

        // Update conversation status if linked
        $this->updateConversationStatus($itemId, $newStatus);

        event(new SystemAlert(
            'Monday.com Status Changed',
            'info',
            [
                'board_id' => $boardId,
                'item_id' => $itemId,
                'new_status' => $newStatus
            ]
        ));
    }

    protected function updateLocalRecords($action, $boardId, $itemId, $data)
    {
        try {
            \DB::transaction(function() use ($action, $boardId, $itemId, $data) {
                // Update sync logs
                MondaySyncLog::create([
                    'action_type' => $action,
                    'source_type' => 'webhook',
                    'monday_board_id' => $boardId,
                    'monday_item_id' => $itemId,
                    'status' => 'completed',
                    'request_data' => $data
                ]);

                // Update related conversations if any
                $this->updateRelatedConversations($itemId, $data);
            });
        } catch (\Exception $e) {
            $this->logWebhookError($e, compact('action', 'boardId', 'itemId'), $data);
            throw $e;
        }
    }

    protected function updateConversationStatus($itemId, $newStatus)
    {
        $conversation = \App\Conversation::where('monday_item_id', $itemId)->first();
        
        if ($conversation) {
            $statusMapping = config('monday.status_mapping', []);
            $freescoutStatus = $statusMapping[$newStatus] ?? null;

            if ($freescoutStatus) {
                $conversation->status = $freescoutStatus;
                $conversation->save();

                $conversation->addNote(
                    __('Status updated from Monday.com: :status', ['status' => $newStatus]),
                    false,
                    \App\Note::TYPE_SYSTEM
                );
            }
        }
    }

    protected function logWebhookError(\Exception $e, $event, $data)
    {
        event(new SystemAlert(
            'Monday.com Webhook Error',
            'error',
            [
                'error' => $e->getMessage(),
                'event' => $event,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]
        ));
    }
}
