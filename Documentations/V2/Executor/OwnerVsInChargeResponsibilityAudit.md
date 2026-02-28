# Executor Dashboard – Owner vs In-Charge Responsibility Audit

**Date:** 2026-02-18  
**Scope:** Structural audit and redesign documentation. No implementation. All findings traced to code.

---

## 1. Current Scope Implementation

### 1.1 Merged Queries (Owner OR In-Charge)

All locations use `where('user_id', $user->id)->orWhere('in_charge', $user->id)` or equivalent. **Owner and in-charge are merged in every case.**

| # | File | Method / Location | Exact Query Logic | Merged? |
|---|------|-------------------|-------------------|---------|
| 1 | `app/Services/ProjectQueryService.php` | `getProjectsForUserQuery` (line 26–28) | `$query->where(function ($q) use ($user) { $q->where('user_id', $user->id)->orWhere('in_charge', $user->id); })` | Yes |
| 2 | `app/Services/ProjectQueryService.php` | `getProjectIdsForUser` (line 42) | Delegates to `getProjectsForUserQuery` | Yes |
| 3 | `app/Services/ProjectQueryService.php` | `getProjectsForUser` | Delegates to `getProjectsForUserQuery` | Yes |
| 4 | `app/Services/ProjectQueryService.php` | `getProjectsForUserByStatus` | Delegates to `getProjectsForUserQuery` | Yes |
| 5 | `app/Services/ProjectQueryService.php` | `getApprovedProjectsForUser` | Via `getProjectsForUserByStatus` | Yes |
| 6 | `app/Services/ProjectQueryService.php` | `getEditableProjectsForUser` | Via `getProjectsForUserByStatus` | Yes |
| 7 | `app/Services/ProjectQueryService.php` | `getRevertedProjectsForUser` | Via `getProjectsForUserByStatus` | Yes |
| 8 | `app/Services/ProjectQueryService.php` | `getTrashedProjectsQuery` (line 195–197) | `$q->where('user_id', $user->id)->orWhere('in_charge', $user->id)` | Yes |
| 9 | `app/Services/ProjectQueryService.php` | `getProjectsForUsersQuery` (line 80–82) | `whereIn('user_id', $userIds)->orWhereIn('in_charge', $userIds)` | Yes |
| 10 | `app/Http/Controllers/ExecutorController.php` | `executorDashboard` | Uses `getProjectsForUserQuery`, `getApprovedProjectsForUser`, `getProjectIdsForUser` | Yes |
| 11 | `app/Http/Controllers/ExecutorController.php` | `reportList` | `$projectIds = ProjectQueryService::getProjectIdsForUser($user)` | Yes |
| 12 | `app/Http/Controllers/ExecutorController.php` | `getActionItems` | `getProjectIdsForUser`, `getRevertedProjectsForUser`, `getApprovedProjectsForUser` | Yes |
| 13 | `app/Http/Controllers/ExecutorController.php` | `getProjectsRequiringAttention` | `getEditableProjectsForUser` | Yes |
| 14 | `app/Http/Controllers/ExecutorController.php` | `getReportsRequiringAttention` | `getProjectIdsForUser` | Yes |
| 15 | `app/Http/Controllers/ExecutorController.php` | `getReportStatusSummary` | `getProjectIdsForUser` | Yes |
| 16 | `app/Http/Controllers/ExecutorController.php` | `getUpcomingDeadlines` | `getApprovedProjectsForUser` | Yes |
| 17 | `app/Http/Controllers/ExecutorController.php` | `getChartData` | `getProjectIdsForUser`, `getApprovedProjectsForUser` | Yes |
| 18 | `app/Http/Controllers/ExecutorController.php` | `getReportChartData` | `getProjectIdsForUser` | Yes |
| 19 | `app/Http/Controllers/ExecutorController.php` | `getQuickStats` | `getProjectsForUserQuery`, `getApprovedProjectsForUser`, `getProjectIdsForUser` | Yes |
| 20 | `app/Services/ActivityHistoryService.php` | `getForExecutor` (line 378–381) | `Project::where(user_id)->orWhere(in_charge)` (direct, no ProjectQueryService) | Yes |
| 21 | `resources/views/executor/widgets/report-overview.blade.php` | Inline @php (lines 54–57) | `Project::where(user_id)->orWhere(in_charge)->pluck('project_id')` | Yes |
| 22 | `app/Helpers/ProjectPermissionHelper.php` | `canEdit`, `canSubmit`, `canView`, `isOwnerOrInCharge`, `getEditableProjects` | `user_id === $user->id \|\| in_charge === $user->id` | Yes |
| 23 | `app/Helpers/ActivityHistoryHelper.php` | `getActivitiesQuery` (line 157–160) | `Project::where(user_id)->orWhere(in_charge)` | Yes |
| 24 | `app/Http/Controllers/Reports/Monthly/ReportController.php` | `index`, `edit`, `update`, `review`, `revert`, `submit` | `ProjectQueryService::getProjectIdsForUser` or `Project::where(user_id)->orWhere(in_charge)` | Yes |
| 25 | `app/Http/Requests/Reports/Monthly/UpdateMonthlyReportRequest.php` | `authorize` (line 43) | `$report->user_id === $user->id \|\| $report->project->in_charge == $user->id` | Yes |
| 26 | `app/Http/Controllers/Reports/Aggregated/*` | Multiple | `Project::where(user_id)->orWhere(in_charge)` | Yes |

