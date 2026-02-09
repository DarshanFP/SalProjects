# IIES Create Flow Forensic Report

**Date:** 2026-02-08  
**Project Type:** Individual – Initial – Educational Support (IIES)  
**Scope:** Read-only forensic audit to identify where and why `iies_bname` becomes NULL  
**Task:** Prove or disprove potential causes using verifiable evidence from the repository

---

## 1. Request Entry Point

| Location | Evidence |
|----------|----------|
| **Route** | `routes/web.php:426` — `Route::post('store', [ProjectController::class, 'store'])->name('projects.store')` |
| **Full URL** | `POST /executor/projects/store` (within `Route::prefix('executor/projects')` group at line 420) |
| **Controller** | `ProjectController@store` |
| **Form Request** | `StoreProjectRequest` (type-hinted at `ProjectController.php:413`) |

---

## 2. UI → HTTP Payload Mapping

### 2.1 Form Structure

| Component | File:Line | Evidence |
|-----------|-----------|----------|
| **Form** | `createProjects.blade.php:8` | `<form id="createProjectForm" action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data">` |
| **IIES section** | `createProjects.blade.php:64-72` | `<div id="iies-sections" style="display:none;">` containing `@include('projects.partials.IIES.personal_info')` |
| **Position** | Inside `<form>` | IIES section is between form start (L8) and form end (L148) |

### 2.2 IIES Personal Info Fields

| UI Label | Input name | Matches DB column | Submitted? |
|----------|------------|-------------------|------------|
| Name | `iies_bname` | Yes (`project_IIES_personal_info.iies_bname`) | Conditional |
| Age | `iies_age` | Yes | Conditional |
| Gender | `iies_gender` | Yes | Conditional |
| Date of Birth | `iies_dob` | Yes | Conditional |
| E-mail | `iies_email` | Yes | Conditional |
| Contact number | `iies_contact` | Yes | Conditional |
| Aadhar number | `iies_aadhar` | Yes | Conditional |
| Full Address | `iies_full_address` | Yes | Conditional |
| Name of Father | `iies_father_name` | Yes | Conditional |
| Name of Mother | `iies_mother_name` | Yes | Conditional |
| Mother tongue | `iies_mother_tongue` | Yes | Conditional |
| Current studies | `iies_current_studies` | Yes | Conditional |
| Caste | `iies_bcaste` | Yes | Conditional |
| Occupation of Father | `iies_father_occupation` | Yes | Conditional |
| Monthly income of Father | `iies_father_income` | Yes | Conditional |
| Occupation of Mother | `iies_mother_occupation` | Yes | Conditional |
| Monthly income of Mother | `iies_mother_income` | Yes | Conditional |

**Source:** `resources/views/projects/partials/IIES/personal_info.blade.php` lines 7-100.

### 2.3 Conditional Rendering

| Condition | Location | Behavior |
|-----------|----------|----------|
| **Initial state** | `createProjects.blade.php:65` | `#iies-sections` has `style="display:none;"` |
| **Enable on project type** | `createProjects.blade.php:250-252` | When `project_type === 'Individual - Initial - Educational support'`: `iiesSections.style.display = 'block'`, `enableInputsIn(iiesSections)` |
| **Default state** | `createProjects.blade.php:227-237` | `hideAndDisableAll()` sets `display:none` and `disabled=true` on all sections including `#iies-sections` |

**Critical:** Browser behavior: **disabled inputs are not submitted** (HTML4/5 specification). When `#iies-sections` is hidden and disabled, `iies_bname` is not included in the form payload.

---

## 3. Form Submission Mechanism

### 3.1 Submission Type

| Aspect | Evidence |
|--------|----------|
| **Mechanism** | Standard POST (no AJAX, no FormData construction) |
| **Trigger** | `<button type="submit" id="createProjectBtn">` or `<button type="button" id="saveDraftBtn">` |
| **Source** | `createProjects.blade.php:8` — `method="POST"` |

### 3.2 JavaScript Pre-Submit Handler

| Location | Code | Purpose |
|----------|------|---------|
| `createProjects.blade.php:358-405` | `createForm.addEventListener('submit', ...)` | Runs before native form submission |
| Lines 371-374 | `disabledFields.forEach(field => { field.disabled = false; });` | Enables all disabled inputs |
| Lines 377-381 | `hiddenSections.forEach(section => { section.style.display = ''; });` | Shows sections with `display:none` |

**Key logic:** The handler runs synchronously, enables disabled fields, then returns `true` to allow the default form submit. At submit time, previously disabled fields (including IIES inputs) should be enabled and included.

### 3.3 Selector for Hidden Sections

| Selector | Source | Match |
|----------|--------|-------|
| `[style*="display: none"]` | `createProjects.blade.php:377` | Matches elements with literal substring `"display: none"` (with space) |
| IIES section inline style | `createProjects.blade.php:65` | `style="display:none;"` (no space) |

