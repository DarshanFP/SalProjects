# Role-Based Sidebar Review (V1)

## Overview

This document is a **read-only technical review** of all sidebar implementations in the Laravel SalProjects application. It identifies every role-based sidebar, where they live, how they are structured, and where duplication or inconsistency exists.

**Purpose:** To provide a complete inventory and analysis before any refactor. A shared sidebar architecture would reduce duplication, centralize scroll/active-state behavior, and make it easier to keep role-specific menus consistent.

**Scope:** Identification and documentation only. No refactoring, no behavior changes, no new shared sidebar in this phase.

---

## Identified Roles

Roles are determined from:

- **Route middleware** in `routes/web.php`: `role:admin`, `role:coordinator,general`, `role:general`, `role:provincial`, `role:executor,applicant`, and combined role groups for shared routes.
- **User model:** `App\Models\User` has a `role` attribute (string) and uses Spatie Laravel Permission (`HasRoles`), so `auth()->user()->hasRole('admin')`, etc. are used in Blade.
- **Dashboard redirect:** `/dashboard` uses `$user->role` with a `match()` to send admin, general, coordinator, provincial, executor, and applicant to their respective dashboards; applicant uses the same dashboard URL as executor.

**Roles that currently have a sidebar:**

| Role       | Route / middleware              | Sidebar used                          |
|-----------|----------------------------------|----------------------------------------|
| Admin     | `role:admin`                    | `admin.sidebar`                        |
| Coordinator | `role:coordinator,general`    | `coordinator.sidebar`                  |
| General   | `role:coordinator,general`     | `general.sidebar`                      |
| Provincial| `role:provincial`               | `provincial.sidebar` (→ layouts.sidebar + partial) |
| Executor  | `role:executor,applicant`       | `executor.sidebar`                     |
| Applicant | `role:executor,applicant`       | Same as executor (`executor.sidebar`)   |

**Note:** There is no separate “Applicant” sidebar; applicants use the executor dashboard and executor sidebar.

---

## Sidebar Inventory

### 1. Admin

| Item    | Value |
|---------|--------|
| **Role** | Admin |
| **Sidebar file(s)** | `resources/views/admin/sidebar.blade.php` |
| **Type** | Full (contains `<nav>`, header, body, all menu markup) |
| **Included by** | `resources/views/admin/layout.blade.php` (all admin dashboard, projects, reports, budget-reconciliation views extend `admin.layout`). Also included by `profileAll.app` when `auth()->user()->hasRole('admin')` (e.g. notifications page). |
| **Scroll** | No. `sidebar-body` has no `sidebar-body--scrollable` class. |
| **Active state** | None. No `request()->routeIs()` or `active` class on links. |
| **Notes** | Brand link uses `route('admin.dashboard')`. Conditional sections: Budget Reconciliation (config), Impersonation (config). |

---

### 2. Coordinator

| Item    | Value |
|---------|--------|
| **Role** | Coordinator |
| **Sidebar file(s)** | `resources/views/coordinator/sidebar.blade.php` |
| **Type** | Full |
| **Included by** | `resources/views/coordinator/dashboard.blade.php`; `profileAll.app` when `hasRole('coordinator')`. |
| **Scroll** | No. Plain `sidebar-body`. |
| **Active state** | None. No route-based active class on links. |
| **Notes** | Dashboard link points to `route('admin.dashboard')` (likely bug; should be coordinator dashboard). Contains placeholder sections: Email (Inbox/Read/Compose), Calendar, Group (Health/Education/Social), Other (404/500), Docs. |

---

### 3. General

| Item    | Value |
|---------|--------|
| **Role** | General |
| **Sidebar file(s)** | `resources/views/general/sidebar.blade.php` |
| **Type** | Full |
| **Included by** | `resources/views/general/dashboard.blade.php`; `profileAll.app` when `hasRole('general')`. |
| **Scroll** | No. Plain `sidebar-body`. |
| **Active state** | Yes. Uses `request()->routeIs(...)` and `active` class on many links (dashboard, activities, notifications, team, projects, reports, budget, profile, change-password). |
| **Notes** | Brand link is `#`. Sections: Main, My Team (Coordinators, Provincial Users, Direct Team, Province, Society, Center), Projects, Reports, Budget & Finance, Documentation, Settings. |

---

### 4. Provincial

