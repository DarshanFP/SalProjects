# Phase 12.7 Implementation: LDP Annexure Record Pruning (M8)

**Date:** 2026-06-27  
**Goal:** Fix medium discrepancy M8 where `LivelihoodAnnexureController` failed to prune existing records when updating a report, leaving renamed or deleted beneficiary entries as permanent orphan rows in the database.

---

## Root Cause Analysis

When saving LDP annexures in `LivelihoodAnnexureController@handleLivelihoodAnnexure`, the code used `QRDLAnnexure::updateOrCreate(['report_id' => $report_id, 'dla_beneficiary_name' => $beneficiaryName], ...)`.

Matching on `dla_beneficiary_name` caused two issues during report edits:
1. If an executor renamed a beneficiary, the old name remained in `rqdl_annexures` while a new row was inserted.
2. If an executor removed a beneficiary row, the deleted row was never pruned.

This differed from `InstitutionalOngoingGroupController` and `ResidentialSkillTrainingController`, which both explicitly execute `delete()` for the report ID prior to re-inserting submitted records.

---

## Changes Made

### [`app/Http/Controllers/Reports/Monthly/LivelihoodAnnexureController.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/app/Http/Controllers/Reports/Monthly/LivelihoodAnnexureController.php)
- Added `QRDLAnnexure::where('report_id', $report_id)->delete();` before iterating through submitted beneficiary data.

---

## Verification

1. **Beneficiary Deletion / Renaming Test:** Edited an LDP report with existing annexure rows, renamed one beneficiary, and removed another. Verified that `rqdl_annexures` contains exactly the newly submitted set without duplicate or orphan rows.
