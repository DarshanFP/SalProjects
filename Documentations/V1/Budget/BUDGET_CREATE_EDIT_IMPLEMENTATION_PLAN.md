# Budget Create/Edit – Implementation Plan (UI Layer Only)

**Role:** Senior Release Architect, Documentation Integrator  
**Date:** 2026-01-30  
**Scope:** Fixes derived from `Documentations/V1/Budget/BUDGET_SECTION_CREATE_EDIT_ISSUES.md`, aligned with the existing system-wide budget architecture under `Documentations/V1/Basic Info fund Mapping Issue/` (FINAL_COMPREHENSIVE_COMPLETION_SUMMARY.md, PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md).

---

## 1. Executive Summary

The system-wide budget architecture is **unchanged**: canonical fund fields live on `projects`; `ProjectFundFieldsResolver` and `BudgetSyncService` resolve and sync at defined lifecycle points; approval, reporting, and dashboards read only from `projects`. This plan addresses **only** the Create/Edit UI and form-calculation layer so that the values **entering** that pipeline (submitted to GeneralInfoController and BudgetController) are correct and the in-form display of derived fields (Amount Sanctioned, Opening Balance) is consistent. One **required** calculation bug (Create form ignoring Local Contribution in JS), two **required** Blade default-value fixes (duplicate `value` attributes), and one **optional** guardrail (minimum one budget row) are defined; all changes are confined to views and their inline/deferred scripts, with explicit “DO NOT TOUCH” boundaries for resolver, sync, approval, and reporting.

---

## 2. Issue Classification Table

| # | Issue (from BUDGET_SECTION_CREATE_EDIT_ISSUES.md) | Classification | Must fix before relying on resolver/sync? | Notes |
|---|--------------------------------------------------|----------------|-------------------------------------------|--------|
| 1 | Create: `localContributionField` undefined in `calculateBudgetFields()` | **Calculation bug** | **YES** | Display of Amount Sanctioned and Opening Balance is wrong when user enters Local Contribution; user trust and any client-side logic depend on correct derived values. Form still submits `local_contribution`; sync uses it. Fix ensures in-form display matches formula. |
| 2 | Create partial: duplicate `value` on rate_multiplier / rate_duration | **UI correctness** | **YES** | Default 1 can be lost; blank inputs yield wrong this_phase and thus wrong overall_project_budget entering the pipeline. |
| 3 | Edit partial: duplicate `value` in empty-state row | **UI correctness** | **YES** | Same as #2 for edit when project has no budget rows. |
| 4 | Amount Sanctioned / Opening Balance not submitted (display-only) | **Info** | No | By design; resolver/sync set them. No code change. |
| 5 | No minimum row check; user can remove all budget rows | **Policy / UX guardrail** | **Optional** | Business rule: allow zero rows vs require at least one. Optional: disable Remove when one row, and/or server-side validation. |
| 6 | Create: no DOMContentLoaded listener for Local Contribution | **Info / Optional** | No | After fixing #1, `oninput="calculateBudgetFields()"` on the field is sufficient. Adding listener is optional consistency. |
| 7 | Column label “Costs” vs `rate_quantity` | **Info / Policy** | No | Naming/UX clarification; not a code bug. |
| 8 | Initial calculation timing on create | **Info** | No | Low risk in current DOM order; no change unless layout changes. |

---

## 3. Implementation Plan (Step-by-Step)

### Principle

- **Scope:** Only `resources/views/projects/partials/` (budget.blade.php, Edit/budget.blade.php, scripts.blade.php, scripts-edit.blade.php). No controller, service, or route changes for the required fixes.
- **Intent:** Correct the values and display that feed or reflect the canonical pipeline; do not duplicate or replace resolver/sync logic.
- **Non-goals:** No new sources of truth; no changes to approval, reporting, or dashboard code; no changes to ProjectFundFieldsResolver, BudgetSyncService, or BudgetSyncGuard.

---

### Step 1 – Fix Create form calculation (REQUIRED)

**File:** `resources/views/projects/partials/scripts.blade.php`  
**Function:** `calculateBudgetFields()`

**Change:** Declare the Local Contribution field so the formula uses it.

- **Current (bug):** `localContributionField` is used but never defined; `localContribution` is effectively 0 on create.
- **Fix:** Immediately after declaring `openingBalanceField`, add:
  ```js
  const localContributionField = document.getElementById('local_contribution');
  ```
