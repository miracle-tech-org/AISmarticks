<?php

namespace Modules\MondayIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\MondayIntegration\Models\MondaySettings;
use Modules\MondayIntegration\Models\MondayFieldMapping;
use Modules\MondayIntegration\Services\MondayApiService;
use App\Mailbox;

class FieldMappingController extends Controller
{
    protected $apiService;

    public function __construct(MondayApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function index($mailbox_id)
    {
        $settings = MondaySettings::all()->pluck('value', 'key')->toArray();
        $pullboardId = $settings['pullboard_id'] ?? null;
        $pushboardId = $settings['pushboard_id'] ?? null;

        // Get current mappings
        $pullMappings = MondayFieldMapping::where('board_type', 'pull')
            ->orderBy('order')
            ->get();

        $pushMappings = MondayFieldMapping::where('board_type', 'push')
            ->orderBy('order')
            ->get();

        // Get FreeScout fields
        $freescoutFields = $this->getFreescoutFields();

        // Get Monday.com columns for both boards
        $pullboardColumns = [];
        $pushboardColumns = [];
        $boards = [];

        try {
            if (!empty($settings['api_token'])) {
                $boards = $this->apiService->getBoards();
    
                if ($pullboardId) {
                    $pullboardColumns = $this->apiService->getBoardColumns($pullboardId);
                }
                
                if ($pushboardId) {
                    $pushboardColumns = $this->apiService->getBoardColumns($pushboardId);
                }
            }
        } catch (\Exception $e) {
            // Log the error
            \Log::error('MondayIntegration: Error retrieving board columns', [
                'error' => $e->getMessage()
            ]);
            
            // Flash error message to session
            session()->flash('error', $e->getMessage());
        }

        // Get available transform rules
        $transformRules = $this->getTransformRules();
        $mailbox = Mailbox::findOrFail($mailbox_id);

        return view('mondayintegration::settings.field_mapping', [
            'pullMappings' => $pullMappings,
            'pushMappings' => $pushMappings,
            'freescoutFields' => $freescoutFields,
            'pullboardColumns' => $pullboardColumns,
            'pushboardColumns' => $pushboardColumns,
            'transformRules' => $transformRules,
            'settings' => $settings,
            'mailbox' => $mailbox,
            'boards' => $boards,
        ]);
    }

    public function save(Request $request)
    {
        try {
            $searchKeyIndex = $request->input('search_key'); // Get selected row index
            $mappings = $request->input('mappings', []);

            MondayFieldMapping::truncate();
            // Create new mappings
            foreach ($mappings as $index => $data) {
                MondayFieldMapping::create([
                    'freescout_field' => $data['freescout_field'],
                    'monday_column' => $data['monday_column'],
                    'search_key' => (int)($index == (int)$searchKeyIndex), // true only for selected
                    'is_overwrite' => isset($data['is_overwrite']) ? 1 : 0,
                ]);
            }
            return redirect()->back();
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    protected function getFreescoutFields()
    {
        return [
            'Conversation' => [
                'conversation.number' => __('Conversation Number'),
                'conversation.subject' => __('Subject'),
                'conversation.status' => __('Status'),
                'conversation.created_at' => __('Created Date'),
                'conversation.updated_at' => __('Updated Date')
            ],
            'Customer' => [
                'customer.first_name' => __('First Name'),
                'customer.last_name' => __('Last Name'),
                'customer.email' => __('Email'),
                'customer.phone' => __('Phone'),
                'customer.company' => __('Company')
            ],
            'Assignee' => [
                'assignee.first_name' => __('Agent First Name'),
                'assignee.last_name' => __('Agent Last Name'),
                'assignee.email' => __('Agent Email')
            ],
            'Custom Fields' => [
                'custom_fields.priority' => __('Priority'),
                'custom_fields.category' => __('Category'),
                'custom_fields.due_date' => __('Due Date')
            ]
        ];
    }

    protected function getTransformRules()
    {
        return [
            'uppercase' => __('Convert to Uppercase'),
            'lowercase' => __('Convert to Lowercase'),
            'date_format' => __('Format Date'),
            'boolean' => __('Convert to Yes/No'),
            'json' => __('Convert to JSON')
        ];
    }

    public function export(Request $request)
    {
        $boardType = $request->input('board_type', 'all');
        
        // Get mappings based on board type
        if ($boardType === 'pull') {
            $mappings = MondayFieldMapping::where('board_type', 'pull')->get();
        } elseif ($boardType === 'push') {
            $mappings = MondayFieldMapping::where('board_type', 'push')->get();
        } else {
            $mappings = MondayFieldMapping::all();
        }
        
        // Format the data for export
        $data = [
            'board_type' => $boardType,
            'mappings' => $mappings->toArray(),
            'exported_at' => now()->toIso8601String(),
            'version' => '1.0'
        ];
        
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'monday_mappings_');
        file_put_contents($tempFile, json_encode($data, JSON_PRETTY_PRINT));
        
        // Generate filename based on board type
        $filename = 'monday_' . $boardType . '_mappings_' . date('Y-m-d') . '.json';
        
        // Return as a downloadable file
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ])->deleteFileAfterSend(true);
    }

