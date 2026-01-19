# PHP Version Update Guide for V1 Subdomain

## Issue
- **Required:** PHP >= 8.2.0 (as per Composer dependencies)
- **Current CLI PHP:** 8.1.33 (via SSH)
- **Panel PHP:** 8.2 (selected in Hostinger control panel)

## Problem
The PHP version in the Hostinger control panel (8.2) may not match the CLI PHP version used via SSH (8.1.33). This is common because:
- Web requests use the PHP version set in the control panel
- SSH/CLI might use a different default PHP version
- You need to ensure both are aligned

---

## Solution: Update to PHP 8.2 or 8.3

### Recommended: Update to PHP 8.2
Since your panel already shows 8.2, you should ensure it's actually active and matches CLI.

### Alternative: Update to PHP 8.3
PHP 8.3 is also available and meets the requirement (>= 8.2.0). It's newer and generally better, but 8.2 is more stable/widely tested.

**Recommendation:** **PHP 8.2** is recommended for stability, but PHP 8.3 will also work fine.

---

## Steps to Update PHP Version

### Option 1: Update via Hostinger Control Panel (Recommended)

1. **Log in to Hostinger hPanel**
   - Go to https://hpanel.hostinger.com/
   - Select your domain `salprojects.org`

2. **Navigate to PHP Configuration**
   - Go to **Advanced** → **PHP Configuration**
   - Or find it in the main menu

3. **Select PHP Version**
   - **Recommended:** Select **PHP 8.2** (already selected)
   - **Alternative:** Select **PHP 8.3** if you prefer
   - Click **"Update"** button

4. **Wait for Update**
   - Hostinger will update the PHP version
   - Takes 1-2 minutes
   - Your site will be temporarily unavailable during update

5. **Verify via SSH**
   After update, SSH into your server and check:
   ```bash
   php -v
   ```
   
   Should show PHP 8.2.x or 8.3.x

### Option 2: Use PHP 8.3 via CLI (Recommended for Hostinger)

**Important:** On Hostinger shared hosting, the control panel PHP version (8.3) affects web requests, but CLI uses a different default PHP version. This is normal!

You can use PHP 8.3 for CLI commands even though `php -v` shows 8.1.33:

1. **Find PHP 8.3 path:**
   ```bash
   # Check for PHP 8.3 binary
   which php83
   # or
   /usr/bin/php8.3 -v
   # or
   ls -la /usr/bin/php*
   # or
   /opt/alt/php83/usr/bin/php -v  # Common Hostinger path
   ```

2. **Use PHP 8.3 for Artisan commands:**
   ```bash
   # Try these options:
   /usr/bin/php8.3 artisan migrate:status
   # or
   php83 artisan migrate:status
   # or (if Hostinger uses alt-php)
   /opt/alt/php83/usr/bin/php artisan migrate:status
   ```

3. **Create an alias (optional, for convenience):**
   Add to your `~/.bashrc` or `~/.bash_profile`:
   ```bash
   alias php83='/opt/alt/php83/usr/bin/php'
   # or
   alias php83='/usr/bin/php8.3'
   ```
   Then reload: `source ~/.bashrc`

4. **Verify PHP 8.3 works:**
   ```bash
   /opt/alt/php83/usr/bin/php -v
   # Should show PHP 8.3.x
   ```

---

## After Updating PHP Version

### 1. Verify PHP Version

```bash
# Check CLI PHP version
php -v

# Should show PHP 8.2.x or 8.3.x
```

### 2. Verify Composer Dependencies

```bash
cd public_html/V1
composer check-platform-reqs
```

### 3. Clear Composer Cache (if needed)

```bash
composer clear-cache
```

### 4. Run Migrations

Once PHP version is correct:

```bash
php artisan migrate:status
```

Should work without the PHP version error.

---

## Recommendation

**Update to PHP 8.2** in the Hostinger control panel:
- Your panel already shows 8.2 selected
- It meets the requirement (>= 8.2.0)
- More stable and widely tested than 8.3
- Make sure to click "Update" button to apply

**If 8.2 doesn't work after clicking Update, then use PHP 8.3:**
- Also meets the requirement
- Newer version
- Should work fine

---

## Verification Checklist

After updating:
- [ ] PHP version in panel shows 8.2 or 8.3
- [ ] `php -v` command shows 8.2.x or 8.3.x
- [ ] `php artisan migrate:status` runs without PHP version error
- [ ] `composer check-platform-reqs` shows no PHP version issues

---

## Troubleshooting

### If CLI still shows PHP 8.1 after panel update:

**This is NORMAL for Hostinger!** The panel PHP version affects web requests, not CLI.

**Solution: Use PHP 8.3 path directly for CLI commands:**

1. **Find PHP 8.3 binary path:**
   ```bash
   # Common Hostinger paths:
   /opt/alt/php83/usr/bin/php -v
   # or
   /usr/bin/php83 -v
   # or
   /usr/local/bin/php83 -v
   ```

2. **Use PHP 8.3 for Artisan commands:**
   ```bash
   # Replace 'php' with the PHP 8.3 path:
   /opt/alt/php83/usr/bin/php artisan migrate:status
   /opt/alt/php83/usr/bin/php artisan migrate
   ```

3. **Create a helper script (recommended):**
   Create a file `php83-artisan` in your V1 directory:
   ```bash
   #!/bin/bash
   /opt/alt/php83/usr/bin/php artisan "$@"
   ```
   
   Make it executable:
   ```bash
   chmod +x php83-artisan
   ```
   
   Then use it:
   ```bash
   ./php83-artisan migrate:status
   ./php83-artisan migrate
   ```

4. **Verify web PHP is 8.3:**
   Create a test file `public/phpinfo.php`:
   ```php
   <?php phpinfo(); ?>
   ```
   Visit `https://v1.salprojects.org/phpinfo.php` - should show PHP 8.3

5. **Contact Hostinger Support (if needed):**
   - Ask them the exact path to PHP 8.3 CLI binary
   - They can confirm web PHP is 8.3 (which is what matters most)

---

## Notes

- **Web vs CLI PHP:** The PHP version in the control panel primarily affects web requests (your website). CLI (command line) might use a different default PHP version.
- **Composer Requirement:** Your Laravel application requires PHP >= 8.2.0, so both 8.2 and 8.3 will work.
- **Stability:** PHP 8.2 is generally considered more stable, but 8.3 is also production-ready.
