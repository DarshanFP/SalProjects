# M3.7 Phase 0 — Canonical Sanctioned Separation: Usage Audit

**Milestone:** M3.7 — Canonical Sanctioned Separation  
**Phase:** Phase 0 — Impact Audit  
**Mode:** READ-ONLY / ZERO ASSUMPTIONS / FULL USAGE MAPPING  
**Date:** 2026-02-16  

---

## OBJECTIVE

Semantic change:

- **For NON-APPROVED projects:** `amount_sanctioned` MUST be 0; `amount_requested` will represent requested amount.
- **For APPROVED projects:** `amount_sanctioned` remains the sanctioned amount from DB.

Before changing the resolver, this audit identifies all usages and classifies their semantic context.

---

## SECTION 1 — Global Search

### All `amount_sanctioned` Matches (Application Code)

| File | Line | Context | Code Snippet |
|------|------|---------|--------------|
| `app/Models/OldProjects/Project.php` | 94 | PHPDoc property | `@property string|null $amount_sanctioned` |
| `app/Models/OldProjects/Project.php` | 287 | Fillable array | `'amount_sanctioned'` |
| `app/Http/Controllers/GeneralController.php` | 2633 | Approval flow | `$amountSanctioned = (float) ($financials['amount_sanctioned'] ?? 0);` |
| `app/Http/Controllers/GeneralController.php` | 2647 | Approval persistence | `$project->amount_sanctioned = $amountSanctioned;` |
| `app/Http/Controllers/CoordinatorController.php` | 151 | Dashboard filter count | `$projects->where('amount_sanctioned', '>', 0)->count()` |
| `app/Http/Controllers/CoordinatorController.php` | 1111 | Approval flow | `$amountSanctioned = (float) ($financials['amount_sanctioned'] ?? 0);` |
| `app/Http/Controllers/CoordinatorController.php` | 1134 | Approval persistence | `$project->amount_sanctioned = $amountSanctioned;` |
| `app/Http/Controllers/CoordinatorController.php` | 1140, 1170 | Log context | `'amount_sanctioned' => $amountSanctioned` |
| `app/Http/Controllers/ProvincialController.php` | 499 | Grand totals init | `'amount_sanctioned' => 0` |
| `app/Http/Controllers/ProvincialController.php` | 508 | Grand totals sum | `$grandTotals['amount_sanctioned'] += (float) ($financials['amount_sanctioned'] ?? 0);` |
| `app/Http/Controllers/Projects/ExportController.php` | 634 | Word export | `$resolvedFundFields['amount_sanctioned'] ?? 0` |
| `app/Http/Controllers/Projects/ProjectController.php` | 401 | Predecessor budget (phase sum) | `'amount_sanctioned' => $phase->sum('amount')` |
| `app/Http/Controllers/Reports/Monthly/ReportController.php` | 87 | Report form prefill | `$amountSanctioned = $project->amount_sanctioned ?? 0.00;` |
| `app/Http/Controllers/Reports/Monthly/ReportController.php` | 1196-1199 | Validation | `$projectAmountSanctioned`, `$reportSanctioned` |
| `app/Http/Controllers/Reports/Monthly/ReportController.php` | 1317-1327 | Report form | `$amountSanctioned = $project->amount_sanctioned ?? 0.00` |
| `app/Http/Controllers/Reports/Monthly/MonthlyDevelopmentProjectController.php` | 32, 50 | Report form | `$amountSanctioned = $project->amount_sanctioned ?? 0;` |
| `app/Services/ProjectStatusService.php` | 244 | Revert reset | `$project->amount_sanctioned = 0;` |
| `app/Services/Budget/AdminCorrectionService.php` | 109 | Stored values | `'amount_sanctioned' => (float) ($project->amount_sanctioned ?? 0)` |
| `app/Services/Budget/AdminCorrectionService.php` | 180, 202, 207 | Apply/audit | `'amount_sanctioned' => ...` |
| `app/Services/Budget/ProjectFundFieldsResolver.php` | 72 | getStoredValues | `'amount_sanctioned' => (float) ($project->amount_sanctioned ?? 0)` |
| `app/Services/Budget/BudgetSyncService.php` | 43, 110-113 | PRE_APPROVAL_FIELDS, syncBeforeApproval | Writes amount_sanctioned on pre-approval sync |
| `app/Services/Budget/BudgetValidationService.php` | 59 | Resolver output | `$amountSanctioned = (float) ($financials['amount_sanctioned'] ?? 0);` |
| `app/Services/Budget/BudgetValidationService.php` | 118, 154, 159, 187 | Budget data array | `'amount_sanctioned' => ...` |
| `app/Services/Budget/BudgetSyncService.php` | 136 | getStoredValues | `'amount_sanctioned' => (float) ($project->amount_sanctioned ?? 0)` |
| `app/Domain/Budget/ProjectFinancialResolver.php` | 81, 104-107 | Invariant check | `$sanctioned = (float) ($data['amount_sanctioned'] ?? 0)` |
| `app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php` | 69, 93, 115, 138, 185 | Strategy output | `'amount_sanctioned' => $sanctioned` (from requested) |
| `app/Domain/Budget/Strategies/PhaseBasedBudgetStrategy.php` | 51, 54, 62 | Strategy output | `$sanctioned = (float) ($project->amount_sanctioned ?? 0)` or calculated |
| `app/Services/ReportMonitoringService.php` | 332, 376, 383, 386 | Report-level sums | `$row->amount_sanctioned`, `->sum('amount_sanctioned')` |
| `app/Services/Reports/AnnualReportService.php` | 695 | Report overview | `$annualReport->amount_sanctioned_overview` |
| `app/Services/Reports/HalfYearlyReportService.php` | 304 | Details sum | `$details->sum('amount_sanctioned')` |
| `app/Services/Reports/QuarterlyReportService.php` | 261 | Detail access | `$item['detail']->amount_sanctioned ?? 0` |
| `app/Http/Controllers/Admin/BudgetReconciliationController.php` | (via view) | Reconciliation | `$row['stored']['amount_sanctioned']`, `$row['resolved']['amount_sanctioned']` |
| `resources/views/projects/partials/Show/general_info.blade.php` | 34, 135 | Display | `$amount_requested = (float) ($rf['amount_sanctioned'] ?? 0);` / `$rf['amount_sanctioned']` |
| `resources/views/projects/partials/Show/budget.blade.php` | 28 | Display | `$amountSanctioned = $budgetData['amount_sanctioned'];` |
| `resources/views/projects/partials/Edit/budget.blade.php` | 145 | Edit form | `value="{{ old('amount_sanctioned', $project->amount_sanctioned ?? 0) }}"` |
| `resources/views/projects/partials/OLdshow/general_info.blade.php` | 57 | Display | `{{ number_format($project->amount_sanctioned, 2) }}` |
| `resources/views/projects/partials/not working show/general_info.blade.php` | 57 | Display | `{{ number_format($project->amount_sanctioned, 2) }}` |
| `resources/views/projects/Oldprojects/pdf.blade.php` | 796 | PDF display | `{{ $resolvedFundFields['amount_sanctioned'] ?? 0 }}` |
| `resources/views/provincial/ProjectList.blade.php` | 124 | Grand total | `{{ $grandTotals['amount_sanctioned'] ?? 0 }}` |
| `resources/views/provincial/ProjectList.blade.php` | 235 | Per-project | `$amountRequested = (float) ($fin['amount_sanctioned'] ?? 0);` |
| `resources/views/reports/monthly/developmentProject/reportform.blade.php` | 196, 204 | Report form | `value="{{ $project->amount_sanctioned }}"` |
| `resources/views/reports/monthly/PDFReport.blade.php` | 338, 349 | Report PDF | `$budget->amount_sanctioned`, `$budgets->sum('amount_sanctioned')` |
| `resources/views/reports/monthly/partials/*/statements_of_account/*.blade.php` | Multiple | Report display | `$report->accountDetails->sum('amount_sanctioned')` |
| `resources/views/reports/monthly/partials/view/budget_monitoring.blade.php` | 68 | Monitoring | `$row['amount_sanctioned'] ?? 0` |
| `resources/views/admin/budget_reconciliation/index.blade.php` | 75-76 | Reconciliation | `$row['stored']['amount_sanctioned']`, `$row['resolved']['amount_sanctioned']` |
| `resources/views/projects/exports/budget-pdf.blade.php` | 198 | Budget export | `$budgetData['amount_sanctioned']` |
| `app/Http/Requests/Projects/UpdateBudgetRequest.php` | 36-40 | Normalization | `phases[*].amount_sanctioned` |
| `app/Http/Requests/Projects/StoreBudgetRequest.php` | 27-31 | Normalization | `phases[*].amount_sanctioned` |
| `app/Http/Requests/Projects/UpdateProjectRequest.php` | 120, 172-173 | Validation | `total_amount_sanctioned` |
| `update_approved_projects.php` | 46, 63 | Script | `->where('amount_sanctioned', '>', 0)` |
| `database/migrations/2025_06_26_*.php` | 32 | Migration | `->where('amount_sanctioned', '>', 0)` |

