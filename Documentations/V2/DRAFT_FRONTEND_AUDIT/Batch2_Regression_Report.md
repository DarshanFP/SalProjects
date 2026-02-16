# Batch 2 Regression Report

**Scope:** Verify Batch 2 changes did not break workflow, submit logic, action modals, JS, authorization, or DB integrity.  
**Type:** Code-trace analysis only. No code was modified.  
**Date:** 2026-02-10

**Batch 2 changes (recap):** Removed `required` from project_type, project_title, society_name in `resources/views/projects/partials/general_info.blade.php`; removed JS in `createProjects.blade.php` that stripped `required` on draft click and bypassed `checkValidity()` for draft submit. No changes to Edit form, actions, controllers, FormRequests (at that time), or services. Subsequent Batch 3 (GIC store uses validated(); StoreProjectRequest project_type always required) is in place; this report confirms no regression from Batch 2 and current flow stability.

---

## 1. Create Flow Status

| Check | Result |
|-------|--------|
| **Create draft** | Create form has no HTML5 `required` on general info (Batch 2). User must select project_type (StoreProjectRequest requires it post–Batch 3). Form submits when "Save Project" (draft) is clicked; backend accepts when project_type is provided; GIC uses validated() and defaults. |
| **Navigate to View** | View (show) page and route unchanged. After create, redirect is to edit or index per existing logic; viewing a project uses `projects.show` and ProjectController::show. No Batch 2 impact. |
| **Create flow path** | Store uses StoreProjectRequest and GeneralInfoController::store (validated() + defaults); status set to DRAFT in GIC and applyPostCommitStatusAndRedirect. Unchanged by Batch 2. |

**Verdict:** Create flow status and redirect behavior are unchanged by Batch 2. No regression introduced.

---

## 2. Submit Flow Status

| Check | Result |
|-------|--------|
| **Submit entry point** | Submit uses route `projects.submitToProvincial` → `ProjectController::submitToProvincial(SubmitProjectRequest $request, $project_id)`. SubmitProjectRequest and ProjectStatusService were not modified in Batch 2. |
| **Status change** | `ProjectStatusService::submitToProvincial()` sets `$project->status = ProjectStatus::SUBMITTED_TO_PROVINCIAL` and saves. Unchanged. |
| **Editing lock** | `ProjectPermissionHelper::canEdit()` uses `ProjectStatus::isEditable($project->status)`. After submit, status is SUBMITTED_TO_PROVINCIAL, which is not in getEditableStatuses(); canEdit returns false and edit is locked. Logic unchanged. |
| **Validation on submit** | SubmitProjectRequest has empty `rules()`; authorization uses `ProjectPermissionHelper::canSubmit()`. No validation errors from SubmitProjectRequest; Batch 2 did not touch it. |

**Verdict:** Submit flow, status transition, and editing lock after submit are unchanged. No regression.

---

## 3. Action Modal Stability

| Check | Result |
|-------|--------|
| **File in scope** | Batch 2 did **not** modify `resources/views/projects/partials/actions.blade.php`. |
| **Approval modal** | Approve modal includes `<select name="commencement_month" ... required>` (lines 90–92). `commencement_month` remains **required**. Commencement year select also has `required` (line 109). |
| **Revert modal** | Revert modal includes `<textarea name="revert_reason" ... required>` (lines 150–154). `revert_reason` remains **required**. |
| **Other modal fields** | No removal of required attributes from action modals. |

**Verdict:** Approve and Revert modals still enforce required fields (commencement_month, commencement_year, revert_reason). No regression.

---

## 4. JS Stability

| Check | Result |
|-------|--------|
| **Batch 2 JS changes** | Only the inline script in `createProjects.blade.php` was changed: removal of the block that did `querySelectorAll('[required]')` and `removeAttribute('required')`, and removal of the draft-only `if (isDraftSave) return true` bypass. Rest of the script (toggleSections, predecessorDataFetched, enabling disabled fields, showing hidden sections, loading state, single `checkValidity()` on submit) kept. |
| **Budget-related JS** | Create page includes `@include('projects.partials.scripts')`. Edit page uses `scripts-edit.blade.php` and `budget-calculations.js`. None of these were modified in Batch 2. Budget logic (parseFloat, row totals, overall_project_budget update, isNaN checks) lives in those partials/assets; no change. |
| **general_info.blade.php script** | The predecessor fetch and field population in `general_info.blade.php` (including `overall_project_budget`) were not changed. Only the three `required` attributes were removed from the HTML. No NaN or calculation logic in that partial. |
| **Console / NaN** | No removal of any guards or calculations. Scripts use `parseFloat(...) \|\| 0` patterns; no new console errors or NaN introduced by Batch 2. |

**Verdict:** Budget and general JS behavior unchanged. No regression; no new console or NaN risk from Batch 2.

---

## 5. Authorization Stability

| Check | Result |
|-------|--------|
| **canEdit** | Used in UpdateProjectRequest::authorize() and ProjectController::edit(). Batch 2 did not modify ProjectPermissionHelper, UpdateProjectRequest::authorize(), or edit flow. |
| **canSubmit** | Used in SubmitProjectRequest::authorize() and ProjectStatusService::submitToProvincial(). Batch 2 did not modify ProjectPermissionHelper, SubmitProjectRequest, or ProjectStatusService. |
| **Permission leakage** | No change to role checks, isOwnerOrInCharge, or status checks in ProjectPermissionHelper. Edit and submit remain gated by the same helpers. |

**Verdict:** canEdit and canSubmit behavior and permission gating unchanged. No regression.

---

## 6. DB Integrity

| Check | Result |
|-------|--------|
| **NOT NULL on create** | Batch 2 was view/JS only. GeneralInfoController::store (post–Batch 3) uses validated() and sets defaults for in_charge and overall_project_budget; project_type is required in StoreProjectRequest, so NOT NULL columns are satisfied when create runs. No Batch 2 change to backend. |
| **Partial writes** | No change to transaction boundaries or to which fields are written in store/update. |
| **Transaction failures** | No change to DB::beginTransaction/commit/rollBack in ProjectController::store or update. |

**Verdict:** No new NOT NULL risk or transaction changes from Batch 2. DB integrity logic unchanged.

---

## 7. Overall Verdict

**SAFE**

- **Create flow:** Status and redirect logic unchanged; Batch 2 only relaxed HTML5 and removed draft-only JS hack on the create form.
- **Submit flow:** Status change, editing lock, and submit validation/authorization unchanged.
- **Action modals:** actions.blade.php untouched; commencement_month and revert_reason still required.
- **JS:** Budget and general scripts unchanged; only draft-specific stripping and bypass removed in createProjects.
- **Authorization:** canEdit and canSubmit unchanged; no permission leakage.
- **DB:** No backend or transaction changes from Batch 2; no new NOT NULL or partial-write risk.

No regression identified. Batch 2 is limited to Create form HTML and Create-page draft JS; workflow, submit logic, modals, shared JS, authorization, and DB behavior are unchanged.

---

**End of report. No code was modified.**
