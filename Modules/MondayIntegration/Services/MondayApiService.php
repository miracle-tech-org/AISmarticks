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
            return "error";
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

    public function createItem($boardId, $itemName, $columnValues)
    {
        $query = '
            mutation (
                $boardId: Int!
                $itemName: String!
                $columnValues: JSON!
            ) {
                create_item (
                    board_id: $boardId,
                    item_name: $itemName,
                    column_values: $columnValues
                ) {
                    id
                }
            }
        ';

        return $this->executeQuery($query, [
            'boardId' => (int)$boardId,
            'itemName' => $itemName,
            'columnValues' => json_encode($columnValues)
        ]);
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
}
