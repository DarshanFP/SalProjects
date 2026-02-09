# Phase 1A — IGE OngoingBeneficiariesController

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
- **Class**: `App\Http\Controllers\Projects\IGE\OngoingBeneficiariesController`
- **File**: `app/Http/Controllers/Projects/IGE/OngoingBeneficiariesController.php`
- **Methods refactored**: `store()` (invoked by `update()` via delegation)

## Anti-patterns Found
- `$validated = $request->all()` in `store()` — pulled in full multi-step form data
- No scoped input — arrays from other sections could pollute or cause type issues
- No per-value scalar coercion when passing to `create()` — nested arrays could cause "Array to string conversion"

## Pattern Used
**Array-Pattern Reference (Pattern B)** — Multi-record controller; creates multiple rows from `obeneficiary_name[]`, `ocaste[]`, `oaddress[]`, `ocurrent_group_year_of_study[]`, `operformance_details[]`.

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Scoped to owned keys: `obeneficiary_name`, `ocaste`, `oaddress`, `ocurrent_group_year_of_study`, `operformance_details`
- Scalar-to-array normalization for all array keys
- Per-value scalar coercion in loop when passing to `create()`
- `update()` delegates to `store()` — preserved
- Original condition: `!is_null($name)` preserved (allows empty string rows)
- Did NOT use `ArrayToScalarNormalizer::forFillable()` — would collapse arrays

## Fillable Keys Used (input scope)
`obeneficiary_name`, `ocaste`, `oaddress`, `ocurrent_group_year_of_study`, `operformance_details`

## Excluded Keys
- `project_id` — set by controller in each `create()`

## Files Modified
- `app/Http/Controllers/Projects/IGE/OngoingBeneficiariesController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- `show()`, `edit()`, `destroy()`
- Transaction, logging, redirect responses

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit IGE Ongoing Beneficiaries flow works
- [ ] Multiple beneficiaries save correctly
- [ ] Log: no new errors for IGE project type
- [ ] No "Array to string conversion" for IGE ongoing beneficiaries table

## Notes / Risks
- Original used `!is_null($name)` (not `!empty()`) — allows empty string; preserved.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
