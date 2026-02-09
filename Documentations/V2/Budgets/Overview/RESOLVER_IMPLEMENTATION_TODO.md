# Project Financial Resolver — Implementation Checklist

⚠️ **This is scaffolding only. Do not wire into controllers until parity tests pass.**

---

## Phase 1 — Skeleton Creation

- [ ] Folder structure created
- [ ] Interface created
- [ ] PhaseBasedBudgetStrategy created
- [ ] DirectMappedIndividualBudgetStrategy created
- [ ] ProjectFinancialResolver created
- [ ] No controller wired yet
- [ ] No behavior changes

---

## Phase 2 — Parity Validation (Future)

- [ ] Compare output with ProjectFundFieldsResolver
- [ ] Add unit test for PhaseBased project
- [ ] Add unit test for Individual project
- [ ] Validate approved project behavior
- [ ] Validate non-approved project behavior

---

## Phase 3 — Controller Arithmetic Elimination

### Phase 3A — Single-Project Display Refactor

- [ ] Replace fallback sanctioned/overall patterns
- [ ] Replace direct sanctioned/opening reads
- [ ] Replace inline remaining math
- [ ] Replace inline utilization math
- [ ] Instantiate resolver once per method
- [ ] All tests pass

### Phase 3B — Aggregation Refactor

- [ ] Replace sum('amount_sanctioned')
- [ ] Replace sum('overall_project_budget')
- [ ] Replace province/team totals
- [ ] Replace analytics totals
- [ ] Performance check for loops
- [ ] Add resolver caching if required
- [ ] All tests pass

---

## Phase 4 — Post Integration

- [ ] Redirect BudgetValidationService to resolver
- [ ] Remove duplicated formulas
- [ ] Remove phase from financial logic
- [ ] Remove blade arithmetic
- [ ] Standardize rounding
