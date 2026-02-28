# Coordinator Hierarchy Access Audit

**Rule:** Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.

---

**Date:** 2026-02-23  
**Audit Type:** Hierarchical access control for COORDINATOR role  
**Scope:** Project view, list, download, activity history — hierarchy propagation verification  
**Assumption:** Phases A–D (Project View Access) already implemented.

---

## 1. Executive Summary

**Conclusion: FAIL**

The coordinator role **does not** implement the expected hierarchical access model. Current behavior treats coordinator as "see all projects" (equivalent to admin), relying on `province_id = null` in `passesProvinceCheck` to allow unrestricted access. The expected chain—**coordinator → provincial → executor/applicant → project**—is **not enforced** in project list, show, or access services. Only `budgetOverview` applies a province-based filter (approximation, not strict parent_id traversal).

| Component | Expected | Actual |
|-----------|----------|--------|
| projectList | Projects where owner/in_charge ∈ executors under coordinator's provincials | **All projects** (no hierarchy filter) |
| showProject | Same hierarchy check | Delegates to `ProjectPermissionHelper::canView` → **unconditional true** for coordinator |
| ProjectAccessService | `getAccessibleUserIds` for coordinator; hierarchy traversal | **Coordinator not supported**; `canViewProject` returns true; `getVisibleProjectsQuery` returns unfiltered |
| Activity history | Hierarchy-scoped | **Unfiltered** for coordinator |

---

## 2. Current Behavior Analysis

### 2.1 ProjectAccessService

| Method | Coordinator Handling | Location |
|--------|----------------------|----------|
| `getAccessibleUserIds(User $provincial)` | **Not applicable** — method signature and logic target provincial/general only. Coordinator is never passed. | `app/Services/ProjectAccessService.php` L24–58 |
| `canViewProject(Project $project, User $user)` | Coordinator grouped with admin/general: `return true` after `passesProvinceCheck`. **No hierarchy check.** | L68–69 |
| `getVisibleProjectsQuery(User $user)` | Coordinator grouped with admin/general: returns unfiltered `Project::query()`. **No scope.** | L93–95 |

**Drift:** ProjectAccessService was built for provincial scope (owner/in-charge parity). Coordinator was left as "full access" with no hierarchy.

### 2.2 CoordinatorController

| Method | Logic | Line(s) |
|--------|-------|---------|
| `projectList()` | Base query: `Project::with([...])` — **no hierarchy filter**. Optional UI filters (province, provincial_id, user_id) are user-driven, not enforced. | 469–471 |
| `showProject()` | Fetches project; no pre-check. Comment: "Coordinator can view all projects." Delegates to `ProjectController::show`. | 661–672 |
| `budgetOverview()` | Uses hierarchy: provincials with `parent_id = coordinator.id`, then `project.user.province IN provincial provinces`. Province-based approximation, not parent_id chain. | 1278–1287 |

**Inconsistency:** `budgetOverview` filters by coordinator→provincial→province; `projectList` and `showProject` do not.

### 2.3 ProjectPermissionHelper

| Method | Coordinator Logic | Location |
|--------|-------------------|----------|
| `passesProvinceCheck()` | If `user->province_id === null` → `true`. Coordinator typically has `province_id = null` → passes. | L20–26 |
| `canView()` | If role in `['admin','coordinator','provincial','general']` → `true` (after province check). **No hierarchy.** | L90–96 |

**ProjectController::show** (L828) uses `ProjectPermissionHelper::canView` — coordinator always passes.

### 2.4 ActivityHistoryHelper

| Method | Coordinator Logic | Location |
|--------|-------------------|----------|
| `canView()` | Returns `true` for admin/coordinator before any project/report check. | L21–24 |
| `canViewProjectActivity()` | Delegates to `ProjectAccessService::canViewProject` → coordinator gets `true`. | L49 |
| `getQueryForUser()` | Coordinator: returns unfiltered `ActivityHistory::query()` — **all activities**. | L80–83 |

---

## 3. Hierarchy Traversal Diagram

### Expected (Not Implemented)

