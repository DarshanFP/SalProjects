# Province-Based Society Assignment — 2026-02-15

**Objective:** Assign society_name to users with NULL society_name based on province. Policy-based mapping confirmed.

---

## 1. Safety Check (Canonical Societies Exist)

**Query:**
```sql
SELECT name FROM societies
WHERE name IN (
    'ST. ANN''S EDUCATIONAL SOCIETY',
    'WILHELM MEYERS DEVELOPMENTAL SOCIETY',
    'ST. ANN''S SOCIETY, BANGLORE',
    'ST. ANNE''S SOCIETY, DIVYODAYA'
);
```

**Result:** 4 rows returned ✓

---

## 2. Pre-Update Snapshot (Users with NULL society_name)

| id | name | province | society_name |
|----|------|----------|--------------|
| 3 | Sr. Brita | Bangalore | NULL |
| 1 | Anita Marki | Generalate | NULL |
| 4 | Sr. Roja Pushpa | Vijayawada | NULL |
| 35 | Celestina | Visakhapatnam | NULL |
| 47 | Sr. Nirmala Mathew | Visakhapatnam | NULL |
| 48 | Sr. Padmini | Visakhapatnam | NULL |
| 5 | Sr. Sandrina | Visakhapatnam | NULL |

**Total:** 7 users with NULL society_name

---

## 3. Province Mapping (Policy-Based)

| Province | Assigned Society |
|----------|------------------|
| Vijayawada | ST. ANN'S EDUCATIONAL SOCIETY |
| Visakhapatnam | WILHELM MEYERS DEVELOPMENTAL SOCIETY |
| Bangalore | ST. ANN'S SOCIETY, BANGLORE |
| Generalate | ST. ANNE'S SOCIETY, DIVYODAYA |

---

## 4. Executed Updates

| Statement | Province | Rows Affected |
|-----------|----------|---------------|
| UPDATE users SET society_name = 'ST. ANN''S EDUCATIONAL SOCIETY' WHERE province = 'Vijayawada' AND society_name IS NULL | Vijayawada | 1 |
| UPDATE users SET society_name = 'WILHELM MEYERS DEVELOPMENTAL SOCIETY' WHERE province = 'Visakhapatnam' AND society_name IS NULL | Visakhapatnam | 4 |
| UPDATE users SET society_name = 'ST. ANN''S SOCIETY, BANGLORE' WHERE province = 'Bangalore' AND society_name IS NULL | Bangalore | 1 |
| UPDATE users SET society_name = 'ST. ANNE''S SOCIETY, DIVYODAYA' WHERE province = 'Generalate' AND society_name IS NULL | Generalate | 1 |

**Total rows updated:** 7

---

## 5. Post-Update Verification

| Check | Result |
|-------|--------|
| Users with NULL society_name | 0 ✓ |
| Total users | 135 |
| Resolved | 134 |
| Unresolved | 1 |
| Resolution % | **99.3%** |

---

## 6. Rules Followed

- ✓ Only users with NULL society_name were modified
- ✓ No existing values overwritten
- ✓ No schema changes
- ✓ No society_id introduced

---

**Status:** Execution complete.
