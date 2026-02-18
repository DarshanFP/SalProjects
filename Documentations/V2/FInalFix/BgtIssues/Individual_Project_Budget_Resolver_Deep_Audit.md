# Individual Project Budget Resolver Deep Audit

**Milestone:** Budget Integrity Investigation  
**Task:** Deep Resolver & Strategy Audit (Individual Project Types)  
**Mode:** STRICTLY READ-ONLY — ZERO ASSUMPTION  
**Scope:** IES, IIES, ILP, IAH only  

**Date:** 2026-02-16  

---

## SECTION 1 — Confirm Data Exists

Sample projects were selected per type via read-only query (`Project::where('project_type', ...)->first()`). Child table row counts and derived sums were then computed.

| Project ID | Type | Child Rows Exist? | Total Derived Sum |
|------------|------|-------------------|-------------------|
| IOES-0001   | IES  | Yes (1 row)       | 123400            |
| IIES-0001   | IIES | No (0 rows)       | 0                 |
| ILA-0001    | ILP  | Yes (1 row)       | 342000            |
| IAH-0001    | IAH  | Yes (5 rows)      | 202000            |

**Evidence:** Artisan tinker read-only execution against `project_IES_expenses`, `project_IIES_expenses`, `project_ILP_budget`, `project_IAH_budget_details` (sums: IES `total_expenses`, IIES `iies_total_expenses`, ILP `cost`, IAH `amount`).

**Implication:** For **IIES-0001**, the resolver will use `fallbackFromProject($project)` because `$project->iiesExpenses` is null (no row). Resolver output will be whatever is stored on `projects` (overall_project_budget, amount_sanctioned, opening_balance). If those are null/0, display will be zero. For IES, ILP, IAH samples, child data exists and strategy can return non-zero derived totals.

---

## SECTION 2 — Resolver Execution Trace

### 2.1 Where `ProjectFinancialResolver::resolve()` is called

| Context        | Called? | File:Line |
|----------------|--------|-----------|
| **Show page**  | Yes    | `app/Http/Controllers/Projects/ProjectController.php`:1031–1032 — `$resolver = app(ProjectFinancialResolver::class);` then `$data['resolvedFundFields'] = $resolver->resolve($project);` |
| **Edit page**  | No     | `ProjectController::edit()` returns view via `compact(...)` at 1293–1314; `resolvedFundFields` is **not** in the compact list and there is no call to the resolver in `edit()`. |
| **Hydrator**   | Yes    | `app/Services/ProjectDataHydrator.php`:284 — `$data['resolvedFundFields'] = $this->financialResolver->resolve($project);` (used by PDF export). |

### 2.2 Strategy class selected per project type

Selection is in `ProjectFinancialResolver::getStrategyForProject()` at **lines 113–126**. Comparison uses `$projectType = $project->project_type ?? ''` and `in_array($projectType, self::CONST, true)` (exact string, strict).

| Project Type                                | Strategy Selected                     | File:Line |
|--------------------------------------------|----------------------------------------|-----------|
| Individual - Ongoing Educational support    | DirectMappedIndividualBudgetStrategy   | ProjectFinancialResolver.php:121–122 |
| Individual - Initial - Educational support  | DirectMappedIndividualBudgetStrategy   | ProjectFinancialResolver.php:121–122 |
| Individual - Livelihood Application        | DirectMappedIndividualBudgetStrategy   | ProjectFinancialResolver.php:121–122 |
| Individual - Access to Health               | DirectMappedIndividualBudgetStrategy   | ProjectFinancialResolver.php:121–122 |

**Default (unknown type):** PhaseBasedBudgetStrategy (line 125). Individual types listed above are in `DIRECT_MAPPED_INDIVIDUAL_TYPES` (lines 38–44), so they do **not** fall into the default.

---

## SECTION 3 — Strategy Logic Inspection

### 3.1 DirectMappedIndividualBudgetStrategy

**File:** `app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php`

- **Entry:** `resolve(Project $project)` at line 29.  
- **Relations loaded:** Line 33 — `$project->loadMissing($this->getRelationsForType($projectType))`.  
- **Per-type dispatch:** Lines 34–41 — `match ($projectType)` to `resolveIIES`, `resolveIES`, `resolveILP`, `resolveIAH`, or `fallbackFromProject`.  
- **Fallback:** Lines 181–189 — returns `$project->overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned`, `opening_balance` (from `projects` table). Used when relation is missing or empty.

