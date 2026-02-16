# PDF & Route Fix Deployment Record

**Date:** 2026-02-12  
**Scope:** Phases 1, 2, 3 — PDF data integrity, route conflicts, export stabilization, blade hardening  
**Status:** Implementation complete

---

## 1. Summary

### 1.1 High-Level Description of Issues Fixed

| Issue | Impact | Resolution |
|-------|--------|------------|
| **IAH PDF crash** | `Attempt to read property "amount" on true` when generating PDF for Individual - Access to Health projects | `loadIAHBudgetDetails()` changed from `->first()` to `->get()`; blade guard added |
| **IGE PDF undefined variable** | IGE budget partial expected `$IGEbudget` but ExportController passed `$budget` | Controller now passes `$data['IGEbudget']` |
| **Route name duplication** | `php artisan route:cache` failed — duplicate names for `budgets.report`, aggregated compare, login, logout | Duplicate routes removed; compare POST routes renamed to `compare.submit` |
| **Data loading duplication** | ExportController and ProjectController duplicated module-loading logic | `ProjectDataHydrator` service created; ExportController uses hydrator |
| **Blade type fragility** | `@foreach` over non-Collection (boolean, null, single model) caused fatal errors | `instanceof \Illuminate\Support\Collection` guards added to all PDF partials |

### 1.2 Production Impact

- **Stability:** PDF generation for IAH and IGE project types no longer crashes.
- **Route cache:** `php artisan route:cache` succeeds; production can use route caching.
- **Maintainability:** Single data-loading path for PDF via `ProjectDataHydrator`.
- **Resilience:** Blade partials guard against unexpected types; dev-only logging aids debugging.

### 1.3 Stability Improvements

1. IAH budget details always passed as Collection.
2. IGE budget variable name matches blade expectation.
3. Route names globally unique; no serialization conflicts.
4. PDF data hydration unified; no controller duplication.
5. Type guards prevent fatal errors from null/boolean/wrong-type variables.

---

## 2. Root Cause Overview

### 2.1 Route Name Duplication

- **budgets.report:** Defined in both auth group (`/budgets/report`) and coordinator group (`/coordinator/budgets/report`).
- **Aggregated compare:** GET and POST for quarterly, half-yearly, annual used identical route names.
- **Login / Logout:** Duplicate definitions in `web.php` and `routes/auth.php`.

### 2.2 Boolean / Single Model Passed to Blade

- **IAH:** `ExportController::loadIAHBudgetDetails()` returned `->first()` (single model or null). Blade expected Collection; iterating over model attributes yielded scalar values (e.g. `true`), causing `$budget->amount` crash.
- **IGE:** Controller passed `$data['budget']`; blade partial expected `$IGEbudget`.

### 2.3 Inconsistent Data Hydration Logic

- **ProjectController:** Used injected controllers for all project types.
- **ExportController:** Used controllers for some types and internal `load*()` methods for others (IES, ILP, IAH, IIES), duplicating logic.

### 2.4 Blade Partial Expecting Collection

- Partials used `->count()`, `->isNotEmpty()`, `@foreach` on variables that could be null, boolean, or single model.
- No type checks before iteration; fatal errors when wrong type passed.

---

## 3. Phase-by-Phase Changes

### Phase 1 – Production Stabilization

| Change | File | Reason |
|--------|------|--------|
| `loadIAHBudgetDetails()` return `->get()` instead of `->first()` | `app/Http/Controllers/Projects/ExportController.php` | IAH blade expects Collection; `->first()` returned single model |
| Pass `$data['IGEbudget']` for IGE case | `app/Http/Controllers/Projects/ExportController.php` | IGE budget partial expects `$IGEbudget` |
| Add `instanceof \Illuminate\Support\Collection` guard | `resources/views/projects/partials/Show/IAH/budget_details.blade.php` | Prevent fatal if null/boolean passed |

### Phase 2 – Data Hydration Unification

| Change | File | Reason |
|--------|------|--------|
| Create `ProjectDataHydrator` service | `app/Services/ProjectDataHydrator.php` | **New file** — unified data loading for PDF |
| Inject `ProjectDataHydrator`; replace `loadAllProjectData()` call | `app/Http/Controllers/Projects/ExportController.php` | Single source for PDF data |
| Remove `loadAllProjectData()` and all private `load*()` methods | `app/Http/Controllers/Projects/ExportController.php` | Eliminate duplication |

### Phase 3 – Hardening

| Change | Category | Reason |
|--------|-----------|--------|
| Collection guards on IGE, IAH, IES, IIES, ILP, RST, CCI, Edu-RUT, LDP partials | Blade | Prevent `@foreach` over non-Collection |
| Null-safe `?? collect()` for `objectives`, `sustainabilities`, `budgets` | Blade | Prevent null on iteration |
| `logUnexpectedPdfDataTypes()` (dev-only) | ExportController | Log wrong types in local/dev; no production impact |

---

## 4. Files Modified (Production Copy Checklist)

