# Coordinator vs Provincial Pending Projects — Feature Comparison Audit

**Date:** 2026-03-08  
**Scope:** Audit only — no code changes  
**Routes:**
- Provincial Pending: `/provincial/projects-list?status=submitted_to_provincial`
- Coordinator Pending: `/coordinator/projects-list?status=forwarded_to_coordinator`

---

## 1. Provincial Page Feature Overview

### 1.1 Route & Controller

| Attribute | Value |
|-----------|-------|
| Route | `GET /provincial/projects-list` |
| Named Route | `provincial.projects.list` |
| Controller | `ProvincialController::projectList()` |
| View | `resources/views/provincial/ProjectList.blade.php` |

### 1.2 Filters

| Filter | Present | Location | Notes |
|--------|---------|----------|-------|
| Financial Year | ✓ | Primary row | `auto-filter`, default next FY when status=submitted_to_provincial |
| Project Type | ✓ | Primary row | FY-scoped options from accessible projects |
| Team Member (Executor) | ✓ | Primary row | Label "Team Member"; users in scope |
| Status | ✓ | Primary row | All status labels from Project::$statusLabels |
| Center | ✓ | Primary row | FY-scoped options |
| Society | ✓ | Primary row | SocietyVisibilityHelper |
| Reset | ✓ | Primary row | Resets to current FY only |

### 1.3 Table Columns

| Column | Present |
|--------|---------|
| S.No | ✓ (TableFormatter::resolveSerial) |
| Project ID | ✓ (link to show) |
| Last Action | ✓ (status_history_max_created_at) |
| Team Member | ✓ (user name, email) |
| Center | ✓ |
| Society | ✓ |
| (Edit Society) | ✓ (Wave 5C, inline icon when canEdit) |
| Project Title | ✓ |
| Project Type | ✓ |
| Overall Budget | ✓ (from resolvedFinancials) |
| Existing Funds | ✓ (amount_forwarded) |
| Local Funds | ✓ (local_contribution) |
| Balance | ✓ (sanctioned or requested by approval status) |
| Health | ✓ (badge: Good/Warning/Critical) |
| Status | ✓ |
| Actions | ✓ (View, Forward, Revert) |

### 1.4 UI Features

| Feature | Present | Notes |
|---------|---------|-------|
| Auto-filter | ✓ | `.auto-filter` on dropdowns, submit on change |
| Reset filters | ✓ | Resets to current FY |
| **Active Filters display** | ✗ | No badge section for active filters |
| Per-page selector | ✓ | TableFormatter::ALLOWED_PAGE_SIZES (10,25,50,100) |
| Grand totals summary | ✓ | Card above table: Total Records, Overall Budget, Existing Funds, Local Contribution, Amount Sanctioned, Amount Requested |
| Status distribution cards | ✓ | 6 status cards above table |
| Status chart modal | ✓ | ApexCharts donut |
| Update Society modal | ✓ | Wave 5C |
| Pagination | ✓ | `$projects->links()` (Laravel paginator) |
| Query string persistence | ✓ | `withQueryString()` on paginator |

### 1.5 Query Architecture

| Component | Usage |
|-----------|-------|
| Project model | `Project::accessibleByUserIds($accessibleUserIds)` |
| FY scope | `->inFinancialYear($fy)` |
| Base query | Manual filters: project_type, user_id, status, center, society_id |
| Full dataset | `(clone $baseQuery)->with([...])->get()` |
| **resolveCollection** | ✓ `ProjectFinancialResolver::resolveCollection($fullDataset)` |
| Grand totals | Computed from resolvedFinancials over full dataset |
| Pagination | `(clone $baseQuery)->paginate($perPage)->withQueryString()` |
| Page items transform | Attach budget_utilization, health_status from resolvedFinancials |
| ProjectQueryService | ✗ Not used in projectList |
| DatasetCacheService | ✗ Not used in projectList |
| Filter options | Direct queries: users, projectTypes (FY-scoped), centers (FY-scoped), societies |

---

## 2. Coordinator Page Feature Overview

### 2.1 Route & Controller

| Attribute | Value |
|-----------|-------|
| Route | `GET /coordinator/projects-list` |
| Named Route | `coordinator.projects.list` |
| Controller | `CoordinatorController::projectList()` |
| View | `resources/views/coordinator/ProjectList.blade.php` |

### 2.2 Filters

