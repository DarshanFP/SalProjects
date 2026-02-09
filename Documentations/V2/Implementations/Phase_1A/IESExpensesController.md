# Phase 1A — IESExpensesController

## Status
- [x] Refactored
- [ ] Merged
- [ ] Verified

## Reference
- Playbook: Phase_1A_Refactor_Playbook.md
- Plan: Phase_Wise_Refactor_Plan.md → Phase 1A.3
- Inventory: Phase_1A/README.md

## Controller
- **Class**: `App\Http\Controllers\Projects\IES\IESExpensesController`
- **File**: `app/Http/Controllers/Projects/IES/IESExpensesController.php`
- **Methods refactored**: `store()` (invoked by `update()` via delegation)

## Anti-patterns Found
- `$validated = $request->all()` — pulled in full multi-step form data
- Manual assignment for header fields instead of scoped `fill()`
- No array-to-scalar coercion for particulars/amounts when creating expense details

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Header fillable: `total_expenses`, `expected_scholarship_govt`, `support_other_sources`, `beneficiary_contribution`, `balance_requested`
- Arrays: `particulars`, `amounts`
- Applied `ArrayToScalarNormalizer::forFillable()` for header data before `$projectExpenses->fill($headerData)`
- Replaced manual assignment with `fill($headerData)`
- Added scalar-to-array normalization for particulars/amounts (single-item forms)
- Added per-value scalar coercion in loop when creating expense details

## Fillable Keys Used (header)
`total_expenses`, `expected_scholarship_govt`, `support_other_sources`, `beneficiary_contribution`, `balance_requested`

## Array Keys (expense details)
`particulars`, `amounts`

## Excluded Keys
- `project_id` — set by controller
- `IES_expense_id` — auto-generated in model boot

## Files Modified
- `app/Http/Controllers/Projects/IES/IESExpensesController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- Attachments, file handling
- Database schema
- `show()`, `edit()`, `destroy()`
- Transaction, logging, and JSON responses
- `update()` — still delegates to `store()`
- BudgetSyncGuard check, BudgetSyncService sync, delete-then-recreate pattern

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit IES Expenses flow works
- [ ] Header totals and expense details save correctly
- [ ] Log: no new errors for IES project type
- [ ] No "Array to string conversion" for `project_IES_expenses` or `project_IES_expense_details`

## Notes / Risks
- Split pattern: header uses fill() + ArrayToScalarNormalizer; details use array iteration + per-value scalar coercion.

## Date
- Refactored: 2026-02-07
- Verified: (pending)
