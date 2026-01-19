# Hostinger CLI PHP Version Guide

## The Issue

- **Control Panel:** PHP 8.3 selected (affects web requests)
- **CLI Default:** PHP 8.1.33 (shown by `php -v`)
- **Application Requires:** PHP >= 8.2.0

## Why This Happens

On Hostinger shared hosting:
- **Web PHP version** (set in control panel) = Used by Apache/Nginx for web requests
- **CLI PHP version** (default system PHP) = Used by command line
- These are **separate** and don't automatically sync

**This is normal!** Your website uses PHP 8.3 (set in panel), but CLI uses PHP 8.1.33 by default.

---

## Solution: Use PHP 8.3 Path for CLI Commands

### Step 1: Find PHP 8.3 Binary Path

Try these commands to find PHP 8.3:

```bash
# Common Hostinger paths:
/opt/alt/php83/usr/bin/php -v
/usr/bin/php83 -v
/usr/local/bin/php83 -v
/usr/bin/php8.3 -v

# List all PHP binaries:
ls -la /usr/bin/php*
ls -la /opt/alt/php*/usr/bin/php
```

One of these should show PHP 8.3.x

### Step 2: Use PHP 8.3 for Artisan Commands

Once you find the path, use it for Laravel commands:

```bash
# Replace 'php artisan' with the full PHP 8.3 path:
/opt/alt/php83/usr/bin/php artisan migrate:status
/opt/alt/php83/usr/bin/php artisan migrate
/opt/alt/php83/usr/bin/php artisan config:clear
```

### Step 3: Create a Convenient Alias (Optional)

Add to your `~/.bashrc` or `~/.bash_profile`:

```bash
# Edit the file
nano ~/.bashrc

# Add this line (adjust path based on what you found):
alias php83='/opt/alt/php83/usr/bin/php'

# Save and reload
source ~/.bashrc
```

Then use:
```bash
php83 artisan migrate:status
```

### Step 4: Create a Helper Script (Alternative)

Create `php83-artisan` file in your V1 directory:

```bash
cd public_html/V1
nano php83-artisan
```

Add this content:
```bash
#!/bin/bash
/opt/alt/php83/usr/bin/php artisan "$@"
```

Make it executable:
```bash
chmod +x php83-artisan
```

Use it:
```bash
./php83-artisan migrate:status
./php83-artisan migrate
```

---

## Quick Commands Reference

### Check PHP 8.3 Path
```bash
/opt/alt/php83/usr/bin/php -v
```

### Run Migrations with PHP 8.3
```bash
/opt/alt/php83/usr/bin/php artisan migrate:status
/opt/alt/php83/usr/bin/php artisan migrate
```

### Clear Caches with PHP 8.3
```bash
/opt/alt/php83/usr/bin/php artisan config:clear
/opt/alt/php83/usr/bin/php artisan cache:clear
```

### Verify Web PHP is 8.3

Create `public/phpinfo.php`:
```php
<?php phpinfo(); ?>
```

Visit: `https://v1.salprojects.org/phpinfo.php`

Should show PHP 8.3.x for web requests.

---

## Important Notes

1. **Web PHP vs CLI PHP:** These are separate on shared hosting
2. **Web requests use PHP 8.3** (from control panel) - this is what matters for your website
3. **CLI uses PHP 8.1.33 by default** - use PHP 8.3 path for commands
4. **Most important:** Your website is running PHP 8.3 (set in panel), which is correct!

---

## Verification Checklist

- [ ] Found PHP 8.3 binary path
- [ ] Can run `php83 artisan migrate:status` (or with full path)
- [ ] Web PHP shows 8.3 (check phpinfo.php)
- [ ] No PHP version errors when running Artisan commands

---

## If You Can't Find PHP 8.3 Path

Contact Hostinger Support and ask:
- "What is the CLI path to PHP 8.3 binary?"
- "How do I use PHP 8.3 for command line commands?"

They'll provide the exact path for your server setup.
