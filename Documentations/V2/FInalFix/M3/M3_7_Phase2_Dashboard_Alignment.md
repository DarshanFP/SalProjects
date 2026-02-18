# M3.7 — Phase 2: Dashboard Alignment

**Scope:** Dashboards and list views. Presentation and aggregation only; no resolver or DB schema changes.

---

## Objective

- **Approved portfolio:** Sum only approved projects; use `resolvedFundFields['amount_sanctioned']`.
- **Pending requests:** Sum only non-approved projects; use `resolvedFundFields['amount_requested']`.
- **Opening balance totals:** Use `resolvedFundFields['opening_balance']`.
- **No inline formula:** Do not compute `overall - (forwarded + local)` in controllers or views.

---

## Changes Made

### A) ProvincialController

| Location | Before | After |
|----------|--------|--------|
| **projectList()** grandTotals | Single `amount_sanctioned` sum over all projects | Stage-separated: `amount_sanctioned` summed over approved only; `amount_requested` summed over non-approved only; added `opening_balance` total. |
| **calculateCenterPerformance()** centerPendingBudget | `max(0, overall - (forwarded + local))` per pending project | Sum of `$resolver->resolve($p)['amount_requested']` for pending projects. |
| **calculateEnhancedBudgetData()** pendingTotal | Same inline formula | Sum of `$resolver->resolve($p)['amount_requested']` for pending projects. |

### B) CoordinatorController

| Location | Before | After |
|----------|--------|--------|
| **getSystemBudgetOverviewData()** pendingTotal | `$pendingProjects->sum(function ($p) { return max(0, $overall - ($forwarded + $local)); })` | `$pendingProjects->sum(fn ($p) => ($financialResolver->resolve($p)['amount_requested'] ?? 0))`. |

### C) Views

| View | Change |
|------|--------|
| **provincial/ProjectList.blade.php** | Summary: two totals — "Total Amount Sanctioned (Approved)" from `$grandTotals['amount_sanctioned']`, "Total Amount Requested (Pending)" from `$grandTotals['amount_requested']`. Table column "Requested / Sanctioned": per row shows `amount_sanctioned` if approved, `amount_requested` if non-approved. |
| **projects/partials/Show/general_info.blade.php** | "Amount Requested" row uses `$rf['amount_requested']`; "Amount Sanctioned" row uses `$rf['amount_sanctioned']` (variable `$amount_sanctioned`). |

---

## Aggregation Rules Enforced

- No dashboard aggregates `amount_requested` and `amount_sanctioned` into one total.
- Approved totals use only approved projects and sanctioned/opening from resolver (or DB opening_balance where already approved-only).
- Pending totals use only non-approved projects and resolver `amount_requested`.
- No negative fallback; no raw DB summation that bypasses resolver for pending amounts.

---

## Files Modified

- `app/Http/Controllers/ProvincialController.php` (projectList grandTotals; calculateCenterPerformance; calculateEnhancedBudgetData)
- `app/Http/Controllers/CoordinatorController.php` (getSystemBudgetOverviewData pendingTotal)
- `resources/views/provincial/ProjectList.blade.php`
- `resources/views/projects/partials/Show/general_info.blade.php`
