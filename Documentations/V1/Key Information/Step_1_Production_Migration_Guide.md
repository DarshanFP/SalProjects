# Step 1 — Production Migration Guide: economic_situation

This guide walks through running the `economic_situation` migration on **production** (Hostinger). Follow each step in order.

---

## Prerequisites

- [ ] Access to production server (SSH or Hostinger File Manager + Terminal)
- [ ] Production `.env` configured with production database credentials
- [ ] Migration file deployed to production (see Step 2)

---

## Step 1: Backup the Production Database

**Do this before any migration.**

### Option A: Hostinger cPanel / hPanel

1. Log in to Hostinger → hPanel
2. Go to **Databases** → **phpMyAdmin**
3. Select database: `u160871038_salprojects` (or your production DB name)
4. Click **Export** tab
5. Choose **Quick** export method, format **SQL**
6. Click **Go** and save the `.sql` file locally

### Option B: SSH / Command Line

```bash
# Replace with your actual production credentials
mysqldump -h YOUR_DB_HOST -u YOUR_DB_USERNAME -p u160871038_salprojects > backup_production_$(date +%Y%m%d_%H%M%S).sql
```

- [ ] Backup created and saved safely

---

## Step 2: Deploy the Migration File to Production

Ensure the migration file exists on production:

**File to deploy:**
```
database/migrations/2026_01_31_120000_add_economic_situation_to_projects_table.php
```

### How to deploy

- **Git:** If you use Git for deployment, commit and push, then pull on the server
- **FTP/SFTP:** Upload the file to `database/migrations/` on production
- **File Manager:** Upload via Hostinger File Manager to the correct path

**Verify the file exists:**

```bash
# SSH into production, then:
ls -la public_html/V1/database/migrations/2026_01_31_120000_add_economic_situation_to_projects_table.php
```

- [ ] Migration file present on production

---

## Step 3: Check Migration Status (Optional but Recommended)

Before running, confirm the migration has not already run:

```bash
# Navigate to Laravel root on production
cd ~/domains/YOUR_DOMAIN/public_html/V1
# Or wherever your Laravel app lives, e.g.:
cd public_html/V1

# Use PHP 8.3 for Artisan (Hostinger - see HOSTINGER_CLI_PHP_GUIDE.md)
/opt/alt/php83/usr/bin/php artisan migrate:status
```

Look for `2026_01_31_120000_add_economic_situation_to_projects_table`. If it shows **Pending**, proceed. If **Ran**, the migration was already applied.

- [ ] Migration status checked

---

## Step 4: Run the Migration

### Option A: Run only this migration (recommended)

```bash
cd public_html/V1   # or your Laravel root path

/opt/alt/php83/usr/bin/php artisan migrate --path=database/migrations/2026_01_31_120000_add_economic_situation_to_projects_table.php
```

### Option B: Run all pending migrations

```bash
/opt/alt/php83/usr/bin/php artisan migrate
```

**Expected output (success):**
```
   INFO  Running migrations.

  2026_01_31_120000_add_economic_situation_to_projects_table ....... DONE
```

- [ ] Migration completed without errors

---

## Step 5: Verify the Column Exists

```bash
/opt/alt/php83/usr/bin/php artisan tinker --execute="echo json_encode(\Schema::getColumnListing('projects'));"
```

Check that `economic_situation` appears in the output.

- [ ] Column verified on `projects` table

---

## Step 6: Clear Application Cache (Recommended)

```bash
/opt/alt/php83/usr/bin/php artisan config:clear
/opt/alt/php83/usr/bin/php artisan cache:clear
```

- [ ] Caches cleared

---

## Rollback (If Something Goes Wrong)

If you need to undo the migration:

```bash
/opt/alt/php83/usr/bin/php artisan migrate:rollback --step=1
```

This will drop the `economic_situation` column. Your backup from Step 1 can restore data if needed.

---

## Checklist Summary

| Step | Action | Done |
|------|--------|------|
| 1 | Backup production database | ☐ |
| 2 | Deploy migration file to production | ☐ |
| 3 | Check migration status | ☐ |
| 4 | Run migration | ☐ |
| 5 | Verify column exists | ☐ |
| 6 | Clear caches | ☐ |

---

## PHP Path Note (Hostinger)

If `/opt/alt/php83/usr/bin/php` does not work, use the path from [HOSTINGER_CLI_PHP_GUIDE.md](../HOSTINGER_CLI_PHP_GUIDE.md). Try:

```bash
/usr/bin/php83 -v
/usr/bin/php8.3 -v
```

Use whichever returns PHP 8.3.x.
