# Error Handling Standards

**Date:** January 2025  
**Version:** 1.0

---

## Overview

This document defines the standardized error handling approach for the Laravel application. All controllers should follow these standards to ensure consistent error handling, logging, and user experience.

---

## Error Handling Trait

### Usage

All controllers should use the `HandlesErrors` trait:

```php
use App\Traits\HandlesErrors;

class YourController extends Controller
{
    use HandlesErrors;
    
    // ... your methods
}
```

---

## Standard Methods

### 1. `handleException()`

Main method for handling exceptions with standardized error handling.

**Signature:**
```php
protected function handleException(
    Exception $e,
    string $context,
    ?string $userMessage = null,
    array $contextData = []
)
```

**Parameters:**
- `$e`: The exception to handle
- `$context`: Context for logging (e.g., 'ProjectController@store')
- `$userMessage`: User-friendly error message (optional)
- `$contextData`: Additional context data for logging

**Returns:**
- Redirect response (for web requests)
- JSON response (for API requests)

**Features:**
- Automatically rolls back transactions
- Logs exceptions with context
- Handles specific exception types appropriately
- Returns user-friendly error messages

**Example:**
```php
try {
    // ... your code ...
} catch (\Exception $e) {
    return $this->handleException($e, 'ProjectController@store', 'Failed to create project', [
        'project_type' => $request->project_type,
    ]);
}
```

---

### 2. `executeInTransaction()`

Execute code within a transaction with automatic error handling.

**Signature:**
```php
protected function executeInTransaction(
    callable $callback,
    string $context,
    ?string $userMessage = null,
    array $contextData = []
)
```

**Parameters:**
- `$callback`: The code to execute (closure/callable)
- `$context`: Context for logging
- `$userMessage`: User-friendly error message (optional)
- `$contextData`: Additional context data for logging

**Returns:**
- Result of callback on success
- Error response on exception

**Example:**
```php
public function store(StoreProjectRequest $request)
{
    return $this->executeInTransaction(function () use ($request) {
        // ... your code ...
        DB::commit(); // Optional - executed automatically
        return redirect()->route('projects.index')->with('success', 'Created successfully.');
    }, 'ProjectController@store', $this->getStandardErrorMessage('create', 'project'), [
        'project_type' => $request->project_type,
    ]);
}
```

---

### 3. `getStandardErrorMessage()`

Get standardized error messages for common actions.

**Signature:**
```php
protected function getStandardErrorMessage(string $action, string $resource = 'resource'): string
```

**Parameters:**
- `$action`: Action type ('create', 'update', 'delete', 'submit', 'load', 'save')
- `$resource`: Resource name (e.g., 'project', 'report')

**Returns:**
- Standardized error message string

**Example:**
```php
$message = $this->getStandardErrorMessage('create', 'project');
// Returns: "There was an error creating the project. Please try again."
```

---

## Exception-Specific Handlers

The trait automatically handles the following exception types:

### 1. ValidationException
- Logs as warning
- Returns validation errors to user
- Preserves input

### 2. ModelNotFoundException
- Logs as warning
- Returns 404 error
- User-friendly message

### 3. ProjectPermissionException
- Logs as warning
- Returns 403 error
- Includes reason

### 4. ProjectStatusException
- Logs as warning
- Returns 403 error
- Includes status information

### 5. ProjectException
- Logs as error
- Returns appropriate HTTP code
- User-friendly message

### 6. Generic Exception
- Logs as error with full trace
- Returns 500 error (or user message)
- Preserves input (for web requests)

---

## Standard Error Messages

Use `getStandardErrorMessage()` for common actions:

| Action | Message Template |
|--------|-----------------|
| create | "There was an error creating the {resource}. Please try again." |
| update | "There was an error updating the {resource}. Please try again." |
| delete | "There was an error deleting the {resource}. Please try again." |
| submit | "There was an error submitting the {resource}. Please try again." |
| load | "Failed to load the {resource}." |
| save | "There was an error saving the {resource}. Please try again." |

---

## Error Response Format

### Web Requests (Redirect)
```php
return redirect()->back()
    ->withErrors(['error' => 'User-friendly message'])
    ->withInput(); // Optional, for forms
```

