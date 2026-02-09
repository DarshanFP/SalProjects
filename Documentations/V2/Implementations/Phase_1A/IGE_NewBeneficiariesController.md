# Phase 1A — IGE NewBeneficiariesController

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
- **Class**: `App\Http\Controllers\Projects\IGE\NewBeneficiariesController`
- **File**: `app/Http/Controllers/Projects/IGE/NewBeneficiariesController.php`
- **Methods refactored**: `store()` (invoked by `update()` via delegation)

## Anti-patterns Found
- `$validated = $request->all()` in `store()` — pulled in full multi-step form data
- No scoped input — arrays from other sections could pollute or cause type issues
- No per-value scalar coercion when passing to `create()` — nested arrays could cause "Array to string conversion"

## Pattern Used
**Array-Pattern Reference (Pattern B)** — Multi-record controller; creates multiple rows from `beneficiary_name[]`, `caste[]`, `address[]`, `group_year_of_study[]`, `family_background_need[]`.

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Scoped to owned keys: `beneficiary_name`, `caste`, `address`, `group_year_of_study`, `family_background_need`
- Scalar-to-array normalization for all array keys
- Per-value scalar coercion in loop when passing to `create()`
- `update()` delegates to `store($request, $projectId, false)` — preserved
- Transaction nesting logic (inTransaction, shouldRedirect) unchanged
- Did NOT use `ArrayToScalarNormalizer::forFillable()` — would collapse arrays

## Fillable Keys Used (input scope)
`beneficiary_name`, `caste`, `address`, `group_year_of_study`, `family_background_need`

## Excluded Keys
- `project_id` — set by controller in each `create()`

## Files Modified
- `app/Http/Controllers/Projects/IGE/NewBeneficiariesController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- Transaction nesting (inTransaction check for ProjectController@update)
- shouldRedirect parameter and redirect/return true behavior
- `show()`, `edit()`, `destroy()`
- Logging, error handling

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit IGE New Beneficiaries flow works
- [ ] Multiple beneficiaries save correctly
- [ ] Log: no new errors for IGE project type
- [ ] No "Array to string conversion" for IGE new beneficiaries table

## Notes / Risks
- Controller may be called from ProjectController@update within a transaction; nesting logic preserved.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
