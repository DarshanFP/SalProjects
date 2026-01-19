# Files Requiring Number Format Updates

This document lists all files that need to be updated to implement Indian number formatting (lakhs, crores system).

## Summary
- **Total Blade Files**: 62 files
- **JavaScript Files (Blade embedded)**: 19 files  
- **PHP Controller/Service Files**: 6 files
- **JavaScript Standalone Files**: 1 file (new helper)
- **CSS Files**: 1 file (optional styling)

---

## Category 1: Blade Template Files Using `number_format()`

### 1.1 Project Views (22 files)

| # | File Path | Lines | Description |
|---|-----------|-------|-------------|
| 1 | `resources/views/projects/partials/Show/budget.blade.php` | 247-250 | Budget table amounts |
| 2 | `resources/views/projects/partials/Show/general_info.blade.php` | 103, 107, 111, 115, 119 | Project budget fields |
| 3 | `resources/views/projects/partials/Show/IAH/budget_details.blade.php` | Multiple | Budget details amounts |
| 4 | `resources/views/projects/partials/Show/IIES/estimated_expenses.blade.php` | Multiple | Estimated expenses |
| 5 | `resources/views/projects/partials/Show/IIES/personal_info.blade.php` | Multiple | Income fields |
| 6 | `resources/views/projects/partials/Show/IIES/family_working_members.blade.php` | Multiple | Family income |
| 7 | `resources/views/projects/partials/Show/IAH/earning_members.blade.php` | Multiple | Earning members |
| 8 | `resources/views/projects/partials/Show/IES/personal_info.blade.php` | Multiple | Personal info amounts |
| 9 | `resources/views/projects/partials/Show/attachments.blade.php` | Multiple | Attachment related |
| 10 | `resources/views/projects/partials/not working show/IIES/personal_info.blade.php` | Multiple | Income fields |
| 11 | `resources/views/projects/partials/not working show/IIES/family_working_members.blade.php` | 30 | Monthly income |
| 12 | `resources/views/projects/partials/not working show/IES/personal_info.blade.php` | Multiple | Personal info |
| 13 | `resources/views/projects/partials/not working show/IAH/earning_members.blade.php` | Multiple | Earning members |
| 14 | `resources/views/projects/partials/not working show/budget.blade.php` | Multiple | Budget amounts |
| 15 | `resources/views/projects/partials/not working show/IIES/estimated_expenses.blade.php` | Multiple | Estimated expenses |
| 16 | `resources/views/projects/partials/not working show/IAH/budget_details.blade.php` | Multiple | Budget details |
| 17 | `resources/views/projects/partials/not working show/general_info.blade.php` | Multiple | General info |
| 18 | `resources/views/projects/partials/OLdshow/general_info.blade.php` | Multiple | General info |
| 19 | `resources/views/projects/partials/OLdshow/budget.blade.php` | Multiple | Budget amounts |
| 20 | `resources/views/projects/Oldprojects/pdf.blade.php` | 794, 798 | PDF amounts |
| 21 | `resources/views/projects/exports/budget-pdf.blade.php` | Multiple | Budget export |
| 22 | `resources/views/projects/exports/budget-report.blade.php` | Multiple | Budget report |

### 1.2 Report Views (15 files)

| # | File Path | Lines | Description |
|---|-----------|-------|-------------|
| 23 | `resources/views/reports/monthly/PDFReport.blade.php` | 336-341, 347 | PDF budget amounts |
| 24 | `resources/views/reports/monthly/PDFReport/statements_of_account.blade.php` | 19-21 | Statements amounts |
| 25 | `resources/views/reports/monthly/pdf.blade.php` | 190, 194, 198, 217-222 | Account amounts |
| 26 | `resources/views/reports/monthly/doc-copy.blade` | 183-230 | Document copy |
| 27 | `resources/views/reports/monthly/partials/view/statements_of_account/individual_livelihood.blade.php` | 12, 14, 16, 79, 86, 93, 100, 107, 114-115, 129, 134, 140, 145, 188-204 | Multiple amount fields |
| 28 | `resources/views/reports/monthly/partials/view/statements_of_account/individual_education.blade.php` | Multiple | Similar to livelihood |
| 29 | `resources/views/reports/monthly/partials/view/statements_of_account/individual_health.blade.php` | Multiple | Health reports |
| 30 | `resources/views/reports/monthly/partials/view/statements_of_account/individual_ongoing_education.blade.php` | Multiple | Ongoing education |
| 31 | `resources/views/reports/monthly/partials/view/statements_of_account/institutional_education.blade.php` | Multiple | Institutional education |
| 32 | `resources/views/reports/monthly/partials/view/statements_of_account/development_projects.blade.php` | Multiple | Development projects |
| 33 | `resources/views/reports/monthly/partials/view/LivelihoodAnnexure.blade.php` | Multiple | Livelihood annexure |
| 34 | `resources/views/reports/aggregated/quarterly/show.blade.php` | 206-211 | Aggregated amounts |
| 35 | `resources/views/reports/monthly/partials/edit/attachments.blade.php` | Multiple | Edit attachments |

