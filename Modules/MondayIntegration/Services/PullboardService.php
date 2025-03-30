<?php

namespace Modules\MondayIntegration\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\MondayIntegration\Models\MondayFieldMapping;
use Modules\MondayIntegration\Models\MondaySettings;

/**
 * Service for retrieving customer data from Monday.com pullboard
 * 
 * This service handles all operations related to retrieving and caching
 * customer data from the configured Monday.com pullboard.
 */
class PullboardService
{
    /**
     * @var MondayApiService
     */
    protected $apiService;

    /**
     * @var string|null
     */
    protected $pullboardId;

    /**
     * @var array
     */
    protected $fieldMappings;

    /**
     * @var int
     */
    protected $cacheTtl = 300; // 5 minutes by default

    /**
     * @var bool
     */
    protected $cacheEnabled = true;

    /**
     * Constructor
     * 
     * @param MondayApiService $apiService
     */
    public function __construct(MondayApiService $apiService)
    {
        $this->apiService = $apiService;
        $this->initialize();
    }

    /**
     * Initialize the service with settings and field mappings
     * 
     * @return void
     */
    protected function initialize()
    {
        // Get pullboard ID from settings
        $settings = MondaySettings::all()->pluck('value', 'key')->toArray();
        $this->pullboardId = $settings['pullboard_id'] ?? null;

        // Get field mappings for pullboard
        $this->fieldMappings = MondayFieldMapping::getFieldMappings('pull');

        // Set cache settings
        if (isset($settings['cache_enabled'])) {
            $this->cacheEnabled = (bool)$settings['cache_enabled'];
        }

        if (isset($settings['cache_ttl']) && is_numeric($settings['cache_ttl'])) {
            $this->cacheTtl = (int)$settings['cache_ttl'];
        }
    }

    /**
     * Find a customer by email address
     * 
     * @param string $email
     * @return array|null
     */
    public function findCustomerByEmail($email)
    {
        if (empty($email) || empty($this->pullboardId)) {
            return null;
        }

        // Check if we have an email column mapping
        $emailColumnId = $this->getColumnIdForField('customer.email');
        if (!$emailColumnId) {
            Log::warning('MondayIntegration: No email column mapping found for pullboard');
            return null;
        }

        // Generate cache key
        $cacheKey = 'monday_customer_email_' . md5($email);

        // Check cache first
        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return (array)Cache::get($cacheKey);
        }

