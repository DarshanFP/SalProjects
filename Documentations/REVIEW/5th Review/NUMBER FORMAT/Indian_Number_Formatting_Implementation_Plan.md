# Indian Number Formatting Implementation Plan

## Overview
This document outlines the comprehensive plan to convert all number/amount formatting from American style (1,000,000) to Indian style (10,00,000) throughout the application. Indian numbering uses a different grouping pattern:
- First 3 digits from right: hundreds, tens, units
- Then every 2 digits: lakhs (1,00,000), crores (1,00,00,000)

## Current Situation Analysis

### American Style Format (Current)
- Example: 1,000,000 (One Million)
- Grouping: Every 3 digits from right

### Indian Style Format (Target)
- Example: 10,00,000 (Ten Lakh)
- Grouping: First 3 digits, then every 2 digits
- Example: 1,00,00,000 (One Crore)

## Files Requiring Updates

### Category 1: Blade Template Files (PHP `number_format()`)

#### 1.1 Project Views (21 files)
1. `resources/views/projects/partials/Show/budget.blade.php`
   - Line 247-250: Budget table amounts (rate_quantity, rate_multiplier, rate_duration, this_phase)
   
2. `resources/views/projects/partials/Show/general_info.blade.php`
   - Line 103, 107, 111, 115, 119: Project budget fields (overall_budget, amount_forwarded, local_contribution, amount_sanctioned, opening_balance)

3. `resources/views/projects/partials/Show/IAH/budget_details.blade.php`
   - Multiple budget amount fields

4. `resources/views/projects/partials/Show/IIES/estimated_expenses.blade.php`
   - Estimated expenses amounts

5. `resources/views/projects/partials/Show/IIES/personal_info.blade.php`
   - Income fields

6. `resources/views/projects/partials/Show/IIES/family_working_members.blade.php`
   - Income amounts

7. `resources/views/projects/partials/Show/IAH/earning_members.blade.php`
   - Earning member amounts

8. `resources/views/projects/partials/Show/IES/personal_info.blade.php`
   - Personal information amounts

9. `resources/views/projects/partials/Show/attachments.blade.php`
   - File size or related amounts

10. `resources/views/projects/partials/not working show/IIES/personal_info.blade.php`
    - Income fields

11. `resources/views/projects/partials/not working show/IIES/family_working_members.blade.php`
    - Monthly income (line 30)

12. `resources/views/projects/partials/not working show/IES/personal_info.blade.php`
    - Personal info amounts

13. `resources/views/projects/partials/not working show/IAH/earning_members.blade.php`
    - Earning member amounts

14. `resources/views/projects/partials/not working show/budget.blade.php`
    - Budget amounts

15. `resources/views/projects/partials/not working show/IIES/estimated_expenses.blade.php`
    - Estimated expenses

16. `resources/views/projects/partials/not working show/IAH/budget_details.blade.php`
    - Budget details

17. `resources/views/projects/partials/not working show/general_info.blade.php`
    - General info amounts

18. `resources/views/projects/partials/OLdshow/general_info.blade.php`
    - General info amounts

19. `resources/views/projects/partials/OLdshow/budget.blade.php`
    - Budget amounts

20. `resources/views/projects/Oldprojects/pdf.blade.php`
    - Line 794, 798: PDF amounts (amount_sanctioned, amount_forwarded, local_contribution)

21. `resources/views/projects/exports/budget-pdf.blade.php`
    - Budget export amounts

22. `resources/views/projects/exports/budget-report.blade.php`
    - Budget report amounts

#### 1.2 Report Views (15 files)
23. `resources/views/reports/monthly/PDFReport.blade.php`
    - Line 336-341, 347: Budget amounts in PDF

24. `resources/views/reports/monthly/PDFReport/statements_of_account.blade.php`
    - Line 19-21: Budget amounts (budget_amount, expenditure, balance)

25. `resources/views/reports/monthly/pdf.blade.php`
    - Line 190, 194, 198, 217-222: Account amounts

26. `resources/views/reports/monthly/doc-copy.blade`
    - Line 183-230: Document copy amounts

