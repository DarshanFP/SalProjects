# Validation & Normalization Layer Design – Batch 3

*Companion to Validation_Normalization_Design.md and Batch 2. Covers route/FormRequest strategy, user-management flows, report flows, shared rules, and admin flows.*

---

## Route & FormRequest Binding Strategy

### Current Architecture

- **Single entry point:** `POST /executor/projects/store` and `PUT /executor/projects/{project_id}/update` (and coordinator/provincial variants) hit `ProjectController@store` and `ProjectController@update`.
- **FormRequest:** Only `StoreProjectRequest` and `UpdateProjectRequest` are type-hinted; they validate project-level fields (project_type, goal, coordinators, etc.).
- **Sub-controllers:** Invoked internally by ProjectController (e.g. `$this->iiesExpensesController->store($request, $project->project_id)`). They receive the **same** request object.
- **No separate routes** for IIES expenses, CCI statistics, budget, etc. – all data is submitted in one monolithic form.

### Implication

Type-specific FormRequests (StoreIIESExpensesRequest, StoreCCIStatisticsRequest, etc.) **cannot** be bound via route model binding because those controllers are never the route handler. The route only hits ProjectController.

### Proposed Strategies

| Strategy | Description | Pros | Cons |
|----------|-------------|------|------|
| **A: Extend Store/UpdateProjectRequest** | Add conditional rules to StoreProjectRequest based on `project_type`. When `project_type === 'IIES'`, merge IIES rules. | Single FormRequest; no route changes | Large, complex rules(); hard to maintain |
| **B: Manual validation in sub-controllers** | Sub-controller calls `Validator::make($request->all(), (new StoreIIESExpensesRequest())->rules())->validate()` before processing | Reuses FormRequest rules; no route change | Duplicates validation invocation; must handle authorization separately |
| **C: Validation service** | `ValidationService::validateForSection($request, 'iies_expenses')` returns validated data or throws | Centralized; can compose rules per section | New abstraction; similar to B |
| **D: AJAX section saves** | Add routes like `POST /projects/{id}/sections/iies-expenses` with StoreIIESExpensesRequest. Frontend saves sections independently. | Clean FormRequest binding; section-level validation | **Requires frontend refactor**; changes UX (section-by-section save) |

**Recommendation:** Use **Strategy B** (or C as a thin wrapper) for the near term. Sub-controllers validate using the rules from their FormRequest class before using `$request->all()`. No route changes. When frontend can support section-level saves, consider D for new flows.

### Implementation Sketch (Strategy B)

```php
// In IIESExpensesController::store (conceptual – do not implement)
$request->validate((new StoreIIESExpensesRequest())->rules());
$validated = $request->validated(); // Now safe to use
// ... rest of logic using $validated instead of $request->all()
```

Or with `Validator::make` when FormRequest has `authorize()` logic that depends on route params:

```php
$validator = Validator::make($request->all(), (new StoreIIESExpensesRequest())->rules());
$validator->validate();
$validated = $validator->validated();
```

---

## ProvincialController & GeneralController – Inline Validation Inventory

### ProvincialController

| Method | Validates | Pattern |
|--------|-----------|---------|
| storeExecutor | name, username, email, password, phone, society_name, center, address, role | `$request->validate([...])` |
| updateExecutor | Same + status | Same |
| storeProvincial | Similar to executor | Same |
| updateProvincial | Same | Same |
| storeCenter | name, province, etc. | Same |
| updateCenter | Same | Same |
| storeSociety | name, etc. | Same |
| updateSociety | Same | Same |
| addProjectComment | comment text | Same |
| updateProjectComment | Same | Same |
| forwardReport | report_id | Same |
| revertReport | report_id, revert_reason | Same |
| bulkForwardReports | report_ids | Same |
| addComment (report) | pmc_comments | Same |

**Gaps:** No FormRequests; rules duplicated if GeneralController has similar methods. Center/society/province existence checks may be inconsistent.

### GeneralController

| Method | Validates | Pattern |
|--------|-----------|---------|
| storeCoordinator | name, username, email, password, province, center, etc. | `$request->validate([...])` |
| updateCoordinator | Same | Same |
| storeProvincial | Same as ProvincialController | Same |
| updateProvincial | Same | Same |
| storeExecutor | Same | Same |
| updateExecutor | Same | Same |
| storeProvince | name, etc. | Same |
| updateProvince | Same | Same |
| storeSociety | Same | Same |
| updateSociety | Same | Same |
| storeCenter | Same | Same |
| updateCenter | Same | Same |
| approveProject | project_id, commencement_month, commencement_year, etc. | ApproveProjectRequest (CoordinatorController) |
| revertProject | revert_reason | Same |
| bulkActionReports | report_ids, bulk_action | Same |
| addProjectComment | Same | Same |
| updateProjectComment | Same | Same |
| addReportComment | Same | Same |
| updateReportComment | Same | Same |

