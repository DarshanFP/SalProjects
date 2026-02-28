# System Alignment & Feasibility Audit

**Scope:** Executor dashboard, ProjectQueryService, ProjectStatus, financial resolver, budget sync, approval flow. Read-only validation; no code changes.

---

## 1. Model-Service Alignment Table

| Service | Model Field Used | Guaranteed Persisted? | Risk Level | Notes |
|---------|------------------|------------------------|------------|--------|
| **ProjectQueryService** | Project: status, user_id, in_charge, province_id, project_id | Yes (query only; no write) | Low | Read-only; relies on DB state. |
| **ProjectQueryService** | User: id, province_id | Yes | Low | Auth user. |
| **ProjectStatusService** | Project: status | Yes (service sets and saves) | Low | approve() only sets status; does not set opening_balance or amount_sanctioned. |
| **ProjectFinancialResolver** | Project: status, amount_sanctioned, opening_balance, amount_forwarded, local_contribution, project_type, current_phase, overall_project_budget | amount_sanctioned/opening_balance not guaranteed | **Medium** | For approved: returns project->opening_balance ?? 0. If column is null/0, returns 0; no fallback. Assumption: approved projects have opening_balance set. |
| **ProjectFinancialResolver** (via strategies) | Project: budgets (relation), amount_forwarded, local_contribution, current_phase, overall_project_budget | Yes for scalar fields used in non-approved branch | Low | Strategies use loadMissing('budgets'); PhaseBased uses project->opening_balance for approved. |
| **BudgetSyncService** | Project: all fund fields (read + write), status | opening_balance only when sync runs and guard allows | **Medium** | syncBeforeApproval writes opening_balance from resolver (non-approved branch = forwarded+local). If guard false (config or status != forwarded_to_coordinator), no write. |
| **ProjectFundFieldsResolver** | Project: (delegates to ProjectFinancialResolver) | N/A | Low | Thin wrapper; no direct field assumption. |
| **PhaseBasedBudgetStrategy** | Project: amount_forwarded, local_contribution, current_phase, budgets, overall_project_budget, amount_sanctioned, opening_balance | opening_balance for approved = DB value | **Medium** | Approved branch: opening = (float)($project->opening_balance ?? 0). Same assumption as resolver. |
| **DirectMappedIndividualBudgetStrategy** | Project: project_type, amount_sanctioned, opening_balance, amount_forwarded, local_contribution + type relations | opening_balance for approved = DB value | **Medium** | Same pattern: approved uses project->opening_balance ?? 0. |
| **BudgetSyncGuard** | Project: status (via isApproved, isForwardedToCoordinator) | Yes | Low | Read-only; uses ProjectStatus constants. |

**Summary:** The only high-impact assumption is that **approved projects have `opening_balance` (and optionally `amount_sanctioned`) already persisted**. When they are not (e.g. sync skipped, legacy data, or approval path that doesn’t write them), the resolver returns 0 and downstream aggregation (e.g. dashboard Total Budget) shows 0. No code path was found that bypasses persistence for status (status is always set and saved by ProjectStatusService); financial persistence is conditional on sync + coordinator approval flow.

---

## 2. Status Consistency Audit

**ProjectStatus constants:** Single source of truth in `App\Constants\ProjectStatus`. All project status strings match the constants (draft, approved_by_coordinator, etc.).

**Project approval checks:**
- ProjectQueryService: `getApprovedProjectsForUser` and `getApprovedOwnedProjectsForUser` use the same three statuses inline: `APPROVED_BY_COORDINATOR`, `APPROVED_BY_GENERAL_AS_COORDINATOR`, `APPROVED_BY_GENERAL_AS_PROVINCIAL`. They do **not** use `ProjectStatus::APPROVED_STATUSES` (duplication of the same set).
- Project::isApproved(): uses `ProjectStatus::isApproved($this->status)` which uses `APPROVED_STATUSES`.
- Project::scopeApproved(): uses `ProjectStatus::APPROVED_STATUSES`.
- ProjectStatusService: uses `ProjectStatus::*` constants for transitions.
- BudgetSyncGuard::isApproved(): delegates to `$project->isApproved()`.
- Controllers (Executor, Provincial, Coordinator, General): project approval filters use `ProjectStatus::APPROVED_STATUSES` or the same three constants.