| Filter | Present | Location | Notes |
|--------|---------|----------|-------|
| Financial Year | ✓ | Primary row | `auto-filter`, default next FY when status=forwarded_to_coordinator |
| Search | ✓ | Primary row | project_id, project_title, project_type, status |
| Province | ✓ | Primary row | All provinces (coordinator scope) |
| Status | ✓ | Primary row | All statuses |
| Project Type | ✓ | Primary row | All project types |
| Clear | ✓ | Primary row | Full reset |
| Provincial | ✓ | Advanced (collapsible) | parent_id filter |
| Executor/Applicant | ✓ | Advanced | user_id filter |
| Center | ✓ | Advanced | Center filter |
| Sort By | ✓ | Advanced | created_at, project_id, project_title, budget_utilization |
| Sort Order | ✓ | Advanced | asc/desc |
| Start Date | ✓ | Advanced | created_at >= |
| End Date | ✓ | Advanced | created_at <= |

### 2.3 Table Columns

| Column | Present |
|--------|---------|
| Project ID | ✓ (link to show) |
| Last Action | ✓ |
| Project Title | ✓ |
| Project Type | ✓ |
| Executor/Applicant | ✓ |
| Province | ✓ |
| Center | ✓ |
| Provincial | ✓ |
| Status | ✓ |
| Budget | ✓ (calculated_budget) |
| Expenses | ✓ (calculated_expenses) |
| Remaining | ✓ (calculated_remaining) |
| Utilization | ✓ (progress bar) |
| Health | ✓ (badge) |
| Reports | ✓ (count, approved count) |
| Actions | ✓ (View, Approve, Revert, Download PDF) |

### 2.4 UI Features

| Feature | Present | Notes |
|---------|---------|-------|
| Auto-filter | ✓ | `.auto-filter` on dropdowns |
| Reset/Clear | ✓ | Full reset |
| **Active Filters display** | ✓ | Badge section for fy, search, province, status, project_type, provincial_id, user_id, center, dates |
| Advanced Filters | ✓ | Collapsible section |
| Pagination | ✓ | Manual skip/take, custom pagination metadata |
| Per-page selector | ✗ | Fixed 100 per page |
| Grand totals summary | ✗ | None |
| Status distribution | ✗ | None |
| Query string persistence | ✓ | `fullUrlWithQuery(['page' => ...])` |

### 2.5 Query Architecture

| Component | Usage |
|-----------|-------|
| ProjectAccessService | ✓ `getVisibleProjectsQuery($coordinator, $fy)` |
| FY scope | ✓ Applied via ProjectAccessService |
| Filters | search, province, provincial_id, user_id, center, project_type, status, start_date, end_date |
| **resolveCollection** | ✗ Not used |
| Resolver | Per-project `$resolver->resolve($project)` in `map()` — N+1 pattern |
| Pagination | Manual `skip()->take()->get()` |
| Filter cache | ✓ `Cache::remember('coordinator_project_list_filters', 5 min)` for dropdown options |
| ProjectQueryService | ✗ Not used |
| DatasetCacheService | ✗ Not used in projectList |

---

## 3. Feature Comparison Table

| Feature | Provincial | Coordinator | Status |
|---------|------------|-------------|--------|
| FY Filter | ✓ | ✓ | Both present |
| Project Type Filter | ✓ | ✓ | Both present |
| Executor Filter | ✓ (Team Member) | ✓ (Advanced) | Both present |
| Center Filter | ✓ | ✓ | Both present |
| Province Filter | ✗ (N/A – provincial sees own) | ✓ | Coordinator-only |
| Provincial Filter | ✗ (N/A) | ✓ | Coordinator-only |
| Society Filter | ✓ | ✗ | Provincial only |
| Status Filter | ✓ | ✓ | Both present |
| Search | ✗ | ✓ | Coordinator only |
| Sort By / Order | ✗ | ✓ | Coordinator only |
| Date Range Filter | ✗ | ✓ | Coordinator only |
| Active Filters Display | ✗ | ✓ | Coordinator only |
| Auto Filter UI | ✓ | ✓ | Both present |
| Reset/Clear | ✓ | ✓ | Both present |
| Per-page Selector | ✓ | ✗ | Provincial only |
| Pagination | ✓ | ✓ | Both present |
| Grand Totals Summary | ✓ | ✗ | Provincial only |
| Status Distribution | ✓ | ✗ | Provincial only |
| Status Chart Modal | ✓ | ✗ | Provincial only |
| Budget Columns (breakdown) | Overall, Existing, Local, Balance | Budget, Expenses, Remaining, Utilization | Different naming; both have budget metrics |
| Health Indicator | ✓ | ✓ | Both present |
| Action Buttons | View, Forward, Revert | View, Approve, Revert, Download PDF | Role-appropriate |
| Update Society | ✓ | ✗ | Provincial only |
| Reports Column | ✗ | ✓ | Coordinator only |
| ProjectQueryService | ✗ | ✗ | Neither uses in projectList |
| resolveCollection | ✓ | ✗ | Provincial only; Coordinator uses per-project resolve |
| Dataset Reuse | ✓ (full dataset → resolvedFinancials) | ✗ | Provincial only |
| Filter Caching | ✗ | ✓ | Coordinator only |
| TableFormatter::resolvePerPage | ✓ | ✗ | Provincial only |
| Laravel Paginator | ✓ | ✗ (manual) | Provincial uses `->paginate()` |