### `sum('amount_sanctioned')` / `orderBy('amount_sanctioned')` / `selectRaw`

| File | Line | Context |
|------|------|---------|
| `app/Services/ReportMonitoringService.php` | 386 | `->sum('amount_sanctioned')` on accountDetails |
| `app/Services/Reports/HalfYearlyReportService.php` | 304 | `$details->sum('amount_sanctioned')` |
| `resources/views/reports/monthly/PDFReport.blade.php` | 349 | `$budgets->sum('amount_sanctioned')` |
| `resources/views/reports/monthly/partials/view/statements_of_account/*.blade.php` | 195-200 | `$report->accountDetails->sum('amount_sanctioned')` |
| `resources/views/reports/quarterly/*/show.blade.php` | 203-205 | `$report->accountDetails->sum('amount_sanctioned')` |

**No `orderBy('amount_sanctioned')` found in application code.**  
**No `selectRaw` with `amount_sanctioned` found in application controllers (only in docs).**

### `$project->amount_sanctioned` (Direct DB Read)

| File | Line | Context |
|------|------|---------|
| `app/Http/Controllers/GeneralController.php` | 2647 | Write (approval) |
| `app/Http/Controllers/CoordinatorController.php` | 1134 | Write (approval); 151 filter |
| `app/Services/ProjectStatusService.php` | 244 | Write (revert) |
| `app/Services/Budget/AdminCorrectionService.php` | 109 | Read (stored) |
| `app/Services/Budget/ProjectFundFieldsResolver.php` | 72 | Read (stored) |
| `app/Domain/Budget/Strategies/PhaseBasedBudgetStrategy.php` | 51 | Read when approved |
| `resources/views/projects/partials/Edit/budget.blade.php` | 145 | Read for form value |
| `resources/views/projects/partials/OLdshow/general_info.blade.php` | 57 | Read for display |
| `resources/views/projects/partials/not working show/general_info.blade.php` | 57 | Read for display |
| `resources/views/reports/monthly/developmentProject/reportform.blade.php` | 196, 204 | Read for report form |
| `app/Http/Controllers/Reports/Monthly/ReportController.php` | 87, 1196, 1318, 1326 | Read for report logic |
| `app/Http/Controllers/Reports/Monthly/MonthlyDevelopmentProjectController.php` | 32, 50 | Read for report form |

