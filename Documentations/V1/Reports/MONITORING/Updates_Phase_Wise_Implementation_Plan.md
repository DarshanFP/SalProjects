# Phase-Wise Implementation Plan — Refined Activity Monitoring (Updates)

**Source:** `Documentations/V1/Reports/MONITORING/Updates.md` (§4 Refined Design, §8–9)  
**Scope:** Refined Activity Monitoring: inline badges (SCHEDULED – REPORTED / NOT SCHEDULED – REPORTED) next to each reported activity; **remove** per‑objective Activity Monitoring block; **Activity Monitoring** section at the bottom (only “scheduled but not reported”, objective‑wise, before Add Comment).  
**Version:** 1.0  
**Location:** `Documentations/V1/Reports/MONITORING/Updates_Phase_Wise_Implementation_Plan.md`

---

## Summary

| Phase | Name | Status | Est. effort |
|-------|------|--------|-------------|
| **1** | Service: `getActivitiesScheduledButNotReportedGroupedByObjective` and `getReportedActivityScheduleStatus` | ✅ Done | 1–1.5 h |
| **2** | Controller: pass data to view | ✅ Done | 0.5 h |
| **3** | Objectives partial: remove per‑objective block, add inline badges | ✅ Done | 1 h |
| **4** | Activity Monitoring partial and include in `show.blade.php` | ✅ Done | 1 h |
| **5** | Integration, testing, and documentation | ✅ Done | 0.5–1 h |

**Total (refined design only):** ~4–5 h.

---

## Phase 1 — Service: `getActivitiesScheduledButNotReportedGroupedByObjective` and `getReportedActivityScheduleStatus`

**Updates.md:** §4.4

### 1.1 Goals

- **Grouped “scheduled but not reported”:** Provide data for the **Activity Monitoring** section: activities scheduled for the report month but not reported, **grouped by project objective**. Include objectives entirely missing from the report.
- **Inline badges:** For each reported `DPActivity` (with `hasUserFilledData()`), provide `scheduled_reported` or `not_scheduled_reported` keyed by `activity_id`.

### 1.2 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 1.1 | **`getActivitiesScheduledButNotReportedGroupedByObjective(DPReport $report): array`** | `app/Services/ReportMonitoringService.php` | Reuse logic from `getActivitiesScheduledButNotReported` (iterate `project->objectives`, `objective->activities`, `ProjectTimeframe` with `month = reportMonth`, `is_active = 1`; exclude when a `DPActivity` with that `project_activity_id` and `hasUserFilledData()` exists in the report). **Return:** `[ ['objective' => '...', 'objective_id' => '...', 'activities' => [ ['activity' => '...', 'activity_id' => '...'] ] ], ... ]` in project objective order. If an objective has no `DPObjective` in the report, use the **project** objective text. |
| 1.2 | **`getReportedActivityScheduleStatus(DPReport $report): array`** | `app/Services/ReportMonitoringService.php` | For each `DPActivity` in `report->objectives->activities` with `hasUserFilledData()`, determine: **scheduled_reported** if `project_activity_id` is non‑empty and a `ProjectTimeframe` exists for that `project_activity_id` with `month = reportMonth` and `is_active = 1`; otherwise **not_scheduled_reported** (covers ad‑hoc and “not scheduled for report month”). **Return:** `[ activity_id => 'scheduled_reported'|'not_scheduled_reported', ... ]`. Can be derived from `getMonitoringPerObjective` or implemented directly. |

### 1.3 Dependencies

- `$report->project` with `objectives.activities.timeframes` loaded (already in `show()`).
- `$report->objectives` with `activities` loaded; each `DPActivity` has `hasUserFilledData()`.
- `ReportMonitoringService::getReportMonth($report)` (or `(int) \Carbon\Carbon::parse($report->report_month_year)->format('n')`).

### 1.4 Output

- `getActivitiesScheduledButNotReportedGroupedByObjective` returns an array of `{ objective, objective_id, activities: [{ activity, activity_id }] }`.
- `getReportedActivityScheduleStatus` returns `[ activity_id => 'scheduled_reported'|'not_scheduled_reported' ]`.

---

## Phase 2 — Controller: Pass Data to View