---

## 4. Architectural Differences

### 4.1 Controller Comparison

| Aspect | Provincial | Coordinator |
|--------|------------|-------------|
| Access layer | `getAccessibleUserIds()` + `Project::accessibleByUserIds()` | `ProjectAccessService::getVisibleProjectsQuery()` |
| FY handling | `inFinancialYear($fy)` on base query | FY passed to `getVisibleProjectsQuery($coordinator, $fy)` |
| Financial resolution | `resolveCollection($fullDataset)` once | Per-project `$resolver->resolve($project)` in loop |
| Grand totals | ✓ From full dataset + resolvedFinancials | ✗ Not computed |
| Status distribution | ✓ From full dataset | ✗ Not computed |
| Pagination | Laravel `->paginate($perPage)->withQueryString()` | Manual `skip()->take()->get()` + metadata array |
| Per-page | `TableFormatter::resolvePerPage($request)` | Fixed 100 |
| Filter options | Direct queries, FY-scoped where applicable | Cached 5 min, not FY-scoped |

### 4.2 Architectural Gaps (Coordinator vs Provincial)

1. **No resolveCollection:** Coordinator uses N+1 per-project resolution instead of batch `resolveCollection()`. Performance impact on large lists.

2. **No full-dataset reuse:** Provincial loads full filtered dataset once, resolves batch, computes grand totals and status distribution, then paginates. Coordinator loads only current page and resolves per item.

3. **No grand totals / status distribution:** Coordinator page does not show summary block or status breakdown.

4. **Manual pagination:** Coordinator uses `skip()->take()->get()` instead of `->paginate()`. No `withQueryString()` on a LengthAwarePaginator; uses custom `$pagination` array and `fullUrlWithQuery()` in Blade.

5. **No per-page selector:** Coordinator hardcodes 100 items per page.

6. **Filter options not FY-scoped:** Coordinator filter cache returns all project types, centers, etc., regardless of selected FY. Provincial scopes project types and centers to FY.

---

## 5. Missing Features (Coordinator vs Provincial)

Features present on Provincial but missing on Coordinator:

| Feature | Impact |
|---------|--------|
| Grand totals summary block | High — no overview of total budget, sanctioned, requested |
| Status distribution cards | High — no at-a-glance status breakdown |
| Status chart modal | Medium — no visual distribution |
| Per-page selector | Medium — fixed 100, no 10/25/50 option |
| resolveCollection (batch resolution) | High — N+1 performance risk |
| Laravel paginator with withQueryString | Medium — manual pagination less robust |
| Society filter | N/A — coordinator sees cross-province; society is provincial-scoped |
| Update Society action | N/A — provincial-only workflow |

---

## 6. Role-Specific Considerations

### 6.1 Provincial Role

- Sees projects from executors/applicants **under them** (same province/hierarchy).
- Filters: FY, Project Type, Team Member, Center, Society, Status.
- Society filter and Update Society are relevant (provincial manages societies in scope).

### 6.2 Coordinator Role

- Sees projects from **multiple provinces** (global oversight).
- Coordinator-specific filters: Province, Provincial.
- Search, sort, date range are useful for large cross-province lists.
- Society filter is less relevant (coordinator typically does not update society).
- Province and Provincial filters are appropriate and already present.

---

## 7. Recommended Improvements

### Critical Improvements

1. **Replace per-project resolve with resolveCollection**
   - Load paginated projects, resolve batch via `ProjectFinancialResolver::resolveCollection()`.
   - Eliminates N+1 and aligns with Provincial/Executor patterns.

2. **Add grand totals summary block**
   - Compute totals over full filtered dataset (as Provincial does).
   - Requires full-dataset or aggregated query; can be done with a separate count/SUM query to avoid loading all rows.

