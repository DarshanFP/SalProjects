# Phase 1A — IAHPersonalInfoController

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
- **Class**: `App\Http\Controllers\Projects\IAH\IAHPersonalInfoController`
- **File**: `app/Http/Controllers/Projects/IAH/IAHPersonalInfoController.php`
- **Methods refactored**: `store()`, `update()` (delegates to `store()`)

## Anti-patterns Found
- `$validated = $request->all()` in `store()` and `update()` — pulled in full multi-step form data
- Manual assignment per field instead of scoped `fill()` — equivalent mass-assignment risk; arrays from other sections could pollute
- No array-to-scalar coercion — risk of "Array to string conversion" if overlapping field names send arrays

## Pattern Used
**Golden Template (Pattern A)** — Single-record controller; one personal info per project.

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Applied `ArrayToScalarNormalizer::forFillable($data, $fillable)` before `fill()`
- Excluded `project_id` and `IAH_info_id` from fillable (set by controller / auto-generated)
- Replaced manual field assignments with `$personalInfo->fill($data)`
- `update()` now delegates to `store()` — both paths use refactored input handling; store() preserves delete-then-create behavior (one row per project)

## Fillable Keys Used
`name`, `age`, `gender`, `dob`, `aadhar`, `contact`, `address`, `email`, `guardian_name`, `children`, `caste`, `religion`

## Excluded Keys
- `project_id` — set by controller
- `IAH_info_id` — auto-generated in model boot

## Files Modified
- `app/Http/Controllers/Projects/IAH/IAHPersonalInfoController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- Attachments, file handling
- Database schema
- `show()`, `edit()`, `destroy()`
- Transaction, logging, and JSON responses
- Delete-then-create behavior in `store()` (one personal info row per project)

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit IAH Personal Info flow works
- [ ] Log: no new errors for IAH project type
- [ ] Submit with empty optional fields → no error; nulls stored
- [ ] Submit with array-like values (if present) → normalized to scalar; no "Array to string conversion"

## Notes / Risks
- None identified. Model `$fillable` aligned with previous manual field list.
- `age` and `children` may receive numeric or string input; `ArrayToScalarNormalizer` coerces arrays only; scalar strings/numbers pass through unchanged.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
