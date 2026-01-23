# Phase-Wise Implementation Plan: Activity Store-Only-When-User-Filled & Ignore-Month

**Based on:** [Activity_Store_Only_When_User_Filled_And_Ignore_Month_Proposal.md](./Activity_Store_Only_When_User_Filled_And_Ignore_Month_Proposal.md)  
**Requirement:** Do not create an activity row when the user has not entered any user-filled field; in those cases, ignore `month` for that `project_activity_id`.

---

## Edit Partials and Controller Methods — Review

### Edit flow (ReportController)

| Step | Method | What it does |
|------|--------|---------------|
| 1 | `update(UpdateMonthlyReportRequest, $report_id)` | Entry; validates; loads report with `objectives.activities`. |
| 2 | `updateReport($validatedData, $report)` | Updates `DP_Reports` row only. |
| 3 | `storeObjectivesAndActivities($request, $report_id, $report)` | Same as **Create**. Upserts `DP_Objectives`, calls `storeActivities` for each. |
| 4 | `storeActivities($request, $objective, $index, $objective_id)` | **Same method as Create.** Builds `$filled`, creates/updates/deletes `DP_Activities`. |
| 5 | `handleAccountDetails` | Statements of account. |
| 6 | `handleOutlooks` | Outlooks. |
| 7 | `updatePhotos` | Photos (existing + new + deletes). |
| 8 | `handleSpecificProjectData` | Type-specific (Livelihood, Institutional, RST, CIC). |
| 9 | `handleUpdateAttachments` | New + legacy attachments. |

**Draft on Edit:** If `save_as_draft` and `$validatedData['objective']` is empty, `storeObjectivesAndActivities` is **skipped**; otherwise it runs. When it runs, `storeActivities` uses the same `$filled` condition, so **Phase 1 applies to Edit** including Save as Draft.

### Edit view and form

| Item | Value |
|------|-------|
| View | `resources/views/reports/monthly/edit.blade.php` |
| Form id | `#editReportForm` |
| Action | `route('monthly.report.update', $report->report_id)` |
| Method | POST + `@method('PUT')` |
| Buttons | **Save as Draft** (`#saveDraftBtn`), **Update Report** (`#updateReportBtn`) |
| Report month/year | `#report_month`, `#report_year` (used by `report-period-sync.js`) |

### Edit objectives partial

| Item | Value |
|------|-------|
| Partial | `resources/views/reports/monthly/partials/edit/objectives.blade.php` |
| Data source | `$report->objectives` (each `$objective->activities` from DB) |
| Prefill | `old(..., $activity->month)`, `old(..., $activity->summary_activities)`, etc. |
| JS | `addActivity`, `removeActivity`, `reindexActivities`, `updateActivityStatus`, `toggleObjectiveCard`, `toggleActivityCard`; calls `syncReportPeriodToSections`, `refreshPhotoActivityOptions` |

### Create vs Edit — objectives partial

| Aspect | Create | Edit |
|--------|--------|------|
| Data | `$objectives` from **project** (ProjectObjective, activities, timeframes) | `$report->objectives` from **report** (DPObjective, DPActivity) |
| `expected_outcome` | `$objective->results` | `$objective->expected_outcome` (array from JSON) |
| `project_objective_id` | `$objective->objective_id` | `$objective->project_objective_id` |
| `project_activity_id` | `$activity->activity_id` | `$activity->project_activity_id` |
| Add Other `project_activity_id` | Hidden `value=""` in `addActivity` HTML | **Missing** in `addActivity` HTML |
| `updateActivityStatus` | Includes `month` in badge logic | Same (includes `month`) |
| `storeActivities` (backend) | Shared | **Same** |

### Edit-only change: Add Other and `project_activity_id`

In **edit** `addActivity`, the Add Other block does **not** include:

```html
<input type="hidden" name="project_activity_id[${objectiveIndex}][${activityIndex}]" value="">
```

