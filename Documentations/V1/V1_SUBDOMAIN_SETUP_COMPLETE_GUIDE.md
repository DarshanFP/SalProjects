# V1 Subdomain Setup Complete Guide

**Date:** January 2025  
**Purpose:** Complete setup guide for `v1.salprojects.org` subdomain with separate database and environment  
**Status:** 📋 **IMPLEMENTATION GUIDE**

---

## 📋 Overview

This guide provides step-by-step instructions for setting up the `v1.salprojects.org` subdomain on Hostinger to test new features before migrating to the main domain (`salprojects.org`).

### Architecture

-   **Subdomain:** `v1.salprojects.org`
-   **Main Domain:** `salprojects.org` (remains functional with old code)
-   **Codebase:** **Separate codebase** in `public_html/V1` (complete isolation)
-   **Database:** **Separate database** (`u160871038_salpro`)
-   **Database User:** `u160871038_salproj`
-   **Environment:** Separate `.env` file in V1 directory
-   **Goal:** Test new features, then migrate to main domain when ready

---

## 🎯 Complete Setup Checklist

### Phase 1: Hostinger Configuration

-   [ ] Create subdomain in Hostinger hPanel
-   [ ] Configure DNS (usually automatic)
-   [ ] Point subdomain to separate directory (`public_html/V1/public`)
-   [ ] Verify subdomain resolves

### Phase 2: Database Setup

-   [ ] Create new database in Hostinger
-   [ ] Create new database user
-   [ ] Grant permissions to user
-   [ ] Test database connection
-   [ ] Import production database structure (optional)
-   [ ] Check migration status (database already has tables/data)
-   [ ] Run only pending migrations

### Phase 3: Environment Configuration

-   [ ] Create `.env` file in V1 directory
-   [ ] Configure environment variables (use template: `Documentations/V1/env.v1.template`)
-   [ ] Set up database connection
-   [ ] Configure application URL
-   [ ] Set up email configuration
-   [ ] Configure session/cookie settings

### Phase 4: Code Deployment

-   [ ] Deploy code to `public_html/V1` directory
-   [ ] Set up `.env` file in V1 directory
-   [ ] Set proper file permissions
-   [ ] Run migrations on V1 database
-   [ ] Clear caches
-   [ ] Test application access

### Phase 5: Testing & Verification

-   [ ] Test subdomain access
-   [ ] Verify database connection
-   [ ] Test authentication
-   [ ] Test new features
-   [ ] Verify file uploads
-   [ ] Test email functionality
-   [ ] Verify sessions work correctly

### Phase 6: Migration to Main Domain (When Ready)

-   [ ] Final testing on v1 subdomain
-   [ ] Backup production database
-   [ ] Backup production files
-   [ ] Update main domain `.env`
-   [ ] Deploy code to main domain
-   [ ] Clear caches
-   [ ] Monitor for issues

---

## 📝 Phase 1: Hostinger Subdomain Configuration

### Step 1.1: Log in to Hostinger hPanel

1. Go to https://hpanel.hostinger.com/
2. Log in with your Hostinger credentials
3. Select your domain `salprojects.org`

### Step 1.2: Create Subdomain

1. **Navigate to Subdomains**

    - Go to **Domains** → **Subdomains**
    - Or **Advanced** → **Subdomains**

