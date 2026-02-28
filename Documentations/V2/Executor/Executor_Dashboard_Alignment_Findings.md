# Executor Dashboard – Codebase Alignment Findings

**Date:** 2026-02-19  
**Scope:** Comparison of implemented code against Documentations/V2/Executor docs and subfolders  
**Purpose:** Identify what is aligned, unaligned, and not working in the Executor dashboard feature improvisation.

---

## Table of Contents

1. [Documentation Baseline](#1-documentation-baseline)
2. [What Is Aligned](#2-what-is-aligned)
3. [What Is Not Working / Unaligned](#3-what-is-not-working--unaligned)
4. [Summary Tables](#4-summary-tables)
5. [Recommendations](#5-recommendations)

---

## 1. Documentation Baseline

The following documentation files were reviewed:

| Document | Purpose |
|----------|---------|
| `ExecutorDashboardAudit.md` | Entry points, data flow, scope, filters, N+1 risks |
| `OwnerVsInChargeResponsibilityAudit.md` | Owner vs in-charge merged scope audit |
| `OwnedVsInChargePhasePlan.md` | Phase plan for owned/in-charge separation |
| `Phase1_Infrastructure_Completed.md` | ProjectQueryService owned/in-charge methods |
| `Phase2_KPI_Separation_Completed.md` | ExecutorController KPI scope switch to owned |
| `Phase2_Budget_Zero_Audit.md` | Root cause of Total Budget = 0 |
| `Phase3_Dashboard_Structural_Split.md` | Two-section layout (owned + in-charge) |
| `Phase3_PostSplit_Fix_UndefinedProjects.md` | Widget variable fix for $projects → $ownedProjects |
| `System_Alignment_Feasibility_Audit.md` | Model-service alignment, financial flow |
| `Dashboard_Statistical_Integrity_Audit.md` | Metric trace, status completeness, pagination scope |
| `Dashboard/Phase1_Status_Domain_Integrity_Implementation.md` | Full report status set (getDashboardStatusKeys) |
| `Dashboard/Phase2_Pagination_Scope_Separation_Implementation.md` | KPI widgets use full filtered scope |
| `Dashboard/Phase3_Scope_Consistency_Harmonization_Implementation.md` | Report Overview owned scope, projectTypes |
| `Dashboard/Dashboard_Integrity_Stabilization_Plan.md` | 6-phase stabilization plan |
| `Dashboard/V1/Status_Centralization_Impact_Review.md` | Inline status arrays impact |
| `Dashboard/V1/Cross_Service_Architectural_Alignment_Audit.md` | Cross-service consistency |

---

## 2. What Is Aligned

### 2.1 Phase 1 – Infrastructure Layer

| Item | Implementation | Location |
|------|----------------|----------|
| `getOwnedProjectsQuery(User $user)` | ✓ Implemented | `ProjectQueryService.php` |
| `getInChargeProjectsQuery(User $user)` | ✓ Implemented | `ProjectQueryService.php` |
| `getOwnedProjectIds(User $user)` | ✓ Implemented | `ProjectQueryService.php` |
| `getInChargeProjectIds(User $user)` | ✓ Implemented | `ProjectQueryService.php` |
| `getApprovedOwnedProjectsForUser(User $user)` | ✓ Implemented | `ProjectQueryService.php` |
| `getEditableOwnedProjectsForUser(User $user)` | ✓ Implemented | `ProjectQueryService.php` |
| `getRevertedOwnedProjectsForUser(User $user)` | ✓ Implemented | `ProjectQueryService.php` |

Province filter is applied in both owned and in-charge base queries per plan.

### 2.2 Phase 2 – KPI Separation

| KPI Method | Data Source | Scope |
|------------|-------------|-------|
| Budget summaries | `getApprovedOwnedProjectsForUser` | Owned ✓ |
| Quick Stats | `getOwnedProjectsQuery`, `getOwnedProjectIds`, `getApprovedOwnedProjectsForUser` | Owned ✓ |
| Action Items | `getOwnedProjectIds`, `getRevertedOwnedProjectsForUser`, `getApprovedOwnedProjectsForUser` | Owned ✓ |
| Report Status Summary | `getOwnedProjectIds` | Owned ✓ |
| Chart Data | `getOwnedProjectIds`, `getApprovedOwnedProjectsForUser` | Owned ✓ |
| Report Chart Data | `getOwnedProjectIds` | Owned ✓ |
| Upcoming Deadlines | `getApprovedOwnedProjectsForUser` | Owned ✓ |
| Projects Requiring Attention | `getEditableOwnedProjectsForUser` | Owned ✓ |
| Reports Requiring Attention | `getOwnedProjectIds` | Owned ✓ |

Main projects list and report list continue to use merged scope per plan (reportList, pendingReports, etc.).

### 2.3 Phase 3 – Dashboard Structural Split

| Item | Implementation |
|------|----------------|
| Two sections | "My Projects (Owned)" and "Assigned Projects (In-Charge)" ✓ |
| Separate pagination | `owned_page` and `incharge_page` query params ✓ |
| Counts | `$ownedCount`, `$inChargeCount` passed to view ✓ |
| Create Report | Shown for owned only; hidden in in-charge section ✓ |
| View/Edit | Available in both sections ✓ |

### 2.4 Phase 3 PostSplit – Widget Variable Fix

| Widget | Variables Used |
|--------|----------------|
| `project-status-visualization` | `$projectChartData` (from `buildProjectChartData`) ✓ |
| `project-health` | `$projectHealthSummary`, `$enhancedFullOwnedProjects` ✓ |

No remaining references to deprecated `$projects` or `$enhancedProjects` in these widgets.

### 2.5 Phase 1 – Status Domain Integrity

| Item | Implementation |
|------|----------------|
| `DPReport::getDashboardStatusKeys()` | ✓ Returns all 15 report status keys |
| `getReportStatusSummary()` | ✓ Initializes with `array_fill_keys(DPReport::getDashboardStatusKeys(), 0)` |
| `getReportChartData()` | ✓ Same canonical status set |
| Report Overview | ✓ Uses `$reportStatusSummary['approved_count']`, `['total']`, `['pending_count']` |

### 2.6 Phase 2 – Pagination Scope Separation

| Item | Implementation |
|------|----------------|
| Base query | `$ownedBaseQuery` with filters applied once ✓ |
| Pagination clone | `$ownedPaginatedQuery` → `$ownedProjects` for table ✓ |
| Full KPI clone | `$ownedFullQuery` → `$ownedFullProjects` for KPIs ✓ |
| Health summary | `getProjectHealthSummary($enhancedFullOwnedProjects)` ✓ |
| Project charts | `buildProjectChartData($ownedFullProjects)` → `$projectChartData` ✓ |
| Health breakdown | Uses `$enhancedFullOwnedProjects` ✓ |

Health and project status/type charts use full filtered owned set, not paginated subset.

### 2.7 Phase 3 – Scope Consistency Harmonization

| Item | Implementation |
|------|----------------|
| Recent Reports | Controller passes `$recentReports` from `getOwnedProjectIds` ✓ |
| Report Overview | No inline `Project::where` query; uses `$recentReports` ✓ |
| Project Types filter | Uses `getOwnedProjectsQuery` for dropdown options ✓ |
| Activity feed | Still merged scope; documented in controller comment ✓ |

---

## 3. What Is Not Working / Unaligned

### 3.1 Budget Total Shows Rs. 0.00 (Critical)

**Observed:** Dashboard displays **Total Budget = Rs. 0.00** even when user has approved owned projects with `overall_project_budget` set.

**Evidence from `storage/logs/laravel.log`:**

```
[2026-02-19] WARNING: Financial invariant violation: approved project must have opening_balance == overall_project_budget
{"project_id":"DP-0017","opening_balance":0.0,"overall_project_budget":1412000.0,"invariant":"opening_balance == overall_project_budget"}

{"project_id":"DP-0002","opening_balance":0.0,"overall_project_budget":1428000.0,"invariant":"opening_balance == overall_project_budget"}
```

**Root Cause (per `Phase2_Budget_Zero_Audit.md`):**

- `ProjectFinancialResolver::applyCanonicalSeparation()` returns `opening_balance = (float)($project->opening_balance ?? 0)` for approved projects.
- The resolver assumes `projects.opening_balance` is already populated at approval time.
- When `opening_balance` is null or 0 in the DB, the resolver returns 0.
- `calculateBudgetSummariesFromProjects` sums these values → Total Budget = 0.

**Why It Happens:**

- BudgetSyncService syncs before approval; CoordinatorController persists resolver output after approval.
- If sync is skipped, or approval flow doesn't persist `opening_balance`, the column stays 0.
- Phase 2 only changed which projects are used (owned vs merged); it did not change resolver or persistence.

**Fix Options (from audit):**

1. Ensure `opening_balance` is set at approval time in the coordinator flow.
2. Add resolver fallback: when `opening_balance` is 0 for approved projects, use `amount_sanctioned` or `overall_project_budget`.
3. One-time data fix for existing approved projects with `opening_balance = 0`.

---

### 3.2 Project Approval Check Missing Third Status (High)

**File:** `resources/views/executor/index.blade.php`  
**Lines:** ~327 (owned table), ~412 (in-charge table)

**Current Code:**

```php
$isApproved = $project->status === ProjectStatus::APPROVED_BY_COORDINATOR 
    || $project->status === ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR;
```

**Missing:** `ProjectStatus::APPROVED_BY_GENERAL_AS_PROVINCIAL`

**Impact:**

- Projects approved by General acting as Provincial are not treated as approved.
- "Create Report" button does not appear for those projects.
- Status badge may not show success styling.

**Canonical Source:** `ProjectStatus::APPROVED_STATUSES` includes all three. The blade should use `in_array($project->status, ProjectStatus::APPROVED_STATUSES)` or explicitly include all three constants.

---

### 3.3 Report Overview Edit Button – Incomplete Reverted Set (Medium)

**File:** `resources/views/executor/widgets/report-overview.blade.php`  
**Line:** ~113

**Current Code:**

```php
@elseif(in_array($report->status, [
    App\Models\Reports\Monthly\DPReport::STATUS_DRAFT,
    App\Models\Reports\Monthly\DPReport::STATUS_REVERTED_BY_PROVINCIAL,
    App\Models\Reports\Monthly\DPReport::STATUS_REVERTED_BY_COORDINATOR
]))
```

**Issue:** Only three statuses are checked. `DPReport::isEditable()` covers:

- `STATUS_DRAFT`
- All reverted statuses: `REVERTED_BY_PROVINCIAL`, `REVERTED_BY_COORDINATOR`, `REVERTED_BY_GENERAL_AS_PROVINCIAL`, `REVERTED_BY_GENERAL_AS_COORDINATOR`, `REVERTED_TO_EXECUTOR`, `REVERTED_TO_APPLICANT`, `REVERTED_TO_PROVINCIAL`, `REVERTED_TO_COORDINATOR`

**Impact:** Reports with `reverted_by_general_as_*` or `reverted_to_*` may not show the Edit button.

**Fix:** Use `$report->isEditable()` instead of hardcoded status list.

---

### 3.4 Budget Summary Ignores `project_type` Filter (Medium)

**Per:** `Dashboard_Integrity_Stabilization_Plan.md` Phase 4

**Recommendation:** Budget summary should respect `project_type` when provided.

**Current:** `calculateBudgetSummariesFromProjects` receives projects from `getApprovedOwnedProjectsForUser` but does not filter by `$request->filled('project_type')`.

**Impact:** When user applies a project type filter, the project list is filtered, but the budget summary shows totals for all project types. Metrics and list are inconsistent.

---

### 3.5 ActivityHistoryService Scope Inconsistency (Low)

**Per:** `ExecutorDashboardAudit.md`, `OwnerVsInChargeResponsibilityAudit.md`

**Current:** `ActivityHistoryService::getForExecutor` uses:

```php
Project::where('user_id', $user->id)->orWhere('in_charge', $user->id)
```

- No province filter.
- Does not use `ProjectQueryService`.
- Other Executor data uses province-scoped ProjectQueryService.

**Impact:** Activity feed can include activities from projects outside the user’s province (if user has such projects).

**Status:** Documented as intentional for "operational visibility" in controller. Consider formalizing in service or docs.

---

### 3.6 Provincial `team-overview` Blade – Incomplete Approval Count (Low, Outside Executor)

**File:** `resources/views/provincial/widgets/team-overview.blade.php` (per `Cross_Service_Architectural_Alignment_Audit.md`)

**Issue:** Uses `STATUS_APPROVED_BY_COORDINATOR` only for approval count, omitting `approved_by_general_as_coordinator` and `approved_by_general_as_provincial`.

**Impact:** Approval rate for team members may be undercounted. Out of scope for Executor dashboard but noted for consistency.

---

## 4. Summary Tables

### 4.1 Alignment Status by Phase

| Phase | Status | Notes |
|-------|--------|-------|
| Phase 1 Infrastructure | ✓ Aligned | All owned/in-charge methods present |
| Phase 2 KPI Separation | ✓ Aligned | KPIs use owned scope |
| Phase 3 Structural Split | ✓ Aligned | Two sections, separate pagination |
| Phase 3 PostSplit Fix | ✓ Aligned | Widgets use correct variables |
| Phase 1 Status Domain | ✓ Aligned | Full status set in DPReport |
| Phase 2 Pagination Scope | ✓ Aligned | Health/charts use full filtered scope |
| Phase 3 Scope Consistency | ✓ Aligned | Report Overview, projectTypes owned |

### 4.2 Issues by Severity

| Severity | Count | Items |
|----------|-------|-------|
| Critical | 1 | Budget total = 0 (opening_balance persistence) |
| High | 1 | Missing APPROVED_BY_GENERAL_AS_PROVINCIAL in blade |
| Medium | 2 | Report Overview Edit status set; Budget filter alignment |
| Low | 2 | ActivityHistoryService province; team-overview approval count |

### 4.3 Files Requiring Changes

| File | Issue | Action |
|------|-------|--------|
| `app/Domain/Budget/ProjectFinancialResolver.php` or approval flow | opening_balance 0 | Fallback or fix persistence |
| `resources/views/executor/index.blade.php` | Incomplete $isApproved | Add third approved status |
| `resources/views/executor/widgets/report-overview.blade.php` | Incomplete Edit statuses | Use isEditable() |
| `app/Http/Controllers/ExecutorController.php` | Budget filter | Filter by project_type when requested |
| `app/Services/ActivityHistoryService.php` | Province scope | Optional: add province filter |
| `resources/views/provincial/widgets/team-overview.blade.php` | Approval count | Use full APPROVED_STATUSES |

---

## 5. Recommendations

### Immediate (High Impact, Low Effort)

1. **Fix `$isApproved` in executor index:** Include `ProjectStatus::APPROVED_BY_GENERAL_AS_PROVINCIAL` so projects approved by General as Provincial show "Create Report" and correct badge.

### High Priority

2. **Resolve budget zero issue:** Either ensure `opening_balance` is persisted correctly at approval, or add a resolver fallback when `opening_balance` is 0 for approved projects (e.g. to `amount_sanctioned` or `overall_project_budget`).

### Medium Priority

3. **Report Overview Edit button:** Replace hardcoded reverted statuses with `$report->isEditable()`.

4. **Budget filter alignment:** When `project_type` is in the request, filter projects before passing to `calculateBudgetSummariesFromProjects` so the summary matches the filtered list.

### Lower Priority

5. **ActivityHistoryService:** Align with ProjectQueryService scope (province filter) or document the intentional divergence clearly.

6. **Provincial team-overview:** Use full `DPReport::APPROVED_STATUSES` for approval count for consistency.

---

## Appendix: Verification Commands

```bash
# Confirm owned methods exist in ProjectQueryService
grep -n "getOwnedProjectsQuery\|getApprovedOwnedProjectsForUser" app/Services/ProjectQueryService.php

# Confirm DPReport has getDashboardStatusKeys
grep -n "getDashboardStatusKeys" app/Models/Reports/Monthly/DPReport.php

# Confirm buildProjectChartData exists
grep -n "buildProjectChartData" app/Http/Controllers/ExecutorController.php

# Check for deprecated $projects in executor widgets
grep -rn "\$projects" resources/views/executor/widgets/
```

---

*End of Findings Document*