27. `resources/views/reports/monthly/partials/view/statements_of_account/individual_livelihood.blade.php`
    - Line 12, 14, 16, 79, 86, 93, 100, 107, 114-115, 129, 134, 140, 145, 188-204: Multiple amount fields

28. `resources/views/reports/monthly/partials/view/statements_of_account/individual_education.blade.php`
    - Similar amount fields as individual_livelihood

29. `resources/views/reports/monthly/partials/view/statements_of_account/individual_health.blade.php`
    - Similar amount fields

30. `resources/views/reports/monthly/partials/view/statements_of_account/individual_ongoing_education.blade.php`
    - Similar amount fields

31. `resources/views/reports/monthly/partials/view/statements_of_account/institutional_education.blade.php`
    - Similar amount fields

32. `resources/views/reports/monthly/partials/view/statements_of_account/development_projects.blade.php`
    - Similar amount fields

33. `resources/views/reports/monthly/partials/view/LivelihoodAnnexure.blade.php`
    - Amount fields

34. `resources/views/reports/aggregated/quarterly/show.blade.php`
    - Line 206-211: Aggregated amounts (opening_balance, amount_forwarded, amount_sanctioned, total_amount, total_expenses, closing_balance)

35. `resources/views/reports/monthly/partials/edit/attachments.blade.php`
    - Any amount fields

#### 1.3 Dashboard & Widget Views (13 files)
36. `resources/views/executor/approvedReports.blade.php`
    - Line 40, 48, 56, 96-99: Budget summaries and report amounts

37. `resources/views/executor/pendingReports.blade.php`
    - Line 44, 52, 60, 100-103: Similar to approvedReports

38. `resources/views/executor/ReportList.blade.php`
    - Report list amounts

39. `resources/views/executor/index.blade.php`
    - Dashboard amounts

40. `resources/views/executor/widgets/quick-stats.blade.php`
    - Line 52, 95, 117, 144, 170: Quick stats amounts and percentages

41. `resources/views/executor/widgets/budget-analytics.blade.php`
    - Budget analytics (also has JavaScript toLocaleString issues)

42. `resources/views/executor/widgets/report-analytics.blade.php`
    - Report analytics

43. `resources/views/coordinator/approvedReports.blade.php`
    - Line 63, 71, 79, 122-125: Budget summaries

44. `resources/views/coordinator/pendingReports.blade.php`
    - Similar to approvedReports

45. `resources/views/coordinator/ProjectList.blade.php`
    - Project list amounts

46. `resources/views/coordinator/approvedProjects.blade.php`
    - Line 109-110: amount_sanctioned, amount_forwarded

47. `resources/views/coordinator/budget-overview.blade.php`
    - Budget overview (also has JavaScript issues)

48. `resources/views/coordinator/budgets.blade.php`
    - Budget amounts

49. `resources/views/coordinator/index.blade.php`
    - Dashboard amounts

50. `resources/views/coordinator/ProjectList-copy.blade`
    - Project list copy

51. `resources/views/provincial/approvedReports.blade.php`
    - Line 63, 71, 79, 122-125: Budget summaries

52. `resources/views/provincial/pendingReports.blade.php`
    - Similar amounts

53. `resources/views/provincial/approvedProjects.blade.php`
    - Line 94-95: amount_sanctioned, amount_forwarded

54. `resources/views/provincial/ProjectList.blade.php`
    - Line 179, 182, 198, 207: Project amounts and utilization percentages

55. `resources/views/provincial/index.blade.php`
    - Line 174, 182, 190, 218-220, 251-253: Dashboard budget summaries

56. `resources/views/provincial/ReportList.blade.php`
    - Report list amounts

57. `resources/views/provincial/widgets/team-overview.blade.php`
    - Line 143: Approval rate percentage

58. `resources/views/provincial/widgets/team-budget-overview.blade.php`
    - Budget overview amounts (also has JavaScript issues)

59. `resources/views/provincial/widgets/team-performance.blade.php`
    - Performance amounts (also has JavaScript issues)

