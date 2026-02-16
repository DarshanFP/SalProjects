# M3 Phase 1 — Workflow Negative Filter Fix

**Milestone:** M3 — Resolver Parity & Financial Stability  
**Phase:** Status Fix Plan — Phase 1  
**Scope:** Fix negative filtering bug in workflow layer  
**Date:** 2025-02-15

---

## 1) Files modified

| File | Change |
|------|--------|
| `app/Http/Controllers/Projects/ProjectController.php` | Replaced single-status negative filter with `->notApproved()` in `index()` (executor/applicant project list). |
| `app/Helpers/ProjectPermissionHelper.php` | Replaced single-status negative filter with `$query->notApproved()` in `getEditableProjects()` for executor/applicant. |

---

## 2) Before (code snippet)

**ProjectController.php (index):**

```php
// Exclude projects with status APPROVED_BY_COORDINATOR for executors
$projects = \App\Services\ProjectQueryService::getProjectsForUserQuery($user)
    ->where('status', '!=', ProjectStatus::APPROVED_BY_COORDINATOR)
    ->with(['user', 'objectives', 'budgets'])
    ->get();
```

**ProjectPermissionHelper.php (getEditableProjects):**

```php
// For executors and applicants, exclude approved projects
if (in_array($user->role, ['executor', 'applicant'])) {
    $query->where('status', '!=', ProjectStatus::APPROVED_BY_COORDINATOR);
}
```

---

## 3) After (code snippet)

**ProjectController.php (index):**

```php
// Exclude all approved projects (any approval status) for executors/applicants — M3 Phase 1
$projects = \App\Services\ProjectQueryService::getProjectsForUserQuery($user)
    ->notApproved()
    ->with(['user', 'objectives', 'budgets'])
    ->get();
```

**ProjectPermissionHelper.php (getEditableProjects):**

```php
// For executors and applicants, exclude all approved projects (M3 Phase 1)
if (in_array($user->role, ['executor', 'applicant'])) {
    $query->notApproved();
}
```

`Project::scopeNotApproved()` (unchanged) is defined as:

```php
return $query->whereNotIn('status', ProjectStatus::APPROVED_STATUSES);
```

---

## 4) Why the bug existed

- The intent was “exclude approved projects” for executor/applicant so they only see editable/pending projects.
- The implementation excluded only **one** approved status: `APPROVED_BY_COORDINATOR`.
- Projects approved by General (as coordinator or provincial), i.e. `APPROVED_BY_GENERAL_AS_COORDINATOR` and `APPROVED_BY_GENERAL_AS_PROVINCIAL`, were **not** excluded.
- Those projects therefore still appeared in the executor/applicant project list and could be treated as editable, contradicting the intended workflow.

---

## 5) Risk removed

- **Workflow corruption:** Executors and applicants can no longer see or treat General-approved projects as editable.
- **Semantic alignment:** “Not approved” now means “not in `ProjectStatus::APPROVED_STATUSES`” (all three approval statuses), consistent with `ProjectStatus::APPROVED_STATUSES` and `Project::scopeNotApproved()`.

---

## 6) Regression risk

**LOW.**

- Only the **set** of projects shown to executor/applicant is narrowed (approved-by-General projects are now correctly excluded).
- No change to status values, financial formulas, resolver, export, dashboard aggregation, or report queries.
- `notApproved()` is the existing, centralized scope; we only switched call sites to use it instead of a single-status inequality.

---

## 7) Verification steps performed

1. **Search for remaining single-status negative filter**  
   Grep for `where('status', '!=', ProjectStatus::APPROVED_BY_COORDINATOR)` across the project: **zero** occurrences in application code (only in documentation).

2. **Executor cannot edit all approved statuses**  
   With the fix:
   - Projects with `APPROVED_BY_COORDINATOR` are excluded by `notApproved()`.
   - Projects with `APPROVED_BY_GENERAL_AS_COORDINATOR` are excluded by `notApproved()`.
   - Projects with `APPROVED_BY_GENERAL_AS_PROVINCIAL` are excluded by `notApproved()`.

3. **Financial integration tests**  
   Run existing financial/resolver integration tests (e.g. `tests/Feature/Budget/`) to confirm no unintended impact; Phase 1 does not change financial logic.

---

**M3 Phase 1 Complete — Workflow Negative Filtering Corrected**
