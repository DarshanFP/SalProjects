# Trash Status Feasibility Analysis

**Date:** 2026-02-16  
**Scope:** Read-only structural analysis. No code, routes, or status added.  
**Proposal:** Introduce `ProjectStatus::TRASHED = 'trashed'` so executors can "Move to Trash" instead of delete; trashed projects hidden from normal listings, non-editable, non-submittable, optionally restorable.

---

## 1. Current Status Architecture

### Location

- **Constants:** `App\Constants\ProjectStatus`

### Defined status constants (16)

| Constant | Value |
|----------|--------|
| DRAFT | 'draft' |
| REVERTED_BY_PROVINCIAL | 'reverted_by_provincial' |
| REVERTED_BY_COORDINATOR | 'reverted_by_coordinator' |
| SUBMITTED_TO_PROVINCIAL | 'submitted_to_provincial' |
| FORWARDED_TO_COORDINATOR | 'forwarded_to_coordinator' |
| APPROVED_BY_COORDINATOR | 'approved_by_coordinator' |
| REJECTED_BY_COORDINATOR | 'rejected_by_coordinator' |
| APPROVED_BY_GENERAL_AS_COORDINATOR | 'approved_by_general_as_coordinator' |
| REVERTED_BY_GENERAL_AS_COORDINATOR | 'reverted_by_general_as_coordinator' |
| APPROVED_BY_GENERAL_AS_PROVINCIAL | 'approved_by_general_as_provincial' |
| REVERTED_BY_GENERAL_AS_PROVINCIAL | 'reverted_by_general_as_provincial' |
| REVERTED_TO_EXECUTOR | 'reverted_to_executor' |
| REVERTED_TO_APPLICANT | 'reverted_to_applicant' |
| REVERTED_TO_PROVINCIAL | 'reverted_to_provincial' |
| REVERTED_TO_COORDINATOR | 'reverted_to_coordinator' |

### Helper arrays

- **APPROVED_STATUSES:** `[approved_by_coordinator, approved_by_general_as_coordinator, approved_by_general_as_provincial]`
- **getEditableStatuses():** draft + all revert statuses (9 values). Used for canEdit, budget guards, and "needs work" filters.
- **getSubmittableStatuses():** same 9 as editable. Used for canSubmit.
- **all():** 15 values (no rejected in the docblock; code returns 15). Used for status dropdowns/filters.

### Helper methods

- **isEditable(string $status):** `in_array($status, getEditableStatuses())`
- **isSubmittable(string $status):** `in_array($status, getSubmittableStatuses())`
- **isDraft(string $status):** `$status === DRAFT`
- **isApproved(string $status):** `in_array($status, APPROVED_STATUSES)`
- **isReverted(string $status):** `in_array($status, [all revert constants])`
- **isSubmittedToProvincial / isForwardedToCoordinator / isRejected:** single-status checks

### Workflow transition map

- **ProjectStatusService::canTransition($currentStatus, $newStatus, $userRole):** fixed map of allowed (from → to → roles). Unknown `$currentStatus` or unknown (from, to) pair returns false. No `TRASHED` key; adding TRASHED would require:
  - Either a new transition **to** TRASHED (e.g. from editable statuses) and optionally **from** TRASHED back to DRAFT for restore.
  - Or TRASHED is set only by a dedicated "move to trash" action and never via existing submit/approve/revert flows.

### Model / UI status labels

- **Project::$statusLabels** (Project model): static array of 15 status keys to human labels. Used in Blades and filters. Unknown key would show as raw status or `$project->status` in views that use `$statusLabels[$project->status] ?? $project->status`.
- **ActivityHistory** and report models have their own status labels/badges; project-specific is Project::$statusLabels.

### Editable vs non-editable (current)

