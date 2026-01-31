# Budget System-Wide Audit – Populate, Mutate, Validate, Approve, Consume

**Role:** Principal Software Architect & Financial Systems Auditor  
**Scope:** Budget-related fields across the entire project lifecycle  
**Application:** Laravel project management (draft → budget entry → approval → execution → reporting → dashboards)  
**Constraint:** Live production system; no full rewrite; explicit sync over implicit computation; conservative treatment of financial data.

---

## 1. Budget Column Inventory

### 1.1 Core table: `projects`

| Column                   | Type (migration)                       | Purpose (intended)                                     | Actual usage                                                                                                                                                                                                                                      |
| ------------------------ | -------------------------------------- | ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `overall_project_budget` | decimal(10,2), default 0               | Total budget for the project                           | **Development:** Set via General Info store/update. **Individual/IGE:** Not set from type-specific budget; often 0. Read by approval, reporting, Basic Info, validation, dashboards.                                                              |
| `amount_forwarded`       | decimal(10,2), nullable                | Funds brought forward from previous phase/year         | **Development:** Set via General Info. **Individual/IGE:** Not set; typically 0. Read by approval; report create/edit passes 0.00.                                                                                                                |
| `local_contribution`     | decimal(15,2), default 0 (added later) | Contribution from beneficiary/family/other sources     | **Development:** Set via General Info. **Individual/IGE:** Not set from type-specific tables; often 0. Read by approval, validation.                                                                                                              |
| `amount_sanctioned`      | decimal(10,2), nullable                | Amount to be requested/sanctioned (authority-approved) | **Written only at approval** (CoordinatorController, GeneralController). Computed as Overall − (Forwarded + Local). **Individual/IGE:** Approval uses 0 overall/local → computes 0 or wrong value. Read everywhere (reports, dashboards, export). |
| `opening_balance`        | decimal(10,2), nullable                | Starting balance = Sanctioned + Forwarded + Local      | **Written only at approval.** **Individual/IGE:** Same as amount_sanctioned—wrong or 0. Read by validation, dashboards, export.                                                                                                                   |

**Model:** `App\Models\OldProjects\Project` — all five columns are in `$fillable`. No observers touch these fields.

---

### 1.2 Development budget table: `project_budgets`

| Column                                                               | Purpose                  | Actual usage                                                                                                                                          |
| -------------------------------------------------------------------- | ------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| `project_id`                                                         | FK to projects           | Set by BudgetController store/update.                                                                                                                 |
| `phase`                                                              | Phase index              | From form.                                                                                                                                            |
| `particular`                                                         | Line description         | From form.                                                                                                                                            |
| `rate_quantity`, `rate_multiplier`, `rate_duration`, `rate_increase` | Calculation inputs       | From form.                                                                                                                                            |
| `this_phase`                                                         | Amount for current phase | From form. **Sum used as fallback** for `overall_project_budget` in approval and BudgetValidationService when `projects.overall_project_budget` is 0. |
| `next_phase`                                                         | Next phase amount        | From form.                                                                                                                                            |

**Note:** This table does **not** store `amount_forwarded` or `local_contribution`; those live only on `projects`. BudgetController does **not** write to `projects`; it only writes to `project_budgets`. So for development projects, overall/forwarded/local must be entered in General Info separately.

---

### 1.3 Type-specific budget/expense tables

#### IIES – `project_IIES_expenses` (single row per project)

| Column                           | Purpose                   | Written by              | Read by                                            |
| -------------------------------- | ------------------------- | ----------------------- | -------------------------------------------------- |
| `iies_total_expenses`            | Total cost of study       | IIES expense controller | Show view, BudgetCalculationService (via strategy) |
| `iies_expected_scholarship_govt` | Govt scholarship          | Same                    | Strategy (contribution source)                     |
| `iies_support_other_sources`     | Other support             | Same                    | Strategy                                           |
| `iies_beneficiary_contribution`  | Beneficiary contribution  | Same                    | Strategy                                           |
| `iies_balance_requested`         | Amount requested from org | Same                    | Strategy (amount sanctioned per row)               |

**Not written to `projects`:** None of these are synced to `projects.overall_project_budget`, `local_contribution`, or `amount_sanctioned`.

#### IES – `project_IES_expenses`

| Column                                                                                                                  | Purpose           | Same pattern as IIES                                                               |
| ----------------------------------------------------------------------------------------------------------------------- | ----------------- | ---------------------------------------------------------------------------------- |
| `total_expenses`, `expected_scholarship_govt`, `support_other_sources`, `beneficiary_contribution`, `balance_requested` | Analogous to IIES | Written by IES expense controller; read by strategy; **not** synced to `projects`. |

#### ILP – `project_ILP_budget` (multiple rows)

| Column                     | Purpose            | Written by                      | Read by                                         |
| -------------------------- | ------------------ | ------------------------------- | ----------------------------------------------- |
| `budget_desc`, `cost`      | Line items         | ILP BudgetController            | Strategy (sum of cost = overall; amount = cost) |
| `beneficiary_contribution` | Own contribution   | Same (single value per project) | Strategy                                        |
| `amount_requested`         | Requested from org | Same                            | Strategy                                        |

**Not synced to `projects`.**

#### IAH – `project_IAH_budget_details` (multiple rows)

| Column                                                      | Purpose                   | Written by                  | Read by  |
| ----------------------------------------------------------- | ------------------------- | --------------------------- | -------- |
| `particular`, `amount`                                      | Line items                | IAH BudgetDetailsController | Strategy |
| `total_expenses`, `family_contribution`, `amount_requested` | Often per row / first row | Same                        | Strategy |

**Not synced to `projects`.**

#### IGE – `project_IGE_budget` (multiple rows, one per beneficiary)

| Column                                                               | Purpose                     | Written by           | Read by         |
| -------------------------------------------------------------------- | --------------------------- | -------------------- | --------------- |
| `college_fees`, `hostel_fees`, `total_amount`                        | Per-row amounts             | IGE BudgetController | Strategy        |
| `scholarship_eligibility`, `family_contribution`, `amount_requested` | Contributions and requested | Same                 | Strategy (sums) |

**Not synced to `projects`.**

---

### 1.4 Report tables (monthly – primary focus)

#### `DP_Reports` (monthly reports)

