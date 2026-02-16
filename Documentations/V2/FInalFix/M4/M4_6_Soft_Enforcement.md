# M4.6 — Soft Enforcement (Log-Only)

**Milestone:** M4 — Workflow & State Machine Hardening  
**Phase:** M4.6 — Soft Enforcement (Add Transition Guard Logging)  
**Date:** 2026-02-15

---

## Objective

Add a **soft guard** inside `ProjectStatusService`: before each status mutation, call `canTransition($from, $to, $user->role)`. If it returns false, log a warning with full context but **do not block** — the transition still runs. This provides observability before any strict enforcement.

---

## 1) Methods Instrumented

Every method that changes project status now has the guard immediately before the status assignment and save:

| Method | File:Line (guard location) |
|--------|----------------------------|
| submitToProvincial | app/Services/ProjectStatusService.php (before status = SUBMITTED_TO_PROVINCIAL) |
| forwardToCoordinator | app/Services/ProjectStatusService.php (before status = FORWARDED_TO_COORDINATOR) |
| approve | app/Services/ProjectStatusService.php (before status = approved status) |
| reject | app/Services/ProjectStatusService.php (before status = REJECTED_BY_COORDINATOR) |
| revertByProvincial | app/Services/ProjectStatusService.php (before applyFinancialResetOnRevert + status) |
| revertByCoordinator | app/Services/ProjectStatusService.php (before applyFinancialResetOnRevert + status) |
| approveAsCoordinator | app/Services/ProjectStatusService.php (before status = APPROVED_BY_GENERAL_AS_COORDINATOR) |
| approveAsProvincial | app/Services/ProjectStatusService.php (before status = FORWARDED_TO_COORDINATOR) |
| revertAsCoordinator | app/Services/ProjectStatusService.php (before applyFinancialResetOnRevert + status) |
| revertAsProvincial | app/Services/ProjectStatusService.php (before applyFinancialResetOnRevert + status) |
| revertToLevel | app/Services/ProjectStatusService.php (before applyFinancialResetOnRevert + status) |

In each method:

1. `$from = $project->status` and `$to =` target status (constant or computed `$newStatus`).
2. `if (!self::canTransition($from, $to, $user->role)) { Log::warning(...); }`
3. Existing logic continues unchanged (including `applyFinancialResetOnRevert` where present, then `$project->status = $to`, `$project->save()`, logging, return).

---

## 2) Log Format

**Level:** `warning`  
**Message:** `Invalid transition detected (soft)`  
**Context array:**

| Key | Description |
|-----|-------------|
| project_id | Project identifier |
| from | Current status before transition |
| to | Target status |
| user_id | ID of user performing the action |
| method | Calling method (e.g. `App\Services\ProjectStatusService::submitToProvincial`) |

---

## 3) Example Log Entry

```json
{
  "message": "Invalid transition detected (soft)",
  "context": {
    "project_id": "DP-2024-001",
    "from": "draft",
    "to": "approved_by_coordinator",
    "user_id": 5,
    "method": "App\\Services\\ProjectStatusService::approve"
  }
}
```

(Laravel typically adds timestamp, channel, and level to the log record.)

---

## 4) Confirmation: Runtime Unchanged

- **No blocking:** No exception, no abort, no early return. If `canTransition` is false, only `Log::warning` runs; execution continues.
- **No return-value change:** Methods still return the same `bool` or values as before.
- **No change to controllers, financial revert, reject, resolver, or constants.** Only `ProjectStatusService` was modified (added guard + use of `$to` in a few logStatusChange/Log::info calls for consistency).
- **Tests:** `FinancialResolverTest` and `CoordinatorAggregationParityTest` pass after implementation.

---

## 5) Risk Level

**LOW.** The guard is read-only (one conditional log). No control flow or return values are altered. If `canTransition()` is wrong or missing a transition, the only effect is a possible false warning in the log; the transition still succeeds.

---

## 6) Plan for Future Strict Enforcement

1. **Monitor logs** for "Invalid transition detected (soft)" in production or staging. Fix any map gaps (M4.5) or bugs so valid transitions do not log.
2. **When ready for strict enforcement:** Replace the guard body with something like:
   - `if (!self::canTransition($from, $to, $user->role)) { throw new Exception('Invalid status transition: ' . $from . ' -> ' . $to); }`
   - Or return a structured error and let the controller respond with 4xx.
3. **Keep rollback and “to draft” in mind:** Ensure the map allows approved_by_general_as_coordinator → forwarded_to_coordinator (rollback) and that “save as draft” (if ever routed through the service) is either in the map or explicitly excluded from the guard.

---

## Files Modified

| File | Change |
|------|--------|
| app/Services/ProjectStatusService.php | Added soft guard (canTransition check + Log::warning) in all 11 transition methods; use of `$to` in logStatusChange/Log where applicable. |

`Illuminate\Support\Facades\Log` was already in use; no new imports.

---

**M4.6 Complete — Soft Enforcement Guard Active (Log-Only)**
