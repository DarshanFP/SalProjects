# Approvals and Post-Approval Reporting – Budget Integration Recommendations

## 1. Purpose and relation to Basic Info mapping

This document recommends how to **integrate the Basic Info fund mapping** (defined in `Basic_Info_Fund_Fields_Mapping_Analysis.md`) into:

1. **Approval** – coordinator (and general-as-coordinator) budget validation and computation of `amount_sanctioned` / `opening_balance`.
2. **Post-approval reporting** – monthly report create/edit, statements of account, budget validation, and any dashboards/aggregates that rely on project budget.

For **Development Projects** (and types using the standard budget partial), the five Basic Info fields already live on `projects` and are used consistently in approvals and reports. For **IIES, IES, ILP, IAH, IGE**, budget data lives in type-specific tables; today approval and reporting read only from `projects`, so those fields are often **Rs. 0.00** and approval/reporting behave incorrectly.

The aim is to use the same **resolved** values (Overall Project Budget, Amount Forwarded, Local Contribution, Amount Sanctioned, Opening Balance) everywhere, in line with the mapping in the companion doc.

---

## 2. Current behaviour – approvals

### 2.1 Coordinator approval

**File:** `app/Http/Controllers/CoordinatorController.php`  
**Method:** `approveProject()` (approx. lines 1064–1164).

**Flow:**

1. Project is loaded with `->with('budgets')` (development budgets only).
2. After `ProjectStatusService::approve()`, the controller reads:
   - `$overallBudget = $project->overall_project_budget ?? 0`
   - `$amountForwarded = $project->amount_forwarded ?? 0`
   - `$localContribution = $project->local_contribution ?? 0`
3. **Fallback for overall:**  
   If `$overallBudget == 0` and `$project->budgets` has rows, then  
   `$overallBudget = $project->budgets->sum('this_phase')`.  
   No fallback for IIES/IES/ILP/IAH/IGE.
4. Validation: `(amountForwarded + localContribution) <= overallBudget`.  
   If individual-type projects have zeros on `projects`, this either fails incorrectly or allows invalid combinations.
5. Computation:
   - `amount_sanctioned = overallBudget - (amountForwarded + localContribution)`
   - `opening_balance = amount_sanctioned + (amountForwarded + localContribution)`
6. These are written to `$project->amount_sanctioned`, `$project->opening_balance` and saved.

So approval today assumes **either** `projects.overall_project_budget` **or** `projects.budgets`; it never looks at IIES expenses, IES expenses, ILP/IAH/IGE budget tables.

### 2.2 General (approve as coordinator)

**File:** `app/Http/Controllers/GeneralController.php`  
**Method:** Approval handler when `approval_context === 'coordinator'` (approx. lines 2541–2573).

Logic is **the same** as CoordinatorController:

- Reads `overall_project_budget`, `amount_forwarded`, `local_contribution` from `$project`.
- Fallback for overall only from `$project->budgets->sum('this_phase')`.
- Validates `combinedContribution <= overallBudget`, then computes and saves `amount_sanctioned` and `opening_balance`.

Same gap: no use of type-specific budget/expense data for IIES/IES/ILP/IAH/IGE.

---

## 3. Current behaviour – post-approval reporting

### 3.1 Monthly report create and edit

**File:** `app/Http/Controllers/Reports/Monthly/ReportController.php`

- **create()** (approx. lines 86–97):  
  `$amountSanctioned = $project->amount_sanctioned ?? 0.00`,  
  `$amountForwarded = 0.00`.  
  Passed to the view (and into statements of account).

- **edit()** (approx. lines 1298–1301):  
  Same: `$amountSanctioned = $project->amount_sanctioned ?? 0.00`,  
  `$amountForwarded = 0.00`.

So report create/edit **always** take amount sanctioned (and forwarded) from `projects`. There is no project-type-specific resolution. If `projects.amount_sanctioned` was never set (e.g. approval used zeros for an individual-type project), reports show Rs. 0.00.

### 3.2 Statements of account

**File:** `resources/views/reports/monthly/partials/statements_of_account.blade.php`

- Dispatches by `$project->project_type` to type-specific partials:
  - `individual_education` (IIES), `individual_ongoing_education` (IES), `individual_health` (IAH), `individual_livelihood` (ILP), `institutional_education` (IGE), `development_projects`, etc.
- Passes `amountSanctioned`, `amountForwarded` into each partial.

Type-specific partials (e.g. under `partials/statements_of_account/individual_education.blade.php`, `…/edit/statements_of_account/…`) use:

