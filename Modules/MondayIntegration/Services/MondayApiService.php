<?php

namespace Modules\MondayIntegration\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use App\Option;
use Modules\MondayIntegration\Exceptions\MondayApiException;
use Modules\MondayIntegration\Models\MondaySettings;

class MondayApiService
{
    protected $client;
    protected $maxRetries = 3;
    protected $retryDelay = 5;
    public $apiToken = '';
    protected $cacheEnabled = true;
    protected $cacheTtl = 300; // 5 minutes cache by default
    protected $cacheService;

    public function __construct()
    {
        $this->initializeClient();
    }

    protected function initializeClient()
    {
        $this->apiToken = MondaySettings::where('key', 'api_token')->value('value') ?? '';

        $this->client = new Client([
            'base_uri' => config('monday-integration.api.base_url'),
            'timeout' => config('monday-integration.api.timeout'),
            'headers' => [
                'Authorization' => $this->apiToken,
                'Content-Type' => 'application/json',
                'API-Version' => config('monday.api.version'),
            ]
        ]);
    }

    public function getBoards($apiToken = "")
    {
        if($apiToken == "") {
            $apiToken = $this->apiToken;
        }
        $this->client = new Client([
            'base_uri' => config('monday-integration.api.base_url'),
            'timeout' => config('monday-integration.api.timeout'),
            'headers' => [
                'Authorization' => $apiToken,
                'Content-Type' => 'application/json',
                'API-Version' => config('monday-integration.api.version'),
            ]
        ]);

        $query = 'query { boards { id name columns { id title type } } }';
        return $this->executeQuery($query);
    }

    public function executeQuery($query, $variables = [])
    {
        try {
            $response = $this->client->post('', [
                'json' => [
                    'query' => $query,
                ]
            ]);
            $data = json_decode($response->getBody(), true);

            if (isset($data['errors'])) {
                return "error";
            }
            return $data['data'];

        } catch (RequestException $e) {
            return $e->getMessage();
        }
    }

    public function getItem($itemId)
    {
        $query = <<<GRAPHQL
        query {
            items(ids: [$itemId]) {
                id
                name
                column_values {
                    id
                    text
                }
                url
            }
        }
        GRAPHQL;

        $response = $this->executeQuery($query);
        if (isset($response['items'][0])) {
            return $response['items'][0];
        }
        
        return null;
    }

    public function createItem($boardId, $itemName, $columnValues = [])
    {
        $query = <<<GRAPHQL
            mutation {
                create_item(
                    board_id: $boardId,
                    item_name: "$itemName",
                    column_values: "{\"date\": \"2023-10-15\", \"location\": \"New York\", \"status\": \"Done\"}"
                ) {
                    id
                    name
                    created_at
                }
            }
        GRAPHQL;
        ;

        return $this->executeQuery($query, [
            'boardId' => (int)$boardId,
            'itemName' => $itemName,
            'columnValues' => json_encode($columnValues)
        ]);
    }

    public function createItemsWithAll($boardId)
    {
        $query = <<<GRAPHQL
            mutation {
                item1: create_item(board_id: $boardId, item_name: "User Data", column_values: "{\"user.id\":\"User ID\",\"user.first_name\":\"First Name\",\"user.last_name\":\"Last Name\",\"user.email\":\"Email Address\",\"user.phone\":\"Phone Number\",\"user.job_title\":\"Job Title\",\"user.timezone\":\"Timezone\",\"user.photo_url\":\"Profile Picture URL\",\"user.role\":\"User Role\",\"user.status\":\"User Status\",\"user.created_at\":\"Created Date\",\"user.updated_at\":\"Last Updated Date\"}") {
                    id
                    name
                }
                item2: create_item(board_id: $boardId, item_name: "Customer Data", column_values: "{\"customer.id\":\"Customer ID\",\"customer.first_name\":\"First Name\",\"customer.last_name\":\"Last Name\",\"customer.email\":\"Email Address\",\"customer.emails\":\"All Email Addresses\",\"customer.phone\":\"Phone Number\",\"customer.company\":\"Company\",\"customer.job_title\":\"Job Title\",\"customer.address\":\"Address\",\"customer.city\":\"City\",\"customer.state\":\"State\",\"customer.zip\":\"Zip/Postal Code\",\"customer.country\":\"Country\",\"customer.website\":\"Website\",\"customer.social_profiles\":\"Social Profiles\",\"customer.photo_url\":\"Profile Picture URL\",\"customer.created_at\":\"Created Date\",\"customer.updated_at\":\"Last Updated Date\"}") {
                    id
                    name
                }
                item3: create_item(board_id: $boardId, item_name: "Conversation Data", column_values: "{\"conversation.id\":\"Conversation ID\",\"conversation.number\":\"Conversation Number\",\"conversation.subject\":\"Subject\",\"conversation.customer_id\":\"Customer ID\",\"conversation.mailbox_id\":\"Mailbox ID\",\"conversation.folder_id\":\"Folder ID\",\"conversation.status\":\"Status\",\"conversation.state\":\"State\",\"conversation.preview\":\"Preview\",\"conversation.source_via\":\"Source Via\",\"conversation.source_type\":\"Source Type\",\"conversation.created_at\":\"Created Date\",\"conversation.updated_at\":\"Last Updated Date\",\"conversation.user_updated_at\":\"Last Updated by User Date\",\"conversation.closed_at\":\"Closed Date\",\"conversation.assigned_to\":\"Assigned User ID\",\"conversation.last_reply_at\":\"Last Reply Date\",\"conversation.last_reply_from\":\"Last Reply From\",\"conversation.has_attachments\":\"Has Attachments\"}") {
                    id
                    name
                }
                item4: create_item(board_id: $boardId, item_name: "Thread Data", column_values: "{\"thread.id\":\"Thread ID\",\"thread.conversation_id\":\"Conversation ID\",\"thread.user_id\":\"User ID\",\"thread.customer_id\":\"Customer ID\",\"thread.type\":\"Type\",\"thread.state\":\"State\",\"thread.status\":\"Status\",\"thread.body\":\"Body\",\"thread.headers\":\"Headers\",\"thread.has_attachments\":\"Has Attachments\",\"thread.created_at\":\"Created Date\",\"thread.updated_at\":\"Last Updated Date\",\"thread.source_via\":\"Source Via\",\"thread.source_type\":\"Source Type\"}") {
                    id
                    name
                }
                item5: create_item(board_id: $boardId, item_name: "Custom Fields", column_values: "{\"custom_fields.1\":\"Custom field1\",\"custom_fields.2\":\"Custom field2\",\"custom_fields.15\":\"custom field test\"}") {
                    id
                    name
                }
            }
        GRAPHQL;
        ;

        return $this->executeQuery($query);
    }


