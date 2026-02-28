# Phase B — Controller Alignment

**Rule:** Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.

---

**Phase:** B  
**Objective:** Ensure CoordinatorController relies fully on ProjectAccessService. Fix budgetOverview to show all projects for coordinator.

---

## 1. Objective

Align CoordinatorController so that `projectList` uses `ProjectAccessService::getVisibleProjectsQuery`, `showProject` uses `ProjectAccessService::canViewProject` (or delegates to flow that does), and `budgetOverview` no longer restricts coordinator by provincial hierarchy. Coordinator must see all projects everywhere.

---

## 2. Scope (Exact Files)

| File | Change |
|------|--------|
| `app/Http/Controllers/CoordinatorController.php` | Inject ProjectAccessService; projectList uses getVisibleProjectsQuery as base; showProject adds canViewProject pre-check or relies on ProjectController; budgetOverview removes province/parent_id filter for coordinator, uses getVisibleProjectsQuery |

---

## 3. What Will NOT Be Touched

- ProjectAccessService (Phase A complete)
- ProjectPermissionHelper
- ActivityHistoryHelper
- ProvincialController
- Routes
- Blade templates

---

## 4. Pre-Implementation Checklist

- [ ] Phase A complete
- [ ] ProjectAccessService explicitly documents coordinator = global
- [ ] CoordinatorController constructor or methods identified for ProjectAccessService injection

---

## 5. Failing Tests to Write First

```php
// tests/Feature/Coordinator/CoordinatorControllerAlignmentTest.php

public function test_project_list_uses_project_access_service(): void
public function test_project_list_returns_all_projects_for_coordinator(): void
public function test_show_project_delegates_to_project_controller_after_access_check(): void
public function test_budget_overview_shows_all_projects_for_coordinator(): void
public function test_budget_overview_no_parent_id_filter_for_coordinator(): void
```

---

## 6. Step-by-Step Implementation Plan

### Step B.1 — Inject ProjectAccessService

**File:** `CoordinatorController.php`

**Actions:**
- Add `ProjectAccessService` to constructor (or resolve via `app()` if constructor injection not used).
- Ensure service is available in methods that need it.

### Step B.2 — projectList Uses getVisibleProjectsQuery

**Location:** `projectList()` (approx. L465–471)

**Current:** Base query is `Project::with([...])` with no scope.

**Actions:**
- Replace base query with: `ProjectAccessService::getVisibleProjectsQuery($coordinator)` (or equivalent) as starting point.
- Chain existing filters (search, province, provincial_id, user_id, center, project_type, status, etc.) on top.
- Ensure coordinator receives unfiltered base (all projects); filters remain as UI refinements.

### Step B.3 — showProject Pre-Check

**Location:** `showProject()` (approx. L660–672)

**Current:** No pre-check; delegates to `ProjectController::show`.

**Actions:**
- Option A: Add pre-check using `ProjectAccessService::canViewProject($project, $coordinator)` before delegate. If false, abort 403.
- Option B: Rely on ProjectController::show which uses ProjectPermissionHelper::canView (Phase C will align that to ProjectAccessService). No change if Phase C ensures consistency.
- Recommendation: Add explicit `canViewProject` check for clarity and single source of truth.

### Step B.4 — budgetOverview Alignment

**Location:** `budgetOverview()` (approx. L1274–1293)

**Current:** Gets provinces from `User::where('parent_id', $coordinator->id)->where('role','provincial')->pluck('province')`, then filters projects by `project.user.province IN provinces`. This restricts coordinator to provincial children's provinces.

**Actions:**
- Replace with: Use `ProjectAccessService::getVisibleProjectsQuery($coordinator)` as base.
- For coordinator, this returns all projects. No parent_id or province filter.
- Remove the `parent_id` / provincial-province logic for coordinator.
- Preserve UI grouping by province if needed (for display only), but do not filter the dataset.

### Step B.5 — Remove Implicit Assumptions

**Actions:**
- Update or remove comment "Coordinator can view all projects" if redundant after changes.
- Ensure no other methods in CoordinatorController apply hierarchy filters to coordinator.

---

## 7. Security Impact Analysis

- **Positive:** Coordinator explicitly uses ProjectAccessService; single source of truth.
- **No new risk:** Coordinator already intended to see all; budgetOverview fix expands scope to match.
- **Boundary:** Coordinator must not gain edit access; workflow actions (approve, revert, reject) remain separate.

---

## 8. Performance Impact Analysis

- **projectList:** getVisibleProjectsQuery for coordinator returns `Project::query()` — no extra query. Same as current.
- **budgetOverview:** Removing filter may increase result set; ensure pagination or limits if needed.
- **N+1:** Ensure eager loading preserved where applicable.

---

## 9. Rollback Strategy

1. Revert CoordinatorController changes.
2. Restore original projectList base query and budgetOverview filter logic.
3. Re-run tests.

---

## 10. Deployment Checklist

- [ ] Phase B tests pass
- [ ] Coordinator project list shows all projects
- [ ] Coordinator budget overview shows all projects
- [ ] No regression for provincial, executor, admin
- [ ] Staging verification

---

## 11. Regression Checklist

- [ ] Provincial project list unchanged
- [ ] Provincial showProject unchanged
- [ ] Executor project list unchanged
- [ ] Admin project access unchanged
- [ ] Coordinator can view any project by direct URL
- [ ] Coordinator budget overview includes all provinces

---

## 12. Sign-Off Criteria

- projectList uses ProjectAccessService
- showProject uses canViewProject (or equivalent)
- budgetOverview shows all projects for coordinator (no parent_id filter)
- Phase B completion MD updated
