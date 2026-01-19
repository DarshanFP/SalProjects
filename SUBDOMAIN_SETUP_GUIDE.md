# Subdomain Setup Guide: v1.salprojects.org

**Purpose:** Set up a subdomain for testing new features before migrating to production  
**Subdomain:** `v1.salprojects.org`  
**Production Domain:** `salprojects.org` (will remain functional with old code)

---

## 📋 Overview

This guide will help you set up the `v1.salprojects.org` subdomain on Hostinger to test your new Laravel features. The setup allows you to:

- Test new code on the subdomain without affecting the main domain
- Use the same or separate database (your choice)
- Switch between environments easily
- Migrate to production when ready

---

## 🎯 Strategy Options

### Option 1: Same Directory, Environment-Based Detection (Recommended for Testing)
- Subdomain points to the same codebase directory
- Laravel detects subdomain and uses different environment settings
- Can use the same or separate database
- **Best for:** Testing new features with real production data structure

### Option 2: Separate Directory (More Isolation)
- Subdomain points to a separate directory
- Complete isolation from production
- Requires separate code deployment
- **Best for:** Complete testing isolation

**We'll use Option 1** as it's simpler and allows easy comparison with production.

---

## 📝 Step-by-Step Setup

### Step 1: Create Subdomain in Hostinger Control Panel

1. **Log in to Hostinger Control Panel** (hPanel)
   - Go to https://hpanel.hostinger.com/
   - Log in with your credentials

2. **Navigate to Subdomains**
   - Go to **Domains** → **Subdomains** (or **Advanced** → **Subdomains**)
   - Look for "Create Subdomain" or "Add Subdomain" button

3. **Create the Subdomain**
   - **Subdomain name:** `v1`
   - **Domain:** `salprojects.org`
   - **Document Root:** Point to the **same directory** as your main domain
     - If your main domain is in `/public_html/` or `/domains/salprojects.org/public_html/`
     - Set subdomain root to the **same path**
     - Example: `/domains/salprojects.org/public_html` or `/public_html`
   
4. **Click "Create" or "Add"**
   - Wait for DNS propagation (usually 5-15 minutes, can take up to 48 hours)

5. **Verify DNS**
   - Check if `v1.salprojects.org` resolves:
     ```bash
     ping v1.salprojects.org
     # or
     nslookup v1.salprojects.org
     ```

---

### Step 2: Configure Apache (if needed)

Most Hostinger setups handle this automatically, but if you have access to `.htaccess`:

**Check your `.htaccess` file** in the `public` directory:
- Ensure it's configured to handle the subdomain
- Laravel's default `.htaccess` should work fine

If you need custom routing, you can add to `.htaccess`:
```apache
# This is usually not needed - Laravel handles it automatically
```

---

### Step 3: Laravel Environment Configuration

You have two approaches:

#### Approach A: Environment Detection via .env (Recommended)

Create a separate `.env.v1` file or use environment detection:

1. **Create environment configuration file:**
   ```bash
   # On your production server
   cp .env .env.v1
   ```

2. **Edit `.env.v1` with subdomain-specific settings:**
   ```env
   APP_NAME=SalProjects
   APP_ENV=production
   APP_KEY=base64:RxRUGdaPlIfnv0HbhmEtn8ncMdWKTnjsva4DsniUGnc=
   APP_DEBUG=true
   APP_URL=https://v1.salprojects.org

   # Database - Choose one:
   # Option 1: Same database (for comparison testing)
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=u160871038_salprojects
   DB_USERNAME=u160871038_salprojects
   DB_PASSWORD=pucohpaX8

   # Option 2: Separate test database (safer, recommended)
   # DB_DATABASE=u160871038_salprojects_v1
   # DB_USERNAME=u160871038_salprojects_v1
   # DB_PASSWORD=<your_password>

   # ... rest of your production config
   ```

#### Approach B: Dynamic Environment Detection (Advanced)

Modify `bootstrap/app.php` or use middleware to detect subdomain and load appropriate `.env`.

---

### Step 4: Create Environment Detection Middleware (Recommended)

Since both domains use the same codebase, we'll detect the subdomain and configure accordingly.

**Create a new middleware** or modify existing code to detect the subdomain:

1. **Check `app/Http/Middleware/`** - You may want to create a middleware that detects the subdomain
2. **Alternative:** Use Laravel's built-in environment detection

**Simpler approach:** Create a script that switches `.env` based on the domain, or use separate `.env` files.

---

### Step 5: Database Setup (Choose One)

#### Option A: Use Same Database (For Comparison)
- **Pros:** Easy comparison, real data structure
- **Cons:** Risk of data conflicts if both domains are used simultaneously
- **Use when:** You want to compare behavior with the same data

#### Option B: Create Separate Test Database (Recommended)
- **Pros:** Complete isolation, safe testing
- **Cons:** Need to sync/migrate data if you want real data for testing
- **Use when:** You want safe testing environment

