# Financial Integrity Forensic Audit

**Milestone:** Financial Integrity Forensic Audit  
**Scope:** Individual Project Types (IES, IIES, ILP, IAH) + Phase-based (DP, CIC, etc.)  
**Mode:** READ-ONLY / FORENSIC ANALYSIS / ZERO ASSUMPTIONS  
**Date:** 2026-02-16  

---

## SECTION 1 — Log Analysis

### Invariant Message

```
Financial invariant violation: non-approved project must have amount_sanctioned == 0
```

### All Occurrences Documented

| Project ID | Type | Status | amount_sanctioned | Trigger (store/update/show) |
|------------|------|--------|-------------------|-----------------------------|
| IOES-0024 | IES | non-approved | 2758.03 | show |
| IOES-0024 | IES | non-approved | 2758.03 | (repeated show) |
| DP-0036 | Phase-based (DP) | draft | 1494400.0 | update |
| DP-0036 | Phase-based (DP) | draft | 1494400.0 | (duplicate in same request) |
| ILA-0002 | ILP | draft | 175000.0 | store |
| ILA-0002 | ILP | draft | 175000.0 | show |
| IAH-0012 | IAH | draft | 362000.0 | store |
| IAH-0012 | IAH | draft | 337000.0 | update |
| IAH-0012 | IAH | draft | 337000.0 | show |
| DP-0036 | Phase-based (DP) | draft | 1303000.0 | update |
| DP-0041 | Phase-based (DP) | draft | 1063000.0 | show |
| DP-0041 | Phase-based (DP) | draft | 1063000.0 | update |
| IIES-0019 | IIES | non-approved | 52000.0 | (batch show/list) |
| IIES-0020 | IIES | non-approved | 52000.0 | (batch show/list) |
| IIES-0021 | IIES | non-approved | 52000.0 | (batch show/list) |
| IIES-0022 | IIES | non-approved | 52000.0 | (batch show/list) |
| IIES-0023 | IIES | non-approved | 45000.0 | (batch show/list) |
| IIES-0024 | IIES | non-approved | 45000.0 | (batch show/list) |
| IIES-0025 | IIES | non-approved | 45000.0 | (batch show/list) |
| IIES-0026 | IIES | non-approved | 113500.0 | (batch show/list) |
| IIES-0027 | IIES | non-approved | 96500.0 | (batch show/list) |
| IIES-0028 | IIES | non-approved | 96500.0 | (batch show/list) |
| IIES-0029 | IIES | non-approved | 66500.0 | (batch show/list) |
| IIES-0030 | IIES | non-approved | 96500.0 | (batch show/list) |

**Log sources:** `storage/logs/laravel.log`, `storage/logs/production1602.log`

**Trigger context:** Invariant is logged when `ProjectFinancialResolver::resolve()` runs. That occurs during: (a) `ProjectController@show` / edit when preparing `resolvedFundFields`; (b) Child budget controller store/update via `BudgetSyncService::syncFromTypeSave()` → `ProjectFundFieldsResolver` → `ProjectFinancialResolver`; (c) Phase-based `BudgetController@update` via same sync chain; (d) Provincial project list when resolving financials per project.

---

## SECTION 2 — Who Writes amount_sanctioned?

### Direct Mutation Sites (projects.amount_sanctioned)

| File | Line | Context |
|------|------|---------|
| `app/Http/Controllers/GeneralController.php` | 2647 | `$project->amount_sanctioned = $amountSanctioned;` — Post-approval persistence |
| `app/Http/Controllers/CoordinatorController.php` | 1134 | `$project->amount_sanctioned = $amountSanctioned;` — Post-approval persistence |
| `app/Services/ProjectStatusService.php` | 244 | `$project->amount_sanctioned = 0;` — On revert (applyFinancialResetOnRevert) |
| `app/Services/Budget/AdminCorrectionService.php` | 169, 180 | Via `applyValuesToProject()` — admin correction (approved only) |
| `app/Services/Budget/BudgetSyncService.php` | 110–113 | `syncBeforeApproval()` — writes PRE_APPROVAL_FIELDS (includes amount_sanctioned) when status = forwarded_to_coordinator |

