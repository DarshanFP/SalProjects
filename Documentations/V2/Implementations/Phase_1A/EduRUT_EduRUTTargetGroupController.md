# Phase 1A — EduRUT EduRUTTargetGroupController

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
- **Class**: `App\Http\Controllers\Projects\EduRUTTargetGroupController`
- **File**: `app/Http/Controllers/Projects/EduRUTTargetGroupController.php`
- **Methods refactored**: `store()`, `update()`

## Anti-patterns Found
- `$validatedData = $request->all()` in `store()` and `update()` — pulled in full multi-step form data
- No scoped input — arrays from other sections could pollute or cause type issues
- No per-value scalar coercion — nested arrays could cause "Array to string conversion"

## Pattern Used
**Array-Pattern (Pattern B)** — Multi-record controller; nested array `target_group[]` with keys `beneficiary_name`, `caste`, `institution_name`, `class_standard`, `total_tuition_fee`, `eligibility_scholarship`, `expected_amount`, `contribution_from_family`.

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Scoped to owned key: `target_group`
- Added scalar-to-array normalization when form sends scalar instead of array
- Added `is_array($group)` guard — skip non-array entries
- Added per-value scalar coercion: when value at key is array, use `reset($value)` — prevents "Array to string conversion"

## Fillable Keys Used (input scope)
`target_group`

## Per-row Keys (scalar coercion)
`beneficiary_name`, `caste`, `institution_name`, `class_standard`, `total_tuition_fee`, `eligibility_scholarship`, `expected_amount`, `contribution_from_family`

## Excluded Keys
- `project_id` — set by controller (from route param)
- `target_group_id` — auto-generated in model boot

## Files Modified
- `app/Http/Controllers/Projects/EduRUTTargetGroupController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- `show()`, `edit()`, `destroy()`, `uploadExcel()`
- Transaction, logging, JSON responses
- store() vs update() behavior: store appends; update delete-then-recreate

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit EduRUT Target Group flow works
- [ ] Multiple entries save correctly
- [ ] Log: no new errors for EduRUT project type
- [ ] No "Array to string conversion" for target group table

## Notes / Risks
- None identified.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
