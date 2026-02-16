# Budget Source Audit Report

Generated on: February 13, 2026

---

## Summary

| Metric | Count |
|--------|-------|
| **Total SAFE usages** | 14 |
| **Total RISKY usages** | 38 |
| **High Priority Fix Areas** | 8 |
| **Controllers needing refactor** | 6 |
| **Blade files with inline arithmetic** | 6 |
| **JS recalculation instances** | 3 |
| **DB write inconsistencies** | 2 |

### High Priority Fix Areas

1. **Provincial ProjectList** – Raw DB columns; list shows different values than project view
2. **ExportController addGeneralInformationSection** – Raw DB for all 5 fund fields in PDF export
3. **resources/views/projects/Oldprojects/pdf.blade.php** – Inline arithmetic `overall - (forwarded + local)` for amount_sanctioned
4. **resources/views/projects/partials/Show/general_info.blade.php** – Fallback to raw DB when `resolvedFundFields` absent; inline `amount_requested` arithmetic
5. **ReportController & report forms** – Direct `$project->amount_sanctioned`, `opening_balance` reads
6. **Coordinator/Provincial approvedProjects views** – Raw `amount_sanctioned`, `amount_forwarded` display
7. **scripts-edit.blade.php** – JS `calculateProjectTotal()` sums `this_phase`; `calculateAmountSanctioned()` recomputes sanctioned/opening
8. **GeneralInfoController** – Persists budget fields from request without resolver validation

### Controllers Needing Refactor

- `ExportController` (addGeneralInformationSection)
- `ProvincialController` (projectList – already uses resolver for health/utilization but not for list display)
- `ReportController` (amount_sanctioned, opening_balance reads)
- `MonthlyDevelopmentProjectController` (amount_sanctioned reads)
- `CoordinatorController` (approveProject – persists from resolver; approvedProjects view receives raw project)
- `GeneralController` (approveAsCoordinator – persists from resolver; ExportController not fixed)

### Blade Files with Inline Arithmetic

- `resources/views/provincial/ProjectList.blade.php`
- `resources/views/projects/partials/Show/general_info.blade.php`
- `resources/views/projects/Oldprojects/pdf.blade.php`
- `resources/views/projects/partials/Edit/budget.blade.php` (initial values from DB)
- `resources/views/projects/partials/Edit/general_info.blade.php`
- `resources/views/reports/monthly/developmentProject/reportform.blade.php`

### JS Recalculation Instances

- `resources/views/projects/partials/scripts-edit.blade.php` – `calculateProjectTotal()`, `calculateAmountSanctioned()`
- `resources/views/projects/partials/scripts.blade.php` – same
- `public/js/budget-calculations.js` – `calculateProjectTotal`, `calculateAmountSanctioned`

### DB Write Inconsistencies

- `GeneralInfoController::update()` – Persists `overall_project_budget`, `amount_forwarded`, `local_contribution` from request; no resolver validation before save
- `BudgetSyncService` / `AdminCorrectionService` – Use `getStoredValues()` (raw DB) for correction flows; may overwrite with values inconsistent with resolver logic

---

## Detailed Findings

---

### File: app/Http/Controllers/Projects/ExportController.php

- **Lines:** 619–629
- **What it does:** Adds Basic Information section to exported Word document. Reads `$project->overall_project_budget`, `amount_forwarded`, `amount_sanctioned`, `opening_balance` directly.
- **Data source:** Raw `$project` attributes
- **Classification:** RISKY
- **Risk reason:** Export shows DB values, not resolved values. For phase-based types, overall/sanctioned/opening may differ from project view.
- **Recommended architectural fix:** Call `ProjectFinancialResolver::resolve($project)` and use `$financials['overall_project_budget']`, `['amount_forwarded']`, `['local_contribution']`, `['amount_sanctioned']`, `['opening_balance']` for export.

---

### File: resources/views/provincial/ProjectList.blade.php

- **Lines:** 179–195
- **What it does:** Displays Overall Project Budget, Existing Funds, Local Contribution, Amount Requested in projects list table.
- **Data source:** `$project->overall_project_budget`, `$project->amount_forwarded`, `$project->local_contribution`; `$amountRequested = max(0, $overallBudget - $existingFunds - $localContribution)`
- **Classification:** RISKY
- **Risk reason:** Raw DB columns. For CIC/NPD/phase-based types, overall comes from budget rows (resolver); list shows stale/wrong values. User-reported mismatch with project view.
- **Recommended architectural fix:** In `ProvincialController::projectList()`, call `ProjectFinancialResolver::resolve()` per project and pass resolved financials to the view. Blade displays from resolved array.

