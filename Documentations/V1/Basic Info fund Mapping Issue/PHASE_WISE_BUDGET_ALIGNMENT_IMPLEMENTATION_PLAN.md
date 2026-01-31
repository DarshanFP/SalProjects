# Phase-Wise Budget Alignment Implementation Plan

**Role:** Principal Engineer, Release Architect, Financial Systems Steward  
**Purpose:** Implementation-ready, incremental plan to fix budget architecture issues in a LIVE Laravel application without breaking existing functionality.  
**Companion documents:** Budget_System_Wide_Audit.md, Basic_Info_Fund_Fields_Mapping_Analysis.md, Approvals_And_Reporting_Budget_Integration_Recommendations.md.

---

## 1. Implementation Principles

### 1.1 Non-breaking changes

- **No removal of existing code paths** until the new path is proven and switched over.
- **Additive only:** New service (resolver), new calls at defined insertion points; existing controller logic remains until explicitly replaced.
- **Same contract:** Approval, ReportController, dashboards, and validation continue to **read** from `projects`; we only ensure `projects` is **populated correctly** at the right times.

### 1.2 Backward compatibility

- **Existing reports and approvals** are never mutated silently. Backfill (Phase 6) is a separate, auditable step.
- **Form request shapes** for report create/edit, General Info, and budget forms stay unchanged; no change to frontend field names or validation rules that would break existing submissions.
- **Database schema:** No new required columns on `projects` or report tables for the core fix; optional audit columns (e.g. `budget_synced_at`) can be added later.

### 1.3 Explicit sync over implicit computation

- **Single resolver** returns the five fund values (overall, forwarded, local, sanctioned, opening) for a project; sync to `projects` happens only at defined events (type budget save, pre-approval) and is logged.
- **No “magic”** in views or JS: report create/edit continue to use `$project->amount_sanctioned` (and report stored values on edit); once `projects` is correct, behaviour is correct without new branching.

### 1.4 Clear ownership of budget fields

| Field(s)                                                           | Owner (who may write)                                                                                              | When                                         |
| ------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------ | -------------------------------------------- |
| `overall_project_budget`, `amount_forwarded`, `local_contribution` | GeneralInfoController (institutional); Resolver sync (individual/IGE); optionally Resolver (development phase sum) | Create, edit, type budget save, pre-approval |
| `amount_sanctioned`, `opening_balance`                             | Approval only (CoordinatorController, GeneralController)                                                           | At coordinator/general approval              |
| Report overview/row amounts                                        | ReportController (from request)                                                                                    | Report create/update                         |
| **Governance override (all five)**                                 | Admin (Phase 6a) – explicit, logged correction for approved projects                                               | Admin reconciliation UI – accept or manual   |

Post-approval: sanctioned and opening are **frozen** by normal flows; overall/forwarded/local edits are **restricted** (Phase 3). Admin correction is an **exception** for governance; every change is logged and explicit.

### 1.5 Auditability and logging

- **Resolver:** Log every call (project_id, project_type, resolved values, dry_run vs sync) at info level.
- **Sync to projects:** Log old vs new values, timestamp, trigger (e.g. `type_budget_save`, `pre_approval`).
- **Approval:** Existing logs; ensure resolved values used for computation are included.
- **Backfill:** Full audit table or log: project_id, old values, new values, run_id, operator.

---

## 2. Phase Overview

| Phase  | Objective                                             | Risk   | Affected areas                                                                    | Rollback strategy                                |
| ------ | ----------------------------------------------------- | ------ | --------------------------------------------------------------------------------- | ------------------------------------------------ |
| **0**  | Pre-implementation safeguards                         | Low    | Config, logging, feature flags                                                    | Remove flag/config; no DB or behaviour change.   |
| **1**  | Canonical budget resolution (read-only)               | Low    | New service; optional view/validation read path                                   | Disable resolver usage; remove calls.            |
| **2**  | Controlled sync to `projects`                         | Medium | Type budget controllers; approval entry point                                     | Disable sync flag; resolver remains read-only.   |
| **3**  | Approval workflow alignment                           | Medium | CoordinatorController; GeneralController; GeneralInfoController; BudgetController | Revert status checks and approval read path.     |
| **4**  | Reporting & statements alignment                      | Low    | ReportController create/edit (optional safety net)                                | Remove optional auto-fill; no change to storage. |
| **5**  | Dashboards & aggregates                               | Low    | No code change if Phase 1–2 correct                                               | N/A (verification only).                         |
| **6**  | Backfill & data correction                            | High   | DB updates; one-off commands                                                      | Restore from backup; document backfill run.      |
| **7**  | Hardening & guardrails                                | Medium | Validation; status checks; tests                                                  | Revert guards and tests.                         |
| **6a** | Admin reconciliation & correction (human-in-the-loop) | Medium | New admin UI; BudgetReconciliationController; audit table                         | Disable feature flag; remove routes.             |

**Why this order:**  
Phase 0 ensures we can observe and kill-switch. Phase 1 proves resolver correctness without writing. Phase 2 populates `projects` so approval and reporting get correct values. Phase 3 freezes post-approval edits. Phase 4 adds optional report safety. Phase 5 validates that dashboards (which already read `projects`) are correct. Phase 6 fixes historical data (automated backfill command). **Phase 6a** provides admin-assisted, explicit correction for approved projects where automated backfill is not desired or where human review is required. Phase 7 prevents regression.

---

## 3. Phase 0 – Pre-Implementation Safeguards

### 3.1 Feature flags (if applicable)

