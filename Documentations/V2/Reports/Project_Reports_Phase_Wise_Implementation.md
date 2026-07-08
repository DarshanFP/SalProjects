# Project Reports — Phase-Wise Implementation Plan

**Document Version:** 1.0  
**Date:** 2026-03-14  
**Related:** [Project_Reports_Findings_And_Suggestions.md](./Project_Reports_Findings_And_Suggestions.md)  
**Scope:** Remediation of report-related issues identified from production log and codebase audit

---

## Overview

This plan organizes fixes into phases by priority and dependencies. Each phase can be executed and validated independently before moving to the next.

---

## Phase 1 — CCI Statistics Edit Fix (Critical, Immediate)

**Objective:** Remove 500 errors when editing CCI projects without `ProjectCCIStatistics` records.

**Tasks:**

1. **Verify local fix is deployed**
   - File: `app/Http/Controllers/Projects/CCI/StatisticsController.php`
   - Ensure `edit()` uses `first()` and returns a new empty model when no record exists (current local behavior).
   - If production still uses `firstOrFail()`, deploy the fixed version.

2. **Regression test**
   - Edit CCI-0001, CCI-0002 (no statistics record).
   - Edit a CCI project that has a statistics record.
   - Confirm no 500 errors and that forms render correctly.

3. **Optional data repair**
   - Create a command or migration to insert empty `ProjectCCIStatistics` rows for CCI projects that have none.
   - Run in staging first; then production if needed.

**Deliverables:**

- [ ] `StatisticsController::edit()` deployed to production
- [ ] Manual test results documented
- [ ] (Optional) Script/command for backfilling missing CCI statistics

**Effort:** 1–2 hours (including deploy and testing)

---

## Phase 2 — Financial Invariant Data Audit and Repair

**Objective:** Identify and correct approved projects with incorrect `amount_sanctioned` and `opening_balance`.

**Tasks:**

1. **Audit query**
   - List approved projects where `amount_sanctioned = 0` or `amount_sanctioned IS NULL`.
   - List approved projects where `opening_balance != overall_project_budget` (or NULL where not allowed).
   - Affected projects: IOGEP-0006, IAH-0002, ILA-0001, etc.

2. **Repair strategy**
   - Define business rules for correct values (e.g. derive from budget, legacy data).
   - Implement dry-run repair (see `Phase2DryRunRepairSimulation.php` or equivalent).
   - Validate results in staging.

3. **Execute repair**
   - Run repair in production during a maintenance window.
   - Add logging and optional rollback if needed.

4. **Post-repair verification**
   - Revisit executor dashboard; confirm no new financial invariant warnings for repaired projects.

**Deliverables:**

- [ ] Audit report (project_ids, current vs expected values)
- [ ] Repair script/command with dry-run
- [ ] Production run log
- [ ] Verification that invariant warnings are reduced or gone

**Effort:** 1–2 days (depending on data volume and business rules)

**Dependencies:** None (can run in parallel with Phase 1)

---

## Phase 3 — Report Controller Resilience (Medium)

**Objective:** Replace `firstOrFail()` with `first()` + explicit 404 handling in report controllers to avoid 500s when records are missing.

**Tasks:**

1. **Identify call sites**
   - ExportReportController: `downloadPdf`, `downloadDoc`
   - ReportController: `show`, `edit`, `review`, `forward`, `approve`, `removePhoto`, etc.
   - ReportAttachmentController: download and delete flows
   - Aggregated controllers: project lookups

2. **Implement pattern**
   - Use `first()` instead of `firstOrFail()` where a “not found” case is possible.
   - Return a proper 404 response with a clear message instead of letting ModelNotFoundException propagate.
   - Keep `firstOrFail()` only where a missing record indicates a programming error (e.g. internal lookups after validation).

3. **Testing**
   - Hit endpoints with invalid/missing report IDs and verify 404 responses.
   - Ensure valid requests still behave as before.

**Deliverables:**

- [ ] Updated controller methods with explicit 404 handling
- [ ] Test cases for missing report/project scenarios

**Effort:** 0.5–1 day

**Dependencies:** None

---

## Phase 4 — Naming Conventions and Documentation (Low)

**Objective:** Document naming conventions and reduce risk of future inconsistencies.

**Tasks:**

1. **Document conventions**
   - Create a short “Schema and model naming conventions” section (e.g. in `Documentations/V2/Reports/` or a shared conventions doc).
   - Note: `DP_*` tables use PascalCase; new tables should use snake_case; report comments use `R_comment_id`.

2. **Checklist for new code**
   - Use snake_case for new columns and tables.
   - Avoid introducing new camelCase or non-standard primary key names unless justified.

3. **No schema renames**
   - Do not rename existing tables/columns in this phase; treat as reference only.

**Deliverables:**

- [ ] Naming conventions document
- [ ] Optional: brief README in `Documentations/V2/Reports/` linking to this doc

**Effort:** 2–4 hours

**Dependencies:** None

---

## Phase 5 — Report Relation Performance (Low)

**Objective:** Ensure `reports` are eager-loaded where used to avoid N+1 queries.

**Tasks:**

1. **Audit usage**
   - Grep for `$project->reports` in controllers and services.
   - Identify places where projects are loaded without `reports`.

2. **Add eager loading**
   - Where a list of projects is loaded and reports are iterated, add `->with('reports')` (or equivalent).
   - Example: CoordinatorController report lists, ProvincialController report lists, ExecutorController project views.

3. **Measure**
   - Optional: Compare query count before/after for representative pages.

**Deliverables:**

- [ ] Eager loading applied where appropriate
- [ ] (Optional) Query count comparison

**Effort:** 2–4 hours

**Dependencies:** None

---

## Phase 6 — IIES Family Working Members (Info Only)

**Objective:** Clarify and optionally improve handling of “no family members” for IIES projects.

**Tasks:**

1. **Confirm behavior**
   - Verify that “No IIES Family Working Members found” is expected for some IIES projects.
   - Check if business rules require at least one family member for certain flows.

2. **Optional UX**
   - If needed: add an explanatory message in the UI when no family members exist, instead of relying only on the log.

3. **Logging**
   - Current WARNING may be downgraded to INFO if this is always expected for some projects.

**Deliverables:**

- [ ] Decision: expected vs unexpected “no family members”
- [ ] (Optional) UI copy or log level change

**Effort:** 1–2 hours

**Dependencies:** None

---

## Implementation Order

| Phase | Description | Recommended Order | Blocking |
|-------|-------------|-------------------|----------|
| **Phase 1** | CCI Statistics fix | 1 | Unblocks CCI project editing |
| **Phase 2** | Financial invariant repair | 2 (or parallel to 1) | Improves data quality |
| **Phase 3** | Report controller resilience | 3 | Improves user experience |
| **Phase 4** | Naming documentation | 4 | Documentation only |
| **Phase 5** | Report relation performance | 5 | Performance improvement |
| **Phase 6** | IIES clarification | 6 | Optional |

---

## Risks and Mitigations

| Risk | Mitigation |
|------|------------|
| Financial repair changes wrong data | Use dry-run; validate in staging; small batches |
| Controller changes introduce regressions | Add tests; deploy to staging first |
| Eager loading increases memory on very large lists | Use pagination; limit `with('reports')` to needed fields if needed |

---

## Sign-off Checklist (Per Phase)

- [ ] Code reviewed
- [ ] Tested in staging
- [ ] Deployed to production
- [ ] Post-deploy verification
- [ ] Log/monitoring checked for new errors
