# IIES Project Creation Forensic Analysis

**Date:** 2026-02-08  
**Project Type:** Individual – Initial – Educational Support (IIES)  
**Scope:** Read-only correctness and integrity audit for IIES project creation  
**Status:** Analysis complete; no fixes proposed

---

## Executive Summary

This document records the findings of a forensic analysis of the IIES (Individual – Initial – Educational Support) project creation flow. The analysis was triggered by:

- Logs reporting "General project details saved"
- `projects` table having no row for `project_id` = IIES-0029
- Error: `SQLSTATE[23000]: Column 'iies_bname' cannot be null`
- Same `project_id` across multiple submissions

---

## Table of Contents

1. [Execution Timeline](#1-execution-timeline)
2. [Transaction Boundaries](#2-transaction-boundaries)
3. [Validation Gaps](#3-validation-gaps)
4. [Root Cause Hypothesis](#4-root-cause-hypothesis)
5. [Evidence](#5-evidence)
6. [Discrepancy Documentation](#6-discrepancy-documentation)

---

## 1. Execution Timeline

Ordered flow from entry point to database:

| Step | Location | Action |
|------|----------|--------|
| 1 | `routes/web.php:426` | `POST executor/projects/store` → `ProjectController@store` |
| 2 | `ProjectController.php:413-414` | `StoreProjectRequest` validated; `DB::beginTransaction()` |
| 3 | `ProjectController.php:415` | `GeneralInfoController()->store($request)` |
| 4 | `GeneralInfoController.php:92` | `Project::create($validated)` → INSERT into `projects` |
| 5 | `Project.php:389-391` | `creating` event → `$model->project_id = $model->generateProjectId()` |
| 6 | `Project.php:414-418` | `generateProjectId()`: latest IIES count, increment → e.g. IIES-0029 |
| 7 | `ProjectController.php:568` | Log: "General project details saved" |
| 8 | `ProjectController.php:440` | `KeyInformationController()->store($request, $project)` |
| 9 | `ProjectController.php:517-524` | IIES switch: `iiesPersonalInfoController->store()` first |
| 10 | `IIESPersonalInfoController.php:53-61` | `DB::beginTransaction()`, `mapRequestToModel()`, `$personalInfo->save()` |
| 11 | `IIESPersonalInfoController.php:65-68` | On exception: `DB::rollBack()`, Log::error, `return response()->json()` |
| 12 | `ProjectController.php:518-522` | Continues: FamilyWorkingMembers, ImmediateFamilyDetails, EducationBackground, etc. |
| 13 | `ProjectController.php:494` | `DB::commit()` (if no exception propagates) |
| 14 | `ProjectController.php:507` | Log: "Project and all related data saved successfully" |

---

## 2. Transaction Boundaries

| Boundary | File:Line | Behavior |
|----------|-----------|----------|
| **Outer transaction start** | `ProjectController.php:414` | `DB::beginTransaction()` |
| **Projects INSERT** | `GeneralInfoController.php:92` | `Project::create()` — within outer transaction |
| **Nested transaction** | `IIESPersonalInfoController.php:53` | `DB::beginTransaction()` (creates savepoint) |
| **Nested rollback** | `IIESPersonalInfoController.php:66` | `DB::rollBack()` — rolls back savepoint only |
| **Commit** | `ProjectController.php:494` | `DB::commit()` — commits outer transaction |
| **Outer rollback** | `ProjectController.php:529-530` | On propagated exception: `DB::rollBack()` |

### Critical Finding: Exception Swallowing

When IIES Personal Info fails with the `iies_bname` integrity error:

1. `IIESPersonalInfoController` catches the exception.
2. Rolls back its nested transaction.
3. Returns `response()->json(['error' => '...'], 500)`.
4. **Does not re-throw.**

`ProjectController` does not check this return value and continues with the next IIES controllers. Only exceptions that propagate to `ProjectController` (e.g. from `KeyInformationController`) cause a full rollback.

---

## 3. Validation Gaps

| Field | DB Constraint | Validation | Gap |
|-------|---------------|------------|-----|
| `iies_bname` | `NOT NULL` (`project_IIES_personal_info`) | `StoreProjectRequest` has no IIES rules | StoreProjectRequest does not validate `iies_bname` |
| `iies_bname` | — | `StoreIIESPersonalInfoRequest` has `required` | StoreIIESPersonalInfoRequest is never used in the create flow |

### Evidence

**StoreProjectRequest** (`app/Http/Requests/Projects/StoreProjectRequest.php`, lines 25-78):

- No `iies_bname` rule.
- Only general info and key information are validated.

**StoreIIESPersonalInfoRequest** (`app/Http/Requests/Projects/IIES/StoreIIESPersonalInfoRequest.php`, line 17):

```php
'iies_bname' => 'required|string|max:255',
```

- Exists but is **never used** in the create flow.

**ProjectController** (`app/Http/Controllers/Projects/ProjectController.php`, line 415):

- Passes `StoreProjectRequest` to the entire flow.
- IIES controllers receive this request, not `StoreIIESPersonalInfoRequest`.

**IIESPersonalInfoController** (`app/Http/Controllers/Projects/IIES/IIESPersonalInfoController.php`, line 51):

- Method signature: `store(FormRequest $request, $projectId)` — receives `StoreProjectRequest`.
- No IIES-specific validation is executed.

---

## 4. Root Cause Hypothesis

### Attempt 1 (20:14:26)

- Logs show "General project details saved" and IIES Personal Info stored successfully.
- If no exception propagates, `DB::commit()` should run and the projects row should persist.
- Same `project_id` (IIES-0029) on both attempts suggests that in at least one scenario the outer transaction was rolled back and nothing was committed.

### Attempt 2 (20:15:06)

- IIES Personal Info fails: `Column 'iies_bname' cannot be null`.
- IIESPersonalInfoController catches the exception and does not re-throw.
- Flow continues: FamilyWorkingMembers, ImmediateFamilyDetails, EducationBackground, etc. succeed.
- `DB::commit()` runs.
- In theory, the projects row from step 4 would be committed.

### Why "General project details saved" but No Projects Row?

Possible explanations:

1. **Outer rollback:** An exception propagates before `DB::commit()` (e.g. from KeyInformationController or another IIES controller) and triggers `ProjectController`’s `DB::rollBack()`.
2. **Unlogged exception:** An exception triggers rollback but is not clearly visible in the logs.
3. **DB / connection mismatch:** The table inspected for the missing row is not the one written to by `Project::create()`.

### Why `iies_bname` Is Null

1. **Validation gap:** `iies_bname` is not validated by `StoreProjectRequest`.
2. **Form behavior:** The IIES section is in a `display:none` div; inputs are disabled until the project type is selected. Although the submit handler enables disabled fields before submission, edge cases (e.g. back + resubmit, JS error) could leave `iies_bname` absent.
3. **Mapping:** `mapRequestToModel()` uses `$request->input('iies_bname')`. If the key is absent, it returns `null`, which is written to a `NOT NULL` column.

---

## 5. Evidence

### 5.1 Routing & Entry Point

**File:** `routes/web.php`, line 426

```php
Route::post('store', [ProjectController::class, 'store'])->name('projects.store');
```

**File:** `app/Http/Controllers/Projects/ProjectController.php`, lines 516-524

```php
case ProjectType::INDIVIDUAL_INITIAL_EDUCATIONAL:
    Log::info('Processing Individual - Initial - Educational support project type');
    $this->iiesPersonalInfoController->store($request, $project->project_id);
    $this->iiesFamilyWorkingMembersController->store($request, $project->project_id);
    $this->iiesImmediateFamilyDetailsController->store($request, $project->project_id);
    $this->iiesEducationBackgroundController->store($request, $project->project_id);
    $this->iiesFinancialSupportController->store($request, $project->project_id);
    $this->iiesAttachmentsController->store($request, $project->project_id);
    $this->iiesExpensesController->store($request, $project->project_id);
    break;
```

### 5.2 Project ID Generation

**File:** `app/Models/OldProjects/Project.php`, lines 386-419

```php
static::creating(function ($model) {
    $model->project_id = $model->generateProjectId();
});
// ...
private function generateProjectId()
{
    $initialsMap = [
        // ...
        'Individual - Initial - Educational support' => 'IIES',
    ];
    $initials = $initialsMap[$this->project_type] ?? 'GEN';
    $latestProject = self::where('project_id', 'like', $initials . '-%')->latest('id')->first();
    $sequenceNumber = $latestProject ? intval(substr($latestProject->project_id, strlen($initials) + 1)) + 1 : 1;
    return $initials . '-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
}
```

### 5.3 Projects INSERT

**File:** `app/Http/Controllers/Projects/GeneralInfoController.php`, line 92

```php
$project = Project::create($validated);
```

### 5.4 IIES Personal Info — Source and Mapping of `iies_bname`

**File:** `app/Http/Controllers/Projects/IIES/IIESPersonalInfoController.php`, lines 44-48

```php
private function mapRequestToModel(FormRequest $request, ProjectIIESPersonalInfo $personalInfo): void
{
    foreach ($this->getPersonalInfoFields() as $field) {
        $personalInfo->$field = $request->input($field);  // iies_bname from request
    }
}
```

**File:** `app/Models/OldProjects/IIES/ProjectIIESPersonalInfo.php`, line 71

```php
protected $fillable = [..., 'iies_bname', ...];
```

**File:** `database/migrations/2025_01_29_174348_create_project_i_i_e_s_personal_infos_table.php`

```php
$table->string('iies_bname');  // NOT NULL by default
```

### 5.5 Exception Handling — Flow Continues After Failure

**File:** `app/Http/Controllers/Projects/IIES/IIESPersonalInfoController.php`, lines 65-68

```php
} catch (\Exception $e) {
    DB::rollBack();
    Log::error('Error saving IIES Personal Info', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Failed to save IIES Personal Info.'], 500);
    // No re-throw — ProjectController continues
}
```

### 5.6 Logging vs Persistence

**File:** `app/Http/Controllers/Projects/ProjectController.php`, line 568

```php
Log::info('General project details saved', ['project_id' => $project->project_id]);
```

- Runs immediately after `GeneralInfoController()->store()` returns.
- Occurs before `DB::commit()` at line 494.
- The row exists only in the uncommitted transaction until commit; if rollback occurs later, the row is removed.

---

## 6. Discrepancy Documentation

### Reported Symptoms

- Logs: "General project details saved"
- `projects` table: no row for `project_id` = IIES-0029
- Later error: `Column 'iies_bname' cannot be null`

### Analysis Conclusion

The "General project details saved" log is written after the projects INSERT and before commit. If the outer transaction commits without rollback, the row should be present in the `projects` table.

Possible reasons for the reported absence of the row:

1. An exception propagated to `ProjectController` and triggered `DB::rollBack()`.
2. A different database or connection was checked.
3. An additional failure or rollback not clearly represented in the available logs.

**Note:** Phase 2.1 and 2.2 changes are not suspected as causes. This is a correctness and integrity audit of the IIES project creation flow.

---

## Appendix: Relevant File Paths

| Component | Path |
|-----------|------|
| Route | `routes/web.php` |
| ProjectController | `app/Http/Controllers/Projects/ProjectController.php` |
| GeneralInfoController | `app/Http/Controllers/Projects/GeneralInfoController.php` |
| KeyInformationController | `app/Http/Controllers/Projects/KeyInformationController.php` |
| IIESPersonalInfoController | `app/Http/Controllers/Projects/IIES/IIESPersonalInfoController.php` |
| IIESFamilyWorkingMembersController | `app/Http/Controllers/Projects/IIES/IIESFamilyWorkingMembersController.php` |
| Project model | `app/Models/OldProjects/Project.php` |
| ProjectIIESPersonalInfo model | `app/Models/OldProjects/IIES/ProjectIIESPersonalInfo.php` |
| StoreProjectRequest | `app/Http/Requests/Projects/StoreProjectRequest.php` |
| StoreIIESPersonalInfoRequest | `app/Http/Requests/Projects/IIES/StoreIIESPersonalInfoRequest.php` |
| IIES create form partial | `resources/views/projects/partials/IIES/personal_info.blade.php` |
| Create projects view | `resources/views/projects/Oldprojects/createProjects.blade.php` |
