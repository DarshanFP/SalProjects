# Phase 1A — IGEBudgetController

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
- **Class**: `App\Http\Controllers\Projects\IGE\IGEBudgetController`
- **File**: `app/Http/Controllers/Projects/IGE/IGEBudgetController.php`
- **Methods refactored**: `store()` (invoked by `update()` via delegation)

## Anti-patterns Found
- `$validated = $request->all()` in `store()` — pulled in full multi-step form data
- No scoped input — arrays from other sections could pollute or cause type issues
- No per-value scalar coercion when passing to `create()` — nested arrays could cause "Array to string conversion"

## Pattern Used
**Array-Pattern Reference (Pattern B)** — Multi-record controller; creates multiple budget rows from `name[]`, `study_proposed[]`, `college_fees[]`, `hostel_fees[]`, `total_amount[]`, `scholarship_eligibility[]`, `family_contribution[]`, `amount_requested[]`.

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Scoped to owned keys: `name`, `study_proposed`, `college_fees`, `hostel_fees`, `total_amount`, `scholarship_eligibility`, `family_contribution`, `amount_requested`
- Scalar-to-array normalization for all array keys
- Per-value scalar coercion in loop when passing to `create()`
- `update()` already delegates to `store()`
- Did NOT use `ArrayToScalarNormalizer::forFillable()` — would collapse arrays

## Fillable Keys Used (input scope)
`name`, `study_proposed`, `college_fees`, `hostel_fees`, `total_amount`, `scholarship_eligibility`, `family_contribution`, `amount_requested`

## Excluded Keys
- `project_id` — set by controller in each `create()`

## Files Modified
- `app/Http/Controllers/Projects/IGE/IGEBudgetController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- BudgetSyncGuard, BudgetSyncService, BudgetAuditLogger
- Delete-then-recreate pattern; BudgetSyncService::syncFromTypeSave()
- `show()`, `edit()`, `destroy()`
- Transaction, logging, redirect responses

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit IGE Budget flow works
- [ ] Multiple budget rows save correctly
- [ ] Log: no new errors for IGE project type
- [ ] No "Array to string conversion" for IGE budget table

## Notes / Risks
- Controller creates multiple records from arrays; Pattern B. Uses redirect() not JSON.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
