# Final Validation & Normalization Layer Design (V1)

**Status:** Authoritative blueprint · Implementation-ready  
**Version:** V1

---

## 1. Executive Summary

### Why This Layer Exists

The Validation & Normalization Layer exists because the application currently allows invalid or ambiguous input to reach the database and runtime logic. The database acts as the first validator; users receive SQLSTATE and 500 errors instead of clear validation messages. Production evidence shows NOT NULL violations, numeric overflow, placeholder values in integer columns, type mismatches (e.g. file array vs single file), and undefined key access.

### What Production Failures It Prevents

- **NOT NULL violations** – Empty string or explicit NULL for NOT NULL decimal/boolean columns (e.g. IIES expenses, IIES financial support).
- **Numeric overflow** – Values exceeding DECIMAL(10,2) range (e.g. budget) causing DB constraint failure.
- **Placeholder in integer columns** – Literal `-` or `N/A` inserted into integer columns (e.g. CCI statistics).
- **Type mismatches** – File input as array when backend expects single file (e.g. IES attachments).
- **Undefined key access** – Accessing `$activityData['activity']` when key is missing (e.g. Logical Framework).
- **Partial saves with false success** – Sub-controllers returning error responses while parent commits; user sees success despite section failure.

### What Problems It Definitively Solves

1. **Single execution order:** Normalize → Validate → Persist. No persistence from raw request.
2. **Single source of truth:** All write validation and normalization live in FormRequest (or reused FormRequest rules); no inline defaults or `$request->all()` for persistence.
3. **Predictable types:** NOT NULL numerics receive `0`; nullable numerics/integers receive `null` when input is empty or placeholder; booleans receive `0` or `1`.
4. **Bounded numerics:** All decimal columns enforced to max 99,999,999.99 (DECIMAL(10,2)); no overflow.
5. **Unified error handling:** Sub-controllers throw on failure; parent owns transaction; no silent partial saves.

---

## 2. Scope & Non-Scope

### In Scope

- **Project creation and update** – All project types (IIES, IES, CCI, IAH, IGE, ILP, RST, EduRUT, CIC, LDP, etc.), including every section and sub-controller invoked by ProjectController.
- **Report creation and update** – Monthly, quarterly, aggregated; type-specific report controllers; report attachments.
- **Budget and financial data** – Project budgets (phases), IIES/IES/IAH/ILP/IGE budget sections, budget reconciliation (admin).
- **Attachments** – Project attachments, report attachments; single and array file handling.
- **Bulk actions** – Bulk report forward, bulk approve/revert, bulk export; validation of `report_ids` and action parameters.
- **Draft vs submit flows** – Relaxed validation for draft saves; strict validation on submit; consistent behavior across project and report forms.
- **User/center/society CRUD** – ProvincialController, GeneralController, CoordinatorController store/update operations.
- **Activity history filters** – Sanitization and validation of filter parameters.

### Out of Scope

- Deployment, environment, or route configuration issues (e.g. `routes/api.php` deployment).
- Read-only APIs and read-only endpoints (no write validation).
- UI/UX redesign or frontend framework changes (frontend contract alignment is in scope only where backend must accept or normalize existing frontend behavior).
- Database schema changes unless unavoidable to satisfy NOT NULL or type constraints; the design assumes current schema and enforces at application layer.

---

## 3. Core Architectural Decisions (Frozen)