### 1.2 Distinction Already Present

| File | Method | Purpose |
|------|--------|---------|
| `app/Helpers/ProjectPermissionHelper.php` | `isOwner` | `$project->user_id === $user->id` |
| `app/Helpers/ProjectPermissionHelper.php` | `isOnlyInCharge` | `$project->in_charge === $user->id && $project->user_id !== $user->id` |

These helpers exist but **are not used** to restrict scope or responsibility anywhere in the Executor dashboard or report flows.

---

## 2. Responsibility Contamination Map

| Component | Uses Owner? | Uses In-Charge? | Merged? | Should Separate? | Classification | Notes |
|-----------|-------------|-----------------|---------|------------------|----------------|------|
| My Projects table | Yes | Yes | Yes | Yes | A, B | Responsibility + visibility. In-charge should not inflate "My" responsibility. |
| Project Budgets Overview | Yes | Yes | Yes | Yes | C | Financial accountability. In-charge projects inflate budget totals. |
| Quick Stats – Total Projects | Yes | Yes | Yes | Yes | A | Ownership responsibility metric. |
| Quick Stats – Active Projects | Yes | Yes | Yes | Yes | A | Ownership responsibility. |
| Quick Stats – Total Reports | Yes | Yes | Yes | Yes | D | Report-writing accountability. |
| Quick Stats – Approved Reports | Yes | Yes | Yes | Yes | D | Same. |
| Quick Stats – Approval Rate | Yes | Yes | Yes | Yes | D | Same. |
| Quick Stats – Budget Utilization | Yes | Yes | Yes | Yes | C | Financial accountability. |
| Report Status Summary | Yes | Yes | Yes | Yes | D | Report accountability. |
| Project Health | Yes | Yes | Yes | Yes | A | Responsibility metric. |
| Action Items – Pending Reports | Yes | Yes | Yes | Yes | D | Report accountability. |
| Action Items – Reverted Projects | Yes | Yes | Yes | Yes | A | Responsibility. |
| Action Items – Overdue Reports | Yes | Yes | Yes | Yes | D | Report accountability. |
| Upcoming Deadlines | Yes | Yes | Yes | Yes | D | Report accountability. |
| Projects Requiring Attention | Yes | Yes | Yes | Yes | A | Responsibility. |
| Reports Requiring Attention | Yes | Yes | Yes | Yes | D | Report accountability. |
| Recent Activity Feed | Yes | Yes | Yes | Partial | B | Operational visibility. Can show both; distinguish source. |
| Chart Data (budget) | Yes | Yes | Yes | Yes | C | Financial accountability. |
| Report Chart Data | Yes | Yes | Yes | Yes | D | Report accountability. |
| Recent Reports (report-overview) | Yes | Yes | Yes | Partial | B, D | Visibility + report accountability. |

