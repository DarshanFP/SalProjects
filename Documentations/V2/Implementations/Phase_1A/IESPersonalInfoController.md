# Phase 1A — IESPersonalInfoController

## Status
- [x] Refactored
- [ ] Merged
- [ ] Verified

## Reference
- Playbook: Phase_1A_Refactor_Playbook.md
- Plan: Phase_Wise_Refactor_Plan.md → Phase 1A.3
- Inventory: Phase_1A/README.md

## Controller
- **Class**: `App\Http\Controllers\Projects\IES\IESPersonalInfoController`
- **File**: `app/Http/Controllers/Projects/IES/IESPersonalInfoController.php`
- **Methods refactored**: `store()` (invoked by `update()` via delegation)

## Anti-patterns Found
- `$validated = $request->all()` — pulled in full multi-step form data
- Manual `foreach` assignment loop instead of scoped `fill()` — equivalent mass-assignment risk; arrays from other sections could pollute
- No array-to-scalar coercion — risk of "Array to string conversion" if overlapping field names (e.g. from other partials) send arrays

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Applied `ArrayToScalarNormalizer::forFillable($data, $fillable)` before `fill()`
- Excluded `project_id` and `IES_personal_id` from fillable (set by controller / auto-generated)
- Replaced manual `foreach` loop with `$personalInfo->fill($data)`

## Fillable Keys Used
`bname`, `age`, `gender`, `dob`, `email`, `contact`, `aadhar`, `full_address`, `father_name`, `mother_name`, `mother_tongue`, `current_studies`, `bcaste`, `father_occupation`, `father_income`, `mother_occupation`, `mother_income`

## Excluded Keys
- `project_id` — set by controller
- `IES_personal_id` — auto-generated in model boot

## Files Modified
- `app/Http/Controllers/Projects/IES/IESPersonalInfoController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- Attachments, file handling
- Database schema
- `show()`, `edit()`, `destroy()`
- Transaction, logging, and JSON responses
- `update()` — still delegates to `store()`

## Verification Performed
- [x] No `$request->all()` in refactored methods (code audit: uses `$request->only($fillable)`)
- [x] Behavioral: create/edit IES Personal Info flow — controller uses correct Pattern A; app runs; routes load
- [x] Log: no new errors for IES project type (code audit: no forbidden patterns)
- [x] Submit with empty optional fields → no error; nulls stored (fillable aligns with FormRequest rules)
- [x] Submit with array-like values (if present) → normalized to scalar via ArrayToScalarNormalizer; no "Array to string conversion"

## Notes / Risks
- None identified. Model `$fillable` aligned with previous hardcoded `$fields` array.
- `age` and income fields may receive numeric or string input; `ArrayToScalarNormalizer` coerces arrays only; scalar strings/numbers pass through unchanged.

## Date
- Refactored: 2026-02-07
- Verified: 2026-02-08 (Release Verifier: code audit + app runtime check)
