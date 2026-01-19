# Database Switch Scripts - Usage Guide

**Date:** January 2025  
**Purpose:** Guide for using database environment switch scripts

---

## Available Scripts

### 1. `switch_to_production_db.sh` - Switch to Production Database

**Purpose:** Switch Laravel application to production database `u160871038_salprojects`

**Usage:**

```bash
./switch_to_production_db.sh
```

**Features:**

-   ✅ Backs up current .env file automatically
-   ✅ Interactive prompts for database credentials
-   ✅ Tests database connection before completing
-   ✅ Shows migration status
-   ✅ Restores backup if connection fails
-   ✅ Clears Laravel cache automatically

**What it does:**

1. Backs up current `.env` file
2. Prompts for database type (Production/Local/Custom)
3. Prompts for database credentials (if production)
4. Updates `.env` file with new credentials
5. Clears Laravel configuration cache
6. Tests database connection
7. Shows migration status

**Example:**

```bash
$ ./switch_to_production_db.sh
========================================
Database Environment Switch Script
========================================

Step 1: Backing up current .env file...
✓ Backup created: .env.backup_20250111_153045

Current Configuration:
  Database: projectsReports
  Host: 127.0.0.1

Select target database:
  1) Production Database (u160871038_salprojects)
  2) Local/Development Database (projectsReports)
  3) Custom Database

Enter choice [1-3]: 1
Enter production database credentials:
DB_HOST [default: 127.0.0.1]: your-production-host.com
DB_USERNAME: u160871038_user
DB_PASSWORD: ********
DB_PORT [default: 3306]: 3306

Step 2: Updating .env file...
✓ .env file updated

Step 3: Clearing Laravel cache...
✓ Cache cleared

Step 4: Testing database connection...
✓ Database connection successful

Connection Details:
  Database: u160871038_salprojects
  Total Tables: 91

Step 5: Checking migration status...
  Total Migrations: 127
  Pending Migrations: 29

⚠ There are 29 pending migrations
Run 'php artisan migrate' to execute them
```

---

### 2. `switch_to_local_db.sh` - Switch to Local Development Database

**Purpose:** Quickly switch back to local development database

**Usage:**

```bash
./switch_to_local_db.sh
```

**Features:**

-   ✅ Backs up current .env file automatically
-   ✅ No prompts needed (uses default local settings)
-   ✅ Tests database connection
-   ✅ Clears Laravel cache automatically

**What it does:**

1. Backs up current `.env` file
2. Updates `.env` to local database settings:
    - Database: `projectsReports`
    - Host: `127.0.0.1`
    - Username: `root`
    - Password: `root`
    - Port: `3306`
3. Clears Laravel configuration cache
4. Tests database connection

**Example:**

```bash
$ ./switch_to_local_db.sh
========================================
Switch to Local Development Database
========================================

Step 1: Backing up current .env file...
✓ Backup created: .env.backup_20250111_153100

Current Database: u160871038_salprojects

Step 2: Updating .env file to local database...
✓ .env file updated

Step 3: Clearing Laravel cache...
✓ Cache cleared

Step 4: Testing database connection...
✓ Database connection successful

Connection Details:
  Database: projectsReports
  Total Tables: 111

✓ Successfully switched to local development database
```

---

### 3. `run_migrations.sh` - Run Pending Migrations

**Purpose:** Run pending migrations with safety checks

**Usage:**

```bash
./run_migrations.sh
```

**Features:**

-   ✅ Checks if connected to production database
-   ✅ Prompts for backup confirmation (if production)
-   ✅ Shows pending migrations before running
-   ✅ Verifies migration completion
-   ✅ Shows table count after migration

**What it does:**

1. Checks current database connection
2. Warns if connected to production database
3. Prompts for backup confirmation (if production)
4. Shows pending migrations count
5. Asks for confirmation before running
6. Runs migrations
7. Verifies completion

**Example:**

```bash
$ ./run_migrations.sh
========================================
Database Migration Runner
========================================

Current Database Configuration:
  Database: u160871038_salprojects
  Host: your-production-host.com

⚠ WARNING: You are connected to PRODUCTION database!

Have you backed up the production database? (yes/no): yes
✓ Backup confirmed

Checking migration status...
  Total Migrations: 127
  Pending Migrations: 29

Pending Migrations:
  2026_01_07_000001_add_local_contribution_to_projects_table ... Pending
  2026_01_07_162317_make_in_charge_nullable_in_projects_table ... Pending
  ... and 27 more

Do you want to run 29 pending migration(s)? (yes/no): yes

Running migrations...

Migrating: 2026_01_07_000001_add_local_contribution_to_projects_table
Migrated:  2026_01_07_000001_add_local_contribution_to_projects_table (XX.XXms)
...

✓ Migrations completed successfully!

Verifying migration status...
✓ All migrations are now up to date!
✓ Total tables in database: 108
```

---

## Quick Workflow

### Switch to Production and Run Migrations

```bash
# 1. Switch to production database
./switch_to_production_db.sh

# 2. Run migrations
./run_migrations.sh
```

### Switch Back to Local

```bash
./switch_to_local_db.sh
```

---

## Safety Features

### Automatic Backups

All scripts automatically backup your `.env` file before making changes:

-   Backup files are named: `.env.backup_YYYYMMDD_HHMMSS`
-   Backup is restored automatically if connection fails

### Production Database Protection

The migration script (`run_migrations.sh`) includes:

-   ⚠️ Warning if connected to production database
-   ✅ Backup confirmation prompt
-   ✅ Migration preview before execution

### Connection Testing

All scripts test database connection before completing:

-   ✅ Verifies connection works
-   ✅ Shows database name and table count
-   ✅ Restores backup if connection fails

---

## Manual .env Update (Alternative)

If you prefer to update `.env` manually:

```bash
# Backup first
cp .env .env.backup_$(date +%Y%m%d_%H%M%S)

# Edit .env file
nano .env  # or use your preferred editor

# Update these lines:
DB_DATABASE=u160871038_salprojects
DB_HOST=your-production-host.com
DB_USERNAME=your-username
DB_PASSWORD=your-password
DB_PORT=3306

# Clear cache
php artisan config:clear
php artisan cache:clear

# Test connection
php artisan tinker --execute="echo DB::connection()->getDatabaseName();"
```

---

## Troubleshooting

### Script Permission Denied

```bash
chmod +x switch_to_production_db.sh
chmod +x switch_to_local_db.sh
chmod +x run_migrations.sh
```

### Connection Fails

1. Check database credentials are correct
2. Verify database server is accessible
3. Check firewall/network settings
4. Verify database exists

### Migration Errors

1. Check Laravel log: `tail -100 storage/logs/laravel.log`
2. Verify database user has proper permissions
3. Check for foreign key constraints
4. Review migration files for issues

---

## Important Notes

1. **Always Backup First:** Especially for production database
2. **Test Connection:** Scripts test connection automatically
3. **Review Migrations:** Check pending migrations before running
4. **Monitor Execution:** Watch for errors during migration
5. **Verify Results:** Check migration status after completion

---

## Backup Commands

### Production Database Backup

```bash
mysqldump -h <PRODUCTION_HOST> -u <USERNAME> -p u160871038_salprojects > backup_production_$(date +%Y%m%d_%H%M%S).sql
```

### Local Database Backup

```bash
mysqldump -u root -proot projectsReports > backup_local_$(date +%Y%m%d_%H%M%S).sql
```

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** ✅ Ready to Use