Create does. The backend still works without it (`$projectActivityIds[$activityIndex] ?? null` is `null`), but for consistency and explicit payload, we add it in **Phase 3.4**.

### Edit: legacy month-only activities

Edit loads all `$report->objectives` and `->activities`; it does **not** filter by `hasUserFilledData()`. So legacy activities with only `month` can appear in the form. On save, `storeActivities` will not add them to `$keptActivityIds` (because `$filled` is false), and they will be **deleted**. No change needed in the edit load; we only document this.

---

## Overview

| Phase | Name | Mandatory | Effort | Risk |
|-------|------|-----------|--------|------|
| 1 | Core backend: exclude `month` from store condition | **Yes** | Low | Low |
| 2 | Model: align `hasUserFilledData()` with user-filled definition | **Yes** | Low | Low |
| 3 | Frontend: status badge (3.2–3.3) + Edit addActivity `project_activity_id` (3.4) | Recommended | Low | Low |
| 4 | Optional: clear `month` on submit when no user-filled content | No | Medium | Low |
| 5 | Documentation and verification | Recommended | Low | — |

**Recommendation:** Implement **Phases 1, 2, and 3** (including 3.4 for Edit); treat **Phase 4** as optional hardening. Phase 5 after each phase or at the end.

### Files touched by phase

| Phase | File(s) |
|-------|---------|
| 1 | `app/Http/Controllers/Reports/Monthly/ReportController.php` (`storeActivities`) |
| 2 | `app/Models/Reports/Monthly/DPActivity.php` (`hasUserFilledData`) |
| 3 | `resources/views/reports/monthly/partials/create/objectives.blade.php` (`updateActivityStatus`); `resources/views/reports/monthly/partials/edit/objectives.blade.php` (`updateActivityStatus`, `addActivity`) |
| 4 | `resources/views/reports/monthly/ReportAll.blade.php` or `edit.blade.php` (or shared script) — form `submit` handler |
| 5 | `Activity_Store_Only_When_User_Filled_And_Ignore_Month_Proposal.md` (implementation status); this file (changelog). |

---

## Prerequisites

- [ ] Codebase at a known good state (tag or branch).
- [ ] `report-period-sync.js` is loaded on create/edit (already true for ReportAll, edit, developmentProject).
- [ ] Access to run PHPUnit and manual tests (create report, edit report, view, PDF export).

---

## Phase 1 — Core Backend: Exclude `month` from Store Condition

**Objective:** Only store an activity when at least one **user-filled** field is present; do not treat `month` as user-filled. If none are filled, do not create the row (and thus ignore `month`).

**Scope:** Create and Edit. Both use the same `storeObjectivesAndActivities` → `storeActivities`. Edit’s `update()` also uses `updateReport`, `handleAccountDetails`, `handleOutlooks`, `updatePhotos`, `handleSpecificProjectData`, `handleUpdateAttachments`; only objectives/activities go through `storeActivities`.

### 1.1 File to change

- `app/Http/Controllers/Reports/Monthly/ReportController.php`  
  - Method: `storeActivities` (private).  
  - Approx. lines: 498–507 (the `$filled` block).

### 1.2 Exact change

**Find (existing):**

```php
        // Only store when the user has filled at least one activity field (month, summary, qual, inter, or "Add Other" activity text).
        $filled = (trim((string) ($month ?? '')) !== '')
            || (trim((string) ($summary ?? '')) !== '')
            || (trim((string) ($qual ?? '')) !== '')
            || (trim((string) ($inter ?? '')) !== '')
            || (
                trim((string) ($projectActivityId ?? '')) === ''
                && trim((string) ($activityText ?? '')) !== ''
            );
```

**Replace with:**

```php
        // Month is filled by JS (report-period-sync), not by user. Only store when at least
        // one user-filled field is present; otherwise ignore this activity (and its month).
        $filled = (trim((string) ($summary ?? '')) !== '')
            || (trim((string) ($qual ?? '')) !== '')
            || (trim((string) ($inter ?? '')) !== '')
            || (
                trim((string) ($projectActivityId ?? '')) === ''
                && trim((string) ($activityText ?? '')) !== ''
            );
```

