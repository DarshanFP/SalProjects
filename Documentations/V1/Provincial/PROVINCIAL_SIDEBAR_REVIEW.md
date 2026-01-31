# Provincial User Sidebar – Resolved Decisions

This document records **final, confirmed decisions** for the Provincial sidebar. All items below are resolved actions to be implemented. No further suggestions or alternatives are in scope.

---

## Resolved Decisions

### Problem 1: Dead / placeholder links

**Decision:** All placeholder and non-functional links are removed.

- **Removed completely:**
  - Web apps → Email (and its sub-items Inbox, Read, Compose)
  - Web apps → Calendar
  - Projects → Group → Health, Education, Social

- **Documentation / User Manual:**
  - Rename the link from "Documentation" to **"User Manual"**.
  - Link target: the Provincial User Manual document at  
    `Documentations/Manual Kit/Provincial_User_Manual.md`.
  - **Delivery mechanism:** The Provincial User Manual is served via a **dedicated PHP page** (Laravel route + controller + view). That page renders content based on the Markdown file above. Provincial users access the User Manual only through this PHP page. The sidebar must link to this page (not to the filesystem or raw Markdown).

- **Sidebar brand ("SAL Projects"):**
  - Link to the Provincial dashboard: `route('provincial.dashboard')`.

**State:** All placeholder links listed above were intentionally removed. No dead links remain in the Provincial sidebar.

---

### Problem 2: Duplicate / confusing sections

**Decision:**

- Remove the **"Project Application"** category entirely.
- Keep a single **"Projects"** section containing only:
  - Pending Projects
  - Approved Projects
- Remove all group-based placeholders under Projects (Health, Education, Social).

---

### Problem 3: Active state (UX)

**Decision:** Active state highlighting is **required** for all Provincial sidebar links.

- Use `request()->routeIs('provincial.*')` (and specific route names as needed) to add the `active` class to the current page’s link.
- While `request()->routeIs('provincial.*')` is the default, sidebar items such as Notifications, Profile, and Change Password may require **explicit route matching** if their routes are not prefixed with `provincial.*`.
- Users must always be able to see which page they are on.

---

### Problem 4: Reports

**Decision:** The Reports structure remains **unchanged** for this work.

- No restructuring of Reports (collapse groups, labels, or order).
- This is an intentional deferral, not a missing fix.

---

### Problem 5: Label consistency

**Decision:** Align labels with Coordinator terminology.

- **Rename:**
  - "Add my Member" → **"Add Member"**
  - "View my Member" → **"View Members"**
- All naming in the Provincial sidebar must match Coordinator terminology where the same feature exists.

---

### Problem 6: Missing expected features

**Decision:** Add the following to the Provincial sidebar:

- **Notifications** (link to notifications index; route already exists).
- **Profile** (link to profile edit; route already exists).
- **Change Password** (link to change-password; route already exists).

This is a visibility and UX fix only; no new routes or backend logic are required.
- Notifications use the **existing global notifications system**. No role-specific notification filtering or backend changes are required.

---

### Problem 7: Sidebar priority order

**Decision:** Reorder the sidebar to match Provincial workflow priority.

**Final order:**

1. Dashboard
2. Projects  
   - Pending Projects  
   - Approved Projects
3. Reports (existing structure unchanged)
4. Team Management
5. Notifications
6. Settings  
   - Profile  
   - Change Password
7. User Manual

Low-priority and non-functional sections were removed to improve focus. No placeholder sections remain between these items.
- **"Team Management"** includes Member Management (Add Member, View Members), Center Management (View Centers, Create Center), and Society Management (View Societies, Create Society) — the items already present in the Provincial sidebar under team-related collapses.

---

### Problem 8: Provincial managing provincials

**Decision:** Provincial users must **not** manage other provincials.

- **Remove from sidebar:**
  - Create Provincial
  - View Provincials
