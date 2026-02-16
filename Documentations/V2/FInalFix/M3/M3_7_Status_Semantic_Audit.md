# M3.7 — Final Semantic & Status Integrity Audit

**Milestone:** M3 — Resolver Parity & Financial Stability  
**Task:** Final Semantic & Status Integrity Audit  
**Mode:** STRICTLY READ-ONLY (No Code Changes)  
**Date:** 2025-02-15  
**Type:** Forensic Audit

---

## SECTION 1 — Negative Filtering Audit

### Search: `!= ProjectStatus::`, `where('status', '!=',`, `where('status', '<>',`, `whereNot('status',`

| File | Line | Full Condition | Purpose | Intended As | Risk |
|------|------|----------------|---------|-------------|------|
| `app/Http/Controllers/Projects/ProjectController.php` | 299 | `->where('status', '!=', ProjectStatus::APPROVED_BY_COORDINATOR)` | Executor index — exclude approved projects from project list | **A) Not approved** (show editable/pending only) | **HIGH** — Excludes only APPROVED_BY_COORDINATOR; projects approved by General (as coordinator/provincial) would still appear in executor list |
| `app/Helpers/ProjectPermissionHelper.php` | 159 | `$query->where('status', '!=', ProjectStatus::APPROVED_BY_COORDINATOR)` | getProjectsForUserQuery — exclude approved for executor/applicant | **A) Not approved** | **HIGH** — Same drift; should use `whereNotIn('status', ProjectStatus::APPROVED_STATUSES)` |
| `app/Http/Controllers/CoordinatorController.php` | 2502 | `$provincials->where('status', '!=', 'active')` | Team stats — User status (active/inactive), not Project status | **C) Other** — User model status | **LOW** — Different domain |
| `app/Http/Controllers/ExecutorController.php` | 622, 652 | `->where('status', '!=', DPReport::STATUS_DRAFT)` | Report queries — exclude draft reports | **C) Other** — DPReport draft filter | **LOW** — Report-specific |

### Potential Semantic Risk

- **ProjectController** and **ProjectPermissionHelper** use `!= ProjectStatus::APPROVED_BY_COORDINATOR`, excluding only one approved status. Projects approved by General as coordinator or provincial would not be excluded and would appear in executor project list, contradicting intent (A — not approved).
- **Recommended fix:** Use `whereNotIn('status', ProjectStatus::APPROVED_STATUSES)` for "not approved" intent.

---

## SECTION 2 — Manual Status Comparisons

### Occurrences referencing APPROVED_BY_COORDINATOR, APPROVED_BY_GENERAL_AS_COORDINATOR, APPROVED_BY_GENERAL_AS_PROVINCIAL

#### Aggregation logic

| File | Line | Usage | Category |
|------|------|-------|----------|
| `app/Http/Controllers/CoordinatorController.php` | 573, 1668, 1674, 1699, 1709, 1769, 1802, 1823, 1847, 1864, 1872, 2055, 2085, 2122, 2161, 2203, 2228, 2248, 2331, 2335, 2337, 2348, 2432, 2538, 2545, 2548, 2563, 2627, 2968, 2994 | `DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)` or collection filter | **Aggregation** — Dashboard/report metrics |
| `app/Http/Controllers/GeneralController.php` | 1752–1753, 3648, 3657 | `whereIn('status', [DPReport::STATUS_APPROVED_BY_COORDINATOR, DPReport::STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR])` | **Aggregation** — Approved reports query; missing `STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL` |
| `app/Http/Controllers/ProvincialController.php` | 1815, 2024, 2027, 2154, 2323 | `DPReport::STATUS_APPROVED_BY_COORDINATOR` | **Aggregation** — Dashboard/report metrics |
| `app/Http/Controllers/ExecutorController.php` | 293, 308, 851, 961, 1008 | `DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)` | **Aggregation** — Executor dashboard |
| `app/Services/Reports/AnnualReportService.php` | 170, 183, 198 | `where('status', STATUS_APPROVED_BY_COORDINATOR)` | **Aggregation** — Report aggregation |
| `app/Services/Reports/HalfYearlyReportService.php` | 170, 186 | Same | **Aggregation** |
| `app/Services/Reports/QuarterlyReportService.php` | 151 | Same | **Aggregation** |

#### Workflow logic

| File | Line | Usage | Category |
|------|------|-------|----------|
| `app/Http/Controllers/Projects/ProjectController.php` | 832, 843 | `ProjectStatus::APPROVED_BY_COORDINATOR` in status check | **Workflow** — Mark as completed guard |
| `app/Services/ProjectPhaseService.php` | 114 | `$project->status !== ProjectStatus::APPROVED_BY_COORDINATOR` | **Workflow** — Eligibility for completion; single-status check |
| `app/Services/ProjectStatusService.php` | 124–125, 232–233, 303, 307, 382–383, 514–515, 528–529, 681–682, 688, 694 | Project approval status assignment/checks | **Workflow** |
| `app/Services/ReportStatusService.php` | 138–139, 245–246, 413, 417, 492–493, 626–627, 640–641 | Report approval status assignment/checks | **Workflow** |

