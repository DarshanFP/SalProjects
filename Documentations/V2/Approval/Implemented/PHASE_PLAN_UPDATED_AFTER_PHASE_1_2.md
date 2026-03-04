# Implementation Plan Update Summary

**Date:** March 2, 2026  
**Trigger:** Phase 1.2 Real Approval Flow Tests  
**File Updated:** `Documentations/V2/Approval/PHASE_WISE_IMPLEMENTATION_PLAN.md`  
**Version:** 1.0 → 1.1

---

## 1. Why Update Was Needed

Phase 1.2 introduced real approval flow integration tests against the development database. Those tests revealed that the approval workflow is **broken** due to a double-save bug that interacts with Wave 6D protection (added Feb 18, 2026).

The original Phase 2 was scoped as a single block for financial invariant enforcement. Given the new findings, Phase 2 needed to be split so that:

1. **The production bug** (403 on approval, incomplete budget data) is fixed first.
2. **Financial invariant enforcement** follows only after the approval flow works correctly.

---

## 2. New Phase Structure

### Phase 2A – Atomic Approval Refactor (Production Bug Fix)

- **Objective:** Fix double-save bug via single atomic save.
- **Scope:** Refactor `CoordinatorController::approveProject()` and `ProjectStatusService::approve()` to use a single save.
- **Estimated Effort:** 1–2 days.
- **Status:** **Next** (after Phase 1.2 completion).

### Phase 2B – Financial Invariant Enforcement

- **Objective:** Block approval of projects with invalid financial state.
- **Scope:** Enforce `opening_balance > 0`, `amount_sanctioned > 0`, and related rules.
- **Dependencies:** Requires Phase 2A completion.
- **Estimated Effort:** 2–3 days.
- **Status:** Pending.

---

## 3. Production Bug Identified

| Aspect | Detail |
|--------|--------|
| **Cause** | Wave 6D blocks updates when project is in FINAL status. Approval performs two saves: first changes status (FINAL), second update is blocked. |
| **Symptom** | 403 Forbidden returned to user during approval. |
| **Data Impact** | Status changes to `approved_by_coordinator`; budget fields not updated. |
| **Other Impact** | Notifications not sent; cache not invalidated; user sees error page. |
| **Location** | `CoordinatorController::approveProject()` (line ~1139) and `Project::booted()` updating event. |

---

## 4. Updated Execution Order

| Order | Phase | Status |
|-------|-------|--------|
| 1 | Phase 0 – Division Safety | ✅ Complete |
| 2 | Phase 1 – Testing Foundation | ✅ Complete |
| 3 | Phase 1.1 – Architectural Snapshot | ✅ Complete |
| 4 | Phase 1.2 – Real Approval Locking | ✅ Complete |
| **5** | **Phase 2A – Atomic Approval Fix** | **🔜 Next** |
| 6 | Phase 2B – Financial Invariants | Pending |
| 7 | Phase 3 – Redirect Standardization | Pending |
| 8 | Phase 4 – Database Hardening | Pending |

---

## 5. Ready for Phase 2A Confirmation

- [x] Production bug documented in implementation plan.
- [x] Phase 2 split into 2A (bug fix) and 2B (invariants).
- [x] Roadmap table updated.
- [x] Risk matrix updated with double-save and financial invariant risks.
- [x] Execution order clarified.
- [x] Phase 2A scope and success criteria defined.

**Recommendation:** Proceed with Phase 2A implementation to restore the approval workflow before adding financial invariant enforcement in Phase 2B.
