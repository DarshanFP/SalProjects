# Manual User Corrections — 2026-02-15

**Objective:** Execute controlled manual corrections for specific users, confirmed by business review.

---

## 1. Safety Check (Canonical Societies Exist)

**Query:**
```sql
SELECT id, name
FROM societies
WHERE name IN (
    'ST. ANN''S SOCIETY, BANGLORE',
    'ST. ANNE''S SOCIETY, DIVYODAYA',
    'SARVAJANA SNEHA CHARITABLE TRUST'
);
```

**Result:** 3 rows returned ✓

| id | name |
|----|------|
| 9 | ST. ANN'S SOCIETY, BANGLORE |
| 2 | ST. ANNE'S SOCIETY, DIVYODAYA |
| 3 | SARVAJANA SNEHA CHARITABLE TRUST |

---

## 2. Pre-Update Values (Logged)

| User ID | Name | society_name (before) |
|---------|------|------------------------|
| 6 | Sr Selvi | St. Ann's Society Southern Region |
| 7 | Sr Sujatha Jacob | St. Ann's Society Southern Region |
| 12 | Sr. Pauline Augustine | Generalate |
| 14 | test | rtyui |
| 45 | Diana Xavier | St. Ann's Society Bangalore |

---

## 3. Executed Updates

| Statement | Rows Affected |
|-----------|---------------|
| UPDATE users SET society_name = 'ST. ANN''S SOCIETY, BANGLORE' WHERE id IN (6, 7, 45) | 3 |
| UPDATE users SET society_name = 'ST. ANNE''S SOCIETY, DIVYODAYA' WHERE id = 12 | 1 |
| UPDATE users SET society_name = 'SARVAJANA SNEHA CHARITABLE TRUST' WHERE id = 14 | 1 |

**Total rows updated:** 5

---

## 4. Post-Update Verification

| User ID | Name | society_name (after) |
|---------|------|----------------------|
| 6 | Sr Selvi | ST. ANN'S SOCIETY, BANGLORE ✓ |
| 7 | Sr Sujatha Jacob | ST. ANN'S SOCIETY, BANGLORE ✓ |
| 12 | Sr. Pauline Augustine | ST. ANNE'S SOCIETY, DIVYODAYA ✓ |
| 14 | test | SARVAJANA SNEHA CHARITABLE TRUST ✓ |
| 45 | Diana Xavier | ST. ANN'S SOCIETY, BANGLORE ✓ |

---

## 5. Resolution Rate After Correction

| Metric | Value |
|--------|-------|
| Total users | 135 |
| Resolved | 127 |
| Unresolved | 8 |
| Resolution % | **94.1%** |

---

## 6. Rules Followed

- ✓ Verified canonical society exists before update
- ✓ Logged previous values
- ✓ Updated only specified IDs (6, 7, 12, 45, 14)
- ✓ No other records modified
- ✓ No schema changes
- ✓ No society_id introduced

---

**Status:** Execution complete.