**Editable / reverted:**
- ProjectQueryService: getEditableStatuses uses `ProjectStatus::getEditableStatuses()`; reverted lists use explicit constants matching `ProjectStatus::isReverted()`.
- No project status string literals found for project status checks; only constants or ProjectStatus methods.

**Inconsistencies:**
1. **Duplication:** ProjectQueryService approved lists are inline arrays duplicating `APPROVED_STATUSES`. Semantically identical; if APPROVED_STATUSES ever changes, owned/merged approved methods must be updated manually.
2. **Report status (different domain):** ReportComparisonController uses hardcoded `'approved_by_coordinator'` for QuarterlyReport/HalfYearlyReport/AnnualReport. Those are report models, not Project; report status constants live on each report model (DPReport::APPROVED_STATUSES etc.). So project status consistency is intact; report status has its own constants.
3. **ExecutorController report filters:** Uses `$status === 'draft'` for DPReport filter (string literal). DPReport::STATUS_DRAFT exists; minor inconsistency for report status only.

**Conclusion:** Project status enum and usage are consistent. No mismatch between enum and DB for project status. Duplication of approved set in ProjectQueryService is a maintainability risk, not a semantic mismatch.

---

## 3. Financial Flow Validation

**Lifecycle trace:**

1. **Project creation:** Project created with status draft (or similar). Fund fields may be null/0. No resolver call required at create.
2. **Budget sync (type save):** BudgetSyncService::syncFromTypeSave — only overall_project_budget, amount_forwarded, local_contribution. Does **not** write amount_sanctioned or opening_balance. Guard: project not approved.
3. **Pre-approval sync:** BudgetSyncService::syncBeforeApproval (CoordinatorController line 1074). Guard: config flags + status = forwarded_to_coordinator. Resolver (via ProjectFundFieldsResolver → ProjectFinancialResolver) runs with project **not** approved; opening_balance = forwarded + local. Sync writes that to DB. So **first write of opening_balance** is here (when guard passes), and it is forwarded+local, not amount_sanctioned.
4. **Approval:** ProjectStatusService::approve() (line 1091): only sets project->status and save(). Does **not** set opening_balance or amount_sanctioned.
5. **Opening balance persistence (coordinator):** After approve(), CoordinatorController (1106–1135) calls ProjectFinancialResolver->resolve($project). Project is now approved; applyCanonicalSeparation returns opening_balance = (float)($project->opening_balance ?? 0). That value (from step 3 if sync ran, else 0) is assigned and saved. So **second write** of opening_balance is here; it can overwrite with 0 if sync did not run or project had no prior value.

**Answers:**

- **At what exact line is opening_balance first set?**  
  In the coordinator approval flow, the **first** persistence of opening_balance is in BudgetSyncService::syncBeforeApproval → project->update($updatePayload) (line 131), when guard allows and project is not yet approved (resolver returns forwarded+local). The **second** persistence is CoordinatorController line 1135: `$project->opening_balance = $openingBalance; $project->save();` (resolver return for approved = current project->opening_balance ?? 0).

- **Can approval occur without opening_balance being written?**  
  Yes. If syncBeforeApproval is skipped (guard false: config off or status != forwarded_to_coordinator), and the project had no opening_balance set earlier, then after approve() the resolver returns 0 and the controller saves 0. So approval can complete with opening_balance never meaningfully written (or written as 0).

- **Can resolver overwrite valid data with zero?**  
  The resolver does not write; the controller does. The controller overwrites project->amount_sanctioned and project->opening_balance with resolver output. For approved, resolver output is project->opening_balance ?? 0. So if the in-memory project has opening_balance 0 or null (e.g. refresh after approve but DB had 0), the controller persists 0. So effectively yes: the approval flow can overwrite with zero when the DB value is null/0.

- **Is resolver idempotent?**  
  Yes for reads: same project (same attributes/relations) yields same result. No side effects; no DB write. Idempotent as a pure function of project state.

- **Does resolver depend on state at runtime or DB?**  
  It depends on the **in-memory** project (and loaded relations). That state ultimately comes from DB when the project is loaded. So effectively DB + runtime load state; no extra queries inside resolver for fund fields (strategies may loadMissing('budgets')).

**Classification:** **FINANCIAL_FLOW_PARTIALLY_SAFE**