---

### File: resources/views/projects/Oldprojects/pdf.blade.php

- **Lines:** 796, 800
- **What it does:** Displays amount_sanctioned (with fallback `max(0, overall - (forwarded + local))`), amount_forwarded, local_contribution.
- **Data source:** `$project->amount_sanctioned`, `$project->overall_project_budget`, `$project->amount_forwarded`, `$project->local_contribution`; inline arithmetic for fallback
- **Classification:** RISKY
- **Risk reason:** Recomputes amount_sanctioned locally when DB is null; uses raw DB for forwarded/local. Duplicates resolver logic.
- **Recommended architectural fix:** Controller passes `resolvedFundFields` (from `ProjectFinancialResolver::resolve()`); Blade uses only resolved values.

---

### File: resources/views/projects/partials/Show/general_info.blade.php

- **Lines:** 28–34, 111–178
- **What it does:** Shows Overall Project Budget, Amount Forwarded, Local Contribution, Amount Requested, Amount Sanctioned, Opening Balance. Uses `$resolvedFundFields` when present, else falls back to raw `$project`; computes `$amount_requested = max(0, $budget_overall - ($budget_forwarded + $budget_local))`.
- **Data source:** `$resolvedFundFields` (from controller) OR raw `$project`; inline `amount_requested` arithmetic
- **Classification:** RISKY (fallback path) / SAFE (when resolvedFundFields present)
- **Risk reason:** When `resolvedFundFields` is null (e.g. resolver disabled), uses raw DB. Inline `amount_requested` duplicates resolver logic even when resolved.
- **Recommended architectural fix:** Always pass `resolvedFundFields` from `ProjectController::show()`. Use `$resolvedFundFields['amount_sanctioned']` for amount_requested display (same formula) or add `amount_requested` to resolver output. Remove fallback to raw project.

---

### File: resources/views/projects/partials/Edit/budget.blade.php

- **Lines:** 82, 98, 122, 145, 163, 173
- **What it does:** Form inputs for overall_project_budget, amount_forwarded, local_contribution, amount_sanctioned, opening_balance. Initial values from `$project`.
- **Data source:** `old()` with fallback to `$project->overall_project_budget`, `$project->amount_forwarded`, etc.
- **Classification:** RISKY
- **Risk reason:** Edit form seeds from DB. For phase-based types, overall should come from sum of budget rows. JS `calculateProjectTotal()` overwrites on load, but initial hydration can be wrong if DB is stale.
- **Recommended architectural fix:** Controller passes resolved financials for initial display; hidden `overall_project_budget` seeded from resolver; or ensure BudgetController/GeneralInfoController sync overall from budgets before edit load.

---

### File: resources/views/projects/partials/scripts-edit.blade.php

- **Lines:** 1036–1176
- **What it does:** `calculateProjectTotal()` sums `this_phase` from `.budget-rows tr`, sets `overall_project_budget`; `calculateAmountSanctioned()` computes `amount_sanctioned = overall - (forwarded + local)`, `opening_balance = sanctioned + combined`.
- **Data source:** DOM inputs (`overall_project_budget`, `amount_forwarded`, `local_contribution`); budget row `this_phase` values
- **Classification:** RISKY
- **Risk reason:** Client-side duplication of resolver logic. Necessary for edit UX (live calculation), but creates second source of truth. If backend does not persist JS result consistently, view/edit diverge.
- **Recommended architectural fix:** Ensure Edit submit includes JS-calculated values; backend validates via resolver before persist. Or: backend recomputes on save from budget rows and ignores client `overall_project_budget` for phase-based types.

---

### File: resources/views/projects/partials/scripts.blade.php

- **Lines:** 46, 89, 101, 121, 272, 283
- **What it does:** Same `calculateProjectTotal()` and `calculateAmountSanctioned()` for create form.
- **Data source:** DOM inputs; budget rows
- **Classification:** RISKY
- **Risk reason:** Same as scripts-edit; create form recalculates locally.
- **Recommended architectural fix:** Align with scripts-edit; ensure create flow persists from validated request; backend may compute overall from budget rows for phase-based types.

