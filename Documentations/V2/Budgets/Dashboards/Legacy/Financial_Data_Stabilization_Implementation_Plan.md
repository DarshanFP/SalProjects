# Financial Data Stabilization Implementation Plan

**Date:** 2026-03-04  
**Status:** DRAFT — Implementation plan only. No code or database modifications performed.  
**Reference Audits:** Financial_Invariant_Rule_Audit.md, Legacy_Approved_Project_Financial_Repair_Audit.md

---

## 1. Executive Summary

This plan describes a phased approach to stabilize the financial system by:

1. **Correcting invariant rules** — Aligning FinancialInvariantService and ProjectFinancialResolver with the architecture: `opening_balance = amount_sanctioned + amount_forwarded + local_contribution`.
2. **Repairing legacy data** — Backfilling `opening_balance` and `amount_sanctioned` for 27 approved projects with missing or incorrect values (21 from Legacy audit + 6 forwarded mismatch from Pre-Implementation audit).
3. **Validating dashboards** — Ensuring Executor, Coordinator, and Provincial dashboards show correct totals after repair.
4. **Verifying integrity** — Confirming the system is stable and invariant checks pass.

**Safe repair order:** Rule correction → Data repair → Dashboard validation → Integrity verification. This order ensures the system rules are correct before data changes, and data changes are validated before sign-off.

---

## 2. Current System Issues

### 2.1 Incorrect Invariant Rules (Financial_Invariant_Rule_Audit)

| Issue | Location | Impact |
|-------|----------|--------|
| **INV-3:** `opening_balance === amount_sanctioned` | FinancialInvariantService | Blocks valid approvals for projects with forwarded/local contributions (e.g. DP-0024, DP-0025). |
| **INV-7:** `opening_balance == overall_project_budget` | ProjectFinancialResolver | Logs false positives for Individual-type projects; rule is a proxy, not the canonical formula. |

**Canonical rule (architecture):** `opening_balance = amount_sanctioned + amount_forwarded + local_contribution`

### 2.2 Legacy Financial Data (Legacy_Approved_Project_Financial_Repair_Audit)

| Issue | Count | Impact |
|-------|-------|--------|
| `opening_balance` NULL, `amount_sanctioned` > 0, forwarded+local=0 | 13 | Resolver returns 0; dashboards understate by ~13.5M. |
| `opening_balance` = 0, `amount_sanctioned` > 0 | 7 | Same understatement (~800K). |
| `amount_sanctioned` = 0, `overall_project_budget` > 0 | 1 (DP-0064) | Missing 100K. |
| Individual types (DP-0041, IIES-0060) | 2 | Manual review; different sanctioned semantics. |
| Zero-budget projects | 7 | Excluded from repair; confirm intent. |
| Forwarded projects (DP-0024, DP-0025) | 2 | No repair; correct by design. |

**Total auto-repair candidates:** 27 projects (21 from Legacy audit; 6 additional forwarded mismatch from Pre_Implementation_System_Audit_Report).

### 2.3 Dashboard Impact

- All dashboards use `resolver->resolve($project)['opening_balance']` for budget totals.
- Resolver returns DB `opening_balance` as-is (or 0 when NULL); no fallback.
- Result: incorrect or null DB values propagate to Executor, Coordinator, and Provincial dashboards.

---

## 3. Phase-wise Implementation Plan

---

### PHASE 0 — Pre-Implementation System Audit

#### Objective

Verify that the current system state still matches the audit findings before starting implementation. This phase ensures that no new approvals or financial changes have occurred since the audit reports were generated.

#### Scope

Verify the system against these reference audits:

- Financial_Invariant_Rule_Audit.md
- Legacy_Approved_Project_Financial_Repair_Audit.md

#### Audit Tasks

1. **Recount approved projects.**  
   Confirm that the total number of approved projects still matches the audit baseline.

2. **Detect new invariant violations.**  
   Re-run invariant checks:  
   `opening_balance = amount_sanctioned + amount_forwarded + local_contribution`  
   Identify any new violations.

3. **Verify legacy repair candidates.**  
   Confirm that the same projects identified earlier still require repair. Check especially:
   - `opening_balance` NULL
   - `opening_balance` = 0 when `amount_sanctioned` > 0

