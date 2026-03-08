# Phase 5 — Resolver Batch Optimization Feasibility Audit

**Date:** 2026-03-05  
**Phase:** Phase 5 — Resolver Batch Optimization  
**Reference:** Provincial_Dashboard_FY_Architecture_Implementation_Plan.md  

---

## Executive Summary

Resolver batch optimization is **feasible and recommended**. `ProjectFinancialResolver::resolveCollection()` already exists and returns a map keyed by `project_id`. The Provincial Dashboard currently invokes `$resolver->resolve($project)` in loops across multiple widget methods and controller flows, resulting in **repeated resolution of the same projects**. For a dashboard with 500 projects, the same project may be resolved 3–6 times across different widgets. A single call to `resolveCollection($teamProjects)` at the start of the dashboard flow, with the resulting map passed to all widgets, would eliminate redundant resolver executions. Both strategies (PhaseBasedBudgetStrategy, DirectMappedIndividualBudgetStrategy) are stateless and compatible with batch resolution. **Recommendation:** Implement Phase 5 by resolving once on `$teamProjects` and passing the map into all aggregation methods.

---

## Step 1 — Current Resolver Call Analysis

### Locations and Call Type

| Location | Method / Action | Call Type | Loop Context |
|----------|-----------------|-----------|--------------|
| ProvincialController ~78 | provincialDashboard (getSocietyStats) | `$resolver->resolve($project)` | foreach ($projects as $project) |
| ProvincialController ~329 | calculateBudgetSummariesFromProjects | `$resolver->resolve($project)` | foreach ($projects as $project) |
| ProvincialController ~568 | projectList | `$resolver->resolve($project)` | foreach ($fullDataset as $project) |
| ProvincialController ~1823 | projectList (paginated attach) | Uses resolvedFinancials map (from fullDataset loop) | N/A — map lookup |
| ProvincialController ~2245 | calculateTeamPerformanceMetrics | `$resolver->resolve($project)` | foreach ($approvedProjects as $project) |
| ProvincialController ~2334 | prepareChartDataForTeamPerformance | `$resolver->resolve($project)` | foreach ($approvedProjects as $project) |
| ProvincialController ~2416 | calculateCenterPerformance | `$resolver->resolve($project)` | Per center: foreach approved + sum(pending resolve) |
| ProvincialController ~2479 | calculateEnhancedBudgetData | `$resolver->resolve($project)` | pendingTotal sum + foreach approved |
| CoordinatorController | Multiple methods | `$resolver->resolve($project)` | Various loops |
| AdminReadOnlyController ~50 | admin view | `$resolver->resolve($project)` | foreach |
| projectList ~2245 | projectList (separate action) | Resolver in fullDataset loop | foreach ($fullDataset as $project) |

### Provincial Dashboard Flow (provincialDashboard action)

| Consumer | Dataset | Resolve Count |
|----------|---------|---------------|
| getSocietyStats | Society projects (separate query) | P (projects in society scope) |
| calculateBudgetSummariesFromProjects | $projects (approved, filtered) | M |
| calculateTeamPerformanceMetrics | teamProjects → approvedProjects | N_approved |
| prepareChartDataForTeamPerformance | teamProjects → approvedProjects | N_approved |
| calculateCenterPerformance | teamProjects → per center | N_approved + N_pending |
| calculateEnhancedBudgetData | teamProjects → approved + pending | N_pending + N_approved |

**Overlap:** The same approved project is resolved in calculateTeamPerformanceMetrics, prepareChartDataForTeamPerformance, calculateCenterPerformance, and calculateEnhancedBudgetData. Pending projects are resolved in calculateCenterPerformance and calculateEnhancedBudgetData.

---

## Step 2 — Resolver Call Frequency Analysis

### Per-Request Resolve Count (provincialDashboard)

| Scale | Projects | Approx. Resolves (Current) | With Batch |
|-------|----------|----------------------------|------------|
| 100 projects | 100 | ~400–600 (4–6× per project across widgets) | 100 |
| 500 projects | 500 | ~2,000–3,000 | 500 |
| 2,000 projects | 2,000 | ~8,000–12,000 | 2,000 |

