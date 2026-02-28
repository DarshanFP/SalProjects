# Status Centralization Impact Review

**Date:** February 19, 2025  
**Mode:** Impact simulation only — no code changes  
**Objective:** Evaluate impact if all inline status arrays are replaced with canonical helpers

---

## Inline Status Inventory

### Project Approved Status Arrays

| File | Method / Location | Exact Statuses | Purpose |
|------|-------------------|----------------|---------|
| ExecutorController.php | executorDashboard (lines 29–33) | APPROVED_BY_COORDINATOR, APPROVED_BY_GENERAL_AS_COORDINATOR, APPROVED_BY_GENERAL_AS_PROVINCIAL | Filter: show approved projects in dashboard list |
| ProjectQueryService.php | getApprovedProjectsForUser (133–139) | Same 3 | Query: approved projects for user |
| ProjectQueryService.php | getApprovedOwnedProjectsForUser (303–307) | Same 3 | Query: approved owned projects for KPIs |
| ProvincialController.php | provincialDashboard (102–106) | Same 3 | Society totals: approved amount_sanctioned |
| BudgetReconciliationController.php | index (56–60), getProjectTypes (93–97) | Same 3 | Admin: approved projects for reconciliation |
| ExportController.php | downloadPdf provincial branch (338–341) | SUBMITTED_TO_PROVINCIAL, REVERTED_BY_COORDINATOR, APPROVED_BY_COORDINATOR | Authorization: provincial can download these |
| ExportController.php | downloadPdf coordinator branch (350–353) | FORWARDED_TO_COORDINATOR, APPROVED_BY_COORDINATOR, REVERTED_BY_COORDINATOR | Authorization: coordinator can download these |

### Project Reverted Status Arrays

| File | Method / Location | Exact Statuses | Purpose |
|------|-------------------|----------------|---------|
| ProjectQueryService.php | getRevertedProjectsForUser (163–172) | 8 reverted constants | Query: reverted projects |
| ProjectQueryService.php | getRevertedOwnedProjectsForUser (347–356) | Same 8 | Query: reverted owned projects for action items |
| ProjectStatus.php | isReverted() (162–171) | Same 8 | Canonical helper |

### Report Pending / Editable Status Arrays

| File | Method / Location | Exact Statuses | Purpose |
|------|-------------------|----------------|---------|
| ExecutorController.php | pendingReports default (249–258) | DRAFT, SUBMITTED_TO_PROVINCIAL, 7 reverted (omits REVERTED_TO_PROVINCIAL, REVERTED_TO_COORDINATOR) | Filter: default pending report list |
| ExecutorController.php | pendingReports projectTypes (297–306) | Same | Filter: project types for pending reports |
| ExecutorController.php | getActionItems pendingReports (471–479) | DRAFT, 6 reverted (omits REVERTED_TO_PROVINCIAL, REVERTED_TO_COORDINATOR) | Action items: pending reports |
| ExecutorController.php | getReportsRequiringAttention (565–573) | Same as above | Action items: reports needing attention |
| ExecutorController.php | getActionItems getGroupedReports (579) | Single STATUS_DRAFT for 'draft' key | Grouping |
| ProvincialController.php | editableStatuses (355–365) | DRAFT + 8 reverted | Filter: editable report statuses for provincial |
| GeneralController.php | pendingStatuses (1602–1611) | FORWARDED_TO_COORDINATOR, SUBMITTED_TO_PROVINCIAL, 6 reverted | Filter: pending reports for General |
| ProvincialController.php | getPendingApprovalsForDashboard reports (1859–1860) | SUBMITTED_TO_PROVINCIAL, REVERTED_BY_COORDINATOR only | Coordinator action queue: reports awaiting provincial forward |
| ProvincialController.php | getApprovalQueueForDashboard (1953–1956) | Same | Approval queue reports |
| ProvincialController.php | getPendingProjectsForDashboard (1977–1979) | Project: SUBMITTED_TO_PROVINCIAL, REVERTED_BY_COORDINATOR | Pending projects for provincial |
| ProvincialController.php | getApprovalQueueProjects (2078–2080) | Same | Approval queue projects |
| ProvincialController.php | totalSubmittedReports (2222–2225) | SUBMITTED_TO_PROVINCIAL, FORWARDED_TO_COORDINATOR, APPROVED_BY_COORDINATOR | Metric: submitted/in-pipeline reports |
| ReportStatusService.php | submitToProvincial allowedStatuses | DRAFT + 7 reverted (per ReportStatus flow) | Approval: from-status validation |
| TestApplicantAccess.php | (307–310) | SUBMITTED_TO_PROVINCIAL, REVERTED_BY_PROVINCIAL, REVERTED_BY_COORDINATOR | Test: applicant access scenarios |