- `$amountSanctioned` (and sometimes `$report->amount_sanctioned_overview`) for “Amount Sanctioned” and “Amount in hand”.
- Row-level amounts come from `BudgetCalculationService::getBudgetsForReport()` (via `$budgets`), which already uses type-specific strategies; but the **overview** sanctioned/forwarded still comes from the controller, i.e. from `$project->amount_sanctioned` / `$project->amount_forwarded`.

So statements of account **do** vary layout by type, but the **top-level sanctioned/forwarded** are still from `projects` only.

### 3.3 BudgetCalculationService

**File:** `app/Services/Budget/BudgetCalculationService.php`

- `getBudgetsForReport($project, $calculateContributions)` uses `config('budget.field_mappings')` and strategy classes to return **budget rows** (with amounts, contributions, etc.) per project type.
- This drives **row-level** data in statements of account (particulars, amount sanctioned per line, etc.).
- It does **not** provide “resolved” Overall / Amount Forwarded / Local Contribution / Amount Sanctioned / Opening Balance at project level. Those are still taken from `$project` by the controller and views.

So the service is the right place for **row** logic; the missing piece is a single place that resolves the **five Basic Info fund fields** for any project type (including individual/IGE).

### 3.4 BudgetValidationService

**File:** `app/Services/BudgetValidationService.php`

- `calculateBudgetData($project)` (approx. lines 47–120):
  - `overall_project_budget` from `$project->overall_project_budget`; if 0 and `budgets` loaded, uses `$project->budgets->sum('this_phase')`.
  - `amount_forwarded` / `local_contribution` from `$project`.
  - `amount_sanctioned` / `opening_balance` are **computed** from those (same formula as approval).
- Used for validation/warnings (negative balance, over budget, etc.). If `projects` has zeros for individual types, validation is wrong or noisy.

### 3.5 Other consumers of project budget

- **ProvincialController:** uses `$project->amount_sanctioned`, `overall_project_budget` for project lists, approved-project dashboards, and budget-by-type/center aggregates (e.g. sums of `amount_sanctioned`).
- **CoordinatorController / ExecutorController / GeneralController:** various dashboard and list views use `amount_sanctioned` or `overall_project_budget` for display and totals.

All of these assume usable values on `projects`; they do not resolve from type-specific tables.

---

## 4. Recommended approach – single source of truth via sync to `projects`

To align with “common budgets” (development) and minimise branching in controllers/views, the recommendation is:

**Use `projects` as the single source of truth for the five fund fields everywhere (approval, reporting, Basic Info, validation, dashboards).**  
For IIES/IES/ILP/IAH/IGE, **sync** type-specific budget data into `projects` at defined points so that approval and reporting can keep reading only from `$project`.

Concretely:

1. **Define a “resolved fund values” helper** that, for a given project type, returns the five values using the mapping in `Basic_Info_Fund_Fields_Mapping_Analysis.md` (either from `projects` or from type-specific tables).
2. **Sync to `projects`** at:
   - **Save/update of type-specific budget** (IIES expenses, IES expenses, ILP budget, IAH budget details, IGE budget), and
   - **Approval**: before validation and computation, ensure `overall_project_budget`, `amount_forwarded`, `local_contribution` (and, if desired, `amount_sanctioned` / `opening_balance`) are set from the resolved helper, then run the existing validation and computation and save.
3. **Reporting and validation** keep using `$project->amount_sanctioned`, `overall_project_budget`, etc.; no change to ReportController or statement-of-account **source** of sanctioned/forwarded, as long as approval (and any pre-approval save) has already synced.

This matches Option B in the first document (“Sync to projects table”) and keeps approval/reporting logic simple while making individual-type projects behave like development projects from the point of view of approvals and reports.

---

## 5. Per-type mapping (for resolver and sync)

The following repeats the mapping from `Basic_Info_Fund_Fields_Mapping_Analysis.md` in a compact form, so the resolver and sync logic can be implemented from this doc alone. Source tables/models are as in that document.

| Project type | Overall | Amount Forwarded | Local Contribution | Amount Sanctioned | Opening Balance |
|--------------|---------|------------------|--------------------|-------------------|------------------|
| **Development / RST / etc.** | `projects.overall_project_budget` | `projects.amount_forwarded` | `projects.local_contribution` | From project or computed | From project or computed |
| **IIES** | `iies_total_expenses` | 0 (or `projects.amount_forwarded`) | `iies_expected_scholarship_govt + iies_support_other_sources + iies_beneficiary_contribution` | `iies_balance_requested` | `iies_total_expenses` (or sanctioned + local when forwarded = 0) |
| **IES** | `total_expenses` | 0 | `expected_scholarship_govt + support_other_sources + beneficiary_contribution` | `balance_requested` | `total_expenses` |
| **ILP** | `sum(cost)` | 0 | `beneficiary_contribution` | `amount_requested` | same as overall |
| **IAH** | `sum(amount)` | 0 | `family_contribution` | `amount_requested` | same as overall |
| **IGE** | `sum(total_amount)` | 0 | `sum(scholarship_eligibility) + sum(family_contribution)` | `sum(amount_requested)` | same as overall |

