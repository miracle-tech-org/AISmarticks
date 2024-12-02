<?php

namespace Modules\AIAssistant\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'ai_agent_id',
        'user_id',
        'feedback', // New field for user feedback
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'feedback' => 'integer', // 1 (Positive), -1 (Negative), 0 (Neutral)
    ];

    protected $table = 'conversations';

}
