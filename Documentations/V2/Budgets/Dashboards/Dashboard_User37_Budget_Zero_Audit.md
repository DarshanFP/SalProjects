# Dashboard User 37 — Total Budget Rs. 0.00 — Forensic Audit Report

**Date:** 2026-03-04  
**Scope:** Executor dashboard; User ID = 37  
**Objective:** Determine why the dashboard shows **Total Budget: Rs. 0.00** for a user who owns approved projects in FY 2025-26 and FY 2026-27.  
**Method:** Database queries, query simulation, resolver verification, pipeline trace. **No application code was modified.**

---

## 1️⃣ User Project Data Snapshot

### Query

Projects where `user_id = 37` OR `in_charge = 37`, and status in:

- `approved_by_coordinator`
- `approved_by_general_as_coordinator`
- `approved_by_general_as_provincial`

### Result: 7 projects

| project_id | user_id | in_charge | commencement_month_year | FY (derived) | opening_balance | amount_sanctioned |
|------------|---------|-----------|-------------------------|--------------|-----------------|-------------------|
| DP-0002    | 28      | 37        | 2025-10-01              | 2025-26      | (empty)         | 1428000.00        |
| DP-0004    | 27      | 37        | 2026-01-01             | 2025-26      | 595500.00       | 595500.00         |
| DP-0017    | **37**  | 26        | 2025-01-01             | **2024-25**  | (empty)         | 1412000.00        |
| DP-0024    | 27      | 37        | 2026-01-01             | 2025-26      | 1040000.00      | 775000.00         |
| DP-0025    | 27      | 37        | 2026-01-01             | 2025-26      | 1830000.00      | 1100000.00        |
| DP-0041    | **37**  | 29        | 2026-06-01             | **2026-27**  | 630000.00       | (empty)           |
| IIES-0060  | **37**  | 144       | 2026-06-01             | **2026-27**  | 16000.00        | (empty)           |

FY derived with: `FinancialYearHelper::fromDate(Carbon::parse(commencement_month_year))`.

### Ownership split

- **Owned by user 37** (`user_id = 37`): **3 projects** — DP-0017, DP-0041, IIES-0060.
- **In-charge only** (`in_charge = 37`, `user_id != 37`): **4 projects** — DP-0002, DP-0004, DP-0024, DP-0025.

### FY distribution of **owned** projects

| FY       | Owned projects                          |
|----------|-----------------------------------------|
| 2024-25  | DP-0017 (commencement 2025-01-01)       |
| 2025-26  | **None**                                |
| 2026-27  | DP-0041, IIES-0060 (commencement 2026-06-01) |

**Finding:** User 37 has **no owned projects** in FY **2025-26**. All owned projects are in 2024-25 (1) and 2026-27 (2).

---

## 2️⃣ FY Derivation Results

- **Current date (audit):** 2026-03-04 → `FinancialYearHelper::currentFY()` = **2025-26**.
- FY range for 2025-26: **2025-04-01** to **2026-03-31** (inclusive).
- `scopeInFinancialYear` uses:
  - `whereNotNull('commencement_month_year')`
  - `whereBetween('commencement_month_year', [$start->format('Y-m-d'), $end->format('Y-m-d')])`
- Stored values (e.g. `2025-01-01`, `2026-06-01`) are in `Y-m-d` form; FY derivation and range comparison are consistent.

---

## 3️⃣ Dashboard Query Simulation

### Simulated query (owner OR in-charge, approved, current FY)

```php
Project::approved()
    ->where(function ($q) {
        $q->where('user_id', 37)->orWhere('in_charge', 37);
    })
    ->inFinancialYear(FinancialYearHelper::currentFY());
```

- **Result:** **4 records** (DP-0002, DP-0004, DP-0024, DP-0025 — all in-charge, none owned).

### Executor dashboard: owned-only query

Budget summary uses **owned projects only** (no in-charge):

- `ProjectQueryService::getApprovedOwnedProjectsForUser($user, $with, $fy)`
- Which uses `getOwnedProjectsQuery($user)` → `user_id = 37` only, then `->inFinancialYear($fy)`.

Simulated:

```php
Project::query()->where('user_id', 37)
    ->whereIn('status', [...approved...])
    ->inFinancialYear('2025-26');
```

- **Result:** **0 records.**

**Conclusion:** For default FY **2025-26**, the dataset passed to the budget aggregation is **empty** because user 37 has **no owned projects** in that FY. Zero records → Total Budget = 0.

