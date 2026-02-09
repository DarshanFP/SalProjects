# Phase 3 — Draft-Safe Validation Boundary — Sign-Off

**Date:** 2026-02-08  
**Status:** Complete and locked

---

## Summary of the Problem

Draft submissions for IIES project creation were being blocked by validation and DB NOT NULL constraints. Users saving minimal drafts (e.g., project title only) encountered failures because:
- Required-field validation (e.g. `iies_bname`) ran unconditionally
- Sub-controllers with required fields (e.g. FinancialSupport: `govt_eligible_scholarship`, `other_eligible_scholarship`) were invoked with incomplete data
- DB inserts attempted with null values on NOT NULL columns

---

## Design Decision

**Orchestration-level enforcement** was selected over schema relaxation.

- Draft saves must never be blocked by validation
- Required-field validation is bypassed when `save_as_draft === true`
- Sub-controllers are invoked only when minimum data is present
- DB constraints remain unchanged; no nullable schema

---

## Implementation Confirmation

- Validation bypass for draft: `iies_bname` validation skipped when `$isIiesDraft`
- IIESPersonalInfoController: called only when `$request->filled('iies_bname')`
- IIESFinancialSupportController: called only when `$request->has('govt_eligible_scholarship') && $request->has('other_eligible_scholarship')` or when non-draft
- All changes in `ProjectController.php` IIES branch only
- No schema, migration, or model changes

---

## Verification Confirmation

Verification scenarios documented in `IIES_Create_Flow_Phase_Implementation_Plan.md` § Phase 3 — Verification. Invariants:

- Transactions rollback correctly on non-draft validation failure
- Draft saves never rollback due to validation

---

## Phase 3 is complete and locked
