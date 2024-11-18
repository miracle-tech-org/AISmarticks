<?php
namespace Modules\AIAssistant\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Modules\AIAssistant\Events\OpenAIServiceUnavailable;
use Modules\AIAssistant\Events\OpenAIAuthenticationFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * OpenAI Service Integration
 *
 * @author [Your Name]
 */
class OpenAIService
{
    protected $client;
    protected $apiKey;
    protected $model;

    /**
     * Constructor
     *
     * @param string $apiKey OpenAI API Key
     * @param string $model  OpenAI Model
     */
    public function __construct($apiKey, $model)
    {
        $this->client = new Client(['base_uri' => 'https://api.openai.com/v1/']);
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    /**
     * Validate API Request Inputs
     *
     * @param array $inputs
     *
     * @return array
     */
    private function validateInputs(array $inputs)
    {
        $validator = Validator::make($inputs, [
           'messages' =>'required|array',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid input: '. $validator->getMessageBag()->first());
        }

        return $inputs;
    }

    /**
     * Generate AI Response
     *
     * @param array $messages Input Messages
     *
     * @return array Response Content and Tokens Used
     */
    public function generateResponse(array $messages)
    {
        $validatedInputs = $this->validateInputs(['messages' => $messages]);

        try {
            $response = $this->client->post('chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '. $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                   'model'    => $this->model,
                   'messages' => $validatedInputs['messages'],
                ],
                'timeout' => 15,
            ]);

            $body = json_decode($response->getBody(), true);
            $aiResponse = $body['choices'][0]['message']['content']?? '';
            $tokensUsed = $body['usage']['total_tokens']?? 0;

            return ['content' => $aiResponse, 'tokens_used' => $tokensUsed];
        } catch (RequestException $e) {
            $statusCode = $e->getResponse()? $e->getResponse()->getStatusCode() : null;

            if ($statusCode == 401) {
                // Authentication failure
                Log::error('OpenAI API Authentication Failed', [
                   'message'  => $e->getMessage(),
                    'code'     => $statusCode,
                ]);
                event(new OpenAIAuthenticationFailed($this->apiKey));
            } elseif ($statusCode == 429) {
                // Rate limit exceeded
                Log::warning('OpenAI API Rate Limit Exceeded', [
                   'message'  => $e->getMessage(),
                    'code'     => $statusCode,
                ]);
                // Implement backoff strategy or notify administrators
            } elseif ($statusCode >= 500 || $e instanceof ConnectException) {
                // Server error or connection issue
                Log::error('OpenAI Service Unavailable', [
                   'message'  => $e->getMessage(),
                    'code'     => $statusCode,
                ]);
                event(new OpenAIServiceUnavailable($e->getMessage()));
            } else {
                // Other client errors
                Log::error('OpenAI API RequestException', [
                   'message'  => $e->getMessage(),
                    'code'     => $statusCode,
                   'response' => $e->hasResponse()? $e->getResponse()->getBody()->getContents() : null,
                ]);
            }

            return ['content' => '', 'tokens_used' => 0];
        } catch (\Exception $e) {
            Log::critical('Unexpected Error in OpenAIService', [
               'message' => $e->getMessage(),
                'code'    => $e->getCode(),
            ]);

            return ['content' => '', 'tokens_used' => 0];
        }
    }
}