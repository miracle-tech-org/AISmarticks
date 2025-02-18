<?php

namespace Modules\MondayIntegration\Models;

use Illuminate\Database\Eloquent\Model;

class MondayWebhook extends Model
{
    protected $fillable = [
        'board_id',
        'board_name',
        'event_type',
        'is_active',
        'webhook_id',
        'last_event_at',
        'error_count'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_event_at' => 'datetime',
        'error_count' => 'integer'
    ];

    public function incrementErrorCount()
    {
        $this->increment('error_count');
        
        if ($this->error_count >= config('monday.webhooks.max_errors', 5)) {
            $this->is_active = false;
            $this->save();
            
            event(new \App\Events\SystemAlert(
                'Monday.com Webhook Disabled',
                'error',
                [
                    'webhook_id' => $this->id,
                    'board_name' => $this->board_name,
                    'error_count' => $this->error_count
                ]
            ));
        }
    }

    public function resetErrorCount()
    {
        $this->error_count = 0;
        $this->save();
    }

    public function recordEvent()
    {
        $this->last_event_at = now();
        $this->save();
    }
}
