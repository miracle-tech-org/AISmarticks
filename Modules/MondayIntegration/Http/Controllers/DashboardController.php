<?php

namespace Modules\MondayIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\MondayIntegration\Models\MondaySyncLog;
use Modules\MondayIntegration\Models\MondayWebhook;
use Modules\MondayIntegration\Models\MondayConversationMapping;
use Modules\MondayIntegration\Models\MondayCallerIdMapping;

use Modules\MondayIntegration\Services\MondayApiService;
use Modules\MondayIntegration\Services\SecurityService;
use Modules\MondayIntegration\Services\QueueService;
use App\Mailbox;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $apiService;
    protected $securityService;
    protected $queueService;

    public function __construct(MondayApiService $apiService, QueueService $queueService, SecurityService $securityService)
    {
        $this->apiService = $apiService;
        $this->queueService = $queueService;
        $this->securityService = $securityService;
    }

    public function index($mailbox_id)
    {
        // Check connection status
        $connectionStatus = $this->checkConnectionStatus();
        
        // Get mapping statistics
        $mappingStats = $this->getMappingStatistics();
        
        // Get sync statistics
        $syncStats = $this->getSyncStatistics();
        
        // Get queue statistics
        $queueStats = $this->queueService->getAllQueueMetrics();
        
        // Get recent activity
        $recentActivity = $this->getRecentActivity();
        
        // Get troubleshooting information
        $troubleshootingInfo = $this->getTroubleshootingInfo();

        $mailbox = Mailbox::findOrFail($mailbox_id);

        return view('mondayintegration::dashboard', compact(
            'connectionStatus',
            'mappingStats',
            'syncStats',
            'queueStats',
            'recentActivity',
            'troubleshootingInfo',
            'mailbox',
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

    public function syncNow()
    {
        try {
            // Get sync options from request
            $options = [
                'scope' => $request->input('scope', 'all'),
                'limit' => (int)$request->input('limit', 50),
                'status' => $request->input('status'),
                'board_id' => $request->input('board_id')
            ];
            
            // Queue the sync job
            $this->queueService->queueSyncJob($options, 'high');
            
            return response()->json([
                'success' => true,
                'message' => __('Sync job has been queued and will run shortly.')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: :message', ['message' => $e->getMessage()])
            ], 500);
        }
    }

    public function status()
    {

    }

    public function retry()
    {

    }

    protected function checkConnectionStatus()
    {
        $status = [
            'is_connected' => false,
            'api_version' => null,
            'last_successful_connection' => null,
            'error_message' => null,
            'token_status' => 'invalid',
        ];

        try {
            // Test the connection
            $result = $this->apiService->testConnection();
            
            if ($result) {
                $status['is_connected'] = true;
                $status['token_status'] = 'valid';
                
                // Get API version
                $versionInfo = $this->apiService->getApiVersion();
                if ($versionInfo && isset($versionInfo['version']['value'])) {
                    $status['api_version'] = $versionInfo['version']['value'].' '.$versionInfo['version']['kind'];
                }
                // Update last successful connection
                $this->securityService->setOption('last_successful_connection', now()->toDateTimeString());
            }
            
            // Get last successful connection time
            $lastConnection = $this->securityService->getOption('last_successful_connection');
            if ($lastConnection) {
                $status['last_successful_connection'] = Carbon::parse($lastConnection);
            }
        } catch (\Exception $e) {
            $status['error_message'] = $e->getMessage();
        }
        
        return $status;
    }

    protected function getMappingStatistics()
    {
        return [
            'conversation_mappings' => [
                'total' => MondayConversationMapping::count(),
                'by_board' => MondayConversationMapping::select('board_id', \DB::raw('count(*) as count'))
                    ->groupBy(['board_id'])
                    ->get()
                    ->pluck('count', 'board_id')
                    ->toArray(),
                'last_created' => MondayConversationMapping::latest()->first(),
            ],
            'caller_id_mappings' => [
                'total' => MondayCallerIdMapping::count(),
                'by_board' => MondayCallerIdMapping::select('board_id', \DB::raw('count(*) as count'))
                    ->groupBy(['board_id'])
                    ->get()
                    ->pluck('count', 'board_id')
                    ->toArray(),
                'by_channel' => MondayCallerIdMapping::select('channel', \DB::raw('count(*) as count'))
                    ->groupBy(['channel'])
                    ->get()
                    ->pluck('count', 'channel')
                    ->toArray(),
            ],
            'field_mappings' => [
                'conversation' => count($this->securityService->getOption('field_mappings_conversation', [])),
                'customer' => count($this->securityService->getOption('field_mappings_customer', [])),
                'thread' => count($this->securityService->getOption('field_mappings_thread', [])),
            ],
        ];
    }

    protected function getSyncStatistics()
    {
        $lastSync = MondaySyncLog::where('status', 'completed')
            ->latest()
            ->first();

        $totalSynced = MondaySyncLog::where('status', 'completed')->count();
        $pendingSyncs = MondaySyncLog::whereIn('status', ['pending', 'processing'])->count();
        $failedSyncs = MondaySyncLog::where('status', 'failed')->count();
        
        // Get statistics by action type
        $byActionType = MondaySyncLog::select('action_type', \DB::raw('count(*) as count'))
            ->groupBy(['action_type'])
            ->get()
            ->pluck('count', 'action_type')
            ->toArray();
            
        // Get statistics by source type
        $bySourceType = MondaySyncLog::select('source_type', \DB::raw('count(*) as count'))
            ->groupBy(['source_type'])
            ->get()
            ->pluck('count', 'source_type')
            ->toArray();
            
        // Get daily sync counts for the last 7 days
        $dailySyncCounts = MondaySyncLog::select(
                \DB::raw('DATE(created_at) as date'),
                \DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy([\DB::raw('DATE(created_at)')])
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        return [
            'last_sync' => $lastSync,
            'total_synced' => $totalSynced,
            'pending_syncs' => $pendingSyncs,
            'failed_syncs' => $failedSyncs,
            'by_action_type' => $byActionType,
            'by_source_type' => $bySourceType,
            'daily_sync_counts' => $dailySyncCounts,
        ];
    }

    protected function getRecentActivity()
    {
        return MondaySyncLog::with(['conversation'])
            ->latest()
            ->take(15)
            ->get()
            ->map(function ($log) {
                $log->details = $this->formatLogDetails($log);
                return $log;
            });
    }

    protected function getTroubleshootingInfo()
    {
        $issues = [];
        
        // Check for API connection issues
        if (!$this->apiService->testConnection()) {
            $issues[] = [
                'type' => 'error',
                'message' => __('Cannot connect to Monday.com API. Check your API token.'),
                'solution' => __('Go to Settings and verify that your API token is correct and has the necessary permissions.'),
            ];
        }
        
        // Check for webhook configuration issues
        $webhooks = MondayWebhook::all();
        if ($webhooks->isEmpty()) {
            $issues[] = [
                'type' => 'warning',
                'message' => __('No webhooks are configured. Real-time updates will not work.'),
                'solution' => __('Set up webhooks in Monday.com to enable real-time updates.'),
            ];
        } else {
            $inactiveWebhooks = $webhooks->where('is_active', false)->count();
            if ($inactiveWebhooks > 0) {
                $issues[] = [
                    'type' => 'warning',
                    'message' => __(':count webhooks are inactive.', ['count' => $inactiveWebhooks]),
                    'solution' => __('Check webhook configurations in Monday.com to ensure they are properly set up.'),
                ];
            }
        }
        
        // Check for field mapping issues
        $conversationMappings = json_decode($this->securityService->getOption('field_mappings_conversation', '[]'), true);
        if (empty($conversationMappings)) {
            $issues[] = [
                'type' => 'warning',
                'message' => __('No conversation field mappings are configured.'),
                'solution' => __('Go to Field Mappings and configure mappings for conversation fields.'),
            ];
        }
        
        // Check for high queue load
        $queueMetrics = $this->queueService->getAllQueueMetrics();
        if ($queueMetrics['high_load']) {
            $issues[] = [
                'type' => 'warning',
                'message' => __('Queue is under high load with :count pending jobs.', ['count' => $queueMetrics['total']]),
                'solution' => __('Consider increasing your queue processing capacity or adjusting the high load threshold in Queue Settings.'),
            ];
        }
        
        // Check for recent failed syncs
        $recentFailures = MondaySyncLog::where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
        if ($recentFailures > 5) {
            $issues[] = [
                'type' => 'error',
                'message' => __(':count sync operations have failed in the last 24 hours.', ['count' => $recentFailures]),
                'solution' => __('Check the logs for error details and verify your Monday.com API permissions.'),
            ];
        }
        
        // Check for dual-board configuration issues
        $boardIds = array_merge(
            MondayConversationMapping::distinct()->pluck('board_id')->toArray(),
            MondayCallerIdMapping::distinct()->pluck('board_id')->toArray()
        );
        $uniqueBoardIds = array_unique($boardIds);
        
        if (count($uniqueBoardIds) < 1) {
            $issues[] = [
                'type' => 'warning',
                'message' => __('No board configurations detected.'),
                'solution' => __('Configure at least one board in the settings to enable proper integration.'),
            ];
        }
        
        return [
            'issues' => $issues,
            'system_info' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'module_version' => config('mondayintegration.version', '1.0.0'),
            ],
        ];
    }
}