# Production Phase 4 — projects.society_id Introduced (Relational Identity)

**Date:** 2026-02-15  
**Objective:** Introduce relational identity for projects via `projects.society_id`. Projects table only. No column removal, no UI change, no dual-write yet.  
**Pre-condition:** 100% society resolution, no NULL society_name, no unresolved values.

---

## Step 1 — Safety pre-check

**Query 1:** `SELECT COUNT(*) FROM projects WHERE society_name IS NULL OR society_name = '';`  
**Result:** **0** ✓  

**Query 2:** Distinct project society_name with no match in societies.  
**Result:** **0 rows** ✓  

---

## Migration 1 — Add Column (Nullable)

**File:** `database/migrations/2026_02_15_225117_production_phase4_add_projects_society_id.php`  
**Command:** `php artisan make:migration production_phase4_add_projects_society_id`

### UP()

- Add `unsignedBigInteger('society_id')->nullable()->index()->after('province_id')` on `projects`.

### DOWN()

- Drop column `society_id` (index dropped with column).

### Code (Migration 1)

```php
public function up(): void
{
    Schema::table('projects', function (Blueprint $table) {
        $table->unsignedBigInteger('society_id')->nullable()->index()->after('province_id');
    });
}

public function down(): void
{
    Schema::table('projects', function (Blueprint $table) {
        $table->dropColumn('society_id');
    });
}
```

**Run:** Migration ran successfully.

---

## Step 4 — Backfill society_id

**Statement:**
```sql
UPDATE projects p
JOIN societies s ON p.society_name = s.name
SET p.society_id = s.id
WHERE p.society_id IS NULL;
```

**Rows updated:** **203**

---

## Step 5 — Verification after backfill

**NULL count:** `SELECT COUNT(*) FROM projects WHERE society_id IS NULL;`  
**Result:** **0** ✓  

**Orphan count:** `SELECT COUNT(*) FROM projects p LEFT JOIN societies s ON p.society_id = s.id WHERE s.id IS NULL;`  
**Result:** **0** ✓  

---

## Migration 2 — Enforce NOT NULL + Add FK

**File:** `database/migrations/2026_02_15_225203_production_phase4_enforce_projects_society_fk.php`  
**Command:** `php artisan make:migration production_phase4_enforce_projects_society_fk`

### UP()

- Alter `projects.society_id` to NOT NULL (MySQL: `ALTER TABLE projects MODIFY society_id BIGINT UNSIGNED NOT NULL`).
- Add foreign key: `projects.society_id` → `societies.id` with `ON DELETE RESTRICT`.

### DOWN()

- Drop foreign key on `society_id`.
- Alter `society_id` back to nullable.

### Code (Migration 2)

```php
public function up(): void
{
    $driver = Schema::getConnection()->getDriverName();
    if ($driver === 'mysql') {
        DB::statement('ALTER TABLE projects MODIFY society_id BIGINT UNSIGNED NOT NULL');
    } else {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('society_id')->nullable(false)->change();
        });
    }
    Schema::table('projects', function (Blueprint $table) {
        $table->foreign('society_id')
            ->references('id')
            ->on('societies')
            ->onDelete('restrict');
    });
}

public function down(): void
{
    Schema::table('projects', function (Blueprint $table) {
        $table->dropForeign(['society_id']);
    });
    // ... MODIFY society_id NULL ...
}
```

**Run:** Migration ran successfully.

---

## Step 8 — Structural verification

### SHOW COLUMNS (projects.society_id)

| Field      | Type                 | Null | Key | Default | Extra |
|------------|----------------------|------|-----|---------|-------|
| society_id | bigint(20) unsigned | **NO** | MUL | *(none)* |       |

**Null = NO** ✓  

### FK verification

**Query:** `information_schema.KEY_COLUMN_USAGE` for `projects.society_id` with `REFERENCED_TABLE_NAME IS NOT NULL`.

**Result (1 row):**

| CONSTRAINT_NAME             | TABLE_NAME | COLUMN_NAME | REFERENCED_TABLE_NAME | REFERENCED_COLUMN_NAME |
|----------------------------|------------|-------------|------------------------|-------------------------|
| projects_society_id_foreign | projects   | society_id  | societies              | id                      |

**FK exists** ✓  

### Invalid insert/update test

**Test:** Update a project to `society_id = 99999` (non-existent society).

**Result:** Update **failed** with:

```
SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`...`.`projects`, CONSTRAINT `projects_society_id_foreign` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`))
```

**Conclusion:** Invalid society_id values are rejected by the FK ✓  

---

## Summary

| Item | Value |
|------|--------|
| Pre-check NULL/empty society_name | 0 ✓ |
| Pre-check unresolved | 0 rows ✓ |
| Migration 1 (add column) | Ran successfully |
| Backfill rows updated | **203** |
| NULL count after backfill | **0** |
| Orphan count after backfill | **0** |
| Migration 2 (NOT NULL + FK) | Ran successfully |
| society_id Null (final) | **NO** ✓ |
| FK | projects_society_id_foreign → societies.id ✓ |
| Invalid update test | Rejected ✓ |

---

## Explicit statement

**Relational identity enforced. No data loss. society_name retained.**

- `projects.society_id` was added, backfilled from `society_name` via join to `societies.name`, then enforced NOT NULL with FK to `societies(id)`.
- The `society_name` column was **not** removed.
- No UI changes and no dual-write logic were introduced.
- All 203 projects have a valid `society_id`; invalid values are rejected by the foreign key.

---

**STOP. Do not remove society_name column. Do not modify UI. Do not introduce dual-write yet.**
