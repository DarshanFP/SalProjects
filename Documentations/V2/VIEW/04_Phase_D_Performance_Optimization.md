# Phase D — Performance Optimization

**Rule:** Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.

---

## 1. Objective

Reduce performance risks identified in the audit:
1. Cache or reduce repeated `getAccessibleUserIds` calls (24+ per provincial request)
2. Add eager loading for `inChargeUser` where needed
3. Review and enforce indexes on `projects.user_id`, `in_charge`, `status`, `province_id`
4. Evaluate large `whereIn` improvements (subquery or indexed scope)

---

## 2. Scope (Exact Files Involved)

| File | Change |
|------|--------|
| `app/Services/ProjectAccessService.php` | Add request-level cache for `getAccessibleUserIdsForProvincial` |
| `app/Http/Controllers/ProvincialController.php` | Pass cached IDs or use service that caches |
| `app/Http/Controllers/Projects/ExportController.php` | Eager load `inChargeUser` if used |
| `database/migrations/xxxx_add_project_access_indexes.php` | **New** — indexes if missing |
| `tests/Feature/Projects/PerformanceOptimizationTest.php` | **New** — assert no N+1, cache hit |
| `config/project_access.php` | **Optional** — cache TTL config |

---

## 3. What Will NOT Be Touched

- Access logic (no behavior change)
- Route or controller method signatures
- Blade templates
- Phase A/B/C access behavior

---

## 4. Pre-Implementation Checklist

- [ ] Phase C complete (ProjectAccessService exists)
- [ ] Database index audit: verify existing indexes on projects
- [ ] Profiling or query log from provincial project list to confirm 24+ getAccessibleUserIds calls
- [ ] Decision: use Laravel `Cache::remember` vs request-level (e.g. `app()->instance`) for IDs

---

## 5. Failing Tests to Write First

Create `tests/Feature/Projects/PerformanceOptimizationTest.php`:

```php
// Test 1: Provincial project list does not call getAccessibleUserIds more than N times
public function test_provincial_project_list_limits_accessible_user_id_calls(): void

// Test 2: Project show does not cause N+1 on user/inChargeUser
public function test_project_show_no_n_plus_one(): void

// Test 3: Cache is used for getAccessibleUserIds when called multiple times in same request
public function test_accessible_user_ids_cached_per_request(): void
```

---

## 6. Step-by-Step Implementation Plan

### Step D.1 — Request-level cache for getAccessibleUserIdsForProvincial

**File:** `app/Services/ProjectAccessService.php`

**Approach:**
```php
public static function getAccessibleUserIdsForProvincial(User $provincial): \Illuminate\Support\Collection
{
    $key = 'project_access_accessible_ids_' . $provincial->id;
    return cache()->remember($key, 60, function () use ($provincial) {
        // existing logic
    });
}
```

**Alternative (request-only, no cross-request cache):**
```php
$requestKey = 'project_access_ids_' . $provincial->id;
if (!app()->has($requestKey)) {
    app()->instance($requestKey, static::computeAccessibleUserIds($provincial));
}
return app($requestKey);
```

**Recommendation:** Use request-level first (no stale cache risk). Add configurable Redis/array cache later if needed.

---

### Step D.2 — Reduce calls in ProvincialController

**File:** `app/Http/Controllers/ProvincialController.php`

- Call `ProjectAccessService::getAccessibleUserIdsForProvincial($provincial)` once at start of each method; store in variable `$accessibleUserIds`.
- Pass `$accessibleUserIds` to any sub-methods or closures instead of re-calling.
- Ensure `projectList`, `provincialDashboard`, report lists, `showProject`, etc. all use the same cached value per request.

---

### Step D.3 — Eager load inChargeUser where needed

**File:** `app/Http/Controllers/Projects/ExportController.php`

- If ExportController needs in_charge user (e.g. for PDF content), add `'user', 'inChargeUser'` to `->with([...])` in downloadPdf and downloadDoc.
- Verify Project model has `inChargeUser` relationship; if not, add:
  ```php
  public function inChargeUser() {
      return $this->belongsTo(User::class, 'in_charge');
  }
  ```

---

### Step D.4 — Add migration for indexes

**File:** `database/migrations/2026_02_23_xxxx_add_project_access_indexes.php`

**Indexes to add (if not exist):**
- `projects_user_id` (may already exist as FK)
- `projects_in_charge` (if not indexed)
- `projects_status` (if filtered often)
- `projects_province_id` (audit mentioned migration 2026_02_18 adds province_id index; verify)

**Check existing migrations first;** only add what is missing.

---

### Step D.5 — Evaluate large whereIn

- If `$accessibleUserIds` can grow to hundreds, consider subquery:
  ```php
  $query->where(function ($q) use ($provincial) {
      $sub = User::where('parent_id', $provincial->id)->whereIn('role', ['executor','applicant'])->select('id');
      $q->whereIn('user_id', $sub)->orWhereIn('in_charge', $sub);
  });
  ```
- Benchmark before/after; apply only if query time is unacceptable.

---

## 7. Code Refactor Notes

- Cache key must be unique per user; consider adding session/request id if needed for isolation.
- Avoid cache in testing: use `Cache::flush()` in tearDown or mock.

---

## 8. Performance Impact Analysis

- **Positive:** Fewer DB calls for accessible user IDs.
- **Positive:** Indexes speed up project list and filter queries.
- **Neutral:** Eager load adds one join; usually faster than N+1.
- **Risk:** Cache invalidation — if user's hierarchy changes mid-request, cached IDs may be stale. Request-level cache avoids this.

---

## 9. Security Impact Analysis

- **No impact:** Same access logic; only performance optimization.
- Ensure cache key cannot be manipulated to leak another user's IDs.

---

## 10. Rollback Strategy

1. Revert Phase D commits.
2. Remove cache logic from ProjectAccessService.
3. Revert index migration: `php artisan migrate:rollback`.
4. Document in `Phase_D_Rollback_Report.md`.

---

## 11. Deployment Checklist

- [ ] All Phase D tests pass
- [ ] Migration runs successfully on staging
- [ ] No regression in access behavior
- [ ] Query log/profile shows reduced calls
- [ ] Production deploy
- [ ] Monitor query time and error rate 24h

---

## 12. Post-Deployment Validation Steps

1. Provincial project list: measure response time (should be same or better).
2. Check logs for cache-related errors.
3. Verify indexes exist: `SHOW INDEX FROM projects`.

---

## 13. Regression Test List

- [ ] All Phase A, B, C tests
- [ ] Provincial project list returns correct data
- [ ] Coordinator project list unchanged
- [ ] No N+1 in project show

---

## 14. Sign-Off Criteria

- Cache in place; call count reduced
- Indexes added if missing
- No behavioral change
- Phase D completion MD updated

---

## Cursor Execution Rule

When implementing this phase, update this MD file with:
- Actual code changes
- File diffs summary
- Test results
- Any deviations from plan
- Date of implementation
- Engineer name