60. `resources/views/provincial/widgets/center-comparison.blade.php`
    - Line 139-142, 146: Center comparison amounts (also has JavaScript issues)

61. `resources/views/projects/partials/Edit/attachment.blade.php`
    - Attachment related amounts

### Category 2: JavaScript Files (toLocaleString Issues)

#### 2.1 Files Using `toLocaleString('en-IN')` - Already Correct (19 files)
These files already use Indian locale, but need verification:
1. `resources/views/reports/monthly/partials/statements_of_account/individual_education.blade.php` (Line 453-457)
2. `resources/views/reports/monthly/partials/statements_of_account/individual_health.blade.php` (Line 453-457)
3. `resources/views/reports/monthly/partials/statements_of_account/individual_livelihood.blade.php` (Line 453-457)
4. `resources/views/reports/monthly/partials/statements_of_account/institutional_education.blade.php` (Line 426-430)
5. `resources/views/reports/monthly/partials/statements_of_account/individual_ongoing_education.blade.php` (Line 409-413)
6. `resources/views/reports/monthly/partials/statements_of_account/development_projects.blade.php` (Line 453-457)
7. `resources/views/reports/monthly/partials/create/statements_of_account.blade.php` (Line 418-422)
8. `resources/views/reports/monthly/partials/edit/statements_of_account/individual_education.blade.php` (Line 439-443)
9. `resources/views/reports/monthly/partials/edit/statements_of_account/individual_health.blade.php` (Line 439-443)
10. `resources/views/reports/monthly/partials/edit/statements_of_account/individual_livelihood.blade.php` (Line 439-443)
11. `resources/views/reports/monthly/partials/edit/statements_of_account/institutional_education.blade.php` (Line 439-443)
12. `resources/views/reports/monthly/partials/edit/statements_of_account/individual_ongoing_education.blade.php` (Line 423-427)
13. `resources/views/reports/monthly/partials/edit/statements_of_account/development_projects.blade.php` (Line 439)
14. `resources/views/projects/partials/Show/budget.blade.php` (Line 551, 568, 579, 613, 622, 632)
15. `resources/views/coordinator/budget-overview.blade.php` (Line 493, 582-584)
16. `resources/views/provincial/widgets/team-budget-overview.blade.php` (Line 325, 360, 419, 485)
17. `resources/views/provincial/widgets/center-comparison.blade.php` (Line 362)
18. `resources/views/provincial/widgets/team-performance.blade.php` (Line 423, 483)

#### 2.2 Files Using `toLocaleString('en-US')` - Needs Fixing (1 file)
1. `resources/views/executor/widgets/budget-analytics.blade.php`
   - Line 233, 273, 287, 393, 500: Change 'en-US' to 'en-IN'

### Category 3: PHP Controller/Service Files

1. `app/Http/Requests/Projects/UpdateGeneralInfoRequest.php`
   - Line 65: Error message with number_format()

2. `app/Services/BudgetValidationService.php`
   - Line 249, 274, 290: Validation messages with number_format()

3. `app/Http/Controllers/Projects/ExportController.php`
   - Line 2115-2130: Export amounts using number_format()

4. `app/Http/Controllers/Reports/Monthly/ExportReportController.php`
   - PDF export amounts

5. `app/Http/Controllers/Reports/Aggregated/AggregatedReportExportController.php`
   - Aggregated report amounts

6. `app/Http/Controllers/Projects/BudgetExportController.php`
   - Budget export amounts

### Category 4: DataTables Configuration

The DataTables library uses its own number formatting. We need to:
1. Configure DataTables to use Indian number format globally
2. Update DataTables language settings in configuration files
3. Check any custom DataTables render functions

Location: `public/backend/assets/vendors/datatables.net/jquery.dataTables.js`
- Line 10950-10954: Default formatNumber function
- Line 15368-15419: DataTable.render.number function
- Line 15413: Uses regex `/\B(?=(\d{3})+(?!\d))/g` for grouping - needs custom function

## Implementation Strategy

### Phase 1: Create Helper Functions