- **Editable (9):** draft, reverted_by_provincial, reverted_by_coordinator, reverted_by_general_as_provincial, reverted_by_general_as_coordinator, reverted_to_executor, reverted_to_applicant, reverted_to_provincial, reverted_to_coordinator.
- **Non-editable (6):** submitted_to_provincial, forwarded_to_coordinator, approved_by_coordinator, rejected_by_coordinator, approved_by_general_as_coordinator, reverted_by_general_as_coordinator, approved_by_general_as_provincial, reverted_by_general_as_provincial. (Some overlap with approved/revert; the definitive list is "not in getEditableStatuses()".)

---

## 2. Status Usage Map

### Hard-coded / explicit status checks (project status)

| Location | Type | Usage |
|----------|------|--------|
| **ProjectStatus** | Constant / array | getEditableStatuses(), getSubmittableStatuses(), all(), APPROVED_STATUSES — TRASHED must not be in editable/submittable/approved. |
| **ProjectStatusService** | in_array($project->status, $allowedStatuses) | Every transition method (submit, forward, approve, revert, etc.) uses a fixed list of allowed "from" statuses. TRASHED would not be in any of them → no transition from TRASHED until explicitly added. |
| **ProjectPermissionHelper** | ProjectStatus::isEditable($project->status) | canEdit/canDelete/canSubmit depend on isEditable/isSubmittable. If TRASHED is not editable/submittable, trashed projects are not editable/submittable. |
| **ProjectQueryService** | whereIn('status', ...) | getEditableProjectsForUser uses getEditableStatuses(); getApprovedProjectsForUser uses APPROVED_STATUSES. No "exclude trashed" today. |
| **Project model** | scopeApproved / scopeNotApproved | whereIn(APPROVED_STATUSES) / whereNotIn(APPROVED_STATUSES). TRASHED is not approved → scopeNotApproved includes trashed. |
| **ProjectController::index** | getProjectsForUserQuery()->notApproved() | Lists all non-approved projects; **trashed would currently appear** (trashed ∉ APPROVED_STATUSES). |
| **ExecutorController** | getProjectsForUserQuery() + whereIn(editable) or whereIn(approved) or no filter | "needs_work" = editable only (trashed excluded); "approved" = approved only (trashed excluded); "all" = **no status filter → trashed would appear**. |
| **GeneralController** | whereIn('status', ...), where('status', ...) | Pending/approved counts and filters use explicit status lists; list projects uses getProjectsForUsersQuery with optional status filter. Unfiltered list would include trashed. |
| **CoordinatorController / ProvincialController** | whereIn(ProjectStatus::APPROVED_STATUSES), where(status, FORWARDED/SUBMITTED) | Dashboard and lists filter by approved or pending statuses; "all projects" style queries would include trashed if no exclusion. |
| **ExportController** | in_array($project->status, [...]) | Export logic branches on approved vs non-approved; trashed would be treated as non-approved (correct) but would still be included in export if the base query includes trashed. |
| **AdminReadOnlyController** | projectsQuery->where('status', $request->status) | Filter by single status; trashed would only appear if user selects "trashed" once it exists and dropdown includes it. |
| **ProjectController::listProjects** | Province-scoped Project::query() | No status filter by default; **trashed would appear**. |
| **Helpers/ProjectPermissionHelper::getEditableProjects** | whereIn('status', getEditableStatuses()) | Editable list only; trashed excluded. |

### Blade / views (project status)

- **Project::$statusLabels** used in: provincial/ProjectList, coordinator/ProjectList, general/projects/index, admin/projects/index, activity-history, team-activity-feed, executor index (grouped by draft/reverted). Missing key → `?? $project->status` or similar; adding `'trashed' => 'Trashed'` would render safely.
- **ActivityHistory** badge: `$badgeClasses[$this->new_status] ?? 'bg-secondary'` — unknown status gets `bg-secondary`; safe for TRASHED.
- **Executor** widgets (project-status-visualization, activity-feed, etc.): use str_contains(status, 'approved'), 'draft', 'reverted', etc.; trashed would fall into default/other unless explicitly handled.
- **Coordinator** index: `$project->status === 'approved_by_coordinator'` for badge; trashed would show as non-approved.

### Summary

