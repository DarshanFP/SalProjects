# Dynamic Financial Year Selector — Feasibility Audit

**Date:** 2026-03-04  
**Objective:** Verify feasibility of replacing static FY lists with data-driven FY lists derived from project data.  
**Method:** Static analysis only. No code was modified.

---

## 1. Current FY Generation

### 1.1 FinancialYearHelper::listAvailableFY()

**Location:** `app/Support/FinancialYearHelper.php` (lines 90–105)

**Signature:** `public static function listAvailableFY(int $yearsBack = 10): array`

**Logic:**
1. Get current FY via `self::currentFY()` (e.g. "2025-26" for India FY April 1 → March 31).
2. Extract start year from current FY (e.g. 2025).
3. Loop `$i = 0` to `$yearsBack - 1`:
   - `$year = $startYear - $i`
   - Build FY string `"{$year}-{$endYearShort}"` (e.g. "2025-26", "2024-25", …).
4. Return array of FY strings, **newest first**.

**Result:** Config-driven; no database access. Returns a fixed window (e.g. last 10 FYs including current).

### 1.2 Where listAvailableFY() is used

| File | Line | Usage |
|------|------|-------|
| ExecutorController | 173 | `$availableFY = FinancialYearHelper::listAvailableFY();` |
| CoordinatorController | 181 | `$availableFY = FinancialYearHelper::listAvailableFY();` |
| ProvincialController | 254 | `$availableFY = FinancialYearHelper::listAvailableFY();` |
| GeneralController | 193 | `$availableFY = FinancialYearHelper::listAvailableFY();` |
| FinancialYearHelperTest | 83 | `FinancialYearHelper::listAvailableFY(5)` (unit test) |

All four dashboard controllers call `listAvailableFY()` with no arguments (default 10 years) and pass the result to views as `$availableFY`.

---

## 2. Controllers Using FY Dropdown

| Controller | Method | Variable passed | View(s) receiving |
|------------|--------|-----------------|-------------------|
| ExecutorController | executorDashboard | `$availableFY`, `$fy` | executor.index |
| CoordinatorController | (dashboard) | `$availableFY`, `$fy` | coordinator.widgets.system-budget-overview |
| ProvincialController | provincialDashboard | `$availableFY`, `$fy` | provincial.index |
| GeneralController | generalDashboard | `$availableFY`, `$fy` | general.index |

Each controller assigns `$availableFY = FinancialYearHelper::listAvailableFY()` and includes it in the view data. The selected FY comes from `$request->input('fy', FinancialYearHelper::currentFY())` or equivalent.

---

## 3. Project Data Analysis

### 3.1 projects table — commencement_month_year

**Source migration:** `database/migrations/2024_07_20_085634_create_projects_table.php`

```php
$table->date('commencement_month_year')->nullable();
```

- **Column type:** `date`
- **Nullable:** Yes
- **Format:** `Y-m-d` (e.g. `2025-10-01`, `2026-06-01`)

### 3.2 Usage in scope and helpers

- **Project::scopeInFinancialYear($fy):** Uses `whereNotNull('commencement_month_year')` and `whereBetween('commencement_month_year', [$start, $end])` with `Y-m-d` bounds.
- **FinancialYearHelper::fromDate():** Accepts Carbon; parses dates in `Y-m-d` form.
- **Typical values (from audit):** `2025-10-01`, `2026-01-01`, `2025-01-01`, `2026-06-01` — all parseable as dates.

### 3.3 Null handling

- Projects with `commencement_month_year = null` are excluded by `scopeInFinancialYear` and by any query using `whereNotNull('commencement_month_year')`.
- Dynamic FY derivation must use `whereNotNull('commencement_month_year')` so nulls are skipped.

---

## 4. Dynamic FY Derivation Simulation

### 4.1 Algorithm

1. Run: `SELECT DISTINCT commencement_month_year FROM projects WHERE commencement_month_year IS NOT NULL` (optionally scoped by user/role).
2. For each value, derive FY: `FinancialYearHelper::fromDate(Carbon::parse($commencement_month_year))`.
3. Collect unique FY strings (remove duplicates).
4. Sort descending (newest first).

### 4.2 Sample derivation (User 37 example from existing audit)

**Owned projects (user_id = 37):**

| commencement_month_year | FY (fromDate) |
|-------------------------|---------------|
| 2025-01-01 | 2024-25 |
| 2026-06-01 | 2026-27 |
| 2026-06-01 | 2026-27 |

**Unique FY list (owned):** `["2026-27", "2024-25"]` (sorted descending)

**In-charge projects (in_charge = 37, user_id != 37):**

| commencement_month_year | FY (fromDate) |
|-------------------------|---------------|
| 2025-10-01 | 2025-26 |
| 2026-01-01 | 2025-26 |
| 2026-01-01 | 2025-26 |
| 2026-01-01 | 2025-26 |

**Unique FY list (in-charge):** `["2025-26"]`

**Owned + in-charge (combined):**

**Unique FY list:** `["2026-27", "2025-26", "2024-25"]`

### 4.3 Implementation sketch (pseudo-code)

