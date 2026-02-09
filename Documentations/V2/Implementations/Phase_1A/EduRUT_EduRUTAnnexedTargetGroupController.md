# Phase 1A — EduRUT EduRUTAnnexedTargetGroupController

## Status
- [x] Refactored
- [ ] Merged
- [ ] Verified

## Reference
- Playbook: Phase_1A_Refactor_Playbook.md
- Plan: Phase_Wise_Refactor_Plan.md → Phase 1A.4
- Pattern Lock: PATTERN_LOCK.md
- Canonical: IESFamilyWorkingMembersController.md (Array-Pattern Reference), CCI_AnnexedTargetGroupController.md (nested array)

## Controller
- **Class**: `App\Http\Controllers\Projects\EduRUTAnnexedTargetGroupController`
- **File**: `app/Http/Controllers/Projects/EduRUTAnnexedTargetGroupController.php`
- **Methods refactored**: `store()`, `update()`

## Anti-patterns Found
- `$validated = $request->all()` in `store()` and `update()` — pulled in full multi-step form data
- No scoped input — arrays from other sections could pollute or cause type issues
- No per-value scalar coercion — nested arrays could cause "Array to string conversion"

## Pattern Used
**Array-Pattern (Pattern B)** — Multi-record controller; nested array `annexed_target_group[]` with keys `beneficiary_name`, `family_background`, `need_of_support`.

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- store(): scoped to `annexed_target_group`, `project_id` (project_id from request for store)
- update(): scoped to `annexed_target_group` (project_id from route)
- Added scalar-to-array normalization when form sends scalar instead of array
- Added `is_array($group)` guard — skip non-array entries
- Added per-value scalar coercion: when value at key is array, use `reset($value)` — prevents "Array to string conversion"

## Fillable Keys Used (input scope)
store: `annexed_target_group`, `project_id`; update: `annexed_target_group`

## Excluded Keys
- `project_id` — set by controller (from request in store, from route in update)
- `annexed_target_group_id` — auto-generated in model boot

## Files Modified
- `app/Http/Controllers/Projects/EduRUTAnnexedTargetGroupController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- `show()`, `edit()`, `destroy()`
- Transaction, logging, JSON responses
- store() vs update() behavior: store appends; update delete-then-recreate

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit EduRUT Annexed Target Group flow works
- [ ] Multiple entries save correctly
- [ ] Log: no new errors for EduRUT project type
- [ ] No "Array to string conversion" for `project_edu_rut_annexed_target_groups`

## Notes / Risks
- store() does not delete before create (appends); update() deletes then creates. Preserved existing behavior.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
