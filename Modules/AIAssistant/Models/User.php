<?php

namespace Modules\AIAssistant\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    // protected $fillable = [];
    public function specializations() {
        return $this->hasMany(AgentSpecialization::class, 'user_id');
    }
}
