<?php

namespace App\Services\Logging;

use App\Traits\Loggable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StructuredLogger
{
    use Loggable;
    /**
     * Log an informational message with structured data
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Log an error message with structured data
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Log a warning message with structured data
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Log a debug message with structured data
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Log an API request with structured data
     */
    public function apiRequest(string $method, string $url, array $requestData = [], array $responseData = [], ?int $statusCode = null): void
    {
        $this->info('API Request', [
            'request_id' => Str::uuid()->toString(),
            'method' => $method,
            'url' => $url,
            'request_data' => $requestData,
            'response_data' => $responseData,
            'status_code' => $statusCode,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log a database query with structured data
     */
    public function query(string $sql, array $bindings = [], float $time = 0): void
    {
        $this->debug('Database Query', [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $time,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log an authentication attempt with structured data
     */
    public function authAttempt(string $email, bool $success, ?string $reason = null): void
    {
        $this->info('Authentication Attempt', [
            'email' => $email,
            'success' => $success,
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log a task operation with structured data
     */
    public function taskOperation(string $operation, int $taskId, array $data = []): void
    {
        $this->info('Task Operation', [
            'operation' => $operation,
            'task_id' => $taskId,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Internal logging method that structures the data
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $structuredData = array_merge([
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
            'environment' => config('app.env'),
            'request_id' =>  Str::uuid()->toString(),
        ], $context);

        Log::channel('stack')->{$level}(json_encode($structuredData));
    }
}