2. **Create New Subdomain**

    - Click **"Create Subdomain"** or **"Add Subdomain"** button
    - **Subdomain name:** `v1`
    - **Domain:** `salprojects.org` (should be pre-selected)
    - **Document Root:**
        - **Important:** Point to the **separate V1 directory**
        - Path: `public_html/V1/public` (Laravel's public directory)
        - Example full paths (adjust based on your structure):
            - `/public_html/V1/public`
            - `/domains/salprojects.org/public_html/V1/public`
            - `/home/u160871038/domains/salprojects.org/public_html/V1/public`
    - Click **"Create"** or **"Add"**

3. **Wait for DNS Propagation**
    - DNS changes usually take 5-15 minutes
    - Can take up to 48 hours in rare cases
    - Verify with: `nslookup v1.salprojects.org` or `ping v1.salprojects.org`

### Step 1.3: Verify Subdomain

```bash
# Check if subdomain resolves
ping v1.salprojects.org

# Or use nslookup
nslookup v1.salprojects.org

# Or use dig
dig v1.salprojects.org
```

---

## 🗄️ Phase 2: Database Setup

### Step 2.1: Database Information

Your V1 database is already set up with the following credentials:

-   **Database Name:** `u160871038_salpro`
-   **Database User:** `u160871038_salproj`
-   **Database Password:** `5vAx#zypro`
-   **Database Host:** `127.0.0.1` (typical for Hostinger)

**Note:** If you need to create/modify the database, follow these steps:

1. **Log in to Hostinger hPanel**
    - Go to **Databases** → **MySQL Databases**

2. **Verify Database Exists**
    - Check that database `u160871038_salpro` exists
    - Verify user `u160871038_salproj` has access

3. **Test Database Connection**
    - Use phpMyAdmin or command line to verify credentials work

### Step 2.2: Grant Permissions (if needed)

1. **Add User to Database** (if not already done)
    - In MySQL Databases section, find **"Add User to Database"**
    - Select user: `u160871038_salproj`
    - Select database: `u160871038_salpro`
    - Check **"ALL PRIVILEGES"** or at minimum:
        - SELECT, INSERT, UPDATE, DELETE
        - CREATE, ALTER, INDEX, DROP
        - REFERENCES (if available)
    - Click **"Add"** or **"Make Changes"**

### Step 2.4: Test Database Connection

**Option A: Test via phpMyAdmin**

1. Go to **Databases** → **phpMyAdmin**
2. Try to log in with new credentials
3. Verify you can see the database

**Option B: Test via SSH/Command Line**

```bash
mysql -h 127.0.0.1 -u u160871038_salproj -p u160871038_salpro
# Enter password: 5vAx#zypro
# If connection works, you'll see MySQL prompt
# Type: exit; to quit
```

**Option C: Test via Laravel Tinker (after .env setup)**

```bash
php artisan tinker
DB::connection()->getPdo();
# Should return PDO object if connection works
```

### Step 2.5: Database Structure Setup

You have two options:

#### Option A: Start Fresh (Recommended for Testing)

-   Run migrations on the new database
-   Seed initial data if needed
-   Test with new/clean data

#### Option B: Import Production Structure (For Comparison)

-   Export structure from production database
-   Import to new database
-   **DO NOT import data** (to avoid conflicts)
-   Run any pending migrations

**If importing structure:**

```bash
# Export structure only (no data)
mysqldump -h 127.0.0.1 -u u160871038_salprojects -p --no-data u160871038_salprojects > structure.sql

# Import to new database
mysql -h 127.0.0.1 -u u160871038_salprojects_v1 -p u160871038_salprojects_v1 < structure.sql
```

---

## ⚙️ Phase 3: Environment Configuration

### Step 3.1: Create `.env` File in V1 Directory

Since you have a separate codebase in `public_html/V1`, you need to create a `.env` file in that directory.

1. **Navigate to V1 directory on your production server:**
   ```bash
   cd public_html/V1
   # or
   cd /path/to/public_html/V1
   ```

2. **Create the `.env` file:**
   - Copy the template from `Documentations/V1/env.v1.template`
   - Or create a new `.env` file in the V1 directory

### Step 3.2: Configure `.env` File

Edit the `.env` file in the V1 directory with the following configuration:

```env
APP_NAME=SalProjects
APP_ENV=production
APP_KEY=base64:RxRUGdaPlIfnv0HbhmEtn8ncMdWKTnjsva4DsniUGnc=
APP_DEBUG=true
APP_URL=https://v1.salprojects.org

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# V1 Database Configuration (SEPARATE DATABASE)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u160871038_salpro
DB_USERNAME=u160871038_salproj
DB_PASSWORD=5vAx#zypro

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Email Configuration (can use same or different)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=passwordReset@salprojects.org
MAIL_PASSWORD=hibpyv3hitnywaqnuR%
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=passwordReset@salprojects.org
MAIL_FROM_NAME="SalProjects V1"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

**Important Notes:**

-   Database credentials are already configured above
-   `APP_URL` must be `https://v1.salprojects.org`
-   `APP_KEY` should be the same as production (for consistency)
-   Save this file as `.env` in the `public_html/V1` directory

**Quick Setup:**
-   Copy the content from `Documentations/V1/env.v1.template`
-   Paste it into `.env` file in `public_html/V1` directory
-   The file is ready to use!

### Step 3.3: Verify Environment File

1. **Verify the `.env` file exists** in `public_html/V1` directory
2. **Check file permissions:**
   ```bash
   chmod 644 public_html/V1/.env
   ```
3. **Verify database credentials** are correct in the `.env` file
4. **Ensure `APP_URL` is set to** `https://v1.salprojects.org`

**That's it!** With separate codebases, you don't need any domain detection or middleware - each codebase has its own `.env` file.

---

## 🚀 Phase 4: Code Deployment & Configuration

### Step 4.1: Deploy Code to V1 Directory

1. **Upload your code** to `public_html/V1` directory on the production server
2. **Create `.env` file** in `public_html/V1` directory (use the template from `Documentations/V1/env.v1.template`)
3. **Set proper permissions:**
    ```bash
    cd public_html/V1
    chmod -R 755 storage bootstrap/cache
    chmod 644 .env
    chown -R www-data:www-data storage bootstrap/cache
    ```

**Note:** Since you have a separate codebase, there's no need for domain detection or middleware. Each codebase is completely independent!

### Step 4.2: Handle Migrations on V1 Database

**Important:** Your V1 database (`u160871038_salpro`) already contains existing tables and data.

#### Step 4.2.1: Check Migration Status First

Before running migrations, check what's already been run:

```bash
cd public_html/V1
php artisan migrate:status
```

This will show you:
- Which migrations have already been run
- Which migrations are pending
- The migration table status

#### Step 4.2.2: Run Only Pending Migrations

Laravel will automatically skip migrations that have already been executed:

```bash
php artisan migrate
```

**What Laravel does:**
- Checks the `migrations` table to see what's been run
- Only runs migrations that haven't been executed yet
- Skips migrations that are already in the database

#### Step 4.2.3: If Migration Table Doesn't Exist

If the database has tables but no `migrations` table (rare case):

1. **Create migrations table:**
   ```bash
   php artisan migrate:install
   ```

2. **Mark existing migrations as run** (optional, only if needed):
   - This is only needed if your database has tables but no migrations tracking
   - Usually not necessary - Laravel will handle it automatically

#### Step 4.2.4: Verify Migration Status

After running migrations:

```bash
php artisan migrate:status
```

Should show all migrations as "Ran" (no pending migrations).

**Note:** Since you have a separate codebase with its own `.env` file, migrations will automatically use the database configured in that `.env` file (`u160871038_salpro`).

---

## 🧪 Phase 5: Testing & Verification

### Step 5.1: Test Subdomain Access

1. **Access the subdomain:**

    ```
    https://v1.salprojects.org
    ```

2. **Verify it loads:**
    - Should see Laravel application
    - Check for any errors

### Step 5.2: Verify Database Connection

1. **Check Laravel logs:**

    ```bash
    tail -f storage/logs/laravel.log
    ```

2. **Test in browser:**

    - Try to access a page that requires database
    - Check for database connection errors

3. **Test via Tinker:**
    ```bash
    php artisan tinker
    DB::connection()->getDatabaseName(); // Should show V1 database name
    DB::connection()->getPdo(); // Should return PDO object
    ```

### Step 5.3: Test Authentication

1. **Try to log in:**

    - Access login page
    - Try logging in with test credentials
    - Verify session works

2. **Check sessions:**
    - Verify cookies are set correctly
    - Check session storage

### Step 5.4: Test New Features

1. **Test all new features** you want to verify
2. **Compare behavior** with main domain (if using similar data)
3. **Document any issues**

### Step 5.5: Test File Uploads

1. **Test file upload functionality**
2. **Verify files are stored correctly**
3. **Check file permissions**

### Step 5.6: Test Email Functionality

1. **Test password reset emails**
2. **Test notification emails**
3. **Verify email configuration**

### Step 5.7: Performance Testing

1. **Test page load times**
2. **Check database query performance**
3. **Monitor server resources**

---

## 🔄 Phase 6: Migration to Main Domain (When Ready)

### Pre-Migration Checklist

-   [ ] All features tested and working on v1.salprojects.org
-   [ ] No critical bugs found
-   [ ] Performance is acceptable
-   [ ] All stakeholders have approved
-   [ ] Backup plan ready
-   [ ] Maintenance window scheduled (if needed)

### Step 6.1: Final Testing on V1

1. **Run comprehensive tests** on v1.salprojects.org
2. **Document any remaining issues**
3. **Get final approval**

### Step 6.2: Backup Production

1. **Backup Database:**

    ```bash
    mysqldump -h 127.0.0.1 -u u160871038_salprojects -p u160871038_salprojects > backup_main_$(date +%Y%m%d_%H%M%S).sql
    ```

2. **Backup Files:**

    ```bash
    tar -czf backup_files_$(date +%Y%m%d_%H%M%S).tar.gz /path/to/project
    ```

3. **Verify backups** are complete and accessible

### Step 6.3: Update Main Domain Configuration

1. **Update main domain's `.env` file:**

    - Update any new configuration values
    - Ensure all new environment variables are set
    - Update `APP_URL` if needed

2. **Verify database connection** is correct

### Step 6.4: Deploy Code to Main Domain

1. **Deploy latest code** (already in same directory if using shared codebase)
2. **Or deploy to main domain directory** if using separate directories

### Step 6.5: Run Migrations (if any new ones)

```bash
php artisan migrate
```

### Step 6.6: Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

### Step 6.7: Test Main Domain

1. **Access main domain:**

    ```
    https://salprojects.org
    ```

2. **Test critical functionality:**

    - Login
    - Key features
    - Database operations
    - File uploads

3. **Monitor for errors:**
    ```bash
    tail -f storage/logs/laravel.log
    ```

### Step 6.8: Monitor and Support

1. **Monitor application** for first few hours/days
2. **Watch error logs**
3. **Respond to any issues quickly**
4. **Have rollback plan ready**

### Step 6.9: Keep V1 Subdomain

-   **Keep v1.salprojects.org** as staging/testing environment
-   Use for future testing before deploying to main
-   Or deactivate if no longer needed

---

## 🆘 Troubleshooting

### Subdomain Not Resolving

**Symptoms:** Cannot access v1.salprojects.org

**Solutions:**

-   Check DNS propagation: `nslookup v1.salprojects.org`
-   Verify subdomain is created in Hostinger
-   Wait 15-30 minutes for DNS propagation
-   Check subdomain's document root is correct

### Database Connection Errors

**Symptoms:** "SQLSTATE[HY000] [2002] Connection refused" or similar

**Solutions:**

-   Verify database credentials in `.env`
-   Check database exists and user has permissions
-   Test connection: `mysql -h 127.0.0.1 -u [user] -p [database]`
-   Verify `DB_HOST` is correct (usually `127.0.0.1` for Hostinger)
-   Check if database user has correct permissions

### Wrong Database Being Used

**Symptoms:** V1 subdomain using main database or vice versa

**Solutions:**

-   Verify middleware is registered in `Kernel.php`
-   Check middleware order (should be first)
-   Clear config cache: `php artisan config:clear`
-   Verify database connection switching logic
-   Check `APP_URL_V1` and database credentials are set

### Session Issues

**Symptoms:** Sessions not working, logged out frequently

**Solutions:**

-   Check `config/session.php` domain setting
-   Verify `SESSION_DOMAIN` in `.env`
-   Clear browser cookies
-   Check session storage directory permissions
-   Verify session driver configuration

### 404 Errors

**Symptoms:** All pages return 404

**Solutions:**

-   Check `.htaccess` file exists in `public` directory
-   Verify mod_rewrite is enabled
-   Check Laravel routes are registered
-   Clear route cache: `php artisan route:clear`
-   Verify document root points to `public` directory

### Environment Variables Not Loading

**Symptoms:** Configuration not working, using default values

**Solutions:**

-   Clear config cache: `php artisan config:clear`
-   Verify `.env` file exists and is readable
-   Check for syntax errors in `.env` file
-   Ensure no extra spaces in `.env` file
-   Verify file permissions

---

## 📊 Configuration Files Summary

### Files to Create/Modify

1. **`.env.v1`** - V1 environment configuration (for reference)
2. **`.env`** - Main environment (add V1 database credentials)
3. **`config/database.php`** - Add `mysql_v1` connection
4. **`app/Http/Middleware/DetectSubdomain.php`** - Domain detection middleware
5. **`app/Http/Kernel.php`** - Register middleware
6. **`config/session.php`** - Session domain configuration (if needed)

### Database Configuration

-   **Main Database:** `u160871038_salprojects`
-   **V1 Database:** `u160871038_salprojects_v1`
-   **Connection Name (V1):** `mysql_v1`

---

## ✅ Quick Reference Checklist

### Initial Setup

-   [ ] Create subdomain in Hostinger
-   [ ] Create V1 database
-   [ ] Create V1 database user
-   [ ] Grant permissions
-   [ ] Add V1 credentials to `.env`
-   [ ] Update `config/database.php`
-   [ ] Create DetectSubdomain middleware
-   [ ] Register middleware in Kernel.php
-   [ ] Run migrations on V1 database
-   [ ] Test subdomain access
-   [ ] Test database connection
-   [ ] Test authentication
-   [ ] Test new features

### Before Migration

-   [ ] All tests passing on V1
-   [ ] Backup production database
-   [ ] Backup production files
-   [ ] Update main `.env` if needed
-   [ ] Schedule maintenance window (if needed)

### Migration

-   [ ] Deploy code
-   [ ] Run migrations
-   [ ] Clear caches
-   [ ] Test main domain
-   [ ] Monitor for issues

---

## 📞 Support & Notes

-   **Hostinger Support:** https://www.hostinger.com/contact
-   **Laravel Documentation:** https://laravel.com/docs
-   **Keep `.env.v1` file** as reference/documentation
-   **Document any custom configurations** you make
-   **Test thoroughly** before migrating to main domain

---

**Good luck with your V1 subdomain setup!** 🚀
