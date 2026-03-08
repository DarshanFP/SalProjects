# Legacy Approved Project Financial Repair Audit

**Date:** 2026-03-04  
**Scope:** Approved projects — financial invariant violations  
**Objective:** Identify approved projects approved before the financial invariant system was introduced and whose financial fields are inconsistent with the current architecture.  
**Method:** Database query, invariant rule checks, resolver simulation, dashboard flow trace. **No application code or database records were modified.**

---

## 1️⃣ Approved Project Statistics

| Metric | Value |
|--------|-------|
| **Total approved projects** | 44 |
| **Projects with no invariant violations** | 16 |
| **Projects with at least one violation** | 28 |
| **Projects with `commencement_month_year` NULL** | 0 |
| **Projects with `commencement_month`/`commencement_year` but NULL `commencement_month_year`** | 0 |

### Status distribution

All 44 projects have status `approved_by_coordinator` (no `approved_by_general_as_coordinator` or `approved_by_general_as_provincial` in the current dataset).

---

## 2️⃣ Invariant Violation Summary

### Rules applied

| Rule | Description | Violations |
|------|-------------|------------|
| **A** | `amount_sanctioned <= 0` | 11 projects |
| **B** | `opening_balance = 0` AND `overall_project_budget > 0` | 10 projects |
| **C** | `opening_balance IS NULL` | 13 projects |
| **D** | `commencement_month_year IS NULL` | 0 projects |
| **E** | `amount_sanctioned != opening_balance` (investigation only) | 12 projects |

### Violation classification by type

| Violation Type | Count | Projects |
|----------------|-------|----------|
| `opening_balance_missing` (C) | 13 | DP-0001, CIC-0001, DP-0002, DP-0003, DP-0005, DP-0006, DP-0007, DP-0008, DP-0009, DP-0016, DP-0017, DP-0020, DP-0022 |
| `amount_sanctioned_missing` (A, overall > 0) | 3 | DP-0041, IIES-0060, DP-0064 |
| `opening_balance_incorrect` (B) | 10 | GEN-0005, GEN-0007, DP-0055, DP-0057, DP-0058, DP-0059, DP-0060, DP-0062, DP-0064 |
| `amount_sanctioned_zero` (A, overall = 0) | 6 | GEN-0006, DP-0056, DP-0061, DP-0063, DP-0065, DP-0067, DP-0069 |
| `sanction_balance_mismatch` (E, by design) | 2 | DP-0024, DP-0025 |
| `commencement_date_missing` (D) | 0 | — |

### Classification table (violating projects)