**Note:** Exact multiplier depends on approved vs pending split and center distribution. Conservative estimate: 4× redundant resolution on average.

### projectList Action

Resolves once per project in fullDataset (no widget overlap). Batch would replace the loop with a single `resolveCollection($fullDataset)`.

---

## Step 3 — Resolver Dependency Requirements

### ProjectFinancialResolver Input

| Requirement | Source | In Phase 4.5 Projection? |
|-------------|--------|--------------------------|
| project_id | Project attribute | ✓ |
| project_type | Project attribute | ✓ |
| status (isApproved) | Project attribute | ✓ |
| amount_forwarded | Project attribute | ✓ |
| local_contribution | Project attribute | ✓ |
| amount_sanctioned | Project attribute | ✓ |
| opening_balance | Project attribute | ✓ |
| overall_project_budget | Project attribute | ✓ |
| current_phase | Project attribute | ✓ |
| user | Relation | ✓ (eager-loaded) |
| reports.accountDetails | Relation | ✓ (eager-loaded) |
| budgets | Relation | ✓ (eager-loaded) |

**Strategy selection:** `getStrategyForProject()` uses `project_type` only. No shared resolver state.

**Conclusion:** Phase 4.5 projected dataset contains all required fields and relations for resolution.

---

## Step 4 — Batch Resolution Strategy

### Existing API

`ProjectFinancialResolver::resolveCollection(Collection $projects): array` already exists (Phase 2.6):

```php
public static function resolveCollection(Collection $projects): array
{
    $resolver = app(self::class);
    $result = [];
    foreach ($projects as $project) {
        $result[$project->project_id] = $resolver->resolve($project);
    }
    return $result;
}
```

### Proposed Usage

```php
// In provincialDashboard, after $teamProjects is loaded
$resolvedFinancials = \App\Domain\Budget\ProjectFinancialResolver::resolveCollection($teamProjects);

// Pass to widgets
$performanceMetrics = $this->calculateTeamPerformanceMetrics($provincial, $fy, $teamProjects, $resolvedFinancials);
$chartData = $this->prepareChartDataForTeamPerformance($provincial, $fy, $teamProjects, $resolvedFinancials);
// ... etc
```

**Data flow:** One resolution pass over `$teamProjects`. Map covers both approved and pending; widgets use `opening_balance` for approved, `amount_requested` for pending.

---

## Step 5 — Resolved Financial Map Design

### Structure

```php
$resolvedFinancials = [
    'DP-0041' => [
        'overall_project_budget' => 100000.00,
        'amount_forwarded' => 0.00,
        'local_contribution' => 5000.00,
        'amount_sanctioned' => 95000.00,
        'amount_requested' => 0.00,
        'opening_balance' => 100000.00,
    ],
    'DP-0042' => [
        // ...
    ],
];
```

### Widget Usage

| Widget | Keys Used |
|--------|-----------|
| calculateBudgetSummariesFromProjects | opening_balance |
| calculateTeamPerformanceMetrics | opening_balance |
| prepareChartDataForTeamPerformance | opening_balance |
| calculateCenterPerformance | opening_balance (approved), amount_requested (pending) |
| calculateEnhancedBudgetData | opening_balance (approved), amount_requested (pending) |

**Lookup:** `$resolvedFinancials[$project->project_id] ?? []`

---

## Step 6 — Widget Integration Strategy

### Option A: Pass Financial Map (Recommended)

- Add `$resolvedFinancials` as final parameter to each widget method.
- Widgets perform map lookup: `$resolvedFinancials[$project->project_id] ?? []`.
- No mutation of project models.
- Explicit dependency; easy to test.

### Option B: Attach to Project Models

- Set `$project->resolvedFinancials = $map[$project->project_id]` (would mutate dataset).
- **Rejected:** Violates Phase 4A immutability contract. Dataset must remain read-only.

**Recommendation:** Option A — pass map as parameter.

---

## Step 7 — Dataset Compatibility

### teamProjects

- Source: `DatasetCacheService::getProvincialDataset($provincial, $fy)`
- Contains: All statuses, FY-scoped, with `user`, `reports.accountDetails`, `budgets`
- Phase 4A: Treated as immutable

### resolveCollection() on teamProjects