| Column                       | Purpose                                   | Written by                                                                                          | Read by                            |
| ---------------------------- | ----------------------------------------- | --------------------------------------------------------------------------------------------------- | ---------------------------------- |
| `amount_sanctioned_overview` | Project-level sanctioned amount on report | ReportController store/update from **request** (form pre-filled from `$project->amount_sanctioned`) | Statements of account, PDF, export |
| `amount_forwarded_overview`  | Project-level forwarded                   | ReportController **always sets 0.0** (backward compatibility)                                       | Same                               |
| `amount_in_hand`             | Opening amount for report                 | From request (typically = amount_sanctioned when forwarded=0)                                       | Same                               |

**Critical:** On report **create**, controller passes `$amountSanctioned = $project->amount_sanctioned ?? 0.00` to the view. The form submits `amount_sanctioned_overview`; that value is stored in `DP_Reports`. So if `projects.amount_sanctioned` is 0 (e.g. individual-type project never synced), the report stores 0 and all downstream (statements, PDF, aggregates) show 0.

#### `DP_AccountDetails` (statement-of-account rows)

| Column              | Purpose            | Written by                                                       | Read by            |
| ------------------- | ------------------ | ---------------------------------------------------------------- | ------------------ |
| `amount_forwarded`  | Per-row forwarded  | ReportController **always 0.0**                                  | Edit/view partials |
| `amount_sanctioned` | Per-row sanctioned | From request (pre-filled from budget row or previous report row) | Same, totals       |
| `total_amount`      | Row total          | Computed (currently = amount_sanctioned when forwarded=0)        | Same               |

Row-level amounts for **new** reports come from `BudgetCalculationService::getBudgetsForReport()` (type-specific strategies). The **overview** sanctioned/forwarded always come from the controller, which got them from `$project`.

---

### 1.5 Other report tables (quarterly, half-yearly, annual)

- **Quarterly/Half-Yearly/Annual report headers:** `amount_sanctioned_overview`, `amount_forwarded_overview` (where present).
- **Detail tables:** `opening_balance`, `amount_forwarded`, `amount_sanctioned` per period/detail row.  
  These are populated from their respective report controllers; project-level sanctioned/forwarded are typically taken from `$project` or from the first period’s logic. Same risk: if `projects.amount_sanctioned` is 0, reports and details can show 0.

---

### 1.6 Summary – intended vs actual

| Location                                  | Intended meaning                                                                                        | Actual                                                                                                                                                   |
| ----------------------------------------- | ------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **projects** (development)                | User enters overall/forwarded/local in General Info; approval computes and stores sanctioned + opening. | General Info and approval **do** write; BudgetController does **not** sync sum of phases to overall. Two sources of “overall” (form vs sum) can diverge. |
| **projects** (IIES/IES/ILP/IAH/IGE)       | Should reflect type-specific totals and sanctioned amount.                                              | **Never** written from type-specific budget. Only approval writes sanctioned/opening, using 0 overall/local → wrong or zero.                             |
| **DP_Reports.amount_sanctioned_overview** | Snapshot of project sanctioned amount at report creation.                                               | Taken from form that was pre-filled from `$project->amount_sanctioned`. If project has 0, report has 0.                                                  |
| **BudgetValidationService**               | Validate budget consistency and remaining balance.                                                      | Uses only `projects` + `budgets`; for individual/IGE types, overall is 0 → validation meaningless or wrong.                                              |
| **Dashboards / aggregates**               | Sum of approved budgets by type/center.                                                                 | Use `$project->amount_sanctioned` (and sometimes overall). Individual types contribute 0.                                                                |

---

## 2. Budget Lifecycle Matrix

For each **project stage** and each **budget column** (or group), the table below states:

- **Expected state:** empty / draft / computed / frozen.
- **Actual behaviour:** what the code does.

### 2.1 Columns in `projects`

| Stage                            | overall_project_budget                                                                                                                                                                                           | amount_forwarded                         | local_contribution  | amount_sanctioned                                                                                                                                   | opening_balance                                                               |
| -------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------- | ------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------- |
| **Draft / Creation**             | Expected: draft (user or 0). Actual: **Development** set in GeneralInfoController store. **Individual/IGE** not set (0).                                                                                         | Same: Dev set in General Info; others 0. | Same.               | Expected: empty. Actual: **not set** (null/0). ✓                                                                                                    | Expected: empty. Actual: **not set**. ✓                                       |
| **Budget entry (save)**          | Expected: Dev = user or sync from budget sum; Individual = sync from type table. Actual: **Dev** not updated by BudgetController; **Individual** never synced.                                                   | Same: no sync.                           | Same.               | Expected: empty. Actual: **not set**. ✓                                                                                                             | Expected: empty. Actual: **not set**. ✓                                       |
| **Coordinator approval**         | Expected: read-only (validate). Actual: **read**; fallback to budgets->sum('this_phase') for Dev only. **Individual:** no fallback → 0.                                                                          | Read.                                    | Read.               | Expected: **computed and frozen**. Actual: **computed** from overall/forwarded/local and **saved**. For Individual, overall/local=0 → sanctioned=0. | Expected: **computed and frozen**. Actual: **computed and saved**. Same flaw. |
| **Post-approval (edit project)** | Expected: frozen or strictly controlled. Actual: **GeneralInfoController update** can overwrite overall/forwarded/local **with no status check**. Risk: approved project’s budget fields changed after approval. | Can be overwritten.                      | Can be overwritten. | Expected: frozen. Actual: **not** overwritten by General Info (not in form). Only approval writes. ✓                                                | Not overwritten by General Info. ✓                                            |
| **Monthly report create**        | Not written.                                                                                                                                                                                                     | Not written.                             | Not written.        | **Read** and passed to view as `$amountSanctioned`. If 0, form and report store 0.                                                                  | Read in some flows.                                                           |
| **Monthly report edit**          | Not written.                                                                                                                                                                                                     | Not written.                             | Not written.        | Same: read from project, passed to view.                                                                                                            | Same.                                                                         |
| **Statements of account**        | Read (Basic Info block).                                                                                                                                                                                         | Read.                                    | Read.               | **Read** from project (via controller). Overview in report from `report.amount_sanctioned_overview` (which came from project at create).            | Read.                                                                         |
| **Dashboards / aggregates**      | Read (some lists).                                                                                                                                                                                               | Read.                                    | Read.               | **Read** (sum by type/center). Individual types add 0.                                                                                              | Read.                                                                         |
| **BudgetValidationService**      | Read; fallback to budgets sum for Dev.                                                                                                                                                                           | Read.                                    | Read.               | **Computed** from overall−forwarded−local (not read from DB).                                                                                       | Computed.                                                                     |

