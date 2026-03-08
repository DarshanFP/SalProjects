# Coordinator Dashboard Implementation Feasibility Audit

**Date:** 2026-03-06  
**Scope:** Validation of Coordinator_Dashboard_Implementation_Roadmap.md  
**Objective:** Verify the roadmap can be safely implemented within the current codebase; identify conflicts, risks, and readiness.  
**Mode:** Audit only — no code modifications.

---

## Executive Summary

The Coordinator Dashboard Implementation Roadmap is **feasible and ready for implementation** with minor clarifications. All eight phases are correctly ordered, dependencies are valid, and no circular dependencies exist. Key findings:

- **Query layer:** `ProjectQueryService::forCoordinator()` integrates cleanly; no conflict with `forProvincial()`; `ProjectAccessService::getVisibleProjectsQuery()` already returns the correct coordinator scope.
- **DatasetCacheService:** Extension is safe; cache keys are isolated (`coordinator_dataset_` vs `provincial_dataset_`); no collision risk.
- **Province partitioning:** Compatible with dataset caching; partitions built after retrieval; Laravel collections serialize correctly.
- **Resolver batch:** `resolveCollection()` exists and is used by Executor and Provincial; coordinator dataset is compatible; requires same eager loads (`user`, `reports.accountDetails`, `budgets`).
- **Widget refactor:** All six target widgets can accept shared dataset + partitions + map; internal queries removable; one exception: getProvincialManagementData groups by provincial (parent), not province — derives from shared dataset via `whereIn('user_id', $teamUserIds)`.
- **Dashboard cache:** Key `coordinator_dashboard_{fy}_{filterHash}` cannot collide with `provincial_dashboard_{province_id}_{fy}_{filterHash}` (different prefixes and structure).
- **Memory:** Estimated 5–25 MB for 500–5,000 projects; acceptable for typical PHP memory limits.
- **Risks:** Low–medium; mitigable via phased rollout and validation.

**Final recommendation:** **READY FOR IMPLEMENTATION.** Proceed with Phase 1; validate each phase before advancing.

---

## 1. Roadmap Phase Validation

### 1.1 Phase Order and Dependencies

| Phase | Dependencies | Valid |
|-------|--------------|-------|
| 1 — Query Layer | None | ✓ |
| 2 — Dataset Cache | Phase 1 | ✓ |
| 3 — Lightweight Projection | Phase 2 | ✓ |
| 4 — Province Partitioning | Phase 2 | ✓ |
| 5 — Resolver Batch | Phase 2 | ✓ |
| 6 — Widget Refactor | Phases 4, 5 | ✓ |
| 7 — Dashboard Cache | Phase 6 | ✓ |
| 8 — Performance Validation | Phase 7 | ✓ |

### 1.2 Circular Dependencies

**None.** Dependency graph is acyclic: 1 → 2 → {3,4,5} → 6 → 7 → 8.

### 1.3 Phase Interaction Notes

- Phases 4 and 5 can run in parallel (different developers) — both depend only on Phase 2.
- Phase 3 is alignment-only; can be merged into Phase 2 implementation.
- Phase 6 is the largest refactor; requires Phases 4 and 5 complete.

---

## 2. Query Layer Feasibility

### 2.1 ProjectQueryService::forCoordinator()

**Feasibility:** **Yes.**

**Implementation options:**

```php
// Option A: Delegate to ProjectAccessService
return app(ProjectAccessService::class)->getVisibleProjectsQuery($coordinator, $fy);

// Option B: Direct query (equivalent for coordinator)
return Project::query()->inFinancialYear($fy);
```

`ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)` for coordinator returns `Project::query()` with `inFinancialYear($fy)` — unfiltered, global scope. Option A maintains consistency; Option B is simpler but bypasses PAS.

**Recommendation:** Option A — single source of truth for access logic.

### 2.2 Conflict with forProvincial()

**No conflict.** `forProvincial()` uses `getAccessibleUserIds()` and `Project::accessibleByUserIds()`; `forCoordinator()` would use `getVisibleProjectsQuery()` (different code path). Method signatures differ: `forProvincial(User, string)`, `forCoordinator(User, string)`.

### 2.3 Filter Application

