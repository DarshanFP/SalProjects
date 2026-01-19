#!/bin/bash

# Switch to Local Development Database Script
# Purpose: Quickly switch back to local development database
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
echo -e "${BLUE}Switch to Local Development Database${NC}"
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
echo -e "${BLUE}Current Database: ${CURRENT_DB}${NC}"
echo ""

# Local development database settings
TARGET_DB="projectsReports"
DB_HOST="127.0.0.1"
DB_USERNAME="root"
DB_PASSWORD="root"
DB_PORT="3306"

echo -e "${YELLOW}Step 2: Updating .env file to local database...${NC}"

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

    echo -e "${GREEN}✓ Successfully switched to local development database${NC}"
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
echo -e "${GREEN}✓ Switch completed successfully!${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${BLUE}Summary:${NC}"
echo -e "  Database: ${TARGET_DB}"
echo -e "  Host: ${DB_HOST}"
echo -e "  Backup File: ${BACKUP_FILE}"
echo ""
