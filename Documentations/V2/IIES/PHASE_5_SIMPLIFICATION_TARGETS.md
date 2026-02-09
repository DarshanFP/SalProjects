# Phase 5 — Orchestration Simplification Targets

**Date:** 2026-02-08  
**Type:** Analysis only — no code changes  
**Scope:** ProjectController@store

---

## 1. Repeated Patterns (validation, logging, guards)

| Location | Current responsibility | Proposed simplification | Risk level |
|----------|------------------------|-------------------------|------------|
| Lines 555–559, 561–563, 566–573, 604–606 | Entry, transaction boundaries, before-switch logging | Group into a single private helper (e.g. `logStoreEntry`, `logTransactionBoundary`) that accepts a context array | Low |
| Lines 754–758, 760–764 | Catch blocks: both log exception class and message in same structure | Extract `logRollbackAndException(Exception $e, string $context)` to reduce duplication | Low |
| Lines 701–706, 720–722 | IIES case: draft detection, conditional validation, conditional FinancialSupport invocation | Extract `isIiesDraft(Request)`, `shouldCallIiesFinancialSupport(Request)` as private helpers | Medium |
| Lines 733–743 | Draft vs non-draft status assignment; both branches set DRAFT and save | Extract `applyPostCommitStatus(Request, Project)` — logic is identical except log message | Low |

---

## 2. Oversized switch/case blocks

| Location | Current responsibility | Proposed simplification | Risk level |
|----------|------------------------|-------------------------|------------|
| Lines 607–732 | Single switch with 13 cases; each case logs then invokes 2–8 sub-controllers | Replace with dispatch map: `ProjectType::X => [callable, callable, ...]`; single loop invokes each; logging per type in map or extracted | Medium |
| Lines 698–725 | IIES case: 28 lines of inline logic (draft detection, validation, guarded calls) | Extract `storeIiesType(Request, Project)` private method; preserves exact branch order and conditions | Medium |

---

## 3. Inline orchestration that could be extracted

| Location | Current responsibility | Proposed simplification | Risk level |
|----------|------------------------|-------------------------|------------|
| Lines 584–597 | Institutional branch: LogicalFramework, Sustainability, Budget, Attachment | Extract `storeInstitutionalSections(Request, Project)` | Low |
| Lines 575–580 | GeneralInfo store + merge project_id + log | Extract `storeGeneralInfoAndMergeProjectId(Request)` returning project | Low |
| Lines 745–752 | Post-commit redirect logic (draft → edit, non-draft → index) | Extract `redirectAfterStore(Request, Project)` | Low |

---

## 4. Controller responsibilities that remain valid but heavy

| Location | Current responsibility | Proposed simplification | Risk level |
|----------|------------------------|-------------------------|------------|
| Lines 552–772 | Full store: transaction, 4 orchestration steps, 13 project-type branches, commit, status, redirect | store() remains entry point; delegate to `executeStoreSteps(Request)` and `handleProjectTypeStore(Request, Project)` to reduce line count while keeping single transaction scope | Medium |
| Lines 607–732 | Project-type routing: knowledge of all 13 types and their sub-controller sequences | Move type-to-controllers mapping to a dedicated class or static config (e.g. `ProjectTypeStoreConfig::getControllersFor(string $type)`) | High |
