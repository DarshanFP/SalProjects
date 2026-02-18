# M3 — Dashboard Smoke Test (Audit)

**Mode:** Audit only — no code changes.  
**Purpose:** Verify dashboards use correct canonical semantics after M3.7 Phase 2.

---

## Canonical Rules (Reference)

- **Approved totals** → use `amount_sanctioned` (for sanctioned metric); budget totals use `opening_balance`.
- **Pending totals** → use `amount_requested`.
- **Opening balance** → use `opening_balance` (resolver or DB for approved).

---

## STEP 1 — Controller Scan

### ProvincialController

| Location | Aggregation / logic | Source | Approved vs non-approved | Match? |
|----------|---------------------|--------|---------------------------|--------|
| **projectList()** ~494–516 | Grand totals | Resolver per project | `if ($project->isApproved())` → add to `amount_sanctioned`; else add to `amount_requested` | ✓ Stage-separated |
| **projectList()** ~508–511 | overall_project_budget, forwarded, local, opening_balance | Resolver | Summed over all projects (informational columns) | ✓ Not used as “approved total” |
| **projectList()** ~518, 552 | Per-project budget for utilization | `$financials['opening_balance']` | Resolver | ✓ opening_balance |
| **calculateCenterPerformance()** ~2234–2239 | centerBudget (approved), centerPendingBudget (pending) | Approved: `$p->opening_balance` (raw). Pending: `$resolver->resolve($p)['amount_requested']` | `$approvedProjects = $centerProjects->filter(fn ($p) => $p->isApproved())`; `$pendingProjects = $centerProjects->filter(fn ($p) => ! $p->isApproved())` | ✓ Separation correct; approved uses opening_balance |
| **calculateEnhancedBudgetData()** ~2284–2302 | pendingTotal (pending), totalBudget (approved) | Pending: resolver `amount_requested`. Approved: `$resolvedFinancials[$project->project_id]['opening_balance']` | `Project::approved()` / `Project::notApproved()` | ✓ |
| **Budget by project type/center** ~2189, 2199 | Sum opening_balance | Resolver (`resolvedFinancials`) | Approved only (from approved project sets) | ✓ |
| **Top projects, by-project budget** ~2322, 2343, 2364, 2386 | Per-project budget | Resolver `opening_balance` | Approved only | ✓ |

**Confirmed:**

- No aggregation uses `overall_project_budget` for approved *totals* (overall is summed separately as its own metric).
- No aggregation uses `amount_sanctioned` for non-approved totals; non-approved use `amount_requested`.
- No negative filtering on financial fields found.
- Approved detection: `$project->isApproved()` (projectList, calculateCenterPerformance) or `Project::approved()` / `Project::notApproved()` (calculateEnhancedBudgetData).

---

### CoordinatorController

| Location | Aggregation / logic | Source | Approved vs non-approved | Match? |
|----------|---------------------|--------|---------------------------|--------|
| **index()** ~151–152 | projects_with_amount_sanctioned, projects_with_overall_budget | Raw DB: `$projects->where('amount_sanctioned', '>', 0)->count()`; `where('overall_project_budget', '>', 0)->count()` | Counts only; not monetary totals | ✓ Stats only; no mixing |
| **getSystemBudgetOverviewData()** ~2043–2048 | pendingTotal, totalBudget | Pending: `$financialResolver->resolve($p)['amount_requested']`. Approved: `$approvedProjects->sum(fn ($p) => (float) ($p->opening_balance ?? 0))` | `Project::approved()` / `Project::notApproved()` | ✓ Pending = resolver; approved = opening_balance |
| **getSystemBudgetOverviewData()** ~2076, 2113, 2152, 2196, 2241–2242 | typeBudget, provinceBudget, centerBudget, provincialBudget, topProjectsByBudget | Raw `$p->opening_balance` | All over `$approvedProjects` or approved-filtered sets | ✓ Approved only; opening_balance |
| **getPendingApprovalsData()** ~1657–1664 | totalBudget | `$resolvedFinancials[$p->project_id]['opening_balance']` | Approved only | ✓ Resolver opening_balance |
| **getProvincialOverviewData()** ~1747–1791 | budgetByMonth, etc. | Resolver `opening_balance` | Approved only | ✓ |
| **Other widget methods** ~1861, 2305–2328, 2407–2431, 2526–2532 | Province/team/total budget | Resolver `opening_balance` or raw `$p->opening_balance` for approved | Approved only | ✓ |
| **approveProject()** ~1107–1135 | Persist after approval | Resolver `amount_sanctioned`, `opening_balance` | Approval flow (not dashboard aggregation) | N/A |

**Confirmed:**

- No aggregation uses `overall_project_budget` for approved monetary totals; approved budget totals use `opening_balance`.
- No aggregation uses `amount_sanctioned` for non-approved; pending uses resolver `amount_requested`.
- No negative filtering on financial fields found.
- Approved detection: `Project::approved()` / `Project::notApproved()` in getSystemBudgetOverviewData; other methods use already-approved sets.