4. **Check approval activity after the audit date.**  
   Verify whether any projects were approved after the audit report date. If new approvals exist, list them.

5. **Verify dashboard totals.**  
   Capture current totals for:
   - Executor dashboard
   - Coordinator dashboard
   - Provincial dashboard  
   These will serve as baseline values before repair.

#### Output

Generate a report: **Pre_Implementation_System_Audit_Report.md**  
Save to: `Documentations/V2/Budgets/Dashboards/Executor/`

#### Validation

- Compare Phase 0 findings with Financial_Invariant_Rule_Audit and Legacy_Approved_Project_Financial_Repair_Audit.
- If material divergence is found, reassess the implementation plan before proceeding to Phase 1.

---

### PHASE 1 — Financial Invariant Rule Correction

#### Objective

Align financial invariant rules with the architecture: `opening_balance = amount_sanctioned + amount_forwarded + local_contribution`.

#### Components Affected

| Component | File | Change Type |
|-----------|------|-------------|
| FinancialInvariantService | `app/Domain/Finance/FinancialInvariantService.php` | Replace INV-3 |
| ProjectFinancialResolver | `app/Domain/Budget/ProjectFinancialResolver.php` | Adjust or remove INV-7 |
| CoordinatorController | `app/Http/Controllers/CoordinatorController.php` | Verify approval pipeline compatibility |

#### Implementation Tasks

1. **Replace INV-3 in FinancialInvariantService**
   - Remove: `opening_balance === amount_sanctioned`.
   - Add: `opening_balance = amount_sanctioned + (amount_forwarded ?? 0) + (local_contribution ?? 0)` with tolerance 0.01.
   - Source forwarded/local from approval data or project.

2. **Review resolver INV-7 in ProjectFinancialResolver**
   - Option A: Remove the `opening_balance == overall_project_budget` assertion.
   - Option B: Replace with `opening_balance = sanctioned + forwarded + local` (log when mismatch).
   - Option C: Restrict to phase-based types only.
   - Recommended: Option A or B to avoid false positives for Individual types.

3. **Verify approval pipeline**
   - Ensure CoordinatorController approval logic computes `amount_sanctioned` and `opening_balance` so they satisfy the corrected invariant.
   - If approval uses `sanctioned = overall - combined`, `opening = sanctioned + combined`, the corrected invariant must allow it.
   - Run approval flow tests with a project that has forwarded funds.

#### Risk Analysis

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Approval fails for previously working cases | Low | High | Test approval with and without forwarded/local before deploy. |
| New invariant rejects valid data | Medium | Medium | Use tolerance 0.01; validate against DP-0024, DP-0025 (forwarded). |
| Regression in other flows | Low | Medium | Run existing approval and budget tests. |

#### Validation Steps

1. Unit test: `FinancialInvariantService::validateForApproval()` passes when `opening = sanctioned + forwarded + local`.
2. Unit test: Validation fails when `opening ≠ sanctioned + forwarded + local`.
3. Integration test: Approve a project with forwarded funds; ensure approval succeeds.
4. Integration test: Approve a project with forwarded=0, local=0; ensure approval succeeds when opening=sanctioned.
5. Verify no new logs from INV-7 for DP-0024, DP-0025, DP-0041, IIES-0060.

#### Rollback Strategy

- Revert FinancialInvariantService and ProjectFinancialResolver to previous versions.
- Approval flow does not persist invariant rules; rollback of code only.

---

### PHASE 2 — Legacy Financial Data Repair

#### Objective

Repair 27 approved projects with missing or incorrect `opening_balance` and `amount_sanctioned`, using safe auto-repair rules. *Original repair candidates: 21. Additional forwarded mismatch projects (Pre-Implementation Audit): 6. Total repair scope: 27.*

#### Components Affected

- Database: `projects` table (`opening_balance`, `amount_sanctioned`).
- No application code changes in this phase (data-only).

#### Implementation Tasks

