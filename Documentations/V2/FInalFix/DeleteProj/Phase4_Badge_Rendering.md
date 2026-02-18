# Phase 4 — Badge Rendering

## Summary

Centralized status badge logic: trashed projects show "Trashed" badge; others show workflow status.

## UI Logic

**Partial:** `resources/views/projects/partials/status-badge.blade.php`

```
IF $project->trashed():
    Display: <span class="badge bg-danger">Trashed</span>
    Optional: show pre-delete workflow status (when showPreDeleteStatus=true)
ELSE:
    Display: workflow status from Project::$statusLabels[$project->status]
```

## Usage

- `@include('projects.partials.status-badge', ['project' => $project])` — default
- `@include('projects.partials.status-badge', ['project' => $project, 'showPreDeleteStatus' => true])` — for trash list (shows Trashed + pre-delete status)

## Updated Views

- `projects/Oldprojects/index.blade.php` — pending projects list
- `projects/Oldprojects/approved.blade.php` — approved projects list
- `projects/trash/index.blade.php` — trash list

## Status vs deleted_at

- `$project->status` — workflow status (draft, submitted, approved, etc.)
- `$project->deleted_at` / `$project->trashed()` — soft delete state
- When trashed, we display "Trashed" as primary; workflow status can be shown as secondary context

## Screenshot Placeholder

_[Screenshot of trash list with Trashed badge and status]_ 

## No Workflow Confusion

- Trashed badge is distinct (bg-danger)
- Workflow status constants unchanged
- Badge color consistent: red for Trashed
