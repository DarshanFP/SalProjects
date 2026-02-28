# Phase C — Centralized Access Service

**Rule:** Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.

---

## 1. Objective

Introduce a single source of truth for project access logic: `ProjectAccessService`. Refactor ProvincialController, ExportController, and ActivityHistoryHelper to use it. Remove duplicated `getAccessibleUserIds`, parent_id, and owner/in_charge logic. Add model scopes if beneficial.

---

## 2. Scope (Exact Files Involved)

| File | Change |
|------|--------|
| `app/Services/ProjectAccessService.php` | **New** — centralized access logic |
| `app/Http/Controllers/ProvincialController.php` | Use ProjectAccessService; remove or delegate getAccessibleUserIds |
| `app/Http/Controllers/Projects/ExportController.php` | Use ProjectAccessService for download (if hierarchy check needed) |
| `app/Helpers/ActivityHistoryHelper.php` | Use ProjectAccessService for canViewProjectActivity, canViewReportActivity |
| `app/Models/OldProjects/Project.php` | Optional: add `scopeForProvincial` |
| `tests/Feature/Projects/ProjectAccessServiceTest.php` | **New** — unit/feature tests |
| `tests/Feature/Projects/ProjectAccessStabilizationTest.php` | Ensure still passing |
| `tests/Feature/Projects/DownloadConsistencyTest.php` | Ensure still passing |

---

## 3. What Will NOT Be Touched

- `ProjectPermissionHelper` — may be kept for backward compatibility; ProjectAccessService can delegate to it
- Route definitions
- Blade templates (except if route names change — they should not)
- Database schema

---

## 4. Pre-Implementation Checklist

- [ ] Phase A and Phase B complete and deployed
- [ ] All Phase A and B tests passing
- [ ] Design review of ProjectAccessService API
- [ ] Decision: keep ProjectPermissionHelper or fully replace with ProjectAccessService

---

## 5. Failing Tests to Write First

Create `tests/Feature/Projects/ProjectAccessServiceTest.php`:

```php
// Test 1: ProjectAccessService::canViewProject matches ProjectPermissionHelper behavior
public function test_can_view_project_matches_helper(): void

// Test 2: ProjectAccessService::getAccessibleUserIdsForProvincial returns correct IDs
public function test_get_accessible_user_ids_for_provincial(): void

// Test 3: Provincial scope query includes owner and in_charge projects
public function test_provincial_scope_includes_owner_and_in_charge(): void

// Test 4: Refactored ProvincialController produces same project list as before
public function test_provincial_project_list_unchanged_after_refactor(): void

// Test 5: ActivityHistoryHelper via ProjectAccessService preserves behavior
public function test_activity_history_access_preserved(): void
```

---

## 6. Step-by-Step Implementation Plan

### Step C.1 — Create ProjectAccessService

**File:** `app/Services/ProjectAccessService.php`

**Methods:**
```php
public static function canViewProject(Project $project, User $user): bool
public static function getAccessibleUserIdsForProvincial(User $provincial): \Illuminate\Support\Collection
public static function canViewProjectActivity(string $projectId, User $user): bool
public static function canViewReportActivity(string $reportId, User $user): bool
```

**Implementation:**
- `canViewProject`: Delegate to `ProjectPermissionHelper::canView` (no behavior change).
- `getAccessibleUserIdsForProvincial`: Extract logic from `ProvincialController::getAccessibleUserIds` (direct children + for general: managed provinces). Return Collection.
- `canViewProjectActivity`: Extract from `ActivityHistoryHelper::canViewProjectActivity`; use `getAccessibleUserIdsForProvincial` for provincial scope.
- `canViewReportActivity`: Extract from `ActivityHistoryHelper::canViewReportActivity`; same pattern.

---

### Step C.2 — Refactor ProvincialController

**File:** `app/Http/Controllers/ProvincialController.php`

- Replace `$this->getAccessibleUserIds($provincial)` with `ProjectAccessService::getAccessibleUserIdsForProvincial($provincial)`.
- Optionally remove `getAccessibleUserIds` method (or keep as deprecated wrapper).
- Ensure all project queries use owner OR in_charge pattern (from Phase A); that logic stays, but IDs come from service.

---

### Step C.3 — Refactor ActivityHistoryHelper

**File:** `app/Helpers/ActivityHistoryHelper.php`

- Replace `canViewProjectActivity` body with `return ProjectAccessService::canViewProjectActivity($projectId, $user);`
- Replace `canViewReportActivity` body with `return ProjectAccessService::canViewReportActivity($reportId, $user);`
- Optionally keep helper as thin wrapper for backward compatibility.

---

### Step C.4 — ExportController (optional)

**File:** `app/Http/Controllers/Projects/ExportController.php`

- Phase B already uses `ProjectPermissionHelper::canView`. To align with centralization, change to `ProjectAccessService::canViewProject($project, $user)` which delegates to canView.
- Minimal change; ensures all access flows through one service.

---

### Step C.5 — Add Project scope (optional)

**File:** `app/Models/OldProjects/Project.php`

```php
public function scopeForProvincial($query, User $provincial)
{
    $ids = ProjectAccessService::getAccessibleUserIdsForProvincial($provincial)->toArray();
    return $query->where(function ($q) use ($ids) {
        $q->whereIn('user_id', $ids)->orWhereIn('in_charge', $ids);
    });
}
```

Use in ProvincialController: `Project::forProvincial($provincial)->...` instead of manual whereIn.

---

## 7. Code Refactor Notes

- Avoid circular dependency: ProjectAccessService must not use ProvincialController.
- ProjectAccessService can use ProjectPermissionHelper, User, Project, DPReport.
- Keep method signatures backward-compatible where helpers are called from multiple places.

---

## 8. Performance Impact Analysis

- **Neutral or positive:** Same logic, potentially cached later in Phase D.
- `getAccessibleUserIdsForProvincial` still called multiple times per request until Phase D caches it.
- No new N+1 if service reuses existing queries.

---

## 9. Security Impact Analysis

- **No behavioral change:** Logic moved, not altered.
- Centralization reduces risk of future drift; one place to audit.
- Ensure ProjectAccessService is never bypassed for critical checks.

---

## 10. Rollback Strategy

1. Revert Phase C commit(s).
2. Restore ProvincialController::getAccessibleUserIds, ActivityHistoryHelper bodies.
3. Remove ProjectAccessService (or leave as unused).
4. Re-run full test suite; fix any failures.
5. Document in `Phase_C_Rollback_Report.md`.

---

## 11. Deployment Checklist

- [ ] All Phase C tests pass
- [ ] Full regression suite pass
- [ ] Code review completed
- [ ] Staging deploy
- [ ] Smoke test: provincial list, show, download; coordinator; activity history
- [ ] Production deploy
- [ ] Monitor 403s and exceptions 24h

---

## 12. Post-Deployment Validation Steps

1. Provincial: project list, show, download — all unchanged behavior.
2. Coordinator: same.
3. General: activity history — unchanged.
4. No new 403s in logs.

---

## 13. Regression Test List

- [ ] All Phase A tests
- [ ] All Phase B tests
- [ ] Provincial project list content
- [ ] Provincial showProject access
- [ ] Activity history for provincial, coordinator, general
- [ ] Download for all roles

---

## 14. Sign-Off Criteria

- ProjectAccessService in place
- ProvincialController, ActivityHistoryHelper, ExportController use it
- All tests pass
- No behavioral regression
- Phase C completion MD updated

---

## Cursor Execution Rule

When implementing this phase, update this MD file with:
- Actual code changes
- File diffs summary
- Test results
- Any deviations from plan
- Date of implementation
- Engineer name
