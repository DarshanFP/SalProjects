# Validation & Normalization Layer Design

## Why This Layer Is Required

### Production Failures Observed

Production log review (January 29–31, 2026) documented 59 unique errors across 10 distinct issue categories. The most severe were:

| Issue | Type | Root Cause |
|-------|------|------------|
| IIES Expenses NOT NULL | DB constraint | `?? 0` fails when key exists with empty string; explicit NULL reaches DB |
| Budget numeric overflow | DB constraint | No `max` validation; frontend calculation produces values > DECIMAL(10,2) range |
| IES Attachments | Type mismatch | View uses `name="field[]"` (array); backend expects single file object |
| CCI Statistics invalid integer | DB constraint | Literal `-` (placeholder) inserted into integer column |
| IIES Financial Support NOT NULL | DB constraint | Boolean column receives explicit NULL despite `(int) ?? 0` |
| Logical Framework | Undefined key | `$activityData['activity']` accessed without existence check |

### Risks of Current Approach

1. **Database acting as first validator** – Errors surface as SQLSTATE exceptions instead of user-friendly validation messages.
2. **Inconsistent null/empty handling** – `??` only handles missing keys; empty string `""` and placeholder `"-"` pass through.
3. **Frontend-backend contract violations** – Blade views and JavaScript assumptions diverge from controller expectations.
4. **No single source of truth** – Validation scattered across FormRequests, inline controller rules, Blade `required`, and DB constraints.
5. **Regression risk** – New project types and flows added without aligned validation/normalization.

---

## Current State Analysis

### Validation Today

#### Where Validation Exists

| Location | Models/Tables | CRUD | What It Validates | What It Does NOT Validate |
|---------|---------------|------|------------------|---------------------------|
| **FormRequest (Projects)** | | | | |
| StoreProjectRequest | projects | Create | project_type, numeric ranges, exists | IIES/CCI/IES sub-form fields |
| UpdateProjectRequest | projects | Update | Same as store | Same gaps |
| StoreGeneralInfoRequest | projects | Create/Update | project_type, coordinators, dates | commencement_month_year composite |
| StoreBudgetRequest | project_budgets | Create | phases.*.budget.* numeric min:0 | **max bounds** (DECIMAL overflow) |
| StoreKeyInformationRequest | projects | Create | goal required | Other key_info fields |
| StoreIIESExpensesRequest | project_IIES_expenses | Create | nullable numeric min:0 | **NOT NULL enforcement**; empty string → 0 |
| StoreCCIStatisticsRequest | project_CCI_statistics | Create | nullable integer min:0 | **Placeholder values** (-, N/A) |
| StoreCCIStatisticsRequest (Update) | project_CCI_statistics | Update | Same | Same |
| **Inline Controller** | | | | |
| BudgetController | project_budgets | Store/Update | phases structure, min:0 | max; type coercion |
| KeyInformationController | projects | Store/Update | goal, key_info fields | Word count; placeholder handling |
| LogicalFrameworkController | objectives, activities | Store/Update | project_id, objectives array | **activity key existence**; nested structure |
| ProvincialController | users, provincials | CRUD | name, email, password, etc. | Center existence; role constraints |
| GeneralController | users | CRUD | Similar to Provincial | Similar gaps |
| ReportController (Monthly) | reports | Create/Update | FormRequest (Store/UpdateMonthlyReportRequest) | Activity structure; partial saves |
| ReportAttachmentController | report attachments | Store/Update | Validator::make file rules | Structure consistency |
| **JavaScript (Blade)** | | | | |
| general_info.blade.php | projects | Create/Edit | required, min, maxlength | Backend alignment; draft vs submit |
| key_information.blade.php | projects | Edit | required on goal | Other fields |
| ReportAll.blade.php | reports | Create | required (removed on draft save) | Backend alignment |
| budget scripts | project_budgets | Edit | calculateBudgetRowTotals | **Numeric bounds**; overflow prevention |

#### Where Validation Is Missing

- **IIES/IES/CCI sub-controllers** – Receive `StoreProjectRequest` or `UpdateProjectRequest` from parent `ProjectController`; these FormRequests do **not** include IIES expenses, CCI statistics, IES attachments, or other type-specific fields. Controllers use `$request->all()` and apply ad-hoc `?? 0` or `?? null`.
- **Budget max bounds** – `StoreBudgetRequest` and `BudgetController` validate `min:0` only. DECIMAL(10,2) max is 99,999,999.99; no `max` rule exists.
- **Placeholder values** – No rule converts `-`, `N/A`, or empty string to null/0 for numeric columns.
- **File input type** – No check whether `$request->file($field)` returns `UploadedFile` or `UploadedFile[]`.
- **Logical Framework activities** – No validation that `$activityData['activity']` exists before access.
- **Draft vs submit** – StoreProjectRequest has conditional `project_type` (nullable when draft), but sub-controllers do not consistently relax validation for draft saves.

