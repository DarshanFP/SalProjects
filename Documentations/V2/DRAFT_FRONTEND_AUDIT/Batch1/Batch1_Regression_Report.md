# Batch 1 Regression Report

**Scope:** Verify that removal of business `required` attributes from Edit General Info partial did not break workflow, submit, approve/revert modals, JS calculations, or authorization.  
**Type:** Analysis only. No code was modified.  
**Date:** 2026-02-10

---

## 1. Submit Flow Status

| Check | Status | Notes |
|-------|--------|--------|
| DRAFT project can still be submitted | **OK** | Submit is triggered from `resources/views/projects/partials/actions.blade.php` (unchanged in Batch 1). Button shown when `in_array($status, $editableStatuses)` and role executor/applicant. `ProjectStatus::getEditableStatuses()` includes `DRAFT`. Submit route: `POST projects.submitToProvincial`. |
| Status changes to SUBMITTED_TO_PROVINCIAL | **OK** | `ProjectController@submitToProvincial` → `ProjectStatusService::submitToProvincial()` sets `$project->status = ProjectStatus::SUBMITTED_TO_PROVINCIAL` and saves. No Batch 1 change. |
| Editing gets locked after submit | **OK** | Edit link/button is gated by `ProjectPermissionHelper::canEdit($project, $user)`, which requires `ProjectStatus::isEditable($project->status)`. `SUBMITTED_TO_PROVINCIAL` is not in `getEditableStatuses()`. So after submit, edit is correctly disabled. No Batch 1 change. |

**Verdict:** Submit flow is unchanged and intact. Batch 1 did not touch actions, routes, or status logic.

---

## 2. Approve/Revert Modal Status

**File:** `resources/views/projects/partials/actions.blade.php` — **not modified** in Batch 1.

| Field | Required attribute | Location |
|-------|--------------------|----------|
| commencement_month | **Present** | Line 92: `<select ... class="form-control" required>`. |
| commencement_year | **Present** | Line 109: `<select ... class="form-control" required>`. |
| revert_reason | **Present** | Line 154: `<textarea ... required>{{ old('revert_reason', '') }}</textarea>`. |

**Verdict:** No accidental removal. Approve and Revert modals still enforce required on commencement_month, commencement_year, and revert_reason.

---

## 3. JS Calculation Stability

**Relevant files:** `partials/scripts-edit.blade.php`, `partials/scripts.blade.php`, `partials/Edit/budget.blade.php`.

| Concern | Finding |
|---------|---------|
| overall_project_budget in JS | Edit general_info still has `<input ... id="overall_project_budget" ...>`. Batch 1 only removed `required`; `id`, `name`, and `value` bindings unchanged. So `document.getElementById('overall_project_budget')` still resolves. |
| Empty value handling | `parseFloat(overallBudgetField.value) \|\| 0` (scripts-edit ~1094, scripts ~189). Empty string or NaN becomes 0. No division by zero without a guard: logic uses `overallBudget > 0` before ratio. Safe. |
| amount_forwarded | `parseFloat(amountForwardedField?.value) \|\| 0`. Optional chaining and \|\| 0 prevent errors when field missing or empty. |
| amount_sanctioned / opening_balance | Derived from overall budget and combined forwarded+local; use same \|\| 0 pattern. |
| Missing element | `if (!overallBudgetField) return;` — early return if element absent; no throw. |

**Verdict:** JS calculations treat empty input as 0 and guard against missing elements. No regression from Batch 1 (only `required` was removed; IDs and structure unchanged).

---

## 4. Authorization Stability

| Check | Finding |
|-------|--------|
| canEdit | `ProjectPermissionHelper::canEdit($project, $user)` uses `ProjectStatus::isEditable($project->status)` and `isOwnerOrInCharge($project, $user)`. No Batch 1 change. Still used by UpdateProjectRequest::authorize() and edit view. |
| canSubmit | `ProjectPermissionHelper::canSubmit($project, $user)` uses `ProjectStatus::isSubmittable($project->status)`, role executor/applicant, and ownership. No Batch 1 change. Still used by SubmitProjectRequest and actions partial. |

**Verdict:** Authorization logic and its use in requests/views are unchanged. Batch 1 did not modify helpers or request authorization.

---

## 5. Runtime Error Scan

| Area | Risk | Result |
|------|------|--------|
| Blade – undefined variables | Edit general_info still uses `$project->project_title`, `$project->project_type`, `$project->in_charge`, `$project->overall_project_budget` in `value="{{ ... }}"` and option selected logic. No variables removed. | **OK** |
| Blade – missing attributes | Only the `required` attribute was removed. `name`, `id`, `class`, `value`, `readonly`, and other attributes are unchanged. No structural or conditional change that would hide entire blocks. | **OK** |
| Blade – layout break | No tags or wrappers removed. Form structure and card layout unchanged. | **OK** |
| Duplicate IDs | Edit general_info has three blocks with duplicate `id="project_title"`, `id="overall_project_budget"`, etc. This predates Batch 1. JS that uses getElementById may target the first match; behavior unchanged. | **No new issue** |

**Verdict:** No new runtime or Blade errors introduced by Batch 1. Optional: address duplicate IDs in general_info in a separate refactor.

---

## 6. Overall Regression Verdict

**SAFE**

- **Submit flow:** Unchanged; DRAFT can be submitted; status and edit lock work as before.
- **Approve/Revert modals:** Unchanged; required on commencement_month, commencement_year, and revert_reason is still in place.
- **JS calculations:** Empty values handled as 0; element presence checked; no change to IDs or structure.
- **Authorization:** canEdit and canSubmit unchanged and still used correctly.
- **Blade:** Only `required` removed; no missing attributes or variables that would cause layout or runtime errors.

Batch 1 was limited to removing the `required` attribute from business fields in a single partial. No other views, scripts, or backend logic were modified. No regressions identified in the areas checked.

---

**End of report. No code was modified.**
