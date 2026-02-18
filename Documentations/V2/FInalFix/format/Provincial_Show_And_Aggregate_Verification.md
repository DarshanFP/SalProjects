# Provincial Show Route & Aggregate Strategy Verification

**Document type:** Read-only investigation (no code changes).  
**Scope:** `projects.show` province isolation + financial column sources and lightweight aggregate feasibility.

---

## 1. projects.show Route Details

| Item | Value |
|------|--------|
| **Route name** | `projects.show` |
| **URL** | `/executor/projects/{project_id}` |
| **Controller** | `App\Http\Controllers\Projects\ProjectController` |
| **Method** | `show($project_id)` |
| **Middleware** | `auth`, `role:executor,applicant` |
| **Route group** | `Route::middleware(['auth', 'role:executor,applicant'])->group(...)` → `Route::prefix('executor/projects')->group(...)` |

**Source:** `routes/web.php` (lines 401, 417–424).

**Note:** Provincial and Coordinator use different named routes: `provincial.projects.show` (ProvincialController::showProject) and `coordinator.projects.show` (CoordinatorController::showProject). They do not use `projects.show`.

---

## 2. Authorization Enforcement Analysis

### 2.1 Controller method (`ProjectController::show`)

- **A) Uses ProjectPermissionHelper:** Yes.  
  `ProjectPermissionHelper::canView($project, $user)` is called before rendering (lines 827–836).

- **B) Uses getAccessibleUserIds():** No.  
  `getAccessibleUserIds()` is used in ProvincialController/listing flows, not in `ProjectController::show`.

- **C) Uses province_id filter in query:** No.  
  Project is loaded by `Project::where('project_id', $project_id)->...->firstOrFail()` with no province filter. Authorization is done after load via the helper.

- **D) Explicit authorization check:** Yes.  
  `if (!ProjectPermissionHelper::canView($project, $user)) { abort(403, 'You do not have permission to view this project.'); }`

- **E) Uses policy (authorize()):** No.  
  No `$this->authorize(...)`; only the helper and `abort(403)`.

### 2.2 Exact code enforcing province boundary

**In `ProjectController::show` (lines 827–836):**

```php
// Province isolation + role-based view (ProjectPermissionHelper::canView)
if (!ProjectPermissionHelper::canView($project, $user)) {
    Log::warning('ProjectController@show - Access denied', [...]);
    abort(403, 'You do not have permission to view this project.');
}
```

**In `ProjectPermissionHelper::canView` (App\Helpers\ProjectPermissionHelper):**

- First calls `passesProvinceCheck($project, $user)`.
- **passesProvinceCheck:**  
  - If `$user->province_id === null` → `true` (admin/general: no province restriction).  
  - Else → `$project->province_id === $user->province_id`.
- Then: admin/coordinator/provincial/general → allow view; executor/applicant → allow only if `$project->user_id === $user->id || $project->in_charge === $user->id`.

So province boundary is enforced inside `ProjectPermissionHelper::canView` via `passesProvinceCheck`.

---

## 3. Province Isolation Guarantee

- **Provincial user and `projects.show`:**  
  A provincial user cannot reach `projects.show` at all: the route is under `role:executor,applicant`. They use `provincial.projects.show`, which calls `ProjectController::show` only after `ProvincialController::showProject` has checked that the project’s `user_id` is in `getAccessibleUserIds($provincial)` (province-scoped). So provincial users never bypass province by using the executor route.

- **Executor/Applicant:**  
  For `projects.show`, after loading the project, `canView` enforces:
  - `passesProvinceCheck`: project must be in the same province as the user (or user has no province).
  - Plus owner/in-charge for executor/applicant.
  So a provincial user cannot open a project belonging to another province when using the executor route (they are blocked by role middleware), and an executor cannot open another province’s project (blocked by `canView` → `passesProvinceCheck`).

- **General/Coordinator:**  
  Allowed global access by design: `passesProvinceCheck` returns `true` when `user->province_id === null` (general); coordinator is explicitly allowed in `canView` after province check. So global access for these roles is intentional.

---

## 4. Bypass Risk Assessment

- **Scenario:** User changes URL to another `project_id` (e.g. guessing or enumerating IDs).

- **Outcome:**  
  - Project is loaded by ID only (no province filter in query).  
  - Then `ProjectPermissionHelper::canView($project, $user)` runs.  
  - If project is in another province → `passesProvinceCheck` is false → `canView` false → **abort(403)**.  
  - If same province but executor/applicant and not owner/in-charge → **abort(403)**.

- **Conclusion:** Manual URL change to another project ID does **not** allow access across province or outside ownership; the response is **abort(403)**. No bypass identified for `projects.show`.

---

## 5. Financial Column Source Analysis

Columns analyzed: `overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned`.

### 5.1 Where values come from (list/summary context)