- **Province, center, role, parent_id, project_type** — applied via `whereHas('user', ...)` or in-memory filter on `$teamProjects`.
- **In-query vs in-memory:** In-query reduces dataset size before cache; in-memory keeps cache simpler (one key per FY). Roadmap allows both; recommend in-query for large filter selections to reduce payload.

### 2.4 Verdict

**Feasible.** No architectural conflicts; ProjectAccessService already supports coordinator scope.

---

## 3. DatasetCacheService Feasibility

### 3.1 getCoordinatorDataset() Extension

**Feasibility:** **Yes.**

- Add new method; no modification to `getProvincialDataset()`.
- Same `$select` and `$with` arrays — copy from Provincial; no new logic.

### 3.2 Cache Key Isolation

| Role | Key Pattern | Example |
|------|-------------|---------|
| Provincial | `provincial_dataset_{id}_{fy}` | `provincial_dataset_42_2025-26` |
| Coordinator | `coordinator_dataset_{fy}_{filterHash}` | `coordinator_dataset_2025-26_a1b2c3` |

**No collision.** Different prefixes; coordinator uses `fy` + `filterHash`; provincial uses `id` + `fy`.

### 3.3 clearCoordinatorDataset()

- **Challenge:** Coordinator keys include `filterHash`; without Redis cache tags or prefix scan, cannot invalidate all coordinator keys for an FY with a single call.
- **Options:**
  - **A)** Maintain a registry of active filter hashes per FY (complex).
  - **B)** Use cache tags if driver is Redis (`Cache::tags(['coordinator', $fy])->flush()`).
  - **C)** Rely on TTL; document that explicit invalidation may leave stale filter variants until TTL.
  - **D)** Use single key per FY (no filter hash in dataset cache); apply filters in-memory — only one key to clear.

**Recommendation:** Option D for dataset cache — `coordinator_dataset_{fy}`; filters applied in-memory. Simplifies invalidation. Dashboard cache can still use filter hash.

### 3.4 Memory Impact

- Caching all FY projects: 500 projects × ~2–5 KB each (with relations) ≈ 1–2.5 MB.
- 5,000 projects ≈ 10–25 MB. Within typical PHP `memory_limit` (128–256 MB).
- Provincial dataset is smaller (one province); coordinator is larger but still manageable.

### 3.5 General Role

- General in coordinator context: roadmap notes session-dependent scope.
- If General uses coordinator dashboard with province filter: either bypass coordinator dataset cache (like Provincial bypasses for General) or cache per session filter. Document as follow-up.

### 3.6 Verdict

**Feasible.** Recommend dataset cache key without filter hash for simpler invalidation; apply filters in-memory in controller.

---

## 4. Province Partitioning Feasibility

### 4.1 Dataset Cache Compatibility

- **Dataset cache stores:** Flat `$teamProjects` only.
- **Partitioning:** Built in CoordinatorController after `getCoordinatorDataset()` returns.
- **No interference.** Partitioning is post-retrieval; cache layer unchanged.

### 4.2 Memory Overhead

- `groupBy()` returns `Collection` of `Collection`s; same project objects, new wrappers.
- Overhead: ~few KB (10–30 province keys + collection wrappers). **Acceptable.**

### 4.3 Partition Structure and Widgets

- Widgets receive `$projectsByProvince->get($province, collect())` — standard pattern.
- **getProvincialManagementData** groups by provincial (parent_id), not province. It iterates provincials and filters `$allProjects->whereIn('user_id', $teamUserIds)`. This works with shared dataset — filter in memory. No province partition needed for that widget; it uses `$teamProjects` directly.

### 4.4 Serialization for Dashboard Cache

- `$projectsByProvince` is `Collection` of `Collection`.
- Laravel Cache serializes Collections (PHP `serialize`); nested structures are supported.
- **Verified:** Provincial dashboard caches similar structures (e.g. `centerComparison`); no known serialization issues.

### 4.5 Verdict

**Feasible.** Partitioning does not affect dataset cache; structure works with widgets; serialization supported.

---

## 5. Resolver Batch Feasibility

### 5.1 resolveCollection() Existence

- **Present** in `ProjectFinancialResolver` (lines 211–220).
- **Used by:** ExecutorController, ProvincialController (per Phase 5 docs).

### 5.2 Input Requirements

