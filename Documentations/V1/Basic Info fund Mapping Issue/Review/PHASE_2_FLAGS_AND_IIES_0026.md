# Phase 2 Budget Sync – Flags Enabled & IIES-0026 Fix

**Date:** 2026-01-29  
**Context:** After enabling Phase 2 budget sync flags so approval and type-save use resolver; and how to fix IIES-0026 (approved with Rs. 0.00).

---

## 1. Inferred Invariant

Because **no project can be edited once approved**, “editable” implies “not approved.” So the requirement:

> _If a project is editable, the project's budget fields must reflect the latest type-specific budget data_

is satisfied by:

- **Sync on type save** (when project is not approved) – after IIES/IES/ILP/IAH/IGE budget save.
- **Sync before approval** (when status is `forwarded_to_coordinator`) – before coordinator/general reads overall/forwarded/local.

Enabling Phase 2 flags makes that invariant hold in practice.

---

## 2. What Was Done

Phase 2 budget sync was enabled in `.env`:

```env
BUDGET_RESOLVER_ENABLED=true
BUDGET_SYNC_ON_TYPE_SAVE=true
BUDGET_SYNC_BEFORE_APPROVAL=true
```

With these set:

- **On IIES (and other type-specific) save:** `syncFromTypeSave` runs and updates `projects.overall_project_budget`, `local_contribution`, `amount_forwarded`.
- **Before coordinator/general approval:** `syncBeforeApproval` runs, refreshes `projects` from type-specific data, and approval reads and saves correct Overall / Local / Sanctioned / Opening.

---

## 3. What You Need To Do

### 3.1 Reload config

So Laravel picks up the new `.env` values:

```bash
php artisan config:clear
```

### 3.2 Fix IIES-0026 (already approved with 0/0/0)

Choose one:

- **Option A – Admin reconciliation (Phase 6a)**  
  If `BUDGET_ADMIN_RECONCILIATION_ENABLED=true`: go to **Admin → Budget Reconciliation**, find IIES-0026, compare stored vs resolved, then **Accept suggested** so `projects` gets the correct values (e.g. 21109 overall, 9109 local, 12000 sanctioned).

- **Option B – Revert and re-approve**  
  **Revert** the project (provincial/coordinator), then **re-forward** and **re-approve**. With the new flags, `syncBeforeApproval` runs before approval and the coordinator will see and save the correct budget.

---

## 4. Why IIES-0026 Showed Rs. 0.00

From the log:

- IIES expenses were saved: **21,109** total, **12,000** balance requested, **9,109** local (1109 + 5000 + 3000).
- Coordinator approval ran **after** that but read from `projects`:
    - `overall_project_budget`: 0.00
    - `local_contribution`: 0.00
    - So it saved `amount_sanctioned`: 0.0, `opening_balance`: 0.0.

`CoordinatorController` **does** call `syncBeforeApproval($project)` before reading budget, but the guard only allows sync when `BUDGET_RESOLVER_ENABLED` and `BUDGET_SYNC_BEFORE_APPROVAL` are true. With no `BUDGET_*` in `.env`, both defaulted to false, so no sync ran and approval used 0/0/0.

---

## 5. Verify After Enabling Flags

1. **New or editable IIES project**  
   Create a new IIES (or edit/save expenses on a non-approved one), then check `projects` for that project: `overall_project_budget`, `local_contribution` (and after approval, `amount_sanctioned`, `opening_balance`) should match type-specific data.

2. **Full flow**  
   Submit → forward to coordinator → approve. The approval success screen should show non-zero Overall / Local / Sanctioned / Opening.

3. **Logs**  
   In `storage/logs/budget-YYYY-MM-DD.log` you should see “Budget sync applied” (and/or resolver) entries when sync runs.

---

## 6. IIES Personal Info Error (Separate)

The same create flow logged:

`Column 'iies_bname' cannot be null` when storing IIES Personal Info.

So the project was created with IIES expenses and the rest, but **Personal Info** failed because `iies_bname` (beneficiary name) was null. That is separate from budget sync: either the create form does not send beneficiary name, or validation allows it but the DB does not. Fix is: require and send `iies_bname` in the IIES create/store flow (form + validation + store).

---

## 7. References

- **Config:** `config/budget.php` – `resolver_enabled`, `sync_to_projects_on_type_save`, `sync_to_projects_before_approval`
- **Guard:** `app/Services/Budget/BudgetSyncGuard.php` – `canSyncBeforeApproval()`, `canSyncOnTypeSave()`
- **Sync:** `app/Services/Budget/BudgetSyncService.php` – `syncBeforeApproval()`, `syncFromTypeSave()`
- **Approval:** `app/Http/Controllers/CoordinatorController.php` – `approveProject()` (sync call before budget read)
- **Review:** `EDITABLE_PROJECT_BUDGET_SYNC_REVIEW.md` (inferred invariant added)
