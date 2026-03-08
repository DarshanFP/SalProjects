# Phase 3 — Dashboard Control Bar Architecture

**Date:** 2026-03-05  
**Phase:** Provincial Dashboard Upgrade  
**Goal:** Convert the filter form into a standardized dashboard control bar similar to the Executor dashboard layout.

---

## Summary

The Provincial Dashboard filter form has been moved out of the Budget Overview card and into a dedicated control bar at the top of the page. Filters are aligned in a single row with labels, include a Clear Filters button, and preserve auto-submit behavior. Query string persistence is maintained.

---

## 1. Control Bar Container

**Implementation:** `resources/views/provincial/index.blade.php`

The filter form is wrapped in a standardized container:

```html
<div class="dashboard-controls card mb-4 p-3">
```

- **dashboard-controls** — Semantic class for control bar (aligns with Executor)
- **card** — Visual separation from dashboard widgets
- **mb-4 p-3** — Spacing and padding

The control bar is placed above the Budget Overview section, after success/error/warning alerts.

---

## 2. Filter Layout — Single Row

**Structure:**

```html
<div class="row g-2 align-items-end">
```

**Columns:**

| Column          | Bootstrap classes | Label          | Content                          |
|-----------------|-------------------|----------------|----------------------------------|
| FY selector     | col-md col-lg-2   | Financial Year | FY dropdown                      |
| Center selector | col-md col-lg-2   | Center         | Center dropdown                  |
| Role selector   | col-md col-lg-2   | Role           | Role dropdown                    |
| Project type    | col-md col-lg-2   | Project Type   | Project type dropdown            |
| Clear Filters   | col-md col-lg-auto| —              | Clear Filters button             |

- **g-2** — Compact gutter between columns
- **align-items-end** — Aligns labels and controls at bottom

---

## 3. Label Visibility

Each filter has an explicit label with `form-label` class:

- **Financial Year** — `for="fySelector"`
- **Center** — `for="center"`
- **Role** — `for="role"`
- **Project Type** — `for="project_type"`

Labels are associated with selects via `for` / `id` attributes for accessibility.

---

## 4. Clear Filters Button

**Implementation:**

```html
<a href="{{ route('provincial.dashboard') }}" class="btn btn-outline-secondary">Clear Filters</a>
```

- Resets all filters by navigating to the dashboard without query parameters
- Placed in the same row as filters

---

## 5. Auto-Submit Behavior

All selects submit the form on change via `onchange="this.form.submit()"`:

| Select       | Auto-submit |
|--------------|-------------|
| FY           | ✓           |
| Center       | ✓           |
| Role         | ✓           |
| Project type | ✓           |

Existing JavaScript in `@push('scripts')` also attaches a change listener to `fySelector`; redundant but harmless.

**Removed:** Apply Filters button (no longer needed with auto-submit).

---

## 6. Query String Persistence

**Form attributes:**

- `method="GET"`
- `action="{{ route('provincial.dashboard') }}"`

**Query string parameters preserved:**

- `fy` — Financial year
- `center` — Center
- `role` — Role
- `project_type` — Project type

Example URL: `?fy=2026-27&center=...&role=...&project_type=...`

All filters are in the same form, so a change to one select submits the form with current values of all fields.

---

## 7. Files Modified

| File                               | Changes |
|------------------------------------|---------|
| `resources/views/provincial/index.blade.php` | Added control bar above Budget Overview; removed filter form from inside the card |
| `resources/views/provincial/dashboard.blade.php` | No structural changes; layout already supports the control bar via `@yield('content')` |

---

## 8. Verification

| Item                        | Status |
|-----------------------------|--------|
| Control bar container       | ✓ `dashboard-controls card mb-4 p-3` |
| Filters in single row       | ✓ `row g-2 align-items-end` |
| Labels present              | ✓ Financial Year, Center, Role, Project Type |
| Clear Filters button        | ✓ `btn btn-outline-secondary` |
| Auto-submit on all selects  | ✓ `onchange="this.form.submit()"` |
| Query string persistence    | ✓ GET form with named inputs |
| Active Filters display      | ✓ Retained in Budget Overview card when filters active |

---

## 9. Layout Comparison

**Before:** Filter form inside the Budget Summary & Details card.

**After:** Dedicated control bar above all widgets, aligned with Executor dashboard layout.
