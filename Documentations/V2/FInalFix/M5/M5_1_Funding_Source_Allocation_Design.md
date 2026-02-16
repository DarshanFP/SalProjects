# M5.1 — Funding Source Allocation Architecture Design

**Milestone:** M5 — Financial Tracking Enhancement  
**Phase:** M5.1 — Funding Source Allocation Architecture Design  
**Mode:** STRICTLY DESIGN ONLY (No Code Changes)  
**Date:** 2026-02-15

---

## SECTION 1 — Problem Statement

### Current Limitation

Expenses are stored **without funding source**. The system tracks:

- **Project-level funds:** `amount_sanctioned`, `amount_forwarded`, `local_contribution`, `opening_balance` (from `Project` and `ProjectFinancialResolver`).
- **Expense totals:** e.g. `total_expenses` on report account details, IES/IIES expense details (line-level `amount`), IAH `total_expenses` per budget line, DP report `expenses_this_month` / `total_expenses` per account detail.

There is **no link** between a rupee spent and whether it came from sanctioned, forwarded, or local contribution. Therefore we **cannot** calculate:

- **sanctioned_used** — amount of sanctioned funds consumed by expenses  
- **local_used** — amount of local contribution consumed  
- **forwarded_used** — amount of forwarded funds consumed  

Consequences:

- **Financial transparency incomplete:** Audits and reports cannot show utilization by source.
- **Remaining balance** is today a single number (e.g. opening_balance − total_expenses); we cannot show remaining per source.
- **Utilization %** cannot be broken down by sanctioned vs forwarded vs local.
- **Compliance and reporting** (e.g. “how much sanctioned money is left?”) cannot be answered from current data.

---

## SECTION 2 — Proposed Schema Change

### New attribute: funding source

Add a **funding source** attribute to expense storage so each expense (or expense line) can be attributed to one of three pools:

| Value       | Meaning                          |
|------------|-----------------------------------|
| `SANCTIONED` | From project’s sanctioned amount  |
| `LOCAL`      | From local contribution           |
| `FORWARDED`  | From amount forwarded             |

**Representation:**

- **Option 2a — ENUM:** `funding_source ENUM('SANCTIONED','LOCAL','FORWARDED')`  
  - Pros: DB constraint, clear domain.  
  - Cons: Adding a new source later requires ALTER; not all DBs support ENUM the same way.
- **Option 2b — VARCHAR:** `funding_source VARCHAR(20) DEFAULT NULL`  
  - Pros: Flexible, easy to add values (e.g. `UNSPECIFIED` for legacy), portable.  
  - Cons: No DB-level enum constraint; application must validate.

**Recommendation:** Use **VARCHAR(20)** with application-level validation against a whitelist. Allows `UNSPECIFIED` or `NULL` for legacy/migration without schema change.

---

### Which tables are affected?

| Area | Table(s) | Nature of expense storage | Where to add funding_source |
|------|----------|----------------------------|-----------------------------|
| **IES** (Individual - Ongoing Educational support) | `project_IES_expense_details` | Line-level: `particular`, `amount` | **Detail table** (per line) |
| **IIES** (Individual - Initial - Educational support) | `project_IIES_expense_details` | Line-level: `iies_particular`, `iies_amount` | **Detail table** (per line) |
| **IAH** (Individual - Access to Health) | `project_IAH_budget_details` | Row-level: `amount`, `total_expenses` | **Same table** (per budget/expense row) |
| **DP monthly reports** | `DP_AccountDetails` | Per report/budget row: `expenses_this_month`, `total_expenses` | **Same table** (per row); one source per row for that row’s expenses, or split columns — see below |
| **RST** (Residential Skill Training) | `project_RST_programme_expenses` | Year-wise: `year_1`–`year_4` (planned/expense) | **Parent table** if these represent expense; else a separate expense table if introduced later |
| **ILP** (Individual - Livelihood) | `project_ILP_revenue_expenses` | Year-wise revenue/expense | Lower priority; add if/when ILP expense tracking is aligned with IES/IIES |

**Summary:**