Do **not** change:

- `if (! $filled) { continue; }`
- `$activityData` (including `'month' => $month` when we do store)
- The rest of `storeActivities`.

### 1.3 Testing — Phase 1

| # | Scenario | Steps | Expected |
|---|----------|-------|----------|
| 1.1 | Create: only `month` (JS), no user fields | Report month selected; leave all activity Summary / Qualitative / Outcomes empty; submit. | No rows in `DP_Activities` for those activities. |
| 1.2 | Create: one user field filled | Fill only Summary for one activity; submit. | One activity row with summary + month (if synced). Others with no user input: not stored. |
| 1.3 | Create: Add Other with only `activity` | Add Other, type activity name only; submit. | One activity row; `month` can be null or synced. |
| 1.4 | Edit: remove last user-filled content | Edit report; clear Summary, Qualitative, Outcomes for an activity that had only Summary; **Update Report**. | That activity is removed (not in `$keptActivityIds` → deleted). |
| 1.5 | Edit: keep user content | Edit; add Summary to an activity that had none; **Update Report**. | Activity is stored with summary (and month if present). |
| 1.6 | Edit: Save as Draft with only `month` | Edit; leave an activity with only `month` (no summary/qual/outcomes); **Save as Draft**. (Ensure objectives are present so `storeObjectivesAndActivities` runs.) | That activity is not in `$keptActivityIds` and is deleted. |

### 1.4 Success criteria

- [ ] Activities with only `month` (and no summary/qual/outcomes/Add-Other activity) are **not** stored on create.
- [ ] Activities with at least one of summary, qualitative, outcomes, or (Add Other + activity) **are** stored; `month` still saved when we store.
- [ ] Edit: clearing all user-filled content for an activity causes it to be deleted on save.

### 1.5 Rollback

Restore the original `$filled` line that includes  
`(trim((string) ($month ?? '')) !== '') ||`.

---

## Phase 2 — Model: Align `hasUserFilledData()` with User-Filled Definition

**Objective:** In view/export, “user-filled” should match the store logic: exclude `month`. Month-only rows (e.g. legacy) will not be shown.

**Scope:** View, PDF, DOC export (wherever `hasUserFilledData()` is used).

### 2.1 File to change

- `app/Models/Reports/Monthly/DPActivity.php`  
  - Method: `hasUserFilledData()`.

### 2.2 Exact change

**Find (existing):**

```php
    public function hasUserFilledData(): bool
    {
        if (trim((string) ($this->month ?? '')) !== '') {
            return true;
        }
        if (trim((string) ($this->summary_activities ?? '')) !== '') {
            return true;
        }
        if (trim((string) ($this->qualitative_quantitative_data ?? '')) !== '') {
            return true;
        }
        if (trim((string) ($this->intermediate_outcomes ?? '')) !== '') {
            return true;
        }
        // "Add Other Activity": project_activity_id empty and user-typed activity
        if (trim((string) ($this->project_activity_id ?? '')) === '' && trim((string) ($this->activity ?? '')) !== '') {
            return true;
        }

        return false;
    }
```

**Replace with:**

```php
    /**
     * Whether the user has filled at least one activity field.
     * month is JS-filled (report-period-sync), not user-filled; excluded from check.
     */
    public function hasUserFilledData(): bool
    {
        if (trim((string) ($this->summary_activities ?? '')) !== '') {
            return true;
        }
        if (trim((string) ($this->qualitative_quantitative_data ?? '')) !== '') {
            return true;
        }
        if (trim((string) ($this->intermediate_outcomes ?? '')) !== '') {
            return true;
        }
        // "Add Other Activity": project_activity_id empty and user-typed activity
        if (trim((string) ($this->project_activity_id ?? '')) === '' && trim((string) ($this->activity ?? '')) !== '') {
            return true;
        }

        return false;
    }
```

### 2.3 Testing — Phase 2

