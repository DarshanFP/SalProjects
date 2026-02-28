# Cross-Service Architectural Alignment Audit

**Date:** February 19, 2025  
**Scope:** Executor Dashboard (Phases 1–3), system-level alignment  
**Mode:** Read-only verification; no code changes

---

## Architecture Map

### Query Layer

| Component | Location | Responsibility |
|-----------|----------|----------------|
| ProjectQueryService | app/Services/ProjectQueryService.php | Centralized project query logic. Methods: getProjectsForUserQuery, getProjectIdsForUser, getOwnedProjectsQuery, getInChargeProjectsQuery, getOwnedProjectIds, getApprovedOwnedProjectsForUser, getEditableOwnedProjectsForUser, getRevertedOwnedProjectsForUser, applySearchFilter. Province-scoped; supports owned vs in-charge separation. |
| ReportQueryService | app/Services/ReportQueryService.php | Thin wrapper delegating to ProjectQueryService for project IDs; provides getReportsForUserQuery, getReportsForUser, getReportsForUserByStatus. **Not used by ExecutorController, ProvincialController, CoordinatorController, or GeneralController.** |

### Domain / Status Layer

| Component | Location | Responsibility |
|-----------|----------|----------------|
| ProjectStatus | app/Constants/ProjectStatus.php | Canonical project status constants: APPROVED_STATUSES, FINAL_STATUSES, getEditableStatuses(), getSubmittableStatuses(), isApproved(), isReverted(). Single source for project status semantics. |
| DPReport | app/Models/Reports/Monthly/DPReport.php | Report status constants (STATUS_DRAFT, STATUS_APPROVED_BY_*, etc.), APPROVED_STATUSES, getDashboardStatusKeys(), isApproved(). Separate domain from project status. |
| Project (model) | app/Models/OldProjects/Project.php | approved(), notApproved() scopes using ProjectStatus::APPROVED_STATUSES; isApproved() delegates to ProjectStatus. |

### Financial Layer

| Component | Location | Responsibility |
|-----------|----------|----------------|
| ProjectFinancialResolver | app/Domain/Budget/ProjectFinancialResolver.php | Single entry point for project fund fields (opening_balance, amount_sanctioned, amount_requested, etc.). Delegates to DirectMappedIndividualBudgetStrategy / PhaseBasedBudgetStrategy. No arithmetic; applies canonical separation for approved vs non-approved. |
| DerivedCalculationService | app/Services/Budget/DerivedCalculationService.php | Math operations: calculateRemainingBalance, calculateUtilization, calculatePhaseTotal, etc. Used by resolver strategies and controllers. |
| BudgetSyncService | app/Services/Budget/BudgetSyncService.php | Syncs fund fields from type save and before approval. Uses ProjectFundFieldsResolver (which delegates to ProjectFinancialResolver). Order-dependent with CoordinatorController approval flow. |

### Approval Layer

| Component | Location | Responsibility |
|-----------|----------|----------------|
| ProjectStatusService | app/Services/ProjectStatusService.php | submitToProvincial, forwardToCoordinator, approve, reject, revertByCoordinator, revertByProvincial, revertAsCoordinator, revertAsProvincial, revertToLevel. All status mutations for projects. |
| ReportStatusService | app/Services/ReportStatusService.php | Report-level approval flow (submit, forward, approve, revert). |

### Activity / Report Lifecycle

| Component | Location | Responsibility |
|-----------|----------|----------------|
| ActivityHistoryService | app/Services/ActivityHistoryService.php | Logs project/report updates, submits, comments; getForExecutor, getForProvincial, getForCoordinator. **getForExecutor uses raw Project::where (user_id or in_charge) without province filter or ProjectQueryService.** |
| ReportController | app/Http/Controllers/Reports/Monthly/ReportController.php | Report CRUD, uses ProjectQueryService for authorization; uses ActivityHistoryService for logging. |

### Presentation Layer

| Component | Responsibility |
|-----------|----------------|
| ExecutorController | executorDashboard, reportList, pendingReports, KPI methods. Uses ProjectQueryService, ProjectFinancialResolver, DerivedCalculationService. |
| ProvincialController | provincialDashboard, team views. Uses raw Project::where / Project::approved; no ProjectQueryService. Uses ProjectFinancialResolver, DerivedCalculationService. |
| CoordinatorController | coordinatorDashboard. Uses raw Project::approved, Project::with; no ProjectQueryService. Uses ProjectFinancialResolver, DerivedCalculationService, BudgetSyncService, ProjectStatusService. |
| GeneralController | General user dashboard. Uses ProjectQueryService for some flows; raw Project queries for others. Uses ProjectStatusService. |