### 2.2 Type-specific tables (IIES, IES, ILP, IAH, IGE)

| Stage                   | Written?                     | Read by approval?                                  | Read by reporting?                                                                                                                                    | Synced to projects? |
| ----------------------- | ---------------------------- | -------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------- |
| **Budget entry (save)** | Yes (respective controller). | No.                                                | No (report uses project).                                                                                                                             | **No.**             |
| **Approval**            | No.                          | **No.** Approval does not load type-specific data. | —                                                                                                                                                     | No.                 |
| **Report create/edit**  | No.                          | —                                                  | **Yes** – BudgetCalculationService (strategies) load type-specific rows for **line-level** statement data. Overview sanctioned still from `$project`. | No.                 |

---

## 3. Discrepancy Analysis

### 3.1 Missing writes

| Discrepancy                                                                             | Location                                                                                                                                                          | Impact                                                                                                                                                             |
| --------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Individual/IGE type-specific data never written to `projects`**                       | No code path syncs IIES/IES/ILP/IAH/IGE budget or expense totals to `projects.overall_project_budget`, `local_contribution`, or pre-approval `amount_sanctioned`. | Basic Info shows Rs. 0.00; approval validates and computes using 0; reports and dashboards show 0 or wrong sanctioned/opening.                                     |
| **Development: budget row sum never synced to `projects.overall_project_budget`**       | BudgetController only writes `project_budgets`; it does not update `projects.overall_project_budget`.                                                             | User can enter a different overall in General Info than sum of phases; approval uses whichever is set (or fallback sum only when overall=0). Two sources of truth. |
| **`projects.amount_forwarded` / `local_contribution` not set from type-specific forms** | IIES/IES/ILP/IAH/IGE forms do not write to projects.                                                                                                              | Approval and validation use 0.                                                                                                                                     |

### 3.2 Premature or incorrect reads

| Discrepancy                                                    | Location                                                                                                                               | Impact                                                                                                                        |
| -------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| **Approval reads `projects` only**                             | CoordinatorController, GeneralController: overall/forwarded/local from `$project`; fallback overall = budgets->sum('this_phase') only. | For IIES/IES/ILP/IAH/IGE, approval **acts on zero** and stores amount_sanctioned = 0, opening_balance = 0.                    |
| **Report create passes `$project->amount_sanctioned` to form** | ReportController::create().                                                                                                            | If project has 0 (e.g. approved individual-type with no sync), user sees 0 and report stores 0; statements and PDF show 0.    |
| **BudgetValidationService assumes `projects` + `budgets`**     | calculateBudgetData() uses project and budgets sum; no type-specific resolution.                                                       | For individual/IGE, overall=0 → amount_sanctioned computed as 0, remaining balance wrong, validation warnings wrong or noisy. |
| **Dashboards sum `project->amount_sanctioned`**                | ProvincialController, CoordinatorController, GeneralController, ExecutorController.                                                    | Approved individual-type projects contribute 0 to totals.                                                                     |

### 3.3 Overwrites after approval

| Discrepancy                                                               | Location                                                                                                                                                                                                     | Impact                                                                                                                                                                                                                                                                                                                                         |
| ------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **General Info update can change overall/forwarded/local after approval** | GeneralInfoController::update() – no check for `status === approved_by_coordinator`. Validated request includes overall_project_budget, amount_forwarded, local_contribution; all are written to `$project`. | Approved project’s “overall” and “local contribution” can be changed post-approval. amount_sanctioned and opening_balance are **not** in the General Info update form, so they stay until next approval (if any). So: **overall/forwarded/local are not frozen**; sanctioned/opening are only set at approval and not updated by General Info. |
| **Development BudgetController can change budget rows after approval**    | BudgetController::update() – no status check.                                                                                                                                                                | Sum of phases can change; approval already ran and stored sanctioned/opening. Next time someone runs validation or a report, fallback overall (sum) may not match stored overall or sanctioned.                                                                                                                                                |

### 3.4 Divergent logic between approval, reporting, and dashboards

| Area                            | Approval                                                             | Reporting                                                                           | Dashboards / Validation                                    |
| ------------------------------- | -------------------------------------------------------------------- | ----------------------------------------------------------------------------------- | ---------------------------------------------------------- |
| **Source of overall**           | projects.overall_project_budget; fallback budgets->sum('this_phase') | Not used directly; report uses amount_sanctioned_overview (from project at create). | projects; validation also uses budgets sum when overall=0. |
| **Source of amount_sanctioned** | **Computed** at approval and saved to project.                       | **Read** from project, passed to form, stored in report.                            | **Read** from project.                                     |
| **Individual/IGE**              | Uses project only → 0.                                               | Uses project → 0; row-level data from BudgetCalculationService (type-specific).     | Project → 0.                                               |

So: **approval** and **reporting/dashboards** all assume the same source of truth (`projects`), but for individual/IGE that source is **never populated** from type-specific data. Row-level report data (strategies) is correct; project-level sanctioned/opening are wrong.

---

## 4. Architectural Root Causes

### 4.1 Historical evolution

- **projects** and **project_budgets** were designed for development-type projects where the user enters overall/forwarded/local in a “General Info” or budget screen and approval computes sanctioned/opening.
- **Individual and IGE** types were added with **separate** budget/expense tables and UI. The assumption that “budget lives in projects + project_budgets” was not extended: no sync from type-specific tables to `projects` was ever added.
- **Approval** was implemented once, using only `projects` (and `budgets` as fallback). It was not extended to resolve type-specific budgets.
- **Reporting** was unified (one ReportController, type-specific partials); project-level sanctioned/forwarded were always taken from `$project`, so they inherit the same gap.

### 4.2 Mixed budget models

- **Development (and RST, CIC, etc.):** Two stores – (1) `projects` (overall, forwarded, local) from General Info, (2) `project_budgets` (line items). Approval uses (1) with optional fallback to sum of (2). No automatic sync from (2) to (1).
- **Individual/IGE:** Single store – type-specific tables. No copy to `projects`. So the “canonical” contract (approval and reporting read from `projects`) is never satisfied for these types.

### 4.3 Implicit assumptions

- That “all project types either use General Info budget fields or use project_budgets” – false for IIES/IES/ILP/IAH/IGE.
- That “approval can always compute sanctioned from overall − forwarded − local” – true only if overall/forwarded/local are set; for individual/IGE they are not.
- That “report amount_sanctioned_overview = project.amount_sanctioned” – correct in code flow, but if project.amount_sanctioned is wrong, the report is wrong.
- That “dashboards can sum project.amount_sanctioned” – understates budget when many projects are individual/IGE.