### Indirect / Resolver-Only (No DB Write)

- **BudgetSyncService::syncFromTypeSave()** — Does **not** write `amount_sanctioned`. Only writes `TYPE_SAVE_FIELDS` = `overall_project_budget`, `amount_forwarded`, `local_contribution`. See `app/Services/Budget/BudgetSyncService.php` lines 29–34, 66–74.
- **Child budget controllers** (IES, IIES, ILP, IAH) call `syncFromTypeSave` only. They do **not** directly update `projects.amount_sanctioned`.

### Fill/Update Usage

- `app/Models/OldProjects/Project.php` line 287: `amount_sanctioned` in `$fillable` (allows mass assignment).
- No `fill([...'amount_sanctioned'...])` or `update([...'amount_sanctioned'...])` found for project table in child budget controllers. `AdminCorrectionService` uses `$project->update([...])` with amount_sanctioned; `BudgetSyncService::syncBeforeApproval` uses `$project->update($updatePayload)` with resolved values including amount_sanctioned.

---

## SECTION 3 — Child Budget Controllers Mutation Check

### IESExpensesController

**File:** `app/Http/Controllers/Projects/IES/IESExpensesController.php`

- **Updates projects.amount_sanctioned?** No. Only mutates `project_ies_expenses`, `project_ies_expense_details`.
- **Updates projects.opening_balance?** No.
- **Updates projects.overall_project_budget?** Indirectly via `BudgetSyncService::syncFromTypeSave()` — only when feature flags allow; sync writes `overall_project_budget`, `amount_forwarded`, `local_contribution`, **not** `amount_sanctioned`.
- **Calls resolver then persist?** Calls `BudgetSyncService::syncFromTypeSave()` at line 103. That resolver call triggers the invariant warning when resolved `amount_sanctioned` is non-zero. Sync does **not** persist `amount_sanctioned`.

### ILPBudgetsController (ILP BudgetController)

**File:** `app/Http/Controllers/Projects/ILP/BudgetController.php`

- **Updates projects.amount_sanctioned?** No.
- **Updates projects.opening_balance?** No.
- **Updates projects.overall_project_budget?** Indirectly via `syncFromTypeSave()` (line 80) — same as IES.
- **Calls resolver then persist?** Same pattern. Invariant fires when resolver runs inside sync; sync does not persist `amount_sanctioned`.

### IAHBudgetDetailsController

**File:** `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php`

- **Updates projects.amount_sanctioned?** No.
- **Updates projects.opening_balance?** No.
- **Updates projects.overall_project_budget?** Indirectly via `syncFromTypeSave()` (line 93).
- **Calls resolver then persist?** Same. Invariant logged during store/update; no `amount_sanctioned` persistence.

### IIESExpensesController

**File:** `app/Http/Controllers/Projects/IIES/IIESExpensesController.php`

- **Updates projects.amount_sanctioned?** No.
- **Updates projects.opening_balance?** No.
- **Updates projects.overall_project_budget?** Indirectly via `syncFromTypeSave()` (line 96).
- **Calls resolver then persist?** Same. No direct persistence of `amount_sanctioned`.

### Phase-Based BudgetController (DP, CIC, etc.)

**File:** `app/Http/Controllers/Projects/BudgetController.php`

- **Updates projects.amount_sanctioned?** No. Sync at line 151 uses `syncFromTypeSave`, which excludes `amount_sanctioned`.
- **Updates projects.opening_balance?** No.
- **Updates projects.overall_project_budget?** Yes, via `syncFromTypeSave()`.

**Conclusion:** Child budget controllers do **not** directly mutate `projects.amount_sanctioned`. The invariant warning is triggered by the resolver’s **output** during sync/show, not by writing to the DB.

---

## SECTION 4 — Resolver Output Inspection

### ProjectFinancialResolver

**File:** `app/Domain/Budget/ProjectFinancialResolver.php`