```
Coordinator (id=C)
    │
    ├── Provincial A (parent_id=C)
    │       ├── Executor 1 (parent_id=A) → Project P1 (user_id=1 or in_charge=1)
    │       └── Executor 2 (parent_id=A) → Project P2
    │
    └── Provincial B (parent_id=C)
            └── Executor 3 (parent_id=B) → Project P3

Coordinator C should see ONLY: P1, P2, P3
```

### Actual

```
Coordinator → no filter
    → projectList: ALL projects
    → showProject: ALL projects (via canView)
    → ActivityHistory: ALL activities
```

---

## 4. Coordinator → Provincial → Executor Flow Analysis

### Database Relationships

| Relation | Table | Column | Description |
|----------|-------|--------|-------------|
| Provincial → Coordinator | users | parent_id | Provincial has `parent_id = coordinator.id` |
| Executor → Provincial | users | parent_id | Executor has `parent_id = provincial.id` |
| Project → Executor | projects | user_id, in_charge | Project owner and in-charge are executor IDs |

**Strict traversal:**
1. Provincial IDs: `User::where('parent_id', $coordinator->id)->where('role','provincial')->pluck('id')`
2. Executor IDs: `User::whereIn('parent_id', $provincialIds)->whereIn('role',['executor','applicant'])->pluck('id')`
3. Projects: `Project::accessibleByUserIds($executorIds)` (owner or in_charge in set)

### Current Implementation

- **ProjectAccessService::getAccessibleUserIds**: Accepts provincial only. Logic: direct executor children of provincial. **Does not support coordinator.**
- **CoordinatorController::projectList**: No call to `getAccessibleUserIds` or equivalent. Base query has no scope.
- **CoordinatorController::budgetOverview**: Uses `province` (string) of provincials — province-based, not parent_id chain. Can over-include (same province, different provincial) or miss (executor in different province under same provincial, if such cases exist).

---

## 5. Issues Identified

| # | Issue | Severity | Location |
|---|-------|----------|----------|
| 1 | Coordinator sees all projects — hierarchy never enforced | **Critical** | CoordinatorController::projectList L469–471; ProjectAccessService::canViewProject L68–69; getVisibleProjectsQuery L93–95 |
| 2 | `getAccessibleUserIds` does not support coordinator role | **High** | ProjectAccessService L24–58 |
| 3 | Coordinator bypasses hierarchy in canViewProject and getVisibleProjectsQuery | **High** | ProjectAccessService L64–106 |
| 4 | ProjectPermissionHelper::canView gives coordinator blanket true | **High** | ProjectPermissionHelper L90–96 |
| 5 | ActivityHistoryHelper returns all activities for coordinator | **High** | ActivityHistoryHelper L80–83 |
| 6 | budgetOverview uses province names, not parent_id chain — inconsistent and potentially wrong | **Medium** | CoordinatorController L1278–1286 |
| 7 | CoordinatorController does not use ProjectAccessService | **Medium** | CoordinatorController (no ProjectAccessService usage) |
| 8 | Session province filter: N/A for coordinator — getAccessibleUserIds is provincial-only | **Low** | ProjectAccessService L26–28 |

---

## 6. Security Risks

| Risk | Severity | Description |
|------|----------|-------------|
| Overexposure | **Critical** | Coordinator can view projects from executors not under their provincial hierarchy. Data leak across organizational boundaries. |
| No isolation | **Critical** | Multiple coordinators share same "see all" behavior; no per-coordinator scoping. |
| Inconsistent policy | **Medium** | budgetOverview restricts by province; projectList/show do not. Policy is undefined. |
| Drift | **Medium** | Provincial uses ProjectAccessService with hierarchy; coordinator does not. Future changes may widen gap. |

---

## 7. Performance Risks

| Risk | Location | Note |
|------|----------|------|
| Unfiltered project list | CoordinatorController::projectList | Loads all projects; no scope. Pagination (100/page) limits rows but query is unbounded by hierarchy. |
| N+1 on hierarchy | N/A | Hierarchy not traversed for coordinator. When implemented, ensure batched queries for provincials → executors. |
| Cache | ProjectAccessService | `getAccessibleUserIds` cached per provincial. If coordinator support added, cache key must include coordinator id. |
| Index usage | projects | `user_id`, `in_charge`, `province_id` indexed. For coordinator scope, `whereIn('user_id', $executorIds)` will use indexes if IDs set is reasonable. |