### 4.4 Lack of a canonical budget contract

- There is no single service or document that defines: “For every project type, these five values (overall, forwarded, local, sanctioned, opening) SHALL be available here (e.g. on `projects`) at these lifecycle points.”
- Approval, reporting, and validation each **assume** project-level values are on `projects` but do not **enforce** that they are populated for all types. No “resolver” or “sync” step guarantees that before approval or before first report.

---

## 5. Recommended Architecture (Live-System Safe)

### 5.1 Principle: `projects` as single source of truth for project-level fund fields

- **Keep** using `projects.overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned`, `opening_balance` as the **only** source for approval, reporting, Basic Info, validation, and dashboards.
- **Ensure** that for **every** project type (including IIES, IES, ILP, IAH, IGE), these columns are **populated** at the right time, either by user input (General Info) or by **explicit sync** from type-specific tables.

### 5.2 Dedicated Project Budget Sync / Resolver

- **Introduce a small service** (e.g. `ProjectFundFieldsResolver` or `ProjectBudgetSyncService`) that:
    - **Resolves** the five fund values for a given project from:
        - **Development (and similar):** `projects` (and optionally sum of `project_budgets.this_phase` for current phase when overall is 0).
        - **IIES, IES, ILP, IAH, IGE:** Type-specific tables, using the mapping in `Basic_Info_Fund_Fields_Mapping_Analysis.md` (and `Approvals_And_Reporting_Budget_Integration_Recommendations.md`).
    - **Sync** (optional but recommended): Writes resolved values back to `projects` so that all existing consumers continue to work without branching on project type.

### 5.3 Where and when it should run

| When                                                    | Action                                                                                                                                                    | Rationale                                                                                                                                                                       |
| ------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **On save/update of type-specific budget**              | After IIES/IES/ILP/IAH/IGE budget or expense is saved, call resolver and **update `projects`** with the five values (or the subset the type defines).     | So that before approval, `projects` already has correct overall/local (and optionally pre-computed sanctioned/opening for display).                                             |
| **Immediately before approval (coordinator / general)** | For project types that use type-specific budgets, **call resolver and sync to `projects`** before reading overall/forwarded/local and running validation. | Handles projects that were never synced (e.g. approved before sync existed, or budget saved in a path that didn’t sync). Ensures approval acts on final, authoritative numbers. |
| **Optional: on first report create**                    | If `amount_sanctioned` is 0 and project type is individual/IGE, call resolver and sync, then use updated project for the form.                            | Safety net for legacy data.                                                                                                                                                     |

### 5.4 Which columns should be canonical vs derived

- **Canonical (stored on `projects`):**  
  `overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned`, `opening_balance`.  
  All approval, reporting, and dashboard logic should **read only** these.

- **Derived at display time (optional):**  
  If sync is guaranteed, nothing need be derived in views. If sync is not yet implemented, Basic Info view could derive from type-specific data for display only (see Basic_Info_Fund_Fields_Mapping_Analysis.md Option A); approval and reporting would still need sync or resolver at approval/report time.

- **Approval “freeze”:**  
  At approval time, the coordinator (or general) **reads** overall/forwarded/local (after sync for individual/IGE), **validates** (forwarded + local ≤ overall), **computes** amount_sanctioned and opening_balance, and **writes** them to `projects`. After that, these two columns should be treated as **frozen** for that approval. Optionally: prevent General Info (and budget) from **changing** overall/forwarded/local when `status === approved_by_coordinator` (or require a formal “budget amendment” flow).

### 5.5 Development projects: optional sync from budget sum

- **Option A:** Leave as-is: user enters overall/forwarded/local in General Info; approval uses them; fallback to sum of phases when overall=0.
- **Option B:** When development budget is saved, optionally **recompute** overall from current phase’s sum and write to `projects.overall_project_budget` (and optionally recompute sanctioned/opening if not yet approved). Reduces divergence between “form overall” and “sum of phases.”

### 5.6 No change to report storage model

- **DP_Reports.amount_sanctioned_overview** (and similar in other report types) should continue to be **set from the request** (form pre-filled from `$project`). Once `projects.amount_sanctioned` is correct (via sync and approval), report create/edit will store correct values without any change to report controllers.

---

## 6. Migration & Backfill Strategy

### 6.1 Detecting incorrect or missing values

- **Queries (examples):**
    - Projects with `project_type` in (IIES, IES, ILP, IAH, IGE) and `status = approved_by_coordinator` and (`amount_sanctioned` is null or 0 or `overall_project_budget` = 0).
    - Same types, with non-zero budget/expense in type-specific tables but `projects.overall_project_budget` = 0.
- **Resolver:** Run the new resolver (read-only) for each such project and compare resolver output to current `projects` values. Log discrepancies.

### 6.2 Safely backfilling

- **Order:** Implement resolver and sync logic first; test on staging with a few projects per type.
- **Backfill script/command:**
    - For each project where type is IIES/IES/ILP/IAH/IGE and type-specific data exists:
        - Call resolver.
        - Update `projects` with resolved overall, forwarded, local.
        - If project is **already approved**, set amount_sanctioned and opening_balance from resolver (or keep existing if you prefer not to change post-approval; then at least overall/local will be correct for validation and display).
    - **Do not** overwrite `amount_sanctioned` / `opening_balance` for approved projects if policy is “freeze at approval” – unless you explicitly run a one-time “recompute from type-specific data” and log it.
- **Idempotency:** Resolver + update can be run multiple times; use resolver output as source of truth for the backfill.

### 6.3 Reports already created with 0

- **Existing reports** that have `amount_sanctioned_overview = 0` because project had 0 at create time: options are (1) leave as-is (historical record), (2) one-time update of `DP_Reports.amount_sanctioned_overview` from current `projects.amount_sanctioned` after backfill (with clear audit log), or (3) document that “reports created before backfill may show 0; use project Basic Info for current sanctioned amount.” Prefer (2) only if you have a clear audit trail and approval.

### 6.4 Logging and audit

- **Log** every resolver run (project_id, project_type, resolved values, whether project was updated).
- **Log** approval: already partially done; ensure overall/forwarded/local/sanctioned/opening are logged at save.
- **Backfill:** Log each project_id updated, old vs new values, timestamp.

---

## 7. Risk Assessment

### 7.1 High-risk silent errors

