# M3.7 — Phase 2: Dashboard Audit

**Scope:** CoordinatorController, ProvincialController, dashboard aggregation services, coordinator/provincial/dashboard views.  
**Purpose:** Identify where amount_sanctioned/opening_balance are summed, whether resolver vs raw DB is used, and if totals are stage-separated.

---

## 1. ProvincialController

### projectList() — Lines 471–593

| Item | Location | Detail |
|------|----------|--------|
| **Base query** | 478–484 | No status filter; optional filters (project_type, user_id, status, center). |
| **Full dataset** | 487–489 | All projects in scope (all statuses). |
| **Resolver** | 491–508 | Used for every project; `$resolvedFinancials[$project->project_id] = $resolver->resolve($project)`. |
| **Grand totals** | 494–508 | Single bucket: `grandTotals['amount_sanctioned'] += (float) ($financials['amount_sanctioned'] ?? 0)` over **all** projects. **Not stage-separated.** |
| **Opening balance** | 509–510, 544 | Per-project budget from `$financials['opening_balance']`; no grand total for opening_balance. |
| **Approved vs non-approved** | — | No filtering; one total mixes approved (sanctioned) and non-approved (0 after Phase 1). |

**Finding:** Grand total labeled “Total Amount Requested” in blade is actually the sum of resolver `amount_sanctioned` (approved-only after Phase 1). No separate “Total Amount Requested” (sum of `amount_requested` for non-approved). **Requires stage-separated totals.**

---

### calculateCenterPerformance() — Lines 2204–2265

| Item | Location | Detail |
|------|----------|--------|
| **Approved** | 2224–2229 | `$centerBudget = $approvedProjects->sum(fn ($p) => (float) ($p->opening_balance ?? 0))` — **raw DB** `opening_balance`. |
| **Pending** | 2229–2235 | `$centerPendingBudget = $pendingProjects->sum(function ($p) { return max(0, $overall - ($forwarded + $local)); })` — **inline formula**, not resolver. |
| **Stage-separated** | Yes | Approved vs pending computed separately. |

**Finding:** Pending uses inline `overall - (forwarded + local)`. Should use resolver `amount_requested` (Phase 2 alignment).

---

### calculateEnhancedBudgetData() — Lines 2269–2320+

| Item | Location | Detail |
|------|----------|--------|
| **Approved** | 2288–2303 | Resolver used; `$resolvedFinancials` only for approved; total from `resolvedFinancials['opening_balance']`. |
| **Pending** | 2288–2293 | `$pendingTotal = $pendingProjects->sum(function ($p) { return max(0, $overall - ($forwarded + $local)); })` — **inline formula**. |
| **Stage-separated** | Yes | Approved vs pending separate. |

**Finding:** Pending total should use resolver `amount_requested` instead of inline formula.

---

### Other ProvincialController usages

- **2095–2191:** Budget-by-status/type/center use `$resolvedFinancials[$project->project_id]['opening_balance']` (resolver). Approved-only filtering where applicable.
- **2229:** `$centerBudget` uses raw `$p->opening_balance` for approved — consistent with approved DB value.

---

## 2. CoordinatorController

### index() — Lines 138–187

| Item | Location | Detail |
|------|----------|--------|
| **projects_with_amount_sanctioned** | 151 | `$projects->where('amount_sanctioned', '>', 0)->count()` — **raw DB**; no status filter. Effectively “projects with DB sanctioned > 0” (after Phase 1, approved only). |
| **Sums** | — | No sum of amount_sanctioned in index. |

**Finding:** Count only; no change for Phase 2. No mixing of requested/sanctioned.

---

### getSystemBudgetOverviewData() — Lines 1995–2265+

| Item | Location | Detail |
|------|----------|--------|
| **Approved** | 2021, 2051 | `$approvedProjects = $approvedProjectsQuery->get()`; `$totalBudget = $approvedProjects->sum(fn ($p) => (float) ($p->opening_balance ?? 0))` — **raw DB** `opening_balance`. |
| **Pending** | 2043–2048 | `$pendingTotal = $pendingProjects->sum(function ($p) { return max(0, $overall - ($forwarded + $local)); })` — **inline formula**. |
| **Budget by type/province/center** | 2079, 2116, 2155 | All use `$p->opening_balance` for **approved** projects. |
| **Top projects** | 2244–2245 | `$p->opening_balance` — raw DB. |
| **Stage-separated** | Yes | Approved totals vs pending total separate. |

