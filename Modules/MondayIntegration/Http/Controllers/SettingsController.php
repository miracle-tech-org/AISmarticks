<?php

namespace Modules\MondayIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\MondayIntegration\Models\MondaySettings;
use Modules\MondayIntegration\Services\MondayApiService;
use App\Mailbox;

class SettingsController extends Controller
{
    protected $apiService;

    public function __construct(MondayApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function index($mailbox_id)
    {
        // Get current settings
        $settings = MondaySettings::pluck('value', 'key')->toArray();

        // Get boards if API token is set
        $boards = [];
        if (!empty($settings['api_token'])) {
            try {
                $boards = $this->apiService->getBoards($settings['api_token']);
            } catch (\Exception $e) {
                // Token might be invalid, we'll show empty boards list
            }
        }
        
		$mailbox = Mailbox::findOrFail($mailbox_id);
        return view('mondayintegration::settings.index', compact('settings', 'boards', 'mailbox'));
    }

    public function getBoards(Request $request) {
        return $this->apiService->getBoards($request->api_token);
    }

    public function save(Request $request)
    {
        // $this->validate($request, [
        //     'api_token' => 'required',
        //     'board_id' => 'required',
        //     'sync_frequency' => 'required|integer|min:5|max:60',
        //     'sync_direction' => 'required|in:both,to_monday,from_monday',
        //     'error_notification' => 'required|in:none,immediate,daily',
        //     'notification_email' => 'nullable|email',
        //     'rate_limit' => 'required|integer|min:1|max:1000',
        //     'retry_attempts' => 'required|integer|min:0|max:10'
        // ]);

        try {
            // Test connection with new token
            $this->apiService->testConnection($request->api_token);

            // Save settings
            foreach ($request->except('_token') as $key => $value) {
                MondaySettings::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                    ]
                );
            }
            return redirect()->back();
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
