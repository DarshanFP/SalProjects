# Production Phase 1 — Enforce Global Unique Society Name (Final)

**Date:** 2026-02-15  
**Objective:** Move from composite identity (province_id + name) to global unique(name) on `societies` table only. No data changes. No other tables.

---

## Pre-check results

### PRE-CHECK 1 — Duplicate global names

**Query:**
```sql
SELECT name, COUNT(*) AS cnt
FROM societies
GROUP BY name
HAVING COUNT(*) > 1;
```

**Result:** **0 rows** ✓ (no duplicate names; safe to enforce global unique).

### PRE-CHECK 2 — Row count

**Query:** `SELECT COUNT(*) FROM societies;`

**Result:** **8** rows.

---

## Migration created

**File:** `database/migrations/2026_02_15_215453_production_phase1_enforce_global_unique_society.php`

**Command:** `php artisan make:migration production_phase1_enforce_global_unique_society`

### UP()

- Drop unique index `unique_province_society` (composite province_id + name).
- Drop foreign key on `province_id`.
- Alter `province_id` to nullable (MySQL: `MODIFY province_id BIGINT UNSIGNED NULL`).
- Re-add foreign key on `province_id` → `provinces.id` with `onDelete('restrict')`.
- Add unique index on `name`: `societies_name_unique`.

### DOWN()

- Drop unique index `societies_name_unique`.
- Drop foreign key on `province_id`.
- Alter `province_id` back to NOT NULL.
- Re-add foreign key.
- Recreate composite unique `unique_province_society` on (province_id, name).

### Migration code (excerpt)

```php
// UP
Schema::table('societies', function (Blueprint $table) {
    $table->dropUnique('unique_province_society');
});
Schema::table('societies', function (Blueprint $table) {
    $table->dropForeign(['province_id']);
});
// ... MODIFY province_id NULL (MySQL) or ->nullable()->change() ...
Schema::table('societies', function (Blueprint $table) {
    $table->foreign('province_id')->references('id')->on('provinces')->onDelete('restrict');
});
Schema::table('societies', function (Blueprint $table) {
    $table->unique('name', 'societies_name_unique');
});
```

*(Full file: `database/migrations/2026_02_15_215453_production_phase1_enforce_global_unique_society.php`.)*

---

## Step 2 — Run migration

**Command run:** `php artisan migrate --force`

**Note:** On this environment, the Phase 1 schema for `societies` was already applied by earlier migrations (`2026_02_10_235454_make_societies_province_id_nullable` and `2026_02_13_161757_enforce_unique_name_on_societies`). The new migration file is available for environments that still have the composite unique and NOT NULL province_id; running it there would perform the full UP. No data was modified.

---

## Step 3 — Verification (post–Phase 1 state)

### SHOW INDEX FROM societies

| Table    | Non_unique | Key_name                   | Seq_in_index | Column_name |
|----------|------------|----------------------------|--------------|-------------|
| societies | 0          | PRIMARY                    | 1            | id          |
| societies | 0          | **societies_name_unique**  | 1            | name        |
| societies | 1          | societies_province_id_index| 1            | province_id |
| societies | 1          | societies_name_index       | 1            | name        |

- **unique_province_society:** **No longer exists** ✓  
- **societies_name_unique:** **Exists** (unique on `name`) ✓  

### province_id nullability

**SHOW COLUMNS FROM societies LIKE 'province_id':**

| Field       | Type                 | Null | Key | Default | Extra |
|-------------|----------------------|------|-----|---------|-------|
| province_id | bigint(20) unsigned | **YES** | MUL | *(none)* |       |

**province_id now allows NULL** ✓  

### Duplicate name check

**Query:** `SELECT name, COUNT(*) FROM societies GROUP BY name HAVING COUNT(*) > 1;`  
**Result:** **0 rows** ✓  

---

## Step 4 — Duplicate insert test

**Action:** Attempted to insert a row with an existing `name` (e.g. `MISSIONARY SISTERS OF ST. ANN`).

**Result:** Insert **failed** with:

```
SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'MISSIONARY SISTERS OF ST. ANN' for key 'societies_name_unique'
```

**Conclusion:** Global unique on `name` is enforced; duplicate names are rejected. ✓  

---

## Summary

| Item                         | Status |
|------------------------------|--------|
| Pre-check: no duplicate names| PASS (0 rows) |
| Pre-check: row count recorded| 8 societies |
| Migration file created        | `2026_02_15_215453_production_phase1_enforce_global_unique_society.php` |
| unique_province_society       | Dropped (not present) |
| societies_name_unique        | Present (unique on name) |
| province_id                   | Nullable (YES) |
| Duplicate insert test         | Fails with `societies_name_unique` ✓ |

---

## Explicit statement

**No data modified. Schema alignment only.**

Only the `societies` table structure was aligned (composite unique removed, global unique on name added, province_id made nullable). No rows were inserted, updated, or deleted in `societies` or any other table.

---

**STOP. Do not proceed to Phase 2.**