---

## SECTION 2 — Resolver Output Consumers

### `resolvedFundFields['amount_sanctioned']` / `$financials['amount_sanctioned']` Usages

| Consumer | File:Line | Context | Approved-Only? | Mixed? | Export? | PDF? | Dashboard? |
|----------|-----------|---------|----------------|--------|---------|------|------------|
| Show general_info | `Show/general_info.blade.php` 34, 135 | Amount Requested + Amount Sanctioned display | No | Yes (all projects) | No | Via include | No |
| ExportController | `ExportController.php` 634 | Word export Key Info | No | Yes | Yes | No | No |
| pdf.blade.php | `pdf.blade.php` 796 | "Amount approved (Sanctioned)" | No | Yes | No | Yes | No |
| ProvincialController projectList | `ProvincialController.php` 508 | Grand totals sum | No | Yes | No | No | Yes |
| ProvincialController projectList | `ProjectList.blade.php` 124, 235 | "Total Amount Requested" label; per-project Amount Requested | No | Yes | No | No | Yes |
| CoordinatorController approve | `CoordinatorController.php` 1111 | Approval persistence | Yes | No | No | No | No |
| GeneralController approve | `GeneralController.php` 2633 | Approval persistence | Yes | No | No | No | No |
| BudgetValidationService | `BudgetValidationService.php` 59 | Budget summary for Show | No | Yes | No | No | No |

### Classification

- **Inside approved-only context:** CoordinatorController/GeneralController approval flow (persistence only; project is approved before persist).
- **Inside mixed project listing:** ProvincialController projectList (all statuses), Show general_info (any project).
- **Inside pending section:** N/A (no explicit "pending" section using sanctioned).
- **Inside export:** ExportController Word export.
- **Inside PDF:** pdf.blade.php.
- **Inside dashboard aggregation:** ProvincialController grand totals (`$grandTotals['amount_sanctioned']`).

