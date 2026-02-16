# Production — Phase 3 Structural Verification (projects.province_id)

**Date:** 2026-02-15  
**Purpose:** Verify whether `projects.province_id` exists before Phase 3.  
**Mode:** READ-ONLY. No schema changes. No data updates.

---

## Step 1 — SHOW COLUMNS (projects.province_id)

**Query:** `SHOW COLUMNS FROM projects LIKE 'province_id';`

**Result:** **0 rows** — column does not exist.

*(No Type, Null, Key, Default, Extra to record.)*

---

## Step 2 — SHOW INDEX (column = province_id)

**Query:** `SHOW INDEX FROM projects WHERE Column_name = 'province_id';`

**Result:** **0 rows** — no index on `province_id` (column does not exist).

---

## Step 3 — FK constraint (projects.province_id)

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
  AND TABLE_NAME = 'projects'
  AND COLUMN_NAME = 'province_id'
  AND REFERENCED_TABLE_NAME IS NOT NULL;
```

**Result:** **0 rows** — no foreign key (column does not exist).

---

## Step 4 — NULL count

**Query:** `SELECT COUNT(*) AS null_count FROM projects WHERE province_id IS NULL;`

**Result:** **N/A** — column does not exist; query not applicable.

---

## Step 5 — Interpretation (MANDATORY)

| Question | Answer |
|----------|--------|
| **1) Does projects.province_id exist?** | **NO** |
| **2) If exists:** | *(Not applicable — column does not exist.)* |
| — Is it nullable? | N/A |
| — Is FK enforced? | N/A |
| — Are there NULL rows? | N/A |

### Structural status classification

**A) Column does NOT exist → Phase 3 required (add + backfill + enforce)**

- The `projects` table has no `province_id` column.
- Phase 3 must: add the column (e.g. nullable initially), backfill from `users.province_id` via `projects.user_id`, then enforce NOT NULL and add FK to `provinces(id)`.

---

## Summary table

| Item | Result |
|------|--------|
| SHOW COLUMNS | 0 rows (column absent) |
| SHOW INDEX | 0 rows |
| FK query | 0 rows |
| NULL count | N/A |
| **Classification** | **A — Phase 3 required (add + backfill + enforce)** |

---

## Explicit statement

**Read-only verification. No changes performed.**

No migrations were run. No schema or data was modified. This document only records the current state of the `projects` table with respect to `province_id`.

---

**STOP. Do not run migrations. Do not modify data.**
