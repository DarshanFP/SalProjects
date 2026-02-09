# Phase 1A — IAHEarningMembersController

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
- **Class**: `App\Http\Controllers\Projects\IAH\IAHEarningMembersController`
- **File**: `app/Http/Controllers/Projects/IAH/IAHEarningMembersController.php`
- **Methods refactored**: `store()`, `update()` (delegates to `store()`)

## Anti-patterns Found
- `$validated = $request->all()` in `store()` and `update()` — pulled in full multi-step form data
- No scoped input — arrays from other sections could pollute or cause type issues
- No per-value scalar coercion when passing to `create()` — nested arrays could cause "Array to string conversion"

## Pattern Used
**Array-Pattern Reference (Pattern B)** — Multi-record controller; creates multiple rows from `member_name[]`, `work_type[]`, `monthly_income[]`.

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Scoped to owned keys: `member_name`, `work_type`, `monthly_income`
- Scalar-to-array normalization — single-item forms wrapped in array
- Per-value scalar coercion in loop: when value at index is array, use `reset($value)` — prevents "Array to string conversion" in `create()`
- `update()` delegates to `store()` — both paths use refactored input handling
- Did NOT use `ArrayToScalarNormalizer::forFillable()` — would collapse arrays to single value

## Fillable Keys Used (input scope)
`member_name`, `work_type`, `monthly_income`

## Excluded Keys
- `project_id` — set by controller in each `create()`
- `IAH_earning_id` — auto-generated in model boot

## Files Modified
- `app/Http/Controllers/Projects/IAH/IAHEarningMembersController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- Delete-then-recreate pattern
- `show()`, `edit()`, `destroy()`
- Transaction, logging, and JSON responses

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit IAH Earning Members flow works
- [ ] Multiple members save correctly
- [ ] Log: no new errors for IAH project type
- [ ] No "Array to string conversion" for `project_IAH_earning_members`

## Notes / Risks
- Controller creates multiple records from arrays; Pattern B.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