- **Permission and workflow:** Adding TRASHED and **not** putting it in getEditableStatuses()/getSubmittableStatuses()/APPROVED_STATUSES makes trashed projects non-editable, non-submittable, and non-approved. Transition map does not reference TRASHED; no transition from TRASHED until a new transition is added (e.g. trashed → draft for restore).
- **Listing risk:** Every place that lists "my projects" or "all non-approved projects" without excluding trashed would show trashed projects. Key call sites: **ProjectController::index** (notApproved()), **ExecutorController** (show=all), **ProjectController::listProjects**, **GeneralController** list projects, and any other use of getProjectsForUserQuery() / getProjectsForUsersQuery() without a status filter.

---

## 3. Workflow Dependencies

### Submit / approve / reject / revert / forward

- **ProjectStatusService:** submitToProvincial, forwardToCoordinator, approveByCoordinator, rejectByCoordinator, revertByProvincial, revertByCoordinator, approveAsCoordinator, approveAsProvincial, revertAsCoordinator, revertAsProvincial, revertToLevel. Each uses `in_array($project->status, $allowedStatuses)` and then sets `$project->status = $to`. TRASHED is not in any current `$allowedStatuses` → no existing method can be called on a trashed project (would throw). So adding TRASHED does not break existing transitions.
- **canTransition():** Fixed map; unknown status returns false. Adding TRASHED as a key is only needed if we allow transitions **to** or **from** TRASHED (e.g. draft → trashed, trashed → draft).

### Status history recording

- **ProjectStatusService::logStatusChange()** and activity history: record previous_status and new_status. No fixed list of statuses; any string is stored. TRASHED would be logged like any other status.

### Central vs scattered logic

- Workflow is **centralized** in ProjectStatusService (and permission in ProjectPermissionHelper). Status checks are **scattered** in controllers (ExecutorController, GeneralController, CoordinatorController, ProvincialController, ProjectController), ProjectQueryService, and Blades. Adding a new status that must be **excluded** from most lists requires either a single scope (e.g. `scopeNotTrashed`) applied at a central query builder (e.g. in ProjectQueryService or in a base Project scope) or many call-site exclusions.

---

## 4. Query Dependencies

### ProjectQueryService

- **getProjectsForUserQuery($user):** province + (user_id or in_charge). **No status filter.** Used by index, dashboard, listProjects, getProjectIdsForUser, getEditableProjectsForUser (adds whereIn(editable)), getApprovedProjectsForUser (adds whereIn(approved)), getRevertedProjectsForUser (adds whereIn(reverted)).
- **getProjectsForUsersQuery($userIds, $currentUser):** province + user_id/in_charge. **No status filter.** Used by GeneralController for coordinator/direct-team project lists and counts.
- If TRASHED is added and we do **not** exclude it: getProjectsForUserQuery() and getProjectsForUsersQuery() would return trashed projects; getEditableProjectsForUser and getApprovedProjectsForUser would not (trashed not in those arrays). So "editable" and "approved" lists are safe; "all" and "not approved" lists are not.

### ProjectController::index

- `getProjectsForUserQuery($user)->notApproved()->get()`. notApproved = whereNotIn(APPROVED_STATUSES). So trashed **would appear** in executor project list.

### ExecutorController (dashboard)

- Base: getProjectsForUserQuery($user). Then:
  - show=approved: whereIn(APPROVED_STATUSES) → trashed excluded.
  - show=needs_work: whereIn(getEditableStatuses()) → trashed excluded.
  - show=all: no status filter → **trashed would appear.**

### Dashboard counts

- **ExecutorController:** getProjectsForUserQuery()->count(), getApprovedProjectsForUser()->count(), newProjectsThisMonth (getProjectsForUserQuery), etc. Total and "new" counts would include trashed unless a global exclusion is added.
- **GeneralController / CoordinatorController / ProvincialController:** Pending counts use where(status, FORWARDED/SUBMITTED); approved use whereIn(APPROVED_STATUSES). Trashed would not be pending or approved; it would only affect "total projects" or "all projects" queries that do not filter by status.

### Export / PDF

