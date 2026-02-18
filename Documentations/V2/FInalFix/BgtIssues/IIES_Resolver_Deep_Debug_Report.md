# Type-Specific Resolver Deep Debug — IIES

**Mode:** Read-only investigation  
**Objective:** Investigate why project type `Individual - Initial - Educational support` returns 0 financial values in General Info while IIES Estimated Expenses show non-zero totals.  
**Reference project_id:** IIES-0039 (for DB verification steps).

---

## Section A — Strategy selection

**File:** `app/Domain/Budget/ProjectFinancialResolver.php`

- **DIRECT_MAPPED_INDIVIDUAL_TYPES** (lines 38–44) includes the exact string:
  - `'Individual - Initial - Educational support'`
- **getStrategyForProject()** (lines 163–176):
  - Uses `$projectType = $project->project_type ?? ''`.
  - Uses `in_array($projectType, self::DIRECT_MAPPED_INDIVIDUAL_TYPES, true)` (strict).
  - If match → `DirectMappedIndividualBudgetStrategy`.
  - Otherwise → `PhaseBasedBudgetStrategy` (default).

**Conclusion:** For the correct strategy to be chosen, `projects.project_type` must be exactly `'Individual - Initial - Educational support'` (no extra/missing spaces, same hyphen and spelling). Any difference (e.g. typo, different hyphen character, trailing space) causes the default PhaseBasedBudgetStrategy to be used, which uses `project_budgets` and returns zeros for IIES (no phase budgets).

**Log recommendation (cannot run in read-only):** For project_id = IIES-0039, log `$project->project_type` and compare byte-for-byte with `'Individual - Initial - Educational support'`, and confirm `in_array($project->project_type, ProjectFinancialResolver::DIRECT_MAPPED_INDIVIDUAL_TYPES, true)` is true.

---

## Section B — Relation status

**File:** `app/Models/OldProjects/Project.php`

- **Relation name:** `iiesExpenses()` (line 757).
- **Definition:**  
  `return $this->hasOne(ProjectIIESExpenses::class, 'project_id', 'project_id');`
- **Type:** **hasOne** (single related model or null).
- **Foreign key:** On child `ProjectIIESExpenses`: column `project_id`.
- **Owner key:** On `Project`: `project_id`.
- **Related model:** `App\Models\OldProjects\IIES\ProjectIIESExpenses`.
- **Table (child model):** `project_IIES_expenses` (set in `ProjectIIESExpenses::$table`).

**Conclusion:** Relation name, FK and owner key are consistent. Laravel will query `project_IIES_expenses` where `project_id = $project->project_id`.

---

## Section C — Child row exists?

**Flow:**

- **ProjectController@show** (lines 795–816) loads the project with:
  - `'iiesExpenses.expenseDetails'` in `->with([...])`.
- So for the same request, `$project->iiesExpenses` is either:
  - The related `ProjectIIESExpenses` instance (if a row exists), or
  - Null (if no row in `project_IIES_expenses` for that `project_id`).

**Important:** The **IIES Estimated Expenses** partial (`resources/views/projects/partials/Show/IIES/estimated_expenses.blade.php`) uses:

- `$iiesExpenses = $project->iiesExpenses ?? new \App\Models\OldProjects\IIES\ProjectIIESExpenses();`
- Then displays `$iiesExpenses->iies_total_expenses`, `$iiesExpenses->iies_balance_requested`, etc.

So if “IIES Estimated Expenses show non-zero totals”, those values come from `$project->iiesExpenses` (same relation the resolver uses). That implies a row should exist for that project and be loaded on the same `$project` before the view.

**Conclusion:** If the UI really shows non-zero totals in Estimated Expenses from that partial, then for that request a row in `project_IIES_expenses` for that `project_id` should exist and `$project->iiesExpenses` should be non-null when the resolver runs. To confirm, run (read-only check):  
`SELECT * FROM project_IIES_expenses WHERE project_id = 'IIES-0039';` and verify one row and non-zero `iies_total_expenses` / `iies_balance_requested`.

---

## Section D — Column mapping match?

**Strategy (DirectMappedIndividualBudgetStrategy::resolveIIES):**  
Reads from `$expenses` (the `ProjectIIESExpenses` model):

| Strategy usage              | Property / column            |
|----------------------------|------------------------------|
| Overall budget             | `iies_total_expenses`        |
| Local contribution         | `iies_expected_scholarship_govt` + `iies_support_other_sources` + `iies_beneficiary_contribution` |
| “Sanctioned” (requested)   | `iies_balance_requested`     |

