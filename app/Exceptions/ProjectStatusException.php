<?php

namespace App\Exceptions;

use Exception;

class ProjectStatusException extends Exception
{
    protected $status;
    protected $allowedStatuses;

    public function __construct(string $message, string $status, array $allowedStatuses = [], int $code = 403)
    {
        parent::__construct($message, $code);
        $this->status = $status;
        $this->allowedStatuses = $allowedStatuses;
    }

    /**
     * Get the project status that caused the exception.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get the allowed statuses.
     */
    public function getAllowedStatuses(): array
    {
        return $this->allowedStatuses;
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => $this->getMessage(),
                'current_status' => $this->status,
                'allowed_statuses' => $this->allowedStatuses,
                'code' => $this->getCode(),
            ], $this->getCode() ?: 403);
        }

        return redirect()->back()
            ->with('error', $this->getMessage())
            ->with('current_status', $this->status)
            ->with('allowed_statuses', $this->allowedStatuses);
    }
}

