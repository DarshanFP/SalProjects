# Executor Project List Filters Implementation

**Date:** 2026-03-05  
**Pages:** `/executor/projects` (Pending) and `/executor/projects/approved` (Approved)

---

## 1. Filters Added

### Financial Year
- **Parameter:** `fy`
- **Source:** `FinancialYearHelper::listAvailableFYFromProjects()` using `getProjectsForUserQuery($user)`
- **Fallback:** `FinancialYearHelper::listAvailableFY()` when no project dates exist
- **Behavior:** When selected, restricts projects to those whose `commencement_month_year` falls within the chosen FY
- **Option:** "All Financial Years" (empty value) shows projects regardless of FY

### My Role
- **Parameter:** `role` (default: `owned_and_in_charge`)
- **Values:**
  - `owned` → Owner / Executor
  - `in_charge` → In-Charge / Applicant
  - `owned_and_in_charge` → All My Projects
- **Backend:** Resolves base query via `ProjectQueryService`:
  - `owned` → `getOwnedProjectsQuery($user)`
  - `in_charge` → `getInChargeProjectsQuery($user)`
  - `owned_and_in_charge` → `getProjectsForUserQuery($user)`

---

## 2. New Column

### Commencement Month/Year
- **Header:** Commencement
- **Value:** `commencement_month_year` formatted as `M Y` (e.g. Apr 2024) via `Carbon::parse()`
- **Empty:** Displays `—` when null
- **Placement:** Between Project Type and Role columns

---

## 3. Query Architecture

- **Builder-based:** Both `index()` and `approvedProjects()` now use Eloquent Builder instead of Collection
- **Flow:**
  1. Resolve base query from `role` (owned / in_charge / owned_and_in_charge)
  2. Apply status filter: `notApproved()` (pending) or `whereIn('status', ProjectStatus::APPROVED_STATUSES)` (approved)
  3. Apply FY filter: `inFinancialYear($fy)` when `$fy` is provided
  4. Eager load: `with(['user', 'objectives', 'budgets'])`
  5. Order by `project_id`
  6. Paginate 15 per page with `appends($request->query())` to preserve `fy` and `role`

---

## 4. Pagination

- **Size:** 15 records per page
- **Persistence:** Query parameters `fy` and `role` are preserved across page links via `->appends($request->query())`
- **View:** `{{ $projects->links() }}` below the table in both Blade files

---

## 5. UI Enhancements

- **Filter form:** Above the table, using `dashboard-controls` and `dashboard-select` classes for consistency with executor dashboard
- **Role labels:**
  - Owner / Executor
  - In-Charge / Applicant
  - All My Projects
- **Apply button:** Triggers GET submit
- **Role badges:** Remain unchanged (Executor, Applicant)
- **Empty state:** "No pending projects found." / "No approved projects found." when filters return no results

---

## 6. Result

Executor project pages now support:

- **Financial Year filtering** — Restrict by project commencement FY
- **Role filtering** — Owner, In-Charge, or both
- **Project commencement visibility** — Commencement column in table
- **Scalable pagination** — 15 per page with filter persistence

---

## Files Modified

| File | Changes |
|------|---------|
| `app/Http/Controllers/Projects/ProjectController.php` | Added `FinancialYearHelper` import; refactored `index()` and `approvedProjects()` with Request, role/FY logic, query builder, pagination, FY dropdown data |
| `resources/views/projects/Oldprojects/index.blade.php` | Filter form, Commencement column, `@forelse` empty state, pagination links |
| `resources/views/projects/Oldprojects/approved.blade.php` | Filter form, Commencement column, pagination links, colspan 7 for empty state |
