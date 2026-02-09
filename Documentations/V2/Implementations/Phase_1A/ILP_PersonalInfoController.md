# Phase 1A — ILP PersonalInfoController

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
- **Class**: `App\Http\Controllers\Projects\ILP\PersonalInfoController`
- **File**: `app/Http/Controllers/Projects/ILP/PersonalInfoController.php`
- **Methods refactored**: `store()` (invoked by `update()` via delegation)

## Anti-patterns Found
- `$validated = $request->all()` in `store()` — pulled in full multi-step form data
- Manual field array in `updateOrCreate()` — equivalent mass-assignment risk
- No array-to-scalar coercion — risk of "Array to string conversion" if overlapping field names send arrays

## Pattern Used
**Golden Template (Pattern A)** — Single-record controller; one personal info per project. Uses `updateOrCreate()` instead of delete-then-create.

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Applied `ArrayToScalarNormalizer::forFillable($data, $fillable)` before `updateOrCreate()`
- Excluded `project_id` and `ILP_personal_id` from fillable (set by controller / auto-generated)
- Replaced manual field array with `$data` variable
- Preserved conditional logic: `spouse_name` (when marital_status == 'Married'), `small_business_details` (when small_business_status == 1), `small_business_status` (int cast)

## Fillable Keys Used
`name`, `age`, `gender`, `dob`, `email`, `contact_no`, `aadhar_id`, `address`, `occupation`, `marital_status`, `spouse_name`, `children_no`, `children_edu`, `religion`, `caste`, `family_situation`, `small_business_status`, `small_business_details`, `monthly_income`, `business_plan`

## Excluded Keys
- `project_id` — set by controller in `updateOrCreate()` match
- `ILP_personal_id` — auto-generated in model boot

## Files Modified
- `app/Http/Controllers/Projects/ILP/PersonalInfoController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- Conditional logic for spouse_name and small_business_details
- `show()`, `edit()`, `destroy()`
- Transaction, logging, and JSON responses
- `update()` — still delegates to `store()`

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit ILP Personal Info flow works
- [ ] Conditional spouse_name and small_business_details behave correctly
- [ ] Log: no new errors for ILP project type

## Notes / Risks
- Conditional overrides applied after normalizer; original business logic preserved.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
