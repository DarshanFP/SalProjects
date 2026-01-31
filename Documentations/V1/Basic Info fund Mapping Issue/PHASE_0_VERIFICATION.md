# Phase 0 – Pre-Implementation Safeguards – Verification Report

**Date:** 2025-01-29  
**Status:** Complete  
**Source:** PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md

---

## 1. What Was Implemented

### 1.1 Feature Flags (config/budget.php)

| Key                                    | Default | Purpose                                                             |
| -------------------------------------- | ------- | ------------------------------------------------------------------- |
| `resolver_enabled`                     | `false` | When true, resolver used for read path (Phase 1) and sync (Phase 2) |
| `sync_to_projects_on_type_save`        | `false` | When true, sync after IIES/IES/ILP/IAH/IGE budget save              |
| `sync_to_projects_before_approval`     | `false` | When true, sync before Coordinator/General approval read            |
| `restrict_general_info_after_approval` | `false` | Phase 3: block overall/forwarded/local edits when approved          |
| `admin_reconciliation_enabled`         | `false` | Phase 6a: enable admin reconciliation UI                            |

**Environment variables:** `BUDGET_RESOLVER_ENABLED`, `BUDGET_SYNC_ON_TYPE_SAVE`, `BUDGET_SYNC_BEFORE_APPROVAL`, `BUDGET_RESTRICT_GENERAL_INFO_AFTER_APPROVAL`, `BUDGET_ADMIN_RECONCILIATION_ENABLED`

### 1.2 Logging Channel (config/logging.php)

- **Channel:** `budget`
- **Path:** `storage/logs/budget-YYYY-MM-DD.log`
- **Retention:** 90 days
- **Purpose:** Resolver calls, sync events, discrepancy logging

### 1.3 BudgetSyncGuard (app/Services/Budget/BudgetSyncGuard.php)

- `canSyncOnTypeSave(Project $project): bool` – config + status checks; returns false when approved
- `canSyncBeforeApproval(Project $project): bool` – config + status checks; true only when `forwarded_to_coordinator`
- `isApproved(Project $project): bool`
- `isReverted(Project $project): bool`

### 1.4 BudgetAuditLogger (app/Services/Budget/BudgetAuditLogger.php)

- `logResolverCall($projectId, $projectType, $resolvedValues, $dryRun)` – Phase 1
- `logDiscrepancy($projectId, $projectType, $resolved, $stored)` – Phase 1
- `logSync($projectId, $trigger, $oldValues, $newValues)` – Phase 2
- `logGuardRejection($projectId, $reason)` – Phase 2

**All methods log only; no DB writes.**

---

## 2. Safe Insertion Points (for Phase 1–2)

### 2.1 Resolver Hooks (Phase 1)

| Location                                                        | Purpose                                                                   |
| --------------------------------------------------------------- | ------------------------------------------------------------------------- |
| `ProjectController::show()` or equivalent                       | Optional: pass resolved values to Basic Info view when `resolver_enabled` |
| `resources/views/projects/partials/Show/general_info.blade.php` | Display resolved values when present (Phase 1 optional)                   |

### 2.2 Sync Hooks (Phase 2)

| Controller                                  | Method                | Trigger                                |
| ------------------------------------------- | --------------------- | -------------------------------------- |
| `IIES\IIESExpensesController`               | `store()`, `update()` | After successful commit                |
| `IES\IESExpensesController` (or equivalent) | `store()`, `update()` | After successful commit                |
| `ILP\BudgetController`                      | `store()`             | After successful commit                |
| `IAH\IAHBudgetDetailsController`            | `store()`, `update()` | After successful commit                |
| `IGE\IGEBudgetController`                   | `store()`             | After successful commit                |
| `CoordinatorController`                     | `approveProject()`    | Before reading overall/forwarded/local |
| `GeneralController`                         | Approval branch       | Before reading overall/forwarded/local |

