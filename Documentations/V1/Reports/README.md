# Documentations / V1 / Reports

This folder holds V1 documentation for **all reporting features** in SalProjects.

## Contents

| Document | Purpose |
|----------|---------|
| **COMPREHENSIVE_REPORTS_REVIEW.md** | Full review of Monthly, Quarterly (program-specific), and Aggregated reports; project types; completed tasks; working features; gaps and recommendations |

## Report Streams (Summary)

1. **Monthly Reports** — 12 project types, DPReport, full workflow, Save Draft, field indexing, Activity Card UI, PDF/DOC export.
2. **Quarterly (program-specific)** — 5 programs: development-project, development-livelihood, institutional-support, skill-training, women-in-distress; full CRUD, review, revert.
3. **Aggregated** — Quarterly, Half-Yearly, Annual; built from approved monthly reports; AI edit; PDF/Word export; comparison.

## Related Documentation (outside this folder)

- `Documentations/REVIEW/5th Review/Report Views/` — Report Views enhancement, Save Draft, expenses tracking, Phase 11/12.
- `Documentations/Manual Kit/Management_Report_Application_Enhancements.md` — High-level report enhancements.
- `Documentations/Manual Kit/Executor_User_Manual.md` — Executor report usage.

## Code References

- **Monthly:** `app/Http/Controllers/Reports/Monthly/ReportController.php`, `app/Models/Reports/Monthly/DPReport.php`
- **Quarterly (program):** `app/Http/Controllers/Reports/Quarterly/*.php`
- **Aggregated:** `app/Http/Controllers/Reports/Aggregated/*.php`
- **Services:** `ReportQueryService`, `ReportStatusService`, `ActivityHistoryService`, `BudgetCalculationService`