For Development (and similar) types, the “resolver” can return `projects.*` and optionally leave Amount Sanctioned/Opening Balance to the existing approval formula. For individual/IGE types, the resolver should return the type-specific values above so that approval and sync write the same numbers to `projects`.

---

## 6. Implementation recommendations

### 6.1 New or extended code units

1. **ProjectFundFieldsResolver (or extend BudgetCalculationService)**  
   - **Responsibility:** For any `Project`, return the five fund values (overall, forwarded, local, sanctioned, opening) using the mapping in Section 5.  
   - **Input:** `Project` (with needed relations loaded for its `project_type`).  
   - **Output:** e.g. `['overall_project_budget' => …, 'amount_forwarded' => …, 'local_contribution' => …, 'amount_sanctioned' => …, 'opening_balance' => …]`.  
   - **Placement:** e.g. `app/Services/Budget/ProjectFundFieldsResolver.php` or a dedicated method in a small service used by sync and approval.  
   - **Reference:** Table in Section 5 and Section 8 / 10 of `Basic_Info_Fund_Fields_Mapping_Analysis.md`.

2. **Sync type-specific → projects**  
   - **When:**  
     - On **store/update** of type-specific budget/expense (IIES expenses, IES expenses, ILP budget, IAH budget details, IGE budget).  
     - Optionally on **approval** (see 6.2), to correct any case where the project was approved before sync existed.  
   - **How:** Call the resolver; write the five values (or the subset that the type defines) to `$project` and save.  
   - **Where:** In the controllers or service methods that persist those type-specific models (or in model observers if you prefer).

### 6.2 Approval flow

**CoordinatorController::approveProject() and GeneralController (approve as coordinator):**

1. **Before** the existing budget block:  
   If the project type is IIES/IES/ILP/IAH/IGE, call the resolver and **update** `$project` with the resolved overall, forwarded, local (and optionally sanctioned/opening). Persist so that the following logic sees non-zero values for individual types when appropriate.
2. **Keep** the existing logic that:  
   - reads overall/forwarded/local from `$project`,  
   - uses `budgets->sum('this_phase')` as fallback for **development** types when overall is 0,  
   - validates `(amountForwarded + localContribution) <= overallBudget`,  
   - computes `amount_sanctioned` and `opening_balance` and saves them on `$project`.

For individual/IGE types, after sync, the “overall” and “local” will already be filled from type-specific data, so validation and computation will use the correct numbers. You may still allow approval to **overwrite** `amount_sanctioned` and `opening_balance` with the computed values so they stay consistent with the shared formula.

**Files to change:**

- `app/Http/Controllers/CoordinatorController.php` – at the start of the budget block in `approveProject()` (around line 1111).
- `app/Http/Controllers/GeneralController.php` – at the start of the budget block in the coordinator-approval branch (around line 2544).

### 6.3 Post-approval reporting

- **ReportController::create() / edit():**  
  No structural change required: keep using `$amountSanctioned = $project->amount_sanctioned ?? 0.00` and `$amountForwarded = 0.00` (or `$project->amount_forwarded` if you ever use it again). Once approval (and pre-approval sync) write correct values to `projects`, reporting will show them.

- **Statements of account:**  
  No change to where `amountSanctioned` / `amountForwarded` come from; they already receive them from the controller. Ensuring approval and sync populate `projects` is enough.

- **BudgetCalculationService:**  
  Keep as-is for **row-level** report budgets. It does not need to implement the five project-level fields; that stays with the resolver and `projects`.

- **BudgetValidationService::calculateBudgetData():**  
  Either:  
  (A) Use the new resolver when `$project->overall_project_budget` is 0 (and optionally when type is IIES/IES/ILP/IAH/IGE) and feed the returned values into the rest of `calculateBudgetData`, or  
  (B) Rely on sync: assume that by the time validation runs, `projects` is already synced for individual types, and keep current logic.  
  Option B is simpler if you always sync on save of type-specific budget and on approval.

**Files to touch only if you add a fallback in validation:**

- `app/Services/BudgetValidationService.php` – `calculateBudgetData()`: optionally call resolver when overall is 0 (and project type is individual/IGE).

### 6.4 Dashboards and aggregates

