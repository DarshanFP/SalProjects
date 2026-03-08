# Phase 4A — Immutable Dataset Architecture Safeguard Feasibility Audit

**Date:** 2026-03-05  
**Phase:** Phase 4A — Immutable Dataset Architecture Safeguard  
**Reference:** Phase 4 Shared Dataset Implementation, Provincial_Dashboard_FY_Architecture_Implementation_Plan.md  

---

## Executive Summary

The audit confirms that the five widget methods receiving `teamProjects` currently use **read-only operations** on the shared dataset. No collection mutation operations (`transform`, `forget`, `push`, etc.) are applied to `teamProjects`. One potential risk—`loadMissing()` in resolver strategies—adds relations to project models but does not mutate the collection. **Recommendation: Strategy A (Documentation + Guidelines)** with optional CI rule. No runtime guard or immutable wrapper required for the current codebase.

---

## Step 1 — Dataset Flow Mapping

### Flow Diagram

```
DatasetCacheService::getProvincialDataset($provincial, $fy)
   │
   │  Returns: Eloquent Collection (user, reports.accountDetails, budgets)
   │  Cache key: provincial_dataset_{provincialId}_{fy}
   │  General users: bypass cache, direct query
   │
   ▼
ProvincialController::provincialDashboard()
   │
   │  $teamProjects = DatasetCacheService::getProvincialDataset(...)
   │
   ├───────────────────────────────────────────────────────────────────
   │  Sequential passes (same reference) to:
   │
   ├──► calculateTeamPerformanceMetrics($provincial, $fy, $teamProjects)
   ├──► prepareChartDataForTeamPerformance($provincial, $fy, $teamProjects)
   ├──► calculateCenterPerformance($provincial, $fy, $teamProjects)
   ├──► calculateEnhancedBudgetData($provincial, $fy, $teamProjects)
   └──► prepareCenterComparisonData($provincial, $fy, $teamProjects)
              │
              └──► delegates to calculateCenterPerformance($provincial, $fy, $teamProjects)
```

### Dataset Ownership

| Dataset | Owner | Scope |
|---------|-------|-------|
| `teamProjects` | DatasetCacheService → Controller | All statuses, FY |
| `projects` | Controller (approved + filters) | Budget Summary only |

`teamProjects` is passed by reference to all five widget methods. Any mutation would affect subsequent callers.

---

## Step 2 — Mutation Detection

### Scan Results: Collection Mutation Operations

| Operation | Locations Found | On teamProjects? |
|-----------|-----------------|------------------|
| `transform()` | Line 612 | **No** — on `$projects->getCollection()` (projectList paginator) |
| `push()` | Lines 220, 225 | **No** — on `$approvalQueueCenters` (new empty collection) |
| `forget()` | Lines 1164, 1258 | **No** — `Cache::forget()` (cache API) |
| `pop()` | None | — |
| `shift()` | None | — |
| `splice()` | None | — |
| `offsetSet` / `[]=` | None on collections | — |

### Conclusion

**No collection mutation operations are applied to `teamProjects` or `projects`** within the dashboard widget flow.

---

## Step 3 — Derived Collection Analysis

### Widget Method Operations on teamProjects

| Method | Operations Used | Returns New Collection? |
|--------|-----------------|-------------------------|
| calculateTeamPerformanceMetrics | `groupBy`, `map`, `whereIn` | Yes |
| prepareChartDataForTeamPerformance | `groupBy`, `map`, `whereIn` | Yes |
| calculateCenterPerformance | `pluck`, `unique`, `filter`, `sort`, `values` | Yes |
| calculateEnhancedBudgetData | `filter`, `map`, `sortByDesc`, `take`, `values` | Yes |
| prepareCenterComparisonData | Delegates; mutates **returned** array, not teamProjects | N/A |

All operations are **non-mutating**:
- `filter()`, `map()`, `groupBy()`, `whereIn()` — return new collections
- `pluck()`, `unique()`, `sort()`, `values()` — return new collections
- `sum()`, `count()` — return scalars
- `foreach` — read-only iteration

---

## Step 4 — Cross-Widget Interaction Risk

### Model-Level Mutation

| Risk | Assessment |
|------|------------|
| Resolver `loadMissing()` | PhaseBasedBudgetStrategy and DirectMappedIndividualBudgetStrategy call `$project->loadMissing('budgets')` or type-specific relations. This adds relation data to the project model. **Additive only** — does not remove or reorder collection items. Same project resolved by multiple widgets receives relations once; subsequent resolves use cached relations. **Low risk.** |
| Direct `$project->attr = x` in widgets | **None found** in the five widget methods. Projects are only read. |
| prepareCenterComparisonData `foreach (&$data)` | Mutates `$centerPerformance` (return value of calculateCenterPerformance), not teamProjects. **No risk.** |

### Execution Order

Widgets are called sequentially. If an earlier widget mutated teamProjects, later widgets would see corrupted data. Current implementation does not perform such mutations.

---

## Step 5 — Dataset Ownership Model

| Model | Current Implementation |
|-------|------------------------|
| **Mutable shared dataset** | Not implemented — no explicit mutations |
| **Read-only by convention** | **Yes** — widget methods use only read/derive operations |
| **Immutable by design** | Partially — PHPDoc states immutability; no enforcement |