- **ExportController:** Builds project lists from queries that may use approved() or status filters. If base query does not exclude trashed, trashed projects could appear in export.

### Report listing

- Report queries are by project_id; projects are typically loaded by project list or by approved/editable. If project list includes trashed, reports for trashed projects could appear in report lists. Excluding trashed from project lists is sufficient to avoid that.

### Conclusion (query layer)

- **Explicit filters** (editable, approved, pending) already exclude TRASHED once TRASHED is not in those arrays. **Unfiltered or notApproved()** project queries would include trashed and must be updated (e.g. scope notTrashed() or equivalent) in: ProjectController::index, ExecutorController (show=all and any getProjectsForUserQuery() used for totals), ProjectController::listProjects, GeneralController list projects, and any other "all projects" or "not approved" listing.

---

## 5. Financial & Reporting Dependencies

### ProjectFinancialResolver

- **resolve(Project $project):** Uses project type and strategy; does not branch on project status. **isApproved()** is used only in assertFinancialInvariants() (logging). Trashed projects would resolve as non-approved; no structural break.

### Budget services / BudgetSyncGuard

- **canEditBudget($project):** Tied to approval/revert semantics (project not approved). Trashed would be non-editable via canEdit; budget edit would remain blocked. No change needed for TRASHED specifically.

### Report services

- Report listing and aggregation filter by project_id sets that come from project queries. If project queries exclude trashed, report views and aggregates would not include trashed projects. No assumption like "status != 'approved'" that would misclassify trashed; trashed is simply non-approved.

### Approved-only logic

- Everywhere: `whereIn('status', ProjectStatus::APPROVED_STATUSES)` or `Project::approved()`. TRASHED is not in APPROVED_STATUSES → trashed projects would not appear in approved-only financial reports, dashboards, or budgets. **Risk:** If any report or dashboard uses "all projects" and then filters in PHP (e.g. approved vs not), trashed would be in the "not approved" bucket; that is correct. No financial inconsistency from adding TRASHED, provided trashed projects are excluded from "main" project lists so they are not double-counted or shown where not intended.

---

## 6. Notification Dependencies

### NotificationService

- **notifyStatusChange($user, $relatedType, $relatedId, $relatedTitle, $oldStatus, $newStatus):** Generic; no fixed status list. If a "move to trash" action sets status to TRASHED and calls this (or logs to activity history), it would work. No change required for TRASHED.

### Event / observer usage

- No project model observers or project-status events found that would auto-fire on status change. Status changes are explicit in ProjectStatusService and logged via logStatusChange / ActivityHistory. So TRASHED would not trigger unintended events unless a new listener is added that reacts to any status change.

### Recommendation

- When implementing "move to trash", either call the same status-change logging (and optional notification) as other transitions, or a dedicated "trashed" action. No structural barrier.

---

## 7. UI Dependencies

### Status badges and labels

- **Project::$statusLabels:** Explicit key => label. Add `'trashed' => 'Trashed'` (or similar) so dropdowns and tables show a proper label; otherwise `$statusLabels[$project->status] ?? $project->status` shows "trashed" as-is.
- **ActivityHistory:** Badge class from map; unknown key → `'bg-secondary'`. Safe.
- **Provincial/Coordinator/General project lists:** Use Project::$statusLabels and sometimes hard-coded badge classes (e.g. approved = success, reverted = warning). Trashed can be given a dedicated class (e.g. secondary or dark) in one place (Project model or a shared helper) and used in Blades.

### Switch / conditional on status

- Blades use `in_array($project->status, $editableStatuses)`, `$project->status === ProjectStatus::APPROVED_BY_COORDINATOR`, etc. Trashed would not be editable and not approved; existing conditionals would not show edit/approve actions for trashed. Dedicated "Restore" or "Trash" UI would be new.

### Unknown status handling

- Most views use `$statusLabels[$status] ?? $status` or badge `?? 'bg-secondary'`. Unknown statuses are handled; TRASHED would be unknown until added to Project::$statusLabels and optionally to ActivityHistory badge map. Low risk.

---

## 8. Risk Classification

