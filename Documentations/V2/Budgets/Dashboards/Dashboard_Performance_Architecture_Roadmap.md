# Dashboard Performance Architecture Roadmap

**Date:** 2026-03-05  
**Scope:** Executor, Provincial, Coordinator dashboards  
**Objective:** Scalable architecture from 100 → 5,000 → 50,000+ projects without future rewrites.

---

## 1. Overview

The dashboard system follows a layered performance architecture. All dashboards (Executor, Provincial, Coordinator) share the same data pipeline:

```
QUERY → DATASET → RESOLVER → AGGREGATION → CACHE → VIEW
```

This document details each component: dataset services, resolver batching, projection datasets, dataset caching, database aggregation, snapshot tables, cache invalidation, and the scalability roadmap.

---

## 2. Dataset Services

### 2.1 Purpose

Dataset services centralize project fetching logic. They:

- Build scoped queries (by user, province, FY)
- Apply role/center/society filters
- Return collections for aggregation

### 2.2 Examples

| Service | Scope | Used By |
|---------|-------|---------|
| `ProjectQueryService` | Executor owned/in-charge projects | ExecutorController |
| `ProvincialDashboardDatasetService` | Provincial team projects | ProvincialController |
| `CoordinatorDatasetService` (or equivalent) | Coordinator/global scope | CoordinatorController |

### 2.3 Responsibilities

- **Query construction:** `accessibleByUserIds`, `inFinancialYear`, status filters
- **Relation loading:** Minimal eager load (e.g., `user` for center/role; reports only when needed)
- **Filter application:** Center, role, project_type from request

### 2.4 Interface Pattern

```php
interface DashboardDatasetServiceInterface
{
    public function fetchApprovedProjects(User $user, string $fy, array $filters = []): Collection;
    public function fetchPendingProjects(User $user, string $fy): Collection;
}
```

---

## 3. Resolver Batching

### 3.1 Problem

Per-project `$resolver->resolve($project)` in loops causes N resolver calls per page load. At 1000+ projects, this is expensive.

### 3.2 Solution

`ProjectFinancialResolver::resolveCollection(Collection $projects): array`

- Resolves all projects in one pass
- Returns map: `[project_id => resolved_data]`
- Call once per request; reuse map for all widgets

### 3.3 Usage

```php
$resolvedFinancials = ProjectFinancialResolver::resolveCollection($approvedProjects);
// Use $resolvedFinancials[$project->project_id] in aggregation loops
```

### 3.4 Benefits

- One resolution pass per project
- No duplicate resolution across widgets
- Scales linearly with project count

---

## 4. Projection Datasets

### 4.1 Problem

Full Eloquent model hydration with `with(['user', 'reports.accountDetails', 'budgets'])` loads many columns and relations. At 5000+ projects, memory usage becomes prohibitive.

### 4.2 Solution: Lightweight Select

Select only fields needed for aggregation and resolver:

```php
Project::accessibleByUserIds($ids)
    ->approved()
    ->inFinancialYear($fy)
    ->select([
        'project_id', 'province_id', 'society_id', 'project_type',
        'user_id', 'in_charge', 'commencement_month_year',
        'opening_balance', 'amount_sanctioned', 'amount_forwarded',
        'local_contribution', 'overall_project_budget', 'status'
    ])
    ->get();
```

### 4.3 Relation Strategy

- **Resolver:** Uses project attributes; no relations required.
- **Center/role grouping:** Join `users` for `center`, `role`; avoid full `user` relation.
- **Reports/expenses:** Separate report-summary query (e.g., `SUM(total_expenses) GROUP BY project_id`) instead of loading full report trees.

### 4.4 Benefits

- 80–90% memory reduction
- 20–40× load speed improvement
- Enables 5000+ project dashboards

---

## 5. Dataset Caching

### 5.1 Purpose

Cache raw datasets (approvedProjects, pendingProjects) so repeated dashboard loads skip database queries.

### 5.2 Cache Key

`dashboard_dataset_{role}_{user_or_province_id}_{fy}`

Examples:

- `dashboard_dataset_executor_37_2025-26`
- `dashboard_dataset_provincial_5_2025-26`
- `dashboard_dataset_coordinator_0_2025-26`

### 5.3 TTL

5–10 minutes (configurable).

### 5.4 Flow

1. Controller requests dataset from service
2. Service checks cache; on hit, return cached collection
3. On miss: query DB, store in cache, return

### 5.5 Benefits

- Prevents repeated dataset queries
- 10–30× acceleration for repeated loads
- Reduces database load

---

## 6. Database Aggregation

### 6.1 Purpose

Move heavy aggregations from PHP to SQL. Avoid loading full project collections when only totals are needed.

### 6.2 Example

