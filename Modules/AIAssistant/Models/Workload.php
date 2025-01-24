<?php

namespace Modules\AIAssistant\Models;

use Illuminate\Database\Eloquent\Model;

class Workload extends Model
{
    protected $fillable = ['ticket_id', 'agent_id', 'review'];

    public function thread() {
        return $this->hasOne('App\Thread', 'id', 'ticket_id');
    }
}
