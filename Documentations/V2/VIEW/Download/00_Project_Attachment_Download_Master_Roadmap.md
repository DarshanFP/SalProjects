# Project Attachment Download — Master Roadmap

**Rule: Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.**

---

**Date:** 2026-02-23  
**Scope:** Project attachments only (DP, IES, IIES, IAH, ILP). Reports are OUT OF SCOPE.  
**Purpose:** Phase-wise plan to ensure Provincial and Coordinator can download project attachments across all project types.

---

## 1. Executive Summary

This roadmap defines a structured, phase-wise implementation plan to ensure:

- **Provincial** users can download project attachments for projects where owner OR in_charge is in their scope (all project types).
- **Coordinator** users can download all project attachments (global read-only oversight, no province restriction).

Implementation follows a guard-chain architecture: Route → Controller → ProjectPermissionHelper → ProjectAccessService. Each phase verifies or aligns one layer without breaking downstream phases.

---

## 2. Scope Definition

| In Scope | Out of Scope |
|----------|--------------|
| DP / Common project attachments | Report attachments (`ReportAttachmentController`) |
| IES attachments | Monthly report logic |
| IIES attachments | Report download routes |
| IAH documents | Report view/report show |
| ILP documents | Report permission helpers |
| View/Download for project attachments | |
| Destroy (delete) — verify restrictions | |

**Controllers in scope:**  
AttachmentController, IESAttachmentsController, IIESAttachmentsController, IAHDocumentsController, ILPAttachedDocumentsController

---

## 3. Current Architecture Overview

```
Request: GET /projects/attachments/download/{id}
    │
    ├── Route middleware: auth, role:executor,applicant,provincial,coordinator,general,admin
    │
    └── Controller (e.g. AttachmentController@downloadAttachment)
            ├── Resolve project from attachment
            ├── ProjectPermissionHelper::passesProvinceCheck(project, user)
            └── ProjectPermissionHelper::canView(project, user)
                    └── ProjectAccessService::canViewProject(project, user)
                            ├── Coordinator: return true (global)
                            ├── Provincial: owner OR in_charge in getAccessibleUserIds
                            └── Executor/Applicant: owner OR in_charge
```

---

## 4. Target Access Model

| Role | Project Attachment Download | Condition |
|------|-----------------------------|-----------|
| **Executor** | ✅ Own or in-charge projects | Unchanged |
| **Applicant** | ✅ Own or in-charge projects | Unchanged |
| **Provincial** | ✅ Owner OR in_charge in scope | Province must match |
| **Coordinator** | ✅ All projects | No province restriction |
| **General** | ✅ All projects | Same as coordinator |
| **Admin** | ✅ All projects | Same as coordinator |

---

## 5. Role Matrix

| Role | passesProvinceCheck | canViewProject | Route Access |
|------|---------------------|----------------|--------------|
| Executor | Province match (if applicable) | Owner OR in_charge | Yes |
| Applicant | Same | Owner OR in_charge | Yes |
| Provincial | province_id match | Owner OR in_charge in scope | Yes |
| Coordinator | Bypass (return true) | Bypass (return true) | Yes |
| General | Bypass (province_id null) | Bypass | Yes |
| Admin | Bypass | Bypass | Yes |

---

## 6. Guard Chain Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│ ROUTE LAYER                                                      │
│ auth, role:executor,applicant,provincial,coordinator,general,admin│
└─────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────┐
│ CONTROLLER LAYER                                                  │
│ 1. Resolve project from attachment/file                          │
│ 2. passesProvinceCheck(project, user)                            │
│ 3. canView(project, user)                                        │
└─────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────┐
│ PROJECTPERMISSIONHELPER                                          │
│ passesProvinceCheck: admin/coordinator bypass; provincial match  │
│ canView: delegates to ProjectAccessService::canViewProject       │
└─────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────┐
│ PROJECTACCESSSERVICE                                             │
│ canViewProject: admin/coordinator/general true;                  │
│ provincial: owner OR in_charge in getAccessibleUserIds;          │
│ executor/applicant: owner OR in_charge                           │
└─────────────────────────────────────────────────────────────────┘
```

---

## 7. Risk Areas

| Risk | Mitigation |
|------|------------|
| Route nested in executor group | Phase A: audit route structure |
| Controller missing passesProvinceCheck/canView | Phase B: align all 5 controllers |
| Coordinator blocked by province | Phase C: verify bypass in ProjectPermissionHelper |
| Provincial in_charge excluded | Phase D: verify getAccessibleUserIds + owner \|\| in_charge |
| View uses executor-only routes | Phase E: verify shared route names |
| Regression on executor flow | Phase F: regression test suite |

---

## 8. Phase Execution Order

| Phase | Focus | Dependency |
|-------|-------|------------|
| **A** | Route Middleware Verification | None |
| **B** | Controller Guard Alignment | A + **C + D confirmed stable** |
| **C** | ProjectPermissionHelper Alignment | None |
| **D** | ProjectAccessService Validation | C |
| **E** | View Layer Verification | B |
| **F** | Testing & Regression Shield | A–E complete |

**Recommended order:** **A → C → D → B → E → F**

**Critical rule:** Phase B (Controller Guard Alignment) must **never** run before Phases C and D are confirmed stable. Controllers depend on correct helper and service behavior; aligning controllers first risks enforcing incorrect logic.

---

## 9. Test Strategy Overview

- **Unit:** ProjectPermissionHelper, ProjectAccessService (if not already covered)
- **Feature:** Provincial download (owner, in_charge, outside province); Coordinator download (all types); Executor unchanged
- **Integration:** Full guard chain via HTTP request
- **Regression:** Executor and Applicant flows must remain unchanged

---

## 10. Documentation Discipline Rule

**Every implementation step must generate or update a corresponding MD file in this same folder documenting:**

- Changes made
- Files touched
- Test results
- Sign-off status

Phase completion files: `Phase_A_Implementation_Summary.md`, `Phase_B_Implementation_Summary.md`, etc.

---

## 11. Execution Strategy

Execute in controlled batches. Do **not** implement all phases at once:

| Batch | Phases | Action |
|-------|--------|--------|
| 1 | **A** | Verify routes; no code changes ideally |
| 2 | **C + D** | Validate helper and service logic; run unit tests |
| 3 | **B** | Align controllers; run feature tests |
| 4 | **E** | Adjust views if needed |
| 5 | **F** | Lock everything down with tests |

**Long-term architectural improvement (optional):** Separate read routes (view, download) and write routes (destroy) into different middleware groups to reduce future risk. Not urgent for this implementation.
