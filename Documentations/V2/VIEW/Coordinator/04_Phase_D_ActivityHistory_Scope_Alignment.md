# Phase D — Activity History Scope Alignment

**Rule:** Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.

---

**Phase:** D  
**Objective:** Ensure coordinator activity history is globally readable but consistent. Route through ProjectAccessService. Remove unfiltered direct return. Avoid N+1.

---

## 1. Objective

ActivityHistoryHelper (or equivalent) currently may return unfiltered activity for coordinator. Align with ProjectAccessService so that activity scope matches project visibility: coordinator sees activity for all projects they can view (all projects). Avoid N+1 queries when filtering.

---

## 2. Scope (Exact Files)

| File | Change |
|------|--------|
| `app/Helpers/ActivityHistoryHelper.php` | Replace unfiltered direct return for coordinator with flow that uses ProjectAccessService (e.g. getVisibleProjectsQuery to filter activity by visible project IDs); ensure no N+1 |
| Activity/Report controllers or services that consume activity history | Update to use the aligned helper if needed |

---

## 3. What Will NOT Be Touched

- ProjectAccessService
- CoordinatorController
- ProjectPermissionHelper
- ExportController
- Activity/report models (unless required for query optimization)
- Routes

---

## 4. Pre-Implementation Checklist

- [ ] Phase A, B, C complete
- [ ] Identify all entry points for activity history (project activity, report activity)
- [ ] Confirm ActivityHistoryHelper::canViewProjectActivity, canViewReportActivity, and any getActivity methods
- [ ] Confirm whether coordinator path currently returns unfiltered query

---

## 5. Failing Tests to Write First

```php
// tests/Unit/Helpers/ActivityHistoryHelperCoordinatorTest.php

public function test_coordinator_activity_scope_uses_project_access_service(): void
public function test_coordinator_sees_activity_for_all_projects(): void
public function test_no_unfiltered_direct_return(): void
public function test_activity_query_no_n_plus_one(): void
```

---

## 6. Step-by-Step Implementation Plan

### Step D.1 — Audit ActivityHistoryHelper

**File:** `app/Helpers/ActivityHistoryHelper.php`

**Actions:**
- Locate methods: `canViewProjectActivity`, `canViewReportActivity`, and any `getActivity*` or query builders.
- Identify where coordinator is handled (likely in canViewProjectActivity / canViewReportActivity via ProjectAccessService already).
- Identify any method that returns unfiltered activity for coordinator (e.g. direct `Activity::query()` with no project filter).

### Step D.2 — Route Through ProjectAccessService

**Actions:**
- For any activity query that is coordinator-scoped: Base the query on project visibility.
- Use `ProjectAccessService::getVisibleProjectsQuery($coordinator)->select('id')` to get visible project IDs.
- Filter activity by `project_id IN (visible_ids)` or equivalent.
- For coordinator, visible_ids = all project IDs; query remains correct.

### Step D.3 — Remove Unfiltered Direct Return

**Actions:**
- If any branch returns `Activity::query()` or similar without project filter for coordinator, remove it.
- Replace with filtered query using visible project IDs from ProjectAccessService.

### Step D.4 — Avoid N+1

**Actions:**
- Use subquery or `whereIn` with single query for visible project IDs.
- Do not loop over projects to fetch activity.
- Use eager loading for related models if needed (e.g. project, user).

### Step D.5 — Report Activity Consistency

**Actions:**
- Ensure report activity (DPReport, etc.) is similarly scoped: coordinator sees report activity for all projects they can view.
- Filter by project_id where reports are linked to projects.

---

## 7. Security Impact Analysis

- **Neutral or positive:** Coordinator already intended to see all activity. Explicit filtering aligns with project visibility.
- **No new exposure:** If current code returns more than intended, this phase tightens scope; if less, it expands. Target: match project visibility.

---

## 8. Performance Impact Analysis

- **Subquery cost:** One extra subquery for visible project IDs. For coordinator, subquery is `SELECT id FROM projects` — lightweight.
- **Index:** Ensure `projects.id` and activity `project_id` are indexed.
- **N+1:** Must be avoided; single query for activity with whereIn.

---

## 9. Rollback Strategy

1. Revert ActivityHistoryHelper changes.
2. Restore any unfiltered return if it was intentional (document why).
3. Re-run tests.

---

## 10. Deployment Checklist

- [ ] Phase D tests pass
- [ ] Coordinator sees activity for all projects
- [ ] No N+1 in activity listing
- [ ] Report activity consistent
- [ ] Provincial activity scope unchanged

---

## 11. Regression Checklist

- [ ] Provincial activity scope unchanged
- [ ] Executor activity scope unchanged
- [ ] Admin activity scope unchanged
- [ ] Coordinator activity scope = all projects
- [ ] Performance acceptable (no N+1)

---

## 12. Sign-Off Criteria

- Activity history filtered by ProjectAccessService visible projects
- No unfiltered direct return for coordinator
- N+1 avoided
- Phase D completion MD updated
