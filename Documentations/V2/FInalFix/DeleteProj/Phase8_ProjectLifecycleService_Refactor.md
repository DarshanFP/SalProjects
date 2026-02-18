# Phase 8 — ProjectLifecycleService Refactor

## Motivation

- **Reduce controller responsibility:** ProjectController no longer contains lifecycle logic
- **Centralize lifecycle logic:** trash, restore, force delete in one service
- **Single source of truth:** Easier to maintain and test

## Methods

### trash(Project $project, User $user): string

1. Validate: `ProjectPermissionHelper::canDelete($project, $user)` — abort 403 if false
2. If already trashed: return `'already_trashed'`
3. `$project->delete()`
4. Return `'trashed'`

### restore(Project $project, User $user): string

1. Validate: `ProjectPermissionHelper::canDelete($project, $user)` — abort 403 if false
2. If not trashed: return `'already_active'`
3. `$project->restore()`
4. Return `'restored'`

### forceDelete(Project $project, User $user): void

1. Validate role: `$user->role === 'admin'` — abort 403 if false
2. Log: `ActivityHistoryService::logProjectForceDelete($project, $user)`
3. Call: `ProjectForceDeleteCleanupService::forceDelete($project)`

## Authorization Handling

- **Delegated to ProjectPermissionHelper:** trash() and restore() use `canDelete()` (province + ownership)
- **Role check in service:** forceDelete() enforces `role === 'admin'` (defense in depth; route middleware already restricts)

## Controller Refactor

| Controller Method | Before | After |
|-------------------|--------|-------|
| destroy() | Load project, canDelete check, delete, redirect | Load project, call service trash(), redirect based on result |
| restore() | Load project, canDelete check, trashed check, restore, redirect | Load project, call service restore(), redirect based on result |
| forceDelete() | Load project, log, cleanup, redirect | Load project, call service forceDelete(), redirect |

## Behavior Verification

- **No functional changes:** Same redirects, same messages, same 403 behavior
- **Province checks:** Preserved via ProjectPermissionHelper::canDelete
- **Ownership:** Preserved via ProjectPermissionHelper::canDelete
- **Restore:** Same flow; early return for non-trashed
- **Force delete:** Same flow; admin-only
- **Dashboard:** Unaffected (no changes to listing or counts)

## Future Extensions

Possible service method additions:

- `submit(Project $project, User $user)` — delegate from submitToProvincial
- `approve(Project $project, User $user)` — delegate from approve flow
- `revert(Project $project, User $user, string $level)` — delegate from revert flow
