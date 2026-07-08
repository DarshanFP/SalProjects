# Controller Cleanup Post Phase-3

**Date:** 2026-03-08  
**Controller:** `app/Http/Controllers/CoordinatorController.php`  
**Context:** Post Phase-3 query layer refactor (projectList uses ProjectQueryService)

---

## Step 1 — Inspect Controller Imports

**Import located at line 29:**
```php
use App\Services\ProjectAccessService;
```

**Search results for `ProjectAccessService` and `projectAccessService` across the controller:**

| Line | Reference |
|------|-----------|
| 29 | `use App\Services\ProjectAccessService;` |
| 44 | Constructor: `private readonly ProjectAccessService $projectAccessService` |
| 517 | Comment only: "ProjectQueryService delegates to ProjectAccessService" |
| 710 | `$this->projectAccessService->canViewProject($project, $coordinator)` |
| 1325-1326 | `$projects = $this->projectAccessService->getVisibleProjectsQuery($coordinator, $fy)` |
| 2657-2658 | `$projectsQuery = $this->projectAccessService->getVisibleProjectsQuery($coordinator, $fy)` |

---

## Step 2 — Determine If Import Is Unused

**Conclusion: Import is NOT unused.**

`ProjectAccessService` is used in multiple places:

| Method / Location | Usage |
|-------------------|-------|
| Constructor | Type-hint for `$projectAccessService` dependency injection |
| ~line 710 | `canViewProject($project, $coordinator)` — project view authorization |
| ~line 1326 | `getVisibleProjectsQuery()` — dashboard or another list method |
| ~line 2658 | `getVisibleProjectsQuery()` — approved projects list |

**Action:** Keep the import. Do not remove.

---

## Step 3 — Remove Unused Import

**Skipped.** Import is still required. No removal performed.

---

## Step 4 — Static Safety Check

Controller imports verified:

| Import | Status |
|--------|--------|
| `use App\Services\ProjectQueryService;` | ✓ Present (line 30) |
| `use App\Domain\Budget\ProjectFinancialResolver;` | ✓ Present (line 31) |
| `use App\Helpers\TableFormatter;` | ✓ Present (line 34) |
| `use App\Services\ProjectAccessService;` | ✓ Present (line 29) — required |

Controller compiles without errors related to these imports.

---

## Step 5 — Final Log Summary

**Controller imports:** No change required — all current imports are in use.

**Unused dependency removed:** N/A — `ProjectAccessService` remains in use.

**Controller behaviour:** Unchanged.

---

## Summary

Phase-3 refactored `projectList()` to use `ProjectQueryService::forCoordinator()`, but `ProjectAccessService` is still used by:
- Constructor dependency injection
- `canViewProject()` (authorization)
- Other methods (dashboard dataset, approved projects query)

The import for `ProjectAccessService` must be retained.
