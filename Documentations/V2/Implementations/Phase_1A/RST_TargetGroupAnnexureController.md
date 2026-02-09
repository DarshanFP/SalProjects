# Phase 1A — RST TargetGroupAnnexureController

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
- **Class**: `App\Http\Controllers\Projects\RST\TargetGroupAnnexureController`
- **File**: `app/Http/Controllers/Projects/RST/TargetGroupAnnexureController.php`
- **Methods refactored**: `store()`, `update()` (delegates to `store()`)

## Anti-patterns Found
- `$validated = $request->all()` in `store()` and `update()` — pulled in full multi-step form data
- No scoped input — arrays from other sections could pollute or cause type issues
- No per-value scalar coercion — nested arrays could cause "Array to string conversion"

## Pattern Used
**Array-Pattern (Pattern B)** — Multi-record controller; parallel arrays `rst_name[]`, `rst_religion[]`, `rst_caste[]`, `rst_education_background[]`, `rst_family_situation[]`, `rst_paragraph[]`.

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Scoped to owned keys: `rst_name`, `rst_religion`, `rst_caste`, `rst_education_background`, `rst_family_situation`, `rst_paragraph`
- Added scalar-to-array normalization when form sends scalar instead of array
- Added per-value scalar coercion in loop: when value at index is array, use `reset($value)` — prevents "Array to string conversion"
- `update()` delegates to `store()` — both paths use delete-then-recreate

## Fillable Keys Used (input scope)
`rst_name`, `rst_religion`, `rst_caste`, `rst_education_background`, `rst_family_situation`, `rst_paragraph`

## Excluded Keys
- `project_id` — set by controller in each `create()`
- `target_group_anxr_id` — auto-generated in model boot

## Files Modified
- `app/Http/Controllers/Projects/RST/TargetGroupAnnexureController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- `show()`, `edit()`, `destroy()`
- Transaction, logging, JSON responses
- Delete-then-recreate pattern

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit RST Target Group Annexure flow works
- [ ] Multiple annexure entries save correctly
- [ ] Log: no new errors for RST project type
- [ ] No "Array to string conversion" for `project_RST_target_group_annexure`

## Notes / Risks
- Removed `if (!empty($rstNames))` guard — now iterates over empty array (no-op) when no names; equivalent behavior.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
