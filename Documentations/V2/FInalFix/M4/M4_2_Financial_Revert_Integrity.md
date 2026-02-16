# M4.2 — Financial Revert Integrity

**Milestone:** M4 — Workflow & State Machine Hardening  
**Phase:** M4.2 — Financial Revert Integrity (Clear Financial Fields on Revert)  
**Date:** 2026-02-15

---

## Objective

When reverting a project from any **approved** status to a non-approved status, enforce canonical financial state in the database:

- `amount_sanctioned = 0`
- `opening_balance = amount_forwarded + local_contribution`

This aligns DB state with the canonical model:

| State       | amount_sanctioned | opening_balance              |
|------------|-------------------|-----------------------------|
| Approved   | > 0               | overall budget (from resolver) |
| Non-approved | 0               | forwarded + local_contribution |

---

## Files Modified

| File | Change |
|------|--------|
| `app/Services/ProjectStatusService.php` | Added `applyFinancialResetOnRevert(Project $project)` and invoked it in all five revert methods before setting status and saving. |

**No other files modified.** Controllers, approval flow, reject logic, resolver, aggregation, and export layer were not changed.

---

## Before / After Behavior

### Before (M4.1 audit finding H2)

- Revert methods only set `$project->status` and called `$project->save()`.
- `amount_sanctioned` and `opening_balance` were **not** updated on revert.
- An approved project (sanctioned > 0, opening_balance = budget) could be reverted while still holding those values.
- Resolvers and guards (e.g. `ProjectFinancialResolver`, `BudgetSyncGuard`) assume non-approved projects have `amount_sanctioned == 0` and opening_balance = forwarded + local; this led to inconsistent state and risk of wrong aggregations or validations.

### After (M4.2)

- On every revert path, **before** setting the new status and saving, the service calls `applyFinancialResetOnRevert($project)`.
- **If current status is approved:**  
  `amount_sanctioned` is set to `0` and `opening_balance` is set to `(amount_forwarded ?? 0) + (local_contribution ?? 0)`.
- **If current status is not approved:**  
  No change to financial fields (idempotent; safe for “double revert” or revert from already reverted state).
- Status update and save happen in the same request; financial reset and status change are persisted together (single `save()`).

---

## Why Stale Sanctioned Was Dangerous

1. **Resolver / invariant assumptions:** `ProjectFinancialResolver` and budget logic assume non-approved projects have `amount_sanctioned == 0`. Stale sanctioned > 0 on a reverted project could trigger invariant warnings or wrong “approved” treatment in calculations.
2. **Aggregation:** Approved-project aggregations use `ProjectStatus::APPROVED_STATUSES` and often use `opening_balance` or sanctioned for totals. If a project was reverted but DB still had sanctioned/opening from approval, it could be double-counted or misclassified depending on whether filters used status only or also financial fields.
3. **Re-approval:** On re-submit and re-approval, having old sanctioned/opening in place could conflict with newly computed values or mask data issues.

Enforcing canonical non-approved state on revert removes this inconsistency and keeps DB aligned with resolver and business rules.

---

## Risk Removed

- **M4.1 HIGH H2:** “Revert leaves financial fields set” — **addressed.** All reverts from an approved status now clear sanctioned and set opening_balance to forwarded + local in the same transaction as the status change.

---

## Implementation Detail

- **Single central point:** `ProjectStatusService::applyFinancialResetOnRevert(Project $project)` (private).
- **Guard:** Only applies reset when `ProjectStatus::isApproved($project->status)`; otherwise no-op (idempotent).
- **Revert entry points updated:**  
  `revertByProvincial`, `revertByCoordinator`, `revertAsCoordinator`, `revertAsProvincial`, `revertToLevel` — each calls `applyFinancialResetOnRevert($project)` after `$previousStatus = $project->status` and before `$project->status = $newStatus` and `$project->save()`.
- **Formulas:** No change to approval-time formulas, resolver logic, or any other financial calculation; only the two fields above are set on revert.

---

## Regression Risk

- **Level:** LOW–MEDIUM.
- **Reason:** Only the two fields `amount_sanctioned` and `opening_balance` are written on revert, and only when transitioning from an approved status. Approval flow, reject, DPReport, resolver, aggregation, and export are untouched.
- **Mitigation:** Existing `FinancialResolverTest` (including “reverted project sanctioned zero opening balance equals forwarded plus local”) passed after implementation. Manual verification below.

---

## Manual Verification (Checklist)

1. **Approve project**  
   - Confirm: `amount_sanctioned > 0`, `opening_balance` = budget (e.g. from resolver / approval flow).

2. **Revert project** (any revert path: provincial, coordinator, or general revert-as-*)  
   - Confirm: `amount_sanctioned = 0`, `opening_balance = amount_forwarded + local_contribution`.

3. **Financial invariant tests**  
   - Run: `php artisan test tests/Feature/FinancialResolverTest.php`  
   - **Result:** All 5 tests passed (approved/draft/reverted invariants and parity).

4. **Idempotency**  
   - Revert an already reverted project (e.g. General revert to level again).  
   - Financial fields should not be recalculated incorrectly; `applyFinancialResetOnRevert` no-ops when status is not approved.

---

## Summary

- Financial reset on revert is **centralized** in `ProjectStatusService` and applied in all five revert methods.
- **No** changes to approval logic, reject logic, resolver, aggregation, or export.
- Canonical non-approved state is enforced on revert: `amount_sanctioned = 0`, `opening_balance = forwarded + local`.
- Idempotency is preserved for non-approved current status.

---

**M4.2 Complete — Financial Revert Integrity Enforced**