**Note:** getSystemBudgetOverviewData uses **raw** `$p->opening_balance` for approved totals; other coordinator methods use **resolver** `resolvedFinancials['opening_balance']`. For approved projects these should match (DB persisted from resolver on approval). Risk: low.

---

### Other controllers (dashboard-related)

- **GeneralController:** Uses `resolvedFinancials[$p->project_id]['opening_balance']` for approved-project aggregations; approval flow uses resolver. No dashboard aggregation of amount_sanctioned for non-approved.
- **ExecutorController / AdminReadOnlyController:** Use resolver for per-project budget (opening_balance). No mixed sanctioned/requested totals found.
- **ExportController:** Stage-aware display (sanctioned vs requested); not dashboard aggregation.

---

## STEP 2 — Resolver Usage

| Controller | Approved totals | Pending totals | Inline financial math |
|------------|------------------|----------------|-------------------------|
| **ProvincialController** | Resolver for projectList grandTotals (amount_sanctioned, opening_balance). calculateCenterPerformance approved uses raw `$p->opening_balance`; calculateEnhancedBudgetData approved uses resolver opening_balance | Resolver `amount_requested` (calculateCenterPerformance, calculateEnhancedBudgetData, projectList grandTotals) | None found |
| **CoordinatorController** | getSystemBudgetOverviewData: raw `$p->opening_balance`. Other methods: resolver `opening_balance` | Resolver `amount_requested` in getSystemBudgetOverviewData | None found |

**Conclusion:** Aggregations rely on resolver for pending (amount_requested) and for most approved budget (opening_balance). One approved-path (getSystemBudgetOverviewData) uses raw DB opening_balance; acceptable for approved. No inline formulas (e.g. overall - forwarded - local) in controller aggregation.

---

## STEP 3 — Aggregation Logic Summary

### ProvincialController

1. **projectList()**  
   - Resolver run for full filtered set.  
   - Grand totals: overall_project_budget, amount_forwarded, local_contribution, opening_balance summed over all; amount_sanctioned summed only when `$project->isApproved()`; amount_requested summed only when not approved.  
   - Per-project budget/utilisation: resolver `opening_balance`.

2. **calculateCenterPerformance()**  
   - Approved: `$approvedProjects->sum(fn ($p) => $p->opening_balance)`.  
   - Pending: `$pendingProjects->sum(fn ($p) => $resolver->resolve($p)['amount_requested'])`.  
   - Separation: `filter(isApproved)` / `filter(!isApproved)`.

3. **calculateEnhancedBudgetData()**  
   - Pending: resolver `amount_requested`.  
   - Approved: resolver `opening_balance` for totalBudget and by-type/by-center.  
   - Separation: `Project::approved()` / `Project::notApproved()`.

### CoordinatorController

1. **getSystemBudgetOverviewData()**  
   - Pending: resolver `amount_requested`.  
   - Approved: raw `$p->opening_balance` for totalBudget, by type/province/center, and top projects.  
   - Separation: `Project::approved()` / `Project::notApproved()`.

2. **getPendingApprovalsData()**  
   - Approved only; resolver `opening_balance`.

3. **getProvincialOverviewData()**  
   - Approved only; resolver `opening_balance`.

4. **index()**  
   - `projects_with_amount_sanctioned`: raw count where `amount_sanctioned > 0` (statistic, not monetary sum).

---

## Mismatches and Risks

| Finding | Risk | Notes |
|---------|------|--------|
| None: ProvincialController projectList | — | Stage-separated; sanctioned only for approved, requested only for non-approved; opening_balance from resolver. |
| None: ProvincialController calculateCenterPerformance / calculateEnhancedBudgetData | — | Pending uses resolver amount_requested; approved uses opening_balance (raw in center, resolver in enhanced). |
| CoordinatorController getSystemBudgetOverviewData approved totals use raw `$p->opening_balance` | **Low** | For approved projects, DB opening_balance is set from resolver on approval; should match. If DB is ever out of sync, totals could diverge until next approval or reconciliation. |
| CoordinatorController index: `projects_with_amount_sanctioned` from raw DB | **Low** | Count only. After M3.7 Phase 1, only approved should have sanctioned > 0; count is effectively “approved with sanctioned”. |
| No dashboard service | — | All aggregation in controllers; no separate service to audit. |

---

## Checklist

- [x] No aggregation uses `overall_project_budget` for approved monetary totals (only for separate “overall budget” metric or counts).
- [x] No aggregation uses `amount_sanctioned` for non-approved totals.
- [x] No negative filtering on financial fields found.
- [x] Approved detection uses `Project::isApproved()` or `Project::approved()` / `Project::notApproved()`.
- [x] Pending totals use resolver `amount_requested`.
- [x] Approved budget totals use `opening_balance` (resolver or raw DB for approved).
- [x] No inline financial math (e.g. overall - forwarded - local) in controller aggregation.

---

**M3 Dashboard Smoke Test Complete — No Code Changes**
