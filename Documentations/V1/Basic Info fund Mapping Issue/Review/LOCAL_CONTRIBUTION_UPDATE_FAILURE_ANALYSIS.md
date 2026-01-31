# Why `projects.local_contribution` Is Not Updated for IIES (Edit/Update Flow)

**Date:** 2026-01-29  
**Project:** IIES-0025 (Individual - Initial - Educational support)  
**Source:** Log analysis (laravel.log lines 13507–13648) and code trace of edit/update methods.

---

## 1. Executive Summary

For **IIES** (and other individual types: IES, ILP, IAH), the **`projects.local_contribution`** column is **never updated** during the normal edit → update flow because:

1. **Form:** The edit form does **not** include any input for `local_contribution` (or `amount_forwarded`) for IIES. The Budget partial that contains these fields is excluded for individual project types.
2. **General Info:** The General Info partial used for all types has `overall_project_budget` but **does not** include `local_contribution` or `amount_forwarded`.
3. **Sync on type save:** The only path that could write `local_contribution` from type-specific data (IIES Expenses) is `BudgetSyncService::syncFromTypeSave()`, which runs after IIES expenses are saved—but it is **disabled by default** (feature flags off).

Result: neither the form nor the sync path updates `projects.local_contribution` for IIES, so it remains 0 (or whatever was last set manually / by sync when flags were on).

---

## 2. Log Trace: Edit and Update Flow

### 2.1 Edit (GET)

From the log (e.g. 17:13:29, 17:14:48):

```
ProjectController@edit - Starting edit process {"project_id":"IIES-0025"}
ProjectController@edit - Fetching project data with relationships
ProjectController@edit - Project fetched {"project_type":"Individual - Initial - Educational support",...}
ProjectController@edit - Fetching IIES data
Editing IIES family working members ...
Editing IIES Immediate Family Details ...
🔍 Fetching IIES Educational Background ...
🔍 Fetching IIES Financial Support ...
✅ ProjectController@edit - IIES Financial Support Found {"data":{...,"family_contrib":null ...}}
IIESAttachmentsController@edit - Start ...
Fetching IIES Expenses for editing ...
IIESExpenses Controller - Fetched IIES Expenses for editing {...}
ProjectController@edit - Preparing data for view {"project_id":"IIES-0025"}
```

- Edit loads the project and IIES-related data (Financial Support, Expenses, etc.).
- **No** step loads or prepares `local_contribution` for the **projects** table for display in a General Info budget block, because for IIES that block is not shown (see Section 3).

### 2.2 Update (PUT)

From the log (e.g. 17:14:05, 17:15:16):

```
ProjectController@update - Starting update process {"project_id":"IIES-0025",...}
ProjectController@update - Fetching project from database
ProjectController@update - Updating general info
GeneralInfoController@update - Start {"project_id":"IIES-0025",...}
GeneralInfoController@update - Data passed to database {"project_id":"IIES-0025","status":"reverted_by_provincial"}
KeyInformationController@update - Data received from form ...
KeyInformationController@update - Data saved successfully ...
ProjectController@update - General info and key information updated
ProjectController@update - Updating IIES data
Updating IIES Personal Info ...
Updating IIES family working members ...
Updating IIES Immediate Family Details ...
Updating IIES Educational Background ...
Updating IIES Financial Support ...
IIES Financial Support updated successfully
IIESAttachmentsController@update - Start ...
IIESExpensesController@store - Success {"project_id":"IIES-0025","expense_id":"IIES-EXP-0034","total_expenses":"13000.00","balance_requested":"12100.00",...}
ProjectController@update - Project updated successfully
```

- **GeneralInfoController@update** runs first and persists only what is in the **validated request** (see Section 4). The request does **not** contain `local_contribution` for IIES, so the projects row is never updated with that field.
- **IIESExpensesController@store** runs later in the same request and successfully writes type-specific expense data (including contribution-related fields in `project_IIES_expenses`). After commit it calls **BudgetSyncService::syncFromTypeSave($project)**. That call **would** compute and write `local_contribution` (and other TYPE_SAVE_FIELDS) to `projects`—but only when the sync feature flags are enabled (Section 5).