**Classification:**
- A) Ownership responsibility  
- B) Operational visibility  
- C) Financial accountability  
- D) Report writing accountability  

---

## 3. Logical Risks Identified

### 3.1 In-Charge Projects Included In

| Metric / Component | Included? | Logic Path |
|--------------------|-----------|------------|
| Budget totals | Yes | `ExecutorController::executorDashboard` → `getApprovedProjectsForUser` → `calculateBudgetSummariesFromProjects`. Uses merged scope. |
| Approval rate | Yes | `getQuickStats` → `getProjectIdsForUser` + `getApprovedProjectsForUser` (indirect via projectIds for reports). Both merged. |
| Overdue report checks | Yes | `getActionItems` → `getApprovedProjectsForUser` → loop over projects, `DPReport::where` per project. Merged. |
| Upcoming deadlines | Yes | `getUpcomingDeadlines` → `getApprovedProjectsForUser`. Merged. |
| Action items – pending reports | Yes | `getActionItems` → `getProjectIdsForUser` → `DPReport::whereIn(project_id, $projectIds)`. Merged. |
| Action items – reverted projects | Yes | `getRevertedProjectsForUser`. Merged. |
| Reverted project alerts | Yes | `getProjectsRequiringAttention` → `getEditableProjectsForUser`. Merged. |
| Report status counts | Yes | `getReportStatusSummary` → `getProjectIdsForUser`. Merged. |
| Chart data (budget, report) | Yes | `getChartData`, `getReportChartData` → `getProjectIdsForUser`, `getApprovedProjectsForUser`. Merged. |

### 3.2 Report Creation and Submission

| Question | Answer | Code Reference |
|----------|--------|----------------|
| Does system assume in_charge can write reports? | Yes | `ProjectPermissionHelper::canSubmit` (line 83): `user_id === $user->id \|\| in_charge === $user->id` |
| Is DPReport creation restricted to owner? | No | `StoreMonthlyReportRequest::authorize` (line 13–16): Only checks `auth()->check() && in_array(role, ['executor','applicant'])`. No project ownership check. |
| Is report edit restricted to owner? | No | `UpdateMonthlyReportRequest::authorize` (line 43): `report->user_id === $user->id \|\| report->project->in_charge == $user->id` |
| Can in_charge submit report? | Yes | `ReportController::submit` (line 1751–1753): Uses `Project::where(user_id)->orWhere(in_charge)` |
| Policies enforcing owner-only report creation? | None | No Policy found for report create/store. FormRequest does not check project ownership. |

### 3.3 ReportController::create Authorization Gap

- **File:** `app/Http/Controllers/Reports/Monthly/ReportController.php` (line 63)
- **Logic:** `Project::where('project_id', $project_id)->firstOrFail()`. No ownership or in-charge check.
- **Effect:** Any executor/applicant can load the create form for any project (subject to route middleware). Store is protected only by `StoreMonthlyReportRequest`, which does not verify project ownership or in-charge assignment.

---

## 4. Required Architectural Separation

### 4.1 Ownership Scope (Owner Only)

**Definition:** `projects.user_id = $user->id`

**Proposed methods in ProjectQueryService:**
- `getOwnedProjectsQuery(User $user): Builder`
- `getOwnedProjectIds(User $user): Collection`
- `getOwnedProjectsForUser(User $user, array $with = []): Collection`
- `getApprovedOwnedProjectsForUser(User $user, array $with = []): Collection`
- `getEditableOwnedProjectsForUser(User $user, array $with = []): Collection`
- `getRevertedOwnedProjectsForUser(User $user, array $with = []): Collection`