**Before (PHP):**

```php
foreach ($projects as $project) {
    $total += $project->opening_balance;
}
```

**After (SQL):**

```sql
SELECT SUM(opening_balance) FROM projects
WHERE province_id = ? AND commencement_month_year BETWEEN ? AND ?
```

### 6.3 Grouped Aggregations

- Province totals: `GROUP BY province_id`
- Center totals: Join users, `GROUP BY users.center`
- Society totals: `GROUP BY society_id`

### 6.4 Use Cases

- Summary cards (total budget, total expenses)
- Breakdown tables (by center, by society)
- Chart data (pie/bar by project type)

### 6.5 Benefits

- Minimal memory
- Scales to tens of thousands of projects
- Faster than PHP loops

---

## 7. Snapshot Tables

### 7.1 Purpose

Precomputed statistics stored in tables. Dashboards read from snapshots instead of computing live. Supports 50,000+ projects and historical analytics.

### 7.2 Example Schema

```sql
CREATE TABLE dashboard_snapshots (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    province_id BIGINT UNSIGNED,
    fy VARCHAR(10),
    total_budget DECIMAL(15,2),
    total_expenses DECIMAL(15,2),
    project_count INT,
    updated_at TIMESTAMP
);
```

### 7.3 Refresh Mechanism

- Scheduled jobs (cron, Laravel scheduler) refresh snapshots hourly or on-demand
- Optional: event-triggered refresh on project/report changes

### 7.4 Dashboard Integration

- Check if snapshot exists and is fresh
- If yes: read snapshot; skip resolver and aggregation
- If no: fall back to live computation

### 7.5 Benefits

- Supports 50,000+ projects
- Reduces resolver and aggregation workload
- Enables historical analytics (trends over time)

---

## 8. Cache Invalidation

### 8.1 Problem

TTL-based cache expiration can leave dashboards showing stale data for minutes after a project/report change.

### 8.2 Solution: Event-Driven Invalidation

Invalidate cache when data changes:

| Event | Cache Keys to Invalidate |
|-------|--------------------------|
| Project approved | `dashboard_dataset_*`, `*_dashboard_{scope}` for affected province/executor |
| Project updated | Same |
| Budget updated | Same |
| Report submitted | Same |
| Report approved | Same |

### 8.3 Implementation

- Model observers: `Project::observe()`, `DPReport::observe()`
- Event listeners: `project.approved`, `report.submitted`, etc.
- Invalidation service: `DashboardCacheInvalidator::invalidateForProject(Project $project)`

### 8.4 Benefits

- Dashboards show fresh data after changes
- Avoids stale totals
- Complements TTL for reliability

---

## 9. Scalability Roadmap

### Level 1 — Standard (≤ 5,000 projects)

| Component | Implementation |
|-----------|----------------|
| Dataset | Shared dataset, single fetch per request |
| Projection | Lightweight select (Phase 4.5) |
| Resolver | `resolveCollection()` once |
| Cache | Dashboard cache (Phase 6) |

**Phases:** 1–7

---

### Level 2 — Large (≤ 50,000 projects)

| Component | Addition |
|-----------|----------|
| Dataset cache | Universal dataset cache (Phase 3.5) |
| Aggregation | Database aggregation (Phase 8) |
| Snapshots | Dashboard snapshot tables (Phase 9) |
| Invalidation | Event-driven cache invalidation (Phase 10) |

**Phases:** 1–10

---

### Level 3 — Enterprise (100,000+ projects)

| Component | Addition |
|-----------|----------|
| Financial summary tables | Extended snapshot schema |
| Analytics warehouse | Read replicas, denormalized views |
| Background jobs | Queue-driven aggregation and snapshot refresh |

---

## 10. Architecture Summary

```
DATABASE
   ↓
DATASET SERVICES (query, projection, optional dataset cache)
   ↓
SHARED DATASETS (approvedProjects, pendingProjects)
   ↓
FINANCIAL RESOLVER (resolveCollection)
   ↓
AGGREGATION LAYER (PHP or DB aggregation; optional snapshots)
   ↓
CACHE LAYER (dataset cache, dashboard cache, snapshot reads)
   ↓
CONTROLLERS (ExecutorController, ProvincialController, CoordinatorController)
   ↓
VIEWS
```

All three dashboards — **Executor**, **Provincial**, **Coordinator** — share this pipeline. Scaling improvements apply uniformly across roles.

---

## 11. References

- `Documentations/V2/Budgets/Dashboards/Provincial/Provincial_Dashboard_FY_Architecture_Implementation_Plan.md`
- `app/Domain/Budget/ProjectFinancialResolver.php`
- `app/Services/ProjectQueryService.php`
- `app/Support/FinancialYearHelper.php`