| Risk                                        | Description                                                                                                                                                                                                | Mitigation                                                                                                                        |
| ------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| **Approval based on zero**                  | Coordinator approves an IIES/IES/ILP/IAH/IGE project; validation passes (0+0≤0); amount_sanctioned and opening_balance stored as 0. No error shown.                                                        | Sync (or resolver) before approval so overall/local are set from type-specific data; then validation and computation are correct. |
| **Reports and statements show 0**           | Executor creates monthly report; form shows Rs. 0.00 sanctioned; report and statements of account store and display 0. Donors/auditors see incorrect figures.                                              | Same: ensure `projects.amount_sanctioned` is set (by sync + approval or backfill).                                                |
| **General Info overwrites approved budget** | User edits General Info after approval and changes overall_project_budget or local_contribution; next validation or display shows new values; amount_sanctioned/opening_balance unchanged → inconsistency. | Restrict General Info budget fields when status is approved (or use a formal amendment workflow).                                 |
| **Dashboard understates budget**            | Provincial/coordinator dashboards sum `amount_sanctioned`; individual/IGE projects contribute 0 → total approved budget is understated.                                                                    | Sync + backfill so projects.amount_sanctioned is correct.                                                                         |

### 7.2 Reporting inaccuracies

- **Current:** Report create/edit and statements use `$project->amount_sanctioned` and store it in reports. If project has 0, all downstream (PDF, export, monitoring) are wrong.
- **After fix:** Same code path; correct values once project is synced and approved.

### 7.3 Approval inconsistencies

- **Current:** Same approval logic for all types; for individual/IGE it effectively “approves” a zero budget.
- **After fix:** Sync before approval so approval always sees and freezes the correct overall/local/sanctioned/opening.

### 7.4 Data integrity

- **Two sources of “overall” for development:** General Info vs sum of phases. Mitigation: optional sync from budget sum on save, or validation warning when they diverge.
- **Type-specific tables vs projects:** Today no referential or derived link. Mitigation: resolver + sync so that `projects` is the derived copy of type-specific totals for individual/IGE.

---

## 8. File and Component Reference (Checklist)

| Component                               | File(s)                                                                                                                             | Role                                                                                                                                     |
| --------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| **projects budget columns**             | `database/migrations/2024_07_20_085634_create_projects_table.php`, `2026_01_07_000001_add_local_contribution_to_projects_table.php` | Schema.                                                                                                                                  |
| **General Info (write)**                | `GeneralInfoController::store()`, `update()`                                                                                        | Writes overall, forwarded, local. Does not check approval status.                                                                        |
| **Development budget (write)**          | `BudgetController::store()`, `update()`                                                                                             | Writes only `project_budgets`. Does not touch `projects`.                                                                                |
| **IIES/IES/ILP/IAH/IGE budget (write)** | IIES expense controller, IES expense controller, ILP BudgetController, IAH BudgetDetailsController, IGE BudgetController            | Write type-specific tables only. Do not sync to `projects`.                                                                              |
| **Approval (read + write)**             | `CoordinatorController::approveProject()`, `GeneralController` (coordinator branch)                                                 | Read overall/forwarded/local from project; fallback overall = budgets->sum('this_phase'); compute and write sanctioned, opening.         |
| **Report create/edit**                  | `ReportController::create()`, `edit()`                                                                                              | Pass `$project->amount_sanctioned` (and 0 forwarded) to view; store request values in DP_Reports.                                        |
| **Statements of account**               | `resources/views/reports/monthly/partials/statements_of_account.blade.php` + type partials                                          | Receive amountSanctioned/amountForwarded from controller; display and submit.                                                            |
| **Budget row-level for reports**        | `BudgetCalculationService::getBudgetsForReport()`, config `config/budget.php`, strategies                                           | Correct per type for **rows**; do not set project-level five fields.                                                                     |
| **Validation**                          | `BudgetValidationService::calculateBudgetData()`                                                                                    | Uses project + budgets; no type-specific resolution.                                                                                     |
| **Dashboards / aggregates**             | ProvincialController, CoordinatorController, GeneralController, ExecutorController                                                  | Sum or display project.amount_sanctioned, overall_project_budget.                                                                        |
| **Basic Info view**                     | `resources/views/projects/partials/Show/general_info.blade.php`                                                                     | Reads all five from `$project`.                                                                                                          |
| **One-time backfill migration**         | `2025_06_26_181405_update_amount_sanctioned_for_approved_projects.php`                                                              | Set amount_sanctioned from overall for approved projects where sanctioned was null/0. Does not handle individual/IGE type-specific data. |
| **IIES budget (write)**                 | `IIES\IIESExpensesController::store()`                                                                                              | Writes `project_IIES_expenses` + details only. No sync to `projects`.                                                                    |
| **IES budget (write)**                  | `IES\IESExpensesController`                                                                                                         | Writes `project_IES_expenses` + details only. No sync to `projects`.                                                                     |
| **ILP budget (write)**                  | `ILP\BudgetController::store()`                                                                                                     | Writes `project_ILP_budget` only. No sync to `projects`.                                                                                 |
| **IAH budget (write)**                  | `IAH\IAHBudgetDetailsController::store()` / `update()`                                                                              | Writes `project_IAH_budget_details` only. No sync to `projects`.                                                                         |
| **IGE budget (write)**                  | `IGE\IGEBudgetController::store()`                                                                                                  | Writes `project_IGE_budget` only. No sync to `projects`.                                                                                 |
| **Monthly report statements (create)**  | `ReportAll.blade.php`, `partials.statements_of_account.*`, `partials.create.statements_of_account`                                  | Type-specific or generic; overview from `$amountSanctioned` (= `$project->amount_sanctioned`).                                           |
| **Monthly report statements (edit)**    | `edit.blade.php`, `partials.edit.statements_of_account.*`, `partials.edit.statements_of_account` (generic)                          | Overview from `$report->amount_sanctioned_overview` / `$amountSanctioned`; row amounts from report or budgets.                           |
| **Project types enum**                  | `App\Constants\ProjectType`                                                                                                         | All institutional and individual types; used for routing and budget config.                                                              |
| **Budget config (strategies)**          | `config/budget.php`                                                                                                                 | Field mappings and strategy per project type for BudgetCalculationService.                                                               |

---

## 9. Summary

