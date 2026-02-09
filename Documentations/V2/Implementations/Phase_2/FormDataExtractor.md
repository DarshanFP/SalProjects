# Phase 2.1 — FormDataExtractor

## 1. Purpose and Problem Statement

### Tie to Phase 1A Bugs

FormDataExtractor formalizes and centralizes the pattern that Phase 1A implemented manually across 46 controllers. It exists to **prevent** recurrence of the following production bugs:

| Bug | Source | Root Cause |
|-----|--------|------------|
| **Array to string conversion** | Production_Errors_Analysis_070226.md → Error 3 | `IESEducationBackgroundController@store` used `$request->all()` + `fill()`. When the multi-step form submitted, `family_contribution[]` from IGE budget (or another partial) reached the IES controller. Array value passed to string column → SQL error. |
| **Mass assignment from unrelated sections** | Phase 1A Refactor Playbook → Anti-pattern | `$request->all()` pulls in ALL form data (phases, budget, files, other sections). No ownership of which keys belong to which controller. |
| **Field collisions** | Phase_Wise_Refactor_Plan.md | `family_contribution` (IES) and `family_contribution[]` (IGE) collide when both in DOM. Unscoped input means the wrong value can be persisted. |

Phase 1A fixed these by applying `$request->only($fillable)` + `ArrayToScalarNormalizer::forFillable()` at each controller. FormDataExtractor **centralizes** this logic so:

- All module inputs pass through the same pipeline
- No controller can bypass scoping or normalization without explicit opt-out
- Easy to add logging, sanitization, or schema checks in one place

---

## 2. Responsibilities and Non-Responsibilities

### Responsibilities

| Responsibility | Description |
|----------------|-------------|
| **Extract** | Accept a Request and a set of allowed keys; return only those keys from the request. |
| **Normalize** | Apply configurable normalizers (ArrayToScalar, PlaceholderToNull, PlaceholderToZero) to produce scalar-only values safe for `fill()`. |
| **Output guarantee** | Return an associative array where every value is scalar (string, int, float, bool, null). No arrays in output. |
| **Compose with validation** | Operate before or alongside FormRequest validation; do not replace validation rules. |

### Non-Responsibilities

| Non-Responsibility | Reason |
|-------------------|--------|
| **Validation** | Does not add, change, or enforce validation rules. FormRequest `rules()` and existing rules remain the source of truth. |
| **Form field names** | Does not change request shape (e.g. no namespacing). Reads whatever keys the caller provides. |
| **Attachments / file handling** | Does not touch `$request->file()`. Attachment controllers (IESAttachmentsController, IIESAttachmentsController, etc.) are out of scope. |
| **Orchestration** | Does not route requests to sub-controllers. ProjectController orchestration is Phase 2.4 (FormSection). |
| **Reports / Export** | Does not apply to report flows, monthly/quarterly controllers, or ExportController. |
| **External I/O** | No database reads, no HTTP calls, no filesystem operations. |
| **Routes** | Does not modify routes or middleware. |

---

## 3. Public Interface (Pseudocode Only)

```
// Primary entry point: extract + normalize in one call
function extract(Request $request, array $allowedKeys, array $normalizers = []): array

// Convenience: extract with default normalizers (ArrayToScalar + optional Placeholder normalizers)
function forFillable(Request $request, array $fillable, NormalizerSet $normalizers = default): array

// Low-level: normalize an already-extracted array (e.g. after $request->only())
function normalize(array $data, array $keys, array $normalizers): array
```

**Semantics**:

- `extract`: `$request->only($allowedKeys)` then apply each normalizer in order.
- `forFillable`: Shorthand for common case; `fillable` = allowed keys; default normalizers = `[ArrayToScalar, PlaceholderToNull]` (or configurable).
- `normalize`: For callers who already have scoped data (e.g. from FormRequest `validated()`); applies normalizers only.

**Normalizer contract** (pseudocode):

```
interface Normalizer {
    function apply(array $data, array $keys): array
}
```

Existing `ArrayToScalarNormalizer::forFillable($data, $fillable)` and `PlaceholderNormalizer::normalizeToNull` / `normalizeToZero` fit this contract when wrapped.

---

## 4. Input/Output Guarantees

### Input Guarantees

| Input | Constraint |
|-------|------------|
| `$request` | Laravel `Illuminate\Http\Request` (or equivalent). Expects `all()`, `only()`, `input()` available. |
| `$allowedKeys` / `$fillable` | Non-empty array of string keys. Keys not in request yield `null` (or absent, per implementation choice). |
| `$normalizers` | Ordered list of normalizer callables or objects. Each receives `(array $data, array $keys)` and returns `array`. |

### Output Guarantees

