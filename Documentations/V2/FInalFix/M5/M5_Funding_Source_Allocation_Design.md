# M5 — Financial Tracking Enhancement

**Milestone:** M5 — Financial Tracking Enhancement  
**Scope:** Funding Source Allocation Per Expense  
**Mode:** DESIGN ONLY (No Code Changes)  
**Date:** 2025-02-15  
**Status:** Architectural Planning Placeholder

---

## SECTION 1 — Problem Statement

### Currently

- Expenses are recorded without funding source.
- System cannot distinguish:
  - Sanctioned usage
  - Local contribution usage
  - Forwarded usage
- Reports only show total expenses.

### This Prevents

- Accurate funding utilization breakdown.
- Financial transparency across roles.
- Visibility into which funding sources are depleted vs remaining.
- Audit trails for fund source allocation.

---

## SECTION 2 — Proposed Enhancement

### Each Expense Entry Must Record

| Field | Type | Values | Description |
|-------|------|--------|-------------|
| `funding_source` | ENUM or VARCHAR | `sanctioned` \| `local` \| `forwarded` | Source from which the expense was drawn |

### Applies To

| Project Type | Expense Table / Context |
|--------------|-------------------------|
| IES (Individual Ongoing Educational Support) | IES expenses |
| IAH (Individual Access to Health) | IAH expenses |
| Phase-based budgets | Phase-based expense/account details if applicable |
| IIES (Individual Initial Educational Support) | IIES expenses |
| ILP, IGE, RST, etc. | Relevant expense/account tables per project type |

---

## SECTION 3 — Schema Changes Required (Future)

### Add Column to Relevant Expense Tables

```sql
-- Placeholder — DO NOT implement yet
funding_source VARCHAR(20) NOT NULL
-- or
funding_source ENUM('sanctioned', 'local', 'forwarded') NOT NULL
```

### Constraints

- NOT NULL with application-level validation.
- Values restricted to: `sanctioned`, `local`, `forwarded`.
- Default strategy for legacy rows to be defined in migration plan.

### Tables Potentially Affected (to be confirmed)

- `ies_expenses` / IES expense details
- `iah_budget_details` / IAH expense details
- `iies_expenses` / IIES expense details
- `account_details` / DP report account details (phase-based)
- Other project-type-specific expense tables as applicable

---

## SECTION 4 — Resolver Impact

### Resolver Must Compute (Future)

| Field | Definition |
|-------|------------|
| `sanctioned_used` | Sum of expenses where `funding_source = 'sanctioned'` |
| `local_used` | Sum of expenses where `funding_source = 'local'` |
| `forwarded_used` | Sum of expenses where `funding_source = 'forwarded'` |
| `remaining_sanctioned` | `amount_sanctioned − sanctioned_used` |
| `remaining_local` | `local_contribution − local_used` |
| `remaining_forwarded` | `amount_forwarded − forwarded_used` |

### Opening Balance Formula

Remains unchanged:

```
Opening_Balance = Amount_Sanctioned + Amount_Forwarded + Local_Contribution
```

### Invariants (Future)

- `total_expenses = sanctioned_used + local_used + forwarded_used`
- `remaining_sanctioned ≥ 0`, `remaining_local ≥ 0`, `remaining_forwarded ≥ 0`

---

## SECTION 5 — Dashboard Enhancements

### Dashboards Must Show (Future)

| Metric | Description |
|--------|-------------|
| Total expenses | Sum of all expenses (unchanged) |
| Sanctioned used | Expenses drawn from sanctioned amount |
| Local used | Expenses drawn from local contribution |
| Forwarded used | Expenses drawn from forwarded amount |
| Remaining per source | `remaining_sanctioned`, `remaining_local`, `remaining_forwarded` |

### Optional Visualizations

- Pie chart: sanctioned vs local vs forwarded usage
- Progress bars per funding source
- Per-project and aggregate views

---

## SECTION 6 — Backward Compatibility Strategy

### For Legacy Expenses (No funding_source)

| Option | Behavior | Pros | Cons |
|--------|----------|------|------|
| **Option A: Treat as sanctioned** | `funding_source = NULL` → assume `sanctioned` for aggregation | Simple; aligns with common expectation that older expenses came from sanctioned funds | May misrepresent if some legacy expenses used local/forwarded |
| **Option B: Treat as unknown** | `funding_source = NULL` → separate "unallocated" bucket; exclude from per-source breakdown | Honest; no assumption | Requires UI to handle unknown bucket; totals still valid |
| **Option C: Migrate with default** | Set `funding_source = 'sanctioned'` for all legacy rows | Clean schema; no nulls | Requires data migration; assumes sanctioned |

### Migration Strategy (To Be Defined Later)

- Define default for `funding_source` (nullable initially vs NOT NULL with backfill).
- Decide Option A, B, or C for legacy data.
- Plan phased rollout: schema change → backfill → validation → UI updates.
- Add application validation before schema enforcement.

---

## SECTION 7 — Risk Assessment

| Risk | Level | Mitigation |
|------|-------|------------|
| Schema impact | High | Defer to dedicated milestone; test in staging; run migrations during low-traffic window |
| Reporting impact | High | All expense aggregations must include `funding_source` filter; regression tests required |
| Resolver / calculation changes | High | Resolver must support new fields; parity with existing totals |
| Backward compatibility | Medium | Clear legacy handling strategy; nullable column or backfill before NOT NULL |
| UI / UX changes | Medium | Dashboards and reports need new layouts; user training may be required |
| Audit / compliance | Low–Medium | Improves traceability; may require documentation for auditors |

**Overall risk:** **HIGH**

**Recommendation:** Implement in a controlled future milestone after M3 financial parity is stable. Requires:

- Schema migration plan
- Resolver extension plan
- Dashboard and report specification
- Test strategy
- Rollback plan

---

**End of M5 Design Placeholder**