| # | Scenario | Steps | Expected |
|---|----------|-------|----------|
| 2.1 | View: legacy month-only activity | Report with an activity that has only `month` in DB (if such exists). | That activity is **not** shown in the view (filtered by `hasUserFilledData`). |
| 2.2 | View: activity with summary | Report with activity that has `summary_activities` (and optionally month). | Activity is shown. |
| 2.3 | PDF/DOC export | Export a report with mix of user-filled and month-only activities. | Only user-filled activities appear. |

### 2.4 Success criteria

- [ ] `hasUserFilledData()` returns `false` when only `month` is set.
- [ ] View and export exclude month-only activities.

### 2.5 Rollback

Re-add the `month` check at the top of `hasUserFilledData()`.

---

## Phase 3 — Frontend: Status Badge Excludes `month` (Create + Edit)

**Objective:** The Empty / In Progress / Complete badge reflects only **user-filled** fields. If only `month` is set (by JS), the badge stays **Empty**.

**Scope:** Create and Edit objectives partials.

### 3.1 Files to change

1. `resources/views/reports/monthly/partials/create/objectives.blade.php`  
   - Function: `updateActivityStatus` (in `<script>`).
2. `resources/views/reports/monthly/partials/edit/objectives.blade.php`  
   - Function: `updateActivityStatus` (in `<script>`).

### 3.2 Exact change — Create (`partials/create/objectives.blade.php`)

In the function `updateActivityStatus`, find and replace as follows.

**Find (existing):**

```javascript
    // Check if form is filled
    const monthSelect = form.querySelector('select[name^="month"]');
    const summaryTextarea = form.querySelector('textarea[name^="summary_activities"]');
    const dataTextarea = form.querySelector('textarea[name^="qualitative_quantitative_data"]');
    const outcomesTextarea = form.querySelector('textarea[name^="intermediate_outcomes"]');

    const month = monthSelect ? monthSelect.value : '';
    const summary = summaryTextarea ? summaryTextarea.value.trim() : '';
    const data = dataTextarea ? dataTextarea.value.trim() : '';
    const outcomes = outcomesTextarea ? outcomesTextarea.value.trim() : '';

    // Update status badge
    if (month && summary && data && outcomes) {
        statusBadge.textContent = 'Complete';
        statusBadge.classList.remove('bg-warning', 'bg-info');
        statusBadge.classList.add('bg-success');
    } else if (month || summary || data || outcomes) {
        statusBadge.textContent = 'In Progress';
        statusBadge.classList.remove('bg-warning', 'bg-success');
        statusBadge.classList.add('bg-info');
    } else {
        statusBadge.textContent = 'Empty';
        statusBadge.classList.remove('bg-success', 'bg-info');
        statusBadge.classList.add('bg-warning');
    }
```

**Replace with:**

```javascript
    // month is JS-filled (report-period-sync), not user-filled; exclude from status
    const summaryTextarea = form.querySelector('textarea[name^="summary_activities"]');
    const dataTextarea = form.querySelector('textarea[name^="qualitative_quantitative_data"]');
    const outcomesTextarea = form.querySelector('textarea[name^="intermediate_outcomes"]');

    const summary = summaryTextarea ? summaryTextarea.value.trim() : '';
    const data = dataTextarea ? dataTextarea.value.trim() : '';
    const outcomes = outcomesTextarea ? outcomesTextarea.value.trim() : '';

    // Update status badge (Complete = all 3 user-filled fields)
    if (summary && data && outcomes) {
        statusBadge.textContent = 'Complete';
        statusBadge.classList.remove('bg-warning', 'bg-info');
        statusBadge.classList.add('bg-success');
    } else if (summary || data || outcomes) {
        statusBadge.textContent = 'In Progress';
        statusBadge.classList.remove('bg-warning', 'bg-success');
        statusBadge.classList.add('bg-info');
    } else {
        statusBadge.textContent = 'Empty';
        statusBadge.classList.remove('bg-success', 'bg-info');
        statusBadge.classList.add('bg-warning');
    }
```

