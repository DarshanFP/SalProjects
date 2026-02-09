# Phase 1A — CCI AnnexedTargetGroupController

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
- **Class**: `App\Http\Controllers\Projects\CCI\AnnexedTargetGroupController`
- **File**: `app/Http/Controllers/Projects/CCI/AnnexedTargetGroupController.php`
- **Methods refactored**: `store()`, `update()`

## Anti-patterns Found
- `$validated = $request->all()` in `store()` and `update()` — pulled in full multi-step form data
- No scoped input — arrays from other sections could pollute or cause type issues
- No per-value scalar coercion when passing values to `create()` / `updateOrCreate()` — nested arrays could cause "Array to string conversion"

## Pattern Used
**Array-Pattern (Pattern B)** — Multi-record controller; iterates over `annexed_target_group[]` array. Each entry is an associative array; per-value scalar coercion applied.

## Refactor Applied
- Replaced `$request->all()` with `$request->only(['annexed_target_group'])`
- Scoped to owned key: `annexed_target_group`
- Added scalar-to-array normalization: when form sends scalar instead of array, wrap in single-element array
- Added `is_array($group)` guard — skip non-array entries
- Added per-value scalar coercion: when value at key is array, use `reset($value)` — prevents "Array to string conversion" in `create()` / `updateOrCreate()`
- Did NOT use `ArrayToScalarNormalizer::forFillable()` — this controller expects arrays and creates multiple records

## Fillable Keys Used (input scope)
`annexed_target_group`

## Per-row Keys (scalar coercion)
`beneficiary_name`, `dob`, `date_of_joining`, `class_of_study`, `family_background_description`

## Excluded Keys
- `project_id` — set by controller in each `create()` / `updateOrCreate()`
- `CCI_target_group_id` — auto-generated in model boot

## Files Modified
- `app/Http/Controllers/Projects/CCI/AnnexedTargetGroupController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- `show()`, `edit()`, `destroy()`
- Transaction, logging, redirect responses
- store() vs update() behavior: store creates new records; update uses updateOrCreate by project_id + beneficiary_name

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit CCI Annexed Target Group flow works
- [ ] Multiple entries save correctly
- [ ] Log: no new errors for CCI project type
- [ ] No "Array to string conversion" for `project_CCI_annexed_target_group`

## Notes / Risks
- Controller creates multiple records from nested array structure; different from IES parallel arrays but same Pattern B principles (scoped input + per-value scalar coercion).

## Date
- Refactored: 2026-02-08
- Verified: (pending)