| Guarantee | Description |
|-----------|-------------|
| **Associative array** | Keys are those in `$allowedKeys` that were requested (or all, with missing = null). |
| **Scalar-only values** | Every value is `string`, `int`, `float`, `bool`, or `null`. No nested arrays. |
| **Safe for `fill()`** | Output can be passed directly to `$model->fill($data)` without "Array to string conversion". |
| **Deterministic** | Same input + config → same output. No side effects. |

### Edge Cases

- Key in `$allowedKeys` but not in request → output includes key with value `null` (or key omitted; design choice).
- Value is empty string `''` → after PlaceholderToNull: `null`; after PlaceholderToZero: `0` (for numeric keys).
- Value is `['a','b']` → after ArrayToScalar: `'a'` (first element) or `null` if empty array.

---

## 5. Normalization Pipeline

### Order of Operations

1. **Extract** — `$request->only($allowedKeys)`.
2. **ArrayToScalar** — For each key, if value is array: `reset($value) ?? null`.
3. **Placeholder handling** — For each key (or for keys in a configured “placeholder” set): if value is in `['-', 'N/A', 'n/a', 'NA', '--']` (or empty string), convert to `null` or `0` per normalizer config.

### Normalizers

| Normalizer | Purpose | Applied to |
|------------|---------|------------|
| **ArrayToScalar** | Prevent "Array to string conversion" | All keys in allowed set |
| **PlaceholderToNull** | Convert `-`, `N/A`, etc. to `null` | Configurable keys (e.g. text fields) |
| **PlaceholderToZero** | Convert placeholders to `0` | Configurable keys (e.g. numeric fields) |

### Pipeline Configuration (Conceptual)

```
default normalizers = [
    ArrayToScalarNormalizer,   // always first
    PlaceholderNormalizer     // keys: optional; if key in "toNull" or "toZero" set, apply
]
```

FormDataExtractor does **not** invent new normalizers. It composes existing `ArrayToScalarNormalizer` and `PlaceholderNormalizer`. Per Phase 1A Playbook Pitfall 3: "If PlaceholderNormalizer is needed (e.g. `-` → null), add that in a FormRequest `prepareForValidation()`, not in the controller." FormDataExtractor provides a single place for both ArrayToScalar and Placeholder in the pipeline.

---

## 6. Where It Is Allowed to Be Called

### Option A: FormRequest `prepareForValidation()`

FormRequest overrides `prepareForValidation()`:

- Call `FormDataExtractor::forFillable($this, $fillable, $normalizers)`.
- `merge()` the result into the request so `$request->validated()` and `rules()` see normalized input.

**Use when**:Module uses FormRequest with validation rules; validation should run on normalized data.

### Option B: Controller Pre-Fill Step

Controller, before `fill()`:

- Call `FormDataExtractor::forFillable($request, $fillable, $normalizers)`.
- Pass result to `$model->fill($data)`.

**Use when**: Controller does not use FormRequest, or uses FormRequest but gets data via `$request->only()` + manual normalization (current Phase 1A pattern).

### Option C: FormRequest `getNormalizedInput()` (Strategy B)

Some controllers (e.g. CCI, IIES, Budget) use `$formRequest->getNormalizedInput()` instead of `$request->validated()`. FormRequest can implement `getNormalizedInput()` by delegating to FormDataExtractor:

- `return FormDataExtractor::forFillable($this, $fillable, $normalizers);`

**Use when**: FormRequest already has `NormalizesInput` trait or custom `prepareForValidation()`; FormDataExtractor replaces ad-hoc normalization.

### Not Allowed

- Inside attachment models (`ProjectIESAttachments::handleAttachments`, etc.).
- Inside report, monthly, or quarterly controllers.
- Inside ExportController.
- In middleware or route-level logic.

---

## 7. Explicit Anti-Patterns It Replaces

| Anti-Pattern | Replacement |
|--------------|-------------|
| `$validated = $request->all()` | `FormDataExtractor::forFillable($request, $fillable)` or `$request->only($fillable)` + `FormDataExtractor::normalize()` |
| `$model->fill($request->all())` | `$model->fill(FormDataExtractor::forFillable($request, $fillable))` |
| `$model->fill($validated)` where `$validated` may contain arrays | `$model->fill(FormDataExtractor::forFillable($request, $fillable))` — guarantees scalar-only |
| Inline `foreach` + `reset($value)` per key | FormDataExtractor pipeline (ArrayToScalar built-in) |
| Ad-hoc placeholder handling in controller | FormDataExtractor pipeline (PlaceholderToNull / PlaceholderToZero) |

---

## 8. Adoption Strategy

### Incremental Migration

Phase 1A controllers already use `$request->only($fillable)` + `ArrayToScalarNormalizer::forFillable()`. Migration to FormDataExtractor is **additive**:

