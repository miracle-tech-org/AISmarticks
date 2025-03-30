<?php

namespace Modules\MondayIntegration\Http\Controllers;

use App\ActivityLog as Activity;
use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\Thread;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log as Logger;
use Illuminate\Support\Facades\Validator;
use Modules\MondayIntegration\Models\MondayCallerIdMapping;
use Modules\MondayIntegration\Models\MondaySettings;
use Modules\MondayIntegration\Services\CallerIdService;
use Modules\MondayIntegration\Services\MondayApiService;
use Modules\MondayIntegration\Services\PullboardService;
use Modules\MondayIntegration\Services\SecurityService;

class SidebarController extends Controller
{
    /**
     * @var MondayApiService
     */
    protected $mondayApi;

    /**
     * @var CallerIdService
     */
    protected $callerIdService;
    
    /**
     * @var PullboardService
     */
    protected $pullboardService;

    /**
     * Constructor
     */
    public function __construct(
        CallerIdService $callerIdService,
        PullboardService $pullboardService,
        MondayApiService $mondayApi
    ) {
        // $this->middleware('auth');
        
        // Initialize services
        $this->callerIdService = $callerIdService;
        $this->pullboardService = $pullboardService;
        $this->mondayApi = $mondayApi;
    }

    public function index($mailbox_id) {
        return $mailbox_id;
    }

    /**
     * Display the sidebar widget with Monday.com data
     * 
     * @param int $conversationId
     * @return \Illuminate\View\View|string
     */
    public function sidebarWidget($conversationId)
    {
        // Get the conversation
        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            return '';
        }

        // Get the customer
        $customer = $conversation->customer;
        if (!$customer) {
            return '';
        }

        // Get settings
        $settings = $this->getSettings();
        if (empty($settings['api_token']) || empty($settings['board_id'])) {
            return '';
        }

        // Get Monday.com items for this customer
        $skipCache = request()->has('skip_cache');
        $boardType = request()->get('board_type', 'primary');
        $mondayItems = $this->getMondayItemsForCustomer($customer, $conversation, $settings, $skipCache, $boardType);

        // Get match information if available
        $matchInfo = $this->getMatchInfo($customer, $conversation);

        // Get quick actions
        $quickActions = $this->getConfiguredQuickActions($settings);

        // If no items found, render empty widget
        if (empty($mondayItems) || empty($mondayItems['data']['items'])) {
            return $this->renderEmptyWidget($conversation, $customer);
        }

