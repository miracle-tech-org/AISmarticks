<?php

namespace Modules\MondayIntegration\Console;

use Illuminate\Console\Command;
use Modules\MondayIntegration\Services\QueueService;
use Illuminate\Support\Facades\Log;

class MonitorQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monday:monitor-queue {--reset : Reset queue metrics}'
                         . ' {--report : Report queue metrics}'
                         . ' {--watch : Watch queue metrics continuously}'
                         . ' {--interval=5 : Interval in seconds when watching}'
                         . ' {--threshold=50 : High load threshold}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor Monday.com integration queue health';

    /**
     * @var QueueService
     */
    protected $queueService;

    /**
     * Create a new command instance.
     *
     * @param QueueService $queueService
     * @return void
     */
    public function __construct(QueueService $queueService)
    {
        parent::__construct();
        $this->queueService = $queueService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->option('reset')) {
            $this->resetQueueMetrics();
            return;
        }

        if ($this->option('report')) {
            $this->reportQueueMetrics();
            return;
        }

        if ($this->option('watch')) {
            $this->watchQueueMetrics();
            return;
        }

        // Default action is to report metrics
        $this->reportQueueMetrics();
    }

    /**
     * Reset queue metrics
     *
     * @return void
     */
    protected function resetQueueMetrics()
    {
        $this->queueService->resetQueueMetrics();
        $this->info('Queue metrics have been reset.');
        Log::info('Monday Integration: Queue metrics reset by command');
    }

    /**
     * Report queue metrics
     *
     * @return void
     */
    protected function reportQueueMetrics()
    {
        $metrics = $this->queueService->getAllQueueMetrics();

        $this->info('Monday.com Integration Queue Metrics:');
        $this->table(
            ['Queue', 'Pending Jobs'],
            [
                ['High Priority', $metrics['high']],
                ['Default Priority', $metrics['default']],
                ['Low Priority', $metrics['low']],
                ['Total', $metrics['total']],
            ]
        );

        $loadStatus = $metrics['high_load'] ? 'HIGH LOAD' : 'Normal';
        $this->info("Load Status: {$loadStatus}");

        Log::info('Monday Integration: Queue metrics reported', $metrics);
    }

    /**
     * Watch queue metrics continuously
     *
     * @return void
     */
    protected function watchQueueMetrics()
    {
        $interval = (int)$this->option('interval');
        $threshold = (int)$this->option('threshold');

        $this->info("Watching queue metrics every {$interval} seconds. Press Ctrl+C to stop.");
        
        // Set a maximum number of iterations to prevent infinite loop warnings
        // In practice, this will run until manually stopped with Ctrl+C
        $maxIterations = 1000;
        $iteration = 0;

        while ($iteration < $maxIterations) {
            $this->output->write("\033[2J\033[1;1H"); // Clear screen
            $this->output->writeln("Monday.com Integration Queue Monitor");
            $this->output->writeln("Updated at: " . date('Y-m-d H:i:s'));
            $this->output->writeln("");

            $metrics = $this->queueService->getAllQueueMetrics();

            $this->table(
                ['Queue', 'Pending Jobs'],
                [
                    ['High Priority', $metrics['high']],
                    ['Default Priority', $metrics['default']],
                    ['Low Priority', $metrics['low']],
                    ['Total', $metrics['total']],
                ]
            );

            $loadStatus = $metrics['high_load'] ? 'HIGH LOAD' : 'Normal';
            $this->info("Load Status: {$loadStatus}");

            if ($metrics['high_load']) {
                $this->warn("Warning: System is under high load! Some jobs may be delayed.");
            }
            
            $iteration++;
            sleep($interval);
        }
        
        $this->info("Maximum monitoring time reached. Run the command again to continue monitoring.");
    }
}