| project_id | violation_type | current_values | expected_values |
|------------|----------------|----------------|-----------------|
| DP-0001 | opening_balance_missing | sanctioned=789000, opening=NULL | opening_balance=789000 |
| CIC-0001 | opening_balance_missing | sanctioned=1896000, opening=NULL | opening_balance=1896000 |
| DP-0002 | opening_balance_missing | sanctioned=1428000, opening=NULL | opening_balance=1428000 |
| DP-0003 | opening_balance_missing | sanctioned=613600, opening=NULL | opening_balance=613600 |
| DP-0005 | opening_balance_missing | sanctioned=1445000, opening=NULL | opening_balance=1445000 |
| DP-0006 | opening_balance_missing | sanctioned=1680000, opening=NULL | opening_balance=1680000 |
| DP-0007 | opening_balance_missing | sanctioned=1259400, opening=NULL | opening_balance=1259400 |
| DP-0008 | opening_balance_missing | sanctioned=520000, opening=NULL | opening_balance=520000 |
| DP-0009 | opening_balance_missing | sanctioned=629500, opening=NULL | opening_balance=629500 |
| DP-0016 | opening_balance_missing | sanctioned=998200, opening=NULL | opening_balance=998200 |
| DP-0017 | opening_balance_missing | sanctioned=1412000, opening=NULL | opening_balance=1412000 |
| DP-0020 | opening_balance_missing | sanctioned=929000, opening=NULL | opening_balance=929000 |
| DP-0022 | opening_balance_missing | sanctioned=659000, opening=NULL | opening_balance=659000 |
| DP-0024 | sanction_balance_mismatch | sanctioned=775000, opening=1040000, forwarded=265000 | Intentional (forwarded contribution) |
| DP-0025 | sanction_balance_mismatch | sanctioned=1100000, opening=1830000, forwarded=730000 | Intentional (forwarded contribution) |
| DP-0041 | amount_sanctioned_missing | sanctioned=NULL, opening=630000, forwarded=0, local=630000 | Individual type; sanctioned semantics differ |
| IIES-0060 | amount_sanctioned_missing | sanctioned=NULL, opening=16000, forwarded=0, local=16000 | Individual type; sanctioned semantics differ |
| GEN-0005 | opening_balance_incorrect | sanctioned=100000, opening=0, overall=100000 | opening_balance=100000 |
| GEN-0006 | amount_sanctioned_zero | sanctioned=0, opening=0, overall=0 | Zero-budget project; may be intentional |
| GEN-0007 | opening_balance_incorrect | sanctioned=100000, opening=0, overall=100000 | opening_balance=100000 |
| DP-0055 | opening_balance_incorrect | sanctioned=100000, opening=0, overall=100000 | opening_balance=100000 |
| DP-0056 | amount_sanctioned_zero | sanctioned=0, opening=0, overall=0 | Zero-budget project; may be intentional |
| DP-0057 | opening_balance_incorrect | sanctioned=100000, opening=0, overall=100000 | opening_balance=100000 |
| DP-0058 | opening_balance_incorrect | sanctioned=100000, opening=0, overall=100000 | opening_balance=100000 |
| DP-0059 | opening_balance_incorrect | sanctioned=100000, opening=0, overall=100000 | opening_balance=100000 |
| DP-0060 | opening_balance_incorrect | sanctioned=100000, opening=0, overall=100000 | opening_balance=100000 |
| DP-0061 | amount_sanctioned_zero | sanctioned=0, opening=0, overall=0 | Zero-budget project; may be intentional |
| DP-0062 | opening_balance_incorrect | sanctioned=100000, opening=0, overall=100000 | opening_balance=100000 |
| DP-0063 | amount_sanctioned_zero | sanctioned=0, opening=0, overall=0 | Zero-budget project; may be intentional |
| DP-0064 | amount_sanctioned_missing, opening_balance_incorrect | sanctioned=0, opening=0, overall=100000 | sanctioned=100000, opening=100000 |
| DP-0065 | amount_sanctioned_zero | sanctioned=0, opening=0, overall=0 | Zero-budget project; may be intentional |
| DP-0067 | amount_sanctioned_zero | sanctioned=0, opening=0, overall=0 | Zero-budget project; may be intentional |
| DP-0069 | amount_sanctioned_zero | sanctioned=0, opening=0, overall=0 | Zero-budget project; may be intentional |

---

## 3️⃣ Projects Requiring Auto-Repair

### CASE 1: `amount_sanctioned = 0` AND `overall_project_budget > 0` (no forwarded/local)

| project_id | overall | sanctioned | opening | Suggested repair |
|------------|---------|------------|---------|------------------|
| DP-0064 | 100000 | 0 | 0 | `amount_sanctioned = 100000`, `opening_balance = 100000` |

**Auto-repair:** Yes, if `amount_forwarded = 0` and `local_contribution = 0`.

---

### CASE 2: `opening_balance = 0` (or NULL) AND `amount_sanctioned > 0`

