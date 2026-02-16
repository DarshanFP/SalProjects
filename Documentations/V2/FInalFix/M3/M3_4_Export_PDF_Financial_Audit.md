# M3.4 — Export & PDF Financial Logic Audit

**Milestone:** M3 — Resolver Parity & Financial Stability  
**Step:** M3.4 — Export & PDF Financial Logic Audit  
**Mode:** STRICTLY READ-ONLY (No Code Changes)  
**Date:** 2025-02-15  
**Type:** Forensic Audit

---

## STEP 1 — Scan Export Layer

### ExportController

| File | Method | Financial Field Used | Logic Type (Direct DB / Computed / Fallback) | Risk Level |
|------|--------|----------------------|-----------------------------------------------|------------|
| `ExportController.php` | `addGeneralInformationSection` (Word export) | `$project->overall_project_budget` | Direct DB | High |
| `ExportController.php` | `addGeneralInformationSection` | `$project->amount_forwarded` | Direct DB | Medium |
| `ExportController.php` | `addGeneralInformationSection` | `$project->amount_sanctioned` | Direct DB | High |
| `ExportController.php` | `addGeneralInformationSection` | `$project->opening_balance` | Direct DB | High |
| `ExportController.php` | `addBudgetSection` | `$this->calculationService->calculateProjectTotal($budgets->map(...))` for this_phase, next_phase | Computed (DerivedCalculationService) | Low |
| `ExportController.php` | `addBudgetSection` | `$budgets->sum('rate_quantity')`, `sum('rate_multiplier')`, `sum('rate_duration')`, `sum('rate_increase')` | Direct collection sum | Low |

**Finding:** ExportController Key Information section (addGeneralInformationSection) uses `$project->overall_project_budget`, `$project->amount_sanctioned`, `$project->opening_balance` directly. No ProjectFinancialResolver. For phase-based projects with budget rows, DB values may not match resolver.

### ReportMonitoringService

| File | Method | Financial Field Used | Logic Type | Risk Level |
|------|--------|----------------------|------------|------------|
| `ReportMonitoringService.php` | `getBudgetUtilisationSummary` | `$report->amount_sanctioned_overview` | Direct DB (report-level) | Low |
| `ReportMonitoringService.php` | `getBudgetUtilisationSummary` | `->filter(...)->sum('amount_sanctioned')` on accountDetails | Direct collection sum (report account details) | Low |

**Note:** ReportMonitoringService operates on report-level data (accountDetails.amount_sanctioned), not project-level aggregation. Different domain.

### Report Services (AnnualReportService, HalfYearlyReportService, QuarterlyReportService)

| File | Financial Field | Logic Type | Risk Level |
|------|-----------------|------------|------------|
| `AnnualReportService.php` | `amount_sanctioned_overview`, `opening_balance`, `amount_sanctioned` from report items | Report aggregation | Low (report domain) |
| `HalfYearlyReportService.php` | Same | Report aggregation | Low |
| `QuarterlyReportService.php` | Same | Report aggregation | Low |

**Note:** These aggregate report-level data, not project-level. Out of scope for project financial canonical model.

### ProjectDataHydrator (PDF data source)

| File | Finding | Risk Level |
|------|---------|------------|
| `ProjectDataHydrator.php` | Does **NOT** include `resolvedFundFields` in hydrated data | High |
| `ProjectDataHydrator.php` | Passes raw `$project`; no resolver call | High |

**Finding:** PDF download uses ProjectDataHydrator->hydrate(). Hydrate does not call ProjectFinancialResolver or add resolvedFundFields. PDF view receives raw project only.

### BudgetExportController & BudgetExport (Excel)

| File | Method | Financial Field Used | Logic Type | Risk Level |
|------|--------|----------------------|------------|------------|
| `BudgetExport.php` | `collection()` | `$project->budgets` (rows: particular, rate_quantity, rate_multiplier, rate_duration, this_phase) | Direct DB (budget rows) | Low |
| `BudgetExport.php` | `styles()` | Uses BudgetValidationService::getBudgetSummary (resolver-backed) for styling only | Computed (resolver via BudgetValidationService) | Low |
| `BudgetExportController.php` | `exportPdf` | BudgetValidationService::getBudgetSummary → ProjectFinancialResolver | Computed (resolver) | Low |
| `BudgetExportController.php` | `prepareReportData` | BudgetValidationService::getBudgetSummary for each project | Computed (resolver) | Low |