**Updates.md:** §9 (Controller)

### 2.1 Goals

- In `ReportController::show()`, call the two new (or adapted) methods and pass the results into the view.
- Ensure `$report->setRelation('project', $project)` and `project->objectives.activities.timeframes` are loaded before calling the service (already done).

### 2.2 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 2.1 | In `show()`, after existing monitoring setup (e.g. after `getMonitoringPerObjective` or in the same `try` block): call `getActivitiesScheduledButNotReportedGroupedByObjective($report)` and `getReportedActivityScheduleStatus($report)` | `app/Http/Controllers/Reports/Monthly/ReportController.php` | Handle `\Throwable` like existing monitoring; on failure, pass `[]` and `[]`. |
| 2.2 | Add to `compact()` (or view `compact`) passed to `view('reports.monthly.show', ...)`: **`activitiesScheduledButNotReportedGroupedByObjective`** and **`reportedActivityScheduleStatus`** | `ReportController.php` | Use `$activitiesScheduledButNotReportedGroupedByObjective ?? []` and `$reportedActivityScheduleStatus ?? []` so view never sees undefined. |

### 2.3 Dependencies

- Phase 1 done (both service methods exist).

### 2.4 Output

- View receives `activitiesScheduledButNotReportedGroupedByObjective` (array) and `reportedActivityScheduleStatus` (array keyed by `activity_id`).

---

## Phase 3 — Objectives Partial: Remove Per‑Objective Block, Add Inline Badges

**Updates.md:** §4.1, §4.2; §9 (Objectives partial)

### 3.1 Goals

- **Remove** the current per‑objective “Activity Monitoring” block (SCHEDULED – REPORTED, SCHEDULED – NOT REPORTED, NOT SCHEDULED – REPORTED).
- **Add** an inline badge (**SCHEDULED – REPORTED** or **NOT SCHEDULED – REPORTED**) next to each reported activity’s heading. Only for `provincial` / `coordinator` when `report->status` is `submitted_to_provincial` or `forwarded_to_coordinator`.

### 3.2 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 3.1 | **Remove** the block that starts with `@php $mon = ($monitoringPerObjective ?? [])[$objective->objective_id] ?? ...` and the `@if($showByStatus && $showByRole && $hasAny)` div that contains “Activity Monitoring”, “SCHEDULED – REPORTED”, “SCHEDULED – NOT REPORTED”, “NOT SCHEDULED – REPORTED” | `resources/views/reports/monthly/partials/view/objectives.blade.php` | Delete the whole block (from the `@php` to the closing `</div>` and `@endif`). |
| 3.2 | **Add** an inline badge next to each activity’s heading. Locate the activity heading, e.g. `<h6 class="activity-block-heading ...">Activity {{ $loop->iteration }} of {{ $loop->count }}: {{ $activity->activity ?? '—' }}</h6>`. | `objectives.blade.php` | After or inside the `<h6>`, add a `<span class="badge ...">` with: **SCHEDULED – REPORTED** (e.g. `bg-success`) or **NOT SCHEDULED – REPORTED** (e.g. `bg-warning text-dark`) using `($reportedActivityScheduleStatus ?? [])[$activity->activity_id] ?? 'not_scheduled_reported'`. |
| 3.3 | **Visibility:** Render the badge only when `in_array(auth()->user()->role ?? '', ['provincial','coordinator'])` and `in_array($report->status ?? '', ['submitted_to_provincial','forwarded_to_coordinator'])` | `objectives.blade.php` | Wrap the badge in `@if(...) ... @endif`. |
| 3.4 | In **`show.blade.php`**, add `'reportedActivityScheduleStatus' => $reportedActivityScheduleStatus ?? []` to the `@include` for `objectives` | `resources/views/reports/monthly/show.blade.php` | The objectives `@include` currently passes `['report' => $report, 'monitoringPerObjective' => $monitoringPerObjective ?? []]`; add `'reportedActivityScheduleStatus' => $reportedActivityScheduleStatus ?? []` so the partial has it in scope. |

### 3.3 Dependencies

- Phase 2 done (`reportedActivityScheduleStatus` in controller `compact()` and thus in `show` view). The objectives `@include` must also pass `reportedActivityScheduleStatus` (task 3.4) because it uses an explicit variable array.

