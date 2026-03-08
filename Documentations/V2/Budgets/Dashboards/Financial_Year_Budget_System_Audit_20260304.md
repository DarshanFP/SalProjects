# Financial Year & Budget Architecture Audit

**Task:** Financial Year & Budget Architecture Audit  
**Date:** 2026-03-04  
**Mode:** Audit (read-only, no code modified)

---

## 1. Budget Storage Structure

### Tables Involved

| Table | Purpose | Budget-Relevant Columns |
|-------|---------|-------------------------|
| `projects` | Primary project record | `overall_project_budget`, `amount_sanctioned`, `amount_forwarded`, `opening_balance` |
| `project_budgets` | Phase-based budget line items (Development Projects) | `particular`, `phase`, `rate_quantity`, `rate_multiplier`, `rate_duration`, `this_phase`, `next_phase` |
| `old_DP_budgets` | Legacy Development Project budgets | Same structure as `project_budgets` |
| `project_ige_budgets` | IGE project type budgets | `amount_requested`, `total_amount`, etc. |
| `project_iah_budget_details` | IAH project type budgets | `amount`, `amount_requested`, `total_expenses` |
| `project_ilp_budgets` | ILP project type budgets | `amount_requested`, `cost`, `budget_desc` |
| `dp_reports`, `quarterly_reports`, `half_yearly_reports`, `annual_reports` | Report snapshots | `amount_sanctioned_overview` (copy at report time) |
| `dp_account_details`, `quarterly_report_details`, etc. | Report account details | `amount_sanctioned`, `opening_balance` per report/period |

### Where Approved Budget Amount Is Stored

- **Primary storage:** `projects.amount_sanctioned` and `projects.opening_balance` (persisted on approval)
- **Alternative/fallback:** `projects.overall_project_budget` (requested total; used when `amount_sanctioned` is null/zero)
- **Line-item source:** `project_budgets.this_phase` (Development Projects) — sum drives `overall_project_budget` for non-approved projects

### Budget Storage Model

**PROJECT_ONLY**

- Budget is stored **per project** only
- No `financial_year`, `fy`, or year column in any budget-related table
- No per-project-per-year or per-tranche budget structure
- Report tables store `amount_sanctioned_overview` as a snapshot at report creation time; they do not define the canonical budget by year

---

## 2. Financial Year Support Analysis

### Search Results

| Search Term | Result |
|-------------|--------|
| `financial_year` | **No matches** in app codebase |
| `fiscal_year` | **No matches** |
| `fy` | **No matches** |
| `financialYear()` | **No matches** |
| `start_year` / `end_year` | Used in half_yearly/aggregated reports for report year, not FY definition |
| `April` / `March` | One reference only |

### Only FY-Related Reference

**File:** `app/Http/Controllers/Reports/Quarterly/DevelopmentProjectController.php` (line 49)

```php
->where('created_at', '>=', now()->startOfYear()->subMonths(9)) // Adjust for financial year starting from April
->where('created_at', '<=', now()->startOfYear()->addMonths(3));
```

- Uses `startOfYear()` (calendar year, 1 Jan) and month offsets to approximate Apr–Mar
- Logic is implicit and incorrect for India FY (1 Apr–31 Mar)
- Used only for previous reports query in quarterly report form
- **Not** a general financial year mechanism

### Financial Year Support

**NONE**

- No explicit financial year storage
- Not derived from project start/approval date in a reusable way
- Not user-selectable anywhere
- No dashboard or report filter by financial year

### Files Referencing Financial Year Logic

| File | Usage |
|------|-------|
| `app/Http/Controllers/Reports/Quarterly/DevelopmentProjectController.php` | Single comment and date filter approximating FY (not India FY compliant) |

---

## 3. Project Date Structure

### Fields Available

| Field | Table | Purpose |
|-------|-------|---------|
| `commencement_month_year` | `projects` | Combined date (Y-m-d) |
| `commencement_month` | `projects` | 1–12 (added 2026-01-19) |
| `commencement_year` | `projects` | Year (added 2026-01-19) |
| `overall_project_period` | `projects` | Duration in phases |
| `current_phase` | `projects` | Current phase |
| `created_at` | `projects` | Record creation |

### Can Financial Year Be Derived?

**Yes.** From `commencement_month_year` or `commencement_month` + `commencement_year`:

- India FY: 1 April (Y) → 31 March (Y+1)
- If commencement is Aug 2024 → FY 2024–25
- For multi-year projects, FY can be derived per phase or per approval date if needed