**Model & DB:**

- **Model:** `App\Models\OldProjects\IIES\ProjectIIESExpenses`, `$table = 'project_IIES_expenses'`.
- **Fillable:** `IIES_expense_id`, `project_id`, `iies_total_expenses`, `iies_expected_scholarship_govt`, `iies_support_other_sources`, `iies_beneficiary_contribution`, `iies_balance_requested`.
- **Migration** `2025_01_31_113236_create_project_i_i_e_s_expenses_table.php`: table `project_IIES_expenses` with columns `iies_total_expenses`, `iies_expected_scholarship_govt`, `iies_support_other_sources`, `iies_beneficiary_contribution`, `iies_balance_requested`.

**Conclusion:** Column names used in the strategy match the model and migration. No column mismatch identified.

---

## Section E — Why the resolver can return 0

**resolveIIES flow:**

1. `$expenses = $project->iiesExpenses;`
2. `if (!$expenses) return $this->fallbackFromProject($project);`
3. Otherwise it builds the array from `$expenses->iies_total_expenses`, etc.

**fallbackFromProject** returns `overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned`, `opening_balance` from the **projects** table. For IIES, those are often null/0 (not synced to project level), so the resolver would show zeros.

So the resolver returns 0 when:

1. **Strategy is not IIES**  
   If `project_type` does not strictly match `'Individual - Initial - Educational support'`, the default **PhaseBasedBudgetStrategy** is used. It uses `$project->budgets` (phase budgets). IIES has no phase budgets, so all resolved amounts are 0.

2. **resolveIIES hits the fallback**  
   If `$project->iiesExpenses` is null (no row in `project_IIES_expenses` for this `project_id`), resolveIIES returns `fallbackFromProject($project)` → zeros from `projects` table.

3. **Relation not loaded / wrong instance**  
   The same `$project` is used for both the resolver and the view. Show loads `iiesExpenses.expenseDetails` and does not reload the project, so in normal flow the relation is already loaded when the resolver runs. If in a different code path the project were loaded without `iiesExpenses`, or a different project instance were passed to the resolver, `iiesExpenses` could be null and trigger the fallback.

Given that the same partial (Estimated Expenses) uses `$project->iiesExpenses` and you report non-zero there, the most plausible explanation is **(1) project_type mismatch**, so the wrong strategy runs and returns zeros.

---

## Section F — Root cause classification

| Code | Description | Likely? |
|------|-------------|--------|
| **(A) Strategy mismatch** | `project_type` in DB ≠ `'Individual - Initial - Educational support'` (string/encoding), so PhaseBasedBudgetStrategy is used and returns 0 for IIES. | **Most likely** |
| **(B) Relation mismatch** | Relation name / FK / table wrong. | **No** — relation and table match. |
| **(C) Column mismatch** | Strategy uses wrong column names. | **No** — names match model and migration. |
| **(D) Data not persisted** | No row in `project_IIES_expenses` for this project. | **Unlikely** if Estimated Expenses really shows non-zero from the same partial (same relation). |
| **(E) Project type string mismatch** | Same as (A): exact `project_type` value (spaces, hyphen, encoding) does not match the constant. | **Most likely** |

**Recommended verification (read-only):**

1. For `project_id = 'IIES-0039'`:  
   `SELECT project_id, project_type, HEX(project_type) FROM projects WHERE project_id = 'IIES-0039';`  
   Compare `project_type` and its hex to the exact constant string.
2. Confirm a row exists and has non-zero amounts:  
   `SELECT project_id, iies_total_expenses, iies_balance_requested FROM project_IIES_expenses WHERE project_id = 'IIES-0039';`
3. In code (temporary, then remove): log `$project->project_type`, `in_array($project->project_type, \App\Domain\Budget\ProjectFinancialResolver::DIRECT_MAPPED_INDIVIDUAL_TYPES, true)`, and `$project->iiesExpenses?->exists` (or `!is_null($project->iiesExpenses)`) immediately before `$resolver->resolve($project)` in ProjectController@show for that project.

**Summary:** Strategy selection and relation/column setup are correct in code. The most likely reason for 0 in General Info is **(A)/(E) project_type string mismatch**, so the default phase-based strategy runs and returns zeros. Verifying the stored `project_type` and relation row for IIES-0039 as above will confirm.