    public function testConnection($apiToken = "")
    {
        if($apiToken == "") {
            $apiToken = $this->apiToken;
        }

        $this->client = new Client([
            'base_uri' => config('monday-integration.api.base_url'),
            'timeout' => config('monday-integration.api.timeout'),
            'headers' => [
                'Authorization' => $apiToken,
                'Content-Type' => 'application/json',
                'API-Version' => config('monday-integration.api.version'),
            ]
        ]);

        return $this->getBoards($apiToken) !== null;
    }

    public function getBoardColumns($boardId = "")
    {
        if($boardId == "")
            $boardId = MondaySettings::where('key', 'board_id')->value('value') ?? '';

        $query = 'query { boards(ids: ' . $boardId . ') { columns { id title type settings_str } } }';
        $response = $this->executeQuery($query);
        $columns = $response['boards'][0]['columns'] ?? [];

        return $columns;
    }

    public function searchItems($boardId, $columnId, $searchValue)
    {
        $query = 'query { items_page_by_column_values(limit: 50, board_id: ' . $boardId . ', columns: [{column_id: "' . $columnId . '", column_values: ["' . $searchValue . '"]}]) { cursor items { id name url column_values { id text value type } } } }';

        $response = $this->client->post('', [
            'json' => [
                'query' => $query,
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['data']['items_page_by_column_values']['items'];
    }

    public function getApiVersion()
    {
        try {
            $query = 'query { version { kind value } }';
            return $this->executeQuery($query);
        } catch (\Exception $e) {
            \Log::error('Failed to get Monday.com API version: ' . $e->getMessage());
            return null;
        }
    }

    public function getBoard($boardId)
    {
        $query = <<<GRAPHQL
        query {
            boards(ids: {$boardId}) {
                id
                name
                description
                board_kind
                state
                board_folder_id
                columns {
                    id
                    title
                    type
                    settings_str
                }
            }
        }
        GRAPHQL;

        $response = $this->executeQuery($query);
        
        if (isset($response['boards'][0])) {
            return $response['boards'][0];
        }
        
        throw new \Exception('Board not found or access denied');
    }

    public function getBoardItems($boardId)
    {
        $query = <<<GRAPHQL
        {
            boards(ids: $boardId) {
                items_page(limit: 10) {
                    items {
                        id
                        name
                        url
                        column_values {
                            id
                            text
                            value
                            type
                        }
                    }
                }
            }
        }
        GRAPHQL;

        $response = $this->executeQuery($query);
        if (isset($response['boards'][0])) {
            return $response['boards'][0];
        }
        return [];
    }

    public function removeBoardItem($itemId)
    {
        $query = <<<GRAPHQL
        mutation {
            delete_item(item_id: $itemId) {
                id
            }
        }
        GRAPHQL;

        $response = $this->executeQuery($query);
        return $response;
    }

    public function getBoardTemplates()
    {
        $query = <<<GRAPHQL
        query {
          boards (state: templates) {
            id
            name
            description
            board_kind
            board_folder_id
          }
        }
        GRAPHQL;
        
        try {
            $response = $this->executeQuery($query);
            
            if (isset($response['data']['boards'])) {
                return $response['data']['boards'];
            }
            
            return [];
        } catch (\Exception $e) {
            Log::error('MondayIntegration: Error getting board templates: ' . $e->getMessage());
            return [];
        }
    }

    public function createBoard($board_name, $board_kind)
    {
        $query = <<<GRAPHQL
        mutation {
            create_board (board_name: "$board_name", board_kind: $board_kind) {
                id
            }
        }
        GRAPHQL;

        try {
            $response = $this->executeQuery($query);
            return $response;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
