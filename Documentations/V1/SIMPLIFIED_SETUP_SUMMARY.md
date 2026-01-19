# V1 Subdomain Setup - Simplified Summary

**Architecture:** Separate codebase in `public_html/V1`

## Quick Setup Steps

1. **Create subdomain in Hostinger** pointing to `public_html/V1/public`
2. **Deploy code** to `public_html/V1` directory
3. **Create `.env` file** in `public_html/V1` (use template: `Documentations/V1/env.v1.template`)
4. **Check existing migrations** - Database already has tables and data
5. **Run only new migrations** (if any) - Use `php artisan migrate:status` first
6. **Set permissions** and clear caches
7. **Test** the subdomain

## Database Credentials

-   Database: `u160871038_salpro`
-   User: `u160871038_salproj`
-   Password: `5vAx#zypro`
-   Host: `127.0.0.1`
-   **Status:** Database already contains existing tables and data

## Important Notes

-   **Database already populated** - Your V1 database (`u160871038_salpro`) already has old tables and data
-   **Check migrations status first** - Before running migrations, check what's already been run
-   **Run new migrations only** - Only run migrations that haven't been executed yet
-   **Be careful with migrations** - Some migrations might modify existing data structures

## Migration Steps (For Existing Database)

1. **Check migration status:**

    ```bash
    cd public_html/V1
    php artisan migrate:status
    ```

2. **Run only pending migrations:**

    ```bash
    php artisan migrate
    ```

    (Laravel will automatically skip migrations that have already been run)

3. **If migration table doesn't exist:**
    - Create migrations table: `php artisan migrate:install`
    - Then mark existing migrations as run (if needed)

## Key Points

-   **No middleware needed** - separate codebase handles everything
-   **Standard Laravel setup** - just deploy and configure `.env`
-   **Complete isolation** - separate code, database, and environment
-   **Existing database** - Database already has data, handle migrations carefully