- Projects must have `user`, `reports.accountDetails`, `budgets` eager-loaded.
- `getCoordinatorDataset` (and Provincial) use same `$with` — compatible.

### 5.3 Output Structure

- Returns `[project_id => [opening_balance, amount_requested, ...]]`.
- Widgets use `$resolvedFinancials[$project->project_id]['opening_balance'] ?? 0` — standard pattern.

### 5.4 Large Dataset Performance

- `resolveCollection()` iterates and calls `$resolver->resolve($project)` per project — O(N).
- Each `resolve()` uses strategy (PhaseBased or DirectMapped); may call `loadMissing('budgets')` — ensure eager load to avoid N+1.
- For 5,000 projects: ~5,000 strategy resolutions; estimated 2–5 seconds. Acceptable for coordinator dashboard load.

### 5.5 Partitioned Dataset

- Partitions are derived from `$teamProjects`; resolver receives flat `$teamProjects`.
- **No conflict.** Partitions are built after resolution.

### 5.6 Verdict

**Feasible.** Resolver is compatible; same eager loads as Provincial; performance acceptable for expected scale.

---

## 6. Widget Refactor Feasibility

### 6.1 Target Widgets

| Widget | Internal Queries | Can Remove? | Uses Partitions? |
|--------|------------------|-------------|------------------|
| getSystemPerformanceData | Project, DPReport | Yes | projectsByProvince, reportsByProvince |
| getSystemAnalyticsData | Project, DPReport | Yes | projectsByProvince, approvedProjectsByProvince, reportsByProvince |
| getSystemBudgetOverviewData | Project (approved + pending), DPReport | Yes | approvedProjectsByProvince |
| getProvinceComparisonData | Project, DPReport, User | Yes (Project, DPReport) | projectsByProvince, reportsByProvince |
| getProvincialManagementData | Project, DPReport, User (provincials) | Yes (Project, DPReport) | No — filters by teamUserIds |
| getSystemHealthData | Project, DPReport | Yes | No (system-wide) |

### 6.2 getProvinceComparisonData — User Query

- Uses `User::whereNotNull('province')->get()->groupBy('province')` for provincial/user counts.
- **Recommendation:** Keep this query separate — small, non-project dataset. Not part of project dataset.

### 6.3 getProvincialManagementData

- Iterates `User::where('role','provincial')` with children; for each provincial, filters `$allProjects->whereIn('user_id', $teamUserIds)`.
- **Feasible:** Pass `$teamProjects`; filter in-memory per provincial. `$allReports` similarly filtered.
- Provincial list (`User::where('role','provincial')`) remains a separate query — small, needed for structure.

### 6.4 Resolver Map Lookup

- All widgets that sum budgets use `$resolvedFinancials[$p->project_id]['opening_balance']`.
- Replacing `$resolver->resolve($project)` with map lookup is mechanical.

### 6.5 Signature Changes

- Widgets will gain 3–5 parameters: `$teamProjects`, `$resolvedFinancials`, `$projectsByProvince`, `$reportsByProvince`, `$approvedProjectsByProvince` (as needed).
- Backward compatibility: not required; coordinator dashboard is sole caller.

### 6.6 Verdict

**Feasible.** All six widgets can be refactored; one-off queries (User, provincials) remain; project/report queries removed.

---

## 7. Dashboard Cache Feasibility

### 7.1 Cache Key Isolation

| Dashboard | Key Pattern |
|-----------|-------------|
| Provincial | `provincial_dashboard_{province_id}_{fy}_{filterHash}` |
| Coordinator | `coordinator_dashboard_{fy}_{filterHash}` |

**No collision.** Different prefixes; coordinator has no `province_id` segment.

### 7.2 Cache Payload Size

- Contains: budgetSummaries, performanceMetrics, chartData, provinceComparison, etc.
- Estimated: 100–500 KB for 500 projects (aggregates, not raw projects).
- 5,000 projects: 500 KB–2 MB. **Acceptable** for Laravel Cache (file/Redis).

### 7.3 Real-Time Widgets Excluded

- **getPendingApprovalsData** — recompute every request or 2 min widget cache.
- **getSystemActivityFeedData** — same.
- Provincial pattern: `getRealtimeDashboardData()` merges; coordinator can replicate.