        return view('mondayintegration::sidebar.caller_widget', [
            'mondayItems' => $mondayItems,
            'quickActions' => $quickActions,
            'settings' => $settings,
            'conversation' => $conversation,
            'customer' => $customer,
            'matchInfo' => $matchInfo
        ]);
    }

    /**
     * Execute a quick action on a Monday.com item
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeQuickAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|string',
            'action_type' => 'required|string',
            'action_data' => 'required|array',
            'conversation_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $itemId = $request->input('item_id');
        $actionType = $request->input('action_type');
        $action = $request->input('action_data');
        $conversationId = $request->input('conversation_id');

        try {
            $result = null;
            
            switch ($actionType) {
                case 'status_change':
                    // Use updateItem method to change the status column value
                    $result = $this->mondayApi->updateItem($itemId, [
                        'status' => ['label' => $action['status_value']]
                    ]);
                    break;
                case 'add_update':
                    $updateText = $this->processUpdateText($action['update_text'], $conversationId);
                    $result = $this->mondayApi->addUpdate($itemId, $updateText);
                    break;
                case 'custom_mutation':
                    $result = $this->mondayApi->executeQuery($action['mutation'], [
                        'item_id' => $itemId,
                        'conversation_id' => $conversationId
                    ]);
                    break;
                default:
                    return response()->json(['success' => false, 'message' => __('Unsupported action type')], 400);
            }

            return response()->json([
                'success' => true,
                'message' => __('Action executed successfully'),
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Logger::error('MondayIntegration: Error executing quick action', [
                'action' => $action,
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Error executing action: ') . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new Monday.com item for a customer
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createItem(Request $request): JsonResponse
    {
        $customerId = $request->input('customer_id');
        $conversationId = $request->input('conversation_id');

        if (empty($customerId)) {
            return response()->json(['success' => false, 'message' => __('Missing customer ID')], 400);
        }

        // Get customer
        $customer = Customer::find($customerId);
        if (!$customer) {
            return response()->json(['success' => false, 'message' => __('Customer not found')], 404);
        }

        // Get conversation if provided
        $conversation = null;
        if ($conversationId) {
            $conversation = Conversation::find($conversationId);
        }

        // Get settings
        $settings = $this->getSettings();
        if (empty($settings['api_token']) || empty($settings['board_id'])) {
            return response()->json(['success' => false, 'message' => __('API not fully configured')], 400);
        }

        try {
            // Prepare customer data
            $itemName = $customer->getFullName() ?: $customer->email;
            $customerData = [
                'name' => $itemName,
                'email' => $customer->email,
                'phone' => $customer->getPhoneNumber(),
                'conversation_id' => $conversation ? $conversation->id : null
            ];
            
            // Use PullboardService to create the customer item
            $result = $this->pullboardService->createCustomer($customerData, $settings['board_id']);
            
            if ($result && !empty($result['id'])) {
                $itemId = $result['id'];

                // Create mapping
                if ($customer->email) {
                    $this->callerIdService->createMapping(
                        $customer->email,
                        $itemId,
                        'email',
                        0.9, // Higher confidence score with optimized service
                        null, // No conversation object
                        $settings['board_id']
                    );
                }

                if ($customer->getPhoneNumber()) {
                    $this->callerIdService->createMapping(
                        $customer->getPhoneNumber(),
                        $itemId,
                        'phone',
                        0.8, // Higher confidence for phone matches with optimized service
                        null, // No conversation object
                        $settings['board_id']
                    );
                }

                // Clear cache
                $this->clearCustomerCache($customer->id);
                $this->pullboardService->clearCache(); // Also clear PullboardService cache

                return response()->json([
                    'success' => true,
                    'message' => __('Monday.com item created successfully'),
                    'data' => $result
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => __('Failed to create Monday.com item')
            ], 500);
        } catch (\Exception $e) {
            Logger::error('MondayIntegration: Error creating Monday.com item', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Error creating Monday.com item: ') . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Monday.com items related to a customer
     * Uses CallerIdService to check local mappings first before API search
     * 
     * @param Customer $customer
     * @param Conversation $conversation
     * @param array $settings
     * @param bool $skipCache
     * @param string $boardType
     * @return array
     */
    public function getMondayItemsForCustomer(Customer $customer, Conversation $conversation, $settings, $skipCache = false, $boardType = 'primary'): array
    {
        $boardId = $settings['board_id'];
        
        // For dual-board architecture, use secondary board if requested
        if ($boardType === 'secondary' && !empty($settings['secondary_board_id'])) {
            $boardId = $settings['secondary_board_id'];
        }
        
        // Cache key for this customer's Monday items
        $cacheKey = 'monday_customer_items_' . md5($customer->id . '_' . $boardId . '_' . $boardType);
        
        // Check cache first if not skipping
        if (!$skipCache && Cache::has($cacheKey)) {
            $cachedItems = Cache::get($cacheKey);
            if ($cachedItems === null) {
                return [];
            }
            // Ensure we're returning an array
            return is_array($cachedItems) ? $cachedItems : [];
        }
        
        // Step 1: Try to find a match using the CallerIdService
        // This will check local mappings first using the cascading search strategy
        $result = $this->callerIdService->findMondayItemByConversation($conversation, $boardId);
        
        if (!$result) {
            // If no match found via conversation, try customer directly
            $result = $this->callerIdService->findMondayItemByCustomer($customer, $boardId);
        }
        
        $items = [];
        
        if ($result) {
            // We found a match in our local mappings or via the CallerIdService
            Logger::info('MondayIntegration: Found customer match using CallerIdService', [
                'customer_id' => $customer->id,
                'monday_item_id' => $result['monday_item_id'],
                'match_type' => $result['match_type'],
                'confidence_score' => $result['confidence_score']
            ]);
            
            // Get item details from Monday.com
            $items = $this->getItemDetailsFromMonday($result['monday_item_id'], $boardId);
        } else {
            // No match found in local mappings, use the PullboardService to search
            Logger::info('MondayIntegration: No match found in local mappings, using PullboardService', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'phone' => $customer->getPhoneNumber()
            ]);
            
            // Use PullboardService to find customer by any identifier
            $customerData = $this->pullboardService->findCustomerByAny([
                'email' => $customer->email,
                'phone' => $customer->getPhoneNumber(),
                'name' => $customer->getFullName()
            ]);
            
            if ($customerData) {
                // Format the response to match the expected structure
                $items = [
                    'data' => [
                        'items' => [$customerData]
                    ]
                ];
                
                // Create mappings for future use
                if ($customer->email) {
                    $this->callerIdService->createMapping(
                        $customer->email,
                        $customerData['id'],
                        'email',
                        0.9, // Higher confidence with optimized search
                        null, // No conversation object
                        $boardId
                    );
                }
                
                if ($customer->getPhoneNumber()) {
                    $this->callerIdService->createMapping(
                        $customer->getPhoneNumber(),
                        $customerData['id'],
                        'phone',
                        0.8, // Higher confidence with optimized search
                        null, // No conversation object
                        $boardId
                    );
                }
            }
        }
        
        // Cache the results for 5 minutes
        Cache::put($cacheKey, $items, now()->addMinutes(5));
        
        return $items;
    }

    /**
     * Get match information for the customer
     * 
     * @param Customer $customer
     * @param Conversation $conversation
     * @return array|null
     */
    protected function getMatchInfo(Customer $customer, Conversation $conversation): ?array
    {
        // Get settings for board IDs
        $settings = $this->getSettings();
        $boardId = $settings['board_id'] ?? '';
        
        // Try to find match information from the CallerIdService
        $result = $this->callerIdService->findMondayItemByConversation($conversation, $boardId);
        
        if (!$result) {
            $result = $this->callerIdService->findMondayItemByCustomer($customer, $boardId);
        }
        
        if ($result) {
            $matchInfo = [
                'monday_item_id' => $result['monday_item_id'],
                'match_type' => $result['match_type'],
                'confidence_score' => $result['confidence_score'],
            ];
            
            // Add alternate identifier if available
            if (isset($result['source_id']) && $result['source_id'] !== $customer->email && $result['source_id'] !== $customer->getPhoneNumber()) {
                $matchInfo['alternate_id'] = $result['source_id'];
            }
            
            return $matchInfo;
        }
        
        return null;
    }

    /**
     * Get item details from Monday.com by item ID
     * 
     * @param string $itemId
     * @param string $boardId
     * @return array
     */
    public function getItemDetailsFromMonday($itemId, $boardId): array
    {
        try {
            // First get the item using the ID
            $item = $this->mondayApi->getItem($itemId);
            
            // If board ID is provided, ensure it matches
            if (!empty($item) && !empty($boardId) && isset($item['board']['id']) && $item['board']['id'] !== $boardId) {
                Logger::warning('MondayIntegration: Item board ID mismatch', [
                    'item_id' => $itemId,
                    'expected_board_id' => $boardId,
                    'actual_board_id' => $item['board']['id']
                ]);
            }
            
            return $item;
        } catch (\Exception $e) {
            Logger::error('MondayIntegration: Error getting item details', [
                'item_id' => $itemId,
                'board_id' => $boardId,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Get configured quick actions from settings
     * 
     * @param array $settings
     * @return array
     */
    protected function getConfiguredQuickActions($settings): array
    {
        $quickActions = [];
        
        if (!empty($settings['quick_actions'])) {
            try {
                $quickActions = json_decode($settings['quick_actions'], true);
                
                if (!is_array($quickActions)) {
                    $quickActions = [];
                }
            } catch (\Exception $e) {
                Logger::error('MondayIntegration: Error parsing quick actions', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $quickActions;
    }

    /**
     * Process update text with variables
     * 
     * @param string $text
     * @param int $conversationId
     * @return string
     */
    protected function processUpdateText($text, $conversationId): string
    {
        // Replace variables in the text
        $conversation = Conversation::find($conversationId);
        
        if ($conversation) {
            $text = str_replace('{conversation_id}', $conversation->id, $text);
            $text = str_replace('{conversation_subject}', $conversation->subject, $text);
            
            if ($conversation->customer) {
                $text = str_replace('{customer_name}', $conversation->customer->getFullName(), $text);
                $text = str_replace('{customer_email}', $conversation->customer->email, $text);
            }
        }
        
        return $text;
    }

    /**
     * Render an empty widget when no data is available
     * 
     * @param Conversation $conversation
     * @param Customer|null $customer
     * @return \Illuminate\View\View
     */
    protected function renderEmptyWidget(Conversation $conversation, Customer $customer = null)
    {
        return view('mondayintegration::sidebar.caller_widget', [
            'mondayItems' => [],
            'quickActions' => [],
            'settings' => $this->getSettings(),
            'conversation' => $conversation,
            'customer' => $customer,
            'matchInfo' => null
        ]);
    }

    /**
     * Get module settings
     * 
     * @return array
     */
    protected function getSettings(): array
    {
        $settings = \Option::get('mondayintegration', []);
        return is_array($settings) ? $settings : [];
    }

    /**
     * Clear customer cache
     * 
     * @param int $customerId
     * @return void
     */
    protected function clearCustomerCache($customerId): void
    {
        $settings = $this->getSettings();
        $primaryBoardId = $settings['board_id'] ?? '';
        $secondaryBoardId = $settings['secondary_board_id'] ?? '';
        
        // Clear primary board cache
        Cache::forget('monday_customer_items_' . md5($customerId . '_' . $primaryBoardId . '_primary'));
        
        // Clear secondary board cache if exists
        if ($secondaryBoardId) {
            Cache::forget('monday_customer_items_' . md5($customerId . '_' . $secondaryBoardId . '_secondary'));
        }
    }

    /**
     * Search Monday.com items for manual linking
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchForLinking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
            'board_id' => 'required|string',
            'conversation_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid search parameters'),
                'errors' => $validator->errors()->all()
            ], 422);
        }

        $query = $request->input('query');
        $boardId = $request->input('board_id');
        $conversationId = $request->input('conversation_id');

        try {
            // Search for items in Monday.com
            $items = $this->mondayApi->searchItems($boardId, $query);
            
            if (empty($items)) {
                return response()->json([
                    'success' => true,
                    'items' => [],
                    'message' => __('No items found matching your search')
                ]);
            }
            
            return response()->json([
                'success' => true,
                'items' => $items,
                'message' => count($items) . ' ' . __('items found')
            ]);
            
        } catch (\Exception $e) {
            Logger::error('MondayIntegration: Error searching for items: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => __('Error searching Monday.com: ') . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a manual link between a conversation and a Monday.com item
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createManualLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|integer',
            'monday_item_id' => 'required|string',
            'monday_item_name' => 'required|string',
            'board_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $conversationId = $request->input('conversation_id');
        $mondayItemId = $request->input('monday_item_id');
        $mondayItemName = $request->input('monday_item_name');
        $boardId = $request->input('board_id');

        try {
            // Get the conversation
            $conversation = Conversation::find($conversationId);
            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => __('Conversation not found')
                ], 404);
            }

            // Map conversation source to channel
            $sourceVia = $conversation->source_via;
            $channel = 'email'; // Default channel
            
            // Simple mapping based on common source types
            if ($sourceVia === 'whatsapp') {
                $channel = 'whatsapp';
            } elseif ($sourceVia === 'phone') {
                $channel = 'phone';
            } elseif ($sourceVia === 'facebook') {
                $channel = 'facebook';
            }

            // Create the mapping using the public method
            $mapping = $this->callerIdService->createMapping(
                $conversation->customer_email ?: $conversation->customer->email,
                $mondayItemId,
                $channel,
                1.0, // Perfect confidence for manual linking
                $conversation,
                $boardId
            );

            if (!$mapping) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to create mapping')
                ], 500);
            }

            // Log the manual linking event
            Logger::info('MondayIntegration: Manual link created', [
                'conversation_id' => $conversationId,
                'monday_item_id' => $mondayItemId,
                'monday_item_name' => $mondayItemName,
                'board_id' => $boardId,
                'user_id' => auth()->user()->id
            ]);

            // Track the manual linking event
            Activity::create([
                'user_id' => auth()->user()->id,
                'conversation_id' => $conversationId,
                'type' => 'monday_manual_link',
                'action' => 'monday_manual_link',
                'source_type' => Conversation::class,
                'source_id' => $conversationId,
                'customer_id' => $conversation->customer_id,
                'data' => json_encode([
                    'monday_item_id' => $mondayItemId,
                    'monday_item_name' => $mondayItemName,
                    'board_id' => $boardId
                ])
            ]);

            // Clear any cached data for this customer
            $this->clearCustomerCache($conversation->customer_id);

            return response()->json([
                'success' => true,
                'message' => __('Successfully linked to Monday.com item'),
                'data' => [
                    'monday_item_id' => $mondayItemId,
                    'monday_item_name' => $mondayItemName,
                    'board_id' => $boardId
                ]
            ]);
        } catch (\Exception $e) {
            Logger::error('MondayIntegration: Error creating manual link', [
                'conversation_id' => $conversationId,
                'monday_item_id' => $mondayItemId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Error creating link: ') . $e->getMessage()
            ], 500);
        }
    }
}
