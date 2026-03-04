# AGE PROFILE STRUCTURAL ALIGNMENT REPORT

**Date:** March 3, 2026  
**Mode:** Audit only — no code modifications

---

# PART 1 — Schema Consistency

## 1.1 Migration Location

- **File:** `database/migrations/2024_10_20_234519_create_project_c_c_i_age_profiles_table.php`
- **Exact table name:** `project_CCI_age_profile`

## 1.2 Existing Column Types for "Other" Fields

| Column | Migration Type |
|--------|----------------|
| `education_below_5_other_prev_year` | `string` (nullable) |
| `education_below_5_other_current_year` | `string` (nullable) |
| `education_6_10_other_prev_year` | `string` (nullable) |
| `education_6_10_other_current_year` | `string` (nullable) |
| `education_11_15_other_prev_year` | `string` (nullable) |
| `education_11_15_other_current_year` | `string` (nullable) |
| `education_16_above_other_prev_year` | `string` (nullable) |
| `education_16_above_other_current_year` | `string` (nullable) |

## 1.3 System Conventions for Similar Fields

- **EduRUT/RST/other CCI migrations:** No `other_specify` columns found in any migration.
- **CCI Age Profile:** Only `*_other_prev_year` and `*_other_current_year` exist; no `*_other_specify`.
- **Other short-text fields:** Typically `string` (nullable), often `max:255` in validation.

## 1.4 Recommended Column Definition for `education_*_other_specify`

| Column | Recommendation | Reasoning |
|--------|----------------|-----------|
| `education_below_5_other_specify` | `$table->string('education_below_5_other_specify', 255)->nullable();` | Matches validation `nullable\|string\|max:255`; aligns with existing string fields; form uses it as a short description. |
| `education_6_10_other_specify` | Same pattern | Same role as other “Other” rows. |
| `education_11_15_other_specify` | Same pattern | Same role. |
| `education_16_above_other_specify` | Same pattern | Same role. |

**Type:** `string` — not `text` (short labels) or `integer` (descriptions).

---

# PART 2 — Validation vs Schema Full Diff

## 2.1 All DB Columns (project_CCI_age_profile)

| Column | Type |
|--------|------|
| id | bigint |
| CCI_age_profile_id | string |
| project_id | string |
| education_below_5_bridge_course_prev_year | integer |
| education_below_5_bridge_course_current_year | integer |
| education_below_5_kindergarten_prev_year | integer |
| education_below_5_kindergarten_current_year | integer |
| education_below_5_other_prev_year | string |
| education_below_5_other_current_year | string |
| education_6_10_primary_school_prev_year | integer |
| education_6_10_primary_school_current_year | integer |
| education_6_10_bridge_course_prev_year | integer |
| education_6_10_bridge_course_current_year | integer |
| education_6_10_other_prev_year | string |
| education_6_10_other_current_year | string |
| education_11_15_secondary_school_prev_year | integer |
| education_11_15_secondary_school_current_year | integer |
| education_11_15_high_school_prev_year | integer |
| education_11_15_high_school_current_year | integer |
| education_11_15_other_prev_year | string |
| education_11_15_other_current_year | string |
| education_16_above_undergraduate_prev_year | integer |
| education_16_above_undergraduate_current_year | integer |
| education_16_above_technical_vocational_prev_year | integer |
| education_16_above_technical_vocational_current_year | integer |
| education_16_above_other_prev_year | string |
| education_16_above_other_current_year | string |
| created_at | timestamp |
| updated_at | timestamp |

**Total data columns (excluding id, timestamps, CCI_age_profile_id, project_id):** 22

## 2.2 All Validation Rules (Store & Update)

| Field | Rule |
|-------|------|
| education_below_5_other_specify | nullable\|string\|max:255 |
| education_below_5_bridge_course_prev_year | nullable, OptionalIntegerRule |
| education_below_5_bridge_course_current_year | nullable, OptionalIntegerRule |
| education_below_5_kindergarten_prev_year | nullable, OptionalIntegerRule |
| education_below_5_kindergarten_current_year | nullable, OptionalIntegerRule |
| education_below_5_other_prev_year | nullable, OptionalIntegerRule |
| education_below_5_other_current_year | nullable, OptionalIntegerRule |

**Total validated fields:** 7

## 2.3 Diff Summary

| Category | Fields |
|----------|--------|
| **Missing validation (in schema, not in rules)** | education_6_10_primary_school_prev_year, education_6_10_primary_school_current_year, education_6_10_bridge_course_prev_year, education_6_10_bridge_course_current_year, education_6_10_other_prev_year, education_6_10_other_current_year, education_11_15_secondary_school_prev_year, education_11_15_secondary_school_current_year, education_11_15_high_school_prev_year, education_11_15_high_school_current_year, education_11_15_other_prev_year, education_11_15_other_current_year, education_16_above_undergraduate_prev_year, education_16_above_undergraduate_current_year, education_16_above_technical_vocational_prev_year, education_16_above_technical_vocational_current_year, education_16_above_other_prev_year, education_16_above_other_current_year (18 fields) |
| **Extra validation (in rules, not in schema)** | education_below_5_other_specify (validated but no DB column) |
| **Fields in schema but not in fillable** | None — all data columns are in fillable |
| **Fields in fillable but not validated** | education_6_10_*, education_11_15_*, education_16_above_* (18 fields); education_below_5_other_specify is validated but not in fillable (and not in schema) |

