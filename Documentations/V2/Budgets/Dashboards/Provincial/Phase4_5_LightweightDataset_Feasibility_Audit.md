# Phase 4.5 — Lightweight Dataset Projection Feasibility Audit

**Date:** 2026-03-05  
**Phase:** Phase 4.5 — Lightweight Dataset Projection  
**Reference:** Provincial_Dashboard_FY_Architecture_Implementation_Plan.md  

---

## Executive Summary

Lightweight dataset projection is **feasible with significant constraints**. The resolver's DirectMappedIndividualBudgetStrategy requires type-specific relations (iiesExpenses, iesExpenses, ilpBudget, iahBudgetDetails, igeBudget) that are not currently eager-loaded; PhaseBasedBudgetStrategy requires the budgets relation. Widgets require user (center, name), reports, and reports.accountDetails for expense aggregation. A **partial projection** (fewer project columns, keep essential relations) can reduce memory; full projection (relations replaced by joins) would require resolver and widget refactors. **Recommendation:** Implement project-column projection first; retain user, reports.accountDetails, budgets. Accept N+1 for DirectMappedIndividual type-specific relations or add conditional eager load.

---

## Step 1 — Current Dataset Analysis

### DatasetCacheService Query

**Source:** `DatasetCacheService::getProvincialDataset()`

**Relations loaded:**
- `user`
- `reports.accountDetails`
- `budgets`

**Project fields:** Full Eloquent model (SELECT * from projects) — ~50+ columns.

**Hydration footprint:**
- Project: full model
- User: full model per project
- Reports: full model per report
- AccountDetails: full model per account detail
- Budgets: full model per budget (project_budgets table)

---

## Step 2 — Widget Field Usage

### Widget → Fields Required

| Widget | Project Attributes | User Attributes | Report Attributes | Budget |
|--------|-------------------|-----------------|-------------------|--------|
| calculateTeamPerformanceMetrics | project_id, status | — | report->status (isApproved), accountDetails->total_expenses | — |
| prepareChartDataForTeamPerformance | project_id, status, project_type | center | — | — |
| calculateCenterPerformance | project_id, user_id, in_charge, status | center | report->status (isApproved), accountDetails->total_expenses | — |
| calculateEnhancedBudgetData | project_id, user_id, project_type, project_title, status | center, name | report->status (isApproved), accountDetails->total_expenses | — |
| prepareCenterComparisonData | (delegates to calculateCenterPerformance) | — | — | — |

### Consolidated Field List

**Project:** project_id, user_id, in_charge, status, project_type, project_title

**User (relation):** center, name

**Reports:** Iteration; each report needs status (for isApproved) and accountDetails->sum('total_expenses')

**Budgets:** Used by resolver (PhaseBasedBudgetStrategy), not directly by widgets

---

## Step 3 — Resolver Dependency Audit

### ProjectFinancialResolver

**Project attributes used:**
- `project_id`
- `project_type` (strategy selection)
- `status` (isApproved)
- `amount_forwarded`, `local_contribution`, `amount_sanctioned`, `opening_balance`
- `current_phase` (PhaseBasedBudgetStrategy)
- `overall_project_budget`

### PhaseBasedBudgetStrategy

| Requirement | Type |
|-------------|------|
| amount_forwarded | Project attribute |
| local_contribution | Project attribute |
| current_phase | Project attribute |
| overall_project_budget | Project attribute |
| amount_sanctioned, opening_balance | Project attribute (approved) |
| budgets | **Relation** (loadMissing) |
| budgets->phase, budgets->this_phase | From budgets relation |

### DirectMappedIndividualBudgetStrategy

| Requirement | Type |
|-------------|------|
| project_type | Project attribute |
| amount_sanctioned, amount_forwarded, local_contribution, opening_balance | Project attribute |
| iiesExpenses, iesExpenses, ilpBudget, iahBudgetDetails, igeBudget | **Type-specific relations** (loadMissing) |

**Finding:** DirectMappedIndividualBudgetStrategy calls `$project->loadMissing($this->getRelationsForType($projectType))` — these relations are **not** in the current dataset eager load. They cause N+1 when present. PhaseBasedBudgetStrategy uses `loadMissing('budgets')` — budgets **is** eager-loaded in DatasetCacheService.

---

## Step 4 — Relation Usage Audit

### user

| Usage | Required? | Replaceable by join? |
|-------|-----------|----------------------|
| user->center | Yes (grouping, display) | Yes — join users, select center |
| user->name | Yes (top projects, team member) | Yes — join users, select name |

### reports

| Usage | Required? | Replaceable? |
|-------|-----------|--------------|
| Iterate reports, filter by status, sum accountDetails | Yes | Difficult — need per-report status and accountDetails aggregate |

### reports.accountDetails

| Usage | Required? | Replaceable? |
|-------|-----------|--------------|
| sum('total_expenses') for approved reports | Yes | Could use raw SQL aggregate per project |

### budgets

| Usage | Required? | Replaceable? |
|-------|-----------|--------------|
| PhaseBasedBudgetStrategy (phase, this_phase) | Yes for phase-based types | Not trivial — strategy iterates phaseBudgets |

**Conclusion:** user can be replaced by joined scalar fields. reports + accountDetails are needed for expense totals; a separate aggregated query could replace them. budgets must remain for PhaseBasedStrategy. Type-specific relations for DirectMappedIndividual are not in the current dataset and would require addition or acceptance of N+1.

---

## Step 5 — Lightweight Projection Design

