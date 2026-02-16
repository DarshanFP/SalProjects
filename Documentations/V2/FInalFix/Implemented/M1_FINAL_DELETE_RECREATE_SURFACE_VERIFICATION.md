# M1 — Delete-Recreate Surface Verification (Projects Controllers)

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Scope:** `app/Http/Controllers/Projects` (read-only audit). **No code modified.**

---

## 1. Executive Summary

All controller files under `app/Http/Controllers/Projects` were scanned for delete patterns (`->delete()`, `::where(...)->delete()`, `expenseDetails()->delete()`, etc.) and for M1 skip-empty guards. Controllers that perform **delete-then-recreate** in `store()` or `update()` (section replace) were classified as **SAFE** (guard present before delete), **PARTIALLY SAFE**, or **UNSAFE** (no guard; empty payload can wipe data). This document lists only those that implement a section-replace pattern (delete all rows for project then recreate from request). Controllers that only delete in `destroy()` or use single-row updateOrCreate/firstOrNew are not counted as delete-then-recreate for this audit.

---

## 2. Totals

| Metric | Count |
|--------|--------|
| **Total controller files scanned** | 61 |
| **Delete-then-recreate controllers (store/update)** | 26 |
| **SAFE** (guard present, before delete) | 18 |
| **PARTIALLY SAFE** | 0 |
| **UNSAFE** (no guard) | 8 |

*(Attachment/document controllers that skip when “no files” are present are counted as SAFE for their pattern; they are listed in the table with a note.)*

---

## 3. Delete-Recreate Controllers Table

| Controller | Pattern Type | Guard Present | Guard Placement | Status | Notes |
|------------|--------------|---------------|-----------------|--------|--------|
| EduRUTTargetGroupController | Multi-row (update only) | Yes | Before transaction (update()) | SAFE | update() has guard; store() append-only |
| IGE/IGEBudgetController | Multi-row | Yes | Before transaction (store()) | SAFE | Budget lock then guard |
| IGE/IGEBeneficiariesSupportedController | Multi-row | Yes | Before transaction (store()) | SAFE | update() delegates to store() |
| IGE/OngoingBeneficiariesController | Multi-row | Yes | Before transaction (store()) | SAFE | update() delegates to store() |
| IGE/NewBeneficiariesController | Multi-row | Yes | Before transaction (store()) | SAFE | update() delegates to store(); transactionLevel check |
| IES/IESFamilyWorkingMembersController | Multi-row | Yes | Before transaction (store()) | SAFE | update() delegates to store() |
| LDP/TargetGroupController | Multi-row | Yes | Before transaction (store()) | SAFE | update() delegates to store() |
| RST/GeographicalAreaController | Multi-row | Yes | Before transaction (store()) | SAFE | update() delegates to store() |
| RST/TargetGroupAnnexureController | Multi-row | Yes | Before transaction (store()) | SAFE | update() delegates to store() |
| IES/IESExpensesController | Nested (parent+children) | Yes | Before transaction (store()) | SAFE | expenseDetails()->delete() + parent delete |
| IIES/IIESExpensesController | Nested (parent+children) | Yes | Before delete (store()) | SAFE | No transaction in store(); guard before delete |
| LogicalFrameworkController | Nested (objectives+results+risks+activities) | Yes | Before DB::transaction (update()) | SAFE | ProjectObjective::where()->delete() then recreate |
| RST/BeneficiariesAreaController | Multi-row | Yes | Before transaction (store()) | SAFE | update() delegates to store() |
| BudgetController (Projects) | Phase budget rows | Yes | Before delete (update()) | SAFE | ProjectBudget::where()->delete() then create |
| IES/IESAttachmentsController | File/attachment replace | Yes (no files skip) | Before mutation (store/update) | SAFE | File-based; skip when no files |
| IIES/IIESAttachmentsController | File/attachment replace | Yes (no files skip) | Before mutation (update) | SAFE | File-based; skip when no files |
| IAH/IAHDocumentsController | File/attachment replace | Yes (no files skip) | Before mutation (store/update) | SAFE | File-based; skip when no files |
| ILP/AttachedDocumentsController | File/attachment replace | Yes (no files skip) | Before mutation (store/update) | SAFE | File-based; skip when no files |
| IIES/IIESFamilyWorkingMembersController | Multi-row | No | N/A | UNSAFE | store() and update() both delete+recreate; no transaction in store() |
| EduRUTAnnexedTargetGroupController | Multi-row (update only) | No | N/A | UNSAFE | update() delete+recreate; store() append-only |
| ILP/StrengthWeaknessController | Single-row replace | No | N/A | UNSAFE | store() delete then create one row (json); empty wipes |
| IAH/IAHEarningMembersController | Multi-row | No | N/A | UNSAFE | store() delete then loop create; update() delegates to store() |
| ILP/BudgetController | Multi-row | No | N/A | UNSAFE | store() delete then loop create; budget lock only |
| IAH/IAHBudgetDetailsController | Multi-row | No | N/A | UNSAFE | store() delete then loop create; budget lock only |
| ILP/RevenueGoalsController | Multi-table (update) | No | N/A | UNSAFE | update() deletes 3 tables then create loops; store() append-only |
| ILP/RiskAnalysisController | Single-row replace | No | N/A | UNSAFE | store() delete then create one row; empty $data wipes |

---

## 4. Notes

- **Guard placement:** “Before transaction” means the guard runs before `DB::beginTransaction()` (or before the delete inside the transaction), so when the guard skips, no transaction is started and no delete runs.
- **update() delegates to store():** Where applicable, if update() only calls store(), the guard in store() protects both paths.
- **destroy():** Controllers that only delete in `destroy()` (explicit delete action) are not classified as delete-then-recreate for section replace; they are out of scope for this table.
- **Single-row replace:** ILP StrengthWeaknessController and ILP RiskAnalysisController delete then create a single row; empty input still causes wipe and replace with empty. Classified UNSAFE.
- **ILP RevenueGoalsController:** store() does not delete (append-only); update() performs the delete-then-recreate. No guard in update().

---

## 5. Confirmation

No code was modified during this verification. No guards were added. No refactoring, validation changes, or logic changes were made. Only scanning, analysis, and documentation were performed.

---

DELETE-RECREATE SURFACE VERIFICATION COMPLETE — NO CODE MODIFIED
