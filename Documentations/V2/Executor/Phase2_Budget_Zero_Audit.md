# Phase 2 – Budget Summary Showing 0 Audit

## Observed Issue

After Phase 2, the executor dashboard shows **Total Budget = Rs. 0.00** even when:

- **OwnedCount** = 6  
- **ActiveProjects** = 1 (from `getApprovedOwnedProjectsForUser($user)->count()`)  
- An approved owned project exists (e.g. **DP-0017**).

So the user has at least one approved owned project, but the budget summary total is zero. The expectation is that Total Budget reflects the sum of opening balances (or equivalent) for approved owned projects.

---

## Budget Flow Trace

1. **ExecutorController::executorDashboard()** (lines 89–99)  
   - Calls `ProjectQueryService::getApprovedOwnedProjectsForUser($user, $with)`.  
   - `$with` = `['reports' => fn ordering, 'reports.accountDetails', 'budgets']`.  
   - Result: `$approvedProjectsForSummary` (Collection of approved owned projects).  
   - Then: `$budgetSummaries = $this->calculateBudgetSummariesFromProjects($approvedProjectsForSummary->all(), $request)`.

2. **ExecutorController::calculateBudgetSummariesFromProjects()** (lines 361–430)  
   - Receives `$projects` = array of the same projects (from `->all()`).  
   - Gets `ProjectFinancialResolver` from container.  
   - For **each** project:  
     - `$financials = $resolver->resolve($project);`  
     - `$projectBudget = (float) ($financials['opening_balance'] ?? 0);`  
     - Adds `$projectBudget` to `$budgetSummaries['total']['total_budget']` and by project type.  
   - So **Total Budget** is the sum of `opening_balance` returned by the resolver for each project.

3. **ProjectFinancialResolver::resolve()** (Domain/Budget/ProjectFinancialResolver.php, lines 70–80)  
   - Gets strategy by project type (e.g. PhaseBasedBudgetStrategy).  
   - `$result = $strategy->resolve($project);`  
   - `$overlaid = $this->applyCanonicalSeparation($project, $result);`  
   - Returns `$this->normalize($overlaid)`.

4. **ProjectFinancialResolver::applyCanonicalSeparation()** (lines 87–108)  
   - For **approved** projects (`$project->isApproved()` true):  
     - Returns (among others):  
       - `'opening_balance' => (float) ($project->opening_balance ?? 0)`  
   - So for approved projects, **opening_balance is taken only from the project model attribute** `$project->opening_balance` (i.e. the `projects.opening_balance` column). The strategy’s `opening_balance` is not used for the approved branch.

**Conclusion:** The dashboard Total Budget is the sum of `$project->opening_balance` for each approved project in the dataset. If that attribute is `null` or `0` in the database (or not loaded), the resolver returns `0` and the total is zero.

- **Dataset passed:** Collection from `getApprovedOwnedProjectsForUser($user, [...])`; same query as “Active projects” count, so if ActiveProjects = 1, the collection has at least one project.  
- **Eager loading:** `reports`, `reports.accountDetails`, `budgets` are requested; the resolver does **not** use these for the approved-project `opening_balance` (it uses only the scalar `projects.opening_balance`).  
- **Aggregation:** Sum of resolver `opening_balance`; aggregation does not depend on merged scope, only on the passed collection and resolver output.

---

## Owned Approved Dataset Analysis

- **getApprovedOwnedProjectsForUser()** (ProjectQueryService.php, lines 301–314):  
  - Builds on `getOwnedProjectsQuery($user)` (province + `user_id = $user->id`).  
  - Adds `whereIn('status', [APPROVED_BY_COORDINATOR, APPROVED_BY_GENERAL_AS_COORDINATOR, APPROVED_BY_GENERAL_AS_PROVINCIAL])`.  
  - Applies `$query->with($with)` when `$with` is non-empty.  
  - Returns `$query->get()`.

- **getApprovedProjectsForUser()** (merged scope) uses the same three status constants via `getProjectsForUserByStatus(..., [...approved statuses...], $with)`.

So **approved statuses and `with()` usage are the same** for owned and merged. The controller passes `['reports' => ..., 'reports.accountDetails', 'budgets']`, so `budgets` and `reports.accountDetails` are eager-loaded. The resolver’s **approved** path does not use `budgets` or `reports` for `opening_balance`; it uses only `$project->opening_balance`.

---

## Financial Resolver Analysis

- **Approved projects:**  
  `ProjectFinancialResolver::applyCanonicalSeparation()` (line 104–106) sets  
  `'opening_balance' => (float) ($project->opening_balance ?? 0)`.  
  So **opening_balance is read only from the `projects` table column** `opening_balance`. No fallback to `amount_sanctioned`, strategy result, or budgets.

