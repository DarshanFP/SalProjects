<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

trait HandlesLogging
{
    /**
     * Log info message with controller context
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $context['controller'] = static::class;
        $context['user_id'] = Auth::id();

        Log::info($message, $context);
    }

    /**
     * Log error message with controller context
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        $context['controller'] = static::class;
        $context['user_id'] = Auth::id();

        Log::error($message, $context);
    }

    /**
     * Log warning message with controller context
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $context['controller'] = static::class;
        $context['user_id'] = Auth::id();

        Log::warning($message, $context);
    }

    /**
     * Log method entry
     *
     * @param string $methodName
     * @param array $context
     * @return void
     */
    protected function logMethodEntry(string $methodName, array $context = []): void
    {
        $this->logInfo("{$methodName} - Starting", $context);
    }

    /**
     * Log method success
     *
     * @param string $methodName
     * @param array $context
     * @return void
     */
    protected function logMethodSuccess(string $methodName, array $context = []): void
    {
        $this->logInfo("{$methodName} - Success", $context);
    }

    /**
     * Log access denied
     *
     * @param string $reason
     * @param array $context
     * @return void
     */
    protected function logAccessDenied(string $reason, array $context = []): void
    {
        $this->logWarning("Access denied - {$reason}", $context);
    }
}
