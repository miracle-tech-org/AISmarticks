<?php

use App\Report;
use Carbon\Carbon;
use App\Option;

/**
 * Report Service that integrates with FreeScout's native reporting system
 */
class ReportService extends Report
{
    /**
     * Initialize report parameters
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->title = __('AI Assistant Report');
        $this->submodule = 'my_improvements';
    }

    /**
     * Define report filters
     */
    protected function initFilters(): void
    {
        $this->filters = [
            'date_from' => [
                'type' => self::FILTER_DATE,
                'default' => Carbon::now()->subDays(30)->format('Y-m-d'),
                'label' => __('Date From'),
            ],
            'date_to' => [
                'type' => self::FILTER_DATE,
                'default' => Carbon::now()->format('Y-m-d'),
                'label' => __('Date To'),
            ],
            'ai_agent' => [
                'type' => self::FILTER_SELECT,
                'multiple' => true,
                'options' => $this->getAIAgentOptions(),
                'label' => __('AI Agent'),
            ],
            'response_time' => [
                'type' => self::FILTER_SELECT,
                'options' => [
                    'fast' => __('Fast (< 1s)'),
                    'medium' => __('Medium (1-3s)'),
                    'slow' => __('Slow (> 3s)')
                ],
                'label' => __('Response Time'),
            ],
            'success_rate' => [
                'type' => self::FILTER_SELECT,
                'options' => [
                    'high' => __('High (> 80%)'),
                    'medium' => __('Medium (50-80%)'),
                    'low' => __('Low (< 50%)')
                ],
                'label' => __('Success Rate'),
            ]
        ];
    }

    /**
     * Define report tables
     */
    protected function initTables(): void
    {
        // Overview table
        $this->tables[] = [
            'name' => 'overview',
            'title' => __('Overview'),
            'columns' => [
                'date' => [
                    'title' => __('Date'),
                    'type' => self::COL_TYPE_DATE,
                    'sortable' => true,
                ],
                'interactions' => [
                    'title' => __('Interactions'),
                    'type' => self::COL_TYPE_INT,
                    'sortable' => true,
                ],
                'avg_response_time' => [
                    'title' => __('Avg Response Time'),
                    'type' => self::COL_TYPE_FLOAT,
                    'sortable' => true,
                ],
                'success_rate' => [
                    'title' => __('Success Rate'),
                    'type' => self::COL_TYPE_PERCENT,
                    'sortable' => true,
                ],
                'token_usage' => [
                    'title' => __('Token Usage'),
                    'type' => self::COL_TYPE_INT,
                    'sortable' => true,
                ]
            ]
        ];

        // Agent performance table
        $this->tables[] = [
            'name' => 'agent_performance',
            'title' => __('Agent Performance'),
            'columns' => [
                'agent_name' => [
                    'title' => __('Agent Name'),
                    'type' => self::COL_TYPE_STRING,
                    'sortable' => true,
                ],
                'total_interactions' => [
                    'title' => __('Total Interactions'),
                    'type' => self::COL_TYPE_INT,
                    'sortable' => true,
                ],
                'avg_response_time' => [
                    'title' => __('Avg Response Time'),
                    'type' => self::COL_TYPE_FLOAT,
                    'sortable' => true,
                ],
                'success_rate' => [
                    'title' => __('Success Rate'),
                    'type' => self::COL_TYPE_PERCENT,
                    'sortable' => true,
                ],
                'total_tokens' => [
                    'title' => __('Total Tokens'),
                    'type' => self::COL_TYPE_INT,
                    'sortable' => true,
                ]
            ]
        ];
    }

    /**
     * Generate report data
     */
    protected function generateData(): void
    {
        // Get filtered data from database
        $logs = $this->getFilteredLogs();

        // Generate overview data
        $this->tables['overview']['rows'] = $this->generateOverviewData($logs);
        $this->tables['agent_performance']['rows'] = $this->generateAgentPerformanceData($logs);

        // Add totals
        $this->addTotals();
    }