#### 1.1 PHP Helper Function
Create: `app/Helpers/NumberFormatHelper.php`

```php
<?php

namespace App\Helpers;

class NumberFormatHelper
{
    /**
     * Format number in Indian style (lakhs, crores)
     * Example: 1000000 becomes "10,00,000"
     * 
     * @param float|int $number
     * @param int $decimals Number of decimal places
     * @return string
     */
    public static function formatIndian($number, $decimals = 2)
    {
        if ($number == 0) {
            return number_format(0, $decimals, '.', ',');
        }

        // Handle negative numbers
        $negative = $number < 0;
        $number = abs($number);

        // Split into integer and decimal parts
        $parts = explode('.', number_format($number, $decimals, '.', ''));
        $integerPart = $parts[0];
        $decimalPart = isset($parts[1]) ? $parts[1] : '';

        // Format integer part in Indian style
        $formattedInteger = '';
        $length = strlen($integerPart);
        
        if ($length <= 3) {
            $formattedInteger = $integerPart;
        } else {
            // First 3 digits from right
            $formattedInteger = substr($integerPart, -3);
            $remaining = substr($integerPart, 0, -3);
            
            // Then every 2 digits
            while (strlen($remaining) > 2) {
                $formattedInteger = substr($remaining, -2) . ',' . $formattedInteger;
                $remaining = substr($remaining, 0, -2);
            }
            
            if (strlen($remaining) > 0) {
                $formattedInteger = $remaining . ',' . $formattedInteger;
            }
        }

        $result = $formattedInteger;
        if ($decimals > 0 && !empty($decimalPart)) {
            $result .= '.' . $decimalPart;
        }

        return ($negative ? '-' : '') . $result;
    }

    /**
     * Format currency in Indian style with Rs. prefix
     * 
     * @param float|int $number
     * @param int $decimals
     * @return string
     */
    public static function formatIndianCurrency($number, $decimals = 2)
    {
        return 'Rs. ' . self::formatIndian($number, $decimals);
    }

    /**
     * Format percentage
     * 
     * @param float|int $number
     * @param int $decimals
     * @return string
     */
    public static function formatPercentage($number, $decimals = 1)
    {
        return self::formatIndian($number, $decimals) . '%';
    }
}
```

#### 1.2 Register Helper Function
Add to `bootstrap/app.php` or create a service provider to register the helper globally:

```php
if (!function_exists('format_indian')) {
    function format_indian($number, $decimals = 2) {
        return \App\Helpers\NumberFormatHelper::formatIndian($number, $decimals);
    }
}

if (!function_exists('format_indian_currency')) {
    function format_indian_currency($number, $decimals = 2) {
        return \App\Helpers\NumberFormatHelper::formatIndianCurrency($number, $decimals);
    }
}
```

#### 1.3 JavaScript Helper Function
Create: `public/js/indian-number-format.js`

```javascript
/**
 * Format number in Indian style (lakhs, crores)
 * Example: 1000000 becomes "10,00,000"
 * 
 * @param {number} number - The number to format
 * @param {number} decimals - Number of decimal places
 * @returns {string} Formatted number string
 */
function formatIndianNumber(number, decimals = 2) {
    if (number == 0) {
        return parseFloat(0).toFixed(decimals);
    }

    const negative = number < 0;
    number = Math.abs(number);

    // Split into integer and decimal parts
    const fixed = number.toFixed(decimals);
    const parts = fixed.split('.');
    let integerPart = parts[0];
    const decimalPart = parts[1] || '';

    // Format integer part in Indian style
    let formattedInteger = '';
    const length = integerPart.length;

    if (length <= 3) {
        formattedInteger = integerPart;
    } else {
        // First 3 digits from right
        formattedInteger = integerPart.slice(-3);
        let remaining = integerPart.slice(0, -3);

        // Then every 2 digits
        while (remaining.length > 2) {
            formattedInteger = remaining.slice(-2) + ',' + formattedInteger;
            remaining = remaining.slice(0, -2);
        }

        if (remaining.length > 0) {
            formattedInteger = remaining + ',' + formattedInteger;
        }
    }

    let result = formattedInteger;
    if (decimals > 0 && decimalPart) {
        result += '.' + decimalPart;
    }

    return (negative ? '-' : '') + result;
}

/**
 * Format currency in Indian style with Rs. prefix
 * 
 * @param {number} number - The number to format
 * @param {number} decimals - Number of decimal places
 * @returns {string} Formatted currency string
 */
function formatIndianCurrency(number, decimals = 2) {
    return 'Rs. ' + formatIndianNumber(number, decimals);
}

/**
 * Format number using toLocaleString with Indian locale
 * This is a wrapper that ensures consistent Indian formatting
 * 
 * @param {number} number - The number to format
 * @param {Object} options - Intl.NumberFormat options
 * @returns {string} Formatted number string
 */
function formatIndianLocale(number, options = {}) {
    const defaultOptions = {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        ...options
    };
    
    return number.toLocaleString('en-IN', defaultOptions);
}
```

