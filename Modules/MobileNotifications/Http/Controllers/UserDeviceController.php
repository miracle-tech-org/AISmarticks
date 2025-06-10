<?php

namespace Modules\MobileNotifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request; // Added for Request
use Modules\MobileNotifications\Models\DeviceToken;
use Modules\MobileNotifications\Models\AgentMobileNotificationPreference; // Added model
use App\User;

class UserDeviceController extends Controller
{
    public function getUsersForSidebar($except_id)
    {
        if (auth()->user()->isAdmin()) {
            return User::sortUsers(User::nonDeleted()->get());/*->except($except_id)*/;
        } else {
            return [];
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $userId = Auth::id();
        $devices = DeviceToken::where('user_id', $userId)->latest()->get();

        $availableTypes = config('mobilenotifications.notification_types', []);
        $preferencesData = [];

        $currentPreferences = AgentMobileNotificationPreference::where('user_id', $userId)
            ->pluck('is_enabled', 'notification_type');

        foreach ($availableTypes as $typeKey => $typeLabel) {
            $preferencesData[] = [
                'key' => $typeKey,
                'label' => $typeLabel,
                // If a preference exists, use it. Otherwise, default to true (all notifications enabled by default).
                'is_enabled' => $currentPreferences[$typeKey] ?? true,
                // Consider adding description here if notification_types.php was more detailed
                // 'description' => $typeConfig['description'] ?? '',
            ];
        }

        $user = Auth::user();
        $users = $this->getUsersForSidebar($userId);

        return view('mobilenotifications::user.devices', compact('devices', 'preferencesData', 'user', 'users'));
    }

    /**
     * Update the user's notification preferences.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePreferences(Request $request)
    {
        $userId = Auth::id();
        $submittedPreferences = $request->input('preferences', []);
        $availableTypes = config('mobilenotifications.notification_types', []);

        foreach (array_keys($availableTypes) as $typeKey) {
            $isEnabled = isset($submittedPreferences[$typeKey]);

            AgentMobileNotificationPreference::updateOrCreate(
                [
                    'user_id' => $userId,
                    'notification_type' => $typeKey,
                ],
                [
                    'is_enabled' => $isEnabled,
                ]
            );
        }

        return redirect()->route('mobilenotifications.user.devices')
                         ->with('status', __('Notification preferences updated.'));
    }
}
