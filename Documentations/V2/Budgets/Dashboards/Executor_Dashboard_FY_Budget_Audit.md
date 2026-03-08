# Executor Dashboard FY Budget — Forensic Audit Report

**Date:** 2026-03-04  
**Scope:** Executor dashboard — why Executor users cannot see correct "Total Budget / Total Funds" when filtering by Financial Year (FY).  
**Method:** Static analysis only. No application code was modified; no refactors or automated fixes.

---

## 1️⃣ Executor Dashboard Architecture

### Entry point

- **File:** `app/Http/Controllers/ExecutorController.php`
- **Method:** `executorDashboard(Request $request)`
- **Route:** `GET /executor/dashboard` → `executor.dashboard`

### FY initialization

```php
$fy = $request->input('fy', FinancialYearHelper::currentFY());
```

- **Verified:** FY is read from request with default `FinancialYearHelper::currentFY()` (e.g. "2025-26" for India FY April 1 → March 31).

### Queries executed (summary)

| Purpose | Query / service | FY applied? |
|--------|-----------------|-------------|
| Owned projects base (list + FY filter) | `ProjectQueryService::getOwnedProjectsQuery($user)` then `->inFinancialYear($fy)` | Yes |
| In-charge projects (list) | `ProjectQueryService::getInChargeProjectsQuery($user)` then `->inFinancialYear($fy)` | Yes |
| **Budget summary dataset** | `ProjectQueryService::getApprovedOwnedProjectsForUser($user, $with, $fy)` | **Yes** |
| Owned count (badge) | `ProjectQueryService::getOwnedProjectsQuery($user)->count()` | **No** |
| In-charge count (badge) | `ProjectQueryService::getInChargeProjectsQuery($user)->count()` | **No** |
| Project types for filters | `getOwnedProjectsQuery($user)->inFinancialYear($fy)->distinct()->pluck('project_type')` | Yes |
| Chart data (Budget Analytics widget) | `getChartData($user, $request)` → internally `getApprovedOwnedProjectsForUser($user, [...])` | **No** |
| Quick Stats widget | `getQuickStats($user)` → internally `getApprovedOwnedProjectsForUser($user, [...])` | **No** |
| Action items / Upcoming deadlines | `getApprovedOwnedProjectsForUser($user)` (no FY) | **No** |

### Services used

- `ProjectQueryService`: getOwnedProjectsQuery, getInChargeProjectsQuery, getApprovedOwnedProjectsForUser, getOwnedProjectIds, applySearchFilter.
- `FinancialYearHelper`: currentFY(), listAvailableFY().
- `ProjectFinancialResolver` (via `calculateBudgetSummariesFromProjects`, `enhanceProjectsWithMetadata`, getChartData, getQuickStats).
- `DerivedCalculationService` (via resolver and controller calculations).

### Data passed to view

- **FY-related:** `$fy`, `$availableFY`.
- **Budget:** `$budgetSummaries` (from `calculateBudgetSummariesFromProjects($approvedProjectsForSummary->all(), $request)`).
- **Projects:** `$ownedProjects` (paginated), `$inChargeProjects` (paginated), `$enhancedOwnedProjects`, `$enhancedInChargeProjects`, `$enhancedFullOwnedProjects`.
- **Counts:** `$ownedCount`, `$inChargeCount` (unfiltered by FY).
- **Widgets:** `$chartData`, `$reportChartData`, `$quickStats`, `$projectHealthSummary`, `$projectChartData`, `$actionItems`, `$reportStatusSummary`, `$upcomingDeadlines`, etc.

**Conclusion:** Budget summary is correctly driven by FY via `getApprovedOwnedProjectsForUser(..., $fy)`. Section header counts and Quick Stats / Budget Analytics are **not** FY-scoped.

---

## 2️⃣ Executor Project Dataset (User 37 Example)

### Query definition

Projects for Executor user 37:

- **Owned:** `user_id = 37`.
- **In-charge:** `in_charge = 37` (and `user_id != 37` for in-charge-only).

Status filter for "approved":

- `approved_by_coordinator`
- `approved_by_general_as_coordinator`
- `approved_by_general_as_provincial`

