<?php

namespace Modules\AIAssistant\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class AIAssistantController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $credentials = [
            'email' => config('ai-assistant.aiagent_mail'),
            'password' => config('ai-assistant.aiagent_pass'),
        ];
        if (Auth::attempt($credentials)) {
            $userId = auth()->user()->id;
            return response()->json([
                'userId' => $userId,
            ])->cookie(cookie('laravel_session', session()->getId(), 60));
        }
    }

    // Show Limits Form
    public function showLimits()
    {
        $settings = [
            'daily_limit' => config('ai-assistant.daily_limit'),
            'monthly_limit' => config('ai-assistant.monthly_limit'),
        ];

        return view('aiassistant::limits', compact('settings'));
    }

    // Save Limits
    public function saveLimits(Request $request)
    {
        $request->validate([
            'daily_limit' => 'required|integer|min:0',
            'monthly_limit' => 'required|integer|min:0',
        ]);

        // Save settings to config or database as needed
        config(['ai-assistant.daily_limit' => $request->input('daily_limit')]);
        config(['ai-assistant.monthly_limit' => $request->input('monthly_limit')]);

        // Optionally, update persistent storage if not using config()
        // DB::table('settings')->updateOrInsert(...);

        return redirect()->back()->with('success', 'Limits updated successfully.');
    }
}