| Decision | Rule |
|----------|------|
| **Where normalization happens** | In FormRequest `prepareForValidation()`. All empty-string, placeholder, and type-coercion rules run before `rules()` are evaluated. No normalization in controllers for persistence. |
| **Where validation happens** | In FormRequest `rules()` only. Controllers SHALL NOT call `$request->validate()` with inline arrays for persistence; all such rules SHALL live in a FormRequest or in reusable rule objects/traits invoked by the FormRequest. |
| **Controller responsibilities** | Controllers SHALL: (1) type-hint the appropriate FormRequest or ensure validation is run via Strategy B where route does not bind a FormRequest; (2) use only `$request->validated()` (or equivalent validated data) for persistence; (3) own transaction when they are the route handler; (4) catch exceptions from sub-controllers and roll back, then rethrow or redirect with error. Controllers SHALL NOT: add defaults for request data, use `$request->all()` for persistence, or begin nested transactions when invoked as sub-controllers. |
| **Sub-controller responsibilities** | When invoked by ProjectController (or equivalent parent): (1) SHALL run validation using the rules from their designated FormRequest (Strategy B: `Validator::make($request->all(), (new StoreXxxRequest())->rules())->validate()` or equivalent); (2) SHALL use only the resulting validated data for persistence; (3) SHALL NOT call `DB::beginTransaction()` / `commit()` / `rollBack()`; (4) SHALL throw on failure (validation or persistence) so the parent can catch and roll back. Sub-controllers SHALL NOT return error responses (e.g. `response()->json(..., 500)`) when used as sub-controllers; the parent does not inspect return values. |
| **Transaction ownership** | The route-handling controller (e.g. ProjectController) OWNS the transaction for the entire request. Sub-controllers SHALL NOT start or commit transactions. On any sub-controller throw, the parent SHALL catch, roll back, and redirect or respond with error. |
| **Error handling (throw vs return)** | On validation failure or persistence failure, sub-controllers SHALL throw (e.g. `ValidationException` or a runtime exception). They SHALL NOT return an error response. The parent SHALL catch and either rethrow `ValidationException` (so Laravel redirects with errors) or handle and redirect with a generic error message. |
| **Logging levels** | Validation failure: INFO or DEBUG (user input error, not system failure). Authorization denied: WARNING (expected security event). DB constraint violation or uncaught exception: ERROR. Normalization applied: DEBUG. Successful save: INFO. |

---

## 4. Normalization Rules (Canonical Matrix)

All normalization SHALL occur in FormRequest `prepareForValidation()`. The matrix below is the **law** for the system. Middleware (TrimStrings, ConvertEmptyStringsToNull) runs first; normalization in FormRequest SHALL handle placeholders and any remaining type coercion.

| Data Type | Empty String | Placeholder (`-`, `N/A`, `n/a`, `NA`, `--`) | Result |
|-----------|--------------|---------------------------------------------|--------|
| NOT NULL decimal | `""` or `null` | Any of the placeholder set | `0` |
| Nullable decimal | `""` or `null` | Any of the placeholder set | `null` |
| Nullable integer | `""` or `null` | Any of the placeholder set | `null` |
| NOT NULL integer (if any) | `""` or `null` | Any of the placeholder set | `0` |
| Boolean / checkbox | `""`, `"0"`, `"false"`, `"off"`, absent | `"1"`, `"true"`, `"on"` | `0` or `1` (via `filter_var(..., FILTER_VALIDATE_BOOLEAN)` then cast to int) |
| Text (string) | — | — | Trimmed (TrimStrings middleware); placeholder may be normalized to `""` or `null` per field semantics |
| File input (single) | — | — | Unchanged |
| File input (array) | — | — | If single-element array, normalize to single `UploadedFile` for handlers that expect one file; otherwise leave as array for multi-file handlers |

**Placeholder set:** `-`, `N/A`, `n/a`, `NA`, `--`. No other value SHALL be treated as placeholder unless this document is updated.

**Empty string:** Includes the value after Laravel’s `ConvertEmptyStringsToNull` (i.e. `null` from form blank). Normalization SHALL treat both `""` and `null` as “empty” for the purposes of the matrix.

**File single vs array:** Handlers that expect a single file SHALL receive either one `UploadedFile` or, if the request has `file('field')` as an array, the first element. Normalization SHALL ensure that before validation/handling, the value is either one `UploadedFile` or a defined array of `UploadedFile`; no ambiguous “array with one item” where the handler calls single-file methods on an array.

---

## 5. Validation Rules (Canonical)

### Required vs Nullable

- **NOT NULL columns (DB):** Rules SHALL use `required|numeric|min:0|max:99999999.99` for decimals, `required|integer|min:0` for integers, `required|boolean` for booleans (after normalization). No `nullable` for these.
- **Nullable columns:** Rules SHALL use `nullable|numeric|min:0|max:99999999.99` for decimals, `nullable|integer|min:0` for integers. Placeholder and empty SHALL be normalized to `null` before validation so that `nullable` accepts them.

### Numeric Bounds

- **DECIMAL(10,2):** min `0`, max `99999999.99`. Every decimal column SHALL have this max in validation.
- **Integer columns:** min `0` where applicable; max SHALL be set where the column has a defined upper bound (e.g. smallint).

### Integer vs Decimal

