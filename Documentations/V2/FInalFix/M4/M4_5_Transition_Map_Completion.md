# M4.5 — Transition Map Completion

**Milestone:** M4 — Workflow & State Machine Hardening  
**Phase:** M4.5 — Transition Map Completion (Align canTransition() With Actual Runtime)  
**Date:** 2026-02-15

---

## Objective

Update `canTransition()` in `ProjectStatusService` so the map fully represents all valid runtime transitions identified in the M4.4 audit. **Alignment only** — no enforcement, no behavior change.

---

## 1) Missing Transitions Identified (from M4.4)

| Category | From | To | Roles |
|----------|------|-----|--------|
| approveAsProvincial | reverted_by_provincial | forwarded_to_coordinator | general |
| approveAsProvincial | reverted_by_general_as_provincial | forwarded_to_coordinator | general |
| approveAsProvincial | reverted_to_executor | forwarded_to_coordinator | general |
| approveAsProvincial | reverted_to_applicant | forwarded_to_coordinator | general |
| approveAsProvincial | reverted_to_provincial | forwarded_to_coordinator | general |
| forwardToCoordinator | reverted_by_coordinator | forwarded_to_coordinator | provincial, general |
| forwardToCoordinator | reverted_by_general_as_coordinator | forwarded_to_coordinator | provincial, general |
| forwardToCoordinator | reverted_to_provincial | forwarded_to_coordinator | provincial, general |
| forwardToCoordinator | reverted_to_coordinator | forwarded_to_coordinator | provincial, general |
| revertByProvincial | forwarded_to_coordinator | reverted_by_provincial | provincial, general |
| revertByProvincial | forwarded_to_coordinator | reverted_by_general_as_provincial | general |
| revertByProvincial | forwarded_to_coordinator | reverted_to_executor, reverted_to_applicant | general |
| revertByProvincial | reverted_by_coordinator | reverted_by_provincial, reverted_by_general_as_provincial, reverted_to_* | provincial, general |
| revertToLevel | reverted_by_provincial | reverted_to_executor, reverted_to_applicant | general |
| revertToLevel | reverted_by_general_as_provincial | reverted_to_executor, reverted_to_applicant | general |
| revertToLevel | reverted_by_coordinator | reverted_to_provincial | general |
| revertToLevel | reverted_by_general_as_coordinator | reverted_to_provincial | general |
| Rollback | approved_by_general_as_coordinator | forwarded_to_coordinator | general |
| revertAsProvincial | submitted_to_provincial | reverted_to_provincial | general |

---

## 2) Transitions Added

All of the above were added to the `$transitions` array in `canTransition()`:

- **REVERTED_BY_PROVINCIAL:** FORWARDED_TO_COORDINATOR [general], REVERTED_TO_EXECUTOR [general], REVERTED_TO_APPLICANT [general].
- **REVERTED_BY_COORDINATOR:** FORWARDED_TO_COORDINATOR [provincial, general], REVERTED_BY_PROVINCIAL [provincial, general], REVERTED_BY_GENERAL_AS_PROVINCIAL [general], REVERTED_TO_EXECUTOR/APPLICANT/PROVINCIAL [general].
- **REVERTED_BY_GENERAL_AS_PROVINCIAL:** FORWARDED_TO_COORDINATOR [general], REVERTED_TO_EXECUTOR/APPLICANT [general].
- **REVERTED_BY_GENERAL_AS_COORDINATOR:** FORWARDED_TO_COORDINATOR [provincial, general], REVERTED_TO_PROVINCIAL [general].
- **REVERTED_TO_EXECUTOR / REVERTED_TO_APPLICANT / REVERTED_TO_PROVINCIAL / REVERTED_TO_COORDINATOR:** FORWARDED_TO_COORDINATOR [general] or [provincial, general] as appropriate.
- **SUBMITTED_TO_PROVINCIAL:** REVERTED_TO_PROVINCIAL [general].
- **FORWARDED_TO_COORDINATOR:** REVERTED_BY_PROVINCIAL [provincial, general], REVERTED_BY_GENERAL_AS_PROVINCIAL [general], REVERTED_TO_EXECUTOR/APPLICANT [general].
- **APPROVED_BY_GENERAL_AS_COORDINATOR:** FORWARDED_TO_COORDINATOR [general] (rollback).

No existing entries were removed or altered except by adding new To-status keys where needed.

---

## 3) Rollback Path Documented

- **Transition:** `approved_by_general_as_coordinator` → `forwarded_to_coordinator`
- **Role:** `general` only
- **Use:** Budget validation failure after General approves as coordinator; controller rolls back status to FORWARDED so the project can be corrected and re-approved.
- **Location in map:** Under `ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR`, entry `ProjectStatus::FORWARDED_TO_COORDINATOR => ['general']` with comment `// rollback on budget validation failure`.

---

## 4) Before / After Map Size

| Metric | Before (M4.4) | After (M4.5) |
|--------|----------------|--------------|
| From-status keys | 12 | 12 |
| Total From→To pairs | 27 | 52 |
| New pairs added | — | 25 |

(Counts exclude duplicate role entries; each From→To pair counted once.)

---

## 5) Risk Assessment

- **Behavior:** No change. Only the `$transitions` array inside `canTransition()` was expanded; no callers enforce it yet, and no service/controller logic was modified.
- **Regression:** Low — existing transitions unchanged; new entries mirror existing service allowed-status logic.
- **Enforcement:** Still **OFF**. No code calls `canTransition()`; enabling enforcement would require a separate change and testing.

---

## 6) Confirmation: Enforcement Still OFF

- `canTransition()` is **not** called from any controller, request, or service.
- No new validation or guards were added.
- Transitions remain driven solely by existing `ProjectStatusService` methods and controller checks; the map is documentation and future-enforcement-ready only.

---

## Files Modified

| File | Change |
|------|--------|
| `app/Services/ProjectStatusService.php` | Expanded `$transitions` in `canTransition()` (lines ~701–791) with all M4.4-identified transitions; added inline comments for approveAsProvincial, forwardToCoordinator, revertByProvincial, revertToLevel, rollback. |

No other files modified (no controllers, no financial/revert/approve logic, no resolver, no constants, no DB).

---

**M4.5 Complete — Transition Map Fully Aligned (No Enforcement Yet)**
