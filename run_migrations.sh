#!/bin/bash

# Run Database Migrations Script
# Purpose: Run pending migrations with safety checks
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
echo -e "${BLUE}Database Migration Runner${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Check current database
CURRENT_DB=$(php artisan tinker --execute="echo DB::connection()->getDatabaseName();" 2>&1 | tail -1)
CURRENT_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2)

echo -e "${BLUE}Current Database Configuration:${NC}"
echo -e "  Database: ${CURRENT_DB}"
echo -e "  Host: ${CURRENT_HOST}"
echo ""

# Check if production database
if [[ "$CURRENT_DB" == *"u160871038"* ]] || [[ "$CURRENT_HOST" != "127.0.0.1" ]]; then
    echo -e "${RED}⚠ WARNING: You are connected to PRODUCTION database!${NC}"
    echo ""
    read -p "Have you backed up the production database? (yes/no): " backup_confirm
    if [ "$backup_confirm" != "yes" ]; then
        echo -e "${RED}Migration cancelled. Please backup database first.${NC}"
        echo -e "${YELLOW}Backup command: mysqldump -h ${CURRENT_HOST} -u <username> -p ${CURRENT_DB} > backup_$(date +%Y%m%d_%H%M%S).sql${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓ Backup confirmed${NC}"
    echo ""
fi

# Check migration status
echo -e "${YELLOW}Checking migration status...${NC}"
PENDING=$(php artisan migrate:status 2>&1 | grep -i "Pending\|Not Run" | wc -l | tr -d ' ')
TOTAL=$(php artisan migrate:status 2>&1 | grep -E "Ran|Pending|Not Run" | wc -l | tr -d ' ')

echo -e "  Total Migrations: ${TOTAL}"
echo -e "  Pending Migrations: ${PENDING}"
echo ""

if [ "$PENDING" -eq 0 ]; then
    echo -e "${GREEN}✓ All migrations are already up to date!${NC}"
    exit 0
fi

# Show pending migrations
echo -e "${YELLOW}Pending Migrations:${NC}"
php artisan migrate:status 2>&1 | grep -i "Pending\|Not Run" | head -10
if [ "$PENDING" -gt 10 ]; then
    echo -e "  ... and $((PENDING - 10)) more"
fi
echo ""

# Ask for confirmation
read -p "Do you want to run ${PENDING} pending migration(s)? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo -e "${YELLOW}Migration cancelled.${NC}"
    exit 0
fi

echo ""
echo -e "${YELLOW}Running migrations...${NC}"
echo ""

# Run migrations
if php artisan migrate --force 2>&1; then
    echo ""
    echo -e "${GREEN}✓ Migrations completed successfully!${NC}"
    echo ""

    # Verify migration status
    echo -e "${YELLOW}Verifying migration status...${NC}"
    NEW_PENDING=$(php artisan migrate:status 2>&1 | grep -i "Pending\|Not Run" | wc -l | tr -d ' ')

    if [ "$NEW_PENDING" -eq 0 ]; then
        echo -e "${GREEN}✓ All migrations are now up to date!${NC}"

        # Get table count
        TABLE_COUNT=$(php artisan tinker --execute="echo count(DB::select('SHOW TABLES'));" 2>&1 | tail -1)
        echo -e "${GREEN}✓ Total tables in database: ${TABLE_COUNT}${NC}"
    else
        echo -e "${YELLOW}⚠ ${NEW_PENDING} migrations still pending${NC}"
    fi

    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${GREEN}✓ Migration process completed!${NC}"
    echo -e "${BLUE}========================================${NC}"
else
    echo ""
    echo -e "${RED}✗ Migration failed!${NC}"
    echo -e "${YELLOW}Check the error messages above and Laravel log:${NC}"
    echo -e "  tail -100 storage/logs/laravel.log"
    exit 1
fi
