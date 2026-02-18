# M3.7 — Phase 1: Canonical Sanctioned vs Requested Separation (Implementation)

**Mode:** Controlled refactor — no feature expansion.  
**Scope:** Resolver layer only. No dashboards, exports, controllers, or DB schema changes.

---

## Objective

Permanently separate financial semantics:

1. **Non-approved projects**
   - `amount_sanctioned` MUST always be **0**
   - `amount_requested` = `overall_project_budget - (amount_forwarded + local_contribution)` (max 0)
   - `opening_balance` = `amount_forwarded + local_contribution`

2. **Approved projects**
   - `amount_sanctioned` = persisted DB value
   - `opening_balance` = persisted DB value
   - `amount_requested` = **0**

3. **Invariant**
   - Non-approved → `amount_sanctioned == 0` (resolver enforces; DB may lag until cleanup).

---

## Files Modified

| File | Change |
|------|--------|
| `app/Domain/Budget/ProjectFinancialResolver.php` | Added `applyCanonicalSeparation()`, `amount_requested` in return and normalize; strict invariant log when non-approved has DB sanctioned > 0 |
| `app/Domain/Budget/Strategies/PhaseBasedBudgetStrategy.php` | Non-approved: return `amount_sanctioned = 0`, `amount_requested = max(0, overall - (forwarded+local))`, `opening_balance = forwarded + local`; added `amount_requested` to normalize |
| `app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php` | After type resolve: if approved use DB sanctioned/opening and `amount_requested = 0`; if not approved use `amount_requested = (type requested value)`, `amount_sanctioned = 0`, `opening_balance = forwarded + local`; added `amount_requested` to normalize and fallback |
| `app/Services/Budget/BudgetSyncService.php` | `syncBeforeApproval`: when project is **not** approved, do not update `amount_sanctioned` (use `PRE_APPROVAL_FIELDS_WITHOUT_SANCTIONED`) |

---

## Before / After Resolver Logic

### Before (Phase 0)

- Strategy returned five fields; for non-approved, PhaseBased set `amount_sanctioned = overall - (forwarded + local)` and `opening_balance = sanctioned + forwarded + local`. DirectMapped returned type “requested” in `amount_sanctioned`. Resolver normalized and asserted; no `amount_requested`.

### After (Phase 1)

- **Resolver**
  - After strategy `resolve()`, runs `applyCanonicalSeparation($project, $result)`:
    - **If NOT approved:** `amount_sanctioned = 0`, `amount_requested = result['amount_requested'] ?? max(0, overall - (forwarded + local))`, `opening_balance = forwarded + local`.
    - **If approved:** `amount_sanctioned = $project->amount_sanctioned`, `amount_requested = 0`, `opening_balance = $project->opening_balance`.
  - Normalize includes `amount_requested`; return array has six fund fields (overall, forwarded, local, sanctioned, **amount_requested**, opening_balance).
- **PhaseBasedBudgetStrategy**
  - Non-approved: returns `amount_sanctioned = 0`, `amount_requested = max(0, overall - (forwarded+local))`, `opening_balance = forwarded + local`. Approved: unchanged (DB sanctioned/opening, requested = 0).
- **DirectMappedIndividualBudgetStrategy**
  - Non-approved: from type result, sets `amount_requested = resolved['amount_sanctioned']`, `amount_sanctioned = 0`, `opening_balance = forwarded + local`. Approved: DB sanctioned/opening, `amount_requested = 0`.
- **BudgetSyncService**
  - `syncBeforeApproval`: if project is not approved, update payload excludes `amount_sanctioned` (only overall, forwarded, local, opening_balance). Draft update never writes sanctioned.

---

## Why Sanctioned Is Zero Pre-Approval

- **Semantics:** “Sanctioned” means money formally approved for the project. Before approval there is no sanction, so the canonical value is 0.
- **Consistency:** Aggregations (e.g. “total sanctioned”) should not include draft “requested” amounts. Resolver and sync must not write or expose non-zero sanctioned for non-approved projects so that future dashboards and reports can rely on sanctioned = approved-only.
- **DB cleanup later:** Existing DB may still have non-zero `amount_sanctioned` for some non-approved projects. Resolver and sync no longer depend on or write that; a future data migration can set it to 0.

---

## Why amount_requested Was Introduced

- **Separation of concepts:** “Requested” (what the project is asking for) vs “sanctioned” (what was approved) were previously conflated in one field. Non-approved projects need a requested amount for display and validation; approved projects do not (requested = 0).
- **Resolver as single source:** All consumers get both `amount_sanctioned` and `amount_requested` from the resolver. Phase 2 can switch dashboards/exports to use `amount_requested` for draft and `amount_sanctioned` for approved without changing the resolver contract again.

---

## Invariant Logic

1. **Resolver output**
   - Non-approved: `amount_sanctioned === 0`, `amount_requested >= 0`, `opening_balance = amount_forwarded + local_contribution`.
   - Approved: `amount_sanctioned` from DB, `amount_requested === 0`, `opening_balance` from DB.

2. **Strict assertion (Phase 1)**
   - In `ProjectFinancialResolver::assertFinancialInvariants()`: if project is **not** approved and `$project->amount_sanctioned > 0`, log **critical** warning with `invariant: non_approved_implies_sanctioned_zero`. No auto-fix; used to detect DB that has not yet been cleaned.

3. **Sync guard**
   - `BudgetSyncService::syncBeforeApproval()` never writes `amount_sanctioned` when the project is not approved. Only the approval flow (controller) persists sanctioned and opening_balance after approval.

---

## Risk Assessment

| Risk | Mitigation |
|------|-------------|
| Consumers expect only 5 keys | Resolver still returns all previous keys; added only `amount_requested`. Existing code using `amount_sanctioned` / `opening_balance` gets the new semantics (0 for non-approved sanctioned). |
| Dashboards sum “sanctioned” | Unchanged in Phase 1; they keep using resolver. Totals for non-approved will now include 0 for sanctioned (correct). Phase 2 can add requested totals if needed. |
| Approval flow | Unchanged; coordinator/general still call resolver and persist `amount_sanctioned` and `opening_balance` on approve. Resolver returns DB values for approved projects. |
| DirectMapped opening_balance for non-approved | Now `forwarded + local` instead of type-specific “overall”. Aligns with canonical definition; individual-type reporting can be revisited in Phase 2 if needed. |
| Tests | Existing tests that assert sanctioned for draft may need to expect 0 and use `amount_requested` for “requested” value; to be updated in Phase 2 or follow-up. |

---

## Next Wave (Phase 2) Plan

- **Dashboards:** Use `amount_requested` for draft column/totals where “requested” is intended; keep `amount_sanctioned` for approved-only totals.
- **Exports / PDF:** Add “Amount Requested” for non-approved; keep “Amount Sanctioned” for approved (or stage-aware labels).
- **Views:** General info / provincial list: show requested vs sanctioned from resolver (`amount_requested` / `amount_sanctioned`).
- **DB cleanup (optional):** One-time migration to set `projects.amount_sanctioned = 0` where status is not approved.
- **Tests:** Update resolver and integration tests for sanctioned=0 and amount_requested semantics.

---

**M3.7 Phase 1 Complete — Canonical Sanctioned Separation Enforced in Resolver**
