# Production Phase 0 — Audit (societies:audit)

**Date:** 2026-02-15  
**Environment:** Production (string-based, pre–Phase 1)  
**Command:** `php artisan societies:audit`  
**Purpose:** Validate production state before running Phase 1.

---

## Exit code

**0**

---

## PASS / FAIL summary

| Result | Description |
|--------|-------------|
| **PASS** | Audit completed; no hard FAIL conditions. |
| **PASS (WITH WARNINGS)** | See warnings below; safe to document and plan next steps. |

**Overall:** **AUDIT PASSED (WITH WARNINGS)**

---

## Duplicate society names

**None.**

- Check: Duplicate society names (societies.name).
- Result: **PASS** — No duplicate society names.

*(No list to show; count = 0.)*

---

## Society resolution rate

| Metric | Value |
|--------|--------|
| Distinct project.society_name | 7 |
| Resolving to societies.name | 0 |
| **Resolution rate** | **0%** |

- **WARNING:** Resolution rate &lt; 100%.  
- Total projects: 203.  
- Projects resolvable to society_id: 0.  
- Projects with unresolved society_name: 203.

---

## Province resolution status

| Check | Status |
|-------|--------|
| Duplicate provinces by name | **PASS** — No duplicate province names. |
| Users with NULL or empty province | **PASS** — All users have non-empty province. |
| User province matches provinces.name | **PASS** — All non-empty user provinces match a provinces.name. |
| Projects whose user's province would fail resolution | **PASS** — All projects have a user with province resolvable to provinces.name. |

**Summary counts (dry-run):**

- Total users: 142  
- Users resolvable to province_id: 142  
- Total projects: 203  
- Projects resolvable to province_id: 203  

**Estimated project province backfill distribution (informational):**

- Visakhapatnam: 89 project(s)  
- Bangalore: 60 project(s)  
- Vijayawada: 54 project(s)

---

## FAIL conditions

**None.**  
No step of the audit reported FAIL. Only WARNINGs were raised (see below).

---

## Warnings (non-fail)

1. **Project society_name not in societies**  
   - 7 distinct `project.society_name` value(s) not found in societies.  
   - See “Distinct project society names not in societies” below.

2. **User society_name not in societies**  
   - 17 distinct `user.society_name` value(s) not found in societies.  
   - See “Distinct user society names not in societies” below.

3. **Society resolution rate**  
   - Resolution rate &lt; 100% (currently 0%).

---

## Distinct project society names not in societies (7)

- SARVAJANA SNEHA CHARITABLE TRUST  
- ST. ANN'S EDUCATIONAL SOCIETY  
- WILHELM MEYERS DEVELOPMENTAL SOCIETY  
- ST. ANN'S SOCIETY, VISAKHAPATNAM  
- ST. ANNS'S SOCIETY, VISAKHAPATNAM  
- ST. ANNE'S SOCIETY  
- ST.ANN'S SOCIETY, SOUTHERN REGION  

---

## Distinct user society names not in societies (17)

- ST.ANN'S SOCIETY, SOUTHERN REGION  
- St. Ann's Society Southern Region  
- SARVAJANA SNEHA CHARITABLE TRUST  
- WILHELM MEYERS DEVELOPMENTAL SOCIETY  
- ST. ANN'S SOCIETY, VISAKHAPATNAM  
- Generalate  
- None  
- rtyui  
- ST. ANN'S EDUCATIONAL SOCIETY  
- Wilhelm Meyers Development Society  
- SSCT  
- St. Ann's Society  
- St Anns Society  
- St.Anns Educational Society  
- St. Ann's Society Bangalore  
- ST. ANNS'S SOCIETY, VISAKHAPATNAM  
- ST. ANNE'S SOCIETY  

---

## Full console output

```
========================================
  PHASE 0 — PRODUCTION AUDIT
  Society → Project Mapping (Revision 5)
========================================

--- 1️⃣  Duplicate society names ---
   PASS: No duplicate society names.

--- 2️⃣  Projects without user ---
   PASS: All projects have a user_id.

--- 3️⃣  project.society_name not found in societies ---
   WARNING: 7 distinct project.society_name value(s) not found in societies.
   - "SARVAJANA SNEHA CHARITABLE TRUST"
   - "ST. ANN'S EDUCATIONAL SOCIETY"
   - "WILHELM MEYERS DEVELOPMENTAL SOCIETY"
   - "ST. ANN'S SOCIETY, VISAKHAPATNAM"
   - "ST. ANNS'S SOCIETY, VISAKHAPATNAM"
   - "ST. ANNE'S SOCIETY"
   - "ST.ANN'S SOCIETY, SOUTHERN REGION"

--- 4️⃣  user.society_name not found in societies ---
   WARNING: 17 distinct user.society_name value(s) not found in societies.
   - "ST.ANN'S SOCIETY, SOUTHERN REGION"
   - "St. Ann's Society Southern Region"
   - "SARVAJANA SNEHA CHARITABLE TRUST"
   - "WILHELM MEYERS DEVELOPMENTAL SOCIETY"
   - "ST. ANN'S SOCIETY, VISAKHAPATNAM"
   - "Generalate"
   - "None"
   - "rtyui"
   - "ST. ANN'S EDUCATIONAL SOCIETY"
   - "Wilhelm Meyers Development Society"
   - "SSCT"
   - "St. Ann's Society"
   - "St Anns Society"
   - "St.Anns Educational Society"
   - "St. Ann's Society Bangalore"
   - "ST. ANNS'S SOCIETY, VISAKHAPATNAM"
   - "ST. ANNE'S SOCIETY"

--- 5️⃣  Duplicate provinces by name ---
   PASS: No duplicate province names.

--- 6️⃣  Users with NULL or empty province ---
   PASS: All users have non-empty province.

--- 7️⃣  Users whose province does NOT match provinces.name ---
   PASS: All non-empty user provinces match a provinces.name.

--- 8️⃣  Projects whose user's province would fail resolution ---
   PASS: All projects have a user with province resolvable to provinces.name.

--- 9️⃣  Estimate projects province backfill distribution ---
   Summary (by user.province):
   - "Visakhapatnam": 89 project(s)
   - "Bangalore": 60 project(s)
   - "Vijayawada": 54 project(s)
   (Informational only — not a pass/fail condition.)

--- 🔟 Estimate society_name resolution success rate ---
   Distinct project.society_name: 7
   Resolving to societies.name:  0
   Resolution rate: 0%
   WARNING: Resolution rate < 100%.

========================================
  DRY-RUN SUMMARY (counts only, no updates)
========================================
   Total users:                      142
   Users resolvable to province_id:   142
   Total projects:                   203
   Projects resolvable to province_id: 203
   Projects resolvable to society_id:  0
   Projects with unresolved society_name: 203

========================================
  AUDIT PASSED (WITH WARNINGS)
========================================
```

*(Exit code: 0)*

---

## Explicit statement

**No schema or data changes performed.**

This audit is read-only. The `societies:audit` command did not run any migrations, and did not insert, update, or delete any data.

---

**STOP — Do not run any migrations until approved.**
