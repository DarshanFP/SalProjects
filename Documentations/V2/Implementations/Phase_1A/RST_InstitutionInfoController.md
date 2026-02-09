# Phase 1A — RST InstitutionInfoController

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
- **Class**: `App\Http\Controllers\Projects\RST\InstitutionInfoController`
- **File**: `app/Http/Controllers/Projects/RST/InstitutionInfoController.php`
- **Methods refactored**: `store()`, `update()` (delegates to `store()`)

## Anti-patterns Found
- `$validated = $request->all()` in `store()` and `update()` — pulled in full multi-step form data
- No array-to-scalar coercion — risk of "Array to string conversion"

## Pattern Used
**Golden Template (Pattern A)** — Single-record controller; one institution info per project. Uses `updateOrCreate()`.

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Applied `ArrayToScalarNormalizer::forFillable($data, $fillable)` before `updateOrCreate()`
- Excluded `project_id` and `RST_institution_id` from fillable (set by controller / auto-generated)
- `update()` delegates to `store()` — both paths unified; update no longer returns 404 if not found (creates via updateOrCreate)

## Fillable Keys Used
`year_setup`, `total_students_trained`, `beneficiaries_last_year`, `training_outcome`

## Excluded Keys
- `project_id` — set by controller in `updateOrCreate()` match
- `RST_institution_id` — auto-generated in model boot

## Files Modified
- `app/Http/Controllers/Projects/RST/InstitutionInfoController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- `show()`, `edit()`, `destroy()`
- Transaction, logging, JSON responses

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit RST Institution Info flow works
- [ ] Log: no new errors for RST project type
- [ ] Submit with empty optional fields → no error; nulls stored

## Notes / Risks
- Original update() returned 404 if record not found; now delegates to store() which uses updateOrCreate (creates if not exists). Aligns with other Phase 1A controllers.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
