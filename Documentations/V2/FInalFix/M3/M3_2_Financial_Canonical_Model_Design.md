# M3.2 — Financial Canonical Model & Parity Design

**Milestone:** M3 — Resolver Parity & Financial Stability  
**Step:** M3.2 — Financial Canonical Model & Parity Design  
**Mode:** STRICTLY DESIGN ONLY (No Code Changes)  
**Date:** 2025-02-15  
**Depends on:** M3.1 Resolver & Financial Computation Audit

---

## SECTION 1 — Canonical Financial Definitions

### 1) Budget (overall_project_budget)

| Attribute | Definition |
|-----------|------------|
| **Canonical value** | `overall_project_budget` |
| **Definition** | Structural total cost of the project — the sum of all budget line items or direct-mapped totals for the current phase/scope |
| **Source** | Phase-based types: `Σ(this_phase)` of budget rows filtered by `current_phase`; Direct-mapped types (IIES/IES/ILP/IAH/IGE): from type-specific tables |
| **Mathematical formula** | Phase-based: `Budget = Σ(row.rate_quantity × row.rate_multiplier × row.rate_duration)` over current phase; Direct-mapped: per-type aggregation (e.g. IES: `total_expenses`, IGE: `Σ(total_amount)`) |
| **Persisted** | Yes, in `projects.overall_project_budget` (may be stale if budget rows exist) |

### 2) Sanctioned (amount_sanctioned)

| Attribute | Definition |
|-----------|------------|
| **Canonical value** | `amount_sanctioned` |
| **Definition** | Approved central authority amount — the portion of budget allocated by the sanctioning body (coordinator/general) |
| **Exists only after approval** | True; null or 0 before approval |
| **Persisted in DB** | Yes, at approval time in `projects.amount_sanctioned` |
| **Mathematical formula** | Phase-based: `Sanctioned = max(0, Budget − (Forwarded + Local))`; Direct-mapped: type-specific (e.g. IES: `balance_requested`, IGE: `Σ(amount_requested)`) |

### 3) Opening Balance

| Attribute | Definition |
|-----------|------------|
| **Canonical value** | `opening_balance` |
| **Definition** | Total available funds for project execution |
| **Before approval** | `Opening Balance = Budget` (available funds = structural total until sanctioned) |
| **After approval** | `Opening Balance = Sanctioned + Forwarded + Local` |
| **Persisted** | Yes, at approval time in `projects.opening_balance` |
| **Mathematical formula** | `Opening Balance = Amount_Sanctioned + Amount_Forwarded + Local_Contribution` |

### 4) Forwarded (amount_forwarded)

| Attribute | Definition |
|-----------|------------|
| **Canonical value** | `amount_forwarded` |
| **Definition** | Existing funds carried forward from prior periods or predecessor projects |
| **Source** | DB `projects.amount_forwarded` |
| **Mathematical formula** | Read from DB; no computation |

### 5) Local Contribution

| Attribute | Definition |
|-----------|------------|
| **Canonical value** | `local_contribution` |
| **Definition** | Contributions from local sources (scholarship, family, beneficiary, etc.) |
| **Source** | DB `projects.local_contribution`; for direct-mapped types, derived from type-specific fields (e.g. IES: `expected_scholarship_govt + support_other_sources + beneficiary_contribution`) |
| **Mathematical formula** | Phase-based: read from DB; Direct-mapped: per-type sum of contribution fields |

### 6) Expenses (total_expenses)

| Attribute | Definition |
|-----------|------------|
| **Canonical value** | `total_expenses` (aggregated, not stored on project) |
| **Definition** | Sum of all reported and approved expenses against the project |
| **Source** | `Σ(report.accountDetails.total_expenses)` over all reports; may be split into approved vs unapproved |
| **Mathematical formula** | `Total_Expenses = Σ(report.accountDetails.total_expenses)` |

### 7) Remaining Balance

| Attribute | Definition |
|-----------|------------|
| **Canonical value** | `remaining_balance` (computed, not stored) |
| **Definition** | Funds remaining after expenses |
| **Mathematical formula** | `Remaining_Balance = Opening_Balance − Total_Expenses` |

### 8) Utilization %