---

### File: public/js/budget-calculations.js

- **Lines:** ~50–120 (calculateProjectTotal, calculateAmountSanctioned)
- **What it does:** Pure JS functions: sum of phase values; `overall - combined` for sanctioned; `sanctioned + combined` for opening.
- **Data source:** Numeric arrays passed from Blade scripts
- **Classification:** RISKY (when used as source of truth for display/persist)
- **Risk reason:** Duplicates DerivedCalculationService/ProjectFinancialResolver formulas. Used by Edit; if backend trusts different logic, inconsistency.
- **Recommended architectural fix:** Treat as UI helper only; backend must be authoritative. Ensure backend uses DerivedCalculationService for any server-side recalculation.

---

### File: app/Http/Controllers/Projects/GeneralInfoController.php

- **Lines:** 31–32, 40, 119, 135, 150–154
- **What it does:** `store()`: defaults `amount_forwarded`, `local_contribution`, `overall_project_budget`; `update()`: `unset` budget fields from validated and does not sync from resolver. Budget fields persisted from request.
- **Data source:** `$request->validated()`; defaults when missing
- **Classification:** RISKY
- **Risk reason:** Persists request values without validating against resolver. For phase-based types, overall should be derived from budget rows; controller may persist stale overall.
- **Recommended architectural fix:** For phase-based types, either (a) sync overall from budget rows before persist via BudgetSyncService, or (b) ignore client overall and recompute from budgets on update.

---

### File: app/Http/Controllers/Reports/Monthly/ReportController.php

- **Lines:** 87, 1196–1197, 1318, 1326
- **What it does:** Reads `$project->amount_sanctioned`, `$project->opening_balance` for report forms/logic.
- **Data source:** Raw `$project` attributes
- **Classification:** RISKY
- **Risk reason:** Report uses DB values. If project approved with resolver-computed values, DB should match; but if any path writes inconsistent values, reports show wrong sanctioned/opening.
- **Recommended architectural fix:** Use `ProjectFinancialResolver::resolve($project)` and pass `amount_sanctioned`, `opening_balance` from resolver to report views.

---

### File: app/Http/Controllers/Reports/Monthly/MonthlyDevelopmentProjectController.php

- **Lines:** 32, 50
- **What it does:** Reads `$project->amount_sanctioned` for report overview.
- **Data source:** Raw `$project->amount_sanctioned`
- **Classification:** RISKY
- **Risk reason:** Same as ReportController.
- **Recommended architectural fix:** Use resolver output.

---

### File: resources/views/reports/monthly/developmentProject/reportform.blade.php

- **Lines:** 196, 200, 204
- **What it does:** Displays `amount_sanctioned`, `amount_forwarded`, and `amount_sanctioned + amount_forwarded` in readonly inputs.
- **Data source:** `$project->amount_sanctioned`, `$project->amount_forwarded`
- **Classification:** RISKY
- **Risk reason:** Raw DB; inline `amount_sanctioned + amount_forwarded`.
- **Recommended architectural fix:** Controller passes resolved financials; view uses those.

---

### File: resources/views/provincial/approvedProjects.blade.php

- **Lines:** 94–95
- **What it does:** Displays `amount_sanctioned`, `amount_forwarded` in table.
- **Data source:** `$project->amount_sanctioned`, `$project->amount_forwarded`
- **Classification:** RISKY
- **Risk reason:** Raw DB. Approved projects should have DB populated by approval flow (which uses resolver), but list does not go through resolver.
- **Recommended architectural fix:** For consistency, pass resolver output or ensure approval flow is sole writer; document that approved lists trust DB.

---

### File: resources/views/coordinator/approvedProjects.blade.php

- **Lines:** 109–110
- **What it does:** Same as provincial approvedProjects.
- **Data source:** `$project->amount_sanctioned`, `$project->amount_forwarded`
- **Classification:** RISKY
- **Risk reason:** Same as above.
- **Recommended architectural fix:** Same.

---

### File: app/Http/Controllers/Projects/ProjectController.php