- **projects** holds the five budget fields that approval, reporting, and dashboards use; for **individual and IGE** types these are **never** populated from type-specific budget/expense tables, so they stay 0 and approval/reporting/dashboards are wrong.
- **Development** projects: overall/forwarded/local come from General Info; budget rows from BudgetController; no sync from budget sum to projects, so two sources of “overall” can diverge; approval can overwrite sanctioned/opening; General Info can overwrite overall/forwarded/local even after approval.
- **Fix:** Introduce a **ProjectFundFieldsResolver** (and optional sync) that fills `projects` from type-specific data for IIES/IES/ILP/IAH/IGE; run sync on type-specific budget save and **before approval**; optionally sync development overall from budget sum; **freeze** or restrict edits to overall/forwarded/local after approval; **backfill** existing approved individual/IGE projects and optionally correct existing reports with audit.
- This keeps the existing contract (“everyone reads from projects”) and fixes it by making sure `projects` is **always** populated correctly for every project type at the right stages, with explicit sync and minimal change to approval/reporting code.

---

## 10. Additional Project Types Audit

All project types are defined in `App\Constants\ProjectType`. The following subsections cover each type and its budget lifecycle.

### 10.1 Institutional types (use `project_budgets` + General Info)

| Type (constant label)                                      | Budget table         | Budget written by           | General Info budget fields      | Approval fallback              | Synced to projects?                     |
| ---------------------------------------------------------- | -------------------- | --------------------------- | ------------------------------- | ------------------------------ | --------------------------------------- |
| **Development Projects**                                   | `project_budgets`    | `Projects\BudgetController` | Yes (overall, forwarded, local) | budgets->sum('this_phase')     | No (overall not synced from budget sum) |
| **Livelihood Development Projects**                        | `project_budgets`    | Same                        | Same                            | Same                           | No                                      |
| **Residential Skill Training Proposal 2**                  | `project_budgets`    | Same                        | Same                            | Same                           | No                                      |
| **PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER**        | `project_budgets`    | Same                        | Same                            | Same                           | No                                      |
| **CHILD CARE INSTITUTION**                                 | `project_budgets`    | Same                        | Same                            | Same                           | No                                      |
| **Rural-Urban-Tribal**                                     | `project_budgets`    | Same                        | Same                            | Same                           | No                                      |
| **NEXT PHASE - DEVELOPMENT PROPOSAL**                      | `project_budgets`    | Same                        | Same                            | Same                           | No                                      |
| **Institutional Ongoing Group Educational proposal (IGE)** | `project_IGE_budget` | `IGE\IGEBudgetController`   | Not used for budget             | **No** (no `budgets` relation) | **No**                                  |

**Lifecycle (institutional except IGE):** Create → General Info (overall/forwarded/local) → Budget (phases in `project_budgets`) → Approval reads `projects` + fallback sum → sanctioned/opening written to `projects`. **Mismatch:** BudgetController never writes to `projects`; overall can diverge from sum of phases.

**IGE:** Budget in `project_IGE_budget` only; no write to `projects`. Approval uses `projects` only → overall=0, sanctioned=0, opening=0.

### 10.2 Individual types (type-specific tables only)

| Type                                                  | Budget/expense table              | Written by                       | Read by approval? | Synced to projects? |
| ----------------------------------------------------- | --------------------------------- | -------------------------------- | ----------------- | ------------------- |
| **Individual - Initial - Educational support (IIES)** | `project_IIES_expenses` + details | `IIES\IIESExpensesController`    | No                | No                  |
| **Individual - Ongoing Educational support (IES)**    | `project_IES_expenses` + details  | `IES\IESExpensesController`      | No                | No                  |
| **Individual - Livelihood Application (ILP)**         | `project_ILP_budget`              | `ILP\BudgetController`           | No                | No                  |
| **Individual - Access to Health (IAH)**               | `project_IAH_budget_details`      | `IAH\IAHBudgetDetailsController` | No                | No                  |

**Lifecycle (each):** Create → type-specific budget/expense form → save to type table only → Approval reads only `projects` (overall/forwarded/local all 0) → amount_sanctioned and opening_balance stored as 0 → Reports and dashboards show 0.

### 10.3 Monthly report statements partial mapping

| Project type                               | Create statements partial                                                 | Edit statements partial                           | Fallback                                             |
| ------------------------------------------ | ------------------------------------------------------------------------- | ------------------------------------------------- | ---------------------------------------------------- |
| IIES                                       | `statements_of_account.individual_education`                              | `edit.statements_of_account.individual_education` | —                                                    |
| IES                                        | `statements_of_account.individual_ongoing_education`                      | `edit...individual_ongoing_education`             | —                                                    |
| ILP                                        | `statements_of_account.individual_livelihood`                             | `edit...individual_livelihood`                    | —                                                    |
| IAH                                        | `statements_of_account.individual_health`                                 | `edit...individual_health`                        | —                                                    |
| IGE                                        | `statements_of_account.institutional_education`                           | `edit...institutional_education`                  | —                                                    |
| Development Projects                       | `statements_of_account.development_projects`                              | `edit...development_projects`                     | —                                                    |
| Livelihood, RST, CIC, CCI, RUT, NEXT PHASE | **Generic** `create.statements_of_account` / `edit.statements_of_account` | Same                                              | development-style rows from BudgetCalculationService |

**Mismatch:** For types using the generic partial, row-level amounts still come from `BudgetCalculationService::getBudgetsForReport()` (correct per type). Overview `amount_sanctioned_overview` and `amount_in_hand` always come from controller: create from `$project->amount_sanctioned`, edit from `$report->amount_sanctioned_overview`. If `projects.amount_sanctioned` is 0 (individual/IGE), report stores and displays 0.

---

## 11. JavaScript vs Backend Discrepancies

### 11.1 Report create/edit – statements of account

| Area                           | Backend expectation                                                                                               | JavaScript behaviour                                                                                                                        | Persisted?                                                 |
| ------------------------------ | ----------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------- |
| **amount_sanctioned_overview** | From request; default `$project->amount_sanctioned ?? 0` (create) or `$report->amount_sanctioned_overview` (edit) | Input is readonly; value comes from server-rendered `$amountSanctioned` / `$report->amount_sanctioned_overview`. No JS computation.         | Yes – form submits value to backend; stored in DP_Reports. |
| **amount_in_hand**             | From request; create view sets = amountSanctioned when forwarded=0                                                | Same: readonly; `calculateTotalAmount()` sets `amount_in_hand = amount_sanctioned_overview` (no forwarded). Not recomputed from row totals. | Yes – stored in DP_Reports.                                |
| **Row amount_sanctioned[]**    | From request; create pre-fills from `BudgetCalculationService::getBudgetsForReport()` (amount_sanctioned per row) | Type-specific partials: readonly; `calculateRowTotals()` uses row amount_sanctioned for total_amount. No JS-only source.                    | Yes – stored in DP_AccountDetails.                         |
| **total_amount[]**             | Computed server-side if missing: `total_amount = amount_sanctioned` (forwarded=0)                                 | `totalAmount = amountSanctioned` (no forwarded). Aligned with backend.                                                                      | Yes.                                                       |
| **Generic partial (edit)**     | `total_amount = amount_forwarded + amount_sanctioned`; backend forces amount_forwarded=0                          | JS: `totalAmount = amountForwarded + amountSanctioned`; form still sends amount_forwarded[] (0).                                            | Consistent.                                                |