- **Config key** (e.g. `config/budget.php`):
    - `budget.resolver_enabled` (bool, default `false`) – when true, resolver is used for read path (Phase 1) and optionally for sync (Phase 2).
    - `budget.sync_to_projects_on_type_save` (bool, default `false`).
    - `budget.sync_to_projects_before_approval` (bool, default `false`).
    - `budget.restrict_general_info_after_approval` (bool, default `false`) – Phase 3.
    - `budget.admin_reconciliation_enabled` (bool, default `false`) – Phase 6a; when true, admin reconciliation UI and apply-correction flow are available.
- **Rationale:** Allows deployment of code with all flags false; enable per phase after validation.

### 3.2 Read-only resolver mode

- Resolver API: `resolve(Project $project, bool $dryRun = true): array`.  
  When `$dryRun === true`, return resolved five values but **never** write to `projects`.  
  Phase 1 uses only `dryRun = true` (or equivalent “read-only” mode).

### 3.3 Logging / dry-run checks

- **Metrics to log (Phase 0 / 1):**
    - Count of projects by type where `projects.overall_project_budget` (or amount_sanctioned) is 0 but resolver returns non-zero.
    - Sample of project_ids for manual spot-check.
- **Dry-run backfill:** Command that runs resolver for all individual/IGE projects and logs what _would_ be written, without writing. Run on staging/copy of production.

### 3.4 Metrics to confirm safety

- Before enabling sync: compare resolver output vs `projects` for a sample of projects per type; confirm no unexpected overwrites of non-zero approved values.
- After Phase 1: confirm no new writes to `projects` (DB trigger or audit log review).

---

## 4. Phase 1 – Canonical Budget Resolution (Read-Only)

### 4.1 Introduce ProjectFundFieldsResolver

- **Location:** `app/Services/Budget/ProjectFundFieldsResolver.php` (or `app/Services/ProjectFundFieldsResolver.php`).
- **Interface:**
    - `resolve(Project $project, bool $dryRun = true): array`
    - Returns: `['overall_project_budget' => float, 'amount_forwarded' => float, 'local_contribution' => float, 'amount_sanctioned' => float, 'opening_balance' => float]`.
- **Logic (read-only):**
    - **Institutional types using `project_budgets`** (Development, Livelihood, RST, CIC, CCI, RUT, NEXT_PHASE):
        - overall = `$project->overall_project_budget ?? 0`; if 0 and `$project->relationLoaded('budgets')`, overall = sum of `this_phase` for current phase (from `config('budget.field_mappings')` phase_selection).
        - forwarded = `$project->amount_forwarded ?? 0`, local = `$project->local_contribution ?? 0`.
        - sanctioned = overall - (forwarded + local), opening = sanctioned + forwarded + local.
    - **IIES:** From `ProjectIIESExpenses` (and details): overall = `iies_total_expenses`, local = sum of scholarship + support_other + beneficiary_contribution, sanctioned = `iies_balance_requested`, forwarded = 0, opening = overall (per Basic_Info_Fund_Fields_Mapping_Analysis.md).
    - **IES, ILP, IAH, IGE:** Same pattern from respective tables and mapping doc.
- **Config:** Reuse or extend `config/budget.php` for type-to-mapping (or inline from Basic_Info_Fund_Fields_Mapping_Analysis.md). Ensure every project type in `ProjectType::all()` is handled (fallback: return project’s current values).

### 4.2 Use resolver only for view consistency and logging

- **Basic Info view:** Optional (Phase 1): when `budget.resolver_enabled` is true and project type is IIES/IES/ILP/IAH/IGE, call resolver in controller (or view composer), pass resolved values to view for **display only**; do not write to DB.  
  **Insertion point:** Controller that loads project for show (e.g. `ProjectController::show` or equivalent). Pass e.g. `$resolvedFundFields` to `general_info.blade.php` and use for display when present; else keep using `$project`.
- **Logging discrepancies:** In resolver or in a scheduled command, for each project type IIES/IES/ILP/IAH/IGE, if resolver’s sanctioned ≠ `$project->amount_sanctioned` (or overall ≠ project overall), log project_id, type, resolved vs stored. No write.

### 4.3 No writes to `projects`

- Resolver must not perform any `$project->update()` or `Project::where(...)->update()`.
- All call sites in Phase 1 use `dryRun = true` or equivalent.

### 4.4 Validation of mappings across all project types

- **Checklist:** For each type in `App\Constants\ProjectType::all()`, unit test: given a project (or in-memory model) with type-specific data set, resolver returns expected five values per Basic_Info_Fund_Fields_Mapping_Analysis.md.
- **Edge cases:** Missing type-specific row (e.g. no IIES expense); then resolver should return 0 or project’s current values (document chosen behaviour).

---

## 5. Phase 2 – Controlled Sync to `projects`

### 5.1 Enable resolver-driven sync

- **When:**
    1. **Type-specific budget save:** After IIES, IES, ILP, IAH, IGE budget/expense store or update (successful transaction).
    2. **Pre-approval:** Immediately before reading overall/forwarded/local in CoordinatorController and GeneralController approval flow.
- **Condition:** Only when `budget.sync_to_projects_on_type_save` or `budget.sync_to_projects_before_approval` is true (and resolver enabled).

### 5.2 Which columns are written

- **On type budget save (individual/IGE):**  
  Write to `projects`: `overall_project_budget`, `local_contribution`, `amount_forwarded` (0).  
  **Do not write** `amount_sanctioned` or `opening_balance` here (those are set only at approval).  
  Optional: write `amount_sanctioned` and `opening_balance` from resolver for **pre-approval display consistency** (e.g. Basic Info); policy decision: if “only approval writes sanctioned/opening”, then omit.
- **Pre-approval:**  
  Call resolver; **sync all five** to `projects` so that the very next read in the same request (approval validation and computation) sees correct values. Approval then computes sanctioned/opening again and overwrites (same formula); so effectively we are ensuring overall/forwarded/local are correct; sanctioned/opening are recomputed by approval.

### 5.3 Safeguards against overwriting approved data

