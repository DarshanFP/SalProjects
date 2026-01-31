# Phase 3 – Hardening & Closure

**Document type:** Release hardening, production safety, closure record  
**Date:** 2026-01-29  
**Role:** Principal Software Engineer, Release Hardening Specialist, Production Safety Owner  
**Source:** PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md, PHASE_3_COMPLETION_SUMMARY.md

---

## 1. Defensive Gap 1: store() vs update() symmetry – CLOSED

### Audit result

All budget-related controllers were audited. One asymmetry was found and fixed:

| Controller                              | store()                        | update()                | Action                      |
| --------------------------------------- | ------------------------------ | ----------------------- | --------------------------- |
| GeneralInfoController                   | N/A (creates new project only) | Guarded                 | No change                   |
| Projects\BudgetController (Development) | Guarded                        | Guarded                 | No change                   |
| IIESExpensesController                  | Guarded                        | Calls store() → guarded | No change                   |
| IESExpensesController                   | Guarded                        | Calls store() → guarded | No change                   |
| **ILP\BudgetController**                | Guarded                        | **Was not guarded**     | **Guard added to update()** |
| IAHBudgetDetailsController              | Guarded                        | Guarded                 | No change                   |
| IGEBudgetController                     | Guarded                        | Calls store() → guarded | No change                   |

### Guard added

- **ILP\BudgetController::update()** – At start of method: load project, call `BudgetSyncGuard::canEditBudget($project)`; if false, log via `BudgetAuditLogger::logBlockedEditAttempt(..., 'ilp_budget_update', ...)` and return `response()->json(['error' => BUDGET_LOCKED_MESSAGE], 403)`.

**Defensive rule:** Any endpoint capable of mutating budget data MUST call `BudgetSyncGuard::canEditBudget()` before performing the mutation. Both store() and update() are now guarded for all budget mutation endpoints.

---

## 2. Defensive Gap 2: Non-UI / future entry points – CONFIRMED

### Audit result

- **Console commands** – No Artisan commands in `app/Console/Commands` mutate project budget fields or type-specific budget tables. Commands present: TestApplicantAccess, TruncateReports, TruncateTestData, VerifyDataCounts. None write to `projects.overall_project_budget`, `amount_forwarded`, `local_contribution`, or type-specific budget models.
- **Background jobs** – No `app/Jobs` directory; no background jobs that mutate budget.
- **Imports** – No import scripts or batch updaters found that write budget data.
- **Helper utilities** – Budget mutations occur only in HTTP controllers listed in PHASE_3_COMPLETION_SUMMARY; all now call `BudgetSyncGuard::canEditBudget()` (or delegate to a guarded store()).
- **BudgetSyncService** – Writes to `projects` only when called from:
    - Type budget controllers (after save) – guarded by `BudgetSyncGuard::canSyncOnTypeSave()` (which returns false when project is approved).
    - Coordinator/General approval flow – `syncBeforeApproval()` only when `canSyncBeforeApproval()` (forwarded_to_coordinator); approval then overwrites sanctioned/opening.
    - No other callers. Service does not bypass Phase 3; it is used only in contexts already guarded.

### Documentation for future developers

- **Code:** `BudgetSyncGuard` class docblock updated to state that any code path that mutates budget data (including future console commands, jobs, or imports) MUST call `canEditBudget($project)` before performing the mutation, and that there are currently no such non-UI paths.
- **Rule:** All budget mutations must pass through `BudgetSyncGuard::canEditBudget()`. Adding new entry points (e.g. data import, scheduled job) requires calling this guard before writing; otherwise post-approval enforcement can be bypassed.

**Conclusion:** No bypass paths exist. Future regressions are prevented by the documented rule and central guard.

---

## 3. Flag enablement preparation

### Verification

- **Flag:** `BUDGET_RESTRICT_GENERAL_INFO_AFTER_APPROVAL`
- **Config key:** `config('budget.restrict_general_info_after_approval')`
- **Default:** `false` (confirmed in `config/budget.php`)
- **Gating:** All Phase 3 enforcement logic is gated by this flag:
    - `BudgetSyncGuard::canEditBudget()` returns `true` when the config is false (no restriction).
    - Controllers that check `canEditBudget()` therefore block only when the flag is true and the project is approved.

### Documentation added

- **Config:** In `config/budget.php`, a comment was added above `restrict_general_info_after_approval`:  
  _"Phase 3: When true, blocks post-approval edits to budget fields (General Info + type-specific). Default = false. Enable in STAGING first; monitor budget log for 'Budget edit blocked' entries; then enable in PRODUCTION. Do not auto-enable."_

### Enablement procedure (for release owner)

1. **STAGING first:** Set `BUDGET_RESTRICT_GENERAL_INFO_AFTER_APPROVAL=true` in STAGING only.
2. **Monitor:** Watch `storage/logs/budget-*.log` (or the configured budget channel) for "Budget edit blocked" entries. Confirm that blocked attempts are logged with project_id, user_id, attempted_action, status, timestamp.
3. **Verify:** Run the Phase 3 verification checklist (approved project store/update blocked; reverted project store/update allowed; Phase 2 sync unchanged).
4. **PRODUCTION:** After sign-off, set `BUDGET_RESTRICT_GENERAL_INFO_AFTER_APPROVAL=true` in PRODUCTION. Do not auto-enable; enable deliberately after staging validation.

---

## 4. Summary

| Item                         | Status                                                                                                                |
| ---------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| store() vs update() symmetry | ILP `update()` guard added; all budget mutation endpoints guarded on both store and update (or update→store).         |
| Non-UI / future entry points | No console, job, or import paths mutate budget. Documented in code and here; future paths must use `canEditBudget()`. |
| Flag default                 | false; no change.                                                                                                     |
| Flag gating                  | All enforcement gated by `restrict_general_info_after_approval`.                                                      |
| Enablement documentation     | Comment in config; procedure in this document (STAGING first, monitor logs, then PRODUCTION).                         |

---

**Phase 3 enforcement is finalized and ready to be enabled.**

Enable the flag deliberately in STAGING first, verify behaviour and logs, then enable in PRODUCTION. Do not proceed to Phase 4 or Phase 6 until Phase 3 is confirmed closed.
