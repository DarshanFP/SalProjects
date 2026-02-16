# Production Phase 0.8 — Southern Consolidation (Final)

**Date:** 2026-02-15  
**Context:** Consolidate Southern-related legacy society_name values to canonical **ST. ANN'S SOCIETY, BANGLORE**. No schema changes. No migrations. Only society_name updates.

---

## Step 1 — Verify Canonical Target Exists

**Query:**
```sql
SELECT id, name, province_id
FROM societies
WHERE name = 'ST. ANN''S SOCIETY, BANGLORE';
```

**Result:** **Exactly 1 row.**

| id | name | province_id |
|----|------|-------------|
| 8 | ST. ANN'S SOCIETY, BANGLORE | 1 |

---

## Step 2 — Pre-Update Counts (Projects)

**Query:** Count by society_name for the three legacy values.

**Result:**

| society_name | cnt |
|------------------------------------------|-----|
| ST.ANN'S SOCIETY, SOUTHERN REGION | 58 |

*(St. Ann's Society Southern Region and St. Ann's Society Bangalore had 0 projects.)*

**Pre-update total (projects in scope):** 58.

---

## Step 3 — Pre-Update Counts (Users)

**Result:**

| society_name | cnt |
|------------------------------------------|-----|
| St. Ann's Society Bangalore | 1 |
| St. Ann's Society Southern Region | 1 |
| ST.ANN'S SOCIETY, SOUTHERN REGION | 37 |

**Pre-update total (users in scope):** 39.

---

## Step 4 — Update Projects

**Statement:**
```sql
UPDATE projects
SET society_name = 'ST. ANN''S SOCIETY, BANGLORE'
WHERE society_name IN (
  'ST.ANN''S SOCIETY, SOUTHERN REGION',
  'St. Ann''s Society Southern Region',
  'St. Ann''s Society Bangalore'
);
```

**Rows updated:** **58**.

---

## Step 5 — Update Users

**Statement:**
```sql
UPDATE users
SET society_name = 'ST. ANN''S SOCIETY, BANGLORE'
WHERE society_name IN (
  'ST.ANN''S SOCIETY, SOUTHERN REGION',
  'St. Ann''s Society Southern Region',
  'St. Ann''s Society Bangalore'
);
```

**Rows updated:** **39**.

---

## Step 6 — Resolution After Consolidation

**Projects:**

| total_projects | resolved_projects | unresolved_projects |
|----------------|-------------------|----------------------|
| 203 | 203 | 0 |

**New project resolution %:** **100%**.

**Users:**

| total_users | resolved_users | unresolved_users |
|-------------|----------------|------------------|
| 142 | 135 | 7 |

**New user resolution %:** **95.1%** (135 / 142).

---

## Remaining Unresolved (After Phase 0.8)

**Projects:** None. No distinct unresolved project society_name values (0 unresolved projects).

**Users:** **7** users remain unresolved. All have **NULL or empty** `society_name` (no non-empty value left that fails to match a society). These are the same users identified in Phase 0 audits (e.g. ids 1, 3, 4, 5, 35, 47, 48). Resolution of these will require assigning a society (e.g. by province or manual) when `society_id` is introduced; no further society_name normalization applies.

**Distinct unresolved society_name values (non-empty):** None.

---

## Summary

| Metric | Before Phase 0.8 | After Phase 0.8 |
|--------|-------------------|-----------------|
| Projects resolved | 145 | **203** |
| Project resolution % | 71.4% | **100%** |
| Users resolved | 96 | **135** |
| User resolution % | 67.6% | **95.1%** |
| Rows updated (projects) | — | **58** |
| Rows updated (users) | — | **39** |

---

## Explicit statement

**Only society_name values updated. No schema changes.**

No migrations were run. No columns or tables were added, dropped, or altered. Only `projects.society_name` and `users.society_name` were updated to the canonical value **ST. ANN'S SOCIETY, BANGLORE** for the three legacy Southern-related values.

---

**STOP. Do not proceed to migration.**
