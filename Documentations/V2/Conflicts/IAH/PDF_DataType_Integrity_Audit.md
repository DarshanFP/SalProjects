# PDF Data Type Integrity Audit — IAH Module

**Date:** 2026-02-12  
**Trigger:** Production error during PDF generation for Individual - Access to Health (IAH) projects  
**Scope:** Static analysis, no code changes

---

## 1. Production Error Summary

| Field | Value |
|-------|-------|
| **Error** | `Attempt to read property "amount" on true` |
| **Location** | `resources/views/projects/partials/Show/IAH/budget_details.blade.php:20` |
| **Trigger** | `ExportController@downloadPdf()` |
| **Project Type** | Individual - Access to Health (IAH) |

**Interpretation:** A boolean (`true`) is being passed where an object with an `amount` property is expected. The view iterates expecting model instances but receives scalar values.

---

## 2. Root Cause (Exact Code Location)

### 2.1 Primary Cause

**File:** `app/Http/Controllers/Projects/ExportController.php`  
**Method:** `loadIAHBudgetDetails()`  
**Lines:** 768–770

```php
private function loadIAHBudgetDetails($project_id) {
    return ProjectIAHBudgetDetails::where('project_id', $project_id)->first();
}
```

**Issue:** The method returns `->first()` (a single model or `null`), but the view expects a **collection** of budget detail rows.

### 2.2 View Expectation

**File:** `resources/views/projects/partials/Show/IAH/budget_details.blade.php`

The partial expects `$IAHBudgetDetails` to be a collection:

- Line 8: `$IAHBudgetDetails->count()` — collection method
- Line 18: `@foreach($IAHBudgetDetails as $budget)` — iterates over collection items
- Line 21: `$budget->amount` — each `$budget` must be a model
- Line 31: `$IAHBudgetDetails->sum('amount')` — collection method
- Line 34–43: `$IAHBudgetDetails->first()` — collection method

### 2.3 Type Corruption Mechanism

When `$IAHBudgetDetails` is a **single Eloquent model** (from `->first()`):

1. `$IAHBudgetDetails->count()` does not exist on a model; Laravel may defer to a different code path or the condition may behave unexpectedly.
2. `@foreach($IAHBudgetDetails as $budget)` iterates over the model's **attributes** (key-value pairs).
3. Each iteration yields `$budget` = the **value** of an attribute (scalar: string, number, boolean).
4. If any attribute value is `true` (e.g. from a boolean column or cast), then `$budget` = `true`.
5. `$budget->amount` then triggers: **Attempt to read property "amount" on true**.

### 2.4 Correct Data Source (ProjectController)

**File:** `app/Http/Controllers/Projects/ProjectController.php`  
**Lines:** 1021–1022

```php
$data['IAHBudgetDetails'] = $this->iahBudgetDetailsController->show($project->project_id);
```

**File:** `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php`  
**Method:** `show()`  
**Lines:** 125–136

```php
$budgetDetails = ProjectIAHBudgetDetails::where('project_id', $projectId)->get();
return $budgetDetails;  // Returns collection
```

The web show flow uses `->get()` (collection); the PDF export flow uses `->first()` (single model).

---

## 3. Why It Fails in PDF but Not Web View

| Aspect | Web View (ProjectController) | PDF Export (ExportController) |
|--------|----------------------------|-------------------------------|
| **Data source** | `iahBudgetDetailsController->show()` | `loadIAHBudgetDetails()` |
| **Query** | `->get()` | `->first()` |
| **Return type** | `Collection` of models | Single model or `null` |
| **View usage** | `@foreach` over models | `@foreach` over model attributes → scalars |
| **Result** | Each `$budget` is a model | Each `$budget` is a scalar (e.g. `true`) |

The web view uses the same partial but receives data from a different path that correctly returns a collection.

---

## 4. Similar Risks Found in Other Modules

### 4.1 ExportController Load Methods — Return Type Analysis

