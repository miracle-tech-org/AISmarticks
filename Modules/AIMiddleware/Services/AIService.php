<?php
namespace App\Modules\MyImprovements\Services;

use App\Option;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * Core AI Service for handling OpenAI API interactions
 */
class AIService
{
    // Constants for retry mechanism
    private const MAX_RETRIES = 3;
    private const BACKOFF_MS = 1000;

    protected Client $client;
    protected string $model;

    /**
     * Initialize the AI service with configuration
     */
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => Option::get('ai_middleware.timeout', 30.0),
        ]);
        $this->model = Option::get('ai_middleware.openai_model', 'gpt-4');
    }

    /**
     * Generate AI response with retry mechanism
     *
     * @param array $messages Array of message objects
     * @return array Response containing content and metadata
     * @throws AIServiceException
     */
    public function generateResponse(array $messages): array
    {
        $attempt = 0;

        do {
            try {
                $startTime = microtime(true);
                $response = $this->client->post('chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . Option::get('ai_middleware.openai_api_key'),
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => $this->model,
                        'messages' => array_merge([
                            [
                                'role' => 'system',
                                'content' => $this->buildSystemPrompt(),
                            ],
                        ], $messages),
                        'temperature' => 0.7,
                        'max_tokens' => Option::get('ai_middleware.max_tokens', 2000),
                    ],
                ]);

                $duration = (microtime(true) - $startTime) * 1000;

                $this->logInfo('AI response generated', [
                    'model' => $this->model,
                    'duration_ms' => $duration,
                ]);

                $result = $this->processResponse($response);
                $result['latency'] = $duration;

                return $result;

            } catch (RequestException $e) {
                $this->logError('API request failed', [
                    'attempt' => ++$attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt >= self::MAX_RETRIES) {
                    throw new AIServiceException("Max retries exceeded", 0, $e);
                }

                usleep(self::BACKOFF_MS * (2 ** ($attempt - 1)));
            }
        } while ($attempt < self::MAX_RETRIES);
    }

    /**
     * Build the system prompt for AI
     */
    private function buildSystemPrompt(): string
    {
        return Option::get('ai_middleware.system_prompt',
            'You are a helpful customer service AI assistant. ' .
            'Output strictly in JSON format with the following structure:
            {
                "response": "<AI reply>",
                "action": "<addTag|notifyTeam|none>",
                "tags": ["<tag1>", "<tag2>"],
                "ticketId": "<ticket_id>",
                "sentiment": "<positive|negative|neutral>",
                "confidence": <0.0-1.0>,
                "priority": "<low|medium|high>"
            }'
        );
    }

    /**
     * Process and validate API response
     */
    private function processResponse($response): array
    {
        $body = json_decode($response->getBody(), true);

        if (!isset($body['choices'][0]['message']['content'])) {
            $this->logError('Invalid API response structure');
            throw new InvalidResponseException("Invalid response structure");
        }

        return [
            'content' => $body['choices'][0]['message']['content'],
            'tokens_used' => $body['usage']['total_tokens'] ?? 0,
            'model' => $body['model'] ?? $this->model,
        ];
    }

    /**
     * Log error with context
     */
    private function logError(string $message, array $context = []): void
    {
        Log::error($message, [
            'source' => 'MyImprovements',
            'user_id' => auth()->id(),
            ...$context,
        ]);
    }

    /**
     * Log info with context
     */
    private function logInfo(string $message, array $context = []): void
    {
        Log::info($message, [
            'source' => 'MyImprovements',
            'user_id' => auth()->id(),
            ...$context,
        ]);
    }
}
