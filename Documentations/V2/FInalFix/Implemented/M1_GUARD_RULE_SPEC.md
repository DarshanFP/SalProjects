# M1 — Data Integrity Shield: Unified Guard Rule Specification

**Date:** 2026-02-14  
**Milestone:** 1 — Data Integrity Shield (Skip-Empty-Sections)  
**Objective:** System-wide guard rule specification for all 20 section controllers identified in M1_SCOPE_VERIFICATION.md. No code modified.

---

## 1. Controller Categories (20 total)

### 1.1 Multi-row sections (14 controllers)

One or more rows per project; section payload is typically an array of rows (or keyed arrays that represent rows). Delete wipes all rows for the project; recreate inserts from the array.

| # | Controller | Section key(s) / payload shape |
|---|------------|---------------------------------|
| 1 | BudgetController | `phases`, `phases[0]['budget']` |
| 2 | IIESFamilyWorkingMembersController | `iies_member_name`, `iies_work_nature`, `iies_monthly_income` (arrays) |
| 3 | EduRUTTargetGroupController | Group arrays |
| 4 | EduRUTAnnexedTargetGroupController | Group arrays |
| 5 | LDP TargetGroupController | `L_beneficiary_name`, `L_family_situation`, etc. (arrays) |
| 6 | RST BeneficiariesAreaController | `project_area`, `category_beneficiary`, etc. (arrays) |
| 7 | RST TargetGroupAnnexureController | RST name/group arrays |
| 8 | RST GeographicalAreaController | Mandal/area arrays |
| 9 | IGE IGEBeneficiariesSupportedController | Class/beneficiary arrays |
| 10 | IGE OngoingBeneficiariesController | Beneficiary name arrays |
| 11 | IGE NewBeneficiariesController | `beneficiary_name`, `caste`, etc. (arrays) |
| 12 | IGE IGEBudgetController | Name/amount arrays |
| 13 | IES IESFamilyWorkingMembersController | `member_name`, etc. (arrays) |

### 1.2 Single-row sections (4 controllers)

Exactly one logical row per project; section payload is a flat set of fields. Delete removes the row; recreate inserts one row from request.

| # | Controller | Section key(s) / payload shape |
|---|------------|---------------------------------|
| 14 | ILP RiskAnalysisController | `identified_risks`, `mitigation_measures`, `business_sustainability`, `expected_profits` |
| 15 | IAH IAHSupportDetailsController | Fillable fields from model (e.g. support details) |
| 16 | IAH IAHHealthConditionController | Health condition fillable fields |
| 17 | IAH IAHPersonalInfoController | Personal info fillable fields |

### 1.3 Nested sections (3 controllers)

Parent record(s) plus child record(s). Section payload has a parent level and one or more child arrays. Delete removes parent (and children); recreate builds parent + children from request.

| # | Controller | Section key(s) / payload shape |
|---|------------|---------------------------------|
| 18 | LogicalFrameworkController | `objectives` (each: `objective`, `results[]`, `risks[]`, `activities[]` with `timeframe`) |
| 19 | IIESExpensesController | Parent totals + `iies_particulars[]`, `iies_amounts[]` (or equivalent) |
| 20 | IES IESExpensesController | Parent totals + expense detail arrays |

---

## 2. Guard Rules by Category

### 2.1 Multi-row sections

**A. Section key absent**

- The **canonical section key** (e.g. `phases` for Budget, `iies_member_name` for IIES family members) is **not present** in the request input used by the controller (e.g. `$request->input('phases')` is missing, or the key does not exist).
- **Normalization:** If the controller uses multiple arrays that together define “rows” (e.g. `member_name`, `work_nature`, `monthly_income`), **section key absent** means the **primary** row-defining key is absent (e.g. the array that defines row count). If all such keys are absent, treat as section key absent.

**B. Section present but empty**

- The section key **is present**, but the value is an **empty array** `[]`, or the value is an array where **every element is null/blank/empty** (no row has any meaningful data).
- “Meaningful” for a row: at least one cell (scalar or nested) is non-empty after trim (string) or is a non-null numeric/boolean where the field is used as such.

**C. Valid section data**

- Section key is present **and** the array has **at least one row** with at least one meaningful value (as above).

**D. When delete+recreate should execute**

- Only when the section is **valid section data** (C). Full payload submit (user intentionally sent rows) must behave exactly as today: delete then recreate.

**E. When mutation must be skipped**

- When the **section key is absent** (A), or when the **section is present but empty** (B). Do not run delete; do not create rows. Leave existing DB state unchanged.

**Invariant**

- Empty array **must NOT** wipe data.
- Missing section key **must NOT** wipe data.
- Valid non-empty data **must** still trigger delete+recreate.

---

### 2.2 Single-row sections

**A. Section key absent**

- The **section is not in the request** at all: e.g. the controller’s section is identified by a known key or set of keys; if that key (or all keys that define “this section”) is/are missing, section is absent.
- In practice: if the controller only ever receives its fields when the section form is present, “section key absent” can be implemented as “none of the section’s field keys are present in the request.”

