<?php

namespace Modules\AIAssistant\Models;

use Illuminate\Database\Eloquent\Model;
use App\Conversation;
use App\Mailbox;
use Modules\AIAssistant\Events\APIUsageLimitApproaching;
use Modules\AIAssistant\Events\APIUsageLimitReached;

class AIAgentAssignment extends Model
{
    protected $fillable = [
        'ai_agent_id',
        'mailbox_id',
        'conversation_id',
        'response_mode',
        'monthly_usage_limit',
        'daily_limit',
        'current_usage',
        'daily_usage',
        'usage_alert_threshold',
        'daily_usage_alert',
        'alert_triggered',
    ];

    protected $table = "ai_agent_assignments";
    public $timestamps = false;

    /**
     * Relationship: AI Agent
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function aiAgent()
    {
        return $this->belongsTo(AIAgent::class);
    }

    /**
     * Relationship: Mailbox
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mailbox()
    {
        return $this->belongsTo(Mailbox::class);
    }

    /**
     * Relationship: Conversation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

        /**
     * Check if usage alert should be triggered
     *
     * @return void
     */
    public function checkUsageAlert()
    {
        if(is_null($this->monthly_usage_limit))
            $this->monthly_usage_limit = 10000;
        
        $usagePercentage = ($this->current_usage / $this->monthly_usage_limit) * 100;

        if ($usagePercentage >= $this->usage_alert_threshold &&!$this->alert_triggered) {
            $this->alert_triggered = true;
            $this->save();

            // Trigger usage alert
            event(new APIUsageLimitApproaching($this));
        } elseif ($usagePercentage >= 100) {
            event(new APIUsageLimitReached($this));
        }
    }
}