| Attribute | Definition |
|-----------|------------|
| **Canonical value** | `utilization` or `percentage_used` (computed, not stored) |
| **Definition** | Percentage of opening balance consumed by expenses |
| **Mathematical formula** | `Utilization = (Total_Expenses / Opening_Balance) × 100` when `Opening_Balance > 0`; else `0` |

---

## SECTION 2 — Stage-Based Authority Model

### STAGE A — Draft / Pre-Approval

| Aspect | Behavior |
|--------|----------|
| **Resolver** | Authoritative for all fund fields |
| **DB sanctioned/opening** | Ignored; may be null or stale |
| **Opening Balance** | `Opening Balance = Budget` (conceptually; resolver computes from sanctioned + forwarded + local where sanctioned = budget − (forwarded + local)) |
| **Source of truth** | Resolver (computed dynamically) |

### STAGE B — Approved

| Aspect | Behavior |
|--------|----------|
| **Resolver** | Computes values; on approval, coordinator/general persists `amount_sanctioned` and `opening_balance` |
| **DB** | Becomes authoritative for `amount_sanctioned` and `opening_balance` after persist |
| **Resolver post-approval** | Must read DB values for sanctioned and opening_balance; must NOT recompute them |
| **Source of truth** | DB for sanctioned and opening_balance; resolver still computes budget, forwarded, local from source data |

### STAGE C — Reverted After Approval

| Aspect | Behavior |
|--------|----------|
| **Option 1: Keep persisted sanctioned/opening** | DB values remain; executor may edit budget; on next approval, new values are persisted. **Risk:** Stale displayed values if executor changes budget before re-submit |
| **Option 2: Recompute dynamically** | On revert, clear or ignore DB sanctioned/opening; resolver always computes. **Benefit:** Display always reflects current budget state |

**Design decision (documented):**

**Recommended: Option 1 (Keep persisted).**

Rationale:
- Preserves audit trail: what was sanctioned at approval remains recorded
- Revert is a workflow step, not a budget reset; executor must re-submit to change budget
- Simplifies implementation: no need to clear/backfill on revert
- Downside: If executor edits budget after revert, DB sanctioned/opening will mismatch until next approval. Mitigation: Resolver should be used for display; when status is reverted, resolver can optionally recompute (treat as pre-approval) so display stays correct. This hybrid is covered in Section 5.

**Alternative (for consideration in implementation):** On revert, treat project as pre-approval for display purposes — resolver computes all, DB sanctioned/opening shown only when status is approved.

---

## SECTION 3 — Aggregation Rule

### Strict Rule

**When aggregating across projects (e.g. dashboard totals, center budgets, province totals):**

```
ALWAYS use Opening Balance
```

**Never mix in the same aggregation:**
- `amount_sanctioned`
- `overall_project_budget`
- `opening_balance`

**Rationale:** `opening_balance` represents total available funds. `amount_sanctioned` is a subset (excludes forwarded and local). `overall_project_budget` is structural cost and may differ from available funds (e.g. when forwarded/local exist). Using `opening_balance` ensures consistent “total funds” semantics across all dashboards.

### Places Requiring Alignment (from M3.1 Audit)

| File | Method / Location | Current Behavior | Required Change |
|------|-------------------|------------------|-----------------|
| `ProvincialController.php` | `calculateCenterPerformance` | `$approvedProjects->sum('amount_sanctioned')` | Use `opening_balance` (via resolver or DB when approved) |
| `CoordinatorController.php` | Fallback in aggregation | `$p->amount_sanctioned ?? $p->overall_project_budget ?? 0` | Use `opening_balance` (resolver or DB) |
| `CoordinatorController.php` | Aggregation methods | Already uses `resolvedFinancials['opening_balance']` | ✅ Aligned |
| `GeneralController.php` | Aggregation methods | Uses `resolvedFinancials['opening_balance']` | ✅ Aligned |
| `ProvincialController.php` | `calculateBudgetSummaries`, `calculateEnhancedBudgetData` | Uses resolver `opening_balance` | ✅ Aligned |
| `ExecutorController.php` | Dashboard | Uses resolver `opening_balance` | ✅ Aligned |
| `AdminReadOnlyController.php` | - | Uses resolver | ✅ Aligned |

---

## SECTION 4 — Reporting & Export Parity Rules

### Rules