```php
// Scoped query (e.g. owned for user, or accessible by provincial)
$query = Project::query()->whereNotNull('commencement_month_year');
// ... apply scope (user_id, in_charge, province_id, etc.) ...

$dates = $query->distinct()->pluck('commencement_month_year');
$fySet = collect($dates)
    ->map(fn ($d) => FinancialYearHelper::fromDate(Carbon::parse($d)))
    ->unique()
    ->sort()
    ->reverse()
    ->values()
    ->toArray();
```

---

## 5. Scope-Based FY Lists

### 5.1 FY differences by dataset

For executor user 37:

| Scope | FYs present | Notes |
|-------|-------------|-------|
| **Owned only** | 2024-25, 2026-27 | No 2025-26 (no owned projects in current FY) |
| **In-charge only** | 2025-26 | Only current FY |
| **Owned + in-charge** | 2024-25, 2025-26, 2026-27 | All three |

### 5.2 Conclusion

FY lists **do** differ by scope. A dynamic FY selector should derive the list from the same dataset used for the dashboard (owned, in-charge, or combined). Each scope can use its own project query and derive FYs from that query’s result.

### 5.3 Fallback when no projects

If the scoped query returns no projects with non-null `commencement_month_year`, the derived FY list would be empty. Fallback options:

- Merge with `listAvailableFY()` (e.g. current + past years).
- Include at least `currentFY()` so the default selection remains valid.
- Document behaviour when user has no projects.

---

## 6. Blade Compatibility

### 6.1 View usage of $availableFY

| View | Pattern |
|------|---------|
| resources/views/executor/index.blade.php | `@foreach($availableFY ?? [] as $year)` |
| resources/views/provincial/index.blade.php | `@foreach($availableFY ?? [] as $year)` |
| resources/views/general/index.blade.php | `@foreach($availableFY ?? [] as $year)` |
| resources/views/coordinator/widgets/system-budget-overview.blade.php | `@foreach($availableFY ?? [] as $year)` |

### 6.2 Requirements

- `$availableFY` must be an **array of FY strings**.
- Blade uses `$availableFY ?? []`, so an empty or null value is handled.
- Options are rendered as `value="{{ $year }}"` and `>FY {{ $year }}`.
- No fixed length or hard-coded FY values in views.

### 6.3 Conclusion

Views are **compatible** with a dynamic FY list. No view changes are needed if the controller supplies an array of FY strings. The only requirement is that the selected `$fy` is present in `$availableFY` (or handled when it is not, e.g. by falling back to `currentFY()`).

---

## 7. Performance Impact

### 7.1 Query for distinct commencement_month_year

**Example:** `SELECT DISTINCT commencement_month_year FROM projects WHERE commencement_month_year IS NOT NULL` (+ scope filters).

- **Typical result size:** Small (one row per distinct date; usually a few dozen at most).
- **Column type:** `date`; comparison and sorting are efficient.

### 7.2 Index

From migrations, there is **no explicit index** on `projects.commencement_month_year`. Indexes exist on `user_id`, `in_charge`, `province_id`, `status`, etc., but not on this column. A `DISTINCT` on an unscoped table would require a full table scan. With user/scope filters (e.g. `user_id = X` or `in_charge = X`), the planner may use other indexes to restrict rows before applying `DISTINCT`.

### 7.3 Mitigation

- Use scope filters (user_id, in_charge, province_id) before `distinct()` so the scan is limited.
- Consider adding an index on `commencement_month_year` if the projects table grows large and this query becomes slow.
- The additional query runs once per request; the rest of the work is in-memory (mapping to FY, deduplication, sorting).

### 7.4 Conclusion

The extra query is **feasible** with low impact for typical dataset sizes. If needed, an index and/or short TTL caching per user/scope can be added later.

---

## 8. Implementation Recommendation

### 8.1 Summary

| Aspect | Finding |
|--------|---------|
| Current FY list | Static; no DB; 10 years from current FY |
| Project data | `commencement_month_year` (date, nullable) is suitable for FY derivation |
| Scope-based FY | FY list differs by scope (owned / in-charge / combined) |
| Blade | Compatible with any array of FY strings |
| Performance | Extra distinct query acceptable; index optional for scale |

### 8.2 Recommendation

Introducing a dynamic FY selector is **feasible**. Suggested approach:

1. **New helper:** Add `FinancialYearHelper::listAvailableFYFromProjects(Builder $projectQuery)` or equivalent that:
   - Uses `$projectQuery->whereNotNull('commencement_month_year')->distinct()->pluck('commencement_month_year')`
   - Maps each value to FY with `fromDate(Carbon::parse(...))`
   - Returns unique FYs, sorted descending.

2. **Executor-specific:** For the executor dashboard, call this helper with the scoped project query (owned, in-charge, or both) and use the result as `$availableFY`. Other dashboards (Coordinator, Provincial, General) can keep using `listAvailableFY()` or optionally use the new helper with their scoped query.

3. **Fallback:** If the derived list is empty, merge with `listAvailableFY()` or ensure at least `currentFY()` is included so the dropdown always has a valid default.

4. **Index:** Add an index on `projects.commencement_month_year` if the projects table is large or this query becomes slow; not strictly required for initial rollout.

5. **Tests:** Add tests for the new helper (empty result, single FY, multiple FYs, scope filtering).

---

**Audit performed without modifying any application code.**
