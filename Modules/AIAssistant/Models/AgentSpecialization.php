<?php

namespace Modules\AIAssistant\Models;

use Illuminate\Database\Eloquent\Model;

class AgentSpecialization extends Model
{
    protected $fillable = ['user_id', 'skill', 'proficiency_level', 'is_primary'];
}
