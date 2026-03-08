# Financial System Computation Audit

**Task:** Application Financial System Audit (Budget Approval + Financial Resolver + Dashboard Aggregation)  
**Date:** 2026-03-04  
**Mode:** Audit (read-only, no code modified)

---

## 1. Budget Field Authority

### Budget Field Authority Table

| Field | Meaning | Updated In | Used By |
|-------|---------|------------|---------|
| `overall_project_budget` | Total project budget (requested total) | GeneralInfoController (store/update), BudgetSyncService (pre-approval sync), BudgetReconciliationController (admin correction) | Resolver strategies, BudgetValidationService, dashboards as fallback |
| `amount_sanctioned` | **APPROVED BUDGET** — amount sanctioned by coordinator on approval | ProjectStatusService::approve (only at approval) | Resolver (for approved projects), FinancialInvariantService, ReportController, ProvincialController society stats |
| `opening_balance` | **CURRENT BALANCE** — total funds at project start (sanctioned + forwarded + local) | ProjectStatusService::approve (only at approval) | Resolver (for approved), dashboards (primary aggregation field), BudgetValidationService |
| `amount_forwarded` | Amount carried forward from previous phase | GeneralInfoController, BudgetSyncService | Resolver, BudgetValidationService |
| `local_contribution` | Beneficiary/family/other contribution | GeneralInfoController, BudgetSyncService | Resolver, BudgetValidationService |
| `amount_requested` | **REQUESTED AMOUNT** (not stored) | — | Derived by resolver: `overall - (forwarded + local)` for non-approved projects |

### Canonical Definitions

- **APPROVED BUDGET:** `projects.amount_sanctioned` — set once at coordinator approval, never overwritten by normal flows (locked via BudgetSyncGuard).
- **CURRENT BALANCE:** `projects.opening_balance` — set at approval; FinancialInvariantService enforces `opening_balance === amount_sanctioned` at approval.
- **REQUESTED AMOUNT:** Not a DB column; computed by `ProjectFinancialResolver` as `amount_requested` for non-approved projects.

### Trace Summary

| Stage | Controller/Service | Fields Touched |
|-------|--------------------|----------------|
| Create | GeneralInfoController::store | overall_project_budget, amount_forwarded, local_contribution, commencement_month_year |
| Edit (pre-approval) | GeneralInfoController::update | Same; BudgetSyncGuard blocks if approved |
| Budget save | BudgetController, IGE/IAH/ILP/IIES controllers | Type-specific tables; BudgetSyncService may sync overall_project_budget (config-driven) |
| Pre-approval sync | BudgetSyncService::syncBeforeApproval | overall_project_budget, amount_forwarded, local_contribution (never amount_sanctioned for non-approved) |
| Approval | CoordinatorController, GeneralController → ProjectStatusService::approve | amount_sanctioned, opening_balance, commencement_* |
| Admin correction | BudgetReconciliationController → AdminCorrectionService | overall_project_budget, amount_forwarded, amount_sanctioned, opening_balance |

---

## 2. Budget Approval Workflow

### Pipeline Diagram

```
Create Project (GeneralInfoController)
    → overall_project_budget, amount_forwarded, local_contribution set/updated
    ↓
Budget Submission (BudgetController / type-specific)
    → project_budgets, project_ige_budgets, project_iah_budget_details, etc.
    ↓
[Optional] BudgetSyncService::syncBeforeApproval (when status = forwarded_to_coordinator)
    → Syncs overall, forwarded, local from resolver (never sanctioned for non-approved)
    ↓
Coordinator/General Approval (CoordinatorController / GeneralController)
    → BudgetSyncService::syncBeforeApproval
    → ProjectFinancialResolver::resolve (pre-approval: sanctioned=0, requested=overall-(forwarded+local), opening=forwarded+local)
    → FinancialInvariantService::validateForApproval (opening > 0, sanctioned > 0, opening === sanctioned)
    → ProjectStatusService::approve($project, $user, $approvalData)
    → Atomic save: status + commencement_* + amount_sanctioned + opening_balance
    ↓
Budget Storage (projects table)
    → amount_sanctioned, opening_balance persisted; BudgetSyncGuard blocks further edits
    ↓
Dashboard Aggregation
    → Resolver or direct DB (opening_balance / amount_sanctioned)
```

### Key Findings

1. **When is `amount_sanctioned` set?** Only at approval, in `ProjectStatusService::approve()`, via `$data['amount_sanctioned']`. Values come from `ProjectFinancialResolver::resolve()` (pre-approval: `combinedContribution`; post-approval: DB value).
2. **When is `opening_balance` calculated?** At approval. Pre-approval: resolver returns `forwarded + local`; approval flow uses that. Post-approval: `FinancialInvariantService` enforces `opening_balance === amount_sanctioned`.
3. **Recalculation?** No. After approval, budget fields are locked. Only AdminCorrectionService (admin reconciliation) can change them.
4. **Overwritten during project updates?** No. `BudgetSyncGuard::canEditBudget()` blocks updates to overall_project_budget, amount_forwarded, local_contribution when approved.

---

## 3. Financial Resolver Analysis