- **Lines:** 482
- **What it does:** Passes `overall_project_budget` in `$data` for some response.
- **Data source:** `$project->overall_project_budget`
- **Classification:** RISKY
- **Risk reason:** Raw DB in API/JSON response.
- **Recommended architectural fix:** Use resolver and pass resolved `overall_project_budget`.

---

### File: app/Services/Budget/ProjectFundFieldsResolver.php

- **Lines:** 69–73
- **What it does:** `fallbackFromProject()` returns raw project values when resolver disabled. `resolve()` delegates to ProjectFinancialResolver; fallback only used in dead code path.
- **Data source:** `$project->overall_project_budget`, etc.
- **Classification:** RISKY (fallback path) / SAFE (resolve delegates to ProjectFinancialResolver)
- **Risk reason:** Fallback reads raw DB; used only when ProjectFinancialResolver not used. Legacy adapter.
- **Recommended architectural fix:** Remove fallback; always delegate to ProjectFinancialResolver.

---

### File: app/Services/Budget/AdminCorrectionService.php

- **Lines:** 106–110, 145–163
- **What it does:** `getStoredValues()` returns raw project; `manualCorrection()` validates and computes `$sanctioned = max(0, $overall - ($forwarded + $local))`, `$opening = $sanctioned + $forwarded + $local`, then persists.
- **Data source:** Raw `$project`; inline arithmetic
- **Classification:** RISKY
- **Risk reason:** Recomputes sanctioned/opening locally; may diverge from ProjectFinancialResolver for type-specific logic (e.g. ILP/IAH/IGE).
- **Recommended architectural fix:** Use ProjectFinancialResolver for display/validation; persist resolver output after admin override of overall/forwarded/local.

---

### File: app/Services/Budget/BudgetSyncService.php

- **Lines:** 133–137
- **What it does:** `getStoredValues()` returns raw project fund fields.
- **Data source:** Raw `$project`
- **Classification:** RISKY (when used to overwrite project)
- **Risk reason:** Sync writes from type-specific logic; getStoredValues is raw. If sync logic differs from resolver, inconsistency.
- **Recommended architectural fix:** Ensure sync output matches ProjectFinancialResolver for the same inputs; use resolver as validation gate.

---

### File: app/Http/Requests/Projects/UpdateProjectRequest.php

- **Lines:** 56–57
- **What it does:** `prepareForValidation()` merges `overall_project_budget` from `$project` when `save_as_draft` and not filled.
- **Data source:** `$project->overall_project_budget`
- **Classification:** LOW RISK
- **Risk reason:** Preservation for draft; avoids NULL. Value comes from existing project (possibly stale).
- **Recommended architectural fix:** Consider using resolver for preservation when available.

---

### File: resources/views/projects/partials/Edit/general_info.blade.php

- **Lines:** 292, 495, 618
- **What it does:** Input values for `overall_project_budget` from `$project`.
- **Data source:** `$project->overall_project_budget`
- **Classification:** RISKY
- **Risk reason:** Edit seeds from DB; JS overwrites for budget section but general_info may show stale overall.
- **Recommended architectural fix:** Pass resolved overall for initial display where applicable.

---

### File: resources/views/projects/partials/Edit/IAH/budget_details.blade.php

- **Lines:** 76
- **What it does:** Readonly overall_project_budget from `$project`.
- **Data source:** `$project->overall_project_budget`
- **Classification:** RISKY
- **Risk reason:** IAH uses type-specific overall (sum of budget details); raw project.overall may differ.
- **Recommended architectural fix:** Use resolved financials for IAH display.

---

### File: resources/views/projects/partials/OLdshow/general_info.blade.php

- **Lines:** 51, 54, 57, 60
- **What it does:** Displays overall_project_budget, amount_forwarded, amount_sanctioned, opening_balance from `$project`.
- **Data source:** Raw `$project`
- **Classification:** RISKY
- **Risk reason:** Legacy/old show; raw DB only.
- **Recommended architectural fix:** Replace with Show/general_info and resolver, or retire.

---

### File: resources/views/projects/partials/not working show/general_info.blade.php

- **Lines:** 51, 54, 57, 60
- **What it does:** Same as OLdshow.
- **Data source:** Raw `$project`
- **Classification:** RISKY
- **Risk reason:** Non-working partial; likely unused but still raw.
- **Recommended architectural fix:** Remove if dead, or align with resolver.

