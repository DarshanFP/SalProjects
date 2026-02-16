# Route Conflict Resolution — Implementation Plan

**Date:** 2026-02-12  
**Related:** `Route_Conflict_Audit_V2.md`  
**Status:** Implemented — `php artisan route:cache` succeeds

---

## 1. Overview

This document describes the implementation of route name conflict resolution to unblock `php artisan route:cache`. All changes were applied as a **controlled structural fix** without modifying middleware logic or breaking existing `route()` references where possible.

### 1.1 Problem Statement

`php artisan route:cache` failed with:

```
Unable to prepare route [coordinator/budgets/report] for serialization. 
Another route has already been assigned name [budgets.report].
```

Laravel requires **globally unique route names** for serialization. Multiple duplicates were identified and resolved.

### 1.2 Implementation Principles

- **No random renames** — only routes that caused conflicts were modified
- **No breaking changes** — `route('budgets.report')` and all other references remain valid
- **No middleware changes** — role-based access unchanged
- **Stabilization patch** — not a V2 refactor

---

## 2. Phase 1 — budgets.report Duplicate Fix

### 2.1 Problem

The route name `budgets.report` was defined twice:

| Location | URI | File:Line | Middleware |
|----------|-----|-----------|------------|
| Auth group | `/budgets/report` | web.php:109 | auth |
| Coordinator group | `/coordinator/budgets/report` | web.php:199 | auth, role:coordinator,general |

Both routes used the same controller method: `BudgetExportController::generateReport`.

### 2.2 Solution

**Remove** the duplicate coordinator route. Keep only the shared route:

- **URI:** `/budgets/report`
- **Middleware:** `auth` — all authenticated users (including coordinator and general) have access
- **Route name:** `budgets.report` (unchanged)

### 2.3 Implementation Detail

**File:** `routes/web.php`

**Before (lines 193–199):**
```php
    Route::get('/coordinator/budget-overview', [CoordinatorController::class, 'budgetOverview'])->name('coordinator.budget-overview');

    // Budget Reports
    Route::get('/coordinator/budgets/report', [BudgetExportController::class, 'generateReport'])->name('budgets.report');

    Route::get('/coordinator/projects/{project_id}/download-pdf', [ExportController::class, 'downloadPdf'])->name('coordinator.projects.downloadPdf');
```

**After:**
```php
    Route::get('/coordinator/budget-overview', [CoordinatorController::class, 'budgetOverview'])->name('coordinator.budget-overview');

    // Budget Reports — use shared /budgets/report (auth group); coordinator/general have access via role middleware

    Route::get('/coordinator/projects/{project_id}/download-pdf', [ExportController::class, 'downloadPdf'])->name('coordinator.projects.downloadPdf');
```

**Lines removed:** 3 (the entire coordinator budgets.report route definition)

### 2.4 References (Unchanged)

The following files continue to use `route('budgets.report')` without modification:

| File | Usage |
|------|-------|
| `resources/views/general/sidebar.blade.php` | `route('budgets.report')` |
| `resources/views/general/budgets/index.blade.php` | `route('budgets.report', array_merge(...))` |
| `resources/views/projects/exports/budget-report.blade.php` | `route('budgets.report')`, `route('budgets.report', array_merge(...))` |

All references resolve to `/budgets/report`. Coordinator and general users receive access via the `auth` middleware; no additional role check is required since the route is inside the auth group.

---

## 3. Phase 2 — Aggregated Compare Route Duplicates

### 3.1 Problem

Inside the groups:

- `prefix: reports/aggregated/quarterly`, `name: aggregated.quarterly.`
- `prefix: reports/aggregated/half-yearly`, `name: aggregated.half-yearly.`
- `prefix: reports/aggregated/annual`, `name: aggregated.annual.`

Both GET and POST for the compare action used the same route name:

```php
Route::get('compare', ...)->name('reports.aggregated.quarterly.compare');
Route::post('compare', ...)->name('reports.aggregated.quarterly.compare');
```

Resulting full names: `aggregated.quarterly.reports.aggregated.quarterly.compare` (GET and POST) — duplicate.

### 3.2 Solution

Differentiate GET and POST by name:

- **GET:** `->name('compare')` → `aggregated.quarterly.compare`, `aggregated.half-yearly.compare`, `aggregated.annual.compare`
- **POST:** `->name('compare.submit')` → `aggregated.quarterly.compare.submit`, `aggregated.half-yearly.compare.submit`, `aggregated.annual.compare.submit`

### 3.3 Implementation Detail

**File:** `routes/web.php`

**Quarterly (lines 584–585):**