- This is a **hard access rule**: Provincial users must not see or use Provincial creation or management features. Sidebar visibility must match this rule.
- **Enforcement note:** Removal of sidebar items does **not** replace backend authorization. Existing permission checks must remain enforced at the controller or policy level.

---

### Problem 9: Sidebar hover & scroll behavior

**Decision:** The following behavior is **mandatory** for the Provincial sidebar.

- The sidebar must be **scrollable** when its content exceeds the viewport height.
- A **scrollbar** must appear and be usable on hover.
- **Scrolling must remain active** when the cursor is over submenu items (nested menus must not block scrolling).
- This ensures all menu items remain accessible on smaller screens and prevents inaccessible items at the bottom of long sidebars.
- Sidebar scroll behavior must work **consistently on mouse, trackpad, and touch-based devices**.

---

## Provincial Sidebar – Implementation Plan

### Implementation order

- **Shared sidebar architecture is implemented FIRST.** All roles use the same container, scroll behavior, and active-state rules.
- **Role-specific content is layered on top** via role-specific partials (e.g. `partials/sidebar/provincial.blade.php`) injected into the shared layout.
- **Provincial User Manual page implementation** is in a **follow-up phase** after sidebar architecture stabilization. The sidebar "User Manual" link and route `provincial.user-manual` are wired; the route may return 501 or a placeholder until the page is implemented.

---

### Shared Sidebar Architecture

This subsection describes the shared sidebar architecture. It is mandatory for all role sidebars that adopt it.

- **Centralized sidebar structure**  
  A single layout (`layouts/sidebar.blade.php`) renders: the sidebar container (`nav.sidebar`), the header (brand link + toggler), and the body. The body uses the class `sidebar-body--scrollable`. Role-specific nav items are **not** duplicated in full sidebar files; they live in partials (e.g. `partials/sidebar/provincial.blade.php`) and are included by the layout using the `$role` variable.

- **Role-based injection strategy**  
  Each role that uses the shared sidebar passes `$role` and `$dashboardRoute` to the layout. The layout includes `partials.sidebar.{role}`. No role maintains a completely separate full sidebar file with duplicated container/header/scroll logic. New roles extend the system by adding a new partial and including the shared layout with that role.

- **Active-state consistency rules**  
  Active state must be accurate for **all** roles and **all** links. Do **not** rely only on a single pattern such as `request()->routeIs('provincial.*')`. Use **explicit route matching per link** where required. Profile, Change Password, Notifications, and other shared routes (e.g. `profile.edit`, `profile.change-password`, `notifications.*`) must highlight correctly when active. Each link in role partials sets the `active` class based on the specific route(s) that correspond to that link.

- **Scroll behavior guarantees**  
  The shared sidebar body has the class `sidebar-body--scrollable`. CSS for this class must: use `overflow-y: auto` (or equivalent) so the sidebar scrolls when content exceeds viewport height; allow the scrollbar to be visible and usable on hover; ensure scrolling remains active when the cursor is over submenu items (nested menus must not block scroll); and work consistently on mouse, trackpad, and touch devices. No menu item may be unreachable due to viewport or height constraints.

---

### 1. Files to be modified

- `resources/views/layouts/sidebar.blade.php` (shared layout)
- `resources/views/partials/sidebar/provincial.blade.php` (provincial nav items)
- `resources/views/provincial/sidebar.blade.php` (includes shared layout with role + dashboardRoute)
- `public/css/custom/sidebar.css` (scroll behavior for `.sidebar-body--scrollable`)
- Any other role sidebar that later adopts the shared layout (coordinator, admin, etc.)
- **Follow-up phase only:** PHP controller and/or view for Provincial User Manual; Markdown-to-HTML logic.

### 2. Step-by-step implementation

1. **Implement shared sidebar layout**  
   Create `layouts/sidebar.blade.php` with container, header (brand from `$dashboardRoute`), toggler, and body with class `sidebar-body--scrollable`. Include `partials.sidebar.{role}` inside the nav list.