- Invariant check: lines 102–108 (non-approved: `amount_sanctioned` must be 0).
- Invariant is applied to `$normalized` (resolver output), not DB.

### DirectMappedIndividualBudgetStrategy

**File:** `app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php`

For **non-approved** projects:

| Project Type | amount_sanctioned source | Fallback | Reads project->amount_sanctioned? |
|--------------|--------------------------|----------|-----------------------------------|
| IIES | `iies_balance_requested` (line 69) | fallbackFromProject (line 63) → `project->amount_sanctioned` | Only when relation missing |
| IES | `balance_requested` (line 93) | fallbackFromProject | Only when relation missing |
| ILP | `amount_requested` from first row (line 115) | fallbackFromProject | Only when relation missing |
| IAH | `amount_requested` from first row (line 138) | fallbackFromProject | Only when relation missing |

- **Fallback:** Lines 181–189 — returns `project->amount_sanctioned` when relation is missing or empty.
- **Main path:** Returns `amount_requested` / `balance_requested` from child tables. That is **requested** amount, not sanctioned. For non-approved, business rule expects `amount_sanctioned == 0`, but resolver returns requested amount for display. Result: invariant violation.

### PhaseBasedBudgetStrategy

**File:** `app/Domain/Budget/Strategies/PhaseBasedBudgetStrategy.php`

- **Approved:** Lines 50–51 — `amount_sanctioned` from `project->amount_sanctioned` (DB).
- **Non-approved:** Lines 53–55 — `amount_sanctioned = calculateAmountSanctioned(overall, forwarded + local)` = `overall - (forwarded + local)`. That is derived requested amount, typically non-zero for draft/forwarded projects.
- **Does it fallback to requested?** Yes — it computes requested as `overall - (forwarded + local)` and returns it as `amount_sanctioned`.
- **Does it read project->amount_sanctioned from DB?** Only when `BudgetSyncGuard::isApproved($project)` is true.

**Conclusion:** For non-approved projects, both strategies return a non-zero value for `amount_sanctioned` (requested / derived requested). The invariant expects 0; the design uses `amount_sanctioned` in the output to represent requested amount for display. Conflict between invariant and resolver semantics.

---

## SECTION 5 — Invariant Enforcement Strength

### Location

**File:** `app/Domain/Budget/ProjectFinancialResolver.php` lines 78–110

```php
private function assertFinancialInvariants(Project $project, array $data): void
{
    // ...
    } else {
        if (abs($sanctioned) > $tolerance) {
            Log::warning('Financial invariant violation: non-approved project must have amount_sanctioned == 0', [
                'project_id' => $projectId,
                'amount_sanctioned' => $sanctioned,
                'invariant' => 'amount_sanctioned == 0',
            ]);
        }
    }
}
```

### Enforcement Type

| Aspect | Finding |
|--------|---------|
| Logging only? | **Yes** — `Log::warning()` only |
| Throwing exception? | **No** |
| Blocking save? | **No** — runs after strategy resolve, before return; does not prevent return or persistence |
| Passive? | **Yes** — non-blocking, observational |

**Classification:** Soft invariant. Log-only. No exception, no rollback, no save prevention.

---

## SECTION 6 — Data Drift Risk

| Question | Answer |
|----------|--------|
| **1. Are draft projects persisting non-zero sanctioned?** | **Depends.** `syncFromTypeSave` does **not** write `amount_sanctioned`. `syncBeforeApproval` **does** (when status = `forwarded_to_coordinator` and flags enabled). If `syncBeforeApproval` has run for a forwarded project, `projects.amount_sanctioned` can be non-zero for non-approved. |
| **2. Is that value used in dashboards?** | **Yes.** Provincial project list uses `$resolvedFinancials[$project_id]['amount_sanctioned']` (ProvincialController 499, 508; ProjectList.blade.php 124, 235). Resolver returns requested for non-approved, so dashboards show requested as sanctioned. |
| **3. Is it used in resolver?** | **Yes.** Fallback path uses `project->amount_sanctioned`. Main path uses child-table requested amounts. |
| **4. Is it overwritten on approval?** | **Yes.** CoordinatorController (1134) and GeneralController (2647) set `$project->amount_sanctioned = $amountSanctioned` from resolver output and save. Approval flow recomputes and overwrites. |
| **5. Is it cleared on revert?** | **Yes.** `ProjectStatusService::applyFinancialResetOnRevert()` (line 244) sets `amount_sanctioned = 0`. |

