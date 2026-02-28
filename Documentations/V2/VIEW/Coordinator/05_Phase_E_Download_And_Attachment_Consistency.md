# Phase E — Download & Attachment Consistency

**Rule:** Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.

---

**Phase:** E  
**Objective:** Ensure coordinator download and attachment logic aligns with view. ExportController and attachment endpoints must rely on ProjectAccessService / canView. Remove redundant checks. Confirm no status restriction drift.

---

## 1. Objective

Coordinator can view all projects. Therefore coordinator must be able to download exports and attachments for any project they can view. ExportController and any attachment controllers must use ProjectAccessService::canViewProject (or equivalent) for coordinator. Remove redundant or conflicting checks (e.g. status restrictions that differ from view).

---

## 2. Scope (Exact Files)

| File | Change |
|------|--------|
| `app/Http/Controllers/Projects/ExportController.php` | Ensure coordinator path uses ProjectAccessService::canViewProject; remove redundant checks; align status logic with view |
| Attachment controllers or services (if any) | Guard by canView; delegate to ProjectAccessService for coordinator |
| `routes/web.php` | Verify coordinator has access to download/export routes (if route-level restrictions exist) |

---

## 3. What Will NOT Be Touched

- ProjectAccessService
- CoordinatorController
- ProjectPermissionHelper
- ActivityHistoryHelper
- Provincial/Executor download logic (unless shared and needs coordinator inclusion)
- Blade templates

---

## 4. Pre-Implementation Checklist

- [ ] Phase A–D complete
- [ ] ExportController download methods identified
- [ ] Attachment endpoints identified
- [ ] Current coordinator handling in ExportController reviewed

---

## 5. Failing Tests to Write First

```php
// tests/Feature/ExportControllerCoordinatorTest.php

public function test_coordinator_can_download_any_project_export(): void
public function test_export_controller_uses_can_view_project_for_coordinator(): void
public function test_no_status_restriction_drift_for_coordinator(): void

// tests/Feature/AttachmentCoordinatorTest.php (if applicable)

public function test_coordinator_can_access_attachment_for_any_viewable_project(): void
```

---

## 6. Step-by-Step Implementation Plan

### Step E.1 — Audit ExportController

**File:** `app/Http/Controllers/Projects/ExportController.php`

**Actions:**
- Locate all download/export methods (e.g. exportProject, downloadReport, downloadAttachment).
- Identify access checks: ProjectPermissionHelper::canView, status checks, role checks.
- Ensure coordinator is explicitly allowed where view is allowed.
- Ensure no status restriction that blocks coordinator when view would allow (e.g. draft vs submitted).

### Step E.2 — Rely on ProjectAccessService for Coordinator

**Actions:**
- For each export method that checks access: Use ProjectAccessService::canViewProject($project, $user) for coordinator (and optionally admin, general) instead of ad-hoc logic.
- Alternatively: Use ProjectPermissionHelper::canView (after Phase C) which delegates to ProjectAccessService. Ensure consistency.

### Step E.3 — Remove Redundant Checks

**Actions:**
- If coordinator is blocked by status (e.g. only submitted) but canView allows draft, align: coordinator can download if they can view.
- Remove duplicate role checks (e.g. multiple `if ($user->role === 'coordinator')` blocks).
- Consolidate into single access gate (canView or canViewProject).

### Step E.4 — Attachment Consistency

**Actions:**
- Identify controllers that serve attachments (e.g. FileController, attachment download route).
- Ensure they use canView/canViewProject before serving file.
- Coordinator must pass for any project they can view.

### Step E.5 — Route Verification

**Actions:**
- Confirm coordinator middleware allows access to export routes.
- Confirm shared download route group includes coordinator (if applicable).
- No admin-only restriction on export routes for coordinator.

---

## 7. Security Impact Analysis

- **Positive:** Single source of truth; download aligns with view.
- **No over-permission:** Coordinator does not gain admin-only exports (e.g. system backups).
- **Boundary:** Coordinator downloads project data only, not system-wide exports.

---

## 8. Performance Impact Analysis

- **Minimal:** canViewProject is lightweight (role check, optional province check).
- **No extra queries:** Project already loaded for export; no additional DB calls for coordinator.

---

## 9. Rollback Strategy

1. Revert ExportController and attachment controller changes.
2. Restore previous access checks.
3. Re-run tests.

---

## 10. Deployment Checklist

- [ ] Phase E tests pass
- [ ] Coordinator can download any project export
- [ ] Coordinator can access attachments for any viewable project
- [ ] No regression for provincial, executor, admin downloads
- [ ] Route permissions correct

---

## 11. Regression Checklist

- [ ] Provincial download unchanged
- [ ] Executor download unchanged
- [ ] Admin download unchanged
- [ ] Coordinator download works for all provinces
- [ ] Status restrictions consistent with view

---

## 12. Sign-Off Criteria

- ExportController relies on ProjectAccessService (or canView that delegates)
- Attachments guarded by canView
- No redundant checks
- No status restriction drift for coordinator
- Phase E completion MD updated