- **On type budget save:** If `ProjectStatus::isApproved($project->status)` is true, **do not sync** (or optionally sync only overall and local_contribution for display, and do not change amount_sanctioned/opening_balance). Prefer: do not sync approved projects on type save to avoid any risk.
- **Pre-approval sync:** Run only when status is “forwarded to coordinator” (or equivalent); approval flow then overwrites sanctioned/opening. No overwrite of already-approved project.
- **Reverted projects:** When a project is reverted (e.g. `reverted_by_coordinator`, `reverted_by_provincial`), status is **not** approved → sync **does** run on type budget save. Executor can edit budget sections again; sync updates `projects` correctly. See **Section 10.D (Revert & Re-approval Lifecycle Alignment)** for full flow.

### 5.4 Insertion points (code references)

- **IIES:** `App\Http\Controllers\Projects\IIES\IIESExpensesController::store()` and `update()` – after successful commit, load project, call resolver, sync (if flag on and not approved).
- **IES:** Same pattern in IES expense controller.
- **ILP:** `App\Http\Controllers\Projects\ILP\BudgetController::store()`.
- **IAH:** `App\Http\Controllers\Projects\IAH\IAHBudgetDetailsController::store()` and `update()`.
- **IGE:** `App\Http\Controllers\Projects\IGE\IGEBudgetController::store()`.
- **Approval:** `CoordinatorController::approveProject()` – after loading project and before reading overall/forwarded/local, call resolver and sync (if flag on). Same in `GeneralController` approval branch (coordinator context).

---

## 6. Phase 3 – Approval Workflow Alignment

### 6.1 Approval always reads resolved budget values

- **Implementation:** In CoordinatorController and GeneralController, **before** reading `$project->overall_project_budget` (and forwarded, local), call ProjectFundFieldsResolver and sync to `projects` (Phase 2). Then existing code that reads from `$project` automatically sees resolved values. No need to pass resolver result through a different variable unless we want to avoid writing (then we’d pass resolved to validation/computation). Preferred: sync to `projects` then re-read from `$project` so one code path.
- **Fallback:** If resolver is disabled, keep current behaviour (read from project; fallback overall from budgets sum).

### 6.2 Freeze sanctioned and opening post-approval

- **Already true:** GeneralInfoController does not include amount_sanctioned or opening_balance in update; only approval writes them. So they are already “frozen” in practice.
- **Explicit guard:** In approval logic, after computing and saving sanctioned/opening, document that no other controller may update these two columns for an approved project (optional: DB trigger or observer that rejects updates to amount_sanctioned/opening_balance when status is approved).

### 6.3 Rules for edit after approval

- **General Info:** When `ProjectStatus::isApproved($project->status)` and `budget.restrict_general_info_after_approval` is true: do not allow updating `overall_project_budget`, `amount_forwarded`, `local_contribution` (either remove from form for approved projects or reject in GeneralInfoController::update()).
- **Budget (development):** When approved, BudgetController::update() can either be disabled for budget rows or allowed with a warning (no change to sanctioned/opening unless re-approval flow exists). Recommended: restrict budget row edits when approved (status check in BudgetController::update()).

### 6.4 Amendment vs correction

- **Amendment:** Formal process (e.g. new request, re-approval) to change overall/forwarded/local or sanctioned/opening after approval – out of scope for this plan; document as future work.
- **Correction:** Typo in General Info before approval – allowed. After approval, corrections to non-budget fields only, or require amendment workflow.

### 6.5 Revert & re-approval behaviour

- **When project is reverted** (ProvincialController::revertToExecutor, CoordinatorController::revertToProvincial): status becomes one of `reverted_by_provincial`, `reverted_by_coordinator`, `reverted_to_executor`, etc. These are in `ProjectStatus::getEditableStatuses()`.
- **Executor can edit again:** ProjectPermissionHelper::canEdit requires isEditable (reverted = editable) and isOwnerOrInCharge. Executor (owner/in-charge) can edit project and budget sections.
- **Type budget save:** Controllers (IIES, IES, ILP, IAH, IGE) do not check status; they rely on project edit gate. Phase 2 sync runs when **not** approved → reverted projects get sync on type save.
- **Re-approval:** Provincial forwards to coordinator; status becomes `forwarded_to_coordinator`. Coordinator approves; pre-approval sync runs; sanctioned/opening written. Values frozen again.
- **Full flow:** See **Section 10.D (Revert & Re-approval Lifecycle Alignment)**.

---

## 7. Phase 4 – Reporting & Statements Alignment

### 7.1 Monthly report create/edit behaviour

- **No change to storage:** ReportController continues to take `amount_sanctioned_overview` and `amount_in_hand` from the request (form pre-filled from `$project->amount_sanctioned` on create, from `$report->amount_sanctioned_overview` on edit). Once `projects.amount_sanctioned` is correct (Phases 1–2), create flow is correct.
- **Optional safety net (Phase 4):** In ReportController::create(), after loading project, if `$project->amount_sanctioned` is 0 and project type is individual/IGE, call resolver and sync to `projects`, then re-read `$project` (or use resolved value) for `$amountSanctioned` passed to view. Ensures first report create after deployment gets correct value even if type budget was saved before sync was enabled.

### 7.2 Statement of account consistency

- Statements already use controller-passed `$amountSanctioned` and report stored values; row amounts from BudgetCalculationService. No change needed once project is correct.

### 7.3 Handling legacy reports created with incorrect values

- **Do not auto-overwrite** existing report rows. Legacy reports that have `amount_sanctioned_overview = 0` remain as-is unless corrected in Phase 6 (backfill).
- **Option:** In report **edit** view, if report has amount_sanctioned_overview = 0 and project now has non-zero amount_sanctioned, show a notice: “Project sanctioned amount has been updated; consider updating the report overview if this report should reflect the current project sanctioned amount.” Link or button to copy project value into form (user submits).