1. **Create repair script (Artisan command or migration)**

   - **CASE A:** `opening_balance` NULL, `amount_sanctioned` > 0, `amount_forwarded` = 0, `local_contribution` = 0  
     - Set `opening_balance = amount_sanctioned`.  
     - Projects: DP-0001, CIC-0001, DP-0002, DP-0003, DP-0005, DP-0006, DP-0007, DP-0008, DP-0009, DP-0016, DP-0017, DP-0020, DP-0022.

   - **CASE B:** `opening_balance` = 0, `amount_sanctioned` > 0, forwarded+local = 0  
     - Set `opening_balance = amount_sanctioned`.  
     - Projects: GEN-0005, GEN-0007, DP-0055, DP-0057, DP-0058, DP-0059, DP-0060, DP-0062.

   - **CASE C:** `amount_sanctioned` = 0, `overall_project_budget` > 0, forwarded+local = 0  
     - Set `amount_sanctioned = overall_project_budget`, `opening_balance = overall_project_budget`.  
     - Project: DP-0064.

   - **CASE D — Forwarded Contribution Mismatch** (Pre-Implementation Audit)  
     - **Condition:** `opening_balance = amount_sanctioned` AND `amount_forwarded` > 0.  
     - **Repair logic:** `opening_balance = amount_sanctioned + amount_forwarded + local_contribution`.  
     - **Projects:** DP-0066, DP-0068, DP-0070, DP-0072, DP-0074, DP-0076.  
     - **Current state:** sanctioned=100000, forwarded=100000, opening=100000.  
     - **Expected opening_balance:** 200000.

2. **Repair scope summary**

   | Case | Description | Count | Projects |
   |------|-------------|-------|----------|
   | A | opening_balance NULL, sanctioned > 0 | 13 | DP-0001, CIC-0001, DP-0002, DP-0003, DP-0005, DP-0006, DP-0007, DP-0008, DP-0009, DP-0016, DP-0017, DP-0020, DP-0022 |
   | B | opening_balance = 0, sanctioned > 0 | 7 | GEN-0005, GEN-0007, DP-0055, DP-0057, DP-0058, DP-0059, DP-0060, DP-0062 |
   | C | sanctioned = 0, overall > 0 | 1 | DP-0064 |
   | D | Forwarded contribution mismatch | 6 | DP-0066, DP-0068, DP-0070, DP-0072, DP-0074, DP-0076 |
   | **Total** | | **27** | |

3. **Exclusions (do not auto-repair)**
   - DP-0024, DP-0025: Forwarded projects; correct by design.
   - DP-0041, IIES-0060: Individual types; manual review.
   - Zero-budget projects (GEN-0006, DP-0056, DP-0061, DP-0063, DP-0065, DP-0067, DP-0069): Confirm intent first.

4. **Audit trail**
   - Log all updates (project_id, old values, new values) before applying.
   - Store repair log in `storage/logs/` or dedicated audit table.

5. **Execution**
   - Run in transaction; rollback on any error.
   - Execute in staging first; compare resolver outputs before/after.

#### Risk Analysis

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Wrong values written | Low | High | Validate repair logic against audit; run in transaction. |
| Data loss | Low | Critical | Backup `projects` before repair; use transactions. |
| Side effects (reports, exports) | Low | Medium | Reports use report-stored values; project-level changes should not break. |

#### Validation Steps

1. Pre-repair: Capture resolver output for each project.
2. Run repair script in staging.
3. Post-repair: Re-run resolver; compare outputs.
4. Verify `opening_balance = amount_sanctioned + amount_forwarded + local_contribution` for all repaired projects.
5. Spot-check CASE A/B: DP-0001 resolver opening_balance = 789000; DP-0017 = 1412000.
6. Spot-check CASE D: DP-0066, DP-0068, DP-0070, DP-0072, DP-0074, DP-0076 resolver opening_balance = 200000 (sanctioned + forwarded).

#### Rollback Strategy

- Restore `projects` from backup for affected rows.
- Or run reverse script: set `opening_balance` back to original values (NULL/0 for CASE A/B/C; 100000 for CASE D projects DP-0066, DP-0068, DP-0070, DP-0072, DP-0074, DP-0076) — only if no other changes occurred.

---

### PHASE 3 — Dashboard Validation

#### Objective

Verify Executor, Coordinator, and Provincial dashboards show correct totals after data repair.

#### Components Affected