1. **Exports must use canonical fields**
   - Use resolver output (or DB when project is approved and resolver is configured to read DB) for `overall_project_budget`, `amount_sanctioned`, `opening_balance`, `amount_forwarded`, `local_contribution`
   - Do not bypass resolver for project-level financials

2. **PDF must not recompute formulas independently**
   - Do not use inline formulas such as `max(0, overall - (forwarded + local))` for amount_sanctioned
   - Use resolver output or DB value passed from controller

3. **Views must not derive sanctioned inline**
   - Do not compute amount_sanctioned in Blade
   - Receive values from controller/service (resolver or validated budget data)

4. **All dashboard totals must align with Opening Balance**
   - Any widget/chart showing “total budget” or “total funds” must use `opening_balance` aggregation

### Acceptable vs Forbidden Duplication

| Type | Acceptable? | Example |
|------|-------------|---------|
| **JS form parity** | ✅ Acceptable | `budget-calculations.js` mirrors DerivedCalculationService for real-time form validation and UX |
| **Business totals in view** | ❌ Forbidden | Views must not compute `amount_sanctioned`, `opening_balance`, or aggregation totals |
| **Display-only row totals** | ⚠️ Acceptable with caveat | `budgetsForShow->sum('this_phase')` for table footer is display-only; must match resolver scope (same phase filter) |
| **Inline sanctioned formula in PDF/view** | ❌ Forbidden | pdf.blade.php fallback formula must be removed; use controller-provided value |

---

## SECTION 5 — Resolver Authority Policy

### Hybrid Model

| Stage | Resolver Behavior | Persistence |
|-------|-------------------|-------------|
| **Before approval** | Resolver computes all fields | None |
| **On approval** | Resolver computes; coordinator/general persist `amount_sanctioned` and `opening_balance` | Yes |
| **After approval** | DB values for sanctioned and opening_balance are authoritative | Resolver reads DB for these two fields |
| **After revert** | Design choice: (A) Keep DB; (B) Treat as pre-approval and recompute. Recommended: (A) for DB; optionally (B) for display by having resolver recompute when status is reverted |

### Required Resolver Behavior

1. **Detect status**  
   Use `BudgetSyncGuard::isApproved($project)` (or equivalent) to determine if project is approved.

2. **Decide compute vs read**
   - If approved: read `amount_sanctioned` and `opening_balance` from DB
   - If not approved: compute from budget rows / type-specific tables

3. **Invariants enforced by resolver**
   - All returned values ≥ 0
   - `opening_balance = amount_sanctioned + amount_forwarded + local_contribution` (when computed)
   - `amount_sanctioned = max(0, overall_project_budget − (amount_forwarded + local_contribution))` (when computed for phase-based)

### Required Invariants

- `opening_balance ≥ amount_sanctioned` (when forwarded + local ≥ 0)
- `amount_sanctioned ≤ overall_project_budget`
- `remaining_balance = opening_balance − total_expenses`
- `utilization = (total_expenses / opening_balance) × 100` when `opening_balance > 0`
- No dashboard may sum `amount_sanctioned` when reporting total funds; use `opening_balance`

---

## SECTION 6 — Invariant Rules (Non-Negotiable)

| Invariant | Formula / Rule |
|-----------|----------------|
| **I1** | `opening_balance = amount_sanctioned + amount_forwarded + local_contribution` |
| **I2** | `opening_balance ≥ amount_sanctioned` (given non-negative forwarded and local) |
| **I3** | `amount_sanctioned ≤ overall_project_budget` |
| **I4** | `remaining_balance = opening_balance − total_expenses` |
| **I5** | `utilization = (total_expenses / opening_balance) × 100` when `opening_balance > 0`; else `0` |
| **I6** | No dashboard or report may sum `amount_sanctioned` when reporting total available funds; ALWAYS use `opening_balance` |
| **I7** | `amount_sanctioned ≥ 0`, `opening_balance ≥ 0`, `overall_project_budget ≥ 0` |
| **I8** | Aggregation across projects: single field only — `opening_balance` |

---

## SECTION 7 — Identified Inconsistencies (From M3.1)

