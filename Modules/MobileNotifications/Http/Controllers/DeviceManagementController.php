<?php

namespace Modules\MobileNotifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MobileNotifications\Models\DeviceToken;
use Illuminate\Http\Request;
use App\Mailbox;

class DeviceManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        $devices = DeviceToken::with('user')->latest()->paginate(20);
        return view('mobilenotifications::devices.index', compact('devices', 'mailbox'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Modules\MobileNotifications\Models\DeviceToken  $device
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $id = $request->device_id;
        $deviceToken = DeviceToken::findOrFail($id);
        $deviceToken->delete();

        return 'Device token deleted successfully.';
    }
}