---

## SECTION 7 — Approval Interaction

When coordinator approves:

| Step | Behavior |
|------|----------|
| Before approval | `BudgetSyncService::syncBeforeApproval($project)` (CoordinatorController 1074, GeneralController 2598) — only when status = `forwarded_to_coordinator` and feature flags on |
| Resolver call | `ProjectFinancialResolver::resolve($project)` — at approval time project is approved, so PhaseBased reads `project->amount_sanctioned`; DirectMapped still uses child requested (project is approved only after status transition) |
| Overwrite | Controller sets `$project->amount_sanctioned = $amountSanctioned` from resolver output and saves (CoordinatorController 1134, GeneralController 2647) |
| Reuse vs overwrite | **Overwrites.** Uses resolver output, not prior value. Approval always recomputes and persists. |

---

## SECTION 8 — Root Cause Classification

**Primary:** **B) Resolver fallback incorrectly maps requested → sanctioned**

- Resolver treats `amount_requested` / `balance_requested` as `amount_sanctioned` for non-approved projects for display.
- Invariant requires `amount_sanctioned == 0` for non-approved.
- Design intent: show requested in sanctioned column for drafts. Invariant assumes sanctioned = 0 until approval.

**Contributing:** **C) Invariant not enforced strongly enough**

- Invariant is log-only; no blocking or correction.

**Secondary:** **E) Combination**

- A) Budget controllers do **not** mutate `amount_sanctioned`; they trigger the resolver via sync.
- D) Approval logic overwrites `amount_sanctioned`; revert clears it. Those paths are correct.
- Data drift risk: `syncBeforeApproval` can write non-zero sanctioned for forwarded projects; that is by design but conflicts with invariant for non-approved status.

---

## SECTION 9 — Deployment Safety Assessment

| Risk | Assessment |
|------|------------|
| Draft projects show inflated sanctioned totals? | **Yes.** Resolver returns requested as sanctioned for drafts. Provincial list and show views display this. |
| Dashboards miscalculate? | **Yes.** `grandTotals['amount_sanctioned']` sums resolver output; non-approved projects contribute requested amounts, inflating totals. |
| Financial reporting drift? | **Medium.** Approved projects use correct DB values. Non-approved projects use requested in display. Aggregates mix approved (real sanctioned) and non-approved (requested). |

**Overall risk classification:** **MEDIUM**

- No hard failure; invariant is soft.
- Display semantics conflate requested and sanctioned for non-approved.
- Aggregations (provincial list, reports) can overstate sanctioned if non-approved projects are included.

---

## Appendix: Key File References

| Component | File:Line |
|-----------|-----------|
| Invariant logging | `app/Domain/Budget/ProjectFinancialResolver.php` 104–107 |
| DirectMapped strategy (amount_sanctioned) | `app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php` 69, 93, 115, 138, 185 |
| PhaseBased strategy (amount_sanctioned) | `app/Domain/Budget/Strategies/PhaseBasedBudgetStrategy.php` 51, 54 |
| BudgetSyncService TYPE_SAVE_FIELDS | `app/Services/Budget/BudgetSyncService.php` 29–34 |
| syncBeforeApproval PRE_APPROVAL_FIELDS | `app/Services/Budget/BudgetSyncService.php` 39–45, 110–113 |
| Revert reset | `app/Services/ProjectStatusService.php` 244 |
| Approval persistence | `app/Http/Controllers/CoordinatorController.php` 1134; `app/Http/Controllers/GeneralController.php` 2647 |

---

**Financial Forensic Audit Complete — No Code Changes Made**
