<?php

namespace Modules\AIMiddleware\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Carbon\Carbon;
use Modules\AIMiddleware\Models\AIInteractionLog;

class CleanupLogs extends Command
{
    protected $signature = 'aimiddleware:cleanup-logs {--days=90 : Days of logs to keep}';
    protected $description = 'Clean up old AI interaction logs';

    public function handle()
    {
        $days = $this->option('days');
        $cutoff = Carbon::now()->subDays($days);

        $this->info("Cleaning up logs older than {$days} days...");

        try {
            $count = AIInteractionLog::where('created_at', '<', $cutoff)->delete();
            $this->info("{$count} logs deleted successfully.");
            
        } catch (\Exception $e) {
            $this->error("Failed to clean up logs: {$e->getMessage()}");
        }
    }
}