- **When is `projects.opening_balance` set?**  
  - **CoordinatorController** (approve flow): after `ProjectStatusService::approve()` (status → approved), it calls `ProjectFinancialResolver::resolve($project)` and then assigns `$project->opening_balance = $openingBalance` and saves. So it **persists whatever the resolver returns**. For an approved project, the resolver returns the **current** `$project->opening_balance`. If that was still `null`/`0` at that moment (e.g. sync didn’t run or didn’t set it), the controller saves `0`.  
  - **BudgetSyncService::syncBeforeApproval()** runs **before** approve and uses **ProjectFundFieldsResolver** (not ProjectFinancialResolver); it can write `opening_balance` for non-approved state. After that, `ProjectStatusService::approve()` only changes status. Then the coordinator flow uses ProjectFinancialResolver and overwrites `amount_sanctioned` and `opening_balance` from the resolver. So if the resolver sees approved and `project->opening_balance` is still 0 (e.g. sync didn’t run, or different code path), it returns 0 and that is saved.  
  - **Legacy or alternate flows:** If a project was approved without going through the coordinator approval screen that writes `opening_balance`, or was approved before that logic existed, `projects.opening_balance` can remain `NULL`/`0`.

- **Strategy:** PhaseBasedBudgetStrategy (and similar) for **approved** projects set `$opening = (float) ($project->opening_balance ?? 0)`. So the strategy also echoes the DB value. The resolver then overwrites with the same in `applyCanonicalSeparation`. There is no path in the resolver that, for approved projects, derives `opening_balance` from budgets or `amount_sanctioned` when `opening_balance` is zero.

---

## Root Cause Identified

**RESOLVER_LOGIC_ASSUMPTION**

The resolver **assumes** that for approved projects the column `projects.opening_balance` is already populated. When it is not (null or 0), it returns 0 and the dashboard shows Total Budget = 0.00.

- **Not DATASET_EMPTY:** If ActiveProjects = 1, the same owned-approved query returns at least one project; that project is in the collection passed to `calculateBudgetSummariesFromProjects`.  
- **Not RELATION_NOT_LOADED:** For the approved branch, `opening_balance` is taken from the project attribute only; relations are not used for that value.  
- **Not STATUS_FILTER_ERROR:** Owned approved query uses the same three statuses as the merged one; status logic is consistent.  
- **RESOLVER_LOGIC_ASSUMPTION:** The canonical behaviour for approved is “opening_balance = DB value”; there is no fallback when that value is 0/null (e.g. to `amount_sanctioned` or strategy-derived total). So when the DB has 0/null, the resolver and thus the dashboard show 0.

---

## Why It Happened After Phase 2

Phase 2 did **not** introduce the resolver behaviour or the fact that `opening_balance` can be 0 in the DB. It only changed **which projects** are used for the budget summary: from **merged** (`getApprovedProjectsForUser`) to **owned** (`getApprovedOwnedProjectsForUser`).

- **Before Phase 2:** If the same project DP-0017 was in the merged list and had `opening_balance` = 0 in the DB, Total Budget would already have been 0 for that project; the merged list might have included other (e.g. in-charge) projects with non-zero `opening_balance`, so the total could have been non-zero.  
- **After Phase 2:** Only **owned** approved projects are summed. If the one approved owned project (e.g. DP-0017) has `opening_balance` = 0 or null, the total becomes 0.00.

So Phase 2 **surfaced** the situation (one approved owned project with no stored opening balance) rather than causing the resolver to return 0. The root cause is the **resolver assumption** (and/or approval flow not populating `opening_balance`), not the switch to owned scope.

---

## Recommended Fix (Design Only)

No implementation; design only.

1. **Ensure `opening_balance` is set at approval time**  
   In the coordinator (and general-as-coordinator) approval flow, when persisting financials after approval, do not rely on the resolver’s approved branch when `project->opening_balance` is 0. For example:  
   - Use the **strategy** result’s `opening_balance` when the project was still non-approved (e.g. from the same resolver run but with a “pre-approval” interpretation), or  
   - Set `opening_balance = amount_sanctioned` (or agreed semantic) when approving and persist that, so the DB is never left with 0 for an approved project when a budget exists.

2. **Resolver fallback for approved projects (optional)**  
   In `ProjectFinancialResolver::applyCanonicalSeparation()`, for approved projects, when `(float) ($project->opening_balance ?? 0)` is zero, consider falling back to e.g. `(float) ($project->amount_sanctioned ?? 0)` so that the dashboard and any other consumer get a non-zero total when sanctioned amount is set. This is a defensive fix; the primary fix should be to persist the correct value on approval.

3. **Data fix for existing projects**  
   For projects already approved with `opening_balance` null/0 but `amount_sanctioned` > 0, run a one-time script or migration to set `opening_balance = amount_sanctioned` (or the agreed rule) so historical data is consistent.

4. **No change to Phase 2 scope**  
   Keep using **owned** scope only for the executor dashboard budget summary; do not revert to merged scope.
