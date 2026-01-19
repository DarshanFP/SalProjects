# Database Connection Troubleshooting for V1

## Error
```
SQLSTATE[HY000] [1045] Access denied for user 'u160871038_salproj'@'127.0.0.1' (using password: YES)
```

## What This Means
The database credentials in your `.env` file are incorrect, or the database user doesn't have proper permissions.

## Steps to Fix

### Step 1: Check if .env File Exists

```bash
cd public_html/V1
ls -la .env
```

If `.env` doesn't exist, you need to create it.

### Step 2: Verify .env File Contents

```bash
# Check database credentials in .env:
grep "^DB_" .env
```

Should show:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u160871038_salpro
DB_USERNAME=u160871038_salproj
DB_PASSWORD=5vAx#zypro
```

### Step 3: Verify Credentials Match Hostinger

Make sure the credentials in `.env` match exactly what you have in Hostinger:
- **Database:** `u160871038_salpro`
- **User:** `u160871038_salproj`
- **Password:** `5vAx#zypro`

### Step 4: Test Database Connection

Try connecting directly with MySQL:

```bash
mysql -h 127.0.0.1 -u u160871038_salproj -p u160871038_salpro
# Enter password: 5vAx#zypro
```

If this fails, the credentials are wrong or the user doesn't have access.

### Step 5: Common Issues

1. **Password has special characters:** Make sure the password in `.env` matches exactly (including any special characters like `#`)

2. **User permissions:** The database user might not have access to the database. Check in Hostinger:
   - Go to **Databases** → **MySQL Databases**
   - Verify user `u160871038_salproj` is added to database `u160871038_salpro`
   - User should have ALL PRIVILEGES

3. **Database host:** Try `localhost` instead of `127.0.0.1`:
   ```env
   DB_HOST=localhost
   ```

4. **Extra spaces in .env:** Make sure there are no spaces around the `=` sign:
   ```env
   DB_PASSWORD=5vAx#zypro
   ```
   NOT:
   ```env
   DB_PASSWORD = 5vAx#zypro
   ```

### Step 6: Recreate .env File (If Needed)

If `.env` doesn't exist or is corrupted:

```bash
cd public_html/V1

# Copy from template (if you uploaded it):
cp Documentations/V1/env.v1.template .env

# Or create manually with correct credentials:
nano .env
```

Paste the content from `Documentations/V1/env.v1.template` and ensure database credentials are correct.

### Step 7: Clear Config Cache

After fixing `.env`:

```bash
/opt/alt/php83/usr/bin/php artisan config:clear
/opt/alt/php83/usr/bin/php artisan cache:clear
```

### Step 8: Test Connection Again

```bash
/opt/alt/php83/usr/bin/php artisan migrate:status
```

---

## Quick Checklist

- [ ] `.env` file exists in `public_html/V1/` directory
- [ ] Database credentials in `.env` match Hostinger exactly
- [ ] Password is correct (including special characters)
- [ ] Database user has permissions in Hostinger
- [ ] User is added to the database in Hostinger
- [ ] No extra spaces in `.env` file
- [ ] Config cache cleared

---

## If Still Not Working

1. **Double-check credentials in Hostinger:**
   - Log in to Hostinger hPanel
   - Go to **Databases** → **MySQL Databases**
   - Verify database name, username, and password

2. **Test MySQL connection directly:**
   ```bash
   mysql -h 127.0.0.1 -u u160871038_salproj -p
   # Enter password when prompted
   # If successful, you'll see MySQL prompt
   ```

3. **Contact Hostinger Support** if credentials don't work
