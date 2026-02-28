# Phase A — Route Middleware Verification

**Rule: Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.**

---

**Phase:** A  
**Objective:** Ensure all project attachment routes allow executor, applicant, provincial, coordinator, general, admin.  
**Scope:** Project attachments only. Reports OUT OF SCOPE.

---

## 1. Objective

Verify that every project attachment route (download, view, destroy) is protected by middleware that explicitly allows provincial and coordinator roles. Detect and document any route that is nested inside an executor-only group or shadowed by another definition.

---

## 2. Scope — Exact Files Involved

| File | Purpose |
|------|---------|
| `routes/web.php` | Sole source of route definitions |

**Routes in scope:**

- `projects.attachments.download`
- `projects.attachments.view`
- `projects.attachments.files.destroy`
- `projects.ies.attachments.download`
- `projects.ies.attachments.view`
- `projects.ies.attachments.files.destroy`
- `projects.iah.documents.view`
- `projects.iah.documents.download`
- `projects.iah.documents.files.destroy`
- `projects.iies.attachments.download`
- `projects.iies.attachments.view`
- `projects.iies.attachments.files.destroy`
- `projects.ilp.documents.view`
- `projects.ilp.documents.download`
- `projects.ilp.documents.files.destroy`

---

## 3. What Will NOT Be Touched

- Controllers
- Models
- Middleware classes (Role.php)
- Report routes
- Report controllers

---

## 4. Pre-Implementation Checklist

- [ ] Backup `routes/web.php`
- [ ] Run `php artisan route:list` to capture current state
- [ ] Document current middleware for each project attachment route
- [ ] Identify nesting structure (which groups contain which routes)
- [ ] Confirm executor group boundary (where it opens and closes)

---

## 5. Step-by-Step Implementation Plan

### Step A1: Audit Route File Structure

1. Open `routes/web.php`.
2. Locate `Route::middleware(['auth', 'role:executor,applicant'])->group(...)` (executor group).
3. Identify the closing `});` of the executor group.
4. Confirm that project attachment routes (lines ~476–512) are **outside** the executor group.

### Step A2: Verify Shared Middleware Group

1. Locate `Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator,general,admin'])->group(...)`.
2. Confirm it contains all 15 project attachment routes listed in Scope.
3. Verify the role string includes: `executor`, `applicant`, `provincial`, `coordinator`, `general`, `admin`.

### Step A3: Detect Duplicates and Shadowing

1. Search for any other definitions of `projects.attachments.*`, `projects.ies.attachments.*`, `projects.iah.documents.*`, `projects.iies.attachments.*`, `projects.ilp.documents.*`.
2. If duplicates exist, document which definition wins (Laravel uses first match).
3. Ensure no executor-prefixed route (e.g. `/executor/projects/attachments/*`) shadows shared routes.

### Step A4: Verify Route Order

1. Confirm more specific routes (e.g. `/projects/attachments/download/{id}`) appear before generic catch-all routes that might shadow them.
2. Document any route order risks.

### Step A5: Produce Route Audit Table

Create a table:

| Route Name | URI | Middleware Stack | Allowed Roles | Nesting Status |

### Step A6: Implement Changes (if any)

- If routes are nested inside executor group: move them to the shared group.
- If roles are missing: add provincial, coordinator (and general, admin if needed) to the role middleware.
- Do NOT change controller logic in this phase.

### Step A7: Clear Route Cache

```bash
php artisan route:clear
php artisan route:list --name=projects.attachments
php artisan route:list --name=projects.ies.attachments
php artisan route:list --name=projects.iah.documents
php artisan route:list --name=projects.iies.attachments
php artisan route:list --name=projects.ilp.documents
```

---

## 6. Security Impact Analysis

| Change | Risk | Mitigation |
|--------|------|------------|
| Add provincial/coordinator to role list | None if guard chain is correct | Controller and service layers enforce project-level access |
| Move routes out of executor group | Routes become accessible to more roles | Ensure controller guards are in place before Phase B |

---

## 7. Performance Impact Analysis

- No performance impact from route middleware changes.
- Same number of middleware runs; role list is slightly longer (no measurable effect).

---

## 8. Rollback Strategy

- Revert `routes/web.php` from backup.
- Run `php artisan route:clear`.
- Verify executor can still download attachments.

---

## 9. Deployment Checklist

- [ ] `php artisan route:clear` (or `route:cache` if used in production)
- [ ] Verify no route cache in version control
- [ ] Test one provincial and one coordinator download after deploy

---

## 10. Regression Checklist

- [ ] Executor: download DP attachment
- [ ] Executor: download IES attachment
- [ ] Applicant: download DP attachment
- [ ] Provincial: can reach route (middleware allows)
- [ ] Coordinator: can reach route (middleware allows)

---

## 11. Sign-Off Criteria

- [ ] All 15 project attachment routes are in a group with `role:executor,applicant,provincial,coordinator,general,admin`
- [ ] No project attachment route is nested inside executor-only group
- [ ] No duplicate or shadowed route definitions
- [ ] `php artisan route:list` confirms expected middleware for each route
- [ ] Phase_A_Implementation_Summary.md created and updated
