<?php

namespace Modules\AIAssistant\Models;

use Illuminate\Database\Eloquent\Model;
use App\Conversation;
use App\Mailbox;

class AIAgentAssignment extends Model
{
    protected $fillable = [
        'ai_agent_id',
        'mailbox_id',
        'conversation_id',
        'response_mode',
        'monthly_usage_limit',
        'current_usage',
        'usage_alert_threshold',
        'alert_triggered',
    ];

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
}