---

## Dashboard Alignment Findings

### A) Status Logic

**Canonical constants reuse:**
- ExecutorController lines 28–32: Uses ProjectStatus::getEditableStatuses() and inline array duplicating ProjectStatus::APPROVED_STATUSES (same three constants).
- ProjectQueryService lines 133–137, 301–307: Inline arrays duplicating ProjectStatus::APPROVED_STATUSES instead of ProjectStatus::APPROVED_STATUSES.
- ProjectQueryService lines 163–171, 346–355: Inline arrays duplicating ProjectStatus::isReverted() set (8 reverted constants) instead of a helper.

**Hardcoded status arrays:**
- ExecutorController lines 249–258, 282–290: Hardcoded DPReport pending/reverted lists (8 constants each) instead of DPReport::getDashboardStatusKeys or a shared helper.
- ExecutorController lines 471–477, 565–572: Duplicate pending report status lists.
- ProvincialController lines 101–105: Inline approved status array duplicating ProjectStatus::APPROVED_STATUSES.
- ProvincialController lines 1977–1978, 2079–2080: Inline [SUBMITTED_TO_PROVINCIAL, REVERTED_BY_COORDINATOR].
- GeneralController: Multiple inline status arrays for pending and approved.

**Controller-level reinterpretation:**
- ExecutorController getReportStatusSummary (lines 626–628): Uses str_starts_with($s, 'reverted_') to count reverted reports instead of a canonical list.
- ExecutorController getActionItems getProjectsRequiringAttention (line 567): Incomplete pending list (6 statuses) vs getDashboardStatusKeys (omits SUBMITTED_TO_PROVINCIAL, FORWARDED_TO_COORDINATOR for pending).

**Positive:**
- ExecutorController getReportStatusSummary uses DPReport::getDashboardStatusKeys and DPReport::APPROVED_STATUSES for aggregation.
- ProjectQueryService uses ProjectStatus::getEditableStatuses() for editable projects.
- Blades use DPReport::STATUS_* constants (report-status-summary.blade.php).

### B) Financial Logic

**ProjectFinancialResolver as sole calculator:**
- ExecutorController: calculateBudgetSummariesFromProjects, getQuickStats, KPI methods all use ProjectFinancialResolver::resolve() for opening_balance. No alternate financial calculation path.
- ProvincialController, CoordinatorController: Same pattern via calculateBudgetSummariesFromProjects or direct resolver calls.

**Duplicated financial math in controller:**
- ExecutorController calculateBudgetSummariesFromProjects: Aggregation logic (approved vs unapproved expenses, remaining balance) is controller-private. Uses DerivedCalculationService::calculateRemainingBalance and report->isApproved(). No duplicate equations; follows resolver + calc service.
- No raw arithmetic for opening_balance or amount_sanctioned in controller.

**Deviation from DerivedCalculationService:**
- None. All utilization and remaining-balance math uses DerivedCalculationService.

### C) Query Logic

**ProjectQueryService consistency (Executor):**
- ExecutorController executorDashboard: getOwnedProjectsQuery, getInChargeProjectsQuery, applySearchFilter, getOwnedProjectIds, getApprovedOwnedProjectsForUser, getOwnedProjectsQuery (counts, project types), getRevertedOwnedProjectsForUser, getEditableOwnedProjectsForUser — all via ProjectQueryService.
- ExecutorController reportList, pendingReports, submitReport: getProjectIdsForUser (merged scope).
- ExecutorController KPI and action methods: getOwnedProjectIds, getApprovedOwnedProjectsForUser, getRevertedOwnedProjectsForUser, getEditableOwnedProjectsForUser.

**Raw Project::where duplications:**
- ActivityHistoryService::getForExecutor: Project::where(user_id or in_charge) — no ProjectQueryService, no province filter.
- ProvincialController: Project::whereIn('user_id', $accessibleUserIds), Project::where('province_id') — no ProjectQueryService. Different scope (team/accessible users) than Executor (owned/in-charge).
- CoordinatorController: Project::approved(), Project::with() — no ProjectQueryService. Coordinator scope is system-wide.

