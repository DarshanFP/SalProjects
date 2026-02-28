# Phase F — Testing & Regression Shield

**Rule:** Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.

---

**Phase:** F  
**Objective:** Prevent future drift. Establish automated tests that codify coordinator behavior and cross-role consistency.

---

## 1. Objective

Add comprehensive tests so that coordinator oversight model is enforced by CI. Any future change that breaks coordinator global read-only access, or introduces hierarchy, or blocks coordinator from expected routes will fail tests. Cross-role regression tests ensure provincial, executor, admin behavior remains correct.

---

## 2. Scope (Exact Files)

| File | Change |
|------|--------|
| `tests/Feature/Coordinator/CoordinatorOversightTest.php` | New: coordinator sees all projects, activity, downloads |
| `tests/Feature/Coordinator/CoordinatorNoEditTest.php` | New: coordinator cannot edit restricted workflow steps |
| `tests/Feature/Coordinator/CoordinatorActivityHistoryTest.php` | New: coordinator activity consistent with project visibility |
| `tests/Feature/Coordinator/CoordinatorAdminBoundaryTest.php` | New: coordinator cannot access admin-only routes |
| `tests/Feature/CrossRoleRegressionTest.php` | New: cross-role regression (provincial, executor, admin, coordinator) |
| `phpunit.xml` or `pest.php` | No change unless new test suite/directory needs registration |

---

## 3. What Will NOT Be Touched

- Application code (this phase is test-only)
- Controllers, services, helpers (except as needed for test setup)
- Routes (unless adding test-only routes)
- Database migrations

---

## 4. Pre-Implementation Checklist

- [ ] Phases A–E complete
- [ ] Test database seeded or factories available for Project, User, DPReport
- [ ] Role fixtures: coordinator, provincial, executor, admin, general
- [ ] Pest or PHPUnit configured

---

## 5. Failing Tests to Write First

Write these tests; they should pass after Phases A–E. If any fail, fix implementation before Phase F sign-off.

```php
// CoordinatorOversightTest.php
public function test_coordinator_sees_all_projects_in_list(): void
public function test_coordinator_can_view_project_from_any_province(): void
public function test_coordinator_can_download_project_export(): void
public function test_coordinator_budget_overview_includes_all_provinces(): void

// CoordinatorNoEditTest.php
public function test_coordinator_cannot_approve_project(): void
public function test_coordinator_cannot_revert_project(): void
public function test_coordinator_cannot_submit_project(): void
public function test_coordinator_can_view_but_not_edit_workflow(): void

// CoordinatorActivityHistoryTest.php
public function test_coordinator_sees_activity_for_all_projects(): void
public function test_coordinator_activity_scope_matches_project_visibility(): void

// CoordinatorAdminBoundaryTest.php
public function test_coordinator_cannot_access_admin_dashboard(): void
public function test_coordinator_cannot_access_user_management(): void
public function test_coordinator_cannot_access_system_settings(): void

// CrossRoleRegressionTest.php
public function test_provincial_sees_only_own_projects(): void
public function test_executor_sees_only_assigned_projects(): void
public function test_admin_sees_all_projects(): void
public function test_coordinator_sees_all_projects(): void
public function test_general_sees_only_accessible_projects(): void
```

---

## 6. Step-by-Step Implementation Plan

### Step F.1 — Coordinator Oversight Tests

**File:** `tests/Feature/Coordinator/CoordinatorOversightTest.php`

**Actions:**
- Create coordinator user (role=coordinator).
- Create projects in multiple provinces.
- Assert coordinator project list contains all.
- Assert coordinator can access show URL for any project.
- Assert coordinator can trigger download for any project.
- Assert budget overview returns projects from all provinces.

### Step F.2 — Coordinator No-Edit Tests

**File:** `tests/Feature/Coordinator/CoordinatorNoEditTest.php`

**Actions:**
- Create coordinator user.
- Attempt POST/PUT to approve, revert, submit endpoints.
- Assert 403 or redirect to unauthorized.
- Assert coordinator can GET show page (view allowed).
- Assert coordinator cannot POST to edit/update project.

### Step F.3 — Activity History Tests

**File:** `tests/Feature/Coordinator/CoordinatorActivityHistoryTest.php`

**Actions:**
- Create coordinator and projects with activity.
- Assert coordinator activity list includes activity from all projects.
- Assert filtering by project returns correct activity.
- Assert no N+1 (optional: query count assertion).

### Step F.4 — Admin Boundary Tests

**File:** `tests/Feature/Coordinator/CoordinatorAdminBoundaryTest.php`

**Actions:**
- Create coordinator user.
- Attempt access to admin-only routes (e.g. /admin/dashboard, /admin/users).
- Assert 403 or redirect.
- Assert coordinator cannot escalate to admin.

### Step F.5 — Cross-Role Regression Tests

**File:** `tests/Feature/CrossRoleRegressionTest.php`

**Actions:**
- For each role: provincial, executor, admin, coordinator, general.
- Create appropriate users and projects.
- Assert expected project list scope for each.
- Assert expected access to show, download.
- Ensures no regression when coordinator logic changes.

### Step F.6 — CI Integration

**Actions:**
- Ensure new test files run in default PHPUnit/Pest configuration.
- Add to CI pipeline if not already covering `tests/Feature/Coordinator/` and `tests/Feature/CrossRoleRegressionTest.php`.
- Document test run command in Phase F completion MD.

---

## 7. Security Impact Analysis

- **Positive:** Tests codify security boundaries. Future changes that break boundaries will fail.
- **No application change:** This phase adds tests only; no new attack surface.

---

## 8. Performance Impact Analysis

- **Test suite:** Additional tests add ~30–60 seconds (estimate). Acceptable.
- **Application:** No impact.

---

## 9. Rollback Strategy

1. Remove or skip new test files if they block deployment.
2. No application rollback needed.

---

## 10. Deployment Checklist

- [ ] All Phase F tests pass
- [ ] CI runs new tests
- [ ] Documentation updated with test commands
- [ ] Phase F completion MD created

---

## 11. Regression Checklist

- [ ] Existing test suite still passes
- [ ] New coordinator tests pass
- [ ] Cross-role tests pass
- [ ] No flaky tests

---

## 12. Sign-Off Criteria

- Coordinator oversight tests pass
- Coordinator no-edit tests pass
- Activity history tests pass
- Admin boundary tests pass
- Cross-role regression tests pass
- Phase F completion MD updated
- CI includes new tests
