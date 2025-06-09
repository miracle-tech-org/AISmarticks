<?php

namespace Modules\MobileNotifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MobileNotifications\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $devices = DeviceToken::with('user')->latest()->paginate(20);
        return view('mobilenotifications::devices.index', compact('devices'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Modules\MobileNotifications\Models\DeviceToken  $device
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeviceToken $device)
    {
        // Authorization is handled by the route group middleware 'roles' => ['admin']
        // For more granular control, a Policy could be used here.
        $device->delete();

        return redirect()->route('mobilenotifications.devices.index')
                         ->with('status', __('Device token deleted successfully.'));
    }
}
