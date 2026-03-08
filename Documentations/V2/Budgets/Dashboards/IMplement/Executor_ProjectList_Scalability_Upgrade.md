# Executor Project List Scalability Upgrade

**Date:** 2026-03-05  
**Goal:** Scale project tables to 10,000+ projects without performance degradation.  
**Pages:** `/executor/projects` (Pending) and `/executor/projects/approved` (Approved)

---

## 1. Database Indexing Strategy

### Migration: `add_project_query_indexes`

Indexes added on the **projects** table:

| Index | Columns | Purpose |
|-------|---------|--------|
| Composite | `user_id`, `status` | Owned-project list: filter by owner + status (e.g. not approved / approved) |
| Composite | `in_charge`, `status` | In-charge project list: filter by in_charge + status |
| Single | `commencement_month_year` | FY filter: `inFinancialYear($fy)` range on commencement date |

### Rationale
- Executor list queries filter by **user_id or in_charge** and **status**, and optionally by **commencement_month_year**.
- Without indexes, large tables require full scans; with these indexes, the database can use index seeks and keep query time stable as rows grow.

### Rollback
- `down()` drops the three indexes so the migration is reversible.

---

## 2. Column Selection Optimization

### Before
- No explicit `select()`: all project columns were loaded.

### After
- Explicit `select()` limited to columns needed for the list view and ordering:

```php
$query->select([
    'id',
    'project_id',
    'project_title',
    'project_type',
    'status',
    'commencement_month_year',
    'user_id',
    'in_charge',
])->with(['user:id,name']);
```

### Benefits
- Less data read from the database and less memory per row.
- Only list-needed fields are loaded; heavy or unused columns (e.g. long text, many nullable fields) are excluded.

---

## 3. Cursor Pagination

### Change
- **Before:** `->paginate(15)` (offset-based).
- **After:** `->cursorPaginate(15)` (cursor-based).

### Why cursor pagination
- Offset pagination (`LIMIT 15 OFFSET n`) becomes slower as `n` grows (e.g. page 500) because the database still has to scan and skip many rows.
- Cursor pagination uses a stable condition (e.g. `project_id > ?`) so each request only fetches the next 15 rows; cost does not grow with “page number”.

### Requirements
- **Stable ordering:** `->orderBy('project_id')` is used so cursor pagination is consistent and correct.
- **Filter persistence:** `->appends($request->query())` keeps `fy` and `role` (and other query params) on next/previous links.

### UI
- Laravel’s cursor paginator provides next/previous links (no page numbers). Existing `{{ $projects->links() }}` in the Blade views works with the cursor paginator.

---

## 4. Default FY Behaviour

| Page | Default FY | Rationale |
|------|------------|-----------|
| **Pending** (`/executor/projects`) | `FinancialYearHelper::nextFY()` | Pending work is often for the upcoming FY. |
| **Approved** (`/executor/projects/approved`) | `FinancialYearHelper::currentFY()` | Approved list focuses on current FY. |

### Helper
- `FinancialYearHelper::nextFY()` added: returns the next financial year after current (e.g. 2025-26 → 2026-27).

### Behaviour
- First load: pending page uses next FY; approved page uses current FY.
- User can still choose “All Financial Years” or any FY from the dropdown; filters continue to work as before.

---

## 5. Relation Loading (Unchanged)

- **Kept:** `->with(['user:id,name'])` — minimal user data for display.
- **Not loaded:** `objectives`, `budgets` — not used on the list and would add cost at scale.

---

## 6. Expected Performance Improvement

| Area | Effect |
|------|--------|
| **Indexes** | Large reduction in query time for owner/in-charge + status + FY filters; stable as rows grow. |
| **Column select** | Fewer columns per row → less I/O and memory. |
| **Cursor pagination** | Constant-time “next page” instead of slower offsets on deep pages. |
| **Light relations** | No N+1 and no heavy joins for objectives/budgets. |

Together, these allow the executor project list pages to scale to **10,000+ projects** without proportional performance degradation.

---

## 7. Files Touched

| File | Change |
|------|--------|
| `FinancialYearHelper.php` | Added `nextFY()`. |
| `database/migrations/..._add_project_query_indexes.php` | New migration: 3 indexes on `projects`. |
| `ProjectController.php` | Default FY (nextFY/currentFY), `select()`, `cursorPaginate(15)` in `index()` and `approvedProjects()`. |

---

## 8. Verification

- **Default pending page:** Next FY + Owner/Executor.
- **Default approved page:** Current FY + Owner/Executor.
- **Dropdowns:** FY and Role filters still apply and persist.
- **Pagination:** Next/previous keeps `fy` and `role`; ordering remains `project_id`.
- **Run migration:** `php artisan migrate` to add the indexes.
