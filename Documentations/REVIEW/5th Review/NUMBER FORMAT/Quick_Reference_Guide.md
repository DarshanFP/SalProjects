# Quick Reference Guide - Indian Number Formatting

This guide provides quick examples and patterns for converting American number formatting to Indian number formatting.

## Number Format Examples

### American Style → Indian Style
| American Format | Indian Format | Value |
|----------------|---------------|-------|
| 1,000 | 1,000 | One Thousand |
| 10,000 | 10,000 | Ten Thousand |
| 1,00,000 | 1,00,000 | One Lakh |
| 10,00,000 | 10,00,000 | Ten Lakh |
| 1,00,00,000 | 1,00,00,000 | One Crore |
| 10,00,00,000 | 10,00,00,000 | Ten Crore |

## Code Patterns

### PHP (Blade Templates)

#### Pattern 1: Currency Amounts (2 decimals)
```php
// ❌ Before (American style)
<td>Rs. {{ number_format($amount, 2) }}</td>
// Output: Rs. 1,000,000.00

// ✅ After (Indian style)
<td>{{ format_indian_currency($amount, 2) }}</td>
// Output: Rs. 10,00,000.00
```

#### Pattern 2: Numbers without Currency (2 decimals)
```php
// ❌ Before
<td>{{ number_format($amount, 2) }}</td>

// ✅ After
<td>{{ format_indian($amount, 2) }}</td>
```

#### Pattern 3: Percentages (1 decimal)
```php
// ❌ Before
<td>{{ number_format($percentage, 1) }}%</td>

// ✅ After
<td>{{ format_indian($percentage, 1) }}%</td>
// Or use helper
<td>{{ format_indian_percentage($percentage, 1) }}</td>
```

#### Pattern 4: Integers (no decimals)
```php
// ❌ Before
<td>{{ number_format($count) }}</td>

// ✅ After
<td>{{ format_indian($count, 0) }}</td>
```

#### Pattern 5: In PHP Controllers/Services
```php
// ❌ Before
$message = 'Amount cannot exceed Rs. ' . number_format($budget, 2);

// ✅ After
use App\Helpers\NumberFormatHelper;

$message = 'Amount cannot exceed ' . NumberFormatHelper::formatIndianCurrency($budget, 2);
```

### JavaScript (In Blade Templates)

#### Pattern 1: Currency with toLocaleString
```javascript
// ❌ Before (American)
cardTotalBudget.textContent = 'Rs. ' + totalBudget.toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
});

// ✅ After (Indian)
cardTotalBudget.textContent = 'Rs. ' + totalBudget.toLocaleString('en-IN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
});
```

#### Pattern 2: Using Custom Function
```javascript
// ✅ Using custom helper function
cardTotalBudget.textContent = formatIndianCurrency(totalBudget, 2);
```

#### Pattern 3: In DataTables Render Functions
```javascript
// ❌ Before
render: DataTable.render.number(',', '.', 2, 'Rs. ', '')

// ✅ After (custom function)
render: function(data, type, row) {
    if (type === 'display' || type === 'filter') {
        return formatIndianCurrency(parseFloat(data) || 0, 2);
    }
    return data;
}
```

#### Pattern 4: Chart.js or Other Libraries
```javascript
// ✅ Using en-IN locale
formatter: function(value) {
    return 'Rs. ' + value.toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
```

## Common Replacements

### Search & Replace Patterns

#### PHP Blade Templates
```
Search:  number_format($([^,)]+), 2)
Replace: format_indian_currency($1, 2)

Search:  number_format($([^,)]+), 1)
Replace: format_indian($1, 1)

Search:  number_format($([^,)]+))
Replace: format_indian($1, 0)
```

#### JavaScript
```
Search:  toLocaleString\('en-US'
Replace: toLocaleString('en-IN'

Search:  .toLocaleString\('en-US',
Replace: .toLocaleString('en-IN',
```

## Function Reference

### PHP Helper Functions

#### `format_indian($number, $decimals = 2)`
Formats a number in Indian style without currency symbol.

**Parameters:**
- `$number` (float|int): The number to format
- `$decimals` (int): Number of decimal places (default: 2)

**Returns:** (string) Formatted number

**Example:**
```php
format_indian(1000000, 2);  // Returns: "10,00,000.00"
format_indian(100000, 0);   // Returns: "1,00,000"
```

#### `format_indian_currency($number, $decimals = 2)`
Formats a number in Indian style with "Rs. " prefix.

**Parameters:**
- `$number` (float|int): The number to format
- `$decimals` (int): Number of decimal places (default: 2)

**Returns:** (string) Formatted currency string

**Example:**
```php
format_indian_currency(1000000, 2);  // Returns: "Rs. 10,00,000.00"
format_indian_currency(50000, 0);    // Returns: "Rs. 50,000"
```

#### `format_indian_percentage($number, $decimals = 1)`
Formats a number as percentage in Indian style.

**Parameters:**
- `$number` (float|int): The percentage value
- `$decimals` (int): Number of decimal places (default: 1)

**Returns:** (string) Formatted percentage string