### 3.3 Exact change — Edit (`partials/edit/objectives.blade.php`)

Apply the **same** logic as in 3.2.

**Find (existing):**

```javascript
        // Check if form is filled
        const monthSelect = form.querySelector('select[name^="month"]');
        const summaryTextarea = form.querySelector('textarea[name^="summary_activities"]');
        const dataTextarea = form.querySelector('textarea[name^="qualitative_quantitative_data"]');
        const outcomesTextarea = form.querySelector('textarea[name^="intermediate_outcomes"]');

        const month = monthSelect ? monthSelect.value : '';
        const summary = summaryTextarea ? summaryTextarea.value.trim() : '';
        const data = dataTextarea ? dataTextarea.value.trim() : '';
        const outcomes = outcomesTextarea ? outcomesTextarea.value.trim() : '';

        // Update status badge
        if (month && summary && data && outcomes) {
            statusBadge.textContent = 'Complete';
            statusBadge.classList.remove('bg-warning', 'bg-info');
            statusBadge.classList.add('bg-success');
        } else if (month || summary || data || outcomes) {
            statusBadge.textContent = 'In Progress';
            statusBadge.classList.remove('bg-warning', 'bg-success');
            statusBadge.classList.add('bg-info');
        } else {
            statusBadge.textContent = 'Empty';
            statusBadge.classList.remove('bg-success', 'bg-info');
            statusBadge.classList.add('bg-warning');
        }
```

**Replace with:**

```javascript
        // month is JS-filled (report-period-sync), not user-filled; exclude from status
        const summaryTextarea = form.querySelector('textarea[name^="summary_activities"]');
        const dataTextarea = form.querySelector('textarea[name^="qualitative_quantitative_data"]');
        const outcomesTextarea = form.querySelector('textarea[name^="intermediate_outcomes"]');

        const summary = summaryTextarea ? summaryTextarea.value.trim() : '';
        const data = dataTextarea ? dataTextarea.value.trim() : '';
        const outcomes = outcomesTextarea ? outcomesTextarea.value.trim() : '';

        // Update status badge (Complete = all 3 user-filled fields)
        if (summary && data && outcomes) {
            statusBadge.textContent = 'Complete';
            statusBadge.classList.remove('bg-warning', 'bg-info');
            statusBadge.classList.add('bg-success');
        } else if (summary || data || outcomes) {
            statusBadge.textContent = 'In Progress';
            statusBadge.classList.remove('bg-warning', 'bg-success');
            statusBadge.classList.add('bg-info');
        } else {
            statusBadge.textContent = 'Empty';
            statusBadge.classList.remove('bg-success', 'bg-info');
            statusBadge.classList.add('bg-warning');
        }
```

(Remove `monthSelect` and `month` from the edit partial’s `updateActivityStatus` as well.)

### 3.4 Edit only: `addActivity` — add `project_activity_id` hidden for Add Other (recommended)

**Objective:** Align edit with create: Add Other in edit should submit `project_activity_id[obj][act]` with an empty value so the backend receives an explicit empty for that index.

**File:** `resources/views/reports/monthly/partials/edit/objectives.blade.php`  
**Function:** `addActivity` (in the template string for the new activity HTML).

**Find (inside the Add Other activity HTML, after the Activity textarea):**

```html
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Activity</label>
                                        <textarea name="activity[${objectiveIndex}][${activityIndex}]" class="form-control activity-field auto-resize-textarea" rows="2"></textarea>
                                    </div>
                                </div>
```

**Replace with:**

```html
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Activity</label>
                                        <textarea name="activity[${objectiveIndex}][${activityIndex}]" class="form-control activity-field auto-resize-textarea" rows="2"></textarea>
                                        <input type="hidden" name="project_activity_id[${objectiveIndex}][${activityIndex}]" value="">
                                    </div>
                                </div>
```

`reindexActivities` in edit already updates `project_activity_id` input names when the element exists, so after this change it will reindex correctly.

