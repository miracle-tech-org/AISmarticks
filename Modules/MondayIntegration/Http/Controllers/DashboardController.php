<?php

namespace Modules\MondayIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\MondayIntegration\Models\MondaySyncLog;
use Modules\MondayIntegration\Models\MondayWebhook;
use Modules\MondayIntegration\Services\MondayApiService;

class DashboardController extends Controller
{
    protected $apiService;

    public function __construct(MondayApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function index()
    {
        // Check connection status
        $isConnected = $this->apiService->testConnection();
        
        // Get sync statistics
        $lastSync = MondaySyncLog::where('status', 'completed')
            ->latest()
            ->first();

        $totalSynced = MondaySyncLog::where('status', 'completed')->count();
        $pendingSyncs = MondaySyncLog::whereIn('status', ['pending', 'processing'])->count();
        $failedSyncs = MondaySyncLog::where('status', 'failed')->count();

        // Get webhook statistics
        $activeWebhooks = MondayWebhook::where('is_active', true)->count();
        $recentWebhookEvents = MondaySyncLog::where('source_type', 'webhook')
            ->where('created_at', '>=', now()->subDay())
            ->count();
        $failedWebhookEvents = MondaySyncLog::where('source_type', 'webhook')
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        // Get recent activity
        $recentActivity = MondaySyncLog::with(['conversation'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($log) {
                $log->details = $this->formatLogDetails($log);
                return $log;
            });

        return view('mondayintegration::dashboard', compact(
            'isConnected',
            'lastSync',
            'totalSynced',
            'pendingSyncs',
            'failedSyncs',
            'activeWebhooks',
            'recentWebhookEvents',
            'failedWebhookEvents',
            'recentActivity'
        ));
    }

    protected function formatLogDetails($log)
    {
        switch ($log->action_type) {
            case 'create':
                return __('Created item #:item for conversation #:conv', [
                    'item' => $log->monday_item_id,
                    'conv' => $log->conversation_id
                ]);

            case 'update':
                return __('Updated item #:item', [
                    'item' => $log->monday_item_id
                ]);

            case 'webhook':
                $data = $log->request_data;
                return __(':event on board :board', [
                    'event' => $data['event_type'] ?? 'Unknown event',
                    'board' => $data['board_name'] ?? 'Unknown board'
                ]);

            default:
                return __('Unknown action');
        }
    }
}