- Integer columns: `integer` rule; no decimal input accepted after normalization.
- Decimal columns: `numeric` rule; integer input accepted.

### Placeholder

- Placeholders SHALL be normalized to `0` or `null` in `prepareForValidation()`. Validation SHALL NOT see placeholders; rules SHALL NOT “reject” placeholders explicitly. Rejection is implicit: if a placeholder is not normalized (e.g. unknown token), the type rule (`numeric`, `integer`) will fail.

### File Size and Type

- File size SHALL be validated with `max:<bytes>` (e.g. from config). File type SHALL be validated with `mimes:...` or `mimetypes:...` derived from config where applicable (e.g. `config('attachments.allowed_types....')`). Single file SHALL use `file`; array of files SHALL use `array` and `array.*` with file rules.

### Array Structure (Parallel / Nested Arrays)

- Repeating rows (e.g. `phases.*.budget.*`, `target_group.*`) SHALL have rules for each key: e.g. `phases.*.budget.*.rate_duration` => `nullable|numeric|min:0|max:99999999.99`. Structure SHALL be validated (e.g. `phases` => `array`, `phases.*.budget` => `array`) so that missing keys are not accessed. For activity/objective structures, each element SHALL have an `activity` key present (defaulted in normalization to `''` if missing) so that validation can require it when applicable and handlers never see undefined key.

### No Per-Controller Repetition

- Shared rules (e.g. decimal bounds, placeholder handling) SHALL live in custom rules (e.g. `app/Rules/NumericBoundsRule`) or FormRequest traits/concerns. Controllers SHALL NOT duplicate rule arrays for the same column semantics.

---

## 6. Draft vs Submit Contract

### What “Draft” Means System-Wide

- **Draft:** A save that explicitly does not require all “required on submit” fields to be present or valid. The request SHALL carry a draft indicator (e.g. `save_as_draft` = true/1) so that FormRequests can relax rules.
- **Submit:** Full submit; all required fields for that operation SHALL be present and valid.

### Which Validations Are Relaxed for Draft

- **Project:** When `save_as_draft` is true, `project_type` and other top-level “required on submit” fields SHALL be `nullable` in the FormRequest rules (e.g. StoreProjectRequest).
- **Report:** When draft, required report fields SHALL be relaxed in the same way (e.g. StoreMonthlyReportRequest / UpdateMonthlyReportRequest).
- **Section-level (sub-controllers):** When the parent request is a draft save, section-level FormRequest rules (used via Strategy B) SHALL relax required rules for that section so that empty IIES/CCI/etc. sections do not cause validation failure. Empty sections SHALL result in no rows or defaulted rows as defined by normalization, not validation failure.

### Which Validations Are Never Relaxed

- **Type and bounds:** Numeric columns SHALL always validate type and min/max (e.g. `numeric|min:0|max:99999999.99`) when the field is present. Draft does not allow invalid numbers.
- **Structure:** Array structure (e.g. `phases` is array, `phases.*.budget` is array) SHALL always be enforced when the key is present. Draft does not allow malformed arrays.
- **File type/size:** When a file is present, file type and size rules SHALL always apply.

### How Draft Intent Propagates to Sub-Controllers

- The same request object is passed to sub-controllers. Sub-controllers SHALL read the draft indicator (e.g. `$request->boolean('save_as_draft')`) when running validation (Strategy B). The FormRequest rules used by the sub-controller SHALL be conditional on this flag (e.g. via a method that returns rules for “draft” vs “submit”). No separate mechanism is required; the single request carries the flag.

---

## 7. Controller Contract

- **Prohibition of `$request->all()` for persistence:** Controllers SHALL NOT use `$request->all()` (or `$request->input()` for full payload) to build models or persist data. Only `$request->validated()` or the result of `Validator::make(...)->validate()` / `validated()` (Strategy B) SHALL be used for persistence.
- **Mandatory use of `$request->validated()`:** After validation (either by type-hinted FormRequest or by explicit Strategy B call), the controller SHALL use only the validated array for assignation to models, creation, or update. No merging of raw request keys into validated data for persistence.
- **Sub-controller behavior on failure:** On validation failure, sub-controller SHALL throw `ValidationException` (or equivalent). On persistence failure (e.g. DB exception), sub-controller SHALL throw. It SHALL NOT return `response()->json(['error' => ...], 500)` or similar; the parent does not check return value.
- **Parent controller behavior:** Parent SHALL wrap sub-controller calls in try/catch. On catch of `ValidationException`, parent SHALL rethrow so Laravel redirects with validation errors. On catch of other exceptions, parent SHALL roll back the transaction and redirect or respond with a generic error message (no sensitive detail). Parent SHALL NOT commit the transaction if any sub-controller has thrown.
- **Partial save prevention:** Because sub-controllers throw and parent owns the transaction, a failure in any sub-controller SHALL result in roll back of the entire operation. No partial save where the user sees success while a section failed.