#### Inconsistencies Across Models

| Pattern | Projects | Budget | IIES | CCI | Reports |
|---------|----------|--------|------|-----|---------|
| FormRequest type-hint | StoreProjectRequest | Request (no FormRequest) | FormRequest (generic) | FormRequest (generic) | StoreMonthlyReportRequest |
| Uses $request->validated() | No (uses all/validate) | No | **No – uses all()** | **No – uses all()** | Yes |
| Uses $request->all() | Via sub-controllers | Via input() | Yes | Yes | Partial |
| prepareForValidation | StoreProjectRequest (save_as_draft) | No | No | No | Store/UpdateMonthlyReportRequest |

---

### Normalization Today

#### Patterns Used

| Pattern | Where | Purpose |
|---------|-------|---------|
| `?? 0` | IIESExpensesController, BudgetController, FinancialSupportController | Default numeric when key missing |
| `?? null` | CCI StatisticsController, ILP PersonalInfoController | Default null for nullable columns |
| `(int) ($x ?? 0)` | FinancialSupportController, ILP PersonalInfoController | Coerce to integer for boolean/tinyint |
| `filter_var(..., FILTER_VALIDATE_BOOLEAN)` | StoreProjectRequest, Store/UpdateMonthlyReportRequest | Normalize save_as_draft |
| `trim()` | ReportController, MonthlyDevelopmentProjectController | Check if string is "filled" |
| `str_pad()` | GeneralInfoController | Build commencement_month_year date |
| `implode()` on arrays | MonthlyDevelopmentProjectController | Flatten summary_activities, etc. |

#### Anti-Patterns Found

1. **`$request->all()` passed directly** – IIESExpensesController, CCI StatisticsController, ILP BudgetController, IAH controllers, CCI controllers, EduRUTAnnexedTargetGroupController, LDP controllers, CICBasicInfoController, ProjectEduRUTBasicInfoController, and ~25+ other project sub-controllers use `$validated = $request->all()` and then apply `??` per-field. This bypasses FormRequest validation for those fields and allows unvalidated keys.

2. **Assuming empty string ≡ null ≡ 0** – `$validated['iies_total_expenses'] ?? 0` does **not** convert `""` to 0. When the form submits an empty string, the key exists; `??` returns `""`, which MySQL may reject or coerce incorrectly.

3. **Defaults applied inconsistently** – Some controllers use `?? 0`, others `?? null`, others `?? ''`. No shared rule for "numeric NOT NULL column" vs "nullable integer" vs "nullable string."

4. **Normalization occurring AFTER persistence attempt** – In some flows, defaults are applied only when building the model; if the insert fails, the user sees a 500 error instead of a validation message.

5. **Placeholder values not normalized** – CCI Statistics received literal `-`; no layer converts `-`, `N/A`, `n/a`, or similar to null before persistence.

6. **File inputs not normalized** – IES `handleAttachments` expects `$request->file($field)` to be a single file; Blade uses `name="field[]"`. No normalization to "single file or first of array."

---

## Database vs Application Responsibility

| Concern | Current Owner | Recommended Owner |
|---------|---------------|-------------------|
| NOT NULL enforcement | Database (fails with SQLSTATE) | Application (validation + normalization) |
| DEFAULT values | Database (only when column omitted) | Application (explicit defaults before insert) |
| Numeric type (int, decimal) | Database (rejects invalid) | Application (type coercion + bounds) |
| String length (max:255) | Mixed (some FormRequests) | Application (validation) |
| Foreign key existence | Application (exists rule) | Application |
| Enum/boolean values | Database (rejects invalid) | Application (normalization + validation) |
| Placeholder → null | None | Application (normalization) |
| Empty string → null/0 | Ad-hoc in controllers | Application (normalization) |
| Decimal overflow | Database (fails) | Application (max validation) |
| File type/size | Application (some controllers) | Application (consistent) |
| Unique constraints | Database | Both (DB as safety net) |

**Principle:** The database should be the **last safety net**, not the first validator. All constraints that can be enforced in the application should be, so users receive clear validation errors instead of 500s.

---

## Proposed Validation Architecture

### FormRequest Usage Rules

