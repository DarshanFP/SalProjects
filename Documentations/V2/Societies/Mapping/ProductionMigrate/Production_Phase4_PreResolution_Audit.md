# Production — Pre-Phase 4 Society Resolution Audit

**Date:** 2026-02-15  
**Purpose:** Verify that all `society_name` values are fully normalized before introducing `projects.society_id`.  
**Mode:** READ-ONLY. No schema changes. No data updates.

---

## Step 1 — Distinct project society_name values

**Query:**
```sql
SELECT DISTINCT society_name
FROM projects
ORDER BY society_name;
```

**Result (6 distinct values):**

| society_name |
|------------------------------------------|
| SARVAJANA SNEHA CHARITABLE TRUST |
| ST. ANN'S EDUCATIONAL SOCIETY |
| ST. ANN'S SOCIETY, BANGLORE |
| ST. ANN'S SOCIETY, VISAKHAPATNAM |
| ST. ANNE'S SOCIETY |
| WILHELM MEYERS DEVELOPMENTAL SOCIETY |

---

## Step 2 — Resolution check (projects)

**Query:**
```sql
SELECT
    COUNT(DISTINCT p.society_name) AS distinct_project_societies,
    COUNT(DISTINCT s.name) AS resolvable_societies
FROM projects p
LEFT JOIN societies s ON p.society_name = s.name;
```

**Result:**

| distinct_project_societies | resolvable_societies |
|---------------------------|----------------------|
| 6                         | 6                    |

---

## Step 3 — Unresolved project society_name

**Query:**
```sql
SELECT DISTINCT p.society_name
FROM projects p
LEFT JOIN societies s ON p.society_name = s.name
WHERE s.id IS NULL OR p.society_name IS NULL OR p.society_name = '';
```

**Result:** **0 rows** — no unresolved project society_name values.

---

## Step 4 — NULL / empty check (projects)

**Query:**
```sql
SELECT COUNT(*) AS null_or_empty_projects
FROM projects
WHERE society_name IS NULL OR society_name = '';
```

**Result:** **null_or_empty_projects = 0**

---

## Step 5 — Unresolved user society_name

**Query:**
```sql
SELECT DISTINCT u.society_name
FROM users u
LEFT JOIN societies s ON u.society_name = s.name
WHERE s.id IS NULL OR u.society_name IS NULL OR u.society_name = '';
```

**Result:** **0 rows** — no unresolved user society_name values.

---

## Step 6 — Resolution percentage

**Query:**
```sql
SELECT
    ROUND(
        (COUNT(DISTINCT s.name) / COUNT(DISTINCT p.society_name)) * 100,
        2
    ) AS resolution_rate_percent
FROM projects p
LEFT JOIN societies s ON p.society_name = s.name;
```

**Result:** **resolution_rate_percent = 100.00**

---

## Step 7 — Interpretation

| Question | Answer |
|----------|--------|
| **1) Total distinct project society_name values** | **6** |
| **2) How many resolve to societies.name** | **6** (all of them) |
| **3) Resolution rate %** | **100%** |
| **4) Any unresolved project values?** | **No** (0 rows) |
| **5) Any NULL or empty project values?** | **No** (0 projects) |
| **6) Any unresolved user values?** | **No** (0 rows) |

### Classification

**A) 100% resolution → Safe for Phase 4**

All distinct project and user `society_name` values resolve to a row in `societies`. No NULL or empty project society_name. Safe to introduce `projects.society_id` and backfill from `society_name` (Phase 4).

---

## Summary table

| Metric | Value |
|--------|--------|
| Distinct project society_name | 6 |
| Resolvable to societies.name | 6 |
| Resolution rate % | **100.00** |
| Unresolved project society_name | 0 |
| NULL/empty project society_name | 0 |
| Unresolved user society_name | 0 |
| **Classification** | **A — Safe for Phase 4** |

---

## Explicit statement

**Read-only audit. No changes performed.**

No schema or data was modified. No migrations were run. This document only records the current resolution state of `society_name` for projects and users.

---

**STOP. Do not modify data. Do not create migrations.**