    public function import(Request $request)
    {
        try {
            $file = $request->file('mapping_file');
            $content = file_get_contents($file->getPathname());
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(__('Invalid JSON file'));
            }
            
            if (!isset($data['mappings']) || !is_array($data['mappings'])) {
                throw new \Exception(__('Invalid mapping file format'));
            }
            
            // Validate mappings
            foreach ($data['mappings'] as $mapping) {
                if (!isset($mapping['board_type']) || !in_array($mapping['board_type'], ['pull', 'push'])) {
                    throw new \Exception(__('Invalid board type in mapping'));
                }
                
                if (empty($mapping['freescout_field']) || empty($mapping['monday_column'])) {
                    throw new \Exception(__('Missing required fields in mapping'));
                }
            }
            
            // Start transaction
            \DB::beginTransaction();
            
            // If overwrite is true, delete existing mappings
            if ($request->input('overwrite')) {
                $boardTypes = collect($data['mappings'])->pluck('board_type')->unique();
                
                foreach ($boardTypes as $boardType) {
                    MondayFieldMapping::where('board_type', $boardType)->delete();
                }
            }
            
            // Import mappings
            foreach ($data['mappings'] as $index => $mapping) {
                // Skip if not overwriting and a mapping with the same fields already exists
                if (!$request->input('overwrite')) {
                    $exists = MondayFieldMapping::where('board_type', $mapping['board_type'])
                        ->where('freescout_field', $mapping['freescout_field'])
                        ->where('monday_column', $mapping['monday_column'])
                        ->exists();
                    
                    if ($exists) {
                        continue;
                    }
                }
                
                // Get the highest order for this board type
                $maxOrder = MondayFieldMapping::where('board_type', $mapping['board_type'])
                    ->max('order');
                
                MondayFieldMapping::create([
                    'board_type' => $mapping['board_type'],
                    'freescout_field' => $mapping['freescout_field'],
                    'monday_column' => $mapping['monday_column'],
                    'transform_rule' => $mapping['transform_rule'] ?? null,
                    'default_value' => $mapping['default_value'] ?? null,
                    'is_required' => !empty($mapping['is_required']),
                    'order' => $maxOrder !== null ? $maxOrder + 1 : 0
                ]);
            }
            
            \DB::commit();
            
            // return response()->json([
            //     'status' => 'success',
            //     'message' => __('Field mappings imported successfully')
            // ]);
            return redirect()->back();

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('MondayIntegration: Error importing field mappings', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function suggestMappings(Request $request)
    {
        $boardType = $request->input('board_type');
        $settings = MondaySettings::all()->pluck('value', 'key')->toArray();
        
        // Determine which board to use based on board type
        if ($boardType === 'pull') {
            $boardId = $settings['pullboard_id'] ?? null;
        } else {
            $boardId = $settings['pushboard_id'] ?? null;
        }

        if (!$boardId) {
            return response()->json([
                'status' => 'error',
                'message' => __('Board ID not configured for ' . $boardType . ' board type.')
            ], 422);
        }

        try {
            // Get board columns
            $decryptedToken = $this->security->decrypt($settings['api_token']);
            $this->apiService->setApiToken($decryptedToken);
            $columns = $this->apiService->getBoardColumns($boardId);
            
            // Get FreeScout fields
            $freescoutFields = $this->getFreescoutFields();
            
            // Generate suggestions
            $suggestions = $this->assistant->suggestMappings($columns, $freescoutFields, $boardType);
            
            return response()->json([
                'status' => 'success',
                'suggestions' => $suggestions
            ]);
        } catch (\Exception $e) {
            Log::error('MondayIntegration: Error generating mapping suggestions', [
                'error' => $e->getMessage(),
                'board_type' => $boardType
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function validateCompatibility(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'column_type' => 'required|string',
            'field' => 'required|string',
            'transform_rule' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => __('Invalid form data'),
                'errors' => $validator->errors()
            ], 422);
        }

        $columnType = $request->input('column_type');
        $field = $request->input('field');
        $transformRule = $request->input('transform_rule');

        $result = $this->assistant->validateCompatibility($columnType, $field, $transformRule);

        return response()->json([
            'status' => 'success',
            'result' => $result
        ]);
    }
}