| Rule | Rationale |
|------|------------|
| **Mandatory for all state-changing requests** | Create, Update, partial save, draft save, bulk, import – each must use a FormRequest or equivalent validated entry point. |
| **One FormRequest per logical operation** | Store vs Update can share rules via traits or base class, but type-hint the correct class for authorization and clarity. |
| **Sub-controllers must receive type-specific FormRequest** | IIESExpensesController should type-hint `StoreIIESExpensesRequest` / `UpdateIIESExpensesRequest`, not generic `FormRequest`. Routes must be updated so these controllers are invoked with the correct request class. |
| **Eliminate inline `$request->validate()`** | Move all rules into FormRequest classes. Controllers should only call `$request->validated()` or `$request->safe()`. |
| **No `$request->all()` for persistence** | Use `$request->validated()` or `$request->safe()->all()` after validation. For fields not in FormRequest rules, add them to the FormRequest (or a dedicated one) rather than bypassing validation. |

### Shared Validation Rule Sets

- **Location:** `app/Rules/` or `app/Http/Requests/Concerns/` (traits).
- **Examples:**
  - `NumericBoundsRule` – min/max for decimal columns (e.g. DECIMAL(10,2) → max:99999999.99).
  - `NullableIntegerOrPlaceholderRule` – accepts integer, empty, or placeholder (`-`, `N/A`) and normalizes to null.
  - `NullableNumericOrPlaceholderRule` – same for decimals.
  - `FileOrFileArrayRule` – accepts single file or array of files; normalizes to single or iterable for handler.
- **Reuse:** FormRequests compose these rules instead of duplicating `nullable|numeric|min:0` everywhere.

### CRUD-Aware Validation

| Operation | Validation Strictness | Notes |
|-----------|------------------------|-------|
| Create (full submit) | Strict – all required fields | project_type, goal, etc. |
| Create (draft) | Relaxed – project_type nullable, goal nullable | StoreProjectRequest already does this for project_type. |
| Update (full) | Same as create | |
| Update (draft) | Same as create draft | |
| Partial update (e.g. budget only) | Only validate fields present | Use `sometimes` or conditional rules. |
| Bulk/Import | Batch validation; collect all errors | Return structured validation result, not first-fail. |

### Draft vs Submit Handling

- **FormRequest responsibility:** `prepareForValidation()` or `rules()` should inspect `save_as_draft` and adjust rules (e.g. `project_type` required vs nullable).
- **Sub-controllers:** When orchestrated by ProjectController, they receive the same request. Their FormRequest (when introduced) should also respect draft mode for type-specific required fields.
- **Reports:** StoreMonthlyReportRequest and UpdateMonthlyReportRequest already use `prepareForValidation` for `save_as_draft`. Extend this pattern to project sub-forms.

---

## Proposed Normalization Architecture

### Execution Point

**Order of operations:**

1. **Normalization** (before validation)
2. **Validation**
3. **Persistence**

Normalization runs in `FormRequest::prepareForValidation()` or in a dedicated `InputNormalizer` service invoked at the start of the request lifecycle (e.g. middleware or in `FormRequest::prepareForValidation()`).

### Normalization Rules

| Input | Normalized To | When |
|-------|---------------|------|
| `""` (empty string) for numeric column | `0` (if NOT NULL) or `null` (if nullable) | Before validation |
| `"-"`, `"N/A"`, `"n/a"`, `"NA"` for numeric | `null` | Before validation |
| `"-"`, `"N/A"` for string | `null` or `""` per column semantics | Before validation |
| Whitespace-only string | `""` or `null` | Before validation |
| `"0"`, `"0.00"` for decimal | `0` / `0.00` (consistent type) | Optional; validation accepts numeric string |
| Boolean-like (`"1"`, `"0"`, `"true"`, `"false"`) | `true`/`false` | Before validation |
| Single-element array for file input | Single `UploadedFile` | Before validation (or in handler) |
| `null` for NOT NULL numeric | `0` (or column default) | Before persistence |

### Reusable Helpers / Traits / Services

- **InputNormalizer** (service): `normalizeForModel(string $model, array $input): array` – applies model-specific rules.
- **Trait `NormalizesNumericInputs`**: Methods like `emptyOrPlaceholderToNull($value)`, `toNumeric($value, $default)`, `toInt($value, $default)`.
- **Trait `NormalizesFileInputs`**: `fileOrFirstOfArray($request, $key): ?UploadedFile`.
- **FormRequest concern**: `prepareForValidation()` calls normalizer for known fields; merged back into request.

---

## Phase-wise Normalization Plan