### 3.4 Output

- No per‑objective Activity Monitoring block.
- Each reported activity shows a small badge: SCHEDULED – REPORTED or NOT SCHEDULED – REPORTED, only for provincial/coordinator when status is under review.

---

## Phase 4 — Activity Monitoring Partial and Include in `show.blade.php`

**Updates.md:** §4.3, §9 (New partial, View)

### 4.1 Goals

- Create **`activity_monitoring.blade.php`**: section that lists **only** “scheduled but not reported” activities, **grouped objective‑wise**. Include the provincial note. Show only when the list is **non‑empty** and when role/status allows.
- In **`show.blade.php`**, include this partial **before the Add Comment section** (i.e. before `@include('reports.monthly.partials.comments')`).

### 4.2 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 4.1 | **Create** `resources/views/reports/monthly/partials/view/activity_monitoring.blade.php` | New file | **Content:** (a) `@php`: `$grouped = $activitiesScheduledButNotReportedGroupedByObjective ?? [];`; `$showStatuses = ['submitted_to_provincial','forwarded_to_coordinator'];`; `$showByStatus = in_array($report->status ?? '', $showStatuses);`; `$showByRole = in_array(auth()->user()->role ?? '', ['provincial','coordinator']);`; `$hasAny = count($grouped) > 0;` (b) `@if($showByStatus && $showByRole && $hasAny)`: card/block “Activity Monitoring” (or “Scheduled for this month but not reported”). (c) For each item in `$grouped`: **Objective:** `objective` text. Under it, `<ul>` or list of `activities[].activity`. (d) Note: “Check ‘What did not happen’ / ‘Why not’ for the relevant objective (if in the report), or consider reverting so the executor can add the objective and explain.” (e) `@endif`. |
| 4.2 | **In `show.blade.php`**, add `@include('reports.monthly.partials.view.activity_monitoring')` **before** `@include('reports.monthly.partials.comments')` | `resources/views/reports/monthly/show.blade.php` | Place it after the Attachments include and before the `</div></div>` that closes the main content column, i.e. immediately before the `card-footer` (Download PDF) or, if preferred, after the `card-footer` and before `@include('reports.monthly.partials.comments')`. Per Updates.md, “before Add Comment” is the requirement; both satisfy it. Prefer: **after Attachments, inside the main content column**, so it appears in the main scroll before Download and Comments. |

### 4.3 Dependencies

- Phase 2 done (`activitiesScheduledButNotReportedGroupedByObjective` in view).

### 4.4 Output

- New partial `activity_monitoring.blade.php` that renders only when there is at least one “scheduled but not reported” activity and when provincial/coordinator and status under review.
- `show.blade.php` includes it in the correct position (before Add Comment).

---

## Phase 5 — Integration, Testing, and Documentation

### 5.1 Integration

| # | Task | Notes |
|---|------|-------|
| 5.1 | Verify `objectives.blade.php` receives `$reportedActivityScheduleStatus` | Done in Phase 3.4: `'reportedActivityScheduleStatus' => $reportedActivityScheduleStatus ?? []` added to the objectives `@include` in `show.blade.php`. |
| 5.2 | Verify `activity_monitoring.blade.php` receives `$activitiesScheduledButNotReportedGroupedByObjective` and `$report` | The partial is included without an explicit variable array; `$report` and `$activitiesScheduledButNotReportedGroupedByObjective` are in the `show` view’s `compact()`, so they are in scope. Confirm. |
| 5.3 | (Optional) Remove or simplify controller logic that builds `$monitoringPerObjective` if it is **only** used by the old per‑objective block | If `getMonitoringPerObjective` is still used elsewhere (e.g. future summary), keep it. If it was only for the removed block, it can be removed in a later cleanup; not required for this plan. |

### 5.2 Testing

