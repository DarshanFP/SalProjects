# Individual Project Budget Display Audit

**Milestone:** Budget Integrity Audit  
**Task:** Individual Project Types Budget Display Investigation  
**Mode:** STRICTLY READ-ONLY — ZERO ASSUMPTION  
**Scope:** IES, IIES, ILP, IAH only  

**Date:** 2026-02-16  

---

## SECTION 1 — Budget Rendering Entry Points

### Where budget-related values are rendered

| File | Field Rendered | Uses $project or $resolvedFundFields? | Line |
|------|----------------|----------------------------------------|------|
| `resources/views/projects/partials/Show/general_info.blade.php` | overall_project_budget, amount_forwarded, local_contribution, amount_sanctioned, opening_balance | **$resolvedFundFields** (as `$rf`) | 30–34, 109–141 |
| `resources/views/projects/partials/Edit/general_info.blade.php` | overall_project_budget | **$project** | 260–262, 467–468, 587–588 |
| `resources/views/projects/partials/Edit/budget.blade.php` | overall_project_budget_display, amount_sanctioned_preview, opening_balance_preview, hidden overall_project_budget | **$project** | 82, 145, 163, 173 |
| `resources/views/projects/partials/Show/budget.blade.php` | overall_budget, amount_sanctioned, opening_balance, etc. | **BudgetValidationService::getBudgetSummary($project)** (which uses ProjectFinancialResolver internally) | 19–29 |
| `resources/views/projects/Oldprojects/pdf.blade.php` | amount_sanctioned, amount_forwarded, local_contribution | **$resolvedFundFields** | 796, 800 |
| `resources/views/projects/Oldprojects/pdf.blade.php` (general_info include) | overall_project_budget, amount_sanctioned, opening_balance | **$resolvedFundFields** (via `projects.partials.Show.general_info`) | 219 (include) |
| `resources/views/projects/partials/OLdshow/general_info.blade.php` | overall_project_budget, amount_sanctioned, opening_balance | **$project** | 51, 57, 60 |
| `resources/views/projects/partials/not working show/general_info.blade.php` | overall_project_budget, amount_sanctioned, opening_balance | **$project** | 51, 57, 60 |

**Note:** The **show** page uses `projects.partials.Show.general_info`, which correctly uses `$resolvedFundFields`. The **edit** page uses `projects.partials.Edit.general_info`, which uses **$project** only; **resolvedFundFields is not passed to the edit view** (see Section 3).

**Show page budget section:** `resources/views/projects/Oldprojects/show.blade.php` line 245–248 includes `Show.budget` **only when project type is NOT** IES, IIES, ILP, IAH. So for IES/IIES/ILP/IAH, the “Budget Overview” card (Show.budget) is **not** rendered; only type-specific partials (e.g. Show.ILP.budget, Show.IAH.budget_details, IES/IIES estimated_expenses) and the Basic Information block (Show.general_info with resolvedFundFields) are shown.

---

## SECTION 2 — Resolver Pipeline Trace

### 2.1 ProjectFinancialResolver

| Location | Description |
|---------|-------------|
| **File** | `app/Domain/Budget/ProjectFinancialResolver.php` |
| **Entry** | `resolve(Project $project): array` — line 63 |
| **Flow** | `getStrategyForProject($project)` → strategy `resolve($project)` → `normalize($result)` → `assertFinancialInvariants()` → return |

**Strategy selection (lines 113–126):**

- **PHASE_BASED_TYPES** (line 27–35): Development Projects, NEXT PHASE - DEVELOPMENT PROPOSAL, Livelihood Development Projects, Residential Skill Training Proposal 2, PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER, CHILD CARE INSTITUTION, Rural-Urban-Tribal → **PhaseBasedBudgetStrategy**
- **DIRECT_MAPPED_INDIVIDUAL_TYPES** (line 38–44): Individual - Initial - Educational support, Individual - Ongoing Educational support, Individual - Livelihood Application, Individual - Access to Health, Institutional Ongoing Group Educational proposal → **DirectMappedIndividualBudgetStrategy**
- **Default** (line 125): **PhaseBasedBudgetStrategy**

**Type strings are exact match** (`in_array($projectType, ..., true)`). No enum used in resolver; `project_type` is string from DB.

### 2.2 BudgetStrategy selection logic

