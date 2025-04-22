<?php

namespace Modules\MondayIntegration\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use App\Option;
use Modules\MondayIntegration\Exceptions\MondayApiException;
use Modules\MondayIntegration\Models\MondaySettings;
use App\ActivityLog;

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
            ActivityLog::create([
                'properties' => '[]',
                'log_name' => 'mondayintegration_errors',
                'description' => $e->getMessage(),
            ]);
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

    public function createColumn($boardId)
    {
        $query = <<<GRAPHQL
            mutation {
                user_id: create_column(board_id: $boardId, title: "User ID", column_type: text) {
                    id
                    title
                    type
                }
            }
        GRAPHQL;
    }

    public function getBoardById($boardId)
    {
        $query = <<<GRAPHQL
        {
              boards(ids: [$boardId]) {
                id
                name
                columns {
                    id
                    title
                    type
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

    public function sendPushboardItem($query)
    {
        try {
            $response = $this->client->post('', [
                'json' => [
                    'query' => $query,
                ]
            ]);
            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (\Exception $e) {
            ActivityLog::create([
                'properties' => '[]',
                'log_name' => 'mondayintegration_errors',
                'description' => $e->getMessage(),
            ]);

            return $e->getMessage();
        }
    }

    public function createColumnsWithAllFields($boardId)
    {
        $query = <<<GRAPHQL
            mutation {
                user_id: create_column(board_id: $boardId, title: "User ID", column_type: text) {
                    id
                    title
                    type
                }
                user_first_name: create_column(board_id: $boardId, title: "First Name", column_type: text) {
                    id
                    title
                    type
                }
                user_last_name: create_column(board_id: $boardId, title: "Last Name", column_type: text) {
                    id
                    title
                    type
                }
                user_email: create_column(board_id: $boardId, title: "Email Address", column_type: text) {
                    id
                    title
                    type
                }
                user_phone: create_column(board_id: $boardId, title: "Phone Number", column_type: text) {
                    id
                    title
                    type
                }
                user_job_title: create_column(board_id: $boardId, title: "Job Title", column_type: text) {
                    id
                    title
                    type
                }
                user_timezone: create_column(board_id: $boardId, title: "Timezone", column_type: text) {
                    id
                    title
                    type
                }
                user_photo_url: create_column(board_id: $boardId, title: "Profile Picture URL", column_type: text) {
                    id
                    title
                    type
                }
                user_role: create_column(board_id: $boardId, title: "User Role", column_type: text) {
                    id
                    title
                    type
                }
                user_status: create_column(board_id: $boardId, title: "User Status", column_type: text) {
                    id
                    title
                    type
                }
                user_created_at: create_column(board_id: $boardId, title: "Created Date", column_type: text) {
                    id
                    title
                    type
                }
                user_updated_at: create_column(board_id: $boardId, title: "Last Updated Date", column_type: text) {
                    id
                    title
                    type
                }
                customer_id: create_column(board_id: $boardId, title: "Customer ID", column_type: text) {
                    id
                    title
                    type
                }
                customer_first_name: create_column(board_id: $boardId, title: "First Name", column_type: text) {
                    id
                    title
                    type
                }
                customer_last_name: create_column(board_id: $boardId, title: "Last Name", column_type: text) {
                    id
                    title
                    type
                }
                customer_email: create_column(board_id: $boardId, title: "Email Address", column_type: text) {
                    id
                    title
                    type
                }
                customer_emails: create_column(board_id: $boardId, title: "All Email Addresses", column_type: text) {
                    id
                    title
                    type
                }
                customer_phone: create_column(board_id: $boardId, title: "Phone Number", column_type: text) {
                    id
                    title
                    type
                }
                customer_company: create_column(board_id: $boardId, title: "Company", column_type: text) {
                    id
                    title
                    type
                }
                customer_job_title: create_column(board_id: $boardId, title: "Job Title", column_type: text) {
                    id
                    title
                    type
                }
                customer_address: create_column(board_id: $boardId, title: "Address", column_type: text) {
                    id
                    title
                    type
                }
                customer_city: create_column(board_id: $boardId, title: "City", column_type: text) {
                    id
                    title
                    type
                }
                customer_state: create_column(board_id: $boardId, title: "State", column_type: text) {
                    id
                    title
                    type
                }
                customer_zip: create_column(board_id: $boardId, title: "Zip/Postal Code", column_type: text) {
                    id
                    title
                    type
                }
                customer_country: create_column(board_id: $boardId, title: "Country", column_type: text) {
                    id
                    title
                    type
                }
                customer_website: create_column(board_id: $boardId, title: "Website", column_type: text) {
                    id
                    title
                    type
                }
                customer_social_profiles: create_column(board_id: $boardId, title: "Social Profiles", column_type: text) {
                    id
                    title
                    type
                }
                customer_photo_url: create_column(board_id: $boardId, title: "Profile Picture URL", column_type: text) {
                    id
                    title
                    type
                }
                customer_created_at: create_column(board_id: $boardId, title: "Created Date", column_type: text) {
                    id
                    title
                    type
                }
                customer_updated_at: create_column(board_id: $boardId, title: "Last Updated Date", column_type: text) {
                    id
                    title
                    type
                }
                conversation_id: create_column(board_id: $boardId, title: "Conversation ID", column_type: text) {
                    id
                    title
                    type
                }
                conversation_number: create_column(board_id: $boardId, title: "Conversation Number", column_type: text) {
                    id
                    title
                    type
                }
                conversation_subject: create_column(board_id: $boardId, title: "Subject", column_type: text) {
                    id
                    title
                    type
                }
                conversation_customer_id: create_column(board_id: $boardId, title: "Customer ID", column_type: text) {
                    id
                    title
                    type
                }
                conversation_mailbox_id: create_column(board_id: $boardId, title: "Mailbox ID", column_type: text) {
                    id
                    title
                    type
                }
                conversation_folder_id: create_column(board_id: $boardId, title: "Folder ID", column_type: text) {
                    id
                    title
                    type
                }
                conversation_status: create_column(board_id: $boardId, title: "Status", column_type: text) {
                    id
                    title
                    type
                }
                conversation_state: create_column(board_id: $boardId, title: "State", column_type: text) {
                    id
                    title
                    type
                }
                conversation_preview: create_column(board_id: $boardId, title: "Preview", column_type: text) {
                    id
                    title
                    type
                }
                conversation_source_via: create_column(board_id: $boardId, title: "Source Via", column_type: text) {
                    id
                    title
                    type
                }
                conversation_source_type: create_column(board_id: $boardId, title: "Source Type", column_type: text) {
                    id
                    title
                    type
                }
                conversation_created_at: create_column(board_id: $boardId, title: "Created Date", column_type: text) {
                    id
                    title
                    type
                }
                conversation_updated_at: create_column(board_id: $boardId, title: "Last Updated Date", column_type: text) {
                    id
                    title
                    type
                }
                conversation_user_updated_at: create_column(board_id: $boardId, title: "Last Updated by User Date", column_type: text) {
                    id
                    title
                    type
                }
                conversation_closed_at: create_column(board_id: $boardId, title: "Closed Date", column_type: text) {
                    id
                    title
                    type
                }
                conversation_assigned_to: create_column(board_id: $boardId, title: "Assigned User ID", column_type: text) {
                    id
                    title
                    type
                }
                conversation_last_reply_at: create_column(board_id: $boardId, title: "Last Reply Date", column_type: text) {
                    id
                    title
                    type
                }
                conversation_last_reply_from: create_column(board_id: $boardId, title: "Last Reply From", column_type: text) {
                    id
                    title
                    type
                }
                conversation_has_attachments: create_column(board_id: $boardId, title: "Has Attachments", column_type: text) {
                    id
                    title
                    type
                }
                thread_id: create_column(board_id: $boardId, title: "Thread ID", column_type: text) {
                    id
                    title
                    type
                }
                thread_conversation_id: create_column(board_id: $boardId, title: "Conversation ID", column_type: text) {
                    id
                    title
                    type
                }
                thread_user_id: create_column(board_id: $boardId, title: "User ID", column_type: text) {
                    id
                    title
                    type
                }
                thread_customer_id: create_column(board_id: $boardId, title: "Customer ID", column_type: text) {
                    id
                    title
                    type
                }
                thread_type: create_column(board_id: $boardId, title: "Type", column_type: text) {
                    id
                    title
                    type
                }
                thread_state: create_column(board_id: $boardId, title: "State", column_type: text) {
                    id
                    title
                    type
                }
                thread_status: create_column(board_id: $boardId, title: "Status", column_type: text) {
                    id
                    title
                    type
                }
                thread_body: create_column(board_id: $boardId, title: "Body", column_type: text) {
                    id
                    title
                    type
                }
                thread_headers: create_column(board_id: $boardId, title: "Headers", column_type: text) {
                    id
                    title
                    type
                }
                thread_has_attachments: create_column(board_id: $boardId, title: "Has Attachments", column_type: text) {
                    id
                    title
                    type
                }
                thread_created_at: create_column(board_id: $boardId, title: "Created Date", column_type: text) {
                    id
                    title
                    type
                }
                thread_updated_at: create_column(board_id: $boardId, title: "Last Updated Date", column_type: text) {
                    id
                    title
                    type
                }
                thread_source_via: create_column(board_id: $boardId, title: "Source Via", column_type: text) {
                    id
                    title
                    type
                }
                thread_source_type: create_column(board_id: $boardId, title: "Source Type", column_type: text) {
                    id
                    title
                    type
                }
                thread_meta: create_column(board_id: $boardId, title: "Meta", column_type: text) {
                    id
                    title
                    type
                }
                custom_fields_1: create_column(board_id: $boardId, title: "Custom field1", column_type: text) {
                    id
                    title
                    type
                }
                custom_fields_2: create_column(board_id: $boardId, title: "Custom field2", column_type: text) {
                    id
                    title
                    type
                }
                custom_fields_15: create_column(board_id: $boardId, title: "custom field test", column_type: text) {
                    id
                    title
                    type
                }
                html_file: create_column(board_id: $boardId, title: "HTML File", column_type: file) {
                    id
                    title
                    type
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
            ActivityLog::create([
                'properties' => '[]',
                'log_name' => 'mondayintegration_errors',
                'description' => 'Failed to get Monday.com API version: ' . $e->getMessage(),
            ]);

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
            ActivityLog::create([
                'properties' => '[]',
                'log_name' => 'mondayintegration_errors',
                'description' => 'MondayIntegration: Error getting board templates: ' . $e->getMessage(),
            ]);
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
            ActivityLog::create([
                'properties' => '[]',
                'log_name' => 'mondayintegration_errors',
                'description' => $e->getMessage(),
            ]);
            return $e->getMessage();
        }
    }

    public function updateItem($item_id, $column_id)
    {
        try {
            $filePath = storage_path('logs/chat.html');

            $client = new Client();

            $response = $client->request('POST', 'https://api.monday.com/v2/file', [
                'headers' => [
                    'API-Version'   => '2024-01',
                    'Authorization' => 'eyJhbGciOiJIUzI1NiJ9.eyJ0aWQiOjQ5NjkxNjYzMywiYWFpIjoxMSwidWlkIjo3NDMwODQzMCwiaWFkIjoiMjAyNS0wNC0wOFQwNzo0NzozNC4wMDBaIiwicGVyIjoibWU6d3JpdGUiLCJhY3RpZCI6Mjg4NjIxMjEsInJnbiI6ImV1YzEifQ.ryQ28s3ptvuLIGYqaApYP87_vdpaJ2a-0mb2GTFnYFg',
                    'Cookie'        => '__cf_bm=9XdwKS5o47o6JhGpTT7O0T9E.Pbe.QybruhQtelxu20-1745227740-1.0.1.1-gcfM5m7VN7AXGMdrEaenkB8vXvGCQ6icVPzvCxlzfC7UZYwTUQAmLdxJV7GDswxbelkzys95Zx2d0a6nQI10Mj00fEJFYCuYggywphg4VZGHwP6pOYayeY9V5nQwi6ov; _cfuvid=A_iMDmVxs4slzA.VZtlOgn7UJaDFnwUvTh97.lV712k-1745104651000-0.0.1.1-604800000',
                ],
                'multipart' => [
                    [
                        'name'     => 'query',
                        'contents' => 'mutation($item_id: ID!, $column_id: String!, $file: File!) { add_file_to_column(item_id: $item_id, column_id: $column_id, file: $file) { id } }'
                    ],
                    [
                        'name'     => 'variables',
                        'contents' => json_encode([
                            'item_id'   => $item_id,
                            'column_id' => $column_id
                        ])
                    ],
                    [
                        'name'     => 'map',
                        'contents' => json_encode([
                            'file' => 'variables.file'
                        ])
                    ],
                    [
                        'name'     => 'file',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath)
                    ],
                ],
            ]);
            
            return json_encode($response->getBody(), true);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
