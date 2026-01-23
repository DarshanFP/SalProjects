# Comprehensive Reports Review — All Project Types & Report Types

**Document Version:** 1.0  
**Date:** January 2025  
**Scope:** All reporting features across Monthly, Quarterly (program-specific), and Aggregated reports  
**Source:** Codebase analysis, `Documentations/REVIEW/5th Review/Report Views`, and related MD files

---

## 1. Executive Summary

The SalProjects application implements **three reporting streams**:

| Report Stream | Project/Source Types | Status | Primary Documentation |
|---------------|----------------------|--------|------------------------|
| **Monthly Reports** | 12 project types (8 Institutional + 4 Individual) | ✅ Complete, in use | Report Views, Save Draft, Expenses |
| **Quarterly (Program-Specific)** | 5 program types | ✅ Implemented | Controllers, routes, views |
| **Aggregated (Q/HY/Annual)** | Built from approved **Monthly** reports | ✅ Implemented | Aggregated controllers, comparison |

---

## 2. Documentation Sources Reviewed

### 2.1 Report Views & Enhancements (`Documentations/REVIEW/5th Review/Report Views/`)

| Document | Purpose | Tasks/Status |
|----------|---------|--------------|
| `IMPLEMENTATION_COMPLETE.md` | Field indexing & Activity Card UI | All 12 phases done; 50+ files; 12 project types |
| `User_Guide.md` | End-user guide for indexing & cards | Current |
| `Reports Save Draft/Reports_Save_Draft_Completion_Summary.md` | Save as Draft for Monthly | Create & Edit; Backend + Frontend |
| `Reports Save Draft/Phase_Wise_Implementation_Plan_Reports_Save_Draft.md` | Implementation plan | Completed |
| `expenses tracking/Approved_Unapproved_Expenses_Implementation_Plan.md` | Approved vs Unapproved expenses | DPReport helpers, views, BudgetValidationService |
| `expenses tracking/Approved_Unapproved_Expenses_Tracking_Analysis.md` | Analysis | — |
| `Phase_11_Integration_Testing_Checklist.md` | Integration testing | Manual testing scenarios |
| `Phase_12_*` | Documentation & cleanup | Complete |

### 2.2 Management & Manual Kit

| Document | Relevance |
|----------|-----------|
| `Documentations/Manual Kit/Management_Report_Application_Enhancements.md` | High-level: Save Draft, Aggregated, Comparison, Export, Activity History, Report display |
| `Documentations/Manual Kit/Executor_User_Manual.md` | Executor report workflows |

### 2.3 Other Report-Related (REVIEW Final, etc.)

- `ReportQueryService`, `ReportStatusService` usage
- `ReportController`, `ExportReportController` (Monthly)
- `ReportComparisonController`, `AggregatedReportExportController`

---

## 3. Monthly Reports — Full Review

### 3.1 Overview

- **Model:** `App\Models\Reports\Monthly\DPReport`  
- **Table:** `DP_Reports`  
- **Controller:** `App\Http\Controllers\Reports\Monthly\ReportController`  
- **Routes prefix:** `reports/monthly`

### 3.2 Project Types Supported (12)

**Institutional (8):**

1. Development Projects  
2. Livelihood Development Projects (LDP) — includes **Livelihood Annexure**  
3. Residential Skill Training Proposal 2 — includes **Residential Skill Training** section  
4. PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER — includes **Crisis Intervention Center** section  
5. CHILD CARE INSTITUTION  
6. Rural-Urban-Tribal  
7. Institutional Ongoing Group Educational proposal — includes **Institutional Ongoing Group** section  
8. NEXT PHASE - DEVELOPMENT PROPOSAL  

**Individual (4):**

1. Individual - Livelihood Application (ILP)  
2. Individual - Access to Health (IAH)  
3. Individual - Ongoing Educational support (IES)  
4. Individual - Initial - Educational support (IIES)  

### 3.3 Statements of Account — Project-Type Mapping