### 7.4 Safe correction or annotation strategy

- **Backfill command (Phase 6):** Optional: for reports where project is individual/IGE and report.amount_sanctioned_overview = 0 and project.amount_sanctioned > 0, update report.amount_sanctioned_overview and amount_in_hand from project; log every change.
- **Annotation:** Add optional `budget_corrected_at` or note in report for audit trail when corrected.

---

## 8. Phase 5 – Dashboards & Aggregates

### 8.1 Verification of totals

- **No code change required** if Phases 1–2 are correct: ProvincialController, CoordinatorController, GeneralController, ExecutorController already sum `$project->amount_sanctioned` (or overall) for lists and aggregates. Once `projects` is populated, totals are correct.
- **Verification:** After Phase 2 and 6, run spot checks: sum of amount_sanctioned for approved projects by type/center and compare to expected from type-specific data (resolver output).

### 8.2 Recalculation vs persisted values

- **Use persisted:** Dashboards use `projects.amount_sanctioned` (and overall where used). Do not recalculate from type tables in dashboard code; keep single source (projects).

### 8.3 Cross-role visibility consistency

- Ensure all roles (provincial, coordinator, general, executor) use the same project query and same fields; no role-specific branching that would show different budget totals.

---

## 9. Phase 6 – Backfill & Data Correction

### 9.1 Identification of affected projects

- **Query:** Projects where `project_type` in (IIES, IES, ILP, IAH, IGE) and (`amount_sanctioned` is null or 0 or `overall_project_budget` = 0) and (type-specific table has at least one row with non-zero total/requested).
- **Development types:** Projects where overall_project_budget = 0 but project_budgets sum(this_phase) > 0 (optional backfill overall from sum).

### 9.2 Resolver-driven backfill strategy

- **Command:** `php artisan budget:backfill-fund-fields {--dry-run} {--project-type=} {--limit=}`.  
  For each affected project: call resolver, then update `projects` with resolved overall, forwarded, local. For **approved** projects: optionally update amount_sanctioned and opening_balance from resolver (policy: do only if organisation agrees to “correct” historical sanctioned/opening).
- **Idempotency:** Running the command multiple times with same data should produce same result; resolver output is deterministic.

### 9.3 Audit logs and traceability

- Log: project_id, old overall/forwarded/local/sanctioned/opening, new values, timestamp, run_id (e.g. command invocation id). Store in `storage/logs` or dedicated audit table.

### 9.4 Rollback plan

- **Before backfill:** Backup `projects` table (or full DB).
- **Rollback:** Restore `projects` columns (overall_project_budget, amount_forwarded, local_contribution, amount_sanctioned, opening_balance) from backup for affected project_ids. Document rollback in runbook.

### 9.5 Admin reconciliation vs automated backfill

- **Automated backfill (Phase 6):** Command-line, batch, resolver-driven; suitable for bulk correction of historical data. No human review per project.
- **Admin reconciliation (Phase 6a):** Human-in-the-loop; admin views list, compares stored vs resolved, and explicitly accepts or manually edits before applying. **Never** automatic mutation of approved data.

---

## 10. Phase 6a – Admin Reconciliation & Correction (Governance)

### Verification summary

**Does the Admin Budget Reconciliation UI exist?** **No.** Verified against the codebase:

- **Admin routes** (`routes/web.php`): Only `admin.dashboard` and `admin.logout` exist. No budget reconciliation routes.
- **Admin sidebar** (`resources/views/admin/sidebar.blade.php`): Dashboard, All Activities, Reports (Quarterly/Half-Yearly/Annual), placeholder links. **No "Budget Reconciliation" menu item.**
- **Admin dashboard** (`resources/views/admin/dashboard.blade.php`): Generic template (New Customers, New Orders, Revenue, etc.). No project list, no stored vs resolved comparison.
- **Controllers:** No BudgetReconciliationController or equivalent. No admin endpoint to apply corrections.

**Conclusion:** The Admin Budget Reconciliation UI must be built as part of Phase 6a. The following design is mandatory.

**Dependencies:** Phase 1 (resolver), Phase 2 (sync logic). Admin reconciliation is **not** part of automated backfill; it is a separate, explicit governance flow.

### A. Admin Reconciliation & Review – Purpose and Scope

#### Purpose

- Provide an **admin** user with a controlled way to view approved projects, compare stored budget values in `projects` with resolved (expected) values from type-specific budget tables, and **explicitly** apply corrections when discrepancies exist.
- Ensure no silent auto-fix; every correction is logged (who, when, what changed, why).

#### Scope

- **Approved projects only:** `ProjectStatus::isApproved($project->status)` (e.g. `approved_by_coordinator`, `approved_by_general_as_coordinator`).
- **Read-only by default:** Admin sees comparison; no write until admin explicitly chooses “Accept suggested” or “Manual correction” and submits.
- **Comparison:** For each project, display:
    - **Stored (current):** `projects.overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned`, `opening_balance`.
    - **Resolved (expected):** Output of `ProjectFundFieldsResolver::resolve($project, true)`.
    - **Discrepancy flag:** When stored ≠ resolved (with tolerance for floating-point).

#### Current system state (verified)