### Report Reverted Status Arrays

| File | Method / Location | Exact Statuses | Purpose |
|------|-------------------|----------------|---------|
| ExecutorController.php | pendingReports reverted filter (233–242) | 8 reverted | Filter: reverted reports |
| ExecutorController.php | pendingReports default (251–257) | 7 reverted (omits TO_PROVINCIAL, TO_COORDINATOR) | Part of pending list |
| ExecutorController.php | projectTypes reverted (282–290) | 8 reverted | Filter for project types |
| ExecutorController.php | projectTypes default (299–305) | 7 reverted | Same |
| ExecutorController.php | getActionItems (473–478) | 6 reverted (omits TO_PROVINCIAL, TO_COORDINATOR) | Action items |
| ExecutorController.php | getReportsRequiringAttention (567–572) | Same | Action items |
| DPReport.php | isEditable() (353–362) | DRAFT + 8 reverted | Canonical: report editable check |
| ProvincialController.php | editableStatuses (356–364) | Same | Duplicate of isEditable |

### Report Approved Status Arrays (Inline or Subset)

| File | Method / Location | Exact Statuses | Purpose |
|------|-------------------|----------------|---------|
| GeneralController.php | approvedStatuses (1835–1838) | STATUS_APPROVED_BY_COORDINATOR, STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR only | Approved reports for General's coordinator hierarchy — excludes APPROVED_BY_GENERAL_AS_PROVINCIAL |
| GeneralController.php | calculateBudgetData (3732, 3741) | STATUS_APPROVED_BY_COORDINATOR only for "approved" param | Budget: approved expenses — coordinator hierarchy and direct team |
| BudgetValidationService.php | getBudgetSummary (79) | STATUS_APPROVED_BY_COORDINATOR only | Approved expenses for validation — excludes General-as-provincial approvals |
| team-overview.blade.php | (85) | STATUS_APPROVED_BY_COORDINATOR only | Performance: approval rate per team member |
| AnnualReportService, HalfYearlyReportService | get*Reports | STATUS_APPROVED_BY_COORDINATOR only | Quarterly/HalfYearly/Annual report eligibility — different report models |

---

## Canonical Comparison Matrix

### Project Approved

| Inline Location | vs ProjectStatus::APPROVED_STATUSES | Classification |
|-----------------|-------------------------------------|----------------|
| ExecutorController, ProjectQueryService, ProvincialController, BudgetReconciliationController | Same 3 statuses | EXACT_MATCH |
| ExportController provincial | Includes SUBMITTED, REVERTED_BY_COORD — not approved-only | SEMANTICALLY_DIFFERENT (access control, not approval filter) |
| ExportController coordinator | Includes FORWARDED, REVERTED_BY_COORD | SEMANTICALLY_DIFFERENT (access control) |

### Project Reverted

| Inline Location | vs ProjectStatus::isReverted() | Classification |
|-----------------|-------------------------------|----------------|
| ProjectQueryService getReverted* | Same 8 statuses | EXACT_MATCH |

### Report Pending / Editable

| Inline Location | vs DPReport::getDashboardStatusKeys / isEditable() | Classification |
|-----------------|---------------------------------------------------|----------------|
| ExecutorController pendingReports default | Pending = draft + submitted + 7 reverted (missing TO_PROVINCIAL, TO_COORDINATOR) | PARTIAL_MATCH — subset; Executor "pending" intentionally narrower |
| ExecutorController getActionItems, getReportsRequiringAttention | 6–7 reverted only | SUBSET — intentionally narrow |
| ProvincialController editableStatuses | Same as DPReport::isEditable() inline | EXACT_MATCH |
| GeneralController pendingStatuses | 8 statuses: forwarded, submitted, 6 reverted | PARTIAL_MATCH — different semantic (General "pending" = actionable by General) |
| ProvincialController [SUBMITTED, REVERTED_BY_COORD] | "Awaiting provincial forward" — 2 statuses only | SEMANTICALLY_DIFFERENT — role-specific queue, not generic pending |
| ProvincialController totalSubmittedReports | SUBMITTED + FORWARDED + APPROVED_BY_COORD | SEMANTICALLY_DIFFERENT — pipeline metric |

