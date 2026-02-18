# Production Phase 5 — users.society_id Backfill

**Date:** 2026-02-17  
**Objective:** Backfill `users.society_id` from `users.society_name` on **production** (same approach as used for local DB). No schema change; column and FK already exist from migration `2026_02_15_194056_add_society_id_to_users_table`.  
**Note:** `users.society_id` remains **nullable**; unresolved users keep `society_id = NULL`.

---

## Prerequisites

- Migration `2026_02_15_194056_add_society_id_to_users_table` has been run (adds `users.society_id` nullable, indexed, FK to `societies.id`).
- `societies` table is populated (canonical names; global unique name enforced).

---

## Step 1 — Pre-check (Optional)

**Unmatched society_name (informational):**
```sql
SELECT u.id, u.name, u.society_name
FROM users u
LEFT JOIN societies s ON u.society_name = s.name
WHERE s.id IS NULL
  AND u.society_name IS NOT NULL
  AND TRIM(u.society_name) != '';
```
Review any rows; these users will keep `society_id = NULL` after backfill.

**Count of users to be backfilled:**
```sql
SELECT COUNT(*) AS to_backfill
FROM users u
JOIN societies s ON u.society_name = s.name
WHERE u.society_id IS NULL;
```
Use this number to confirm expected row count before running the backfill.

---

## Step 2 — Backfill (Production)

**Option A — Artisan command (recommended):**
```bash
# Dry run: show what would be updated, no writes
php artisan users:society-backfill --dry-run

# Execute backfill
php artisan users:society-backfill
```

**Option B — Raw SQL (run on production DB):**
```sql
UPDATE users u
JOIN societies s ON u.society_name = s.name
SET u.society_id = s.id
WHERE u.society_id IS NULL;
```
Record the number of rows affected for verification.

---

## Step 3 — Verification

**NULL count (expected > 0 if some users have no or unmatched society_name):**
```sql
SELECT COUNT(*) FROM users WHERE society_id IS NULL;
```

**Orphan check (must be 0):**
```sql
SELECT COUNT(*)
FROM users u
LEFT JOIN societies s ON u.society_id = s.id
WHERE u.society_id IS NOT NULL AND s.id IS NULL;
```
**Result:** **0** ✓  

**Resolution rate (optional):**
```sql
SELECT
  COUNT(*) AS total,
  SUM(CASE WHEN society_id IS NOT NULL THEN 1 ELSE 0 END) AS with_society_id
FROM users;
```

---

## Summary

| Item | Status |
|------|--------|
| Pre-check (unmatched) | Optional; review for awareness |
| Backfill | Artisan `users:society-backfill` or raw UPDATE |
| users.society_id | Remains nullable ✓ |
| Orphan count | 0 ✓ |

---

## Explicit statement

**Data only.** No schema changes. Only `users.society_id` is set where `users.society_name` matches `societies.name`. Users with NULL or unmatched `society_name` keep `society_id = NULL`.

---

**Next:** Dual-write and read-switch (Phase 5B) can proceed; user dropdowns already support `society_id` where backfilled.