| Type | overall_project_budget | Sums child rows? | Fallback to $project->overall_project_budget? | Relations loaded |
|------|------------------------|------------------|-----------------------------------------------|------------------|
| **IIES** | `(float)($expenses->iies_total_expenses ?? 0)` — single row (hasOne) | N/A (one row) | Yes, if `!$expenses` (line 61–63) | `['iiesExpenses']` line 49 |
| **IES**  | `(float)($expenses->total_expenses ?? 0)` — from first() of hasMany | No (uses first() only) | Yes, if `!$expenses` (line 84–86) | `['iesExpenses']` line 50 |
| **ILP**  | `calculateProjectTotal($budgets->map(fn ($b) => (float)($b->cost ?? 0)))` | Yes (all rows) | Yes, if `!$budgets \|\| $budgets->isEmpty()` (107–109) | `['ilpBudget']` line 51 |
| **IAH**  | `calculateProjectTotal($details->map(fn ($d) => (float)($d->amount ?? 0)))` | Yes (all rows) | Yes, if `!$details \|\| $details->isEmpty()` (132–134) | `['iahBudgetDetails']` line 52 |

**Note:** IES uses only the **first** child row for overall (and local/sanctioned). ILP/IAH sum all rows for overall but use **first()** for local_contribution and amount_sanctioned (lines 114–116, 138–140).

### 3.2 PhaseBasedBudgetStrategy

**File:** `app/Domain/Budget/Strategies/PhaseBasedBudgetStrategy.php`

- **Not used for IES, IIES, ILP, IAH** when `project_type` matches the exact strings in `DIRECT_MAPPED_INDIVIDUAL_TYPES`.  
- For completeness: overall = sum of `this_phase` for `budgets` filtered by `current_phase` (lines 39–47); if no phase budgets, overall = `$project->overall_project_budget` (line 46). Relations: `loadMissing('budgets')` (line 37).

---

## SECTION 4 — Strategy Selection Logic

**File:** `app/Domain/Budget/ProjectFinancialResolver.php`, method `getStrategyForProject()` at lines 113–126.

| Check | Result | Evidence |
|-------|--------|----------|
| How project_type is matched | Exact string in array | `in_array($projectType, self::PHASE_BASED_TYPES, true)` and `in_array($projectType, self::DIRECT_MAPPED_INDIVIDUAL_TYPES, true)` |
| Case-sensitive? | Yes | `true` as third argument to `in_array` (strict comparison). |
| Enum-based? | No | Uses `$project->project_type` (string from DB). No enum in resolver. |
| Individual type in wrong branch? | No | IES, IIES, ILP, IAH strings are in `DIRECT_MAPPED_INDIVIDUAL_TYPES` (lines 39–43); they match before the default branch and receive DirectMappedIndividualBudgetStrategy. |

**Constant values (lines 38–44):**

- `'Individual - Initial - Educational support'`
- `'Individual - Ongoing Educational support'`
- `'Individual - Livelihood Application'`
- `'Individual - Access to Health'`

These match `App\Constants\ProjectType` (e.g. `INDIVIDUAL_ONGOING_EDUCATIONAL = 'Individual - Ongoing Educational support'`).

---

## SECTION 5 — Hydration & Passing

| Check | Result | Evidence |
|-------|--------|----------|
| resolvedFundFields passed to **show** view? | Yes | `ProjectController.php`:1032 — `$data['resolvedFundFields'] = $resolver->resolve($project);` then same `$data` passed to `view('projects.Oldprojects.show', $data)` at 1040. |
| resolvedFundFields passed to **edit** view? | No | `ProjectController.php`:1293–1314 — `return view('projects.Oldprojects.edit', compact(...));` — `resolvedFundFields` is not in the compact list. |
| resolvedFundFields non-null when passed? | Yes (when passed) | Resolver `resolve()` always returns an array (normalized at lines 131–146); never null. So when show/hydrator pass it, it is a non-null array. |
| resolvedFundFields contains zero or non-zero? | Depends on data | If child rows exist and have values → non-zero. If no child rows (or fallback and project row null/0) → zeros. Section 1: IIES-0001 has 0 child rows → fallback → zeros unless project row is set; IES/ILP/IAH samples have child data → resolver can return non-zero. |

---

## SECTION 6 — Silent Zero Sources

