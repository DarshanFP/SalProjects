# Commencement Date Integrity Audit

**Task:** Commencement Date Integrity Audit (Preparation for Financial Year Dashboards)  
**Date:** 2026-03-04  
**Mode:** Audit (read-only; no code or database modified)

---

## 1. Schema Verification

### Schema Audit — `projects.commencement_month_year`

| Property | Value |
|----------|-------|
| Column exists | Yes |
| Column type | `date` |
| Nullable status | Nullable |
| Default value | None |
| Indexes | None on `commencement_month_year` |

### Migration Source

- **Initial:** `2024_07_20_085634_create_projects_table.php` — `$table->date('commencement_month_year')->nullable();`
- **Supplemental:** `2026_01_19_000002_add_commencement_month_year_to_projects_table.php` adds `commencement_month` and `commencement_year` (separate fields); `commencement_month_year` already existed.

### Related Columns

| Column | Type | Purpose |
|--------|------|---------|
| `commencement_month` | unsignedTinyInteger, nullable | Month (1–12) for coordinator approval |
| `commencement_year` | unsignedSmallInteger, nullable | Year for coordinator approval |
| `commencement_month_year` | date, nullable | Combined Y-m-d value (source for FY derivation) |

---

## 2. Data Completeness Analysis

### Metrics (Live Database Query)

| Metric | Count |
|--------|-------|
| TOTAL_PROJECTS | 258 |
| APPROVED_PROJECTS | 44 |
| APPROVED_WITH_COMMENCEMENT_DATE | 44 |
| APPROVED_WITH_NULL_COMMENCEMENT | 0 |

### Summary

- All 44 approved projects have `commencement_month_year` populated.
- 10 projects (3.88%) have null `commencement_month_year`, all non-approved.

### Null Commencement by Status

| Status | Count |
|--------|-------|
| draft | 2 |
| forwarded_to_coordinator | 8 |

### Null Commencement by Created Year

| Year | Count |
|------|-------|
| 2026 | 10 |

---

## 3. Date Validity Findings

### Validity Checks

| Issue | Count |
|-------|-------|
| commencement_month_year &lt; 1990-01-01 | 0 |
| commencement_month_year &gt; CURRENT_DATE | 123 |
| Zero dates (0000-00-00, 1970-01-01) | 0 |
| Invalid format (non YYYY-MM-DD) | 0 |

### Future Dates Note

- **123 projects** have `commencement_month_year` after today.
- These are planned commencement dates (e.g. 2026-10-01, 2026-11-01).
- Business logic allows future commencement for projects yet to start.
- These are valid for FY derivation; no corrective action needed.

---

## 4. Approval Flow Consistency

### Coordinator Approval

| Aspect | Finding |
|--------|---------|
| Controller | CoordinatorController::approveProject |
| Form Request | ApproveProjectRequest (commencement_month, commencement_year required) |
| Field required | Yes — `required|integer|min:1|max:12` (month), `required|integer|min:2000|max:2100` (year) |
| Approval without commencement | Not possible — validation blocks submission |
| Who sets value | CoordinatorController builds `$approvalData` with `commencement_month_year` from validated month/year; ProjectStatusService persists it atomically |

### General Approval (as Coordinator)

| Aspect | Finding |
|--------|---------|
| Controller | GeneralController::approveProject (approval_context = coordinator) |
| Validation | Inline: `commencement_month` and `commencement_year` required |
| Past-date check | Yes — `commencementDate->isBefore($currentDate)` blocks past commencement |
| Who sets value | GeneralController updates `commencement_month_year` before calling ProjectStatusService |

### General Approval (as Provincial)

| Aspect | Finding |
|--------|---------|
| Flow | Forwards to coordinator; does not set commencement |
| commencement_month_year | Not set at this step |

### Approval Flow Summary

