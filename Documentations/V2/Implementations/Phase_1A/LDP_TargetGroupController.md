# Phase 1A — LDP TargetGroupController

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
- **Class**: `App\Http\Controllers\Projects\LDP\TargetGroupController`
- **File**: `app/Http/Controllers/Projects/LDP/TargetGroupController.php`
- **Methods refactored**: `store()`, `update()` (delegates to `store()`)

## Anti-patterns Found
- `$validated = $request->all()` in `store()` — pulled in full multi-step form data
- No scoped input — arrays from other sections could pollute or cause type issues
- No per-value scalar coercion — nested arrays could cause "Array to string conversion"

## Pattern Used
**Array-Pattern (Pattern B)** — Multi-record controller; parallel arrays `L_beneficiary_name[]`, `L_family_situation[]`, `L_nature_of_livelihood[]`, `L_amount_requested[]`.

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Scoped to owned keys: `L_beneficiary_name`, `L_family_situation`, `L_nature_of_livelihood`, `L_amount_requested`
- Added scalar-to-array normalization when form sends scalar instead of array
- Added per-value scalar coercion in loop: when value at index is array, use `reset($value)` — prevents "Array to string conversion"
- Preserved skip logic: only create if at least one field is non-null

## Fillable Keys Used (input scope)
`L_beneficiary_name`, `L_family_situation`, `L_nature_of_livelihood`, `L_amount_requested`

## Excluded Keys
- `project_id` — set by controller in each `create()`
- `LDP_target_group_id` — auto-generated in model boot

## Files Modified
- `app/Http/Controllers/Projects/LDP/TargetGroupController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- `show()`, `edit()`, `destroy()`
- Transaction, logging, redirect responses
- Delete-then-recreate pattern
- Skip-empty-row logic (create only if at least one field non-null)

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit LDP Target Group flow works
- [ ] Multiple entries save correctly; empty rows skipped
- [ ] Log: no new errors for LDP project type
- [ ] No "Array to string conversion" for `project_LDP_target_group`

## Notes / Risks
- None identified.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