**To create a separate database in Hostinger:**
1. Go to **Databases** → **MySQL Databases** in hPanel
2. Create a new database: `u160871038_salprojects_v1`
3. Create a new user and grant privileges
4. Update `.env.v1` with new credentials

---

### Step 6: Update Laravel Configuration for Subdomain

You may need to update session/cookie configuration to handle subdomains:

**Check `config/session.php`:**
```php
'domain' => env('SESSION_DOMAIN', '.salprojects.org'), // Note the leading dot
```

**Check `config/sanctum.php`** (if using Sanctum):
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'salprojects.org,v1.salprojects.org')),
```

---

### Step 7: Enable TrustHosts Middleware (if needed)

Your `TrustHosts` middleware already supports subdomains. If you want to enable it:

**Edit `app/Http/Kernel.php`:**
```php
protected $middleware = [
    \App\Http\Middleware\TrustHosts::class, // Uncomment this line
    \App\Http\Middleware\TrustProxies::class,
    // ... rest
];
```

---

### Step 8: Deploy Code to Production

1. **Upload your code** to the production server (same directory as main domain)
2. **Ensure all migrations are run** (if using separate database, run migrations there)
3. **Clear caches:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

---

### Step 9: Test the Subdomain

1. **Access the subdomain:**
   ```
   https://v1.salprojects.org
   ```

2. **Verify it's working:**
   - Check if the application loads
   - Test login functionality
   - Verify database connection
   - Test new features

3. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## 🔧 Quick Setup Script

If you prefer a script-based approach, here's what you can do on the server:

```bash
#!/bin/bash
# Run this on your production server

# 1. Create .env.v1 (copy from production)
cp .env .env.v1

# 2. Update APP_URL in .env.v1
sed -i 's|APP_URL=https://salprojects.org|APP_URL=https://v1.salprojects.org|g' .env.v1

# 3. If using separate database, update DB settings in .env.v1
# sed -i 's|DB_DATABASE=u160871038_salprojects|DB_DATABASE=u160871038_salprojects_v1|g' .env.v1

# 4. Clear caches
php artisan config:clear
php artisan cache:clear
```

---

## 🚨 Important Considerations

### 1. Session/Cookie Handling
- Both domains may share cookies if using `.salprojects.org` domain
- Consider using separate session storage or domain-specific sessions

### 2. CSRF Tokens
- CSRF tokens are domain-specific, so this should be fine
- Test form submissions to ensure they work

### 3. File Storage
- If using local file storage, both domains will share the same `storage/` directory
- Consider separate storage or cloud storage for complete isolation

### 4. Cache
- Laravel cache is shared if using file cache
- Consider Redis or separate cache prefixes

### 5. Email Configuration
- Both environments will use the same email settings
- Consider different `MAIL_FROM_ADDRESS` for testing

---

## 📊 Testing Checklist

Before going live with the subdomain:

- [ ] Subdomain resolves correctly
- [ ] Application loads on v1.salprojects.org
- [ ] Database connection works
- [ ] Login/authentication works
- [ ] New features work as expected
- [ ] File uploads work (if applicable)
- [ ] Emails send correctly
- [ ] Sessions work correctly
- [ ] No conflicts with main domain
- [ ] Error logging works

---

## 🔄 Migration Plan (When Ready)

Once testing is complete and you're ready to migrate:

1. **Backup production database**
2. **Backup production files**
3. **Run final tests on v1.salprojects.org**
4. **Deploy new code to main domain**
5. **Update main domain's `.env` if needed**
6. **Clear caches**
7. **Monitor for issues**
8. **Keep v1.salprojects.org as backup/staging for future**

---

## 🆘 Troubleshooting

### Subdomain Not Resolving
- Check DNS propagation: `nslookup v1.salprojects.org`
- Verify subdomain is created in Hostinger
- Wait 15-30 minutes for DNS propagation

### 404 Errors
- Verify document root is correct
- Check `.htaccess` file
- Verify Laravel routes are working

### Database Connection Errors
- Verify database credentials in `.env.v1`
- Check database exists and user has permissions
- Test connection: `php artisan tinker` → `DB::connection()->getPdo();`

### Session Issues
- Clear browser cookies
- Check `config/session.php` domain setting
- Verify session storage directory permissions

---

## 📞 Need Help?

If you encounter issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check Apache/Nginx error logs in Hostinger
3. Verify all configuration files are correct
4. Test database connection separately

---

## ✅ Summary

**Quick Setup Steps:**
1. ✅ Create subdomain in Hostinger (hPanel)
2. ✅ Point to same directory as main domain
3. ✅ Create `.env.v1` with subdomain URL
4. ✅ (Optional) Create separate test database
5. ✅ Deploy code
6. ✅ Test subdomain
7. ✅ Run migrations if using separate database

**After setup:**
- Test all features on `v1.salprojects.org`
- Compare with `salprojects.org`
- Fix any issues
- Migrate when ready

---

**Good luck with your testing!** 🚀