- Safe: Status transitions and guard usage are consistent; resolver is read-only and idempotent; single place (coordinator approval) persists resolver output for amount_sanctioned/opening_balance.
- Risk: Resolver assumes approved projects have opening_balance set; persistence can write 0 when that assumption fails; sync is config- and status-dependent so approval can complete without a valid opening_balance.

---

## 4. Query Layer Validation

**ProjectQueryService:**

- **Scope methods:** getProjectsForUserQuery (merged), getOwnedProjectsQuery, getInChargeProjectsQuery, getTrashedProjectsQuery, getProjectsForUsersQuery. All use Project::query() or Project::onlyTrashed(); province filter when user->province_id !== null; no other global scope.
- **Owned vs in-charge vs combined:** Owned: user_id = user->id. In-charge: in_charge = user->id AND user_id != user->id. Combined: (user_id = user->id OR in_charge = user->id). No overlap; definitions are consistent.
- **Status filtering:** getApproved*, getEditable*, getReverted* use the same status sets as the merged counterparts (approved: same three constants; editable: getEditableStatuses(); reverted: same eight constants). Duplication but no semantic drift.
- **Province:** All user-scoped methods apply the same rule: if user->province_id !== null, where('province_id', user->province_id). Duplicated in getOwnedProjectsQuery and getInChargeProjectsQuery (not factored); behaviour matches getProjectsForUserQuery.
- **Soft delete:** getTrashedProjectsQuery uses onlyTrashed(); other methods do not exclude trashed (default Eloquent behaviour). So list methods can include soft-deleted projects unless a global scope excludes them; trashed is a separate query. No inconsistency with other scopes.

**Duplication:** Province filter logic is duplicated in getOwnedProjectsQuery and getInChargeProjectsQuery (same block as getProjectsForUserQuery). Approved status list is duplicated (inline array vs APPROVED_STATUSES). Reverted list is duplicated between getRevertedProjectsForUser and getRevertedOwnedProjectsForUser.

**Bypass / drift:** No method bypasses the base query in a way that changes scope semantics. getProjectsForUserByStatus builds on getProjectsForUserQuery; owned variants build on getOwnedProjectsQuery/getInChargeProjectsQuery. applySearchFilter is applied on top of a passed query; no hidden scope.

**Classification:** **QUERY_LAYER_PARTIAL_RISK**

- Clean: Scope definitions are clear; owned/in-charge/merged are consistent; status and province rules are applied consistently.
- Partial risk: Duplication of province and status lists; if one place is updated and another forgotten, drift could occur. No fragmentation (single service, single responsibility).

---

## 5. Dashboard Contract Validation

**ExecutorController::executorDashboard():**

- Passes to view: ownedProjects, inChargeProjects, ownedCount, inChargeCount, enhancedOwnedProjects, enhancedInChargeProjects, budgetSummaries, projectTypes, actionItems, reportStatusSummary, upcomingDeadlines, chartData, reportChartData, quickStats, recentActivities, projectHealthSummary, projectsRequiringAttention, reportsRequiringAttention, showType.
- Budget summary: getApprovedOwnedProjectsForUser($user, ['reports' => ..., 'reports.accountDetails', 'budgets']) → calculateBudgetSummariesFromProjects. So dashboard uses **owned scope** for responsibility metrics (budget, stats, action items, charts, etc.). projectTypes still from getProjectsForUserQuery (merged) for filter dropdown.

**Resolver input vs expectation:**

- Input: Collection of approved owned projects with reports, reports.accountDetails, budgets eager-loaded.
- Resolver (in calculateBudgetSummariesFromProjects): For each project, resolve($project). For **approved** branch, resolver uses only project->opening_balance (and amount_sanctioned); it does not use reports or budgets for opening_balance. So eager load is sufficient for expense aggregation in the same method but **not** required for the resolver’s opening_balance. Resolver expectation is: project with status approved and, for correct totals, opening_balance already set in DB. When opening_balance is 0/null, resolver returns 0; contract is satisfied but business result is wrong.

**Widgets:**

- index.blade.php and widgets use ownedProjects, enhancedOwnedProjects (project-health, project-status-visualization). No remaining reference to $projects or $enhancedProjects for dashboard KPIs. projectTypes (merged) is only for filter options.
- No JS found that assumes merged scope or removed variables for project lists.

**Circular dependency:** None identified. Controller → ProjectQueryService, controller → resolver, controller → calculateBudgetSummariesFromProjects; no service depending on ExecutorController or view.