### Report Reverted

| Inline Location | vs DPReport isEditable reverted portion | Classification |
|-----------------|-----------------------------------------|----------------|
| ExecutorController (multiple) | 6–8 reverted; some omit TO_PROVINCIAL, TO_COORDINATOR | PARTIAL_MATCH — inconsistent subsets |
| ProvincialController editableStatuses | Same as DPReport | EXACT_MATCH |

### Report Approved (Subset Usages)

| Inline Location | vs DPReport::APPROVED_STATUSES | Classification |
|-----------------|--------------------------------|----------------|
| GeneralController approvedStatuses | 2 of 3 — excludes APPROVED_BY_GENERAL_AS_PROVINCIAL | SUBSET — intentional for coordinator-hierarchy view |
| BudgetValidationService | 1 of 3 — STATUS_APPROVED_BY_COORDINATOR only | SUBSET — would change financial classification |
| team-overview.blade.php | Same 1 of 3 | SUBSET |
| GeneralController calculateBudgetData | 1 status passed as param | SUBSET — role-specific approved definition |

---

## Role-Based Impact Analysis

### Executor

| Feature | Current Inline | Centralization Effect | Classification |
|---------|----------------|------------------------|----------------|
| Dashboard KPIs (approved count, budget) | ProjectQueryService inline approved | Replace with ProjectStatus::APPROVED_STATUSES | NO_BEHAVIOR_CHANGE |
| Status charts (approved filter) | Same | Same | NO_BEHAVIOR_CHANGE |
| Action items (pending reports) | 6–7 reverted + draft | If use DPReport::getEditableStatuses or similar, would include TO_PROVINCIAL, TO_COORDINATOR | COUNT_CHANGE — more reports in "pending" |
| Report counts (reportStatusSummary) | DPReport::getDashboardStatusKeys, APPROVED_STATUSES | Already canonical | NO_BEHAVIOR_CHANGE |
| Pending counts | Hardcoded draft + submitted + forwarded + revertedCount (str_starts_with) | str_starts_with is heuristic; centralizing reverted to helper would align | NO_BEHAVIOR_CHANGE |
| Reverted counts | str_starts_with('reverted_') | Replace with DPReport helper; need getRevertedStatuses() | Potential VISIBILITY_CHANGE if helper includes statuses not prefixed |
| Filters (status filter) | approvedStatuses inline | ProjectStatus::APPROVED_STATUSES | NO_BEHAVIOR_CHANGE |
| Authorization | N/A for status centralization | N/A | — |
| Report list / pending reports page | Pending = draft + submitted + 7 reverted | If canonical "pending" = draft + submitted + forwarded + all 8 reverted, scope widens | COUNT_CHANGE — items with REVERTED_TO_PROVINCIAL, REVERTED_TO_COORDINATOR would appear |

**Executor verdict:** NO_BEHAVIOR_CHANGE for approved/reverted project logic if ProjectStatus helpers used. COUNT_CHANGE for report pending if "pending" is widened to include all 8 reverted statuses.

### Provincial

| Feature | Current Inline | Centralization Effect | Classification |
|---------|----------------|------------------------|----------------|
| Dashboard approved totals | Inline [3 approved] | ProjectStatus::APPROVED_STATUSES | NO_BEHAVIOR_CHANGE |
| Pending approvals (reports) | [SUBMITTED_TO_PROVINCIAL, REVERTED_BY_COORDINATOR] | No canonical "awaiting provincial" — this is role-specific | LOGIC_DEPENDENCY_RISK — must NOT replace with generic pending |
| Approval queue (reports) | Same 2 statuses | Same | LOGIC_DEPENDENCY_RISK |
| totalSubmittedReports | SUBMITTED + FORWARDED + APPROVED_BY_COORD | Role-specific metric | LOGIC_DEPENDENCY_RISK |
| Editable statuses | Same as DPReport::isEditable | DPReport::getEditableStatuses() (new helper) | NO_BEHAVIOR_CHANGE |
| Export PDF access | [SUBMITTED, REVERTED_BY_COORD, APPROVED] | If replaced with APPROVED_STATUSES only, provincial loses access to non-approved | AUTH_CHANGE — BREAKING |

