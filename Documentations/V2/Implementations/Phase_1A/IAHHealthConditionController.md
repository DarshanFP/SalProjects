# Phase 1A — IAHHealthConditionController

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
- **Class**: `App\Http\Controllers\Projects\IAH\IAHHealthConditionController`
- **File**: `app/Http/Controllers/Projects/IAH/IAHHealthConditionController.php`
- **Methods refactored**: `store()`, `update()` (delegates to `store()`)

## Anti-patterns Found
- `$validated = $request->all()` in `store()` and `update()` — pulled in full multi-step form data
- Manual assignment per field instead of scoped `fill()` — equivalent mass-assignment risk
- No array-to-scalar coercion — risk of "Array to string conversion" if overlapping field names send arrays

## Pattern Used
**Golden Template (Pattern A)** — Single-record controller; one health condition row per project.

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Applied `ArrayToScalarNormalizer::forFillable($data, $fillable)` before `fill()`
- Excluded `project_id` and `IAH_health_id` from fillable (set by controller / auto-generated)
- Replaced manual field assignments with `$healthCondition->fill($data)`
- `update()` now delegates to `store()` — both paths use refactored input handling

## Fillable Keys Used
`illness`, `treatment`, `doctor`, `hospital`, `doctor_address`, `health_situation`, `family_situation`

## Excluded Keys
- `project_id` — set by controller
- `IAH_health_id` — auto-generated in model boot

## Files Modified
- `app/Http/Controllers/Projects/IAH/IAHHealthConditionController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- Attachments, file handling
- Database schema
- `show()`, `edit()`, `destroy()`
- Transaction, logging, and JSON responses
- Delete-then-create behavior in `store()` (one row per project)

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit IAH Health Condition flow works
- [ ] Log: no new errors for IAH project type
- [ ] Submit with empty optional fields → no error; nulls stored

## Notes / Risks
- None identified. Model `$fillable` aligned with previous manual field list.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