**Example:**
```php
format_indian_percentage(85.5, 1);  // Returns: "85.5%"
```

### JavaScript Helper Functions

#### `formatIndianNumber(number, decimals = 2)`
Formats a number in Indian style without currency symbol.

**Parameters:**
- `number` (number): The number to format
- `decimals` (number): Number of decimal places (default: 2)

**Returns:** (string) Formatted number

**Example:**
```javascript
formatIndianNumber(1000000, 2);  // Returns: "10,00,000.00"
formatIndianNumber(100000, 0);   // Returns: "1,00,000"
```

#### `formatIndianCurrency(number, decimals = 2)`
Formats a number in Indian style with "Rs. " prefix.

**Parameters:**
- `number` (number): The number to format
- `decimals` (number): Number of decimal places (default: 2)

**Returns:** (string) Formatted currency string

**Example:**
```javascript
formatIndianCurrency(1000000, 2);  // Returns: "Rs. 10,00,000.00"
formatIndianCurrency(50000, 0);    // Returns: "Rs. 50,000"
```

#### `formatIndianLocale(number, options = {})`
Wrapper around `toLocaleString('en-IN', options)` for consistent formatting.

**Parameters:**
- `number` (number): The number to format
- `options` (object): Intl.NumberFormat options

**Returns:** (string) Formatted number string

**Example:**
```javascript
formatIndianLocale(1000000, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
// Returns: "10,00,000.00"
```

## DataTables Configuration

### Global Configuration
```javascript
// Include after DataTables library loads
$.fn.dataTable.defaults.formatNumber = function(toFormat) {
    return formatIndianNumber(toFormat, 0);
};
```

### Per-Table Configuration
```javascript
$('#myTable').DataTable({
    language: {
        thousands: ',',
        decimal: '.'
    },
    // Custom renderer for amount column
    columnDefs: [{
        targets: [2], // Amount column index
        render: function(data, type, row) {
            if (type === 'display' || type === 'filter') {
                return formatIndianCurrency(parseFloat(data) || 0, 2);
            }
            return data;
        }
    }]
});
```

## Edge Cases Handling

### Zero Values
```php
// ✅ Handles zero correctly
format_indian_currency(0, 2);  // Returns: "Rs. 0.00"
```

### Negative Numbers
```php
// ✅ Handles negative numbers
format_indian_currency(-1000000, 2);  // Returns: "Rs. -10,00,000.00"
```

### Very Large Numbers (Crores)
```php
// ✅ Handles crores
format_indian_currency(100000000, 2);  // Returns: "Rs. 10,00,00,000.00"
```

### Null or Empty Values
```php
// In Blade templates, use null coalescing
{{ format_indian_currency($amount ?? 0, 2) }}
```

### JavaScript Null Handling
```javascript
// ✅ Handle null/undefined
const formatted = formatIndianCurrency(amount || 0, 2);
```

## Testing Examples

### PHP Unit Test
```php
public function test_format_indian_currency()
{
    $this->assertEquals('Rs. 10,00,000.00', format_indian_currency(1000000, 2));
    $this->assertEquals('Rs. 1,00,000', format_indian_currency(100000, 0));
    $this->assertEquals('Rs. 0.00', format_indian_currency(0, 2));
    $this->assertEquals('Rs. -10,00,000.00', format_indian_currency(-1000000, 2));
}
```

### JavaScript Test
```javascript
// Using console or testing framework
console.assert(formatIndianCurrency(1000000, 2) === 'Rs. 10,00,000.00');
console.assert(formatIndianNumber(100000, 0) === '1,00,000');
```

## Common Mistakes to Avoid

### ❌ Wrong: Using number_format() with Indian locale
```php
// This won't work - number_format() doesn't support Indian formatting
number_format($amount, 2, '.', ',');  // Still gives 1,000,000.00
```

### ❌ Wrong: Not handling decimals correctly
```php
// Missing decimal parameter
format_indian($amount);  // Uses default 2 decimals
format_indian($amount, 0);  // No decimals - correct for counts
```

### ❌ Wrong: Inconsistent locale in JavaScript
```javascript
// Mixing locales
value1.toLocaleString('en-IN');  // Indian
value2.toLocaleString('en-US');  // American - WRONG!
```

### ✅ Correct: Consistent usage
```php
// Always use helper functions
format_indian_currency($amount, 2);
format_indian($count, 0);
format_indian($percentage, 1) . '%';
```

## Migration Checklist

When updating each file:
- [ ] Replace all `number_format()` calls
- [ ] Update JavaScript `toLocaleString('en-US')` to `'en-IN'`
- [ ] Test with various number sizes (thousands, lakhs, crores)
- [ ] Test with decimals
- [ ] Test with zero and negative numbers
- [ ] Verify PDF generation if applicable
- [ ] Verify exports if applicable
- [ ] Check responsive design (numbers shouldn't break layout)

---

**Quick Tips:**
1. Use `format_indian_currency()` for all currency amounts
2. Use `format_indian()` for percentages and counts
3. Always specify decimal places explicitly
4. Use null coalescing (`??`) for nullable values
5. Test with edge cases (zero, negative, very large numbers)
