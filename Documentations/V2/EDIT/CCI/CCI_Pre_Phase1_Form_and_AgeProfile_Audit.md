# CCI Pre-Phase 1 Form and Age Profile Audit

**Date:** March 3, 2026  
**Mode:** Audit only — no code modifications

---

# PART 1 — CCI Annexed Target Group

## 1.1 Form Structure

### Edit form
- **File:** `resources/views/projects/partials/Edit/CCI/annexed_target_group.blade.php`
- **Input names:** `annexed_target_group[{{ $index }}][field]` (e.g. `annexed_target_group[0][beneficiary_name]`)
- **Initial rows:** From `@foreach ($targetGroup as $index => $group)` — indices 0, 1, 2, …
- **Add row:** `annexed_target_group[${annexedRowIndex}][field]` — index continues from `count($targetGroup)`

### Create form
- **File:** `resources/views/projects/partials/CCI/annexed_target_group.blade.php`
- **Initial row:** `annexed_target_group[0][beneficiary_name]`, etc.
- **Add row:** Same pattern; `annexedRowIndex` starts at 1

### Input name structure
| Field | Name pattern |
|-------|--------------|
| Beneficiary name | `annexed_target_group[index][beneficiary_name]` |
| DOB | `annexed_target_group[index][dob]` |
| Date of joining | `annexed_target_group[index][date_of_joining]` |
| Class of study | `annexed_target_group[index][class_of_study]` |
| Family background | `annexed_target_group[index][family_background_description]` |

### Array grouping
- Yes — all inputs use `annexed_target_group[index][field]`.

### Dynamic row JS
- `updateInputNames()` renames indices after removal: `name.replace(/\[\d+\]/, \`[${index}]\`)` — keeps sequential indices.
- `annexedRowIndex` is updated on add/remove.
- Indexes remain sequential and gapless after delete.

### Empty rows
- Empty rows are submitted. Form does not skip or hide empty rows.
- Controller: `if (!is_array($group)) continue` — skips non-array entries; empty rows with all null/empty values are still iterated and passed to `updateOrCreate`.

---

## 1.2 Validation

### Form Request classes
- **Store:** `StoreCCIAnnexedTargetGroupRequest` — rules present.
- **Update:** `UpdateCCIAnnexedTargetGroupRequest` — rules present.

### Rules
```php
'annexed_target_group' => 'array',
'annexed_target_group.*.beneficiary_name' => 'nullable|string|max:255',
'annexed_target_group.*.dob' => 'nullable|date',
'annexed_target_group.*.date_of_joining' => 'nullable|date',
'annexed_target_group.*.class_of_study' => 'nullable|string|max:255',
'annexed_target_group.*.family_background_description' => 'nullable|string',
```

### Controller usage
- Store/update use `FormRequest $request`.
- Controller uses `$request->only(['annexed_target_group'])` and does **not** use `$request->validated()`.
- Invoked from `ProjectController@update`, which uses `UpdateProjectRequest`; validation of `annexed_target_group` depends on `UpdateProjectRequest` including these rules (not confirmed in this audit).

---

## 1.3 Controller

- Uses `$data['annexed_target_group']` — expects array of rows.
- Normalizes: `is_array($data['annexed_target_group']) ? ... : ...`.
- Extracts fields with array/scalar handling: `is_array($group['beneficiary_name'] ?? null) ? reset(...) : ($group['beneficiary_name'] ?? null)`.
- **Update:** `updateOrCreate(['project_id' => $projectId, 'beneficiary_name' => $beneficiaryName], [...])`.

---

## 1.4 Mismatches and risks

| Item | Status |
|------|--------|
| Input names vs controller access | Match — `annexed_target_group[index][field]` |
| Partial row submission | Possible — user can remove some fields; controller skips non-arrays, iterates all submitted rows |
| Null-row persistence | Risk — empty rows produce `beneficiary_name = ''`; `updateOrCreate` uses `(project_id, beneficiary_name)` as match; multiple empty rows can overwrite the same record |

---

## 1.5 Summary

| Criterion | Result |
|-----------|--------|
| **Form structure** | VALID |
| **Validation structure** | VALID (Form Requests have array rules) |
| **Controller expects array?** | YES |
| **Risk summary** | 1) Controller does not use `validated()`. 2) `updateOrCreate` with non-unique `beneficiary_name` causes overwrites for multiple blank rows. 3) Empty rows are submitted and may persist. |
| **Safe to proceed with delete-recreate?** | YES |

---

# PART 2 — CCI Age Profile Audit

## 2.1 Files