### Minimal Project Columns

```php
[
    'project_id',
    'province_id',
    'society_id',
    'project_type',
    'user_id',
    'in_charge',
    'commencement_month_year',
    'opening_balance',
    'amount_sanctioned',
    'amount_forwarded',
    'local_contribution',
    'overall_project_budget',
    'status',
    'current_phase',        // PhaseBasedBudgetStrategy
    'project_title',        // Enhanced budget top projects
]
```

### Relations to Retain

| Relation | Reason |
|----------|--------|
| user | center, name — or replace with join selecting users.center, users.name |
| reports | Expense aggregation |
| reports.accountDetails | total_expenses |
| budgets | PhaseBasedBudgetStrategy |

### Relations to Add (or Accept N+1)

| Relation | Used By | When |
|----------|---------|------|
| iiesExpenses, iesExpenses, ilpBudget, iahBudgetDetails, igeBudget | DirectMappedIndividualBudgetStrategy | Per project_type |

---

## Step 6 — DatasetCacheService Compatibility

### Cache Serialization

- Laravel Cache::remember serializes the collection (PHP serialize).
- Eloquent models serialize with their attributes and loaded relations.
- A `select()` projection returns Eloquent models with only selected attributes; serialization works.
- Relations must be eager-loaded before cache; they are stored with the model.

### Compatibility

| Aspect | Compatible? |
|--------|-------------|
| Project select() | Yes — models have fewer attributes |
| Eager load relations | Yes — same as today |
| Resolver resolve() | Yes — if required project attrs + relations present |
| Widget aggregation | Yes — if user, reports, accountDetails present |

---

## Step 7 — Resolver Compatibility

### resolveCollection() Requirements

PHPDoc: "Projects must have reports, reports.accountDetails, budgets eager-loaded."

### With Project select()

| Strategy | Works with select()? |
|----------|----------------------|
| PhaseBasedBudgetStrategy | Yes — needs project attrs + budgets. budgets must be eager-loaded. |
| DirectMappedIndividualBudgetStrategy | Partial — uses loadMissing on type-specific relations. Either add conditional eager load for those types, or accept N+1. |

### Mitigation for DirectMappedIndividual

- **Option A:** Eager load type-specific relations for all projects (heavy).
- **Option B:** Eager load conditionally based on project_type (complex, multiple with() paths).
- **Option C:** Accept N+1 for DirectMappedIndividual projects (typically fewer per province).

---

## Step 8 — Performance Impact Estimation

### Assumptions

- Full Project model: ~2 KB per project (50+ columns, JSON, text).
- Project projection (15 columns): ~0.5 KB per project.
- Relations unchanged: user ~0.5 KB, reports+accountDetails variable (2–10 reports × ~1 KB).

### Memory (Rough)

| Scale | Full Model | Projection Only | Projection + Same Relations |
|-------|------------|-----------------|-----------------------------|
| 100 projects | ~5 MB | ~4 MB | ~4.5 MB |
| 1,000 projects | ~50 MB | ~45 MB | ~45 MB |
| 5,000 projects | ~250 MB | ~220 MB | ~225 MB |

Project column reduction yields ~10–15% savings; the bulk of memory is relations (reports, accountDetails, user). Larger gains require reducing or aggregating report/accountDetail data.

### Cache Payload

- Smaller project attributes → smaller serialized cache.
- Estimated 5–15% reduction in cache size for project-column projection alone.

### Query Time

- `select()` reduces data transfer from DB.
- Marginal improvement; relations dominate query cost.

---

## Step 9 — Risk Analysis

### Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| Missing project column | High | Audit and include all resolver + widget fields in select |
| Missing relation | High | Retain user, reports.accountDetails, budgets |
| DirectMappedIndividual N+1 | Medium | Document; optionally add conditional eager load for common types |
| Resolver loadMissing fails | Medium | Ensure budgets always eager-loaded; type-specific loadMissing is additive |
| Cache deserialization | Low | Eloquent models with select() serialize correctly |

### Mitigation

1. Use comprehensive select() list including current_phase, project_title.
2. Keep user, reports.accountDetails, budgets in eager load.
3. Document N+1 for DirectMappedIndividual; consider follow-up to optimize if profiling shows impact.
4. Add integration tests for dashboard with projected dataset.

---

## Step 10 — Updated Phase 4.5 Implementation Plan

Refinements based on audit:

1. **Project select()** — Use the minimal column list; add `current_phase`, `project_title`.
2. **Relations** — Retain `user`, `reports.accountDetails`, `budgets`. Do not remove for Phase 4.5.
3. **DirectMappedIndividual** — Document N+1; add conditional eager load only if profiling justifies it.
4. **User join option** — Future optimization: join users and select center, name as scalar attributes to avoid full user models.
5. **Report aggregation** — Future: replace reports + accountDetails with a pre-aggregated query for expense totals; would require widget refactor.

---

## Verification Checklist

| # | Verification | Status |
|---|--------------|--------|
| 1 | Widget field dependencies documented | ✓ |
| 2 | Resolver field dependencies documented | ✓ |
| 3 | Relation requirements identified | ✓ |
| 4 | Project column projection designed | ✓ |
| 5 | DatasetCacheService compatibility assessed | ✓ |
| 6 | DirectMappedIndividual N+1 risk documented | ✓ |
| 7 | Memory impact estimated | ✓ |
| 8 | Risks and mitigations listed | ✓ |
| 9 | Phase 4.5 plan refinements captured | ✓ |