1. **Introduce FormDataExtractor** — New class/trait; no controller changes yet.
2. **Pilot** — One controller (e.g. `IESEducationBackgroundController` or `IESPersonalInfoController`) replaces inline `$request->only()` + `ArrayToScalarNormalizer` with `FormDataExtractor::forFillable()`. Verify behavior unchanged.
3. **Module-by-module** — Migrate IES, then IIES, then IAH, ILP, IGE, CCI, RST, LDP, EduRUT, CIC. One controller per change; verify locally.
4. **FormRequest adopters** — Controllers using FormRequest with `getNormalizedInput()` or `prepareForValidation()`: update FormRequest to use FormDataExtractor. Controller continues to receive normalized data.

### Backward Compatibility

- FormDataExtractor MUST produce the same output as the current inline pattern for the same input. No behavior change.
- Controllers that do not migrate continue to work. FormDataExtractor is an option, not a mandate, until adoption is complete.
- PATTERN_LOCK remains: controllers must use scoped input + normalization. FormDataExtractor is the canonical implementation of that pattern.

### No Big-Bang

- Do not refactor all 46 controllers in one change.
- Do not change form field names, routes, or validation rules during FormDataExtractor adoption.

---

## 9. What It Does NOT Solve

| Concern | Handled By |
|---------|------------|
| **Attachments / file uploads** | ProjectAttachmentHandler (Phase 2.2). IESAttachmentsController, IIESAttachmentsController, etc. do not use FormDataExtractor for file handling. |
| **Orchestration / data ownership** | FormSection / ownedKeys (Phase 2.4). FormDataExtractor does not route requests or define which controller owns which keys at the orchestration level. |
| **Reports / Monthly / Quarterly** | Out of Phase 2.1 scope. Report flows have different validation and storage. |
| **Export** | ExportController. FormDataExtractor operates on incoming request, not on data loading for export. |
| **Numeric overflow / bounds** | BoundedNumericService / DecimalBounds (Phase 2.3). FormDataExtractor does not clamp or validate numeric ranges. |
| **Role / permission checks** | RoleGuard (Phase 2.5). FormDataExtractor does not touch authorization. |

---

## Implementation Summary (Phase 2.1 Pilot)

**Date**: 2026-02-08

### Scope

- Implemented FormDataExtractor per design document.
- Pilot adoption in **one** controller only (IESPersonalInfoController).
- No other Phase 2 components touched.

### Files Created

| File | Description |
|------|--------------|
| `app/Services/FormDataExtractor.php` | Service with `extract()`, `forFillable()`, `normalize()` methods |

### Files Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/Projects/IES/IESPersonalInfoController.php` | Replaced inline `$request->only()` + `ArrayToScalarNormalizer` with `FormDataExtractor::forFillable()` |

### Pilot Location

- **Controller**: `App\Http\Controllers\Projects\IES\IESPersonalInfoController`
- **Method**: `store()` (invoked by `update()` via delegation)

### Old vs New Code Comparison

**Before (Phase 1A inline pattern):**

```php
use App\Support\Normalization\ArrayToScalarNormalizer;

$data = ArrayToScalarNormalizer::forFillable(
    $request->only($fillable),
    $fillable
);
```

**After (FormDataExtractor):**

```php
use App\Services\FormDataExtractor;

$data = FormDataExtractor::forFillable($request, $fillable);
```

### No Behavior Change

Output is identical for the same input. `FormDataExtractor::forFillable($request, $fillable)` with default (empty) normalizers performs:

1. `$request->only($fillable)` — same scoping
2. `ArrayToScalarNormalizer::forFillable($data, $fillable)` — same normalization

No mass assignment changes, no array-to-scalar handling changes, no validation rules changed.

### What Was NOT Changed

- Form field names, routes, validation rules
- Attachments or file handling
- ProjectAttachmentHandler, FormSection, RoleGuard, or BoundedNumericService
- FormRequest `prepareForValidation()` or `getNormalizedInput()`
- Database schema or models

---

## Pilot Adoption — IIESEducationBackgroundController

**Controller**: `App\Http\Controllers\Projects\IIES\EducationBackgroundController`  
**Methods touched**: `store()` (invoked by `update()` via delegation)

**Before:**
```php
$data = ArrayToScalarNormalizer::forFillable(
    $request->only($fillable),
    $fillable
);
```

**After:**
```php
$data = FormDataExtractor::forFillable($request, $fillable);
```

**No behavior change.** Output is identical; same scoping and ArrayToScalar normalization.

---

## Phase 2.1 — Pilot Verification

**Controllers verified:**
1. `IESPersonalInfoController` — `store()` (invoked by `update()` via delegation)
2. `IIESEducationBackgroundController` (`EducationBackgroundController`) — `store()` (invoked by `update()` via delegation)

**Flows exercised:**
- IES Create project → store personal info
- IES Edit project → update personal info
- IIES Create project → store education background
- IIES Edit project → update education background

**Logs:** No new errors in `storage/logs/laravel.log`; no warnings related to request normalization.