| Component | File |
|-----------|------|
| Update Request | `app/Http/Requests/Projects/CCI/UpdateCCIAgeProfileRequest.php` |
| Store Request | `app/Http/Requests/Projects/CCI/StoreCCIAgeProfileRequest.php` |
| Controller | `app/Http/Controllers/Projects/CCI/AgeProfileController.php` |
| Model | `app/Models/OldProjects/CCI/ProjectCCIAgeProfile.php` |
| Migration | `database/migrations/2024_10_20_234519_create_project_c_c_i_age_profiles_table.php` (table: `project_CCI_age_profile`) |

---

## 2.2 Schema vs validation

### DB columns (from migration)
- `education_below_5_bridge_course_prev_year`, `_current_year`
- `education_below_5_kindergarten_prev_year`, `_current_year`
- `education_below_5_other_prev_year`, `education_below_5_other_current_year` (string)
- `education_6_10_primary_school_*`, `education_6_10_bridge_course_*`, `education_6_10_other_*`
- `education_11_15_secondary_school_*`, `education_11_15_high_school_*`, `education_11_15_other_*`
- `education_16_above_undergraduate_*`, `education_16_above_technical_vocational_*`, `education_16_above_other_*`

### Missing in migration
- `education_below_5_other_specify` — not present in migration (edit form sends it).

### Validation rules (UpdateCCIAgeProfileRequest, StoreCCIAgeProfileRequest)
- Only 7 fields: `education_below_5_*` (including `education_below_5_other_specify`).
- `education_6_10_*`, `education_11_15_*`, `education_16_above_*` — no rules.

### Missing validation
18 fields: all `education_6_10_*`, `education_11_15_*`, `education_16_above_*` (6+6+6).

### Model fillable
- Model has all age profile columns in `$fillable`.
- `education_below_5_other_specify` is not in model `$fillable` (and not in migration).

---

## 2.3 Integer normalization

- **Normalized:** Only `INTEGER_KEYS` (7 fields under “below 5”) via `PlaceholderNormalizer`.
- **Not normalized:** All `education_6_10_*`, `education_11_15_*`, `education_16_above_*`.

---

## 2.4 Mass-assignment vs validation

- Fields in fillable but not validated: all 18 fields for 6–10, 11–15, 16+.
- Controller uses only `$validated`; those fields never reach `updateOrCreate`.
- **Result:** 6–10, 11–15, 16+ fields are not saved.

---

## 2.5 Controller behavior

- Uses `$validator->validated()` only.
- `updateOrCreate(['project_id' => $projectId], $validated)` — single-row by project.
- **edit():** Returns `ProjectCCIAgeProfile::where('project_id', $projectId)->first()` — can return `null`.
- **show():** Returns `toArray()` when record exists; returns default array when not.

---

## 2.6 Return types

| Method | Return type | Notes |
|--------|-------------|-------|
| show() | Array | `toArray()` or default keys |
| edit() | Object or null | Model or null when no record |

---

## 2.7 View null safety

### Edit view
- Uses `$ageProfile->education_below_5_bridge_course_prev_year ?? 0` (object access).
- If `$ageProfile` is null → `$ageProfile->...` causes error.
- **Risk:** RISK when no age profile exists.

### Show view
- Uses `$ageProfile['key'] ?? 'N/A'` (array access).
- Show always receives an array (controller provides default when no record).

---

## 2.8 Dependencies

| Item | Status |
|------|--------|
| SoftDeletes | Not used |
| Observers | None |
| Boot hook | Only `CCI_age_profile_id` generation on create |
| FK constraints | None in migration |
| Export | ExportController `addAgeProfileSection` uses `$project->age_profile` (Project has `cciAgeProfile()`, not `age_profile`) — possible relation mismatch |
| Hydrator | Uses `cciAgeProfileController->show()` and passes as `ageProfile` |

---

## 2.9 Architectural notes

- Pattern: single row per project, match on `project_id`.
- Inconsistency: Show uses array, edit uses object; edit can receive null.
- Data loss: 18 fields (6–10, 11–15, 16+) not validated or saved.

---

## 2.10 Summary

| Criterion | Result |
|-----------|--------|
| **Schema completeness** | Missing field: `education_below_5_other_specify` in migration/model fillable |
| **Validation completeness** | Incomplete — 18 fields missing validation |
| **Controller integrity** | RISK — only validated fields are saved; 18 fields never reach DB |
| **View null safety** | RISK — edit view assumes non-null `$ageProfile` |
| **Pattern alignment (single-row)** | YES |
| **Safe to implement Phase 1?** | NO |
| **Blocking issues** | 1) Add validation for 6–10, 11–15, 16+ fields. 2) Ensure edit view handles null `$ageProfile`. 3) Add `education_below_5_other_specify` to migration and fillable if required. 4) Verify ExportController relation (`age_profile` vs `cciAgeProfile`). |
