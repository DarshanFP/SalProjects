# Required Attribute Master List

**Scope:** `resources/views/projects/` (recursive)  
**Purpose:** Identify every HTML `required` attribute before safe removal for draft partial-data support.  
**Date:** 2026-02-10  
**No files were modified; discovery only.**

---

## Summary

| Metric | Value |
|--------|--------|
| **Total blade files scanned** | 304 (all under `resources/views/projects/`) |
| **Total required occurrences (form controls)** | **28** (each row in tables below) |
| **Unique field names** | project_type, project_title, society_name, in_charge, overall_project_period, current_phase, overall_project_budget, goal, comment, commencement_month, commencement_year, revert_reason |
| **Files affected** | **8** blade files contain at least one `required` on an input/select/textarea |

**Note:** Help text such as "Minimum 100 words required" and JS that references "required" (e.g. `querySelectorAll('[required]')`, "required inputs not found") are not form-control required attributes and are excluded from the occurrence count. `step="0.01"` and similar are validation hints, not required-ness.

---

## 1. Structural Required Fields (Keep)

Fields that are structural for routing, workflow, or assignment. Recommend **keeping** `required` for non-draft flows or for action-specific forms (approve/revert).

| File | Line | Field | Type | Reason |
|------|------|--------|------|--------|
| partials/actions.blade.php | 92 | commencement_month | select | Approve Project modal: coordinator must set commencement date to approve. Not project edit form. |
| partials/actions.blade.php | 109 | commencement_year | select | Same modal; required for approval action. |
| partials/actions.blade.php | 154 | revert_reason | textarea | Revert to Provincial modal: reason required for revert action. |

**Classification:** These live in **modals** (Approve Project, Revert to Provincial), not in the main project create/edit form. They gate specific actions (approve, revert). Keeping them required is appropriate for those actions.

**project_type and in_charge:** Treated in §2 below as business/structural borderline; backend already allows nullable project_type when `save_as_draft`. For **draft** partial-data, project_type and in_charge are candidates to not be HTML-required so that "Save as draft" can submit without them; for **submit** they remain enforced by backend/authorization.

---

## 2. Business Required Fields (Candidate for Removal)

These are content fields. Removing HTML `required` (and relying on backend conditional validation when `save_as_draft`) allows draft to save with partial data.

| File | Line | Field | Type | Project Type | Reason |
|------|------|--------|------|--------------|--------|
| partials/general_info.blade.php | 5 | project_type | select | All (create) | Business: project type selection. Backend allows nullable when draft (StoreProjectRequest). **Borderline structural** for routing; candidate for removal only when draft. |
| partials/general_info.blade.php | 45 | project_title | input text | All (create) | Business: title. |
| partials/general_info.blade.php | 49 | society_name | select | All (create) | Business: society/trust name. |
| partials/Edit/general_info.blade.php | 98 | project_title | input text | All (edit) | Business: title. |
| partials/Edit/general_info.blade.php | 104 | society_name | select | All (edit) | Business: society/trust name. |
| partials/Edit/general_info.blade.php | 227 | overall_project_period | select | All (edit) | Business: project period in years. |
| partials/Edit/general_info.blade.php | 294 | overall_project_budget | input number | All (edit) | Business: budget. Used in calculations (see §5). **High-risk** if removed: ensure backend/JS handle null/empty. |
| partials/Edit/general_info.blade.php | 435 | project_title | input text | All (edit) | Duplicate block in same file. |
| partials/Edit/general_info.blade.php | 441 | project_type | select | All (edit) | Duplicate block. Backend UpdateProjectRequest allows nullable when save_as_draft. |
| partials/Edit/general_info.blade.php | 452 | society_name | select | All (edit) | Duplicate block. |
| partials/Edit/general_info.blade.php | 463 | in_charge | select | All (edit) | **Structural/FK:** in-charge user. Backend keeps NOT NULL; candidate to remove required only for draft so form can submit. |
| partials/Edit/general_info.blade.php | 476 | overall_project_period | select | All (edit) | Duplicate block. |
| partials/Edit/general_info.blade.php | 485 | current_phase | select | All (edit) | Business: phase. |
| partials/Edit/general_info.blade.php | 530 | project_title | input text | All (edit) | Second duplicate block. |
| partials/Edit/general_info.blade.php | 535 | project_type | select | All (edit) | Second duplicate block. |
| partials/Edit/general_info.blade.php | 555 | society_name | select | All (edit) | Second duplicate block. |
| partials/Edit/general_info.blade.php | 573 | in_charge | select | All (edit) | Second duplicate block. |
| partials/Edit/general_info.blade.php | 600 | overall_project_period | input number | All (edit) | Second duplicate block. |
| partials/Edit/general_info.blade.php | 606 | current_phase | select | All (edit) | Second duplicate block. |
| partials/Edit/general_info.blade.php | 619 | overall_project_budget | input number | All (edit) | Second duplicate block; readonly. Used in calculations. |
| partials/Edit/key_information.blade.php | 183 | goal | textarea | All (edit) | Business: project goal. Backend allows nullable (KeyInformation; goal nullable in DB). |
| partials/NPD/key_information.blade.php | 8 | goal | textarea | NPD (Next Phase) | Business: goal for next-phase key info. |
| partials/ProjectComments.blade.php | 34 | comment | textarea | All | Business: comment text. Feature requires a comment to post; **review** whether empty comment should be allowed. |
| comments/edit.blade.php | 11 | comment | textarea | All | Same: edit comment form. |