**B. Section present but empty**

- The section’s field keys **are present** (or the request clearly targets this section), but **all** section fields are null, empty string, or blank after trim. No meaningful value in any field.

**C. Valid section data**

- At least **one** section field has a meaningful value (non-empty string after trim, or non-null numeric/boolean where applicable).

**D. When delete+recreate should execute**

- When the section has **valid section data** (C): at least one meaningful field.

**E. When mutation must be skipped**

- **Missing key** (section not in request): skip.  
- **All fields null/blank**: skip.  
- Do not delete or overwrite when the user did not intend to submit this section or submitted it empty.

**Invariant**

- Missing key → skip.  
- All fields null/blank → skip.  
- At least one meaningful field → execute delete+recreate.

---

### 2.3 Nested sections

**A. Section key absent**

- The **parent-level key** (e.g. `objectives`, or the key that carries parent + children for expenses) is **absent** from the request.

**B. Section present but empty**

- Parent key **is present**, but:
  - **Parent** has no meaningful data (all parent fields null/blank/empty), **and**
  - **Children** are absent or empty (no child array, or child array is `[]`, or every child element is empty).
- So: “parent empty AND children empty” → section present but empty.

**C. Valid section data**

- Parent key is present **and** either:
  - at least one **parent** field is meaningful, **or**
  - at least **one valid child** exists (e.g. one objective with non-empty `objective` text, or one expense line with particular and amount).

**D. When delete+recreate should execute**

- When the section has **valid section data** (C): **parent present with at least one valid child** and/or meaningful parent fields. Full payload submit must behave as today.

**E. When mutation must be skipped**

- **Section key absent** (A): skip.  
- **Parent empty AND children empty** (B): skip.  
- Do not delete or recreate when the user did not send the section or sent it fully empty.

**Invariant**

- Parent empty AND children empty → skip.  
- Parent present with at least one valid child (or meaningful parent data) → execute.

---

## 3. Unified Helper Concept

### 3.1 Name and responsibility

**Concept name:** `isSectionMeaningfullyFilled($input, $sectionConfig)`

- **Purpose:** Decide whether the given section input justifies running delete+recreate for that section.
- **Return:** `true` → safe to run delete+recreate (section has valid data). `false` → skip mutation (section absent or present but empty).
- **Input:**
  - `$input`: the raw or normalized request data for the section (array or object).
  - `$sectionConfig`: a small structure that describes the section type (multi-row / single-row / nested) and the keys/rules used for that controller (section key name, row-defining keys, child keys, “meaningful” field list for single-row).

### 3.2 Semantics (no code, specification only)

- **Multi-row:**  
  - If section key is absent in `$input` → return false.  
  - If section key is present and value is not an array → return false.  
  - If value is empty array `[]` → return false.  
  - If value is array: return true if and only if at least one element has at least one meaningful value (per row rule above).

- **Single-row:**  
  - If section keys are absent (per config) → return false.  
  - If all section fields are null/blank → return false.  
  - Return true if and only if at least one section field is meaningful.

- **Nested:**  
  - If parent key is absent → return false.  
  - If parent key is present: treat as “parent + children.” If parent is empty (all parent fields null/blank) and children are absent or empty (no valid child) → return false.  
  - Return true if and only if parent has at least one meaningful field or there is at least one valid child.

### 3.3 Usage in controllers

- At the start of `update()` or `store()` (before any delete):
  - Build the section input (e.g. from `$request->input('phases')`, or the set of section keys).
  - Call the equivalent of `isSectionMeaningfullyFilled($sectionInput, $configForThisSection)`.
  - If false → **return early** (or no-op); do not delete, do not create.
  - If true → proceed with existing delete+recreate logic unchanged (full payload behavior as today).

- Implementation can be a single helper service method, or a small per-category helper; the **behavior** must match this spec.

---

## 4. Examples

### 4.1 Budget (multi-row)

- **Section key:** `phases` (and effectively `phases[0]['budget']` for the current phase’s rows).

**Section key absent**

- `$request->input('phases')` is missing, or request has no `phases` key.  
- **Action:** Skip. Do not delete budget rows; do not create.

**Section present but empty**

- `phases` is `[]`, or `phases` is `[ [] ]`, or `phases[0]['budget']` is missing/`[]`, or every element in `phases[0]['budget']` has no meaningful value (e.g. all `particular` and amount fields empty).  
- **Action:** Skip. Do not wipe existing budget.

**Valid section data**

- `phases` is present, `phases[0]['budget']` is a non-empty array, and at least one budget row has e.g. non-empty `particular` or non-zero amount.  
- **Action:** Execute delete (for current phase) + recreate. Same as current full submit.

**Example payload (valid)**

```json
{
  "phases": [
    {
      "budget": [
        { "particular": "Staff", "this_phase": 10000 },
        { "particular": "Equipment", "this_phase": 5000 }
      ]
    }
  ]
}
```

→ Meaningfully filled; delete+recreate runs.

**Example payload (empty array – must not wipe)**

```json
{
  "phases": []
}
```

or

