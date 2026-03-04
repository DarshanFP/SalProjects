# AGE PROFILE RUNTIME DEBUG REPORT

---

## STEP 1 — CONTROLLER UPDATE FLOW

**updateOrCreate call (AgeProfileController lines 45–47):**

```php
ProjectCCIAgeProfile::updateOrCreate(
    ['project_id' => $projectId],
    $validated
);
```

**Findings:**
- `$validated` comes from `$validator->validated()` (line 41).
- Match condition: `['project_id' => $projectId]`.
- Second argument: full `$validated` array.
- No manual filtering or casting; whatever is in `$validated` is passed through.
- **Issue:** `$validated` only includes keys that have rules. Fields without rules are never in `$validated` and never persisted.

---

## STEP 2 — VALIDATED DATA TRACE

**Rules coverage:**
- **16 integer fields** via `INTEGER_KEYS` + `OptionalIntegerRule`.
- **4 string fields** via `nullable|string|max:255`: all `*_other_specify` columns.
- **Total: 20 validated fields.**

**Fields with no validation rules (never in `$validated`):**
- education_below_5_other_prev_year
- education_below_5_other_current_year
- education_6_10_other_prev_year
- education_6_10_other_current_year
- education_11_15_other_prev_year
- education_11_15_other_current_year
- education_16_above_other_prev_year
- education_16_above_other_current_year

**Effect:** These 8 fields are in the model `$fillable` and the form, but they are never in `$validated` and therefore never written to the database.

**Behavior for numeric fields:**

| Submitted value | After normalization | After validation | Stored value |
|-----------------|---------------------|------------------|--------------|
| `""`            | `null` (via normalizeToNull) | passes `nullable` | `null` |
| `"0"`           | `"0"` (unchanged)           | passes OptionalIntegerRule | `0` |
| `"N/A"`         | `null` (placeholder)        | passes `nullable` | `null` |
| `"-"`           | `null` (placeholder)        | passes `nullable` | `null` |
| `"6"`           | `"6"` (unchanged)           | passes OptionalIntegerRule | `6` |

---

## STEP 3 — PLACEHOLDER NORMALIZATION

**PlaceholderNormalizer::normalizeToNull():**
- Converts `"N/A"` → `null`
- Converts `"-"` → `null`
- Converts `""` → `null`
- Does **not** convert `"0"` → `null`; `0` remains `0`

**Final stored value:**

| Input  | Output |
|--------|--------|
| `""`   | `null` |
| `"0"`  | `0`    |
| `"N/A"` | `null` |
| `"6"`  | `6`    |

**Note:** Normalization runs only on `INTEGER_KEYS`. The 8 string fields above are not normalized and are also missing validation rules.

---

## STEP 4 — EDIT() RETURN

- Uses `ProjectCCIAgeProfile::firstOrNew(['project_id' => $projectId])`.
- Returns either an existing model or a new, unsaved one with `project_id` set.
- No defaults or transformation before returning.
- Edit view always receives a model instance (no null).

---

## STEP 5 — EDIT VIEW

**File:** `resources/views/projects/partials/Edit/CCI/age_profile.blade.php`

**Value usage:**
- Numeric: `{{ $ageProfile->field ?? 0 }}`
- Text: `{{ $ageProfile->field ?? '' }}`

**Findings:**
- No hardcoded `"N/A"`.
- Uses `old()` only implicitly via form state; no explicit `old('field')`.
- Correct fallbacks for null: `0` for numbers, `''` for strings.

---

## STEP 6 — SHOW VIEW

**File:** `resources/views/projects/partials/Show/CCI/age_profile.blade.php`

**Value usage:**
- Uses array access: `{{ $ageProfile['field'] ?? 'N/A' }}`
- Integer/string fields: `?? 'N/A'` when null or missing.
- `*_other_specify`: `?? 'Other'`.

**Findings:**
- Any null or missing value is shown as `"N/A"`.
- That is the intended source of `"N/A"` in the show view.
- Show receives an array (from `show()` via `toArray()` or fallback).

**show() fallback array (lines 66–74):** Only 7 keys. Missing all `education_6_10_*`, `education_11_15_*`, `education_16_above_*`. Those missing keys produce `"N/A"` via `?? 'N/A'`.

---

## STEP 7 — DATABASE (LOGICAL)

**Expected storage:**
- Integer fields: `null` or integer (0, 1, 2, …).
- `*_other_specify`: strings if validated and persisted.
- The 8 `*_other_prev_year` / `*_other_current_year` string columns are never updated because they never appear in `$validated`. On create they stay null; on update they keep old values.

---

## SUMMARY

| Issue Type | Status |
|------------|--------|
| Controller persistence | **YES** — `updateOrCreate` only receives `$validated`, which omits 8 string fields |
| Validation | **YES** — 8 string columns have no rules → never in `$validated` |
| Normalization | **NO** — Normalization behaves as intended; the problem is missing validation rules |
| Edit view rendering | **NO** — Edit view uses model and correct fallbacks |
| Show view rendering | **NO** — `"N/A"` is by design for null/missing values |
| DB storage mismatch | **YES** — 8 string columns are never written because they are never in `$validated` |

---

## ROOT CAUSE

1. **Main cause:** The 8 `*_other_prev_year` and `*_other_current_year` columns have no validation rules.
2. `$validator->validated()` returns only keys that have rules, so those 8 keys are excluded.
3. `updateOrCreate` receives only `$validated`, so those fields are never saved.
4. Show view shows `"N/A"` for null/missing values, which is expected when:
   - Those values were never persisted (validation gap), or
   - No record exists and the fallback array is incomplete.

---

## RECOMMENDED FIXES

**Primary fix — Validation:**

Add validation rules for the 8 string columns in both `StoreCCIAgeProfileRequest` and `UpdateCCIAgeProfileRequest`, e.g.:

- `education_below_5_other_prev_year` → `'nullable|string|max:255'`
- `education_below_5_other_current_year` → `'nullable|string|max:255'`
- `education_6_10_other_prev_year` → `'nullable|string|max:255'`
- `education_6_10_other_current_year` → `'nullable|string|max:255'`
- `education_11_15_other_prev_year` → `'nullable|string|max:255'`
- `education_11_15_other_current_year` → `'nullable|string|max:255'`
- `education_16_above_other_prev_year` → `'nullable|string|max:255'`
- `education_16_above_other_current_year` → `'nullable|string|max:255'`

**Secondary fix — show() fallback:**

Extend the fallback array in `show()` to include all Age Profile keys used by the Show view (including `education_6_10_*`, `education_11_15_*`, `education_16_above_*`), so missing-record views are consistent.

**No changes needed for:**

- Controller logic (beyond what fixing `$validated` implies)
- Normalization
- Edit blade
- Show blade’s `?? 'N/A'` usage
