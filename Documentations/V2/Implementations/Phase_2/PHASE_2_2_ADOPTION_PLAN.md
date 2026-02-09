# Phase 2.2 — ProjectAttachmentHandler Adoption Plan

## 1. Purpose

This plan governs the controlled rollout of ProjectAttachmentHandler after successful IES + IIES pilots. The rollout minimizes blast radius and preserves legacy behavior by:

- Adopting one controller at a time with mandatory verification
- Avoiding request shape changes, frontend changes, or route changes
- Preserving legacy read compatibility throughout
- Stopping immediately on the first observed regression

---

## 2. Entry Criteria (Already Met)

- Design approved
- IES pilot implemented + verified
- IIES pilot implemented + verified
- No behavior regressions observed

---

## 3. Adoption Principles

- One controller at a time
- Mandatory local verification after each adoption
- No request shape changes
- No frontend or route changes
- Legacy read compatibility preserved
- Stop immediately on first regression

---

## 4. Adoption Order (Safest First)

| Order | Controller | Module | Risk Level | Rationale | Status |
| ----- | ---------- | ------ | ---------- | --------- | ------ |
| 1 | IAHAttachmentsController | IAH | Low | Same pattern as IES | ⬜ Pending |
| 2 | ILPAttachmentsController | ILP | Medium | Multiple fields | ⬜ Pending |
| 3 | IGEAttachmentsController | IGE | Medium | Budget adjacency | ⬜ Pending |
| 4 | CCIAttachmentsController | CCI | Medium | NGO docs | ⬜ Pending |
| 5 | RSTAttachmentsController | RST | Medium | Institutional | ⬜ Pending |
| 6 | LDPAttachmentsController | LDP | Medium | Multi-file | ⬜ Pending |
| 7 | EduRUTAttachmentsController | EduRUT | High | Excel + PDFs | ⬜ Pending |
| 8 | CICAttachmentsController | CIC | High | Mixed legacy | ⬜ Pending |

---

## 5. Per-Controller Execution Checklist

Reusable checklist for each adoption:

- [ ] Controller adopted using ProjectAttachmentHandler
- [ ] No legacy behavior removed
- [ ] Create flow verified
- [ ] Edit/view flow verified
- [ ] Update flow verified
- [ ] Validation failure verified (422, no partial storage)
- [ ] Logs reviewed (no new ERRORs)
- [ ] Adoption tracker updated

---

## 6. Explicit Non-Goals

- No attachment schema changes
- No storage migration
- No report/export attachment refactor
- No UI or JS changes
- No FormSection or orchestration changes

---

## 7. Completion Rule

Phase 2.2 is considered complete only when:

- All planned controllers are adopted **AND**
- Local verification is completed for each **AND**
- EXECUTION_CHECKLIST.md is updated **AND**
- PHASE_2_SIGNOFF.md is approved

---

*This is a planning document only. No PHP files, controllers, or code are modified by this plan.*
