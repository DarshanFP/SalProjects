# Production Phase 0.5 — Canonical Society Seeding

**Date:** 2026-02-15  
**Environment:** Production (string-based, no Phase 1–4 migrations applied)  
**Objective:** Insert canonical societies into `societies` without modifying existing rows.

---

## Insert statements executed

Inserts used **“if not exists”** logic: for each canonical name, a row was inserted only when no row with that `province_id` and `name` existed. No existing rows were updated or deleted.

**Method:** Laravel `DB::table('societies')->insert(...)` after `where('province_id', $pid)->where('name', $name)->exists()` check. Equivalent to `INSERT ... WHERE NOT EXISTS (...)`.

**Province assignment (production has `province_id` NOT NULL):**

| Name | province_id | Province (reference) |
|------|-------------|------------------------|
| SARVAJANA SNEHA CHARITABLE TRUST | 3 | Visakhapatnam |
| ST. ANN'S EDUCATIONAL SOCIETY | 3 | Visakhapatnam |
| WILHELM MEYERS DEVELOPMENTAL SOCIETY | 3 | Visakhapatnam |
| ST. ANN'S SOCIETY, VISAKHAPATNAM | 3 | Visakhapatnam |
| ST. ANNE'S SOCIETY | 3 | Visakhapatnam |
| ST.ANN'S SOCIETY, SOUTHERN REGION | 1 | Bangalore |

**Logical insert statements (one per name):**

1. `INSERT INTO societies (province_id, name, is_active, created_at, updated_at) VALUES (3, 'SARVAJANA SNEHA CHARITABLE TRUST', 1, NOW(), NOW())` — only if no row with (3, name) exists.
2. `INSERT INTO societies (province_id, name, is_active, created_at, updated_at) VALUES (3, 'ST. ANN''S EDUCATIONAL SOCIETY', 1, NOW(), NOW())` — only if not exists.
3. `INSERT INTO societies (province_id, name, is_active, created_at, updated_at) VALUES (3, 'WILHELM MEYERS DEVELOPMENTAL SOCIETY', 1, NOW(), NOW())` — only if not exists.
4. `INSERT INTO societies (province_id, name, is_active, created_at, updated_at) VALUES (3, 'ST. ANN''S SOCIETY, VISAKHAPATNAM', 1, NOW(), NOW())` — only if not exists.
5. `INSERT INTO societies (province_id, name, is_active, created_at, updated_at) VALUES (3, 'ST. ANNE''S SOCIETY', 1, NOW(), NOW())` — only if not exists.
6. `INSERT INTO societies (province_id, name, is_active, created_at, updated_at) VALUES (1, 'ST.ANN''S SOCIETY, SOUTHERN REGION', 1, NOW(), NOW())` — only if not exists.

---

## Row counts inserted

| Metric | Count |
|--------|--------|
| **Rows inserted** | **6** |
| Rows updated | 0 |
| Rows deleted | 0 |

---

## Final list of canonical societies (from verification)

Verification query run after inserts:

```sql
SELECT name FROM societies
WHERE name IN (
  'SARVAJANA SNEHA CHARITABLE TRUST',
  'ST. ANN''S EDUCATIONAL SOCIETY',
  'WILHELM MEYERS DEVELOPMENTAL SOCIETY',
  'ST. ANN''S SOCIETY, VISAKHAPATNAM',
  'ST. ANNE''S SOCIETY',
  'ST.ANN''S SOCIETY, SOUTHERN REGION'
);
```

**Result (6 rows):**

1. SARVAJANA SNEHA CHARITABLE TRUST  
2. ST. ANN'S EDUCATIONAL SOCIETY  
3. WILHELM MEYERS DEVELOPMENTAL SOCIETY  
4. ST. ANN'S SOCIETY, VISAKHAPATNAM  
5. ST. ANNE'S SOCIETY  
6. ST.ANN'S SOCIETY, SOUTHERN REGION  

**Full `societies` table after seeding (8 rows total):**

| id | province_id | name |
|----|-------------|------|
| 1 | 8 | MISSIONARY SISTERS OF ST. ANN |
| 2 | 4 | ST. ANNE'S SOCIETY, DIVYODAYA |
| 3 | 3 | SARVAJANA SNEHA CHARITABLE TRUST |
| 4 | 3 | ST. ANN'S EDUCATIONAL SOCIETY |
| 5 | 3 | WILHELM MEYERS DEVELOPMENTAL SOCIETY |
| 6 | 3 | ST. ANN'S SOCIETY, VISAKHAPATNAM |
| 7 | 3 | ST. ANNE'S SOCIETY |
| 8 | 1 | ST.ANN'S SOCIETY, SOUTHERN REGION |

---

## Confirmation: no existing rows were modified

- **Rows 1 and 2** (existing before seeding) were verified unchanged:
  - id 1: `MISSIONARY SISTERS OF ST. ANN`, province_id 8  
  - id 2: `ST. ANNE'S SOCIETY, DIVYODAYA`, province_id 4  
- Only **inserts** were performed; no UPDATE or DELETE was run.

---

## Explicit statement

**No schema changes performed.**

Only data inserts were executed. No migrations were run. No columns or tables were added, dropped, or altered.

---

**STOP — Do not proceed to normalization. Do not run migrations.**
