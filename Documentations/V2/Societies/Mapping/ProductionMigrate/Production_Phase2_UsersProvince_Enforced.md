# Production Phase 2 — users.province_id Integrity Enforced

**Date:** 2026-02-15  
**Objective:** Enforce NOT NULL and foreign key on `users.province_id`. Users table only. No data changes.  
**Pre-condition:** NULL count = 0, Orphan count = 0.

---

## Step 1 — Pre-Check (Safety)

**Query 1:** `SELECT COUNT(*) FROM users WHERE province_id IS NULL;`  
**Result:** **0** ✓  

**Query 2:**
```sql
SELECT COUNT(*)
FROM users u
LEFT JOIN provinces p ON u.province_id = p.id
WHERE p.id IS NULL;
```
**Result:** **0** ✓  

Both pre-checks passed. Safe to enforce NOT NULL and add FK.

---

## Step 2 — Migration Created and Run

**File:** `database/migrations/2026_02_15_222314_production_phase2_enforce_users_province_integrity.php`  
**Command:** `php artisan make:migration production_phase2_enforce_users_province_integrity`

### UP()

- Alter `users.province_id` to NOT NULL (MySQL: `ALTER TABLE users MODIFY province_id BIGINT UNSIGNED NOT NULL`).
- Add foreign key: `users.province_id` → `provinces.id` with `ON DELETE RESTRICT`.

### DOWN()

- Drop foreign key on `province_id`.
- Alter `users.province_id` back to nullable (MySQL: `MODIFY province_id BIGINT UNSIGNED NULL`).

### Migration code (excerpt)

```php
// UP
$driver = Schema::getConnection()->getDriverName();
if ($driver === 'mysql') {
    DB::statement('ALTER TABLE users MODIFY province_id BIGINT UNSIGNED NOT NULL');
} else {
    Schema::table('users', function (Blueprint $table) {
        $table->unsignedBigInteger('province_id')->nullable(false)->change();
    });
}
Schema::table('users', function (Blueprint $table) {
    $table->foreign('province_id')
        ->references('id')
        ->on('provinces')
        ->onDelete('restrict');
});

// DOWN: dropForeign(['province_id']), then MODIFY province_id NULL
```

**Run:** `php artisan migrate --path=database/migrations/2026_02_15_222314_production_phase2_enforce_users_province_integrity.php --force`  
**Result:** Migration ran successfully (1,643ms).

---

## Step 4 — Verification

### SHOW COLUMNS (users.province_id)

| Field       | Type                 | Null | Key | Default | Extra |
|-------------|----------------------|------|-----|---------|-------|
| province_id | bigint(20) unsigned | **NO** | MUL | *(none)* |       |

**Null = NO** ✓  

### FK verification

**Query:** `information_schema.KEY_COLUMN_USAGE` for `users.province_id` with `REFERENCED_TABLE_NAME IS NOT NULL`.

**Result (1 row):**

| CONSTRAINT_NAME             | TABLE_NAME | COLUMN_NAME | REFERENCED_TABLE_NAME | REFERENCED_COLUMN_NAME |
|----------------------------|------------|-------------|------------------------|-------------------------|
| users_province_id_foreign  | users      | province_id | provinces             | id                      |

**FK exists** ✓ (`users.province_id` → `provinces.id`, ON DELETE RESTRICT).

### Orphan insert/update test

**Test:** Update a user to `province_id = 99999` (non-existent province).

**Result:** Update **failed** with:

```
SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`...`.`users`, CONSTRAINT `users_province_id_foreign` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`))
```

**Conclusion:** Orphan values are rejected by the FK ✓  

---

## Summary

| Item              | Status |
|-------------------|--------|
| Pre-check NULL    | 0 ✓    |
| Pre-check Orphan  | 0 ✓    |
| Migration run     | Success |
| province_id Null  | NO ✓   |
| FK present        | users_province_id_foreign → provinces.id ✓ |
| Orphan update test| Rejected ✓ |

---

## Explicit statement

**Schema enforcement only. No data modified.**

Only the `users` table schema was changed (NOT NULL and foreign key added). No user rows were inserted, updated, or deleted.

---

**STOP. Do not proceed to Phase 3.**