So from the log we see:

- General Info update does **not** receive or save `local_contribution`.
- IIES expense save succeeds, but sync to `projects` is conditional on config and is off by default.

---

## 3. Edit Form Structure: Why `local_contribution` Is Never Sent

### 3.1 Which partials are included for IIES

File: `resources/views/projects/Oldprojects/edit.blade.php`

- **All types** get:
    - General Information → `@include('projects.partials.Edit.general_info')`
    - Key Information → `@include('projects.partials.Edit.key_information')`
- **IIES** additionally gets:
    - IIES Personal Info, Family Working Members, Immediate Family Details, Education Background
    - **Scope Financial Support** (includes `family_contrib` in **IIES** table, not `projects.local_contribution`)
    - **IIES Estimated Expenses** (type-specific expense and contribution fields)
    - IIES Attachments

Crucially, the **Budget** partial is **excluded** for individual types:

```php
@if (!in_array($project->project_type, [
    'Individual - Ongoing Educational support',
    'Individual - Livelihood Application',
    'Individual - Access to Health',
    'Individual - Initial - Educational support',
]))
    @include('projects.partials.Edit.logical_framework')
    @include('projects.partials.Edit.sustainibility')
    @include('projects.partials.Edit.budget')   // ← Contains local_contribution, amount_forwarded
    @include('projects.partials.Edit.attachment')
@endif
```

So for **IIES**, the edit form **never** includes `projects.partials.Edit.budget`, and therefore **no** `<input name="local_contribution">` (or `amount_forwarded`) is rendered.

### 3.2 General Info partial

File: `resources/views/projects/partials/Edit/general_info.blade.php`

- Contains `overall_project_budget` (and other General Info fields).
- **Does not** contain `local_contribution` or `amount_forwarded` (grep confirms no matches).

So even the General Info section does not send `local_contribution` on submit.

**Conclusion:** For IIES, the PUT request to update the project **never** includes `local_contribution`. The only way `projects.local_contribution` could be updated in this flow is via the **sync-on-type-save** path after IIES expenses are stored.

---

## 4. GeneralInfoController@update: What Gets Written to `projects`

File: `app/Http/Controllers/Projects/GeneralInfoController.php`

- Uses `$validated = $request->validated();` and then `$project->update($validated);`.
- Validation rules (and thus `$validated`) include `local_contribution` and `amount_forwarded` only if they are **present in the request** (nullable).
- For IIES, those keys are **not** in the request (no form fields), so they are **not** in `$validated` and **never** passed to `$project->update()`.

So **GeneralInfoController does not update `projects.local_contribution` for IIES** because the value is never submitted.

(If budget lock is active, approved projects also have these keys stripped so they are never written; that is a separate safeguard.)

---

## 5. Sync-on-Type-Save: The Only Path That Could Set `local_contribution`

After **IIESExpensesController@store** commits, it calls:

```php
$project = Project::where('project_id', $projectId)->first();
if ($project) {
    app(BudgetSyncService::class)->syncFromTypeSave($project);
}
```

**BudgetSyncService::syncFromTypeSave()**:

- Resolves fund fields (including `local_contribution`) from type-specific data via **ProjectFundFieldsResolver** (for IIES: from `ProjectIIESExpenses` — e.g. `iies_expected_scholarship_govt` + `iies_support_other_sources` + `iies_beneficiary_contribution`).
- Writes only `overall_project_budget`, `local_contribution`, and `amount_forwarded` to the **projects** table (TYPE_SAVE_FIELDS).

So **if** this sync runs, `projects.local_contribution` **would** be updated from IIES expense data.

**BudgetSyncGuard::canSyncOnTypeSave($project)** requires **all** of:

1. `config('budget.resolver_enabled')` === true
2. `config('budget.sync_to_projects_on_type_save')` === true
3. Project status is **not** approved (reverted is allowed)

Default config (e.g. in `config/budget.php` and `.env`):