List and summary views (e.g. ProvincialController budget overview, CoordinatorController, GeneralController) get financials via:

- `$resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);`
- `$financials = $resolver->resolve($project);`

The resolver delegates by `project_type` to:

- **PhaseBasedBudgetStrategy** (e.g. Development Projects, LDP, RST, CCI, EduRUT).
- **DirectMappedIndividualBudgetStrategy** (IIES, IES, ILP, IAH, IGE).

### 5.2 Per-column source

| Column | Stored in DB? | Derived / calculated? | Notes |
|--------|----------------|-------------------------|--------|
| **overall_project_budget** | Yes (`projects.overall_project_budget`) | Sometimes | Phase-based: can come from SUM of `project.budgets` (current phase) via `DerivedCalculationService` when phase budgets exist; else from `project->overall_project_budget`. Direct-mapped types: from type-specific tables (e.g. IIES/IES expenses, ILP/IAH/IGE budget tables), not from `projects.overall_project_budget`. |
| **amount_forwarded** | Yes (`projects.amount_forwarded`) | No for phase-based | Phase-based: always `project->amount_forwarded`. Direct-mapped: often 0 (hardcoded in strategy). |
| **local_contribution** | Yes (`projects.local_contribution`, migration `add_local_contribution_to_projects_table`) | No for phase-based | Phase-based: always `project->local_contribution`. Direct-mapped: from type-specific tables (e.g. IIES/IES/ILP/IAH/IGE). |
| **amount_sanctioned** | Yes (`projects.amount_sanctioned`) | Sometimes | Phase-based: if approved, from `project->amount_sanctioned`; else computed as `overall - (forwarded + local)`. Direct-mapped: from type-specific tables (e.g. balance_requested, amount_requested). |

### 5.3 Dependency on reports/accountDetails

- For **per-project** financial values (the four columns above), the resolver uses **project** (and its relations: `budgets`, or IIES/IES/ILP/IAH/IGE tables). It does **not** use reports or accountDetails to compute `overall_project_budget`, `amount_forwarded`, `local_contribution`, or `amount_sanctioned`.
- Reports/accountDetails are used **separately** in list views for utilization (e.g. total expenses from approved reports → budget utilization), not as the source of these four columns.

---

## 6. SQL Aggregation Feasibility

### 6.1 Per column

| Column | Pure SQL aggregate possible? | Reason |
|--------|-------------------------------|--------|
| **amount_forwarded** | Partially | Stored on `projects`. Phase-based: `SUM(projects.amount_forwarded)` matches resolver. Direct-mapped: resolver often uses 0; SUM would use DB value → may differ from resolver for those types. |
| **local_contribution** | Partially | Same as above: safe for phase-based from `projects.local_contribution`; direct-mapped types use other tables. |
| **overall_project_budget** | No (in general) | Resolver can override from `project.budgets` (phase-based) or from type-specific tables (IIES/IES/ILP/IAH/IGE). Single `SUM(projects.overall_project_budget)` would not match resolver for those cases. |
| **amount_sanctioned** | No (in general) | Approved: from DB. Non-approved: derived (overall - forwarded - local). Direct-mapped: from other tables. So one aggregate expression cannot replicate full resolver behaviour. |

### 6.2 What would be needed for full parity

- **Phase-based only, and only when phase budgets are not used for overall:**  
  Then `SUM(projects.overall_project_budget)`, `SUM(projects.amount_forwarded)`, `SUM(projects.local_contribution)`, and for approved projects `SUM(projects.amount_sanctioned)` could mirror the resolver for those columns.
- **Mixed project types or phase-derived overall / type-specific tables:**  
  Would require either:
  - Per-type subqueries and JOINs (complex, brittle), or
  - Keeping resolver for per-project values and only aggregating in PHP after resolve (current pattern).

So: **full SQL-only aggregation** for the four columns with **exact resolver parity** is not feasible across all project types and approval states. A **hybrid** (DB sums where safe, resolver for the rest) is possible but adds complexity and risk of drift from resolver logic.

---

## 7. Proposed Lightweight Aggregate Strategy

### 7.1 Option A: Pure DB aggregate (no resolver)

- **Query shape:**  
  From existing province-filtered base query (e.g. `clone $baseQuery`), add:
  - `selectRaw('SUM(projects.overall_project_budget) as sum_overall_project_budget')`,  
  - same for `amount_forwarded`, `local_contribution`, `amount_sanctioned`.
- **Pros:** One extra query, low memory, no per-project resolver.  
- **Cons:** Does **not** match resolver for: phase-based projects where overall comes from budgets, non-approved amount_sanctioned, and all direct-mapped types. **Not recommended** if list/summary must match single-project show/export.

### 7.2 Option B: Hybrid (DB sums + resolver for per-row display)