### Location

- **Actual:** `app/Domain/Budget/ProjectFinancialResolver.php`  
- **Note:** Audit referred to `app/Services/ProjectFinancialResolver.php` — that file does not exist; resolver lives in Domain.

### Resolver Responsibility Table

| Method | Inputs | Output | Risk |
|--------|--------|--------|------|
| `resolve(Project $project)` | Project (with budgets/type relations), `project->amount_sanctioned`, `project->opening_balance`, `project->isApproved()` | `[overall_project_budget, amount_forwarded, local_contribution, amount_sanctioned, amount_requested, opening_balance]` | Low — delegates to strategies; no raw arithmetic in main class |
| `applyCanonicalSeparation()` | Result from strategy, project status | Overlays: non-approved → sanctioned=0, requested=max(0, overall-combined), opening=combined; approved → sanctioned/opening from DB | Low |
| `assertFinancialInvariants()` | Resolved data | Logs only; no mutation | Low |

### Strategy Inputs (What Resolver Reads)

| Strategy | Project Fields Read | Type-Specific Tables |
|----------|---------------------|----------------------|
| PhaseBasedBudgetStrategy | amount_forwarded, local_contribution, current_phase, overall_project_budget | project_budgets (this_phase) |
| DirectMappedIndividualBudgetStrategy | amount_forwarded, local_contribution, overall_project_budget | iiesExpenses, iesExpenses, ilpBudget, iahBudgetDetails, igeBudget |

### Authority Trust

- **Approved projects:** Resolver trusts `project->amount_sanctioned` and `project->opening_balance` as final authority; returns them unchanged.
- **Non-approved projects:** Resolver computes from type-specific data; returns sanctioned=0, requested=overall-(forwarded+local), opening=forwarded+local.

### FY Awareness

**NO** — Resolver has no financial year logic. It does not read or filter by `commencement_month_year` or any FY dimension.

---

## 4. Dashboard Aggregation Pipeline

### Dashboard Aggregation Table

| Dashboard | Query Source | Budget Field | Filters |
|-----------|--------------|--------------|---------|
| Executor | ProjectQueryService::getOwnedProjectIds, getApprovedOwnedProjectsForUser | Resolver `opening_balance` | Own projects only, approved only |
| Coordinator (system performance) | Project::approved() | Resolver `opening_balance` | province, center, role, parent_id |
| Coordinator (project list) | Project::approved() | Resolver `opening_balance` | Same + start_date, end_date (created_at) |
| Coordinator (approve finance dashboard) | Project::approved(), Project::notApproved() | **Direct** `p->opening_balance` (approved), Resolver `amount_requested` (pending) | province, center, role, project_type |
| Provincial | Project::accessibleByUserIds()->approved() | Resolver `opening_balance` (most), **Direct** `SUM(amount_sanctioned)` (society stats) | center, role, project_type |
| General | ProjectQueryService (coordinator hierarchy + direct team) | Resolver `opening_balance` | coordinator_id, center |
| Budget Report (BudgetExportController) | Project (no role filter in query) | BudgetValidationService (uses resolver) | project_type, status, start_date, end_date (created_at) |

### Aggregation Behaviour

1. **All projects vs approved only:** Dashboards aggregate **approved projects only** for budget totals. Pending totals use resolver `amount_requested`.
2. **Budget field used:** Primarily `opening_balance` (via resolver or direct `p->opening_balance`). Society stats in ProvincialController use `SUM(amount_sanctioned)`.
3. **Resolver usage:** Executor, Coordinator (system performance, project list), Provincial (main dashboard), General all use resolver. Coordinator finance dashboard uses direct `p->opening_balance` for approved projects.

### Inconsistency

- Coordinator approve finance dashboard (`approveFinanceDashboard`) uses direct `$p->opening_balance` instead of resolver. For approved projects this should match, but it bypasses the canonical resolver path.

---

## 5. Role-Based Financial Visibility

### Role Financial Scope

| Role | Project Scope | Budget Visibility |
|------|---------------|-------------------|
| **Executor** | `user_id = user->id` OR `in_charge = user->id` | Own projects only; resolver `opening_balance` |
| **Provincial** | `Project::accessibleByUserIds($ids)` — executors/applicants in scope | Region (province/center); resolver + society stats (SUM amount_sanctioned) |
| **General** | Coordinator hierarchy + direct team (ProjectQueryService) | Managed provinces; resolver |
| **Coordinator** | All projects (no scope filter) | Global; resolver or direct opening_balance |
| **Admin** | All projects | Global; resolver, AdminCorrectionService |

### Implementation

- **ProjectAccessService::getVisibleProjectsQuery()** — Returns query filtered by role (executor/applicant: own; provincial: accessibleByUserIds; coordinator/general/admin: unfiltered).
- **ProjectAccessService::getAccessibleUserIds()** — Used by provincial/general for scope.
- **ProjectPermissionHelper::canView()** — Project-level view check.
- Financial aggregation applies these scopes before summing; no cross-role leakage observed.

---

## 6. Financial Year Feasibility

### Field: `projects.commencement_month_year`