        try {
            // Optimize query to only search for the specific email
            $query = $this->buildCustomerQuery([
                'column_id' => $emailColumnId,
                'column_value' => $email
            ]);

            $response = $this->apiService->executeQuery($query);
            $customer = $this->processCustomerResponse($response);

            // Cache the result
            if ($this->cacheEnabled && $customer) {
                Cache::put($cacheKey, $customer, $this->cacheTtl);
            }

            return $customer;
        } catch (\Exception $e) {
            Log::error('MondayIntegration: Error finding customer by email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Find a customer by phone number
     * 
     * @param string $phone
     * @return array|null
     */
    public function findCustomerByPhone($phone)
    {
        if (empty($phone) || empty($this->pullboardId)) {
            return null;
        }

        // Check if we have a phone column mapping
        $phoneColumnId = $this->getColumnIdForField('customer.phone');
        if (!$phoneColumnId) {
            Log::warning('MondayIntegration: No phone column mapping found for pullboard');
            return null;
        }

        // Normalize phone number for search
        $normalizer = new PhoneNormalizer();
        $normalizedPhone = $normalizer->normalize($phone);

        // Generate cache key
        $cacheKey = 'monday_customer_phone_' . md5($normalizedPhone);

        // Check cache first
        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return (array)Cache::get($cacheKey);
        }

        try {
            // Optimize query to only search for the specific phone
            $query = $this->buildCustomerQuery([
                'column_id' => $phoneColumnId,
                'column_value' => $normalizedPhone
            ]);

            $response = $this->apiService->executeQuery($query);
            $customer = $this->processCustomerResponse($response);

            // If exact match not found, try fuzzy matching
            if (!$customer) {
                $customer = $this->findCustomerByPhoneFuzzy($normalizedPhone);
            }

            // Cache the result
            if ($this->cacheEnabled && $customer) {
                Cache::put($cacheKey, $customer, $this->cacheTtl);
            }

            return $customer;
        } catch (\Exception $e) {
            Log::error('MondayIntegration: Error finding customer by phone', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Find a customer by name
     * 
     * @param string $name
     * @return array|null
     */
    public function findCustomerByName($name)
    {
        if (empty($name) || empty($this->pullboardId)) {
            return null;
        }

        // Generate cache key
        $cacheKey = 'monday_customer_name_' . md5($name);

        // Check cache first
        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return (array)Cache::get($cacheKey);
        }

        try {
            // Search by item name (which is usually the customer name in Monday.com)
            $query = $this->buildCustomerQuery([
                'item_name' => $name
            ]);

            $response = $this->apiService->executeQuery($query);
            $customer = $this->processCustomerResponse($response);

            // Cache the result
            if ($this->cacheEnabled && $customer) {
                Cache::put($cacheKey, $customer, $this->cacheTtl);
            }

            return $customer;
        } catch (\Exception $e) {
            Log::error('MondayIntegration: Error finding customer by name', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Find a customer by Monday.com item ID
     * 
     * @param string $itemId
     * @return array|null
     */
    public function findCustomerById($itemId)
    {
        if (empty($itemId) || empty($this->pullboardId)) {
            return null;
        }

        // Generate cache key
        $cacheKey = 'monday_customer_id_' . $itemId;

        // Check cache first
        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return (array)Cache::get($cacheKey);
        }

        try {
            // Query for a specific item by ID
            $query = <<<GRAPHQL
            query {
                items(ids: [{$itemId}]) {
                    id
                    name
                    board {
                        id
                    }
                    column_values {
                        id
                        title
                        text
                        value
                        type
                    }
                }
            }
            GRAPHQL;

            $response = $this->apiService->executeQuery($query);
            
            if (isset($response['data']['items'][0])) {
                $item = $response['data']['items'][0];
                
                // Verify this item belongs to the pullboard
                if ($item['board']['id'] == $this->pullboardId) {
                    $customer = $this->formatCustomerData($item);
                    
                    // Cache the result
                    if ($this->cacheEnabled) {
                        Cache::put($cacheKey, $customer, $this->cacheTtl);
                    }
                    
                    return $customer;
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('MondayIntegration: Error finding customer by ID', [
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Find a customer by any available identifier
     * 
     * This method tries to find a customer using multiple identifiers in order of reliability:
     * 1. Email (most reliable)
     * 2. Phone number
     * 3. Name (least reliable)
     * 
     * @param array $identifiers Array with keys 'email', 'phone', and/or 'name'
     * @return array|null Customer data or null if not found
     */
    public function findCustomerByAny(array $identifiers)
    {
        // Check for required configuration
        if (empty($this->pullboardId)) {
            Log::warning('MondayIntegration: Pullboard ID not configured');
            return null;
        }

        // Generate a unique cache key for this combination of identifiers
        $cacheKey = 'monday_customer_any_' . md5(json_encode($identifiers));

        // Check cache first
        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return (array)Cache::get($cacheKey);
        }

        $customer = null;

        // Try email first (most reliable)
        if (!empty($identifiers['email'])) {
            $customer = $this->findCustomerByEmail($identifiers['email']);
            if ($customer) {
                Log::info('MondayIntegration: Found customer by email', [
                    'email' => $identifiers['email'],
                    'item_id' => $customer['id']
                ]);
            }
        }

        // If not found by email, try phone
        if (!$customer && !empty($identifiers['phone'])) {
            $customer = $this->findCustomerByPhone($identifiers['phone']);
            if ($customer) {
                Log::info('MondayIntegration: Found customer by phone', [
                    'phone' => $identifiers['phone'],
                    'item_id' => $customer['id']
                ]);
            }
        }

        // If still not found, try name
        if (!$customer && !empty($identifiers['name'])) {
            $customer = $this->findCustomerByName($identifiers['name']);
            if ($customer) {
                Log::info('MondayIntegration: Found customer by name', [
                    'name' => $identifiers['name'],
                    'item_id' => $customer['id']
                ]);
            }
        }

        // Cache the result (even if null, to prevent repeated searches)
        if ($this->cacheEnabled) {
            Cache::put($cacheKey, $customer, $this->cacheTtl);
        }

        return $customer;
    }

    /**
     * Find multiple customers matching search criteria
     * 
     * @param array $searchParams
     * @param int $limit
     * @return array
     */
    public function searchCustomers(array $searchParams, $limit = 10)
    {
        if (empty($this->pullboardId)) {
            return [];
        }

        // Generate cache key based on search parameters
        $cacheKey = 'monday_customer_search_' . md5(json_encode($searchParams) . '_' . $limit);

        // Check cache first
        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return (array)Cache::get($cacheKey);
        }

        try {
            // Build a query based on search parameters
            $query = $this->buildCustomerQuery($searchParams, $limit);
            $response = $this->apiService->executeQuery($query);
            
            $customers = [];
            
            // Process items from the response
            if (isset($response['data']['items_by_column_values'])) {
                $items = $response['data']['items_by_column_values'];
                foreach ($items as $item) {
                    $customers[] = $this->formatCustomerData($item);
                }
            } elseif (isset($response['data']['items_page_by_column_values']['items'])) {
                $items = $response['data']['items_page_by_column_values']['items'];
                foreach ($items as $item) {
                    $customers[] = $this->formatCustomerData($item);
                }
            } elseif (isset($response['data']['boards'][0]['items'])) {
                $items = $response['data']['boards'][0]['items'];
                foreach ($items as $item) {
                    $customers[] = $this->formatCustomerData($item);
                }
            }
            
            // Cache the results
            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $customers, $this->cacheTtl);
            }
            
            return $customers;
        } catch (\Exception $e) {
            Log::error('MondayIntegration: Error searching customers', [
                'params' => $searchParams,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Attempt to find a customer by fuzzy phone number matching
     * 
     * @param string $phone
     * @return array|null
     */
    protected function findCustomerByPhoneFuzzy($phone)
    {
        if (empty($phone) || empty($this->pullboardId)) {
            return null;
        }

        $phoneColumnId = $this->getColumnIdForField('customer.phone');
        if (!$phoneColumnId) {
            return null;
        }

        // Try different phone number formats
        $phoneFormats = $this->generatePhoneFormats($phone);
        
        foreach ($phoneFormats as $format) {
            try {
                $query = $this->buildCustomerQuery([
                    'column_id' => $phoneColumnId,
                    'column_value' => $format
                ]);

                $response = $this->apiService->executeQuery($query);
                $customer = $this->processCustomerResponse($response);
                
                if ($customer) {
                    return $customer;
                }
            } catch (\Exception $e) {
                // Continue trying other formats
                continue;
            }
        }
        
        return null;
    }

    /**
     * Process the API response to extract customer data
     * 
     * @param array $response
     * @return array|null
     */
    protected function processCustomerResponse($response)
    {
        if (isset($response['data']['items_by_column_values'][0])) {
            return $this->formatCustomerData($response['data']['items_by_column_values'][0]);
        }
        
        if (isset($response['data']['items_page_by_column_values']['items'][0])) {
            return $this->formatCustomerData($response['data']['items_page_by_column_values']['items'][0]);
        }
        
        if (isset($response['data']['boards'][0]['items'][0])) {
            return $this->formatCustomerData($response['data']['boards'][0]['items'][0]);
        }
        
        return null;
    }

    /**
     * Format Monday.com item data into a standardized customer format
     * 
     * @param array $item
     * @return array
     */
    protected function formatCustomerData($item)
    {
        $customer = [
            'id' => $item['id'],
            'name' => $item['name'],
            'board_id' => $this->pullboardId,
            'url' => 'https://monday.com/boards/' . $this->pullboardId . '/pulses/' . $item['id'],
            'fields' => []
        ];
        
        // Process column values
        if (isset($item['column_values'])) {
            foreach ($item['column_values'] as $column) {
                $customer['fields'][$column['id']] = [
                    'title' => $column['title'],
                    'value' => $column['text'],
                    'raw_value' => $column['value'],
                    'type' => $column['type']
                ];
                
                // Map standard fields based on field mappings
                foreach ($this->fieldMappings as $fieldKey => $columnId) {
                    if ($column['id'] == $columnId) {
                        $parts = explode('.', $fieldKey);
                        if (count($parts) == 2) {
                            if (!isset($customer[$parts[0]])) {
                                $customer[$parts[0]] = [];
                            }
                            $customer[$parts[0]][$parts[1]] = $column['text'];
                        }
                    }
                }
            }
        }
        
        return $customer;
    }

    /**
     * Process a customer item from Monday.com API response
     * 
     * @param array $item The item data from Monday.com API
     * @return array|null Processed customer data or null if invalid
     */
    protected function processCustomerItem($item)
    {
        if (empty($item) || !isset($item['id']) || !isset($item['name'])) {
            return null;
        }

        // Initialize customer data with basic information
        $customer = [
            'id' => $item['id'],
            'name' => $item['name'],
            'board_id' => $this->pullboardId,
            'customer' => [],
            'column_values' => []
        ];

        // Process column values
        if (isset($item['column_values']) && is_array($item['column_values'])) {
            $customer['column_values'] = $item['column_values'];
            
            // Extract customer-specific data from column values
            foreach ($item['column_values'] as $column) {
                // Skip columns without an ID
                if (!isset($column['id'])) {
                    continue;
                }
                
                // Map column IDs to internal field names
                $fieldMappings = $this->getFieldMappings();
                foreach ($fieldMappings as $mapping) {
                    if (isset($mapping['monday_column_id']) && $mapping['monday_column_id'] === $column['id']) {
                        $internalField = $mapping['internal_field'] ?? '';
                        
                        // Extract the field name from the internal field (e.g., 'customer.email' -> 'email')
                        $parts = explode('.', $internalField);
                        if (count($parts) > 1) {
                            $fieldName = end($parts);
                            $customer['customer'][$fieldName] = $column['text'] ?? null;
                        }
                    }
                }
            }
        }

        return $customer;
    }

    /**
     * Build an optimized GraphQL query for customer retrieval
     * 
     * @param array $params
     * @param int $limit
     * @return string
     */
    protected function buildCustomerQuery(array $params, $limit = 1)
    {
        // Default query - search by column value
        if (isset($params['column_id']) && isset($params['column_value'])) {
            return <<<GRAPHQL
            query {
                items_by_column_values(board_id: {$this->pullboardId}, column_id: "{$params['column_id']}", column_value: "{$params['column_value']}", limit: {$limit}) {
                    id
                    name
                    column_values {
                        id
                        title
                        text
                        value
                        type
                    }
                }
            }
            GRAPHQL;
        }
        
        // Search by item name
        if (isset($params['item_name'])) {
            return <<<GRAPHQL
            query {
                boards(ids: {$this->pullboardId}) {
                    items(limit: {$limit}, page: 1, query: "{$params['item_name']}") {
                        id
                        name
                        column_values {
                            id
                            title
                            text
                            value
                            type
                        }
                    }
                }
            }
            GRAPHQL;
        }
        
        // Default query - get all items
        return <<<GRAPHQL
        query {
            boards(ids: {$this->pullboardId}) {
                items(limit: {$limit}, page: 1) {
                    id
                    name
                    column_values {
                        id
                        title
                        text
                        value
                        type
                    }
                }
            }
        }
        GRAPHQL;
    }

    /**
     * Get the Monday.com column ID for a specific FreeScout field
     * 
     * @param string $fieldKey
     * @return string|null
     */
    protected function getColumnIdForField($fieldKey)
    {
        // Get field mappings from settings
        $mappings = $this->getFieldMappings();
        
        if (empty($mappings) || !is_array($mappings)) {
            return null;
        }
        
        // Look for the field in mappings
        foreach ($mappings as $mapping) {
            if (!empty($mapping['field_name']) && $mapping['field_name'] === $fieldKey) {
                return $mapping['column_id'] ?? null;
            }
        }
        
        // If no direct mapping found, try some default mappings for common fields
        $defaultMappings = [
            'customer.email' => 'email',
            'customer.phone' => 'phone',
            'customer.name' => 'text',
            'customer.conversation_id' => 'text1'
        ];
        
        return $defaultMappings[$fieldKey] ?? null;
    }
    
    /**
     * Get field mappings from settings
     * 
     * @return array Field mappings
     */
    protected function getFieldMappings()
    {
        // Try to get mappings from cache
        $cacheKey = 'monday_field_mappings';
        
        if ($this->isCacheEnabled() && Cache::has($cacheKey)) {
            return (array) Cache::get($cacheKey);
        }
        
        // Get mappings from settings
        $settings = $this->getSettings();
        $mappings = $settings['field_mappings'] ?? [];
        
        // Cache the mappings
        if ($this->isCacheEnabled()) {
            Cache::put($cacheKey, $mappings, $this->getCacheTtl());
        }
        
        return $mappings;
    }

    /**
     * Get settings from cache or database
     * 
     * @return array Settings
     */
    protected function getSettings()
    {
        // Try to get settings from cache
        $cacheKey = 'monday_settings';
        
        if ($this->isCacheEnabled() && Cache::has($cacheKey)) {
            return (array) Cache::get($cacheKey);
        }
        
        // Get settings from database
        $settings = MondaySettings::getSettings();
        
        // Cache the settings
        if ($this->isCacheEnabled()) {
            Cache::put($cacheKey, $settings, $this->getCacheTtl());
        }
        
        return $settings;
    }
    
    /**
     * Clear all cache entries for this service
     *
     * @param string|null $identifier Optional specific identifier to clear (email, phone, etc.)
     * @param string|null $type Optional type of identifier (email, phone, name, id)
     * @return void
     */
    public function clearCache($identifier = null, $type = null)
    {
        if ($identifier && $type) {
            // Clear specific cache entry
            switch ($type) {
                case 'email':
                    Cache::forget('monday_customer_email_' . md5($identifier));
                    break;
                case 'phone':
                    $normalizer = new PhoneNormalizer();
                    $normalizedPhone = $normalizer->normalize($identifier);
                    Cache::forget('monday_customer_phone_' . md5($normalizedPhone));
                    break;
                case 'name':
                    Cache::forget('monday_customer_name_' . md5($identifier));
                    break;
                case 'id':
                    Cache::forget('monday_customer_id_' . $identifier);
                    break;
            }
        } else {
            // Clear all customer cache entries
            // Clear field mappings cache
            Cache::forget('monday_field_mappings');
            Cache::forget('monday_settings');
            
            // Clear customer caches - we'll use a prefix to identify them
            $keys = Cache::getStore()->keys('monday_customer_*');
            foreach ($keys as $key) {
                Cache::forget($key);
            }
            
            // Clear board cache
            Cache::forget('monday_pullboard_' . $this->pullboardId);
            
            // Clear API service cache
            $this->apiService->clearCache();
        }
    }

    /**
     * Check if caching is enabled
     * 
     * @return bool
     */
    protected function isCacheEnabled()
    {
        return $this->cacheEnabled;
    }
    
    /**
     * Get cache TTL
     * 
     * @return int Cache TTL in seconds
     */
    protected function getCacheTtl()
    {
        return $this->cacheTtl;
    }
    
    /**
     * Get the total count of customers in the pullboard
     * 
     * @return int
     */
    public function getCustomerCount()
    {
        $cacheKey = 'monday_customer_count';

        // Check cache first
        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return (int) Cache::get($cacheKey);
        }

        try {
            // Build the GraphQL query
            $query = 'query {
                boards(ids: "' . $this->pullboardId . '") {
                    items_count
                }
            }';

            // Execute the query
            $response = $this->apiService->executeQuery($query);

            // Extract the count from the response
            $count = $response['data']['boards'][0]['items_count'] ?? 0;

            // Cache the result
            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $count, $this->cacheTtl);
            }

            return (int) $count;
        } catch (\Exception $e) {
            Log::error('Error getting customer count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Create a new customer item in Monday.com
     * 
     * @param array $customerData Customer data with keys: name, email, phone, etc.
     * @param string|null $boardId Optional board ID to override the default pullboard
     * @return array|null Created customer data or null on failure
     */
    public function createCustomer(array $customerData, ?string $boardId = null)
    {
        // Use provided board ID or default pullboard ID
        $boardId = $boardId ?: $this->pullboardId;
        
        if (empty($boardId)) {
            Log::warning('MondayIntegration: Cannot create customer - no board ID provided');
            return null;
        }
        
        try {
            // Prepare item name and column values
            $itemName = $customerData['name'] ?? 'New Customer';
            $columnValues = [];
            
            // Map customer data to Monday.com columns
            if (!empty($customerData['email'])) {
                $emailColumnId = $this->getColumnIdForField('customer.email');
                if ($emailColumnId) {
                    $columnValues[$emailColumnId] = $customerData['email'];
                }
            }
            
            if (!empty($customerData['phone'])) {
                $phoneColumnId = $this->getColumnIdForField('customer.phone');
                if ($phoneColumnId) {
                    $columnValues[$phoneColumnId] = $customerData['phone'];
                }
            }
            
            // Add any additional fields from customerData that have mappings
            foreach ($customerData as $key => $value) {
                if ($key !== 'name' && $key !== 'email' && $key !== 'phone' && !empty($value)) {
                    $columnId = $this->getColumnIdForField('customer.' . $key);
                    if ($columnId) {
                        $columnValues[$columnId] = $value;
                    }
                }
            }
            
            // Build the mutation for creating an item
            $mutation = $this->buildCreateItemMutation($boardId, $itemName, $columnValues);
            
            // Execute the mutation
            $response = $this->apiService->executeMutation($mutation);
            
            // Process the response
            if (!empty($response['data']['create_item']['id'])) {
                // Get the created item's full data
                $itemId = $response['data']['create_item']['id'];
                
                // Clear any related caches
                if (!empty($customerData['email'])) {
                    $cacheKey = 'monday_customer_email_' . md5($customerData['email']);
                    Cache::forget($cacheKey);
                }
                
                if (!empty($customerData['phone'])) {
                    $cacheKey = 'monday_customer_phone_' . md5($customerData['phone']);
                    Cache::forget($cacheKey);
                }
                
                // Return the created item data in a standardized format
                return [
                    'id' => $itemId,
                    'name' => $itemName,
                    'board_id' => $boardId,
                    'column_values' => $this->formatColumnValues($columnValues)
                ];
            }
            
            Log::error('MondayIntegration: Failed to create customer item', [
                'response' => $response,
                'customer_data' => $customerData
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('MondayIntegration: Error creating customer item', [
                'error' => $e->getMessage(),
                'customer_data' => $customerData
            ]);
            
            return null;
        }
    }
    
    /**
     * Build a GraphQL mutation for creating an item
     * 
     * @param string $boardId Board ID
     * @param string $itemName Item name
     * @param array $columnValues Column values as key-value pairs
     * @return string GraphQL mutation
     */
    protected function buildCreateItemMutation($boardId, $itemName, array $columnValues)
    {
        // Format column values for the mutation
        $formattedValues = json_encode($columnValues);
        
        return <<<GRAPHQL
        mutation {
            create_item (board_id: $boardId, item_name: "$itemName", column_values: $formattedValues) {
                id
                name
                board {
                    id
                }
                column_values {
                    id
                    text
                    value
                }
            }
        }
        GRAPHQL;
    }
    
    /**
     * Format column values for response
     * 
     * @param array $columnValues Column values as key-value pairs
     * @return array Formatted column values
     */
    protected function formatColumnValues(array $columnValues)
    {
        $formatted = [];
        
        foreach ($columnValues as $columnId => $value) {
            $formatted[] = [
                'id' => $columnId,
                'text' => $value,
                'value' => json_encode(['text' => $value])
            ];
        }
        
        return $formatted;
    }

    /**
     * Get all customers from the pullboard
     * 
     * @param int $limit Maximum number of customers to return
     * @param int $page Page number for pagination
     * @return array Array of customer items
     */
    public function getAllCustomers($limit = 100, $page = 1)
    {
        // Generate cache key
        $cacheKey = "monday_all_customers_{$this->pullboardId}_{$limit}_{$page}";

        // Check cache first
        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return (array) Cache::get($cacheKey);
        }

        try {
            // Calculate pagination
            $skip = ($page - 1) * $limit;
            
            // Build the GraphQL query
            $query = 'query {
                boards(ids: "' . $this->pullboardId . '") {
                    items(limit: ' . $limit . ', page: ' . $page . ') {
                        id
                        name
                        column_values {
                            id
                            title
                            text
                            value
                            type
                        }
                    }
                }
            }';

            // Execute the query
            $response = $this->apiService->executeQuery($query);

            // Extract the items from the response
            $items = $response['data']['boards'][0]['items'] ?? [];

            // Process the items to extract customer data
            $customers = [];
            foreach ($items as $item) {
                $customer = $this->processCustomerItem($item);
                if ($customer) {
                    $customers[] = $customer;
                }
            }

            // Cache the result
            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $customers, $this->cacheTtl);
            }

            return $customers;
        } catch (\Exception $e) {
            Log::error('Error getting all customers: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate different phone number formats for fuzzy matching
     * 
     * @param string $phone
     * @return array
     */
    protected function generatePhoneFormats($phone)
    {
        $formats = [$phone];
        
        // Remove all non-numeric characters
        $digitsOnly = preg_replace('/[^0-9]/', '', $phone);
        $formats[] = $digitsOnly;
        
        // Last 9 digits
        if (strlen($digitsOnly) > 9) {
            $formats[] = substr($digitsOnly, -9);
        }
        
        // Last 10 digits
        if (strlen($digitsOnly) > 10) {
            $formats[] = substr($digitsOnly, -10);
        }
        
        // With different prefixes
        if (strlen($digitsOnly) >= 9) {
            $lastNine = substr($digitsOnly, -9);
            $formats[] = '+' . $lastNine;
            $formats[] = '0' . $lastNine;
        }
        
        // Common formatting patterns
        if (strlen($digitsOnly) == 10) {
            $formats[] = substr($digitsOnly, 0, 3) . '-' . substr($digitsOnly, 3, 3) . '-' . substr($digitsOnly, 6);
            $formats[] = '(' . substr($digitsOnly, 0, 3) . ') ' . substr($digitsOnly, 3, 3) . '-' . substr($digitsOnly, 6);
        }
        
        return array_unique($formats);
    }
}
