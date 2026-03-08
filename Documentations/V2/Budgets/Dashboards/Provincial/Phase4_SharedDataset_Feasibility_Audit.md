# Phase 4 — Shared Dataset Architecture Feasibility Audit

**Date:** 2026-03-05  
**Phase:** Phase 4 — Dataset Optimization (Shared Dataset Architecture)  
**Reference:** Provincial_Dashboard_FY_Architecture_Implementation_Plan.md  

---

## Executive Summary

This audit analyzes the current Provincial dashboard widget query patterns, dataset semantics, resolver compatibility, and architecture safeguards against the Phase 4 implementation plan. The audit identifies a **dataset semantic mismatch** between the plan (approved-only) and current requirements (all-statuses for most widgets), and recommends an **immutable dataset safeguard** before implementation.

---

## Step 1 — Current Widget Query Patterns

### Controller Flow (Current State)

The `provincialDashboard()` method:
1. Builds `$baseProjectsQuery = ProjectQueryService::forProvincial($provincial, $fy)`
2. Builds `$projects` = approved + filters (center, role, project_type) → used for `calculateBudgetSummariesFromProjects`
3. Loads `$teamProjectsInFy = DatasetCacheService::getProvincialDataset($provincial, $fy)` → **all statuses**, with `user`, `reports.accountDetails`
4. Passes `$teamProjectsInFy` to five widget methods

### Widget Method Analysis

| Method | Current Query Source | Project Status Scope | Relations Required | Dataset Shareable | Refactor Complexity |
|--------|---------------------|----------------------|--------------------|-------------------|---------------------|
| `calculateBudgetSummariesFromProjects` | Controller: `$projects` (approved, filtered) | APPROVED only | user, reports.accountDetails | **No** (different: approved + filters) | Low |
| `calculateTeamPerformanceMetrics` | `$teamProjects` (passed) or fallback: Project::accessibleByUserIds→inFinancialYear | **All statuses** | user, reports.accountDetails | **Yes** | Low |
| `prepareChartDataForTeamPerformance` | Same | **All statuses** (filters to approved in-memory for budget) | user, reports.accountDetails | **Yes** | Low |
| `calculateCenterPerformance` | Same | **All statuses** (approved + pending per center) | user, reports.accountDetails | **Yes** | Low |
| `calculateEnhancedBudgetData` | Same | **All statuses** (approved + pending) | user, reports.accountDetails | **Yes** | Low |
| `prepareCenterComparisonData` | Delegates to `calculateCenterPerformance` | Same | Same | **Yes** | Low |

### Additional Queries Within Widget Methods

| Method | Extra Queries | Notes |
|--------|---------------|-------|
| `calculateTeamPerformanceMetrics` | `DPReport::accessibleByUserIds($accessibleUserIds)->get()` | Team reports (all statuses) — not project data |
| `prepareChartDataForTeamPerformance` | Same | Same |
| `calculateCenterPerformance` | `DPReport::whereIn('user_id', $centerUsers)->get()` per center | Report counts per center |
| `calculateEnhancedBudgetData` | `DPReport::accessibleByUserIds($accessibleUserIds)->...` in trends loop (6 iterations) | Monthly expense trends |

---

## Step 2 — Dataset Semantics Comparison

### Widget Dataset Requirements

| Widget | Required dataset | Rationale |
|--------|------------------|-----------|
| **Budget Summary** | APPROVED only, center/role/project_type filters | Matches filter form; totals by type/center |
| **Team Performance Metrics** | ALL statuses | `projects_by_status`, `reports_by_status`; budget from approved subset |
| **Chart Data (Team Performance)** | ALL statuses | Same; filters to approved for budget charts |
| **Center Performance** | ALL statuses | Approved + pending per center; centerReports from DPReport |
| **Enhanced Budget Data** | ALL statuses | `approvedProjects` + `pendingProjects`; `pendingTotal` from pending |
| **Center Comparison** | Same as Center Performance | Delegates to `calculateCenterPerformance` |

### Single vs Multiple Datasets

**Finding:** A **single approved-only dataset cannot serve all widgets**.