- **ProvincialController, CoordinatorController, GeneralController, ExecutorController:**  
  They already use `$project->amount_sanctioned` and `overall_project_budget`. Once sync (and approval) write the correct numbers to `projects`, these will be right for individual/IGE types as well.  
  No code changes required unless you want an explicit fallback (e.g. “if amount_sanctioned is 0 and type is IIES, call resolver”) for legacy data that was never synced.

### 6.5 Where to implement the resolver’s type-specific logic

Use the same type–model–table references as in `Basic_Info_Fund_Fields_Mapping_Analysis.md`, Section 10, and optionally `config/budget.php` (e.g. model and strategy per type). The resolver may load:

- IIES: `ProjectIIESExpenses` (with aggregated or first-record fields: `iies_total_expenses`, `iies_expected_scholarship_govt`, `iies_support_other_sources`, `iies_beneficiary_contribution`, `iies_balance_requested`).
- IES: `ProjectIESExpenses` (`total_expenses`, `expected_scholarship_govt`, `support_other_sources`, `beneficiary_contribution`, `balance_requested`).
- ILP: `ProjectILPBudget` (sum of `cost`, and first-row or aggregated `beneficiary_contribution`, `amount_requested`).
- IAH: `ProjectIAHBudgetDetails` (sum of `amount`, and first-row or aggregated `family_contribution`, `amount_requested`).
- IGE: `ProjectIGEBudget` (sums of `total_amount`, `scholarship_eligibility`, `family_contribution`, `amount_requested`).

Exact field names and aggregation (e.g. first row vs sum) should match the analysis doc and existing controllers for those types.

---

## 7. Files and entry points – checklist

| Area | File | Entry point / what to do |
|------|------|---------------------------|
| **Resolver** | New: `app/Services/Budget/ProjectFundFieldsResolver.php` (or similar) | Implement `resolve(Project $project): array` using Section 5 mapping; load type-specific relations by `project_type`. |
| **Sync on save** | IIES expense store/update | After saving IIES expenses, call resolver and update `$project` with the five values, then `$project->save()`. |
| | IES expense store/update | Same for IES. |
| | ILP budget store/update | Same for ILP. |
| | IAH budget details store/update | Same for IAH. |
| | IGE budget store/update | Same for IGE. |
| **Approval** | `CoordinatorController.php` | In `approveProject()`, before reading overall/forwarded/local, sync from resolver for IIES/IES/ILP/IAH/IGE (e.g. lines ~1111–1116). |
| | `GeneralController.php` | In coordinator-approval branch, same sync before budget validation (~2544–2549). |
| **Reporting** | `ReportController.php` | No change if sync/approval are in place; optional: use resolver as fallback when `amount_sanctioned` is 0 and type is individual/IGE. |
| **Validation** | `BudgetValidationService.php` | Optional: in `calculateBudgetData()`, when `overall_project_budget` is 0 and type is IIES/IES/ILP/IAH/IGE, use resolver and use its output instead of `projects` for that call. |
| **Basic Info view** | `resources/views/projects/partials/Show/general_info.blade.php` | Can stay as-is if sync is done on save (and approval); else implement view-time resolution from the first doc’s Option A. |

---

## 8. Order of implementation

1. Implement **ProjectFundFieldsResolver** and unit/local tests for each project type (IIES, IES, ILP, IAH, IGE), using the mapping table in Section 5.
2. Add **sync-on-save** in the type-specific budget/expense flows so that new or updated data updates `projects`.
3. Add **sync (or explicit resolver call) in approval** so that even projects that were never synced before get correct values at approval time.
4. Run approval and reporting for sample IIES/IES/ILP/IAH/IGE projects and confirm amounts in Basic Info, approval summary, and report statements of account.
5. Optionally add a **resolver fallback** in `BudgetValidationService` and in ReportController for legacy data, then backfill `projects` for existing individual-type projects via a one-off command or migration script.

---

## 9. Summary

- **Approvals** today use only `projects` and `projects.budgets`; individual/IGE types are not handled, so validation and stored amounts are wrong or zero.
- **Reporting** (create/edit and statements of account) uses `$project->amount_sanctioned` (and forwarded); it does not resolve from type-specific tables.
- **Recommendation:** Introduce a **ProjectFundFieldsResolver** from the mapping in `Basic_Info_Fund_Fields_Mapping_Analysis.md`, **sync** those values to `projects` on type-specific budget save and at approval, and leave approval/reporting/validation/dashboards **reading only from `projects`**. That keeps behaviour aligned with “common budgets” and limits changes to a resolver, sync points, and two approval code paths.

---

*Document version: 1.0 – Recommendations for integrating Basic Info fund mapping into approvals and post-approval reporting. Companion to `Basic_Info_Fund_Fields_Mapping_Analysis.md`.*