**Dashboard scope rules – MUST use ownership scope:**
- Budget summaries (Project Budgets Overview)
- Quick Stats (total projects, active projects, approval rate, budget utilization, avg project budget)
- Report status summary (if representing report-writing accountability)
- Action items – reverted projects
- Action items – overdue reports
- Upcoming deadlines
- Projects Requiring Attention
- Reports Requiring Attention
- Chart data (budget, report completion)
- Project Health

### 4.2 In-Charge Scope (Visibility Only)

**Definition:** `projects.in_charge = $user->id AND projects.user_id != $user->id`

**Proposed methods:**
- `getInChargeProjectsQuery(User $user): Builder`
- `getInChargeProjectIds(User $user): Collection`
- `getInChargeProjectsForUser(User $user, array $with = []): Collection`

**Dashboard scope rules – visibility only:**
- Separate list/section "Assigned Projects (In-Charge)"
- View-only access to projects and reports
- Activity feed: can show in-charge projects with visual distinction

### 4.3 Combined Scope (Where Both Are Valid)

**Definition:** Owner OR in-charge (current merged scope).

**Retain for:**
- General "can view" checks (ProjectPermissionHelper::canView)
- Trash (if in-charge is allowed to see trashed in-charge projects)
- Activity feed (combined, but tag source as owner vs in-charge)

**Remove from responsibility metrics:** Budget, approval rate, overdue, deadlines, action items (when representing responsibility).

---

## 5. Impact on Approval & Budget Domains

### 5.1 Budget Domain

| Aspect | Current | After Separation |
|--------|---------|------------------|
| Budget totals | Include owner + in-charge projects | Include **owner only** |
| Approved expenses | Same | Same (owner projects only) |
| Remaining budget | Same | Same |
| Unapproved expenses | Same | Same |

### 5.2 Approval Domain

| Aspect | Current | After Separation |
|--------|---------|------------------|
| Project approval visibility | Owner + in-charge | Owner: full. In-charge: view only (no "Create Report", no edit) |
| Report submission | Owner + in-charge | **Owner only** (policy change required) |
| Report edit/update | Owner + in-charge | **Owner only** (policy change required) |
| Report create | No project check (any executor) | **Owner only** (add project ownership check) |

### 5.3 Report Creation Authority

- **Current:** `StoreMonthlyReportRequest` does not check project. ReportController::create does not check. In practice, in-charge can create if they reach the form.
- **Required:** Add `ProjectPermissionHelper::canCreateReport(Project $project, User $user)` returning `$project->user_id === $user->id` (owner only). Use in ReportController::create and StoreMonthlyReportRequest::authorize.

### 5.4 Report Edit/Update Authority

- **Current:** `UpdateMonthlyReportRequest`: owner OR in-charge.
- **Required:** Restrict to owner only for edit/update if in-charge must not manage reports.

### 5.5 Approval Logs

- **In-charge in approval logs:** Undefined in current architecture. Approval workflows log status changes; they do not currently distinguish owner vs in-charge. If in-charge must not appear as "responsible" in logs, a separate audit would be needed.

---

## 6. Proposed Refactoring Strategy (Design Only)

### Phase 1: ProjectQueryService Extensions

1. Add `getOwnedProjectsQuery(User $user)` — province + `where('user_id', $user->id)`.
2. Add `getInChargeProjectsQuery(User $user)` — province + `where('in_charge', $user->id)->where('user_id', '!=', $user->id)`.
3. Add corresponding `getOwnedProjectIds`, `getApprovedOwnedProjectsForUser`, `getEditableOwnedProjectsForUser`, `getRevertedOwnedProjectsForUser`.
4. Add `getInChargeProjectIds`, `getInChargeProjectsForUser`.
5. Keep existing `getProjectsForUserQuery` for backward compatibility (or rename to `getProjectsForUserQueryCombined`) where combined scope is still valid.

