# M4.3 — Reject Centralization

**Milestone:** M4 — Workflow & State Machine Hardening  
**Phase:** M4.3 — Reject Centralization (Move Reject Transition Into ProjectStatusService)  
**Date:** 2026-02-15

---

## Objective

Previously, **CoordinatorController** set the rejected status directly:

```php
$project->status = ProjectStatus::REJECTED_BY_COORDINATOR;
$project->save();
```

This bypassed ProjectStatusService and transition control. Reject is now centralized in the service with the same behavior.

---

## Files Modified

| File | Change |
|------|--------|
| `app/Services/ProjectStatusService.php` | Added `reject(Project $project, User $user): bool`; added `REJECTED_BY_COORDINATOR` to `canTransition()` map for `FORWARDED_TO_COORDINATOR`. |
| `app/Http/Controllers/CoordinatorController.php` | Replaced direct status assignment and `logStatusChange` with `ProjectStatusService::reject($project, $coordinator)` inside try/catch. |

---

## Before / After Snippets

### CoordinatorController — rejectProject()

**Before:**

```php
if($coordinator->role !== 'coordinator' || !ProjectStatus::isForwardedToCoordinator($project->status)) {
    abort(403, 'Unauthorized action.');
}

$previousStatus = $project->status;
$project->status = ProjectStatus::REJECTED_BY_COORDINATOR;
$project->save();

// Log status change
\App\Services\ProjectStatusService::logStatusChange($project, $previousStatus, ProjectStatus::REJECTED_BY_COORDINATOR, $coordinator);

// Notify executor about rejection
```

**After:**

```php
if ($coordinator->role !== 'coordinator' || !ProjectStatus::isForwardedToCoordinator($project->status)) {
    abort(403, 'Unauthorized action.');
}

try {
    ProjectStatusService::reject($project, $coordinator);
} catch (Exception $e) {
    return redirect()->back()->withErrors(['error' => $e->getMessage()]);
}

// Notify executor about rejection
```

### ProjectStatusService — new method

```php
/**
 * Reject project (coordinator only). M4.3: centralizes reject transition.
 * Does NOT modify financial fields.
 */
public static function reject(Project $project, User $user): bool
{
    if ($user->role !== 'coordinator') {
        throw new Exception('Only coordinator can reject projects.');
    }

    if (!ProjectStatus::isForwardedToCoordinator($project->status)) {
        throw new Exception('Project can only be rejected when forwarded to coordinator. Current status: ' . $project->status);
    }

    $previousStatus = $project->status;
    $project->status = ProjectStatus::REJECTED_BY_COORDINATOR;
    $saved = $project->save();

    if ($saved) {
        self::logStatusChange($project, $previousStatus, ProjectStatus::REJECTED_BY_COORDINATOR, $user);
        Log::info('Project rejected by coordinator', [...]);
    }

    return $saved;
}
```

---

## Why Controller-Level Reject Was Risky

1. **Bypassed transition control:** Approve, revert, forward, and submit all go through ProjectStatusService with validation and logging. Reject was the only transition done in the controller, so it was invisible to any future transition guard or audit that relies on the service.
2. **canTransition() inconsistency:** The transition map in `canTransition()` had no entry for REJECTED_BY_COORDINATOR, so any code using that map would treat reject as invalid even though it was allowed in the UI.
3. **Single point of change:** All status transitions are now in one place (ProjectStatusService), making it easier to add cross-cutting behavior (e.g. logging, events, validation) without touching controllers.

---

## Risk Removed

- **M4.1 HIGH H1:** “Reject bypasses ProjectStatusService” — **addressed.** Reject is now performed and logged inside ProjectStatusService; the controller only authorizes and calls the service.

---

## canTransition() Update

- **FORWARDED_TO_COORDINATOR** now includes:  
  `ProjectStatus::REJECTED_BY_COORDINATOR => ['coordinator']`.
- `canTransition()` is still not enforced in the request path; only the mapping was aligned so future use (e.g. guards or UI) will treat reject as a valid transition.

---

## What Was Not Modified

- Financial revert logic (M4.2)
- Approval logic
- Revert logic
- DPReport
- Dashboard logic
- Resolver
- Export layer
- Permissions (controller still performs the same 403 check before calling the service)

---

## Regression Risk

- **Level:** LOW.
- Reject still only applies when status is FORWARDED_TO_COORDINATOR and user role is coordinator. No financial fields are changed. Notification, cache invalidation, and redirect are unchanged.

---

## Manual Verification Checklist

1. **Reject a forwarded project**  
   - As coordinator, reject a project in status “forwarded to coordinator”.  
   - Confirm status becomes `REJECTED_BY_COORDINATOR`.

2. **No financial change**  
   - Reject does not set or clear `amount_sanctioned` or `opening_balance`; confirm in DB or UI that those fields are unchanged after reject.

3. **Revert still works**  
   - Revert flows (provincial/coordinator/general) unchanged; confirm revert still works as before.

4. **Integration tests**  
   - Run relevant tests (e.g. `FinancialResolverTest`, `CoordinatorAggregationParityTest`); all passed.

---

**M4.3 Complete — Reject Transition Centralized**