**Finding:** BudgetExport Excel class exports budget row details (no project-level totals in Excel). BudgetExportController exportPdf and generateReport use BudgetValidationService, which delegates to ProjectFinancialResolver — **canonical** for budget PDF and budget report.

---

## STEP 2 — Scan PDF Blade Files

### Project PDF (pdf.blade.php)

| Blade File | Calculation Found | Canonical Compliant? | Risk |
|------------|-------------------|----------------------|------|
| `resources/views/projects/Oldprojects/pdf.blade.php` | `$project->amount_sanctioned ?? max(0, ($project->overall_project_budget ?? 0) - (($project->amount_forwarded ?? 0) + ($project->local_contribution ?? 0)))` | **No** | **High** |

**Detail:** Line 796 — Inline fallback for "Amount approved (Sanctioned)". When `amount_sanctioned` is null, recomputes using `overall - (forwarded + local)`. Bypasses ProjectFinancialResolver. For direct-mapped types (IIES/IES/ILP/IAH/IGE), this formula is wrong (they use type-specific logic).

### PDF includes Show.general_info

| Blade File | Calculation | Canonical Compliant? | Risk |
|------------|-------------|----------------------|------|
| `projects.partials.Show.general_info` (included in pdf) | Uses `$resolvedFundFields ?? []`; when absent (PDF case), `$rf = []` → all budget values 0 | **No** (resolvedFundFields not passed to PDF) | **High** |

**Detail:** ProjectDataHydrator does not pass resolvedFundFields. So general_info receives `$resolvedFundFields = null` → `$rf = []` → `$rf['overall_project_budget'] ?? 0 = 0`, etc. Budget fields in general_info section would display 0 in PDF unless Blade has alternate logic. (Blade uses only $rf; no fallback to $project for numeric display.)

### Monthly Report PDF (PDFReport.blade.php)

| Blade File | Calculation Found | Canonical Compliant? | Risk |
|------------|-------------------|----------------------|------|
| `resources/views/reports/monthly/PDFReport.blade.php` | `$budgets->sum('amount_sanctioned')` (line 349) | N/A (report account details) | Low |
| `resources/views/reports/monthly/PDFReport.blade.php` | `$budget->amount_sanctioned` per row | Report-level | Low |

**Note:** Monthly report PDF uses report account details (budgets = accountDetails), not project-level financials. Different domain.

### Statements of Account (report edit/view partials)

| Blade File | Calculation | Canonical Compliant? | Risk |
|------------|-------------|----------------------|------|
| `reports/monthly/partials/view/statements_of_account/*.blade.php` | `$report->accountDetails->sum('amount_sanctioned')` | Report-level | Low |
| `reports/monthly/partials/edit/statements_of_account/*.blade.php` | `amount_sanctioned_overview`, `amount_sanctioned[]` | Report-level | Low |

**Note:** Report-level; not project aggregation.

---

## STEP 3 — Check Role-Based Financial Differences

### Exports

| Question | Answer |
|----------|--------|
| Do exports show different totals for provincial vs coordinator? | **No** — ExportController does not branch on role for financial fields. Same Word/PDF export logic for all roles. |
| Is sanctioned shown when project not approved? | **Yes** — ExportController addGeneralInformationSection uses `$project->amount_sanctioned` directly. For non-approved projects, this may be null/0; display shows raw value. |
| Is budget used incorrectly post-approval? | **Yes** — ExportController uses `$project->overall_project_budget` for Key Information. For phase-based approved projects, resolver uses sum(this_phase); DB `overall_project_budget` may be stale or differ. |

### PDF (project downloadPdf)

| Question | Answer |
|----------|--------|
| Do exports show different totals for provincial vs coordinator? | **No** — Same PDF view for all roles with access. |
| Is sanctioned shown when project not approved? | **Yes** — pdf.blade.php uses `$project->amount_sanctioned ?? max(0, overall - (forwarded + local))`. Fallback computes sanctioned when null. |
| Is budget used incorrectly post-approval? | **Possible** — Inline formula `overall - (forwarded + local)` is valid for phase-based types but wrong for IIES/IES/ILP/IAH/IGE (type-specific sanctioned logic). |

