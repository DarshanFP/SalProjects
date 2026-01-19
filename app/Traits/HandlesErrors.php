<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\ProjectException;
use App\Exceptions\ProjectPermissionException;
use App\Exceptions\ProjectStatusException;
use Exception;

trait HandlesErrors
{
    /**
     * Handle exceptions with standardized error handling
     *
     * @param Exception $e The exception to handle
     * @param string $context Context for logging (e.g., 'ProjectController@store')
     * @param string|null $userMessage User-friendly error message (optional)
     * @param array $contextData Additional context data for logging
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function handleException(
        Exception $e,
        string $context,
        ?string $userMessage = null,
        array $contextData = []
    ) {
        // Rollback transaction if one is active
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        // Log the exception with context
        $logContext = array_merge([
            'context' => $context,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], $contextData);

        Log::error("Error in {$context}", $logContext);

        // Handle specific exception types
        if ($e instanceof ValidationException) {
            return $this->handleValidationException($e);
        }

        if ($e instanceof ModelNotFoundException) {
            return $this->handleModelNotFoundException($e, $userMessage);
        }

        if ($e instanceof ProjectPermissionException) {
            return $this->handleProjectPermissionException($e);
        }

        if ($e instanceof ProjectStatusException) {
            return $this->handleProjectStatusException($e);
        }

        if ($e instanceof ProjectException) {
            return $this->handleProjectException($e);
        }

        // Generic exception handling
        return $this->handleGenericException($e, $userMessage ?? 'An error occurred. Please try again.');
    }

    /**
     * Handle validation exceptions
     *
     * @param ValidationException $e
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handleValidationException(ValidationException $e)
    {
        Log::warning('Validation failed', [
            'errors' => $e->errors(),
        ]);

        return redirect()->back()
            ->withErrors($e->errors())
            ->withInput();
    }

    /**
     * Handle model not found exceptions
     *
     * @param ModelNotFoundException $e
     * @param string|null $userMessage
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function handleModelNotFoundException(ModelNotFoundException $e, ?string $userMessage = null)
    {
        $message = $userMessage ?? 'The requested resource was not found.';

        if (request()->expectsJson()) {
            return response()->json([
                'error' => $message,
            ], 404);
        }

        Log::warning('Model not found', [
            'model' => $e->getModel(),
            'ids' => $e->getIds(),
        ]);

        return redirect()->back()
            ->withErrors(['error' => $message]);
    }

    /**
     * Handle project permission exceptions
     *
     * @param ProjectPermissionException $e
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function handleProjectPermissionException(ProjectPermissionException $e)
    {
        // Exception already handles rendering, but we can log it
        Log::warning('Permission denied', [
            'message' => $e->getMessage(),
            'reason' => $e->getReason(),
        ]);

        return $e->render(request());
    }

    /**
     * Handle project status exceptions
     *
     * @param ProjectStatusException $e
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function handleProjectStatusException(ProjectStatusException $e)
    {
        // Exception already handles rendering, but we can log it
        Log::warning('Status error', [
            'message' => $e->getMessage(),
            'status' => $e->getStatus(),
            'allowed_statuses' => $e->getAllowedStatuses(),
        ]);

        return $e->render(request());
    }

    /**
     * Handle project exceptions
     *
     * @param ProjectException $e
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function handleProjectException(ProjectException $e)
    {
        // Exception already handles rendering, but we can log it
        Log::error('Project error', [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
        ]);

        return $e->render(request());
    }

    /**
     * Handle generic exceptions
     *
     * @param Exception $e
     * @param string $userMessage
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function handleGenericException(Exception $e, string $userMessage)
    {
        if (request()->expectsJson()) {
            return response()->json([
                'error' => $userMessage,
            ], 500);
        }

        return redirect()->back()
            ->withErrors(['error' => $userMessage])
            ->withInput();
    }

    /**
     * Execute code within a transaction with error handling
     *
     * @param callable $callback The code to execute
     * @param string $context Context for logging
     * @param string|null $userMessage User-friendly error message
     * @param array $contextData Additional context data for logging
     * @return mixed
     */
    protected function executeInTransaction(
        callable $callback,
        string $context,
        ?string $userMessage = null,
        array $contextData = []
    ) {
        DB::beginTransaction();

        try {
            $result = $callback();
            DB::commit();
            return $result;
        } catch (Exception $e) {
            return $this->handleException($e, $context, $userMessage, $contextData);
        }
    }

    /**
     * Standard error messages
     */
    protected function getStandardErrorMessage(string $action, string $resource = 'resource'): string
    {
        $messages = [
            'create' => "There was an error creating the {$resource}. Please try again.",
            'update' => "There was an error updating the {$resource}. Please try again.",
            'delete' => "There was an error deleting the {$resource}. Please try again.",
            'submit' => "There was an error submitting the {$resource}. Please try again.",
            'load' => "Failed to load the {$resource}.",
            'save' => "There was an error saving the {$resource}. Please try again.",
        ];

        return $messages[$action] ?? "An error occurred while processing the {$resource}.";
    }
}