- **Detail table (per expense line):** IES expense details, IIES expense details → add `funding_source` on the **detail** row so each line item has one source.
- **Row = budget + expense:** IAH budget details, DP account details → add `funding_source` on the **same row** that holds the expense amount(s). For DP, each account detail row has `expenses_this_month` / `total_expenses`; one `funding_source` per row implies “this row’s expenses are all from this source.” If one row must support multiple sources, we would need either multiple columns (e.g. `expenses_sanctioned`, `expenses_forwarded`, `expenses_local`) or a separate expense-detail table; for a first version, **one source per row** is simpler.
- **RST / ILP:** Treat as secondary; add when expense reporting is standardized.

---

### Nullable vs non-nullable

- **Nullable (recommended for rollout):** `funding_source VARCHAR(20) NULL DEFAULT NULL`  
  - Legacy and backfilled rows can stay `NULL` or be set to `'UNSPECIFIED'`.  
  - New flows can require a value in the application while the DB allows NULL during transition.
- **Non-nullable later:** Once all data is backfilled and all entry points set a value, consider `NOT NULL` with default `'UNSPECIFIED'` or drop default and require explicit value.

---

### Default behavior for legacy rows

- **Before backfill:** Leave `funding_source` as `NULL` (or `'UNSPECIFIED'` if using a sentinel).
- **After backfill (optional):** Run a one-off job to set legacy rows to a single source (e.g. `SANCTIONED`) or to `UNSPECIFIED`; see Section 5.

---

## SECTION 3 — Allocation Model Options

### Option A — Manual source selection per expense

**Description:** When the user enters or edits an expense (or expense line), they choose the funding source (Sanctioned / Local / Forwarded).

| Criterion | Assessment |
|-----------|------------|
| **Accuracy** | High — user explicitly assigns source; reflects real intent. |
| **Complexity** | Medium — UI and validation in every expense form; must enforce totals ≤ available per source. |
| **User experience** | Can be heavy — users must understand and choose; risk of mistakes if they don’t care. |
| **Backward compatibility** | Good — legacy rows stay NULL/UNSPECIFIED; new data is explicit. |

**Trade-off:** Best accuracy and auditability; more UI and validation work.

---

### Option B — Automatic priority allocation (sanctioned first, etc.)

**Description:** System does not store funding source per expense. When reporting, we **derive** usage by a fixed rule (e.g. exhaust sanctioned first, then forwarded, then local) and compute sanctioned_used, forwarded_used, local_used from totals.

| Criterion | Assessment |
|-----------|------------|
| **Accuracy** | Medium — reflects a policy assumption, not necessarily how each rupee was actually spent. |
| **Complexity** | Low — no new columns; logic only in resolver/aggregation. |
| **User experience** | No change — no new fields. |
| **Backward compatibility** | Excellent — no migration of expense rows. |

**Trade-off:** Simple and backward compatible, but no per-expense attribution and less flexibility (e.g. “this line was from local”).

---

### Option C — Hybrid model

**Description:**  
- **New expenses:** User selects source (or we default to a policy, e.g. sanctioned first) and we **persist** `funding_source` on the expense row.  
- **Reporting:** For rows with a source we sum by source; for rows without (legacy) we apply a **heuristic** (e.g. Option B) so that totals still reconcile.

| Criterion | Assessment |
|-----------|------------|
| **Accuracy** | High for new data; legacy is approximate. |
| **Complexity** | Medium–high — both storage + heuristic in reporting. |
| **User experience** | Can be improved over time (optional field with smart default). |
| **Backward compatibility** | Good — legacy stays NULL; heuristic fills the gap. |

**Trade-off:** Balances accuracy for new data with a path for legacy and incremental rollout.

---

### Recommendation

- **Short term (M5):** Implement **Option C (hybrid)** — add `funding_source` to the relevant tables (nullable), add UI for manual selection where feasible, and in resolvers/reports use:  
  - **Stored source** when present.  
  - **Heuristic (Option B)** for NULL/UNSPECIFIED so that sanctioned_used / forwarded_used / local_used always sum to total_expenses and remaining_* are consistent.
- **Long term:** Move toward **Option A** where all new expenses require a chosen source and legacy is fully backfilled or explicitly marked UNSPECIFIED.

---

## SECTION 4 — Canonical Financial Model Impact

### New derived fields (conceptual)