**Provincial verdict:** REQUIRES_ROLE_REVIEW. Approval queue and "awaiting provincial" lists are intentionally narrow. Export authorization would break if APPROVED_STATUSES used.

### Coordinator

| Feature | Current Inline | Centralization Effect | Classification |
|---------|----------------|------------------------|----------------|
| Dashboard approved projects | Project::approved() → ProjectStatus::APPROVED_STATUSES | Already uses model scope | NO_BEHAVIOR_CHANGE |
| Report approval counts | DPReport::APPROVED_STATUSES | Already canonical | NO_BEHAVIOR_CHANGE |
| Pending reports | STATUS_FORWARDED_TO_COORDINATOR | Single status; no array | N/A |
| Export PDF access | [FORWARDED, APPROVED, REVERTED_BY_COORD] | If replaced with APPROVED_STATUSES only, coordinator loses access to forwarded/reverted | AUTH_CHANGE — BREAKING |
| Unapproved expenses | STATUS_FORWARDED_TO_COORDINATOR | Single status | N/A |

**Coordinator verdict:** NO_BEHAVIOR_CHANGE for dashboard KPIs. AUTH_CHANGE for export if centralization applied to ExportController.

### General

| Feature | Current Inline | Centralization Effect | Classification |
|---------|----------------|------------------------|----------------|
| Approved reports (coordinator hierarchy) | [APPROVED_BY_COORD, APPROVED_BY_GENERAL_AS_COORD] | DPReport::APPROVED_STATUSES adds APPROVED_BY_GENERAL_AS_PROVINCIAL | COUNT_CHANGE — reports approved by provincial would appear in coordinator view |
| Budget calculation (coordinator vs direct) | STATUS_APPROVED_BY_COORDINATOR for both branches | If DPReport::APPROVED_STATUSES, all 3 approved count | COUNT_CHANGE, FINANCIAL — approved expenses would increase |
| Pending statuses | 8 statuses (forwarded, submitted, 6 reverted) | Generic "pending" helper would match | NO_BEHAVIOR_CHANGE if semantic match |
| Export | Uses ProjectPermissionHelper; no direct status array in General branch | N/A | — |

**General verdict:** COUNT_CHANGE and FINANCIAL impact. General intentionally uses 2 approved statuses for coordinator hierarchy; 3 for direct team. Centralizing to DPReport::APPROVED_STATUSES would widen coordinator-hierarchy scope.

### Admin

| Feature | Current Inline | Centralization Effect | Classification |
|---------|----------------|------------------------|----------------|
| BudgetReconciliationController | Inline [3 approved] | ProjectStatus::APPROVED_STATUSES | NO_BEHAVIOR_CHANGE |
| AdminReadOnlyController | [FORWARDED, SUBMITTED] for report classification | Role-specific; no generic helper | LOGIC_DEPENDENCY_RISK |

### Report Submitters (Executor / Applicant)

| Feature | Current Inline | Centralization Effect | Classification |
|---------|----------------|------------------------|----------------|
| Report submit eligibility | ReportStatusService allowedStatuses | Uses ReportStatusService; transition logic | N/A for centralization |
| Pending reports list | ExecutorController | As per Executor | COUNT_CHANGE if pending widened |

---

## Approval Flow Impact

| Component | Uses Inline Arrays? | Canonical? | Centralization Impact |
|-----------|---------------------|------------|------------------------|
| ProjectStatusService | Uses ProjectStatus constants directly; allowedStatuses arrays for transition validation | Yes — constants | NO — approval flow does not use inline "approved" arrays |
| ReportStatusService | Uses DPReport constants; allowedStatuses for from-status checks | Yes — constants | NO — transition logic uses constants |
| CoordinatorController approve | ProjectStatusService::approve | N/A | NO |
| ProvincialController forward | ProjectStatusService::forwardToCoordinator | N/A | NO |
| GeneralController approve/revert | ProjectStatusService | N/A | NO |

**Conclusion:** Approval flow does not depend on inline approved/reverted arrays. ProjectStatusService and ReportStatusService use constants and their own allowedStatuses for transitions. Centralizing dashboard/filter arrays would not affect approval transitions.

