# Phase 1A — IESImmediateFamilyDetailsController

## Status
- [x] Refactored
- [ ] Merged
- [ ] Verified

## Reference
- Playbook: Phase_1A_Refactor_Playbook.md
- Plan: Phase_Wise_Refactor_Plan.md → Phase 1A.3
- Inventory: Phase_1A/README.md

## Controller
- **Class**: `App\Http\Controllers\Projects\IES\IESImmediateFamilyDetailsController`
- **File**: `app/Http/Controllers/Projects/IES/IESImmediateFamilyDetailsController.php`
- **Methods refactored**: `store()` (invoked by `update()` via delegation)

## Anti-patterns Found
- `$familyDetails->fill($request->all())` — pulled in full multi-step form data; arrays from other sections could cause "Array to string conversion"
- No scoped input — no ownership of which keys belong to this controller

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Applied `ArrayToScalarNormalizer::forFillable($data, $fillable)` before `fill()`
- Excluded `project_id` and `IES_family_detail_id` from fillable (set by controller / auto-generated in model boot)
- Replaced `fill($request->all())` with `fill($data)`
- Left NOT_NULL_BOOLEAN_FIELDS post-fill loop unchanged (existing behavior)

## Fillable Keys Used
`mother_expired`, `father_expired`, `grandmother_support`, `grandfather_support`, `father_deserted`, `family_details_others`, `father_sick`, `father_hiv_aids`, `father_disabled`, `father_alcoholic`, `father_health_others`, `mother_sick`, `mother_hiv_aids`, `mother_disabled`, `mother_alcoholic`, `mother_health_others`, `own_house`, `rented_house`, `residential_others`, `family_situation`, `assistance_need`, `received_support`, `support_details`, `employed_with_stanns`, `employment_details`

## Excluded Keys
- `project_id` — set by controller
- `IES_family_detail_id` — auto-generated in model boot

## Files Modified
- `app/Http/Controllers/Projects/IES/IESImmediateFamilyDetailsController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- Attachments, file handling
- Database schema
- `show()`, `edit()`, `destroy()`
- Transaction, logging, and JSON responses
- `update()` — still delegates to `store()`
- NOT_NULL_BOOLEAN_FIELDS post-fill loop (null/empty → 0 for boolean columns)

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit IES Immediate Family Details flow works
- [ ] Log: no new errors for IES project type
- [ ] Boolean fields (checkboxes) persist correctly; NOT_NULL_BOOLEAN_FIELDS normalization still applies
- [ ] No "Array to string conversion" for `project_IES_immediate_family_details`

## Notes / Risks
- Controller uses `Request` (not FormRequest); no validation rules in place. Refactor only scoped input; did not add validation.
- NOT_NULL_BOOLEAN_FIELDS loop runs after fill; ArrayToScalarNormalizer may coerce arrays to scalar before this loop. No conflict expected.

## Date
- Refactored: 2026-02-07
- Verified: (pending)
