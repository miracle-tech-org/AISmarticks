<?php

namespace Modules\AIAssistant\Events;

use Illuminate\Queue\SerializesModels;

class OpenAIAuthenticationFailed
{
    use SerializesModels;

    public $apiKey;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }
}