**Proposed:** Extract FormRequests for each logical operation (StoreCoordinatorRequest, UpdateExecutorRequest, StoreCenterRequest, etc.). Share rules via traits where General and Provincial have identical validation (e.g. StoreExecutorRequest).

---

## CoordinatorController – Validation Patterns

| Method | Validates | FormRequest? |
|--------|-----------|---------------|
| storeProvincial | User fields | Inline |
| updateProvincial | Same | Inline |
| approveProject | commencement_month, commencement_year, etc. | ApproveProjectRequest |
| revertProject | revert_reason | Inline |
| bulkReportAction | report_ids, action, revert_reason | Inline |
| updateUserCenter | center assignments | Inline |

**Note:** ApproveProjectRequest exists and is used. Revert and bulk actions use inline validation.

---

## Report Flows – Validation Patterns

### Monthly Reports

| Controller | Store | Update | FormRequest |
|------------|-------|--------|-------------|
| ReportController | StoreMonthlyReportRequest | UpdateMonthlyReportRequest | Yes |
| MonthlyDevelopmentProjectController | Inline validate | Inline validate | No |
| LivelihoodAnnexureController | Inline | - | No |
| PartialDevelopmentLivelihoodController | Inline | - | No |
| ResidentialSkillTrainingController | Inline | - | No |
| InstitutionalOngoingGroupController | Inline | Inline | No |
| CrisisInterventionCenterController | Inline | - | No |
| ReportAttachmentController | Validator::make | Validator::make | No |

**Gap:** ReportController uses FormRequest and `$request->validated()`. Type-specific monthly controllers (DevelopmentProject, Livelihood, etc.) use inline `$request->validate()` and may not align with StoreMonthlyReportRequest on optional fields.

### Quarterly Reports

| Controller | Store | Update | FormRequest |
|------------|-------|--------|-------------|
| DevelopmentProjectController | Inline | Inline | No |
| DevelopmentLivelihoodController | Inline | Inline | No |
| SkillTrainingController | Inline | Inline | No |
| InstitutionalSupportController | Inline | Inline | No |
| WomenInDistressController | Inline | Inline | No |

**Pattern:** All use `$validatedData = $request->validate([...])` then `$request->input()` for nested arrays (objective, expected_outcome, month) – partial bypass of validated data.

### Aggregated Reports

| Controller | Store | Update | FormRequest |
|------------|-------|--------|-------------|
| AggregatedQuarterlyReportController | Inline | Inline (updateAI) | No |
| AggregatedHalfYearlyReportController | Inline | Inline | No |
| AggregatedAnnualReportController | Inline | Inline | No |
| ReportComparisonController | Inline (compare forms) | - | No |

**Proposed:** Introduce Store/Update FormRequests for each report type. Use `$request->validated()` for top-level fields; validate nested structure (objectives, activities) in rules or dedicated nested validation.

---

## Admin Flows

### BudgetReconciliationController

| Method | Validates | Notes |
|--------|-----------|-------|
| acceptSuggested | admin_comment nullable | Inline |
| manualCorrection | overall_project_budget, amount_forwarded, local_contribution, admin_comment required | **No max** for decimals; same overflow risk as budget |
| reject | admin_comment nullable | Inline |

**Proposed:** Add `max:99999999.99` (or appropriate) for manual correction numeric fields. Consider AcceptSuggestedRequest, ManualCorrectionRequest, RejectCorrectionRequest for consistency.

---

## Shared Rule Design (Conceptual)

*Do not implement. Design only.*

### NumericBoundsRule

- **Purpose:** Enforce min/max for decimal columns.
- **Config:** `NumericBoundsRule::forDecimal(10, 2)` → min 0, max 99,999,999.99.
- **Usage:** `'rate_duration' => [new NumericBoundsRule::forDecimal(10, 2)]`.
- **Placement:** `app/Rules/NumericBoundsRule.php` (directory does not exist today).

### NullableIntegerOrPlaceholderRule

- **Purpose:** Accept integer, null, empty string, or placeholder (`-`, `N/A`, `n/a`, `NA`); normalize to null before validation.
- **Behavior:** In `prepareForValidation` or as custom rule, convert placeholder → null. Then `nullable|integer|min:0`.
- **Usage:** For CCI Statistics, CCI PersonalSituation, AgeProfile, EconomicBackground, etc.

### NullableNumericOrPlaceholderRule

