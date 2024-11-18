<?php

namespace Modules\AIAssistant\Listeners;

use Modules\AIAssistant\Events\OpenAIServiceUnavailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\User;
use App\Notifications\SystemAlert;

class OpenAIServiceUnavailableListener
{

    /**
     * Handle the event.
     *
     * @param OpenAIServiceUnavailable $event
     * @return void
     */
    public function handle(OpenAIServiceUnavailable $event)
    {
        $message = "OpenAI Service Unavailable: ". $event->message;
        $admins = User::role('admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new SystemAlert('OpenAI Service Unavailable', $message));
        }
    }
}