---

## SECTION 3 — Dashboard Impact Classification

### ProvincialController

| Method | Line | Usage | Filtering | Classification |
|--------|------|-------|-----------|----------------|
| projectList | 501-508 | `$grandTotals['amount_sanctioned'] += $financials['amount_sanctioned']` | No status filter; all projects in filter | **B) Unsafe (mixed)** |
| projectList | 508 | Same sum used for "Total Amount Requested" card | Mixed | **B) Unsafe** |
| calculateTeamPerformanceMetrics | 2090-2100 | Uses `opening_balance` from resolver for approved only | `$approvedProjects` | **A) Safe** |
| calculateCenterPerformance | 2229 | `opening_balance` for approved; `overall - forwarded - local` for pending | Stage-separated | **A) Safe** |
| calculateEnhancedBudgetData | 2303-2305 | `opening_balance` for approved; inline requested for pending | Stage-separated | **A) Safe** |

### CoordinatorController

| Method | Line | Usage | Filtering | Classification |
|--------|------|-------|-----------|----------------|
| dashboard | 151 | `$projects->where('amount_sanctioned', '>', 0)->count()` | All projects in filter | **D) Needs separation** — count mixes approved + non-approved |
| approveProject | 1111, 1134 | Resolver → persist sanctioned | Approved only (at persist) | **A) Safe** |
| getSystemBudgetOverviewData | 2050-2051 | `opening_balance` for approved; inline requested for pending | Stage-separated | **A) Safe** |

### GeneralController

| Method | Line | Usage | Filtering | Classification |
|--------|------|-------|-----------|----------------|
| approveAsCoordinator | 2633, 2647 | Resolver → persist sanctioned | Approved only | **A) Safe** |

### Summary

- **A) Safe (approved-only):** Coordinator/General approval persistence; Provincial calculateTeamPerformanceMetrics, calculateCenterPerformance, calculateEnhancedBudgetData; Coordinator getSystemBudgetOverviewData.
- **B) Unsafe (mixed):** Provincial projectList grand totals.
- **C) Requested fallback logic:** Show general_info uses `$rf['amount_sanctioned']` as "Amount Requested" for non-approved — semantic mismatch.
- **D) Needs separation:** Coordinator dashboard `projects_with_amount_sanctioned` count.

---

## SECTION 4 — Export & PDF Impact

### ExportController

| Location | File:Line | Usage | Stage Awareness |
|----------|-----------|-------|-----------------|
| addGeneralInfoSection | ExportController.php 634 | `$resolvedFundFields['amount_sanctioned'] ?? 0` | **No** — uses resolver output for any project |
| PDF (project) | pdf.blade.php 796 | `$resolvedFundFields['amount_sanctioned'] ?? 0` | **No** — same |

### Assumptions

- **Assumes sanctioned = requested pre-approval?** Yes — resolver currently returns requested as sanctioned for non-approved.
- **Stage awareness enforced?** No — no check for approved before showing "Amount approved (Sanctioned)".

### Report-Level amount_sanctioned (Separate Semantics)

- `report->amount_sanctioned_overview`, `accountDetail->amount_sanctioned` — report/account-detail level, not project. Out of scope for project `amount_sanctioned` separation but noted for completeness.

---

## SECTION 5 — Sorting & Filtering

### Filtering

| File | Line | Usage |
|------|------|-------|
| CoordinatorController | 151 | `$projects->where('amount_sanctioned', '>', 0)->count()` |
| update_approved_projects.php | 46, 63 | `->where('amount_sanctioned', '>', 0)` |
| database/migrations/2025_06_26_*.php | 32 | `->where('amount_sanctioned', '>', 0)` |

### Sorting

- **No `orderBy('amount_sanctioned')`** in application code.
- **No DataTables sorting by amount_sanctioned** found.
- **No index/query sorting by amount_sanctioned** found.

---

## SECTION 6 — DB-Level Usage

### Raw SQL / Eloquent

| Pattern | File | Line | Context |
|---------|------|------|---------|
| `where('amount_sanctioned', '>', 0)` | CoordinatorController | 151 | Dashboard count |
| `where('amount_sanctioned', '>', 0)` | update_approved_projects.php | 46, 63 | One-off script |
| `where('amount_sanctioned', '>', 0)` | migration 2025_06_26 | 32 | Data migration |
| `SUM(amount_sanctioned)` | — | — | **Not found** in app code (only in Documentation examples) |
| `GROUP BY amount_sanctioned` | — | — | **Not found** |

