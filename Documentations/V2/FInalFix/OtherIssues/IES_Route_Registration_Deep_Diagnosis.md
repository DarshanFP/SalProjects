# IES Route Registration Deep Diagnosis

**Error:** `Route [projects.ies.attachments.view] not defined`  
**Date:** 2026-02-16  
**Mode:** Read-only investigation. No code modified, no cache cleared, no fixes applied.

---

## 1. Route Definition In Source

| Item | Value |
|------|--------|
| **File path** | `routes/web.php` |
| **Line numbers** | 478 (download), 479 (view) |
| **Full route definition (view)** | `Route::get('/projects/ies/attachments/view/{fileId}', [IESAttachmentsController::class, 'viewFile'])->name('projects.ies.attachments.view');` |
| **Full route definition (download)** | `Route::get('/projects/ies/attachments/download/{fileId}', [IESAttachmentsController::class, 'downloadFile'])->name('projects.ies.attachments.download');` |
| **Middleware group** | `Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator,general'])->group(function () { ... });` |
| **Prefix group** | None. No `Route::prefix(...)` wrapping this block. |
| **Name prefix** | None. No `Route::name(...)` wrapping this block. |
| **Actual runtime name (by source)** | `projects.ies.attachments.view` and `projects.ies.attachments.download` |

The IES routes sit in a plain middleware group with no prefix or name prefix; the names in source are the intended runtime names.

---

## 2. Runtime Route List Findings

**Command run (read-only):** `php artisan route:list` then filtered for routes containing `ies` or `attachments`.

**Result:**

| URI | Name | Present? |
|-----|------|----------|
| `projects/attachments/download/{id}` | `projects.attachments.download` | Yes |
| `projects/iies/attachments/download/{fileId}` | `projects.iies.attachments.download` | Yes |
| `projects/iies/attachments/view/{fileId}` | `projects.iies.attachments.view` | Yes |
| **`projects/ies/attachments/download/{fileId}`** | **`projects.ies.attachments.download`** | **No** |
| **`projects/ies/attachments/view/{fileId}`** | **`projects.ies.attachments.view`** | **No** |

When the application uses the route cache (`bootstrap/cache/routes-v7.php`), the IES routes do **not** appear in the registered route list. IIES routes do. So at runtime, `route('projects.ies.attachments.view', $id)` fails because that name is not registered.

---

## 3. Route Cache Status

| Check | Result |
|-------|--------|
| **Cached route file** | Present: `bootstrap/cache/routes-v7.php` |
| **`projects.ies.attachments.view` in cache?** | **No** |
| **`projects.ies.attachments.download` in cache?** | **No** |
| **`projects.iies.attachments.view` in cache?** | Yes (line 3429, 17202) |
| **`projects.iies.attachments.download` in cache?** | Yes (line 3406, 17161) |

In the cached file, the shared project-attachment block has this order:

- `projects.list`
- `projects.downloadPdf`
- `projects.downloadDoc`
- `projects.attachments.download`
- **→ next in cache is `projects.iies.attachments.download`** (no IES, no IAH)

So the cache was built from a version of `web.php` that did **not** include the IES (or IAH) attachment routes. Current `web.php` has IES at 478–479 and IAH at 481–482, but those entries are missing from `routes-v7.php`. **Conclusion: route cache is outdated relative to `web.php`.**

---

## 4. Name Prefix Analysis

- The group containing the IES routes is:
  - `Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator,general'])->group(function () { ... });`
- There is **no** `Route::prefix(...)` or `Route::name(...)` around this group.
- So the runtime name is **not** mutated to `executor.projects.ies.attachments.view` or anything else; the intended name is `projects.ies.attachments.view`.
- **Conclusion:** No name prefix mismatch. The Blade uses the correct name; the name is simply not in the compiled route map when cache is used.

---

## 5. Environment Analysis

- **Route loading:** `app/Providers/RouteServiceProvider.php` loads `routes/web.php` unconditionally via `Route::middleware('web')->group(base_path('routes/web.php'));`. No `app()->environment(...)` or `config(...)` guard around this.
- **Conditional loading:** No `if (app()->environment(...))` or similar in `routes/web.php` around the IES block. The IES routes are in the same group as `projects.list` and IIES; they are always loaded when the file is executed.
- **Conclusion:** When Laravel uses **uncached** routes, `web.php` is executed and the IES routes would be registered. When Laravel uses the **cached** routes file, `web.php` is not executed and only what is in the cache is registered—and the cache does not contain the IES routes. So the issue is **cache vs source**, not environment-specific conditional loading.

---

## 6. IES vs IIES Comparison

| Aspect | IES (`projects.ies.attachments.*`) | IIES (`projects.iies.attachments.*`) |
|--------|------------------------------------|--------------------------------------|
| In `web.php` | Yes (lines 477–479) | Yes (lines 485–487) |
| In route cache | **No** | Yes |
| In `route:list` (with cache) | **No** | Yes |
| Blade route name | `projects.ies.attachments.view` | `projects.iies.attachments.view` (different type) |
| Controller | IESAttachmentsController | IIESAttachmentsController |

So IIES “works” (resolves) and IES “fails” when the app runs from cache because **only IIES (and the older shared routes) were present in the route set at the time the cache was last built**. IES and IAH were added to `web.php` after that. The difference is not naming or Blade usage; it is **presence vs absence in the cached route list**.

---

## 7. Root Cause Conclusion

**Classification: A) Route cache outdated**

- The route **exists in source** (`routes/web.php`, lines 478–479) with the correct name and no prefix/name mutation.
- The route **does not exist** in `bootstrap/cache/routes-v7.php`.
- When the application runs with the cached route file (e.g. after `php artisan route:cache` or in an environment that uses the cache), Laravel never loads `web.php` and only serves routes from the cache. So `projects.ies.attachments.view` and `projects.ies.attachments.download` are never registered, and `route('projects.ies.attachments.view', $file->id)` in the Blade throws `RouteNotFoundException`.
- Not B (name prefix mismatch), C (group not loaded—the group is in web.php and would load if cache were not used), D (duplicate override), E (Blade uses correct name), or F (deployment mismatch) as primary cause—though F can apply in the sense that deployment may have run `route:cache` from an older revision.

---

## 8. Safe Fix Strategy (NO CODE APPLIED)

Recommended actions for the maintainer (no changes applied in this diagnosis):

1. **Rebuild route cache from current source**  
   In the environment where the error occurs (and where route cache is enabled), run:
   - `php artisan route:clear`
   - `php artisan route:cache`
   so that the compiled routes include the IES (and IAH) routes from the current `web.php`.

2. **Verify after rebuild**  
   Run `php artisan route:list` and confirm that `projects.ies.attachments.view` and `projects.ies.attachments.download` appear. Then load an IES project show/edit page and confirm the View/Download links work.

3. **Deployment**  
   Ensure deployments that use route caching run `route:cache` **after** deploying the version of `web.php` that contains the IES (and IAH) routes, so the cache is never generated from an older file.

4. **Optional**  
   If the app does not require route caching for performance, avoid using `route:cache` so that `web.php` is always loaded and new routes are never missing from the map.

No code, config, or cache was modified during this investigation.
