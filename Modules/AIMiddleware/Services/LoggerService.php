<?php

namespace Modules\AIMiddleware\Services;

use App\Logger\Logger;
use App\Misc\Helper;
/**
 * Logger Service that integrates with FreeScout's native logging system
 */
class LoggerService
{
    /**
     * Log info level message
     * 
     * @param string $message
     * @param array $context Additional context information
     */
    public function info(string $message, array $context = []): void
    {
        Logger::info($message, $this->enrichContext($context));
    }

    /**
     * Log error level message
     * 
     * @param string $message
     * @param array $context Additional context information
     */
    public function error(string $message, array $context = []): void
    {
        Logger::error($message, $this->enrichContext($context));
    }

    /**
     * Log warning level message
     * 
     * @param string $message
     * @param array $context Additional context information
     */
    public function warning(string $message, array $context = []): void
    {
        Logger::warning($message, $this->enrichContext($context));
    }

    /**
     * Log critical level message
     * 
     * @param string $message
     * @param array $context Additional context information
     */
    public function critical(string $message, array $context = []): void
    {
        Logger::critical($message, $this->enrichContext($context));
    }

    /**
     * Log API interaction
     * 
     * @param string $operation Type of operation
     * @param array $details Operation details
     */
    public function apiInteraction(string $operation, array $details): void
    {
        $this->info('AI API Interaction', [
            'operation' => $operation,
            'details' => $details,
            'response_time' => $details['duration'] ?? null
        ]);
    }

    /**
     * Log user action
     * 
     * @param string $action
     * @param array $details
     */
    public function userAction(string $action, array $details = []): void
    {
        $this->info('User Action', [
            'action' => $action,
            'user_id' => auth()->id(),
            'details' => $details
        ]);
    }

    /**
     * Log AI response metrics
     * 
     * @param array $metrics Response metrics
     */
    public function aiMetrics(array $metrics): void
    {
        $this->info('AI Response Metrics', [
            'response_time' => $metrics['duration'] ?? null,
            'tokens_used' => $metrics['tokens'] ?? null,
            'success_rate' => $metrics['success_rate'] ?? null
        ]);
    }

    /**
     * Log system error with full context
     * 
     * @param \Exception $e
     * @param array $additionalContext
     */
    public function exception(\Exception $e, array $additionalContext = []): void
    {
        $this->error('System Error', array_merge([
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ], $additionalContext));
    }

    /**
     * Enrich context with standard information
     * 
     * @param array $context
     * @return array
     */
    protected function enrichContext(array $context): array
    {
        return array_merge($context, [
            'source' => 'AIMiddleware',
            'user_id' => auth()->id() ?? 'system',
            'browser_info' => Helper::getBrowserInfo(),
            'timestamp' => now()->toDateTimeString()
        ]);
    }
}
