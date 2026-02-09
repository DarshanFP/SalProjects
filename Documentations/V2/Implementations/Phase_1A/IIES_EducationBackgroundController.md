# Phase 1A — IIES EducationBackgroundController

## Status
- [x] Refactored
- [ ] Merged
- [ ] Verified

## Reference
- Playbook: Phase_1A_Refactor_Playbook.md (via Phase_Wise_Implementation_Guide.md)
- Pattern Lock: PATTERN_LOCK.md
- Canonical: IESPersonalInfoController.md / IESEducationBackgroundController (Golden Template)

## Controller
- **Class**: `App\Http\Controllers\Projects\IIES\EducationBackgroundController`
- **File**: `app/Http/Controllers/Projects/IIES/EducationBackgroundController.php`
- **Methods refactored**: `store()`, `update()` (delegates to `store()`)

## Anti-patterns Found
- `$validated = $request->all()` in `store()` — pulled in full form data
- Manual `foreach` assignment loop in `update()` — equivalent mass-assignment risk; arrays from other sections could pollute
- No array-to-scalar coercion — risk of "Array to string conversion" if overlapping field names send arrays

## Pattern Used
**Golden Template (Pattern A)** — Single-record controller; one education background per project.

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Applied `ArrayToScalarNormalizer::forFillable($data, $fillable)` before `fill()`
- Excluded `project_id` and `IIES_education_id` from fillable (set by controller / auto-generated)
- Replaced `updateOrCreate()` with `first() ?: new` + `fill($data)` + `save()`
- Replaced manual `foreach` loop in `update()` with delegation to `store()`
- Removed `getEducationBackgroundFields()` private method (superseded by model `getFillable()`)

## Fillable Keys Used
`prev_education`, `prev_institution`, `prev_insti_address`, `prev_marks`, `current_studies`, `curr_institution`, `curr_insti_address`, `aspiration`, `long_term_effect`

## Excluded Keys
- `project_id` — set by controller
- `IIES_education_id` — auto-generated in model boot

## Files Modified
- `app/Http/Controllers/Projects/IIES/EducationBackgroundController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- Attachments, file handling
- Database schema
- `show()`, `edit()`, `destroy()`
- Transaction, logging, and JSON responses

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit IIES Education Background flow works
- [ ] Log: no new errors for IIES project type
- [ ] Submit with empty optional fields → no error; nulls stored
- [ ] Submit with array-like values (if present) → normalized to scalar; no "Array to string conversion"

## Notes / Risks
- None identified. Model `$fillable` aligned with previous hardcoded fields.
- `update()` now delegates to `store()`; both routes use their respective FormRequest validation before reaching controller.

## Date
- Refactored: 2026-02-07
- Verified: (pending)
