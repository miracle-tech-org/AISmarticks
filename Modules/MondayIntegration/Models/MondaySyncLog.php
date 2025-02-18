<?php

namespace Modules\MondayIntegration\Models;

use Illuminate\Database\Eloquent\Model;

class MondaySyncLog extends Model
{
    protected $fillable = [
        'action_type',
        'source_type',
        'monday_board_id',
        'monday_item_id',
        'conversation_id',
        'status',
        'error_message',
        'request_data',
        'response_data',
        'retry_count'
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'retry_count' => 'integer'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_RETRYING = 'retrying';

    public function conversation()
    {
        return $this->belongsTo(\App\Conversation::class);
    }

    public function markAsFailed($errorMessage)
    {
        $this->status = self::STATUS_FAILED;
        $this->error_message = $errorMessage;
        $this->save();

        if ($this->shouldRetry()) {
            $this->scheduleRetry();
        } else {
            $this->notifyFailure();
        }
    }

    public function markAsCompleted($responseData = null)
    {
        $this->status = self::STATUS_COMPLETED;
        $this->response_data = $responseData;
        $this->save();
    }

    protected function shouldRetry()
    {
        $maxRetries = config('monday.sync.max_retries', 3);
        return $this->retry_count < $maxRetries;
    }

    protected function scheduleRetry()
    {
        $this->status = self::STATUS_RETRYING;
        $this->retry_count++;
        $this->save();

        // Schedule retry with exponential backoff
        $delay = pow(2, $this->retry_count) * config('monday.sync.retry_delay', 5);
        \App\Jobs\MondaySyncRetry::dispatch($this)->delay(now()->addMinutes($delay));
    }

    protected function notifyFailure()
    {
        event(new \App\Events\SystemAlert(
            'Monday.com Sync Failed',
            'error',
            [
                'log_id' => $this->id,
                'action_type' => $this->action_type,
                'error' => $this->error_message,
                'conversation_id' => $this->conversation_id,
                'monday_item_id' => $this->monday_item_id
            ]
        ));
    }
}