---

## STEP 4 — Final Confirmation

### 1) Is there any instance of: `sanctioned ?? (budget - forwarded - local)`?

**Yes.**  
- **File:** `resources/views/projects/Oldprojects/pdf.blade.php` (line 796)  
- **Expression:** `$project->amount_sanctioned ?? max(0, ($project->overall_project_budget ?? 0) - (($project->amount_forwarded ?? 0) + ($project->local_contribution ?? 0)))`  
- **Risk:** High — Inline formula; bypasses resolver; wrong for direct-mapped project types.

### 2) Is overall_project_budget used directly for approved totals?

**Yes.**  
- **ExportController** `addGeneralInformationSection`: Uses `$project->overall_project_budget` for "Overall Project Budget" display.  
- **pdf.blade.php** inline fallback: Uses `$project->overall_project_budget` in sanctioned fallback.  
- **Risk:** For phase-based projects with budget rows, `overall_project_budget` in DB may not match resolver (sum of this_phase). For approved projects, opening_balance is the canonical "total funds"; overall_project_budget is structural cost.

### 3) Are there inline Blade calculations bypassing canonical model?

**Yes.**  
- **pdf.blade.php** line 796: Inline `amount_sanctioned ?? max(0, overall - (forwarded + local))`.  
- **Show.general_info** (when used with empty resolvedFundFields): Uses `$rf['amount_sanctioned']` etc.; when `$rf = []`, displays 0 — no inline arithmetic there, but wrong source (missing resolvedFundFields in PDF flow).

### 4) Is resolver consistently used in exports?

**No.**  
- **ExportController** Word export (addGeneralInformationSection): Does **not** use ProjectFinancialResolver. Uses `$project->overall_project_budget`, `amount_sanctioned`, `opening_balance` directly.  
- **ExportController** downloadPdf: Uses ProjectDataHydrator, which does **not** add resolvedFundFields. PDF receives raw project.  
- **ExportController** addBudgetSection: Uses DerivedCalculationService for phase totals (computeProjectTotal) — aligned with resolver arithmetic, but budget section only; Key Information still bypasses resolver.

---

## Summary Table — Export & PDF Financial Sources

| Location | Resolver Used? | Direct DB? | Inline Formula? | Risk |
|----------|----------------|------------|-----------------|------|
| ExportController addGeneralInformationSection | No | Yes | No | High |
| ExportController addBudgetSection | No (uses DerivedCalculationService for sums) | Partially (budgets from project) | No | Medium |
| ProjectDataHydrator | No | Yes (raw project) | N/A | High |
| pdf.blade.php (approval block) | No | Yes (with fallback) | Yes | High |
| pdf.blade.php @include general_info | No (resolvedFundFields not passed) | Indirect (0 when $rf empty) | No | High |
| BudgetExportController exportPdf | Yes (via BudgetValidationService) | No | No | Low |
| BudgetExportController generateReport | Yes (via BudgetValidationService) | No | No | Low |
| BudgetExport (Excel) | N/A (budget rows only) | Yes (project.budgets) | No | Low |
| Report PDF (PDFReport.blade.php) | N/A | Report accountDetails | No | Low |
| ReportMonitoringService | N/A | Report-level | No | Low |

---

## Recommendations (Documentation Only — No Implementation)

1. **ExportController addGeneralInformationSection:** Call ProjectFinancialResolver->resolve($project) and use resolved values for overall_project_budget, amount_sanctioned, opening_balance.  
2. **ProjectDataHydrator:** Add resolvedFundFields = app(ProjectFinancialResolver::class)->resolve($project) to hydrated data for PDF.  
3. **pdf.blade.php line 796:** Remove inline fallback. Use `$resolvedFundFields['amount_sanctioned'] ?? 0` (or equivalent from controller). Ensure resolvedFundFields is always passed.  
4. **ExportController addBudgetSection:** Already uses DerivedCalculationService for phase totals; acceptable. Key Information alignment is the priority.

---

**M3.4 Export & PDF Financial Audit Complete — No Code Changes Made**
