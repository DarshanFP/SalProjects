# M3.1 — Resolver & Financial Computation Audit

**Milestone:** M3 — Resolver Parity & Financial Stability  
**Step:** M3.1 — Comprehensive Resolver & Financial Calculation Audit  
**Mode:** STRICTLY READ-ONLY (No Code Changes)  
**Date:** 2025-02-15

---

## SECTION 1 — Identify All Resolver-Like Components

| File | Class | Method | What It Calculates | Used By |
|------|-------|--------|--------------------|---------|
| `app/Domain/Budget/ProjectFinancialResolver.php` | ProjectFinancialResolver | `resolve()` | overall_project_budget, amount_forwarded, local_contribution, amount_sanctioned, opening_balance | ProjectController, ProvincialController, GeneralController, CoordinatorController, ExecutorController, AdminReadOnlyController, BudgetValidationService |
| `app/Services/Budget/ProjectFundFieldsResolver.php` | ProjectFundFieldsResolver | `resolve()` | Same as above; delegates to ProjectFinancialResolver | Tests (ViewEditParityTest, ProjectFinancialResolverParityTest) |
| `app/Domain/Budget/Strategies/PhaseBasedBudgetStrategy.php` | PhaseBasedBudgetStrategy | `resolve()` | Phase-based: sum(this_phase) for overall; sanctioned = overall - (forwarded+local); opening = sanctioned + forwarded + local; uses DB when approved | ProjectFinancialResolver |
| `app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php` | DirectMappedIndividualBudgetStrategy | `resolve()` | IIES/IES/ILP/IAH/IGE: from type-specific tables (iiesExpenses, iesExpenses, ilpBudget, iahBudgetDetails, igeBudget) | ProjectFinancialResolver |
| `app/Services/Budget/DerivedCalculationService.php` | DerivedCalculationService | `calculateRowTotal()`, `calculatePhaseTotal()`, `calculateProjectTotal()`, `calculateRemainingBalance()`, `calculateUtilization()` | Row total (q×m×d), phase total, project total, remaining balance, utilization % | ProjectFinancialResolver strategies, BudgetValidationService, ProvincialController, CoordinatorController, ExecutorController, GeneralController, ExportController |
| `app/Services/Budget/BudgetCalculationService.php` | BudgetCalculationService | `calculateContributionPerRow()`, `calculateTotalContribution()`, `calculateAmountSanctioned()`, `getBudgetsForReport()`, `getBudgetsForExport()` | Contribution per row, total contribution, amount sanctioned after contribution; budgets for reports | ReportController, ReportMonitoringService, ExportReportController, SingleSourceContributionStrategy, MultipleSourceContributionStrategy |
| `app/Services/BudgetValidationService.php` | BudgetValidationService | `calculateBudgetData()` (private), `validateBudget()`, `getBudgetSummary()` | Budget data via resolver + expenses aggregation; validation checks | budget.blade.php (Show), validation flows |
| `app/Models/OldProjects/ProjectBudget.php` | ProjectBudget | `getThisPhaseAttribute()`, `getRemainingBalanceAttribute()` | Row total (via DerivedCalculationService), remaining balance | Budget model accessors |

---

## SECTION 2 — Identify All Budget Total Calculations

