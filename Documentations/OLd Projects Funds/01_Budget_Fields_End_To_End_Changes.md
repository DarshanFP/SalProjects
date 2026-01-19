# Budget Fields — End-to-End Changes (Implemented)

## Goal (in human terms)

Budget values in `projects` needed to behave like this:

- **Overall Project Budget**: total budget of the project (auto from budget items)
- **Amount Forwarded (Existing Funds)**: money already available with the organization (entered by executor/applicant)
- **Local Contribution**: local funds available (entered by executor/applicant)
- **Amount Sanctioned (To Request)**: money to request from SAL  
  \(\text{Amount Sanctioned} = \text{Overall Budget} - (\text{Forwarded} + \text{Local})\)
- **Opening Balance**: total funds available after approval  
  \(\text{Opening Balance} = \text{Amount Sanctioned} + (\text{Forwarded} + \text{Local})\)  
  (which equals **Overall Budget** when inputs are valid)

---

## Database change

- **Added new field**: `projects.local_contribution`
  - File: `database/migrations/2026_01_07_000001_add_local_contribution_to_projects_table.php`
  - Meaning: local funds available for the project (like “existing funds”, but from local sources)

---

## Model changes (why the value wasn’t saving before)

- **Enabled mass assignment** for the new column
  - File: `app/Models/OldProjects/Project.php`
  - Change: add `local_contribution` to `$fillable`
  - Human impact: without this, the edit form could “submit” the value but Eloquent would silently ignore it.

---

## Create/Edit forms — new inputs + live calculations

### Create: add inputs and previews

- File: `resources/views/projects/partials/budget.blade.php`
- Changes:
  - Added numeric input: `amount_forwarded`
  - Added numeric input: `local_contribution`
  - Added readonly previews: `amount_sanctioned_preview`, `opening_balance_preview`
  - Updated label: **Amount Forwarded (Existing Funds)**

### Edit: same fields, pre-filled

- File: `resources/views/projects/partials/Edit/budget.blade.php`
- Changes:
  - Same fields as create
  - Pre-filled values from `$project` using `old('field', $project->field ?? 0.00)`

### JS (create + edit) — calculations & guardrails

- File: `resources/views/projects/partials/scripts.blade.php`
- File: `resources/views/projects/partials/scripts-edit.blade.php`
- Changes:
  - Added/updated `calculateBudgetFields()`
  - Uses:
    - `overall_project_budget`
    - `amount_forwarded`
    - `local_contribution`
  - Calculates previews:
    - `amount_sanctioned_preview`
    - `opening_balance_preview`
  - Validation behavior:
    - if \(\text{forwarded} + \text{local} > \text{overall}\) → user gets warned and values are corrected.

> Note: The previously commented multi-phase JS section was intentionally left commented, per requirement.

---

## Server-side validation (so rules also apply even if JS is bypassed)

### Create validation

- File: `app/Http/Requests/Projects/StoreProjectRequest.php`
- Changes:
  - Added validation rules for `amount_forwarded` and `local_contribution`
  - Added business rule: \((\text{forwarded} + \text{local}) \le \text{overall}\)

### Update validation

- File: `app/Http/Requests/Projects/UpdateProjectRequest.php`
- File: `app/Http/Requests/Projects/UpdateGeneralInfoRequest.php`
- Changes:
  - Same combined constraint checks to avoid invalid budgets on updates.

---

## Persisting values on store/update (backend)

- File: `app/Http/Controllers/Projects/GeneralInfoController.php`
- Changes:
  - Ensure default values:
    - `amount_forwarded` defaults to `0.00` if missing
    - `local_contribution` defaults to `0.00` if missing
  - This prevents null math and ensures consistent calculations later.

---

## Coordinator approval — where “sanctioned” becomes official

- File: `app/Http/Controllers/CoordinatorController.php`
- Method: `approveProject($project_id)`
- Change (business logic):
  - Read:
    - `overall_project_budget`
    - `amount_forwarded`
    - `local_contribution`
  - Validate:
    - prevent approval if \((\text{forwarded} + \text{local}) > \text{overall}\)
  - Save:
    - `amount_sanctioned = overall - (forwarded + local)`
    - `opening_balance = amount_sanctioned + (forwarded + local)`
  - Add logs for auditability.

---

## Show view — display updates

### Budget section

- File: `resources/views/projects/partials/Show/budget.blade.php`
- Changes:
  - Added a “summary grid” with:
    - Overall Project Budget
    - Amount Forwarded (Existing Funds)
    - Local Contribution
    - Amount Sanctioned
    - Opening Balance
  - Updated math display to use **forwarded + local**.

### Basic Information section

- File: `resources/views/projects/partials/Show/general_info.blade.php`
- Changes:
  - Show **Local Contribution** after **Amount Forwarded (Existing Funds)**

---

## Styling (UX fix)

- File: `public/css/custom/project-forms.css`
- Change:
  - Added `.budget-summary-input` styling to ensure readonly summary fields stay readable on dark background.

---

## Related migration hardening (during running migrations)

- File: `database/migrations/2025_06_29_104156_add_is_budget_row_to_dp_account_details_table.php`
- Change:
  - Added a column-existence guard (`Schema::hasColumn`) so migration is idempotent and won’t fail if column already exists.


