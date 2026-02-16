# Unresolved Users — Pre Phase 4

**Analysis only. No data changes. No inserts.**

---

## 1. Resolution Summary

| Metric | Value |
|--------|-------|
| Total users | 135 |
| Resolved (society_name matches societies.name) | 122 |
| Unresolved | 13 |
| Resolution % | **90.4%** |

**Breakdown of unresolved:**
- 7 users with NULL or empty society_name
- 6 users with society_name that does not match any societies.name

---

## 2. Distinct Unresolved Values (non-NULL)

| society_name | usage_count | Category | Proposed Action |
|--------------|-------------|----------|-----------------|
| St. Ann's Society Southern Region | 2 | Deterministic typo/variant | Map → ST. ANN'S SOCIETY, BANGLORE |
| Generalate | 1 | Garbage / Non-society | Leave unchanged; society_id = NULL |
| None | 1 | Garbage / Placeholder | Leave unchanged; society_id = NULL |
| rtyui | 1 | Garbage / Test/erroneous | Leave unchanged; society_id = NULL |
| St. Ann's Society Bangalore | 1 | Deterministic casing variant | Map → ST. ANN'S SOCIETY, BANGLORE |

---

## 3. Full User Listing

### 3a. Users with non-matching society_name (6 users)

| id | name | role | province | society_name |
|----|------|------|----------|--------------|
| 12 | Sr. Pauline Augustine | general | Generalate | Generalate |
| 13 | Admin | admin | Vijayawada | None |
| 14 | test | executor | Bangalore | rtyui |
| 45 | Diana Xavier | applicant | Bangalore | St. Ann's Society Bangalore |
| 6 | Sr Selvi | executor | Bangalore | St. Ann's Society Southern Region |
| 7 | Sr Sujatha Jacob | executor | Bangalore | St. Ann's Society Southern Region |

### 3b. Users with NULL or empty society_name (7 users)

| id | name | role | province | society_name |
|----|------|------|----------|--------------|
| 1 | Anita Marki | coordinator | Generalate | (NULL) |
| 35 | Celestina | applicant | Visakhapatnam | (NULL) |
| 3 | Sr. Brita | provincial | Bangalore | (NULL) |
| 47 | Sr. Nirmala Mathew | executor | Visakhapatnam | (NULL) |
| 48 | Sr. Padmini | applicant | Visakhapatnam | (NULL) |
| 4 | Sr. Roja Pushpa | provincial | Vijayawada | (NULL) |
| 5 | Sr. Sandrina | provincial | Visakhapatnam | (NULL) |

---

## 4. Observations

### Can be safely normalized (3 users)

- **St. Ann's Society Southern Region** (2 users: Sr Selvi, Sr Sujatha Jacob) → ST. ANN'S SOCIETY, BANGLORE
- **St. Ann's Society Bangalore** (1 user: Diana Xavier) → ST. ANN'S SOCIETY, BANGLORE

### Must remain NULL (4 users)

- **Generalate** (Sr. Pauline Augustine) — headquarters designation, not a society
- **None** (Admin) — placeholder
- **rtyui** (test) — erroneous/test input

### NULL society_name (7 users)

- Legitimate cases (applicants, provincials without society assignment)
- society_id will be NULL until backfilled or manually assigned

### Policy concerns

- Admin user (id 13) has society_name = "None" — consider clearing to NULL for consistency
- Test user (id 14) with society_name = "rtyui" — consider cleanup in non-production

---

## 5. Risk Assessment

| Question | Answer |
|----------|--------|
| Is it safe to proceed with society_id introduction? | Yes. society_id will be nullable. Unresolved users will have society_id = NULL. |
| Will unresolved remain NULL? | Yes, until normalization is applied (3 users) or manual assignment. Garbage values (Generalate, None, rtyui) should stay NULL. |
| Any data inconsistency risk? | Low. 3 users can be normalized before or after society_id backfill. The 7 NULL society_name users are valid; society_id will also be NULL. |

---

## Manual User Corrections Executed

| User ID | Old Value | New Canonical Value | Execution Date |
|---------|-----------|---------------------|----------------|
| 6 | St. Ann's Society Southern Region | ST. ANN'S SOCIETY, BANGLORE | 2026-02-15 |
| 7 | St. Ann's Society Southern Region | ST. ANN'S SOCIETY, BANGLORE | 2026-02-15 |
| 12 | Generalate | ST. ANNE'S SOCIETY, DIVYODAYA | 2026-02-15 |
| 45 | St. Ann's Society Bangalore | ST. ANN'S SOCIETY, BANGLORE | 2026-02-15 |
| 14 | rtyui | SARVAJANA SNEHA CHARITABLE TRUST | 2026-02-15 |

**Note:** Explicit manual corrections. No schema changes. No other user records modified. User resolution rate updated to **94.1%**.

---

## Province-Based Assignment for NULL Users

| Province | Assigned Society | Users Updated | Execution Date |
|----------|------------------|---------------|----------------|
| Vijayawada | ST. ANN'S EDUCATIONAL SOCIETY | 1 | 2026-02-15 |
| Visakhapatnam | WILHELM MEYERS DEVELOPMENTAL SOCIETY | 4 | 2026-02-15 |
| Bangalore | ST. ANN'S SOCIETY, BANGLORE | 1 | 2026-02-15 |
| Generalate | ST. ANNE'S SOCIETY, DIVYODAYA | 1 | 2026-02-15 |

**Note:** Only users with NULL society_name were modified. No existing values overwritten. User resolution rate updated to **99.3%**.