| Inconsistency | Location | Severity | Description |
|---------------|----------|----------|-------------|
| ProvincialController calculateCenterPerformance uses amount_sanctioned | `ProvincialController.php` → `calculateCenterPerformance` | **Critical** | Violates I6; center budget total understates available funds; inconsistent with other provincial aggregates |
| ExportController Key Information bypasses resolver | `ExportController.php` → Key Information section | **High** | Uses `$project->overall_project_budget`, `$project->amount_sanctioned`, `$project->opening_balance` directly; for phase-based projects with budget rows, DB may not match resolver |
| pdf.blade.php inline sanctioned formula | `resources/views/projects/Oldprojects/pdf.blade.php` | **High** | `$project->amount_sanctioned ?? max(0, overall - (forwarded + local))` — forbidden duplication; can diverge from resolver |
| Fallback logic in CoordinatorController | `CoordinatorController.php` aggregation fallbacks | **Medium** | `$p->amount_sanctioned ?? $p->overall_project_budget ?? 0` — wrong field for “total funds”; should use opening_balance |
| Stale values on revert | ProjectStatusService (no change on revert) | **Medium** | DB sanctioned/opening remain after revert; if executor edits budget, display can show stale values unless resolver recomputes |

---

## SECTION 8 — Implementation Strategy (Preview Only)

**DO NOT IMPLEMENT.** Outline only.

| Step | Description |
|------|-------------|
| **Step 1** | Align aggregation to Opening Balance: change ProvincialController `calculateCenterPerformance` to use `opening_balance` (resolver or DB) instead of `amount_sanctioned` |
| **Step 2** | Remove sanctioned-based dashboard sums: audit all `sum('amount_sanctioned')` and replace with `opening_balance` aggregation |
| **Step 3** | Remove inline formula in PDF: pass amount_sanctioned from controller (resolver output) to pdf view; remove fallback formula |
| **Step 4** | Ensure resolver reads DB after approval: verify PhaseBasedBudgetStrategy and DirectMappedIndividualBudgetStrategy read `amount_sanctioned` and `opening_balance` from project when approved |
| **Step 5** | Add parity tests: tests that resolver output matches DB post-approval; tests that all aggregation paths use opening_balance; tests that export/PDF use canonical fields |

---

## SECTION 9 — Risk Assessment

| Risk Area | Assessment |
|-----------|------------|
| **Financial reporting risk** | High — Provincial center totals understate funds; export/PDF may show wrong values. Mitigation: Align aggregation and exports to canonical model. |
| **Approval consistency risk** | Low — Approval flow correctly uses resolver and persists. Risk is in display/aggregation, not approval logic. |
| **Backward compatibility risk** | Medium — Changing aggregation from amount_sanctioned to opening_balance will change displayed totals (increase). Stakeholders must be informed. Historical reports may differ from new behavior. |
| **Regression risk** | Medium — Multiple call sites; tests required. BudgetCalculationService (reports) is separate path; ensure no collateral impact. |

**Overall risk:** **HIGH but controllable.**

- Critical and high-severity issues are localized and well-identified
- Implementation strategy is incremental
- Parity tests can lock in correct behavior
- No change to approval persistence logic reduces risk of approval regressions

---

## SECTION X — Domain Model Refinement (Future Hardening)

### 1) Current Implementation

The current implementation centralizes approval statuses in `ProjectStatus` via:

- `ProjectStatus::APPROVED_STATUSES` constant
- `ProjectStatus::isApproved(string $status)` static method
- `Project::scopeApproved()` and `Project::scopeNotApproved()` query scopes

This provides a single source of truth for "approved" semantics in financial aggregation.

### 2) Future Refinement

A future architectural hardening would move approval semantics into the `Project` model via an instance method:

```php
public function isApproved(): bool
{
    return ProjectStatus::isApproved($this->status ?? '');
}
```

This ensures:

- **Status meaning remains inside domain model** — The Project model encapsulates what "approved" means for a project.
- **Controllers do not depend on status constants directly** — Controllers call `$project->isApproved()` instead of `ProjectStatus::isApproved($project->status)`.
- **Reduced long-term drift risk** — If approval semantics evolve (e.g. additional statuses or rules), the change is localized to the model.

### 3) Clarification

- This is **architectural hardening**, not a bug fix or correctness requirement.
- **Not required for M3 correctness** — M3 financial parity is achieved with the current centralized status detection in `ProjectStatus`.
- **Can be implemented in a future cleanup wave** — Low priority; suitable for refactoring when touching project-related code.

---

**End of M3.2 Design**