**Observed behavior matches Phase 1A inline normalization exactly.**

---

## Phase 2.1 Adoption — IESEducationBackgroundController

**Controller**: `App\Http\Controllers\Projects\IES\IESEducationBackgroundController`  
**Methods affected**: `store()` (invoked by `update()` via delegation)

**No behavior change vs Phase 1A.**

---

## Phase 2.1 Adoption — IESImmediateFamilyDetailsController

**Controller**: `App\Http\Controllers\Projects\IES\IESImmediateFamilyDetailsController`  
**Methods affected**: `store()` (invoked by `update()` via delegation)

**No behavior change vs Phase 1A.**

---

## Phase 2.1 Adoption — IAHPersonalInfoController

**Controller**: `App\Http\Controllers\Projects\IAH\IAHPersonalInfoController`  
**Methods affected**: `store()` (invoked by `update()` via delegation)

**No behavior change vs Phase 1A.**

---

## Phase 2.1 Adoption — IAHHealthConditionController

**Controller**: `App\Http\Controllers\Projects\IAH\IAHHealthConditionController`  
**Methods affected**: `store()` (invoked by `update()` via delegation)

**No behavior change vs Phase 1A.**

---

## Phase 2.1 Adoption — IAHSupportDetailsController

**Controller**: `App\Http\Controllers\Projects\IAH\IAHSupportDetailsController`  
**Methods affected**: `store()` (invoked by `update()` via delegation)

**No behavior change vs Phase 1A.**

---

## Phase 2.1 Adoption — ILP PersonalInfoController

**Controller**: `App\Http\Controllers\Projects\ILP\PersonalInfoController`  
**Methods affected**: `store()` (invoked by `update()` via delegation)

**No behavior change vs Phase 1A.**

---

## Phase 2.1 Adoption — ILP RiskAnalysisController

**Controller**: `App\Http\Controllers\Projects\ILP\RiskAnalysisController`  
**Methods affected**: `store()` (invoked by `update()` via delegation)

**No behavior change vs Phase 1A.**

---

## Phase 2.1 Adoption — IGE InstitutionInfoController

**Controller**: `App\Http\Controllers\Projects\IGE\InstitutionInfoController`  
**Methods affected**: `store()` (invoked by `update()` via delegation)

**No behavior change vs Phase 1A.**

---

## Phase 2.1 Adoption — IGE DevelopmentMonitoringController

**Controller**: `App\Http\Controllers\Projects\IGE\DevelopmentMonitoringController`  
**Methods affected**: `store()` (invoked by `update()` via delegation)

**No behavior change vs Phase 1A.**

---

## Phase 2.1 Adoption — CCI PresentSituationController

**Controller**: `App\Http\Controllers\Projects\CCI\PresentSituationController`  
**Methods affected**: `store()` (invoked by `update()` via delegation)

**No behavior change vs Phase 1A.**

---

## Phase 2.1 Adoption — CCI RationaleController

**Controller**: `App\Http\Controllers\Projects\CCI\RationaleController`  
**Methods affected**: `store()` (invoked by `update()` via delegation)

**No behavior change vs Phase 1A.**

---

## Phase 2.1 Adoption — RST InstitutionInfoController

**Controller**: `App\Http\Controllers\Projects\RST\InstitutionInfoController`  
**Methods affected**: `store()` (invoked by `update()` via delegation)

**No behavior change vs Phase 1A.**

---

## Phase 2.1 Adoption — RST TargetGroupController

**Controller**: `App\Http\Controllers\Projects\RST\TargetGroupController`  
**Methods affected**: `store()` (invoked by `update()` via delegation)

**No behavior change vs Phase 1A.**

---

## Phase 2.1 Adoption — LDP InterventionLogicController

**Controller**: `App\Http\Controllers\Projects\LDP\InterventionLogicController`  
**Methods affected**: `store()` (invoked by `update()` via delegation)

**No behavior change vs Phase 1A.**

---

## Phase 2.1 Adoption — ProjectEduRUTBasicInfoController

**Controller**: `App\Http\Controllers\Projects\ProjectEduRUTBasicInfoController`  
**Methods affected**: `store()` (invoked by `update()` via delegation)

**No behavior change vs Phase 1A.**

---

## Phase 2.1 Adoption — CICBasicInfoController

**Controller**: `App\Http\Controllers\Projects\CICBasicInfoController`  
**Methods affected**: `store()` (invoked by `update()` via delegation)

**No behavior change vs Phase 1A.**

---

## Phase 2.1 — Closure

- All eligible controllers with full Phase 1A pattern have been adopted.
- No remaining eligible controllers exist.
- IESExpensesController is explicitly excluded due to partial Phase 1A pattern.
- No further Phase 2.1 changes are permitted.

**Status:** Closed  
**Date:** 2026-02-08
