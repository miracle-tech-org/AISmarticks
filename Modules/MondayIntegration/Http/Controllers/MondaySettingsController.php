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
use App\Jobs\SendAlert;
use Carbon;
use Schema;

class MondaySettingsController extends Controller
{
    protected $mondayApi;
    protected $security;

    private $countryPhoneCodes = [
        "AF" => "93", "AL" => "355", "DZ" => "213", "AS" => "1", "AD" => "376",
        "AO" => "244", "AI" => "1", "AQ" => "672", "AG" => "1", "AR" => "54",
        "AM" => "374", "AW" => "297", "AU" => "61", "AT" => "43", "AZ" => "994",
        "BS" => "1", "BH" => "973", "BD" => "880", "BB" => "1", "BY" => "375",
        "BE" => "32", "BZ" => "501", "BJ" => "229", "BM" => "1", "BT" => "975",
        "BO" => "591", "BA" => "387", "BW" => "267", "BR" => "55", "IO" => "246",
        "VG" => "1", "BN" => "673", "BG" => "359", "BF" => "226", "BI" => "257",
        "KH" => "855", "CM" => "237", "CA" => "1", "CV" => "238", "KY" => "1",
        "CF" => "236", "TD" => "235", "CL" => "56", "CN" => "86", "CX" => "61",
        "CC" => "61", "CO" => "57", "KM" => "269", "CK" => "682", "CR" => "506",
        "HR" => "385", "CU" => "53", "CW" => "599", "CY" => "357", "CZ" => "420",
        "CD" => "243", "DK" => "45", "DJ" => "253", "DM" => "1", "DO" => "1",
        "TL" => "670", "EC" => "593", "EG" => "20", "SV" => "503", "GQ" => "240",
        "ER" => "291", "EE" => "372", "ET" => "251", "FK" => "500", "FO" => "298",
        "FJ" => "679", "FI" => "358", "FR" => "33", "GF" => "594", "PF" => "689",
        "GA" => "241", "GM" => "220", "GE" => "995", "DE" => "49", "GH" => "233",
        "GI" => "350", "GR" => "30", "GL" => "299", "GD" => "1", "GP" => "590",
        "GU" => "1", "GT" => "502", "GG" => "44", "GN" => "224", "GW" => "245",
        "GY" => "592", "HT" => "509", "HN" => "504", "HK" => "852", "HU" => "36",
        "IS" => "354", "IN" => "91", "ID" => "62", "IR" => "98", "IQ" => "964",
        "IE" => "353", "IM" => "44", "IL" => "972", "IT" => "39", "CI" => "225",
        "JM" => "1", "JP" => "81", "JE" => "44", "JO" => "962", "KZ" => "7",
        "KE" => "254", "KI" => "686", "XK" => "383", "KW" => "965", "KG" => "996",
        "LA" => "856", "LV" => "371", "LB" => "961", "LS" => "266", "LR" => "231",
        "LY" => "218", "LI" => "423", "LT" => "370", "LU" => "352", "MO" => "853",
        "MK" => "389", "MG" => "261", "MW" => "265", "MY" => "60", "MV" => "960",
        "ML" => "223", "MT" => "356", "MH" => "692", "MQ" => "596", "MR" => "222",
        "MU" => "230", "YT" => "262", "MX" => "52", "FM" => "691", "MD" => "373",
        "MC" => "377", "MN" => "976", "ME" => "382", "MS" => "1", "MA" => "212",
        "MZ" => "258", "MM" => "95", "NA" => "264", "NR" => "674", "NP" => "977",
        "NL" => "31", "NC" => "687", "NZ" => "64", "NI" => "505", "NE" => "227",
        "NG" => "234", "NU" => "683", "NF" => "672", "KP" => "850", "MP" => "1",
        "NO" => "47", "OM" => "968", "PK" => "92", "PW" => "680", "PS" => "970",
        "PA" => "507", "PG" => "675", "PY" => "595", "PE" => "51", "PH" => "63",
        "PN" => "64", "PL" => "48", "PT" => "351", "PR" => "1", "QA" => "974",
        "CG" => "242", "RE" => "262", "RO" => "40", "RU" => "7", "RW" => "250",
        "BL" => "590", "SH" => "290", "KN" => "1", "LC" => "1", "MF" => "590",
        "PM" => "508", "VC" => "1", "WS" => "685", "SM" => "378", "ST" => "239",
        "SA" => "966", "SN" => "221", "RS" => "381", "SC" => "248", "SL" => "232",
        "SG" => "65", "SX" => "1", "SK" => "421", "SI" => "386", "SB" => "677",
        "SO" => "252", "ZA" => "27", "GS" => "500", "SS" => "211", "ES" => "34",
        "LK" => "94", "SD" => "249", "SR" => "597", "SJ" => "47", "SZ" => "268",
        "SE" => "46", "CH" => "41", "SY" => "963", "TW" => "886", "TJ" => "992",
        "TZ" => "255", "TH" => "66", "TG" => "228", "TK" => "690", "TO" => "676",
        "TT" => "1", "TN" => "216", "TR" => "90", "TM" => "993", "TC" => "1",
        "TV" => "688", "UG" => "256", "UA" => "380", "AE" => "971", "GB" => "44",
        "US" => "1", "UY" => "598", "UZ" => "998", "VU" => "678", "VA" => "379",
        "VE" => "58", "VN" => "84", "VI" => "1", "WF" => "681", "EH" => "212",
        "YE" => "967", "ZM" => "260", "ZW" => "263",
    ];