| Project Type | Strategy Used | File:Line |
|--------------|---------------|-----------|
| IES (Individual - Ongoing Educational support) | DirectMappedIndividualBudgetStrategy | ProjectFinancialResolver.php:121–122 |
| IIES (Individual - Initial - Educational support) | DirectMappedIndividualBudgetStrategy | ProjectFinancialResolver.php:121–122 |
| ILP (Individual - Livelihood Application) | DirectMappedIndividualBudgetStrategy | ProjectFinancialResolver.php:121–122 |
| IAH (Individual - Access to Health) | DirectMappedIndividualBudgetStrategy | ProjectFinancialResolver.php:121–122 |

### 2.3 PhaseBasedBudgetStrategy

- **File:** `app/Domain/Budget/Strategies/PhaseBasedBudgetStrategy.php`
- **overall_project_budget:** Lines 42–47: from `project->budgets` filtered by `current_phase`, sum of `this_phase`; else `project->overall_project_budget`.
- **amount_sanctioned:** Lines 49–55: if approved, from `project->amount_sanctioned`; else `overall - (forwarded + local)`.
- **opening_balance:** Lines 50–54: if approved, from `project->opening_balance`; else `sanctioned + forwarded + local`.

**Not used for IES, IIES, ILP, IAH** when `project_type` matches DIRECT_MAPPED_INDIVIDUAL_TYPES.

### 2.4 DirectMappedIndividualBudgetStrategy

- **File:** `app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php`

| Project Type | overall_project_budget | amount_sanctioned | opening_balance | File:Line |
|--------------|------------------------|-------------------|----------------|-----------|
| **IIES** | `iiesExpenses->iies_total_expenses` | `iiesExpenses->iies_balance_requested` | same as overall | 66–71, 73–77 |
| **IES** | `iesExpenses->first()->total_expenses` | `iesExpenses->first()->balance_requested` | same as overall | 84–93, 95–101 |
| **ILP** | Sum of `ilpBudget->cost` (via DerivedCalculationService) | `ilpBudget->first()->amount_requested` | same as overall | 112–117, 119–124 |
| **IAH** | Sum of `iahBudgetDetails->amount` (via DerivedCalculationService) | `iahBudgetDetails->first()->amount_requested` | same as overall | 136–141, 143–149 |

**Fallback:** When relation is missing or empty (e.g. no IIES expense row, no IES expenses, no ILP/IAH budget rows), `fallbackFromProject($project)` is used (lines 181–189): reads `project->overall_project_budget`, `project->amount_forwarded`, `project->local_contribution`, `project->amount_sanctioned`, `project->opening_balance` from **projects** table.

**Relation loading:** Lines 33–34 — `$project->loadMissing($this->getRelationsForType($projectType))`. Relations: IIES → `['iiesExpenses']`, IES → `['iesExpenses']`, ILP → `['ilpBudget']`, IAH → `['iahBudgetDetails']`, IGE → `['igeBudget']`.

---

## SECTION 3 — Hydration & Controller Layer

### 3.1 ProjectDataHydrator

| Check | Result | File:Line |
|-------|--------|-----------|
| resolvedFundFields passed? | **Yes** — set in hydrated data | `app/Services/ProjectDataHydrator.php:227` |
| Resolver invoked? | **Yes** — `$this->financialResolver->resolve($project)` | `app/Services/ProjectDataHydrator.php:227` |
| Resolver injected? | **Yes** — constructor `ProjectFinancialResolver $financialResolver` | `app/Services/ProjectDataHydrator.php:119` |

**Conclusion:** PDF export path uses hydrator; `resolvedFundFields` is present in `$data` passed to `projects.Oldprojects.pdf` view.

### 3.2 ProjectController show

| Check | Result | File:Line |
|-------|--------|-----------|
| resolvedFundFields passed? | **Yes** | `app/Http/Controllers/Projects/ProjectController.php:1032` |
| Resolver invoked? | **Yes** — `$resolver->resolve($project)` | `app/Http/Controllers/Projects/ProjectController.php:1031–1032` |
| View | `projects.Oldprojects.show` | Line 1040 |

### 3.3 ProjectController edit

| Check | Result | File:Line |
|-------|--------|-----------|
| resolvedFundFields passed? | **No** — not in `compact()` list | `app/Http/Controllers/Projects/ProjectController.php:1293–1314` |
| Resolver invoked? | **No** — no call to ProjectFinancialResolver in edit() | — |

**Conclusion:** Edit view receives **only** `$project` (and other section data). Edit partials use `$project->overall_project_budget`, `$project->amount_sanctioned`, `$project->opening_balance`. For individual types, these columns on **projects** may be null/0 because budget is stored in child tables and not synced to projects.

