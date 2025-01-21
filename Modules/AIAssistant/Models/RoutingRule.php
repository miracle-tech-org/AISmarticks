<?php

namespace Modules\AIAssistant\Models;

use Illuminate\Database\Eloquent\Model;

class RoutingRule extends Model
{
    protected $fillable = [
        'name',
        'conditions',
        'priority',
        'team_id',
        'required_skills',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
    ];
    
    public function team()
    {
        return $this->belongsTo(User::class, 'team_id');
    }
}