```json
{
  "phases": [ { "budget": [] } ]
}
```

→ Skip. No delete, no create.

---

### 4.2 LogicalFramework (nested)

- **Section key:** `objectives`. Each objective has `objective`, `results`, `risks`, `activities` (with timeframes).

**Section key absent**

- `$request->input('objectives')` is missing.  
- **Action:** Skip. Do not delete objectives/results/risks/activities.

**Section present but empty**

- `objectives` is `[]`, or every element has empty `objective` and no valid results/risks/activities (all child arrays empty or all text fields blank).  
- **Action:** Skip.

**Valid section data**

- `objectives` is present and at least one objective has: non-empty `objective` text, or at least one result/risk/activity with meaningful content.  
- **Action:** Execute delete+recreate (current behavior).

**Example payload (valid)**

```json
{
  "objectives": [
    {
      "objective": "Improve literacy",
      "results": [ { "result": "X% students pass" } ],
      "risks": [],
      "activities": [ { "activity": "Training", "verification": "Records", "timeframe": { "months": {} } } ]
    }
  ]
}
```

→ At least one valid objective with text; delete+recreate runs.

**Example payload (parent empty AND children empty – skip)**

```json
{
  "objectives": [
    { "objective": "", "results": [], "risks": [], "activities": [] }
  ]
}
```

→ No meaningful parent or child; skip.

---

### 4.3 IES expenses (nested)

- **Section:** Parent (e.g. total fields) + children (e.g. `iies_particulars[]` / `iies_amounts[]` or IES equivalents).

**Section key absent**

- Request has no parent key and no child keys that identify this section (e.g. no expense totals and no particulars/amounts).  
- **Action:** Skip.

**Section present but empty**

- Parent fields are all null/zero/blank and child arrays are missing or empty (no expense lines).  
- **Action:** Skip.

**Valid section data**

- At least one parent field meaningful (e.g. non-zero total) or at least one expense line with particular and amount.  
- **Action:** Execute delete+recreate.

**Example payload (valid)**

```json
{
  "iies_total_expenses": 50000,
  "iies_particulars": ["Tuition", "Books"],
  "iies_amounts": [30000, 20000]
}
```

→ Parent + children meaningful; delete+recreate runs.

**Example payload (empty – skip)**

```json
{
  "iies_total_expenses": null,
  "iies_expected_scholarship_govt": null,
  "iies_particulars": [],
  "iies_amounts": []
}
```

→ Parent and children empty; skip. Existing expenses unchanged.

---

### 4.4 IAH single-row (e.g. Support Details)

- **Section:** Single set of fillable fields (e.g. support type, amount, notes).

**Section key absent**

- None of the section’s field keys appear in the request (user did not submit this section).  
- **Action:** Skip.

**Section present but empty**

- Section keys are present but every field is null or blank.  
- **Action:** Skip.

**Valid section data**

- At least one field has a non-empty or non-null meaningful value.  
- **Action:** Execute delete+recreate.

**Example payload (valid)**

```json
{
  "support_type": "Financial",
  "amount": 5000,
  "notes": "One-time grant"
}
```

→ At least one meaningful field; delete+recreate runs.

**Example payload (all blank – skip)**

```json
{
  "support_type": null,
  "amount": null,
  "notes": ""
}
```

→ Skip. Do not overwrite existing row with empty data.

---

## 5. Requirements Checklist

| Requirement | How it is met |
|-------------|----------------|
| Full payload submit behaves exactly as today | Delete+recreate runs only when `isSectionMeaningfullyFilled` is true; for a full, valid payload it is true, so behavior is unchanged. |
| Empty array must NOT wipe data | Multi-row and nested: empty array or all-empty rows → false → skip. No delete. |
| Missing section key must NOT wipe data | All categories: section key absent → false → skip. No delete. |
| Valid non-empty data must still delete+recreate | When section is meaningfully filled → true → proceed with existing logic. |
| Nested: parent empty AND children empty → skip | Nested rule (B, E): both conditions checked; skip when both hold. |
| Nested: parent present with at least one valid child → execute | Nested rule (C, D): execute when parent or any child is valid. |
| Single-row: missing key → skip | Single-row rule (A, E). |
| Single-row: all fields null/blank → skip | Single-row rule (B, E). |
| Single-row: at least one meaningful field → execute | Single-row rule (C, D). |

---

## 6. Summary

- **20 controllers** are grouped into **multi-row (14)**, **single-row (4)**, and **nested (3)**.
- Each category has a clear definition of: **section key absent**, **section present but empty**, **valid section data**, **when to execute** delete+recreate, and **when to skip** mutation.
- The unified helper concept **`isSectionMeaningfullyFilled($input, $sectionConfig)`** drives the decision at the start of each section’s update/store; when it returns false, the controller skips delete and create and leaves existing data intact.
- Examples for Budget, LogicalFramework, IES expenses, and IAH single-row illustrate the rules and show that full payloads still run delete+recreate while empty arrays and missing keys do not wipe data.

*End of M1 Guard Rule Spec. No code was modified.*