---

## Financial Flow Impact

| Component | Status Usage | Centralization Effect |
|-----------|--------------|------------------------|
| ProjectFinancialResolver | project->isApproved() → ProjectStatus::isApproved() | No inline array; already canonical |
| ExecutorController calculateBudgetSummariesFromProjects | report->isApproved() → DPReport::isApproved() → APPROVED_STATUSES | No inline; already canonical |
| BudgetValidationService::getBudgetSummary | report->status === STATUS_APPROVED_BY_COORDINATOR | SUBSET — only coordinator-approved. If switched to report->isApproved(), APPROVED_BY_GENERAL_AS_PROVINCIAL and APPROVED_BY_GENERAL_AS_COORDINATOR would count | FINANCIAL — approved expenses would increase; unapproved would decrease |
| GeneralController calculateBudgetData | STATUS_APPROVED_BY_COORDINATOR passed as "approved" param | Intentional role-specific. If DPReport::APPROVED_STATUSES, all 3 approved | FINANCIAL — approved expenses would increase for both coordinator and direct team |
| CoordinatorController budget summaries | DPReport::APPROVED_STATUSES | Already canonical | NO |
| ProvincialController budget | ProjectStatus::APPROVED_STATUSES, DPReport::APPROVED_STATUSES | Already canonical where used | NO |

**Conclusion:** BudgetValidationService and GeneralController use a subset (1–2 approved statuses). Centralizing to DPReport::APPROVED_STATUSES would increase approved expenses and decrease unapproved — FINANCIAL CALCULATION CHANGE.

---

## Filter & Export Impact

### Status Filters in Dashboards

| Dashboard | Filter Type | Inline Array | Centralization |
|-----------|-------------|--------------|----------------|
| Executor | show=approved/needs_work/all | approvedStatuses, editableStatuses | ProjectStatus helpers — NO_BEHAVIOR_CHANGE |
| Provincial | Approved totals | Same 3 | ProjectStatus::APPROVED_STATUSES — NO_BEHAVIOR_CHANGE |
| Coordinator | Approved scope | Project::approved() | N/A |

### Status Filters in List Pages

| Page | Filter | Inline | Centralization |
|------|--------|--------|----------------|
| Executor pending reports | status=draft, reverted, or specific | Multiple arrays | If canonical getPendingStatuses() exists and matches current semantics — NO_CHANGE. If broader — COUNT_CHANGE. |
| Provincial approval queue | [SUBMITTED, REVERTED_BY_COORD] | Role-specific | Must NOT use generic pending — LOGIC_DEPENDENCY_RISK |

### Status Filters in Exports

| Export | Authorization Logic | Inline | Centralization |
|--------|---------------------|--------|----------------|
| ExportController downloadPdf | Provincial: [SUBMITTED, REVERTED_BY_COORD, APPROVED]; Coordinator: [FORWARDED, APPROVED, REVERTED_BY_COORD] | Role-specific access sets | If replaced with APPROVED_STATUSES only: provincial loses access to SUBMITTED and REVERTED_BY_COORD; coordinator loses FORWARDED and REVERTED_BY_COORD | BREAKING — AUTH_CHANGE |

### Pagination

Status filters affect query results. If centralization widens scope (e.g. pending reports), pagination counts would increase. If narrows (e.g. export auth), fewer projects would be downloadable.

---

## Edge Case Findings

### 1. New Status Added

Centralization reduces break risk: one update in ProjectStatus or DPReport propagates to all consumers. Currently, 15+ locations must be updated manually.

### 2. Intentionally Narrow Arrays

| Location | Intentional? | Reason |
|----------|--------------|--------|
| ProvincialController [SUBMITTED, REVERTED_BY_COORD] | Yes | "Reports awaiting provincial to forward" — not all pending |
| GeneralController approvedStatuses (2 of 3) | Yes | Coordinator hierarchy excludes provincial-approved; direct team uses coordinator-approved |
| ExecutorController getActionItems pending (6 reverted) | Unclear | May omit TO_PROVINCIAL, TO_COORDINATOR because executor cannot act on those |
| ExportController access lists | Yes | Role-specific download access — includes transitional statuses |
| BudgetValidationService STATUS_APPROVED_BY_COORDINATOR | Unclear | May be legacy or intentional narrow scope |
| team-overview approval rate | Likely bug | Uses 1 of 3 approved; approval rate undercounted |