Report-level tables use `amount_sanctioned` / `amount_sanctioned_overview` (dp_reports, account_details, etc.) — different schema.

---

## SECTION 7 — Semantic Risk Mapping

| Location | Current Meaning | After Change Impact | Risk Level |
|----------|-----------------|---------------------|------------|
| ProvincialController projectList grand totals | Sum of resolver output for all projects (requested as sanctioned) | Will sum 0 for non-approved; total drops | **HIGH** — label says "Amount Requested", will show lower total |
| ProjectList.blade.php "Total Amount Requested" | Same sum | Semantic fix — will need `amount_requested` from resolver | **MEDIUM** — requires new resolver key |
| ProjectList.blade.php per-project "Amount Requested" | `$fin['amount_sanctioned']` | Needs `amount_requested` key | **MEDIUM** |
| Show general_info "Amount Requested" | `$rf['amount_sanctioned']` | Needs `amount_requested` | **MEDIUM** |
| Show general_info "Amount Sanctioned" | `$rf['amount_sanctioned']` | For non-approved will show 0 (correct) | **LOW** |
| ExportController Word export | `$resolvedFundFields['amount_sanctioned']` | Non-approved will show 0 | **MEDIUM** — "Amount Sanctioned" may be 0 for drafts |
| pdf.blade.php "Amount approved (Sanctioned)" | Same | Same | **MEDIUM** |
| CoordinatorController dashboard count | `where('amount_sanctioned', '>', 0)` | Count of projects with sanctioned > 0 (DB) | **LOW** — DB will have 0 for non-approved |
| Coordinator/General approval flow | Persist from resolver | Resolver will return sanctioned only when approved | **SAFE** |
| ReportController report form | `$project->amount_sanctioned` | Prefill for reports; approved projects only in normal flow | **LOW** — reports typically for approved |
| Edit budget.blade | `$project->amount_sanctioned` | May show 0 for individual types (often not synced) | **LOW** |
| OLdshow / not working show | `$project->amount_sanctioned` | Direct DB; non-approved may have stale value | **LOW** (legacy) |
| BudgetValidationService | Resolver output | Uses sanctioned in budget summary | **MEDIUM** — needs stage-aware display |
| Report-level amount_sanctioned | Report/accountDetail columns | Different schema | **N/A** |

---

## SECTION 8 — Final Impact Summary

### Counts

| Metric | Count |
|--------|-------|
| **Total usages (application code, excl. docs)** | ~55 distinct sites |
| **Safe (approved-only or report-level)** | ~18 |
| **Require modification** | ~15 |
| **Likely break if resolver changes without migration** | ~8 |
| **New `amount_requested` key required** | ~6 consumers |

### Consumers Requiring Modification

1. **ProvincialController** projectList — use `amount_requested` for "Total Amount Requested" and per-project display; keep `amount_sanctioned` for sanctioned-only aggregates when applicable.
2. **Show general_info** — use `amount_requested` for "Amount Requested" row; `amount_sanctioned` for "Amount Sanctioned" row.
3. **ExportController** — consider `amount_requested` for draft/forwarded; `amount_sanctioned` for approved.
4. **pdf.blade.php** — stage-aware: show "Amount approved (Sanctioned)" only when approved; or show "Amount Requested" vs "Amount Sanctioned" by stage.
5. **BudgetValidationService** — ensure stage-aware labels/display.
6. **CoordinatorController** dashboard `projects_with_amount_sanctioned` — clarify intent (approved with sanctioned > 0 vs any with sanctioned > 0).

### Overall Risk Assessment

**Deployment impact: MEDIUM**

- Resolver change (non-approved → sanctioned = 0) is backward-compatible for approved projects.
- Provincial project list and Show views will show 0 for "Amount Sanctioned" on non-approved until `amount_requested` is added and wired.
- Report forms and export/PDF need stage-aware handling to avoid confusion.
- No critical failures expected; display semantics and aggregations need migration.

---

## Appendix: Resolver Key Addition

To support the semantic split:

- Add `amount_requested` to resolver output for non-approved (and optionally approved, where it equals sanctioned).
- Keep `amount_sanctioned` = 0 for non-approved; = DB value for approved.
- Migrate consumers of "requested" display from `amount_sanctioned` to `amount_requested`.

---

**M3.7 Phase 0 Audit Complete — No Code Changes Made**
