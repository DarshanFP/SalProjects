# Connect Old App to New Database - Complete Guide

**Date:** January 2025  
**Purpose:** Connect the old/main application to the new database `u160871038_salpro`

---

## ✅ Step 1: Update .env File (Already Done)

You've already updated your `.env` file with:
```
DB_CONNECTION=mysql
DB_HOST=srv1281.hstgr.io
DB_PORT=3306
DB_DATABASE=u160871038_salpro
DB_USERNAME=u160871038_salproj
DB_PASSWORD=5vAx!zyPro
```

---

## 🔧 Step 2: Clear Laravel Caches

Laravel caches configuration, so you need to clear all caches after updating `.env`:

### Option A: Using Artisan Commands (Recommended)

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Or clear everything at once
php artisan optimize:clear
```

### Option B: Using the Connection Test Script

```bash
php connect_to_new_database.php
```

This script will:
- Display current database configuration
- Test the database connection
- Check if key tables exist
- Show record counts

---

## 🧪 Step 3: Verify Database Connection

### Test 1: Run Connection Test Script

```bash
php connect_to_new_database.php
```

**Expected Output:**
```
✅ Database connection successful!
📊 Connected to database: u160871038_salpro
```

### Test 2: Test with Artisan

```bash
php artisan db:show
```

### Test 3: Test with Tinker (if available)

```bash
php artisan tinker --execute="echo 'Connected! Database: ' . \DB::connection()->getDatabaseName() . PHP_EOL;"
```

---

## 📋 Step 4: Verify Key Tables and Data

After connecting, verify that your app can access the data:

```bash
php verify_data_counts.php
```

This will show:
- Province count
- Center count
- User data status

---

## ⚠️ Important Considerations

### 1. **Both Apps Using Same Database**

Since both your old app and V1 subdomain are now using the same database (`u160871038_salpro`), be aware:

- ✅ **Shared Data**: Both apps will see the same users, projects, reports, etc.
- ⚠️ **Concurrent Access**: Both apps can modify the same data
- ✅ **Consistent State**: Changes in one app are immediately visible in the other

### 2. **Environment Differences**

Make sure your old app's `.env` has:
- Same database credentials
- Correct `APP_URL` (should be your main domain, not v1 subdomain)
- Same `APP_KEY` if you want to share sessions (optional)

### 3. **Session Storage**

If both apps share the same database, you might want to:
- Use database sessions: `SESSION_DRIVER=database`
- Or use separate session storage for each app

### 4. **Cache Storage**

Consider using separate cache prefixes or different cache drivers if needed.

---

## 🔍 Troubleshooting

### Issue: "Access denied for user"

**Solution:**
1. Verify Remote MySQL is enabled in Hostinger panel
2. Check that your IP is allowed (or use `%` for all hosts)
3. Verify credentials match exactly (case-sensitive)

### Issue: "Unknown database"

**Solution:**
1. Verify database name: `u160871038_salpro`
2. Check database exists in Hostinger panel
3. Verify user has access to the database

### Issue: "Connection timeout"

**Solution:**
1. Verify hostname: `srv1281.hstgr.io`
2. Check firewall settings
3. Try using IP address if hostname doesn't work

### Issue: "Configuration not updating"

**Solution:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
```

---

## ✅ Verification Checklist

After completing all steps, verify:

- [ ] `.env` file updated with new database credentials
- [ ] All Laravel caches cleared
- [ ] Database connection test successful
- [ ] Key tables accessible (users, provinces, centers, projects)
- [ ] Data counts match expected values
- [ ] App can login and access data
- [ ] No errors in `storage/logs/laravel.log`

---

## 🚀 Quick Start Commands

```bash
# 1. Clear all caches
php artisan optimize:clear

# 2. Test connection
php connect_to_new_database.php

# 3. Verify data
php verify_data_counts.php

# 4. Test app functionality
# Open your app in browser and test login/data access
```

---

## 📝 Next Steps

After successfully connecting:

1. **Test Login**: Try logging in with existing users
2. **Check Data**: Verify projects, reports, and other data are visible
3. **Test Features**: Test key features to ensure everything works
4. **Monitor Logs**: Check `storage/logs/laravel.log` for any errors

---

## 💡 Additional Notes

- The old app and V1 subdomain now share the same database
- All migrations and seeders have been run on this database
- User data migration is 93% complete (5 users need manual review)
- Both apps will see the same data in real-time

---

**Status:** Ready to connect ✅

Run the connection test script to verify everything is working!