| Before | After |
|--------|-------|
| `->name('reports.aggregated.quarterly.compare')` (GET) | `->name('compare')` |
| `->name('reports.aggregated.quarterly.compare')` (POST) | `->name('compare.submit')` |

**Half-Yearly (lines 599–600):**

| Before | After |
|--------|-------|
| `->name('reports.aggregated.half-yearly.compare')` (GET) | `->name('compare')` |
| `->name('reports.aggregated.half-yearly.compare')` (POST) | `->name('compare.submit')` |

**Annual (lines 614–615):**

| Before | After |
|--------|-------|
| `->name('reports.aggregated.annual.compare')` (GET) | `->name('compare')` |
| `->name('reports.aggregated.annual.compare')` (POST) | `->name('compare.submit')` |

**Current route definitions:**
```php
// Quarterly Report Comparison
Route::get('compare', [ReportComparisonController::class, 'compareQuarterly'])->name('compare');
Route::post('compare', [ReportComparisonController::class, 'compareQuarterly'])->name('compare.submit');

// Half-Yearly Report Comparison
Route::get('compare', [ReportComparisonController::class, 'compareHalfYearly'])->name('compare');
Route::post('compare', [ReportComparisonController::class, 'compareHalfYearly'])->name('compare.submit');

// Annual Report Comparison
Route::get('compare', [ReportComparisonController::class, 'compareAnnual'])->name('compare');
Route::post('compare', [ReportComparisonController::class, 'compareAnnual'])->name('compare.submit');
```

### 3.4 Blade Form Updates

Forms submit via POST. They were updated to use the new submit route names.

| File | Before | After |
|------|--------|-------|
| `resources/views/reports/aggregated/comparison/quarterly-form.blade.php` | `route('reports.aggregated.quarterly.compare')` | `route('aggregated.quarterly.compare.submit')` |
| `resources/views/reports/aggregated/comparison/half-yearly-form.blade.php` | `route('reports.aggregated.half-yearly.compare')` | `route('aggregated.half-yearly.compare.submit')` |
| `resources/views/reports/aggregated/comparison/annual-form.blade.php` | `route('reports.aggregated.annual.compare')` | `route('aggregated.annual.compare.submit')` |

**Example change (quarterly-form.blade.php line 15):**
```blade
{{-- Before --}}
<form method="POST" action="{{ route('reports.aggregated.quarterly.compare') }}">

{{-- After --}}
<form method="POST" action="{{ route('aggregated.quarterly.compare.submit') }}">
```

---

## 4. Phase 3 — Login and Logout Duplicate Removal

### 4.1 Problem

**Login:** `web.php` defined `GET /login` with `->name('login')`, and `auth.php` also defined `GET login` with `->name('login')`.

**Logout:** `web.php` defined `GET /logout` with `->name('logout')`, and `auth.php` defines `POST logout` with `->name('logout')`.

### 4.2 Solution

Remove the duplicate definitions from `web.php` and keep only the canonical routes in `auth.php`:

- **Login:** `auth.php` — `Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login')`
- **Logout:** `auth.php` — `Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout')`

### 4.3 Implementation Detail

**File:** `routes/web.php`

**Login removal (previously lines 48–51):**

**Before:**
```php
Route::get('/', function () {
    return view('auth.login');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/logout', function () {
```

**After:**
```php
Route::get('/', function () {
    return view('auth.login');
});

// Login route defined in routes/auth.php (single canonical definition)
// Logout route defined in routes/auth.php (POST, single canonical definition)

// Default redirect to dashboard based on role
```

**Logout removal:** The custom GET `/logout` closure was removed. The POST logout in `auth.php` is the canonical logout route and is used by all layouts (e.g. `layoutAll/header.blade.php`, `layouts/navigation.blade.php`) via form submit.

### 4.4 Rationale

- **Login:** Both `web.php` and `auth.php` returned `view('auth.login')`. `auth.php` uses `AuthenticatedSessionController@create`, which is the standard Laravel pattern. A single canonical route avoids name conflicts.
- **Logout:** Per `LOGOUT_ROUTE_FIX_PLAN.md`, POST logout is preferred for security (CSRF, session invalidation). All views already use POST; removal of the GET logout does not break any flow.

### 4.5 References (Unchanged)

- `route('login')` — used in login form and links; resolves to `auth.php` route
- `route('logout')` — used in logout forms; resolves to `auth.php` POST route

---

## 5. Phase 4 — Validation

### 5.1 Commands Run

```bash
php artisan route:clear
php artisan route:cache
```

**Result:** `Routes cached successfully.`

### 5.2 Duplicate Check

