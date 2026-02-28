# Phase 1 – Infrastructure Layer (Completed)

**Target:** `app/Services/ProjectQueryService.php`  
**Rule:** Add only. No modifications to existing methods, no refactors, no controller/permission changes.

---

## Objective

Introduce explicit scope separation for dashboard use:

1. **Owned scope** — `projects.user_id = $user->id`
2. **In-Charge scope** — `projects.in_charge = $user->id` AND `projects.user_id != $user->id`

The existing merged scope (owner or in-charge) remains unchanged and is not replaced.

---

## Methods Added

### 1. Base query builders

| Method | Scope | Description |
|--------|--------|-------------|
| `getOwnedProjectsQuery(User $user): Builder` | Owned | Province filter + `where('user_id', $user->id)`. No in_charge condition. |
| `getInChargeProjectsQuery(User $user): Builder` | In-Charge | Province filter + `where('in_charge', $user->id)` + `where('user_id', '!=', $user->id)`. Excludes owned. |

Province filter applied in both is the same as in `getProjectsForUserQuery()`:  
`if ($user->province_id !== null) { $query->where('province_id', $user->province_id); }`

### 2. ID helpers

| Method | Returns |
|--------|--------|
| `getOwnedProjectIds(User $user): Collection` | `getOwnedProjectsQuery($user)->pluck('project_id')` |
| `getInChargeProjectIds(User $user): Collection` | `getInChargeProjectsQuery($user)->pluck('project_id')` |

### 3. Convenience methods (owned only)

| Method | Logic |
|--------|--------|
| `getApprovedOwnedProjectsForUser(User $user, array $with = [])` | Owned query + same approved statuses as `getApprovedProjectsForUser()` + `with($with)` → get() |
| `getEditableOwnedProjectsForUser(User $user, array $with = [])` | Owned query + `ProjectStatus::getEditableStatuses()` + `with($with)` → get() |
| `getRevertedOwnedProjectsForUser(User $user, array $with = [])` | Owned query + same reverted statuses as `getRevertedProjectsForUser()` + `with($with)` → get() |

---

## Documentation

Each new method has a PHPDoc block stating:

- Scope type (Owned / In-Charge)
- That it does **not** replace the merged scope
- That it is safe infrastructure for dashboard separation (e.g. Owned vs In-Charge tabs)

---

## What Was Not Changed

- No existing method in `ProjectQueryService` was modified
- `getProjectsForUserQuery()` is untouched
- No controller, permission, or call-site changes
- No refactors; province filtering is duplicated only in the two new base query methods (by design, to avoid touching existing code)

---

## Verification

- PHP syntax: `php -l` passes
- Linter: no issues reported
- Behavior: merged scope and all existing methods behave as before

---

## Related docs

- `OwnedVsInChargePhasePlan.md` — overall phase plan
- `OwnerVsInChargeResponsibilityAudit.md` — responsibility audit
- `ExecutorDashboardAudit.md` — executor dashboard audit
