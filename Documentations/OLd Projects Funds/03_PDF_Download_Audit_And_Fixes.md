# PDF Download — Audit and Fixes (Implemented)

## What the user sees

When a user clicks **Download PDF** for a project (example: `/projects/DP-0006/download-pdf`), the system generates a PDF using mPDF from a Blade template.

This audit ensured:

- PDF generation works for **all roles/routes** (admin/provincial/coordinator/executor/applicant)
- The PDF reflects the latest budget field rules:
  - Amount Forwarded (Existing Funds)
  - Local Contribution
  - Correct sanctioned/opening math
- The PDF budget summary cards render cleanly (no dark blue background)

---

## Routes (where PDF download endpoints are defined)

- File: `routes/web.php`
- Active routes:
  - `/projects/{project_id}/download-pdf` → `ExportController@downloadPdf`
  - `/provincial/projects/{project_id}/download-pdf` → `ExportController@downloadPdf`
  - `/coordinator/projects/{project_id}/download-pdf` → `ExportController@downloadPdf`

---

## Controller (what code runs)

- File: `app/Http/Controllers/Projects/ExportController.php`
- Method: `downloadPdf($project_id)`

### What it does (human readable)

- Loads the project with relationships (budgets, objectives, attachments, etc.)
- Checks permissions depending on role:
  - Admin can download all
  - Coordinator/provincial have status-based rules
  - Executor/applicant checked via `ProjectPermissionHelper::canView(...)`
- Builds a `$projectRoles` array for signature sections
- Calls `loadAllProjectData($project_id)` so the PDF has the same “shape” as the Show view
- Renders the view:
  - `resources/views/projects/Oldprojects/pdf.blade.php`
- Uses mPDF to output the final PDF

---

## Template and partials (what HTML becomes the PDF)

### Main PDF template

- File: `resources/views/projects/Oldprojects/pdf.blade.php`

### It reuses the same partials as web “Show”

Important includes:

- `@include('projects.partials.Show.general_info')`
  - File: `resources/views/projects/partials/Show/general_info.blade.php`
- `@include('projects.partials.Show.budget')`
  - File: `resources/views/projects/partials/Show/budget.blade.php`

This reuse is intentional: if the Show partials are correct, the PDF stays aligned.

---

## Budget + local contribution updates reflected in PDF

### “Basic Information” section (show + pdf)

- File: `resources/views/projects/partials/Show/general_info.blade.php`
- Change:
  - Added **Local Contribution** line after **Amount Forwarded (Existing Funds)**

### “Budget” section summary cards (show + pdf)

- File: `resources/views/projects/partials/Show/budget.blade.php`
- Change:
  - Summary grid now includes:
    - Overall Project Budget
    - Amount Forwarded (Existing Funds)
    - Local Contribution
    - Amount Sanctioned
    - Opening Balance
  - Display calculations use:
    - `forwarded + local`

---

## Approval section in PDF

- File: `resources/views/projects/Oldprojects/pdf.blade.php`
- Change:
  - Updated “Amount approved (Sanctioned)” to use the correct formula using:
    - `amount_sanctioned` or fallback computed from `overall - (forwarded + local)`
  - Added “Contributions considered” row showing forwarded + local values.

---

## Fix: remove dark blue background for budget cards in PDFs

### Why it happened

The “Show budget” partial (`resources/views/projects/partials/Show/budget.blade.php`) contains its own `<style>` block that sets:

- `.budget-summary-card { background-color: #132f6b; color: #fff; ... }`

mPDF renders this style inside the PDF, resulting in dark cards.

### What we changed

- File: `resources/views/projects/Oldprojects/pdf.blade.php`
  - Added a `pdf-document` class on `<body>`
  - Added **high-specificity CSS overrides** (and a final override at the end of `<body>`) to force:
    - white background
    - black text
    - black border

- File: `resources/views/projects/partials/Show/budget.blade.php`
  - Added a `@media print` fallback override for `.budget-summary-card` (so print/PDF contexts can be white)

---

## How to smoke-test quickly

- Visit:
  - `/projects/DP-0006/download-pdf`
  - `/provincial/projects/DP-0006/download-pdf`
  - `/coordinator/projects/DP-0006/download-pdf`
- Confirm in the PDF:
  - Local Contribution is visible
  - Amount Sanctioned math matches: overall − (forwarded + local)
  - Opening Balance is correct (typically equals overall if valid)
  - Budget summary cards are **white (not dark blue)**
- Monitor logs:
  - `storage/logs/laravel.log` for errors from `ExportController@downloadPdf`