    private function hasCountryCode(string $phone): bool
    {
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        if (str_starts_with($cleanPhone, '+')) {
            return true;
        }
        if (str_starts_with($cleanPhone, '00')) {
            $numberAfter00 = substr($cleanPhone, 2); // Get digits after '00'
            if (strlen($numberAfter00) < 8 || preg_match('/^0+$/', $numberAfter00)) {
                return false;
            }
            foreach ($this->countryPhoneCodes as $code) {
                if (str_starts_with($numberAfter00, $code)) {
                    $remainingPart = substr($numberAfter00, strlen($code));
                    if (!preg_match('/^0+$/', $remainingPart)) {
                        return true; // Found a valid-looking international number
                    }
                }
            }
            return false; // No valid country code found after '00'
        }
        return false;
    }

    private function getCountryCode(string $countryCode): ?string
    {
        $countryCode = strtoupper($countryCode);
        return $this->countryPhoneCodes[$countryCode] ?? null;
    }

    private function formatLocalPhoneNumber(string $localPhoneNumber, string $countryCode): string
    {
        $callingCode = $this->getCountryCode($countryCode);
        if (is_null($callingCode)) {
            return $localPhoneNumber;
        }
        $cleanLocalNumber = preg_replace('/[^0-9]/', '', $localPhoneNumber);
        if (str_starts_with($cleanLocalNumber, '0')) {
            $cleanLocalNumber = substr($cleanLocalNumber, 1);
        }
        return $callingCode . $cleanLocalNumber;
    }

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
            MondaySettings::whereIn('key', ['push_on_conversation_created', 'push_on_conversation_closed', 'push_on_conversation_updated', 'push_on_conversation_fully_closed', 'push_attachments'])->delete();
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
                'message' => __('Connection successful'),
                'info' => $result
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
            $result = $this->mondayApi->testConnection();
            