| Load Method | Returns | View Expects | Match? |
|-------------|---------|--------------|--------|
| `loadIAHBudgetDetails` | `->first()` (model) | Collection | ❌ **MISMATCH** |
| `loadIAHDocuments` | `->first()` (model) | Model (documents partial uses `$IAHDocuments` as single record) | ✅ |
| `loadIAHEarningMembers` | `->get()` (collection) | Collection | ✅ |
| `loadIGEBudget` | `->first()` (model) | Collection (IGE budget partial expects `$IGEbudget` collection) | ❌ **MISMATCH** |
| `loadILPBudget` | Array with `'budgets' => ->get()` | `$ILPBudgets['budgets']` | ✅ |
| `loadIESExpenses` | `->first()` (model) | Model (partial uses `$project->iesExpenses`) | ✅ (via relation) |

### 4.2 IGE Variable Name Mismatch (Documented)

**File:** `resources/views/projects/partials/Show/IGE/budget.blade.php`  
**Expects:** `$IGEbudget` (collection)

**ExportController** for IGE (uses controller, not load*):

```php
$data['budget'] = $this->igeBudgetController->show($project->project_id);
```

**Result:** View receives `$budget`, not `$IGEbudget` → undefined variable (Production_Forensic_Review_10022026).

### 4.3 PDF Include Contract

**File:** `resources/views/projects/Oldprojects/pdf.blade.php`  
**Pattern:** `@include('projects.partials.Show.IAH.budget_details')` — no explicit variable passing.

Included partials inherit all variables from the parent. The parent receives `$data` from `ExportController::downloadPdf`, so keys like `IAHBudgetDetails`, `project`, `projectRoles` become `$IAHBudgetDetails`, `$project`, `$projectRoles`.

---

## 5. Risk Assessment Table

| Module | Controller | Risk Level | Issue Type | Description |
|--------|------------|------------|------------|-------------|
| **IAH** | ExportController | **HIGH** | Wrong return type | `loadIAHBudgetDetails` returns `->first()`; view expects collection; `@foreach` yields scalars → crash on `$budget->amount` |
| **IGE** | ExportController | **HIGH** | Variable name mismatch | Passes `$data['budget']`; view expects `$IGEbudget` → undefined variable |
| **IAH** | ExportController | MEDIUM | Potential null | `loadIAHDocuments` returns `->first()`; documents partial has null guard |
| **IES** | ExportController | LOW | Relation-based | Partial uses `$project->iesExpenses`; project may not have relation loaded in PDF path |
| **ILP** | ExportController | LOW | Complex structure | `loadILPBudget` returns array; partial extracts `$ILPBudgets['budgets']` with fallbacks |
| **IIES** | ExportController | LOW | Single-model partials | Most IIES partials expect single models; loaders use `->first()` |
| **CCI, RST, etc.** | ExportController | LOW | Controller show | Uses controller->show() which returns correct types |

---

## 6. Immediate Fix Plan

### 6.1 IAH Budget Details (Critical)

**Change:** `loadIAHBudgetDetails` must return a collection.

**Current (ExportController.php:768–770):**
```php
private function loadIAHBudgetDetails($project_id) {
    return ProjectIAHBudgetDetails::where('project_id', $project_id)->first();
}
```

**Proposed:**
```php
private function loadIAHBudgetDetails($project_id) {
    return ProjectIAHBudgetDetails::where('project_id', $project_id)->get();
}
```

**Alternative:** Use `$this->iahBudgetDetailsController->show($project_id)` to align with ProjectController and avoid duplicated logic.

### 6.2 Null-Safe Blade Guards (Defensive)

In `budget_details.blade.php`, add a guard before the `@foreach`:

```blade
@if($IAHBudgetDetails && ($IAHBudgetDetails instanceof \Illuminate\Support\Collection) && $IAHBudgetDetails->count())
```

Or: ensure `$IAHBudgetDetails` is always a collection (empty when no data):

```php
$data['IAHBudgetDetails'] = ProjectIAHBudgetDetails::where('project_id', $project_id)->get();
```

### 6.3 IGE Budget (Variable Name)

