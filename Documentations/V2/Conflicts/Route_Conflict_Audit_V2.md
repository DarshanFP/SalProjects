# Route Conflict Audit — V2 Architecture

**Date:** 2026-02-12  
**Scope:** Full audit of route naming conflicts and standardisation inconsistencies blocking `php artisan route:cache`  
**Status:** Audit + Planning — **NO CODE CHANGES APPLIED**

---

## 1. Executive Summary

`php artisan route:cache` fails with:

> Unable to prepare route [coordinator/budgets/report] for serialization. Another route has already been assigned name [budgets.report].

Laravel requires **globally unique route names** for serialization. This audit identified:

| Category | Count | Impact |
|----------|-------|--------|
| **Confirmed duplicate route names** | 1 | Blocks `route:cache` |
| **Potential duplicate route names** | 4 | Would block after first fix |
| **Role-prefix naming violations** | 3+ | Structural inconsistency |
| **Same logical feature in multiple places** | 2 | Reuse without scoping |

**Root cause:** Route names were reused across different middleware groups (auth vs coordinator) and across HTTP methods (GET vs POST) without role-prefixing or method differentiation.

**Immediate blocker:** `budgets.report` is defined twice (auth group + coordinator group) with identical names.

---

## 2. List of Duplicate Route Names

### 2.1 Confirmed Duplicates (Blocks route:cache)

| Route Name | Occurrences | File | Line | URI | Method | Middleware Group |
|------------|-------------|------|------|-----|--------|------------------|
| **budgets.report** | 2 | routes/web.php | 115 | `/budgets/report` | GET | auth |
| **budgets.report** | 2 | routes/web.php | 199 | `/coordinator/budgets/report` | GET | auth, role:coordinator,general |

**Usage:** `route('budgets.report')` is referenced in:
- `resources/views/general/sidebar.blade.php`
- `resources/views/general/budgets/index.blade.php`
- `resources/views/projects/exports/budget-report.blade.php`

### 2.2 Potential Duplicates (Would surface after fixing budgets.report)

| Route Name | Occurrences | File | Line | URI | Method | Middleware |
|------------|-------------|------|------|-----|--------|------------|
| **aggregated.quarterly.reports.aggregated.quarterly.compare** | 2 | routes/web.php | 451–452 | `/reports/aggregated/quarterly/compare` | GET, POST | auth, role:executor,applicant,provincial,coordinator,general |
| **aggregated.half-yearly.reports.aggregated.half-yearly.compare** | 2 | routes/web.php | 462–463 | `/reports/aggregated/half-yearly/compare` | GET, POST | Same |
| **aggregated.annual.reports.aggregated.annual.compare** | 2 | routes/web.php | 473–474 | `/reports/aggregated/annual/compare` | GET, POST | Same |

**Note:** These result from `->name('reports.aggregated.quarterly.compare')` etc. inside a group with `->name('aggregated.quarterly.')`. Both GET and POST share the same name; Laravel rejects this during serialization.

### 2.3 Potential Duplicate (Auth vs Web)

| Route Name | Occurrences | File | Line | URI | Method | Notes |
|------------|-------------|------|------|-----|--------|-------|
| **login** | 2 | routes/web.php | 51 | `/login` | GET | Closure |
| **login** | 2 | routes/auth.php | 20 | `login` | GET | AuthenticatedSessionController@create |

**Documented in:** `Documentations/V2/AUTH/Login_Error_Discovery.md` — both routes render the same view; registration order determines which wins. If route:cache iteration order differs from runtime, this could surface as a duplicate.

---

## 3. Phase 1 — Route Name Collision Scan (Full Table)