**Conclusion:** JS does not introduce a second source of truth for sanctioned/opening; it uses server-provided values. The discrepancy is upstream: backend passes `$project->amount_sanctioned` which is 0 for individual/IGE.

### 11.2 Project create/edit – General Info

| Area                                                             | Backend                                                                 | Frontend                                                | Note                                                                                                                                   |
| ---------------------------------------------------------------- | ----------------------------------------------------------------------- | ------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| **overall_project_budget, amount_forwarded, local_contribution** | GeneralInfoController store/update; validated and written to `projects` | Form fields; no JS auto-fill from type-specific budget. | Individual/IGE forms do not show or sync these from type tables; they stay 0 unless user enters in General Info (rare for individual). |

### 11.3 Budget forms (type-specific)

| Type                     | JS behaviour                               | Backend                                        | Synced to projects? |
| ------------------------ | ------------------------------------------ | ---------------------------------------------- | ------------------- |
| Development (phases)     | Form may sum phases for display            | BudgetController writes only `project_budgets` | No                  |
| IIES, IES, ILP, IAH, IGE | Type-specific forms; totals in type tables | Controllers write only type-specific tables    | No                  |

**Conclusion:** No JS computes project-level five fields from type-specific data; no sync from type-specific save to `projects`. Backend never expects JS to send overall/sanctioned/opening from individual/IGE forms.

### 11.4 Where defaults differ

| Layer                   | amount_forwarded       | amount_sanctioned_overview (report)                                                |
| ----------------------- | ---------------------- | ---------------------------------------------------------------------------------- |
| Backend (report create) | 0.00 always            | `$project->amount_sanctioned ?? 0.00`                                              |
| Backend (report edit)   | 0 in DP_AccountDetails | `$report->amount_sanctioned_overview` (from DB)                                    |
| View (create)           | 0.00                   | `$amountSanctioned ?? 0.00` (from controller)                                      |
| View (edit)             | 0 (stored)             | `$report->amount_sanctioned_overview ?? $amountSanctioned ?? 0.00` (type partials) |

Defaults are aligned (0) where forwarded is concerned; sanctioned is aligned to project/report, but project is wrong for individual/IGE.

---

## 12. Monthly Reporting Lifecycle Matrix (Expanded)

Per project type: where sanctioned/opening/forwarded come from at create vs edit, and what is stored.

### 12.1 Report create

| Project type                                     | amount_sanctioned passed to view                                                   | amount_in_hand                   | Row amount_sanctioned[] source                                 | Stored in DP_Reports | Stored in DP_AccountDetails |
| ------------------------------------------------ | ---------------------------------------------------------------------------------- | -------------------------------- | -------------------------------------------------------------- | -------------------- | --------------------------- |
| Development, RST, CIC, CCI, RUT, LDP, NEXT_PHASE | `$project->amount_sanctioned` (set only at approval; may be 0 if not yet approved) | Same as sanctioned (forwarded=0) | BudgetCalculationService (this_phase or strategy)              | From form (request)  | From form (request)         |
| IIES, IES, ILP, IAH                              | `$project->amount_sanctioned` → **0** (never synced)                               | 0                                | BudgetCalculationService (type strategy; correct per row)      | 0                    | Correct row amounts         |
| IGE                                              | `$project->amount_sanctioned` → **0**                                              | 0                                | BudgetCalculationService (DirectMappingStrategy; total_amount) | 0                    | Correct row amounts         |

### 12.2 Report edit

| Project type | amount_sanctioned_overview reloaded from                                                                                      | amount_in_hand                        | Row amounts reloaded from                             | Updated in DP_Reports | Updated in DP_AccountDetails |
| ------------ | ----------------------------------------------------------------------------------------------------------------------------- | ------------------------------------- | ----------------------------------------------------- | --------------------- | ---------------------------- |
| All          | `$report->amount_sanctioned_overview` (and type partials may fallback to `$amountSanctioned` = `$project->amount_sanctioned`) | `$report->amount_in_hand` or computed | Existing DP_AccountDetail rows + budgets for new rows | Request (validated)   | Request (validated)          |

**Critical:** On edit, the overview values are **trusted** from the report (and project fallback). They are not recomputed from type-specific budget. So if the report was created when `projects.amount_sanctioned` was 0, the report keeps 0 until someone manually changes it or backend is fixed (sync + optional backfill).

### 12.3 Backend vs frontend responsibilities

| Responsibility                             | Backend                                                                                                | Frontend (JS/views)                                                 |
| ------------------------------------------ | ------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------- |
| **Source of sanctioned (overview)**        | ReportController: create = `$project->amount_sanctioned`, edit = `$report->amount_sanctioned_overview` | Display only; readonly inputs; submit whatever backend put in form. |
| **Source of row amount_sanctioned**        | BudgetCalculationService::getBudgetsForReport() (create); existing accountDetails (edit)               | Display; readonly in type partials; submit to backend.              |
| **Recompute sanctioned from type budget?** | No (today)                                                                                             | No                                                                  |
| **Write to projects on type budget save?** | No                                                                                                     | N/A                                                                 |

---

## 13. Newly Identified Discrepancies

### 13.1 Missing writes (additional)

| Discrepancy                                                                        | Location                                                                                                         | Impact                                                                                                     |
| ---------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| **IGE budget never synced to projects**                                            | IGEBudgetController writes only `project_IGEBudget`; approval has no fallback for IGE (no `budgets` relation)    | IGE projects always approved with sanctioned=0, opening=0; reports and dashboards show 0.                  |
| **RST, CIC, CCI, RUT, LDP, NEXT_PHASE use project_budgets but overall not synced** | Same as Development: BudgetController does not update `projects.overall_project_budget`                          | Same two-sources risk: General Info overall vs sum of phases.                                              |
| **Generic statements partial used for RST, CIC, CCI, RUT, LDP, NEXT_PHASE**        | Edit view: these types fall to `edit.statements_of_account` (generic) which still uses report/project sanctioned | Flow is correct; issue remains that project sanctioned is wrong if approval used 0 (e.g. overall not set). |

