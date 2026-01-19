<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogHelper
{
    /**
     * List of sensitive fields that should never be logged
     */
    private static array $sensitiveFields = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'api_token',
        'secret',
        'private_key',
        'credit_card',
        'cvv',
        'ssn',
        'social_security_number',
        'bank_account',
        'routing_number',
    ];

    /**
     * Log request data safely, excluding sensitive fields
     *
     * @param string $message Log message
     * @param Request $request The request object
     * @param array $allowedFields Fields to include in log (empty = all non-sensitive)
     * @param string $level Log level (info, warning, error)
     * @return void
     */
    public static function logSafeRequest(
        string $message,
        Request $request,
        array $allowedFields = [],
        string $level = 'info'
    ): void {
        $data = [];
        
        // If specific fields are allowed, only log those
        if (!empty($allowedFields)) {
            foreach ($allowedFields as $key => $value) {
                // Support both array('field') and array('field' => 'custom_value')
                if (is_numeric($key)) {
                    $field = $value;
                    if ($request->has($field) && !self::isSensitiveField($field)) {
                        $data[$field] = $request->input($field);
                    }
                } else {
                    // Custom value provided
                    $data[$key] = $value;
                }
            }
        } else {
            // Log all non-sensitive fields
            $allData = $request->all();
            foreach ($allData as $field => $value) {
                if (!self::isSensitiveField($field)) {
                    // Truncate long values to prevent log bloat
                    if (is_string($value) && strlen($value) > 500) {
                        $data[$field] = substr($value, 0, 500) . '... (truncated)';
                    } else {
                        $data[$field] = $value;
                    }
                }
            }
        }
        
        // Add metadata
        $data['method'] = $request->method();
        $data['url'] = $request->url();
        $data['ip'] = $request->ip();
        $data['user_agent'] = $request->userAgent();
        
        // Log with appropriate level
        match ($level) {
            'warning' => Log::warning($message, $data),
            'error' => Log::error($message, $data),
            default => Log::info($message, $data),
        };
    }
    
    /**
     * Log error with safe request data
     *
     * @param string $message Error message
     * @param \Exception|\Throwable $exception The exception
     * @param Request|null $request Optional request object
     * @param array $allowedFields Fields to include in log
     * @return void
     */
    public static function logError(
        string $message,
        \Exception|\Throwable $exception,
        ?Request $request = null,
        array $allowedFields = []
    ): void {
        $data = [
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];
        
        if ($request) {
            $requestData = [];
            if (!empty($allowedFields)) {
                foreach ($allowedFields as $key => $value) {
                    if (is_numeric($key)) {
                        $field = $value;
                        if ($request->has($field) && !self::isSensitiveField($field)) {
                            $requestData[$field] = $request->input($field);
                        }
                    } else {
                        $requestData[$key] = $value;
                    }
                }
            }
            $requestData['method'] = $request->method();
            $requestData['url'] = $request->url();
            $data['request'] = $requestData;
        }
        
        Log::error($message, $data);
    }
    
    /**
     * Get default allowed fields for project requests
     *
     * @return array
     */
    public static function getProjectAllowedFields(): array
    {
        return [
            'project_id',
            'project_type',
            'project_title',
            'society_name',
            'overall_project_period',
            'current_phase',
        ];
    }
    
    /**
     * Get default allowed fields for report requests
     *
     * @return array
     */
    public static function getReportAllowedFields(): array
    {
        return [
            'project_id',
            'report_type',
            'report_period',
            'month',
            'year',
        ];
    }
    
    /**
     * Check if a field is sensitive
     *
     * @param string $field Field name
     * @return bool
     */
    private static function isSensitiveField(string $field): bool
    {
        $fieldLower = strtolower($field);
        
        foreach (self::$sensitiveFields as $sensitive) {
            if (str_contains($fieldLower, $sensitive)) {
                return true;
            }
        }
        
        return false;
    }
}