| Route Name | URI | Method | Middleware | File | Line | Duplicate? |
|------------|-----|--------|------------|------|------|------------|
| login | /login | GET | web | web.php | 51 | ⚠️ Yes (auth.php:20) |
| dashboard | /dashboard | GET | auth | web.php | 85 | No |
| profile.edit | /profile | GET | auth | web.php | 89 | No |
| profile.update | /profile | PATCH | auth | web.php | 90 | No |
| profile.destroy | /profile | DELETE | auth | web.php | 91 | No |
| profile.change-password | /profile/change-password | GET | auth | web.php | 92 | No |
| profile.update-password | /profile/update-password | POST | auth | web.php | 93 | No |
| notifications.index | notifications/ | GET | auth | web.php | 98 | No |
| notifications.read | notifications/{id}/read | POST | auth | web.php | 99 | No |
| notifications.mark-all-read | notifications/mark-all-read | POST | auth | web.php | 100 | No |
| notifications.destroy | notifications/{id} | DELETE | auth | web.php | 101 | No |
| notifications.unread-count | notifications/unread-count | GET | auth | web.php | 102 | No |
| notifications.recent | notifications/recent | GET | auth | web.php | 103 | No |
| notifications.preferences.update | notifications/preferences | POST | auth | web.php | 104 | No |
| projects.budget.export.excel | projects/{project_id}/budget/export/excel | GET | auth | web.php | 113 | No |
| projects.budget.export.pdf | projects/{project_id}/budget/export/pdf | GET | auth | web.php | 114 | No |
| **budgets.report** | **/budgets/report** | **GET** | **auth** | **web.php** | **115** | **🔴 Yes** |
| admin.dashboard | admin/dashboard | GET | auth, role:admin | web.php | 123 | No |
| admin.logout | admin/logout | GET | auth, role:admin | web.php | 124 | No |
| admin.activities.all | admin/activities/all | GET | auth, role:admin | web.php | 126 | No |
| admin.projects.index | admin/projects | GET | auth, role:admin | web.php | 127 | No |
| admin.projects.show | admin/projects/{project_id} | GET | auth, role:admin | web.php | 128 | No |
| admin.reports.index | admin/reports | GET | auth, role:admin | web.php | 129 | No |
| admin.reports.monthly.show | admin/reports/monthly/{report_id} | GET | auth, role:admin | web.php | 130 | No |
| admin.budget-reconciliation.* | admin/budget-reconciliation/* | various | auth, role:admin | web.php | 132–137 | No |
| coordinator.* | coordinator/* | various | auth, role:coordinator,general | web.php | 143–214 | No |
| **budgets.report** | **coordinator/budgets/report** | **GET** | **auth, role:coordinator,general** | **web.php** | **199** | **🔴 Yes** |
| general.* | general/* | various | auth, role:general | web.php | 220–261 | No |
| provincial.* | provincial/* | various | auth, role:provincial | web.php | 313–398 | No |
| executor.* | executor/* | various | auth, role:executor,applicant | web.php | 353–441 | No |
| projects.* | executor/projects/* | various | auth, role:executor,applicant | web.php | 419–440 | No |
| monthly.report.* | reports/monthly/* | various | auth, role:executor,applicant | web.php | 447–469 | No |
| projects.list | projects-list | GET | auth, role:executor,applicant,provincial,coordinator,general | web.php | 375 | No |
| projects.downloadPdf | projects/{project_id}/download-pdf | GET | auth, role:... | web.php | 479 | No |
| projects.downloadDoc | projects/{project_id}/download-doc | GET | auth, role:... | web.php | 480 | No |
| projects.attachments.download | projects/attachments/download/{id} | GET | auth, role:... | web.php | 481 | No |
| projects.iies.attachments.download | projects/iies/attachments/download/{fileId} | GET | auth, role:... | web.php | 484 | No |
| projects.iies.attachments.view | projects/iies/attachments/view/{fileId} | GET | auth, role:... | web.php | 485 | No |
| projects.activity-history | projects/{project_id}/activity-history | GET | auth, role:... | web.php | 488 | No |
| reports.activity-history | reports/{report_id}/activity-history | GET | auth, role:... | web.php | 489 | No |
| reports.attachments.download | reports/monthly/attachments/download/{id} | GET | auth, role:... | web.php | 397 | No |
| monthly.report.* | reports/monthly/* | various | auth, role:... | web.php | 399–410 | No |
| quarterly.* | reports/quarterly/* | various | auth, role:executor,applicant | web.php | 413–572 | No |
| aggregated.quarterly.* | reports/aggregated/quarterly/* | various | auth, role:... | web.php | 579–589 | No |
| aggregated.quarterly.reports.aggregated.quarterly.compare | reports/aggregated/quarterly/compare | GET, POST | auth, role:... | web.php | 451–452 | 🔴 Yes |
| aggregated.half-yearly.reports.aggregated.half-yearly.compare | reports/aggregated/half-yearly/compare | GET, POST | auth, role:... | web.php | 462–463 | 🔴 Yes |
| aggregated.annual.reports.aggregated.annual.compare | reports/aggregated/annual/compare | GET, POST | auth, role:... | web.php | 473–474 | 🔴 Yes |
| aggregated.comparison.* | reports/aggregated/comparison/* | various | auth, role:... | web.php | 623–634 | No |
| test.middleware | test-middleware | GET | auth, role:... | web.php | 641 | No |
| register | register | GET | guest | auth.php | 16 | No |
| login | login | GET | guest | auth.php | 20 | ⚠️ Yes (web.php:51) |
| password.request | forgot-password | GET | guest | auth.php | 26 | No |
| password.email | forgot-password | POST | guest | auth.php | 29 | No |
| password.reset | reset-password/{token} | GET | guest | auth.php | 31 | No |
| password.store | reset-password | POST | guest | auth.php | 34 | No |
| verification.notice | verify-email | GET | auth | auth.php | 39 | No |
| verification.verify | verify-email/{id}/{hash} | GET | auth | auth.php | 43 | No |
| verification.send | email/verification-notification | POST | auth | auth.php | 48 | No |
| password.confirm | confirm-password | GET | auth | auth.php | 51 | No |
| password.update | password | PUT | auth | auth.php | 55 | No |
| logout | logout | POST | auth | auth.php | 57 | No (web.php logout commented) |

---

## 4. Role-Based Route Pattern Analysis

### 4.1 Expected Pattern vs Actual

| Expected Pattern | Example | Status |
|------------------|---------|--------|
| coordinator.budgets.report | coordinator.budgets.report | ❌ Violated — uses `budgets.report` |
| provincial.budgets.report | N/A (provincial has no budget report) | N/A |
| general.budgets.report | N/A | N/A |

### 4.2 Routes That Violate Role-Prefixed Naming

| Route Name | URI Prefix | Should Be | Role Context |
|------------|------------|-----------|--------------|
| budgets.report | /budgets/report, /coordinator/budgets/report | coordinator.budgets.report (for coordinator path) | Reused globally |
| activities.all-activities | /activities/all-activities | coordinator.activities.all-activities | In coordinator group |
| activities.team-activities | /activities/team-activities | provincial.activities.team-activities | In provincial group |
| activities.my-activities | /activities/my-activities | executor.activities.my-activities | In executor group |
| projects.revertToProvincial | /projects/{project_id}/revert-to-provincial | coordinator.projects.revertToProvincial | In coordinator group |
| projects.approve | /projects/{project_id}/approve | coordinator.projects.approve | In coordinator group |
| projects.reject | /projects/{project_id}/reject | coordinator.projects.reject | In coordinator group |
| projects.revertToExecutor | /projects/{project_id}/revert-to-executor | provincial.projects.revertToExecutor | In provincial group |
| projects.forwardToCoordinator | /projects/{project_id}/forward-to-coordinator | provincial.projects.forwardToCoordinator | In provincial group |

### 4.3 Routes That Should Be Role-Scoped But Are Globally Named

These routes are inside role-specific middleware but use generic names:

- `projects.revertToProvincial`, `projects.approve`, `projects.reject` — coordinator-only actions
- `projects.revertToExecutor`, `projects.forwardToCoordinator` — provincial-only actions
- `projects.submitToProvincial`, `projects.markCompleted` — executor-only actions

### 4.4 Routes That May Collide During Caching

- `budgets.report` — confirmed collision
- `login` — potential collision (web.php vs auth.php)
- `aggregated.quarterly.reports.aggregated.quarterly.compare` — GET and POST share name
- `aggregated.half-yearly.reports.aggregated.half-yearly.compare` — same
- `aggregated.annual.reports.aggregated.annual.compare` — same

---

## 5. Standardisation Drift Analysis

### 5.1 Same Logical Feature Defined in Multiple Places

| Feature | Location 1 | Location 2 | Difference |
|---------|------------|------------|------------|
| Budget report export | auth group: /budgets/report | coordinator group: /coordinator/budgets/report | Same controller, same name; different paths and middleware |
| Monthly report show | reports/monthly prefix: show/{report_id} | shared reports group: show/{report_id} | Conflicting structure; shared group has no prefix |

### 5.2 Routes Differing Only by Prefix

| Role | Budget Report Path | Activity History Path |
|------|--------------------|------------------------|
| All auth | /budgets/report | N/A |
| Coordinator | /coordinator/budgets/report | /activities/all-activities |
| General | Uses coordinator path (role:coordinator,general) | Uses coordinator path |
| Provincial | N/A | /activities/team-activities |
| Executor | N/A | /activities/my-activities |

### 5.3 Legacy Route Remnants

- `Route::get('show/{report_id}', ...)->name('monthly.report.show')` at line 405 — inside a group with no prefix; resolves to `/show/{report_id}` (orphaned path).
- Commented `Route::get('/logout', ...)->name('logout')` at web.php 53–56 — documented in LOGOUT_ROUTE_FIX_PLAN.md; currently commented, so no conflict.

---

## 6. Markdown Document Review (Last 7 Days)

### 6.1 Route-Related Documentation Examined

| Document | Route Content | Implementation Alignment |
|----------|---------------|---------------------------|
| LOGOUT_ROUTE_FIX_PLAN.md | Remove custom GET logout; keep auth.php POST logout | ⚠️ Partially applied — logout is commented in web.php |
| ATTACHMENT_ACCESS_REVIEW.md | Shared routes for attachments; role middleware; generic vs role-prefixed naming | Partial; shared groups exist; no role-prefixed attachment names |
| AUTH/Login_Error_Discovery.md | Two login routes (web.php + auth.php); same view | Not aligned — both still defined |
| Societies_CRUD_And_Access_Audit.md | general.societies, provincial.societies — correct role prefix | Aligned |

### 6.2 Proposed Patterns Not Fully Implemented

- **Role-prefixed naming:** Documented in ATTACHMENT_ACCESS_REVIEW; coordinator/provincial PDF/DOC routes use prefixes; budget report does not.
- **Single logout route:** LOGOUT_ROUTE_FIX_PLAN calls for removal of custom GET logout; done via comment, but no route naming audit for other duplicates.
- **Shared group consolidation:** Attachment routes moved to shared group; budget report left in both auth and coordinator groups with same name.

### 6.3 Partial Implementation Risks

- Merging groups without renaming led to `budgets.report` being defined twice.
- Aggregated report comparison routes use full names inside prefixed groups, causing redundant names and GET/POST sharing the same name.

---

## 7. Architectural Risk Assessment

### 7.1 Why Laravel route:cache Fails

Laravel serializes the route collection to a file. Each route name must be unique because:

1. `route('name')` must resolve to a single URL.
2. The serialized map uses route names as keys.
3. Duplicate keys cause `LogicException` during serialization.

### 7.2 Structurally Unsafe Naming Strategies

| Strategy | Problem | Example |
|----------|---------|---------|
| Reusing names across role groups | Same name, different paths/middleware | budgets.report in auth + coordinator |
| Same name for GET and POST | One name, two routes | aggregated.*.compare |
| Generic names in role-scoped groups | No isolation; future collisions | projects.approve, projects.reject |
| Full name inside name-prefixed group | Redundant, error-prone | aggregated.quarterly.reports.aggregated.quarterly.compare |

### 7.3 Where Standardisation Broke

1. **Budget exports:** Auth group and coordinator group both added `budgets.report` without renaming the coordinator variant.
2. **Aggregated reports:** Compare routes use `->name('reports.aggregated.quarterly.compare')` inside `->name('aggregated.quarterly.')`; GET and POST not differentiated.
3. **Login:** Custom closure in web.php and auth scaffolding both use `login`; both kept for compatibility.

---

## 8. Recommended Naming Strategy

### 8.1 Core Principles

1. **Role prefix for role-specific routes:** `{role}.{feature}.{action}` (e.g. `coordinator.budgets.report`).
2. **Unique names for shared routes:** Single definition; middleware controls access.
3. **Method differentiation when needed:** `{name}.get`, `{name}.post`, or separate action names.
4. **Avoid full names inside prefixed groups:** Use short suffixes (e.g. `compare` → `compare.get`, `compare.post` or `compareForm`, `compareSubmit`).

### 8.2 Proposed Name Mappings

| Current | Proposed | Notes |
|---------|----------|------|
| budgets.report (auth) | budgets.report | Keep as single shared route; remove coordinator duplicate |
| budgets.report (coordinator) | REMOVE | Use shared route; coordinator/general already in auth group |
| aggregated.quarterly.reports.aggregated.quarterly.compare (GET) | aggregated.quarterly.compare | Short suffix in prefixed group |
| aggregated.quarterly.reports.aggregated.quarterly.compare (POST) | aggregated.quarterly.compare.post | Or: aggregated.quarterly.compareSubmit |
| login (web.php) | REMOVE or RENAME | Prefer single login from auth.php |
| login (auth.php) | login | Keep as canonical |

### 8.3 Shared vs Role-Specific

- **Shared:** routes used by multiple roles with same URL (e.g. projects.downloadPdf, projects.attachments.download).
- **Role-specific:** routes with role-specific paths (e.g. /coordinator/*, /provincial/*) → must use role prefix in name.

---

## 9. Proposed Safe Refactor Plan (Phased)

### Phase 1 — Unblock route:cache (Critical)

| Step | Action | Risk |
|------|--------|------|
| 1.1 | Remove `budgets.report` from coordinator group (line 199); keep auth group (line 115) | Low — general/coordinator are in auth; route resolves |
| 1.2 | Verify `route('budgets.report')` in views works for coordinator/general | Low |
| 1.3 | Run `php artisan route:cache` | — |

### Phase 2 — Fix Aggregated Report Compare Duplicates

| Step | Action | Risk |
|------|--------|------|
| 2.1 | Rename GET compare to `compare` (already in prefixed group) | Low |
| 2.2 | Rename POST compare to `compare.post` or `compareSubmit` | Low |
| 2.3 | Update blade/controller references to new names | Medium — search required |
| 2.4 | Run `php artisan route:cache` | — |

### Phase 3 — Resolve Login Duplicate

| Step | Action | Risk |
|------|--------|------|
| 3.1 | Remove `->name('login')` from web.php closure (line 51); keep auth.php login | Low |
| 3.2 | Or: remove web.php login route entirely; rely on auth.php | Low — both render same view |
| 3.3 | Verify login form `route('login')` resolves correctly | Low |

### Phase 4 — Role-Prefix Standardisation (Non-Blocking)

| Step | Action | Risk |
|------|--------|------|
| 4.1 | Rename `activities.all-activities` → `coordinator.activities.all` | Medium — references |
| 4.2 | Rename `activities.team-activities` → `provincial.activities.team` | Medium |
| 4.3 | Rename `activities.my-activities` → `executor.activities.my` | Medium |
| 4.4 | Rename project action routes (revertToProvincial, approve, etc.) with role prefix | High — many references |

### Phase 5 — Structural Cleanup

| Step | Action | Risk |
|------|--------|------|
| 5.1 | Fix `show/{report_id}` route at line 405 — ensure correct prefix | Medium |
| 5.2 | Audit route references in controllers and views | — |
| 5.3 | Add route naming guidelines to project docs | — |

---

## 10. Verification Commands

```bash
# Clear and regenerate route cache
php artisan route:clear
php artisan route:cache

# List routes by name (verify no duplicates)
php artisan route:list | grep -E 'budgets\.report|login|aggregated.*compare'

# Search for route references
grep -r "route('budgets.report')" resources/ app/
```

---

## 11. Appendix — Route Loading Order

```
RouteServiceProvider::boot()
  └── Route::middleware('web')->group(routes/web.php)
        ├── web.php: /, /login (name: login), /dashboard
        ├── web.php: profile, notifications
        ├── web.php: auth group → budgets.report (line 115)
        ├── require auth.php → login, register, logout, password.*
        ├── web.php: admin routes
        ├── web.php: coordinator routes → budgets.report (line 199) ← DUPLICATE
        ├── web.php: general routes
        ├── web.php: provincial routes
        ├── web.php: executor routes
        ├── web.php: shared projects/reports routes
        └── web.php: aggregated report routes (compare GET/POST duplicates)
```

---

*End of audit.*