### 3.4 ExportController

| Method | resolvedFundFields | Resolver | File:Line |
|--------|--------------------|----------|-----------|
| downloadPdf | Via **ProjectDataHydrator** — hydrator adds `resolvedFundFields` | Invoked inside `ProjectDataHydrator::hydrate()` | ExportController.php:382–384 (hydrate); ProjectDataHydrator.php:227 |
| downloadDoc | **Yes** — explicit `$resolvedFundFields = $this->financialResolver->resolve($project)` and passed to `addGeneralInfoSection` | **Yes** | ExportController.php:513–517, 601–637 |

---

## SECTION 4 — Individual Budget Storage Model

| Project Type | Budget Source Table | Direct DB Field on projects? | Derived? |
|--------------|--------------------|------------------------------|----------|
| **IES** | `project_IES_expenses` (hasMany; strategy uses `->first()`) | `overall_project_budget` exists on `projects` but typically not populated from IES expenses | **Derived** from ies_expenses: total_expenses, balance_requested, etc. |
| **IIES** | `project_IIES_expenses` (hasOne) | Same as above | **Derived** from iies_expenses: iies_total_expenses, iies_balance_requested, etc. |
| **ILP** | `project_ILP_budget` (hasMany) | Same as above | **Derived** from ilp_budget: sum(cost), first()->amount_requested, first()->beneficiary_contribution |
| **IAH** | `project_IAH_budget_details` (hasMany) | Same as above | **Derived** from iah_budget_details: sum(amount), first()->amount_requested, first()->family_contribution |

**projects table:** Has columns `overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned`, `opening_balance` (see `app/Models/OldProjects/Project.php` fillable 284–288). For individual types, application logic does **not** consistently persist resolver-derived totals back to these columns; display is intended to come from resolver/child tables.

---

## SECTION 5 — Strategy Mismatch Detection

| Check | Result | Evidence |
|-------|--------|----------|
| Individual types incorrectly use PhaseBasedBudgetStrategy? | **No** | DIRECT_MAPPED_INDIVIDUAL_TYPES includes IES, IIES, ILP, IAH; resolver returns DirectMappedIndividualBudgetStrategy for these (ProjectFinancialResolver.php:121–122). |
| Strategy selection based on project_type incorrect? | **No** | project_type string match is exact; constants in `App\Constants\ProjectType` match (e.g. INDIVIDUAL_ONGOING_EDUCATIONAL = 'Individual - Ongoing Educational support'). |
| Type string mismatch? | **None found** | Resolver and ProjectType constants use same strings. |
| Enum mismatch? | **N/A** | Resolver uses string `project_type` from model; no enum in resolver. |
| Case mismatch? | **None found** | All comparisons use exact string match. |

**Potential design nuance:** DirectMappedIndividualBudgetStrategy uses `first()` for ILP local/sanctioned and IAH local/sanctioned (one row). If multiple rows are meaningful, aggregation might be expected; this is a design assumption, not a type mismatch.

---

## SECTION 6 — Data Existence Check

**Verification approach (read-only):** No DB queries were run. Inference from code:

1. **Does DB contain budget rows?** — Depends on data. For strategy to return non-zero:
   - **IIES:** At least one row in `project_IIES_expenses` (hasOne).
   - **IES:** At least one row in `project_IES_expenses`.
   - **ILP:** At least one row in `project_ILP_budget`.
   - **IAH:** At least one row in `project_IAH_budget_details`.

2. **Does overall_project_budget exist on projects?** — Yes (column exists). For individual types it may be **null or 0** if never set by GeneralInfoController or sync logic.

3. **Does resolver return 0 despite data existing?** — Possible when:
   - Relation not loaded before resolve (resolver calls `loadMissing`, so should be loaded).
   - Wrong relation name (e.g. typo) — none found; relation names match Project model.
   - Child table has no row yet → strategy uses `fallbackFromProject($project)` → project row values; if those are null/0, output is 0.

**Recommended manual check (outside this audit):** For one project per type (IES, IIES, ILP, IAH), confirm in DB: (a) corresponding child table has rows with non-zero amounts, (b) `projects.overall_project_budget` / amount_sanctioned / opening_balance for that project. Then load show page and confirm whether Basic Information shows resolver values or zeros.

---

## SECTION 7 — Root Cause Analysis

**Is the problem due to:**

