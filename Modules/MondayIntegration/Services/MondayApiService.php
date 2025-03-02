<?php

namespace Modules\MondayIntegration\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use App\Option;
use Modules\MondayIntegration\Exceptions\MondayApiException;

class MondayApiService
{
    protected $client;
    protected $maxRetries = 3;
    protected $retryDelay = 5;

    public function __construct()
    {
        $this->initializeClient();
    }

    protected function initializeClient()
    {
        $apiToken = Option::get('mondayintegration.api_token');

        $this->client = new Client([
            'base_uri' => config('monday.api.base_url'),
            'timeout' => config('monday.api.timeout'),
            'headers' => [
                'Authorization' => $apiToken,
                'Content-Type' => 'application/json',
                'API-Version' => config('monday.api.version'),
            ]
        ]);
    }

    public function getBoards($apiToken)
    {
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

    protected function executeQuery($query, $variables = [])
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
                // throw new MondayApiException(
                //     'Monday.com API Error: ' . $data['errors'][0]['message']
                // );
            }

            return $data['data'];

        } catch (RequestException $e) {
            return "error";
            // throw new MondayApiException(
            //     'Failed to communicate with Monday.com: ' . $e->getMessage()
            // );
        }
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

    public function testConnection($apiToken)
    {
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
}