---

### File: app/Http/Controllers/Reports/Quarterly/DevelopmentProjectController.php

- **Lines:** 43
- **What it does:** `$amountSanctionedOverview = $this->calculationService->calculateProjectTotal($budgets->map(fn ($b) => (float) ($b->this_phase ?? 0)))`
- **Data source:** Sum of `project_budgets.this_phase` via DerivedCalculationService
- **Classification:** RISKY
- **Risk reason:** Manual sum of this_phase; no phase filter mentioned. May not align with ProjectFinancialResolver (which filters by current_phase for phase-based types).
- **Recommended architectural fix:** Use ProjectFinancialResolver for project-level sanctioned/overview; or ensure phase filter matches resolver.

---

### File: app/Http/Controllers/Projects/ExportController.php

- **Lines:** 2168, 2215–2216
- **What it does:** Phase-based export uses `calculateProjectTotal($budgets->map(...))` for "Amount Sanctioned in Phase X".
- **Data source:** `budgets->map(fn ($b) => $b->this_phase)` per phase
- **Classification:** LOW RISK / ACCEPTABLE
- **Risk reason:** Per-phase sum is intentional for export; uses DerivedCalculationService. Aligns with phase-based semantics.
- **Recommended architectural fix:** Document that this is phase-scoped; ensure phase filter matches project's current_phase when exporting current phase.

---

### File: app/Services/BudgetValidationService.php

- **Lines:** 51–60, 111
- **What it does:** `calculateBudgetData()` calls `ProjectFinancialResolver::resolve()` and uses financials. `budget_items_total` = `$phaseBudgets->sum('this_phase')` for validation mismatch check.
- **Data source:** ProjectFinancialResolver; budgets for validation
- **Classification:** SAFE (financials) / ACCEPTABLE (budget_items_total for validation)
- **Risk reason:** Uses resolver for display; sum(this_phase) for consistency check only.
- **Recommended architectural fix:** None for resolver path. Ensure phase filter matches PhaseBasedBudgetStrategy.

---

### File: resources/views/projects/partials/Show/budget.blade.php

- **Lines:** 26–29, 254, 271
- **What it does:** Receives `$budgetData` from BudgetValidationService (which uses resolver). Displays `amount_forwarded`, `local_contribution`, `opening_balance` from budgetData. Also shows `$budget->this_phase` and `$budgetsForShow->sum('this_phase')` in table.
- **Data source:** BudgetValidationService (resolver); budget rows for table display
- **Classification:** SAFE (fund fields from service) / ACCEPTABLE (table sum is display of budget rows)
- **Risk reason:** Budget Overview uses resolver via service. Table footer sum is for display of loaded budgets.
- **Recommended architectural fix:** Ensure BudgetValidationService phase filter aligns with resolver. Table sum is acceptable as display of budget rows.

---

### File: app/Http/Controllers/ProvincialController.php

- **Lines:** 261, 500
- **What it does:** `calculateBudgetSummariesFromProjects` and `projectList` use `ProjectFinancialResolver::resolve()` for `opening_balance` (budget/utilization). List view receives raw `$projects`; Blade uses raw attributes for Overall/Existing/Local/Requested.
- **Data source:** Resolver for utilization; raw project for list display
- **Classification:** MIXED – SAFE for utilization; RISKY for list table columns
- **Risk reason:** List table (ProjectList.blade.php) uses raw project; resolver only used for health/utilization.
- **Recommended architectural fix:** Resolve financials per project in projectList and pass to view; Blade displays from resolved.

---

### File: app/Http/Controllers/CoordinatorController.php

- **Lines:** 1106, 1134–1135
- **What it does:** `approveProject()` calls `ProjectFinancialResolver::resolve()`, uses financials, persists `amount_sanctioned` and `opening_balance` from resolver.
- **Data source:** ProjectFinancialResolver
- **Classification:** SAFE
- **Risk reason:** Uses resolver for approval; persists resolver output.
- **Recommended architectural fix:** None.

---

### File: app/Http/Controllers/GeneralController.php

