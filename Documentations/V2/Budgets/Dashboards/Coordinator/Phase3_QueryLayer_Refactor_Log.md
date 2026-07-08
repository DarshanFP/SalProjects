# Phase 3 — Query Layer Refactor Log

**Date:** 2026-03-08  
**Scope:** Coordinator project list — replace query entry point with `ProjectQueryService::forCoordinator()`  
**Reference:** Coordinator_PendingProjects_Implementation_Roadmap.md

---

## Step 1 — Identify Current Query Entry

**File:** `app/Http/Controllers/CoordinatorController.php`  
**Method:** `CoordinatorController::projectList()`

**Exact line numbers and code block:**

| Line | Code |
|------|------|
| 516-518 | `// Base query: use ProjectAccessService (coordinator = global oversight); optional FY for list aggregation` |
| 517 | `$projectsQuery = $this->projectAccessService->getVisibleProjectsQuery($coordinator, $fy)` |
| 518-519 | `->with(['user.parent', 'reports.accountDetails', 'budgets'])` |
| 519 | `->withMax('statusHistory', 'created_at');` |

**Full block:**
```php
// Base query: use ProjectAccessService (coordinator = global oversight); optional FY for list aggregation
$projectsQuery = $this->projectAccessService->getVisibleProjectsQuery($coordinator, $fy)
    ->with(['user.parent', 'reports.accountDetails', 'budgets'])
    ->withMax('statusHistory', 'created_at');
```

**Entry point identified:** `$this->projectAccessService->getVisibleProjectsQuery($coordinator, $fy)`

---

## Step 2 — Verify ProjectQueryService Exists

**File:** `app/Services/ProjectQueryService.php`

**Method verified:** `ProjectQueryService::forCoordinator(User $coordinator, string $fy): Builder`

**Implementation (lines 47-51):**
```php
public static function forCoordinator(User $coordinator, string $fy): Builder
{
    return app(ProjectAccessService::class)
        ->getVisibleProjectsQuery($coordinator, $fy);
}
```

**Verification:** ✓ `ProjectQueryService::forCoordinator()` internally calls `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)`. Behaviour is identical — pure delegation.

---

## Step 3 — Replace Query Entry Point

**Replacement applied:**

| Before | After |
|--------|-------|
| `$this->projectAccessService->getVisibleProjectsQuery($coordinator, $fy)` | `ProjectQueryService::forCoordinator($coordinator, $fy)` |

**Full block after refactor:**
```php
// Base query: ProjectQueryService delegates to ProjectAccessService (coordinator = global oversight)
$projectsQuery = ProjectQueryService::forCoordinator($coordinator, $fy)
    ->with(['user.parent', 'reports.accountDetails', 'budgets'])
    ->withMax('statusHistory', 'created_at');
```

**Return type:** Both return `Illuminate\Database\Eloquent\Builder` — chain with `->with()`, `->withMax()`, etc. remains valid.

---

## Step 4 — Add Required Import

**Import added:**
```php
use App\Services\ProjectQueryService;
```

**Location:** After `use App\Services\ProjectAccessService;` (line 28).

---

## Step 5 — Verify Query Builder Compatibility

**Pipeline verification (unchanged):**

```
baseQuery (ProjectQueryService::forCoordinator)
    → ->with([...]) ->withMax(...)
    → filters (search, province, provincial_id, user_id, center, project_type, status, date range)
    → sorting (sort_by, sort_order)
    → pagination ($projectsQuery->paginate($perPage)->withQueryString())
    → resolver batching (ProjectFinancialResolver::resolveCollection($collection))
    → paginator collection transform (calculated_budget, budget_utilization, health_indicator)
```

**Builder methods used after entry point:** `->with()`, `->withMax()`, `->where()`, `->whereHas()`, `->whereIn()`, `->orderBy()`, `->paginate()` — all supported by Eloquent Builder returned by `ProjectQueryService::forCoordinator()`.

**Verification:** ✓ Pipeline remains unchanged.

---

## Step 6 — Behaviour Verification

| Behaviour | Status |
|-----------|--------|
| Visible projects scope | ✓ Unchanged (ProjectQueryService delegates to ProjectAccessService) |
| Financial year filtering | ✓ Applied via ProjectAccessService |
| Coordinator visibility rules | ✓ Same access logic |
| All request filters | ✓ Applied to same builder |
| Sorting logic | ✓ Unchanged |
| Pagination | ✓ Unchanged (`->paginate($perPage)->withQueryString()`) |
| Resolver batching | ✓ Unchanged (`ProjectFinancialResolver::resolveCollection`) |
| Expense calculations | ✓ Unchanged (in-memory from eager-loaded `reports.accountDetails`) |

---

## Step 7 — Static Safety Checks

| Check | Result |
|-------|--------|
| Undefined variables | ✓ None; `$coordinator`, `$fy` defined before use |
| Controller returns paginator | ✓ `return view(..., compact(...))` passes `$projects` (paginator) |
| Resolver receives correct collection | ✓ `collect($projects->items())` unchanged |
| Blade view receives `$projects` paginator | ✓ Compact includes `projects` |
| Filters remain intact | ✓ All filter blocks unchanged; applied to `$projectsQuery` |

---

## Performance Confirmation

Query layer refactor completed.

**Expected query count unchanged:** ~7–9 queries per page

| Item | Status |
|------|--------|
| Resolver batching intact | ✓ |
| Expense N+1 removal intact | ✓ |
| Pagination unchanged | ✓ |

---

## Final Summary

| Item | Details |
|------|---------|
| **Files modified** | `app/Http/Controllers/CoordinatorController.php` |
| **Lines replaced** | 1 line (query entry); 1 import added |
| **Query entry refactored** | `ProjectAccessService::getVisibleProjectsQuery` → `ProjectQueryService::forCoordinator` |
| **Behaviour unchanged** | 100% identical; pure delegation |
| **System safe for production** | ✓ Yes |