**Finding:** When IIES is selected, `iiesSections.style.display = 'block'` changes the style, so `#iies-sections` no longer has `display:none`. The "show hidden sections" logic targets other type-specific sections. The **enable disabled fields** step is the critical one for IIES; it runs on `createForm.querySelectorAll('[disabled]')` (line 372).

### 3.4 IIES Fields in Payload

| Scenario | Expected |
|----------|----------|
| User selects IIES, fills Name, submits | `iies_bname` key present with value |
| User selects IIES, leaves Name empty, submits | `iies_bname` key present with `""` |
| User selects IIES, JS error before submit, native submit | Fields may remain disabled → `iies_bname` absent |
| User uses Save as Draft | Same handler (lines 335-341) enables disabled fields before submit |

---

## 4. Backend Request Handling

### 4.1 Request Reception in ProjectController@store

| Step | File:Line | Action |
|------|-----------|--------|
| 1 | `ProjectController.php:413` | `store(StoreProjectRequest $request)` — Laravel resolves and validates |
| 2 | `ProjectController.php:415` | `GeneralInfoController()->store($request)` |
| 3 | `ProjectController.php:425` | `$request->merge(['project_id' => $project->project_id])` |
| 4 | `ProjectController.php:440` | `KeyInformationController()->store($request, $project)` |
| 5 | `ProjectController.php:686-692` | IIES case: `$request->validate(['iies_bname' => 'required|string|max:255'])` then `iiesPersonalInfoController->store($request, $project->project_id)` |

### 4.2 StoreProjectRequest Filtering

| Aspect | File:Line | Evidence |
|--------|-----------|----------|
| **Rules** | `StoreProjectRequest.php:21-79` | No `iies_bname` rule. No `only()` or `except()`. |
| **prepareForValidation** | `StoreProjectRequest.php:119-127` | Only normalizes `save_as_draft`; does not touch `iies_bname` |
| **Conclusion** | — | `$request->all()` and `$request->input('iies_bname')` reflect raw POST; no backend stripping of `iies_bname` |

### 4.3 Validation Order

| Order | Location | Effect |
|-------|----------|--------|
| 1 | `StoreProjectRequest` (before controller) | Validates general fields; no IIES rules |
| 2 | `ProjectController.php:689-691` | `$request->validate(['iies_bname' => 'required|string|max:255'])` inside IIES case |

**Evidence:** If `iies_bname` is absent or empty, step 2 throws `ValidationException` before `IIESPersonalInfoController->store` runs. The controller would not reach the database insert with NULL. The DB error "Column 'iies_bname' cannot be null" therefore implies that either (a) validation was bypassed or absent in the version that failed, or (b) `iies_bname` was present and non-empty at validation but became NULL before or during persistence.

---

## 5. Persistence Layer Analysis

### 5.1 IIES Personal Info Insert

| Component | File:Line | Evidence |
|-----------|-----------|----------|
| **Controller** | `IIESPersonalInfoController.php:51-67` | `store(FormRequest $request, $projectId)` |
| **Mapping** | `IIESPersonalInfoController.php:44-49` | `mapRequestToModel($request, $personalInfo)` — `$personalInfo->$field = $request->input($field)` for each field including `iies_bname` |
| **Persist** | `IIESPersonalInfoController.php:59` | `$personalInfo->save()` |

### 5.2 Source of iies_bname

| Step | Code | Result when key absent |
|------|------|------------------------|
| 1 | `$request->input('iies_bname')` | Returns `null` |
| 2 | `$personalInfo->iies_bname = null` | Model attribute set to null |
| 3 | `$personalInfo->save()` | Eloquent builds INSERT with `iies_bname = NULL` |
| 4 | DB | `project_IIES_personal_info.iies_bname` is NOT NULL → `SQLSTATE[23000]` |

**Source:** `IIESPersonalInfoController.php:46-48`:
```php
foreach ($this->getPersonalInfoFields() as $field) {
    $personalInfo->$field = $request->input($field);
}
```

### 5.3 Database Constraint

| Column | Migration | Constraint |
|--------|-----------|------------|
| `iies_bname` | `database/migrations/2025_01_29_174348_create_project_i_i_e_s_personal_infos_table.php:14` | `$table->string('iies_bname');` — NOT NULL (Laravel default) |

### 5.4 NULL Path

NULL reaches the persistence layer when `$request->input('iies_bname')` returns `null`, which happens when the `iies_bname` key is **absent** from the request. Empty string `""` would not produce NULL; it would be written as `''`.

---

## 6. Transaction Behavior

### 6.1 Transaction Boundaries

| Boundary | File:Line | Behavior |
|----------|-----------|----------|
| **Start** | `ProjectController.php:414` | `DB::beginTransaction()` |
| **Projects INSERT** | `GeneralInfoController` (via `Project::create`) | Inside outer transaction |
| **Key Information** | `ProjectController.php:440` | Inside outer transaction |
| **IIES Personal Info** | `IIESPersonalInfoController->store` | Inside outer transaction (no nested transaction in current code) |
| **Commit** | `ProjectController.php:706` | `DB::commit()` |
| **Rollback** | `ProjectController.php:527-528, 530-531` | On `ValidationException` or general `Exception` |

