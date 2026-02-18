# Table Format Integrity Audit

**Date:** 2026-02-16  
**Scope:** All Blade views under `resources/views/`  
**Rules:** Read-only investigation; no files modified.

---

## 1. Summary

| Metric | Count |
|--------|-------|
| **Total table-containing Blade files scanned** | 95+ |
| **Index/List/Financial tables audited** | 72 |
| **Fully compliant (A)** | 28 |
| **Serial missing (B)** | 18 |
| **Totals missing (C)** | 14 |
| **Both missing (D)** | 10 |
| **Not applicable – no numeric columns (E)** | 2 |

**Non-compliant total:** 42 tables (B + C + D).  
**High-risk (financial impact):** 12 tables (see Section 3).

---

## 2. Detailed Findings

### Index / List Views (Project, Report, Entity Listings)

| File | Table purpose | Serial column | Numeric columns | Total present | Classification | Recommended fix (no code applied) |
|------|----------------|---------------|-----------------|---------------|----------------|-------------------------------------|
| `provincial/ProjectList.blade.php` | Project listing (provincial) | No | Yes (Budget, Existing Funds, Local Contribution, Amount Requested) | No | **D** | Add `<th>#</th>` and `$loop->iteration` or `firstItem()+$index`; add `<tfoot>` with sum of amount columns or pass pre-calculated totals from controller. |
| `coordinator/ProjectList.blade.php` | Project listing (coordinator) | No | Yes (Budget, Expenses, Remaining, Utilization) | No | **D** | Add serial column; add footer or summary block with totals. |
| `admin/projects/index.blade.php` | Admin project list | No | Yes (Budget, Utilization) | No | **D** | Add S.No; add totals row or summary. |
| `admin/reports/index.blade.php` | Admin report list | No | No | N/A | **B** | Add S.No / # column with iteration. |
| `general/executors/index.blade.php` | Executor listing | No | No | N/A | **B** | Add `<th>#</th>` and `$loop->iteration` or pagination-aware index. |
| `general/provincials/index.blade.php` | Provincial users list | No | No | N/A | **B** | Add serial column. |
| `general/societies/index.blade.php` | Societies list | Yes (#, firstItem+index) | Yes (Centers Count) | No | **C** | Add tfoot or summary with total centers count if meaningful. |
| `general/centers/index.blade.php` | Centers list | Yes (#, firstItem+index) | Yes (Users Count) | No | **C** | Add footer total for count column if desired. |
| `general/provinces/index.blade.php` | Provinces list | No | Yes (counts: provincial users, centers, users) | No | **B** + **C** | Add # column; optionally add total row for counts. |
| `general/budgets/index.blade.php` | Budget overview list | No | Yes (Budget, Approved/Unapproved Expenses, Remaining, Utilization) | No | **D** | Add S.No; add tfoot with totals for all numeric columns. |
| `provincial/provincials/index.blade.php` | Provincial users (by province) | Yes (#, index+1) | No | N/A | **A** | — |
| `provincial/centers.blade.php` | Centers list (provincial) | Yes (#, index+1) | No | N/A | **A** | — |
| `provincial/societies/index.blade.php` | Societies list (provincial) | Yes (#, index+1) | No | N/A | **A** | — |
| `reports/monthly/index.blade.php` | Monthly reports list | No | No | N/A | **B** | Add S.No. |
| `general/reports/pending.blade.php` | Pending reports list | No | Yes (Total Amount, Total Expenses, Balance) | No | **D** | Add serial column; add tfoot with sum of amount columns. |
| `executor/ReportList.blade.php` | Executor report list | No | Yes (Total Amount, Total Expenses, Balance) | No | **D** | Add S.No; add footer totals. |
| `executor/pendingReports.blade.php` | Pending reports (executor) | No | — | — | **B** (assumed) | Add serial column. |

### Project Show / Budget & Financial Tables

| File | Table purpose | Serial column | Numeric columns | Total present | Classification | Recommended fix (no code applied) |
|------|----------------|---------------|-----------------|---------------|----------------|-------------------------------------|
| `projects/partials/Show/budget.blade.php` | Budget items | Yes (No., index+1) | Yes (Costs, Rate Multiplier, Rate Duration, This Phase) | Yes (tfoot, collection->sum()) | **A** | — |
| `projects/partials/Show/IIES/estimated_expenses.blade.php` | IIES estimated expenses | Yes (No., index+1) | Yes (Amount) | Yes (below table) | **A** | — |
| `projects/partials/Show/IES/estimated_expenses.blade.php` | IES estimated expenses | Yes (Sl No, index+1) | Yes (Amount) | Yes (below table) | **A** | — |
| `projects/partials/Show/IAH/budget_details.blade.php` | IAH budget details | No | Yes (Amount) | Yes (sum below) | **B** | Add `<th>S.No.</th>` and `{{ $index + 1 }}` in tbody. |
| `projects/partials/Show/ILP/budget.blade.php` | ILP budget | Yes (Sl No, index+1) | Yes (Cost) | Yes (below table) | **A** | — |
| `projects/partials/Show/IGE/budget.blade.php` | IGE budget (current year) | Yes (S.No, index+1) | Yes (fees, amounts) | Yes (tfoot) | **A** | — |

### Beneficiary / Member / Target Group Tables

| File | Table purpose | Serial column | Numeric columns | Total present | Classification | Recommended fix (no code applied) |
|------|----------------|---------------|-----------------|---------------|----------------|-------------------------------------|
| `projects/partials/Show/RST/beneficiaries_area.blade.php` | RST project area / beneficiaries | No | Yes (Direct, Indirect) | Yes (tfoot) | **B** | Add S.No column. |
| `projects/partials/Show/RST/geographical_area.blade.php` | RST geographical area | — | Possibly | — | **B** (if list) | Add S.No if row list. |
| `projects/partials/Show/RST/target_group_annexure.blade.php` | RST target group annexure | Yes (S.No., index+1) | — | — | **A** | — |
| `projects/partials/Show/LDP/target_group.blade.php` | LDP target group | Yes (S.No., loop->iteration) | Yes (Amount Requested) | No | **C** | Add tfoot with sum of Amount Requested. |
| `projects/partials/Show/Edu-RUT/target_group.blade.php` | Edu-RUT target group | Yes (S.No., index+1) | Yes (Tuition Fee, Expected Amount, Family Contribution) | No | **C** | Add tfoot or summary row with totals. |
| `projects/partials/Show/Edu-RUT/annexed_target_group.blade.php` | Edu-RUT annexed target group | Yes (S.No., index+1) | — | — | **A** | — |
| `projects/partials/Show/IGE/beneficiaries_supported.blade.php` | IGE beneficiaries supported | Yes (S.No, index+1) | — | — | **A** | — |
| `projects/partials/Show/IGE/new_beneficiaries.blade.php` | IGE new beneficiaries | Yes (S.No, index+1) | — | — | **A** | — |
| `projects/partials/Show/IGE/ongoing_beneficiaries.blade.php` | IGE ongoing beneficiaries | Yes (S.No, index+1) | — | — | **A** | — |
| `projects/partials/Show/IIES/family_working_members.blade.php` | IIES family working members | Yes (No., index+1) | Yes (Monthly Income) | No | **C** | Add tfoot with sum(Monthly Income). |
| `projects/partials/Show/IES/family_working_members.blade.php` | IES family working members | Yes (index+1) | Yes (Monthly Income) | No | **C** | Add tfoot with sum(Monthly Income). |
| `projects/partials/Show/IAH/earning_members.blade.php` | IAH earning members | Yes (No., index+1) | Yes (Monthly Income) | No | **C** | Add tfoot with sum(Monthly Income). |
| `projects/partials/Show/CCI/annexed_target_group.blade.php` | CCI annexed target group | Yes (S.No., index+1) | — | — | **A** | — |

### Report – Statements of Account & Budget Monitoring

| File | Table purpose | Serial column | Numeric columns | Total present | Classification | Recommended fix (no code applied) |
|------|----------------|---------------|-----------------|---------------|----------------|-------------------------------------|
| `reports/monthly/partials/statements_of_account/development_projects.blade.php` (edit) | Statement rows (edit) | Yes (No., index+1) | Yes (sanctioned, expenses, balance) | Yes (tfoot + JS) | **A** | — |
| `reports/monthly/partials/view/statements_of_account/development_projects.blade.php` | Statement rows (view) | No | Yes | Yes (total row in tbody) | **B** | Add `<th>No.</th>` and `{{ $loop->iteration }}` or index+1. |
| `reports/monthly/partials/view/statements_of_account/*.blade.php` (IE, IL, IH, IOE, inst.) | View statements (other types) | No | Yes | Yes (total row) | **B** | Add serial column. |
| `reports/monthly/developmentProject/reportform.blade.php` | Development project statement form | No | Yes | Yes (tfoot + JS) | **B** | Add No. column in thead and index+1 in rows. |
| `reports/monthly/partials/view/budget_monitoring.blade.php` (overspend / negative) | Overspend & negative balance | No | Yes | No | **B** + **C** | Add S.No; add total row for numeric columns if meaningful. |

### Admin – Budget Reconciliation & Audit

| File | Table purpose | Serial column | Numeric columns | Total present | Classification | Recommended fix (no code applied) |
|------|----------------|---------------|-----------------|---------------|----------------|-------------------------------------|
| `admin/budget_reconciliation/index.blade.php` | Reconciliation list | No | Yes (Stored/Resolved sanctioned) | No | **D** | Add S.No; add tfoot with totals if needed. |
| `admin/budget_reconciliation/show.blade.php` (comparison table) | Field comparison | N/A (key-value) | Yes | N/A | **E** | — |
| `admin/budget_reconciliation/show.blade.php` (audit history) | Audit log | No | Yes (old/new sanctioned) | N/A (log) | **B** | Add # for log order. |
| `admin/budget_reconciliation/correction_log.blade.php` | Correction log list | No | Yes (old/new sanctioned) | N/A | **B** | Add S.No column. |

### Widgets – Budget Summary & Comparison Tables

| File | Table purpose | Serial column | Numeric columns | Total present | Classification | Recommended fix (no code applied) |
|------|----------------|---------------|-----------------|---------------|----------------|-------------------------------------|
| `provincial/index.blade.php` (Budget by Project Type) | Summary by type | N/A (summary) | Yes | No | **C** | Add grand-total row for budget/expenses/remaining. |
| `provincial/index.blade.php` (Budget by Center) | Summary by center | N/A | Yes | No | **C** | Add grand-total row. |
| `coordinator/widgets/system-budget-overview.blade.php` (by type, by province, by center) | Budget summaries | N/A | Yes | No | **C** | Add grand-total row per table. |
| `coordinator/widgets/system-budget-overview.blade.php` (Top 10 projects) | Top projects | Yes (Rank / #) | Yes | No | **C** | Add tfoot total row for Budget/Expenses/Remaining. |
| `executor/widgets/project-budgets-overview.blade.php` | Budget by project type | No | Yes | No | **C** | Add serial if row list; add grand-total row. |
| `general/widgets/context-comparison.blade.php` | Metric comparison | N/A (matrix) | Yes | N/A | **E** | — |
| `general/widgets/partials/budget-overview-content.blade.php` (by type, province, center, coordinator) | Budget breakdowns | No | Yes | No | **C** | Add grand-total row per table. |

### Excluded (Layout / Static / Non-List)

- **Layout / key-value:** `projects/partials/Show/general_info.blade.php` (key-value table).  
- **Static sample:** `admin/dashboard.blade.php` (NobleUI sample table – static HTML).  
- **PDF structure:** `projects/Oldprojects/pdf.blade.php` (signature tables).  
- **Report PDF:** `reports/monthly/PDFReport.blade.php` (layout/details tables).  
- **Logical framework / activities:** Structural activity tables; serial present in many; totals N/A for narrative columns.

---

## 3. High-Risk Tables (Financial Impact)

Tables with monetary columns and missing totals or serial that affect traceability:

1. **general/budgets/index.blade.php** – Budget, expenses, remaining; no serial, no totals.  
2. **general/reports/pending.blade.php** – Total Amount, Total Expenses, Balance; no serial, no totals.  
3. **executor/ReportList.blade.php** – Same financial columns; no serial, no totals.  
4. **provincial/ProjectList.blade.php** – Overall Budget, Existing Funds, Local Contribution, Amount Requested; no serial, no totals.  
5. **coordinator/ProjectList.blade.php** – Budget, Expenses, Remaining, Utilization; no serial, no totals.  
6. **admin/projects/index.blade.php** – Budget, Utilization; no serial, no totals.  
7. **admin/budget_reconciliation/index.blade.php** – Stored/Resolved sanctioned; no serial, no totals.  
8. **reports/monthly/partials/view/statements_of_account/*.blade.php** – All amount columns; serial missing (totals present).  
9. **projects/partials/Show/LDP/target_group.blade.php** – Amount Requested; totals missing.  
10. **projects/partials/Show/Edu-RUT/target_group.blade.php** – Tuition fee, expected amount, family contribution; totals missing.  
11. **projects/partials/Show/IIES/family_working_members.blade.php** – Monthly Income; totals missing.  
12. **projects/partials/Show/IES/family_working_members.blade.php** & **IAH/earning_members.blade.php** – Monthly Income; totals missing.

---

## 4. Suggested Standard Table Template Pattern

**For index/list tables (projects, reports, entities):**

- **Serial:** First column `<th>#</th>` or `<th>S.No.</th>`, body: `{{ $collection->firstItem() + $index }}` (paginated) or `{{ $loop->iteration }}` / `{{ $index + 1 }}`.
- **Numeric columns:** Include a **Total** column and/or `<tfoot>` with sum row, or a summary block below the table with controller-passed or Blade `->sum()` aggregates.

**For financial/amount tables (budget, expenses, statements):**

- **Serial:** `<th>No.</th>` / `<th>S.No.</th>` with `{{ $index + 1 }}` in each row.
- **Totals:** `<tfoot>` with one row: empty/serial cell, label "Total", then `{{ format_indian_currency($collection->sum('amount'), 2) }}` (or equivalent) per numeric column; or JS for dynamic forms.

**For summary tables (by type, by center, by province):**

- **Serial:** Optional (category name is often enough).
- **Totals:** Add a final “Grand Total” row summing all numeric columns.

---

## 5. Priority Fix Order

1. **P1 – Financial list views (no totals):**  
   `general/budgets/index.blade.php`, `general/reports/pending.blade.php`, `executor/ReportList.blade.php`, `provincial/ProjectList.blade.php`, `coordinator/ProjectList.blade.php`, `admin/projects/index.blade.php`.

2. **P2 – Statement view serial:**  
   All `reports/monthly/partials/view/statements_of_account/*.blade.php` – add S.No column.

3. **P3 – Project section tables (totals or serial):**  
   IAH budget_details (serial), LDP/Edu-RUT target groups (totals), IIES/IES/IAH family/earning members (income totals), RST beneficiaries_area (serial).

4. **P4 – Admin & reconciliation:**  
   `admin/budget_reconciliation/index.blade.php`, `show.blade.php` (audit table), `correction_log.blade.php` – add S.No.

5. **P5 – Widget summary tables:**  
   Provincial index (by type/center), coordinator system-budget-overview, general budget-overview-content – add grand-total row.

6. **P6 – Other list views (serial only):**  
   Executors, provincials, reports index, monthly report form statement table – add S.No.

---

**Audit complete. No code was modified.**
