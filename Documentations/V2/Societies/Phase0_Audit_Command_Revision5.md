# Phase 0 Audit Command — Revision 5

**Command:** `php artisan societies:audit`  
**Implementation plan:** Society_Project_Mapping_PhasePlan_V2.md (Revision 5)

**Purpose:** Validate production data integrity before any schema migration. READ-ONLY. No schema changes. No updates. No deletes.

---

## Checks performed

| # | Check | Condition |
|---|--------|-----------|
| 1 | Duplicate society names | FAIL if any |
| 2 | Projects without user | FAIL if any |
| 3 | project.society_name not found in societies | WARNING |
| 4 | user.society_name not found in societies | WARNING |
| 5 | Duplicate provinces by name | FAIL if any |
| 6 | Users with NULL or empty province | FAIL if > 0 |
| 7 | Users whose province does NOT match provinces.name | FAIL if any |
| 8 | Projects whose user's province would fail resolution | FAIL if any |
| 9 | Estimate projects province backfill distribution | Informational only |
| 10 | Society_name resolution success rate | WARNING if < 100% |

---

## Exit codes

- **Any FAIL** → exit code **1**
- **Only warnings** → exit code **0**
- **Fully clean** → exit code **0**

---

## Example console output — fully clean

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
   PASS: All non-empty project.society_name values exist in societies.

--- 4️⃣  user.society_name not found in societies ---
   PASS: All non-empty user.society_name values exist in societies.

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
   - "Bangalore": 10 project(s)
   - "Vijayawada": 8 project(s)
   (Informational only — not a pass/fail condition.)

--- 🔟 Estimate society_name resolution success rate ---
   Distinct project.society_name: 5
   Resolving to societies.name:  5
   Resolution rate: 100%
   PASS: 100% resolution rate.

========================================
  DRY-RUN SUMMARY (counts only, no updates)
========================================
   Total users:                      50
   Users resolvable to province_id:   50
   Total projects:                   18
   Projects resolvable to province_id: 18
   Projects resolvable to society_id:  18
   Projects with unresolved society_name: 0

========================================
  AUDIT PASSED
========================================
```

---

## Example console output — province mismatch failure

```
--- 7️⃣  Users whose province does NOT match provinces.name ---
   FAIL: 1 distinct user province value(s) have no matching provinces.name.
   - "none"

--- 8️⃣  Projects whose user's province would fail resolution ---
   PASS: All projects have a user with province resolvable to provinces.name.

...

========================================
  AUDIT FAILED
========================================
```
(Exit code 1. Do not proceed to migration until province names align with `provinces.name` or missing provinces are added.)

---

## Example console output — society mismatch warning

```
--- 3️⃣  project.society_name not found in societies ---
   WARNING: 7 distinct project.society_name value(s) not found in societies.
   - "ST. ANNS'S SOCIETY, VISAKHAPATNAM"
   - "ST. ANN'S SOCIETY, VISAKHAPATNAM"
   ...

--- 🔟 Estimate society_name resolution success rate ---
   Distinct project.society_name: 7
   Resolving to societies.name:  0
   Resolution rate: 0%
   WARNING: Resolution rate < 100%.

========================================
  AUDIT PASSED (WITH WARNINGS)
========================================
```
(Exit code 0. Safe to proceed with migration; resolve society name typos during backfill.)

---

## Confirmation: no data modification

The `societies:audit` command:

- **Does not** alter schema (no migrations, no `ALTER TABLE`, no new columns/indexes).
- **Does not** insert, update, or delete any rows in any table.
- **Only** runs `SELECT` (and equivalent read-only queries) to compute counts and list problematic values.

All queries use `DB::table(...)->select/join/where/count/pluck/get`. No `update()`, `insert()`, `delete()`, or `Schema::*` calls. The dry-run summary section reports counts only; it does not write to the database.

**DO NOT PROCEED TO MIGRATION** until the audit is run and any FAIL conditions are resolved. The command is intended to be run before Phase 1 schema changes.
