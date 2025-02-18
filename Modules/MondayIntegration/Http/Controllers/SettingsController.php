<?php

namespace Modules\MondayIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\MondayIntegration\Models\MondaySettings;
use Modules\MondayIntegration\Services\MondayApiService;
use Modules\MondayIntegration\Services\SecurityService;

class SettingsController extends Controller
{
    protected $apiService;
    protected $security;

    public function __construct(MondayApiService $apiService, SecurityService $security)
    {
        $this->apiService = $apiService;
        $this->security = $security;
    }

    public function index()
    {
        // Get current settings
        $settings = MondaySettings::pluck('value', 'key')
            ->map(function ($value, $key) {
                return $this->security->isEncrypted($key) ? 
                    $this->security->decrypt($value) : 
                    $value;
            })
            ->toArray();

        // Get boards if API token is set
        $boards = [];
        if (!empty($settings['api_token'])) {
            try {
                $boards = $this->apiService->getBoards();
            } catch (\Exception $e) {
                // Token might be invalid, we'll show empty boards list
            }
        }

        return view('mondayintegration::settings.index', compact('settings', 'boards'));
    }

    public function save(Request $request)
    {
        $this->validate($request, [
            'api_token' => 'required',
            'board_id' => 'required',
            'sync_frequency' => 'required|integer|min:5|max:60',
            'sync_direction' => 'required|in:both,to_monday,from_monday',
            'error_notification' => 'required|in:none,immediate,daily',
            'notification_email' => 'nullable|email',
            'rate_limit' => 'required|integer|min:1|max:1000',
            'retry_attempts' => 'required|integer|min:0|max:10'
        ]);

        try {
            // Test connection with new token
            $this->apiService->testConnection($request->api_token);

            // Save settings
            foreach ($request->except('_token') as $key => $value) {
                if ($this->security->isEncrypted($key)) {
                    $value = $this->security->encrypt($value);
                }

                MondaySettings::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'is_encrypted' => $this->security->isEncrypted($key)
                    ]
                );
            }

            return response()->json([
                'message' => __('Settings saved successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function testConnection(Request $request)
    {
        try {
            $token = $request->input('api_token');
            if ($token) {
                $this->apiService->testConnection($token);
            } else {
                $this->apiService->testConnection();
            }

            return response()->json([
                'message' => __('Connection successful')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    protected function isEncrypted($key)
    {
        return in_array($key, [
            'api_token',
            'webhook_secret'
        ]);
    }
}