| Item    | Value |
|---------|--------|
| **Role** | Provincial |
| **Sidebar file(s)** | (1) `resources/views/provincial/sidebar.blade.php` (thin wrapper), (2) `resources/views/layouts/sidebar.blade.php` (shared container), (3) `resources/views/partials/sidebar/provincial.blade.php` (menu items only) |
| **Type** | Wrapper + shared full container + partial. Provincial is the only role using the shared `layouts.sidebar` + partial pattern. |
| **Included by** | `resources/views/provincial/dashboard.blade.php` includes `provincial/sidebar`; `profileAll.app` when `hasRole('provincial')`. |
| **Scroll** | Yes. `layouts/sidebar.blade.php` uses `sidebar-body sidebar-body--scrollable`; `public/css/custom/sidebar.css` defines scroll for `.sidebar-body--scrollable`. Provincial dashboard also links `sidebar.css`. |
| **Active state** | Yes. In `partials/sidebar/provincial.blade.php`, links use `request()->routeIs(...)` and `active` on nav-item and nav-link. |
| **Notes** | Wrapper passes `role` and `dashboardRoute` into `layouts.sidebar`; container then `@include('partials.sidebar.' . $role)`. Menu order: Main → Projects → Reports → Team Management → Notifications → Settings → User Manual. |

---

### 5. Executor (and Applicant)

| Item    | Value |
|---------|--------|
| **Role** | Executor, Applicant |
| **Sidebar file(s)** | `resources/views/executor/sidebar.blade.php` |
| **Type** | Full |
| **Included by** | `resources/views/executor/dashboard.blade.php`; `profileAll.app` when `hasRole('executor')` or `hasRole('applicant')`. |
| **Scroll** | No. Plain `sidebar-body`. |
| **Active state** | None. No route-based active class on links. |
| **Notes** | Brand link is `#`. Sections: Main, web apps (Email), Create projects (Projects), View Reports (Monthly, Quarterly, Biannual, Annual), Project Application (Individual/Group/Other placeholders), Docs. Some sub-links point to real routes; Individual/Group/Other point to static HTML or placeholders. |

---

### 6. Reports layout (role-agnostic / legacy)

| Item    | Value |
|---------|--------|
| **Role** | Not tied to a specific role in code. |
| **Sidebar file(s)** | `resources/views/reports/layout/sidebar.blade.php` |
| **Type** | Full |
| **Included by** | Only `resources/views/reports/app.blade.php`. No view in the codebase was found that `@extends('reports.app')`; this layout appears unused or legacy. |
| **Scroll** | No. Plain `sidebar-body`. |
| **Active state** | None. |
| **Notes** | Brand link is `#`. Contains typo “Project Applicatipon”. Menu: Main (Dashboard → admin.dashboard), web apps (Email, Calendar), Reports (Quarterly create routes, Biannual/Annual collapsed with no sub-links), Project Application (Individual/Group/Other placeholders), Docs. |

---

### 7. profileAll.app (role-based inclusion)

| Item    | Value |
|---------|--------|
| **Layout** | `resources/views/profileAll/app.blade.php` |
| **Behavior** | Single layout that includes one of the role sidebars via `@if (auth()->user()->hasRole(...)) @include(...)`: admin → `admin.sidebar`, coordinator → `coordinator.sidebar`, general → `general.sidebar`, provincial → `provincial.sidebar`, executor/applicant → `executor.sidebar`. |
| **Used by** | `resources/views/notifications/index.blade.php` (`@extends('profileAll.app')`). |
| **CSS** | Links `public/css/custom/sidebar.css` (scroll behavior). |

---

### 8. admin_app layout (broken or legacy)

| Item    | Value |
|---------|--------|
| **Layout** | `resources/views/profileAll/admin_app.blade.php` |
| **Sidebar reference** | `@include('admin.layout.sidebar')` — resolves to `resources/views/admin/layout/sidebar.blade.php`. |
| **Issue** | The path `admin/layout/sidebar.blade.php` does not exist. The project has `admin/sidebar.blade.php` and `admin/layout.blade.php` (which includes `admin.sidebar`), but no `admin/layout/` directory or `admin/layout/sidebar.blade.php`. Rendering a view that uses this layout would fail. |
| **Usage** | No view in the codebase was found that `@extends('profileAll.admin_app')`. |

---

## Layouts that embed sidebars (summary)