### Phase 2: Update Blade Templates

#### Strategy for Blade Files:
1. Replace `number_format($amount, 2)` with `format_indian_currency($amount, 2)`
2. Replace `number_format($amount, 1)` with `format_indian($amount, 1)` for percentages
3. Replace `number_format($amount)` with `format_indian($amount, 0)` for integers

#### Example Replacement:
```php
// Before
<td>Rs. {{ number_format($budget->amount, 2) }}</td>

// After
<td>{{ format_indian_currency($budget->amount, 2) }}</td>
```

### Phase 3: Update JavaScript Files

#### Strategy for JavaScript Files:
1. Replace `toLocaleString('en-US', ...)` with `toLocaleString('en-IN', ...)`
2. For custom formatting, use `formatIndianCurrency()` or `formatIndianLocale()`
3. Update DataTables number renderers

#### Example Replacement:
```javascript
// Before
val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

// After
val.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

// Or use custom function
formatIndianCurrency(val, 2)
```

### Phase 4: DataTables Configuration

#### Global DataTables Configuration
Create: `public/js/datatables-indian-config.js`

```javascript
/**
 * Configure DataTables for Indian number formatting
 */
(function() {
    if (typeof $.fn.dataTable !== 'undefined') {
        // Override default formatNumber function
        $.fn.dataTable.defaults.formatNumber = function(toFormat) {
            return formatIndianNumber(toFormat, 0);
        };

        // Override number renderer
        $.fn.dataTable.render.number = function(thousands, decimal, precision, prefix, postfix) {
            return {
                display: function(d) {
                    if (typeof d !== 'number' && typeof d !== 'string') {
                        return d;
                    }

                    if (d === '' || d === null) {
                        return d;
                    }

                    const number = parseFloat(d);
                    if (isNaN(number)) {
                        return d;
                    }

                    const formatted = formatIndianNumber(number, precision || 0);
                    return (prefix || '') + formatted + (postfix || '');
                }
            };
        };
    }
})();
```

Include this script after DataTables but before initializing tables.

### Phase 5: CSS-Only Solution (Limited Use)

CSS cannot format numbers, but we can use CSS for visual consistency after server-side/JavaScript formatting. However, for actual number formatting, we must use PHP or JavaScript.

CSS can help with:
- Consistent font styling for numbers
- Right-aligning numeric columns
- Spacing for formatted numbers

Example CSS:
```css
.amount-cell {
    text-align: right;
    font-family: 'Courier New', monospace; /* Monospace for consistent digit width */
    letter-spacing: 0.02em;
}
```

## Implementation Checklist

### Phase 1: Setup (Priority: High)
- [ ] Create `app/Helpers/NumberFormatHelper.php`
- [ ] Create `public/js/indian-number-format.js`
- [ ] Register PHP helper functions globally
- [ ] Test helper functions with various numbers

### Phase 2: Core Files (Priority: High)
- [ ] Update all project view files (Category 1.1)
- [ ] Update all report view files (Category 1.2)
- [ ] Update dashboard/widget files (Category 1.3)
- [ ] Update PHP controller/service files (Category 3)

