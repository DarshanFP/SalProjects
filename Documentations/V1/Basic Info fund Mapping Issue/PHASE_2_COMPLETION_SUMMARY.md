# Phase 2 – Controlled Sync to `projects` – Completion Summary

**Date:** 2026-01-29  
**Status:** Implemented – awaiting verification  
**Source:** PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md, PHASE_0_VERIFICATION.md, PHASE_1_COMPLETION_SUMMARY.md, PHASE_1_RESOLVER_REVIEW_AND_VALIDATION.md

---

## 1. What Was Implemented

### 1.1 BudgetSyncService

**Location:** `app/Services/Budget/BudgetSyncService.php`

**Responsibilities:**

- **syncFromTypeSave(Project $project): bool** – Step 2A
    - Guard: `BudgetSyncGuard::canSyncOnTypeSave($project)` (resolver + sync-on-type-save flags on; project NOT approved).
    - Call `ProjectFundFieldsResolver::resolve($project, false)`.
    - Update **only** `overall_project_budget`, `local_contribution`, `amount_forwarded` on `projects`.
    - Log via `BudgetAuditLogger::logSync(project_id, 'budget_save', old_values, new_values, project_type)`.
    - Idempotent; no sanctioned/opening writes.

- **syncBeforeApproval(Project $project): bool** – Step 2B
    - Guard: `BudgetSyncGuard::canSyncBeforeApproval($project)` (resolver + sync-before-approval flags on; status `forwarded_to_coordinator`).
    - Call resolver; update **all five** fund fields on `projects`.
    - Log via `BudgetAuditLogger::logSync(..., 'pre_approval', ...)`.
    - Approval controller then computes sanctioned/opening and overwrites them.

### 1.2 Step 2A – Sync on Type-Specific Budget Save (LOW RISK)

Sync runs **after** successful commit in:

| Controller                       | Methods               | Trigger                                |
| -------------------------------- | --------------------- | -------------------------------------- |
| `IIES\IIESExpensesController`    | `store()`             | After DB::commit()                     |
| `IES\IESExpensesController`      | `store()`             | After DB::commit()                     |
| `ILP\BudgetController`           | `store()`, `update()` | After DB::commit()                     |
| `IAH\IAHBudgetDetailsController` | `store()`, `update()` | After DB::commit()                     |
| `IGE\IGEBudgetController`        | `store()`             | After DB::commit()                     |
| `Projects\BudgetController`      | `update()`            | After phase budget write (Development) |

Flow: budget save → commit → load project → `BudgetSyncService::syncFromTypeSave($project)` (guarded). Only overall, local_contribution, amount_forwarded are written.

### 1.3 Step 2B – Sync Before Approval (MEDIUM RISK)

Sync runs **before** reading overall/forwarded/local in approval:

| Controller              | Method             | Insertion point                                                                                               |
| ----------------------- | ------------------ | ------------------------------------------------------------------------------------------------------------- |
| `CoordinatorController` | `approveProject()` | After loading project (with budgets); before commencement/approve; then `$project->refresh()`                 |
| `GeneralController`     | `approveProject()` | In coordinator-context branch, after loading project; before commencement/approve; then `$project->refresh()` |

Flow: load project → `BudgetSyncService::syncBeforeApproval($project)` → refresh → (commencement) → approve → read overall/forwarded/local → compute sanctioned/opening → save. Approval overwrites sanctioned and opening.

### 1.4 Logging

- **BudgetAuditLogger::logSync()** updated: message "Budget sync applied"; includes `project_id`, `project_type`, `trigger`, `old_values`, `new_values`, `timestamp`.
- Guard rejections: `BudgetAuditLogger::logGuardRejection(project_id, reason)` when sync is blocked.

### 1.5 Guards & Safety (Enforced)

- If project is **approved** → no sync on type save (BudgetSyncGuard::canSyncOnTypeSave returns false).
- If project is **reverted** → sync on type save allowed (not approved).
- Feature flags gate all writes: `budget.resolver_enabled`, `budget.sync_to_projects_on_type_save`, `budget.sync_to_projects_before_approval`.
- All sync writes logged via BudgetAuditLogger.
- Sync logic is idempotent (same input → same output).

