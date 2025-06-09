<?php

namespace Modules\MobileNotifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MobileNotifications\Models\DeviceToken;
use Illuminate\Validation\Rule; // Added for Rule::in

class RegistrationController extends Controller
{
    public function form($user)
    {
        if (! request()->hasValidSignature()) {
            abort(401);
        }

        return view('mobilenotifications::register', ['user_id' => $user]);
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

        return response()->json(['success' => true, 'id' => $device->id]);
    }

    public function delete($id)
    {
        $token = DeviceToken::findOrFail($id);
        if ($token->user_id != auth()->user()->id) {
            abort(403);
        }
        $token->delete();

        return redirect()->back();
    }
}