- Executor dashboard (`ExecutorController`, `resources/views/executor/*`)
- Coordinator dashboard (`CoordinatorController`, `resources/views/coordinator/*`)
- Provincial dashboard (`ProvincialController`, `resources/views/provincial/*`)
- No code changes; validation only.

#### Implementation Tasks

1. **Clear dashboard cache**
   - Invalidate cache keys used by dashboard aggregation (e.g. `coordinator_*`, `provincial_*` if applicable).
   - Ensure dashboards recompute from DB.

2. **Recalculate dashboard totals**
   - For a known user (e.g. user 37): sum resolver `opening_balance` for their approved projects in each FY.
   - Compare with dashboard displayed total.

3. **Validate per dashboard**
   - **Executor:** Project Budgets Overview widget; total_budget, approved_expenses, remaining.
   - **Coordinator:** System performance, provincial overview, budget overview widgets.
   - **Provincial:** Budget summaries, center performance, team performance.

4. **FY filtering**
   - Confirm FY dropdown still filters correctly.
   - Ensure projects in different FYs do not bleed across.

#### Risk Analysis

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Cache serving stale data | Medium | Medium | Explicit cache invalidation post-repair. |
| FY scope wrong | Low | Medium | Re-use existing FY tests; manual check. |

#### Validation Steps

1. User 37 (from Dashboard_User37_Budget_Zero_Audit): Select FY 2024-25, 2026-27; confirm non-zero totals where expected.
2. Coordinator: System-wide total budget; compare with sum of all approved projects' resolver opening_balance.
3. Provincial: For a province with repaired projects; compare displayed total with expected.
4. Regression: Ensure previously correct dashboards (e.g. DP-0024, DP-0025) still show correct values.

#### Rollback Strategy

- No code changes in this phase; if dashboards are wrong, root cause is data (Phase 2) or resolver (unchanged). Rollback Phase 2 if needed.

---

### PHASE 4 — Financial Integrity Verification

#### Objective

Confirm the financial system is stable and invariant checks pass across all approved projects.

#### Components Affected

- ProjectFinancialResolver (read-only)
- BudgetValidationService (read-only)
- All approved projects in database

#### Implementation Tasks

1. **Run invariant checks**
   - For each approved project: compute `expected_opening = amount_sanctioned + amount_forwarded + local_contribution`.
   - Compare with `opening_balance`; flag mismatches (tolerance 0.01).
   - Document any failures (should be 0 after Phase 2 for auto-repaired set).

2. **Resolver simulation**
   - For each approved project: `ProjectFinancialResolver::resolve($project)`.
   - Compare `resolver['opening_balance']` with expected.
   - Ensure no projects return 0 when DB has non-null, non-zero opening_balance.

3. **Aggregation logic check**
   - Confirm `calculateBudgetSummariesFromProjects` uses resolver `opening_balance` only.
   - No raw `amount_sanctioned` or `overall_project_budget` for totals.
   - Executor, Coordinator, Provincial all follow same pattern.

4. **Report**
   - Generate verification report: projects checked, invariants passed/failed, resolver outputs summary.

#### Risk Analysis

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Residual violations | Low | Low | Document and queue for manual review. |
| Individual/zero-budget edge cases | Medium | Low | Exclude from pass/fail; list separately. |

#### Validation Steps

1. Invariant pass rate: 100% for auto-repaired projects.
2. Resolver output: No approved project with sanctioned+forwarded+local > 0 returns opening_balance = 0.
3. Aggregation: Sum of resolver opening_balance across approved set matches dashboard total (within rounding).

#### Rollback Strategy

- No modifications in this phase; findings inform further repairs or manual review.

---

## 4. Risk Assessment

### 4.1 Overall Risk Matrix

| Phase | Risk Level | Key Risks |
|-------|------------|-----------|
| Phase 1 | Medium | Approval regression; invariant too strict/loose. |
| Phase 2 | Medium | Data corruption; incorrect repair logic. |
| Phase 3 | Low | Cache; no code changes. |
| Phase 4 | Low | Verification only. |

### 4.2 Dependencies

- Phase 2 depends on Phase 1 (correct rules before data repair).
- Phase 3 depends on Phase 2 (validate after repair).
- Phase 4 can run after Phase 2 or 3; it is independent validation.

