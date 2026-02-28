# Phase A — Access Stabilization

**Rule:** Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.

---

## 1. Objective

Stabilize access for Coordinator and Provincial roles by fixing:
1. Provincial owner/in_charge parity in project list, show, and report flows
2. Wrong blade route for project ID link in Provincial ProjectList (causing 403)
3. ActivityHistoryHelper exclusion of general users (403 on project activity history)
4. ExportController null-safety for `$project->user`

---

## 2. Scope (Exact Files Involved)

| File | Change |
|------|--------|
| `app/Http/Controllers/ProvincialController.php` | owner + in_charge in projectList, showProject, report lists, pending approvals, team overview |
| `resources/views/provincial/ProjectList.blade.php` | Project ID link: `projects.show` → `provincial.projects.show` |
| `app/Helpers/ActivityHistoryHelper.php` | Add `general` to admin/coordinator branch in `canViewProjectActivity` and `canViewReportActivity` |
| `app/Http/Controllers/Projects/ExportController.php` | Null check for `$project->user` before `parent_id` access |
| `tests/Feature/Projects/ProjectAccessStabilizationTest.php` | **New file** — failing tests to add first |

---

## 3. What Will NOT Be Touched

- `ProjectPermissionHelper` (no changes)
- `CoordinatorController` (no changes)
- `AttachmentController` (no changes)
- `ProjectController::show` (no changes)
- ExportController status logic (addressed in Phase B)
- Routes (no new routes; only blade route name change)

---

## 4. Pre-Implementation Checklist

- [ ] Audit document `Project_View_Access_Audit_And_Implementation_Plan.md` reviewed
- [ ] Branch created from stable main/develop
- [ ] Database backup verified (if applicable)
- [ ] Local/staging environment ready
- [ ] Team informed of Phase A start

---

## 5. Failing Tests to Write First

Create `tests/Feature/Projects/ProjectAccessStabilizationTest.php` with:

```php
// Test 1: Provincial can view project where in_charge is in team, owner is not
public function test_provincial_can_view_project_when_in_charge_is_in_team(): void

// Test 2: Provincial project list includes projects where in_charge is in accessible users
public function test_provincial_project_list_includes_in_charge_projects(): void

// Test 3: Provincial showProject allows access when in_charge in scope
public function test_provincial_show_project_allows_in_charge_scope(): void

// Test 4: General can access project activity history
public function test_general_can_access_project_activity_history(): void

// Test 5: ExportController does not throw when project user is null (or skip if FK prevents)
public function test_export_controller_handles_missing_project_user_gracefully(): void
```

**Note:** Test 2 and 3 may require seeding users/projects with specific owner vs in_charge relationships. Document seed data in phase completion MD.

---

## 6. Step-by-Step Implementation Plan

### Step A.1 — Provincial projectList: Add in_charge to base query

**File:** `app/Http/Controllers/ProvincialController.php`

**Current (approx line 534):**
```php
$baseQuery = Project::whereIn('user_id', $accessibleUserIds)
```

**Change to:**
```php
$baseQuery = Project::where(function ($q) use ($accessibleUserIds) {
    $ids = $accessibleUserIds->toArray();
    $q->whereIn('user_id', $ids)->orWhereIn('in_charge', $ids);
})
```

**Apply same pattern** to all `Project::whereIn('user_id', $accessibleUserIds)` usages in:
- `projectList()` base query
- `provincialDashboard()` approved projects query
- Report list queries (`DPReport::whereIn('user_id', ...)` — reports use `user_id`; in_charge affects projects only; confirm whether report owner vs project owner/in_charge matters)
- `getPendingApprovalsForDashboard` pending projects
- Team overview queries
- Approved projects queries
- Any other project-scoped queries using `accessibleUserIds`

**Verify:** Reports are tied to project via `project_id`; report `user_id` is typically executor. For reports, keep `user_id` unless business rule says in_charge can submit reports. Document decision.

---

### Step A.2 — Provincial showProject: Allow access when in_charge in scope

**File:** `app/Http/Controllers/ProvincialController.php`

