# Phase 1A — ILP RevenueGoalsController

## Status
- [x] Refactored
- [ ] Merged
- [ ] Verified

## Reference
- Playbook: Phase_1A_Refactor_Playbook.md
- Plan: Phase_Wise_Refactor_Plan.md → Phase 1A.4
- Pattern Lock: PATTERN_LOCK.md
- Canonical: IESFamilyWorkingMembersController.md (Array-Pattern Reference)

## Controller
- **Class**: `App\Http\Controllers\Projects\ILP\RevenueGoalsController`
- **File**: `app/Http/Controllers/Projects/ILP/RevenueGoalsController.php`
- **Methods refactored**: `store()`, `update()`

## Anti-patterns Found
- `$validated = $request->all()` in `store()` and `update()` — pulled in full multi-step form data
- No scoped input — arrays from other sections could pollute or cause type issues
- No per-value scalar coercion when passing nested values to `create()` — nested arrays could cause "Array to string conversion"

## Pattern Used
**Array-Pattern Reference (Pattern B)** — Multi-record controller; creates rows from `business_plan_items[]`, `annual_income[]`, `annual_expenses[]` (each item is associative array with item/description/year_* keys).

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Scoped to owned keys: `business_plan_items`, `annual_income`, `annual_expenses`
- Scalar-to-array normalization for the three arrays
- Per-value scalar coercion in loops — nested values (e.g. `$item['item']`, `$item['year_1']`) use `reset()` when array
- `store()` keeps create-only (no delete) — original behavior preserved
- `update()` keeps delete-then-create — original behavior preserved

## Fillable Keys Used (input scope)
`business_plan_items`, `annual_income`, `annual_expenses`

## Excluded Keys
- `project_id` — set by controller in each `create()`

## Files Modified
- `app/Http/Controllers/Projects/ILP/RevenueGoalsController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- store() create-only vs update() delete-then-create behavior
- `show()`, `edit()`, `destroy()`
- Transaction, logging, and JSON responses

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit ILP Revenue Goals flow works
- [ ] Log: no new errors for ILP project type
- [ ] No "Array to string conversion" for ILP revenue tables

## Notes / Risks
- Nested array structure (item/income/expense with item, description, year_* keys) requires per-value coercion in loops.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