| Layout / view              | Sidebar included              | Sidebar CSS loaded |
|----------------------------|-------------------------------|--------------------|
| `admin/layout.blade.php`   | `admin.sidebar`               | No                 |
| `profileAll.app`           | Role-based (see §7)           | Yes                |
| `provincial/dashboard.blade.php` | `provincial/sidebar`     | Yes                |
| `coordinator/dashboard.blade.php` | `coordinator.sidebar`  | No                 |
| `executor/dashboard.blade.php`    | `executor.sidebar`       | No                 |
| `general/dashboard.blade.php`    | `general.sidebar`        | No                 |
| `reports/app.blade.php`    | `reports.layout.sidebar`      | No                 |

Only Provincial dashboard and profileAll.app load `sidebar.css`; admin, coordinator, executor, and general dashboards do not. Reports layout does not load it either.

---

## Structural Comparison

### Common patterns

- All sidebars use the same top-level structure: `<nav class="sidebar">` → `sidebar-header` (brand + toggler) → `sidebar-body` → `<ul class="nav">` and category/list items.
- Same CSS/JS stack (NobleUI/demo2, feather icons, template.js, etc.) and same class names for header/toggler/body.
- Role selection in the shared layout (`profileAll.app`) is done with `hasRole()` and a series of `@include`s; no single shared “sidebar container” is used for admin, coordinator, general, or executor.

### Differences

- **Provincial** is the only role using a shared container (`layouts/sidebar.blade.php`) plus a menu partial (`partials/sidebar/provincial.blade.php`). All others are self-contained full sidebars.
- **Scroll:** Only the provincial flow uses `sidebar-body--scrollable` and the custom `sidebar.css`; other role dashboards do not add the scroll class or the CSS.
- **Active state:** Only **General** and **Provincial** (in its partial) implement route-based active state. Admin, Coordinator, Executor, and Reports sidebars do not.
- **Brand link:** Admin uses `route('admin.dashboard')`; Provincial wrapper passes `route('provincial.dashboard')` into the shared layout; Coordinator incorrectly uses `route('admin.dashboard')`; General, Executor, and Reports use `#`.

### Duplication points

- **Container markup:** The same `<nav class="sidebar">` + header (brand + toggler) + `sidebar-body` block is repeated in: `admin/sidebar.blade.php`, `coordinator/sidebar.blade.php`, `general/sidebar.blade.php`, `executor/sidebar.blade.php`, `reports/layout/sidebar.blade.php`, and once in `layouts/sidebar.blade.php` (for provincial). Six places with the same structural boilerplate.
- **Scroll behavior:** Intended scroll behavior lives in `sidebar.css` and one class (`sidebar-body--scrollable`), but only provincial uses it. The same scroll logic is not applied to the other five sidebars, so behavior can differ if content overflows.
- **Active state:** Implemented in two places (general and provincial partial) with similar but not identical patterns; the other four sidebars have no active state, so behavior and UX differ by role.
- **Placeholder content:** Coordinator, Executor, and Reports sidebars contain similar placeholder items (Email, Calendar, Group Health/Education/Social, Other 404/500, Docs) with different IDs and sometimes dead links, increasing maintenance and risk of drift.

---

## Risks & Observations

### Maintenance risks

- **Six copies of sidebar structure:** Any change to container, header, or scroll behavior must be repeated in multiple files or will be inconsistent (e.g. only provincial scrolls).
- **Two implementations of active state:** General and provincial each do their own `request()->routeIs()` and `active`; others have none. Unifying or extending active state later will touch many files.
- **Legacy/broken references:** `reports.app` includes a full sidebar but no view extends it. `profileAll/admin_app` includes `admin.layout.sidebar`, which does not exist; that layout would throw when rendered.

### UX risks

- **Inconsistent active state:** Users with General or Provincial see the current page highlighted; Admin, Coordinator, and Executor do not.
- **Scroll behavior:** Long menus (e.g. General) can overflow without scroll where the dashboard does not load `sidebar.css` or use `sidebar-body--scrollable`; Provincial behaves differently.
- **Wrong link:** Coordinator sidebar “Dashboard” points to `admin.dashboard` instead of coordinator dashboard.

### Scaling risks

- Adding a new role currently means adding another full sidebar file and another branch in `profileAll.app`, plus a new dashboard layout if the role has its own dashboard. No single “sidebar shell” is reused except for provincial.
- Any future shared behavior (e.g. toggler, mobile behavior, accessibility) would need to be applied in up to six sidebar files.

---

## Next Step (Explicit)

The next phase is to introduce a **shared sidebar container** (and, if desired, a single place for scroll and active-state behavior) so that:

- Container/header/scroll logic exist once.
- Role-specific content is limited to menu items (e.g. partials or a single config-driven structure).

**No refactoring is performed in this phase.** This document is analysis and inventory only.