**Option A:** Pass `IGEbudget` in `$data` for IGE projects:
```php
$data['IGEbudget'] = $this->igeBudgetController->show($project->project_id);
```
(And remove or rename `$data['budget']` if unused.)

**Option B:** Update the IGE budget partial to accept `$budget` when `$IGEbudget` is not set:
```blade
@php $IGEbudget = $IGEbudget ?? $budget ?? collect(); @endphp
```

---

## 7. Structural Refactor Plan

### 7.1 Centralize Project Data Hydration

**Problem:** `ExportController::loadAllProjectData` and `ProjectController::show` load the same data in different ways.

**Proposed:** Introduce a shared service or trait:

```php
// Example: ProjectDataHydrator::hydrateForShow($project_id, $project_type)
// Returns array with keys matching view expectations (IAHBudgetDetails, IGEbudget, etc.)
```

Both `ProjectController::show` and `ExportController::downloadPdf` would call this hydrator.

### 7.2 Align ExportController with ProjectController

For IAH, use the same controller methods:

```php
case 'Individual - Access to Health':
    $data['IAHBudgetDetails'] = $this->iahBudgetDetailsController->show($project->project_id);
    $data['IAHDocuments'] = $this->iahDocumentsController->show($project->project_id) ?? [];
    // ... etc.
```

This removes the custom `loadIAH*` methods for IAH and guarantees type consistency.

### 7.3 View Contract Documentation

Define a contract per partial:

| Partial | Required Variables | Type |
|---------|--------------------|------|
| `IAH/budget_details` | `$IAHBudgetDetails` | `Collection<ProjectIAHBudgetDetails>` |
| `IGE/budget` | `$IGEbudget` | `Collection<ProjectIGEBudget>` |
| `ILP/budget` | `$ILPBudgets` | `array` with `budgets`, `total_amount`, etc. |

---

## 8. Long-Term Hardening Plan

### 8.1 Strict Type Expectations

- Add return type hints to load methods: `Collection`, `?Model`, etc.
- Use DTOs or ViewModels for export data to enforce structure.

### 8.2 Avoid Boolean-Returning Helpers in View Context

- Do not use `->exists()` or `->count()` as the sole source of data passed to views.
- Prefer `->get()` or `->first()` with explicit null handling.

### 8.3 Logging for Type Mismatch Detection

In development/staging, log when a non-collection is passed to a partial that expects a collection:

```php
if (!$data['IAHBudgetDetails'] instanceof \Illuminate\Support\Collection) {
    Log::warning('IAHBudgetDetails passed to PDF is not a collection', [
        'type' => gettype($data['IAHBudgetDetails']),
        'project_id' => $project_id
    ]);
}
```

### 8.4 ViewModel Pattern for Exports

Create export-specific DTOs that always provide the expected shape:

```php
class IAHExportData {
    public function __construct(
        public Collection $budgetDetails,
        public ?ProjectIAHDocuments $documents,
        // ...
    ) {}
}
```

---

## 9. Files Referenced

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Projects/ExportController.php` | PDF generation; `loadIAHBudgetDetails`, `loadAllProjectData` |
| `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php` | Correct `show()` returns `->get()` |
| `app/Http/Controllers/Projects/ProjectController.php` | Web show; uses controller->show for IAH |
| `resources/views/projects/partials/Show/IAH/budget_details.blade.php` | Expects collection; line 20 crash |
| `resources/views/projects/Oldprojects/pdf.blade.php` | Includes partials; passes `$data` |
| `app/Models/OldProjects/IAH/ProjectIAHBudgetDetails.php` | Model schema |

---

## 10. Related Documentation

| Document | Content |
|----------|---------|
| `Documentations/V2/ERRORS10022026/Production_Forensic_Review_10022026.md` | IGE `$IGEbudget` undefined; PDF export failures |
| `Documentations/V1/A-issues/Frontend_Backend_Contract_Audit.md` | Variable name mismatches; view-controller contract |
| `Documentations/V1/A-issues/Production_Log_Review_3031.md` | Issue 7: Undefined variable `$IGEbudget` |

---

*End of audit.*