### Phase 1 – Critical (Data Safety)

- **Prevent DB constraint violations**
  - Normalize empty string and placeholder to `0` for NOT NULL decimal columns (IIES expenses, budget).
  - Normalize empty string and placeholder to `null` for nullable integer columns (CCI statistics).
  - Coerce boolean-like values to `0`/`1` for NOT NULL boolean/tinyint (IIES Financial Support).
- **Enforce types and ranges**
  - Add `max` rules for DECIMAL(10,2) columns (e.g. 99999999.99).
  - Add `max` for integer columns where applicable.
  - Ensure numeric validation runs on normalized values (after empty/placeholder → 0/null).

### Phase 2 – Input Sanitation

- **Trim strings** – Apply `trim()` to string inputs before validation (except passwords).
- **Normalize placeholders** – Expand placeholder list (`-`, `N/A`, `n/a`, `NA`, `--`, etc.) and map to null or 0 per column.
- **Handle empty values** – Consistent rule: empty string for nullable column → null; for NOT NULL numeric → 0.

### Phase 3 – Structural Normalization

- **Nested arrays** – Ensure `objectives[].activities[]` has required keys; normalize missing `activity` to empty string or skip.
- **Repeating form rows** – Normalize `phases[].budget[]` structure; filter out fully empty rows before validation.
- **Optional sections** – When section is omitted, ensure default structure (e.g. empty array) to avoid undefined key access.

### Phase 4 – Contextual Normalization

- **Draft vs submit** – Relax required rules for draft; ensure optional fields default correctly.
- **Role-based input** – If different roles submit different fields, normalize missing keys to defaults per role.
- **Partial saves** – Only normalize and validate fields present in the request; do not require absent fields.

---

## Model-Level Examples (Conceptual)

*These examples illustrate the design. Do not implement.*

### ProjectIIESExpenses

- **DB:** `iies_total_expenses`, `iies_expected_scholarship_govt`, etc. – NOT NULL, default 0.
- **Current:** Controller uses `$validated['iies_total_expenses'] ?? 0`; `$validated` from `$request->all()`.
- **Proposed:** FormRequest `StoreIIESExpensesRequest` / `UpdateIIESExpensesRequest` used by IIESExpensesController. `prepareForValidation()` normalizes: empty string, `-`, `N/A` → `0` for these fields. Rules: `required|numeric|min:0|max:99999999.99`. Controller uses `$request->validated()` only.

### ProjectBudget

- **DB:** `rate_duration`, `this_phase`, etc. – DECIMAL(10,2) nullable.
- **Current:** Inline validation `min:0` only; no max; `?? 0` in controller.
- **Proposed:** FormRequest (or shared rules) adds `max:99999999.99`. Normalization: empty string → `0` before validation. Frontend calculation bounded or validated.

### ProjectCCIStatistics

- **DB:** All integer columns nullable.
- **Current:** `?? null`; form sends `-` which is not null.
- **Proposed:** Normalization: `-`, `N/A`, empty string → `null`. Validation: `nullable|integer|min:0` (after normalization). Controller uses `$request->validated()`.

### ProjectIESAttachments (file handling)

- **Current:** `$request->file($field)` – expects single file; view uses `field[]`.
- **Proposed:** Normalization in handler: `$file = $request->file($field); if (is_array($file)) { $file = $file[0] ?? null; }`. Or: Blade changed to single file if UX requires single. Contract must be aligned.

### Logical Framework (objectives/activities)

- **Current:** `$activityData['activity']` – undefined key when activity entry is malformed.
- **Proposed:** Normalization: ensure each `activityData` has `activity` key (default `''`). Validation: `activity` required when activities array is present. Or: filter out entries without `activity` before processing.

---

## Risks If This Layer Is Not Implemented

### Data Integrity Issues

- Invalid values (placeholders, empty strings, overflow) continue to reach the database or cause runtime errors.
- Inconsistent null/empty handling across project types leads to subtle bugs.

### Production Instability

- NOT NULL violations, numeric overflow, and type mismatches produce 500 errors.
- Users cannot complete workflows (e.g. IIES expense save, CCI statistics save).

### Increased Regression Risk

- New features and project types will repeat the same patterns.
- Fixes in one controller will not propagate to similar controllers.
- Frontend and backend will continue to diverge.

---

## DO NOT

- Implement code
- Refactor controllers
- Add validation rules
- Modify database schema

This document is for architectural design and planning only.

---

*Document generated: January 31, 2026*  
*Based on codebase analysis and Production_Log_Review_3031.md*
