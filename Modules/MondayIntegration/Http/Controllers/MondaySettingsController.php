<?php

namespace Modules\MondayIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\MondayIntegration\Services\MondayApiService;
use Modules\MondayIntegration\Services\SecurityService;
use Modules\MondayIntegration\Models\MondaySettings;
use Modules\MondayIntegration\Services\FieldMappingAssistantService;
use App\Mailbox;
use App\Conversation;
use App\Thread;
use App\User;
use DB;
use GuzzleHttp\Client;

class MondaySettingsController extends Controller
{
    protected $mondayApi;
    protected $security;

    public function __construct(MondayApiService $mondayApi, SecurityService $security)
    {
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
        if($request->has('form_origin') && $request->input('form_origin') == 'pushboard') {
            MondaySettings::whereIn('key', ['push_on_conversation_created', 'push_on_conversation_closed', 'push_on_conversation_updated', 'push_on_conversation_fully_closed'])->delete();
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
                    $redirectRoute = 'monday.sidebar_settings';
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
        $fieldService = new FieldMappingAssistantService;
        $fields = $fieldService->getAllAvailableFields();

        return view('mondayintegration::settings.pushboard', [
            'settings' => $settings,
            'boards' => $boards,
            'mailbox' => $mailbox,
            'fields' => $fields
        ]);
    }

    public function clearMapping()
    {
        MondaySettings::where('key', 'pullboard_id')->delete();
        MondaySettings::where('key', 'pushboard_id')->delete();
        return redirect()->back();
    }

    public function createBoard(Request $request)
    {
        $board_name = $request->board_name;
        $board_kind = $request->board_kind;
        $fields = $request->fields;

        $result = $this->mondayApi->createBoard($board_name, $board_kind);
        $board_id = $result['create_board']['id'];

        if($fields != "all") {
            return $this->mondayApi->createColumn($board_id, $fields);
        } else {
            return $this->mondayApi->createColumnsWithAllFields($board_id);
        }
    }

    public function getBoardItems(Request $request)
    {
        $board_id = $request->board_id;
        return $this->mondayApi->getBoardItems($board_id);
    }

    public function deleteBoardItem(Request $request)
    {
        $itemId = $request->itemId;
        return $this->mondayApi->removeBoardItem($itemId);
    }

    public function createBoardItem(Request $request)
    {
        $board_id = $request->board_id;
        $item_name = $request->item_name;
        return $this->mondayApi->createItem($board_id, $item_name);
    }

    public function getBoardById(Request $request)
    {
        $board_id = $request->board_id;
        return $this->mondayApi->getBoardById($board_id);
    }

    public function sendPushboardItem(Request $request)
    {
        $pushboard_id = DB::table('monday_settings')
            ->where('key', 'pushboard_id')
            ->value('value');

        $responseBody = $this->mondayApi->getBoardById($pushboard_id);

        $conversation_id = $request->conversation_id;
        $thread_id = $request->thread_id;
        $conversation = Conversation::find($conversation_id);
        $thread = Thread::find($thread_id);
        $column_values = [];

        if ($conversation->user) {
            $user_data = [
                'id' => (string)$conversation->user->id,
                'first_name' => (string)$conversation->user->first_name ?? '',
                'last_name' => (string)$conversation->user->last_name ?? '',
                'email' => (string)$conversation->user->email ?? '',
                'phone' => (string)'',
                'job_title' => (string)$conversation->user->job_title ?? '',
                'timezone' => (string)$conversation->user->timezone ?? '',
                'photo_url' => (string)$conversation->user->photo_url ?? '',
                'role' => (string)$conversation->user->role ?? '',
                'status' => (string)$conversation->user->status ?? '',
                'created_at' => (string)$conversation->user->created_at ?? '',
                'updated_at' => (string)$conversation->user->updated_at ?? '',
            ];
    
            // Map user data to column IDs
            foreach ($responseBody['columns'] as $index => $column) {
                $column_id = $column['id'];
                $field_name = strtolower(str_replace(' ', '_', $column['title'])); // Convert title to field name
    
                if (isset($user_data[$field_name])) {
                    $column_values[$column_id] = $user_data[$field_name];
                }
            }
        }

        if ($conversation->customer) {
            $phone_number = "";
            $jsonString = (string)$conversation->customer->phones;
            $jsonArray = json_decode($jsonString, true);
            if(count($jsonArray)) {
                $phone_number = $jsonArray[0]['value'];
            }

            $customer_data = [
                'id' => (string)$conversation->customer->id,
                'first_name' => (string)$conversation->customer->first_name ?? '',
                'last_name' => (string)$conversation->customer->last_name ?? '',
                'email' => (string)'',
                'emails' => (string)'',
                'phones' => (string)$phone_number,
                'company' => (string)$conversation->customer->company ?? '',
                'job_title' => (string)$conversation->customer->job_title ?? '',
                'address' => (string)$conversation->customer->address ?? '',
                'city' => (string)$conversation->customer->city ?? '',
                'state' => (string)$conversation->customer->state ?? '',
                'zip' => (string)$conversation->customer->zip ?? '',
                'country' => (string)$conversation->customer->country ?? '',
                'websites' => (string)$conversation->customer->websites ?? '',
                'social_profiles' => (string)$conversation->customer->social_profiles ?? '',
                'photo_url' => (string)$conversation->customer->photo_url ?? '',
                'created_at' => (string)$conversation->customer->created_at ?? '',
                'updated_at' => (string)$conversation->customer->updated_at ?? '',
            ];
    
            // Map customer data to column IDs
            foreach ($responseBody['columns'] as $index => $column) {
                $column_id = $column['id'];
                $field_name = strtolower(str_replace(' ', '_', $column['title'])); // Convert title to field name
    
                if (isset($customer_data[$field_name])) {
                    $column_values[$column_id] = $customer_data[$field_name];
                }
            }
        }
        
        if($conversation) {
            $conversation_data = [
                'id' => (string)$conversation->id,
                'number' => (string)$conversation->number,
                'subject' => (string)$conversation->subject,
                'customer_id' => (string)$conversation->customer_id,
                'mailbox_id' => (string)$conversation->mailbox_id,
                'folder_id' => (string)$conversation->folder_id,
                'status' => (string)$conversation->status,
                'state' => (string)$conversation->state,
                'preview' => (string)$conversation->preview,
                'source_via' => (string)$conversation->source_via,
                'source_type' => (string)$conversation->source_type,
                'created_at' => (string)$conversation->created_at,
                'updated_at' => (string)$conversation->updated_at,
                'user_updated_at' => (string)$conversation->user_updated_at,
                'closed_at' => (string)$conversation->closed_at,
                'assigned_to' => (string)"",
                'last_reply_at' => (string)$conversation->last_reply_at,
                'last_reply_from' => (string)$conversation->last_reply_from,
                'has_attachments' => (string)$conversation->has_attachments,
            ];
            // Map customer data to column IDs
            foreach ($responseBody['columns'] as $index => $column) {
                $column_id = $column['id'];
                $field_name = strtolower(str_replace(' ', '_', $column['title'])); // Convert title to field name
    
                if (isset($conversation_data[$field_name])) {
                    $column_values[$column_id] = $conversation_data[$field_name];
                }
            }
        }
        if($thread) {
            $thread_data = [
                'id' => (string)$thread->id,
                'conversation_id' => (string)$thread->conversation_id,
                'user_id' => (string)$thread->user_id,
                'customer_id' => (string)$thread->customer_id,
                'type' => (string)$thread->type,
                'state' => (string)$thread->state,
                'status' => (string)$thread->status,
                'body' => (string)$thread->body,
                'headers' => (string)$thread->headers,
                'has_attachments' => (string)$thread->has_attachments,
                'created_at' => (string)$thread->created_at,
                'updated_at' => (string)$thread->updated_at,
                'source_via' => (string)$thread->source_via,
                'source_type' => (string)$thread->source_type,
            ];
            // Map customer data to column IDs
            foreach ($responseBody['columns'] as $index => $column) {
                $column_id = $column['id'];
                $field_name = strtolower(str_replace(' ', '_', $column['title'])); // Convert title to field name
    
                if (isset($thread_data[$field_name])) {
                    $column_values[$column_id] = $thread_data[$field_name];
                }
            }
        }

        $column_values_json = json_encode($column_values);
        $column_values_json = addslashes($column_values_json);

        $query = <<<GRAPHQL
            mutation {
                create_item(
                    board_id: $pushboard_id,
                    item_name: "Item $thread->id", 
                    column_values: "$column_values_json"
                ) {
                    id
                }
            }
        GRAPHQL;
        $this->mondayApi->sendPushboardItem($query);
    }
}