### 1.3 Dashboard & Widget Views (25 files)

#### Executor Views
| # | File Path | Lines | Description |
|---|-----------|-------|-------------|
| 36 | `resources/views/executor/approvedReports.blade.php` | 40, 48, 56, 96-99 | Approved reports |
| 37 | `resources/views/executor/pendingReports.blade.php` | 44, 52, 60, 100-103 | Pending reports |
| 38 | `resources/views/executor/ReportList.blade.php` | Multiple | Report list |
| 39 | `resources/views/executor/index.blade.php` | Multiple | Dashboard |
| 40 | `resources/views/executor/widgets/quick-stats.blade.php` | 52, 95, 117, 144, 170 | Quick stats |
| 41 | `resources/views/executor/widgets/budget-analytics.blade.php` | Multiple | Budget analytics |
| 42 | `resources/views/executor/widgets/report-analytics.blade.php` | Multiple | Report analytics |

#### Coordinator Views
| # | File Path | Lines | Description |
|---|-----------|-------|-------------|
| 43 | `resources/views/coordinator/approvedReports.blade.php` | 63, 71, 79, 122-125 | Approved reports |
| 44 | `resources/views/coordinator/pendingReports.blade.php` | Multiple | Pending reports |
| 45 | `resources/views/coordinator/ProjectList.blade.php` | Multiple | Project list |
| 46 | `resources/views/coordinator/approvedProjects.blade.php` | 109-110 | Approved projects |
| 47 | `resources/views/coordinator/budget-overview.blade.php` | Multiple | Budget overview |
| 48 | `resources/views/coordinator/budgets.blade.php` | Multiple | Budgets |
| 49 | `resources/views/coordinator/index.blade.php` | Multiple | Dashboard |
| 50 | `resources/views/coordinator/ProjectList-copy.blade` | Multiple | Project list copy |

#### Provincial Views
| # | File Path | Lines | Description |
|---|-----------|-------|-------------|
| 51 | `resources/views/provincial/approvedReports.blade.php` | 63, 71, 79, 122-125 | Approved reports |
| 52 | `resources/views/provincial/pendingReports.blade.php` | Multiple | Pending reports |
| 53 | `resources/views/provincial/approvedProjects.blade.php` | 94-95 | Approved projects |
| 54 | `resources/views/provincial/ProjectList.blade.php` | 179, 182, 198, 207 | Project list |
| 55 | `resources/views/provincial/index.blade.php` | 174, 182, 190, 218-220, 251-253 | Dashboard |
| 56 | `resources/views/provincial/ReportList.blade.php` | Multiple | Report list |
| 57 | `resources/views/provincial/widgets/team-overview.blade.php` | 143 | Team overview |
| 58 | `resources/views/provincial/widgets/team-budget-overview.blade.php` | Multiple | Budget overview |
| 59 | `resources/views/provincial/widgets/team-performance.blade.php` | Multiple | Performance |
| 60 | `resources/views/provincial/widgets/center-comparison.blade.php` | 139-142, 146 | Center comparison |

#### Other Views
| # | File Path | Lines | Description |
|---|-----------|-------|-------------|
| 61 | `resources/views/projects/partials/Edit/attachment.blade.php` | Multiple | Edit attachment |

---

## Category 2: JavaScript Files with `toLocaleString()`

### 2.1 Files Already Using `en-IN` (Verify Correctness) - 18 files