- **Lines:** 2544–2564
- **What it does:** `approveAsCoordinator()` uses `ProjectFinancialResolver::resolve()`, persists `amount_sanctioned`, `opening_balance` from resolver.
- **Data source:** ProjectFinancialResolver
- **Classification:** SAFE
- **Risk reason:** Uses resolver for approval.
- **Recommended architectural fix:** None.

---

### File: app/Http/Controllers/Projects/ProjectController.php

- **Lines:** 1074–1077
- **What it does:** `show()` calls `ProjectFundFieldsResolver::resolve($project, true)` and passes `resolvedFundFields` to view when resolver enabled.
- **Data source:** ProjectFundFieldsResolver (delegates to ProjectFinancialResolver)
- **Classification:** SAFE
- **Risk reason:** View receives resolved fields; general_info uses them when present.
- **Recommended architectural fix:** Ensure resolver always enabled for show; remove fallback to raw project in Blade.

---

### File: app/Domain/Budget/ProjectFinancialResolver.php

- **Lines:** 62–67
- **What it does:** Single entry point; delegates to PhaseBasedBudgetStrategy or DirectMappedIndividualBudgetStrategy.
- **Data source:** Strategies compute from project + budgets
- **Classification:** SAFE (canonical source)
- **Risk reason:** N/A
- **Recommended architectural fix:** None.

---

### File: app/Domain/Budget/Strategies/PhaseBasedBudgetStrategy.php

- **Lines:** 34–65
- **What it does:** Reads `amount_forwarded`, `local_contribution` from project; `overall` from sum of `this_phase` (current phase) or fallback to `overall_project_budget`; approved uses DB sanctioned/opening; else computes sanctioned/opening.
- **Data source:** `$project` attributes; `project->budgets->where('phase', currentPhase)`
- **Classification:** SAFE (canonical for phase-based types)
- **Risk reason:** N/A
- **Recommended architectural fix:** None.

---

### File: app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php

- **Lines:** 75–198
- **What it does:** Type-specific resolution for IIES, IES, ILP, IAH, IGE. Reads from type tables and project as appropriate.
- **Data source:** Type-specific tables; project for fallback
- **Classification:** SAFE (canonical for individual/IGE types)
- **Risk reason:** N/A
- **Recommended architectural fix:** None.

---

### File: app/Services/Budget/DerivedCalculationService.php

- **Lines:** 53
- **What it does:** `calculateProjectTotal()` – pure sum of iterable. Used by strategies and ExportController.
- **Data source:** Input iterable
- **Classification:** SAFE (math only; used by resolver)
- **Risk reason:** N/A
- **Recommended architectural fix:** None.

---

### File: app/Models/OldProjects/Project.php

- **Lines:** 264, 281 (fillable)
- **What it does:** Model defines fillable including `overall_project_budget`, `local_contribution`, etc.
- **Data source:** Schema/storage
- **Classification:** N/A (model definition)
- **Risk reason:** N/A
- **Recommended architectural fix:** None.

---

## Appendix: Excluded from Production Findings

- **Documentations/** – Reference only; not production code
- **storage/framework/views/** – Compiled Blade; source files audited
- **tests/** – Test fixtures and assertions; not display/persist paths (tests that verify resolver are SAFE)
- **config/decimal_bounds.php** – Bounds config; no financial logic
- **app/Http/Requests/Projects/StoreProjectRequest.php** – Validation only; does not display/mutate
- **Type-specific views (ILP, IAH, IGE, LDP)** – Use type-specific `amount_requested` from type tables; acceptable when aligned with DirectMappedIndividualBudgetStrategy
- **IAHBudgetDetailsController, ILPBudgetController, IGEBudgetController** – Compute type-specific `amount_requested`; design-specific
- **LDP TargetGroupController** – `L_amount_requested` is target-group field, not project fund field

---

## Recommended Refactor Order

1. **ProvincialController::projectList** + ProjectList.blade.php – High user impact
2. **ExportController addGeneralInformationSection** – Export parity with view
3. **resources/views/projects/Oldprojects/pdf.blade.php** – PDF parity
4. **ReportController + report forms** – Report accuracy
5. **general_info.blade.php** – Remove raw fallback; always use resolved
6. **approvedProjects views** (coordinator, provincial) – Lower priority if approval flow is correct
7. **GeneralInfoController** – Persist strategy alignment
8. **AdminCorrectionService / BudgetSyncService** – Align with resolver for corrections
