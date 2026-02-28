# Coordinator Project View — Issues Analysis

**Date:** 2026-02-23  
**Scope:** Identify why coordinator users cannot view projects

---

## Flow Summary

1. Coordinator accesses project list via `coordinator.projects.list` → `CoordinatorController::projectList`
2. Clicks "View" → `coordinator.projects.show` → `CoordinatorController::showProject` → `ProjectController::show`
3. `ProjectController::show` checks `ProjectPermissionHelper::canView($project, $user)` before rendering

---

## Potential Issues Identified

### 1. Province check blocking coordinator (most likely)

**Location:** `ProjectPermissionHelper::passesProvinceCheck()` → `ProjectController::show` line 828

**Logic:**
```php
if ($user->province_id === null) return true;  // coordinator with null passes
return $project->province_id === $user->province_id;  // otherwise must match
```

**If coordinator has `province_id` set:**
- Coordinator can only view projects where `project.province_id === coordinator.province_id`
- Projects from other provinces → **403**

**If coordinator has `province_id = null`:**
- `passesProvinceCheck` returns true → coordinator can view all projects

**Action:** Verify coordinator users in your system: do they have `province_id` set? If yes, that is the cause. Coordinators typically should have `province_id = null` to view all projects.

---

### 2. Project has null province_id

**Location:** Same `passesProvinceCheck`

**Logic:** If `coordinator.province_id = 5` and `project.province_id = null`:
- `null === 5` → false → **403**

**Action:** Check if any projects have `province_id IS NULL`. Migrations added `province_id` later; older projects might not have it set.

---

### 3. "Back to Projects" link uses executor route (403 on click)

**Location:** `resources/views/projects/Oldprojects/show.blade.php` lines 258–262

```blade
@if(auth()->user()->role === 'admin')
    <a href="{{ route('admin.projects.index') }}">Back to Projects</a>
@else
    <a href="{{ route('projects.index') }}">Back to Projects</a>  {{-- executor only! --}}
@endif
```

**Issue:** For coordinator (and provincial, general), "Back to Projects" uses `route('projects.index')` → `/executor/projects`, which is under `role:executor,applicant`. Coordinator gets **403** when clicking it.

**Fix:** Use role-specific back routes, e.g.:
- coordinator → `coordinator.projects.list`
- provincial → `provincial.projects.list`
- general → coordinator-style route

---

### 4. Predecessor project link uses executor route (403 on click)

**Location:** `resources/views/projects/partials/Show/general_info.blade.php` line 51

```blade
<a href="{{ route('projects.show', $project->predecessor->project_id) }}">
```

**Issue:** `projects.show` resolves to `/executor/projects/{project_id}` (executor-only). Coordinator clicking the predecessor link gets **403**.

**Fix:** Use role-based show route, e.g. `coordinator.projects.show` for coordinator.

---

### 5. Edit Project link (if shown)

**Location:** `show.blade.php` line 272 — `route('projects.edit', ...)`

**Issue:** `projects.edit` is under executor prefix → coordinator gets **403**. But `canEdit` should be false for coordinator on approved projects, so the button may not show. Verify whether coordinator ever sees the Edit button for draft/reverted projects.

---

## Quick Diagnostic Steps

1. **Check coordinator’s province_id:**
   ```sql
   SELECT id, name, role, province_id FROM users WHERE role = 'coordinator';
   ```
   If `province_id` is set, coordinator is restricted to that province.

2. **Check project province_id:**
   ```sql
   SELECT project_id, province_id FROM projects WHERE province_id IS NULL LIMIT 10;
   ```

3. **Check Laravel logs** when coordinator gets 403:
   - `ProjectController@show - Access denied` → `ProjectPermissionHelper::canView` failed (province or role)
   - Middleware 403 → wrong route (e.g. executor route)

4. **Confirm entry path:** Coordinator must open projects via `coordinator.projects.show` (e.g. from project list), not via `projects.show` (executor URL).

---

## Recommended Fixes

| # | Issue | Fix |
|---|-------|-----|
| 1 | Province check | Ensure coordinator users have `province_id = null` (or relax check for coordinator role) |
| 2 | Null project province | Backfill or handle `project.province_id` null in `passesProvinceCheck` |
| 3 | Back to Projects | Use role-based back route: coordinator → `coordinator.projects.list`, provincial → `provincial.projects.list`, general → coordinator route |
| 4 | Predecessor link | Use role-based show route: e.g. helper that returns `coordinator.projects.show` or `provincial.projects.show` based on `auth()->user()->role` |

---

*Analysis based on codebase review 2026-02-23.*
