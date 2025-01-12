<?php

namespace Modules\AIMiddleware\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Carbon\Carbon;
use Modules\AIMiddleware\Models\AIInteractionLog;

class GenerateAnalytics extends Command
{
    protected $signature = 'my-improvements:generate-analytics';
    protected $description = 'Generate analytics reports for My Improvements module';

    /**
     * Execute the command
     */
    public function handle()
    {
        $this->info('Generating analytics reports...');

        try {
            // Generate daily stats
            $this->generateDailyStats();

            // Generate monthly report
            $this->generateMonthlyReport();

            // Generate performance metrics
            $this->generatePerformanceMetrics();

            $this->info('Analytics reports generated successfully.');
            
        } catch (\Exception $e) {
            $this->error("Failed to generate analytics: {$e->getMessage()}");
        }
    }

    /**
     * Generate daily statistics
     */
    protected function generateDailyStats()
    {
        $this->info('Generating daily statistics...');
        
        $stats = AIInteractionLog::whereDate('created_at', Carbon::today())
            ->selectRaw('
                COUNT(*) as total_interactions,
                AVG(response_time) as avg_response_time,
                SUM(tokens_used) as total_tokens,
                AVG(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) * 100 as success_rate
            ')
            ->first();

        // Store stats in cache for dashboard
        \Cache::put('my_improvements_daily_stats', $stats, now()->addHours(1));
    }

    /**
     * Generate monthly report
     */
    protected function generateMonthlyReport()
    {
        $this->info('Generating monthly report...');

        $monthStart = now()->startOfMonth();
        $report = AIInteractionLog::where('created_at', '>=', $monthStart)
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total_interactions,
                AVG(response_time) as avg_response_time,
                SUM(tokens_used) as total_tokens,
                AVG(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) * 100 as success_rate
            ')
            ->groupBy('date')
            ->get();

        // Store report data
        \Cache::put('my_improvements_monthly_report', $report, now()->addDay());
    }

    /**
     * Generate performance metrics
     */
    protected function generatePerformanceMetrics()
    {
        $this->info('Generating performance metrics...');

        $metrics = [
            'response_times' => $this->calculateResponseTimeMetrics(),
            'success_rates' => $this->calculateSuccessRateMetrics(),
            'token_usage' => $this->calculateTokenUsageMetrics(),
        ];

        // Store metrics
        \Cache::put('my_improvements_performance_metrics', $metrics, now()->addHours(6));
    }

    /**
     * Calculate response time metrics
     */
    protected function calculateResponseTimeMetrics()
    {
        return AIInteractionLog::selectRaw('
            DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour,
            AVG(response_time) as avg_response_time,
            MIN(response_time) as min_response_time,
            MAX(response_time) as max_response_time
        ')
        ->where('created_at', '>=', now()->subDays(7))
        ->groupBy('hour')
        ->get();
    }

    /**
     * Calculate success rate metrics
     */
    protected function calculateSuccessRateMetrics()
    {
        return AIInteractionLog::selectRaw('
            DATE(created_at) as date,
            COUNT(*) as total,
            SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as successful,
            (SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as success_rate
        ')
        ->where('created_at', '>=', now()->subDays(30))
        ->groupBy('date')
        ->get();
    }

    /**
     * Calculate token usage metrics
     */
    protected function calculateTokenUsageMetrics()
    {
        return AIInteractionLog::selectRaw('
            DATE(created_at) as date,
            SUM(tokens_used) as total_tokens,
            AVG(tokens_used) as avg_tokens,
            COUNT(*) as total_requests
        ')
        ->where('created_at', '>=', now()->subDays(30))
        ->groupBy('date')
        ->get();
    }
}
