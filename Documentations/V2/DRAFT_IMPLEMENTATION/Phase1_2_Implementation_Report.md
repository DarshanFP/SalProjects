# Draft Implementation Report

**Phase 1 & 2 – Draft-Safe Backend Alignment**  
**Date:** 2026-02-10

---

## 1. Files Modified

| File | Change |
|------|--------|
| `database/migrations/2026_02_10_120000_set_projects_status_default_to_draft.php` | **New.** Sets `projects.status` column default to `'draft'` (was `'underwriting'`). Does not modify existing rows. Down restores default `'underwriting'`. |
| `app/Http/Requests/Projects/UpdateProjectRequest.php` | Conditional validation in `rules()`: when `save_as_draft` is true, `project_type` is `nullable|string|max:255`; otherwise `required|string|max:255`. All other rules unchanged. |
| `app/Http/Controllers/Projects/ProjectController.php` | In `update()`: (1) When `save_as_draft` and `project_type` not filled, merge existing `project_type` into request to avoid NOT NULL violation. (2) After successful update and refresh, when `save_as_draft` set `project->status = ProjectStatus::DRAFT` and save before redirect. |
| `resources/views/projects/Oldprojects/edit.blade.php` | Removed JS that stripped `[required]` attributes and that bypassed `checkValidity()` for draft. "Save as draft" button still adds hidden `save_as_draft=1` and submits; backend handles validation. Form submit handler now always runs HTML5 validation. |

---

## 2. Validation Changes Summary

- **UpdateProjectRequest**
  - **Draft (`save_as_draft` true):** `project_type` is optional (`nullable|string|max:255`). All other fields unchanged (already nullable or conditional).
  - **Not draft:** `project_type` remains required (`required|string|max:255`).
- **Authorization:** Unchanged. Still uses `ProjectPermissionHelper::canEdit($project, $user)`.
- **SubmitProjectRequest:** Not modified. Submission logic and validation unchanged.
- **No new required rules:** No strict completeness or submission validation added.

---

## 3. Status Handling Changes

- **Migration:** New projects (inserts without an explicit `status`) will get `status = 'draft'`. Existing rows keep their current `status`; only the column default is changed.
- **Create (store):** Unchanged. `applyPostCommitStatusAndRedirect()` still sets status to DRAFT and saves for both draft and non-draft; only redirect differs.
- **Update:** When the request has `save_as_draft` true, after a successful update the controller sets `$project->status = ProjectStatus::DRAFT` and saves, so editing and saving as draft keeps status DRAFT.
- **Submit:** Unchanged. `ProjectStatusService::submitToProvincial()` still sets status to `SUBMITTED_TO_PROVINCIAL`; no changes to submit routes or behavior.

---

## 4. Frontend Cleanup Summary

- **Removed:**
  - In form `submit` handler: the branch that detected draft (via `input[name="save_as_draft"]`) and returned `true` without running `checkValidity()`.
  - In "Save as draft" button handler: the block that selected all `[required]` fields and called `removeAttribute('required')`.
- **Kept:**
  - Hidden input `save_as_draft` with value `'1'` when the "Save as draft" button is clicked.
  - Loading state (button disabled, "Saving..." text) before submit.
  - Form submit still triggers; backend applies relaxed rules when `save_as_draft` is present.
- **Result:** No client-side bypass of HTML5 validation. If the form has `required` on fields (e.g. in Blade), the browser may still block submit until those are filled; backend will not require `project_type` when `save_as_draft` is sent.

---

## 5. Behavior Verification Checklist

| # | Check | Expected |
|---|--------|----------|
| 1 | Create new project | Status is DRAFT (and migration default for new rows is `'draft'`). |
| 2 | Edit project → Save (normal submit) | Full validation; no change to current behavior; status unchanged by this action. |
| 3 | Edit project → Save as draft (with `save_as_draft=1`) | Partial data allowed (e.g. `project_type` optional); status set to DRAFT after save. |
| 4 | Submit project (from show page) | Status becomes SUBMITTED_TO_PROVINCIAL; editing disabled (unchanged). |
| 5 | Draft save with missing business fields | No validation error for `project_type` when `save_as_draft` is true; other nullable fields unchanged. |
| 6 | Submission flow | No change to submit validation, authorization, or status service. |
| 7 | Strict validation | No new required or completeness rules added. |

---

## 6. Risk Notes

- **Migration and `->change()`:** The migration uses `Schema::table('projects', ...)->default('draft')->change()`. On **SQLite** and **PostgreSQL**, changing a column often requires the `doctrine/dbal` package. If the migration fails with a driver/change error, either install `doctrine/dbal` or replace the migration body with a driver-specific raw SQL (e.g. MySQL: `ALTER TABLE projects ALTER status SET DEFAULT 'draft'`). MySQL often works without dbal. This is noted in the migration file comment; no package was added per constraints.
- **project_type NOT NULL:** The `projects` table still has `project_type` NOT NULL. When saving as draft without sending `project_type`, the controller merges the existing `project_type` from the loaded project into the request so that downstream update logic does not write NULL. This keeps draft behavior correct without schema changes.
- **Edit form HTML5 required:** The edit view may still render `required` on some inputs (e.g. in partials). With the JS hack removed, the browser will enforce those on any submit (including when "Save as draft" is used). So "partial data" for draft is only guaranteed for fields that are not required in HTML; backend will not require `project_type` when `save_as_draft` is true. Optional follow-up: remove or conditionally omit `required` in Blade for draft-relevant fields if full partial-data draft save from the edit page is desired.
- **Redirect:** Update still always redirects to `projects.index` with "Project updated successfully." No separate message or redirect for draft (per "Do NOT change redirect behavior").

---

**End of report.**
