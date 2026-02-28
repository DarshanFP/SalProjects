# Phase E — View Layer Verification

**Rule: Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.**

---

**Phase:** E  
**Objective:** Ensure Blade files use shared attachment routes and do not conditionally hide download/view links for provincial or coordinator.  
**Scope:** Project attachments only. Reports OUT OF SCOPE.

---

## 1. Objective

Verify that project attachment links in views:

- Use shared route names (e.g. `projects.attachments.download`, `projects.ies.attachments.download`, etc.)
- Are NOT prefixed with executor (e.g. no `executor.projects.attachments.download`)
- Are shown to provincial and coordinator (no role-based hiding)
- Resolve to the same URLs used by executor (shared routes)

---

## 2. Scope — Exact Files Involved

| File | Attachment Routes Used |
|------|------------------------|
| `resources/views/projects/partials/Show/attachments.blade.php` | projects.attachments.view, projects.attachments.download |
| `resources/views/projects/partials/Edit/attachment.blade.php` | projects.attachments.view, projects.attachments.download, projects.attachments.files.destroy |
| `resources/views/projects/partials/Show/IES/attachments.blade.php` | (if exists) |
| `resources/views/projects/partials/Edit/IES/attachments.blade.php` | projects.ies.attachments.view, projects.ies.attachments.download, projects.ies.attachments.files.destroy |
| `resources/views/projects/partials/Show/IIES/attachments.blade.php` | (if exists) |
| `resources/views/projects/partials/Edit/IIES/attachments.blade.php` | projects.iies.attachments.view, projects.iies.attachments.download, projects.iies.attachments.files.destroy |
| `resources/views/projects/partials/Show/IAH/documents.blade.php` | (if exists) |
| `resources/views/projects/partials/Edit/IAH/documents.blade.php` | projects.iah.documents.view, projects.iah.documents.download, projects.iah.documents.files.destroy |
| `resources/views/projects/partials/Show/ILP/attached_docs.blade.php` | projects.ilp.documents.view, projects.ilp.documents.download |
| `resources/views/projects/partials/Edit/ILP/attached_docs.blade.php` | projects.ilp.documents.view, projects.ilp.documents.download, projects.ilp.documents.files.destroy |

**Parent views that include these partials:**
- Project show/edit views (shared by executor, provincial, coordinator via ProjectController::show)

---

## 3. What Will NOT Be Touched

- Report attachment views
- Report show/edit views
- Controller logic
- Routes

---

## 4. Pre-Implementation Checklist

- [ ] List all Blade files that render project attachment links
- [ ] Grep for `route('projects.attachments`, `route('projects.ies.attachments`, etc.
- [ ] Grep for `executor.*attachments` or role-prefixed attachment routes
- [ ] Check for `@if(auth()->user()->role === 'executor')` or similar hiding links
- [ ] Verify project show is rendered by provincial/coordinator (provincial.projects.show, coordinator.projects.show → ProjectController::show)

---

## 5. Step-by-Step Implementation Plan

### Step E1: Audit Project Show Partial — DP Attachments

File: `resources/views/projects/partials/Show/attachments.blade.php`

**Verify:**
- [ ] Uses `route('projects.attachments.view', $attachment->id)`
- [ ] Uses `route('projects.attachments.download', $attachment->id)`
- [ ] No `@can` or role check that hides links for provincial/coordinator
- [ ] Links visible to all roles that can view the project (controller enforces access)

### Step E2: Audit Project Edit Partial — DP Attachments

File: `resources/views/projects/partials/Edit/attachment.blade.php`

**Verify:**
- [ ] View/download use shared routes
- [ ] Destroy uses `projects.attachments.files.destroy`
- [ ] Edit partial may be shown only when user can edit — verify provincial/coordinator see view/download (or are redirected to show)
- [ ] If edit is never shown to provincial/coordinator, show partial is the one that matters for download links

### Step E3: Audit IES Partials

Files: Show and Edit IES attachments

**Verify:**
- [ ] Use `projects.ies.attachments.view`, `projects.ies.attachments.download`, `projects.ies.attachments.files.destroy`
- [ ] No executor-prefixed routes
- [ ] No conditional hiding for provincial/coordinator

### Step E4: Audit IIES Partials

**Verify:**
- [ ] Use `projects.iies.attachments.view`, `projects.iies.attachments.download`, `projects.iies.attachments.files.destroy`
- [ ] Shared routes only

### Step E5: Audit IAH Partials

**Verify:**
- [ ] Use `projects.iah.documents.view`, `projects.iah.documents.download`, `projects.iah.documents.files.destroy`
- [ ] Shared routes only

### Step E6: Audit ILP Partials

**Verify:**
- [ ] Use `projects.ilp.documents.view`, `projects.ilp.documents.download`, `projects.ilp.documents.files.destroy`
- [ ] Shared routes only

### Step E7: Verify Project Show Context

**Key:** Provincial and Coordinator use:
- `provincial.projects.show` → ProvincialController::showProject → ProjectController::show
- `coordinator.projects.show` → CoordinatorController::showProject → ProjectController::show

Both delegate to `ProjectController::show`, which renders the same project show view with the same partials. Therefore:
- [ ] Project show view includes attachments partial for all project types (DP, IES, IIES, IAH, ILP)
- [ ] No role-based `@include` that excludes attachment partials for provincial/coordinator

### Step E8: Replace Any Wrong Routes

If any view uses:
- `executor.projects.attachments.*` (does not exist or is executor-only)
- Role-specific route that restricts provincial/coordinator

→ Replace with shared route: `projects.attachments.*`, `projects.ies.attachments.*`, etc.

### Step E9: Remove Conditional Hiding (if any)

If links are hidden with:
- `@if(in_array(auth()->user()->role, ['executor','applicant']))`
- `@role('executor')`

→ Remove or expand to include provincial, coordinator so they see the links. Controller will enforce access on click.

---

## 6. Security Impact Analysis

| Change | Risk | Mitigation |
|--------|------|------------|
| Show links to provincial/coordinator | They can click; controller enforces access | Correct; 403 if not authorized |
| Use shared routes | Same URL for all roles | Middleware and controller guard |
| Remove role-based hiding | Links visible to more roles | Controller must enforce (Phase B) |

---

## 7. Performance Impact Analysis

- No performance impact from route name changes
- Conditional removal may show a few more DOM elements; negligible

---

## 8. Rollback Strategy

- Revert modified Blade files from version control

---

## 9. Deployment Checklist

- [ ] All relevant partials audited and updated
- [ ] Clear view cache if used: `php artisan view:clear`
- [ ] Manual test: provincial and coordinator see attachment links on project show

---

## 10. Regression Checklist

- [ ] Executor: sees attachment links on project show (DP, IES, IIES, IAH, ILP)
- [ ] Provincial: sees attachment links when viewing project (owner or in_charge)
- [ ] Coordinator: sees attachment links when viewing any project
- [ ] Clicking link triggers correct route (shared, not executor-prefixed)
- [ ] No 404 from invalid route name

---

## 11. Sign-Off Criteria

- [ ] All project attachment links use shared route names
- [ ] No executor-prefixed attachment routes
- [ ] No conditional hiding of attachment links for provincial/coordinator
- [ ] Provincial and coordinator can see and click attachment links on project show
- [ ] Phase_E_Implementation_Summary.md created and updated