1. **Coordinator approval:** Requires `commencement_month` and `commencement_year` via ApproveProjectRequest; builds `commencement_month_year` and persists via ProjectStatusService::approve.
2. **General as coordinator:** Same requirement; validates and sets commencement before approval.
3. **General as provincial:** Forwards only; does not set commencement.
4. **Conclusion:** Coordinator (or General acting as Coordinator) approval always sets `commencement_month_year`; approval without it is not possible in the current flow.

---

## 5. Legacy Project Detection

### Approved Projects with Null Commencement

| Year (created_at) | Count |
|-------------------|-------|
| *(none)* | 0 |

- No approved projects have null `commencement_month_year`.
- No legacy approved projects without commencement were found.

### Projects with Null Commencement (All Statuses)

| Year (created_at) | Count |
|-------------------|-------|
| 2026 | 10 |

- All 10 null-commencement projects were created in 2026 and are non-approved (draft or forwarded_to_coordinator).

---

## 6. Financial Year Simulation

### FY Derivation Logic

- If month ≥ 4: FY = `year`–`(year+1)` (e.g. 2024-08-01 → FY 2024-25).
- If month &lt; 4: FY = `(year-1)`–`year` (e.g. 2024-02-10 → FY 2023-24).

### Project Count by Derived FY (All Projects with Commencement)

| Financial Year | Project Count |
|----------------|---------------|
| 2021-22 | 1 |
| 2024-25 | 7 |
| 2025-26 | 117 |
| 2026-27 | 123 |

### Approved Projects by FY

| Financial Year | Project Count |
|----------------|---------------|
| 2024-25 | 1 |
| 2025-26 | 37 |
| 2026-27 | 6 |

---

## 7. Dashboard Impact Analysis

### Impact Scenarios

| Impact Scenario | Severity |
|-----------------|----------|
| FY dashboards filter by `commencement_month_year`; approved projects all have it | Low — no approved projects excluded |
| Non-approved projects (draft, forwarded) with null commencement excluded from FY totals | Low — FY dashboards typically include only approved projects |
| 10 non-approved projects excluded from FY breakdown | Low — no budget impact for approved aggregation |
| Future commencement dates (123 projects) used for FY derivation | None — valid; FY 2026-27 is correctly derived |

### Conclusion

- For approved projects (the main scope for FY budget dashboards), 100% have `commencement_month_year`.
- Impact of missing or invalid commencement on FY dashboards is negligible under current use.

---

## 8. Data Cleanup Strategy

### Current State

- No approved projects with null `commencement_month_year`.
- 10 non-approved projects have null commencement; no cleanup required for FY dashboards that aggregate approved projects only.

### If Future Cleanup Were Needed

| Option | Risk | Accuracy | Complexity |
|--------|------|----------|------------|
| **A — Backfill from approval date** | Low | Medium — may differ from actual commencement | Low — use `project_status_histories` or `updated_at` at approval |
| **B — Backfill from created_at** | Low | Low — creation ≠ commencement | Low |
| **C — Manual correction** | Low | High | High — human effort |

### Recommendation

- No cleanup required for current FY dashboard preparation.
- If FY dashboards later include non-approved projects, consider:
  - Excluding projects with null commencement from FY filter, or
  - Backfilling from approval/status-change date for non-approved projects that will be approved.

---

## 9. Final Verdict

### READY

**Reasoning**

1. **Schema:** `commencement_month_year` exists as `date` (nullable) with no validity issues observed.
2. **Approval flow:** Coordinator approval requires and sets commencement; approval without it is not possible.
3. **Data completeness:** 100% of approved projects have `commencement_month_year`.
4. **Legacy projects:** No approved projects with null commencement.
5. **FY derivation:** Logic is straightforward; simulation shows correct FY assignment; future dates are valid planned commencements.
6. **Dashboard impact:** FY dashboards that filter by `commencement_month_year` will include all approved projects.

**Minor Notes**

- 10 non-approved projects have null commencement; acceptable if FY dashboards include only approved projects.
- 123 projects have future commencement dates; these are valid and correctly derive to FY 2026-27.

---

*Audit completed in read-only mode. No database changes or code modifications were made.*