### Phase 3: JavaScript Updates (Priority: Medium)
- [ ] Fix `executor/widgets/budget-analytics.blade.php` en-US to en-IN
- [ ] Verify all en-IN locales are working correctly
- [ ] Update DataTables configuration
- [ ] Test DataTables number rendering

### Phase 4: PDF & Export Files (Priority: Medium)
- [ ] Update PDF generation templates
- [ ] Update export controllers
- [ ] Test PDF output
- [ ] Test Excel/Word exports

### Phase 5: Testing (Priority: High)
- [ ] Test all project views
- [ ] Test all report views
- [ ] Test all dashboard views
- [ ] Test PDF generation
- [ ] Test exports
- [ ] Test with large numbers (lakhs, crores)
- [ ] Test with decimal numbers
- [ ] Test with negative numbers
- [ ] Test with zero

### Phase 6: Documentation (Priority: Low)
- [ ] Update developer documentation
- [ ] Create usage examples
- [ ] Document helper functions

## Testing Strategy

### Unit Tests
Create: `tests/Unit/NumberFormatHelperTest.php`

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\NumberFormatHelper;

class NumberFormatHelperTest extends TestCase
{
    public function test_format_indian_with_thousands()
    {
        $this->assertEquals('1,000', NumberFormatHelper::formatIndian(1000));
        $this->assertEquals('10,000', NumberFormatHelper::formatIndian(10000));
        $this->assertEquals('1,00,000', NumberFormatHelper::formatIndian(100000));
        $this->assertEquals('10,00,000', NumberFormatHelper::formatIndian(1000000));
        $this->assertEquals('1,00,00,000', NumberFormatHelper::formatIndian(10000000));
    }

    public function test_format_indian_with_decimals()
    {
        $this->assertEquals('1,00,000.50', NumberFormatHelper::formatIndian(100000.50, 2));
    }

    public function test_format_indian_currency()
    {
        $this->assertEquals('Rs. 10,00,000', NumberFormatHelper::formatIndianCurrency(1000000, 0));
    }

    public function test_format_indian_with_zero()
    {
        $this->assertEquals('0.00', NumberFormatHelper::formatIndian(0));
    }
}
```

### Manual Testing Checklist
1. **View all project pages** and verify amounts are formatted correctly
2. **View all report pages** and verify amounts are formatted correctly
3. **Generate PDFs** and verify amounts in PDFs
4. **Export data** and verify exported amounts
5. **Check dashboards** for correctly formatted statistics
6. **Test with edge cases**: very large numbers, very small numbers, zero, negative

## Rollback Plan

If issues arise:
1. Keep original `number_format()` calls in git history
2. Create a feature flag to switch between Indian and American formatting
3. Revert helper function if needed
4. Use git revert for specific commits

## Performance Considerations

- Helper function overhead is minimal (single function call per format)
- JavaScript function is lightweight
- No database queries involved
- Consider caching formatted values for frequently accessed amounts

## Future Enhancements

1. Add currency symbols (₹ instead of Rs.)
2. Support for different Indian languages (Hindi, Tamil, etc.)
3. Configurable formatting per user preference
4. Add unit labels (lakh, crore) automatically
5. Localization support for other regions

## Notes

- **Vendor Files**: Do not modify vendor files (like DataTables). Instead, override configuration
- **Backward Compatibility**: Ensure existing functionality is not broken
- **Browser Support**: `toLocaleString('en-IN')` is supported in modern browsers. For older browsers, use custom JavaScript function
- **Decimal Places**: Standardize decimal places (2 for currency, 1 for percentages, 0 for counts)

## References

- [Indian numbering system - Wikipedia](https://en.wikipedia.org/wiki/Indian_numbering_system)
- [PHP NumberFormatter](https://www.php.net/manual/en/class.numberformatter.php)
- [JavaScript Intl.NumberFormat](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Intl/NumberFormat)
- [Laravel Number Helper](https://laravel.com/docs/collections#method-number)

---

**Document Created**: [Current Date]
**Last Updated**: [Current Date]
**Status**: Draft - Ready for Implementation
