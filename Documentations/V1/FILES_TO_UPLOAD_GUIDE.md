# Files and Folders to Upload to V1 Directory

**Target Directory:** `public_html/V1` on your production server

---

## 📦 Complete Upload List

Upload the **entire Laravel application** to `public_html/V1`, but exclude certain files/folders.

---

## ✅ **FILES AND FOLDERS TO UPLOAD**

### **Core Application Files**
- ✅ `app/` - All application code
- ✅ `bootstrap/` - Bootstrap files
- ✅ `config/` - Configuration files
- ✅ `database/` - Migrations, seeders, factories
- ✅ `public/` - Public assets (this is where your subdomain points)
- ✅ `resources/` - Views, assets, language files
- ✅ `routes/` - Route definitions
- ✅ `storage/` - Storage directory (ensure it's writable)
- ✅ `vendor/` - Composer dependencies (or run `composer install` on server)
- ✅ `artisan` - Artisan command file
- ✅ `.env.example` - Environment example (optional)
- ✅ `composer.json` - Composer dependencies
- ✅ `composer.lock` - Composer lock file
- ✅ `package.json` - NPM dependencies (if using)
- ✅ `package-lock.json` - NPM lock file (if using)
- ✅ `vite.config.js` - Vite configuration (if using)
- ✅ `postcss.config.js` - PostCSS configuration (if using)

### **Documentation (Optional - You can skip these)**
- `Documentations/` - Documentation folder (optional, not needed for production)
- `README.md` - Readme file (optional)
- `.editorconfig` - Editor configuration (optional)
- `.gitattributes` - Git attributes (optional)

---

## ❌ **FILES AND FOLDERS TO EXCLUDE (DO NOT UPLOAD)**

### **Version Control**
- ❌ `.git/` - Git repository folder
- ❌ `.gitignore` - Git ignore file (not needed on server)
- ❌ `.github/` - GitHub workflows (optional, not needed)

### **Environment Files**
- ❌ `.env` - Your local environment file (create new one on server)
- ❌ `.env.backup*` - Backup environment files
- ❌ `.env.local.backup`
- ❌ `.env.prod.backup`
- ❌ `.env.v1` - Template file (not needed on server)

### **IDE/Editor Files**
- ❌ `.idea/` - PhpStorm/IntelliJ IDEA
- ❌ `.vscode/` - VS Code settings
- ❌ `.phpstorm.meta.php` - PhpStorm metadata
- ❌ `*.sublime-*` - Sublime Text settings

### **Node Modules (If building assets on server)**
- ❌ `node_modules/` - NPM packages (can be regenerated)
- ❌ `npm-debug.log`
- ❌ `yarn-error.log`

### **Development/Test Files**
- ❌ `tests/` - Test files (optional, not needed for production)
- ❌ `phpunit.xml` - PHPUnit configuration (optional)
- ❌ `.phpunit.result.cache` - PHPUnit cache

### **Build/Compiled Assets**
- ❌ `public/build/` - Compiled assets (if you'll rebuild on server)
- ❌ `public/hot` - Vite HMR file
- ❌ `public/storage` - Symlink (will be created on server)

### **Cache/Logs (Will be regenerated)**
- ❌ `storage/logs/*.log` - Log files (directory will be created)
- ❌ `storage/framework/cache/` - Cache files (keep directory)
- ❌ `storage/framework/sessions/` - Session files (keep directory)
- ❌ `storage/framework/views/` - Compiled views (keep directory)
- ❌ `bootstrap/cache/*.php` - Bootstrap cache (keep directory, exclude files)

### **Backup/Migration Scripts (Optional)**
- ❌ `backup_*.sql` - SQL backup files
- ❌ `*.sh` - Shell scripts (optional)
- ❌ `*.php` files in root (like `switch_to_*.php`, `run_migration.php`, etc.) - These are helper scripts

### **Temporary Files**
- ❌ `*.tmp`
- ❌ `*.temp`
- ❌ `.DS_Store` - macOS files
- ❌ `Thumbs.db` - Windows files

---

## 📋 **RECOMMENDED UPLOAD METHOD**

### **Option 1: FTP/SFTP Upload (Manual)**

1. **Upload all folders** (excluding the ones listed above):
   ```
   app/
   bootstrap/
   config/
   database/
   public/
   resources/
   routes/
   storage/
   vendor/ (or run composer install on server)
   ```

2. **Upload root files:**
   ```
   artisan
   composer.json
   composer.lock
   package.json (if using)
   package-lock.json (if using)
   vite.config.js (if using)
   ```

3. **Create `.env` file** on server (copy from template: `Documentations/V1/env.v1.template`)

4. **Ensure directories exist and are writable:**
   ```
   storage/
   storage/app/
   storage/framework/
   storage/framework/cache/
   storage/framework/sessions/
   storage/framework/views/
   storage/logs/
   bootstrap/cache/
   ```

### **Option 2: Git (If you have Git on server)**

1. **SSH into server**
2. **Clone repository** (if using Git):
   ```bash
   cd public_html
   git clone [your-repo-url] V1
   ```
3. **Run composer install:**
   ```bash
   cd V1
   composer install --no-dev --optimize-autoloader
   ```
4. **Create `.env` file**
5. **Set permissions**

### **Option 3: Archive Upload (Recommended for initial setup)**

1. **Create a ZIP/TAR archive** locally (excluding files listed above)
2. **Upload archive** to server
3. **Extract** on server:
   ```bash
   cd public_html
   unzip V1.zip
   # or
   tar -xzf V1.tar.gz
   ```
4. **Create `.env` file**
5. **Run composer install** (if vendor/ wasn't included)
6. **Set permissions**

---

## 🔧 **POST-UPLOAD STEPS**

After uploading files, you need to:

1. **Create `.env` file:**
   - Copy content from `Documentations/V1/env.v1.template`
   - Save as `.env` in `public_html/V1/`

2. **Install dependencies** (if vendor/ wasn't uploaded):
   ```bash
   cd public_html/V1
   composer install --no-dev --optimize-autoloader
   ```

3. **Build assets** (if needed):
   ```bash
   npm install
   npm run build
   # or
   php artisan vite:build
   ```

4. **Set permissions:**
   ```bash
   cd public_html/V1
   chmod -R 755 storage bootstrap/cache
   chmod 644 .env
   chown -R www-data:www-data storage bootstrap/cache
   ```

5. **Create storage link:**
   ```bash
   php artisan storage:link
   ```

6. **Run migrations:**
   ```bash
   php artisan migrate
   ```

7. **Clear caches:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

---

## 📊 **QUICK CHECKLIST**

- [ ] Upload `app/` folder
- [ ] Upload `bootstrap/` folder
- [ ] Upload `config/` folder
- [ ] Upload `database/` folder
- [ ] Upload `public/` folder
- [ ] Upload `resources/` folder
- [ ] Upload `routes/` folder
- [ ] Upload `storage/` folder (ensure subdirectories exist)
- [ ] Upload `vendor/` folder OR run `composer install` on server
- [ ] Upload `artisan` file
- [ ] Upload `composer.json` and `composer.lock`
- [ ] Upload `package.json` and `package-lock.json` (if using)
- [ ] Upload `vite.config.js` (if using)
- [ ] Create `.env` file on server (from template)
- [ ] Set proper permissions
- [ ] Run migrations
- [ ] Clear caches

---

## ⚠️ **IMPORTANT NOTES**

1. **Storage Directory:** Make sure `storage/` and `bootstrap/cache/` directories exist and are writable

2. **Vendor Folder:** 
   - Option A: Upload `vendor/` folder (large, but faster)
   - Option B: Run `composer install` on server (requires Composer on server)

3. **Public Folder:** The subdomain should point to `public_html/V1/public`, NOT to `public_html/V1`

4. **Environment File:** Never upload your local `.env` file. Always create a new one on the server using the template.

5. **Permissions:** Storage and cache directories must be writable by the web server user (usually `www-data` or `apache`)

---

## 📁 **DIRECTORY STRUCTURE ON SERVER**

After upload, your structure should look like:

```
public_html/
├── V1/                          # Your V1 application
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/                  # Subdomain points here
│   │   ├── index.php
│   │   ├── .htaccess
│   │   └── ...
│   ├── resources/
│   ├── routes/
│   ├── storage/                 # Must be writable
│   ├── vendor/
│   ├── .env                     # Create this file
│   ├── artisan
│   ├── composer.json
│   └── ...
└── (main domain files)
```

---

**That's it!** Upload these files and you're ready to configure and test your V1 subdomain! 🚀