- **Read-only:** resolveCollection iterates and reads; does not mutate the collection.
- **Compatible:** Each project has required attributes and relations.
- **Cache:** Dataset is cached; resolution runs on the same in-memory collection. No cache mutation.

### $projects (Budget Summaries)

- Subset of teamProjects (approved + filters). All project_ids in $projects exist in teamProjects.
- Lookup: `$resolvedFinancials[$project->project_id]` works for every project in $projects.

---

## Step 8 — Resolver Strategy Compatibility

### PhaseBasedBudgetStrategy

- Stateless; no instance state between calls.
- Uses `$project->loadMissing('budgets')`; budgets are eager-loaded in teamProjects.
- Compatible with batch.

### DirectMappedIndividualBudgetStrategy

- Stateless; no shared state.
- Uses `$project->loadMissing($this->getRelationsForType($projectType))` for type-specific relations.
- loadMissing is additive; does not mutate existing relations.
- Compatible with batch (N+1 for type-specific relations remains; same as today).

### ProjectFinancialResolver

- No cross-project state.
- `resolve()` is pure for a given project.
- `resolveCollection()` is a simple loop; no state accumulation.

---

## Step 9 — Performance Impact Estimation

### Resolver Executions

| Scale | Current | With Batch | Reduction |
|-------|---------|------------|-----------|
| 100 projects | ~400–600 | 100 | ~75–83% |
| 500 projects | ~2,000–3,000 | 500 | ~75–83% |
| 2,000 projects | ~8,000–12,000 | 2,000 | ~75–83% |

### Expected Improvements

- **CPU time:** Fewer resolver invocations; strategy selection and canonical separation run once per project.
- **Dashboard response time:** Estimated 15–30% improvement for large provinces (500+ projects), depending on DB and PHP load.
- **Memory:** One map of ~6 floats per project; negligible vs full model hydration.

---

## Step 10 — Risk Analysis

| Risk | Severity | Mitigation |
|------|----------|------------|
| Missing project_id in map | Medium | Use `$resolvedFinancials[$project->project_id] ?? []`; empty array yields 0 for numeric keys |
| Widget receives null map | Medium | Validate map is non-null before passing; fallback to inline resolution if needed (defensive) |
| Society stats uses different dataset | Low | getSocietyStats has separate query; can call resolveCollection(societyProjects) or keep inline for now |
| projectList uses different dataset | Low | projectList has fullDataset; can use resolveCollection(fullDataset) in that action |
| Resolver state assumption | Low | Strategies are stateless; verified |

---

## Step 11 — Updated Phase 5 Implementation Plan

### Refinements Based on Audit

1. **Single resolution pass:** Call `resolveCollection($teamProjects)` once. The map includes both approved and pending; no need to split into approvedProjects/pendingProjects before resolution.

2. **Widget signature change:** Add optional `?array $resolvedFinancials = null` to each widget. When provided, use map lookup; when null (fallback), retain current inline resolution for backward compatibility during rollout.

3. **Budget summaries:** `$projects` is a subset of teamProjects; `$resolvedFinancials[$project->project_id]` suffices. Pass the same map.

4. **Society stats:** Separate flow; can be optimized in a follow-up. Phase 5 focuses on dashboard widgets and budget summaries.

5. **projectList:** Separate action; apply same pattern: `resolveCollection($fullDataset)` once, reuse for grand totals and paginated attach.

---

## Verification Checklist

| # | Verification | Status |
|---|--------------|--------|
| 1 | All resolver call sites in ProvincialController identified | ✓ |
| 2 | Resolver call frequency and redundancy documented | ✓ |
| 3 | resolveCollection() API and behaviour understood | ✓ |
| 4 | Resolved financial map structure supports all widgets | ✓ |
| 5 | Widget integration strategy (pass map) chosen | ✓ |
| 6 | Dataset compatibility confirmed | ✓ |
| 7 | Phase 4A immutability preserved | ✓ |
| 8 | Both strategies compatible with batch | ✓ |
| 9 | Performance improvement estimated | ✓ |
| 10 | Risks and mitigations documented | ✓ |
| 11 | Phase 5 implementation plan refined | ✓ |
