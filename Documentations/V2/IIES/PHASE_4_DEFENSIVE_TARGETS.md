# Phase 4 — Defensive Persistence Targets

**Date:** 2026-02-08  
**Type:** Analysis only — no code changes

---

## IIES Persistence Points — Defensive Guard Analysis

| Controller | File | Method | Minimum required field(s) | Current behavior if missing | Risk level |
|------------|------|--------|---------------------------|-----------------------------|------------|
| IIESPersonalInfoController | `IIESPersonalInfoController.php` | store | iies_bname | `mapRequestToModel` sets null; `save()` triggers SQLSTATE[23000] NOT NULL violation | High |
| IIESPersonalInfoController | `IIESPersonalInfoController.php` | update | iies_bname | Same as store; no orchestration guard on edit flow | High |
| IIESFamilyWorkingMembersController | `IIESFamilyWorkingMembersController.php` | store | iies_member_name, iies_work_nature, iies_monthly_income (per row) | Only creates rows when all three present and non-empty; empty arrays yield no inserts | Low |
| IIESFamilyWorkingMembersController | `IIESFamilyWorkingMembersController.php` | update | iies_member_name, iies_work_nature, iies_monthly_income (per row) | Same as store; row creation guarded | Low |
| IIESImmediateFamilyDetailsController | `IIESImmediateFamilyDetailsController.php` | store | None (all nullable or default) | Booleans default to 0; text fields null; schema accepts | Low |
| IIESImmediateFamilyDetailsController | `IIESImmediateFamilyDetailsController.php` | update | None (all nullable or default) | Same as store | Low |
| IIESEducationBackgroundController | `EducationBackgroundController.php` | store | None (all nullable) | FormDataExtractor fills; nullable columns accept null | Low |
| IIESEducationBackgroundController | `EducationBackgroundController.php` | update | None (all nullable) | Delegates to store | Low |
| IIESAttachmentsController | `IIESAttachmentsController.php` | store | Files (for meaningful persistence) | ProjectAttachmentHandler does `updateOrCreate` with empty array; creates/updates attachment record even when no files | Medium |
| FinancialSupportController | `FinancialSupportController.php` | store | govt_eligible_scholarship, other_eligible_scholarship | Validation fails before persistence; orchestration skips when draft and keys absent | Medium |
| IIESExpensesController | `IIESExpensesController.php` | store | iies_total_expenses, etc. (main); iies_particular, iies_amount (per detail row) | Main record uses ?? 0; detail rows only created when particular and amount present | Low |

---

## Schema Reference (NOT NULL columns)

| Table | NOT NULL columns |
|-------|------------------|
| project_IIES_personal_info | project_id, IIES_personal_id, iies_bname |
| project_IIES_family_working_members | project_id, IIES_family_member_id, iies_member_name, iies_work_nature, iies_monthly_income |
| project_IIES_immediate_family_details | project_id, IIES_family_detail_id (others nullable or default) |
| project_IIES_education_background | project_id, IIES_education_id (others nullable) |
| project_IIES_attachments | project_id, IIES_attachment_id (file fields nullable) |
| project_IIES_scope_financial_support | project_id, IIES_fin_sup_id (others nullable or default) |
| project_IIES_expenses | project_id, IIES_expense_id, decimal columns (default 0) |
| project_IIES_expense_details | IIES_expense_id, iies_particular, iies_amount |