| File | Method | Calculation Logic | Context (View / Controller / Service / Report / Export) |
|------|--------|-------------------|---------------------------------------------------------|
| `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php` | store/update | `array_sum($amounts)` for total expenses | Controller |
| `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php` | - | `$budgetDetails->sum('amount')` | Controller |
| `app/Http/Controllers/Projects/ProjectController.php` | create | `$phase->sum('amount')` for amount_sanctioned per phase (predecessor) | Controller |
| `app/Http/Controllers/ProvincialController.php` | calculateBudgetSummariesFromProjects | `$report->accountDetails->sum('total_expenses')` | Controller |
| `app/Http/Controllers/ProvincialController.php` | calculateBudgetSummaries | `$report->accountDetails->sum('total_amount')`, `sum('total_expenses')`, `sum('balance_amount')` | Controller |
| `app/Http/Controllers/ProvincialController.php` | calculateTeamStats | `$teamMembers->sum('projects_count')`, `sum('reports_count')` | Controller |
| `app/Http/Controllers/ProvincialController.php` | calculateCenterPerformance | `$approvedProjects->sum('amount_sanctioned')` | Controller — **DB direct, not resolver** |
| `app/Http/Controllers/ProvincialController.php` | calculateBudgetSummaries, calculateEnhancedBudgetData | `$approvedProjects->sum(fn => resolvedFinancials['opening_balance'])` | Controller — **resolver-based** |
| `app/Services/BudgetValidationService.php` | calculateBudgetData | `$phaseBudgets->sum('this_phase')` for budget_items_total | Service |
| `app/Services/BudgetValidationService.php` | calculateBudgetData | `$report->accountDetails->sum('total_expenses')` | Service |
| `app/Services/BudgetValidationService.php` | checkTotalsMatch | `calculatedOpening = amount_sanctioned + amount_forwarded + local_contribution` | Service |
| `app/Services/BudgetValidationService.php` | checkTotalsMatch | `calculatedRemaining = opening_balance - total_expenses` | Service |
| `resources/views/projects/partials/Show/budget.blade.php` | inline @php | `$budgetsForShow->sum('rate_quantity')`, `sum('rate_multiplier')`, `sum('rate_duration')`, `sum('this_phase')` | View |
| `resources/views/projects/partials/Edit/RST/beneficiaries_area.blade.php` | inline | `$beneficiariesArea->sum('direct_beneficiaries')`, `sum('indirect_beneficiaries')` | View |
| `resources/views/projects/partials/RST/beneficiaries_area.blade.php` | inline | `collect($beneficiaries)->sum('direct')`, `sum('indirect')`, `sum('direct')+sum('indirect')` | View |
| `resources/views/projects/partials/Show/RST/beneficiaries_area.blade.php` | inline | `$RSTBeneficiariesArea->sum('direct_beneficiaries')`, `sum('indirect_beneficiaries')` | View |
| `resources/views/projects/partials/Show/IAH/budget_details.blade.php` | inline | `$IAHBudgetDetails->sum('amount')` | View |
| `app/Http/Controllers/Projects/ExportController.php` | addBudgetSection | `$budgets->sum('rate_quantity')`, `sum('rate_multiplier')`, `sum('rate_duration')`, `sum('rate_increase')` | Export |
| `app/Http/Controllers/Projects/ExportController.php` | addBudgetSection | `$this->calculationService->calculateProjectTotal($budgets->map(...))` for this_phase, next_phase | Export |
| `app/Http/Controllers/GeneralController.php` | - | `$report->accountDetails->sum('total_amount')`, `sum('total_expenses')`, `sum('expenses_this_month')`, `sum('balance_amount')` | Controller |
| `app/Http/Controllers/GeneralController.php` | - | `$allReports->sum('total_amount')`, `sum('total_expenses')`, etc. | Controller |
| `app/Http/Controllers/GeneralController.php` | - | `$projects->sum('calculated_budget')`, `sum('calculated_expenses')`, etc. | Controller |
| `app/Http/Controllers/GeneralController.php` | - | `$projects->sum(fn => resolvedFinancials['opening_balance'])` | Controller |
| `app/Http/Controllers/CoordinatorController.php` | - | `$report->accountDetails->sum('total_amount')`, `sum('total_expenses')`, `sum('balance_amount')` | Controller |
| `app/Http/Controllers/CoordinatorController.php` | - | `$approvedProjects->sum(fn => resolvedFinancials['opening_balance'])` | Controller |
| `app/Http/Controllers/CoordinatorController.php` | - | `return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0` (fallback) | Controller |
| `app/Http/Controllers/GeneralController.php` | - | `selectRaw('project_id, SUM(CAST(total_expenses AS DECIMAL(15,2))) as total_expenses')` | Controller |

**Manual addition patterns:**
- PhaseBasedBudgetStrategy: `$opening = $sanctioned + $forwarded + $local`
- DirectMappedIndividualBudgetStrategy (IIES/IES): `$local = scholarship + support + beneficiary` (inline addition)
- BudgetValidationService: `$calculatedOpening = amount_sanctioned + amount_forwarded + local_contribution`
- provincial/index.blade.php: `$remainingPercent = ($totalRemaining / $totalBudget) * 100`
- coordinator/budget-overview.blade.php: `$item['budget'] - $item['expenses']` for remaining

---

## SECTION 3 — Role-Based Financial Logic Differences