| File Path | Type | Phase | Risk Level |
|-----------|------|-------|------------|
| `routes/web.php` | Route | Route fix | Low |
| `app/Http/Controllers/Projects/ExportController.php` | Controller | 1, 2, 3 | Medium |
| `resources/views/projects/partials/Show/IAH/budget_details.blade.php` | Blade | 1, 3 | Low |
| `resources/views/projects/partials/Show/IAH/documents.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/IAH/earning_members.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/IGE/budget.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/IGE/beneficiaries_supported.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/IGE/new_beneficiaries.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/IGE/ongoing_beneficiaries.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/IES/attachments.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/IES/estimated_expenses.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/IES/family_working_members.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/IIES/attachments.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/IIES/estimated_expenses.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/IIES/family_working_members.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/ILP/attached_docs.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/ILP/budget.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/LDP/target_group.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/RST/beneficiaries_area.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/RST/geographical_area.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/RST/target_group_annexure.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/CCI/annexed_target_group.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/Edu-RUT/annexed_target_group.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/Edu-RUT/target_group.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/attachments.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/budget.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/logical_framework.blade.php` | Blade | 3 | Low |
| `resources/views/projects/partials/Show/sustainability.blade.php` | Blade | 3 | Low |
| `resources/views/reports/aggregated/comparison/annual-form.blade.php` | Blade | Route fix | Low |
| `resources/views/reports/aggregated/comparison/half-yearly-form.blade.php` | Blade | Route fix | Low |
| `resources/views/reports/aggregated/comparison/quarterly-form.blade.php` | Blade | Route fix | Low |

---

## 5. Files Added

| File Path | Purpose |
|-----------|---------|
| `app/Services/ProjectDataHydrator.php` | Unified project data loading for PDF; replaces duplicated logic in ExportController |
| `Documentations/V2/Conflicts/IAH/PDF_DataType_Integrity_Audit.md` | Pre-implementation audit of IAH PDF crash |
| `Documentations/V2/Conflicts/Route_Conflict_Audit_V2.md` | Route conflict audit |
| `Documentations/V2/Conflicts/Route_Conflict_Implementation_Plan.md` | Route fix implementation details |
| `Documentations/V2/Conflicts/Global/PDF_Hardening_Audit.md` | Phase 3 hardening audit |
| `Documentations/V2/Conflicts/PDF_and_Route_Fix_Deployment_Record.md` | This document |

---

## 6. Files Removed

None. Logic was removed from `ExportController` (loadAllProjectData and load* methods) but no files were deleted.

---

## 7. Production Deployment Steps

1. **Backup**
   ```bash
   # Backup database and codebase before deployment
   php artisan down --refresh=15
   ```

2. **Pull latest code**
   ```bash
   git pull origin <branch>
   ```

3. **Install dependencies (if composer.json changed)**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

4. **Clear caches**
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   php artisan cache:clear
   ```

5. **Rebuild route cache**
   ```bash
   php artisan route:cache
   ```

6. **Rebuild config cache (optional)**
   ```bash
   php artisan config:cache
   ```

7. **Bring application up**
   ```bash
   php artisan up
   ```

8. **Verify PDF generation**
   - Download PDF for an IAH project.
   - Download PDF for an IGE project.
   - Confirm no errors in `storage/logs/laravel.log`.

9. **Verify aggregated compare**
   - Submit quarterly, half-yearly, and annual comparison forms.
   - Confirm POST submissions succeed.

10. **Monitor logs**
    ```bash
    tail -f storage/logs/laravel.log
    ```

---

## 8. Rollback Instructions

### 8.1 Revert to Previous Commit

```bash
git log -1 --oneline   # Note current commit
git revert <commit-hash> --no-edit
# Or, if full rollback:
git reset --hard <previous-commit-hash>
```

### 8.2 Files to Restore Manually (if needed)

If reverting selectively, restore these files from the commit before deployment:

- `routes/web.php`
- `app/Http/Controllers/Projects/ExportController.php`
- `app/Services/ProjectDataHydrator.php` (delete if reverting Phase 2)
- All blade files listed in Section 4

### 8.3 Clear Caches After Rollback

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan route:cache
```

### 8.4 Post-Rollback

- If Phase 2 is rolled back, `ExportController` must be restored to a version that includes `loadAllProjectData()` and all `load*()` methods.
- Remove `ProjectDataHydrator` from `ExportController` constructor and restore `$data = $this->loadAllProjectData($project_id)`.

---

## 9. Verification Checklist

| Check | Command / Action |
|-------|------------------|
| Route cache builds successfully | `php artisan route:cache` — expect "Routes cached successfully." |
| No duplicate route names | `php artisan route:list` — no duplicate names in output |
| IAH PDF works | Download PDF for an "Individual - Access to Health" project |
| IGE PDF works | Download PDF for an "Institutional Ongoing Group Educational proposal" project |
| Aggregated compare routes work | Submit forms at quarterly, half-yearly, annual compare URLs |
| budgets.report accessible | Navigate to `/budgets/report` as coordinator or general user |
| Login / logout functional | Log in and log out; confirm no route errors |
| No production ERROR logs | Check `storage/logs/laravel.log` for new errors post-deploy |

---

## 10. Related Documentation

| Document | Purpose |
|----------|---------|
| `Documentations/V2/Conflicts/IAH/PDF_DataType_Integrity_Audit.md` | IAH PDF crash root cause |
| `Documentations/V2/Conflicts/Route_Conflict_Implementation_Plan.md` | Route fix details |
| `Documentations/V2/Conflicts/Global/PDF_Hardening_Audit.md` | Phase 3 blade guards |
| `Documentations/V2/LOGOUT_ROUTE_FIX_PLAN.md` | Logout route analysis |

---

*End of deployment record.*