---

## 8. FormRequest Strategy

- **Mandatory FormRequest for all write operations:** Every state-changing request (create, update, partial save, draft save, bulk action, import) SHALL pass through a FormRequest or through an explicit invocation of FormRequest rules (Strategy B) before any persistence. There SHALL be no write that uses only inline `$request->validate()` with rules that are not also owned by a FormRequest or shared rule set.
- **Strategy for sub-controllers invoked by ProjectController:** Because the route binds only StoreProjectRequest/UpdateProjectRequest and sub-controllers receive the same request, type-specific FormRequests (e.g. StoreIIESExpensesRequest) CANNOT be bound by route. **Strategy B is the final decision:** sub-controllers SHALL run validation using the rules from their designated FormRequest, e.g. `Validator::make($request->all(), (new StoreIIESExpensesRequest())->rules())->validate()`, then use the validated data only. Authorization for the sub-controller section SHALL be enforced by the parent or by the sub-controller in a separate step (e.g. policy check on project).
- **Reuse of FormRequest rules:** FormRequest classes SHALL exist per logical operation (e.g. StoreIIESExpensesRequest, UpdateIIESExpensesRequest). Their `rules()` SHALL be reusable. Sub-controllers SHALL invoke these rules via Strategy B. Shared rules (e.g. decimal bounds, placeholder normalization) SHALL be implemented in custom Rule classes or traits used by FormRequests; the same FormRequest SHALL use `prepareForValidation()` for normalization so that both normalization and rules are in one place.
- **Organization:** FormRequests SHALL live under `app/Http/Requests/` (with optional subdirectories by domain). Shared normalization logic SHALL live in traits or a small set of helper classes used inside `prepareForValidation()`. Custom rules SHALL live in `app/Rules/`. No duplication of rule arrays across FormRequests for the same column semantics.

---

## 9. Phase-Wise Implementation Plan

### Phase 0 – Preparation

- **What will be done:** Create `app/Rules/`; implement shared rules (e.g. NumericBoundsRule, placeholder handling via traits or rules); create InputNormalizer or FormRequest concern used in `prepareForValidation()` with the canonical placeholder set and empty/type coercion; document frontend contract for each form (what is sent for empty, placeholder, file single/array).
- **What will NOT be touched:** Controllers, routes, database schema, frontend views.
- **Success criteria:** Rules and normalizer exist and are unit-testable; document listing frontend contract per form is available.

### Phase 1 – Critical Data Safety

- **What will be fixed:** IIES expenses (normalize empty/placeholder → 0, add max, use validated()); IIES financial support (boolean normalization, validated()); project budgets (max 99999999.99, normalize empty → 0); CCI Statistics and CCI PersonalSituation, AgeProfile, EconomicBackground (placeholder → null, validated()); IIES family working members (required when row present, max for income, normalized decimals). Sub-controllers SHALL stop using nested transactions and SHALL throw on failure; ProjectController SHALL own transaction and catch/rollback.
- **What will NOT be touched:** Other project types’ sections, report flows, admin flows, frontend.
- **Success criteria:** No NOT NULL violations for IIES/CCI in production; no budget overflow; no partial save when a sub-controller fails (user sees error and no commit).

### Phase 2 – Type & Structure Alignment

- **What will be fixed:** IES attachments (file single vs array normalized before handler); Logical Framework (ensure `activity` key exists in each element, no undefined key); BudgetReconciliationController manualCorrection (add max for decimals).
- **What will NOT be touched:** Other report controllers, Provincial/General CRUD, bulk actions.
- **Success criteria:** IES attachment save works with current frontend; Logical Framework save does not throw undefined key; admin manual correction rejects values over max.

### Phase 3 – Consistency & Refactor

