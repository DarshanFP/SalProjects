# Production Phase 0.6 — Resolution Audit After Seeding

**Date:** 2026-02-15  
**Context:** Canonical societies inserted (Phase 0.5). No schema changes. No normalization done yet.  
**Objective:** Measure current resolution rate and identify mismatches (read-only).

---

## Step 1 — Project Resolution

**Query:**
```sql
SELECT
    COUNT(*) AS total_projects,
    SUM(CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END) AS resolved_projects,
    SUM(CASE WHEN s.id IS NULL THEN 1 ELSE 0 END) AS unresolved_projects
FROM projects p
LEFT JOIN societies s ON p.society_name = s.name;
```

**Result:**

| total_projects | resolved_projects | unresolved_projects |
|----------------|-------------------|----------------------|
| 203            | 134               | 69                   |

**Project resolution rate:** **66.0%** (134 / 203).

---

## Step 2 — Distinct Unresolved Project Names

**Query:**
```sql
SELECT DISTINCT p.society_name
FROM projects p
LEFT JOIN societies s ON p.society_name = s.name
WHERE s.id IS NULL
  AND p.society_name IS NOT NULL;
```

**Result (2 distinct names):**

| society_name |
|------------------------------------------|
| ST. ANNS'S SOCIETY, VISAKHAPATNAM |
| ST.ANN'S SOCIETY, SOUTHERN REGION |

---

## Step 3 — User Resolution

**Query:**
```sql
SELECT
    COUNT(*) AS total_users,
    SUM(CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END) AS resolved_users,
    SUM(CASE WHEN s.id IS NULL THEN 1 ELSE 0 END) AS unresolved_users
FROM users u
LEFT JOIN societies s ON u.society_name = s.name;
```

**Result:**

| total_users | resolved_users | unresolved_users |
|-------------|----------------|------------------|
| 142         | 85             | 57               |

**User resolution rate:** **59.9%** (85 / 142).

---

## Step 4 — Distinct Unresolved User Society Names

**Query:**
```sql
SELECT DISTINCT u.society_name
FROM users u
LEFT JOIN societies s ON u.society_name = s.name
WHERE s.id IS NULL
  AND u.society_name IS NOT NULL;
```

**Result (12 distinct names):**

| society_name |
|------------------------------------------|
| ST.ANN'S SOCIETY, SOUTHERN REGION |
| St. Ann's Society Southern Region |
| Generalate |
| None |
| rtyui |
| Wilhelm Meyers Development Society |
| SSCT |
| St. Ann's Society |
| St Anns Society |
| St.Anns Educational Society |
| St. Ann's Society Bangalore |
| ST. ANNS'S SOCIETY, VISAKHAPATNAM |

*(Note: Empty/NULL society_name users are excluded by the query; they are counted in unresolved_users.)*

---

## Step 5 — Southern Province Check

**Query (projects):**
```sql
SELECT COUNT(*)
FROM projects
WHERE society_name = 'ST. ANN''S SOCIETY, SOUTHERN PROVINCE';
```

**Result:** **0**

**Query (users):**
```sql
SELECT COUNT(*)
FROM users
WHERE society_name = 'ST. ANN''S SOCIETY, SOUTHERN PROVINCE';
```

**Result:** **0**

*(No rows use the literal "SOUTHERN PROVINCE"; canonical name is "ST.ANN'S SOCIETY, SOUTHERN REGION".)*

---

## Resolution Summary

| Entity  | Total | Resolved | Unresolved | Resolution % |
|---------|-------|----------|------------|---------------|
| Projects | 203 | 134 | 69 | **66.0%** |
| Users     | 142 | 85  | 57 | **59.9%** |

---

## List of Unresolved Names

**Projects (2):**

1. ST. ANNS'S SOCIETY, VISAKHAPATNAM *(typo: ANNS'S → ANN'S)*  
2. ST.ANN'S SOCIETY, SOUTHERN REGION *(may be spacing/collation vs seeded name)*  

**Users (12):**

1. ST.ANN'S SOCIETY, SOUTHERN REGION  
2. St. Ann's Society Southern Region  
3. Generalate  
4. None  
5. rtyui  
6. Wilhelm Meyers Development Society  
7. SSCT  
8. St. Ann's Society  
9. St Anns Society  
10. St.Anns Educational Society  
11. St. Ann's Society Bangalore  
12. ST. ANNS'S SOCIETY, VISAKHAPATNAM  

---

## Comparison Against Local Normalized State

| Metric | Production (Phase 0.6, post-seeding, no normalization) | Local (post–Phase 3 + normalization) |
|--------|--------------------------------------------------------|--------------------------------------|
| Project resolution | 66.0% (134/203) | **100%** |
| User resolution    | 59.9% (85/142)  | Higher (normalization applied) |
| Unresolved project names | 2 distinct | 0 (all normalized) |
| Unresolved user names    | 12 distinct | Reduced by normalization batches |

**Observation:** Local achieved 100% project resolution after deterministic name normalization (e.g. ST. ANNS'S → ST. ANN'S, St. Ann's Society Southern Region → ST.ANN'S SOCIETY, SOUTHERN REGION, SSCT → SARVAJANA SNEHA CHARITABLE TRUST). Production has not run normalization; remaining gaps are expected and align with those legacy/variant strings.

---

## Explicit statement

**No data modified.**

This audit was read-only. No migrations were run. No INSERT, UPDATE, or DELETE was executed.

---

**STOP. Do not run migrations. Do not update any data.**
