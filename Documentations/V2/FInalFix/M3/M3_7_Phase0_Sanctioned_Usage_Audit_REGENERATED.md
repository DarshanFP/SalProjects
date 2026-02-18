# M3.7 — Phase 0: Full Regeneration Audit — Canonical Sanctioned Separation

**Mode:** READ-ONLY — ZERO ASSUMPTIONS — CURRENT CODEBASE ONLY  
**Generated:** From live repository scan (app/, resources/views/, database/migrations/, tests/, scripts).  
**Excluded:** vendor/, node_modules/, storage/framework/views (compiled views; sources listed instead).

---

## OBJECTIVE (Reference)

- **Non-approved projects** → `amount_sanctioned = 0`; introduce new field `amount_requested`.
- **Approved projects** → `amount_sanctioned` remains DB-sanctioned value.
- **Resolver** must enforce invariant.

This document is a **complete usage map** from the current repository only. No code was modified.

---

## STEP 1 — GLOBAL USAGE SEARCH

Search scope: entire repository excluding `vendor`, `node_modules`. Compiled views under `storage/framework/views` are not listed; their source blades are.

### Structured table: all `amount_sanctioned` (and direct variant) occurrences

| File | Line | Context | Code Snippet | Type |
|------|------|---------|--------------|------|
| app/Models/OldProjects/Project.php | 94 | PHPDoc property | `@property string|null $amount_sanctioned` | Model |
| app/Models/OldProjects/Project.php | 287 | fillable | `'amount_sanctioned',` | Model |
| app/Http/Controllers/ProvincialController.php | 499 | grandTotals init | `'amount_sanctioned' => 0,` | Controller |
| app/Http/Controllers/ProvincialController.php | 508 | sum from resolver | `$grandTotals['amount_sanctioned'] += (float) ($financials['amount_sanctioned'] ?? 0);` | Controller |
| app/Http/Controllers/CoordinatorController.php | 151 | dashboard count | `'projects_with_amount_sanctioned' => $projects->where('amount_sanctioned', '>', 0)->count(),` | Controller |
| app/Http/Controllers/CoordinatorController.php | 1111 | from resolver | `$amountSanctioned = (float) ($financials['amount_sanctioned'] ?? 0);` | Controller |
| app/Http/Controllers/CoordinatorController.php | 1134 | persist on approve | `$project->amount_sanctioned = $amountSanctioned;` | Controller |
| app/Http/Controllers/CoordinatorController.php | 1140 | log | `'amount_sanctioned' => $amountSanctioned,` | Controller |
| app/Http/Controllers/CoordinatorController.php | 1170 | log | `'amount_sanctioned' => $amountSanctioned,` | Controller |
| app/Http/Controllers/GeneralController.php | 2633 | from resolver | `$amountSanctioned = (float) ($financials['amount_sanctioned'] ?? 0);` | Controller |
| app/Http/Controllers/GeneralController.php | 2647 | persist on approve | `$project->amount_sanctioned = $amountSanctioned;` | Controller |
| app/Http/Controllers/Projects/ProjectController.php | 401 | predecessor budget map | `'amount_sanctioned' => $phase->sum('amount'),` | Controller |
| app/Http/Controllers/Projects/ExportController.php | 634 | Word export | `"Amount Sanctioned: " . ... ($resolvedFundFields['amount_sanctioned'] ?? 0, 2)` | Controller |
| app/Http/Requests/Projects/UpdateProjectRequest.php | 120 | validation | `'total_amount_sanctioned' => 'nullable|numeric|min:0',` | Validation |
| app/Http/Requests/Projects/UpdateProjectRequest.php | 172-173 | messages | total_amount_sanctioned.numeric / .min | Validation |
| app/Http/Requests/Projects/UpdateGeneralInfoRequest.php | 80 | validation | `'total_amount_sanctioned' => 'nullable|numeric|min:0',` | Validation |
| app/Http/Requests/Projects/StoreProjectRequest.php | 60 | validation | `'total_amount_sanctioned' => 'nullable|numeric|min:0',` | Validation |
| app/Http/Requests/Projects/StoreProjectRequest.php | 113-114 | messages | total_amount_sanctioned.numeric / .min | Validation |
| app/Http/Requests/Projects/StoreGeneralInfoRequest.php | 50 | validation | `'total_amount_sanctioned' => 'nullable|numeric|min:0',` | Validation |
| app/Services/Reports/AnnualReportService.php | 98 | aggregatedData | `'amount_sanctioned_overview' => ...` | Service |
| app/Services/Reports/AnnualReportService.php | 219 | sum | `'amount_sanctioned_overview' => $halfYearlyReports->sum('amount_sanctioned_overview'),` | Service |
| app/Services/Reports/AnnualReportService.php | 240 | sum | `'amount_sanctioned_overview' => $quarterlyReports->sum(...)` | Service |
| app/Services/Reports/AnnualReportService.php | 261 | sum | `'amount_sanctioned_overview' => $monthlyReports->sum(...)` | Service |
| app/Services/Reports/AnnualReportService.php | 263 | amount_in_hand | `+ $monthlyReports->sum('amount_sanctioned_overview')` | Service |
| app/Services/Reports/AnnualReportService.php | 294 | items sum | `$amountSanctioned = $items->sum('detail.amount_sanctioned');` | Service |
| app/Services/Reports/AnnualReportService.php | 323 | key | `'amount_sanctioned' => $amountSanctioned,` | Service |
| app/Services/Reports/AnnualReportService.php | 359 | items sum | `$amountSanctioned = $items->sum('detail.amount_sanctioned');` | Service |
| app/Services/Reports/AnnualReportService.php | 382 | key | `'amount_sanctioned' => $amountSanctioned,` | Service |
| app/Services/Reports/AnnualReportService.php | 419 | items sum | `$amountSanctioned = $items->sum('detail.amount_sanctioned');` | Service |
| app/Services/Reports/AnnualReportService.php | 445 | key | `'amount_sanctioned' => $amountSanctioned,` | Service |
| app/Services/Reports/AnnualReportService.php | 695 | totalBudget | `$annualReport->amount_sanctioned_overview + ...` | Service |
| app/Services/Reports/HalfYearlyReportService.php | 89 | aggregatedData | `'amount_sanctioned_overview' => ...` | Service |
| app/Services/Reports/HalfYearlyReportService.php | 207 | sum | `'amount_sanctioned_overview' => $quarterlyReports->sum(...)` | Service |
| app/Services/Reports/HalfYearlyReportService.php | 228 | sum | `'amount_sanctioned_overview' => $monthlyReports->sum(...)` | Service |
| app/Services/Reports/HalfYearlyReportService.php | 230 | amount_in_hand | `+ $monthlyReports->sum('amount_sanctioned_overview')` | Service |
| app/Services/Reports/HalfYearlyReportService.php | 263 | items sum | `$amountSanctioned = $items->sum('detail.amount_sanctioned');` | Service |
| app/Services/Reports/HalfYearlyReportService.php | 280 | key | `'amount_sanctioned' => $amountSanctioned,` | Service |
| app/Services/Reports/HalfYearlyReportService.php | 304 | details sum | `$amountSanctioned = $details->sum('amount_sanctioned');` | Service |
| app/Services/Reports/HalfYearlyReportService.php | 329 | key | `'amount_sanctioned' => $amountSanctioned,` | Service |
| app/Services/Reports/QuarterlyReportService.php | 76 | aggregatedData | `'amount_sanctioned_overview' => ...` | Service |
| app/Services/Reports/QuarterlyReportService.php | 207 | sum | `$amountSanctionedOverview = $monthlyReports->sum('amount_sanctioned_overview');` | Service |
| app/Services/Reports/QuarterlyReportService.php | 218 | key | `'amount_sanctioned_overview' => $amountSanctionedOverview,` | Service |
| app/Services/Reports/QuarterlyReportService.php | 261 | return | `return $item['detail']->amount_sanctioned ?? 0;` | Service |
| app/Services/Reports/QuarterlyReportService.php | 284 | key | `'amount_sanctioned' => $amountSanctioned,` | Service |
| app/Services/Reports/QuarterlyReportService.php | 308 | key | `'amount_sanctioned' => $detail['amount_sanctioned'],` | Service |
| app/Services/ReportMonitoringService.php | 386 | budget rows sum | `->sum('amount_sanctioned');` (report accountDetails) | Service |
| app/Services/ProjectStatusService.php | 234 | comment M4.2 | amount_sanctioned = 0 on revert | Service |
| app/Services/ProjectStatusService.php | 244 | revert | `$project->amount_sanctioned = 0;` | Service |
| app/Domain/Budget/ProjectFinancialResolver.php | 60 | docblock | amount_sanctioned: float | Service |
| app/Domain/Budget/ProjectFinancialResolver.php | 81 | invariant | `$sanctioned = (float) ($data['amount_sanctioned'] ?? 0);` | Service |
| app/Domain/Budget/ProjectFinancialResolver.php | 88,90,91 | log | approved must have amount_sanctioned > 0 | Service |
| app/Domain/Budget/ProjectFinancialResolver.php | 104,106,107 | log | non-approved must have amount_sanctioned == 0 | Service |
| app/Domain/Budget/ProjectFinancialResolver.php | 137 | normalize keys | `'amount_sanctioned',` | Service |
| app/Domain/Budget/Strategies/PhaseBasedBudgetStrategy.php | 51 | approved read | `$sanctioned = (float) ($project->amount_sanctioned ?? 0);` | Service |
| app/Domain/Budget/Strategies/PhaseBasedBudgetStrategy.php | 62 | return | `'amount_sanctioned' => max(0, $sanctioned),` | Service |
| app/Domain/Budget/Strategies/PhaseBasedBudgetStrategy.php | 85 | normalize | `'amount_sanctioned',` | Service |
| app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php | 77,100 | return | `'amount_sanctioned' => $sanctioned` (from amount_requested) | Service |
| app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php | 116,123 | IES | sanctioned from first->amount_requested | Service |
| app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php | 139,146 | IAH | sanctioned from first->amount_requested | Service |
| app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php | 166,174 | IGE | sanctioned from sum amount_requested | Service |
| app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php | 185 | fallback | `'amount_sanctioned' => (float) ($project->amount_sanctioned ?? 0),` | Service |
| app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php | 199 | normalize | `'amount_sanctioned',` | Service |
| app/Services/Budget/ProjectFundFieldsResolver.php | 34 | docblock | amount_sanctioned in return | Service |
| app/Services/Budget/ProjectFundFieldsResolver.php | 72 | getStoredValues | `'amount_sanctioned' => (float) ($project->amount_sanctioned ?? 0),` | Service |
| app/Services/Budget/AdminCorrectionService.php | 27 | FUND_KEYS | `'amount_sanctioned',` | Service |
| app/Services/Budget/AdminCorrectionService.php | 109 | getStoredValues | `'amount_sanctioned' => (float) ($project->amount_sanctioned ?? 0),` | Service |
| app/Services/Budget/AdminCorrectionService.php | 169 | normalizeManualValues | `'amount_sanctioned' => $sanctioned,` | Service |
| app/Services/Budget/AdminCorrectionService.php | 180 | applyValuesToProject | `'amount_sanctioned' => (string) $values['amount_sanctioned'],` | Service |
| app/Services/Budget/AdminCorrectionService.php | 202,207 | logAudit | old_sanctioned / new_sanctioned | Service |
| app/Services/Budget/BudgetSyncService.php | 42 | PRE_APPROVAL_FIELDS | `'amount_sanctioned',` | Service |
| app/Services/Budget/BudgetSyncService.php | 136 | getStoredValues | `'amount_sanctioned' => (float) ($project->amount_sanctioned ?? 0),` | Service |
| app/Services/BudgetValidationService.php | 59 | from resolver | `$amountSanctioned = (float) ($financials['amount_sanctioned'] ?? 0);` | Service |
| app/Services/BudgetValidationService.php | 118 | return | `'amount_sanctioned' => $amountSanctioned,` | Service |
| app/Services/BudgetValidationService.php | 154 | check | `if ($budgetData['amount_sanctioned'] < 0)` | Service |
| app/Services/BudgetValidationService.php | 159 | error value | `'value' => $budgetData['amount_sanctioned'],` | Service |
| app/Services/BudgetValidationService.php | 187 | calculatedOpening | `$budgetData['amount_sanctioned'] + ...` | Service |
| app/View/Components/FinancialTable.php | 62 | docblock | amount_sanctioned column example | View |
| app/View/Components/DataTable.php | 31 | docblock | amount_sanctioned footer sum example | View |
| app/Helpers/TableFormatter.php | 49 | docblock | column e.g. amount_sanctioned | Helper |
| app/Helpers/TableFormatter.php | 64 | docblock | columns e.g. amount_sanctioned | Helper |
| app/Models/Reports/Monthly/DPReport.php | 28 | property | amount_sanctioned_overview | Model |
| app/Models/Reports/Monthly/DPReport.php | 172 | fillable | amount_sanctioned_overview | Model |
| resources/views/provincial/ProjectList.blade.php | 124 | grand total | `$grandTotals['amount_sanctioned'] ?? 0` | View |
| resources/views/provincial/ProjectList.blade.php | 235 | row display | `$amountRequested = (float) ($fin['amount_sanctioned'] ?? 0);` | View |
| resources/views/projects/partials/Show/general_info.blade.php | 34 | requested var | `$amount_requested = (float) ($rf['amount_sanctioned'] ?? 0);` | View |
| resources/views/projects/partials/Show/general_info.blade.php | 135 | display | `$rf['amount_sanctioned'] ?? 0` | View |
| resources/views/projects/partials/Show/budget.blade.php | 28 | from budgetData | `$amountSanctioned = $budgetData['amount_sanctioned'];` | View |
| resources/views/projects/partials/not working show/general_info.blade.php | 57 | direct | `$project->amount_sanctioned` | View |
| resources/views/projects/partials/OLdshow/general_info.blade.php | 57 | direct | `$project->amount_sanctioned` | View |
| resources/views/projects/partials/Show/IES/educational_background.blade.php | 23 | IES model | `$educationBackground->amount_sanctioned` | View |
| resources/views/projects/Oldprojects/pdf.blade.php | 796 | PDF | `$resolvedFundFields['amount_sanctioned'] ?? 0` | View |
| resources/views/projects/partials/Edit/budget.blade.php | 136-145,176 | form | amount_sanctioned_preview, total_amount_sanctioned | View |
| resources/views/projects/partials/budget.blade.php | 12-13,119-128,159 | form | phases[].amount_sanctioned, total_amount_sanctioned | View |
| resources/views/projects/partials/NPD/budget.blade.php | 15,51 | form | phases[].amount_sanctioned, total_amount_sanctioned | View |
| resources/views/projects/partials/scripts.blade.php | 143,166,170,179 | JS | total_amount_sanctioned, amount_sanctioned_preview | View |
| resources/views/projects/partials/scripts-edit.blade.php | 1049,1072,1076,1085 | JS | same | View |
| resources/views/reports/monthly/developmentProject/reportform.blade.php | 195-196,204,226,528,541,568,595,622 | form/JS | project amount_sanctioned, amount_sanctioned[] | View |
| resources/views/reports/monthly/PDFReport.blade.php | 338,349 | report budget | budget->amount_sanctioned, budgets->sum('amount_sanctioned') | View |
| resources/views/reports/monthly/partials/view/statements_of_account/*.blade.php | 200 | report | accountDetails->sum('amount_sanctioned') | View |
| resources/views/reports/quarterly/*.blade.php | 203,205 | report | accountDetails->sum('amount_sanctioned') | View |
| resources/views/reports/monthly/partials/edit/statements_of_account/*.blade.php | various | report form | amount_sanctioned_overview, amount_sanctioned[] | View |
| resources/views/admin/budget_reconciliation/show.blade.php | 49 | label | 'amount_sanctioned' => 'Amount sanctioned' | View |
| resources/views/admin/budget_reconciliation/index.blade.php | 75-76 | stored/resolved | row['stored']['amount_sanctioned'], row['resolved']['amount_sanctioned'] | View |
| resources/views/projects/exports/budget-pdf.blade.php | 198 | export | budgetData['amount_sanctioned'] | View |
| resources/views/dev/table_component_preview.blade.php | 9-11,17,25,29-30,32 | dev preview | mock amount_sanctioned | View |
| database/migrations/2024_07_20_085634_create_projects_table.php | 35 | schema | amount_sanctioned decimal | Migration |
| database/migrations/2024_07_21_092111_create_dp_reports_table.php | 29 | schema | amount_sanctioned_overview | Migration |
| database/migrations/2024_07_21_092344_create_dp_account_details_table.php | 18 | schema | amount_sanctioned | Migration |
| database/migrations/2024_10_24_010909_create_project_i_e_s_education_backgrounds_table.php | 15 | schema | amount_sanctioned | Migration |
| database/migrations/2025_06_26_181405_update_amount_sanctioned_for_approved_projects.php | 15-25,32 | data migration | where/update amount_sanctioned | Migration |
| database/migrations/2026_01_08_* (reports) | various | report schema | amount_sanctioned_overview / amount_sanctioned | Migration |
| update_approved_projects.php | 18-21,31-32,46,63 | script | where amount_sanctioned, update, count | Script |
| tests/Feature/Budget/CoordinatorAggregationParityTest.php | 76,111,134 | test data | 'amount_sanctioned' => ... | Script |
| tests/Feature/Budget/ViewEditParityTest.php | 123 | test data | 'amount_sanctioned' => ... | Script |
| tests/Feature/FinancialResolverTest.php | 55,61,86,92,117,123,149,157,189,218 | assertions | amount_sanctioned | Script |

**Note:** Report-layer `amount_sanctioned` / `amount_sanctioned_overview` (DP reports, account details, annual/half-yearly/quarterly) are **different schema** (report tables), not `projects.amount_sanctioned`. They are listed for completeness; impact of project-level semantic change on report logic depends on how report data is populated (e.g. from project vs. snapshot).

---

## STEP 2 — CLASSIFY CONTEXT

For each usage, classification and stage-aware flag.

| File | Line | Classification | Stage-aware? |
|------|------|----------------|--------------|
| app/Models/OldProjects/Project.php | 94,287 | G (model/DB) | NO |
| app/Http/Controllers/ProvincialController.php | 499,508 | C (dashboard aggregation) | NO — sums over full filtered set (all statuses) |
| app/Http/Controllers/CoordinatorController.php | 151 | C (dashboard) | NO — raw DB where('amount_sanctioned','>',0) |
| app/Http/Controllers/CoordinatorController.php | 1111,1134,1140,1170 | A (approval flow) | YES — approval context |
| app/Http/Controllers/GeneralController.php | 2633,2647 | A (approval flow) | YES |
| app/Http/Controllers/Projects/ProjectController.php | 401 | B (predecessor budget map; phase amounts) | NO |
| app/Http/Controllers/Projects/ExportController.php | 634 | D (export) | NO — shows "Amount Sanctioned" for any status |
| app/Http/Requests/* (total_amount_sanctioned) | various | F (validation) | NO |
| app/Services/Reports/* (amount_sanctioned_overview, detail.amount_sanctioned) | various | E (report schema) | N/A (report schema) |
| app/Services/ReportMonitoringService.php | 386 | E (report accountDetails) | N/A |
| app/Services/ProjectStatusService.php | 234,244 | A (revert → sanctioned=0) | YES |
| app/Domain/Budget/ProjectFinancialResolver.php | all | F (invariant enforcement) | YES |
| app/Domain/Budget/Strategies/PhaseBasedBudgetStrategy.php | all | B (mixed: approved=DB, non-approved=derived) | YES |
| app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php | all | B (uses amount_requested as sanctioned) | Partially |
| app/Services/Budget/ProjectFundFieldsResolver.php | 72 | G (adapter to resolver) | YES (via resolver) |
| app/Services/Budget/AdminCorrectionService.php | all | A (approved-only reconciliation) | YES |
| app/Services/Budget/BudgetSyncService.php | 42,136 | A (pre-approval sync) | YES (pre-approval only) |
| app/Services/BudgetValidationService.php | all | F + B (validation; uses resolver) | YES |
| app/View/Components/*, TableFormatter | docblocks | G (generic column names) | NO |
| resources/views/provincial/ProjectList.blade.php | 124,235 | C (dashboard list + grand total) | NO |
| resources/views/projects/partials/Show/general_info.blade.php | 34,135 | B (displays requested + sanctioned from resolver) | YES (resolver) |
| resources/views/projects/partials/Show/budget.blade.php | 28 | B (BudgetValidationService → resolver) | YES |
| resources/views/projects/partials/not working show/general_info.blade.php | 57 | B (direct model) | NO |
| resources/views/projects/partials/OLdshow/general_info.blade.php | 57 | B (direct model) | NO |
| resources/views/projects/partials/Show/IES/educational_background.blade.php | 23 | E (IES education_background model) | NO |
| resources/views/projects/Oldprojects/pdf.blade.php | 796 | D (PDF) | NO (resolver used; label "Amount approved (Sanctioned)") |
| resources/views/projects/partials/Edit/budget.blade.php | various | B (edit form) | NO |
| resources/views/projects/partials/budget.blade.php | various | B (create/edit) | NO |
| resources/views/projects/partials/NPD/budget.blade.php | 15,51 | B (NPD budget) | NO |
| resources/views/reports/monthly/developmentProject/reportform.blade.php | 196,204 | D (report form; project->amount_sanctioned) | NO — draft can show non-zero |
| resources/views/reports/monthly/PDFReport.blade.php | 338,349 | E (report budget rows) | N/A (report schema) |
| resources/views/reports/*/statements_of_account/* | various | E (report) | N/A |
| resources/views/admin/budget_reconciliation/* | 49,75-76 | A (approved-only admin) | YES |
| resources/views/projects/exports/budget-pdf.blade.php | 198 | D (budget export PDF) | YES (via BudgetValidationService) |
| database/migrations/* | various | G | N/A |
| update_approved_projects.php | all | G (one-time script) | YES (approved only) |
| tests/* | all | G (tests) | YES (test semantics) |

---

## STEP 3 — RESOLVER CONSUMER MAP

| Consumer | File | Uses sanctioned for | Approved only? | Risk Level |
|----------|------|---------------------|----------------|------------|
| ProvincialController | app/Http/Controllers/ProvincialController.php | Grand totals + per-row display (as "amount requested" in column) | No — full filtered list | High (totals and labels) |
| CoordinatorController | app/Http/Controllers/CoordinatorController.php | Approval persistence, logs, success message | Yes (approve flow) | Low |
| GeneralController | app/Http/Controllers/GeneralController.php | Approval persistence, logs; also resolver in list views | Approval: yes; lists: no | Medium (list views) |
| ProjectController | app/Http/Controllers/Projects/ProjectController.php | resolvedFundFields for show | No | Medium (show must show requested vs sanctioned) |
| ExportController | app/Http/Controllers/Projects/ExportController.php | Word export "Amount Sanctioned" | No | Medium (draft export shows sanctioned) |
| ProjectDataHydrator | app/Services/ProjectDataHydrator.php | resolvedFundFields for PDF/show | No | Medium |
| BudgetValidationService | app/Services/BudgetValidationService.php | Validation + getBudgetSummary | No | Medium (opening formula uses sanctioned) |
| BudgetSyncService | app/Services/Budget/BudgetSyncService.php | Pre-approval sync (writes sanctioned) | Pre-approval only | High (writes; must not set sanctioned for non-approved) |
| AdminCorrectionService | app/Services/Budget/AdminCorrectionService.php | Stored vs resolved; apply values | Yes (approved only) | Low |
| ProjectFundFieldsResolver | app/Services/Budget/ProjectFundFieldsResolver.php | Delegates to ProjectFinancialResolver | Same as resolver | — |
| BudgetReconciliationController | app/Http/Controllers/Admin/BudgetReconciliationController.php | Uses AdminCorrectionService | Yes | Low |
| BudgetExportController | app/Http/Controllers/Projects/BudgetExportController.php | BudgetValidationService → budget-pdf | No | Medium |
| Exports/BudgetExport.php | app/Exports/BudgetExport.php | BudgetValidationService | No | Medium |
| ExecutorController | app/Http/Controllers/ExecutorController.php | Resolver for views | No | Medium |
| AdminReadOnlyController | app/Http/Controllers/Admin/AdminReadOnlyController.php | Resolver | No | Low |

---

## STEP 4 — DASHBOARD & INDEX SAFETY

### ProvincialController

- **projectList():** Base query has **no status filter** (only optional request filters). `fullDataset` = all projects in scope. Grand total sums `$grandTotals['amount_sanctioned'] += (float) ($financials['amount_sanctioned'] ?? 0)` over **all** those projects (resolver per project).
- **1) Does any dashboard sum amount_sanctioned without filtering approved?**  
  **Yes.** Provincial projectList sums resolver `amount_sanctioned` over the full filtered set (all statuses). It does **not** filter to approved only.
- **2) Does any dashboard treat amount_sanctioned as "requested"?**  
  **Yes.** ProjectList blade uses `$fin['amount_sanctioned']` in a variable `$amountRequested` and displays it in a column (conceptually "amount requested"). So the **column** is already labeled conceptually as requested; the **grand total** is summed from the same key.
- **3) Would setting non-approved sanctioned = 0 change dashboard totals?**  
  **Yes.** Non-approved projects currently contribute their resolver-derived "sanctioned" (e.g. overall - forwarded - local) to the grand total. After invariant: non-approved → 0. So provincial dashboard **grand total** would drop by the sum of those contributions. Column per row would show 0 for non-approved (if we keep same key) or require `amount_requested` for non-approved.
- **4) Would any index listing break?**  
  **Display:** No hard break. Totals and per-row values would change as above. If UI expects "requested" for draft and "sanctioned" for approved, we need two fields or stage-aware label.

### CoordinatorController

- **index():** `projects_with_amount_sanctioned` = `$projects->where('amount_sanctioned', '>', 0)->count()` — **raw DB**, no resolver, no status filter. So it counts projects with DB `amount_sanctioned > 0` (today that can include data-entry quirks; after change, only approved should have > 0). So after change this becomes a de facto "approved with sanctioned" count if we enforce invariant.
- **1) Sum without filtering approved?**  
  No sum of amount_sanctioned in coordinator dashboard; only **count** of projects with amount_sanctioned > 0.
- **2) Treat sanctioned as requested?**  
  No.
- **3) Would non-approved sanctioned = 0 change totals?**  
  The **count** would change: projects that currently have DB amount_sanctioned > 0 but are not approved would drop out after we set them to 0. So count may decrease.
- **4) Index break?**  
  No; count widget would reflect new semantics.

### GeneralController

- Resolver used in approval flow (approved-only persist) and in **list views** (e.g. project lists with resolvedFinancials). No single "dashboard sum of amount_sanctioned" found in GeneralController; list views use resolver for per-row display.
- **1) Sum without filtering approved?**  
  No explicit dashboard sum of amount_sanctioned in scanned code.
- **2) Treat as requested?**  
  Depends on view; provincial ProjectList is the main place that uses the value as "amount requested" in label.
- **3) Would non-approved = 0 change totals?**  
  Any list that shows or sums resolver `amount_sanctioned` would see 0 for non-approved after change.
- **4) Index break?**  
  No hard break; display semantics may need stage-aware labels/amount_requested.

---

## STEP 5 — EXPORT & PDF IMPACT

### PDF

- **resources/views/projects/Oldprojects/pdf.blade.php:** Row "Amount approved (Sanctioned)" uses `$resolvedFundFields['amount_sanctioned']`. So **after resolver change**, for draft projects this would show **0** (correct for "approved (Sanctioned)"). No change needed for label if "approved" is understood as post-approval only; if PDF is shown for draft, label may need to be stage-aware or show "Amount Requested" separately.
- **resources/views/projects/exports/budget-pdf.blade.php:** Uses `$budgetData['amount_sanctioned']` from BudgetValidationService (which uses resolver). So **after change**, draft projects would show 0 for amount_sanctioned in budget PDF.

### Export (Word)

- **ExportController::addGeneralInfoSection():** Writes "Amount Sanctioned: " + `$resolvedFundFields['amount_sanctioned']`. So for **draft** projects, export would show **0** after change. If we want "Amount Requested" for draft, we need resolver to expose `amount_requested` and export to use it when not approved.

### Report form (DP)

- **reportform.blade.php:** `value="{{ $project->amount_sanctioned }}"` for "Amount Sanctioned: Rs." and `amount_in_hand` = `$project->amount_sanctioned + $project->amount_forwarded`. For **draft** projects, after DB change these would be 0 (and amount_in_hand would be forwarded only). So report form would need **amount_requested** for draft if we want to show "requested" in that field, or keep label "Amount Sanctioned" and show 0 for draft.

### Answers

- **Does any PDF show "Amount Sanctioned" for draft projects?**  
  **Yes.** Oldprojects/pdf and Word export both show the same resolver field; today draft can have non-zero from resolver. After change they would show **0** for draft.
- **Does any export assume sanctioned == requested?**  
  **Yes.** Show/general_info uses `amount_sanctioned` for both "Amount Requested" and "Amount Sanctioned" (two rows). Export and report form effectively treat the single value as the main monetary figure for both concepts for non-approved.
- **Are labels stage-aware?**  
  **No.** Labels are fixed ("Amount Sanctioned", "Amount approved (Sanctioned)"); they are not switched by status.

---

## STEP 6 — DB LEVEL ANALYSIS

- **Migrations:**  
  - `projects.amount_sanctioned` exists (create_projects_table).  
  - Report tables use `amount_sanctioned_overview` / `amount_sanctioned` in **report** schema (dp_reports, dp_account_details, annual/half-yearly/quarterly details).  
  - Migration `2025_06_26_181405_update_amount_sanctioned_for_approved_projects` updates **approved** projects only: sets `amount_sanctioned = overall_project_budget` where sanctioned null/0 and overall > 0. No logic that **depends** on sanctioned > 0 for non-approved at DB level.
- **Raw queries:**  
  - `update_approved_projects.php`: `where('amount_sanctioned', '>', 0)` for verification/count; `whereNull('amount_sanctioned')->orWhere('amount_sanctioned', 0)` for update. All in **approved** context.  
  - CoordinatorController: `$projects->where('amount_sanctioned', '>', 0)->count()` — no status filter; after invariant this effectively counts "has sanctioned > 0" (should be approved only if we enforce).
- **groupBy / aggregates:**  
  - No application code found that groupBy('amount_sanctioned') on projects. Report-layer aggregates use report tables' amount_sanctioned/amount_sanctioned_overview.
- **Conclusion:**  
  No DB logic **depends** on non-approved projects having sanctioned > 0. Enforcing non-approved → 0 at resolver and DB is consistent. Report schema is separate; population of report `amount_sanctioned_overview` from project may need to use `amount_requested` for draft when that field exists.

---

## STEP 7 — FINAL IMPACT MATRIX

| Area | Current Behavior | After Change | Risk | Requires Refactor? |
|------|------------------|--------------|------|--------------------|
| ProjectFinancialResolver + strategies | Non-approved: PhaseBased returns overall - contributions; DirectMapped uses amount_requested as sanctioned | Non-approved: return sanctioned=0; expose amount_requested where needed | High | Yes (strategies + resolver contract) |
| ProvincialController projectList | Sums resolver sanctioned over all statuses; column shows same as "requested" | Grand total excludes non-approved sanctioned; column needs requested for draft | High | Yes (grand total semantics; optional amount_requested column) |
| CoordinatorController dashboard | Counts projects with DB amount_sanctioned > 0 | Count = approved with sanctioned (if we enforce) | Low | Optional (document semantics) |
| CoordinatorController approveProject | Persists resolver sanctioned/opening | Same; resolver returns DB sanctioned for approved | Low | No |
| GeneralController approve | Persists resolver sanctioned/opening | Same | Low | No |
| ProjectController show | resolvedFundFields; general_info shows requested + sanctioned (same value today) | Show 0 sanctioned for draft; need amount_requested for "Amount Requested" row | Medium | Yes (general_info + resolver) |
| ExportController Word | "Amount Sanctioned" from resolvedFundFields | Draft → 0 | Medium | Optional (add Amount Requested for draft) |
| ProjectDataHydrator / pdf.blade.php | resolvedFundFields for PDF | Draft → 0 for "Amount approved (Sanctioned)" | Low | Optional label/clarification |
| BudgetValidationService | opening = sanctioned + forwarded + local; validates >= 0 | Non-approved sanctioned=0 → opening = forwarded + local | Medium | Yes (ensure formula and labels) |
| BudgetSyncService syncBeforeApproval | Writes resolver output including amount_sanctioned | Must **not** write non-zero sanctioned for non-approved | High | Yes (only write sanctioned when approved or keep 0) |
| AdminCorrectionService | Approved only; read/write sanctioned | No change | Low | No |
| Report form (DP) | project->amount_sanctioned, amount_in_hand | Draft: 0; amount_in_hand = forwarded only | Medium | Yes if "Amount Requested" needed for draft |
| Budget export PDF | budgetData['amount_sanctioned'] | Draft → 0 | Low | Optional |
| Report schema (DP, annual, etc.) | amount_sanctioned_overview, detail.amount_sanctioned | Populated from project or snapshot | Low | Only if report uses project.amount_sanctioned for draft |
| DB migrations / scripts | Approved-only updates | No change | Low | No |
| Tests | Assert sanctioned 0 for draft, >0 for approved | Align with new semantics + amount_requested | Medium | Yes |

### Counts

- **Total project-level sanctioned usages (app + views, excluding report schema):** ~75+ mention points (many in same file).
- **Safe (approved-only or post-change correct):** Coordinator/General approve flow, AdminCorrectionService, ProjectStatusService revert, migrations, tests once updated.
- **Requires modification:** Resolver + strategies, ProvincialController projectList (totals + column), Show general_info (amount_requested), BudgetSyncService (do not set sanctioned for non-approved), BudgetValidationService (formula/labels), report form (draft requested), optional export/PDF labels.
- **High-risk breakpoints:** Resolver/strategies (source of truth), BudgetSyncService (writes), ProvincialController (dashboard totals and semantics), general_info (requested vs sanctioned display).

---

## STEP 8 — RECOMMENDED MIGRATION ORDER

1. **Resolver + strategies**  
   - Implement invariant: non-approved → `amount_sanctioned = 0`.  
   - Add `amount_requested` to resolver return (and strategies) where it differs from sanctioned (e.g. non-approved: requested = current derived value, sanctioned = 0).

2. **BudgetSyncService**  
   - Pre-approval sync: do **not** set `amount_sanctioned` (or set 0) for non-approved; or restrict syncBeforeApproval to only overwrite when project is about to be approved (already guarded by status).

3. **Persistence on approval**  
   - Keep Coordinator/General approval flow: persist resolver `amount_sanctioned` and `opening_balance` on approve (resolver already returns correct values for approved).

4. **Views and controllers**  
   - ProvincialController projectList: use resolver `amount_requested` for column if present, else sanctioned; grand total: document that it is "total sanctioned" (approved only after invariant) or add separate "total requested" if needed.  
   - Show general_info: "Amount Requested" row ← `amount_requested`; "Amount Sanctioned" row ← `amount_sanctioned` (0 for non-approved).  
   - Report form: for draft, consider showing `amount_requested` in "Amount Sanctioned" read-only field with label clarification, or keep 0 and add separate "Amount Requested" field when available.

5. **Export / PDF**  
   - Word export: add "Amount Requested" when not approved (from resolver).  
   - PDF: optional label tweak for draft ("Amount Requested" vs "Amount approved (Sanctioned)").

6. **DB**  
   - One-time data fix: set `projects.amount_sanctioned = 0` where status is not approved (if product requirement is to store 0 for non-approved).  
   - Add `amount_requested` column if stored at project level (optional; resolver can compute).

7. **Tests**  
   - Update FinancialResolverTest and related tests for sanctioned=0 non-approved and amount_requested where used.

---

**M3.7 Phase 0 Regeneration Audit Complete — Current Codebase Mapped**