### Dataset (from existing audit)

| project_id | user_id | in_charge | commencement_month_year | FY (derived) | opening_balance | amount_sanctioned |
|------------|---------|-----------|-------------------------|--------------|-----------------|-------------------|
| DP-0002    | 28      | 37        | 2025-10-01              | 2025-26      | (empty)         | 1428000.00        |
| DP-0004    | 27      | 37        | 2026-01-01              | 2025-26      | 595500.00       | 595500.00         |
| DP-0017    | **37**  | 26        | 2025-01-01              | **2024-25**  | (empty)         | 1412000.00        |
| DP-0024    | 27      | 37        | 2026-01-01              | 2025-26      | 1040000.00      | 775000.00         |
| DP-0025    | 27      | 37        | 2026-01-01              | 2025-26      | 1830000.00      | 1100000.00        |
| DP-0041    | **37**  | 29        | 2026-06-01              | **2026-27**  | 630000.00       | (empty)           |
| IIES-0060  | **37**  | 144       | 2026-06-01              | **2026-27**  | 16000.00        | (empty)           |

FY derived with: `FinancialYearHelper::fromDate(Carbon::parse(commencement_month_year))`.

### Ownership and FY (owned only)

- **Owned by user 37:** DP-0017 (FY 2024-25), DP-0041 (FY 2026-27), IIES-0060 (FY 2026-27).
- **In-charge only:** DP-0002, DP-0004, DP-0024, DP-0025 (all in FY 2025-26).

Budget summary uses **owned only**; in-charge projects are **excluded** from Total Budget / Total Funds.

For default FY **2025-26**, `getApprovedOwnedProjectsForUser($user, ..., '2025-26')` returns **0** projects for user 37 → empty dataset → Total Budget = 0.

---

## 3️⃣ FY Derivation Results

- **Current FY (e.g. 2026-03-04):** `FinancialYearHelper::currentFY()` = **2025-26**.
- **Scope:** `Project::scopeInFinancialYear($fy)`:
  - `whereNotNull('commencement_month_year')`
  - `whereBetween('commencement_month_year', [$start->format('Y-m-d'), $end->format('Y-m-d')])`
  - Bounds: `FinancialYearHelper::startDate($fy)` → 1 Apr, `endDate($fy)` → 31 Mar 23:59:59.

Projects with `commencement_month_year` **null** are excluded from FY-filtered lists and from the budget summary dataset.

---

## 4️⃣ Resolver Output Verification

For each **owned** project, `ProjectFinancialResolver::resolve($project)` returns (for approved) `opening_balance = (float) ($project->opening_balance ?? 0)` (canonical separation in `applyCanonicalSeparation`).

| project_id | FY       | DB opening_balance | Resolver opening_balance |
|------------|----------|--------------------|---------------------------|
| DP-0017    | 2024-25  | null               | 0                         |
| DP-0041    | 2026-27  | 630000.00          | 630000                    |
| IIES-0060  | 2026-27  | 16000.00           | 16000                     |

- **Resolver does not force zero** when DB has a value; for approved projects it uses `$project->opening_balance`.
- **DP-0017:** Resolver returns 0 because DB `opening_balance` is null (data issue; `amount_sanctioned` is set).
- **Conclusion:** Resolver logic is correct. Incorrect Total Budget is not caused by resolver returning zero for projects that have DB values; it is caused by **which projects** are in the aggregation set (FY filter + owned-only).

---

## 5️⃣ Budget Aggregation Analysis

### Where totals are calculated

- **Method:** `ExecutorController::calculateBudgetSummariesFromProjects($projects, $request)` (private).
- **Called with:** `$approvedProjectsForSummary->all()` where `$approvedProjectsForSummary = ProjectQueryService::getApprovedOwnedProjectsForUser($user, $with, $fy)`.

### Aggregation logic (excerpt)

```php
foreach ($projects as $project) {
    $financials = $resolver->resolve($project);
    $projectBudget = (float) ($financials['opening_balance'] ?? 0);
    // ... expenses from reports ...
    $budgetSummaries['total']['total_budget'] += $projectBudget;
    // ... by_project_type and other keys ...
}
```

