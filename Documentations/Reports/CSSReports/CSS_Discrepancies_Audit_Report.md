# Monthly Reports CSS & Visual Aesthetics Audit Report

**Date:** 2026-06-28  
**Scope:** Full monthly report styling lifecycle across Create, Edit, View, PDF Export, and DOC Export views for all 12 project types.  
**Objective:** Identify CSS discrepancies, styling inconsistencies, responsive layout bottlenecks, hardcoded color clashing, theme contrast issues, and print page-break fragility.

---

## 1. Architecture & Styling Overview

Monthly reporting interface styling relies on a hybrid approach combining Bootstrap base styling, project-level CSS, and component-level `<style>` blocks embedded within individual Blade templates and partials.

```
+-----------------------------------------------------------------------------------+
|                            Monthly Report Styling Layers                          |
+-----------------------------------------------------------------------------------+
| 1. Global / Bootstrap Baseline  :: app.css, Bootstrap 5 grid, cards, and tables    |
| 2. Page-Level Layout Overrides  :: show.blade.php, edit.blade.php, PDFReport.blade |
| 3. Component / Partial Styles   :: statements_of_account/*.blade.php (<style>)    |
| 4. Ad-hoc Inline Attributes     :: style="background-color: #202ba3;"             |
+-----------------------------------------------------------------------------------+
```

### Component Styling Coverage Matrix

| Component / Template Stage | Baseline CSS Class | Hardcoded Inline Styles | Responsive Containers | Theme Contrast |
|---|---|---|---|---|
| **Create Form Base** (`reportform.blade.php`) | Bootstrap 5 Card | High (Custom colors) | Moderate | Standard |
| **Create SOA Partials** (6 types) | `.table-bordered` | High (`#202ba3` inputs) | ✅ Wrapped (`Phase 14.1`) | ⚠️ Dark input contrast |
| **Edit SOA Partials** (6 types) | `.budget-statements-table` | High (`#202ba3` inputs) | ✅ Wrapped (`Phase 14.1`) | ⚠️ Dark input contrast |
| **Web View Stage** (`show.blade.php`) | `.report-value-entered` | Low | High (`.info-grid`) | ✅ High (Green highlight) |
| **PDF Export** (`PDFReport.blade.php`) | Dedicated PDF CSS | Moderate | N/A (mPDF layout) | ⚠️ Page-break gaps |
| **DOC Export** (`doc.blade.php` / PhpWord) | PhpWord Table Styles | Inline cell margins | N/A (Word document) | Standard |

---

## 2. Comprehensive CSS Discrepancy Matrix

| Finding ID | Discrepancy Category | Affected Templates | Severity | Visual Impact |
|---|---|---|---|---|
| **CSS-01** | Hardcoded Inline Background Colors | Create/Edit SOA, Attachments, IGE profile | **Medium** | Inconsistent dark blue inputs clashing with light theme form inputs. |
| **CSS-02** | Inconsistent Table Styling & Utility Classes | Create vs Edit vs View SOA partials | **Medium** | Uneven padding, header background colors, and border widths across report stages. |
| **CSS-03** | Status Indicator & Badge Color Divergence | Create, Edit, View, and PDF templates | **Low** | Budget row badges switch between Teal (`#0f766e`), Cyan (`#17a2b8`), and Green. |
| **CSS-04** | PDF Print & Page-Break Fragility | `PDFReport.blade.php` | **Medium** | Table headers detach from rows; multi-row blocks split awkwardly across pages. |
| **CSS-05** | Card Header & Section Typography Misalignment | Annexure view & create partials | **Low** | Subtle vertical alignment shifts due to un-reset `h4`/`h5` margins in card headers. |

---

## 3. Detailed Technical Findings & Remediation Plans

### CSS-01 — Hardcoded Inline Background Colors (`#202ba3` / `#1f2ba4`)

#### Root Cause Analysis
In multiple create and edit Blade templates, inputs designed for active month entry utilize inline style overrides:
- `style="background-color: #202ba3;"` (in `attachments.blade.php`, `institutional_ongoing_group.blade.php`, and all 6 create/edit SOA partials).
- `style="background-color: #1f2ba4;"` (in `institutional_ongoing_group.blade.php` total rows).

```html
<!-- Example from create/attachments.blade.php -->
<input type="text" name="attachment_title[]" class="form-control" style="background-color: #202ba3;">
```

#### Visual & Functional Impact
1. **Accessibility / Contrast:** Text entered inside these fields defaults to dark or black text depending on browser defaults, creating severe contrast accessibility issues against `#202ba3` (dark royal blue).
2. **Design System Fragmentation:** Circumvents central CSS theme tokens. If the application changes primary colors, these inputs retain hardcoded hex values.

#### Recommended Remediation
Replace ad-hoc inline background styles with a unified CSS utility class in `app.css`:
```css
.input-highlight-active {
    background-color: #1e293b !important;
    color: #ffffff !important;
    border-color: #3b82f6 !important;
}
```

