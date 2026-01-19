<?php

namespace App\Exceptions;

use Exception;

class ProjectPermissionException extends Exception
{
    protected $reason;

    public function __construct(string $message, string $reason = '', int $code = 403)
    {
        parent::__construct($message, $code);
        $this->reason = $reason;
    }

    /**
     * Get the reason for the permission denial.
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => $this->getMessage(),
                'reason' => $this->reason,
                'code' => $this->getCode(),
            ], $this->getCode() ?: 403);
        }

        return redirect()->back()
            ->with('error', $this->getMessage())
            ->with('reason', $this->reason);
    }
}

