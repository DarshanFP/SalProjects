# Phase 2 — Pre-Implementation Clarification Audit

**Date:** 2026-03-04  
**Purpose:** Verify concerns raised before executing Phase 2 of the Financial Data Stabilization plan.  
**Reference Documents:** Financial_Data_Stabilization_Implementation_Plan.md, Phase1_Invariant_Rule_Correction_Audit.md, Pre_Implementation_System_Audit_Report.md  
**Method:** Database queries, code inspection, resolver verification. **No application code or database records were modified.**

---

## 1. Project Type Identification Architecture

### 1.1 Data Model

| Column | Exists | Description |
|--------|--------|-------------|
| `project_type` | Yes | String; determines resolver strategy and project_id prefix |
| `project_category` | No | Not present in `projects` table |

### 1.2 Project ID Prefix Mapping

Project ID prefix is derived from `project_type` at creation (see `Project::generateProjectId()`):

| project_type | Prefix |
|--------------|--------|
| Development Projects | DP |
| CHILD CARE INSTITUTION | CIC |
| Individual - Initial - Educational support | IIES |
| Individual - Ongoing Educational support | IOES |
| Individual - Livelihood Application | ILA |
| Individual - Access to Health | IAH |
| Institutional Ongoing Group Educational proposal | IOGEP |
| Livelihood Development Projects | LDP |
| PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER | CIC |
| NEXT PHASE - DEVELOPMENT PROPOSAL | NPD |
| Residential Skill Training Proposal 2 | RSTP2 |
| Rural-Urban-Tribal | RUT |
| (other) | GEN |

### 1.3 ProjectFinancialResolver Strategy Selection

| Strategy | project_type values |
|----------|---------------------|
| PhaseBasedBudgetStrategy | Development Projects, NEXT PHASE - DEVELOPMENT PROPOSAL, Livelihood Development Projects, Residential Skill Training Proposal 2, PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER, CHILD CARE INSTITUTION, Rural-Urban-Tribal |
| DirectMappedIndividualBudgetStrategy | Individual - Initial - Educational support, Individual - Ongoing Educational support, Individual - Livelihood Application, Individual - Access to Health, Institutional Ongoing Group Educational proposal |
| (fallback) | PhaseBasedBudgetStrategy for unrecognized types |

### 1.4 ProjectType Constants

`ProjectType::getIndividualTypes()` returns: Individual - Initial - Educational support, Individual - Ongoing Educational support, Individual - Livelihood Application, Individual - Access to Health.

---

## 2. DP-0041 Classification Analysis

### 2.1 Record Verification

| Field | Value |
|-------|-------|
| project_id | DP-0041 |
| project_type | **Development Projects** |
| status | approved_by_coordinator |
| amount_sanctioned | NULL |
| opening_balance | 630,000.00 |
| amount_forwarded | 0.00 |
| local_contribution | 630,000.00 |
| overall_project_budget | 1,681,000.00 |

### 2.2 Classification Conclusion

**DP-0041 is a Development Project (phase-based), NOT an Individual-type project.**

- `project_type = 'Development Projects'` → PhaseBasedBudgetStrategy
- Project ID prefix "DP" matches Development Projects
- Uses `project_budgets` (phase-based), not IIES/IES/ILP/IAH/IGE tables

### 2.3 Why Phase 1 Audit Classified It as Individual

The Phase 1 audit and Implementation Plan stated "Individual types (DP-0041, IIES-0060)". This was an **incorrect inference** based on:

1. **amount_sanctioned = 0 / NULL** — Same pattern as some Individual projects (100% local/beneficiary-funded)
2. **Project ID prefix "DP"** — Ambiguous; DP can mean Development Projects (which DP-0041 is) or was assumed to be a different convention

The actual `project_type` column is the source of truth. DP-0041 uses the PhaseBasedBudgetStrategy and is a Development Project with 100% local contribution (amount_sanctioned = 0, opening_balance = local_contribution = 630,000).

### 2.4 Financial Semantics

For DP-0041: `opening_balance (630,000) = amount_sanctioned (0) + amount_forwarded (0) + local_contribution (630,000)`. The canonical rule holds; the project is 100% locally funded. The Phase 1 invariant validation fails only because it requires `amount_sanctioned > 0`, which does not apply when the entire budget is from local contribution.