### Phase 2: ExecutorController Refactor

1. **Budget summaries:** Switch to `getApprovedOwnedProjectsForUser` (ownership only).
2. **Quick Stats:** Use ownership scope for all metrics.
3. **Action Items:** Use ownership scope (reverted projects, overdue reports, pending reports for owned projects only).
4. **Upcoming Deadlines:** Use `getApprovedOwnedProjectsForUser`.
5. **Projects Requiring Attention:** Use `getEditableOwnedProjectsForUser`.
6. **Reports Requiring Attention:** Use `getOwnedProjectIds`.
7. **Report Status Summary:** Use `getOwnedProjectIds` (if responsibility) or provide separate counts (owned vs in-charge).
8. **Chart Data:** Use ownership scope.
9. **Main projects list:** Provide two datasets — `$ownedProjects` (paginated, default) and `$inChargeProjects` (separate section).
10. **Project types filter:** Combine from both scopes for filter dropdown, or split by section.

### Phase 3: ActivityHistoryService and Blades

1. **ActivityHistoryService::getForExecutor:** Option A: Return both owner and in-charge activities, with `source` flag (owner vs in-charge). Option B: Return owner-only for "responsibility" view; separate method for in-charge visibility.
2. **report-overview blade:** Replace raw query with controller-passed data. Use `getOwnedProjectIds` for Recent Reports (responsibility view).

### Phase 4: Permission and Request Changes

1. **ReportController::create:** Add ownership check before rendering form: `ProjectPermissionHelper::canCreateReport($project, $user)` (owner only).
2. **StoreMonthlyReportRequest::authorize:** Add project ownership check (owner only).
3. **UpdateMonthlyReportRequest::authorize:** Change to owner only: `$report->project->user_id === $user->id`.
4. **ReportController::submit (ExecutorController::submitReport):** Restrict to owner only.
5. **ProjectPermissionHelper:** Add `canCreateReport`, `canEditReport`, `canSubmitReport` with owner-only semantics for executor/applicant.

### Phase 5: UI Logical Grouping

1. **Section 1 — My Projects (Owned):** All responsibility-based widgets, main project table (default tab), budget overview, charts, action items, deadlines, health.
2. **Section 2 — Assigned Projects (In-Charge):** Separate list/cards, view-only links. No "Create Report", no edit. No inclusion in budget/approval metrics.
3. **Counters:** Separate badges: "Owned: N", "Assigned: N".
4. **Tabs or sections:** "My Projects" | "Assigned Projects" (or equivalent labels).

### Phase 6: Data Integrity

1. **Budget calculations:** Already scoped by project; switching to owned-only changes input set. No schema change.
2. **Approval workflows:** No change to approval state machine. Only who can trigger transitions (owner vs in-charge) changes.
3. **Existing reports created by in-charge:** Undefined handling. If in-charge is restricted from creating/editing, existing reports created by in-charge remain. Decision needed: allow view-only, or reassign to owner.

---

## 7. UI Logical Grouping Requirements

| Section | Content | Owner-Only Metrics? | In-Charge Visibility? |
|---------|---------|---------------------|------------------------|
| Section 1 — My Projects (Owned) | Main project list, budget overview, quick stats, action items, deadlines, projects/reports requiring attention, charts, health | Yes | No |
| Section 2 — Assigned Projects (In-Charge) | Separate project list, view links only | No | Yes (view only) |
| Counters | "My Projects: N" and "Assigned: N" | Yes | Yes |
| Tabs / Navigation | "My Projects" and "Assigned Projects" | — | — |
| Activity Feed | Can show both; distinguish owner vs in-charge | Optional | Yes |

**No visual/UX design specified** — only logical grouping and data boundaries.