3. **Add status distribution**
   - Status count cards and optional chart modal.
   - Requires full-dataset or grouped count query.

### High-Value UX Improvements

4. **Add per-page selector**
   - Use `TableFormatter::resolvePerPage($request)` and `TableFormatter::ALLOWED_PAGE_SIZES`.
   - Replace manual pagination with Laravel `->paginate($perPage)->withQueryString()`.

5. **FY-scope filter options**
   - Limit project types, centers, users to those with projects in selected FY (as Provincial does).
   - Consider invalidating or not using filter cache when FY changes, or including FY in cache key.

### Optional Improvements

6. **Active Filters on Provincial**
   - Provincial lacks Active Filters badges; could add for parity with Coordinator.

7. **Filter cache tuning**
   - Coordinator caches filter options 5 min. Consider FY in cache key or shorter TTL when FY-scoping is added.

8. **Preserve filters on redirect**
   - When returning from approve/revert, preserve query string (see `Preserve_Filters_On_Redirect_Pattern.md` for Provincial).

---

## 8. Phase-wise Implementation Plan

### Phase 1 — Filter & Pagination Architecture

| Task | Description |
|------|-------------|
| 1.1 | Introduce `TableFormatter::resolvePerPage($request)` |
| 1.2 | Replace manual skip/take with Laravel `->paginate($perPage)->withQueryString()` |
| 1.3 | Add per-page selector UI (10, 25, 50, 100) |
| 1.4 | FY-scope filter options (project types, centers) — include FY in cache key or query |

**Deliverables:** Standard pagination, per-page control, FY-aware filters.

---

### Phase 2 — Financial Resolution & Summary

| Task | Description |
|------|-------------|
| 2.1 | Load full filtered dataset (or use aggregated queries) for grand totals |
| 2.2 | Call `ProjectFinancialResolver::resolveCollection($projects)` once |
| 2.3 | Compute grand totals (overall budget, sanctioned, requested, etc.) |
| 2.4 | Add grand totals summary block to Blade |
| 2.5 | Replace per-project `$resolver->resolve()` in paginated list with lookup from resolvedFinancials map |

**Deliverables:** Batch resolution, grand totals summary, no N+1.

---

### Phase 3 — Status Distribution

| Task | Description |
|------|-------------|
| 3.1 | Compute status distribution from full dataset (or grouped count) |
| 3.2 | Add status distribution cards above table |
| 3.3 | Add status chart modal (ApexCharts donut) — optional |

**Deliverables:** Status breakdown cards, optional chart.

---

### Phase 4 — Dataset Reuse & Optional Cache

| Task | Description |
|------|-------------|
| 4.1 | Align controller flow: base query → full dataset for totals/distribution → paginate for listing |
| 4.2 | Share resolvedFinancials map between grand totals, status distribution, and table rows |
| 4.3 | Evaluate DatasetCacheService for coordinator project list (low priority; list is filter-heavy) |

**Deliverables:** Single resolveCollection, shared financial map, documented cache options.

---

### Phase 5 — UI Polish

| Task | Description |
|------|-------------|
| 5.1 | Align budget column naming (Overall/Existing/Local/Balance vs Budget/Expenses/Remaining) if desired |
| 5.2 | Preserve filters on redirect after approve/revert |
| 5.3 | Add tooltips, improve table layout consistency with Provincial |

**Deliverables:** Consistent UX, filter preservation on redirect.

---

## 9. Summary

| Area | Provincial | Coordinator | Gap |
|------|------------|-------------|-----|
| Filters | FY, Type, Executor, Center, Status, Society | FY, Search, Province, Status, Type, Provincial, Executor, Center, Sort, Date | Coordinator has more filters; Provincial has Society |
| Budget display | Overall, Existing, Local, Balance | Budget, Expenses, Remaining, Utilization | Different breakdown; both adequate |
| Grand totals | ✓ | ✗ | **Missing** |
| Status distribution | ✓ | ✗ | **Missing** |
| resolveCollection | ✓ | ✗ (N+1) | **Missing** |
| Per-page selector | ✓ | ✗ | **Missing** |
| Laravel paginator | ✓ | ✗ | **Missing** |
| Active Filters | ✗ | ✓ | Coordinator has; Provincial does not |

The Coordinator page has stronger filter capabilities (province, provincial, search, sort, date range) and Active Filters display, but lacks the financial/status summary architecture and batch resolution used on the Provincial page. The recommended phases bring Coordinator in line with Provincial patterns while preserving Coordinator-specific filters and actions.