| Project Type | Partial (create/edit/view) | Notes |
|--------------|----------------------------|-------|
| Development Projects | `development_projects` | Default/fallback |
| Livelihood Development Projects | `development_projects` | + Livelihood Annexure |
| Individual - Livelihood Application | `individual_livelihood` | |
| Individual - Access to Health | `individual_health` | |
| Individual - Ongoing Educational support | `individual_ongoing_education` | |
| Individual - Initial - Educational support | `individual_education` | |
| Institutional Ongoing Group Educational proposal | `institutional_education` | |
| Institutional - Initial - Educational support | `institutional_education` | (in some views) |
| Residential Skill Training Proposal 2 | `development_projects` | + RST section |
| PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER | `development_projects` | + CIC section |
| CHILD CARE INSTITUTION | `development_projects` | |
| Rural-Urban-Tribal | `development_projects` | |
| NEXT PHASE - DEVELOPMENT PROPOSAL | (fallback) | → `development_projects` |

**Partials location:**
- Create: `reports.monthly.partials.statements_of_account.*` and `partials.create.statements_of_account`
- Edit: `reports.monthly.partials.edit.statements_of_account.*`
- View: `reports.monthly.partials.view.statements_of_account.*`

### 3.4 Project-Type-Specific Sections (View/Create/Edit)

| Project Type | Extra Section | Partial / Controller |
|--------------|---------------|----------------------|
| Livelihood Development Projects | Livelihood Annexure (Impact Groups) | `LivelihoodAnnexure.blade.php`, `LivelihoodAnnexureController` |
| Institutional Ongoing Group Educational proposal | Institutional Ongoing Group (Age Profiles) | `institutional_ongoing_group`, `InstitutionalOngoingGroupController`, `RQISAgeProfile` |
| Residential Skill Training Proposal 2 | Residential Skill Training (Trainee Profiles) | `residential_skill_training`, `ResidentialSkillTrainingController`, `RQSTTraineeProfile` |
| PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER | Crisis Intervention Center (Inmate Profiles) | `crisis_intervention_center`, `CrisisInterventionCenterController`, `RQWDInmatesProfile` |

### 3.5 Monthly Report Workflow & Statuses

**Statuses (DPReport):**
- `draft`  
- `submitted_to_provincial`  
- `reverted_by_provincial`  
- `forwarded_to_coordinator`  
- `reverted_by_coordinator`  
- `approved_by_coordinator`  
- `rejected_by_coordinator`  
- `approved_by_general_as_coordinator`, `reverted_by_general_as_coordinator`  
- `approved_by_general_as_provincial`, `reverted_by_general_as_provincial`  
- `reverted_to_executor`, `reverted_to_applicant`, `reverted_to_provincial`, `reverted_to_coordinator`  

**Actions:** Submit to Provincial, Forward to Coordinator, Approve, Revert; with `ReportStatusService` and `ActivityHistoryService`.

### 3.6 Completed Features (Monthly)

| Feature | Status | Notes |
|---------|--------|-------|
| **Create** | ✅ | `create(project_id)`, `ReportAll.blade.php`, project-type-specific partials |
| **Store** | ✅ | `StoreMonthlyReportRequest`, draft support |
| **Edit** | ✅ | `edit(report_id)`, `edit.blade.php`, `UpdateMonthlyReportRequest` |
| **Update** | ✅ | Draft support, activity logging |
| **Show** | ✅ | `show.blade.php`, view partials, activity history, action buttons |
| **Save as Draft** | ✅ | Create & Edit; `save_as_draft`; conditional validation; no notifications for draft |
| **Submit / Forward / Approve / Revert** | ✅ | `ReportStatusService`, notifications |
| **Field indexing** | ✅ | Outlook, Statements, Photos, Activities, Attachments, LDP Annexure |
| **Activity Card UI** | ✅ | Collapsible cards, status (Empty / In Progress / Complete) |
| **Approved vs Unapproved expenses** | ✅ | `DPReport::getApprovedExpenses()`, `getUnapprovedExpenses()`, `isApproved()` |
| **Activity history** | ✅ | `activity_history.blade.php`, `ActivityHistoryService::logReportCreate/Update` |
| **Attachments** | ✅ | `ReportAttachmentController`, add/remove/download |
| **Photos** | ✅ | Add/remove, `ReportController::removePhoto` |
| **PDF / DOC export** | ✅ | `ExportReportController::downloadPdf`, `downloadDoc` |
| **Comments** | ✅ | `ReportComment`, `comments` on `DPReport` |

