# Completed Work Index (Old Projects Funds)

## Purpose

This folder documents the **completed implementation work** for Old Projects budget fields and related stability fixes (type-hint mismatches, data-loss fixes, and PDF updates).

Use this as a quick entry point to understand **what changed**, **why it changed**, and **where in the codebase it lives**.

---

## What we completed (high level)

- **Budget fields made functional end-to-end**
  - Added UI inputs for **Amount Forwarded (Existing Funds)** and **Local Contribution**
  - Implemented **Amount Sanctioned** + **Opening Balance** calculations (JS previews + coordinator approval persistence)
  - Added server-side validation for the new field rules
  - Updated Show + PDF views to display the new fields and updated labels

- **Stability fixes for project update flow**
  - Fixed repeated `TypeError` issues caused by strict controller method signatures when called by the orchestrator controller
  - Fixed **data loss** for JS-generated / dynamic nested fields by ensuring controllers capture full request payload

- **PDF download audit + alignment**
  - Confirmed all download routes point to `ExportController@downloadPdf`
  - Ensured PDFs render budget + general info consistently (including `local_contribution`)
  - Ensured budget summary cards **don’t render dark-blue backgrounds** in PDFs

---

## Detailed docs in this folder

- **Budget field implementation**
  - `01_Budget_Fields_End_To_End_Changes.md`

- **Controller TypeHint + data-loss fixes**
  - `02_Controller_TypeHint_And_DataLoss_Fixes.md`

- **PDF download audit and fixes**
  - `03_PDF_Download_Audit_And_Fixes.md`

---

## Where the deeper audit reports live (reference)

Some work (especially the long lists of controllers fixed) is tracked in the REVIEW folder:

- `Documentations/REVIEW/3rd Review/TypeHint_Mismatch_Audit.md`
- `Documentations/REVIEW/3rd Review/Data_Loss_Fix_Final_Report.md`
- `Documentations/REVIEW/2nd Review/Final_Review_Discrepancies.md`


