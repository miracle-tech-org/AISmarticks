<?php

namespace Modules\AIAssistant\Events;

use Illuminate\Queue\SerializesModels;
use Modules\AIAssistant\Models\AIAgentAssignment;

class APIUsageLimitReached
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

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
