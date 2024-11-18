<?php

namespace Modules\AIAssistant\Events;

use Illuminate\Queue\SerializesModels;
use Modules\AIAssistant\Models\AIAgentAssignment;

class APIUsageLimitApproaching
{
    use SerializesModels;

    public $assignment;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(AIAgentAssignment $assignment)
    {
        $this->assignment = $assignment;
    }
}