| Strategy | Serves | Does Not Serve |
|----------|--------|----------------|
| `approvedProjects` only | Budget Summary, budget portions of charts/center/enhanced | Team metrics (projects_by_status), Center (pending_budget), Enhanced (pending_total) |
| `teamProjects` (all statuses) | Team metrics, Chart, Center, Enhanced | Budget Summary (requires filters) |
| **Recommended** | | |
| `teamProjects` (all statuses) | Team metrics, Chart, Center, Enhanced | — |
| `projects` (approved + filters) | Budget Summary | — |

**Current implementation alignment:**
- `$teamProjectsInFy` = all statuses (via DatasetCacheService) → used by 4 widget methods ✓
- `$projects` = approved + filters → used by `calculateBudgetSummariesFromProjects` ✓

**Phase 4 plan mismatch:** The plan proposes passing `$approvedProjects` to all widget methods. This would **break** Team Performance Metrics (no projects_by_status), Center Performance (no pending_budget), and Enhanced Budget (no pending_total).

**Recommendation:** Use **two datasets**:
1. **`teamProjects`** — all statuses, FY-scoped, for Team Metrics, Chart, Center, Enhanced
2. **`projects`** — approved, filtered, for Budget Summary

Phase 4 should refactor to pass `teamProjects` (not `approvedProjects`) to the four widget methods that need all-statuses. Budget Summary continues to use filtered `projects`.

---

## Step 3 — Resolver Compatibility

### ProjectFinancialResolver::resolveCollection()

**Location:** `app/Domain/Budget/ProjectFinancialResolver.php` (lines 212–221)

**Signature:** `resolveCollection(Collection $projects): array`

**Documentation:** "Projects must have reports, reports.accountDetails, budgets eager-loaded."

### Required Project Attributes (from strategies)

| Strategy | Project attributes | Relations |
|----------|--------------------|-----------|
| **PhaseBasedBudgetStrategy** | amount_forwarded, local_contribution, current_phase, overall_project_budget, amount_sanctioned, opening_balance | `budgets` (loadMissing if not loaded) |
| **DirectMappedIndividualBudgetStrategy** | project_type, amount_forwarded, local_contribution, amount_sanctioned, opening_balance | Type-specific: iiesExpenses, iesExpenses, ilpBudget, iahBudgetDetails, igeBudget |

### Current Eager Load vs Resolver Needs

| Source | Loads | Missing for Resolver |
|--------|-------|----------------------|
| DatasetCacheService | user, reports.accountDetails | **budgets** (PhaseBased); type-specific relations (DirectMappedIndividual) |
| Phase 4 plan | user, reports, reports.accountDetails, budgets | Type-specific relations for DirectMappedIndividual |

### Potential N+1 Risks

1. **PhaseBasedBudgetStrategy:** Calls `$project->loadMissing('budgets')` — causes N+1 if budgets not eager-loaded. Current DatasetCacheService does **not** load budgets.
2. **DirectMappedIndividualBudgetStrategy:** Calls `$project->loadMissing($this->getRelationsForType($projectType))` — causes N+1 for IIES, IES, ILP, IAH, IGE projects.
3. **Lightweight projection (Phase 4.5):** A `select([...])` that omits relations would break both strategies unless relations are added to the projection or eager load.

### Recommendation

- Add `budgets` to dataset eager load for PhaseBased project types.
- Add type-specific relations for DirectMappedIndividual types, or accept N+1 for those (typically fewer per province).
- Phase 4.5 lightweight projection must either include required relations or use a resolver-compatible structure.

---

## Step 4 — Controller-Owned Dataset Mutation (Anti-Pattern)

### Risk: "Controller-Owned Dataset Mutation"

**Pattern:**
```
Controller fetches dataset
   ↓
Passes mutable collection to multiple widget methods
   ↓
Widget methods filter/mutate the same collection
```

**Consequences:**
- Cross-widget data corruption if one widget mutates the shared collection
- Inconsistent aggregations when filters run in different order
- Scaling issues when datasets grow (mutations on large collections)

### Current Implementation

Widget methods use **non-mutating** operations:
- `$teamProjects->filter()` → returns new collection
- `$teamProjects->groupBy()` → returns new collection
- `$teamProjects->whereIn()` → returns new collection
- `$teamProjects->map()` → returns new collection

**Verdict:** The shared `$teamProjectsInFy` is **not mutated** by widget methods. They only read and derive new collections.