    /**
     * Generate overview data
     */
    protected function generateOverviewData($logs): array
    {
        $grouped = $logs->groupBy(function($log) {
            return $log->created_at->format('Y-m-d');
        });

        return $grouped->map(function($dailyLogs) {
            return [
                'date' => $dailyLogs->first()->created_at->format('Y-m-d'),
                'interactions' => $dailyLogs->count(),
                'avg_response_time' => $dailyLogs->avg('response_time'),
                'success_rate' => $this->calculateSuccessRate($dailyLogs),
                'token_usage' => $dailyLogs->sum('tokens_used')
            ];
        })->values()->toArray();
    }

    /**
     * Generate agent performance data
     */
    protected function generateAgentPerformanceData($logs): array
    {
        $grouped = $logs->groupBy('agent_id');

        return $grouped->map(function($agentLogs) {
            $agent = $agentLogs->first()->agent;
            return [
                'agent_name' => $agent->name,
                'total_interactions' => $agentLogs->count(),
                'avg_response_time' => $agentLogs->avg('response_time'),
                'success_rate' => $this->calculateSuccessRate($agentLogs),
                'total_tokens' => $agentLogs->sum('tokens_used')
            ];
        })->values()->toArray();
    }

    /**
     * Get filtered logs based on report filters
     */
    protected function getFilteredLogs()
    {
        $query = \App\Modules\AIMiddleware\Models\AIInteractionLog::query()
            ->with('agent')
            ->whereBetween('created_at', [
                Carbon::parse($this->filter_data['date_from']),
                Carbon::parse($this->filter_data['date_to'])->endOfDay()
            ]);

        // Apply AI agent filter
        if (!empty($this->filter_data['ai_agent'])) {
            $query->whereIn('agent_id', $this->filter_data['ai_agent']);
        }

        // Apply response time filter
        if (!empty($this->filter_data['response_time'])) {
            switch ($this->filter_data['response_time']) {
                case 'fast':
                    $query->where('response_time', '<', 1000);
                    break;
                case 'medium':
                    $query->whereBetween('response_time', [1000, 3000]);
                    break;
                case 'slow':
                    $query->where('response_time', '>', 3000);
                    break;
            }
        }

        return $query->get();
    }

    /**
     * Calculate success rate for a collection of logs
     */
    protected function calculateSuccessRate($logs): float
    {
        $total = $logs->count();
        if (!$total) return 0;

        $successful = $logs->where('rating', '>=', 4)->count();
        return round(($successful / $total) * 100, 2);
    }

    /**
     * Get AI agent options for filter
     */
    protected function getAIAgentOptions(): array
    {
        return \App\Modules\AIMiddleware\Models\AIAgent::pluck('name', 'id')
            ->toArray();
    }

    /**
     * Add totals to tables
     */
    protected function addTotals(): void
    {
        // Add totals to overview table
        $this->tables['overview']['totals'] = [
            'interactions' => array_sum(array_column($this->tables['overview']['rows'], 'interactions')),
            'token_usage' => array_sum(array_column($this->tables['overview']['rows'], 'token_usage')),
            'avg_response_time' => $this->calculateAverageValue(
                $this->tables['overview']['rows'],
                'avg_response_time'
            ),
            'success_rate' => $this->calculateAverageValue(
                $this->tables['overview']['rows'],
                'success_rate'
            )
        ];

        // Add totals to agent performance table
        $this->tables['agent_performance']['totals'] = [
            'total_interactions' => array_sum(array_column($this->tables['agent_performance']['rows'], 'total_interactions')),
            'total_tokens' => array_sum(array_column($this->tables['agent_performance']['rows'], 'total_tokens')),
            'avg_response_time' => $this->calculateAverageValue(
                $this->tables['agent_performance']['rows'],
                'avg_response_time'
            ),
            'success_rate' => $this->calculateAverageValue(
                $this->tables['agent_performance']['rows'],
                'success_rate'
            )
        ];
    }

    /**
     * Calculate average value for a column
     */
    protected function calculateAverageValue(array $rows, string $column): float
    {
        if (empty($rows)) return 0;
        return round(array_sum(array_column($rows, $column)) / count($rows), 2);
    }
}