**Current (approx lines 683–685):**
```php
if (!in_array($project->user_id, $accessibleUserIds->toArray())) {
    abort(403, 'Unauthorized');
}
```

**Change to:**
```php
$ids = $accessibleUserIds->toArray();
$ownerInScope = in_array($project->user_id, $ids);
$inChargeInScope = $project->in_charge && in_array($project->in_charge, $ids);
if (!$ownerInScope && !$inChargeInScope) {
    abort(403, 'Unauthorized');
}
```

---

### Step A.3 — Provincial ProjectList: Fix project ID link route

**File:** `resources/views/provincial/ProjectList.blade.php`

**Current (line 277):**
```blade
<a href="{{ route('projects.show', $project->project_id) }}">
```

**Change to:**
```blade
<a href="{{ route('provincial.projects.show', $project->project_id) }}">
```

---

### Step A.4 — ActivityHistoryHelper: Add general to canViewProjectActivity

**File:** `app/Helpers/ActivityHistoryHelper.php`

**Current (approx line 49):**
```php
if (in_array($user->role, ['admin', 'coordinator'])) {
    return true;
}
```

**Change to:**
```php
if (in_array($user->role, ['admin', 'coordinator', 'general'])) {
    return true;
}
```

**Same change** in `canViewReportActivity` (approx line 93).

---

### Step A.5 — ExportController: Null-safety for $project->user

**File:** `app/Http/Controllers/Projects/ExportController.php`

**Current (downloadPdf, approx line 337):**
```php
if ($project->user->parent_id === $user->id) {
```

**Change to:**
```php
if ($project->user && $project->user->parent_id === $user->id) {
```

**Same change** in `downloadDoc` (approx line 465).

---

## 7. Code Refactor Notes

- No refactor in this phase; only additive and corrective changes.
- Use `$accessibleUserIds->toArray()` once per method to avoid repeated conversion.
- Consider extracting `$ids = $accessibleUserIds->toArray()` at start of methods that use it multiple times.

---

## 8. Performance Impact Analysis

- **Minimal:** `orWhereIn('in_charge', $ids)` may add a small query cost; ensure `projects.in_charge` is indexed.
- No new N+1 introduced.
- Null check is negligible.

---

## 9. Security Impact Analysis

- **Positive:** Provincial now correctly sees in-charge projects; no new attack surface.
- **Neutral:** General can now access activity history; aligns with existing general permissions.
- **Positive:** Null check prevents potential exception; no information disclosure.

---

## 10. Rollback Strategy

1. Revert commit(s) for Phase A.
2. No database migrations; no schema changes to roll back.
3. Restore previous blade route if needed.
4. Run full regression suite; document any failures in `Phase_A_Rollback_Report.md`.

---

## 11. Deployment Checklist

- [ ] All Phase A tests pass
- [ ] Manual QA: provincial views in-charge project, clicks project ID link (no 403)
- [ ] Manual QA: general opens project activity history (no 403)
- [ ] Staging deploy and smoke test
- [ ] Production deploy during low-traffic window
- [ ] Monitor logs for 403s and exceptions for 24h

---

## 12. Post-Deployment Validation Steps

1. Provincial user: open project list, click project ID → must open show page (no 403).
2. Provincial user: open project where in_charge is in team, owner not → must see project.
3. General user: open project, click Activity History → must see activity (no 403).
4. ExportController: verify no new exceptions in logs for projects with valid users.

---

## 13. Regression Test List

- [ ] Executor can view own projects
- [ ] Executor can view in-charge projects
- [ ] Provincial can view owner projects (unchanged)
- [ ] Coordinator can view all projects (unchanged)
- [ ] Attachment view/download still works for provincial and coordinator
- [ ] Download PDF/DOC still works for provincial and coordinator (same status rules as before)

---

## 14. Sign-Off Criteria

- All 5 failing tests pass
- No new linter/static analysis errors
- Manual QA checklist completed
- Phase A completion MD updated with diffs, test results, date, engineer

---

## Cursor Execution Rule

When implementing this phase, update this MD file with:
- Actual code changes
- File diffs summary
- Test results
- Any deviations from plan
- Date of implementation
- Engineer name