- `resolver_enabled` => `env('BUDGET_RESOLVER_ENABLED', false)` → **false**
- `sync_to_projects_on_type_save` => `env('BUDGET_SYNC_ON_TYPE_SAVE', false)` → **false**

So by default, **sync on type save does not run**, and `projects.local_contribution` is **never** written after IIES expense save.

---

## 6. Root Causes (Summary)

| #   | Cause                                                         | Detail                                                                                                                                                                                                                                                                                                       |
| --- | ------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 1   | **Form does not send `local_contribution` for IIES**          | Edit view excludes `Edit.budget` for individual types (IIES, IES, ILP, IAH). General Info partial has no `local_contribution` (or `amount_forwarded`) field. So the update request never contains `local_contribution`.                                                                                      |
| 2   | **GeneralInfoController only updates what is in the request** | It uses `$request->validated()` and `$project->update($validated)`. Since `local_contribution` is not in the request, it is never updated in `projects`.                                                                                                                                                     |
| 3   | **Sync-on-type-save is off by default**                       | The only path that can set `projects.local_contribution` from IIES data is `BudgetSyncService::syncFromTypeSave()`, which is gated by `BUDGET_RESOLVER_ENABLED` and `BUDGET_SYNC_ON_TYPE_SAVE`. Both default to false, so sync never runs and `local_contribution` is never written from type-specific data. |

---

## 7. Recommendations

### 7.1 Short term (display already fixed)

- **Show page:** Resolved fund fields (including Local Contribution) are now passed for IIES (and other type-specific budget types) in **ProjectController@show**, so the Basic Info block displays the correct **computed** Local Contribution even when `projects.local_contribution` is 0. No further change required for **display** on show.

### 7.2 To actually update `projects.local_contribution` for IIES

Choose one or both:

**Option A – Enable sync on type save (Phase 2)**

- Set in `.env` (or config):
    - `BUDGET_RESOLVER_ENABLED=true`
    - `BUDGET_SYNC_ON_TYPE_SAVE=true`
- Then, on every IIES expense save (and other type-specific budget saves), the resolver will run and `syncFromTypeSave()` will write `overall_project_budget`, `local_contribution`, and `amount_forwarded` to `projects`. This keeps `projects` in sync with type-specific data without changing the form.

**Option B – Add hidden (or read-only) fields for individual types**

- For IIES (and optionally IES, ILP, IAH), in the **General Info** (or a type-specific) section, add hidden inputs (or read-only display + hidden) that are **populated by JavaScript** from the type-specific section (e.g. from IIES Estimated Expenses: sum of scholarship + support + beneficiary). Submit them as `local_contribution` (and if needed `amount_forwarded`) so **GeneralInfoController@update** receives and persists them. This updates `projects` on every update without relying on sync flags.

**Option C – Backfill existing IIES projects**

- Run a one-off command or migration that, for each IIES project, resolves fund fields (using **ProjectFundFieldsResolver**), then updates `projects.overall_project_budget`, `projects.local_contribution`, and `projects.amount_forwarded` from the resolved values. Useful to fix existing rows that were never synced.

---

## 8. References

- **Resolver (read):** `app/Services/Budget/ProjectFundFieldsResolver.php` — `resolveIIES()` computes `local_contribution` from IIES expenses.
- **Sync (write):** `app/Services/Budget/BudgetSyncService.php` — `syncFromTypeSave()`, TYPE_SAVE_FIELDS.
- **Guard:** `app/Services/Budget/BudgetSyncGuard.php` — `canSyncOnTypeSave()`.
- **Edit view:** `resources/views/projects/Oldprojects/edit.blade.php` — conditional inclusion of `Edit.budget`.
- **General Info edit:** `resources/views/projects/partials/Edit/general_info.blade.php` — no `local_contribution` / `amount_forwarded`.
- **Update flow:** `app/Http/Controllers/Projects/ProjectController.php` — `update()` calls GeneralInfoController then type-specific controllers; IIES expense save triggers sync when flags are on.
- **Config:** `config/budget.php` — `resolver_enabled`, `sync_to_projects_on_type_save`.
- **Plan:** `Documentations/V1/Basic Info fund Mapping Issue/PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md`.
