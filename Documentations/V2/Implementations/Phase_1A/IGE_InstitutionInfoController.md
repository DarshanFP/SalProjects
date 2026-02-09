# Phase 1A — IGE InstitutionInfoController

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
- **Class**: `App\Http\Controllers\Projects\IGE\InstitutionInfoController`
- **File**: `app/Http/Controllers/Projects/IGE/InstitutionInfoController.php`
- **Methods refactored**: `store()` (invoked by `update()` via delegation)

## Anti-patterns Found
- `$validated = $request->all()` in `store()` — pulled in full multi-step form data
- Manual field array in `updateOrCreate()` — equivalent mass-assignment risk
- No array-to-scalar coercion — risk of "Array to string conversion" if overlapping field names send arrays

## Pattern Used
**Golden Template (Pattern A)** — Single-record controller; one institution info per project. Uses `updateOrCreate()`.

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Applied `ArrayToScalarNormalizer::forFillable($data, $fillable)` before `updateOrCreate()`
- Excluded `project_id` and `IGE_institution_id` from fillable (set by controller / auto-generated)
- Replaced manual field array with `$data` variable
- `update()` — still delegates to `store()`

## Fillable Keys Used
`institutional_type`, `age_group`, `previous_year_beneficiaries`, `outcome_impact`

## Excluded Keys
- `project_id` — set by controller in `updateOrCreate()` match
- `IGE_institution_id` — auto-generated in model boot

## Files Modified
- `app/Http/Controllers/Projects/IGE/InstitutionInfoController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- `show()`, `edit()`, `destroy()`
- Transaction, logging, redirect responses

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit IGE Institution Info flow works
- [ ] Log: no new errors for IGE project type
- [ ] Submit with empty optional fields → no error; nulls stored

## Notes / Risks
- None identified. Model `$fillable` aligned with previous manual field list.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
