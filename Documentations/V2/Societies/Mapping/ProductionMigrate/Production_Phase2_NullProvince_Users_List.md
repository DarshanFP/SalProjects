# Production — Users With NULL province_id (Identification)

**Date:** 2026-02-15
**Purpose:** Identify users where `province_id IS NULL` before Phase 2 backfill/enforcement.
**Mode:** READ-ONLY. No data changes. No schema changes.

---

## Step 1 — Count NULL province_id

**Query:**

```sql
SELECT COUNT(*) AS null_count
FROM users
WHERE province_id IS NULL;
```

**Result:** **null_count = 3**

---

## Step 2 — List Affected Users

**Query:**

```sql
SELECT
    id,
    name,
    username,
    role,
    province,
    province_id,
    society_name
FROM users
WHERE province_id IS NULL;
```

**Result (3 users):**

| id  | name         | username | role      | province   | province_id | society_name                     |
| --- | ------------ | -------- | --------- | ---------- | ----------- | -------------------------------- |
| 13  | Admin        | admin    | admin     | Vijayawada | *(NULL)*  | SARVAJANA SNEHA CHARITABLE TRUST |
| 116 | Sr. Reena P. | reena    | applicant | Generalate | *(NULL)*  | ST. ANNE'S SOCIETY, DIVYODAYA    |
| 117 | Sr. Susanthi | susanthi | executor  | Generalate | *(NULL)*  | ST. ANNE'S SOCIETY, DIVYODAYA    |

---

## Step 3 — Distinct Province Strings (where province_id IS NULL)

**Query:**

```sql
SELECT DISTINCT u.province
FROM users u
WHERE u.province_id IS NULL;
```

**Result (2 distinct values):**

| province   |
| ---------- |
| Vijayawada |
| Generalate |

All three users have a non-empty `province` string; these can be used to resolve `province_id` from the `provinces` table (e.g. match on `provinces.name`).

---

## Summary

| Metric                           | Value                  |
| -------------------------------- | ---------------------- |
| Users with NULL province_id      | 3                      |
| Distinct province strings        | Vijayawada, Generalate |
| Users with province = Vijayawada | 1 (id 13)              |
| Users with province = Generalate | 2 (ids 116, 117)       |

---

## Explicit statement

**Read-only inspection. No changes performed.**

No data was modified. No migrations were run. This document only records which users have NULL `province_id` and their `province` strings for resolution.

---

**STOP. Do not modify data. Do not run migrations.**