| # | File Path | Lines | Status |
|---|-----------|-------|--------|
| 1 | `resources/views/reports/monthly/partials/statements_of_account/individual_education.blade.php` | 453-457 | ✓ Verify |
| 2 | `resources/views/reports/monthly/partials/statements_of_account/individual_health.blade.php` | 453-457 | ✓ Verify |
| 3 | `resources/views/reports/monthly/partials/statements_of_account/individual_livelihood.blade.php` | 453-457 | ✓ Verify |
| 4 | `resources/views/reports/monthly/partials/statements_of_account/institutional_education.blade.php` | 426-430 | ✓ Verify |
| 5 | `resources/views/reports/monthly/partials/statements_of_account/individual_ongoing_education.blade.php` | 409-413 | ✓ Verify |
| 6 | `resources/views/reports/monthly/partials/statements_of_account/development_projects.blade.php` | 453-457 | ✓ Verify |
| 7 | `resources/views/reports/monthly/partials/create/statements_of_account.blade.php` | 418-422 | ✓ Verify |
| 8 | `resources/views/reports/monthly/partials/edit/statements_of_account/individual_education.blade.php` | 439-443 | ✓ Verify |
| 9 | `resources/views/reports/monthly/partials/edit/statements_of_account/individual_health.blade.php` | 439-443 | ✓ Verify |
| 10 | `resources/views/reports/monthly/partials/edit/statements_of_account/individual_livelihood.blade.php` | 439-443 | ✓ Verify |
| 11 | `resources/views/reports/monthly/partials/edit/statements_of_account/institutional_education.blade.php` | 439-443 | ✓ Verify |
| 12 | `resources/views/reports/monthly/partials/edit/statements_of_account/individual_ongoing_education.blade.php` | 423-427 | ✓ Verify |
| 13 | `resources/views/reports/monthly/partials/edit/statements_of_account/development_projects.blade.php` | 439 | ✓ Verify |
| 14 | `resources/views/projects/partials/Show/budget.blade.php` | 551, 568, 579, 613, 622, 632 | ✓ Verify |
| 15 | `resources/views/coordinator/budget-overview.blade.php` | 493, 582-584 | ✓ Verify |
| 16 | `resources/views/provincial/widgets/team-budget-overview.blade.php` | 325, 360, 419, 485 | ✓ Verify |
| 17 | `resources/views/provincial/widgets/center-comparison.blade.php` | 362 | ✓ Verify |
| 18 | `resources/views/provincial/widgets/team-performance.blade.php` | 423, 483 | ✓ Verify |

### 2.2 Files Using `en-US` (Needs Fixing) - 1 file

| # | File Path | Lines | Action Required |
|---|-----------|-------|-----------------|
| 1 | `resources/views/executor/widgets/budget-analytics.blade.php` | 233, 273, 287, 393, 500 | ⚠ Change to 'en-IN' |

---

## Category 3: PHP Controller/Service Files

| # | File Path | Lines | Description |
|---|-----------|-------|-------------|
| 1 | `app/Http/Requests/Projects/UpdateGeneralInfoRequest.php` | 65 | Validation error message |
| 2 | `app/Services/BudgetValidationService.php` | 249, 274, 290 | Validation messages |
| 3 | `app/Http/Controllers/Projects/ExportController.php` | 2115-2130 | Export amounts |
| 4 | `app/Http/Controllers/Reports/Monthly/ExportReportController.php` | Multiple | PDF export |
| 5 | `app/Http/Controllers/Reports/Aggregated/AggregatedReportExportController.php` | Multiple | Aggregated export |
| 6 | `app/Http/Controllers/Projects/BudgetExportController.php` | Multiple | Budget export |

---

## Category 4: New Files to Create

| # | File Path | Description |
|---|-----------|-------------|
| 1 | `app/Helpers/NumberFormatHelper.php` | PHP helper class for Indian number formatting |
| 2 | `public/js/indian-number-format.js` | JavaScript helper functions |
| 3 | `public/js/datatables-indian-config.js` | DataTables configuration for Indian format |
| 4 | `tests/Unit/NumberFormatHelperTest.php` | Unit tests for helper functions |
| 5 | `resources/css/indian-number-format.css` | Optional CSS styling (if needed) |

---

## Category 5: Files to Update (Configuration)

| # | File Path | Action | Description |
|---|-----------|--------|-------------|
| 1 | `bootstrap/app.php` or `app/Providers/AppServiceProvider.php` | Add | Register global helper functions |
| 2 | `resources/views/layouts/*.blade.php` | Add | Include `indian-number-format.js` script |
| 3 | `resources/views/layouts/*.blade.php` | Add | Include `datatables-indian-config.js` (if using DataTables) |

---

## Priority Order

### High Priority (Core Functionality)
1. Create helper files (NumberFormatHelper.php, indian-number-format.js)
2. Update project views (Category 1.1)
3. Update report views (Category 1.2)
4. Fix JavaScript en-US to en-IN (Category 2.2)

### Medium Priority (Dashboard & Exports)
5. Update dashboard/widget views (Category 1.3)
6. Update PHP controllers/services (Category 3)
7. Update PDF generation templates

### Low Priority (Enhancements)
8. DataTables configuration
9. CSS styling
10. Additional JavaScript refinements

---

## Notes

- Files in "not working show" and "OLdshow" folders may be deprecated. Verify before updating.
- All `number_format()` calls should be replaced with `format_indian()` or `format_indian_currency()`
- All `toLocaleString('en-US')` should be changed to `toLocaleString('en-IN')`
- Verify files already using `en-IN` are working correctly
- Test PDF generation after updates
- Test export functionality after updates

---

**Last Updated**: [Current Date]
**Total Files to Update**: ~88 files (62 Blade + 19 JS + 6 PHP + 1 JS fix)
**New Files to Create**: 5 files