**Classification:** **DASHBOARD_CONTRACT_SAFE**

- Dashboard consistently uses owned scope for KPIs and project lists; resolver input matches what the resolver reads (project attributes + optional relations); no widget assumes merged scope for metrics; no circular dependency. The only issue is resolver/DB assumption (opening_balance), not the contract between dashboard and services.

---

## 6. Cross-Service Coupling Analysis

**Explicit dependencies (constructor or app()):**

- BudgetSyncService → ProjectFundFieldsResolver.
- ProjectFundFieldsResolver → ProjectFinancialResolver (app()).
- ProjectFinancialResolver → DerivedCalculationService; strategies → DerivedCalculationService, BudgetSyncGuard.
- CoordinatorController → BudgetSyncService, ProjectStatusService, ProjectFinancialResolver.
- ExecutorController → ProjectQueryService, ProjectFinancialResolver (via calculateBudgetSummariesFromProjects), no direct BudgetSyncService/ProjectStatusService for dashboard.

**Implicit / logical coupling:**

- ProjectStatusService::approve() does not write financials; CoordinatorController (or GeneralController) does, after calling the resolver. So **approval semantics** (status) and **financial persistence** are split: status in ProjectStatusService, financials in controller + resolver. A different approval entry point that did not call the resolver and save would leave amount_sanctioned/opening_balance unchanged (or 0).
- BudgetSyncService and CoordinatorController both use the resolver (BudgetSyncService via ProjectFundFieldsResolver). Sync runs before approve; controller runs after. So **two call sites** persist or prepare financials; order is fixed in one flow (sync → approve → resolve → save) but the assumption that “approved implies opening_balance set” depends on sync having run or another path having set it.
- ProjectQueryService has no dependency on ProjectFinancialResolver or BudgetSyncService; it only reads Project and User. Clean separation for query layer.

**Controller logic duplicating service logic:**

- ExecutorController status filters (approved/needs_work/all) duplicate the same status arrays used in ProjectQueryService (e.g. approvedStatuses inline). Not full duplication of query logic; duplication of constant set.
- calculateBudgetSummariesFromProjects contains aggregation and expense logic (approved vs unapproved by report status); that logic is controller-private, not in a shared service. Resolver only provides opening_balance per project; aggregation is in controller.

**Financial logic split:**

- Resolver (Domain) + strategies: compute per-project fund fields.
- BudgetSyncService: writes to project from resolver (pre-approval).
- CoordinatorController/GeneralController: read resolver, validate, write amount_sanctioned and opening_balance.
- ExecutorController: read resolver for aggregation only (no write). So financial **read** is in resolver; **write** is in sync service and controllers. Split is clear but approval flow must remember to call resolver and save.

**Classification:** **MEDIUM COUPLING**

- Clear boundaries for query (ProjectQueryService) and status (ProjectStatusService). Resolver is single place for financial computation.
- Coupling: Approval flow depends on correct sequence (sync → approve → resolve → save). Financial persistence is in two places (sync + controller). Controller holds aggregation logic that could live in a small service. No circular dependency; coupling is orchestration and split persistence.

---

## 7. Overall System Integrity Classification

**STRUCTURALLY_SAFE_WITH_RISKS**

**Rationale:**

- **Architecturally coherent:** Status is centralized in ProjectStatus; project scopes (owned/in-charge/merged) are consistent; resolver is the single financial read path; dashboard uses owned scope for KPIs; query layer is one service with clear methods.
- **Risks:** (1) Resolver assumes approved projects have opening_balance set; when they do not, dashboard and any other consumer get 0. (2) Financial persistence is split (sync + controller) and order-dependent. (3) Duplication of province and status lists in ProjectQueryService and controller creates maintainability and possible drift risk.
- **Not fragmented:** No competing definitions of “approved” or “owned”; no duplicate resolver for the same concept; no conflicting persistence paths for the same field (sync writes pre-approval, controller writes post-approval for the same fields, in a defined order).

**Recommendation:** Address the opening_balance assumption (data + optional resolver fallback) and document the approval flow (sync → approve → resolve → save) so future changes do not break the assumption. Phase 4 (or next steps) can proceed with the understanding that financial display depends on opening_balance being persisted at approval; a one-time data fix or resolver fallback would reduce risk.
