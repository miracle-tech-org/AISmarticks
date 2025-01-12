<?php

namespace Modules\AIMiddleware\Entities;

use Illuminate\Database\Eloquent\Model;

class AIInteractionLog extends Model
{
    protected $fillable = [
        'ticket_id',
        'agent_id',
        'user_query',
        'ai_response',
        'tags',
        'tokens_used',
        'sentiment',
        'confidence',
        'priority',
        'rating',
        'response_time'
    ];

    protected $casts = [
        'tags' => 'array',
        'tokens_used' => 'integer',
        'confidence' => 'float',
        'rating' => 'integer',
        'response_time' => 'float'
    ];

    // Relationships
    public function ticket()
    {
        return $this->belongsTo(\App\Models\Ticket::class);
    }

    public function agent()
    {
        return $this->belongsTo(AIAgent::class);
    }

    public function trainingData()
    {
        return $this->hasMany(AITrainingData::class, 'interaction_id');
    }
}
