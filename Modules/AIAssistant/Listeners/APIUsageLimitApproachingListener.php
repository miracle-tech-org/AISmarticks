<?php

namespace Modules\AIAssistant\Listeners;

use Modules\AIAssistant\Events\APIUsageLimitApproaching;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\User;
use App\Notifications\SystemAlert;

class APIUsageLimitApproachingListener
{
    /**
     * Handle the event.
     *
     * @param APIUsageLimitApproaching $event
     * @return void
     */
    public function handle(APIUsageLimitApproaching $event)
    {
        // Notify administrators about approaching usage limit
        $admins = User::role('admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new SystemAlert('API Usage Limit Approaching', 'Usage limit for assignment ID ' . $event->assignment->id . 'is approaching.'));
        }
    }
}
