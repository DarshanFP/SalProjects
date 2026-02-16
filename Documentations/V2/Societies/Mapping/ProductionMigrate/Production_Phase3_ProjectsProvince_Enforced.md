# Production Phase 3 — projects.province_id Add and Enforce

**Date:** 2026-02-15  
**Objective:** Add `projects.province_id`, backfill from `users.province_id`, enforce NOT NULL and FK to `provinces(id)`. Projects table only. No other tables modified.

---

## Migration 1 — Add Column (Nullable)

**File:** `database/migrations/2026_02_15_223440_production_phase3_add_projects_province_id.php`  
**Command:** `php artisan make:migration production_phase3_add_projects_province_id`

### UP()

- Add `unsignedBigInteger('province_id')->nullable()->index()->after('user_id')` on `projects`.

### DOWN()

- Drop column `province_id` (index is dropped with the column).

### Code (Migration 1)

```php
public function up(): void
{
    Schema::table('projects', function (Blueprint $table) {
        $table->unsignedBigInteger('province_id')->nullable()->index()->after('user_id');
    });
}

public function down(): void
{
    Schema::table('projects', function (Blueprint $table) {
        $table->dropColumn('province_id');
    });
}
```

**Run:** `php artisan migrate --path=.../2026_02_15_223440_production_phase3_add_projects_province_id.php --force`  
**Result:** Migration ran successfully.

---

## Step 3 — Backfill

**Statement:**
```sql
UPDATE projects p
JOIN users u ON p.user_id = u.id
SET p.province_id = u.province_id
WHERE p.province_id IS NULL;
```

**Rows updated:** **203**

---

## Step 4 — Verification After Backfill

**NULL count:** `SELECT COUNT(*) FROM projects WHERE province_id IS NULL;`  
**Result:** **0** ✓  

**Orphan count:** `SELECT COUNT(*) FROM projects p LEFT JOIN provinces pr ON p.province_id = pr.id WHERE pr.id IS NULL;`  
**Result:** **0** ✓  

---

## Migration 2 — Enforce NOT NULL + Add FK

**File:** `database/migrations/2026_02_15_223523_production_phase3_enforce_projects_province_fk.php`  
**Command:** `php artisan make:migration production_phase3_enforce_projects_province_fk`

### UP()

- Alter `projects.province_id` to NOT NULL (MySQL: `ALTER TABLE projects MODIFY province_id BIGINT UNSIGNED NOT NULL`).
- Add foreign key: `projects.province_id` → `provinces.id` with `ON DELETE RESTRICT`.

### DOWN()

- Drop foreign key on `province_id`.
- Alter `province_id` back to nullable.

### Code (Migration 2)

```php
public function up(): void
{
    $driver = Schema::getConnection()->getDriverName();
    if ($driver === 'mysql') {
        DB::statement('ALTER TABLE projects MODIFY province_id BIGINT UNSIGNED NOT NULL');
    } else {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('province_id')->nullable(false)->change();
        });
    }
    Schema::table('projects', function (Blueprint $table) {
        $table->foreign('province_id')
            ->references('id')
            ->on('provinces')
            ->onDelete('restrict');
    });
}

public function down(): void
{
    Schema::table('projects', function (Blueprint $table) {
        $table->dropForeign(['province_id']);
    });
    // ... MODIFY province_id NULL ...
}
```

**Run:** `php artisan migrate --path=.../2026_02_15_223523_production_phase3_enforce_projects_province_fk.php --force`  
**Result:** Migration ran successfully.

---

## Step 7 — Structural Verification

### SHOW COLUMNS (projects.province_id)

| Field       | Type                 | Null | Key | Default | Extra |
|-------------|----------------------|------|-----|---------|-------|
| province_id | bigint(20) unsigned | **NO** | MUL | *(none)* |       |

**Null = NO** ✓  

### FK verification

**Query:** `information_schema.KEY_COLUMN_USAGE` for `projects.province_id` with `REFERENCED_TABLE_NAME IS NOT NULL`.

**Result (1 row):**

| CONSTRAINT_NAME              | TABLE_NAME | COLUMN_NAME | REFERENCED_TABLE_NAME | REFERENCED_COLUMN_NAME |
|-----------------------------|------------|-------------|------------------------|-------------------------|
| projects_province_id_foreign | projects   | province_id | provinces              | id                      |

**FK exists** ✓  

---

## Summary

| Item | Value |
|------|--------|
| Migration 1 (add column) | Ran successfully |
| Backfill rows updated | **203** |
| NULL count after backfill | **0** |
| Orphan count after backfill | **0** |
| Migration 2 (NOT NULL + FK) | Ran successfully |
| province_id Null (final) | **NO** ✓ |
| FK | projects_province_id_foreign → provinces.id ✓ |

---

## Explicit statement

**Schema enforcement only. No data inconsistencies introduced.**

Only the `projects` table was changed: column added, backfilled from `users.province_id` via `user_id`, then NOT NULL and foreign key enforced. No other tables were modified. All 203 projects have a valid `province_id` referencing `provinces(id)`.

---

**STOP. Do not proceed to Phase 4.**