| Role | File | Method | Financial Logic Difference |
|------|------|--------|----------------------------|
| Provincial | `ProvincialController.php` | calculateCenterPerformance | Uses `$approvedProjects->sum('amount_sanctioned')` — **DB column direct** |
| Provincial | `ProvincialController.php` | calculateBudgetSummariesFromProjects, calculateEnhancedBudgetData | Uses resolver `opening_balance` |
| Coordinator | `CoordinatorController.php` | approveProject, aggregation methods | Uses resolver for approve; uses `resolvedFinancials['opening_balance']` for aggregation |
| Coordinator | `CoordinatorController.php` | Fallback in some aggregation | `$p->amount_sanctioned ?? $p->overall_project_budget ?? 0` when resolver not used |
| General | `GeneralController.php` | approveProject, aggregation | Same as Coordinator — resolver for approve and aggregation |
| Executor | `ExecutorController.php` | Dashboard, project lists | Uses resolver `opening_balance` |
| Admin | `AdminReadOnlyController.php` | - | Uses resolver |
| Executor/Applicant | Report views | budget_monitoring, objectives | `$showByRole = in_array(auth()->user()->role, ['provincial','coordinator'])` — **hides budget monitoring from executor** |
| Provincial/Coordinator | Report views | pmc_comments, activity_monitoring | Different visibility for comments and monitoring |

**Key finding:** Provincial `calculateCenterPerformance` uses `amount_sanctioned` (DB) while other provincial methods use resolver `opening_balance`. Since `opening_balance = amount_sanctioned + amount_forwarded + local_contribution`, these produce different totals.

---

## SECTION 4 — Resolver vs Database Stored Values

| Field | Stored in DB? | Computed? | Source of Truth | Risk Level |
|-------|---------------|-----------|-----------------|------------|
| overall_project_budget | Yes (projects table) | Yes (resolver: sum(this_phase) for phase-based when rows exist) | Resolver when budget rows exist; DB fallback | Medium — view/export may use DB |
| amount_forwarded | Yes | No (read from DB) | DB | Low |
| local_contribution | Yes | No (read from DB) | DB | Low |
| amount_sanctioned | Yes (persisted on approve) | Yes (resolver: overall - (forwarded+local) when not approved) | Resolver pre-approve; DB post-approve | Medium — PDF fallback formula differs |
| opening_balance | Yes (persisted on approve) | Yes (resolver: sanctioned + forwarded + local when not approved) | Resolver pre-approve; DB post-approve | Medium |
| total_expenses | No (aggregated) | Yes (sum of report accountDetails) | Computed from reports | Low |
| remaining_balance | No | Yes (opening_balance - total_expenses) | DerivedCalculationService | Low |
| percentage_used / utilization | No | Yes | DerivedCalculationService | Low |
| budget_items_total | No | Yes (phaseBudgets->sum('this_phase')) | BudgetValidationService | Low |

**Mismatch risks:**
- ExportController Key Information uses `$project->overall_project_budget`, `$project->amount_sanctioned`, `$project->opening_balance` — for phase-based projects with budget rows, these may not match resolver.
- ProvincialController calculateCenterPerformance uses `sum('amount_sanctioned')` — should use `opening_balance` for consistency (or document intentional difference).
- pdf.blade.php: `$project->amount_sanctioned ?? max(0, overall - (forwarded + local))` — fallback formula matches PhaseBasedBudgetStrategy but is inline in view.

---

## SECTION 5 — Submission & Approval Flow Impact

| Flow Stage | File | Financial Calculation Occurring? | Description |
|------------|------|----------------------------------|-------------|
| submit (submitToProvincial) | ProjectStatusService | No | Only status change; no financial recalculation |
| forward (forwardToCoordinator) | ProjectStatusService | No | Only status change |
| approve (approve / approveAsCoordinator) | CoordinatorController, GeneralController | **Yes** | Resolver is called; `amount_sanctioned` and `opening_balance` are persisted to project |
| sanction | N/A (approve = sanction) | - | Same as approve |
| revert (revertByProvincial, revertByCoordinator) | ProjectStatusService | No | Only status change; DB amount_sanctioned/opening_balance are NOT cleared or recalculated |

**Critical:** On approve, financials are persisted from resolver. On revert, the stored values remain. If executor edits budget after revert, the stored amount_sanctioned/opening_balance may become stale until next approve.

---

## SECTION 6 — View-Level Calculations

