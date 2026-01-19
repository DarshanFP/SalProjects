<?php

namespace App\Exceptions;

use Exception;

class ProjectException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => $this->getMessage(),
                'code' => $this->getCode(),
            ], $this->getCode() ?: 400);
        }

        return redirect()->back()
            ->with('error', $this->getMessage())
            ->withInput();
    }
}