- **What will be fixed:** All project sub-controllers SHALL use Strategy B and `validated()` only; PDF export SHALL pass the same variables to views as the show action (variable contract); ProvincialController and GeneralController SHALL use FormRequests for user/center/society CRUD (extract from inline validation).
- **What will NOT be touched:** Report type-specific validation logic beyond what is needed for consistency; database schema.
- **Success criteria:** No project sub-controller uses `$request->all()` for persistence; PDF export does not miss required variables; user/center/society CRUD use FormRequest and validated().

### Phase 4 – Reports & Admin

- **What will be fixed:** Report controllers (monthly type-specific, quarterly, aggregated) SHALL use FormRequests and `validated()`; bulk report actions SHALL validate `report_ids` and action parameters; activity history filters SHALL sanitize and validate filter keys.
- **What will NOT be touched:** Read-only report views; deployment or infrastructure.
- **Success criteria:** Report create/update use FormRequest; bulk actions reject invalid IDs or action; filter endpoint rejects unknown or invalid filter keys.

### Phase 5 – Sanitation & Hardening

- **What will be fixed:** Trim applied consistently (rely on TrimStrings; ensure no skipped keys that need trim); placeholder list applied everywhere it is relevant (already in normalizer); nested array validation (e.g. `target_group.*`, `phases.*.budget.*`) with full key and type rules; optional sections default to empty array where needed to avoid undefined key.
- **What will NOT be touched:** New features; database schema.
- **Success criteria:** All normalized inputs follow the canonical matrix; no undefined key in nested arrays; validation coverage for repeating rows is complete.

---

## 10. Testing Strategy (Design-Level)

- **Unit tests:** InputNormalizer (or equivalent): empty string → 0 for NOT NULL numeric; empty/placeholder → null for nullable integer; placeholder → 0 for NOT NULL decimal; boolean coercion. NumericBoundsRule: rejects value > 99999999.99. Placeholder rule/trait: accepts `-`, `N/A`, `""`, `null` and produces the correct normalized value.
- **Feature tests:** IIESExpensesController store with empty string in a NOT NULL decimal → stored as 0; with placeholder in that field → stored as 0. BudgetController store with value > 99999999.99 → validation error. CCI StatisticsController store with placeholder in integer field → stored as null. LogicalFrameworkController store with missing `activity` key in one element → no undefined key error (normalization or validation prevents it). Sub-controller throw → parent rolls back and user receives error (no partial save).
- **Regression monitoring in production:** Log and alert on NOT NULL constraint violations, numeric overflow errors, and undefined index/key errors in the areas addressed. After each phase, confirm zero such errors for the modified code paths.

---

## 11. Success Metrics

- **Log cleanliness:** No ERROR-level logs for validation failures or authorization denied; DB constraint and uncaught exceptions at ERROR with sufficient context.
- **Error reduction:** Zero NOT NULL, numeric overflow, or undefined key errors in production for the flows covered by Phases 1–5.
- **User-visible improvements:** No “data didn’t save” or “form won’t submit” for IIES, CCI, budget, IES attachments, Logical Framework, and report flows after the corresponding phases.
- **Regression prevention:** No new 500s introduced by validation or normalization changes; feature and unit tests added per phase to lock behavior.

---

## 12. Final Guardrails

- **DO NOT** add defaults in controllers for request data; normalization and validation SHALL provide the only source of defaulted or coerced values for persistence.
- **DO NOT** bypass FormRequest (or Strategy B) for any write; every state-changing request SHALL be validated using FormRequest rules and SHALL use only validated data for persistence.
- **DO NOT** rely on database defaults for NOT NULL columns when the application sends a value; the application SHALL send 0 or the correct value after normalization. DB defaults apply only when the column is omitted from the insert.
- **DO NOT** reintroduce `$request->all()` (or equivalent raw input) for persistence in any controller or sub-controller.
- **DO NOT** use nested transactions in sub-controllers invoked by ProjectController; the parent SHALL own the single transaction.
- **DO NOT** return error responses from sub-controllers when used by a parent; sub-controllers SHALL throw so the parent can roll back and respond.
- **DO NOT** relax type or bounds validation (numeric, integer, min, max) for draft; only “required” SHALL be relaxed when draft is true.
- **DO NOT** add new placeholder tokens without updating this document and the shared normalizer.

---

*End of Final Validation & Normalization Layer Design (V1)*
