<?php

namespace Modules\MondayIntegration\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Modules\MondayIntegration\Jobs\SyncConversationJob;
use Modules\MondayIntegration\Jobs\SyncThreadsJob;
use Modules\MondayIntegration\Jobs\SyncStatusesJob;
use Modules\MondayIntegration\Jobs\ManualSyncJob;
use App\Conversation;
use App\Thread;

class QueueService
{
    /**
     * Maximum number of items to process in a batch
     *
     * @var int
     */
    protected $batchSize = 10;

    /**
     * Queue names for different priority levels
     *
     * @var array
     */
    protected $queues = [
        'high' => 'monday_high',
        'default' => 'monday_default',
        'low' => 'monday_low'
    ];

    /**
     * Threshold for high load (number of pending jobs)
     *
     * @var int
     */
    protected $highLoadThreshold = 50;

    /**
     * Cache TTL for queue metrics (in seconds)
     *
     * @var int
     */
    protected $metricsTtl = 60; // 1 minute

    /**
     * Queue a conversation sync job
     *
     * @param Conversation $conversation
     * @param array $options
     * @param string $priority high|default|low
     * @return void
     */
    public function queueConversationSync(Conversation $conversation, array $options = [], string $priority = 'default')
    {
        $queue = $this->getQueueName($priority);
        $job = new SyncConversationJob($conversation, $options);
        $job->onQueue($queue);

        $this->dispatchJob($job);
        $this->incrementQueueMetric($queue);

        Log::info('QueueService: Queued conversation sync job', [
            'conversation_id' => $conversation->id,
            'queue' => $queue
        ]);
    }

    /**
     * Queue a batch of threads for sync
     *
     * @param array $threads Array of Thread objects
     * @param string $priority high|default|low
     * @return void
     */
    public function queueThreadsSync(array $threads, string $priority = 'default')
    {
        if (empty($threads)) {
            return;
        }

        $queue = $this->getQueueName($priority);
        
        // Split into batches if needed
        $threadBatches = array_chunk($threads, $this->batchSize);
        
        foreach ($threadBatches as $batch) {
            $job = new SyncThreadsJob($batch);
            $job->onQueue($queue);
            
            $this->dispatchJob($job);
            $this->incrementQueueMetric($queue);
            
            $threadIds = array_map(function($thread) {
                return $thread->id;
            }, $batch);
            
            Log::info('QueueService: Queued thread sync job', [
                'thread_count' => count($batch),
                'thread_ids' => $threadIds,
                'queue' => $queue
            ]);
        }
    }

    /**
     * Queue a batch of conversations for status sync
     *
     * @param array $conversations Array of Conversation objects
     * @param string $priority high|default|low
     * @return void
     */
    public function queueStatusesSync(array $conversations, string $priority = 'default')
    {
        if (empty($conversations)) {
            return;
        }

        $queue = $this->getQueueName($priority);
        
        // Split into batches if needed
        $conversationBatches = array_chunk($conversations, $this->batchSize);
        
        foreach ($conversationBatches as $batch) {
            $job = new SyncStatusesJob($batch);
            $job->onQueue($queue);
            
            $this->dispatchJob($job);
            $this->incrementQueueMetric($queue);
            
            $conversationIds = array_map(function($conversation) {
                return $conversation->id;
            }, $batch);
            
            Log::info('QueueService: Queued status sync job', [
                'conversation_count' => count($batch),
                'conversation_ids' => $conversationIds,
                'queue' => $queue
            ]);
        }
    }
    
    /**
     * Queue a manual sync job
     *
     * @param array $options Options for the sync job
     * @param string $priority high|default|low
     * @return void
     */
    public function queueSyncJob(array $options = [], string $priority = 'high')
    {
        $queue = $this->getQueueName($priority);
        $job = new ManualSyncJob($options);
        $job->onQueue($queue);
        
        $this->dispatchJob($job);
        $this->incrementQueueMetric($queue);
        
        Log::info('QueueService: Queued manual sync job', [
            'options' => $options,
            'queue' => $queue
        ]);
    }

    /**
     * Dispatch a job, considering the current load
     *
     * @param mixed $job
     * @return void
     */
    protected function dispatchJob($job)
    {
        // Check if we're in high load mode
        if ($this->isHighLoad()) {
            // For high load, we'll only process high priority jobs immediately
            if ($job->queue === $this->queues['high']) {
                dispatch($job);
            } else {
                // Delay non-high priority jobs during high load
                dispatch($job)->delay(now()->addMinutes(5));
            }
        } else {
            // Normal load, dispatch normally
            dispatch($job);
        }
    }

    /**
     * Get the queue name for a given priority
     *
     * @param string $priority
     * @return string
     */
    protected function getQueueName(string $priority)
    {
        return $this->queues[$priority] ?? $this->queues['default'];
    }

    /**
     * Check if the system is under high load
     *
     * @return bool
     */
    public function isHighLoad()
    {
        $totalPending = $this->getPendingJobCount();
        return $totalPending > $this->highLoadThreshold;
    }

    /**
     * Get the total number of pending jobs across all queues
     *
     * @return int
     */
    public function getPendingJobCount()
    {
        $total = 0;
        foreach ($this->queues as $queue) {
            $total += $this->getQueueMetric($queue);
        }
        return $total;
    }

    /**
     * Get queue metrics for a specific queue
     *
     * @param string $queue
     * @return int
     */
    public function getQueueMetric(string $queue)
    {
        return (int)Cache::get('queue_metric_' . $queue, 0);
    }

    /**
     * Increment queue metric counter
     *
     * @param string $queue
     * @return void
     */
    protected function incrementQueueMetric(string $queue)
    {
        $current = $this->getQueueMetric($queue);
        Cache::put('queue_metric_' . $queue, $current + 1, now()->addSeconds($this->metricsTtl));
    }

    /**
     * Decrement queue metric counter
     *
     * @param string $queue
     * @return void
     */
    public function decrementQueueMetric(string $queue)
    {
        $current = $this->getQueueMetric($queue);
        $newValue = max(0, $current - 1);
        Cache::put('queue_metric_' . $queue, $newValue, now()->addSeconds($this->metricsTtl));
    }

    /**
     * Get queue metrics for all queues
     *
     * @return array
     */
    public function getAllQueueMetrics()
    {
        $metrics = [];
        foreach ($this->queues as $priority => $queue) {
            $metrics[$priority] = $this->getQueueMetric($queue);
        }
        $metrics['total'] = $this->getPendingJobCount();
        $metrics['high_load'] = $this->isHighLoad();
        return $metrics;
    }

    /**
     * Reset all queue metrics (for monitoring purposes)
     *
     * @return void
     */
    public function resetQueueMetrics()
    {
        foreach ($this->queues as $queue) {
            Cache::forget('queue_metric_' . $queue);
        }
        Log::info('QueueService: Reset all queue metrics');
    }
}