#### UI filtering

| File | Line | Usage | Category |
|------|------|-------|----------|
| `app/Http/Controllers/CoordinatorController.php` | 356, 524 | `$reportsQuery->where('status', $request->status)` | **UI filtering** — User-selected filter |
| `app/Http/Controllers/GeneralController.php` | 292, 523, 908, 1119–1120, 1303–1304 | `->where('status', $request->status)` | **UI filtering** |
| `app/Http/Controllers/ProvincialController.php` | 489 | `$projectsQuery->where('status', $request->status)` | **UI filtering** |

#### Export / reporting

| File | Line | Usage | Category |
|------|------|-------|----------|
| `app/Http/Controllers/Projects/ExportController.php` | 341, 352, 468, 479 | `ProjectStatus::APPROVED_BY_COORDINATOR` in download access check | **Export** — Permission guard for PDF/Word download |

#### Validation

| File | Line | Usage | Category |
|------|------|-------|----------|
| `app/Services/BudgetValidationService.php` | 79 | `$report->status === DPReport::STATUS_APPROVED_BY_COORDINATOR` | **Validation** — Only coordinator-approved reports counted as approved expenses; excludes General-as-approver |

#### Other

| File | Line | Usage | Category |
|------|------|-------|----------|
| `app/Constants/ProjectStatus.php` | 15, 19, 23, 36–38 | Constant definitions | **Definition** |
| `app/Http/Controllers/Admin/BudgetReconciliationController.php` | 57–59, 94–96 | `whereIn('status', [APPROVED_BY_COORDINATOR, APPROVED_BY_GENERAL_AS_COORDINATOR, APPROVED_BY_GENERAL_AS_PROVINCIAL])` | **Aggregation** — Canonical; all three statuses |

---

## SECTION 3 — DPReport Model Audit

### DPReport Model Location

**File:** `app/Models/Reports/Monthly/DPReport.php`  
**Lines:** 103–285

### Status Constants

| Constant | Value |
|----------|-------|
| `STATUS_DRAFT` | 'draft' |
| `STATUS_SUBMITTED_TO_PROVINCIAL` | 'submitted_to_provincial' |
| `STATUS_REVERTED_BY_PROVINCIAL` | 'reverted_by_provincial' |
| `STATUS_FORWARDED_TO_COORDINATOR` | 'forwarded_to_coordinator' |
| `STATUS_REVERTED_BY_COORDINATOR` | 'reverted_by_coordinator' |
| `STATUS_APPROVED_BY_COORDINATOR` | 'approved_by_coordinator' |
| `STATUS_REJECTED_BY_COORDINATOR` | 'rejected_by_coordinator' |
| `STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR` | 'approved_by_general_as_coordinator' |
| `STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR` | 'reverted_by_general_as_coordinator' |
| `STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL` | 'approved_by_general_as_provincial' |
| `STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL` | 'reverted_by_general_as_provincial' |
| + granular revert statuses | |

### Approval-Related Statuses

- `STATUS_APPROVED_BY_COORDINATOR`
- `STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR`
- `STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL`

### DPReport::isApproved() (lines 277–285)

```php
return in_array($this->status, [
    self::STATUS_APPROVED_BY_COORDINATOR,
    self::STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR,
    self::STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL,
]);
```

### DPReport Status Usage in Queries

- **Single status:** Many queries use `DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)` — CoordinatorController, ProvincialController, ExecutorController, GeneralController, AnnualReportService, HalfYearlyReportService, QuarterlyReportService, AdminReadOnlyController, TestApplicantAccess
- **Multi-status:** GeneralController uses `whereIn('status', [STATUS_APPROVED_BY_COORDINATOR, STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR])` — **missing** `STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL`

### Answers

1. **Do DPReport statuses align with ProjectStatus APPROVED_STATUSES?**  
   **Yes** — Same string values: `approved_by_coordinator`, `approved_by_general_as_coordinator`, `approved_by_general_as_provincial`. DPReport defines its own constants but values match ProjectStatus.

2. **Are they using different semantic meaning?**  
   **No** — Same semantics; report approval mirrors project approval.

3. **Is there risk of approval drift between Project and DPReport?**  
   **Yes — MEDIUM.** Many DPReport queries use only `STATUS_APPROVED_BY_COORDINATOR`, excluding reports approved by General. DPReport has `isApproved()` with all three statuses, but queries do not consistently use it or `whereIn('status', [...all three...])`.