---

## 4️⃣ Resolver Output Verification

For each **owned** project, `ProjectFinancialResolver::resolve($project)` was run (with relations loaded).

| project_id | FY       | commencement_month_year | DB opening_balance | Resolver opening_balance |
|------------|----------|--------------------------|--------------------|--------------------------|
| DP-0017    | 2024-25  | 2025-01-01               | null               | 0                        |
| DP-0041    | 2026-27  | 2026-06-01               | 630000.00          | 630000                   |
| IIES-0060  | 2026-27  | 2026-06-01               | 16000.00           | 16000                    |

- Resolver uses **canonical separation**: for approved projects it returns `opening_balance = (float) ($project->opening_balance ?? 0)`.
- **DP-0017:** DB `opening_balance` is null → resolver returns 0 (data issue: `amount_sanctioned` = 1412000 but `opening_balance` not set).
- **DP-0041, IIES-0060:** Resolver output matches DB; no resolver bug for these.

**Conclusion:** Resolver behaviour is correct. For user 37, the only reason Total Budget can be 0 is that the **set of projects** passed to aggregation is empty for the selected FY (2025-26), not that the resolver returns wrong values for the projects that do exist in other FYs.

---

## 5️⃣ Aggregation Pipeline Analysis

### Flow

1. **Controller:** `ExecutorController::executorDashboard(Request $request)`
   - `$fy = $request->input('fy', FinancialYearHelper::currentFY());` → default **2025-26**.
2. **Dataset:**
   - `$approvedProjectsForSummary = ProjectQueryService::getApprovedOwnedProjectsForUser($user, [...], $fy);`
   - For `$fy = '2025-26'` this returns **0** projects (no owned projects in 2025-26).
3. **Aggregation:**
   - `$budgetSummaries = $this->calculateBudgetSummariesFromProjects($approvedProjectsForSummary->all(), $request);`
   - Iterates over `$projects`; for 0 projects, loop does nothing; `total_budget` stays 0.
4. **View:**
   - `resources/views/executor/widgets/project-budgets-overview.blade.php` displays `$budgetSummaries['total']['total_budget']` → **Rs. 0.00**.

### Aggregation field verification

- `calculateBudgetSummariesFromProjects()` uses **resolver** output only:
  - `$financials = $resolver->resolve($project);`
  - `$projectBudget = (float) ($financials['opening_balance'] ?? 0);`
- It does **not** use raw `amount_sanctioned` or `overall_project_budget` for the total.
- **Conclusion:** Aggregation correctly uses **resolver `opening_balance`**. The zero total is due to the **empty project set** for the chosen FY, not wrong field usage.

### ProjectAccessService

- **Executor dashboard does not use** `getVisibleProjectsQuery()`.
- It uses `ProjectQueryService::getApprovedOwnedProjectsForUser($user, $with, $fy)` with `$fy` passed.
- No missing FY propagation in this path.

---

## 6️⃣ FY Dropdown Implementation Audit

### Source

- Controllers use: `$availableFY = FinancialYearHelper::listAvailableFY();`
- **Implementation:** `FinancialYearHelper::listAvailableFY(int $yearsBack = 10)`:
  - Builds list from **current FY** and **previous 9 years**.
  - Does **not** use project data; no `SELECT DISTINCT FY FROM projects`.
  - Formula: `currentFY()` then `(currentFY start year - i)` for `i = 0..9` → e.g. 2025-26, 2024-25, …, 2016-17.

### List at audit time

- `listAvailableFY()`: 2025-26, 2024-25, 2023-24, 2022-23, 2021-22, 2020-21, 2019-20, 2018-19, 2017-18, 2016-17.
- **Contains 2024-25:** yes.  
- **Contains 2026-27:** **no** (next FY is not included).

### Design vs requirement

- **Current design:** Config-driven, last 10 years from current FY (no DB query).
- **Stated requirement:** Dropdown from project data, e.g. distinct FY from projects with non-null `commencement_month_year`, derived via `FinancialYearHelper::fromDate()`.
- **Gap:** User 37’s owned projects in **2026-27** cannot be selected because 2026-27 is not in the dropdown. So even though they have budget in 2026-27, they cannot switch FY to see it.

---

## 7️⃣ View Layer FY Selector Check