### Project Date Structure Suitability

**SUFFICIENT**

- `commencement_month` and `commencement_year` (or `commencement_month_year`) allow derivation of the financial year(s) a project falls into
- No schema change required for FY derivation; only helper/service logic needed

---

## 4. Dashboard Financial Logic

### Dashboards Reviewed

| Dashboard | Controller | Aggregation | Year Filter | Filters Used |
|-----------|------------|-------------|-------------|--------------|
| Executor | `ExecutorController` | Sum of `opening_balance` for owned projects via `ProjectFinancialResolver` | **None** | Project ownership only |
| Coordinator | `CoordinatorController` | Sum of `opening_balance` for all approved projects | **None** | province, center, role, parent_id |
| General | `GeneralController` | Sum for coordinator hierarchy + direct team | **None** | coordinator_id, center |
| Provincial | `ProvincialController` | Sum for accessible users’ projects | **None** | center, role, project_type |
| Coordinator Project List | `CoordinatorController::projectList` | Per-project and grand totals | `start_date`/`end_date` on `created_at` | province, status, project_type, start_date, end_date |
| Budget Report | `BudgetExportController::generateReport` | Total budget, expenses, remaining | `start_date`/`end_date` on `created_at` | project_type, status, start_date, end_date |
| System Analytics | Coordinator widget | Budget utilization timeline by month | `start_date`/`end_date` (analytics range) | Optional date range |

### Observations

1. **No financial year filter** — Dashboards aggregate by role/region, not by FY
2. **Date filters use `created_at`** — Budget Report and Project List filter by project creation date, not approval date or FY
3. **Aggregation is global/all-time** — Unless a date range is applied, totals include all projects in scope
4. **Role-based scoping only** — Different roles see different project sets; no FY dimension

### Dashboard Financial Model

**MIXED**

- **Role-based:** Executor (owned), Provincial (region), General (hierarchy), Coordinator/Admin (global)
- **Date filters:** Use `created_at` only, not financial year
- **No FY-based aggregation or filtering**

---

## 5. Role-Based Financial Visibility

### Role Financial Scope

| Role | Scope | Mechanism |
|------|-------|-----------|
| **Executor / Applicant** | PROJECT_LEVEL (own projects only) | `user_id = user->id` OR `in_charge = user->id` |
| **Provincial** | REGION_LEVEL | `Project::accessibleByUserIds($accessibleUserIds)` — users in province/center |
| **General** | REGION_LEVEL | Coordinator hierarchy + direct team (descendant user IDs) |
| **Coordinator** | GLOBAL | All projects; optional filters (province, center, role) |
| **Admin** | GLOBAL | All projects |

### Implementation

- `ProjectAccessService::getVisibleProjectsQuery()` and `getAccessibleUserIds()`
- `ProjectPermissionHelper::canView()` for project-level checks
- `ProjectQueryService::getOwnedProjectIds()`, `getApprovedOwnedProjectsForUser()`, etc.
- Province check via `ProjectPermissionHelper::passesProvinceCheck()`

### Role Financial Scope Summary

**MIXED:** PROJECT_LEVEL (executor/applicant), REGION_LEVEL (provincial, general), GLOBAL (coordinator, admin)

---

## 6. Gap Analysis

### Current State vs FY Requirements

| Requirement | Current | Gap |
|-------------|---------|-----|
| Store financial year | No | Need schema or derivation strategy |
| Filter dashboard by FY | No | Need FY filter in controllers/views |
| Aggregate by FY | No | Need FY-based grouping in queries |
| FY-aware exports/reports | No | Budget report uses `created_at`; no FY filter |
| India FY (1 Apr–31 Mar) | Not implemented | One ad-hoc reference, not India FY compliant |

### Option Analysis

#### Option A: Financial Year in `projects` Table

| Aspect | Analysis |
|--------|----------|
| Schema | Add `approval_financial_year` (e.g. `2024` for FY 2024–25) or `financial_year_start` date |
| Migration | Straightforward; backfill from `commencement_month_year` or approval date |
| Dashboard | Filter `WHERE approval_financial_year = ?` or derived FY |
| Reports | Add FY filter to Budget Report, exports |
| Limitation | Single FY per project; multi-year projects may need multiple rows or a separate `project_financial_years` table |

#### Option B: Financial Year in `project_budgets` Table

