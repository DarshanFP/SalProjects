# Phase 1A — RST BeneficiariesAreaController

## Status
- [x] Refactored
- [ ] Merged
- [ ] Verified

## Reference
- Playbook: Phase_1A_Refactor_Playbook.md
- Plan: Phase_Wise_Refactor_Plan.md → Phase 1A.4
- Pattern Lock: PATTERN_LOCK.md
- Canonical: IESFamilyWorkingMembersController.md (Array-Pattern Reference)

## Controller
- **Class**: `App\Http\Controllers\Projects\RST\BeneficiariesAreaController`
- **File**: `app/Http/Controllers/Projects/RST/BeneficiariesAreaController.php`
- **Methods refactored**: `store()`, `update()` (delegates to `store()`)

## Anti-patterns Found
- `$validated = $request->all()` in `store()`
- `$validatedData = $request->all()` in `update()` — pulled in full multi-step form data
- No scoped input — arrays from other sections could pollute or cause type issues
- No per-value scalar coercion — nested arrays could cause "Array to string conversion"

## Pattern Used
**Array-Pattern (Pattern B)** — Multi-record controller; parallel arrays `project_area[]`, `category_beneficiary[]`, `direct_beneficiaries[]`, `indirect_beneficiaries[]`.

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Scoped to owned keys: `project_area`, `category_beneficiary`, `direct_beneficiaries`, `indirect_beneficiaries`
- Added scalar-to-array normalization when form sends scalar instead of array
- Added per-value scalar coercion in loop: when value at index is array, use `reset($value)` — prevents "Array to string conversion"
- `update()` delegates to `store()` — both paths use delete-then-recreate

## Fillable Keys Used (input scope)
`project_area`, `category_beneficiary`, `direct_beneficiaries`, `indirect_beneficiaries`

## Excluded Keys
- `project_id` — set by controller in each `create()`
- `DPRST_bnfcrs_area_id` — auto-generated in model boot

## Model Note
Uses `ProjectDPRSTBeneficiariesArea` (DPRST = RST/DP shared).

## Files Modified
- `app/Http/Controllers/Projects/RST/BeneficiariesAreaController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- `show()`, `edit()`, `destroy()`
- Transaction, logging, JSON responses
- Delete-then-recreate pattern

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit RST Beneficiaries Area flow works
- [ ] Multiple area entries save correctly
- [ ] Log: no new errors for RST project type
- [ ] No "Array to string conversion" for beneficiaries area table

## Notes / Risks
- None identified.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