```bash
php artisan route:list 2>&1 | awk '{...}' | sort | uniq -d
```

**Result:** No output (no duplicates).

### 5.3 Fixed Routes Summary

| Route Name | URI | Method | Middleware |
|------------|-----|--------|------------|
| budgets.report | budgets/report | GET | auth |
| login | login | GET | guest |
| logout | logout | POST | auth |
| aggregated.quarterly.compare | reports/aggregated/quarterly/compare | GET | auth, role:executor,applicant,provincial,coordinator,general |
| aggregated.quarterly.compare.submit | reports/aggregated/quarterly/compare | POST | same |
| aggregated.half-yearly.compare | reports/aggregated/half-yearly/compare | GET | same |
| aggregated.half-yearly.compare.submit | reports/aggregated/half-yearly/compare | POST | same |
| aggregated.annual.compare | reports/aggregated/annual/compare | GET | same |
| aggregated.annual.compare.submit | reports/aggregated/annual/compare | POST | same |

---

## 6. Files Modified — Summary

| File | Change Type | Lines Changed |
|------|-------------|---------------|
| `routes/web.php` | Removed coordinator budgets.report route | −3 |
| `routes/web.php` | Updated 6 aggregated compare route names | 6 |
| `routes/web.php` | Removed login route | −4 |
| `routes/web.php` | Removed logout route | −5 |
| `resources/views/reports/aggregated/comparison/quarterly-form.blade.php` | Form action route name | 1 |
| `resources/views/reports/aggregated/comparison/half-yearly-form.blade.php` | Form action route name | 1 |
| `resources/views/reports/aggregated/comparison/annual-form.blade.php` | Form action route name | 1 |

**Total:** 4 files modified, ~17 lines changed.

---

## 7. Production Deployment — Files to Update

Use this checklist when deploying the route conflict fix to production. Update **only these files** on the production server.

### 7.1 File Paths (Copy-Paste)

```
routes/web.php
resources/views/reports/aggregated/comparison/quarterly-form.blade.php
resources/views/reports/aggregated/comparison/half-yearly-form.blade.php
resources/views/reports/aggregated/comparison/annual-form.blade.php
```

### 7.2 Summary by Type

| Type | Path | Change |
|------|------|--------|
| **Routes** | `routes/web.php` | Removed duplicate budgets.report; removed login/logout; renamed aggregated compare routes |
| **View** | `resources/views/reports/aggregated/comparison/quarterly-form.blade.php` | Form action → `aggregated.quarterly.compare.submit` |
| **View** | `resources/views/reports/aggregated/comparison/half-yearly-form.blade.php` | Form action → `aggregated.half-yearly.compare.submit` |
| **View** | `resources/views/reports/aggregated/comparison/annual-form.blade.php` | Form action → `aggregated.annual.compare.submit` |

### 7.3 Post-Deploy Commands (Production)

After deploying the files, run:

```bash
php artisan route:clear
php artisan route:cache
```

> **Note:** `routes/auth.php` was **not** modified. Login and logout remain defined there; only the duplicate definitions were removed from `web.php`.

---

## 8. References Unchanged (No Updates Required)

| Route Name | Referenced In | Status |
|------------|---------------|--------|
| budgets.report | general/sidebar.blade.php, general/budgets/index.blade.php, projects/exports/budget-report.blade.php | Unchanged |
| login | auth/login.blade.php, layouts | Unchanged (resolves to auth.php) |
| logout | layoutAll/header.blade.php, layouts/navigation.blade.php | Unchanged (resolves to auth.php) |

---

## 9. Related Documentation

| Document | Purpose |
|----------|---------|
| `Route_Conflict_Audit_V2.md` | Pre-implementation audit and planning |
| `Documentations/V2/LOGOUT_ROUTE_FIX_PLAN.md` | Logout duplicate analysis and fix plan |
| `Documentations/V2/AUTH/Login_Error_Discovery.md` | Login route duality |
| `Documentations/V2/ATTACHMENT_ACCESS_REVIEW.md` | Shared vs role-prefixed route naming |

---

## 10. Verification Commands

```bash
# Clear and regenerate route cache
php artisan route:clear
php artisan route:cache

# Verify no duplicate route names
php artisan route:list --json | php -r '
$j = json_decode(file_get_contents("php://stdin"), true);
foreach ($j as $r) {
  $n = $r["name"] ?? "";
  if (strpos($n, "compare") !== false || $n === "budgets.report" || $n === "login" || $n === "logout") {
    echo $n . " | " . $r["method"] . " | " . $r["uri"] . "\n";
  }
}
'
```

---

*End of implementation plan.*