| project_id | sanctioned | opening | forwarded | local | Suggested repair |
|------------|------------|---------|-----------|-------|------------------|
| DP-0001 | 789000 | NULL | 0 | 0 | `opening_balance = 789000` |
| CIC-0001 | 1896000 | NULL | 0 | 0 | `opening_balance = 1896000` |
| DP-0002 | 1428000 | NULL | 0 | 0 | `opening_balance = 1428000` |
| DP-0003 | 613600 | NULL | 0 | 0 | `opening_balance = 613600` |
| DP-0005 | 1445000 | NULL | 0 | 0 | `opening_balance = 1445000` |
| DP-0006 | 1680000 | NULL | 0 | 0 | `opening_balance = 1680000` |
| DP-0007 | 1259400 | NULL | 0 | 0 | `opening_balance = 1259400` |
| DP-0008 | 520000 | NULL | 0 | 0 | `opening_balance = 520000` |
| DP-0009 | 629500 | NULL | 0 | 0 | `opening_balance = 629500` |
| DP-0016 | 998200 | NULL | 0 | 0 | `opening_balance = 998200` |
| DP-0017 | 1412000 | NULL | 0 | 0 | `opening_balance = 1412000` |
| DP-0020 | 929000 | NULL | 0 | 0 | `opening_balance = 929000` |
| DP-0022 | 659000 | NULL | 0 | 0 | `opening_balance = 659000` |
| GEN-0005 | 100000 | 0 | 0 | 0 | `opening_balance = 100000` |
| GEN-0007 | 100000 | 0 | 0 | 0 | `opening_balance = 100000` |
| DP-0055 | 100000 | 0 | 0 | 0 | `opening_balance = 100000` |
| DP-0057 | 100000 | 0 | 0 | 0 | `opening_balance = 100000` |
| DP-0058 | 100000 | 0 | 0 | 0 | `opening_balance = 100000` |
| DP-0059 | 100000 | 0 | 0 | 0 | `opening_balance = 100000` |
| DP-0060 | 100000 | 0 | 0 | 0 | `opening_balance = 100000` |
| DP-0062 | 100000 | 0 | 0 | 0 | `opening_balance = 100000` |

**Auto-repair:** Yes, when `amount_forwarded + local_contribution = 0`. Set `opening_balance = amount_sanctioned`.

---

### CASE 3: `commencement_month_year` NULL but `commencement_month` and `commencement_year` exist

**Result:** No projects found. All 44 approved projects have `commencement_month_year` populated.

---

### Summary of auto-repair candidates

| Repair type | Count | Projects |
|-------------|-------|----------|
| Set `opening_balance = amount_sanctioned` (forwarded=0, local=0) | 20 | DP-0001, CIC-0001, DP-0002, DP-0003, DP-0005, DP-0006, DP-0007, DP-0008, DP-0009, DP-0016, DP-0017, DP-0020, DP-0022, GEN-0005, GEN-0007, DP-0055, DP-0057, DP-0058, DP-0059, DP-0060, DP-0062 |
| Set `amount_sanctioned = overall`, `opening_balance = overall` | 1 | DP-0064 |

**Total auto-repair candidates:** 21 projects.

---

## 4️⃣ Projects Requiring Manual Review

| project_id | Reason |
|------------|--------|
| **DP-0024** | sanction ≠ opening by design (forwarded=265000). No repair needed. |
| **DP-0025** | sanction ≠ opening by design (forwarded=730000). No repair needed. |
| **DP-0041** | Individual type. sanctioned=NULL, opening=630000 (forwarded+local). Individual projects may have different sanctioned semantics. |
| **IIES-0060** | Individual type. sanctioned=NULL, opening=16000 (forwarded+local). Same as above. |
| **GEN-0006** | Zero-budget (overall=0, sanctioned=0, opening=0). Intentional? |
| **DP-0056** | Zero-budget. Intentional? |
| **DP-0061** | Zero-budget. Intentional? |
| **DP-0063** | Zero-budget. Intentional? |
| **DP-0065** | Zero-budget. Intentional? |
| **DP-0067** | Zero-budget. Intentional? |
| **DP-0069** | Zero-budget. Intentional? |

**Action:** Confirm with product/business whether zero-budget projects are valid and whether DP-0041/IIES-0060 sanctioned semantics should be aligned with the invariant system.

---

## 5️⃣ Resolver Impact Analysis

