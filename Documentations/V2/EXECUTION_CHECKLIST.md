# Execution Checklist — Fix-First, Single-Deploy

## How to Use This Document
- This checklist is updated continuously
- No phase may advance unless all items in the previous phase are checked
- Deployment occurs ONLY after all phases are complete and signed off

---

## Phase 0 — Stabilization

- [x] All Phase 0 fixes implemented (0.1, 0.5, 0.3, 0.2, 0.4)
- [x] Phase 0 implementation MDs created
- [x] Local verification completed
- [x] Phase 0 implicitly accepted for Fix-First flow (Production verification deferred to final deploy)

---

## Phase 1A — Input & Authority Hardening

**Local verification scope**: See `Implementations/Phase_1A/README.md` → "Local Verification Scope (Phase 1A)" for definition, flows to test, and PASS/FAIL criteria.

### Phase 1A.1 — Pattern Establishment
- [x] Phase_1A_Refactor_Playbook.md approved
- [x] PATTERN_LOCK.md finalized
- [x] Golden Template identified (IESPersonalInfoController.md)
- [x] Attachment-only exception documented (IESAttachmentsController.md)

### Phase 1A.2 — Pilot Controllers
- [x] IESPersonalInfoController refactored
- [x] Pilot verified locally
- [x] Pattern validated

### Phase 1A.3 — IES + IIES Completion
- [x] All IES controllers refactored (6)
- [x] All IIES controllers refactored (2)
- [x] All Phase 1A implementation MDs created
- [x] Local verification completed
- [x] PHASE_1A_3_SIGNOFF.md approved

### Phase 1A.4 — Remaining Modules
- [x] All remaining Phase 1A controllers refactored (38: IAH, ILP, IGE, CCI, RST, LDP, EduRUT, CIC)
- [x] Inventory updated to 100% Completed (46/46)
- [x] Local verification completed

### Phase 1A — Final Closure
- [x] PHASE_1A_FINAL_SIGNOFF.md approved

---

## Phase 1B — Structural Cleanup

- [x] Phase 1B scope confirmed
- [x] Phase 1A Final Sign-off exists
- [x] Each Phase 1B item has an implementation MD
- [x] Local verification completed
- [x] PHASE_1B_SIGNOFF.md approved

---

## Phase 2 — Architectural Prevention

### Phase 2.1 — FormDataExtractor
- [x] Design approved
- [x] Implementation completed
- [x] Adoption completed (all eligible controllers)
- [x] Local verification completed

### Phase 2.2 — ProjectAttachmentHandler
- [x] Phase 2.2 ProjectAttachmentHandler pilot completed
- [x] Phase 2.2 local verification completed (IES, IIES, IAH, ILP)
- [x] Phase 2.2 fully adopted across eligible modules
- [x] Phase 2.2 scope confirmed complete

### Phase 2.3 — BoundedNumericService (Pilot)
- [x] Phase 2.3 pilot implemented (BudgetController)
- [x] Phase 2.3 local verification completed
- [x] Phase 2.3 fully rolled out (no remaining decimal-bound drift)

- [x] Phase 2 entry requirements met
- [x] Phase 1B Sign-off exists
- [x] Architectural components designed
- [x] Phase 2 implementation MDs created
- [x] Local verification completed (Phase 2.1 only)
- [x] Phase 2.4 — GeneralInfo Address Ownership implemented (Phase_2_4_GeneralInfo_Address_Ownership.md)
- [ ] PHASE_2_SIGNOFF.md approved

---

### IIES Remediation

- [x] Phase 0 — Emergency Correctness Fixes implemented and verified
- [x] Phase 1 — Validation Boundary Hardening (IIES Create Flow) — implemented
- [x] Phase 1 — Validation Boundary Hardening — locally verified
- [x] Phase 1 — Validation Boundary Hardening — locked (documentation complete)
- [x] Phase 1 — Transaction Boundary Normalization — implemented and verified

### Phase 3 — Draft Semantics

- [x] Phase 3 — Draft semantics defined
- [x] Phase 3 — Draft semantics implemented
- [x] Phase 3 — Draft behavior verified
- [x] Phase 3 — Signed off

### Phase 4 — Defensive Persistence

- [x] Phase 4 — Defensive persistence implemented
- [x] Phase 4 — Verified
- [x] Phase 4 — Signed off

### Phase 5 — Orchestration Simplification

- [x] Phase 5 — Orchestration simplification completed
- [x] Phase 5 — Verified
- [x] Phase 5 — Signed off

---

## Final Deployment Readiness

- [ ] All phases completed
- [ ] All sign-off MDs approved
- [ ] No unresolved high-risk items
- [ ] Final local regression test completed
- [ ] Deployment window approved

---

## Final Deployment & Verification

- [ ] Deployed to production
- [ ] Production smoke tests passed
- [ ] Production logs clean
- [ ] Refactor program formally closed