### 6.2 IIESPersonalInfoController Exception Handling

| File:Line | Code | Behavior |
|-----------|------|----------|
| `IIESPersonalInfoController.php:62-66` | `catch (\Exception $e) { Log::error(...); throw $e; }` | Exception is re-thrown |

**Evidence:** The controller does **not** swallow the exception. It logs and re-throws, so `ProjectController` will catch it and run `DB::rollBack()`.

### 6.3 Rollback on IIES Personal Info Failure

| Event | Result |
|-------|--------|
| IIES Personal Info throws (e.g. DB integrity error) | Exception propagates to `ProjectController` catch block |
| `ProjectController.php:530-531` | `DB::rollBack()` executes |
| **Effect** | Full rollback: projects row, key information, and any prior IIES work are not committed |

**Conclusion:** "No record exists in projects table after submission" is consistent with an exception during IIES Personal Info insert causing full transaction rollback.

---

## 7. Hypothesis Verdict Table

| Hypothesis | Proven / Disproven | Evidence |
|------------|--------------------|----------|
| **Input missing name="iies_bname"** | **Disproven** | `personal_info.blade.php:9` — `<input type="text" name="iies_bname" class="form-control">` has correct name |
| **Field outside &lt;form&gt;** | **Disproven** | `createProjects.blade.php:8,64-72,148` — IIES partial is inside `#createProjectForm` |
| **JS submit omits field** | **Partially Proven (edge case)** | Submit handler enables disabled fields. If JS errors before that, or user submits without JS, or handler does not run, IIES inputs remain disabled and are omitted from POST. |
| **Backend strips field** | **Disproven** | `StoreProjectRequest` has no `only()`/`except()`; no middleware or normalizer that removes `iies_bname` |
| **Conditional UI removes field** | **Partially Proven** | IIES section is in `display:none` div; inputs are disabled until project type is IIES. If project type is not IIES at submit, or if enabled state is not applied, field is omitted. |
| **Validation allows NULL** | **Disproven (current code)** | `ProjectController.php:689-691` validates `iies_bname` as `required|string|max:255` before IIES sub-controllers. Empty or absent fails validation. **Caveat:** Remediation (Phase 0) added this; pre-remediation code did not validate `iies_bname` at orchestration level. |

---

## 8. Single Root Cause (if proven)

**Most likely root cause (by certainty):**

1. **Primary:** `iies_bname` is **absent from the HTTP request** because the input was **disabled** at submit time. Disabled inputs are not submitted (HTML spec). This can occur when:
   - User selects IIES, fills the form, but a JS error prevents the submit handler from enabling disabled fields.
   - User uses browser back/forward and resubmits; state may differ.
   - User submits via a pathway that bypasses the submit handler (e.g. direct form submit, bookmark, or custom script).

2. **Contributing:** **Pre-remediation**, `StoreProjectRequest` did not validate `iies_bname`, and `StoreIIESPersonalInfoRequest` (which has `required` for `iies_bname`) is **never used** in the create flow (`IIESPersonalInfoController` receives `StoreProjectRequest`). So validation did not stop NULL from reaching the DB.

3. **Flow:** Absent key → `$request->input('iies_bname')` returns `null` → `mapRequestToModel` assigns null → `save()` attempts INSERT with NULL → DB rejects with `Column 'iies_bname' cannot be null` → exception propagates → full rollback → no projects row.

---

## 9. Open Questions

1. **Exact reproduction path:** Was the failing submission a regular "Save Project Application" or "Save as Draft"? Both use the same pre-submit handler.

2. **Browser/JS environment:** Were there JS errors or extensions that could block or alter the submit handler?

3. **Code version:** Did the failure occur before the Phase 0 validation (`$request->validate(['iies_bname' => 'required|string|max:255'])`) was added? If so, the validation gap would align with the symptom.

4. **Empty string vs absent:** If the user submitted an empty string, `$request->input('iies_bname')` would return `''`, not `null`. The DB error specifically indicates NULL. Therefore the key was likely **absent**, not present with empty value.

---

## Appendix: File References

| Component | Path |
|-----------|------|
| Create form view | `resources/views/projects/Oldprojects/createProjects.blade.php` |
| IIES personal info partial | `resources/views/projects/partials/IIES/personal_info.blade.php` |
| ProjectController | `app/Http/Controllers/Projects/ProjectController.php` |
| IIESPersonalInfoController | `app/Http/Controllers/Projects/IIES/IIESPersonalInfoController.php` |
| StoreProjectRequest | `app/Http/Requests/Projects/StoreProjectRequest.php` |
| StoreIIESPersonalInfoRequest | `app/Http/Requests/Projects/IIES/StoreIIESPersonalInfoRequest.php` |
| ProjectIIESPersonalInfo model | `app/Models/OldProjects/IIES/ProjectIIESPersonalInfo.php` |
| Migration | `database/migrations/2025_01_29_174348_create_project_i_i_e_s_personal_infos_table.php` |
| Routes | `routes/web.php` |