---

## SECTION 4 — Cross-Model Status Duplication

### Status-Related Classes / Enums

| File | Type | Status Constants | Defines Approval? |
|------|------|------------------|-------------------|
| `app/Constants/ProjectStatus.php` | class | APPROVED_BY_COORDINATOR, APPROVED_BY_GENERAL_AS_COORDINATOR, APPROVED_BY_GENERAL_AS_PROVINCIAL, APPROVED_STATUSES, + others | **Yes** — APPROVED_STATUSES, isApproved() |
| `app/Models/Reports/Monthly/DPReport.php` | model | STATUS_APPROVED_BY_COORDINATOR, STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR, STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL | **Yes** — isApproved() |
| `app/Models/Reports/Annual/AnnualReport.php` | model | STATUS_APPROVED_BY_COORDINATOR, STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR, STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL | **Yes** (constants only) |
| `app/Models/Reports/HalfYearly/HalfYearlyReport.php` | model | Same | **Yes** (constants only) |
| `app/Models/Reports/Quarterly/QuarterlyReport.php` | model | Same | **Yes** (constants only) |
| `app/Services/ProjectStatusService.php` | class | Uses ProjectStatus | No — delegates to ProjectStatus |
| `app/Services/ReportStatusService.php` | class | Uses DPReport constants | No — delegates to DPReport |
| `app/Models/ProjectStatusHistory.php` | model | Tracks status changes | No |
| `app/Exceptions/ProjectStatusException.php` | exception | — | No |

### Duplication and Mismatch

- **Duplication:** ProjectStatus and DPReport (and Annual/HalfYearly/Quarterly) each define the same three approval string values.
- **Naming:** ProjectStatus uses `ProjectStatus::APPROVED_BY_COORDINATOR`; DPReport uses `DPReport::STATUS_APPROVED_BY_COORDINATOR` — same semantics, different naming.
- **No central approval authority:** Project and DPReport (and other report models) define approval independently. Values match today; future changes could diverge.

---

## SECTION 5 — Summary & Risk Assessment

### 1) Negative filtering risk level

**HIGH**

- ProjectController and ProjectPermissionHelper use `where('status', '!=', ProjectStatus::APPROVED_BY_COORDINATOR)`.
- Intent: exclude all approved projects.
- Result: only excludes `approved_by_coordinator`; projects approved by General still appear for executors.
- Fix: use `whereNotIn('status', ProjectStatus::APPROVED_STATUSES)`.

### 2) DPReport approval alignment risk

**MEDIUM**

- DPReport::isApproved() correctly uses all three approval statuses.
- Most DPReport queries use only `STATUS_APPROVED_BY_COORDINATOR`.
- GeneralController uses two of three (`APPROVED_BY_COORDINATOR`, `APPROVED_BY_GENERAL_AS_COORDINATOR`), missing `APPROVED_BY_GENERAL_AS_PROVINCIAL`.
- Reports approved by General as provincial are excluded from some aggregations.

### 3) Cross-model drift risk

**MEDIUM**

- ProjectStatus and DPReport (and other report models) define the same approval values independently.
- No shared contract; changes in one place could diverge from others.
- Values are currently aligned; risk is future drift.

### 4) Recommended action strategy

| Strategy | Scope | Priority |
|----------|-------|----------|
| **Minimal patch** | Replace `where('status', '!=', APPROVED_BY_COORDINATOR)` with `whereNotIn('status', ProjectStatus::APPROVED_STATUSES)` in ProjectController and ProjectPermissionHelper | **High** — Fixes executor project list |
| **DPReport query alignment** | Replace `DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)` with `$report->isApproved()`-based filtering or `whereIn('status', [all three])` in aggregation paths | **Medium** |
| **Status normalization** | Introduce shared approval constants or a single source used by both Project and DPReport | **Lower** |
| **Central approval authority** | Define an `ApprovalStatus` contract / interface used by Project and Report models | **Future** |
| **New interface contract** | e.g. `ApprovalStatusInterface::APPROVED_STATUSES` used by ProjectStatus and DPReport | **Future** |

---

## Evidence Summary

| Section | Key Finding |
|---------|-------------|
| 1 | ProjectController L299, ProjectPermissionHelper L159: `!= APPROVED_BY_COORDINATOR` — semantic risk |
| 2 | Many DPReport aggregation queries use single approved status; GeneralController approvedStatuses missing third status |
| 3 | DPReport status constants match ProjectStatus; DPReport::isApproved() correct; queries inconsistent |
| 4 | ProjectStatus and report models duplicate approval constants; no shared contract |
| 5 | Negative filtering: HIGH; DPReport: MEDIUM; Cross-model: MEDIUM |

---

**M3 Status Semantic Audit Complete — No Code Changes Made**
