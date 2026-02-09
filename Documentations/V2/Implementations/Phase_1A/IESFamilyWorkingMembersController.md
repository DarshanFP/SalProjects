# Phase 1A — IESFamilyWorkingMembersController

## Status
- [x] Refactored
- [ ] Merged
- [ ] Verified

## Reference
- Playbook: Phase_1A_Refactor_Playbook.md
- Plan: Phase_Wise_Refactor_Plan.md → Phase 1A.3
- Inventory: Phase_1A/README.md

## Controller
- **Class**: `App\Http\Controllers\Projects\IES\IESFamilyWorkingMembersController`
- **File**: `app/Http/Controllers/Projects/IES/IESFamilyWorkingMembersController.php`
- **Methods refactored**: `store()` (invoked by `update()` via delegation)

## Anti-patterns Found
- `$validated = $request->all()` — pulled in full multi-step form data
- No scoped input — arrays from other sections could pollute or cause type issues
- No array-to-scalar coercion when passing values to `create()` — nested arrays could cause "Array to string conversion"

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Scoped to owned keys: `member_name`, `work_nature`, `monthly_income`
- Added `is_array()` guards when extracting arrays — ensures iteration over arrays only
- Added scalar coercion in loop: when value at index is array, use `reset($value)` — prevents "Array to string conversion" in `create()`
- Did NOT use `ArrayToScalarNormalizer::forFillable()` — this controller expects arrays (member_name[], work_nature[], monthly_income[]) and creates multiple records; normalizer would collapse to single record

## Fillable Keys Used (input scope)
`member_name`, `work_nature`, `monthly_income`

## Excluded Keys
- `project_id` — set by controller in each `create()`
- `IES_family_member_id` — auto-generated in model boot

## Files Modified
- `app/Http/Controllers/Projects/IES/IESFamilyWorkingMembersController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- Attachments, file handling
- Database schema
- `show()`, `edit()`, `destroy()`
- Transaction, logging, and JSON responses
- `update()` — still delegates to `store()`
- Delete-then-recreate pattern; loop logic

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit IES Family Working Members flow works
- [ ] Multiple members save correctly
- [ ] Log: no new errors for IES project type
- [ ] No "Array to string conversion" for `project_IES_family_working_members`

## Notes / Risks
- Controller creates multiple records from arrays; different pattern from single-record fill(). Scoped input + per-value scalar coercion applied instead of ArrayToScalarNormalizer.
- Scalar-to-array normalization: when form sends scalar (e.g. single member), value is wrapped in single-element array so loop still processes it.

## Date
- Refactored: 2026-02-07
- Verified: (pending)