### 3.7 Supporting Services & Models (Monthly)

- **ReportQueryService:** `getProjectIdsForUser`, `getReportsForUserQuery`, `getReportsForUser`, `getReportsForUserByStatus`  
- **ReportStatusService:** `submitToProvincial`, `forwardToCoordinator`, `approve`, `revert`, etc.  
- **ActivityHistoryService:** `logReportCreate`, `logReportUpdate`  
- **BudgetCalculationService:** `getBudgetsForReport`  
- **Models:** `DPReport`, `DPObjective`, `DPActivity`, `DPAccountDetail`, `DPPhoto`, `DPOutlook`, `ReportAttachment`, `QRDLAnnexure`, `RQISAgeProfile`, `RQSTTraineeProfile`, `RQWDInmatesProfile`  

### 3.8 Routes (Monthly)

- `reports/monthly/create/{project_id}` (create)  
- `reports/monthly/store` (store)  
- `reports/monthly/index` (index)  
- `reports/monthly/edit/{report_id}` (edit)  
- `reports/monthly/update/{report_id}` (update)  
- `reports/monthly/review/{report_id}`, `revert`, `submit`, `forward`, `approve`  
- `show/{report_id}` (under shared auth)  
- `reports/monthly/downloadPdf/{report_id}`, `downloadDoc/{report_id}`  
- Livelihood annexure: show, edit, update  
- Attachments: remove; photos: remove  

---

## 4. Quarterly Reports (Program-Specific) — Full Review

### 4.1 Overview

Five **separate** quarterly report programs, each with own controller, views, and (where used) legacy `Old*` models. **Not** built from monthly reports.

### 4.2 Program Types & Implementation

| Program | Route Prefix | Controller | Views | Models / Tables |
|---------|--------------|------------|-------|------------------|
| **Development Project** | `reports/quarterly/development-project` | `DevelopmentProjectController` | `quarterly/developmentProject/` (list, reportform, show) | OldDevelopmentProject, RQDP* (e.g. rqdp_reports) |
| **Development Livelihood** | `reports/quarterly/development-livelihood` | `DevelopmentLivelihoodController` | `quarterly/developmentLivelihood/` | — |
| **Institutional Support** | `reports/quarterly/institutional-support` | `InstitutionalSupportController` | `quarterly/institutionalSupport/` | — |
| **Skill Training** | `reports/quarterly/skill-training` | `SkillTrainingController` | `quarterly/skillTraining/` | — |
| **Women in Distress** | `reports/quarterly/women-in-distress` | `WomenInDistressController` | `quarterly/womenInDistress/` | — |

### 4.3 Common CRUD & Workflow (Per Program)

- **Create:** `create` (project/program id from each program’s context)  
- **Store:** `store`  
- **Edit:** `{id}/edit`  
- **Update:** `put {id}`  
- **Review:** `{id}/review`  
- **Revert:** `post {id}/revert`  
- **List:** `list` (index)  
- **Show:** `{id}` (show)  

### 4.4 Completed vs Gaps (Quarterly Program-Specific)

| Item | Status | Notes |
|------|--------|-------|
| CRUD + Review + Revert | ✅ | Implemented per program |
| List & Show views | ✅ | Per program |
| **Save as Draft** | ❌ | Not in program-specific quarterlies (only in Monthly) |
| **Field indexing / Activity cards** | ⚠️ | Per IMPLEMENTATION_COMPLETE, focus was Monthly; quarterlies may not have same enhancements |
| **Unified project type set** | — | These use program-specific entities, not the 12 monthly project types |

---

## 5. Aggregated Reports (Quarterly, Half-Yearly, Annual) — Full Review

### 5.1 Overview

- **Built from:** Approved **monthly reports** (`DPReport` with `approved_by_coordinator` or equivalent).  
- **Source projects:** `App\Models\OldProjects\Project` (same as Monthly).  
- **Models:** `QuarterlyReport`, `HalfYearlyReport`, `AnnualReport` (+ Detail, AI, etc.).

### 5.2 Report Types