**Note:** `partials/Edit/general_info.blade.php` contains **three** parallel "Basic Information" blocks (roughly from top, ~line 420, and ~line 521). Each block repeats the same set of required fields. Any removal of `required` should be applied consistently across all three blocks (or the duplicate blocks refactored later).

---

## 3. Conditional Required Fields (Needs Review)

No Blade conditionals were found that wrap **only** the `required` attribute (e.g. `@if(...) required @endif`). All required attributes in scope are unconditional on the element.

**Dynamic behavior:**

- **Edit form – in_charge:** The first in-charge block (around line 176) in `partials/Edit/general_info.blade.php` does **not** have `required` on the select; later duplicate blocks (463, 573) do. So required-ness is inconsistent within the same file (likely legacy/duplicate markup).
- **Create form:** `createProjects.blade.php` uses JS to remove `required` from all fields when "Save as draft" is clicked (lines 445–448). That hack is separate from this audit; removal of business required in Blade would reduce reliance on that hack.

---

## 4. Dynamic/JS-Based Validation

| Location | Behavior | Risk / Note |
|----------|----------|-------------|
| Oldprojects/createProjects.blade.php (445–448) | On "Save as draft" click: `querySelectorAll('[required]')` then `removeAttribute('required')` on each, then submit. | Draft-only; allows partial data on create. Redundant if Blade required are removed for draft. |
| partials/scripts-edit.blade.php (1081–1088) | `calculateAmountSanctioned()` gets elements by ID (e.g. `overall_project_budget`, `amount_forwarded`). Comment says "Get all required field elements" but code only uses presence for **calculations**, not for enforcing required. | No JS enforcement of required; safe. |
| partials/scripts.blade.php (175–183) | Same pattern: get fields by ID for budget calculation; no required enforcement. | No JS enforcement of required; safe. |
| partials/Edit/IES/estimated_expenses.blade.php (92) | `alert("At least one expense entry is required.")` when adding an expense row with no amount. | Business rule in JS; not HTML5 required. Document for removal planning if draft allows empty expenses. |

No other JS was found that adds or enforces `required` on project edit/create form controls.

---

## 5. Removal Execution Checklist

Use this list for stepwise removal of **business** required attributes. Do **not** remove required from §1 (action modals) without a separate decision. For draft-safe edit, focus on fields that block "Save as draft" with partial data.

**Create form (general_info – used by createProjects):**

- [ ] resources/views/projects/partials/general_info.blade.php – project_type (line 5)
- [ ] resources/views/projects/partials/general_info.blade.php – project_title (line 45)
- [ ] resources/views/projects/partials/general_info.blade.php – society_name (line 49)

**Edit form – general info (three blocks in one file):**

