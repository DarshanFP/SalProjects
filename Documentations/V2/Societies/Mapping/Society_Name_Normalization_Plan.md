# Society Name Normalization Plan

**Objective:** Design a controlled normalization plan for `society_name` before introducing `society_id`. Analysis only. No updates executed. No schema changes.

---

## Section 1 — Current Resolution Status

### Resolution Rates (Sample Results)

| Source | Total with society_name | Matched to societies.name | Resolution Rate |
|--------|-------------------------|---------------------------|-----------------|
| projects | 135 | 120 | 88.9% |
| users | 128 | 113 | 88.3% |

### SQL Queries

```sql
-- Project resolution rate
SELECT
  COUNT(*) AS total_with_society_name,
  (SELECT COUNT(*) FROM projects p2
   INNER JOIN societies s ON p2.society_name = s.name
   WHERE p2.society_name IS NOT NULL AND p2.society_name != '') AS matched,
  ROUND(100.0 * (SELECT COUNT(*) FROM projects p2
   INNER JOIN societies s ON p2.society_name = s.name
   WHERE p2.society_name IS NOT NULL AND p2.society_name != '')
   / NULLIF(COUNT(*), 0), 1) AS resolution_pct
FROM projects
WHERE society_name IS NOT NULL AND society_name != '';

-- User resolution rate (analogous)
SELECT
  COUNT(*) AS total_with_society_name,
  (SELECT COUNT(*) FROM users u2
   INNER JOIN societies s ON u2.society_name = s.name
   WHERE u2.society_name IS NOT NULL AND u2.society_name != '') AS matched
FROM users
WHERE society_name IS NOT NULL AND society_name != '';
```

### Distinct project.society_name NOT Matching societies.name

```sql
SELECT DISTINCT p.society_name
FROM projects p
LEFT JOIN societies s ON p.society_name = s.name
WHERE p.society_name IS NOT NULL AND p.society_name != ''
  AND s.id IS NULL;
```

| society_name | projects_count |
|--------------|----------------|
| ST. ANNS'S SOCIETY, VISAKHAPATNAM | 15 |

### Distinct user.society_name NOT Matching societies.name

```sql
SELECT DISTINCT u.society_name, COUNT(*) AS user_count
FROM users u
LEFT JOIN societies s ON u.society_name = s.name
WHERE u.society_name IS NOT NULL AND u.society_name != ''
  AND s.id IS NULL
GROUP BY u.society_name;
```

| society_name | user_count |
|--------------|------------|
| Generalate | 1 |
| None | 1 |
| rtyui | 1 |
| SSCT | 1 |
| St Anns Society | 1 |
| St. Ann's Society | 1 |
| St. Ann's Society Bangalore | 1 |
| St. Ann's Society Southern Region | 2 |
| ST. ANNS'S SOCIETY, VISAKHAPATNAM | 1 |
| St.Anns Educational Society | 4 |
| Wilhelm Meyers Development Society | 1 |

### Canonical societies.name Reference (Sample)

```
BIARA SANTA ANNA, MAUSAMBI
MISSIONARY SISTERS OF ST. ANN
SARVAJANA SNEHA CHARITABLE TRUST
ST. ANN'S CONVENT, LURO
ST. ANN'S EDUCATIONAL SOCIETY
ST. ANN'S SOCIETY, VISAKHAPATNAM
ST. ANNE'S SOCIETY
ST. ANNE'S SOCIETY, DIVYODAYA
ST.ANN'S SOCIETY, SOUTHERN REGION
WILHELM MEYERS DEVELOPMENTAL SOCIETY
```

---

## Section 2 — Categorization

| Legacy Value | Category | Notes |
|--------------|----------|-------|
| ST. ANNS'S SOCIETY, VISAKHAPATNAM | **1) Typo** | Extra "S" in ANNS'S → ANN'S |
| St.Anns Educational Society | **2) Casing variant** | Normalize to ST. ANN'S EDUCATIONAL SOCIETY |
| St Anns Society | **2) Casing variant** | Normalize to canonical St. Ann's variant |
| St. Ann's Society | **2) Casing variant** | Ambiguous; may need manual disambiguation |
| St. Ann's Society Southern Region | **2) Casing variant** | Maps to ST.ANN'S SOCIETY, SOUTHERN REGION |
| St. Ann's Society Bangalore | **4) Alternate wording** | No exact canonical; manual review |
| Wilhelm Meyers Development Society | **1) Typo** | Missing "al" → DEVELOPMENTAL |
| SSCT | **3) Abbreviation** | Acronym for SARVAJANA SNEHA CHARITABLE TRUST |
| Generalate | **5) Garbage / invalid** | Not a society; headquarters designation |
| None | **5) Garbage / invalid** | Placeholder/null indicator |
| rtyui | **5) Garbage / invalid** | Test or erroneous input |

---

## Section 3 — Canonical Mapping Table

| Legacy Value | Canonical societies.name | Action Type |
|--------------|--------------------------|-------------|
| ST. ANNS'S SOCIETY, VISAKHAPATNAM | ST. ANN'S SOCIETY, VISAKHAPATNAM | Direct replace |
| St.Anns Educational Society | ST. ANN'S EDUCATIONAL SOCIETY | Direct replace |
| Wilhelm Meyers Development Society | WILHELM MEYERS DEVELOPMENTAL SOCIETY | Direct replace |
| SSCT | SARVAJANA SNEHA CHARITABLE TRUST | Direct replace |
| St. Ann's Society Southern Region | ST.ANN'S SOCIETY, SOUTHERN REGION | Direct replace |
| St Anns Society | ST. ANN'S SOCIETY, VISAKHAPATNAM (or other) | Manual review required |
| St. Ann's Society | ST. ANN'S EDUCATIONAL SOCIETY (or other) | Manual review required |
| St. Ann's Society Bangalore | — | Manual review required |
| Generalate | — | Leave unchanged |
| None | — | Leave unchanged |
| rtyui | — | Leave unchanged |