| File | Calculation | Notes |
|------|-------------|-------|
| `resources/views/projects/partials/Show/budget.blade.php` | `$budgetsForShow->sum('rate_quantity')`, `sum('rate_multiplier')`, `sum('rate_duration')`, `sum('this_phase')` | Phase-filtered budget rows totals |
| `resources/views/projects/partials/RST/beneficiaries_area.blade.php` | `collect($beneficiaries)->sum('direct')`, `sum('indirect')`, `sum('direct')+sum('indirect')` | Beneficiary totals |
| `resources/views/projects/partials/Edit/RST/beneficiaries_area.blade.php` | `$beneficiariesArea->sum('direct_beneficiaries')`, `sum('indirect_beneficiaries')` | Edit form totals |
| `resources/views/projects/partials/Show/RST/beneficiaries_area.blade.php` | `$RSTBeneficiariesArea->sum('direct_beneficiaries')`, `sum('indirect_beneficiaries')` | Show totals |
| `resources/views/projects/partials/Show/IAH/budget_details.blade.php` | `$IAHBudgetDetails->sum('amount')` | IAH budget total |
| `resources/views/coordinator/budget-overview.blade.php` | `collect($provinces)->sum(function($data) { return $data['total_budget']; })` | Aggregation from controller data |
| `resources/views/provincial/index.blade.php` | `$remainingPercent = ($totalRemaining / $totalBudget) * 100`, `100 - $approvedPercent` | Percentage calculations |
| `resources/views/general/widgets/budget-charts.blade.php` | `($item['budget'] ?? 0) - ($item['approved_expenses'] ?? 0)` | Remaining per item |
| `resources/views/projects/Oldprojects/pdf.blade.php` | `$project->amount_sanctioned ?? max(0, ($project->overall_project_budget ?? 0) - (($project->amount_forwarded ?? 0) + ($project->local_contribution ?? 0)))` | **Inline fallback for amount_sanctioned** |

**JavaScript (client-side):**
- `public/js/budget-calculations.js`: `calculateRowTotal`, `calculatePhaseTotal`, `calculateProjectTotal`, `calculateRemainingBalance`, `calculateAmountSanctioned` — matches DerivedCalculationService
- Blade-compiled views (storage/framework/views): IES/IIES `IEScalculateTotalExpenses()`, `IEScalculateBalanceRequested()`, `calculateRowTotal`, `calculateProjectTotal`, `calculateAmountSanctioned` — form input handlers

---

## SECTION 7 — Duplication Detection

| Calculation | Location 1 | Location 2 | Location 3 | Notes |
|-------------|------------|------------|------------|-------|
| overall_project_budget (phase-based) | PhaseBasedBudgetStrategy: sum(this_phase) | BudgetValidationService: phaseBudgets->sum('this_phase') as budget_items_total | budget.blade.php: budgetsForShow->sum('this_phase') | Same logic, different scopes |
| amount_sanctioned | PhaseBasedBudgetStrategy: overall - (forwarded+local) | budget-calculations.js: calculateAmountSanctioned | pdf.blade.php: max(0, overall - (forwarded+local)) | Duplicated formula |
| opening_balance | PhaseBasedBudgetStrategy: sanctioned + forwarded + local | BudgetValidationService: checkTotalsMatch calculatedOpening | - | Validation uses same formula |
| Row total (q×m×d) | DerivedCalculationService::calculateRowTotal | budget-calculations.js calculateRowTotal | ProjectBudget getThisPhaseAttribute | Intentionally duplicated (JS parity) |
| Phase/project total | DerivedCalculationService::calculateProjectTotal | ExportController: calculationService->calculateProjectTotal | budget-calculations.js calculateProjectTotal | Export uses service; JS for form |
| Budget aggregation (opening_balance) | CoordinatorController, GeneralController, ProvincialController (most) | ProvincialController calculateCenterPerformance: **amount_sanctioned** | - | **Inconsistency: one uses amount_sanctioned, others use opening_balance** |
| Remaining balance | DerivedCalculationService::calculateRemainingBalance | BudgetValidationService: opening_balance - total_expenses | Various views: budget - expenses | Service + inline |
| Utilization % | DerivedCalculationService::calculateUtilization | ProvincialController, etc. via $calc | Views: (x/y)*100 | Centralized in service |

---

## SECTION 8 — Financial Risk Map

