<?php

namespace Modules\MondayIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Conversation;
use App\Customer;
use App\Misc\Helper;
use App\Mailbox;
use Modules\MondayIntegration\Services\MondayApiService;
use Modules\MondayIntegration\Models\MondaySettings;


class SidebarController extends Controller
{
    protected $mondayApi;

    public function __construct(MondayApiService $mondayApi)
    {
        // $this->middleware('auth');
        $this->mondayApi = $mondayApi;
    }

    /**
     * Display the sidebar widget with Monday.com data
     *
     * @param int $conversationId
     * @return \Illuminate\Http\Response
     */
    public function sidebarWidget($mailbox_id, $conversationId)
    {
        try {
            $mailbox = Mailbox::findOrFail($mailbox_id);
            $conversation = Conversation::findOrFail($conversationId);
            $customer = $conversation->customer;
            if (!$customer) {
                return $this->renderEmptyWidget($conversation);
            }
            // Get settings
            $settings = MondaySettings::all()->pluck('value', 'key')->toArray();
            
            // Check if the widget is enabled
            if (empty($settings['enable_sidebar_widget']) || $settings['enable_sidebar_widget'] != '1') {
                return '';
            }

            // Get Monday.com items related to this customer
            $mondayItems = $this->getMondayItemsForCustomer($customer, $settings);
            var_dump($mondayItems);
            exit();
            // Get configured quick actions
            $quickActions = $this->getConfiguredQuickActions($settings);
            
            return view('mondayintegration::sidebar.caller_widget', [
                'conversation' => $conversation,
                'customer' => $customer,
                'mondayItems' => $mondayItems,
                'quickActions' => $quickActions,
                'settings' => $settings
            ]);
        } catch (\Exception $e) {
            Logger::error('MondayIntegration: Error rendering sidebar widget', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversationId
            ]);
            
            return '';
        }
    }
    
    /**
     * Execute a quick action on a Monday.com item
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeQuickAction(Request $request)
    {
        try {
            $actionId = $request->input('action_id');
            $itemId = $request->input('item_id');
            $conversationId = $request->input('conversation_id');
            
            if (!$actionId || !$itemId) {
                throw new \Exception(__('Missing required parameters'));
            }
            
            // Get settings to find the action configuration
            $settings = MondaySettings::all()->pluck('value', 'key')->toArray();
            $quickActions = json_decode($settings['quick_actions'] ?? '[]', true);
            
            // Find the requested action
            $action = null;
            foreach ($quickActions as $configuredAction) {
                if ($configuredAction['id'] == $actionId) {
                    $action = $configuredAction;
                    break;
                }
            }
            
            if (!$action) {
                throw new \Exception(__('Action not found'));
            }
            
            // Execute the action based on its type
            switch ($action['type']) {
                case 'status_change':
                    $result = $this->mondayApi->changeItemStatus($itemId, $action['value']);
                    break;
                    
                case 'add_update':
                    $conversation = Conversation::findOrFail($conversationId);
                    $message = str_replace(
                        ['{conversation_id}', '{customer_name}', '{customer_email}'],
                        [$conversation->id, $conversation->customer->getFullName(), $conversation->customer->email],
                        $action['value']
                    );
                    $result = $this->mondayApi->addUpdate($itemId, $message);
                    break;
                    
                case 'custom_action':
                    // Execute custom GraphQL mutation
                    $result = $this->mondayApi->executeCustomMutation($action['value'], [
                        'item_id' => $itemId,
                        'conversation_id' => $conversationId
                    ]);
                    break;
                    
                default:
                    throw new \Exception(__('Unsupported action type'));
            }
            
            Logger::info('MondayIntegration: Quick action executed', [
                'action' => $actionId,
                'item_id' => $itemId,
                'conversation_id' => $conversationId,
                'result' => $result
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => __('Action executed successfully')
            ]);
        } catch (\Exception $e) {
            Logger::error('MondayIntegration: Error executing quick action', [
                'error' => $e->getMessage(),
                'action_id' => $request->input('action_id'),
                'item_id' => $request->input('item_id')
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Get Monday.com items related to a customer
     *
     * @param Customer $customer
     * @param array $settings
     * @return array
     */
    protected function getMondayItemsForCustomer(Customer $customer, $settings)
    {
        // Determine search field (email, phone, etc.)
        $searchField = $settings['customer_search_field'] ?? 'email';
        $searchValue = '';
        
        switch ($searchField) {
            case 'email':
                $searchValue = $customer->email;
                break;
                
            case 'phone':
                $searchValue = $customer->getPhoneNumber();
                break;
                
            case 'name':
                $searchValue = $customer->getFullName();
                break;
                
            default:
                $searchValue = $customer->email;
        }
        
        // If no search value, return empty
        if (empty($searchValue)) {
            return [];
        }
        
        // Get board ID from settings
        $boardId = $settings['board_id'] ?? null;
        if (!$boardId) {
            return [];
        }
        
        // // Cache key for this customer's Monday items
        // $cacheKey = 'monday_customer_items_' . md5($searchField . '_' . $searchValue . '_' . $boardId);
        
        // // Check cache first
        // if (cache()->has($cacheKey)) {
        //     return cache()->get($cacheKey);
        // }
        
        // Search for items in Monday.com
        $items = $this->mondayApi->searchItems($boardId, $searchField, $searchValue);
        // Cache the results for 5 minutes
        // cache()->put($cacheKey, $items, 300);
        
        return $items;
    }
    
    /**
     * Get configured quick actions from settings
     *
     * @param array $settings
     * @return array
     */
    protected function getConfiguredQuickActions($settings)
    {
        if (empty($settings['quick_actions'])) {
            return [];
        }
        
        return json_decode($settings['quick_actions'], true);
    }
    
    /**
     * Render an empty widget when no data is available
     *
     * @param Conversation $conversation
     * @return \Illuminate\View\View
     */
    protected function renderEmptyWidget(Conversation $conversation)
    {
        $settings = MondaySettings::all()->pluck('value', 'key')->toArray();
        
        return view('mondayintegration::sidebar.caller_widget', [
            'conversation' => $conversation,
            'customer' => null,
            'mondayItems' => [],
            'quickActions' => [],
            'settings' => $settings
        ]);
    }
}
