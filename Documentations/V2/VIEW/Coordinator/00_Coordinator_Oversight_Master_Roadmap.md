# Coordinator Oversight — Master Roadmap

**Rule:** Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.

---

**Date:** 2026-02-23  
**Purpose:** Align the entire system with the Coordinator as a top-level global oversight role (non-hierarchical).

---

## 1. Executive Summary

The Coordinator role is defined as **top-level oversight**: global read-only access to all projects and activity history, without administrative privileges and without inheriting access through provincial hierarchy. This roadmap defines a phased implementation plan to align all controllers, services, and helpers with this model, eliminate drift, and prevent future hierarchical logic from being mistakenly applied to Coordinator.

---

## 2. Clarified Role Definition

| Role | Access Model | View Projects | Edit Projects | Hierarchy |
|------|--------------|---------------|---------------|-----------|
| **Coordinator** | Global read-only oversight | All projects, all provinces | Only permitted workflow actions (approve, revert, reject) | None |
| **Admin** | Full administrative oversight | All projects | Read-only; administrative actions | None |
| **Provincial** | Hierarchical (owner + in-charge in team) | Projects where owner or in-charge is in provincial's scope | Permitted workflow actions | parent_id → executors |
| **General** | Broader than provincial; can act as coordinator or provincial | Context-dependent | Permitted workflow actions | Managed provinces |
| **Executor/Applicant** | Own projects only | Own or in-charge projects | Own or in-charge projects | N/A |

**Coordinator characteristics:**
- Can view ALL projects across ALL provinces
- Can view ALL activity history
- Can download any project they can view
- Must NOT edit projects outside permitted workflow (approve, revert, reject for forwarded projects)
- Must NOT bypass role boundaries
- Does NOT inherit via provincial `parent_id`
- Similar to admin for read scope, but without admin-only routes

---

## 3. Current System State Analysis

| Component | Coordinator Behavior | Aligned? | Notes |
|-----------|---------------------|----------|-------|
| ProjectAccessService::canViewProject | Returns true (after province check) | ✓ | Province check passes (province_id null) |
| ProjectAccessService::getVisibleProjectsQuery | Returns unfiltered query | ✓ | Correct for global |
| CoordinatorController::projectList | No hierarchy filter; shows all | ✓ | Correct |
| CoordinatorController::showProject | No pre-check; delegates to ProjectController | ✓ | Correct |
| CoordinatorController::budgetOverview | **Filters by provincial children's provinces** | ✗ | Uses parent_id; restricts coordinator |
| ProjectPermissionHelper::canView | Returns true for coordinator | ✓ | Correct |
| ActivityHistoryHelper | Unfiltered for coordinator | ✓ | Correct |
| ExportController | Coordinator uses canView; no status restriction | ✓ | Correct (after Phase 4) |

**Key drift:** `budgetOverview` applies province-based filtering via coordinator→provincial→province, which restricts coordinator scope. This contradicts the global oversight model.

---

## 4. Risks of Current Drift

| Risk | Severity | Description |
|------|----------|-------------|
| budgetOverview over-restriction | Medium | Coordinator sees only projects from their provincial children's provinces; should see all |
| Ambiguous documentation | Medium | Previous audit assumed hierarchy; no canonical doc states coordinator = global |
| Future hierarchy enforcement | High | Developer might add parent_id logic for coordinator based on wrong assumptions |
| Inconsistent service usage | Medium | CoordinatorController does not use ProjectAccessService; logic duplicated |
| Role boundary confusion | Low | Coordinator vs admin vs general boundaries not clearly documented in code |

---

## 5. Target Access Model

```
Coordinator
  ├── View: ALL projects (no province, no parent_id filter)
  ├── Download: ALL projects (align with view)
  ├── Activity History: ALL activities
  ├── Edit: NONE (read-only; workflow actions via separate endpoints)
  └── Workflow: approve, revert, reject (for forwarded projects only)
```

**No traversal:** coordinator → provincial → executor chain does NOT apply.

---

## 6. Implementation Order Justification

1. **Phase A (Access Service):** Establish single source of truth. Document and clarify coordinator = global. No controller changes yet.
2. **Phase B (Controller):** Align CoordinatorController to use ProjectAccessService. Fix budgetOverview to show all.
3. **Phase C (ProjectPermissionHelper):** Ensure canView delegates to ProjectAccessService for coordinator; remove duplicated logic.
4. **Phase D (Activity History):** Ensure ActivityHistoryHelper uses ProjectAccessService; document global scope.
5. **Phase E (Download/Attachment):** Verify ExportController and attachments use canView; no status drift.
6. **Phase F (Testing):** Add regression shield; prevent future drift.

---

## 7. Dependency Graph

```
                    ┌─────────────────────────────────────────┐
                    │   Phase A: Clarify Access Service        │
                    │   (Document coordinator = global)        │
                    └─────────────────────────────────────────┘
                                        │
                    ┌───────────────────┴───────────────────┐
                    ▼                                       ▼
    ┌───────────────────────────────┐   ┌───────────────────────────────┐
    │ Phase B: Controller Alignment │   │ Phase C: ProjectPermission     │
    │ (Use ProjectAccessService)    │   │ Helper Alignment               │
    └───────────────────────────────┘   └───────────────────────────────┘
                    │                                       │
                    └───────────────────┬───────────────────┘
                                        ▼
                    ┌─────────────────────────────────────────┐
                    │ Phase D: Activity History Alignment      │
                    └─────────────────────────────────────────┘
                                        │
                                        ▼
                    ┌─────────────────────────────────────────┐
                    │ Phase E: Download & Attachment           │
                    │ Consistency                             │
                    └─────────────────────────────────────────┘
                                        │
                                        ▼
                    ┌─────────────────────────────────────────┐
                    │ Phase F: Testing & Regression Shield     │
                    └─────────────────────────────────────────┘
```

---

## 8. Security Boundary Model

| Boundary | Coordinator | Provincial | Admin |
|----------|-------------|------------|-------|
| View projects | All | Team only (owner+in-charge) | All |
| Edit projects | Workflow only | Workflow + team | Read-only |
| Activity history | All | Team only | All |
| Download | All (if can view) | Team only | All |
| Admin routes | No | No | Yes |
| Hierarchy | None | parent_id | None |

---

## 9. Test Strategy Overview

1. **Pre-phase tests:** Write failing tests that capture desired behavior before implementation.
2. **Regression tests:** Ensure provincial, executor, admin, general unchanged.
3. **Coordinator-specific tests:** Coordinator sees all projects; coordinator cannot edit outside workflow; activity history unfiltered.
4. **Boundary tests:** Coordinator cannot access admin-only routes.

---

## 10. Documentation Discipline Rule

Every implementation step must:
- Generate or update a corresponding MD file in `Documentations/V2/VIEW/Coordinator/`
- Document changes made, files touched, and test results
- Include deviations from plan and rollback steps if applicable

---

## Phase File Index

| File | Phase | Focus |
|------|-------|-------|
| `01_Phase_A_Clarify_Access_Service_Behavior.md` | A | ProjectAccessService coordinator = global |
| `02_Phase_B_Controller_Alignment.md` | B | CoordinatorController → ProjectAccessService |
| `03_Phase_C_ProjectPermissionHelper_Alignment.md` | C | canView delegation |
| `04_Phase_D_ActivityHistory_Scope_Alignment.md` | D | ActivityHistoryHelper consistency |
| `05_Phase_E_Download_And_Attachment_Consistency.md` | E | ExportController, attachments |
| `06_Phase_F_Testing_And_Regression_Shield.md` | F | Tests and regression |

---

*Master roadmap completed 2026-02-23.*
