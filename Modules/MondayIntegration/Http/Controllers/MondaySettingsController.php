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
use App\Customer;
use App\Email;
use App\User;
use DB;
use GuzzleHttp\Client;
use App\ActivityLog;
use Modules\MondayIntegration\Models\MondayFieldMapping;

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

        // Get Monday.com columns for both boards
        $pullboardColumns = [];
        $pushboardColumns = [];
        $boards = [];
        $pullboardId = $settings['pullboard_id'] ?? null;
        $pushboardId = $settings['pushboard_id'] ?? null;

        try {
            if (!empty($settings['api_token'])) {
                $boards = $this->mondayApi->getBoards();
                if ($pullboardId) {
                    $pullboardColumns = $this->mondayApi->getBoardColumns($pullboardId);
                }
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
        $freescoutFields = [
            'Customer' => [
                'customer.first_name' => __('First Name'),
                'customer.last_name' => __('Last Name'),
                'customer.email' => __('Email'),
                'customer.phone' => __('Phone'),
                'customer.company' => __('Company')
            ],
            'Custom Fields' => [
                'custom_fields.priority' => __('Priority'),
                'custom_fields.category' => __('Category'),
                'custom_fields.due_date' => __('Due Date')
            ]
        ];

        $transformRules = [
            'uppercase' => __('Convert to Uppercase'),
            'lowercase' => __('Convert to Lowercase'),
            'date_format' => __('Format Date'),
            'boolean' => __('Convert to Yes/No'),
            'json' => __('Convert to JSON')
        ];
        // Get current mappings
        $pullMappings = MondayFieldMapping::where('board_type', 'pull')
            ->orderBy('order')
            ->get();

        $pushMappings = MondayFieldMapping::where('board_type', 'push')
            ->orderBy('order')
            ->get();
        $mondayName = $this->mondayApi->testConnection();
        if($mondayName) {
            $mondayName = $mondayName['me']['account']['slug'];
        } else {
            $mondayName = "";
        }

        return view('mondayintegration::settings.pullboard', [
            'mondayName' => $mondayName,
            'settings' => $settings,
            'boards' => $boards,
            'mailbox' => $mailbox,
            'pullboardColumns' => $pullboardColumns,
            'freescoutFields' => $freescoutFields,
            'pullMappings' => $pullMappings,
            'pushMappings' => $pushMappings,
            'transformRules' => $transformRules,
        ]);
    }

    public function pushboardSettings($mailbox_id)
    {
        $settings = MondaySettings::all()->pluck('value', 'key')->toArray();
        $boards = [];

        try {
            $boards = $this->mondayApi->getBoards();
            $mondayName = $this->mondayApi->testConnection();
            if($mondayName) {
                $mondayName = $mondayName['me']['account']['slug'];
            } else {
                $mondayName = "";
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
        $fieldService = new FieldMappingAssistantService;
        $fields = $fieldService->getAllAvailableFields();

        return view('mondayintegration::settings.pushboard', [
            'settings' => $settings,
            'boards' => $boards,
            'mailbox' => $mailbox,
            'fields' => $fields,
            'mondayName' => $mondayName
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
        try {
            $pushboard_id = DB::table('monday_settings')
                ->where('key', 'pushboard_id')
                ->value('value');

            $responseBody = $this->mondayApi->getBoardById($pushboard_id);
            $conversation_id = $request->conversation_id;
            $thread_id = $request->thread_id;
            $conversation = Conversation::find($conversation_id);
            $thread = Thread::find($thread_id);
            $column_values = [
                [$responseBody['columns'][0]['id'] => "Name"]
            ];

            if ($conversation->user) {
                $role = "";
                if($conversation->user->role == 1) {
                    $role = "User";
                }
                if($conversation->user->role == 2) {
                    $role = "Admin";
                }
                array_push($column_values, [$responseBody['columns'][1]['id'] => (string)$conversation->user->id ?? '']);
                array_push($column_values, [$responseBody['columns'][2]['id'] => (string)$conversation->user->first_name ?? '']);
                array_push($column_values, [$responseBody['columns'][3]['id'] => (string)$conversation->user->last_name ?? '']);
                array_push($column_values, [$responseBody['columns'][4]['id'] => (string)$conversation->user->email ?? '']);
                array_push($column_values, [$responseBody['columns'][5]['id'] => (string)'']);
                array_push($column_values, [$responseBody['columns'][6]['id'] => (string)$conversation->user->job_title ?? '']);
                array_push($column_values, [$responseBody['columns'][7]['id'] => (string)$conversation->user->timezone ?? '']);
                array_push($column_values, [$responseBody['columns'][8]['id'] => (string)$conversation->user->photo_url ?? '']);
                array_push($column_values, [$responseBody['columns'][9]['id'] => $role]);
                array_push($column_values, [$responseBody['columns'][10]['id'] => (string)$conversation->user->status ?? '']);
                array_push($column_values, [$responseBody['columns'][11]['id'] => (string)$conversation->user->created_at ?? '']);
                array_push($column_values, [$responseBody['columns'][12]['id'] => (string)$conversation->user->updated_at ?? '']);
            }

            if ($conversation->customer) {
                $phone_number = "";
                $jsonString = (string)$conversation->customer->phones;
                $jsonArray = json_decode($jsonString, true);
                if($jsonArray != "" && count($jsonArray)) {
                    $phone_number = $jsonArray[0]['value'];
                }
                array_push($column_values, [$responseBody['columns'][13]['id'] => (string)$conversation->customer->id ?? '']);
                array_push($column_values, [$responseBody['columns'][14]['id'] => (string)$conversation->customer->first_name ?? '']);
                array_push($column_values, [$responseBody['columns'][15]['id'] => (string)$conversation->customer->last_name ?? '']);
                array_push($column_values, [$responseBody['columns'][16]['id'] => (string)'']);
                array_push($column_values, [$responseBody['columns'][17]['id'] => (string)'']);
                array_push($column_values, [$responseBody['columns'][18]['id'] => (string)$phone_number ?? '']);
                array_push($column_values, [$responseBody['columns'][19]['id'] => (string)$conversation->customer->company ?? '']);
                array_push($column_values, [$responseBody['columns'][20]['id'] => (string)$conversation->customer->job_title ?? '']);
                array_push($column_values, [$responseBody['columns'][21]['id'] => (string)$conversation->customer->address ?? '']);
                array_push($column_values, [$responseBody['columns'][22]['id'] => (string)$conversation->customer->city ?? '']);
                array_push($column_values, [$responseBody['columns'][23]['id'] => (string)$conversation->customer->state ?? '']);
                array_push($column_values, [$responseBody['columns'][24]['id'] => (string)$conversation->customer->zip ?? '']);
                array_push($column_values, [$responseBody['columns'][25]['id'] => (string)$conversation->customer->country ?? '']);
                array_push($column_values, [$responseBody['columns'][26]['id'] => (string)$conversation->customer->websites ?? '']);
                array_push($column_values, [$responseBody['columns'][27]['id'] => (string)$conversation->customer->social_profiles ?? '']);
                array_push($column_values, [$responseBody['columns'][28]['id'] => (string)$conversation->customer->photo_url ?? '']);
                array_push($column_values, [$responseBody['columns'][29]['id'] => (string)$conversation->customer->created_at ?? '']);
                array_push($column_values, [$responseBody['columns'][30]['id'] => (string)$conversation->customer->updated_at ?? '']);
            }
            
            if($conversation) {
                array_push($column_values, [$responseBody['columns'][31]['id'] => (string)$conversation->id ?? '']);
                array_push($column_values, [$responseBody['columns'][32]['id'] => (string)$conversation->number ?? '']);
                array_push($column_values, [$responseBody['columns'][33]['id'] => (string)$conversation->subject ?? '']);
                array_push($column_values, [$responseBody['columns'][34]['id'] => (string)$conversation->customer_id ?? '']);
                array_push($column_values, [$responseBody['columns'][35]['id'] => (string)$conversation->mailbox_id ?? '']);
                array_push($column_values, [$responseBody['columns'][36]['id'] => (string)$conversation->folder_id ?? '']);
                array_push($column_values, [$responseBody['columns'][37]['id'] => (string)$conversation->status ?? '']);
                array_push($column_values, [$responseBody['columns'][38]['id'] => (string)$conversation->state ?? '']);
                array_push($column_values, [$responseBody['columns'][39]['id'] => (string)$conversation->preview ?? '']);
                array_push($column_values, [$responseBody['columns'][40]['id'] => (string)$conversation->source_via ?? '']);
                array_push($column_values, [$responseBody['columns'][41]['id'] => (string)$conversation->source_type ?? '']);
                array_push($column_values, [$responseBody['columns'][42]['id'] => (string)$conversation->created_at ?? '']);
                array_push($column_values, [$responseBody['columns'][43]['id'] => (string)$conversation->updated_at ?? '']);
                array_push($column_values, [$responseBody['columns'][44]['id'] => (string)$conversation->user_updated_at ?? '']);
                array_push($column_values, [$responseBody['columns'][45]['id'] => (string)$conversation->closed_at ?? '']);
                array_push($column_values, [$responseBody['columns'][46]['id'] => (string)'']);
                array_push($column_values, [$responseBody['columns'][47]['id'] => (string)$conversation->last_reply_at ?? '']);
                array_push($column_values, [$responseBody['columns'][48]['id'] => (string)$conversation->last_reply_from ?? '']);
                array_push($column_values, [$responseBody['columns'][49]['id'] => (string)$conversation->has_attachments ?? '']);
            }
            if($thread) {
                $meta = "";
                $jsonArray = $thread->meta;
                if($jsonArray != "" && count($jsonArray)) {
                    $meta = $jsonArray['channel'];
                }
                array_push($column_values, [$responseBody['columns'][50]['id'] => (string)$thread->id ?? '']);
                array_push($column_values, [$responseBody['columns'][51]['id'] => (string)$thread->conversation_id ?? '']);
                array_push($column_values, [$responseBody['columns'][52]['id'] => (string)$thread->user_id ?? '']);
                array_push($column_values, [$responseBody['columns'][53]['id'] => (string)$thread->customer_id ?? '']);
                array_push($column_values, [$responseBody['columns'][54]['id'] => (string)$thread->type ?? '']);
                array_push($column_values, [$responseBody['columns'][55]['id'] => (string)$thread->state ?? '']);
                array_push($column_values, [$responseBody['columns'][56]['id'] => (string)$thread->status ?? '']);
                array_push($column_values, [$responseBody['columns'][57]['id'] => (string)$thread->body ?? '']);
                array_push($column_values, [$responseBody['columns'][58]['id'] => (string)'']);
                array_push($column_values, [$responseBody['columns'][59]['id'] => (string)$thread->has_attachments ?? '']);
                array_push($column_values, [$responseBody['columns'][60]['id'] => (string)$thread->created_at ?? '']);
                array_push($column_values, [$responseBody['columns'][61]['id'] => (string)$thread->updated_at ?? '']);
                array_push($column_values, [$responseBody['columns'][62]['id'] => (string)$thread->source_via ?? '']);
                array_push($column_values, [$responseBody['columns'][63]['id'] => (string)$thread->source_type ?? '']);
                array_push($column_values, [$responseBody['columns'][64]['id'] => (string)$meta ?? '']);
            }

            array_push($column_values, [$responseBody['columns'][65]['id'] => (string)'']);
            array_push($column_values, [$responseBody['columns'][66]['id'] => (string)'']);
            array_push($column_values, [$responseBody['columns'][67]['id'] => (string)'']);

            $file_column_id = $responseBody['columns'][68]['id'];

            $filteredArray = array_filter($column_values, function ($subArray) {
                // Check if the sub-array contains non-empty values
                return !empty(array_filter($subArray));
            });
            $flattenedArray = [];
            foreach ($filteredArray as $subArray) {
                foreach ($subArray as $key => $value) {
                    $flattenedArray[$key] = $value;
                }
            }

            $column_values_json = json_encode($flattenedArray);
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
            $res = $this->mondayApi->sendPushboardItem($query);
            if(isset($res['data'])/* && $request->upload == 'yes'*/) {
                $item_id = $res['data']['create_item']['id'];
                $this->exportHtml($conversation_id);
                $response = $this->mondayApi->updateItem($item_id, $file_column_id);
                var_dump($response);
            }
        } catch (\Exception $e) {
            ActivityLog::create([
                'properties' => '[]',
                'log_name' => 'mondayintegration_errors',
                'description' => $e->getMessage(),
            ]);
        }
    }

    public function exportHtml($conversation_id) {
        $conversation = Conversation::with(['customer', 'threads.user'])
            ->where('id', $conversation_id)
            ->firstOrFail();

        $lang = 'he';
        $chatView = 'y'; // 'y' = chat view, 'n' = full thread

        $html = "
        <html>
            <head>
                <meta charset=\"UTF-8\">
                <title>" . ($lang === 'he' ? 'שיחה #' : 'Conversation #') . $conversation->id . "</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        padding: 20px;
                        direction: " . ($lang === 'he' ? 'rtl' : 'ltr') . ";
                    }
                    .conversation {
                        border: 1px solid #ccc;
                        padding: 10px;
                        margin: 15px;
                    }
                    .thread {
                        margin-bottom: 10px;
                        background: #f9f9f9;
                        padding: 5px;
                        white-space: pre-wrap;
                    }
                </style>
            </head>
            <body>
            <div class=\"conversation\">
                <h2>" . ($lang === 'he' ? 'שיחה #' : 'Conversation #') . " {$conversation->id}</h2>

                <p><strong>" . ($lang === 'he' ? 'נושא:' : 'Subject:') . "</strong> " . ($conversation->subject ?? '') . "</p>
                <p><strong>" . ($lang === 'he' ? 'סטטוס:' : 'Status:') . "</strong> " .
                    ($conversation->status === 2
                        ? ($lang === 'he' ? 'סגור' : 'Closed')
                        : ($lang === 'he' ? 'פתוח' : 'Open')) .
                "</p>
                <p><strong>" . ($lang === 'he' ? 'נוצר בתאריך:' : 'Created At:') . "</strong> " . $conversation->created_at->format('Y-m-d H:i:s') . "</p>

                <p><strong>" . ($lang === 'he' ? 'ערוץ:' : 'Channel:') . "</strong> " .
                    ($conversation->customer->channel === 13
                        ? '<img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" width="16"> ' . ($lang === 'he' ? 'ווטסאפ' : 'WhatsApp')
                        : ($lang === 'he' ? 'לא ידוע' : 'Unknown')) .
                "</p>

                <p><strong>" . ($lang === 'he' ? 'מספר זיהוי:' : 'Caller ID:') . "</strong> " . $conversation->customer->channel_id . "</p>

                <h4>" .
                    ($chatView === 'y'
                        ? ($lang === 'he' ? 'שיחה:' : 'Chat:')
                        : ($lang === 'he' ? 'שרשורים:' : 'Threads:')) .
                "</h4>";

                foreach ($conversation->threads as $thread) {
                    $first_name = "משתמש לא ידוע";
                    if($thread->created_by_customer_id != "") {
                        $first_name = $thread->customer->first_name;
                    }
                    if($thread->created_by_user_id != "") {
                        $first_name = User::find($thread->created_by_user_id)->first_name;
                    }
                    $html .= "<p class=\"thread\"><strong>{$first_name}:</strong> {$thread->body}</p>";
                }

                $html .= "
            </div>
            </body>
            </html>";

        $htmlPath = storage_path('logs/chat.html');
        file_put_contents($htmlPath, $html);
    }

    public function syncNow(Request $request)
    {
        $board_id = $request->board_id;
        $result = $this->mondayApi->getBoardItems($board_id, 200);
        foreach ($result['items_page']['items'] as $key => $value) {
            $name = $value['name'];
            $phone = $value['column_values'][0]['text'];
            $email = $value['column_values'][1]['text'];
            $phones = "[{\"value\":\"$phone\",\"type\":1,\"n\":\"$phone\"}]";

            if($email == '') continue;

            $existingCustomer = DB::table('customers')
                ->where('phones', $phones)
                ->first();

            if ($existingCustomer) {
                continue; // Skip if customer already exists
            }

            // Check if an email with the same address exists
            $existingEmail = DB::table('emails')
                ->where('email', $email)
                ->first();

            if ($existingEmail) {
                continue; // Skip if email already exists
            }

            // Create a new customer
            $customerId = DB::table('customers')->insertGetId([
                'first_name' => $name,
                'phones' => $phones,
            ]);

            // Create a new email associated with the customer
            DB::table('emails')->insert([
                'type' => 1,
                'customer_id' => $customerId,
                'email' => $email,
            ]);
        }
        return $result;
    }
}
