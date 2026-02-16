# Production Phase 0.9 — Province-Based NULL Society Assignment

**Date:** 2026-02-15  
**Context:** Assign `society_name` for users where `society_name IS NULL`, by province. No other updates. No schema changes.

**Policy:**

| Province     | society_name (canonical)              |
|-------------|----------------------------------------|
| Vijayawada   | ST. ANN'S EDUCATIONAL SOCIETY         |
| Visakhapatnam | WILHELM MEYERS DEVELOPMENTAL SOCIETY |
| Bangalore    | ST. ANN'S SOCIETY, BANGLORE          |
| Generalate   | ST. ANNE'S SOCIETY, DIVYODAYA        |

**Scope:** Only users where `society_name IS NULL`. No schema changes.

---

## Step 1 — Verify NULL Count

**Query:**
```sql
SELECT COUNT(*) FROM users WHERE society_name IS NULL;
```

**Result:** **7** ✓ (must be 7).

---

## Step 2 — Verify Canonical Targets Exist

**Query:** `SELECT name FROM societies WHERE name IN (...);`

**Result:** **4 rows** ✓ (ST. ANN'S EDUCATIONAL SOCIETY, WILHELM MEYERS DEVELOPMENTAL SOCIETY, ST. ANN'S SOCIETY, BANGLORE, ST. ANNE'S SOCIETY, DIVYODAYA).

---

## Step 3 — Pre-Update Snapshot (NULL users)

**Query:**
```sql
SELECT id, name, province
FROM users
WHERE society_name IS NULL;
```

**Result (7 users):**

| id | name              | province     |
|----|-------------------|--------------|
| 1  | Anita Marki       | Generalate   |
| 3  | Sr. Brita         | Bangalore    |
| 4  | Sr. Roja Pushpa   | Vijayawada   |
| 5  | Sr. Sandrina      | Visakhapatnam|
| 35 | Celestina         | Visakhapatnam|
| 47 | Sr. Nirmala Mathew| Visakhapatnam|
| 48 | Sr. Padmini       | Visakhapatnam|

---

## Step 4 — Apply Province-Based Assignment

**Statements executed:**

1. `UPDATE users SET society_name = 'ST. ANN''S EDUCATIONAL SOCIETY' WHERE society_name IS NULL AND province = 'Vijayawada';`
2. `UPDATE users SET society_name = 'WILHELM MEYERS DEVELOPMENTAL SOCIETY' WHERE society_name IS NULL AND province = 'Visakhapatnam';`
3. `UPDATE users SET society_name = 'ST. ANN''S SOCIETY, BANGLORE' WHERE society_name IS NULL AND province = 'Bangalore';`
4. `UPDATE users SET society_name = 'ST. ANNE''S SOCIETY, DIVYODAYA' WHERE society_name IS NULL AND province = 'Generalate';`

**Rows updated per province:**

| Province     | Rows updated |
|-------------|----------------|
| Vijayawada   | **1**         |
| Visakhapatnam | **4**         |
| Bangalore    | **1**         |
| Generalate   | **1**         |
| **Total**    | **7**         |

---

## Step 5 — Post-Update Verification

**Query:**
```sql
SELECT COUNT(*) FROM users WHERE society_name IS NULL;
```

**Result:** **0** ✓ (must be 0).

---

## Step 6 — Final Resolution Check

**Query:** Total users, resolved (join to societies), unresolved.

**Result:**

| total_users | resolved_users | unresolved_users |
|-------------|----------------|-------------------|
| 142         | 142            | 0                 |

**Final user resolution %:** **100%**.

---

## Summary

| Metric              | Value |
|---------------------|--------|
| Pre-update NULL users | 7   |
| Rows updated (Vijayawada)   | 1   |
| Rows updated (Visakhapatnam)| 4   |
| Rows updated (Bangalore)    | 1   |
| Rows updated (Generalate)   | 1   |
| **Total rows updated**      | **7** |
| Post-update NULL count      | 0   |
| Final user resolution %     | **100%** |

---

## Explicit statement

**Only users with NULL society_name updated. No schema changes.**

No migrations were run. No columns or tables were added, dropped, or altered. Only `users.society_name` was set for the 7 users who had NULL, using the province-based policy above.

---

**STOP. Do not proceed to migration.**
