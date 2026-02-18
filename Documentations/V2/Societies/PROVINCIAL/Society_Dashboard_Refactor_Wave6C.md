# Society Dashboard Refactor (Wave 6C)

**Version:** Wave 6C  
**Date:** 2026-02-18

---

## 1. Purpose

- Add a **read-only, society-wise financial breakdown** on the **Provincial dashboard** when the province has **more than one active society**.
- Provide an analytical overlay (approved totals, pending pipeline, reported spending, remaining) per society without changing any lifecycle, approval, or project/report mutation logic.
- Keep province isolation strict (no cross-province leakage) and avoid N+1 queries.

---

## 2. Multi-Society Condition Trigger

The society breakdown section is **only** shown when **all** of the following hold:

1. **User** is a Provincial (or general acting in provincial context).
2. **Province** is known: `$provincial->province_id` is set.
3. **Active society count** in that province is **greater than 1**:
   ```php
   $societyCount = Society::where('province_id', $provinceId)
       ->where('is_active', true)
       ->count();
   $enableSocietyBreakdown = ($societyCount > 1);
   ```

If the province has **one or zero** active societies, the section is **not** rendered and no society-wise aggregation queries are run.

---

## 3. Aggregation Formulas (Approved, Pending, Reported)

| Layer | Source | Formula / Logic |
|-------|--------|------------------|
| **Approved** | `projects` | Sum of `amount_sanctioned` for projects in the province with status in `APPROVED_BY_COORDINATOR`, `APPROVED_BY_GENERAL_AS_COORDINATOR`, `APPROVED_BY_GENERAL_AS_PROVINCIAL`. Grouped by `society_id`. |
| **Pending** | `projects` | Sum of `GREATEST(0, overall_project_budget - amount_forwarded - local_contribution)` for projects in the province with status **not** in `ProjectStatus::FINAL_STATUSES`. Grouped by `society_id`. Represents the “requested” pipeline per society. |
| **Reported** | `DP_Reports` + `DP_AccountDetails` | Sum of `total_expenses` from account details, joined to reports. Reports filtered by `province_id`; grouping by report’s **snapshot** `society_id`. Only reports with non-null `society_id` are included. |

**Remaining** (per society): `max(approved_total - reported_total, 0)`.

---

## 4. Data Sources (Project vs Report Snapshot)

- **Approved / Pending** use the **project** table only, filtered by `project.province_id` and `project.society_id`. No resolution via relationships; direct aggregation.
- **Reported** uses the **report snapshot**: `DP_Reports.province_id` and `DP_Reports.society_id` (Wave 6A snapshot at report creation). Spending is summed from `DP_AccountDetails.total_expenses` joined by `report_id`. Society is **not** resolved via `report->project->society_id` to avoid lifecycle drift and to align with the snapshot model.

---

## 5. Financial Interpretation Guide

| Column | Meaning |
|--------|--------|
| **Society** | Name of the active society in the province. |
| **Approved** | Total amount **sanctioned** (approved) for that society’s projects in this province. Immutable once approved. |
| **Pending** | Total “requested” amount (pre-approval pipeline) for projects in non-final status, by society. |
| **Reported** | Total **expenses** recorded in reports (from account details) attributed to that society via the report’s society snapshot. If no reports exist, 0. |
| **Remaining** | Approved minus Reported, or 0 if negative. Indicates unused approved budget per society from this dashboard’s perspective. |

---

## 6. Performance Considerations

- **Queries**: Three aggregations (approved, pending, reported) run only when `$enableSocietyBreakdown` is true and `$provinceId` is set. No per-row project/report loading; all are single grouped queries.
- **Indexes (Wave 6C migration)**:
  - **projects**: Composite index `(province_id, society_id)` to support `WHERE province_id = ?` and `GROUP BY society_id`.
  - **DP_Reports**: Composite index `(province_id, society_id)` to support the reported-totals join and group.
- No N+1: societies are loaded once; totals are plucked as keyed collections and merged in a single loop.

---

## 7. No Impact to Other Roles

- Logic lives only in the **Provincial** dashboard action and view.
- No changes to approval workflow, project edit logic, report creation, or coordinator/general/executor flows.
- Other roles do not see or depend on this section.

---

## 8. Safety Rules (Enforced)

- All queries filter by **province_id** (projects and reports).
- **ProjectStatus::FINAL_STATUSES** is used for the “pending” filter (non-final only).
- Report society comes from **report.society_id** (snapshot), not from project.
- No mutation of projects, reports, or statuses.
- No dynamic status rewriting or bypass endpoints.

---

**End of Wave 6C documentation.**
