<?php

namespace Modules\MondayIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\MondayIntegration\Models\MondayFieldMapping;
use Modules\MondayIntegration\Services\MondayApiService;

class FieldMappingController extends Controller
{
    protected $apiService;

    public function __construct(MondayApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function index()
    {
        // Get current mappings
        $mappings = MondayFieldMapping::orderBy('order')->get();

        // Get FreeScout fields
        $freescoutFields = $this->getFreescoutFields();

        // Get Monday.com columns
        try {
            $mondayColumns = $this->apiService->getBoardColumns();
        } catch (\Exception $e) {
            $mondayColumns = [];
        }

        // Get available transform rules
        $transformRules = $this->getTransformRules();

        return view('mondayintegration::mappings.field_mapping', compact(
            'mappings',
            'freescoutFields',
            'mondayColumns',
            'transformRules'
        ));
    }

    public function save(Request $request)
    {
        $this->validate($request, [
            'mappings' => 'required|array',
            'mappings.*.smarticks_field' => 'required',
            'mappings.*.monday_column' => 'required',
            'mappings.*.transform_rule' => 'nullable',
            'mappings.*.default_value' => 'nullable',
            'mappings.*.is_required' => 'boolean'
        ]);

        try {
            // Start transaction
            \DB::beginTransaction();

            // Delete existing mappings
            MondayFieldMapping::truncate();

            // Create new mappings
            foreach ($request->mappings as $index => $mapping) {
                MondayFieldMapping::create([
                    'smarticks_field' => $mapping['smarticks_field'],
                    'monday_column' => $mapping['monday_column'],
                    'transform_rule' => $mapping['transform_rule'],
                    'default_value' => $mapping['default_value'],
                    'is_required' => !empty($mapping['is_required']),
                    'order' => $index
                ]);
            }

            \DB::commit();

            return response()->json([
                'message' => __('Field mappings saved successfully')
            ]);
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
}