- **Why REQUIRED:** Without this, Amount Sanctioned and Opening Balance previews are wrong whenever the user enters Local Contribution on create. Correct display is required before relying on resolver/sync so the user and any client-side logic see the same formula the backend uses.
- **Verification:** On create, set Overall Project Budget (e.g. from one budget row), set Local Contribution to a non-zero value; confirm Amount Sanctioned = Overall − (Amount Forwarded + Local Contribution) and Opening Balance = Amount Sanctioned + (Amount Forwarded + Local Contribution). Repeat with Amount Forwarded non-zero.

**Regression:** Edit form uses `scripts-edit.blade.php`, which already defines `localContributionField`; no change there. Other project types (IIES, IES, ILP, IAH, IGE) use type-specific budget partials and scripts; this file is only used for the default/create Development budget partial.

---

### Step 2 – Fix duplicate `value` in Create budget partial (REQUIRED)

**File:** `resources/views/projects/partials/budget.blade.php`  
**Location:** First data row – inputs for `rate_multiplier` and `rate_duration`.

**Change:** Use a single `value` attribute with Blade default.

- **Current:** Two `value` attributes (e.g. `value="1"` and `value="{{ old('phases.0.budget.0.rate_multiplier', 1) }}"`); the second overwrites the first; when `old()` is empty, field can be blank.
- **Fix:** One attribute per input, e.g.:
  - `value="{{ old('phases.0.budget.0.rate_multiplier', 1) }}"`
  - `value="{{ old('phases.0.budget.0.rate_duration', 1) }}"`
- **Why REQUIRED:** Ensures default 1 so this_phase = rate_quantity × rate_multiplier × rate_duration is correct and overall_project_budget (sum of this_phase) is correct when entering the pipeline.
- **Verification:** Load create form; confirm the first budget row shows 1 in Rate Multiplier and Rate Duration; add a row, confirm new row defaults to 1; submit and confirm persisted values.

**Regression:** Only the default Development budget create partial; type-specific partials (ILP, IGE, etc.) are separate.

---

### Step 3 – Fix duplicate `value` in Edit budget partial empty state (REQUIRED)

**File:** `resources/views/projects/partials/Edit/budget.blade.php`  
**Location:** Single empty row when `$project->budgets` is empty (else branch).

**Change:** Same as Step 2: single `value` with Blade default for `rate_multiplier` and `rate_duration`.

- **Fix:** e.g. `value="{{ old('phases.0.budget.0.rate_multiplier', 1) }}"` and `value="{{ old('phases.0.budget.0.rate_duration', 1) }}"`
- **Why REQUIRED:** Same integrity as Step 2 for the edit flow when there are no existing budget rows.
- **Verification:** Edit a Development project that has no budget rows (or delete all and save); reload edit; confirm the single row shows 1 in Rate Multiplier and Rate Duration.

**Regression:** Only the default Development budget edit partial; conditional on project type in edit.blade.php.

---

### Step 4 – Optional: Minimum one budget row (OPTIONAL)

**Files:** `resources/views/projects/partials/scripts.blade.php`, `resources/views/projects/partials/scripts-edit.blade.php`  
**Function:** `removeBudgetRow(button)` and/or UI.

**Options (choose one or none):**

- **A. UI guardrail:** In `removeBudgetRow`, if `tableBody.querySelectorAll('tr').length <= 1`, return without removing (disable Remove when only one row).
- **B. Server-side:** In BudgetController (store/update) or request validation, require at least one budget row for Development projects; return validation error otherwise.

**Why OPTIONAL:** Policy decision: business may allow zero rows (e.g. draft) or require at least one. If required, prefer server-side validation (B) plus optional UI (A).

**Verification:** If A: ensure Remove is disabled when one row; if B: submit zero rows and confirm validation error. Ensure type-specific budget flows (ILP, IGE, etc.) are not broken.

**Regression:** Only Development budget table; type-specific add/remove logic is separate.

---

## 4. Verification Checklist (per fix)