| Type | Prefix | Controller | Create From |
|------|--------|------------|-------------|
| **Aggregated Quarterly** | `reports/aggregated/quarterly` | `AggregatedQuarterlyReportController` | Approved monthly reports for the quarter |
| **Aggregated Half-Yearly** | `reports/aggregated/half-yearly` | `AggregatedHalfYearlyReportController` | Approved monthly reports for the half-year |
| **Aggregated Annual** | `reports/aggregated/annual` | `AggregatedAnnualReportController` | Approved monthly reports for the year |

### 5.3 Features

| Feature | Status | Notes |
|---------|--------|-------|
| Index | ✅ | Role-based (executor, applicant, provincial, coordinator) |
| Create | ✅ | `create/{project_id}`, choice of monthly reports to aggregate |
| Store | ✅ | Quarter/year or equivalent, optional `use_ai` |
| Show | ✅ | `show/{report_id}` |
| Edit (AI) | ✅ | `edit-ai/{report_id}`, `update-ai` |
| Export PDF | ✅ | `export-pdf/{report_id}` |
| Export Word | ✅ | `export-word/{report_id}` |
| **Comparison** | ✅ | `ReportComparisonController`: quarterly-form, half-yearly-form, annual-form; compare Quarter/HY/Annual |

### 5.4 Comparison

- Routes: `reports/aggregated/quarterly/compare`, `half-yearly/compare`, `annual/compare` (GET/POST).  
- Views: `aggregated/comparison/quarterly-form`, `quarterly-result`, `half-yearly-form`, `half-yearly-result`, `annual-form`, `annual-result`.

### 5.5 Project Types

Aggregated reports are **project-agnostic** at the type level: any project with approved monthly reports can have aggregated Q/HY/Annual. The 12 monthly project types are reflected only indirectly via the underlying monthly data.

---

## 6. Role-Based Access & Integration

### 6.1 Roles and Reports

- **Executor / Applicant:** Create, edit, submit monthly; create/view aggregated; program-specific quarterly per program access.  
- **Provincial:** Forward, revert monthly; view/forward program quarterlies; view aggregated.  
- **Coordinator:** Approve, revert monthly; view all; view aggregated.  
- **General:** Can act as Coordinator or Provincial (approve/revert/forward) per existing logic.  
- **Admin:** Broad access (handled in admin routes).

### 6.2 Shared Routes (auth + executor, applicant, provincial, coordinator, general)

- `show/{report_id}` (monthly)  
- `reports/monthly/attachments/download/{id}`  
- `reports/{report_id}/activity-history`  
- `reports/monthly/downloadPdf/{report_id}`, `downloadDoc/{report_id}`  

### 6.3 Coordinator / Provincial / General

- `coordinator/reports/{type}/{id}`, `provincial/reports/{type}/{id}`  
- `general/reports/pending`, `general/reports/approved`, `general/reports/bulk-action`, `general/report/{report_id}/approve`  

---

## 7. Tasks Completed (from MD Files)

### 7.1 Report Views Enhancement (IMPLEMENTATION_COMPLETE.md)

- Phase 1–10: Field indexing (Outlook, Statements, Photos, Activities, Attachments, LDP Annexure) and Activity Card UI.  
- Phase 11: Integration testing (checklist, scripts, issues tracking).  
- Phase 12: Documentation and cleanup.  
- **Outcome:** 50+ files, 12 project types, create + edit.

### 7.2 Save Draft (Reports_Save_Draft_Completion_Summary.md)

- Store/Update: `save_as_draft` handling, `draft` status, conditional validation, no notifications for draft.  
- Create/Edit UI: “Save as Draft” + “Submit Report” / “Update Report”.  
- Activity history: `logReportCreate`, `logReportUpdate` with `previousStatus`.  
- Show: Activity history partial, Edit / Submit to Provincial buttons.  

### 7.3 Approved vs Unapproved Expenses (Approved_Unapproved_Expenses_*)

- `DPReport::isApproved()`, `getApprovedExpenses()`, `getUnapprovedExpenses()` (implemented in `DPReport`).  
- Plan exists for BudgetValidationService and views; implementation level in views not re-verified here.

### 7.4 Management Report Enhancements (Management_Report_Application_Enhancements.md)

- Save as Draft, Aggregated (Q/HY/Annual), Report Comparison, Export (PDF/DOC), Activity History, improved report display — all align with implemented features.

---

## 8. What Is Working (Summary)