### Feasibility: **MEDIUM RISK**

- **Reasons:**
  - **Status system is partly flexible:** ProjectStatus uses arrays (getEditableStatuses, APPROVED_STATUSES, all()) and helper methods; adding a constant and keeping TRASHED out of editable/submittable/approved is straightforward. Transition map and permission logic do not assume a closed set in a way that breaks.
  - **Multiple modules assume "visible" projects:** Listing and dashboard logic often use getProjectsForUserQuery() or getProjectsForUsersQuery() with only notApproved() or no status filter. So trashed would **appear** in executor project list, executor "all" dashboard, listProjects, and general list unless we add a consistent exclusion (scope or filter) in several places.
  - **No single "visibility" concept:** There is no central "projects visible in normal lists" scope. We have approved(), notApproved(), and ad hoc whereIn(editable) or whereIn(approved). Adding "not trashed" requires either a new scope used everywhere or many call-site changes.
  - **Workflow is centralized:** ProjectStatusService and ProjectPermissionHelper are the single place for transitions and permission; adding TRASHED does not break existing transitions. Risk is mainly in query and UI coverage.

### Top 5 most impacted modules

1. **ProjectQueryService / Project model** — Add a scope (e.g. `scopeNotTrashed()` or `scopeVisible()`) and use it in getProjectsForUserQuery() and getProjectsForUsersQuery() (or document that callers must apply it). Alternatively, add `getTrashedStatus()` / `isTrashed()` and have every listing add `where('status', '!=', ProjectStatus::TRASHED)` (or whereNotIn if we have multiple "hidden" statuses later).
2. **ProjectController** — index() and listProjects() and any other project list that uses notApproved() or no status filter; must exclude trashed (via scope or explicit where).
3. **ExecutorController** — Dashboard project query (show=all), project types query, quick stats, chart data, and any count that uses getProjectsForUserQuery() without status filter; must exclude trashed.
4. **GeneralController** — List projects and dashboard counts that use getProjectsForUsersQuery() without status filter; must exclude trashed.
5. **ProjectStatus + Project model + Blades** — Add TRASHED constant; keep it out of getEditableStatuses(), getSubmittableStatuses(), APPROVED_STATUSES; add to ProjectStatus::all() and Project::$statusLabels; optionally add to ActivityHistory badge map; add transition(s) to/from TRASHED in ProjectStatusService if we support "move to trash" and "restore".

### Is TRASHED safe to add?

- **Conceptually yes**, if:
  - TRASHED is not in getEditableStatuses(), getSubmittableStatuses(), or APPROVED_STATUSES (so trashed projects are non-editable, non-submittable, non-approved).
  - All "normal" project listings and counts explicitly exclude trashed (via a shared scope or filter).
  - Transition to TRASHED (and optionally from TRASHED back to draft) is implemented in ProjectStatusService and permission is enforced (e.g. only owner/in_charge can trash/restore).
- **Risks if we only add the constant and do nothing else:** Trashed projects would appear in executor project list, "all" dashboard, listProjects, and general list; they would be non-editable and non-submittable but visible and could confuse users.

---

## 9. Alternative Design Comparison

### Option 1: Boolean column `is_archived` (or `is_trashed`)

- **Idea:** Add `projects.is_archived` (or `is_trashed`) true/false; "move to trash" sets it to true. Listings use `where('is_archived', false)` (or scope).
- **Complexity:** One column, one scope, apply scope in central query builders. Similar number of call-site checks as "exclude status trashed" unless we add the scope to ProjectQueryService base query.
- **Risk:** Two sources of truth (status + is_archived). If a project is reverted or approved, we must decide whether is_archived is cleared; usually we would clear it on restore only. Less risk of forgetting a status in a dropdown (status remains unchanged when trashing).
- **Cleanliness:** Clear meaning: "hidden from normal list". No new status in workflow maps or labels. Slightly cleaner for "visibility" only.
- **Impact surface:** Same as TRASHED: every place that builds "visible" project list must apply the scope or filter. Migration + scope + same list of call sites as TRASHED.

