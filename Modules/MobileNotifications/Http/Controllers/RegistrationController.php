<?php

namespace Modules\MobileNotifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MobileNotifications\Models\DeviceToken;
use Illuminate\Validation\Rule; // Added for Rule::in
use Auth;
use App\User;

class RegistrationController extends Controller
{
    public function getUsersForSidebar($except_id)
    {
        if (auth()->user()->isAdmin()) {
            return User::sortUsers(User::nonDeleted()->get());/*->except($except_id)*/;
        } else {
            return [];
        }
    }

    public function form($user)
    {
        $userData = Auth::user();
        $users = $this->getUsersForSidebar($user);

        return view('mobilenotifications::register', ['user_id' => $user, 'user' => $userData, 'users' => $users]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'token' => 'required|string|max:1024', // Added max length
            'platform' => ['required', 'string', Rule::in(['Android', 'iOS', 'Web'])], // Added Rule::in
        ]);

        $device = DeviceToken::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'token' => $request->input('token'),
            ],
            [
                'platform' => $request->input('platform'),
            ]
        );

        return 'Device registered successfully';
    }

    public function delete(Request $request)
    {
        $id = $request->id;
        $token = DeviceToken::findOrFail($id);
        $token->delete();

        return 'Device token deleted successfully';
    }
}
