# Phase 1.2 — Legacy Quarterly Route Authentication

**Date implemented:** 2026-06-13  
**Plan reference:** [`Reporting_System_Phase_Wise_Implementation_Plan.md`](../Reporting_System_Phase_Wise_Implementation_Plan.md) — Phase 1, Task 1.2  
**Priority:** P0 — Security  
**Status:** ✅ Implemented (pending staging verification)

---

## Problem

Five legacy quarterly report route groups (`reports/quarterly/*`) were registered **outside** any authenticated middleware group in `routes/web.php` (after the shared reports group closed at line 543).

Only global `web` middleware applied → unauthenticated users could hit quarterly CRUD URLs.

**Affected route name prefixes:**
- `quarterly.developmentProject.*`
- `quarterly.skillTraining.*`
- `quarterly.developmentLivelihood.*`
- `quarterly.institutionalSupport.*`
- `quarterly.womenInDistress.*`

---

## Solution

Wrapped all five legacy quarterly prefix groups in:

```php
Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator,general'])->group(function () {
    // ... all reports/quarterly/* routes
});
```

Role set matches aggregated report routes (`aggregated.quarterly.*`) for consistency.

---

## Files changed

| File | Change |
|------|--------|
| `routes/web.php` | Lines ~545–606: quarterly routes moved inside auth + role middleware group |

---

## Verification

After `php artisan route:clear`:

```bash
php artisan route:list --name=quarterly.developmentProject.index -v
```

**Expected middleware stack:**
- `web`
- `App\Http\Middleware\Authenticate`
- `App\Http\Middleware\Role:executor,applicant,provincial,coordinator,general`

**Manual test:**
- [ ] Visit `/reports/quarterly/development-project/list` while logged out → redirect to login
- [ ] Visit while logged in as executor → 200 (or expected app response)

---

## Notes

- If route cache was previously built (`php artisan route:cache`), run `route:clear` or rebuild cache after deploy.
- Legacy quarterly controllers still accept `society_name` from request (no snapshot) — out of scope for Phase 1; see Phase 12 in master plan.

---

## Rollback

Remove the wrapping `Route::middleware([...])->group()` and restore previous structure (not recommended — re-exposes unauthenticated access).