- [ ] resources/views/projects/partials/Edit/general_info.blade.php – project_title (line 98)
- [ ] resources/views/projects/partials/Edit/general_info.blade.php – society_name (line 104)
- [ ] resources/views/projects/partials/Edit/general_info.blade.php – overall_project_period (line 227)
- [ ] resources/views/projects/partials/Edit/general_info.blade.php – overall_project_budget (line 294)
- [ ] resources/views/projects/partials/Edit/general_info.blade.php – project_title (line 435)
- [ ] resources/views/projects/partials/Edit/general_info.blade.php – project_type (line 441)
- [ ] resources/views/projects/partials/Edit/general_info.blade.php – society_name (line 452)
- [ ] resources/views/projects/partials/Edit/general_info.blade.php – in_charge (line 463)
- [ ] resources/views/projects/partials/Edit/general_info.blade.php – overall_project_period (line 476)
- [ ] resources/views/projects/partials/Edit/general_info.blade.php – current_phase (line 485)
- [ ] resources/views/projects/partials/Edit/general_info.blade.php – project_title (line 530)
- [ ] resources/views/projects/partials/Edit/general_info.blade.php – project_type (line 535)
- [ ] resources/views/projects/partials/Edit/general_info.blade.php – society_name (line 555)
- [ ] resources/views/projects/partials/Edit/general_info.blade.php – in_charge (line 573)
- [ ] resources/views/projects/partials/Edit/general_info.blade.php – overall_project_period (line 600)
- [ ] resources/views/projects/partials/Edit/general_info.blade.php – current_phase (line 606)
- [ ] resources/views/projects/partials/Edit/general_info.blade.php – overall_project_budget (line 619)

**Edit form – key information:**

- [ ] resources/views/projects/partials/Edit/key_information.blade.php – goal (line 183)

**NPD (Next Phase) key information:**

- [ ] resources/views/projects/partials/NPD/key_information.blade.php – goal (line 8)

**Comments (review before removal):**

- [ ] resources/views/projects/partials/ProjectComments.blade.php – comment (line 34)
- [ ] resources/views/projects/comments/edit.blade.php – comment (line 11)

**Do not remove (action modals – §1):**

- resources/views/projects/partials/actions.blade.php – commencement_month (line 92)
- resources/views/projects/partials/actions.blade.php – commencement_year (line 109)
- resources/views/projects/partials/actions.blade.php – revert_reason (line 154)

---

## 6. Risk Flagging

### Fields used in calculations

- **overall_project_budget** (Edit/general_info.blade.php, lines 294, 619)  
  Used in `scripts-edit.blade.php` and `scripts.blade.php` for amount_sanctioned / opening_balance and amount_forwarded vs budget. Code uses `parseFloat(field.value) || 0`, so empty/null is treated as 0. **Risk: low** for removal of required; ensure backend and display handle null/empty for draft.

### Fields used in submission gating

- **project_type**  
  Backend uses it for routing (type-specific controllers) and for sub-controller updates. When draft, controller already merges existing project_type if not sent. **Risk: low** if required is removed only in edit form and draft path preserves/merges project_type.

- **in_charge**  
  Used in permission (ProjectPermissionHelper::isOwnerOrInCharge). Backend keeps in_charge NOT NULL; draft save currently merges existing project_type but does not set in_charge. If in_charge is empty on submit (draft), controller would need to preserve existing in_charge (same pattern as project_type). **Risk: medium** – ensure update path does not write null to in_charge when draft leaves it empty.

### Duplicated across partials

- **project_title, project_type, society_name, in_charge, overall_project_period, current_phase, overall_project_budget**  
  Repeated in **three** blocks inside `partials/Edit/general_info.blade.php`. Removal must be applied to all three for consistent behavior.

### JS validation

- **IES estimated_expenses**  
  "At least one expense entry is required" alert (Edit/IES/estimated_expenses.blade.php). Not an HTML required attribute; document for future draft behavior if empty expense rows are allowed.

---

## 7. Other Validation Attributes (No required)

- **minlength:** Not found on any input/textarea in the scanned files. Help text "Minimum 100 words required" appears in key_information partials but is not enforced via `minlength`.
- **pattern:** Not found in project views.
- **step:** Used on number inputs (e.g. budget, scholarship amounts, expenses) for decimal precision (`step="0.01"`). Does not enforce presence; only format. No change needed for draft.

---

**End of audit. No blade files were modified.**
