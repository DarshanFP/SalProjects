#!/bin/bash

# Database Environment Switch Script
# Purpose: Switch Laravel application to production database
# Date: January 2025

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Database Environment Switch Script${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo -e "${RED}Error: .env file not found!${NC}"
    exit 1
fi

# Backup current .env file
BACKUP_FILE=".env.backup_$(date +%Y%m%d_%H%M%S)"
echo -e "${YELLOW}Step 1: Backing up current .env file...${NC}"
cp .env "$BACKUP_FILE"
echo -e "${GREEN}✓ Backup created: $BACKUP_FILE${NC}"
echo ""

# Get current database info
CURRENT_DB=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2)
CURRENT_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2)
echo -e "${BLUE}Current Configuration:${NC}"
echo -e "  Database: ${CURRENT_DB}"
echo -e "  Host: ${CURRENT_HOST}"
echo ""

# Prompt for database type
echo -e "${YELLOW}Select target database:${NC}"
echo "  1) Production Database (u160871038_salprojects)"
echo "  2) Local/Development Database (projectsReports)"
echo "  3) Custom Database"
echo ""
read -p "Enter choice [1-3]: " choice

case $choice in
    1)
        TARGET_DB="u160871038_salprojects"
        echo -e "${YELLOW}Enter production database credentials:${NC}"
        read -p "DB_HOST [default: 127.0.0.1]: " DB_HOST
        DB_HOST=${DB_HOST:-127.0.0.1}
        read -p "DB_USERNAME: " DB_USERNAME
        read -sp "DB_PASSWORD: " DB_PASSWORD
        echo ""
        read -p "DB_PORT [default: 3306]: " DB_PORT
        DB_PORT=${DB_PORT:-3306}
        ;;
    2)
        TARGET_DB="projectsReports"
        DB_HOST="127.0.0.1"
        DB_USERNAME="root"
        DB_PASSWORD="root"
        DB_PORT="3306"
        echo -e "${GREEN}Switching to local development database...${NC}"
        ;;
    3)
        read -p "Database Name: " TARGET_DB
        read -p "DB_HOST [default: 127.0.0.1]: " DB_HOST
        DB_HOST=${DB_HOST:-127.0.0.1}
        read -p "DB_USERNAME: " DB_USERNAME
        read -sp "DB_PASSWORD: " DB_PASSWORD
        echo ""
        read -p "DB_PORT [default: 3306]: " DB_PORT
        DB_PORT=${DB_PORT:-3306}
        ;;
    *)
        echo -e "${RED}Invalid choice. Exiting.${NC}"
        exit 1
        ;;
esac

echo ""
echo -e "${YELLOW}Step 2: Updating .env file...${NC}"

# Update .env file
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    sed -i '' "s|^DB_DATABASE=.*|DB_DATABASE=$TARGET_DB|" .env
    sed -i '' "s|^DB_HOST=.*|DB_HOST=$DB_HOST|" .env
    sed -i '' "s|^DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|" .env
    sed -i '' "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|" .env
    sed -i '' "s|^DB_PORT=.*|DB_PORT=$DB_PORT|" .env
else
    # Linux
    sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$TARGET_DB|" .env
    sed -i "s|^DB_HOST=.*|DB_HOST=$DB_HOST|" .env
    sed -i "s|^DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|" .env
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|" .env
    sed -i "s|^DB_PORT=.*|DB_PORT=$DB_PORT|" .env
fi

echo -e "${GREEN}✓ .env file updated${NC}"
echo ""

# Clear Laravel cache
echo -e "${YELLOW}Step 3: Clearing Laravel cache...${NC}"
php artisan config:clear 2>/dev/null || echo -e "${YELLOW}⚠ Config cache clear skipped${NC}"
php artisan cache:clear 2>/dev/null || echo -e "${YELLOW}⚠ Application cache clear skipped${NC}"
echo -e "${GREEN}✓ Cache cleared${NC}"
echo ""

# Test database connection
echo -e "${YELLOW}Step 4: Testing database connection...${NC}"
if php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'SUCCESS'; } catch (Exception \$e) { echo 'ERROR: ' . \$e->getMessage(); }" 2>&1 | grep -q "SUCCESS"; then
    echo -e "${GREEN}✓ Database connection successful${NC}"

    # Get database info
    DB_NAME=$(php artisan tinker --execute="echo DB::connection()->getDatabaseName();" 2>&1 | tail -1)
    TABLE_COUNT=$(php artisan tinker --execute="echo count(DB::select('SHOW TABLES'));" 2>&1 | tail -1)

    echo ""
    echo -e "${GREEN}Connection Details:${NC}"
    echo -e "  Database: ${DB_NAME}"
    echo -e "  Total Tables: ${TABLE_COUNT}"
    echo ""

    # Check migration status
    echo -e "${YELLOW}Step 5: Checking migration status...${NC}"
    PENDING=$(php artisan migrate:status 2>&1 | grep -i "Pending\|Not Run" | wc -l | tr -d ' ')
    TOTAL=$(php artisan migrate:status 2>&1 | grep -E "Ran|Pending|Not Run" | wc -l | tr -d ' ')

    echo -e "  Total Migrations: ${TOTAL}"
    echo -e "  Pending Migrations: ${PENDING}"

    if [ "$PENDING" -gt 0 ]; then
        echo ""
        echo -e "${YELLOW}⚠ There are ${PENDING} pending migrations${NC}"
        echo -e "${YELLOW}Run 'php artisan migrate' to execute them${NC}"
    else
        echo -e "${GREEN}✓ All migrations are up to date${NC}"
    fi
else
    echo -e "${RED}✗ Database connection failed!${NC}"
    echo -e "${YELLOW}Restoring backup...${NC}"
    cp "$BACKUP_FILE" .env
    php artisan config:clear 2>/dev/null
    echo -e "${GREEN}✓ .env file restored from backup${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}✓ Environment switch completed successfully!${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${BLUE}Summary:${NC}"
echo -e "  Previous Database: ${CURRENT_DB}"
echo -e "  Current Database: ${TARGET_DB}"
echo -e "  Backup File: ${BACKUP_FILE}"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
if [ "$PENDING" -gt 0 ]; then
    echo -e "  1. Review pending migrations"
    echo -e "  2. Backup database (if production): mysqldump -h ${DB_HOST} -u ${DB_USERNAME} -p ${TARGET_DB} > backup_$(date +%Y%m%d_%H%M%S).sql"
    echo -e "  3. Run migrations: php artisan migrate"
    echo -e "  4. Verify migrations: php artisan migrate:status"
else
    echo -e "  ✓ All migrations are up to date"
    echo -e "  ✓ Ready to use"
fi
echo ""