| Capability                                                        | Status             | Location / Notes                                                                                             |
| ----------------------------------------------------------------- | ------------------ | ------------------------------------------------------------------------------------------------------------ |
| Admin role                                                        | **Exists**         | `role === 'admin'`; ProjectController show grants admin view of all projects                                 |
| Admin dashboard                                                   | **Exists**         | `AdminController::adminDashboard()`, `admin.dashboard` – generic template, no budget features                |
| Admin sidebar / menu                                              | **Exists**         | `resources/views/admin/sidebar.blade.php` – Dashboard, All Activities, Reports; **no Budget Reconciliation** |
| Admin routes                                                      | **Exists**         | `routes/web.php`: only `admin.dashboard`, `admin.logout` – **no reconciliation routes**                      |
| Admin reconciliation list (approved projects, stored vs resolved) | **Does not exist** | No UI or controller for this                                                                                 |
| Stored vs resolved comparison                                     | **Does not exist** | Resolver (Phase 1) will provide resolved; no comparison view                                                 |
| Accept suggested correction                                       | **Does not exist** | No admin endpoint to apply resolver output to `projects`                                                     |
| Manual correction (admin edits fields)                            | **Does not exist** | No admin endpoint to update fund fields with reason                                                          |
| Budget correction audit log                                       | **Does not exist** | No table or log for admin corrections                                                                        |
| Revert flow (provincial, coordinator)                             | **Exists**         | `ProjectStatusService::revertByProvincial`, `revertByCoordinator`                                            |
| Executor edit after revert                                        | **Exists**         | `ProjectStatus::getEditableStatuses()` includes reverted; `ProjectPermissionHelper::canEdit`                 |

**Conclusion:** The Admin Budget Reconciliation UI **does not exist**. It must be built as part of Phase 6a. The following design is mandatory.

---

### A. Admin Budget Reconciliation UI – Mandatory Design & Flow

#### Entry point

- **Menu:** Admin → Budget Reconciliation
- **Route:** e.g. `GET /admin/budget-reconciliation` (or under existing admin prefix)
- **Access:** Admin-only (`role === 'admin'`). Middleware: `auth`, `role:admin`. Feature flag: `budget.admin_reconciliation_enabled`.
- **Sidebar:** Add link in `resources/views/admin/sidebar.blade.php` under Main or a new "Governance" category; visible only when flag is enabled.

#### Screen 1: Approved Projects List

- **Purpose:** List approved projects with stored vs resolved comparison; highlight discrepancies.
- **Data:** Query projects where `ProjectStatus::isApproved($project->status)`; for each, call `ProjectFundFieldsResolver::resolve($project, true)`; compare stored (`projects`) vs resolved.
- **Columns (minimum):** Project ID, Title, Type, Approval date, Stored sanctioned, Resolved sanctioned, Discrepancy (yes/no), Actions (Reconcile).
- **Filters:**
    - Project type (dropdown)
    - Approval date range
    - "Show only discrepancies" (checkbox) – when checked, show only projects where stored ≠ resolved (with tolerance, e.g. 0.01).
- **Visual:** Row highlighting (e.g. amber) when discrepancy exists.
- **Actions:** "Reconcile" button per row → navigates to Screen 2.

#### Screen 2: Project Budget Reconciliation

- **Purpose:** Per-project side-by-side comparison; admin chooses accept suggested or manual adjustment.
- **Read-only project summary:** Project ID, title, type, status, approval date.
- **Side-by-side comparison table:**

| Field              | Stored (current) | Resolved (expected) | Difference     |
| ------------------ | ---------------- | ------------------- | -------------- |
| Overall budget     | from `projects`  | from resolver       | highlight if ≠ |
| Amount forwarded   | from `projects`  | from resolver       | highlight if ≠ |
| Local contribution | from `projects`  | from resolver       | highlight if ≠ |
| Amount sanctioned  | from `projects`  | from resolver       | highlight if ≠ |
| Opening balance    | from `projects`  | from resolver       | highlight if ≠ |

- **Visual:** Highlight cells where stored ≠ resolved (e.g. background colour).
- **Admin decision:** Admin must explicitly choose one of two paths before any edit or apply.

#### Admin decision flow

Admin must choose **one** of:

1. **Accept system-suggested correction** – apply resolver output to `projects`.
2. **Manually adjust values** – unlock editable fields; admin enters values; backend validates and applies.

No apply without explicit choice and confirmation.

#### Accept suggested correction

- **UI:** Read-only display of suggested (resolved) values; no editable inputs.
- **Mandatory reason:** Text input (e.g. "Budget alignment correction – type-specific data was not synced at approval").
- **Confirmation modal:** "You are about to apply the system-suggested values to this project. This will update the project's budget fields. Reason: [entered reason]. Confirm?"
- **On confirm:** Backend applies resolver output to `projects`; writes to `budget_correction_audit`; redirect with success.
- **Audit:** `action = accept_suggested`, `old_values`, `new_values`, `user_id`, `reason`, `created_at`.

#### Manual adjustment

- **UI:** Editable fields unlocked **only after** admin selects "Manual adjustment."
- **Editable:** `overall_project_budget`, `amount_forwarded`, `local_contribution`. Optional: allow `amount_sanctioned`, `opening_balance` if policy permits; otherwise lock and recompute from overall/forwarded/local.
- **Sanctioned/opening:** Locked by default; if admin edits overall/forwarded/local, show **preview** of computed sanctioned and opening (e.g. "Sanctioned will be: X; Opening will be: Y") – do not allow direct edit unless policy permits.
- **Mandatory reason:** Required for manual correction.
- **Confirmation modal:** "You are about to manually correct this project's budget. Changes: [summary]. Reason: [entered reason]. Confirm?"
- **On confirm:** Backend validates; applies; writes to audit; redirect.
- **Audit:** `action = manual_correction`, `old_values`, `new_values`, `user_id`, `reason` (required), `created_at`.

#### Audit log (admin-viewable)

- **Screen:** Admin → Budget Reconciliation → Correction Log (or tab on Screen 1).
- **Columns:** Who (user name/email), When (timestamp), Project ID, Action (accept_suggested | manual_correction), Old values, New values, Reason.
- **Filter:** By project, by date, by user.
- **Read-only:** No edit or delete of audit entries.

#### UI safeguards (governance-grade)

