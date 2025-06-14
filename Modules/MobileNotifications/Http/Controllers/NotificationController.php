<?php

namespace Modules\MobileNotifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MobileNotifications\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected $service;

    public function __construct(FirebaseNotificationService $service)
    {
        $this->service = $service;
    }

    public function test()
    {
        try {
            return $this->service->sendTestMessage(auth()->user());
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }
}
