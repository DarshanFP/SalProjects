# Production — Structural Verification Before Phase 1

**Date:** 2026-02-15  
**Purpose:** Verify current `societies` table structure before Phase 1 migrations.  
**Mode:** READ-ONLY. No schema changes. No data changes.

---

## Step 1 — Full SHOW INDEX FROM societies

| Table    | Non_unique | Key_name                   | Seq_in_index | Column_name | Collation | Index_type |
|----------|------------|----------------------------|--------------|--------------|-----------|------------|
| societies | 0          | PRIMARY                    | 1            | id           | A         | BTREE      |
| societies | 0          | unique_province_society    | 1            | province_id  | A         | BTREE      |
| societies | 0          | unique_province_society    | 2            | name         | A         | BTREE      |
| societies | 1          | societies_province_id_index | 1          | province_id  | A         | BTREE      |
| societies | 1          | societies_name_index      | 1            | name         | A         | BTREE      |

- **unique_province_society:** **EXISTS** — Unique index (Non_unique = 0), columns: province_id (seq 1), name (seq 2).
- **societies_name_unique:** **DOES NOT EXIST** — No index with this name. The only index on `name` is `societies_name_index`, which is non-unique (Non_unique = 1).
- **Other unique indexes:** PRIMARY (id); unique_province_society (province_id + name). No other unique indexes.

---

## Step 2 — SHOW COLUMNS FROM societies LIKE 'province_id'

| Field       | Type                  | Null | Key | Default | Extra |
|-------------|-----------------------|------|-----|---------|-------|
| province_id | bigint(20) unsigned   | **NO** | MUL | *(none)* |       |

- **Type:** bigint(20) unsigned  
- **Null:** **NO** (not nullable)  
- **Key:** MUL (part of non-primary index / foreign key)  
- **Default:** *(empty)*  

---

## Step 3 — Composite Unique Definition (unique_province_society)

**Query:** `SHOW INDEX FROM societies WHERE Key_name = 'unique_province_society';`

**Result:** Index exists. Columns involved:

| Seq_in_index | Column_name |
|--------------|-------------|
| 1            | province_id |
| 2            | name        |

**Confirmed:** Composite unique on **(province_id, name)**. Same society name may exist in different provinces; duplicate (province_id, name) is disallowed.

---

## Step 4 — Indexes Touching Column "name"

**Query:** `SHOW INDEX FROM societies WHERE Column_name = 'name';`

| Key_name                | Non_unique | Seq_in_index | Column_name |
|-------------------------|------------|--------------|-------------|
| unique_province_society | 0 (unique) | 2            | name        |
| societies_name_index    | 1 (non-unique) | 1       | name        |

- **unique_province_society:** name is the second column of a **unique** composite index (province_id + name).  
- **societies_name_index:** **non-unique** standalone index on name (Non_unique = 1).  
- **Unique on name alone:** None. There is no global unique constraint on `name` by itself.

---

## Step 5 — Interpretation (MANDATORY)

| Question | Answer |
|----------|--------|
| **1) Composite unique present?** | **YES** — `unique_province_society` on (province_id, name). |
| **2) Global unique(name) present?** | **NO** — No index named `societies_name_unique` or equivalent; no unique index on `name` alone. |
| **3) province_id nullable?** | **NO** — `SHOW COLUMNS` shows Null = NO. |
| **4) Current identity model** | **Composite (province_id + name)** — Uniqueness is (province_id, name) only. No global name-only uniqueness. |

---

## Explicit statement

**No changes performed. Read-only verification.**

No migrations were run. No schema or data was modified. This document records the current state of the `societies` table only.

---

**STOP. Do not run migrations. Do not modify schema. Do not modify data.**