---

## 8. Required Fixes (Minimal Patch Approach)

1. **ProjectAccessService::getAccessibleUserIdsForCoordinator(User $coordinator)**  
   - New method (or extend existing with coordinator handling).  
   - Return executor IDs: provincials with `parent_id = coordinator.id` → executors with `parent_id IN provincial_ids`.  
   - Cache per request (similar to provincial).

2. **ProjectAccessService::canViewProject**  
   - For coordinator: do **not** return true unconditionally.  
   - Check: project `user_id` or `in_charge` ∈ `getAccessibleUserIdsForCoordinator($user)`.

3. **ProjectAccessService::getVisibleProjectsQuery**  
   - For coordinator: use `Project::accessibleByUserIds(getAccessibleUserIdsForCoordinator($user))` instead of unfiltered query.

4. **CoordinatorController::projectList**  
   - Add default scope: projects where owner or in_charge ∈ `getAccessibleUserIdsForCoordinator($coordinator)`.  
   - Keep existing optional filters (province, provincial_id, etc.) as refinements.

5. **CoordinatorController::showProject**  
   - Add pre-check: if project owner/in_charge ∉ accessible IDs → 403.  
   - Or delegate to `ProjectAccessService::canViewProject` and rely on it.

6. **ActivityHistoryHelper::getQueryForUser**  
   - For coordinator: filter activities to projects in coordinator scope (use `getVisibleProjectsQuery` or equivalent).

7. **ProjectPermissionHelper**  
   - `canView` for coordinator: either delegate to `ProjectAccessService::canViewProject` or replicate hierarchy check. Current blanket true is incorrect for hierarchical model.

---

## 9. Refactor Recommendation

- Introduce `ProjectAccessService::getAccessibleUserIdsForUser(User $user)` that handles coordinator, provincial, and general uniformly.
- Have `CoordinatorController` call `ProjectAccessService` for list and show scope instead of ad-hoc queries.
- Align `budgetOverview` with strict parent_id traversal (executor IDs under coordinator's provincials) instead of province names.
- Consider a single `canViewProject` entry point (e.g. in ProjectAccessService) used by ProjectController, ActivityHistoryHelper, ExportController to avoid drift.

---

## 10. Test Cases to Add

| # | Scenario | Expected |
|---|----------|----------|
| 1 | Coordinator A views project where executor's provincial has parent_id = A | 200 |
| 2 | Coordinator A views project where executor's provincial has parent_id = B (other coordinator) | 403 |
| 3 | Coordinator project list returns only projects under their provincial children | Pass |
| 4 | Coordinator project list excludes projects from executors under other coordinators' provincials | Pass |
| 5 | Activity history for coordinator filtered to projects in scope | Pass |
| 6 | Coordinator with province_id set: verify province check does not override hierarchy | Document/verify |
| 7 | budgetOverview and projectList return same project set for coordinator | Pass (after refactor) |

---

## 11. Regression Checklist

After implementing fixes:

- [ ] Provincial project list unchanged (still uses ProjectAccessService)
- [ ] Provincial showProject unchanged
- [ ] Executor/applicant access unchanged
- [ ] General user access unchanged
- [ ] Admin access unchanged
- [ ] Coordinator sees only projects under their provincial hierarchy
- [ ] Coordinator show returns 403 for out-of-scope project
- [ ] Activity history scoped for coordinator
- [ ] ExportController coordinator download still works (and respects hierarchy if applicable)
- [ ] No N+1 on hierarchy traversal

---

## 12. Conclusion

| Verdict | **FAIL** |
|---------|----------|
| Hierarchy propagation | Not implemented for coordinator |
| Project list scope | None (shows all) |
| Project show scope | None (canView returns true) |
| ProjectAccessService | Coordinator treated as admin; no hierarchy |
| Activity history | Unfiltered for coordinator |

The coordinator role does not enforce the intended hierarchy. A coordinator can currently view all projects, regardless of provincial structure. Implementing the expected behavior requires changes in ProjectAccessService, CoordinatorController, and ActivityHistoryHelper as outlined in Section 8.

---

*Audit completed 2026-02-23.*