### 3.5 Testing — Phase 3

| # | Scenario | Steps | Expected |
|---|----------|-------|----------|
| 3.1 | Create: only report month selected | Select report month (JS syncs to activity month); do not fill Summary/Qualitative/Outcomes. | Badge = **Empty**. |
| 3.2 | Create: one user field | Fill only Summary. | Badge = **In Progress**. |
| 3.3 | Create: all three user fields | Fill Summary, Qualitative, Outcomes. | Badge = **Complete**. |
| 3.4 | Edit: same cases | Same on edit form. | Same as 3.1–3.3. |
| 3.5 | Add Other + reindex | Add Other, fill only activity name; run reindex. | Status uses summary/data/outcomes only; for Add Other, “Complete” would need activity — current logic does not require it; In Progress if any of the three is filled. |
| 3.6 | Edit: Add Other (after 3.4) | Edit; Add Other; ensure `project_activity_id` hidden in DOM; fill activity text; save. | Backend receives empty `project_activity_id`; activity is stored (Add Other branch). |

### 3.6 Success criteria

- [ ] When only `month` is set (by sync), badge = **Empty**.
- [ ] When at least one of summary/qualitative/outcomes is filled, badge = **In Progress**.
- [ ] When all three are filled, badge = **Complete**.
- [ ] Create and Edit behave the same.
- [ ] Edit Add Other includes `project_activity_id` hidden (if 3.4 implemented).

### 3.7 Rollback

- **updateActivityStatus (3.2, 3.3):** Restore `monthSelect`, `month`, and the original `if (month && summary && data && outcomes)` / `else if (month || summary || data || outcomes)` logic in both create and edit partials.
- **Edit addActivity (3.4):** Remove the added `<input type="hidden" name="project_activity_id[${objectiveIndex}][${activityIndex}]" value="">` line.

---

## Phase 4 — Optional: Clear `month` on Submit When No User-Filled Content

**Objective:** Defence-in-depth: before submit, for each activity with no user-filled content, clear or omit `month` so the server never receives a “user-less” month.

**Scope:** Create and Edit forms that include the objectives partial.

### 4.1 Where to implement

- Either in the objectives partial (submit handler for the form that contains it), or
- In the parent view’s form `submit` listener (ReportAll, edit, etc.).

Needs to run **before** the form is submitted (in a `submit` handler that runs prior to the default submit, and only continues with `form.submit()` or by not calling `preventDefault` after clearing).

### 4.2 Logic (pseudocode)

```
On form submit (first handler, before default):
  For each .activity-card in #objectives-container (or equivalent):
    summary = trim( summary_activities[obj][act][1] )
    qual    = trim( qualitative_quantitative_data[obj][act][1] )
    inter   = trim( intermediate_outcomes[obj][act][1] )
    project_activity_id = project_activity_id[obj][act]
    activity_text = activity[obj][act]
    is_add_other = (project_activity_id is empty)
    has_activity = (is_add_other and trim(activity_text) !== '')

    user_filled = (summary !== '' || qual !== '' || inter !== '' || has_activity)

    If NOT user_filled:
      Find the month select for this activity: select[name="month[obj][act]"]
      Set select.value = '' (or removeAttribute('name') so it is not submitted)
  Then allow form to submit (do not preventDefault, or call form.submit())
```

### 4.3 Files to change

- **Create:** parent form `#createReportForm` (ReportAll) or `#reportForm` (ReportCommonForm). Attach `submit` in the view or in the objectives partial; ensure it runs for both **Save Report** and **Save as Draft** (draft adds `save_as_draft=1` and submits the same form).
- **Edit:** `resources/views/reports/monthly/edit.blade.php`, form `#editReportForm`. Attach `submit` so it runs for both **Update Report** and **Save as Draft** (`#saveDraftBtn` adds `save_as_draft=1` and submits the same form).

Alternatively, a small shared script included by both create and edit that:

