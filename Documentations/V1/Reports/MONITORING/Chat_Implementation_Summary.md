# Implementation Summary — Chat Session

This document summarizes all implementations from the chat session, covering **Activity store‑when‑filled**, **Activity Monitoring** (objective‑wise, inline badges, scheduled‑but‑not‑reported), **Photo grouping by activity**, and **Export DOC** changes.

---

## 1. Activity: Store Only When User‑Filled (Month Excluded)

### 1.1 Rationale

- **Month** is filled by JavaScript (e.g. report‑period‑sync), not by the user. It is excluded from the “user‑filled” check.
- Only activities where the user has entered at least one of: **Summary of Activities**, **Qualitative & Quantitative Data**, **Intermediate Outcomes**, or (for “Add Other Activity”) **Activity** text are stored.
- Empty activity rows are not persisted; on view/export only activities with user‑filled data are shown.

### 1.2 `DPActivity::hasUserFilledData()`

**File:** `app/Models/Reports/Monthly/DPActivity.php`

- **Month is excluded** from the check (JS‑filled).
- Returns `true` if any of the following is non‑empty (after `trim`):
  - `summary_activities`
  - `qualitative_quantitative_data`
  - `intermediate_outcomes`
  - Or: `project_activity_id` is empty **and** `activity` is non‑empty (Add Other Activity).

### 1.3 `ReportController::storeActivities()`

**File:** `app/Http/Controllers/Reports/Monthly/ReportController.php`

- `$filled` no longer includes `month`.
- `$filled = (summary) || (qual) || (inter) || (project_activity_id empty && activity)`.
- Only create/update when `$filled`; collect `$keptActivityIds`; delete activities not in `$keptActivityIds`.

### 1.4 `MonthlyDevelopmentProjectController::store()`

**File:** `app/Http/Controllers/Reports/Monthly/MonthlyDevelopmentProjectController.php`

- Only creates an activity when at least one of `month`, `summary_activities`, `qualitative_quantitative_data`, `intermediate_outcomes` is non‑empty.

### 1.5 View / Export: Only Show User‑Filled Activities

- **ReportController::show**, **ExportReportController::downloadPdf**, **ExportReportController::downloadDoc**, **MonthlyDevelopmentProjectController::show**
- After loading the report, each objective’s `activities` relation is filtered:
  - `$objective->setRelation('activities', $objective->activities->filter(fn ($a) => $a->hasUserFilledData())->values())`

---

## 2. Activity Monitoring: Refined Design

### 2.1 Inline Badge on Each Activity (Objectives View)

**File:** `resources/views/reports/monthly/partials/view/objectives.blade.php`

- Each activity block has a heading:  
  **Activity X of Y: {activity name}**
- For **provincial** and **coordinator**, when status is `submitted_to_provincial` or `forwarded_to_coordinator`, an inline badge is shown:
  - **SCHEDULED – REPORTED** (`bg-success`) when the reported activity is scheduled for the report month.
  - **NOT SCHEDULED – REPORTED** (`bg-warning`) when it is ad‑hoc or not scheduled for the report month.
- Badge is driven by `$reportedActivityScheduleStatus[$activity->activity_id]` (`'scheduled_reported'` or `'not_scheduled_reported'`).

### 2.2 `ReportMonitoringService::getReportedActivityScheduleStatus()`

**File:** `app/Services/ReportMonitoringService.php`

- Returns `array<string, 'scheduled_reported'|'not_scheduled_reported'>` keyed by `DPActivity.activity_id`.
- For each reported `DPActivity` with `hasUserFilledData()`:
  - **scheduled_reported**: `project_activity_id` has a `ProjectTimeframe` for the report month with `is_active=1`.
  - **not_scheduled_reported**: ad‑hoc (`project_activity_id` empty) or no such timeframe.

### 2.3 “Scheduled but Not Reported” Block (Objective‑Wise)

**File:** `resources/views/reports/monthly/partials/view/activity_monitoring.blade.php`

- Shown only when status is `submitted_to_provincial` or `forwarded_to_coordinator` and role is `provincial` or `coordinator`.
- Renders a single card: **Activity Monitoring — Scheduled for this month but not reported**.
- Lists, **per objective**, project activities that are scheduled for the report month but have **no** reported `DPActivity` with `hasUserFilledData()` and matching `project_activity_id`.
- Uses `$activitiesScheduledButNotReportedGroupedByObjective`.
- Note for provincial: *Check “What did not happen” / “Why not” for the relevant objective, or consider reverting so the executor can add the objective and explain.*

### 2.4 `ReportMonitoringService::getActivitiesScheduledButNotReportedGroupedByObjective()`

**File:** `app/Services/ReportMonitoringService.php`

- Returns  
  `array<int, array{ objective, objective_id, activities: array<{ activity, activity_id }> }>`.
- For each **project** objective, collects project activities that:
  - Have a `ProjectTimeframe` for the report month with `is_active=1`, and
  - Are **not** in the set of reported `project_activity_id` (from `DPActivity` with `hasUserFilledData()`).