- **Discourage casual edits:** Prominent warning text: "Budget corrections affect financial records. Only correct when you have verified the discrepancy and have authority to do so."
- **No bulk apply:** No "Apply all" or "Apply to selected" without per-project confirmation. Each project requires individual review and confirm.
- **Back navigation:** Allow return to list without applying; no accidental submit.

---

### B. Admin Action Paths

#### Path 1: Accept suggested correction

1. Admin views reconciliation list; for a project with discrepancy, sees “Suggested values” (resolver output).
2. Admin clicks “Accept suggested” (or equivalent).
3. Backend: validate admin role; load project; call resolver; compare resolver output to current; if different, **apply** resolver values to `projects` (all five fields for overall/forwarded/local/sanctioned/opening, or policy-defined subset).
4. **Log:** `project_id`, `user_id` (admin), `action` = `accept_suggested`, `old_values`, `new_values`, `timestamp`, optional `reason` (e.g. “Budget alignment correction”).
5. **Never** overwrite without explicit admin action; no background cron or auto-apply.

#### Path 2: Manual correction

1. Admin views reconciliation list; for a project, chooses “Manual correction.”
2. Admin sees form with current stored values and resolved values (read-only) for reference; editable fields for the values admin is allowed to change (see Section E).
3. Admin edits selected fields (e.g. overall_project_budget, local_contribution) and submits.
4. Backend: validate admin role; validate input (numeric, non-negative where applicable); apply only the fields admin submitted.
5. **Log:** `project_id`, `user_id`, `action` = `manual_correction`, `old_values`, `new_values` (only changed fields), `timestamp`, `reason` (required: admin must provide reason for manual correction).

#### Validation rules

- **Accept suggested:** Resolver must return valid values; apply only when project is approved.
- **Manual correction:** Server-side validation: overall ≥ 0, forwarded ≥ 0, local ≥ 0; combined contribution ≤ overall; sanctioned = overall − (forwarded + local); opening = sanctioned + forwarded + local. If admin edits overall/forwarded/local, recompute sanctioned and opening, or require admin to provide all five (policy choice: prefer recompute for consistency).

#### Logging and audit trail requirements

- **Table or log channel:** `budget_correction_audit` (or equivalent) with: `id`, `project_id`, `user_id`, `user_role`, `action` (accept_suggested | manual_correction), `old_overall`, `old_forwarded`, `old_local`, `old_sanctioned`, `old_opening`, `new_overall`, `new_forwarded`, `new_local`, `new_sanctioned`, `new_opening`, `reason` (nullable for accept_suggested, required for manual), `ip_address` (optional), `created_at`.
- **Retention:** Per organisational policy; minimum 1 year for financial audit.

### C. Phase Placement & Integration Points

#### Phase placement

- **Phase:** Admin Budget Reconciliation UI is introduced in **Phase 6a**, after Phase 6 (backfill) or in parallel.
- **Dependencies:** Phase 1 (ProjectFundFieldsResolver), Phase 2 (sync logic). Resolver must exist and return correct values before admin can meaningfully compare or accept suggested corrections.
- **Why not part of automated backfill:** Backfill is batch, unattended, and may update many projects without human review. Admin reconciliation requires human review, explicit action per project, and audit trail per correction. Governance and auditability require separation. Backfill is for bulk historical correction; admin UI is for selective, audited correction.
- **Feature flag:** `budget.admin_reconciliation_enabled` (Phase 0). When false, routes return 403; sidebar link hidden or disabled.

#### Integration points

| Integration                                | How                                                                                                                                                                                                                                                                                                                                |
| ------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Resolver**                               | Screen 1: For each approved project, call `ProjectFundFieldsResolver::resolve($project, true)` to get resolved values. Screen 2: Same call for side-by-side comparison. Accept suggested: apply resolver output to `projects`.                                                                                                     |
| **Projects table**                         | Updates write to `projects.overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned`, `opening_balance`. No other tables modified. No change to project status.                                                                                                                                       |
| **Approval authority**                     | Admin correction does **not** change project status. Approval remains the sole authority for the **act** of approving (status transition). Admin correction is a **governance override** for correcting stored values when they are wrong; it does not replace or bypass approval. Coordinator/general approval flow is unchanged. |
| **Existing executor and revert workflows** | Unchanged. Admin reconciliation operates only on **approved** projects. Executor edit, revert, re-approval flows are unaffected.                                                                                                                                                                                                   |

### D. Revert & Re-approval Lifecycle Alignment

#### What happens when an approved project is reverted

- **Provincial revert:** `ProjectStatusService::revertByProvincial()` → status becomes `reverted_by_provincial` or `reverted_to_executor` (or other granular status).
- **Coordinator revert:** `ProjectStatusService::revertByCoordinator()` → status becomes `reverted_by_coordinator` or `reverted_to_provincial`.
- **Code references:** `ProvincialController::revertToExecutor()`, `CoordinatorController::revertToProvincial()`.

#### Budget fields become editable again

- **ProjectStatus::getEditableStatuses()** includes all reverted statuses (`reverted_by_provincial`, `reverted_by_coordinator`, `reverted_to_executor`, etc.).
- **ProjectPermissionHelper::canEdit()** requires `ProjectStatus::isEditable($project->status)` **and** `isOwnerOrInCharge($project, $user)`.
- **Result:** Executor (owner or in-charge) **can** edit the project when reverted. Type-specific budget controllers (IIES, IES, ILP, IAH, IGE) and GeneralInfoController do **not** currently check status; they rely on the project edit gate (ProjectController::edit uses canEdit). So when executor accesses project edit, they can update budget sections.

#### Resolver + sync in reverted state

