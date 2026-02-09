# Phase 1A — IAHBudgetDetailsController

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
- **Class**: `App\Http\Controllers\Projects\IAH\IAHBudgetDetailsController`
- **File**: `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php`
- **Methods refactored**: `store()`, `update()` (delegates to `store()`)

## Anti-patterns Found
- `$validated = $request->all()` in `store()` and `update()` — pulled in full multi-step form data
- No scoped input — arrays from other sections could pollute or cause type issues
- No per-value scalar coercion when passing to `create()` — nested arrays could cause "Array to string conversion"

## Pattern Used
**Array-Pattern Reference (Pattern B)** — Multi-record controller; creates multiple budget rows from `particular[]`, `amount[]`; `family_contribution` is scalar header.

## Refactor Applied
- Replaced `$request->all()` with `$request->only($fillable)`
- Scoped to owned keys: `particular`, `amount`, `family_contribution`
- Scalar-to-array normalization for `particular` and `amount` — single-item forms wrapped in array
- Per-value scalar coercion in loop: when value at index is array, use `reset($value)` — prevents "Array to string conversion" in `create()`
- `family_contribution` normalized: if array, use `reset()`; else use scalar
- `update()` delegates to `store()` after budget guard check (preserves `iah_budget_update` audit log path)
- Did NOT use `ArrayToScalarNormalizer::forFillable()` — would collapse arrays to single value

## Fillable Keys Used (input scope)
`particular`, `amount`, `family_contribution`

## Excluded Keys
- `project_id` — set by controller in each `create()`
- `total_expenses`, `amount_requested` — computed in controller

## Files Modified
- `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- BudgetSyncGuard, BudgetSyncService, BudgetAuditLogger
- Delete-then-recreate pattern; BudgetSyncService::syncFromTypeSave()
- `show()`, `edit()`, `destroy()`
- Transaction, logging, JSON responses

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit IAH Budget Details flow works
- [ ] Multiple budget rows save correctly
- [ ] Log: no new errors for IAH project type
- [ ] No "Array to string conversion" for `project_IAH_budget_details`

## Notes / Risks
- Controller creates multiple records from arrays; Pattern B. Budget guard in `update()` preserved before delegation.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
