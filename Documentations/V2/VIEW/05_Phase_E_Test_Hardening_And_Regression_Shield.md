# Phase E — Test Hardening and Regression Shield

**Rule:** Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.

---

## 1. Objective

Establish comprehensive test coverage for Project View, Attachment, Download, and Activity History access for Coordinator and Provincial roles. Add parity tests, cross-province tests, null-safety tests, download/view alignment tests, and activity history role coverage tests. Create a regression shield that catches future access-control regressions.

---

## 2. Scope (Exact Files Involved)

| File | Change |
|------|--------|
| `tests/Feature/Projects/ProjectAccessParityTest.php` | **New** — owner vs in_charge parity |
| `tests/Feature/Projects/CrossProvinceAccessTest.php` | **New** — province isolation |
| `tests/Feature/Projects/NullSafetyAccessTest.php` | **New** — null user, null in_charge |
| `tests/Feature/Projects/DownloadViewAlignmentTest.php` | **New** — view implies download |
| `tests/Feature/Projects/ActivityHistoryRoleCoverageTest.php` | **New** — all roles |
| `tests/Feature/Projects/ProjectAccessStabilizationTest.php` | Extend if needed |
| `phpunit.xml` or `tests/TestCase.php` | Ensure project test suite runs all |
| `Documentations/V2/VIEW/Regression_Test_Matrix.md` | **New** — matrix of scenarios |

---

## 3. What Will NOT Be Touched

- Production application code (unless fixing a discovered bug)
- Database schema
- Routes
- Existing passing tests (except to extend coverage)

---

## 4. Pre-Implementation Checklist

- [ ] Phases A, B, C, D complete
- [ ] All prior phase tests passing
- [ ] Test database seeded with required users/projects
- [ ] Decision: use RefreshDatabase or specific seeders for each test class

---

## 5. Failing Tests to Write First

Create the following test classes and methods. Tests should fail only if behavior is wrong; if implementation is correct, they pass immediately.

### ProjectAccessParityTest.php
```php
public function test_provincial_sees_project_where_in_charge_in_team_owner_not(): void
public function test_provincial_project_list_includes_in_charge_projects(): void
public function test_executor_sees_own_and_in_charge_projects(): void
```

### CrossProvinceAccessTest.php
```php
public function test_provincial_cannot_view_project_in_other_province(): void
public function test_coordinator_can_view_project_any_province(): void
public function test_province_check_blocks_cross_province_attachment_download(): void
```

### NullSafetyAccessTest.php
```php
public function test_export_controller_handles_null_project_user(): void
public function test_activity_history_handles_missing_project(): void
```

### DownloadViewAlignmentTest.php
```php
public function test_if_user_can_view_then_can_download(): void
public function test_provincial_download_all_statuses_in_scope(): void
public function test_coordinator_download_all_statuses(): void
```

### ActivityHistoryRoleCoverageTest.php
```php
public function test_provincial_can_access_project_activity_when_in_scope(): void
public function test_coordinator_can_access_project_activity(): void
public function test_general_can_access_project_activity(): void
public function test_executor_can_access_own_project_activity(): void
```

---

## 6. Step-by-Step Implementation Plan

### Step E.1 — Create ProjectAccessParityTest

- Seed: provincial user, executor A (owner), executor B (in_charge), project with owner=A, in_charge=B, provincial as B's parent.
- Assert: provincial can view project, project appears in list.
- Seed: project with owner=external, in_charge=B.
- Assert: provincial can view (in_charge in team).

---

### Step E.2 — Create CrossProvinceAccessTest

- Seed: provincial (province 1), project (province 2), executor in province 2.
- Assert: provincial gets 403 on show.
- Assert: coordinator (no province) can view.

---

### Step E.3 — Create NullSafetyAccessTest

- Mock or seed project with user_id pointing to deleted user (or use factory with nullable user).
- Call ExportController download; assert no exception (403 or 404 acceptable).
- Call ActivityHistoryHelper with non-existent project_id; assert false, no exception.

---

### Step E.4 — Create DownloadViewAlignmentTest

- For each role (coordinator, provincial): create project in each status (draft, submitted, approved, etc.).
- Assert: if canView, then downloadPdf returns 200.
- Use ProjectPermissionHelper::canView to determine expected view access; then assert download matches.

---

### Step E.5 — Create ActivityHistoryRoleCoverageTest

- For provincial, coordinator, general, executor: hit `projects/{id}/activity-history`.
- Assert 200 when in scope, 403 when not.
- Seed data for each scenario.

---

### Step E.6 — Create Regression Test Matrix

**File:** `Documentations/V2/VIEW/Regression_Test_Matrix.md`

| Scenario | Role | Expected | Test Method |
|----------|------|----------|-------------|
| View own project | Executor | 200 | ... |
| View in-charge project | Executor | 200 | ... |
| View project (in_charge in team) | Provincial | 200 | ... |
| View project (other province) | Provincial | 403 | ... |
| View any project | Coordinator | 200 | ... |
| Download after view | All | 200 | ... |
| Activity history | Provincial | 200 when in scope | ... |
| ... | ... | ... | ... |

---

## 7. Code Refactor Notes

- Use Laravel factories for User, Project where possible.
- Use `actingAs($user)` for auth.
- Group tests by role or scenario for clarity.
- Ensure tests are isolated (no shared state).

---

## 8. Performance Impact Analysis

- Test suite will run longer; acceptable for CI.
- No production performance impact.

---

## 9. Security Impact Analysis

- Tests validate security boundaries; no direct security impact.
- Tests should not expose secrets or use production data.

---

## 10. Rollback Strategy

- No production changes; rollback = remove or skip new tests if they block CI.
- Prefer fixing failing tests over removing them.

---

## 11. Deployment Checklist

- [ ] All new tests pass
- [ ] Full suite passes in CI
- [ ] Regression Test Matrix documented
- [ ] CI configured to run project access tests on every PR

---

## 12. Post-Deployment Validation Steps

1. Run full test suite: `php artisan test --filter=Project`
2. Verify no flaky tests (run 3x)
3. Add test run to deployment pipeline

---

## 13. Regression Test List

- [ ] ProjectAccessParityTest
- [ ] CrossProvinceAccessTest
- [ ] NullSafetyAccessTest
- [ ] DownloadViewAlignmentTest
- [ ] ActivityHistoryRoleCoverageTest
- [ ] ProjectAccessStabilizationTest (Phase A)
- [ ] DownloadConsistencyTest (Phase B)
- [ ] ProjectAccessServiceTest (Phase C)
- [ ] PerformanceOptimizationTest (Phase D)

---

## 14. Sign-Off Criteria

- All new tests pass
- Regression Test Matrix complete
- No production code change (or only minimal bug fixes)
- Phase E completion MD updated

---

## Cursor Execution Rule

When implementing this phase, update this MD file with:
- Actual code changes
- File diffs summary
- Test results
- Any deviations from plan
- Date of implementation
- Engineer name
