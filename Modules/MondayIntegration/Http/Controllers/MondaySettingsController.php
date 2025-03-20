<?php

namespace Modules\MondayIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\MondayIntegration\Services\MondayApiService;
use Modules\MondayIntegration\Services\SecurityService;
use Modules\MondayIntegration\Models\MondaySettings;
use App\Mailbox;

class MondaySettingsController extends Controller
{
    protected $mondayApi;
    protected $security;

    public function __construct(
        MondayApiService $mondayApi,
        SecurityService $security
    ) {
        $this->mondayApi = $mondayApi;
        $this->security = $security;
    }

    public function index($mailbox_id)
    {
        $settings = MondaySettings::all()->pluck('value', 'key')->toArray();
        $boards = [];

        try {
            if (!empty($settings['api_token'])) {
                $boards = $this->mondayApi->getBoards();
            }
        } catch (\Exception $e) {
            \Session::flash('flash_error_floating', $e->getMessage());
        }
		$mailbox = Mailbox::findOrFail($mailbox_id);

        return view('mondayintegration::settings.index', [
            'settings' => $settings,
            'mailbox' => $mailbox
        ]);
    }

    public function save(Request $request, $mailbox_id)
    {
        $settings = $request->all();
        // Save sidebar widget settings
        if (isset($settings['enable_sidebar_widget'])) {
            $settings['enable_sidebar_widget'] = $settings['enable_sidebar_widget'] ? '1' : '0';
        } else {
            $settings['enable_sidebar_widget'] = '0';
        }
        
        // Process quick actions if provided
        if (!empty($settings['quick_actions_json'])) {
            try {
                // Validate JSON format
                $quickActions = json_decode($settings['quick_actions_json'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON format for quick actions');
                }
                
                // Store the validated JSON
                $settings['quick_actions'] = $settings['quick_actions_json'];
                
                // Remove the temporary field
                unset($settings['quick_actions_json']);
                
                \Log::info('MondayIntegration: Quick actions updated', [
                    'count' => count($quickActions)
                ]);
            } catch (\Exception $e) {
                \Log::error('MondayIntegration: Error saving quick actions', [
                    'error' => $e->getMessage()
                ]);
                
                return redirect()->back()
                    ->withInput()
                    ->with('error', __('Invalid quick actions format: :error', ['error' => $e->getMessage()]));
            }
        }
        // Save all settings
        foreach ($settings as $key => $value) {
            MondaySettings::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
        
        \Log::info('MondayIntegration: Settings saved');
        
        // Determine the redirect route based on the form origin
        $redirectRoute = 'monday.settings';
        if ($request->has('form_origin')) {
            switch ($request->input('form_origin')) {
                case 'pullboard':
                    $redirectRoute = 'monday.settings.pullboard';
                    break;
                case 'pushboard':
                    $redirectRoute = 'monday.settings.pushboard';
                    break;
                case 'sidebar_widget':
                    $redirectRoute = 'monday.settings.sidebar-widget';
                    break;
            }
        }
        
        return redirect()->route($redirectRoute, ['mailbox_id' => $mailbox_id])
            ->with('success', __('Settings saved successfully'));
    }

    public function testConnection()
    {
        try {
            $result = $this->mondayApi->testConnection();
            return response()->json([
                'success' => true,
                'message' => __('Connection successful')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function testBoardConnection(Request $request)
    {
        $apiToken = $request->input('api_token');
        $boardId = $request->input('board_id');
        
        if (empty($apiToken)) {
            return response()->json([
                'status' => 'error',
                'message' => __('API token is required')
            ], 422);
        }
        
        if (empty($boardId)) {
            return response()->json([
                'status' => 'error',
                'message' => __('Board ID is required')
            ], 422);
        }
        
        try {
            // Test the connection by fetching board details
            $board = $this->mondayApi->getBoard($boardId);
            $columns = $this->mondayApi->getBoardColumns($boardId);
            
            return response()->json([
                'status' => 'success',
                'message' => __('Successfully connected to board :name with :count columns', [
                    'name' => $board['name'],
                    'count' => count($columns)
                ])
            ]);
        } catch (\Exception $e) {
            \Log::error('MondayIntegration: Board connection test failed', [
                'error' => $e->getMessage(),
                'board_id' => $boardId
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function pullboardSettings($mailbox_id)
    {
        $settings = MondaySettings::all()->pluck('value', 'key')->toArray();
        $boards = [];

        try {
            if (!empty($settings['api_token'])) {
                $boards = $this->mondayApi->getBoards();
            }
        } catch (\Exception $e) {
            // Log the error
            \Log::error('MondayIntegration: Error retrieving boards', [
                'error' => $e->getMessage()
            ]);
            
            // Flash error message to session
            session()->flash('error', $e->getMessage());
        }
        $mailbox = Mailbox::findOrFail($mailbox_id);
        return view('mondayintegration::settings.pullboard', [
            'settings' => $settings,
            'boards' => $boards,
            'mailbox' => $mailbox,
        ]);
    }

    public function pushboardSettings($mailbox_id)
    {
        $settings = MondaySettings::all()->pluck('value', 'key')->toArray();
        $boards = [];

        try {
            $boards = $this->mondayApi->getBoards();
        } catch (\Exception $e) {
            // Log the error
            \Log::error('MondayIntegration: Error retrieving boards', [
                'error' => $e->getMessage()
            ]);
            
            // Flash error message to session
            session()->flash('error', $e->getMessage());
        }
        $mailbox = Mailbox::findOrFail($mailbox_id);

        return view('mondayintegration::settings.pushboard', [
            'settings' => $settings,
            'boards' => $boards,
            'mailbox' => $mailbox
        ]);
    }
}
