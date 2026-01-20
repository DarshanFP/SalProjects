# Resolved: Coordinator Approve, Revert & UI Fixes

**Location:** `Documentations/V1/resolved/`  
**Scope:** Coordinator workflow, notifications, database, and approval modals  
**Period:** January 2026

---

## Table of Contents

1. [Coordinator Revert Flow](#1-coordinator-revert-flow)
2. [NotificationService `notifyRevert` TypeError](#2-notificationservice-notifyrevert-typeerror)
3. [Coordinator Approval – Missing `commencement_month` / `commencement_year`](#3-coordinator-approval--missing-commencement_month--commencement_year)
4. [Success/Error Messages Showing Raw HTML](#4-successerror-messages-showing-raw-html)
5. [Pending Projects Sidebar Filter](#5-pending-projects-sidebar-filter)
6. [Approve Modal – Commencement Month & Year Not Prefilled](#6-approve-modal--commencement-month--year-not-prefilled)

---

## 1. Coordinator Revert Flow

**Issue:**  
Clicking “Revert to Provincial” triggered an immediate revert and opened a comment box. Submitting the comment afterwards caused a “project status error” because the project was already reverted.

**Root cause:**  
On the project detail page (`projects/partials/actions.blade.php`), “Revert to Provincial” was a direct form submit with **no** `revert_reason` and **no** modal. The revert ran on button click; any separate “comment” UI was effectively a second step after status had already changed.

**Changes:**

| File | Change |
|------|--------|
| `resources/views/projects/partials/actions.blade.php` | Replaced direct submit with a button that opens a Bootstrap modal. Modal contains a form with required `revert_reason` textarea; submit sends both revert action and reason in one request. Added `data-open-on-error` and JS to re-open the modal when validation fails. |
| `app/Http/Controllers/CoordinatorController.php` | In `revertToProvincial`: added validation for `revert_reason` (required, string, max 1000). Replaced `abort(403, ...)` with `redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput($request->only('revert_reason'))`. Added logging for debugging. |
| `resources/views/projects/Oldprojects/show.blade.php` | Added blocks to show `$errors->any()`, `session('error')`, and `session('success')`. |

**Result:**  
Revert and reason are always submitted together. User must provide a reason before the revert is performed. Validation errors and server errors are shown and the revert modal can re-open on error.

---

## 2. NotificationService `notifyRevert` TypeError

**Issue:**  
`TypeError: NotificationService::notifyRevert(): Argument #3 ($relatedId) must be of type int, string given` when reverting a project. `project_id` values (e.g. `DP-0025`) are strings; `notifications.related_id` was `BIGINT UNSIGNED`.

**Root cause:**  
`NotificationService::notifyRevert()` (and related methods) expected `int $relatedId` and the DB column was integer-only, while `$project->project_id` is a string.

**Changes:**

| File | Change |
|------|--------|
| `app/Services/NotificationService.php` | `create`: `?int $relatedId` changed to accept `int`, `string`, or `null`; store as `(string) $relatedId` when not null. `notifyRevert`, `notifyApproval`, `notifyRejection`, `notifyDeadlineReminder`: `int $relatedId` changed to accept `int` or `string`. |
| `database/migrations/2026_01_19_000001_change_notifications_related_id_to_string.php` | New migration: `notifications.related_id` changed from `BIGINT UNSIGNED` to `VARCHAR(255) NULL` (MySQL). `down()` reverts to `BIGINT UNSIGNED NULL`. |

**Result:**  
Notifications work for both integer and string IDs (e.g. `project_id`). Revert and other flows that call these methods no longer throw the `TypeError`.

---

## 3. Coordinator Approval – Missing `commencement_month` / `commencement_year`

**Issue:**  
Coordinator approval failed with `QueryException`: unknown columns `commencement_month` and `commencement_year`. The approval form and `CoordinatorController::approveProject` expected these fields, but the `projects` table did not have them.

**Root cause:**  
Only `commencement_month_year` existed. Approval logic and forms used `commencement_month` and `commencement_year`.

**Changes:**

| File | Change |
|------|--------|
| `database/migrations/2026_01_19_000002_add_commencement_month_year_to_projects_table.php` | New migration: add `commencement_month` (`unsignedTinyInteger`, nullable, after `current_phase`) and `commencement_year` (`unsignedSmallInteger`, nullable, after `commencement_month`). `down()` drops both. |
| `app/Models/OldProjects/Project.php` | Added `commencement_month` and `commencement_year` to `$fillable`. Added `@property` docblock for both. |
| `resources/views/coordinator/ProjectList.blade.php` | Replaced direct approve form with an “Approve” modal that includes `commencement_month` (select) and `commencement_year` (input, then later select). |
| `resources/views/coordinator/widgets/pending-approvals.blade.php` | Added shared “Approve Project” modal with `commencement_month` and `commencement_year`. Approve button opens the modal; JS sets form action from `data-project-id`. |
| `resources/views/projects/partials/actions.blade.php` | Approve modal already had commencement fields; ensured they post `commencement_month` and `commencement_year` correctly. |
| `app/Http/Controllers/CoordinatorController.php` | In `approveProject`: use `commencement_month` and `commencement_year` from the request and persist on `Project`. Added logging. |
| `app/Http/Requests/Projects/ApproveProjectRequest.php` | Validation and rules for `commencement_month` and `commencement_year`; added logging for auth and validation failures. |

**Result:**  
Approval works with `commencement_month` and `commencement_year` stored on `projects`. All coordinator approve UIs (list, pending-approvals widget, project detail) send these fields.

---

## 4. Success/Error Messages Showing Raw HTML

**Issue:**  
After approval, the success message showed raw HTML (e.g. `<br>`, `<strong>`) instead of rendered line breaks and bold text.

**Root cause:**  
`{{ session('success') }}` and `{{ session('error') }}` escape HTML. Messages from the controller include HTML for layout.

**Changes:**

| File | Change |
|------|--------|
| `resources/views/coordinator/ProjectList.blade.php` | `{{ session('success') }}` → `{!! session('success') !!}` and `{{ session('error') }}` → `{!! session('error') !!}`. |
| `resources/views/projects/Oldprojects/show.blade.php` | Added `@if(session('success'))` block with `{!! session('success') !!}` and `@if(session('error'))` with `{!! session('error') !!}`. |

**Result:**  
Success and error messages render HTML correctly. Ensure only trusted, server-generated content is put into these flashes.

---

## 5. Pending Projects Sidebar Filter

**Issue:**  
The “Pending Projects” link in the coordinator sidebar went to `coordinator/projects-list` with no status filter, so it showed all projects instead of only those pending coordinator action.

**Root cause:**  
Link used `route('coordinator.projects.list')` without a `status` query.

**Changes:**

| File | Change |
|------|--------|
| `resources/views/coordinator/sidebar.blade.php` | “Pending Projects” `href` set to `route('coordinator.projects.list', ['status' => 'forwarded_to_coordinator'])`. |

**Result:**  
“Pending Projects” opens the project list filtered by `status=forwarded_to_coordinator`.

---

## 6. Approve Modal – Commencement Month & Year Not Prefilled

**Issue:**  
In the Approve modal, Commencement Month and Commencement Year were prefilled (e.g. current month/year). The requirement was that both must start as “Select one” so the user explicitly chooses.

**Root cause:**  
Month `<select>` had a `selected` option based on `$project->commencement_month` or `date('n')`. Year used an `<input type="number">` with `value="{{ date('Y') }}"` or similar. The pending-approvals widget also pre-filled from `data-commencement-month` and `data-commencement-year` when opening the modal.

**Changes:**

| File | Change |
|------|--------|
| `resources/views/coordinator/ProjectList.blade.php` | Month: added first option `<option value="">Select month</option>`, removed `selected` from the loop. Year: replaced number input with `<select>`; first option `<option value="">Select year</option>`, then years from `date('Y')` to `date('Y')+10`; no pre-selection. |
| `resources/views/coordinator/widgets/pending-approvals.blade.php` | Month: added “Select month” as first option; removed `selected` in the loop. Year: replaced input with select and “Select year” as first option. Removed `data-commencement-month` and `data-commencement-year` from the Approve button. JS: on modal open, set `approve_commencement_month` and `approve_commencement_year` to `''` instead of pre-filling from `data-*`. Removed unused `month`/`year` variables from the approve click handler. |
| `resources/views/projects/partials/actions.blade.php` | Month: added “Select month” first; removed `selected` in the loop. Year: replaced number input with select; “Select year” first, then years; no pre-selection. Kept “Current: …” hint text. |

**Result:**  
All Approve modals (ProjectList, pending-approvals, project detail) show “Select month” and “Select year” by default. Both remain `required`, so the user must choose before submitting.

---

## Summary of Files Touched

| Category | Files |
|----------|-------|
| **Views** | `resources/views/coordinator/ProjectList.blade.php`, `resources/views/coordinator/widgets/pending-approvals.blade.php`, `resources/views/coordinator/sidebar.blade.php`, `resources/views/projects/partials/actions.blade.php`, `resources/views/projects/Oldprojects/show.blade.php` |
| **Controllers** | `app/Http/Controllers/CoordinatorController.php` |
| **Requests** | `app/Http/Requests/Projects/ApproveProjectRequest.php` |
| **Services** | `app/Services/NotificationService.php` |
| **Models** | `app/Models/OldProjects/Project.php` |
| **Migrations** | `2026_01_19_000001_change_notifications_related_id_to_string.php`, `2026_01_19_000002_add_commencement_month_year_to_projects_table.php` |

---

## Migrations to Run

If deploying to a new or older database, ensure these are run:

```bash
php artisan migrate
```

Relevant migrations:

- `2026_01_19_000001_change_notifications_related_id_to_string`
- `2026_01_19_000002_add_commencement_month_year_to_projects_table`

---

*Last updated: January 2026*
