# IIES Phase 0 — Verification and Lock

**Document:** Phase 0 Formal Verification  
**Date:** 2026-02-08  
**Status:** Verified and Locked

---

## 1. Purpose of Phase 0

Phase 0 Emergency Correctness Fixes address structural violations in the IIES project creation flow:

- **Exception swallowing:** Sub-controllers previously caught exceptions and returned HTTP responses; the orchestrator never saw failures.
- **Partial persistence:** Failures in some sub-controllers allowed the outer transaction to commit, leaving orphaned or incomplete records.
- **Nested transactions:** Sub-controllers used their own transactions, causing savepoint rollbacks instead of full outer rollback.
- **Validation gap:** Required IIES fields (e.g. `iies_bname`) were not enforced at orchestration level, leading to integrity constraint violations.

Phase 0 restores transactional correctness and atomicity for IIES create.

---

## 2. Controllers Modified

| Controller | File | Phase 0 Changes |
|------------|------|-----------------|
| IIESPersonalInfoController | `app/Http/Controllers/Projects/IIES/IIESPersonalInfoController.php` | Removed nested transaction; catch re-throws |
| IIESImmediateFamilyDetailsController | `app/Http/Controllers/Projects/IIES/IIESImmediateFamilyDetailsController.php` | Same |
| EducationBackgroundController | `app/Http/Controllers/Projects/IIES/EducationBackgroundController.php` | Same |
| IIESAttachmentsController | `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php` | Same; explicit failures throw exceptions |
| ProjectController | `app/Http/Controllers/Projects/ProjectController.php` | Added `iies_bname` validation when project type is IIES |

---

## 3. Failure Scenarios Tested

### 3.1 Missing iies_bname

**Scenario:** Submit IIES create form with beneficiary name (iies_bname) empty.

**Expected:** Validation fails before any IIES sub-controller runs. No project row inserted. No `Column 'iies_bname' cannot be null` error from database. User receives validation error.

**Observed:** Validation triggers at orchestration level. Request does not reach IIESPersonalInfoController. No project row in `projects` table. No integrity constraint violation in logs.

### 3.2 Forced Sub-Controller Failure

**Scenario:** Simulate a failure in an IIES sub-controller (e.g. database error, or constraint violation in a downstream section).

**Expected:** Exception propagates to ProjectController. Orchestrator calls `DB::rollBack()`. No project row persisted. User redirected with error message.

**Observed:** Exception propagates. Orchestrator catches, rolls back, redirects. No project row in `projects` table. Logs show rollback and error.

### 3.3 Happy Path

**Scenario:** Submit valid IIES create form with all required fields populated.

**Expected:** All sub-controllers complete successfully. Orchestrator commits. Project row and all IIES-related rows visible in database. User redirected with success message.

**Observed:** Full flow completes. Project row and related IIES records present in database. Success redirect returned.

---

## 4. Expected vs Observed Behavior

| Scenario | Expected | Observed |
|----------|----------|----------|
| Missing iies_bname | Validation error; no persistence | PASS |
| Sub-controller failure | Full rollback; no project row | PASS |
| Happy path | Full commit; all records persisted | PASS |

---

## 5. Partial Saves No Longer Occur

**Confirmation:** Partial persistence is no longer possible.

- Before Phase 0: A failure in IIESPersonalInfoController, IIESImmediateFamilyDetailsController, EducationBackgroundController, or IIESAttachmentsController would be caught and ignored; the orchestrator would continue and commit. Project + key info + some IIES sections would persist while others would not.

- After Phase 0: Any failure in an IIES sub-controller propagates to the orchestrator. The orchestrator rolls back the outer transaction. The only outcomes are full success (all records committed) or full rollback (no records committed).

---

## 6. Rollback Is Orchestrator-Owned

**Confirmation:** The orchestrator (ProjectController) is the sole owner of transaction boundaries.

- IIES sub-controllers no longer call `DB::beginTransaction()`, `DB::commit()`, or `DB::rollBack()` in their store methods.
- Only ProjectController executes `DB::beginTransaction()` at entry and `DB::commit()` or `DB::rollBack()` on success or failure.
- Sub-controllers participate within the orchestrator-owned transaction; they do not manage transactions.

---

## 7. Phase 0 Lock

Phase 0 is formally verified and locked. No further changes to Phase 0 scope are permitted without a new remediation plan.

---

**End of Document**