### API Requests (JSON)
```php
return response()->json([
    'error' => 'User-friendly message',
], 500);
```

---

## Logging Standards

### Log Levels

- **Error**: Unexpected exceptions, system errors
- **Warning**: Validation failures, not found, permission denied
- **Info**: Normal operations, success cases

### Log Context

Always include:
- Context (controller@method)
- Error message
- Stack trace (for errors)
- Relevant identifiers (project_id, report_id, user_id, etc.)

**Example:**
```php
Log::error('Error in ProjectController@store', [
    'context' => 'ProjectController@store',
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
    'project_type' => $request->project_type,
    'project_title' => $request->project_title,
]);
```

---

## Migration Guide

### Before (Old Pattern)
```php
public function store(Request $request)
{
    DB::beginTransaction();
    try {
        // ... code ...
        DB::commit();
        return redirect()->route('projects.index')->with('success', 'Created.');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error', ['error' => $e->getMessage()]);
        return redirect()->back()->withErrors(['error' => 'Error occurred.'])->withInput();
    }
}
```

### After (New Pattern)
```php
use App\Traits\HandlesErrors;

class ProjectController extends Controller
{
    use HandlesErrors;

    public function store(StoreProjectRequest $request)
    {
        return $this->executeInTransaction(function () use ($request) {
            // ... code ...
            return redirect()->route('projects.index')->with('success', 'Created.');
        }, 'ProjectController@store', $this->getStandardErrorMessage('create', 'project'), [
            'project_type' => $request->project_type,
        ]);
    }
}
```

---

## Best Practices

1. **Always use the trait** for error handling
2. **Use `executeInTransaction()`** for database operations
3. **Use `getStandardErrorMessage()`** for common error messages
4. **Include context data** in error handling for better logging
5. **Use custom exceptions** where appropriate (ProjectException, ProjectPermissionException, etc.)
6. **Don't expose internal errors** to users - use user-friendly messages
7. **Always log errors** with sufficient context for debugging
8. **Roll back transactions** on errors (handled automatically by trait)
9. **Preserve input** for form submissions (handled automatically)
10. **Use appropriate HTTP status codes** (handled automatically)

---

## Custom Exceptions

### When to Use

- **ProjectException**: Generic project-related errors
- **ProjectPermissionException**: Permission/authorization errors
- **ProjectStatusException**: Status-related errors (invalid status transitions)

### Throwing Custom Exceptions

```php
use App\Exceptions\ProjectPermissionException;

if (!$user->canEdit($project)) {
    throw new ProjectPermissionException(
        'You do not have permission to edit this project.',
        'Project is not editable in current status'
    );
}
```

The trait will automatically handle these exceptions appropriately.

---

## Testing

When testing controllers with error handling:

1. Test that exceptions are caught and handled
2. Test that transactions are rolled back
3. Test that appropriate error messages are returned
4. Test that errors are logged
5. Test both web and API responses

---

## Examples

### Example 1: Simple Store Method
```php
public function store(StoreProjectRequest $request)
{
    return $this->executeInTransaction(function () use ($request) {
        $project = Project::create($request->validated());
        return redirect()->route('projects.index')->with('success', 'Project created.');
    }, 'ProjectController@store', $this->getStandardErrorMessage('create', 'project'));
}
```

### Example 2: Method with Try-Catch
```php
public function show($id)
{
    try {
        $project = Project::findOrFail($id);
        return view('projects.show', compact('project'));
    } catch (\Exception $e) {
        return $this->handleException($e, 'ProjectController@show', $this->getStandardErrorMessage('load', 'project'), [
            'project_id' => $id,
        ]);
    }
}
```

### Example 3: Custom Exception
```php
public function submit($id)
{
    return $this->executeInTransaction(function () use ($id) {
        $project = Project::findOrFail($id);
        
        if (!$project->canBeSubmitted()) {
            throw new ProjectStatusException(
                'Project cannot be submitted in current status.',
                $project->status,
                ['draft', 'reverted']
            );
        }
        
        // ... submit logic ...
        return redirect()->route('projects.index')->with('success', 'Project submitted.');
    }, 'ProjectController@submit', $this->getStandardErrorMessage('submit', 'project'));
}
```

---

**Version:** 1.0  
**Last Updated:** January 2025