---

## 2. What Was NOT Done (Per Plan)

- No modification of **approved** projects.
- No modification of historical reports or dashboards.
- No change to approval controllers’ **core** logic (only pre-read sync + refresh).
- No auto-correction of approved data.
- No admin reconciliation UI.
- No backfill of legacy approved projects.

---

## 3. How to Enable Phase 2

In `.env` (or config override):

```env
BUDGET_RESOLVER_ENABLED=true
BUDGET_SYNC_ON_TYPE_SAVE=true
BUDGET_SYNC_BEFORE_APPROVAL=true
```

Or in `config/budget.php`:

```php
'resolver_enabled' => true,
'sync_to_projects_on_type_save' => true,
'sync_to_projects_before_approval' => true,
```

**Recommendation:** Enable on staging first; confirm logs and behaviour before production.

---

## 4. Verification Requirements (After Enabling)

Demonstrate with **at least**:

1. **One Development project**
    - Before: note `projects.overall_project_budget`, `local_contribution`, `amount_forwarded` (and optionally sanctioned/opening).
    - Update phase budget (BudgetController::update).
    - After: confirm sync log in `storage/logs/budget-YYYY-MM-DD.log`; confirm `projects` has updated overall/local/forwarded; sanctioned/opening unchanged until approval.

2. **One IIES project**
    - Before: note `projects` fund fields.
    - Save IIES expenses (IIESExpensesController::store or update).
    - After: confirm sync log; `projects` has overall, local, forwarded from resolver; sanctioned/opening not written by type save.

3. **One IGE project**
    - Before: note `projects` fund fields.
    - Save IGE budget (IGEBudgetController::store).
    - After: confirm sync log; `projects` updated from resolver (overall, local, forwarded only on type save).

4. **Approval overwrites sanctioned/opening**
    - Use a pre-approval project (e.g. forwarded_to_coordinator).
    - Trigger pre-approval sync (navigate to approval and load project with flags on).
    - Confirm log "Budget sync applied" with trigger `pre_approval`.
    - Complete approval.
    - Confirm coordinator/general approval computes and saves `amount_sanctioned` and `opening_balance`; these are the authority values, not the provisional pre-approval sync values.

---

## 5. Rollback (Phase 2)

1. Set in `.env` or config:  
   `BUDGET_SYNC_ON_TYPE_SAVE=false`  
   `BUDGET_SYNC_BEFORE_APPROVAL=false`
2. Deploy; no sync runs. Resolver remains available for read path.
3. Optionally revert controller edits (remove sync calls and BudgetSyncService usage) and delete or bypass `BudgetSyncService.php`.

No schema changes; no automatic backfill in this phase.

---

## 6. Files Touched

| File                                                               | Change                                                            |
| ------------------------------------------------------------------ | ----------------------------------------------------------------- |
| `app/Services/Budget/BudgetSyncService.php`                        | **New** – sync from type save + sync before approval              |
| `app/Services/Budget/BudgetAuditLogger.php`                        | logSync: message + timestamp + project_type param                 |
| `app/Http/Controllers/Projects/IIES/IIESExpensesController.php`    | After store commit: load project, syncFromTypeSave                |
| `app/Http/Controllers/Projects/IES/IESExpensesController.php`      | After store commit: load project, syncFromTypeSave                |
| `app/Http/Controllers/Projects/ILP/BudgetController.php`           | After store/update commit: load project, syncFromTypeSave         |
| `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php` | After store/update commit: load project, syncFromTypeSave         |
| `app/Http/Controllers/Projects/IGE/IGEBudgetController.php`        | After store commit: load project, syncFromTypeSave                |
| `app/Http/Controllers/Projects/BudgetController.php`               | After update: refresh + load budgets, syncFromTypeSave            |
| `app/Http/Controllers/CoordinatorController.php`                   | approveProject: after load, syncBeforeApproval + refresh          |
| `app/Http/Controllers/GeneralController.php`                       | approveProject (coordinator branch): syncBeforeApproval + refresh |

---

**Document status:** Phase 2 implementation complete. **STOP and WAIT for confirmation** before proceeding to Phase 3.