2. **Implement role partials**  
   Create `partials/sidebar/provincial.blade.php` with nav items only (no wrapper `<nav>` or duplicate header). Order: Dashboard → Projects → Reports → Team Management → Notifications → Settings → User Manual. Remove unused sections (Web apps, Project Application, Provincial Management, Group placeholders). Rename: "Add my Member" → "Add Member", "View my Member" → "View Members".

3. **Wire provincial sidebar to shared layout**  
   Replace full content of `provincial/sidebar.blade.php` with a single include of `layouts.sidebar` passing `role` => `'provincial'` and `dashboardRoute` => `route('provincial.dashboard')`.

4. **Add active state (explicit per link)**  
   For every link in the provincial partial, set the `active` class using explicit `request()->routeIs(...)` for the route(s) that link corresponds to. Use `request()->routeIs('profile.edit')`, `request()->routeIs('profile.change-password')`, `request()->routeIs('notifications.*')` for shared routes; use `request()->routeIs('provincial.user-manual')` for User Manual; use appropriate provincial.* routes for the rest.

5. **Add Notifications, Profile, and Change Password**  
   In the provincial partial: Notifications link to `route('notifications.index')`; under Settings, Profile (`route('profile.edit')`) and Change Password (`route('profile.change-password')`).

6. **Link User Manual**  
   Sidebar "User Manual" link points to `route('provincial.user-manual')`. Route exists and is wired for active state. Implementation of the User Manual page (controller/view, Markdown rendering) is **deferred** to a follow-up phase; the route may return 501 or a placeholder until then.

7. **Implement scroll behavior**  
   Add CSS for `.sidebar-body--scrollable`: `overflow-y: auto`, `overflow-x: hidden`, `-webkit-overflow-scrolling: touch`, `overscroll-behavior-y: contain`. Load this CSS where the provincial (and other role) dashboards load styles so scroll works on mouse, trackpad, and touch.

### 3. UX & CSS requirements

- The sidebar container must:
  - Use **overflow-y** (e.g. `overflow-y: auto`) to allow vertical scrolling when content exceeds viewport height.
  - Show and enable the **scrollbar on hover** (no hiding of scrollbar in a way that blocks scrolling).
  - **Preserve scroll functionality** while the user hovers over or navigates nested (collapse) menus; nested menus must not capture hover in a way that disables sidebar scroll.
- Ensure **no menu item is unreachable** due to viewport or height constraints; all items must be reachable by scrolling.

### 4. Access control alignment

- Sidebar visibility must match backend authorization.
- Provincial users must **not** see or access:
  - Create Provincial
  - View Provincials
- Remove these entries from the Provincial sidebar and ensure no other provincial UI exposes these actions to the provincial role.

### 5. Non-goals (explicit)

- **No** report structure changes (Reports section layout and grouping remain as-is).
- **No** new feature development beyond the resolved sidebar items.
- **No** database changes.
- **No** policy or permission refactor beyond UI visibility (no new roles, abilities, or middleware changes required for this work).
- **No** CMS or admin UI for editing the User Manual.
- **No** dynamic editing of the Markdown file via the application.
- User Manual content updates are **file-based only** (edit the Markdown file on disk; the PHP page reads and renders it).

---

### 6. Follow-up phase: Provincial User Manual page

**Provincial User Manual page implementation** is **not** part of the initial sidebar architecture work. It is done in a **follow-up phase** after sidebar architecture stabilization.

- The sidebar "User Manual" link and the route `provincial.user-manual` are wired and active-state is correct.
- The route may return 501 or a placeholder until the follow-up phase.
- In the follow-up phase: read `Documentations/Manual Kit/Provincial_User_Manual.md`; render it via a Laravel route/controller/view (Markdown to HTML); restrict access to Provincial users; link the sidebar to this page. No CMS or in-app editing of the manual.

---

*Document version: Final. All decisions above are binding for implementation.*