- **Phase 2 sync on type budget save:** Guard is “do not sync when `ProjectStatus::isApproved`”. When reverted, status is **not** approved → sync **runs** on type budget save. Correct.
- **Phase 2 pre-approval sync:** Runs only when status is `forwarded_to_coordinator`. After revert, project is in reverted status; when provincial forwards again, status becomes `forwarded_to_coordinator` → pre-approval sync runs at next approval. Correct.

#### Projects table must reflect updated values

- After revert, executor edits type-specific budget (e.g. IIES expenses). With Phase 2 sync enabled, resolver runs after save and updates `projects.overall_project_budget`, `local_contribution`, `amount_forwarded` (0). `amount_sanctioned` and `opening_balance` are **not** written by type save (only at approval). So `projects` reflects updated overall/local; sanctioned/opening remain from previous approval until re-approval.

#### Re-approval freezes values again

- When coordinator (or general) approves the reverted-then-forwarded project, pre-approval sync runs (Phase 2), then approval reads from `projects`, computes sanctioned and opening, and writes them. Values are frozen again.

#### Verification checklist (revert flow)

- [ ] Executor can edit project when status is reverted (ProjectPermissionHelper::canEdit, ProjectStatus::isEditable).
- [ ] Type-specific budget save (IIES, IES, ILP, IAH, IGE) does **not** check status; executor reaches it via project edit flow.
- [ ] Phase 2 sync on type save: runs when **not** approved; reverted = not approved → sync runs.
- [ ] Phase 2 pre-approval sync: runs when status = forwarded_to_coordinator; after revert, provincial must forward again before coordinator can approve.
- [ ] Re-approval overwrites sanctioned and opening; no stale values.

### E. Role-Based Access & Safeguards

#### Who can see reconciliation UI

- **Role:** `admin` only. Coordinator, provincial, general, executor, applicant do **not** have access to the reconciliation list or apply-correction flow.
- **Route/middleware:** e.g. `Route::middleware(['auth', 'role:admin'])->group(...)` for reconciliation routes.

#### Who can apply corrections

- **Role:** `admin` only. No delegation to coordinator or provincial for this flow.

#### Which fields are editable by admin

- **Admin may correct (with audit):** `overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned`, `opening_balance`.
- **Policy:** Admin correction is an **override** for governance purposes (e.g. fixing historical misalignment). It is **not** approval; approval authority remains with coordinator/general. Admin correction does **not** change project status.

#### Which fields are NEVER editable outside approval

- **Normal flow:** `amount_sanctioned` and `opening_balance` are “approval authority” fields. Only CoordinatorController and GeneralController (approval flow) write them. Admin reconciliation is an **exception** for explicit, logged correction of approved projects where values are wrong. The safeguard is: admin must explicitly act and provide reason; every change is logged. No other role (executor, applicant, provincial, coordinator, general) may edit these two fields outside approval.

#### Safeguards against accidental overwrite

- **Confirmation step:** Before applying (accept or manual), show summary of changes and require explicit “Confirm” or “Apply” click.
- **No bulk apply without per-project review:** Admin must act per project; no “Apply all” for the entire list without individual confirmation (or disable bulk apply entirely).

---

## 11. Phase 7 – Hardening & Guardrails

### 11.1 Preventing regression

- **Code ownership:** Only resolver (and approval) write the five fields to `projects`; GeneralInfoController and type controllers either restricted (Phase 3) or call resolver for sync (Phase 2). No ad-hoc writes elsewhere.

### 11.2 Server-side validations

- **Report submit:** Optional: when project type is individual/IGE and type-specific tables have non-zero requested amount, if request amount_sanctioned_overview is 0, reject with message or auto-fill from resolver and continue.
- **Approval:** Already validates combined contribution ≤ overall; ensure resolved overall is used so validation is meaningful for individual/IGE.

### 11.3 Status-based write restrictions

- GeneralInfoController: when status is approved, do not update overall/forwarded/local (Phase 3).
- BudgetController (development): when status is approved, reject update to budget rows (or allow with audit and no change to sanctioned/opening).

### 11.4 Tests and monitoring recommendations

- **Unit:** ProjectFundFieldsResolver for each project type (and edge cases).
- **Integration:** Approval flow: with resolver and sync enabled, approve an IIES project and assert projects.amount_sanctioned and opening_balance are non-zero.
- **Monitoring:** Alert when resolver sync fails (exception log); weekly report of “projects with type-specific budget but projects.amount_sanctioned still 0” (should go to zero after Phase 6).

---

## 12. Risk Analysis & Mitigation

| Phase | What can go wrong                                            | Detection                               | Mitigation / rollback                                                                                     |
| ----- | ------------------------------------------------------------ | --------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| 0     | Flag misconfiguration                                        | Logs show resolver called when disabled | Review config; set flags to false.                                                                        |
| 1     | Resolver returns wrong values for a type                     | Unit tests; discrepancy logs            | Fix mapping; do not enable sync until tests pass.                                                         |
| 2     | Sync overwrites approved project                             | Guard: do not sync when status approved | Revert sync for approved; restore from backup if already overwritten.                                     |
| 2     | Sync runs in wrong order (e.g. before type save committed)   | Integration test; transaction order     | Call sync after DB::commit() in type controller.                                                          |
| 3     | General Info restriction blocks legitimate edit              | User report                             | Temporarily disable restrict_general_info_after_approval; add exception for specific role/flow if needed. |
| 4     | Safety net changes report value unexpectedly                 | Compare request vs stored               | Safety net only updates project; report still from form. Optional; can disable.                           |
| 6     | Backfill corrupts data                                       | Dry-run first; compare backup           | Restore from backup; fix command and re-run dry-run.                                                      |
| 6     | Backfill updates approved projects and stakeholder disagrees | Policy decision before run              | Do not update sanctioned/opening for approved in backfill; or get sign-off.                               |
| 6a    | Admin applies wrong correction                               | Audit log; confirmation step            | Revert via admin manual correction (restore previous values); document in audit.                          |
| 6a    | Non-admin gains access to reconciliation UI                  | Role middleware; route protection       | Ensure only `role === 'admin'` can access; audit access attempts.                                         |
| 7     | New validation rejects valid submissions                     | Staging testing                         | Relax validation or add exception; roll out validation in feature-flagged way.                            |