**Scope drift:**
- Executor dashboard KPIs: Owned scope (getOwnedProjectIds, getApprovedOwnedProjectsForUser) — consistent.
- Executor reportList, pendingReports: Merged scope (getProjectIdsForUser) — intentional per docs.
- ActivityHistoryService::getForExecutor: Merged scope but via raw Project::where; no province filter. Documented inconsistency (ExecutorDashboardAudit, OwnerVsInChargeResponsibilityAudit).

### D) Approval Logic

**Direct status mutation:**
- ExecutorController: No direct project status mutation. Uses ReportStatusService::submitToProvincial for reports.
- No bypass of ProjectStatusService for projects in Executor flow.

**ProjectStatusService usage:**
- Executor dashboard does not perform approvals; it only displays data. No ProjectStatusService calls in ExecutorController.

### E) Blade Layer

**Inline queries:**
- resources/views/provincial/widgets/team-overview.blade.php line 83: DPReport::where('user_id', $member->id)->get() — raw query in view.
- resources/views/provincial/widgets/team-overview.blade.php line 85: Uses DPReport::STATUS_APPROVED_BY_COORDINATOR only for approval count; omits STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR and STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL.

**Merged scope leakage:**
- Executor blades receive $reportStatusSummary, $projectHealthSummary, $enhancedFullOwnedProjects from controller. No scope logic in blades; data is pre-computed. No merged/owned scope leakage in Executor blades.

**Other blades:**
- Executor report-status-summary.blade.php: Uses DPReport::STATUS_* constants. No queries.
- Executor project-health.blade.php: Uses $projectHealthSummary, $enhancedFullOwnedProjects; no queries.

---

## Cross-Dashboard Consistency

### Provincial Dashboard

| Aspect | Provincial | Executor |
|--------|------------|----------|
| Financial resolver | ProjectFinancialResolver via calculateBudgetSummariesFromProjects | Same |
| Status constants | ProjectStatus::APPROVED_STATUSES in some places; inline [APPROVED_BY_COORDINATOR, ...] in provincialDashboard (lines 102–105) | Inline approved array; ProjectStatus::getEditableStatuses |
| Scope | Project::whereIn('user_id', $accessibleUserIds) — team/accessible users | ProjectQueryService::getOwnedProjectsQuery / getInChargeProjectsQuery |
| Query service | No ProjectQueryService | ProjectQueryService throughout |

**Divergence:** Provincial uses raw Project queries and does not use ProjectQueryService. Scope philosophy differs (team access vs owned/in-charge). Same financial resolver.

### Coordinator Dashboard

| Aspect | Coordinator | Executor |
|--------|-------------|----------|
| Financial resolver | ProjectFinancialResolver | Same |
| Status constants | ProjectStatus::APPROVED_STATUSES, DPReport::APPROVED_STATUSES | Same constants (via inline duplication) |
| Scope | Project::approved() — system-wide | Owned/in-charge per user |
| Query service | No ProjectQueryService | ProjectQueryService |

**Divergence:** Coordinator uses raw Project::approved() and system-wide scope. Same financial resolver and status constants.

### Admin / General Dashboard

| Aspect | General | Executor |
|--------|---------|----------|
| Financial resolver | Not used in dashboard summary (different flows) | ProjectFinancialResolver |
| Status constants | ProjectStatus::APPROVED_STATUSES, ProjectStatus::FORWARDED_TO_COORDINATOR, etc. | Same |
| Scope | ProjectQueryService::getProjectsForUsersQuery for some flows; raw Project for others | ProjectQueryService |
| Query service | Partial ProjectQueryService | Full ProjectQueryService |

**Divergence:** General uses ProjectQueryService for some flows and raw queries for others. Mixed usage.

### Summary

All dashboards use the same ProjectFinancialResolver and DerivedCalculationService. Status constants are semantically aligned but duplicated in multiple places. Only Executor consistently uses ProjectQueryService; Provincial and Coordinator use raw Project queries. ReportQueryService is not used by any dashboard.

---

## Duplication & Drift Findings

### Duplicate Status Arrays