---

## 3. IIES-0060 Classification Analysis

### 3.1 Record Verification

| Field | Value |
|-------|-------|
| project_id | IIES-0060 |
| project_type | **Individual - Initial - Educational support** |
| status | approved_by_coordinator |
| amount_sanctioned | NULL |
| opening_balance | 16,000.00 |
| amount_forwarded | 0.00 |
| local_contribution | 16,000.00 |
| overall_project_budget | 76,500.00 |

### 3.2 Classification Conclusion

**IIES-0060 is correctly an Individual-type project.**

- `project_type = 'Individual - Initial - Educational support'` → DirectMappedIndividualBudgetStrategy
- Project ID prefix "IIES" matches Individual - Initial - Educational support
- Uses `project_iies_expenses` (IIES module)

### 3.3 Resolver Logic (IIES)

- **overall_project_budget:** from `iies_total_expenses`
- **local_contribution:** from `iies_expected_scholarship_govt` + `iies_support_other_sources` + `iies_beneficiary_contribution`
- **amount_sanctioned:** from `iies_balance_requested` (pre-approval) or DB `amount_sanctioned` (post-approval)
- **opening_balance:** For approved projects, taken from DB `opening_balance`

### 3.4 Expense Relation

IIES projects use `project_iies_expenses` (relation: `iiesExpenses`). Financial values are derived from this table rather than `project_budgets`.

### 3.5 Financial Semantics

IIES-0060 has 100% local/beneficiary funding. The canonical rule holds: `opening_balance (16,000) = 0 + 0 + 16,000`. Individual projects can have `amount_sanctioned = 0` when fully funded by local/beneficiary contributions.

---

## 4. Approved Project Count Verification

### 4.1 Count Comparison

| Source | Total | Date/Context |
|--------|-------|--------------|
| Pre-Implementation Audit baseline | 44 | 2026-03-04 (audit report) |
| Phase 1 Resolver Simulation | 45 | 2026-03-04 (during implementation) |
| Current Clarification Audit | 46 | 2026-03-04 |

### 4.2 Discrepancy Analysis

**Why 45 instead of 44 in Phase 1?**

At least one project was approved after the Pre-Implementation audit. The Phase 1 simulation ran when 45 approved projects existed.

**Current state (46):** Two projects are in the approved set that were not in the Pre-Implementation list:

| project_id | status | updated_at |
|------------|--------|------------|
| DP-0078 | approved_by_coordinator | 2026-03-04 19:49:56 |
| DP-0080 | approved_by_coordinator | 2026-03-04 19:54:20 |

### 4.3 Conclusion

- **Baseline:** 44 (Pre-Implementation)
- **New approvals since baseline:** DP-0078, DP-0080 (both 2026-03-04)
- **Current total:** 46 approved projects
- Phase 1’s 45 reflects an intermediate state (one of these two approved)

---

## 5. Individual Project Financial Calculation Logic

### 5.1 DirectMappedIndividualBudgetStrategy

Individual types use type-specific expense tables:

| Type | Relation | Budget Source |
|------|----------|---------------|
| IIES | iiesExpenses | project_iies_expenses |
| IES | iesExpenses | project_ies_expenses |
| ILP | ilpBudget | project_ilp_budgets |
| IAH | iahBudgetDetails | project_iah_budget_details |
| IGE | igeBudget | project_ige_budgets |

### 5.2 Computation Pattern

- **overall_project_budget:** Sum from type-specific expense rows
- **local_contribution:** From scholarship, support, beneficiary/family contribution columns
- **amount_sanctioned:** From `amount_requested`/`balance_requested` (pre-approval) or DB (approved)
- **opening_balance:** For approved: DB `opening_balance`; for non-approved: `forwarded + local`

### 5.3 Canonical Rule Applicability

**The canonical rule applies to Individual projects:**

`opening_balance = amount_sanctioned + amount_forwarded + local_contribution`

- Individual projects can have `amount_sanctioned = 0` when fully funded by local/beneficiary sources.
- The Phase 1 invariant requires `amount_sanctioned > 0`, which blocks approval for 100% local-funded projects. That is a policy choice, not a structural incompatibility with the canonical rule.
- For IIES-0060: `16,000 = 0 + 0 + 16,000` — rule holds.

### 5.4 Difference from Phase-Based Projects

