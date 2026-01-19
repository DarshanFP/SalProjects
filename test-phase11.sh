#!/bin/bash

# Phase 11: Automated Test Execution Script
# Run this script to execute automated tests for Report Views Enhancement

echo "=========================================="
echo "Phase 11: Automated Test Execution"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: artisan file not found. Please run this script from Laravel root directory.${NC}"
    exit 1
fi

# Step 1: Run PHPUnit Feature Tests
echo -e "${BLUE}Step 1: Running PHPUnit Feature Tests...${NC}"
echo ""

php artisan test --filter ReportViewsIndexing

PHPUNIT_EXIT_CODE=$?

echo ""
if [ $PHPUNIT_EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}✅ PHPUnit tests passed${NC}"
else
    echo -e "${RED}❌ PHPUnit tests failed (Exit code: $PHPUNIT_EXIT_CODE)${NC}"
fi

echo ""
echo "=========================================="
echo "Step 2: Browser Console Tests"
echo "=========================================="
echo ""
echo -e "${YELLOW}Instructions:${NC}"
echo "1. Start Laravel development server:"
echo "   ${BLUE}php artisan serve${NC}"
echo ""
echo "2. Open report create/edit page in browser"
echo "   Example: http://localhost:8000/reports/monthly/create/{project_id}"
echo ""
echo "3. Open browser console (F12)"
echo ""
echo "4. Copy contents of: resources/js/test-phase11-browser-console.js"
echo "   Paste into console and press Enter"
echo ""
echo "5. Run: ${BLUE}runAllTests()${NC}"
echo ""

echo "=========================================="
echo "Step 3: Manual Testing Checklist"
echo "=========================================="
echo ""
echo "Please follow the manual testing procedure in:"
echo "Documentations/REVIEW/5th Review/Report Views/Phase_11_Test_Script_Runner.md"
echo ""

echo "=========================================="
echo "Test Script Execution Complete"
echo "=========================================="
echo ""
echo "Next Steps:"
echo "1. Review PHPUnit test results above"
echo "2. Run browser console tests on report pages"
echo "3. Complete manual testing checklist"
echo "4. Document results in Phase_11_Test_Results.md"
echo ""

# Return exit code
exit $PHPUNIT_EXIT_CODE