### 4.3 Recommended Execution Order

1. Phase 1 (rule correction) → Deploy → Test approval flow.
2. Phase 2 (data repair) → Staging first → Production with backup.
3. Phase 3 (dashboard validation) → Manual and automated checks.
4. Phase 4 (integrity verification) → Final sign-off report.

---

## 5. Data Safety Strategy

### 5.1 Backup Requirements

- Full backup of `projects` table before Phase 2.
- Backup of `project_status_histories` and `activity_histories` if they reference financial fields (optional).
- Retain backup for at least 30 days post-implementation.

### 5.2 Repair Execution

- Use database transactions: wrap all updates in a single transaction; commit only after validation.
- Dry-run mode: script should support `--dry-run` to log intended changes without writing.
- Idempotency: script should be safe to re-run (skip already-correct rows).

### 5.3 Audit Trail

- Log file: `storage/logs/financial_repair_YYYYMMDD.log`.
- Contents: project_id, field, old_value, new_value, timestamp.
- Optional: Insert into `budget_audit_log` or similar if available.

---

## 6. Validation Strategy

### 6.1 Unit Tests

- FinancialInvariantService: test `validateForApproval()` with (sanctioned, forwarded, local) combinations.
- Resolver: test that `opening_balance` is returned correctly for approved projects with various DB states.

### 6.2 Integration Tests

- Approval flow: approve project with forwarded funds; approve project with no forwarded funds.
- Dashboard: mock project set; assert `calculateBudgetSummariesFromProjects` total matches sum of resolver outputs.

### 6.3 Manual Validation

- Staging: run full flow; verify dashboards for sample users.
- Production: spot-check 3–5 users (Executor, Coordinator, Provincial) after deploy.
- Compare totals before/after for a known cohort (e.g. user 37's projects).

### 6.4 Acceptance Criteria

| Criterion | Phase |
|-----------|-------|
| Approval of project with forwarded funds succeeds | Phase 1 |
| Approval of project with no forwarded funds succeeds | Phase 1 |
| All 27 auto-repair candidates have correct opening_balance | Phase 2 |
| Resolver returns non-zero opening_balance for repaired projects | Phase 2 |
| Executor dashboard shows correct total for user 37 in FY 2024-25, 2026-27 | Phase 3 |
| Coordinator dashboard system total matches sum of approved projects | Phase 3 |
| No invariant violations for repaired projects | Phase 4 |

---

## 7. Post-Implementation Monitoring

### 7.1 Log Monitoring

- Watch for `Financial invariant violation` logs from ProjectFinancialResolver (should decrease).
- Watch for `Approval blocked` errors from FinancialInvariantService (should not increase for valid cases).

### 7.2 Dashboard Metrics

- Track dashboard total budget for a fixed cohort (e.g. all approved projects) before and after.
- Alert if total drops unexpectedly (indicates possible regression).

### 7.3 Data Integrity Checks

- Weekly (or post-deploy): Run invariant check script; ensure 0 failures for repaired projects.
- Document any projects requiring manual review (Individual types, zero-budget).

### 7.4 Escalation

- If approval flow fails after Phase 1: Rollback FinancialInvariantService; investigate.
- If dashboard totals wrong after Phase 2: Verify repair script output; consider rollback of data.
- If new violations appear: Queue for manual review; do not auto-repair without audit update.

---

## 8. Summary Checklist

| Step | Description | Owner |
|------|-------------|-------|
| 1 | Phase 1: Correct INV-3, adjust INV-7 | Dev |
| 2 | Phase 1: Test approval with forwarded project | QA |
| 3 | Phase 2: Backup `projects` table | Ops |
| 4 | Phase 2: Create and run repair script (staging) | Dev |
| 5 | Phase 2: Validate resolver outputs; run in production | Dev/Ops |
| 6 | Phase 3: Invalidate cache; validate dashboards | QA |
| 7 | Phase 4: Run integrity verification; generate report | Dev |
| 8 | Post-implementation: Monitor logs and totals | Ops |

---

*This plan is for implementation guidance only. No code or database modifications have been performed. Execute phases in order with appropriate testing and rollback readiness.*
