# CCI Annexed Target Group & Age Profile Discrepancy Findings

**Date:** March 3, 2026  
**Source:** User-reported issues for CCI project edit/create/view  
**Affected sections:**
1. Annexed Target Group (CCI) — beneficiary name not saving or not visible
2. Age Profile of Children in the Institution — data not being created/updated, not visible in view or edit

---

## 1. Annexed Target Group (CCI) — Beneficiary Name Issue

### 1.1 User Report

> First row, first column (beneficiary name) is not being saved, or if saved, not visible in view or edit.

### 1.2 Root Cause Analysis

#### A. Flawed `updateOrCreate` Match Logic

**File:** `app/Http/Controllers/Projects/CCI/AnnexedTargetGroupController.php` (lines 88–96)

```php
ProjectCCIAnnexedTargetGroup::updateOrCreate(
    ['project_id' => $projectId, 'beneficiary_name' => $beneficiaryName],
    ['dob' => $dob, 'date_of_joining' => $dateOfJoining, ...]
);
```

- The controller uses `project_id` + `beneficiary_name` as the match criteria.
- `beneficiary_name` is **not unique** and can be **null or empty**.
- **Problem 1:** Multiple rows with `beneficiary_name` = `''` or `null` all match the same record, causing overwrites.
- **Problem 2:** Rows are matched by name instead of a stable row identifier, so updates can target the wrong record.
- **Problem 3:** No stable row ID (e.g. `CCI_target_group_id`) is passed from the form, so the controller cannot reliably “update row X”.

#### B. Data Inconsistency

- Logs show records with `beneficiary_name: null` (e.g. CCI-TG-0041).
- When multiple rows have blank names, `updateOrCreate` collapses them into a single row, producing data loss and confusion about which row “has” a name.

### 1.3 Variable Naming Consistency

- **Edit view:** uses `$targetGroup` (from `ProjectController` edit flow).
- **Show view:** uses `$annexedTargetGroup`.
- Both come from the same controller and represent the same data; naming differs but is consistent with how data is passed.

### 1.4 Recommendations

| # | Recommendation |
|---|----------------|
| 1 | Stop using `updateOrCreate` with `beneficiary_name` as part of the match. Switch to one of: (a) delete-all-then-recreate (like EduRUT), or (b) pass hidden `CCI_target_group_id` per row and match on that. |
| 2 | For new rows, use `create()`; for existing rows, use `update()` with `CCI_target_group_id` (or row id) from the form. |
| 3 | Add hidden inputs: `annexed_target_group[{index}][id]` or `annexed_target_group[{index}][CCI_target_group_id]` for existing rows. |
| 4 | If keeping `updateOrCreate`, ensure a unique match (e.g. `id` or `CCI_target_group_id`), not `beneficiary_name`. |

---

## 2. Age Profile of Children in the Institution — Data Not Saved/Visible

### 2.1 User Report

> Data for the complete section is neither being created nor updated; unable to view in view or edit.

### 2.2 Root Cause — Incomplete Validation Rules

**Files:**

- `app/Http/Requests/Projects/CCI/UpdateCCIAgeProfileRequest.php`
- `app/Http/Requests/Projects/CCI/StoreCCIAgeProfileRequest.php`

Both requests only define rules for 7 fields (children below 5):

| Field | In rules? |
|-------|-----------|
| `education_below_5_bridge_course_prev_year` | Yes |
| `education_below_5_bridge_course_current_year` | Yes |
| `education_below_5_kindergarten_prev_year` | Yes |
| `education_below_5_kindergarten_current_year` | Yes |
| `education_below_5_other_specify` | Yes |
| `education_below_5_other_prev_year` | Yes |
| `education_below_5_other_current_year` | Yes |
| `education_6_10_primary_school_*`, `education_6_10_bridge_course_*`, `education_6_10_other_*` | **No** |
| `education_11_15_secondary_school_*`, `education_11_15_high_school_*`, `education_11_15_other_*` | **No** |
| `education_16_above_undergraduate_*`, `education_16_above_technical_vocational_*`, `education_16_above_other_*` | **No** |

### 2.3 Effect on Create/Update

**AgeProfileController** uses:

```php
$validated = $validator->validated();
ProjectCCIAgeProfile::updateOrCreate(['project_id' => $projectId], $validated);
```

`validated()` returns only keys defined in the rules. As a result:

- Only the 7 “below 5” fields are in `$validated`.
- All `education_6_10_*`, `education_11_15_*`, and `education_16_above_*` fields are never saved.

The model `ProjectCCIAgeProfile` and DB have these columns, but the request excludes them.

### 2.4 Edit Form vs. Validation

The edit form (`resources/views/projects/partials/Edit/CCI/age_profile.blade.php`) has inputs for all age categories:

- Below 5 years
- 6–10 years
- 11–15 years
- 16 and above

Those extra fields are submitted but are not in the Form Request rules, so they are dropped before `updateOrCreate`.

### 2.5 Show View Behavior

- **Show view:** expects `$ageProfile` as an array (from `show()` → `toArray()`).
- **Edit view:** expects `$ageProfile` as an object (model instance).
- If no row exists, `edit()` returns `null`; the edit view uses `$ageProfile->...` without a null check, which will error when `$ageProfile` is null.

### 2.6 Recommendations

| # | Recommendation |
|---|----------------|
| 1 | Add validation rules for all Age Profile fields in both `StoreCCIAgeProfileRequest` and `UpdateCCIAgeProfileRequest`: `education_6_10_*`, `education_11_15_*`, `education_16_above_*`. |
| 2 | Extend `INTEGER_KEYS` and normalization so all numeric fields are handled consistently. |
| 3 | Ensure `edit()` never passes `null` to the view: return a new/empty model or default array when no record exists. |
| 4 | Align show and edit data shapes (object vs array) or add safe access (`$ageProfile?->...` / `$ageProfile['key'] ?? null`) where needed. |

---

## 3. Summary of Fixes Required

| Section | Issue | Fix |
|---------|-------|-----|
| Annexed Target Group | `updateOrCreate` uses `beneficiary_name` as match, causing overwrites | Use row id or delete-and-recreate strategy |
| Annexed Target Group | No stable row id passed from form | Add hidden inputs for `id` or `CCI_target_group_id` per row |
| Age Profile | Form Request rules cover only 7 “below 5” fields | Add rules for 6–10, 11–15, 16+ fields |
| Age Profile | `edit()` can return `null` | Return fallback object/array when no record exists |
| Age Profile | Show vs edit data shape | Normalize object vs array and add null safety |

---

## 4. Files to Modify

| File | Changes |
|------|---------|
| `app/Http/Controllers/Projects/CCI/AnnexedTargetGroupController.php` | Replace `updateOrCreate` logic with id-based update or delete-and-recreate |
| `resources/views/projects/partials/Edit/CCI/annexed_target_group.blade.php` | Add hidden `id` or `CCI_target_group_id` for existing rows |
| `app/Http/Requests/Projects/CCI/UpdateCCIAgeProfileRequest.php` | Add rules for all Age Profile fields |
| `app/Http/Requests/Projects/CCI/StoreCCIAgeProfileRequest.php` | Add rules for all Age Profile fields |
| `app/Http/Controllers/Projects/CCI/AgeProfileController.php` | Provide non-null default when no record exists in `edit()` |