- **Executor dashboard:** `resources/views/executor/index.blade.php` (main filters around 248–330).
  - **FY selector present:** Yes.
  - Markup: `<select name="fy" id="fy" ...>` with `@foreach($availableFY ?? [] as $year)` and `option value="{{ $year }}"`, selected when `($fy ?? '') == $year`.
  - Form submits to `route('executor.dashboard')`; controller receives `fy` and uses it for `getApprovedOwnedProjectsForUser(..., $fy)` and project lists.
- **Project Budgets Overview widget:** `resources/views/executor/widgets/project-budgets-overview.blade.php`.
  - Has **Project Type** filter only; **no FY selector** in the widget.
  - Budget totals are driven by the **main page** FY (from the main filter form); the widget does not need a separate FY field if the page already submits `fy`.

**Conclusion:** Controller passes `$fy` and `$availableFY`; the view renders the FY selector in the main dashboard form. No missing FY in the view for the executor dashboard.

---

## 8️⃣ Root Cause Analysis

### Why does the dashboard show Total Budget = Rs. 0.00 for user 37?

**Primary cause: FY filtering returns an empty dataset for the default FY.**

1. **Default FY:** `$fy = $request->input('fy', FinancialYearHelper::currentFY());` → **2025-26** when no `fy` is sent.
2. **Budget dataset:** Only **owned** projects are used: `getApprovedOwnedProjectsForUser($user, ..., $fy)` with `user_id = 37`.
3. **Owned projects by FY:**
   - 2024-25: 1 (DP-0017)
   - 2025-26: **0**
   - 2026-27: 2 (DP-0041, IIES-0060)
4. So for default **2025-26**, the aggregation receives **0 projects** → Total Budget = 0.

**Contributing factor: FY dropdown does not include 2026-27.**

- User 37 has **two owned projects** in **2026-27** with non-zero resolver `opening_balance` (646000 total).
- `listAvailableFY()` only offers current and past years; it does **not** include **2026-27**.
- So the user **cannot** select 2026-27 in the UI to see that budget.

**Secondary (data) note: DP-0017 in 2024-25**

- If the user selects **2024-25**, they get one owned project (DP-0017).
- DB `opening_balance` for DP-0017 is **null** (while `amount_sanctioned` = 1412000).
- Resolver correctly returns `opening_balance = 0` for that project.
- So even in 2024-25, Total Budget would show 0 unless `opening_balance` is populated in the DB (data fix, not code bug).

### Ruled out

- **Resolver:** Returns correct values where DB has `opening_balance`; no bug for DP-0041 / IIES-0060.
- **Aggregation field:** Uses resolver `opening_balance`; not raw DB fields.
- **getVisibleProjectsQuery / missing FY:** Not used on executor dashboard; FY is passed where it matters.
- **View missing FY selector:** FY selector exists and is wired to the same request/controller that drives the budget summary.

---

## 9️⃣ Recommended Fix Strategy

(Recommendations only; no code was modified in this audit.)

1. **Immediate UX fix — default FY when no owned projects in current FY**  
   - If `getApprovedOwnedProjectsForUser($user, [], currentFY())` returns 0 and the user has owned projects in other FYs, consider:
     - Defaulting to the **latest FY in which the user has at least one owned approved project**, or
     - Keeping current FY but making the “no projects in this FY” state explicit (e.g. message: “No owned projects in FY 2025-26. Select another FY to see budget.”).

2. **Dropdown includes FYs where user has projects (or next FY)**  
   - Either:
     - Extend `listAvailableFY()` to include **next** FY (e.g. 2026-27), so users can see budget for projects starting in the next FY, or
     - Derive dropdown from **project data**: e.g. distinct FY from projects with non-null `commencement_month_year` (and optionally restrict to user’s visible/owned projects), so 2026-27 appears when user 37 has projects there.

3. **Data fix for DP-0017**  
   - If DP-0017 is approved and `amount_sanctioned` is set, consider populating `opening_balance` per business rules so that resolver (and thus 2024-25 budget) shows the intended value when the user selects 2024-25.

4. **Optional: in-charge projects in budget summary**  
   - Currently the **budget summary** is **owned-only**. If product intent is to include in-charge projects in “Total Budget,” then the dataset for aggregation would need to include both owned and in-charge (with a clear definition of which amounts to sum). This is a product/design decision, not a bug fix.

---

**Audit performed without modifying any application code. All findings are from database queries, query simulation, resolver runs, and static code/view inspection.**
