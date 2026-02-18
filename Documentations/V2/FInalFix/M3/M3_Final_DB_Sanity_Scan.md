# M3 — Final DB Sanity Scan

**Mode:** Audit only — no code or DB changes.  
**Purpose:** Verify database state alignment with M3 canonical rules.

---

## Canonical Rules (Reference)

- **Non-approved projects:** `amount_sanctioned` MUST be 0 (or NULL treated as 0 for display; canonical target is 0).
- **Approved projects:** `amount_sanctioned` MUST be > 0; `opening_balance` MUST NOT be NULL.

---

## STEP 1 — Approved Statuses

Approved status is defined by `ProjectStatus::isApproved($status)` / `Project::isApproved()`, which uses `ProjectStatus::APPROVED_STATUSES`:

| Status constant | Value |
|-----------------|--------|
| `APPROVED_BY_COORDINATOR` | `'approved_by_coordinator'` |
| `APPROVED_BY_GENERAL_AS_COORDINATOR` | `'approved_by_general_as_coordinator'` |
| `APPROVED_BY_GENERAL_AS_PROVINCIAL` | `'approved_by_general_as_provincial'` |

**Source:** `app/Constants/ProjectStatus.php` (lines 35–38, 104–106).  
**Non-approved:** Any `status` NOT IN these three values.

**SQL list of approved statuses (for use in queries):**
- `'approved_by_coordinator'`
- `'approved_by_general_as_coordinator'`
- `'approved_by_general_as_provincial'`

---

## STEP 2 — SQL Audit Queries

Table: `projects`. Columns used: `status`, `amount_sanctioned`, `opening_balance`, `overall_project_budget`, `amount_forwarded`, `local_contribution`.  
Run these against your database and record the result counts.

---

### A) Non-approved projects with sanctioned > 0

**Rule violated:** Non-approved must have `amount_sanctioned = 0`.

```sql
SELECT COUNT(*) AS violation_count
FROM projects
WHERE status NOT IN (
    'approved_by_coordinator',
    'approved_by_general_as_coordinator',
    'approved_by_general_as_provincial'
)
AND (
    amount_sanctioned IS NOT NULL
    AND (amount_sanctioned > 0 OR amount_sanctioned < 0)
);
```

*Optional detail (list rows):*
```sql
SELECT id, project_id, status, amount_sanctioned, opening_balance, overall_project_budget
FROM projects
WHERE status NOT IN (
    'approved_by_coordinator',
    'approved_by_general_as_coordinator',
    'approved_by_general_as_provincial'
)
AND amount_sanctioned IS NOT NULL
AND amount_sanctioned != 0;
```

---

### B) Approved projects with sanctioned <= 0

**Rule violated:** Approved must have `amount_sanctioned > 0`.

```sql
SELECT COUNT(*) AS violation_count
FROM projects
WHERE status IN (
    'approved_by_coordinator',
    'approved_by_general_as_coordinator',
    'approved_by_general_as_provincial'
)
AND (amount_sanctioned IS NULL OR amount_sanctioned <= 0);
```

*Optional detail:*
```sql
SELECT id, project_id, status, amount_sanctioned, opening_balance, overall_project_budget
FROM projects
WHERE status IN (
    'approved_by_coordinator',
    'approved_by_general_as_coordinator',
    'approved_by_general_as_provincial'
)
AND (amount_sanctioned IS NULL OR amount_sanctioned <= 0);
```

---

### C) Approved projects with opening_balance IS NULL

**Rule violated:** Approved must have non-NULL `opening_balance`.

```sql
SELECT COUNT(*) AS violation_count
FROM projects
WHERE status IN (
    'approved_by_coordinator',
    'approved_by_general_as_coordinator',
    'approved_by_general_as_provincial'
)
AND opening_balance IS NULL;
```

*Optional detail:*
```sql
SELECT id, project_id, status, amount_sanctioned, opening_balance, overall_project_budget
FROM projects
WHERE status IN (
    'approved_by_coordinator',
    'approved_by_general_as_coordinator',
    'approved_by_general_as_provincial'
)
AND opening_balance IS NULL;
```

---

### D) Any project where opening_balance < 0

**Rule violated:** Opening balance must not be negative.

```sql
SELECT COUNT(*) AS violation_count
FROM projects
WHERE opening_balance IS NOT NULL AND opening_balance < 0;
```