| Finding | Risk Level | Description |
|---------|------------|-------------|
| ProvincialController calculateCenterPerformance uses `sum('amount_sanctioned')` instead of resolver `opening_balance` | **High** | Center budget total differs from other provincial aggregates; amount_sanctioned ≠ opening_balance |
| ExportController Key Information uses DB columns (overall_project_budget, amount_sanctioned, opening_balance) | **Medium** | For phase-based projects with budget rows, DB may not match resolver |
| pdf.blade.php inline amount_sanctioned fallback | **Medium** | Duplicate formula; could drift if resolver changes |
| CoordinatorController fallback `amount_sanctioned ?? overall_project_budget` when resolver not used | **Medium** | Different semantics than opening_balance; used in aggregation paths |
| Budget locked on approve; revert does not clear amount_sanctioned/opening_balance | **Low-Medium** | Stale values if budget edited after revert |
| Role-based visibility: budget monitoring hidden from executor | **Low** | Display difference only |
| View-level sum() in budget.blade.php | **Low** | Display-only; matches resolver scope when same phase filter |
| GeneralController calculated_budget, calculated_expenses (virtual attributes) | **Low** | Set from resolver; display-only |

**Critical risk:** Approval logic (CoordinatorController, GeneralController) uses resolver and persists correctly. Display logic in ProvincialController calculateCenterPerformance uses a different field (amount_sanctioned vs opening_balance), producing inconsistent center totals.

---

## SECTION 9 — Final Audit Summary

### 1) How many independent total calculation paths exist?

**At least 8 distinct paths:**
1. **ProjectFinancialResolver** (with PhaseBasedBudgetStrategy, DirectMappedIndividualBudgetStrategy) — canonical project-level financials
2. **BudgetValidationService** — budget data + expenses + validation (uses resolver)
3. **ProvincialController** — mix: resolver (opening_balance) in most methods, DB sum(amount_sanctioned) in calculateCenterPerformance
4. **CoordinatorController / GeneralController** — resolver for aggregation; fallback amount_sanctioned|overall_project_budget in some code paths
5. **ExportController** — DB columns for Key Information; DerivedCalculationService + budgets->sum for budget section
6. **View layer** — budgetsForShow->sum, collect()->sum in various blades
7. **JavaScript** — budget-calculations.js (form parity)
8. **BudgetCalculationService** — separate path for report contribution calculations (SingleSource/MultipleSource strategies)

### 2) Is there a single source of truth?

**No.** ProjectFinancialResolver is the intended source for project-level fund fields (overall_project_budget, amount_sanctioned, opening_balance), but:
- ExportController bypasses it for Key Information
- ProvincialController calculateCenterPerformance bypasses it (uses amount_sanctioned)
- DB columns are used directly in many places
- BudgetCalculationService is a separate path for reports

### 3) Are totals centralized?

**Partially.** DerivedCalculationService centralizes arithmetic (row total, project total, remaining, utilization). ProjectFinancialResolver centralizes project-level resolution. But aggregation across projects is done differently in ProvincialController (amount_sanctioned vs opening_balance), and views perform their own sums.

### 4) Is resolver authoritative or just decorative?

**Authoritative for approve flow and most dashboards.** Coordinator/General approve persists resolver values. Provincial/Coordinator/Executor/General dashboards use resolver opening_balance for aggregation in most places. However, ProvincialController calculateCenterPerformance and ExportController Key Information do not use resolver for key totals.

### 5) Are financial numbers consistent across roles?

**No.** Provincial center performance uses amount_sanctioned (smaller than opening_balance). Other provincial views use opening_balance. Executor sees budget monitoring only when permitted by role; numbers themselves are from same resolver where used.

### 6) Is there calculation duplication?

**Yes, extensive:**
- amount_sanctioned formula: PhaseBasedBudgetStrategy, budget-calculations.js, pdf.blade.php
- overall_project_budget (sum this_phase): Resolver, BudgetValidationService, budget.blade.php
- Row total: DerivedCalculationService, JS, ProjectBudget accessor
- Remaining, utilization: Service + inline in views

### 7) Is M3 high-risk or moderate-risk?

**M3 is HIGH-RISK.**

Reasons:
- **Critical:** Provincial center budget uses `amount_sanctioned` while rest of system uses `opening_balance` — different totals for same concept
- **Critical:** Approval flow correctly persists resolver values, but display/export bypass resolver in multiple places
- **Medium:** Export and PDF use DB or inline formulas that can diverge from resolver
- **Medium:** No single source of truth; multiple parallel calculation paths
- **Low:** Duplication in views/JS is mostly display parity; formulas are documented as matching

---

**End of M3.1 Audit**
