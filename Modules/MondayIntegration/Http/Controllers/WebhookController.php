<?php

namespace Modules\MondayIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    protected $security;

    public function __construct(SecurityService $security)
    {
        $this->security = $security;
    }

    public function handle(Request $request)
    {
        try {
            // Verify webhook signature if provided
            $signature = $request->header('X-Monday-Signature');
            if ($signature && !$this->verifySignature($request, $signature)) {
                throw new \Exception('Invalid webhook signature');
            }

            // Process webhook data
            $event = $request->input('event');
            $data = $request->input('data');

            // Handle different event types
            switch ($event['type']) {
                case 'item_created':
                    $this->handleItemCreated($data);
                    break;
                case 'item_updated':
                    $this->handleItemUpdated($data);
                    break;
                default:
                    \Log::info('Unhandled Monday.com webhook event', ['event' => $event]);
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            \Log::error('Monday.com webhook error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    protected function verifySignature(Request $request, $signature)
    {
        $secret = config('monday.security.webhook_secret');
        if (!$secret) {
            return true;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    protected function handleItemCreated($data)
    {
        // Implement item creation logic
        \Log::info('Monday.com item created', $data);
    }

    protected function handleItemUpdated($data)
    {
        // Implement item update logic
        \Log::info('Monday.com item updated', $data);
    }
}