**Finding:** Pending total must use resolver `amount_requested`. Approved uses raw `opening_balance` (acceptable for approved); optional later switch to resolver for consistency.

---

### Other CoordinatorController usages

- **1664, 1695, 1766, 1786, 1861, 2328, 2434, 2535:** Use `$resolvedFinancials[$p->project_id]['opening_balance']` where `resolvedFinancials` is built from resolver — **resolver used**.
- **2050–2051, 2079, 2116, 2155, 2199, 2243–2245:** Use `$p->opening_balance` on approved projects — **raw DB**.

---

## 3. Views

### resources/views/provincial/ProjectList.blade.php

| Line | Usage | Source | Stage-aware? |
|------|--------|--------|--------------|
| 124 | Summary total | `$grandTotals['amount_sanctioned']` | No — single total; label “Total Amount Requested”. |
| 231–235 | Per-row “Amount Requested” | `$amountRequested = (float) ($fin['amount_sanctioned'] ?? 0)` | No — uses sanctioned for all; after Phase 1 non-approved show 0. |

**Finding:** Need two summary totals (Sanctioned vs Requested) and per-row: show `amount_requested` for non-approved, `amount_sanctioned` for approved.

---

### resources/views/coordinator/approvedProjects.blade.php (Line 109)

| Line | Usage | Source | Stage-aware? |
|------|--------|--------|--------------|
| 109 | Per-row | `$project->amount_sanctioned` | N/A — list is approved-only; raw DB acceptable. |

---

### resources/views/provincial/approvedProjects.blade.php (Line 94)

| Line | Usage | Source | Stage-aware? |
|------|--------|--------|--------------|
| 94 | Per-row | `$project->amount_sanctioned` | N/A — approved-only; raw DB acceptable. |

---

### resources/views/projects/partials/Show/general_info.blade.php

| Line | Usage | Source | Stage-aware? |
|------|--------|--------|--------------|
| 34 | Amount Requested | `$amount_requested = (float) ($rf['amount_sanctioned'] ?? 0)` | No — should use `$rf['amount_requested']`. |
| 135 | Amount Sanctioned | `$rf['amount_sanctioned']` | Correct key; display is correct after Phase 1. |

**Finding:** “Amount Requested” row should use `$rf['amount_requested']`.

---

## 4. Summary Table

| File | Line(s) | What is summed/displayed | Resolver? | Approved-only filter? | Stage-separated? |
|------|---------|---------------------------|-----------|------------------------|------------------|
| ProvincialController | 495–508 | grandTotals (all projects) | Yes | No | No |
| ProvincialController | 2229–2235 | centerBudget (approved), centerPendingBudget (pending) | No (raw + inline) | Yes | Yes |
| ProvincialController | 2288–2293 | pendingTotal (pending) | No (inline) | Yes | Yes |
| CoordinatorController | 151 | Count sanctioned > 0 | Raw DB | No | N/A |
| CoordinatorController | 2043–2048 | pendingTotal | No (inline) | Yes | Yes |
| CoordinatorController | 2051, 2079, 2116, 2155, 2244 | opening_balance totals | Raw DB (approved) | Yes | Yes |
| provincial/ProjectList.blade | 124, 235 | Grand total + row | Resolver (controller) / sanctioned in row | No | No |
| Show/general_info.blade | 34, 135 | Requested + Sanctioned | Resolver | N/A | Use amount_requested + amount_sanctioned |

---

## 5. Inline Formula Locations (to replace with resolver)

- **ProvincialController:** 2230–2234 (`calculateCenterPerformance`), 2289–2293 (`calculateEnhancedBudgetData`).
- **CoordinatorController:** 2044–2048 (`getSystemBudgetOverviewData`).

All three compute “pending” as `max(0, overall - (forwarded + local))`. Phase 2: use resolver and sum `amount_requested` for pending projects.