            return response()->json([
                'status' => 'success',
                'message' => __('Successfully connected to board :name with :count columns', [
                    'name' => $board['name'],
                    'count' => count($columns)
                ]),
                'info' => $result
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
        $results = DB::select('SELECT * FROM customer_fields');
        $customerFields = [];
        foreach ($results as $key => $value) {
            $customerFields[('customer_fields.' . $value->id)] = $value->name . ' (text)';
        }
        // $results = DB::select('SELECT * FROM custom_fields');
        // foreach ($results as $key => $value) {
        //     $customerFields[('custom_fields.' . $key)] = $value->name;
        // }

        $freescoutFields = [
            'Customer' => [
                'customers.first_name' => __('First Name (text)'),
                'customers.last_name' => __('Last Name (text)'),
                'emails.email' => __('Email (text)'),
                'customers.phones' => __('Phones (text)'),
                'customers.company' => __('Company (text)'),
                'customers.websites' => __('Website (text)'),
                'customers.job_title' => __('Job Title (text)'),
                'customers.photo_type' => __('Photo Type (number)'),
                'customers.photo_url' => __('Photo Url (text)'),
                'customers.social_profiles' => __('Social Profiles (text)'),
                'customers.notes' => __('Notes (text)'),
                'customers.address' => __('Address (text)'),
                'customers.city' => __('City (text)'),
                'customers.state' => __('State (text)'),
                'customers.zip' => __('Zip (text)'),
                'customers.country' => __('Country (text)'),
            ],
            'Custom Fields' => $customerFields
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

        // var_dump($pullMappings->toArray());
        // exit();

        $logFile = storage_path('logs/sync_status.log');
        $logs = [];

        if (file_exists($logFile)) {
            $logs = file($logFile, FILE_IGNORE_NEW_LINES);
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
            'logs' => $logs
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
            $columns = $this->mondayApi->createColumn($board_id, $fields);
            $res = [
                'board_id' => $board_id,
                'columns' => $columns
            ];
            return $res;
        } else {
            $columns = $this->mondayApi->createColumnsWithAllFields($board_id);
            $res = [
                'board_id' => $board_id,
                'columns' => $columns
            ];
            return $res;
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
            $settings = MondaySettings::all()->pluck('value', 'key')->toArray();

            $pushboard_id = DB::table('monday_settings')
                ->where('key', 'pushboard_id')
                ->value('value');
    
            $responseBody = $this->mondayApi->getBoardById($pushboard_id);
            $conversation_id = $request->conversation_id;
            $thread_id = $request->thread_id;
            $conversation = Conversation::find($conversation_id);
            $thread = Thread::find($thread_id);
            // if($thread->created_by_user_id != '')
            //     return;
            
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
                array_push($column_values, [$responseBody['columns'][5]['id'] => (string)$conversation->user->phone ?? '']);
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
                $email = Email::where('customer_id', $conversation->customer->id)->value('email') ?? '';
                array_push($column_values, [$responseBody['columns'][13]['id'] => (string)$conversation->customer->id ?? '']);
                array_push($column_values, [$responseBody['columns'][14]['id'] => (string)$conversation->customer->first_name ?? '']);
                array_push($column_values, [$responseBody['columns'][15]['id'] => (string)$conversation->customer->last_name ?? '']);
                array_push($column_values, [$responseBody['columns'][16]['id'] => (string)$email]);
                array_push($column_values, [$responseBody['columns'][17]['id'] => (string)$email]);
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
                for($i = 66; isset($responseBody['columns'][$i]); $i++) {
                    $customerFieldName = $responseBody['columns'][$i]['title'];
                    $customerFieldId = DB::table('customer_fields')
                        ->where('name', $customerFieldName)
                        ->value('id');

                    $fieldValue = DB::table('customer_customer_field')
                        ->where('customer_id', $conversation->customer->id)
                        ->where('customer_field_id', $customerFieldId)
                        ->value('value');

                    array_push($column_values, [$responseBody['columns'][$i]['id'] => (string)($fieldValue ?? '')]);
                }
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
                if($meta == "") {
                    if($conversation->customer_email != "") $meta = "email";
                    else $meta = "telegram";
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

            $file_column_id = $responseBody['columns'][65]['id'];
            
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
            if(isset($res['data']) && $request->upload == 'yes') {
                $item_id = $res['data']['create_item']['id'];
                $htmlContent = $this->exportHtml($conversation_id);
                try {
                    if(isset($settings['html_display_method']) && $settings['html_display_method'] == 'update') {

                        $response = $this->mondayApi->addHtmlUpdate($item_id, $htmlContent);
            
                        // Log success 
                        \Log::info('MondayIntegration: Successfully attached HTML to Monday item', [
                            'item_id' => $item_id,
                            'conversation_id' => $conversation_id
                        ]);
                        return $response;
                    } else {
                        $response = $this->mondayApi->updateItem($item_id, $file_column_id);
                        // Log success 
                        \Log::info('MondayIntegration: Successfully attached HTML to Monday item', [
                            'item_id' => $item_id,
                            'conversation_id' => $conversation_id
                        ]);
                        return $response;
                    }
        
                } catch (\Exception $e) {
                    \Log::error('MondayIntegration: Failed to attach HTML to Monday item', [
                        'item_id' => $item_id,
                        'conversation_id' => $conversation_id,
                        'error' => $e->getMessage()
                    ]);
                    return ['error' => $e->getMessage()];
                }
            }
        } catch (\Exception $e) {
            $alert = new SendAlert("Monday: Push Error\n".$e->getMessage());
            $alert->handle();

            ActivityLog::create([
                'properties' => '[]',
                'log_name' => 'mondayintegration_errors',
                'description' => $e->getMessage(),
            ]);
        }
    }

    public function exportHtml($conversation_id, $saveToFile = true) {
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
                    ($conversation->status === 3
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
                    if($thread->body != "")
                        $html .= "<p class=\"thread\"><strong>{$first_name}:</strong> {$thread->body}</p>";
                }

                $html .= "
            </div>
            </body>
            </html>";

        if($saveToFile) {
            $htmlPath = storage_path('logs/chat.html');
            file_put_contents($htmlPath, $html);
        }
        return $html;
    }

    public function syncNow(Request $request)
    {
        $settings = MondaySettings::all()->pluck('value', 'key')->toArray();
        $mappings = MondayFieldMapping::all();

        $boards = $this->mondayApi->getBoards();
        $status = false;

        $pull_exist = false;
        foreach ($boards['boards'] as $board) {
            if($board['id'] == $settings['pullboard_id']) {
                $pull_exist = true;
                break;
            }
        }
        if($pull_exist == false) {
            $alert = new SendAlert("Monday: Pullboard doesn't exist. please configure pullboard");
            $alert->handle();
            return [];
        }

        $board_id = $settings['pullboard_id'];
        $result = $this->mondayApi->getBoardItems($board_id, 500);

        $search_monday_key = "";
        $search_freescout_key = "";
        foreach ($mappings as $value) {
            if($value['search_key'] == 1) {
                $search_monday_key = $value['monday_column'];
                $search_freescout_key = $value["freescout_field"];
            }
        }
        if($search_freescout_key == "") {
            return [];
        }

        $mappings = MondayFieldMapping::where('is_overwrite', 1)->get();

        foreach ($result['items_page']['items'] as $key => $value) {
            $query = "SELECT customers.id AS customer_id, customers.first_name, customers.last_name, emails.email, emails.id AS email_id, customers.phones, customers.company, customers.websites, customers.job_title, customers.photo_type, customers.photo_url, customers.social_profiles, customers.notes, customers.address, customers.city, customers.state, customers.zip, customers.country FROM customers LEFT JOIN emails ON customers.id = emails.customer_id WHERE $search_freescout_key = ?";
            $found = false;
            $searchValue = "";
            if($search_monday_key == "name") {
                $searchValue = $value['name'];
                $found = true;
            }

            if($found == false) {
                foreach ($value['column_values'] as $col) {
                    if($col['id'] == $search_monday_key) {
                        $searchValue = $col['text'];
                        if($col['type'] == 'phone') {
                            if($searchValue != "") {
                                if(!$this->hasCountryCode($searchValue)) {
                                    $searchValue = $this->formatLocalPhoneNumber($searchValue, $settings['country_code']);
                                }
                            }
                            $searchValue = "[{\"value\":\"$searchValue\",\"type\":1,\"n\":\"$searchValue\"}]";
                        }
                        $found = true;
                        break;
                    }
                }
            }
            if($searchValue == "")
                continue;

            if($found) {
                $res = DB::select($query, [$searchValue]);
                if(!empty($res)) {
                    foreach ($res as $row) {
                        $customerId = $row->customer_id;
                        $emailId = $row->email_id;
                    
                        $customerUpdates = [];
                        $emailUpdates = [];
                        $customerFieldUpdates = [];
    
                        foreach ($mappings as $mapping) {
                            $mondayColumnId = $mapping->monday_column;
                            $freescoutField = $mapping->freescout_field;
                    
                            // Find the value in Monday item
                            $mondayValue = null;
                            if ($mondayColumnId === 'name') {
                                $mondayValue = $value['name'];
                            } else {
                                foreach ($value['column_values'] as $col) {
                                    if ($col['id'] === $mondayColumnId) {
                                        $mondayValue = $col['text'];
                                        break;
                                    }
                                }
                            }
                    
                            if (!is_null($mondayValue)) {
                                // Determine if this field is in customers or emails table
                                if ($freescoutField == "emails.email") {
                                    $field = str_replace("emails.", "", $freescoutField);
                                    $emailUpdates[$field] = $mondayValue;
                                }
                                else if(strstr($freescoutField, "customers.")) {
                                    $field = str_replace("customers.", "", $freescoutField);
                                    if($field == 'phones') {
                                        if($mondayValue != "") {
                                            if(!$this->hasCountryCode($mondayValue)) {
                                                $mondayValue = $this->formatLocalPhoneNumber($mondayValue, $settings['country_code']);
                                            }
                                        }                                        $customerUpdates[$field] = "[{\"value\":\"$mondayValue\",\"type\":1,\"n\":\"$mondayValue\"}]";
                                    } else {
                                        $customerUpdates[$field] = $mondayValue;
                                    }
                                }
                                else {
                                    $field = str_replace("customer_fields.", "", $freescoutField);
                                    array_push($customerFieldUpdates, [
                                        'customer_field_id' => $field,
                                        'customer_id' => $customerId,
                                        'value' => $mondayValue,
                                    ]);
                                }
                            } else {
                                $status = false;
                            }
                        }

                        if (!empty($customerUpdates)) {
                            DB::table('customers')
                                ->where('id', $customerId)
                                ->update($customerUpdates);
                        }

                        if (!empty($emailUpdates)) {
                            DB::table('emails')
                                ->where('id', $emailId)
                                ->update($emailUpdates);
                        }
                        if(!empty($customerFieldUpdates)) {
                            DB::table('customer_customer_field')
                                ->where('customer_id', $customerId)
                                ->delete();

                            foreach ($customerFieldUpdates as $cfu) {
                                DB::table('customer_customer_field')
                                    ->insert([
                                        'customer_id' => $cfu['customer_id'],
                                        'customer_field_id' => $cfu['customer_field_id'],
                                        'value' => $cfu['value']
                                    ]);
                            }
                        }
                    }
                }
                else {
                    $customerInsert = [];
                    $emailInsert = [];
                    $customerFieldInsert = [];

                    foreach ($mappings as $mapping) {
                        $mondayColumnId = $mapping->monday_column;
                        $freescoutField = $mapping->freescout_field;

                        // Get value from Monday item
                        $mondayValue = null;
                        if ($mondayColumnId === 'name') {
                            $mondayValue = $value['name'];
                        } else {
                            foreach ($value['column_values'] as $col) {
                                if ($col['id'] === $mondayColumnId) {
                                    $mondayValue = $col['text'];
                                    break;
                                }
                            }
                        }
                        if (!is_null($mondayValue)) {
                            if ($freescoutField == "emails.email") {
                                $field = str_replace("emails.", "", $freescoutField);
                                $emailInsert[$field] = $mondayValue;
                            }
                            else if(strstr($freescoutField, "customers.")) {
                                $field = str_replace("customers.", "", $freescoutField);
                                if($field == 'phones') {
                                    if($mondayValue != "") {
                                        if(!$this->hasCountryCode($mondayValue)) {
                                            $mondayValue = $this->formatLocalPhoneNumber($mondayValue, $settings['country_code']);
                                        }
                                    }
                                    $customerInsert[$field] = "[{\"value\":\"$mondayValue\",\"type\":1,\"n\":\"$mondayValue\"}]";
                                } else {
                                    $customerInsert[$field] = $mondayValue;
                                }
                            }
                            else {
                                $field = str_replace("customer_fields.", "", $freescoutField);
                                array_push($customerFieldInsert, [
                                    'customer_field_id' => $field,
                                    'value' => $mondayValue,
                                ]);
                            }
                        }
                    }

                    // Step 2: Insert customer and get ID
                    $customerId = DB::table('customers')->insertGetId($customerInsert);

                    // Step 3: Insert email and link to customer
                    if(count($emailInsert) != 0 && $emailInsert['email'] != "") {
                        $emailInsert['customer_id'] = $customerId;
                        DB::table('emails')->insert($emailInsert);
                    }

                    if(!empty($customerFieldInsert)) {
                        DB::table('customer_customer_field')
                            ->where('customer_id', $customerId)
                            ->delete();

                        foreach ($customerFieldInsert as $cfu) {
                            DB::table('customer_customer_field')
                                ->insert([
                                    'customer_id' => $customerId,
                                    'customer_field_id' => $cfu['customer_field_id'],
                                    'value' => $cfu['value']
                                ]);
                        }
                    }
                }
            }
            $status = true;
        }

        if($request->isClick != 'yes') {
            $logFile = storage_path('logs/sync_status.log');
            $status = $status ? 'successfully' : 'unsuccessfully';
            $timestamp = now()->format('d.m.Y H:i');
            $logLine = "Synced at $timestamp, status- $status";

            // Read existing log lines
            $lines = file_exists($logFile) ? file($logFile, FILE_IGNORE_NEW_LINES) : [];

            // Add new line
            $lines[] = $logLine;

            // Keep only the last 3
            $lines = array_slice($lines, -3);

            // Save back to file
            file_put_contents($logFile, implode(PHP_EOL, $lines));
        }
        return $result;
    }
}