| Definition | Location |
|------------|----------|
| Project approved (3 statuses) | ProjectStatus::APPROVED_STATUSES |
| Project approved (inline) | ProjectQueryService lines 133–137, 303–307; ExecutorController lines 29–32; ProvincialController lines 102–105 |
| Project reverted (8 statuses) | ProjectStatus::isReverted() |
| Project reverted (inline) | ProjectQueryService lines 163–171, 347–355 |
| Report pending (draft + submitted + forwarded + reverted) | ExecutorController lines 249–258, 282–290, 471–477, 565–572 — multiple definitions |
| Report reverted (8 statuses) | DPReport constants |
| Report reverted (inline) | ExecutorController lines 233–242, 282–290; ProvincialController; GeneralController lines 1605–1610 |

### Duplicate Approval Status Lists

- ProjectStatus::APPROVED_STATUSES vs inline arrays in ProjectQueryService (2 places), ExecutorController (1), ProvincialController (1).
- DPReport::APPROVED_STATUSES used correctly in ExecutorController, CoordinatorController, ProvincialController; team-overview blade uses only STATUS_APPROVED_BY_COORDINATOR.

### Duplicate Reverted Lists

- Project: ProjectStatus::isReverted() vs inline in ProjectQueryService (2 methods), ProjectStatus::getEditableStatuses (overlaps).
- Report: DPReport constants vs inline in ExecutorController (4+ locations), ProvincialController, GeneralController.

### Duplicate Financial Equations

- None. Single DerivedCalculationService for calculateRemainingBalance, calculateUtilization.

### Duplicate Scope Filters

- Province filter: ProjectQueryService (province_id when user->province_id set) vs ActivityHistoryService (no province filter).
- Owned definition: ProjectQueryService::getOwnedProjectsQuery (user_id = user.id) — single definition.
- Merged definition: ProjectQueryService::getProjectsForUserQuery (user_id or in_charge) vs ActivityHistoryService::getForExecutor (raw Project::where) — logic equivalent but not shared.

### Multiple Definitions of "Approved"

- Project: ProjectStatus::APPROVED_STATUSES (canonical) vs inline arrays in 4+ files. Semantically identical; maintainability risk.
- Report: DPReport::APPROVED_STATUSES (canonical) vs team-overview blade using STATUS_APPROVED_BY_COORDINATOR only — **incomplete** definition.

### Multiple Definitions of "Owned"

- Single definition: ProjectQueryService::getOwnedProjectsQuery (user_id = user.id). No competing definitions.

---

## Database & Model Consistency

### Dashboard-Used Fields

| Field | Table | Migration / Model | Nullable |
|-------|-------|-------------------|----------|
| project_id, user_id, in_charge | projects | create_projects_table | user_id, in_charge required (in_charge later nullable per migration) |
| province_id | projects | add_province_id | Yes, then enforced not null |
| status | projects | create_projects_table | No default 'draft' per set_projects_status_default |
| opening_balance, amount_sanctioned | projects | create_projects_table | Nullable |
| overall_project_budget, amount_forwarded, local_contribution | projects | create_projects_table, add_local_contribution | Nullable / default 0 |
| project_type, report_month_year | projects, DP_Reports | Present | — |

All dashboard-used project and report fields exist in schema.

### Nullable Field Guarding

- ProjectFinancialResolver::applyCanonicalSeparation uses (float)($project->opening_balance ?? 0), (float)($project->amount_sanctioned ?? 0). Nullable handling present.
- ExecutorController calculateBudgetSummariesFromProjects uses $financials['opening_balance'] ?? 0. Guarded.

### opening_balance Assumptions

- Resolver assumes for approved projects: opening_balance is set by BudgetSyncService (pre-approval) and CoordinatorController (post-approval). If neither runs, resolver returns 0. Documented in Phase2_Budget_Zero_Audit and System_Alignment_Feasibility_Audit.
- No resolver assumption contradicts DB design. opening_balance is nullable; resolver treats null as 0.

### Resolver vs DB Design

- Resolver does not assume non-null opening_balance. Uses ?? 0. Consistent with nullable column.

---

## Scalability Review

### 1. New Status Added

**Layers to change:**
- ProjectStatus or DPReport (canonical constants).
- ProjectQueryService (inline approved/reverted arrays in 4 methods).
- ExecutorController (inline approved array, editable, pending/reverted report arrays in 6+ locations).
- ProvincialController, CoordinatorController, GeneralController (inline status arrays).
- ProjectStatusService (revert/approval logic).
- ExportController, ProjectPermissionHelper, Helpers (status checks).
- Estimated: 8+ files, 15+ methods. Duplication increases change surface.

