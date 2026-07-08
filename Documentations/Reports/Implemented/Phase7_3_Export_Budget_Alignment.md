# Phase 7.3 — Export Budget Alignment

**Status:** ✅ Evaluated & documented  
**Date:** 2026-06-13

## Evaluation

Plan suggested using `BudgetCalculationService::getBudgetsForReport($project, true)` in `ExportReportController` for PDF/DOC.

## Decision

**Keep `$report->accountDetails`** for export SOA sections.

Exports must reflect **persisted report data** (what was submitted/approved), not a live re-fetch from project budgets. Re-fetching could show different phase rows or amounts than the saved report.

PDF and DOC already iterate `accountDetails` in views and `addStatementsOfAccountSection()`.

## Cleanup

- Removed unused dead method `getBudgetDataByProjectType()` from `ExportReportController`
- Clarified comments in `downloadPdf()` / `downloadDoc()`: SOA from saved account details

## When to use `getBudgetsForReport`

| Context | Source |
|---------|--------|
| Report **create/edit** forms | `getBudgetsForReport()` — live project budgets |
| Report **PDF/DOC export** | `$report->accountDetails` — saved SOA rows |
| Project show / dashboards | `ProjectFinancialResolver` |

## Files

- `app/Http/Controllers/Reports/Monthly/ExportReportController.php`