| # | Task | Notes |
|---|------|-------|
| 5.4 | **Inline badges:** Report with mixed: (a) activity scheduled for report month and reported with user‑filled data → **SCHEDULED – REPORTED**; (b) ad‑hoc or activity not scheduled for report month → **NOT SCHEDULED – REPORTED**. View as provincial/coordinator, status `submitted_to_provincial` or `forwarded_to_coordinator`. | Badges appear. As executor or other status, badges hidden. |
| 5.5 | **Activity Monitoring section:** (a) Report where **all** scheduled activities are reported → section **hidden**. (b) Report where one or more scheduled activities are **not** reported (or whole objective missing) → section **visible**, list grouped by objective. (c) Project with no objectives/timeframes → grouped list empty, section hidden. | |
| 5.6 | **Per‑objective block removed:** No block with “SCHEDULED – REPORTED”, “SCHEDULED – NOT REPORTED”, “NOT SCHEDULED – REPORTED” under each objective. | Only inline badges and the bottom Activity Monitoring section. |
| 5.7 | **Placement:** Activity Monitoring section appears **before** the Add Comment block when it is shown. | |

### 5.3 Documentation

| # | Task | Notes |
|---|------|-------|
| 5.8 | In **`Updates.md`**, add a short **Implementation status** (e.g. after §10 or in §4): “Refined design (§4): Phase‑wise plan in `Updates_Phase_Wise_Implementation_Plan.md`. Status: Phase 1 ⏳, 2 ⏳, …” and update as phases complete. | |
| 5.9 | In **`Updates_Phase_Wise_Implementation_Plan.md`** (this file), update the Summary table **Status** column as each phase is done. | ✅ when complete. |

---

## File Checklist

### New files

| File | Phase |
|------|-------|
| `resources/views/reports/monthly/partials/view/activity_monitoring.blade.php` | 4 |

### Modified files

| File | Phase | Changes |
|------|-------|---------|
| `app/Services/ReportMonitoringService.php` | 1 | Add `getActivitiesScheduledButNotReportedGroupedByObjective`, `getReportedActivityScheduleStatus` |
| `app/Http/Controllers/Reports/Monthly/ReportController.php` | 2 | In `show()`: call the two methods; add `activitiesScheduledButNotReportedGroupedByObjective`, `reportedActivityScheduleStatus` to `compact()` |
| `resources/views/reports/monthly/partials/view/objectives.blade.php` | 3 | Remove per‑objective Activity Monitoring block; add inline badge (SCHEDULED – REPORTED / NOT SCHEDULED – REPORTED) per reported activity; pass `reportedActivityScheduleStatus` in `@include` if not in scope |
| `resources/views/reports/monthly/show.blade.php` | 3, 4 | **Phase 3:** add `'reportedActivityScheduleStatus' => $reportedActivityScheduleStatus ?? []` to the objectives `@include`. **Phase 4:** add `@include('reports.monthly.partials.view.activity_monitoring')` before `@include('reports.monthly.partials.comments')` (after Attachments, inside main content). Note: `reportedActivityScheduleStatus` is added to the controller’s `compact()` in Phase 2. |
| `Documentations/V1/Reports/MONITORING/Updates.md` | 5 | Implementation status re. refined design and this plan |
| `Documentations/V1/Reports/MONITORING/Updates_Phase_Wise_Implementation_Plan.md` | 5 | Update Status in Summary table |

---

## Execution Order

1. **Phase 1** — Service: `getActivitiesScheduledButNotReportedGroupedByObjective`, `getReportedActivityScheduleStatus`.
2. **Phase 2** — Controller: call both, pass `activitiesScheduledButNotReportedGroupedByObjective`, `reportedActivityScheduleStatus` to the view.
3. **Phase 3** — Objectives partial: remove per‑objective block; add inline badges; ensure `reportedActivityScheduleStatus` is passed/available.
4. **Phase 4** — Create `activity_monitoring.blade.php`; include it in `show.blade.php` before Add Comment.
5. **Phase 5** — Integration (pass vars into objectives `@include` if needed), manual testing, documentation updates.

---

## References

- `Documentations/V1/Reports/MONITORING/Updates.md` (§4 Refined Design, §8–9)
- `app/Services/ReportMonitoringService.php`
- `app/Http/Controllers/Reports/Monthly/ReportController.php` (`show`)
- `resources/views/reports/monthly/show.blade.php`
- `resources/views/reports/monthly/partials/view/objectives.blade.php`

---

**End of Updates Phase-Wise Implementation Plan**