### 13.2 Premature or incorrect reads (additional)

| Discrepancy                                                      | Location                                                                                                                                                                          | Impact                                                                                                                  |
| ---------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| **Approval never loads type-specific budget for individual/IGE** | CoordinatorController, GeneralController: only `$project->overall_project_budget`, `amount_forwarded`, `local_contribution`; fallback only `$project->budgets->sum('this_phase')` | IGE has no `budgets`; individual types have type tables only → fallback not used → 0.                                   |
| **BudgetValidationService budget_items_total only from budgets** | `calculateBudgetData()`: budget_items_total = `$project->budgets->sum('this_phase')`                                                                                              | For individual/IGE, budgets relation empty or not loaded → 0 → "no budget items" warning even when type table has data. |

### 13.3 Double sources of truth

| Context                                               | Source 1                                         | Source 2                                                                   | When they diverge                                                      |
| ----------------------------------------------------- | ------------------------------------------------ | -------------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| Development (and RST, CIC, CCI, RUT, LDP, NEXT_PHASE) | `projects.overall_project_budget` (General Info) | Sum of `project_budgets.this_phase` (current phase)                        | When user enters overall ≠ sum; approval uses overall or fallback sum. |
| Individual/IGE project-level sanctioned               | `projects.amount_sanctioned` (approval; 0)       | Type-specific table totals (e.g. iies_balance_requested, amount_requested) | Always: projects never updated from type tables.                       |

### 13.4 Type-specific exceptions

| Type                                  | Exception                                                                                                                                                           |
| ------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **IGE**                               | Uses `project_IGE_budget`; config uses DirectMappingStrategy with `total_amount`; no phase_based; no `project_budgets` → approval fallback never applies.           |
| **NEXT PHASE - DEVELOPMENT PROPOSAL** | Same budget flow as Development; may have predecessor; no separate budget controller.                                                                               |
| **Livelihood Development Projects**   | Same as Development for budget; has separate report annexure (LivelihoodAnnexure); statements fall to generic in edit (no dedicated livelihood statements partial). |

---

## 14. Updated Recommendations

### 14.1 Standardize across ALL project types

1. **Single canonical ledger:** Treat `projects` as the only project-level source for `overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned`, `opening_balance` for approval, reporting, Basic Info, validation, and dashboards.
2. **Populate before approval:** For every project type, ensure these five fields are populated (by user input or by explicit sync from type-specific tables) **before** coordinator/general approval. No approval should run on zero for types that have non-zero type-specific budget.
3. **One resolver for all types:** Implement a **ProjectFundFieldsResolver** (or extend BudgetCalculationService) that:
    - For institutional types using `project_budgets`: resolve overall from `projects` or sum of `project_budgets.this_phase` (current phase), forwarded/local from `projects`.
    - For IIES, IES, ILP, IAH, IGE: resolve overall, local_contribution, and (pre-approval) amount_sanctioned/opening from type-specific tables per Basic_Info_Fund_Fields_Mapping_Analysis.md.
    - Optionally write resolved values back to `projects` (sync) on type-specific budget save and immediately before approval.

### 14.2 Logic to move to shared services

| Current location                                                           | Move to                                                            | Purpose                                                                                          |
| -------------------------------------------------------------------------- | ------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------ |
| Approval budget read + fallback (CoordinatorController, GeneralController) | ProjectFundFieldsResolver (or shared helper)                       | Single place to resolve five fund fields for any project type before validation and computation. |
| Type-specific “amount requested / balance requested” → sanctioned mapping  | Same resolver + config/mapping                                     | So approval and reporting use same mapping as Basic_Info_Fund_Fields_Mapping_Analysis.md.        |
| BudgetValidationService calculateBudgetData() overall/budgets logic        | Call resolver when overall=0 and type is individual/IGE (optional) | So validation uses resolved overall/local for warnings.                                          |

### 14.3 JavaScript and server-side validation

| Action                    | Recommendation                                                                                                                                                                                                          |
| ------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Reduce JS logic**       | Keep report statements as display + submit of server-provided values; do not add JS that recomputes sanctioned from type budget (would duplicate logic and risk divergence).                                            |
| **Validate server-side**  | On report submit, optionally validate that `amount_sanctioned_overview` is not 0 when project type is individual/IGE and type-specific tables have non-zero requested amount; either reject or auto-fill from resolver. |
| **Freeze after approval** | Restrict General Info (and development BudgetController) from changing overall/forwarded/local when status is approved_by_coordinator (or require amendment workflow).                                                  |

### 14.4 New sync points

| When                                                          | Action                                                                                                                                                                                     |
| ------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **On save/update of IIES, IES, ILP, IAH, IGE budget/expense** | Call resolver; update `projects.overall_project_budget`, `local_contribution`, and optionally pre-approval display fields (not amount_sanctioned/opening if policy is “only at approval”). |
| **Immediately before coordinator/general approval**           | For all project types, call resolver and sync the five fields to `projects` so approval reads correct overall/forwarded/local and computes correct sanctioned/opening.                     |
| **Optional: on first monthly report create**                  | If `amount_sanctioned` is 0 and project type is individual/IGE, call resolver and sync, then re-read project for the form (safety net for legacy data).                                    |
| **Development (optional)**                                    | On BudgetController store/update, optionally recompute `projects.overall_project_budget` from current phase sum and write to `projects` to reduce divergence with General Info.            |

### 14.5 Reporting and statements

- **No change to report storage model:** DP_Reports and DP_AccountDetails continue to receive values from the request (form pre-filled from project/report). Once `projects.amount_sanctioned` is correct (via sync and approval), report create/edit will store correct values without changing ReportController logic.
- **Backfill:** After resolver + sync is in place, run a one-time backfill for existing approved individual/IGE projects (and optionally update existing reports that have amount_sanctioned_overview=0) with audit logging.

---

_Document version: 1.1 – Extended system-wide budget audit: all project types, monthly reporting lifecycle, JS vs backend, and updated recommendations. Companion to Basic_Info_Fund_Fields_Mapping_Analysis.md and Approvals_And_Reporting_Budget_Integration_Recommendations.md._