### Vulnerability

The implementation plan does **not** explicitly require immutability. Future changes could introduce:
- `$teamProjects->transform(...)` (mutates in place)
- `$teamProjects->forget($key)` (mutates)
- Custom logic that modifies project attributes

**Recommendation:** Add **Phase 4A — Immutable Dataset Architecture Safeguard** to the plan. Datasets must be treated as immutable; widget methods must not mutate shared collections. Consider wrapping datasets in immutable DTOs or documenting an immutability contract.

---

## Step 5 — Implementation Plan Update (Phase 4A)

**Action:** Add Phase 4A subsection to `Provincial_Dashboard_FY_Architecture_Implementation_Plan.md` (see that file for full text).

**Summary:** Phase 4A mandates immutable datasets, explains the Dataset Service Pattern, and establishes rules to prevent controller-owned dataset mutation.

---

## Step 6 — Dataset Memory Impact

### Current Model Load

- **Project:** Full Eloquent model (~50+ columns)
- **Relations:** user, reports, reports.accountDetails
- **Missing:** budgets (loadMissing in PhaseBasedStrategy → N+1), type-specific relations (N+1 for IIES/IES/etc.)

### Estimated Memory (Rough)

| Scale | Projects | Est. Memory (projects + user + reports + accountDetails) |
|-------|----------|----------------------------------------------------------|
| 100 | 100 | ~2–5 MB |
| 1,000 | 1,000 | ~15–30 MB |
| 5,000 | 5,000 | ~75–150 MB |

### Phase 4 Dataset Sharing Impact

| Aspect | Impact |
|--------|--------|
| **Queries** | Reduces project queries (already achieved in Phase 3.2/3.3) ✓ |
| **Memory** | Single shared collection instead of 4+ separate fetches — **reduces** total memory |
| **New pressure** | None; sharing replaces duplicate loads |

### Phase 4.5 Lightweight Projection Impact

- **Memory reduction:** 80–90% (fewer columns, optional relation pruning)
- **Risk:** Must satisfy resolver strategies; omit budgets/type-relations only with resolver changes or accept N+1 for subsets

---

## Step 7 — Final Audit Summary

### PHASE 4 FEASIBILITY RESULT

| Verdict | **Needs Minor Refactor** |
|---------|---------------------------|
| Reason | Dataset semantic mismatch: plan assumes `approvedProjects` for all widgets; current (and correct) design uses `teamProjects` (all statuses) for 4 widgets. Budget Summary correctly uses filtered approved `projects`. |

### Required Adjustments

1. **Dataset strategy:** Pass `teamProjects` (all statuses) to Team Metrics, Chart, Center, Enhanced. Keep `projects` (approved + filters) for Budget Summary. Do **not** switch all widgets to approved-only.

2. **Resolver compatibility:** Add `budgets` to dataset eager load; address type-specific relation N+1 (add to load or accept for small subsets).

3. **Immutable dataset safeguard:** Add Phase 4A; document immutability rule; consider Dataset Service returning immutable collections.

### Recommended Implementation Order

1. **Phase 4** — Shared dataset (with corrected semantics: teamProjects + projects)
2. **Phase 4A** — Immutable dataset safeguard
3. **Phase 4.5** — Lightweight projection (ensure resolver compatibility)
4. **Phase 5** — Resolver batch optimization (resolveCollection)

---

## Appendix: Dataset Flow Diagram

```
Current (Phase 3.2/3.3):
────────────────────────
Controller
   ├─ baseProjectsQuery (ProjectQueryService::forProvincial)
   ├─ projects = approved + filters → calculateBudgetSummariesFromProjects
   └─ teamProjectsInFy = DatasetCacheService.getProvincialDataset (all statuses)
         ├─ calculateTeamPerformanceMetrics
         ├─ prepareChartDataForTeamPerformance
         ├─ calculateCenterPerformance
         ├─ calculateEnhancedBudgetData
         └─ prepareCenterComparisonData

Phase 4 (corrected):
────────────────────
Controller
   ├─ teamProjects = all statuses, FY (shared)  [or from cache]
   ├─ projects = approved + filters (for Budget Summary)
   └─ Pass teamProjects to 4 widget methods; projects to Budget Summary
```
