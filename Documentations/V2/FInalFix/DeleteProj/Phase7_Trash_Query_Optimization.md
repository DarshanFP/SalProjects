# Phase 7 — Trash Query Optimization

## Before

- **Query structure:** `Project::onlyTrashed()` via `ProjectQueryService::getTrashedProjectsQuery($user)`, then `->with(['user'])` in controller
- **Potential N+1:** If Blade accessed `$project->society` or `$project->user`, each row would trigger an extra query (1 + N for user, 1 + N for society per page)

## After

- **Eager loading added:** `->with(['society', 'user'])`
- **Relations loaded:** Society and User are loaded in 2 additional queries per page (one for societies, one for users), regardless of result count

## Performance Impact

- **Reduced query count:** Without eager loading, 15 projects = 1 base query + up to 15 user + up to 15 society = 31 queries. With eager loading: 1 base + 1 societies + 1 users = 3 queries (plus pagination count query).
- **No change to visibility rules:** Province and ownership filters unchanged

## Security Check

- **Province boundary preserved:** `getTrashedProjectsQuery()` still applies `where('province_id', $user->province_id)` when `$user->province_id !== null`
- **Ownership preserved:** Executor/applicant ownership closure `where(user_id = X OR in_charge = X)` unchanged
- **Authorization logic untouched:** No changes to `ProjectPermissionHelper` or permission checks