| Aspect | Analysis |
|--------|----------|
| Schema | Add `financial_year` to `project_budgets` (or equivalent) |
| Migration | Budget rows are phase-based, not year-based; would require new semantics and data migration |
| Dashboard | Could aggregate by FY if budget rows are FY-scoped |
| Reports | FY breakdown possible |
| Limitation | `project_budgets` is phase/particular oriented; not all project types use it (IGE, IAH, ILP use type-specific tables) |

#### Option C: Derived Financial Year (Calculated from Dates)

| Aspect | Analysis |
|--------|----------|
| Schema | No change |
| Migration | None |
| Dashboard | Add FY helper (e.g. `FinancialYearHelper::fromDate($date)`) and filter `WHERE` derived FY matches selection |
| Reports | Same helper; filter projects/reports by derived FY |
| Limitation | Need clear definition: FY from `commencement_month_year`, approval date, or report period |

### Recommendation

- **Option C (Derived FY)** for initial implementation — no schema change, works with existing dates
- **Option A** if a stored FY is required for audit/performance — add `approval_financial_year` or `primary_financial_year` to `projects` and backfill

---

## 7. Implementation Roadmap

If financial year support is to be added:

### Phase 1: Foundation (No Schema Change)

1. **`FinancialYearHelper`**
   - `fromDate(Carbon $date): string` → e.g. `"2024-25"`
   - `startDate(string $fy): Carbon` → 1 Apr
   - `endDate(string $fy): Carbon` → 31 Mar
   - `listAvailable(): array` → e.g. `["2022-23", "2023-24", "2024-25"]`

2. **Derived FY for projects**
   - From `commencement_month_year` or approval timestamp (from status history or similar)
   - Apply in queries via scope or subquery

### Phase 2: Dashboard Filter Logic

1. **Add FY filter to dashboards**
   - Coordinator, General, Provincial: dropdown for FY
   - Default: current FY (1 Apr–31 Mar)
   - Filter projects where derived FY = selected FY

2. **Update aggregation**
   - `ProjectFinancialResolver` unchanged
   - Apply FY filter before summing

### Phase 3: Role-Based Visibility

- Existing role scoping remains
- FY filter applied after role scoping
- No change to `ProjectAccessService` or `ProjectPermissionHelper`

### Phase 4: Reporting and Exports

1. **Budget Report (`BudgetExportController`)**
   - Add FY filter option
   - Filter by derived FY from `commencement_month_year` or approval date

2. **Project List**
   - Add FY filter alongside existing `start_date`/`end_date`

3. **Half-yearly/Annual reports**
   - `half_yearly_reports.year` and similar can be interpreted as FY end year; document and use consistently

---

## 8. Implementation Complexity

**Medium**

- No mandatory schema change if using derived FY
- Multiple dashboards and reports to update
- Clear FY helper and consistent definition reduce risk
- Backfill and optional schema (Option A) increase effort if adopted

---

## 9. Final Verdict

### ARCHITECTURE UPDATE REQUIRED

**Summary**

- Budget is stored **per project only** (PROJECT_ONLY); no financial year in schema
- Financial year support is effectively **NONE**; one ad-hoc reference, not India FY compliant
- Project date structure is **SUFFICIENT** to derive FY from `commencement_month_year` (or approval date)
- Dashboards use **MIXED** financial model: role-based scoping, optional `created_at` date filters, **no FY filter**
- Role visibility is **MIXED**: project-level, region-level, and global

**To support a financial-year–based dashboard:**

1. Introduce `FinancialYearHelper` (or equivalent) for India FY (1 Apr–31 Mar)
2. Add FY filter to relevant dashboards (Coordinator, General, Provincial)
3. Add FY filter to Budget Report and Project List
4. Define and apply FY derivation consistently (commencement vs approval date)
5. Optionally add `approval_financial_year` to `projects` for auditability and performance

---

## Appendix: Tables Summary

| Table | Budget-Related Columns | FY Column |
|-------|------------------------|-----------|
| `projects` | `overall_project_budget`, `amount_sanctioned`, `amount_forwarded`, `opening_balance` | None |
| `project_budgets` | `this_phase`, `next_phase`, `rate_*` | None |
| `project_ige_budgets` | `amount_requested`, `total_amount` | None |
| `project_iah_budget_details` | `amount`, `amount_requested` | None |
| `project_ilp_budgets` | `amount_requested`, `cost` | None |
| `half_yearly_reports` | `amount_sanctioned_overview` | `year` (report year) |
| `quarterly_reports` | `amount_sanctioned_overview` | None |
| `annual_reports` | `amount_sanctioned_overview` | `year` (report year) |

---

*Audit completed in read-only mode. No code was modified.*
