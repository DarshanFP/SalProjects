# Production — Phase 2 Structural Verification (users.province_id)

**Date:** 2026-02-15  
**Purpose:** Verify production state of `users.province_id` before Phase 2.  
**Mode:** READ-ONLY. No schema changes. No data updates.

---

## Step 1 — SHOW COLUMNS (users.province_id)

**Query:** `SHOW COLUMNS FROM users LIKE 'province_id';`

**Result:**

| Field       | Type                 | Null | Key | Default | Extra |
|-------------|----------------------|------|-----|---------|-------|
| province_id | bigint(20) unsigned | **YES** | MUL | *(none)* |       |

- **Type:** bigint(20) unsigned  
- **Null:** **YES** (nullable)  
- **Key:** MUL (part of non-primary index / FK candidate)  
- **Default:** *(empty)*  
- **Extra:** *(empty)*  

---

## Step 2 — SHOW INDEX (column = province_id)

**Query:** `SHOW INDEX FROM users WHERE Column_name = 'province_id';`

**Result:**

| Table | Non_unique | Key_name                 | Seq_in_index | Column_name |
|-------|------------|--------------------------|--------------|-------------|
| users | 1          | users_province_id_index  | 1            | province_id |

- **Index name:** users_province_id_index  
- **Non_unique:** 1 (non-unique index)  
- **Seq_in_index:** 1  

---

## Step 3 — Foreign key constraint

**Query:**
```sql
SELECT
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'users'
  AND COLUMN_NAME = 'province_id'
  AND REFERENCED_TABLE_NAME IS NOT NULL;
```

**Result:** **0 rows** (no foreign key on `users.province_id`).

No FK to `provinces(id)` is present.

---

## Step 4 — NULL data state

**Query:** `SELECT COUNT(*) AS null_count FROM users WHERE province_id IS NULL;`

**Result:** **null_count = 3**

Three users have `province_id` NULL.

---

## Step 5 — Interpretation (MANDATORY)

| Question | Answer |
|----------|--------|
| **1) Does users.province_id exist?** | **YES** |
| **2) If exists:** | |
| — Is it nullable? | **YES** |
| — Does it have FK to provinces(id)? | **NO** |
| — Does it have index? | **YES** (users_province_id_index) |
| — Any NULL rows present? | **YES** — 3 rows |

### Structural status classification

**B) Column exists but nullable → Phase 2 required (enforce NOT NULL)**

- The column exists and is indexed.
- There is no foreign key to `provinces(id)` yet.
- Three users have NULL `province_id`; these must be backfilled or otherwise resolved before enforcing NOT NULL (and before adding FK if required by Phase 2).

---

## Summary table

| Item                    | Value |
|-------------------------|--------|
| Column exists           | YES |
| Nullable                | YES |
| FK to provinces(id)     | NO |
| Index on province_id    | YES (users_province_id_index) |
| NULL row count          | 3 |
| **Classification**      | **B — Phase 2 required (enforce NOT NULL)** |

---

## Explicit statement

**Read-only verification. No changes performed.**

No migrations were run. No schema or data was modified. This document only records the current state of `users.province_id`.

---

**STOP. Do not run migrations. Do not modify data.**
