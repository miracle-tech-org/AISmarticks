<?php

namespace Modules\AIAssistant\Events;

use Illuminate\Queue\SerializesModels;

class OpenAIServiceUnavailable
{
    use SerializesModels;
    
    public $message;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($message)
    {
        $this->message = $message;
    }
}