| Aspect | Phase-Based | Individual (Direct-Mapped) |
|--------|-------------|----------------------------|
| Budget source | project_budgets (phases) | Type-specific tables (IIES, IES, etc.) |
| amount_forwarded | Often used | Usually 0 |
| amount_sanctioned = 0 | Rare (typically new sanction) | Common (100% local) |
| Resolver | PhaseBasedBudgetStrategy | DirectMappedIndividualBudgetStrategy |

---

## 6. Phase 2 Repair Eligibility Assessment

### 6.1 DP-0041

- **Actual type:** Development Projects (phase-based)
- **Current state:** opening_balance = 630,000, amount_sanctioned = NULL, local = 630,000
- **Canonical rule:** Satisfied (630,000 = 0 + 0 + 630,000)
- **Phase 2 eligibility:** The plan excluded DP-0041 as "Individual type; manual review." That exclusion was based on a wrong classification. As a Development Project with 100% local funding, DP-0041:
  - Does not need opening_balance repair (already correct)
  - Could receive `amount_sanctioned = 0` if we choose to persist it (currently NULL)
  - **Recommendation:** Treat as **in-scope for Phase 2** only if we add a rule for "amount_sanctioned = 0, 100% local" Development Projects; otherwise keep as manual review.

### 6.2 IIES-0060

- **Actual type:** Individual - Initial - Educational support
- **Current state:** opening_balance = 16,000, amount_sanctioned = NULL, local = 16,000
- **Canonical rule:** Satisfied (16,000 = 0 + 0 + 16,000)
- **Phase 2 eligibility:** Plan excludes as "Individual type; manual review." Data is already correct. No repair needed.

### 6.3 Option A — Include Individual Types in Phase 2

- **Pros:** Single repair phase; IIES-0060 needs no change.
- **Cons:** Risk of applying phase-based repair rules to Individual projects with different semantics.
- **Conclusion:** IIES-0060 requires no repair. Including it would be a no-op.

### 6.4 Option B — Separate Phase 2.A for Individual Projects

- **Pros:** Explicit handling of different semantics; clear separation.
- **Cons:** IIES-0060’s data is already correct; Phase 2.A would have nothing to repair for it.
- **Conclusion:** Phase 2.A is only useful if we discover other Individual projects with actual repairs.

### 6.5 New Projects (DP-0078, DP-0080)

| project_id | project_type | sanctioned | forwarded | local | opening_balance | expected | Match |
|------------|--------------|------------|-----------|-------|-----------------|----------|-------|
| DP-0078 | Development Projects | 100,000 | 100,000 | 0 | 200,000 | 200,000 | yes |
| DP-0080 | Development Projects | 100,000 | 100,000 | 0 | 200,000 | 200,000 | yes |

Both projects satisfy the canonical rule. No repair needed.

---

## 7. Recommendation

### 7.1 Summary of Findings

1. **DP-0041** is a Development Project (phase-based), not Individual. Previous "Individual" classification was wrong.
2. **IIES-0060** is correctly Individual. Its data already satisfies the canonical rule.
3. **Approved count:** 44 (baseline) → 45 (Phase 1) → 46 (current). New projects: DP-0078, DP-0080.
4. The canonical rule applies to both phase-based and Individual projects.
5. Individual projects can have `amount_sanctioned = 0` when 100% locally funded.

### 7.2 Final Recommendation

**PROCEED_WITH_PHASE_2**

**Rationale:**

- Phase 2 auto-repair cases (A, B, C, D) target phase-based projects with specific data issues. DP-0041 and IIES-0060 do not fit those cases.
- DP-0041: Development Project with correct opening_balance (630,000). No repair under existing cases. Can remain as manual review.
- IIES-0060: Individual project with correct data. No repair needed.
- New projects DP-0078, DP-0080: Include in pre-repair invariant check; add to repair scope only if they fail.
- No need for a separate Phase 2.A for Individual projects at this time; the single Individual project in scope (IIES-0060) already has correct data.

**Suggested Phase 2 Adjustments:**

1. Update documentation: DP-0041 is Development Projects, not Individual.
2. Before repair, run invariant check on DP-0078 and DP-0080; add to repair scope if violations exist.
3. Keep DP-0041 and IIES-0060 as exclusions (manual review / no repair) as in the plan.
