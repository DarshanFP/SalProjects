# Phase B — Download Consistency

**Rule:** Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.

---

## 1. Objective

Align project PDF/DOC download access with view access. Remove status-based restrictions from ExportController so that if a user can view a project, they can download it. Eliminate role-switch duplication by using `ProjectPermissionHelper::canView` as the single authorization source for download.

---

## 2. Scope (Exact Files Involved)

| File | Change |
|------|--------|
| `app/Http/Controllers/Projects/ExportController.php` | Replace role/status switch with `ProjectPermissionHelper::canView` for coordinator and provincial |
| `tests/Feature/Projects/DownloadConsistencyTest.php` | **New file** — failing tests for view-then-download alignment |
| `Documentations/V2/VIEW/02_Phase_B_Changes.md` | **New file** — document changes and rationale |

---

## 3. What Will NOT Be Touched

- `ProjectPermissionHelper`
- `AttachmentController`
- Provincial or Coordinator show routes
- Blade templates (download links unchanged)
- Phase A changes (assume Phase A is complete and deployed)

---

## 4. Pre-Implementation Checklist

- [ ] Phase A complete and deployed
- [ ] Phase A regression tests passing
- [ ] Audit understanding: current provincial allowed statuses = SUBMITTED_TO_PROVINCIAL, REVERTED_BY_COORDINCIAL, APPROVED_BY_COORDINATOR
- [ ] Audit understanding: current coordinator allowed statuses = FORWARDED_TO_COORDINATOR, APPROVED_BY_COORDINATOR, REVERTED_BY_COORDINATOR
- [ ] Product owner sign-off on behavior change (download will be available for all viewable statuses)

---

## 5. Failing Tests to Write First

Create `tests/Feature/Projects/DownloadConsistencyTest.php` with:

```php
// Test 1: Coordinator can download project in draft status (if they can view)
public function test_coordinator_can_download_project_in_draft_when_viewable(): void

// Test 2: Provincial can download project in draft when in scope
public function test_provincial_can_download_project_in_draft_when_in_scope(): void

// Test 3: Coordinator can download project in submitted_to_provincial
public function test_coordinator_can_download_submitted_to_provincial(): void

// Test 4: Download access matches canView for all statuses
public function test_download_access_matches_can_view_for_all_statuses(): void
```

**Note:** Tests must seed projects in various statuses and assert that coordinator/provincial with view access can also download.

---

## 6. Step-by-Step Implementation Plan

### Step B.1 — Simplify ExportController::downloadPdf authorization

**File:** `app/Http/Controllers/Projects/ExportController.php`

**Current logic (approx lines 329–370):** Role switch with status whitelists for provincial and coordinator.

**Replace with:**
```php
$user = Auth::user();
if (!ProjectPermissionHelper::canView($project, $user)) {
    abort(403, 'You do not have permission to download this project.');
}
```

**Remove:** Entire `if (in_array($user->role, ['admin', 'coordinator', 'provincial'])) { switch ... } else { canView }` block.

**Rationale:** `canView` already handles admin, coordinator, provincial, general (and executor/applicant owner/in_charge). No status filter in canView; download will align with view.

---

### Step B.2 — Simplify ExportController::downloadDoc authorization

**File:** `app/Http/Controllers/Projects/ExportController.php`

**Same change as B.1** for `downloadDoc` method (approx lines 456–496).

---

### Step B.3 — Add in_charge check for provincial (if Phase A extended ExportController)

**Note:** Phase A did not change ExportController access logic for provincial (only null-safety). Phase B replaces the entire access block. If provincial hierarchy check (owner or in_charge in scope) is required, `canView` for provincial returns true after `passesProvinceCheck` — it does NOT enforce hierarchy. ProvincialController::showProject enforces hierarchy before delegating to ProjectController::show.

**Critical:** `ProjectPermissionHelper::canView` for provincial returns true for any project in the same province. ProvincialController gates by hierarchy. ExportController is called directly via routes (provincial.projects.downloadPdf, etc.). So a provincial could theoretically download a project in their province but outside their team if they guess the project_id.

**Decision required:** Either:
- **Option 1:** Keep canView only — provincial with province_id sees only same-province projects; hierarchy is enforced at list/show level (user never sees the link). Risk: URL guessing.
- **Option 2:** Add hierarchy check in ExportController for provincial: load accessibleUserIds and require owner or in_charge in scope.

**Recommendation for Phase B:** Use `canView` only (Option 1). Hierarchy is enforced by ProvincialController at list/show; download links only appear for projects the user can already see. URL guessing is mitigated by project_id being non-sequential. Document this as a known limitation; Phase C can introduce a shared hierarchy check if needed.

---

## 7. Code Refactor Notes

- Remove ~40 lines of role/status switch logic.
- Single line: `ProjectPermissionHelper::canView($project, $user)`.
- Ensure `$project->user` is still loaded for PDF/DOC content (executor name, etc.); no change to hydration.

---

## 8. Performance Impact Analysis

- **Positive:** Fewer conditionals; simpler code path.
- No additional queries.
- Same project load and hydration as before.

---

## 9. Security Impact Analysis

- **Aligned:** Download now matches view. No new privilege escalation.
- **Provincial scope:** If canView allows provincial to see any same-province project, download will too. Current canView for provincial returns true after passesProvinceCheck; provincial typically has province_id set, so they only see same-province. Verify ProvincialController never shows projects outside hierarchy; then download links are only for in-scope projects.
- **Coordinator:** canView returns true (province null); coordinator sees all. Download aligns.

---

## 10. Rollback Strategy

1. Revert Phase B commit.
2. Restore role/status switch in ExportController.
3. Re-run DownloadConsistencyTest — tests will fail (expected after rollback).
4. Document rollback in `Phase_B_Rollback_Report.md`.

---

## 11. Deployment Checklist

- [ ] Phase B tests pass
- [ ] Product owner confirms: download available for all viewable statuses
- [ ] Manual QA: coordinator downloads draft project
- [ ] Manual QA: provincial downloads draft project (in scope)
- [ ] Staging deploy
- [ ] Production deploy
- [ ] Monitor download 403s for 24h

---

## 12. Post-Deployment Validation Steps

1. Coordinator: view project in draft → click Download PDF → must succeed.
2. Provincial: view project in draft (in scope) → click Download PDF → must succeed.
3. Verify no increase in 403s for previously allowed statuses.

---

## 13. Regression Test List

- [ ] Admin can download (canView includes admin)
- [ ] Executor can download own project
- [ ] Executor can download in-charge project
- [ ] Provincial cannot download project outside scope (if hierarchy enforced at show only)
- [ ] Attachment download unchanged

---

## 14. Sign-Off Criteria

- All Phase B tests pass
- ExportController reduced to single canView check
- No new 403s in staging for valid scenarios
- Phase B completion MD updated

---

## Cursor Execution Rule

When implementing this phase, update this MD file with:
- Actual code changes
- File diffs summary
- Test results
- Any deviations from plan
- Date of implementation
- Engineer name