### 7.4 Invalidation Events

- Project approval, revert; report approval, revert; budget sync.
- **Current state:** `clearProvincialDataset` exists but is not wired in app code (per grep). Roadmap assumes wiring.
- **Coordinator:** Add `clearCoordinatorDataset` and wire to same events. New invalidation path; no conflict with Provincial.

### 7.5 Verdict

**Feasible.** Keys are isolated; payload size acceptable; real-time exclusion follows Provincial pattern; invalidation is additive.

---

## 8. Memory Usage Analysis

### 8.1 Estimated Memory (Approximate)

| Component | 100 projects | 500 projects | 2,000 projects | 5,000 projects |
|-----------|--------------|--------------|----------------|----------------|
| Dataset (projects + relations) | ~0.5 MB | ~2 MB | ~8 MB | ~20 MB |
| Resolver map | ~0.05 MB | ~0.2 MB | ~0.8 MB | ~2 MB |
| Partitions (projectsByProvince, etc.) | ~0.1 MB | ~0.3 MB | ~1 MB | ~2.5 MB |
| Reports collection | ~0.2 MB | ~1 MB | ~4 MB | ~10 MB |
| **Total (request)** | **~1 MB** | **~4 MB** | **~15 MB** | **~35 MB** |
| Cached dataset | +0.5 MB | +2 MB | +8 MB | +20 MB |
| Cached dashboard | +0.1 MB | +0.3 MB | +0.8 MB | +1.5 MB |

### 8.2 PHP Memory Limits

- Typical `memory_limit`: 128–256 MB.
- Peak usage for coordinator dashboard: ~35 MB (5,000 projects) + Laravel base (~20–40 MB) ≈ 55–75 MB.
- **Verdict:** Within limits. For 10,000+ projects, consider pagination or streaming for project list; dashboard aggregates may need SQL-based aggregation (Phase 8+).

---

## 9. Implementation Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| Controller complexity increase | Low | Phased refactor; extract helper methods if needed |
| Resolver memory pressure at 5k+ projects | Medium | Monitor; consider chunking or SQL aggregation for scale |
| Dataset cache invalidation (filter hash) | Medium | Use `coordinator_dataset_{fy}` without filter hash; filter in-memory |
| Cache key mismatch in invalidateDashboardCache | Low | Audit and align keys; add unit test for invalidation |
| Widget refactor bugs (wrong totals) | Medium | Phase 8 validation; compare before/after metrics; spot-check |
| getProvincialManagementData logic change | Low | Verify team filtering (`whereIn('user_id', $teamUserIds)`) matches current behavior |
| General role coordinator scope | Low | Document; bypass or scope cache if session filter active |

---

## 10. Final Recommendation

### 10.1 Readiness

**READY FOR IMPLEMENTATION**

The roadmap is feasible. No blocking architectural conflicts. Minor adjustments recommended:

1. **Dataset cache key:** Use `coordinator_dataset_{fy}` (no filter hash); apply filters in controller.
2. **clearCoordinatorDataset:** Clear single key per FY; straightforward.
3. **General role:** Document handling for session-dependent province filter; implement bypass if needed.

### 10.2 Implementation Order

Proceed in order: Phase 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8. Validate each phase before proceeding.

### 10.3 Pre-Implementation Checklist

- [ ] Confirm cache driver (file vs Redis) for coordinator dataset/dashboard cache
- [ ] Confirm invalidation events (where `clearProvincialDataset` would be called) for wiring `clearCoordinatorDataset`
- [ ] Ensure Project model has `isApproved()` method (used in partitioning)
- [ ] Ensure DPReport has `accessibleByUserIds` or equivalent if reports need scoping (coordinator = global, so may not apply)

---

## 11. References

- `Coordinator_Dashboard_Implementation_Roadmap.md`
- `Coordinator_Dashboard_Performance_Architecture_Audit.md`
- `Coordinator_Dashboard_Architecture_Audit.md`
- `app/Services/ProjectQueryService.php`
- `app/Services/DatasetCacheService.php`
- `app/Services/ProjectAccessService.php`
- `app/Domain/Budget/ProjectFinancialResolver.php`
- `Documentations/V2/Budgets/Dashboards/Provincial/Phase6_DashboardCache_Implementation.md`
