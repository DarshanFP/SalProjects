# Phase 14.5 Implementation: CSS-04 PDF Page-Break Protection & Orphan Prevention

**Date:** 2026-06-28  
**Goal:** Prevent orphaned table headers, fragmented data rows, and awkward section splits during multi-page PDF exports.

---

## 1. Problem Description & Root Cause

When generating multi-page PDF documents (`PDFReport.blade.php` and `pdf.blade.php`), large tables (Statements of Account, Activity Monitoring, and Annexures) lacked explicit print page-break constraints on table rows (`tr`).
Consequently:
- Table headers frequently appeared isolated at the very bottom margin of a page.
- Individual data rows would split across page boundaries, severing text lines vertically.

---

## 2. Changes Implemented

### Stylesheet Rules (`PDFReport.blade.php` & `pdf.blade.php`)
Injected strict page-break control rules into both PDF export master templates:
```css
.info-table, .details-table, .activities-table, .account-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    page-break-inside: auto;
}
tr {
    page-break-inside: avoid;
    page-break-after: auto;
}
thead {
    display: table-header-group;
}
tfoot {
    display: table-footer-group;
}
.section-header {
    page-break-after: avoid;
}
```

---

## 3. Verification

1. **Orphan Header Elimination:** `thead { display: table-header-group; }` ensures table headers repeat gracefully at the top of subsequent pages if a table spans multiple pages.
2. **Clean Row Splits:** `tr { page-break-inside: avoid; }` guarantees that table rows remain intact on a single page, eliminating text splitting during export.