---

## Section 4 — Update Strategy

### Controlled Update Plan

1. **Update projects table first** — Lower row count; validate before users.
2. **Update users table second** — Apply same mappings after project verification.
3. **Perform updates in batches** — One mapping per batch; log counts.
4. **Log affected row counts** — Store before/after per batch for rollback.
5. **Wrap in transaction (if safe)** — Single-table updates per batch; avoid cross-table transactions to reduce lock scope.

### Sample SQL Templates (DO NOT EXECUTE)

```sql
-- Batch 1: Typo fix (projects)
-- Pre-log: SELECT COUNT(*) FROM projects WHERE society_name = 'ST. ANNS''S SOCIETY, VISAKHAPATNAM';
UPDATE projects
SET society_name = 'ST. ANN''S SOCIETY, VISAKHAPATNAM'
WHERE society_name = 'ST. ANNS''S SOCIETY, VISAKHAPATNAM';
-- Post-log: affected row count

-- Batch 2: Typo fix (users)
UPDATE users
SET society_name = 'ST. ANN''S SOCIETY, VISAKHAPATNAM'
WHERE society_name = 'ST. ANNS''S SOCIETY, VISAKHAPATNAM';

-- Batch 3: Casing variant (users)
UPDATE users
SET society_name = 'ST. ANN''S EDUCATIONAL SOCIETY'
WHERE society_name = 'St.Anns Educational Society';

-- Batch 4: Abbreviation (users)
UPDATE users
SET society_name = 'SARVAJANA SNEHA CHARITABLE TRUST'
WHERE society_name = 'SSCT';

-- Batch 5: Typo (users)
UPDATE users
SET society_name = 'WILHELM MEYERS DEVELOPMENTAL SOCIETY'
WHERE society_name = 'Wilhelm Meyers Development Society';

-- Batch 6: Casing variant (users)
UPDATE users
SET society_name = 'ST.ANN''S SOCIETY, SOUTHERN REGION'
WHERE society_name = 'St. Ann''s Society Southern Region';
```

**Note:** Values marked "Manual review required" or "Leave unchanged" are excluded from automated update templates.

---

## Section 5 — Verification Queries

### Post-Update Resolution Rate Check

```sql
-- Projects
SELECT
  COUNT(*) AS total,
  SUM(CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END) AS matched,
  ROUND(100.0 * SUM(CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 1) AS resolution_pct
FROM projects p
LEFT JOIN societies s ON p.society_name = s.name
WHERE p.society_name IS NOT NULL AND p.society_name != '';

-- Users (analogous)
SELECT
  COUNT(*) AS total,
  SUM(CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END) AS matched,
  ROUND(100.0 * SUM(CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 1) AS resolution_pct
FROM users u
LEFT JOIN societies s ON u.society_name = s.name
WHERE u.society_name IS NOT NULL AND u.society_name != '';
```

### Check for Unintended Changes

```sql
-- Values that existed before and should remain (sample)
SELECT society_name, COUNT(*) FROM projects GROUP BY society_name;
SELECT society_name, COUNT(*) FROM users GROUP BY society_name;
```

### Count Comparison Before/After

| Metric | Before | After (target) |
|--------|--------|----------------|
| Projects unmatched | 15 | 0 (direct-replace only) |
| Users unmatched | 15 | ≤4 (manual/garbage excluded) |

---

## Section 6 — Rollback Plan

### Option A — Restore from Backup

1. Take full backup before first batch.
2. If verification fails, restore from backup.
3. Document backup path and timestamp.

### Option B — Reverse Using Logged Values

1. Before each batch, log: `(table, old_value, new_value, row_count)`.
2. Reverse each batch with inverse UPDATE using logged values.
3. Example:

```sql
-- Reverse Batch 1 (example)
UPDATE projects
SET society_name = 'ST. ANNS''S SOCIETY, VISAKHAPATNAM'
WHERE society_name = 'ST. ANN''S SOCIETY, VISAKHAPATNAM'
  AND <optional: id IN (logged_ids)>;
```

---

## Section 7 — Exit Criteria

| Criterion | Target |
|-----------|--------|
| Project resolution rate | ≥ 99% |
| User resolution rate (excl. garbage/manual) | ≥ 99% |
| Unintended society_name introduced | 0 |
| All canonical values in mapping | Exist in societies table |
| Direct-replace mappings only | 100% applied successfully |

---

## Executed Deterministic Mappings (Round 3)

| Legacy Value | Canonical Name | Affected Projects | Affected Users | Execution Date |
|--------------|----------------|-------------------|----------------|----------------|
| SSCT | SARVAJANA SNEHA CHARITABLE TRUST | 0 | 1 | 2026-02-15 |
| ST. ANNS'S SOCIETY, VISAKHAPATNAM | ST. ANN'S SOCIETY, VISAKHAPATNAM | 15 | 1 | 2026-02-15 |
| St.Anns Educational Society | ST. ANN'S EDUCATIONAL SOCIETY | 0 | 4 | 2026-02-15 |
| St.Ann's society, Southern Region | ST.ANN'S SOCIETY, SOUTHERN REGION | 0 | 5 | 2026-02-15 |
| Wilhelm Meyers Development Society | WILHELM MEYERS DEVELOPMENTAL SOCIETY | 0 | 1 | 2026-02-15 |

**Note:** These mappings were executed safely. No schema changes performed. Project resolution rate updated to **100%**.