- **Purpose:** Same as above for decimal columns; normalize to null or 0 (configurable).
- **Usage:** For nullable decimal columns where placeholder means "no value."

### RequiredNumericOrPlaceholderRule

- **Purpose:** For NOT NULL decimal columns; normalize empty/placeholder → 0.
- **Usage:** IIES expenses, budget amounts.

### FileOrFileArrayRule

- **Purpose:** Accept single `UploadedFile` or `UploadedFile[]`; normalize to single for validation.
- **Usage:** IES attachments when view uses `name="field[]"`.

### BooleanCoercionRule

- **Purpose:** Accept `1`, `0`, `"1"`, `"0"`, `true`, `false`, `"true"`, `"false"`; normalize to 0/1.
- **Usage:** IIES ImmediateFamilyDetails boolean fields, IIES Financial Support govt_eligible_scholarship.

---

## IIES ImmediateFamilyDetails – Boolean Normalization Pattern

### Current Implementation

```php
foreach ($this->getImmediateFamilyBooleanFields() as $field) {
    $model->$field = $request->has($field) ? 1 : 0;
}
```

- **Behavior:** Checkbox absent → 0; checkbox present (any value) → 1.
- **Risk:** If frontend sends `field=""` or `field="false"`, `has()` is true, so value becomes 1. May be correct for checkboxes (unchecked = omitted).

### Proposed Normalization

For boolean columns that must be 0 or 1:

- `filter_var($value, FILTER_VALIDATE_BOOLEAN)` maps "1", "true", "on", "yes" → true; others → false.
- Then cast to int: `(int) filter_var($request->input($field, false), FILTER_VALIDATE_BOOLEAN)`.

**Design rule:** Document whether "omitted" = false or "empty string" = false. For HTML checkboxes, omitted usually means unchecked. Normalize accordingly in `prepareForValidation` or in the mapping method.

---

## Excel Upload Flows

### EduRUT Target Group / Annexed Target Group

- **Current:** `uploadExcel` returns "Excel upload feature is disabled." No validation when enabled.
- **Proposed when enabled:**
  - FormRequest: `UploadTargetGroupExcelRequest` – validate `file` required, mimes:xlsx,xls, max:5120.
  - Import class (`EduRUTTargetGroupImport`) should validate each row (required columns, types, placeholders).
  - Normalize placeholder in rows before insert (same rules as manual form).

### Route

- `POST /upload-target-group-excel` and `POST /upload-annexed-target-group-excel` – would need FormRequest when feature is enabled.

---

## Nested Array Validation – Repeating Rows

### Pattern (EduRUT Target Group, CCI Annexed Target Group, etc.)

```php
foreach (($validatedData['target_group'] ?? []) as $group) {
    ProjectEduRUTTargetGroup::create([
        'beneficiary_name' => $group['beneficiary_name'] ?? null,
        'total_tuition_fee' => $group['total_tuition_fee'] ?? null,
        // ...
    ]);
}
```

**Gaps:**

- No validation that `target_group` is array of objects with expected keys.
- No validation of `total_tuition_fee` as numeric (placeholder, empty string).
- No max for decimal columns.

**Proposed rules:**

```
'target_group' => 'nullable|array',
'target_group.*.beneficiary_name' => 'nullable|string|max:255',
'target_group.*.total_tuition_fee' => 'nullable|numeric|min:0|max:99999999.99',
'target_group.*.eligibility_scholarship' => 'nullable|numeric|min:0|max:99999999.99',
// ... etc.
```

With normalization: `target_group.*.total_tuition_fee` empty/placeholder → null before validation.

---

## Implementation Priority Addendum (Batch 3)

| Priority | Area | From Batch 2 | Batch 3 Additions |
|----------|------|--------------|-------------------|
| P1 | Route/FormRequest strategy | - | Decide Strategy B vs C; document for team |
| P2 | ProvincialController / GeneralController | - | Extract FormRequests for user/center/society CRUD |
| P2 | Report controllers | - | FormRequests for Monthly type-specific, Quarterly, Aggregated |
| P2 | BudgetReconciliationController manualCorrection | - | Add max for decimals |
| P3 | Shared rules (NumericBoundsRule, etc.) | - | Implement when P0/P1 controllers are refactored |
| P3 | IIES ImmediateFamilyDetails boolean | - | Document checkbox semantics; ensure consistent coercion |
| P4 | Excel upload (when enabled) | - | FormRequest + row-level validation in Import |

---

## DO NOT

- Implement code
- Refactor controllers
- Add validation rules
- Modify database schema

This document extends the architectural design for planning only.

---

*Document generated: January 31, 2026*  
*Companion to Validation_Normalization_Design.md and Batch 2*