- **Field aggregated for Total Budget:** Resolver `opening_balance` only (not raw `amount_sanctioned` or `overall_project_budget`).
- **Order:** FY filtering is applied **before** aggregation (in `getApprovedOwnedProjectsForUser(..., $fy)`). Aggregation runs on the already FY-filtered collection.

**Conclusion:** Aggregation correctly uses resolver `opening_balance` and runs on the FY-scoped owned-approved set. If the set is empty for the selected FY (e.g. user 37 and 2025-26), Total Budget is 0 by design.

---

## 6️⃣ Blade Partial Audit

### Views and variables

| View | Responsibility | Variables used for Total Budget / Funds |
|------|----------------|----------------------------------------|
| `resources/views/executor/index.blade.php` | Main dashboard; includes widgets and project lists | Passes `$budgetSummaries`, `$fy`, `$availableFY` |
| `resources/views/executor/widgets/project-budgets-overview.blade.php` | **Total Budget**, Approved/Unapproved Expenses, Total Remaining | `$budgetSummaries['total']['total_budget']`, `total_remaining`, etc. |
| `resources/views/executor/widgets/quick-stats.blade.php` | Quick Stats card | `$quickStats['total_budget']` (not from `$budgetSummaries`) |
| `resources/views/executor/widgets/budget-analytics.blade.php` | Budget Analytics charts | `$chartData['total_budget']` (not from `$budgetSummaries`) |

### FY-filtered data in blade

- **Project Budgets Overview** receives `$budgetSummaries` from the controller, which is computed from `getApprovedOwnedProjectsForUser(..., $fy)`. So the main "Total Budget" and "Total Remaining" in that widget **are** FY-filtered.
- **Quick Stats** and **Budget Analytics** receive `$quickStats` and `$chartData`; these are built in the controller from `getApprovedOwnedProjectsForUser($user, ...)` **without** `$fy`, so they show **all-year** totals and can disagree with the FY-selected Project Budgets Overview.

### Project Budgets Overview form and FY

- The widget has a filter form (Project Type, Apply/Reset) that submits to `route('executor.dashboard')`.
- The form does **not** include a hidden `fy` input (only `show` when present).
- **Effect:** If the user has selected an FY in the main filters and then uses "Apply Filters" or "Reset" in the Project Budgets Overview widget, the request may be sent **without** `fy`, so the dashboard falls back to default FY and the displayed Total Budget can appear to "change" or "reset" incorrectly.

---

## 7️⃣ FY Selector Implementation Review

### Presence in UI

- **Location:** Main dashboard filters in `resources/views/executor/index.blade.php` (collapsible "Filters" section).
- **Markup:** `<select name="fy" id="fy" class="form-select" onchange="this.form.submit()">` with `@foreach($availableFY ?? [] as $year)` and `option value="{{ $year }}"`, selected when `($fy ?? '') == $year`.
- **Controller:** Passes `$fy` and `$availableFY` in `compact(...)`.

### Source of dropdown values

- **Method:** `FinancialYearHelper::listAvailableFY(int $yearsBack = 10)`.
- **Implementation:** Builds a list from **current FY** backward: `currentFY()` then `(currentFY start year - i)` for `i = 0..9`. No database query; no project data.
- **Result:** e.g. 2025-26, 2024-25, …, 2016-17. **Does not include future FY** (e.g. 2026-27).

### Gap

- User 37 has **owned** projects in **2026-27** (DP-0041, IIES-0060). They cannot select 2026-27 in the dropdown, so they cannot see Total Budget for that FY in the UI.
- Correct architecture (per audit requirement) would derive available FY from project data (e.g. distinct FY from projects with non-null `commencement_month_year`) so that any FY in which the user has projects can be selected.

---

## 8️⃣ Cache Layer Verification

- **Search:** `cache()->remember`, `Cache::` in `ExecutorController.php`.
- **Result:** No cache usage in the Executor dashboard controller.
- **Conclusion:** Incorrect Total Funds is **not** caused by cache keys omitting FY or cache contamination.

---

## 9️⃣ Root Cause Analysis

### Why Executor users see incorrect Total Budget / Total Funds when filtering by FY