---

# PART 3 — Integer Normalization Audit

## 3.1 Integer Columns in Migration

| Column |
|--------|
| education_below_5_bridge_course_prev_year |
| education_below_5_bridge_course_current_year |
| education_below_5_kindergarten_prev_year |
| education_below_5_kindergarten_current_year |
| education_6_10_primary_school_prev_year |
| education_6_10_primary_school_current_year |
| education_6_10_bridge_course_prev_year |
| education_6_10_bridge_course_current_year |
| education_11_15_secondary_school_prev_year |
| education_11_15_secondary_school_current_year |
| education_11_15_high_school_prev_year |
| education_11_15_high_school_current_year |
| education_16_above_undergraduate_prev_year |
| education_16_above_undergraduate_current_year |
| education_16_above_technical_vocational_prev_year |
| education_16_above_technical_vocational_current_year |

**Total integer columns:** 16

## 3.2 INTEGER_KEYS Constant

```php
[
    'education_below_5_bridge_course_prev_year',
    'education_below_5_bridge_course_current_year',
    'education_below_5_kindergarten_prev_year',
    'education_below_5_kindergarten_current_year',
    'education_below_5_other_prev_year',        // schema: STRING
    'education_below_5_other_current_year',     // schema: STRING
]
```

## 3.3 Gaps

| Issue | Fields |
|-------|--------|
| **Missing from normalization (integer in schema)** | education_6_10_primary_school_prev_year, education_6_10_primary_school_current_year, education_6_10_bridge_course_prev_year, education_6_10_bridge_course_current_year, education_11_15_secondary_school_prev_year, education_11_15_secondary_school_current_year, education_11_15_high_school_prev_year, education_11_15_high_school_current_year, education_16_above_undergraduate_prev_year, education_16_above_undergraduate_current_year, education_16_above_technical_vocational_prev_year, education_16_above_technical_vocational_current_year (12 fields) |
| **Included in INTEGER_KEYS but schema is string** | education_below_5_other_prev_year, education_below_5_other_current_year |

Note: PlaceholderNormalizer::normalizeToNull converts empty/placeholder to null for any type. OptionalIntegerRule accepts numeric or empty. So the string columns in INTEGER_KEYS still get input normalization; the naming is misleading.

---

# PART 4 — Controller Safety

## 4.1 edit() Method

```php
$ageProfile = ProjectCCIAgeProfile::where('project_id', $projectId)->first();
return $ageProfile;
```

- **Can return null?** Yes — when no row exists, `first()` returns `null`.

## 4.2 View Assumption

- Edit view uses `$ageProfile->education_below_5_bridge_course_prev_year` (object access).
- If `$ageProfile` is null → `$ageProfile->...` causes error.

## 4.3 Minimal Safe Options

| Option | Approach | Pros / Cons |
|--------|----------|-------------|
| firstOrNew | `ProjectCCIAgeProfile::firstOrNew(['project_id' => $projectId])` | Returns unsaved model with defaults; view gets object; no null. |
| Default object | Return `new ProjectCCIAgeProfile()` when `first()` is null | Same shape as model; no DB hit for new. |
| Default array | Return `[]` or keyed array when null; change view to array access | Matches show(); edit and show consistent; requires view change. |

**Recommendation:** Use `firstOrNew(['project_id' => $projectId])` so the view always receives an object; no change to view syntax.

---

# FINAL OUTPUT

| Criterion | Result |
|-----------|--------|
| **Schema change required** | YES |
| **Recommended column definition** | Add `education_below_5_other_specify`, `education_6_10_other_specify`, `education_11_15_other_specify`, `education_16_above_other_specify` as `string(255)->nullable()` to match form and validation |
| **Validation gaps** | 18 fields missing validation; 1 validated field (`education_below_5_other_specify`) not in schema; 3 additional `*_other_specify` fields in form but not validated |
| **Normalization gaps** | 12 integer columns not in INTEGER_KEYS; 2 string columns incorrectly in INTEGER_KEYS |
| **Model fillable gaps** | Add 4 `*_other_specify` columns to fillable when added to schema |
| **Null safety issue** | YES — edit() can return null; view expects object |
| **Safe to implement after alignment?** | NO — requires: (1) migration for 4 `*_other_specify` columns, (2) validation for 18+ fields, (3) INTEGER_KEYS / normalization alignment, (4) edit() null safety (e.g. firstOrNew) |