- **Grand totals only:**  
  Run one aggregate query on `projects` (with same filters as base query) for `SUM(amount_forwarded)`, `SUM(local_contribution)`, and optionally `SUM(overall_project_budget)` / `SUM(amount_sanctioned)` for “approximate” totals.
- **Per-row:**  
  Keep current approach: load page of projects, run resolver for each, attach to collection; use resolver sums for any per-row display.
- **Pros:** Slightly less work for grand totals; per-row remains correct.  
- **Cons:** Totals may not match sum of resolved per-row values (same parity issue as above). Adds two sources of truth.

### 7.3 Option C: Keep current (full dataset + resolver)

- **Current approach:**  
  Load full filtered set (or paginated + full set for totals), run `ProjectFinancialResolver::resolve($project)` for each project, sum in PHP for grand totals.
- **Pros:** Single source of truth; totals and per-row always match resolver (show/export parity).  
- **Cons:** More memory and CPU when many projects; no single SQL aggregate.

### 7.4 Recommended aggregate query (if Option B is accepted for approximate totals only)

Example for a province-scoped base query:

```php
$baseQuery = ...; // existing province-safe project query
$aggregates = (clone $baseQuery)
    ->selectRaw('
        COALESCE(SUM(CAST(overall_project_budget AS DECIMAL(15,2))), 0) as sum_overall_project_budget,
        COALESCE(SUM(CAST(amount_forwarded AS DECIMAL(15,2))), 0) as sum_amount_forwarded,
        COALESCE(SUM(CAST(local_contribution AS DECIMAL(15,2))), 0) as sum_local_contribution,
        COALESCE(SUM(CAST(amount_sanctioned AS DECIMAL(15,2))), 0) as sum_amount_sanctioned
    ')
    ->first();
```

Use only where “DB-only” totals are acceptable; they will not match resolver for all project types/states.

---

## 8. Performance Comparison

| Approach | Complexity | Performance | Memory | Risk |
|----------|------------|-------------|--------|------|
| **Current (full load + resolver)** | Medium | Load N projects + N resolver runs; acceptable for hundreds of projects; degrades for very large N | O(N) models + relations | Low (single source of truth). |
| **Pure SQL aggregate (Option A)** | Low | One aggregate query | O(1) | High (wrong totals for phase-based and direct-mapped). |
| **Hybrid (Option B)** | Medium | One aggregate + current per-page resolver | Slightly lower than current for totals | Medium (two sources of truth; totals can diverge from per-row). |

- **Complexity:** Current approach is well understood and consistent. Pure aggregate is simple but incorrect for parity; hybrid adds branches and documentation burden.
- **Performance benefit:** Pure or hybrid aggregate gives benefit mainly on “grand totals only” and only if we accept non-parity for some project types.
- **Memory impact:** Current: full collection in memory for the full set used for totals. Aggregate reduces that if we avoid loading full set for totals (e.g. use aggregate query for totals and resolver only for current page).
- **Risk:** Highest for pure SQL (wrong numbers); medium for hybrid (confusion, drift).

---

## 9. Recommendation (Keep Current / Optimize)

### projects.show (province isolation)

- **Verdict:** **Safe.**  
  Province isolation is enforced by `ProjectPermissionHelper::canView` (and thus `passesProvinceCheck`). Changing the URL to another project ID results in **abort(403)** when the project is outside the user’s province or ownership. General/Coordinator global access is intentional.

### Lightweight aggregate

- **Verdict:** **Feasible only with caveats.**  
  - **Full parity with resolver:** Not feasible for all project types and states with a single SQL aggregate.  
  - **Approximate DB-only totals:** Feasible for quick dashboard totals if product accepts that they may not match resolved per-project sums for phase-based and direct-mapped types.  
  - **Recommended next action:** **Keep current approach** (full dataset + resolver for list/summary) unless there is a proven performance issue. If grand totals are a bottleneck, consider a **hybrid** that:  
  - Uses one SQL aggregate **only for approximate grand totals** (and labels them as such if needed), and  
  - Keeps resolver for per-row and for any “official” totals that must match show/export.  
  Document clearly that DB-only totals are approximate and where they differ from resolver.

### Confirmation

- **MD created:** Yes.  
  **Path:** `Documentations/V2/FInalFix/format/Provincial_Show_And_Aggregate_Verification.md`

---

## Summary

| Question | Answer |
|----------|--------|
| Is `projects.show` safe (province isolation)? | Yes. Enforced via `ProjectPermissionHelper::canView` → `passesProvinceCheck`; URL tampering returns 403. |
| Is lightweight aggregate feasible with full resolver parity? | No for all types/states; yes only for approximate DB-only totals. |
| Recommended next action | Keep current resolver-based list/summary; consider hybrid only if grand-total performance is an issue and approximate totals are acceptable. |
| MD created? | Yes — `Documentations/V2/FInalFix/format/Provincial_Show_And_Aggregate_Verification.md`. |