| Check | Result | Evidence |
|-------|--------|----------|
| **collect()->sum() on empty** | Returns 0; no exception | Not used directly in strategy. ILP/IAH use `$budgets->map(...)` then `calculateProjectTotal($costValues)`. |
| **calculateProjectTotal(empty)** | Returns 0.0 | `DerivedCalculationService::calculateProjectTotal()` (lines 53–65): `$total = 0.0`; foreach over empty iterable does nothing → returns 0.0. Silent zero. |
| **Relationship not eager loaded** | Mitigated by loadMissing | DirectMappedIndividualBudgetStrategy line 33: `$project->loadMissing($this->getRelationsForType($projectType))`. So relations are loaded before resolveIIES/resolveIES/resolveILP/resolveIAH. If still no rows in DB, relation returns empty collection or null (hasOne) → fallback. |
| **Missing foreign key** | N/A | Relations use `project_id`; strategy does not assume FK presence beyond Eloquent’s behavior. |
| **Wrong column name** | None found | IIES: `iies_total_expenses`, `iies_balance_requested`, etc. (ProjectIIESExpenses fillable 47–53). IES: `total_expenses`, `balance_requested`, etc. (ProjectIESExpenses 49–53). ILP: `cost`, `beneficiary_contribution`, `amount_requested` (ProjectILPBudget 41). IAH: `amount`, `family_contribution`, `amount_requested` (ProjectIAHBudgetDetails 47–50). Strategy uses same attribute names. |
| **Numeric cast issue** | Defensive casts present | Strategy uses `(float)($x ?? 0)` throughout. Resolver and strategy `normalize()` use `round(max(0, (float) $v), 2)`. No evidence of string/numeric mismatch causing silent zero. |

**Conclusion:** Silent zeros can arise from (1) **empty child data** → fallback to project row; (2) **fallback when project row is null/0** → normalized 0; (3) **calculateProjectTotal(empty collection)** → 0.0. No wrong column or missing load for the intended relations.

---

## SECTION 7 — Root Cause Conclusion

**Primary:** **G) Combination**

- **Edit page:** **F) View ignoring resolver** — Resolver is **not** invoked in `ProjectController::edit()` and **resolvedFundFields** is **not** passed to the edit view (compact list at 1293–1314). Edit partials use only `$project->overall_project_budget`, `$project->amount_sanctioned`, `$project->opening_balance`. For individual types, these columns are often not populated (budget lives in child tables), so edit can show zero or stale values regardless of child data.

- **Show/PDF when zeros appear:** **A) Data absent** and/or **E) Relation not loaded (no rows)** — If for a given project there are no child rows (e.g. IIES-0001 in this audit), the strategy uses `fallbackFromProject($project)`. If `projects.overall_project_budget` (and sanctioned/opening) are null or zero, the resolver returns zeros. So “data absent” in child table **or** in project row after fallback both produce zeros. Relations are loaded via `loadMissing`; “not loaded” means “no rows” here, not a bug in eager loading.

**Not the root cause for individual types:**

- **B) Strategy mismatch** — IES, IIES, ILP, IAH correctly get DirectMappedIndividualBudgetStrategy.
- **C) Strategy logic wrong** — Logic matches model attributes and table design; ILP/IAH sum child rows for overall; IES/IIES use single/first row. Design choice, not a wrong formula.
- **D) Resolver not invoked** — True for **edit** only; for **show** and **hydrator** (PDF), resolver is invoked.

**Technical summary:** For **show** and **export**, zeros are explained by missing child data and/or fallback to empty project row. For **edit**, zeros (or wrong values) are explained by the edit view not using the resolver at all and reading only from `$project`, which for individual types is often unsynced with child-table budget.

---

## SECTION 8 — Risk Classification

| Area | Classification | Rationale |
|------|----------------|-----------|
| **Edit page budget display** | **MEDIUM** — resolver misalignment | Edit does not use resolver; shows raw project attributes. For IES/IIES/ILP/IAH this misaligns display with actual budget derived from child tables. User-visible and can cause confusion or wrong decisions; does not by itself change stored financial totals. |
| **Show/PDF when data exists** | **LOW** — display only | When child (or project) data exists, resolver and view are aligned; display reflects resolver output. When data is absent, zeros are correct for that data state. |
| **Aggregation / reports** | **MEDIUM** — depends on usage | If list/dashboard/reports use `$project->overall_project_budget` or `opening_balance` instead of resolver for individual types, totals can be wrong (MEDIUM). If they use resolver (e.g. ProvincialController pattern), risk is lower. Not fully traced in this audit. |
| **Financial persistence** | **LOW** in this audit | Resolver is read-only; no evidence that approval or save logic is broken by resolver strategy selection. Risk of **HIGH (financial aggregation corrupted)** would require evidence that a critical path uses raw project fields for individual-type totals; not confirmed here. |

**Overall:** Treat as **MEDIUM** for edit-page and any aggregation that ignores resolver for individual types; **LOW** for show/export when data exists and code uses resolver.

---

**Individual Project Budget Resolver Deep Audit Complete — No Code Changes Made**