| Aspect | Finding |
|--------|---------|
| Type | `date` (nullable) |
| Set when | At approval (CoordinatorController, GeneralController); optional at create/edit (GeneralInfoController) |
| Format | Y-m-d (e.g. 2024-08-01) |
| Nullable | Yes — can be null before approval |

### FY Derivation

- **India FY:** 1 April (Y) → 31 March (Y+1).
- **Example:** Aug 2024 (2024-08-01) → FY 2024–25.
- **Logic:** If month >= 4, FY = year; else FY = year - 1. Format: `{fy_start}-{fy_end}` (e.g. "2024-25").

### FY Filter Query Feasibility

```sql
-- Example: projects in FY 2024-25
WHERE commencement_month_year >= '2024-04-01'
  AND commencement_month_year <= '2025-03-31'
```

### FY Feasibility Status

**NEEDS DATA CLEANUP**

- `commencement_month_year` is **nullable**. Projects approved before commencement was required may have null.
- `ProjectPhaseService` logs when `commencement_month_year` is missing.
- Recommendation: Run a data audit to count approved projects with null `commencement_month_year`. If any exist, backfill before FY filtering.

---

## 7. Financial Data Consistency Risks

### Risk Table

| Risk | Severity | Location |
|------|----------|----------|
| Provincial society stats use `SUM(amount_sanctioned)` while other dashboards use `opening_balance` | Low | ProvincialController:72 — For approved projects, FinancialInvariantService enforces opening === sanctioned; values should match |
| Coordinator approve finance dashboard uses direct `p->opening_balance` instead of resolver | Low | CoordinatorController:2042, 2070, 2107, 2146, 2190, 2234 — Bypasses canonical path; acceptable for approved projects |
| Approved projects with null `amount_sanctioned` or `opening_balance` | Medium | FinancialInvariantService blocks approval if ≤0; migration `update_amount_sanctioned_for_approved_projects` backfilled some; legacy data may still have gaps |
| Projects approved without `commencement_month_year` | Low | Coordinator approval form requires commencement; General approval same; historical approvals may have null |
| Report `amount_sanctioned_overview` can diverge from `projects.amount_sanctioned` | Medium | ReportController shows discrepancy note when abs(report - project) > tolerance; reports are snapshots, not source of truth |
| Duplicate aggregation logic (resolver vs direct DB) | Low | Some dashboards use resolver, others use direct column; for approved projects they should align |

---

## 8. Dashboard Financial Reliability

### Factors

- **Multi-year projects:** All budget is treated as a single total; no FY split. Totals can span multiple FYs.
- **Role filtering:** Correctly applied; aggregation respects role scope.
- **Absence of FY logic:** Dashboards show all-time or created_at–filtered totals, not FY-specific.
- **Mixed budget fields:** Most use `opening_balance` (resolver or direct); society stats use `amount_sanctioned`; for approved projects these match.

### Dashboard Reliability

**MEDIUM**

- Role scoping and aggregation logic are largely correct.
- Resolver is used in most places; consistency is good.
- Gaps: no FY dimension, mixed use of resolver vs direct column, potential legacy nulls in `commencement_month_year` and financial fields.

---

## 9. FY Dashboard Implementation Readiness

### Minimal Architecture Proposal

Using `projects.commencement_month_year`:

1. **FinancialYearHelper**
   - `fromDate(Carbon $date): string` → e.g. "2024-25"
   - `startDate(string $fy): Carbon` → 1 Apr
   - `endDate(string $fy): Carbon` → 31 Mar
   - `listAvailable(): array` → e.g. ["2022-23", "2023-24", "2024-25"]

2. **Dashboard FY Dropdown**
   - Add FY filter to Coordinator, Provincial, General dashboards.
   - Default: current FY.

3. **Query Scope for FY Filtering**
   - `Project::scopeInFinancialYear($query, string $fy)` using:
     ```php
     $start = FinancialYearHelper::startDate($fy);
     $end = FinancialYearHelper::endDate($fy);
     $query->whereBetween('commencement_month_year', [$start, $end]);
     ```
   - Alternative: use approval date from `project_status_histories` if `commencement_month_year` is null.

### Pre-requisites

- Data audit: count approved projects with null `commencement_month_year`.
- Backfill or define fallback (e.g. approval date, created_at) for FY derivation when commencement is null.

---

## 10. Final Verdict

### SAFE TO IMPLEMENT FY DASHBOARDS

**Summary**

- Budget field authority is clear: `amount_sanctioned` = approved budget, `opening_balance` = current balance.
- Approval workflow is well-defined and atomic; financial fields are locked after approval.
- ProjectFinancialResolver is the canonical source for display; most dashboards use it correctly.
- Role-based visibility is correctly enforced.
- `commencement_month_year` is sufficient for FY derivation with minor data cleanup for nulls.

**Recommendations Before FY Rollout**

1. Run data audit for null `commencement_month_year` on approved projects; backfill where possible.
2. Add `FinancialYearHelper` and scope; introduce FY filter to dashboards.
3. Consider aligning Coordinator approve finance dashboard to use resolver for consistency (low priority).

---

*Audit completed in read-only mode. No code was modified.*