These are **computed** from project funds and expense breakdown by source (either stored or heuristic):

| Field | Definition |
|-------|------------|
| **sanctioned_used** | Sum of expense amounts where `funding_source = 'SANCTIONED'` (or allocated by heuristic). |
| **local_used** | Sum of expense amounts where `funding_source = 'LOCAL'`. |
| **forwarded_used** | Sum of expense amounts where `funding_source = 'FORWARDED'`. |
| **remaining_sanctioned** | `amount_sanctioned - sanctioned_used` (capped at 0). |
| **remaining_local** | `local_contribution - local_used` (capped at 0). |
| **remaining_forwarded** | `amount_forwarded - forwarded_used` (capped at 0). |

Invariant: `sanctioned_used + local_used + forwarded_used = total_expenses` (within rounding).  
So: `remaining_sanctioned + remaining_local + remaining_forwarded = opening_balance - total_expenses = remaining_balance` (current “single” remaining).

### Integration with existing model

- **Opening balance:** Today `opening_balance = amount_sanctioned` (for approved) or `amount_forwarded + local_contribution` (non-approved). It remains the **total** available; we do not change its definition.
- **Remaining balance:** Today `remaining = opening_balance - total_expenses` (or equivalent). We keep this; in addition we expose:
  - `remaining_sanctioned`, `remaining_local`, `remaining_forwarded` so that remaining is **split by source**.
- **Utilization %:** Today we can have overall utilization (e.g. total_expenses / opening_balance). With the new fields we add:
  - **Sanctioned utilization:** `sanctioned_used / amount_sanctioned` (when amount_sanctioned > 0).
  - **Forwarded utilization:** `forwarded_used / amount_forwarded` (when amount_forwarded > 0).
  - **Local utilization:** `local_used / local_contribution` (when local_contribution > 0).

Resolvers (e.g. `ProjectFinancialResolver`) and report aggregation would **add** these derived fields in their output; they would **not** replace existing opening_balance or total_expenses.

---

## SECTION 5 — Migration Strategy for Existing Expense Data

Three approaches:

| Option | Description | Pros | Cons |
|--------|-------------|------|------|
| **Mark all historical as UNSPECIFIED** | Set `funding_source = 'UNSPECIFIED'` (or leave NULL) for every existing expense row. Reporting uses heuristic for UNSPECIFIED so totals still work. | Simple, safe, no financial reclassification. | Historical reports do not show “real” source breakdown; only new data is accurate. |
| **Allocate historically by heuristic** | One-off job: for each project, sort expenses by date (or id), then assign SANCTIONED until sanctioned pool exhausted, then FORWARDED, then LOCAL, and write `funding_source`. | Gives a full breakdown for past data and consistent remaining_* back in time. | Heuristic may not match reality; audit risk if users assume it’s exact. |
| **Leave legacy untouched** | Do not add column to old rows; only new tables/columns are populated. Old reports keep current behavior. | No migration risk. | Two reporting paths (legacy vs new); complexity. |

**Recommendation:**  
- **Phase 1:** Add column as **nullable**, no backfill. All existing rows stay `NULL`.  
- **Reporting:** Treat `NULL` as UNSPECIFIED and apply **automatic allocation heuristic** when computing sanctioned_used / forwarded_used / local_used so that existing dashboards and reports keep working and new fields appear gradually.  
- **Optional later:** Run a **one-off heuristic backfill** (Option 2) with clear documentation that it is “estimated” and not user-verified. Prefer marking backfilled rows with a flag (e.g. `funding_source_estimated = true`) if schema allows, so reports can distinguish user-set vs estimated.

---

## SECTION 6 — Reporting Impact

Areas that will eventually show or use source-level usage/remaining:

| Area | Current behavior | Change with funding source |
|------|------------------|-----------------------------|
| **Dashboard (Executor / Provincial / Coordinator)** | Shows total expenses and remaining (single number). | Can show remaining_sanctioned / remaining_local / remaining_forwarded and utilization % by source. |
| **Exports (Excel/CSV)** | Export project/report with total_expenses. | Add columns: sanctioned_used, forwarded_used, local_used, remaining_sanctioned, remaining_forwarded, remaining_local (or same in report export). |
| **PDF (project/report)** | Budget and expense summary. | Add a small table or line: utilization by source and remaining by source. |
| **Provincial reports** | Aggregated budget summaries, total_expenses, total_remaining. | Aggregate sanctioned_used / forwarded_used / local_used and remaining_* across projects/reports. |
| **Coordinator analytics** | Approved projects, budget vs expense. | Same aggregates by source; possible filters (e.g. “projects with high sanctioned utilization”). |
| **Project show (budget section)** | Shows opening_balance, total expenses, remaining. | Can show the same plus breakdown by source (from resolver or report rollup). |
| **Monthly / quarterly / annual reports** | DP report account details with expenses; IES/IIES/IAH expense views. | When storing funding_source, show it in tables; in summaries show used/remaining by source. |

Implementation of these is **later phase** (Section 7); this section only identifies impact.

---

## SECTION 7 — Rollout Plan

| Phase | Scope | Deliverable |
|-------|--------|-------------|
| **Phase 1 — Schema + nullable field** | Add `funding_source` (VARCHAR, nullable) to chosen expense tables (IES/IIES detail; IAH budget details; DP_AccountDetails if applicable). No backfill. | Migrations; model fillable/casts; DB only. |
| **Phase 2 — UI update** | In expense create/edit forms (IES, IIES, IAH, and DP if applicable), add funding source dropdown (Sanctioned / Local / Forwarded). Optional with default (e.g. SANCTIONED) or required for new rows. | Forms + validation; existing flows still work with NULL. |
| **Phase 3 — Resolver enhancement** | In `ProjectFinancialResolver` (or a dedicated “expense by source” service), compute sanctioned_used, forwarded_used, local_used from DB where stored; for NULL/UNSPECIFIED apply heuristic. Expose remaining_sanctioned, remaining_local, remaining_forwarded. | Resolver/output contract; no change to approval/revert logic. |
| **Phase 4 — Report upgrade** | Dashboards, exports, PDF, provincial/coordinator summaries consume the new derived fields and show utilization and remaining by source. | Views, export columns, PDF snippets. |
| **Phase 5 — Strict enforcement (optional)** | Once all entry points set funding_source and legacy is acceptable: make field required for new rows; optionally backfill legacy and add `NOT NULL` or business rule “no UNSPECIFIED for new data.” | Validation + optional DB constraint. |

No code or schema is implemented in M5.1; this is the target roadmap.

---

## SECTION 8 — Risk Assessment

| Risk | Level | Mitigation |
|------|--------|------------|
| **Financial risk** | Medium | Wrong source attribution could mislead audits. Mitigate: document heuristic clearly; prefer manual selection (Option A/C); optional “estimated” flag for backfilled rows. |
| **Migration risk** | Medium | Adding columns and backfilling can lock tables or introduce bugs. Mitigate: nullable column first; backfill in low-traffic window; test on copy of production data. |
| **Performance impact** | Low | New column and SUM by funding_source are small; indexes on (project_id, funding_source) if we filter/group often. Monitor report query time. |
| **Backward compatibility risk** | Low | Nullable column and “UNSPECIFIED + heuristic” keep existing totals and remaining_balance unchanged; new fields are additive. |

---

## Summary

- **Problem:** Expenses have no funding source → cannot compute sanctioned_used, local_used, forwarded_used or remaining by source.
- **Schema:** Add `funding_source` (VARCHAR, nullable) to expense **detail** tables (IES, IIES) and to **row-level** expense tables (IAH, DP_AccountDetails as needed); RST/ILP later.
- **Model:** Prefer **hybrid** — store source when user provides it; use **automatic allocation heuristic** for NULL/UNSPECIFIED so derived fields always sum to total_expenses.
- **Derived fields:** sanctioned_used, local_used, forwarded_used, remaining_sanctioned, remaining_local, remaining_forwarded; integrated with existing opening_balance and remaining_balance.
- **Migration:** Add column only first; treat legacy as UNSPECIFIED + heuristic; optional heuristic backfill later with “estimated” labeling.
- **Rollout:** Schema → UI → Resolver → Reports → Optional strict enforcement.

---

**M5.1 Funding Source Allocation Design Drafted — No Code Changes Made**
