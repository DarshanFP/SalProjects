# Phase 1A — RST TargetGroupController

## Status
- [x] Refactored
- [ ] Merged
- [ ] Verified

## Reference
- Playbook: Phase_1A_Refactor_Playbook.md
- Plan: Phase_Wise_Refactor_Plan.md → Phase 1A.4
- Pattern Lock: PATTERN_LOCK.md
- Canonical: IESPersonalInfoController.md (Golden Template)

## Controller
- **Class**: `App\Http\Controllers\Projects\RST\TargetGroupController`
- **File**: `app/Http/Controllers/Projects/RST/TargetGroupController.php`
- **Methods refactored**: `store()`, `update()` (delegates to `store()`)

## Anti-patterns Found
- `$validated = $request->all()` in `store()` — pulled in full multi-step form data
- Manual update/create logic — equivalent to updateOrCreate
- No array-to-scalar coercion — risk of "Array to string conversion"

## Pattern Used
**Golden Template (Pattern A)** — Single-record controller; one target group per project. Uses `updateOrCreate()`.

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Applied `ArrayToScalarNormalizer::forFillable($data, $fillable)` before `updateOrCreate()`
- Excluded `project_id` and `RST_target_group_id` from fillable (set by controller / auto-generated)
- Replaced manual if/else with `updateOrCreate()`
- Removed redundant `generateTargetGroupId()` — model boot handles it

## Fillable Keys Used
`tg_no_of_beneficiaries`, `beneficiaries_description_problems`

## Excluded Keys
- `project_id` — set by controller in `updateOrCreate()` match
- `RST_target_group_id` — auto-generated in model boot

## Files Modified
- `app/Http/Controllers/Projects/RST/TargetGroupController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- `show()`, `edit()`, `destroy()`
- Transaction, logging, JSON responses

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit RST Target Group flow works
- [ ] Log: no new errors for RST project type
- [ ] Submit with empty optional fields → no error; nulls stored

## Notes / Risks
- None identified.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
