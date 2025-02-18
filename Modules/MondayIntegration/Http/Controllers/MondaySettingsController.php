<?php

namespace Modules\MondayIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\MondayIntegration\Services\MondayApiService;
use Modules\MondayIntegration\Services\SecurityService;
use Modules\MondayIntegration\Models\MondaySettings;

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

    public function index()
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

        return view('mondayintegration::settings.index', [
            'settings' => $settings,
            'boards' => $boards
        ]);
    }

    public function save(Request $request)
    {
        try {
            $apiToken = $request->input('api_token');
            if ($apiToken) {
                if (!$this->security->validateToken($apiToken)) {
                    throw new \Exception(__('Invalid API token format'));
                }
                MondaySettings::setValue('api_token', $apiToken, true);
            }

            MondaySettings::setValue('board_id', $request->input('board_id'));
            MondaySettings::setValue('sync_frequency', $request->input('sync_frequency'));

            $this->mondayApi->testConnection();

            \Session::flash('flash_success_floating', __('Settings saved successfully'));
            return redirect()->route('monday.settings.index');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
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
}