### 3. Centralization Would Unintentionally Widen Scope

| Location | Current Scope | Canonical Would Add |
|----------|---------------|---------------------|
| GeneralController approvedStatuses | 2 approved | APPROVED_BY_GENERAL_AS_PROVINCIAL — reports in coordinator view |
| BudgetValidationService | 1 approved | 2 more — approved expenses increase |
| team-overview blade | 1 approved | 2 more — approval rate recalculated |
| ExecutorController pending (if use getEditableStatuses) | 6–7 reverted | REVERTED_TO_PROVINCIAL, REVERTED_TO_COORDINATOR — more "pending" |

### 4. Centralization Would Unintentionally Narrow Scope

| Location | Current Scope | Canonical Would Remove |
|----------|---------------|------------------------|
| ExportController provincial | SUBMITTED, REVERTED_BY_COORD, APPROVED | Provincial could no longer download SUBMITTED or REVERTED_BY_COORD |
| ExportController coordinator | FORWARDED, APPROVED, REVERTED_BY_COORD | Coordinator could no longer download FORWARDED or REVERTED_BY_COORD |
| ProvincialController approval queue | SUBMITTED, REVERTED_BY_COORD | Replacing with generic pending would break queue semantics |

---

## Risk Classification

**Overall: REQUIRES_ROLE_REVIEW**

### Justification

1. **Project approved / reverted centralization:** SAFE. All inline project approved arrays (except ExportController) match ProjectStatus::APPROVED_STATUSES. Project reverted match ProjectStatus::isReverted(). Replacing with helpers = NO_BEHAVIOR_CHANGE for dashboards, KPIs, queries.

2. **ExportController:** HIGH_RISK. Access control uses role-specific status sets that include transitional statuses. Replacing with APPROVED_STATUSES would BREAK export for provincial and coordinator. Must remain role-specific.

3. **Provincial approval queue:** HIGH_RISK. [SUBMITTED, REVERTED_BY_COORD] is intentionally narrow. No generic "pending" helper can replace it without changing semantics.

4. **GeneralController approved / budget:** REQUIRES_ROLE_REVIEW. Intentional 2-status subset for coordinator hierarchy. Centralizing would change counts and financials. Need product/domain confirmation.

5. **BudgetValidationService:** REQUIRES_ROLE_REVIEW. Single-status approved check. May be bug (undercount) or intentional. Centralizing would change financial output.

6. **Executor pending reports:** SAFE_WITH_MINOR_COUNT_ADJUSTMENT. Current pending omits REVERTED_TO_PROVINCIAL, REVERTED_TO_COORDINATOR. If that is intentional (executor cannot act), centralizing to getEditableStatuses would add them — COUNT_CHANGE. If unintentional, centralization fixes undercount.

7. **Report reverted centralization:** SAFE if DPReport gets getRevertedStatuses() matching isEditable reverted portion. ExecutorController uses inconsistent subsets (6 vs 7 vs 8) — consolidation would standardize; need to confirm intended semantics.

8. **team-overview blade:** COUNT_CHANGE (approval rate would increase) — likely a bug fix. Using 1 approved undercounts.

---

## Final Recommendation

**Do not centralize blindly.** Different contexts use intentionally different status sets:

- **Safe to centralize:** Project approved (Executor, Provincial, Coordinator, Admin dashboards), project reverted (ProjectQueryService), report status in getReportStatusSummary, Executor show filter.
- **Do not centralize:** ExportController access lists (role-specific), ProvincialController approval-queue lists (awaiting provincial), GeneralController approved reports for coordinator hierarchy (intentional subset).
- **Requires review before centralizing:** BudgetValidationService (financial impact), GeneralController budget params (financial impact), ExecutorController pending report lists (clarify executor scope), team-overview blade (likely bug).

**Suggested approach:**
1. Centralize project approved/reverted in ProjectQueryService and dashboard controllers (excluding ExportController).
2. Add DPReport::getEditableStatuses() and DPReport::getRevertedStatuses() as canonical helpers.
3. Keep ExportController and Provincial approval-queue logic role-specific; document as intentional.
4. Review GeneralController, BudgetValidationService, and Executor pending semantics with domain owner before centralizing report approved/pending logic.