### 2. New Project Type Added

**Charts adaptation:**
- ExecutorController getChartData, buildProjectChartData group by project_type from query results. New types appear automatically in charts if they exist in data.
- ProjectFinancialResolver uses project_type for strategy selection. New types require strategy registration (PhaseBasedBudgetStrategy or DirectMappedIndividualBudgetStrategy). Not automatic.

### 3. Report Status Logic Change

**Canonical source:**
- DPReport::getDashboardStatusKeys and DPReport::APPROVED_STATUSES are canonical.
- ExecutorController getReportStatusSummary uses them. Other places (pendingReports, getActionItems, getReportsRequiringAttention) use hardcoded arrays. If canonical source changes, hardcoded arrays will drift.

### 4. Approval Flow Change

**Dashboard impact:**
- Dashboards consume status and financial data. They do not perform approvals. If ProjectStatusService adds new statuses or transitions, dashboards that use inline arrays will show incorrect counts until updated. ProjectQueryService approved/reverted methods would also need updates. Risk: medium — approval flow change would require coordinated updates across Query, Controller, and possibly view layers.

---

## Architectural Classification

**Classification: ALIGNED_BUT_CONTROLLER_HEAVY**

### Justification

**Aligned aspects:**
- Single financial path: ProjectFinancialResolver and DerivedCalculationService. No parallel financial logic.
- Single approval path: ProjectStatusService for project status mutations; ReportStatusService for reports.
- Executor dashboard consistently uses ProjectQueryService for project queries.
- Project and report status domains are separated (ProjectStatus vs DPReport).
- No circular dependencies; controller → services → models.

**Controller-heavy aspects:**
- ExecutorController contains large private methods (calculateBudgetSummariesFromProjects, getReportStatusSummary, getChartData, getReportChartData, getActionItems, getQuickStats, etc.). Aggregation logic lives in controller, not in a dedicated service.
- Inline status arrays in ExecutorController (lines 28–32, 233–242, 249–258, 282–290, 471–477, 565–572) duplicate canonical constants.
- ProvincialController and CoordinatorController bypass ProjectQueryService; raw Project queries with different scope logic.

**Evidence:**
- ProjectQueryService lines 133–137, 303–307: inline approved array vs ProjectStatus::APPROVED_STATUSES.
- ExecutorController lines 28–32: same duplication.
- ActivityHistoryService::getForExecutor lines 377–381: raw Project::where, no ProjectQueryService, no province.
- ReportQueryService: defined but never used (no ReportQueryService:: in codebase).
- resources/views/provincial/widgets/team-overview.blade.php: inline DPReport query and partial approval constant.

**Not SERVICE_FRAGMENTATION_RISK:** No competing implementations of the same concept. Single resolver, single status constant source, single calculation service.

**Not PARALLEL_SYSTEM_FORMING:** Executor does not introduce a parallel query or financial system. It uses existing services. Duplication is of constants/lists, not of core logic.

---

## Final Feasibility Verdict

**Feasibility: FEASIBLE WITH MAINTAINABILITY RISKS**

The Executor Dashboard implementation (Phases 1–3) is architecturally aligned with the existing system:

- Uses ProjectQueryService for project queries.
- Uses ProjectFinancialResolver and DerivedCalculationService for financials.
- Uses ProjectStatus and DPReport constants (with duplication).
- Does not bypass ProjectStatusService or ReportStatusService.
- Does not introduce parallel query or financial systems.

**Risks:**
1. Duplication of approved and reverted status lists across ProjectQueryService, ExecutorController, ProvincialController — future status changes require multi-file updates.
2. ActivityHistoryService::getForExecutor bypasses ProjectQueryService and province filter — scope/visibility inconsistency with rest of Executor dashboard.
3. ReportQueryService exists but is unused — potential confusion or future fragmentation if adopted inconsistently.
4. Provincial team-overview blade uses inline DPReport query and incomplete approval constant — presentation-layer duplication and possible drift.

**No blocking issues.** The dashboard does not reimplement core domain logic, does not bypass services for writes, and does not create conflicting persistence paths. Recommended follow-ups (for future work, not part of this audit): consolidate status lists to canonical constants, align ActivityHistoryService with ProjectQueryService, and remove or adopt ReportQueryService consistently.