| Step | Check | Pass criteria |
|------|--------|----------------|
| 1 | Create: set Overall Budget (e.g. 10000), Amount Forwarded 0, Local Contribution 2000 | Amount Sanctioned = 8000, Opening Balance = 10000 |
| 1 | Create: set Overall 10000, Amount Forwarded 1000, Local Contribution 1000 | Amount Sanctioned = 8000, Opening Balance = 10000 |
| 1 | Create: change Local Contribution after Amount Forwarded | Both previews update immediately and match formula |
| 1 | Edit: same scenarios | Already correct; no regression |
| 2 | Create: load form, first row | Rate Multiplier and Rate Duration show 1 |
| 2 | Create: add row, new row | New row shows 1, 1; this_phase and total recalc correctly |
| 2 | Create: submit with one row (qty 100, mult 1, dur 1) | overall_project_budget 100; stored budget row correct |
| 3 | Edit: project with zero budget rows | Single row shows 1, 1 |
| 3 | Edit: submit that row | Budget row created with rate_multiplier 1, rate_duration 1 |
| All | Edit: Development project with existing budget rows | Existing rows and totals unchanged; lock state (if approved) still works |
| All | Create/Edit: Amount Sanctioned and Opening Balance | Remain display-only (readonly); not submitted; resolver/sync unchanged |

---

## 5. Explicit Non-Goals (DO NOT TOUCH)

The following are **out of scope** for this implementation plan. No changes, no new logic, no duplication.

| Area | Do not touch | Reason |
|------|----------------|--------|
| **Resolver** | `ProjectFundFieldsResolver`, `app/Services/Budget/ProjectFundFieldsResolver.php` | Canonical resolution per project type; already implemented and validated. |
| **Sync** | `BudgetSyncService`, `BudgetSyncGuard`, `BudgetAuditLogger` | Sync at type save and pre-approval; guards and logging are defined. |
| **Approval** | CoordinatorController / GeneralController approval flow, computation and write of amount_sanctioned and opening_balance | Approval is the authority for sanctioned/opening; form does not submit them. |
| **Reporting** | ReportController, report create/edit, amount_sanctioned_overview, statements of account | Reports read from `projects`; no change to report storage or display. |
| **Dashboards** | ProvincialController, CoordinatorController, ExecutorController, GeneralController – budget aggregates | Use canonical project fields only; no recalculation from forms. |
| **Admin reconciliation** | AdminCorrectionService, BudgetReconciliationController, budget_correction_audit | Phase 6a; separate governance flow. |
| **Type-specific budget** | IIES, IES, ILP, IAH, IGE budget controllers and partials (except shared scripts if used) | Different sources per type; resolver maps them; no change to type-specific save or display. |
| **GeneralInfoController / BudgetController** | Validation rules, which fields are accepted from request, when sync is invoked | Form continues to submit overall_project_budget, amount_forwarded, local_contribution; controllers and sync insertion points stay as in Phase 2/3. |
| **Config / flags** | `config/budget.php`, feature flags | Enablement and rollout are per FINAL_COMPREHENSIVE_COMPLETION_SUMMARY. |
| **New sources of truth** | No new tables, no new “budget overview” services, no JS persistence of sanctioned/opening | Single source remains `projects`; resolver and sync populate it. |

---

## 6. Regression Risks and How to Test

| Risk | Mitigation | Test |
|------|------------|------|
| Edit form behaviour changes | Only scripts.blade.php (create) is changed for Issue 1; scripts-edit.blade.php unchanged | Run all Edit verification steps; confirm lock state and existing rows. |
| Type-specific projects (ILP, IGE, etc.) break | Budget partials and scripts are per view; create/edit for Development use the partials in scope | Create/edit Development only; open one ILP/IGE edit and confirm no JS errors and budget section loads. |
| Submitted request shape changes | No change to input names or to controller validation | Submit create and edit with budget data; confirm request has same keys and values for overall_project_budget, amount_forwarded, local_contribution, phases[0][budget][*]. |
| Resolver or sync sees wrong data | Fixes only affect client-side display and default values; form already sends local_contribution | After fix, create project with local_contribution set; confirm DB projects.local_contribution and that sync (if enabled) does not overwrite with wrong value. |

---

## 7. References

- **Issues source:** `Documentations/V1/Budget/BUDGET_SECTION_CREATE_EDIT_ISSUES.md`
- **Architecture and completion:** `Documentations/V1/Basic Info fund Mapping Issue/FINAL_COMPREHENSIVE_COMPLETION_SUMMARY.md`
- **Phase plan and boundaries:** `Documentations/V1/Basic Info fund Mapping Issue/PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md`
- **System-wide audit:** `Documentations/V1/Basic Info fund Mapping Issue/Budget_System_Wide_Audit.md`

---

**Document version:** 1.0  
**Status:** Implementation plan only; success = minimal, non-breaking fixes that fit inside the existing budget phases and do not alter resolver, sync, approval, or reporting.
