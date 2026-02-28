# Master Phase Roadmap — Project View, Attachment, Download & Activity History Access

**Rule:** Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.

---

## Executive Summary

This roadmap defines a phased, production-safe approach to resolve and stabilize access control for **Coordinator** and **Provincial** roles across four domains: Project VIEW, Attachment, Download (PDF/DOC), and Activity History. The audit identified owner vs in-charge asymmetry, route errors, status-based download restrictions, duplicated logic, and performance risks. Phased implementation allows incremental delivery with minimal blast radius and clear rollback points.

---

## Current Architecture Risks

| Risk | Severity | Impact |
|------|----------|--------|
| **Logic drift** | High | ProvincialController uses `user_id` only; ActivityHistoryHelper uses `user_id` OR `in_charge`. Same concept, different implementations. |
| **Status inconsistency** | High | View: no status filter. Download: status whitelist. Users can view but not download in certain statuses. |
| **Route error** | High | Provincial ProjectList uses `projects.show` (executor-only) for project ID link → 403. |
| **General excluded** | Medium | ActivityHistoryHelper returns false for general users → 403 on project activity history. |
| **Null-safety** | Medium | ExportController uses `$project->user->parent_id` without null check. |
| **Duplicated logic** | Medium | `getAccessibleUserIds`, parent_id checks, and scope logic scattered across controllers and helpers. |
| **Performance** | Medium | `getAccessibleUserIds` called 24+ times per provincial request. |

---

## Why Phased Implementation Is Required

1. **Blast radius control:** Each phase isolates changes to a bounded set of files and behaviors.
2. **Rollback safety:** Small phases enable straightforward git revert if issues arise.
3. **Stakeholder confidence:** Incremental delivery with documented validation at each step.
4. **Test-first discipline:** Each phase mandates failing tests first, then implementation.
5. **Dependency management:** Phase C (centralized service) depends on A and B being stable; D and E build on C.

---

## Overview of All Phases

| Phase | Name | Primary Goal | Risk Level |
|-------|------|--------------|------------|
| **A** | Access Stabilization | Fix owner/in_charge parity, wrong route, general exclusion, null-safety | Medium |
| **B** | Download Consistency | Align download with view; remove status whitelist | Medium |
| **C** | Centralized Access Service | Single source of truth for project access logic | High |
| **D** | Performance Optimization | Reduce repeated calls, eager loading, indexes | Low |
| **E** | Test Hardening & Regression Shield | Full coverage for parity, cross-province, null-safety | Low |

---

## High-Level Dependency Graph

```
                    ┌─────────────────────────────────────────┐
                    │             Phase A: Access Stabilization │
                    │  (Parity, route fix, general fix, null)   │
                    └─────────────────────────────────────────┘
                                        │
                                        ▼
                    ┌─────────────────────────────────────────┐
                    │          Phase B: Download Consistency    │
                    │  (Remove status restrictions, align view) │
                    └─────────────────────────────────────────┘
                                        │
                                        ▼
                    ┌─────────────────────────────────────────┐
                    │    Phase C: Centralized Access Service    │
                    │  (ProjectAccessService, refactor all)     │
                    └─────────────────────────────────────────┘
                                        │
                         ┌──────────────┴──────────────┐
                         ▼                              ▼
         ┌───────────────────────────┐   ┌───────────────────────────────────┐
         │ Phase D: Performance Opt.  │   │ Phase E: Test Hardening            │
         │ (Cache, eager load, index) │   │ (Parity, cross-province, coverage) │
         └───────────────────────────┘   └───────────────────────────────────┘
```

---

## Implementation Order Justification

1. **Phase A first:** Addresses immediate correctness and user-facing bugs (403s, missing projects). No architectural change; low complexity.
2. **Phase B second:** Removes status inconsistency between view and download. Depends on stable access checks from A.
3. **Phase C third:** Introduces `ProjectAccessService` and refactors call sites. Requires A and B to be validated so regressions are detectable.
4. **Phase D fourth:** Performance work benefits from centralized logic (C); easier to add caching and scopes to one service.
5. **Phase E last:** Full test hardening uses the finalized architecture; validates entire stack end-to-end.

---

## Estimated Risk per Phase

| Phase | Estimated Risk | Mitigation |
|-------|----------------|------------|
| A | Medium | Small, localized changes; thorough manual QA before deploy |
| B | Medium | Explicit test for "view then download" alignment |
| C | High | Feature-flag optional; incremental migration; extensive tests |
| D | Low | Index and cache changes are additive; no behavioral change |
| E | Low | Additive tests only; no production code change unless fixing failures |

---

## Test-First Strategy Overview

1. **Before each phase:** Write failing tests that capture the desired behavior.
2. **Implement:** Make tests pass with minimal code changes.
3. **Refactor:** Improve structure without changing behavior.
4. **Validate:** Run full regression suite; document results in phase MD.

---

## Documentation Discipline Rule

**Every implementation step must generate or update a corresponding MD file in this same folder documenting:**
- Changes made
- Files touched
- Test results
- Deviations from plan
- Date of implementation
- Engineer name

---

## Phase File Index

| File | Phase | Focus |
|------|-------|-------|
| `01_Phase_A_Access_Stabilization.md` | A | Parity, route fix, general fix, null-safety |
| `02_Phase_B_Download_Consistency.md` | B | Download aligns with view |
| `03_Phase_C_Centralized_Access_Service.md` | C | ProjectAccessService, refactor |
| `04_Phase_D_Performance_Optimization.md` | D | Cache, eager load, indexes |
| `05_Phase_E_Test_Hardening_And_Regression_Shield.md` | E | Parity, cross-province, coverage |

---

*Last updated: 2026-02-23*