The `ProjectFinancialResolver` applies **canonical separation** for approved projects:

```php
'opening_balance' => (float) ($project->opening_balance ?? 0)
```

So when `opening_balance` is NULL, the resolver returns **0**.

### Resolver vs DB comparison (projects where DB and resolver differ)

| project_id | DB_opening_balance | resolver_opening_balance | Impact |
|------------|--------------------|--------------------------|--------|
| DP-0001 | NULL | 0 | Dashboard shows 0 instead of 789000 |
| CIC-0001 | NULL | 0 | Dashboard shows 0 instead of 1896000 |
| DP-0002 | NULL | 0 | Dashboard shows 0 instead of 1428000 |
| DP-0003 | NULL | 0 | Dashboard shows 0 instead of 613600 |
| DP-0005 | NULL | 0 | Dashboard shows 0 instead of 1445000 |
| DP-0006 | NULL | 0 | Dashboard shows 0 instead of 1680000 |
| DP-0007 | NULL | 0 | Dashboard shows 0 instead of 1259400 |
| DP-0008 | NULL | 0 | Dashboard shows 0 instead of 520000 |
| DP-0009 | NULL | 0 | Dashboard shows 0 instead of 629500 |
| DP-0016 | NULL | 0 | Dashboard shows 0 instead of 998200 |
| DP-0017 | NULL | 0 | Dashboard shows 0 instead of 1412000 |
| DP-0020 | NULL | 0 | Dashboard shows 0 instead of 929000 |
| DP-0022 | NULL | 0 | Dashboard shows 0 instead of 659000 |
| GEN-0005 | 0 | 0 | Resolver reflects DB; expected 100000 |
| GEN-0007 | 0 | 0 | Resolver reflects DB; expected 100000 |
| DP-0055, DP-0057, DP-0058, DP-0059, DP-0060, DP-0062 | 0 | 0 | Same pattern |
| DP-0064 | 0 | 0 | Resolver reflects DB; expected 100000 |

**Conclusion:** The resolver **does not** infer a fallback when `opening_balance` is NULL. It returns 0, so incorrect DB data is propagated to dashboards. Projects with correct `opening_balance` (e.g. DP-0004, DP-0012, DP-0024, DP-0025, DP-0041, IIES-0060, DP-0066) show correct values.

---

## 6️⃣ Dashboard Impact Summary

### Executor dashboard

- **Data source:** `ProjectQueryService::getApprovedOwnedProjectsForUser($user, $with, $fy)` → `calculateBudgetSummariesFromProjects()`
- **Aggregation:** Uses `resolver->resolve($project)['opening_balance']` for `total_budget`
- **Impact:** 13 projects with NULL `opening_balance` contribute **0** to total budget even though they have non-zero `amount_sanctioned`. 8 projects with `opening_balance = 0` but `amount_sanctioned > 0` also contribute 0. **21 projects** cause total budget to be understated.

### Coordinator dashboard

- **Data source:** Project queries with `inFinancialYear($fy)`; budget summaries via `calculateBudgetSummariesFromProjects()` or similar flows
- **Aggregation:** Same resolver `opening_balance`
- **Impact:** System-wide totals (by province, project type, etc.) are understated by the same projects.

### Provincial dashboard

- **Data source:** Projects accessible to provincial user; `calculateBudgetSummariesFromProjects()` for budget widgets
- **Aggregation:** Same resolver `opening_balance`
- **Impact:** Province-level and center-level totals are understated where affected projects fall in scope.

### Aggregate understatement (approximate)

| Category | Projects | Sanctioned/expected opening sum |
|----------|----------|---------------------------------|
| opening_balance NULL | 13 | ~13.5M (approx) |
| opening_balance = 0, sanctioned > 0 | 8 | ~800,000 |
| sanctioned = 0, overall > 0 | 1 | 100,000 |

**Total estimated understatement:** ~14.4M (approximate; excludes Individual-type nuance).

---

*Audit completed without modifying any code or database records. All findings are from query results and resolver simulation.*