**Primary cause: Empty dataset for the selected FY (owned projects only).**

1. Budget summary uses **only owned** projects: `getApprovedOwnedProjectsForUser($user, ..., $fy)`.
2. If the executor has **no owned approved projects** in the selected FY (e.g. user 37 and 2025-26), the set is empty → Total Budget = 0.
3. In-charge projects are **intentionally** excluded from the budget summary; they appear in the "Assigned Projects (In-Charge)" table but do not contribute to Total Budget.

**Contributing cause: FY dropdown does not include all relevant FYs.**

- `listAvailableFY()` is static (current + past 10 years). It does **not** include the **next** FY (e.g. 2026-27).
- Executors with projects starting in the next FY cannot select that FY and therefore cannot see the correct Total Budget for that year.

**Contributing cause: Project Budgets Overview form drops FY.**

- The widget’s filter form does not preserve `fy`. Submitting that form can clear the FY parameter and revert to default FY, making the displayed Total Budget appear wrong relative to the user’s intended FY.

**Contributing cause: Inconsistent FY scope across widgets.**

- Project Budgets Overview: FY-filtered (correct).
- Quick Stats and Budget Analytics: use `getApprovedOwnedProjectsForUser($user, ...)` without `$fy` → all-year totals. Users may perceive a mismatch between "Total Budget" in the overview and totals in other widgets.

**Secondary (data):** Projects with approved status but **null** `opening_balance` (e.g. DP-0017) contribute 0 to Total Budget even when selected FY contains them; resolver correctly reflects DB. Fix is data population, not resolver logic.

**Ruled out:**

- Resolver returning zero when DB has value: not observed; approved path uses `$project->opening_balance`.
- Aggregation using wrong field: aggregation uses resolver `opening_balance`.
- Cache: not used in Executor dashboard.
- Missing FY on main project list: main list and budget summary both use the same `$fy` from the request.

---

## 🔟 Recommended Fix Strategy

(Recommendations only; no code was modified in this audit.)

1. **Default FY when no owned projects in current FY**  
   If the user has no owned approved projects in the default (current) FY but has some in other FYs, either:
   - Default to the **latest FY in which the user has at least one owned approved project**, or  
   - Keep current FY but show an explicit message: e.g. "No owned projects in FY 2025-26. Select another FY to see budget."

2. **Available FY from project data**  
   - Derive dropdown options from projects (e.g. distinct FY from `commencement_month_year` via `FinancialYearHelper::fromDate()`), optionally restricted to owned/visible projects, so that 2026-27 (and any other FY with projects) appears when relevant.  
   - Alternatively, extend `listAvailableFY()` to include the **next** FY so that upcoming-year projects are selectable.

3. **Preserve FY in Project Budgets Overview form**  
   - When the widget form is submitted, preserve the current FY (e.g. hidden input `fy` with value `{{ request('fy', $fy ?? '') }}`) so that applying Project Type filter or Reset does not clear the FY selection.

4. **Align Quick Stats and Budget Analytics with FY**  
   - Pass `$fy` into `getChartData($user, $request, $fy)` and `getQuickStats($user, $fy)` (or equivalent) and use `getApprovedOwnedProjectsForUser($user, ..., $fy)` so that these widgets show the same FY as the Project Budgets Overview.

5. **Optional: FY-scoped section counts**  
   - Consider making "My Projects (Owned)" and "Assigned Projects (In-Charge)" badges reflect the selected FY (e.g. count only projects in `$fy`) so that counts match the listed rows and the selected FY.

6. **Data fix for projects with null opening_balance**  
   - For approved projects where `amount_sanctioned` is set but `opening_balance` is null, populate `opening_balance` per business rules so that resolver and Total Budget reflect the intended value.

7. **Product decision: in-charge in Total Budget**  
   - If product intent is to include in-charge projects in "Total Budget / Total Funds," the aggregation dataset would need to include both owned and in-charge (with clear rules). This is a product/design decision, not a resolver or FY-filter bug.

---

**Audit performed without modifying any application code. Findings are from static code inspection, existing documentation (including Dashboard_User37_Budget_Zero_Audit.md), and the described data and pipeline behaviour.**