---

### CSS-02 — Inconsistent Table Styling & Utility Classes

#### Root Cause Analysis
Table components across the 12 project types employ fragmented class combinations:
1. **Create SOA Partials:** `<table class="table table-bordered">`
2. **Edit SOA Partials:** `<table class="table table-bordered budget-statements-table">`
3. **View SOA Partials:** `<table class="table table-bordered table-custom budget-details-table">`
4. **Monitoring Partials:** `<table class="table table-sm table-bordered">`

#### Visual & Functional Impact
- **Cell Padding Variance:** `table-sm` compresses row height to 0.375rem, while standard `table` uses 0.75rem. Users viewing monthly report details experience sudden density changes when scrolling from activity monitoring to financial statements.
- **Header Background Mismatch:** Edit tables enforce styled headers via `.budget-statements-table`, while Create tables rely on default white/transparent headers.

#### Recommended Remediation
Standardize all report table definitions under unified class abstractions:
- Form Tables: `class="table table-bordered align-middle report-form-table"`
- Summary View Tables: `class="table table-bordered align-middle report-view-table"`

---

### CSS-03 — Status Indicator & Badge Color Divergence

#### Root Cause Analysis
Visual indicators representing "Budget Row" or "User Entered Data" vary significantly across rendering targets:
- **Create/Edit Web Form:** `.badge.scheduled-months-badge` using `#0f766e` (dark teal).
- **PDF Export:** `.budget-badge` using `#17a2b8` (cyan badge).
- **Web View Mode:** `.report-value-entered` using green left border `#05a34a` and light green background `rgba(5, 163, 74, 0.12)`.

```css
/* PDF Report Badge */
.budget-badge { background-color: #17a2b8; color: white; }

/* Form Badge */
.badge.scheduled-months-badge { background-color: #0f766e !important; color: #fff; }
```

#### Visual & Functional Impact
Cognitive dissonance for users and reviewers comparing online web view reports against exported PDF documents due to shifting color semantics for the exact same data classification.

#### Recommended Remediation
Unify color token variables across web and PDF stylesheets:
- `Budget Row Indicator`: Uniform Teal (`#0f766e`) across both HTML and PDF templates.
- `Entered Expense Highlight`: Soft emerald green theme across both Web and PDF targets.

---

### CSS-04 — PDF Print & Page-Break Fragility

#### Root Cause Analysis
In `resources/views/reports/monthly/PDFReport.blade.php`, container blocks for photos use `page-break-inside: avoid;`. However, large table blocks (Statements of Account, Annexure tables, and Activity Monitoring tables) do not enforce orphan prevention rules on table rows.

```css
/* Existing PDF rules in PDFReport.blade.php */
.photo-container { margin-bottom: 20px; page-break-inside: avoid; }
```

#### Visual & Functional Impact
During multi-page PDF exports, table header rows frequently get printed at the very bottom of a page while data rows break onto the next page, creating fragmented PDF documents.

#### Recommended Remediation
Add global table print rules to `PDFReport.blade.php`:
```css
table { page-break-inside: auto; }
tr    { page-break-inside: avoid; page-break-after: auto; }
thead { display: table-header-group; }
tfoot { display: table-footer-group; }
```

---

### CSS-05 — Card Header & Section Typography Misalignment

#### Root Cause Analysis
Headings within annexure cards (`LivelihoodAnnexure.blade.php`, `institutional_ongoing_group.blade.php`, `residential_skill_training.blade.php`, `crisis_intervention_center.blade.php`) have un-reset heading margins.

```css
/* LivelihoodAnnexure.blade.php */
.card-header h4 { margin-bottom: 0; }

/* residential_skill_training.blade.php */
.card-header h4 { margin-bottom: 0; }
```
Other partials omit `.card-header h4 { margin-bottom: 0; }`, resulting in browser-default bottom margins (e.g., 0.5rem) expanding card header heights unevenly.

#### Visual & Functional Impact
Subtle vertical misalignment of card header text heights across different annexure sections.

#### Recommended Remediation
Inject global resets for card headers across all report partials:
```css
.card-header h4, .card-header h5, .card-header h6 {
    margin-bottom: 0 !important;
    line-height: 1.2;
}
```

---

## 4. Implementation Roadmap & Summary

1. **Phase 14.1 (Completed):** Added `<div class="table-responsive">` containers across all 12 Create and Edit SOA partials to resolve horizontal overflow.
2. **Phase 14.2 (Recommended):** Extract hardcoded inline background styles (`#202ba3`) into central CSS utility classes with high-contrast text styling.
3. **Phase 14.3 (Recommended):** Apply `page-break-inside: avoid;` rules to `PDFReport.blade.php` table rows to prevent orphaned headers during PDF generation.
4. **Phase 14.4 (Recommended):** Harmonize badge color tokens (`#0f766e`) across web views and PDF templates.
