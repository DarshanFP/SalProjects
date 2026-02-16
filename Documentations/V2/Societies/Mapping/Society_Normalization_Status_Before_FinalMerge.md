# Society Normalization Status — Pre Final Canonical Insert

**Analysis only. No data changes. No inserts.**

---

## 1. Resolution Summary

### Projects

| Metric | Value |
|--------|-------|
| Total projects | 135 |
| Resolved | 135 |
| Unresolved | 0 |
| Resolution % | **100%** |

### Users

| Metric | Value |
|--------|-------|
| Total users (with society_name) | 128 |
| Resolved | 122 |
| Unresolved | 6 |
| Resolution % | **95.3%** |

---

## 2. Unresolved Project Values

| society_name | usage_count | Category (to be determined) |
|--------------|-------------|-----------------------------|
| *(none)* | — | — |

All project `society_name` values resolve to `societies.name`. No unresolved project values.

---

## 3. Unresolved User Values

| society_name | usage_count | Category (to be determined) |
|--------------|-------------|-----------------------------|
| St. Ann's Society Southern Region | 2 | Casing/format variant |
| Generalate | 1 | Garbage / not a society |
| None | 1 | Garbage / placeholder |
| rtyui | 1 | Garbage / test/erroneous |
| St. Ann's Society Bangalore | 1 | Casing/format variant |

---

## 4. Trailing Space Issues

| Table | Rows with society_name ≠ TRIM(society_name) |
|-------|---------------------------------------------|
| projects | 0 |
| users | 0 |

No trailing or leading space issues detected.

---

## 5. Case Sensitivity

**societies.name column:**
- Type: `varchar(255)`
- Collation: `utf8mb4_unicode_ci` (case-insensitive)
- Key: UNI (unique)

---

## 6. Observations

### Unresolved Values

| Value | Suggested category | Possible action |
|-------|--------------------|-----------------|
| St. Ann's Society Southern Region | Deterministic casing/format variant | Map → `ST. ANN'S SOCIETY, BANGLORE` (Southern Region was merged into BANGLORE) |
| St. Ann's Society Bangalore | Deterministic casing variant | Map → `ST. ANN'S SOCIETY, BANGLORE` |
| Generalate | Not a society (headquarters) | Leave unchanged or treat as special |
| None | Placeholder/null indicator | Leave unchanged or NULL |
| rtyui | Test/erroneous input | Manual review or leave unchanged |

### Deterministic mappings (if applied)

- `St. Ann's Society Southern Region` → `ST. ANN'S SOCIETY, BANGLORE`
- `St. Ann's Society Bangalore` → `ST. ANN'S SOCIETY, BANGLORE`

Would resolve 3 of 6 remaining users (100% for deterministic society names).

### Non-deterministic (manual review)

- Generalate, None, rtyui — do not map to a canonical society without business confirmation.

---

## 7. Risk Assessment

| Question | Answer |
|----------|--------|
| Is it safe to insert new canonical? | No new canonical needed for current unresolved values. `ST. ANN'S SOCIETY, BANGLORE` already exists. |
| Would resolution reach 100%? | Yes, if `St. Ann's Society Southern Region` and `St. Ann's Society Bangalore` are mapped to `ST. ANN'S SOCIETY, BANGLORE`. The remaining 3 (Generalate, None, rtyui) are non-society values. |
| Any ambiguous entries remaining? | Yes: Generalate, None, rtyui. These require business decision before mapping. |

---

## 8. Current Canonical Societies

```
BIARA SANTA ANNA, MAUSAMBI
MISSIONARY SISTERS OF ST. ANN
SARVAJANA SNEHA CHARITABLE TRUST
ST. ANN'S CONVENT, LURO
ST. ANN'S EDUCATIONAL SOCIETY
ST. ANN'S SOCIETY, BANGLORE
ST. ANN'S SOCIETY, VISAKHAPATNAM
ST. ANNE'S SOCIETY
ST. ANNE'S SOCIETY, DIVYODAYA
WILHELM MEYERS DEVELOPMENTAL SOCIETY
```

---

**Status:** Analysis complete. No data changes made.