### Option 2: Laravel SoftDeletes

- **Idea:** Add `deleted_at` to projects; "move to trash" = soft delete. Default Eloquent queries exclude soft-deleted; withTrashed() for trash/restore views.
- **Complexity:** Model change (SoftDeletes trait), migration. No new status; no change to ProjectStatus or transition map.
- **Risk:** Semantic shift: "delete" today is hard delete (and not exposed). Soft delete would mean "trash" and would auto-hide from all default project queries. Lower risk of trashed projects leaking into lists. Restore = update deleted_at to null. Possible conflict with future "real" delete (force delete vs restore).
- **Cleanliness:** Very clean for "hide from all lists". No status lists to update; no new status in UI except in a dedicated "Trash" view. Slightly less explicit in reporting (e.g. "trashed" vs "deleted" in activity log) unless we also log a status or type.
- **Impact surface:** Smaller for listing (default queries exclude soft-deleted). We must audit every Project::query() and ProjectQueryService usage to ensure we do not accidentally include trashed where we want "all" (e.g. admin). Need a dedicated "trash" route and "restore" action.

### Comparison summary

| Criterion | TRASHED status | is_archived boolean | SoftDeletes |
|-----------|----------------|---------------------|-------------|
| Complexity | Medium (constant + scope/filter + labels + transitions) | Medium (column + scope + same call sites) | Low (trait + migration; scope by default) |
| Risk of trashed appearing in lists | Higher until every list excludes it | Same as TRASHED if scope not applied everywhere | Lower (default exclusion) |
| Cleanliness | Status carries meaning; one field | Two concepts (status + archived) | Single concept (deleted_at) |
| Impact surface | Many query call sites + status arrays + UI | Many query call sites | Fewer list call sites; need explicit withTrashed() for trash view |
| Restore | Set status back to draft (or previous) | Set is_archived = false | set deleted_at = null |
| Reporting / analytics | Easy to filter "trashed" in SQL/reports | Easy to filter is_archived | Need withTrashed() for "all" analytics |

**Verdict:** TRASHED is **safe to add** with coordinated updates to query layer and UI. **SoftDeletes** is **safer** for "never show in normal lists" with less query-layer change but a different semantics (delete vs status). **is_archived** is a middle ground: same visibility logic as TRASHED without extending the status set.

---

## 10. Recommendation

- **Feasibility:** **MEDIUM.** Adding `ProjectStatus::TRASHED` is feasible and does not break workflow or permissions if TRASHED is kept out of editable/submittable/approved and transitions to/from TRASHED are defined. The main effort is ensuring **all** "normal" project listings and counts exclude trashed (single scope applied in ProjectQueryService or Project model is recommended).
- **Implementability (when permitted):**
  1. Add `ProjectStatus::TRASHED` and include it in `ProjectStatus::all()` only; do **not** add to getEditableStatuses(), getSubmittableStatuses(), or APPROVED_STATUSES.
  2. Add `Project::scopeNotTrashed()` (or scopeVisible()) and apply it in ProjectQueryService::getProjectsForUserQuery() and getProjectsForUsersQuery() (and document that these return "visible" projects only). Add a separate method or query for "trashed only" (e.g. getTrashedProjectsForUser) for the trash view.
  3. Update Project::$statusLabels and, if desired, ActivityHistory badge map for 'trashed'.
  4. In ProjectStatusService, add allowed transition(s): e.g. from editable statuses to TRASHED (role executor/applicant) and from TRASHED to DRAFT (restore). Enforce permission (owner/in_charge) in controller.
  5. Add "Move to Trash" and "Restore" routes/actions and UI; ensure no submit/approve/revert actions are shown for trashed projects (already achieved by isEditable/isSubmittable).
- **Alternative:** If the goal is minimal risk of trashed projects appearing anywhere, **SoftDeletes** is the safer option with a smaller query-layer footprint; reserve TRASHED status for a future refinement (e.g. "trashed" label in activity log while using SoftDeletes for visibility).

---

**End of analysis. No code or configuration was modified.**