**Emergency rollback (any phase):** Set all `budget.*` flags to false; deploy; no resolver sync or restrictions active. Backfill rollback: restore `projects` from backup. Admin reconciliation rollback: set `admin_reconciliation_enabled` to false; no further corrections.

---

## 13. Developer Checklist

### Phase 0

- [ ] Add `config/budget.php` keys: resolver_enabled, sync_to_projects_on_type_save, sync_to_projects_before_approval, restrict_general_info_after_approval, admin_reconciliation_enabled (all false).
- [ ] Document how to enable flags per environment.
- [ ] Confirm logging channel for budget/resolver (e.g. daily log file or stack).

### Phase 1

- [ ] Create ProjectFundFieldsResolver with resolve(Project, dryRun).
- [ ] Implement mapping for every ProjectType (Development, Livelihood, RST, CIC, CCI, RUT, NEXT_PHASE, IGE, IIES, IES, ILP, IAH) per Basic_Info_Fund_Fields_Mapping_Analysis.md.
- [ ] Add unit tests per type (and missing type-specific data).
- [ ] Optional: use resolver in Basic Info view for display (controller or composer) when resolver_enabled and individual/IGE; no write.
- [ ] Log resolver discrepancies (resolved vs project) for individual/IGE; no write.
- [ ] Review: no code path writes to projects from resolver.

### Phase 2

- [ ] After IIES/IES/ILP/IAH/IGE budget save (success), call resolver and sync overall, local_contribution, amount_forwarded (0); only when sync_to_projects_on_type_save and not approved.
- [ ] Before approval read in CoordinatorController and GeneralController, call resolver and sync all five when sync_to_projects_before_approval; only when status is forwarded_to_coordinator (or equivalent).
- [ ] Log every sync (project_id, trigger, old/new values).
- [ ] Integration test: create IIES project, save expense, assert projects has correct overall/local; approve, assert sanctioned/opening correct.

### Phase 3

- [ ] In GeneralInfoController::update(), when ProjectStatus::isApproved and restrict_general_info_after_approval, exclude overall_project_budget, amount_forwarded, local_contribution from update (or return validation error).
- [ ] In BudgetController::update(), when project status is approved, return 403 or validation error (or document allowed behaviour).
- [ ] Document amendment process (future).

### Phase 4

- [ ] Optional: In ReportController::create(), if project amount_sanctioned 0 and type individual/IGE, call resolver sync then re-read project for $amountSanctioned.
- [ ] Optional: Report edit view notice when report has 0 overview but project has non-zero sanctioned.
- [ ] No change to report store/update logic (request → DB).

### Phase 5

- [ ] Run dashboard aggregate query for a sample of centers; compare to resolver sum for same projects.
- [ ] Confirm no dashboard code path recalculates from type tables.

### Phase 6

- [ ] Implement artisan budget:backfill-fund-fields --dry-run.
- [ ] Run dry-run on staging/copy; review log.
- [ ] Backup projects table; run backfill with limit; verify; full run.
- [ ] Optional: backfill DP_Reports.amount_sanctioned_overview for reports with 0 and project now non-zero; log.
- [ ] Document rollback (restore projects columns from backup).

### Phase 6a (Admin Budget Reconciliation UI)

- [ ] **Entry point:** Add "Budget Reconciliation" to Admin sidebar (`resources/views/admin/sidebar.blade.php`); route `GET /admin/budget-reconciliation`; middleware `auth`, `role:admin`; feature flag `admin_reconciliation_enabled`.
- [ ] **Screen 1 (Approved Projects List):** Query approved projects; call resolver per project; display stored vs resolved; filters (project type, approval date, "Show only discrepancies"); row highlighting for discrepancies; "Reconcile" button per row.
- [ ] **Screen 2 (Project Budget Reconciliation):** Read-only project summary; side-by-side comparison table (Stored vs Resolved for all five fields); visual highlighting of differences; admin must choose Accept suggested OR Manual adjustment.
- [ ] **Accept suggested flow:** Read-only suggested values; mandatory reason input; confirmation modal; on confirm: apply resolver output to `projects`; log to `budget_correction_audit`.
- [ ] **Manual adjustment flow:** Editable fields (overall, forwarded, local) unlocked after choice; sanctioned/opening locked by default (or recomputed preview); mandatory reason; confirmation modal; on confirm: validate, apply, log.
- [ ] **Audit log screen:** Admin-viewable correction log (who, when, what changed, old vs new, reason); filter by project, date, user; read-only.
- [ ] Create `budget_correction_audit` table with required columns.
- [ ] Create BudgetReconciliationController: index, show, acceptSuggested (POST), manualCorrection (POST), correctionLog (GET).
- [ ] Add governance-grade safeguards: prominent warning text; no bulk apply; back navigation without apply.
- [ ] Verify: no automatic mutation; every correction is explicit and logged; approval authority preserved.

### Phase 7

- [ ] Add server-side validation (report or approval) if agreed.
- [ ] Add/update unit and integration tests for resolver and approval.
- [ ] Add monitoring/alert for resolver exceptions and “zero sanctioned despite type budget” count.

---

**Document version:** 1.2 – Phase-wise budget alignment implementation plan. Extended with Phase 6a (Admin Budget Reconciliation UI – mandatory design & flow), verified current system state (UI does not exist), Phase Placement & Integration Points, Revert & Re-approval Lifecycle, and Role-Based Access & Safeguards. Safe for incremental rollout; reviewable by engineers; auditable for financial and release governance.
