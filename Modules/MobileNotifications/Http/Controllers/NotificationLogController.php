<?php

namespace Modules\MobileNotifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MobileNotifications\Models\NotificationLog;
use Illuminate\Http\Request;
use App\Mailbox;

class NotificationLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        $logs = NotificationLog::with(['user', 'deviceToken'])->latest()->paginate(30);
        return view('mobilenotifications::logs.index', compact('logs', 'mailbox'));
    }
}