| Option | Applicable? | Evidence |
|--------|-------------|----------|
| **A) Resolver not called** | **No** (for show/PDF/DOC) | Show and Export (PDF/DOC) both call resolver or use hydrator that calls resolver. |
| **B) Wrong strategy used** | **No** | IES, IIES, ILP, IAH map to DirectMappedIndividualBudgetStrategy. |
| **C) Strategy returns 0** | **Possible** | If child relation is empty or missing, fallback is project row; for individual types project row is often not populated → 0. |
| **D) View using wrong variable** | **Yes for EDIT only** | Edit uses `$project->overall_project_budget` (and sanctioned/opening) in Edit/general_info and Edit/budget; **resolvedFundFields is not passed to edit view**. Show and PDF use resolvedFundFields. |
| **E) Hydrator not injecting resolvedFundFields** | **No** | Hydrator injects resolvedFundFields at line 227. |
| **F) Missing relation loading** | **Unlikely** | Resolver calls `loadMissing(getRelationsForType())` for the correct relations. |
| **G) Incorrect DB storage** | **Possible** | For individual types, budget is in child tables; `projects.overall_project_budget` (and sanctioned/opening) may never be set, so fallback yields 0. |
| **H) Other** | **Edit-specific** | Edit form does not receive or use resolver output; display is purely from $project. |

**Conclusion:**

- **Show page (Basic Information):** Resolver is called and `resolvedFundFields` is passed; view uses it. If display is still 0, the cause is likely **C** (strategy returns 0 due to empty child data or fallback) or **G** (no child rows / project row not set).
- **Edit page:** **D** — Edit view does **not** receive `resolvedFundFields` and uses only `$project`. For IES/IIES/ILP/IAH, `$project->overall_project_budget` (and sanctioned/opening) are often null/0, so **edit form shows zero or stale values** regardless of resolver.
- **PDF/DOC export:** Resolver/hydrator used; export should show resolver values. If export shows 0, same as show — **C** or **G**.

---

## SECTION 8 — Risk Assessment

| Area | Classification | Notes |
|------|----------------|--------|
| **Cosmetic display** | **Yes (Edit)** | Edit page Basic Information / budget preview shows $project values; for individual types this can be 0 or stale. |
| **Financial calculation broken** | **Unclear** | Resolver math is consistent; persistence to `projects` for individual types is not fully traced here. Approval flow may write sanctioned/opening from resolver (e.g. GeneralController) — not verified in this audit. |
| **Aggregation affected** | **Possible** | If list/dashboard code uses `$project->overall_project_budget` or `opening_balance` instead of resolver for individual types, totals could be wrong. ProvincialController uses resolver for per-project financials; other aggregations need review. |
| **Export affected** | **Low** | PDF uses hydrator (resolvedFundFields); DOC uses resolver. Only if resolver returns 0 (e.g. missing child data) would export show 0. |
| **Dashboard affected** | **Possible** | Depends whether dashboard uses raw project attributes or resolver; not audited here. |

---

## SECTION 9 — Recommended Fix Strategy (No Code Yet)

1. **Fix view variable (Edit):**  
   - In **ProjectController@edit**, call ProjectFinancialResolver and add **resolvedFundFields** to the data passed to the view.  
   - In **Edit/general_info** (and Edit/budget if used for individual types), use **resolvedFundFields** for **display** of overall_project_budget, amount_sanctioned, opening_balance (read-only or preview), while keeping form submission semantics (e.g. which fields are submitted for phase-based vs individual) unchanged.

2. **Fix strategy mapping:**  
   - No change required for IES/IIES/ILP/IAH strategy selection; already correct.

3. **Fix resolver:**  
   - No structural change required. Optionally ensure `loadMissing` is always called with correct relations and consider logging when fallback is used for individual types to aid debugging.

4. **Fix hydration:**  
   - No change; hydrator already passes resolvedFundFields.

5. **Fix DB alignment (optional):**  
   - If business rule is that `projects.overall_project_budget` (and sanctioned/opening) should be synced for individual types (e.g. on save or approval), add or align sync logic so fallbackFromProject() has meaningful values when child data exists. This is a product/design choice.

6. **Aggregation / dashboard:**  
   - Ensure any listing or dashboard that shows budget totals for individual types uses **resolver output** (or equivalent) per project, not raw `$project->overall_project_budget` / opening_balance.

---

**Individual Project Budget Display Audit Complete — No Code Changes Made**
