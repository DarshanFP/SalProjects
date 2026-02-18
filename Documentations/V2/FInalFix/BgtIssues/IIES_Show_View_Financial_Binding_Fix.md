# IIES Show View — General Info Financial Binding Fix

**Objective:** Fix show view for IIES (and all project types) so that General Information financial fields use `resolvedFundFields` instead of legacy budget variables. Universal rendering; no project-type branching for finances.

**Scope:** View binding only. Resolver, controller logic, and DB unchanged.

---

## 1) Old binding source

- Financial values were derived from a local `$rf` (alias of `$resolvedFundFields`) and intermediate PHP variables: `$budget_overall`, `$budget_forwarded`, `$budget_local`, `$amount_requested`, `$amount_sanctioned`, `$opening_balance`.
- Display used `format_indian_currency($var, 2)` with those variables.
- Source was already `$resolvedFundFields` (no raw `$project->overall_project_budget` etc. in this blade); the change is to bind **directly** to `$resolvedFundFields` and use a single, explicit format.

---

## 2) New binding source

All General Info financial display now uses **canonical binding**:

| Field | New binding |
|-------|-------------|
| Overall Project Budget | `{{ number_format($resolvedFundFields['overall_project_budget'] ?? 0, 2) }}` |
| Amount Forwarded (Existing Funds) | `{{ number_format($resolvedFundFields['amount_forwarded'] ?? 0, 2) }}` |
| Local Contribution | `{{ number_format($resolvedFundFields['local_contribution'] ?? 0, 2) }}` |
| Amount Requested | `{{ number_format($resolvedFundFields['amount_requested'] ?? 0, 2) }}` |
| Amount Sanctioned | `{{ number_format($resolvedFundFields['amount_sanctioned'] ?? 0, 2) }}` (row shown only when approved) |
| Opening Balance | `{{ number_format($resolvedFundFields['opening_balance'] ?? 0, 2) }}` |

- No intermediate variables for these fields.
- Top of block: `$resolvedFundFields = $resolvedFundFields ?? [];` so the variable exists for all project types.
- **Amount Sanctioned row** is wrapped in `@if($project->isApproved())` so it is shown only for approved projects (any approved status: approved_by_coordinator, etc.).

---

## 3) Confirmation — Resolver untouched

- **ProjectFinancialResolver:** Not modified.
- **DirectMappedIndividualBudgetStrategy / PhaseBasedBudgetStrategy:** Not modified.
- **applyCanonicalSeparation / canonical separation logic:** Not modified.
- **ProjectController@show** (data passed to view): Not modified; it already passes `resolvedFundFields` from the resolver.

---

## 4) Confirmation — All project types unified

- **No** `@if($project->project_type == 'Individual - Initial - Educational support')` (or similar) around financial fields in this blade. There was none to remove.
- The same General Info partial is included for all project types from `projects.Oldprojects.show`; financial rows use `$resolvedFundFields` for every type (IIES, IES, CCI, IGE, etc.). No branching by project type for these fields.

---

## 5) Before vs After behavior

| Aspect | Before | After |
|--------|--------|--------|
| Source of financial values | `$resolvedFundFields` via `$rf` and local variables | Direct `$resolvedFundFields['key']` in template |
| Format | `format_indian_currency($var, 2)` | `number_format($resolvedFundFields['key'] ?? 0, 2)` |
| Amount Sanctioned visibility | Row inside `@if($project->isApproved())` | Unchanged: row inside `@if($project->isApproved())` |
| IIES-specific logic | None in this partial | None; universal rendering |
| Project types | Same partial for all | Same; no type-based conditional for finances |

**Result:** IIES (and every type) now shows General Info financial fields from the resolver only; no legacy `$project->overall_project_budget` or `$budget->*` in this blade. Amount Sanctioned remains hidden for non-approved projects.