*Optional detail:*
```sql
SELECT id, project_id, status, amount_sanctioned, opening_balance, overall_project_budget
FROM projects
WHERE opening_balance IS NOT NULL AND opening_balance < 0;
```

---

### E) Any project where overall_project_budget < 0

**Rule violated:** Overall budget must not be negative.

```sql
SELECT COUNT(*) AS violation_count
FROM projects
WHERE overall_project_budget IS NOT NULL AND overall_project_budget < 0;
```

*Optional detail:*
```sql
SELECT id, project_id, status, overall_project_budget, amount_forwarded, local_contribution, amount_sanctioned, opening_balance
FROM projects
WHERE overall_project_budget IS NOT NULL AND overall_project_budget < 0;
```

---

### F) Any project where amount_forwarded < 0

**Rule violated:** Amount forwarded must not be negative.

```sql
SELECT COUNT(*) AS violation_count
FROM projects
WHERE amount_forwarded IS NOT NULL AND amount_forwarded < 0;
```

*Optional detail:*
```sql
SELECT id, project_id, status, amount_forwarded, overall_project_budget, amount_sanctioned, opening_balance
FROM projects
WHERE amount_forwarded IS NOT NULL AND amount_forwarded < 0;
```

---

### G) Any project where local_contribution < 0

**Rule violated:** Local contribution must not be negative.

```sql
SELECT COUNT(*) AS violation_count
FROM projects
WHERE local_contribution IS NOT NULL AND local_contribution < 0;
```

*Optional detail:*
```sql
SELECT id, project_id, status, local_contribution, overall_project_budget, amount_sanctioned, opening_balance
FROM projects
WHERE local_contribution IS NOT NULL AND local_contribution < 0;
```

---

## STEP 3 — Result Counts (To Be Filled)

Run each query and record counts below. Replace `?` with actual numbers after running the audit.

| Query | Description | Result count | Risk |
|-------|-------------|--------------|------|
| A | Non-approved with sanctioned != 0 | ? | High — resolver expects 0; display/aggregation may be wrong until cleanup. |
| B | Approved with sanctioned <= 0 or NULL | ? | High — approval flow expects sanctioned > 0. |
| C | Approved with opening_balance NULL | ? | High — utilization/remaining calculations can fail. |
| D | opening_balance < 0 | ? | High — invalid financial state. |
| E | overall_project_budget < 0 | ? | Medium — invalid budget. |
| F | amount_forwarded < 0 | ? | Medium — invalid. |
| G | local_contribution < 0 | ? | Medium — invalid. |

---

## Risk Classification

| Finding | Risk level | Impact |
|--------|------------|--------|
| A (non-approved, sanctioned > 0) | **High** | Resolver and Phase 2 dashboards treat non-approved sanctioned as 0; DB still has old value. Cleanup migration should set `amount_sanctioned = 0` for non-approved. |
| B (approved, sanctioned <= 0) | **High** | Approval flow should have set sanctioned; reports/dashboards may show 0 for approved projects. May need backfill from `overall_project_budget` or manual correction. |
| C (approved, opening_balance NULL) | **High** | Budget totals and utilization use opening_balance; NULL can cause errors or wrong totals. |
| D, E, F, G (negative values) | **Medium–High** | Data integrity; should be corrected (constraints or cleanup). |

---

## Cleanup Migration Required?

- **If A > 0:** Yes — recommend a data migration that sets `projects.amount_sanctioned = 0` where `status NOT IN (approved statuses)`. Run after backup; optional: log affected `project_id`s.
- **If B > 0 or C > 0:** Yes — approved projects must have sanctioned > 0 and opening_balance NOT NULL. Options: (1) Run existing or similar logic to backfill from resolver/budget (e.g. one-time script or migration), or (2) Manual review and correction.
- **If D, E, F, or G > 0:** Recommend corrective updates (or constraints to prevent future negatives); scope as data fix or migration.

**This document does not apply any changes. Run the queries, fill the counts, then decide on cleanup in a separate change.**

---

## Summary

- **Approved statuses:** `approved_by_coordinator`, `approved_by_general_as_coordinator`, `approved_by_general_as_provincial`.
- **Seven audit queries** (A–G) are provided above; run them and record counts in the result table.
- **Risk:** A, B, C are high (canonical M3 rules); D–G are integrity/negative-value issues.
- **Cleanup:** Required if any of A, B, or C return rows; recommended for D–G if any return rows. No changes applied in this audit.

---

**M3 DB Sanity Scan Complete — No Changes Applied**