### 2.3 Logging Hooks

- **Resolver:** Call `BudgetAuditLogger::logResolverCall()` in resolver or at call site
- **Discrepancy:** In resolver or scheduled command, call `BudgetAuditLogger::logDiscrepancy()` when resolved ≠ stored
- **Sync:** Call `BudgetAuditLogger::logSync()` before any `$project->update()` in Phase 2
- **Guard rejection:** Call `BudgetAuditLogger::logGuardRejection()` when `BudgetSyncGuard::canSync*()` returns false

---

## 3. Verification: Project Status Checks

| Check                                       | Location                          | Verified                                                                                                                    |
| ------------------------------------------- | --------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| `ProjectStatus::isApproved()`               | `app/Constants/ProjectStatus.php` | Yes – returns true for `approved_by_coordinator`, `approved_by_general_as_coordinator`, `approved_by_general_as_provincial` |
| `ProjectStatus::isReverted()`               | `app/Constants/ProjectStatus.php` | Yes – includes all reverted statuses                                                                                        |
| `ProjectStatus::isForwardedToCoordinator()` | `app/Constants/ProjectStatus.php` | Yes                                                                                                                         |
| `ProjectStatus::getEditableStatuses()`      | `app/Constants/ProjectStatus.php` | Yes – includes reverted statuses                                                                                            |

---

## 4. Verification: Role Checks

| Check      | Location                 | Verified                                                                     |
| ---------- | ------------------------ | ---------------------------------------------------------------------------- |
| Admin role | `users.role === 'admin'` | Yes – `ProjectPermissionHelper::canView()` grants admin view of all projects |
| Role field | `app/Models/User.php`    | Yes – `$user->role` (string)                                                 |

---

## 5. Verification: No Unexpected Budget Mutations

| Writer                                    | Fields Written                                                     | When                                                                    |
| ----------------------------------------- | ------------------------------------------------------------------ | ----------------------------------------------------------------------- |
| `GeneralInfoController::update()`         | `overall_project_budget`, `amount_forwarded`, `local_contribution` | On General Info form submit (via `UpdateProjectRequest` validated data) |
| `GeneralInfoController::store()`          | Same + `amount_sanctioned`/`opening_balance` not written           | New project create                                                      |
| `CoordinatorController::approveProject()` | `amount_sanctioned`, `opening_balance`                             | At coordinator approval                                                 |
| `GeneralController` (approval branch)     | `amount_sanctioned`, `opening_balance`                             | At general-as-coordinator approval                                      |

**Confirmed:** `amount_sanctioned` and `opening_balance` are written **only** by approval flows. `UpdateProjectRequest` has `total_amount_sanctioned` but GeneralInfoController does not map it to `amount_sanctioned` in the update path; approval remains sole authority.

**Type-specific controllers (IIES, IES, ILP, IAH, IGE):** Do **not** currently write to `projects` budget fields. They write to type-specific tables only. Phase 2 will add sync calls at these insertion points.

---

## 6. Assumptions

1. All feature flags remain `false` until Phase 1 logs are reviewed and Phase 2 is explicitly enabled.
2. The `budget` log channel will be monitored for Phase 1 discrepancy logs before enabling sync.
3. No code path in Phase 0 writes to `projects`; all changes are additive (config, logging, guard/logger classes).

---

## 7. Rollback (Phase 0)

- Remove feature flags from `config/budget.php` (or set all to false)
- Remove `budget` channel from `config/logging.php`
- Delete `BudgetSyncGuard.php` and `BudgetAuditLogger.php`
- No DB changes; no behaviour change when flags are false

---

## 8. Next Steps

**Phase 1:** Create `ProjectFundFieldsResolver` with `resolve(Project $project, bool $dryRun = true)`. Use only `dryRun = true`; add optional Basic Info display; log discrepancies. **Do not proceed until Phase 0 is reviewed and confirmed.**