- Includes objectives that exist only in the project (entirely missing from the report) when they have such activities.

### 2.5 Controller and View Wiring

**ReportController::show()**

- Defines and passes:
  - `$monitoringPerObjective` (from `getMonitoringPerObjective`) — retained for possible reuse
  - `$activitiesScheduledButNotReportedGroupedByObjective`
  - `$reportedActivityScheduleStatus`
- `compact()` includes these for the view.

**show.blade.php**

- `@include('reports.monthly.partials.view.objectives', [..., 'reportedActivityScheduleStatus' => $reportedActivityScheduleStatus ?? []])`
- After Attachments:  
  `@include('reports.monthly.partials.view.activity_monitoring')`

The former **objectives_activity_monitoring** partial (single block for scheduled‑not‑reported, reported‑not‑scheduled, ad‑hoc) is **no longer included**; it is replaced by inline badges on each activity and the **activity_monitoring** partial for “scheduled but not reported” only.

---

## 3. Photos: Group by `activity_id`

### 3.1 `ReportController::updatePhotos()`

**File:** `app/Http/Controllers/Reports/Monthly/ReportController.php`

- **Group key** for photo descriptions and `photo_activity_id` is now `$photo->activity_id ?? '_unassigned_'` (replacing `description`).
- Aligns with the edit form: one section per activity.

### 3.2 `ReportController::edit()` — `groupedPhotos`

**File:** `app/Http/Controllers/Reports/Monthly/ReportController.php`

- Photos are grouped by `activity_id` (or `'_unassigned_'` when `activity_id` is null).
- `$ordered` is built by:
  1. Iterating `report->objectives` and their `activities`; for each activity with photos, appending  
     `['groupKey' => $aid, 'photos' => ..., 'activityLabel' => 'Objective X – {activity}']`.
  2. Adding **orphan** groups: `activity_id` present in photos but no longer in the report’s objectives; label e.g. *Activity (removed from report)* or from `DPActivity` if found.
  3. Appending **Unassigned** (`groupKey = '_unassigned_'`) when present.
- `$groupedPhotos = $ordered` is passed to the edit view.

---

## 4. Export DOC: Activity Heading

**File:** `app/Http/Controllers/Reports/Monthly/ExportReportController.php`

- In `addObjectivesSection()`, each activity block heading is:
  - **Activity X of Y: {activity name}**  
  (e.g. `Activity 1 of 3: Workshop on hygiene`).
- Uses `$actCount = $objective->activities->count()` and `$activity->activity`.

---

## 5. File Checklist

| File | Changes |
|------|---------|
| `app/Models/Reports/Monthly/DPActivity.php` | `hasUserFilledData()`: month excluded. |
| `app/Http/Controllers/Reports/Monthly/ReportController.php` | `storeActivities`: `$filled` without month; `updatePhotos`: `groupKey = activity_id`; `edit`: `groupedPhotos` by `activity_id` and `$ordered`; `show`: `monitoringPerObjective`, `activitiesScheduledButNotReportedGroupedByObjective`, `reportedActivityScheduleStatus`; filter activities by `hasUserFilledData` in `show`. |
| `app/Http/Controllers/Reports/Monthly/MonthlyDevelopmentProjectController.php` | `store`: only create activity when at least one field filled; `show`: filter activities by `hasUserFilledData`. |
| `app/Http/Controllers/Reports/Monthly/ExportReportController.php` | `downloadPdf`, `downloadDoc`: filter activities by `hasUserFilledData`; `addObjectivesSection`: Activity X of Y: name. |
| `app/Services/ReportMonitoringService.php` | `getActivitiesScheduledButNotReportedGroupedByObjective()`, `getReportedActivityScheduleStatus()`, `getMonitoringPerObjective()` (retained). |
| `resources/views/reports/monthly/show.blade.php` | Objectives include with `reportedActivityScheduleStatus`; `@include` `activity_monitoring` after Attachments. |
| `resources/views/reports/monthly/partials/view/objectives.blade.php` | Activity block heading “Activity X of Y: {name}” and inline badge (SCHEDULED – REPORTED / NOT SCHEDULED – REPORTED); per‑objective monitoring block removed. |
| `resources/views/reports/monthly/partials/view/activity_monitoring.blade.php` | **New.** “Scheduled for this month but not reported” by objective, using `$activitiesScheduledButNotReportedGroupedByObjective`. |

---

## 6. Visibility and Status

- **Inline badges** and **activity_monitoring** block:  
  - Roles: `provincial`, `coordinator`.  
  - Statuses: `submitted_to_provincial`, `forwarded_to_coordinator`.

---

## 7. Related Documentation

- `Updates.md` (§4.3 — refined activity monitoring design)
- `Activity_Store_Only_When_User_Filled_Phase_Wise_Implementation_Plan.md` (create flow)
- `Activity_Store_Only_When_User_Filled_And_Ignore_Month_Proposal.md`
- `Provincial_Monthly_Report_Monitoring_Guide.md`
- `Provincial_Monthly_Report_Monitoring_Implementation_Plan.md`

---

**End of Chat Implementation Summary**