1. Finds the report form (`#createReportForm`, `#reportForm`, or `#editReportForm`).
2. On `submit`, runs the clear logic for all activities (`.activity-card` in `#objectives-container`), then allows submission.

### 4.4 Testing — Phase 4

- Submit create with some activities that have only `month` (and no user fields): inspect request or log; those `month` keys should be absent or `""`.
- Backend should still not store those activities (Phase 1). This phase only adds a client-side safeguard.

### 4.5 Success criteria

- [ ] For activities with no user-filled fields, `month` is not sent or is sent empty.

### 4.6 Rollback

Remove the submit handler that clears `month`.

---

## Phase 5 — Documentation and Verification

**Objective:** Record what was implemented and run a short regression suite.

### 5.1 Documentation

- [x] In [Activity_Store_Only_When_User_Filled_And_Ignore_Month_Proposal.md](./Activity_Store_Only_When_User_Filled_And_Ignore_Month_Proposal.md): add an **Implementation status** section, e.g.  
  - Phase 1: Done (date, branch/tag)  
  - Phase 2: Done  
  - Phase 3: Done  
  - Phase 4: Skipped / Done  
- [x] In this file: optional **Changelog** at the bottom with phases and date.

### 5.2 Verification / Regression

| # | Area | Check |
|---|------|-------|
| 5.1 | Create report | Create with mixed activities (some only month, some with user data); verify DB and view. |
| 5.2 | Edit: Update Report | Edit; add/remove user content; **Update Report**; verify activities stored/deleted as per rules. |
| 5.3 | Edit: Save as Draft | Edit; leave an activity with only `month`; **Save as Draft**; verify that activity is deleted when objectives are present. |
| 5.4 | Edit: Add Other | Edit; **Add Other**; fill only activity text (and, if 3.4 done, ensure `project_activity_id` hidden in DOM); save; verify stored. |
| 5.5 | View report | View; only user-filled activities shown. |
| 5.6 | PDF export | PDF includes only user-filled activities. |
| 5.7 | DOC export | Same as PDF. |
| 5.8 | report-period-sync | Change report month; activity month selects still sync on create and edit; storing still depends on user fields only. |
| 5.9 | Add Other (create) | Create; Add Other with only activity text; stored; badge and view behave as expected. |

### 5.3 Optional: Unit / Feature tests

- **`ReportController::storeActivities`:**  
  - Request with `month` set and summary/qual/inter empty for an activity → that activity should not be in `DP_Activities`.  
  - Request with `summary` set (and optionally month) → activity should be in `DP_Activities`.
- **`DPActivity::hasUserFilledData()`:**  
  - Model with only `month` → `false`.  
  - Model with `summary_activities` → `true`.

---

## Changelog (to fill while implementing)

| Date | Phase | Status | Notes |
|------|-------|--------|-------|
| 2026-01-21 | 1    | ✅ Done | Backend `storeActivities` — exclude `month` from `$filled` |
| 2026-01-21 | 2    | ✅ Done | `DPActivity::hasUserFilledData` — exclude `month` |
| 2026-01-21 | 3    | ✅ Done | 3.2 create `updateActivityStatus`; 3.3 edit `updateActivityStatus`; 3.4 edit `addActivity` (`project_activity_id` hidden for Add Other) |
| 2026-01-21 | 4    | ⬜ Skipped | Clear month on submit (optional hardening) |
| 2026-01-21 | 5    | ✅ Done | Docs: Changelog + Implementation status in Proposal |

---

## Quick Reference: User-Filled vs JS-Filled

| Field | Filled by | Counts for “store activity”? | Counts for “Complete” badge? |
|-------|-----------|------------------------------|------------------------------|
| `month` | JS (`report-period-sync`) | **No** | **No** (after Phase 3) |
| `summary_activities` | User | Yes | Yes |
| `qualitative_quantitative_data` | User | Yes | Yes |
| `intermediate_outcomes` | User | Yes | Yes |
| `activity` (Add Other only) | User | Yes | — (not in badge today) |