**Verdict:** The system behaves as **read-only by convention**. Developer discipline and PHPDoc guidance are the only safeguards.

---

## Step 6 — Enforcement Strategy Evaluation

### Strategy A — Documentation Only

| Aspect | Assessment |
|--------|------------|
| Complexity | Low |
| Performance impact | None |
| Developer ergonomics | High — no API changes |
| Compatibility | Full — no code changes |
| Enforcement | None — relies on discipline and code review |

### Strategy B — Runtime Guard

| Aspect | Assessment |
|--------|------------|
| Complexity | Medium — requires wrapping collection, intercepting mutating methods |
| Performance impact | Method-call overhead on every collection operation |
| Developer ergonomics | Medium — mutations throw; debugging cost if guard too strict |
| Compatibility | Risk — `loadMissing()` or other internal Eloquent behavior may trigger false positives |
| Enforcement | Strong — fails fast on mutation |

### Strategy C — Immutable Wrapper (DTO)

| Aspect | Assessment |
|--------|------------|
| Complexity | High — new wrapper class, possible conversion at cache boundary |
| Performance impact | Extra wrapping; collection copy if implementing true immutability |
| Developer ergonomics | Lower — wrapper may not support all Collection methods widgets use |
| Compatibility | Risk — DatasetCacheService returns Collection; cache serialization may not preserve wrapper |
| Enforcement | Strong — wrapper prevents mutation |

### Recommendation

**Strategy A (Documentation + Guidelines)** is sufficient because:

1. Current code already uses only read-only operations.
2. No mutations have been introduced.
3. Strategy B and C add complexity and potential compatibility issues (cache, Eloquent, Phase 4.5/5).
4. A simple CI/code review rule (e.g. forbid `transform`/`forget`/`push` on `$teamProjects` in widget methods) can reinforce the convention.

---

## Step 7 — Performance Impact

### Strategy A (Documentation)

| Scale | Memory | CPU |
|-------|--------|-----|
| 100 projects | No change | No change |
| 1,000 projects | No change | No change |
| 5,000 projects | No change | No change |

### Strategy B (Runtime Guard)

| Scale | Memory | CPU |
|-------|--------|-----|
| 100 projects | Negligible | Negligible |
| 1,000 projects | Small (wrapper) | Per-call overhead |
| 5,000 projects | Noticeable | Cumulative overhead on filter/map/groupBy |

### Strategy C (Immutable Wrapper)

| Scale | Memory | CPU |
|-------|--------|-----|
| 100 projects | +wrapper, possible copy | Copy cost if clone-on-write |
| 1,000 projects | Significant if full copy | Copy cost |
| 5,000 projects | High | Substantial copy cost |

---

## Step 8 — Architecture Recommendation

### Recommended Strategy: **A — Documentation + Guidelines**

1. **Current state:** Widget methods already treat teamProjects as read-only.
2. **Low risk:** No mutations detected; convention is sufficient with explicit documentation.
3. **Future phases:** Phase 4.5 (lightweight projection) and Phase 5 (resolver batching) work with collections; no need for runtime guards.
4. **Cache layer:** DatasetCacheService returns standard Collections; no wrapper complexity.
5. **Mitigation:** Add a development guideline / RULE or CI check to block `transform`, `forget`, `push`, etc. on parameters named `$teamProjects` (or equivalent) in the widget methods.

### Optional Enhancement

If stricter enforcement is desired later, a **lightweight audit rule** (e.g. PHPStan/Psalm custom rule or simple grep in CI) can flag:

- `$teamProjects->transform(`
- `$teamProjects->forget(`
- `$teamProjects->push(`

within the dashboard widget methods. No runtime cost.

---

## Step 9 — Updated Phase 4A Implementation Plan

Phase 4A section has been refined in the implementation plan to reflect:

- Strategy A (Documentation) as the primary approach
- Explicit list of prohibited operations
- Optional CI/audit rule
- No runtime guard or immutable wrapper in scope

---

## Step 10 — Verification Checklist

| # | Verification | Status |
|---|--------------|--------|
| 1 | Dataset flow mapped | ✓ |
| 2 | No mutation operations on teamProjects | ✓ |
| 3 | Widget methods use only read-only operations | ✓ |
| 4 | No cross-widget mutation risk identified | ✓ |
| 5 | Resolver loadMissing is additive only | ✓ |
| 6 | Strategy A recommended | ✓ |
| 7 | Performance impact of Strategy A: none | ✓ |
| 8 | Phase 4A implementation plan updated | ✓ |

---

## Appendix: Prohibited vs Allowed Operations

### Prohibited on shared datasets

- `transform()`
- `forget()`
- `push()`
- `pop()`
- `shift()`
- `splice()`
- `put()` / `offsetSet`
- `prepend()`
- Direct `$collection[$key] = $value`

### Allowed (derive new collections)

- `filter()`
- `map()`
- `groupBy()`
- `where()` / `whereIn()`
- `pluck()`
- `merge()`
- `unique()`
- `sort()` / `sortBy()` / `sortByDesc()`
- `values()`
- `take()`
- `sum()`
- `count()`