### 8.1 Monthly Reports

- Full lifecycle: create, store (including draft), edit, update (including draft), show, submit, forward, approve, revert.  
- All 12 project types with correct Statements of Account partials and type-specific sections (LDP, IOGEP, RST, CIC).  
- Field indexing and Activity Card UI in create/edit.  
- Approved/unapproved expense helpers on `DPReport`.  
- Attachments, photos, comments, activity history.  
- PDF/DOC export.  

### 8.2 Quarterly (Program-Specific)

- Full CRUD, review, revert, list, show for all 5 programs.  
- Program-specific forms and show views.

### 8.3 Aggregated

- Create from approved monthly reports for Q/HY/Annual.  
- Show, AI edit, PDF/Word export.  
- Comparison for quarterly, half-yearly, annual.

### 8.4 Cross-Cutting

- `ReportQueryService`, `ReportStatusService`, `ActivityHistoryService`.  
- Notifications on submit/forward/approve/revert (excluding draft).  
- Role-based visibility and actions.

---

## 9. Gaps and Limitations

| Area | Gap | Severity |
|------|-----|----------|
| **Quarterly (program-specific)** | No Save as Draft | Low (documents note “lower priority”) |
| **Quarterly (program-specific)** | Field indexing / Activity cards not confirmed | Low (if only used in Monthly) |
| **ReportAll vs statements_of_account** | `ReportAll` uses some `@elseif` for `Institutional - Initial - Educational support`; `statements_of_account` map uses `Institutional Ongoing Group`; ensure no missing/duplicate mapping | Medium (consistency) |
| **NEXT PHASE - DEVELOPMENT PROPOSAL** | Not in every `projectTypeMap`; relies on fallback to `development_projects` | Low if fallback is desired |
| **Manual testing** | IMPLEMENTATION_COMPLETE marks manual testing (all 12 types, cross-browser) as pending | Medium |
| **Expenses in views** | Full rollout of approved/unapproved in all 19 views per plan not re-verified | Low–Medium |

---

## 10. Recommendations

1. **Consolidate project-type → partial mapping** in one place (e.g. a helper or config) used by `ReportAll`, `edit`, `create`/`edit`/`view` `statements_of_account` to avoid drift.  
2. **Explicitly add** `NEXT PHASE - DEVELOPMENT PROPOSAL` to mappings where the intent is `development_projects`.  
3. **Run Phase 11 manual tests** for all 12 monthly project types and for create/edit/view.  
4. **Consider extending Save as Draft** to program-specific quarterly reports if product prioritizes it.  
5. **Keep** `Documentations/V1/Reports/` as the main place for report-specific docs (this review, future checklists, and change logs).

---

## 11. Quick Reference — Report Types and Project Types

### 11.1 Monthly: 12 Project Types

- Development Projects, Livelihood Development Projects, Residential Skill Training Proposal 2, PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER, CHILD CARE INSTITUTION, Rural-Urban-Tribal, Institutional Ongoing Group Educational proposal, NEXT PHASE - DEVELOPMENT PROPOSAL.  
- Individual - Livelihood Application, Individual - Access to Health, Individual - Ongoing Educational support, Individual - Initial - Educational support.

### 11.2 Monthly: 7 Statements of Account Partials

- `development_projects`, `individual_livelihood`, `individual_health`, `individual_education`, `individual_ongoing_education`, `institutional_education`; fallback: `development_projects`.

### 11.3 Quarterly (Program-Specific): 5 Programs

- development-project, development-livelihood, institutional-support, skill-training, women-in-distress.

### 11.4 Aggregated: 3 Periods

- Quarterly, Half-Yearly, Annual (from approved monthly reports).

---

## 12. Document Index (V1/Reports and related)

| Location | Document |
|----------|----------|
| `Documentations/V1/Reports/` | `COMPREHENSIVE_REPORTS_REVIEW.md` (this file) |
| `Documentations/REVIEW/5th Review/Report Views/` | `IMPLEMENTATION_COMPLETE.md`, `User_Guide.md`, Phase 11/12, expenses, Save Draft |
| `Documentations/Manual Kit/` | `Management_Report_Application_Enhancements.md`, `Executor_User_Manual.md` |

---

**End of Comprehensive Reports Review**
