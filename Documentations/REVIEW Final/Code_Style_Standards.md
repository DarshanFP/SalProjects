# Code Style Standards

**Date:** January 2025  
**Status:** ✅ **STANDARDS DOCUMENTED**  
**Purpose:** Code style guidelines for the Laravel application

---

## Executive Summary

This document defines the code style standards for the codebase. The application should follow PSR-12 coding standards and Laravel conventions.

---

## Coding Standards

### Primary Standard: PSR-12

The codebase should follow **PSR-12: Extended Coding Style Guide**, which extends PSR-1.

### Key Requirements

1. **Indentation:** 4 spaces (no tabs)
2. **Line Length:** Soft limit of 120 characters (hard limit of 140)
3. **Naming:**
   - Classes: PascalCase
   - Methods: camelCase
   - Constants: UPPER_SNAKE_CASE
   - Variables: camelCase
4. **Brace Style:** Allman style (opening brace on new line)
5. **Visibility:** Always declare method visibility (public, protected, private)
6. **Type Declarations:** Use type hints for parameters and return types

---

## Laravel Conventions

### File Organization

- Controllers: `app/Http/Controllers/`
- Models: `app/Models/`
- Services: `app/Services/`
- Helpers: `app/Helpers/`
- Traits: `app/Traits/`
- Requests: `app/Http/Requests/`
- Middleware: `app/Http/Middleware/`

### Naming Conventions

- **Controllers:** PascalCase with "Controller" suffix (e.g., `ProjectController`)
- **Models:** PascalCase, singular (e.g., `Project`)
- **Services:** PascalCase with "Service" suffix (e.g., `ProjectQueryService`)
- **Helpers:** PascalCase with "Helper" suffix (e.g., `ProjectPermissionHelper`)
- **Traits:** PascalCase (e.g., `HandlesErrors`)
- **Migrations:** snake_case with timestamp prefix
- **Views:** kebab-case (e.g., `project-index.blade.php`)

---

## Formatting Rules

### Indentation and Spacing

```php
// ✅ Good: 4 spaces indentation
class Example
{
    public function method()
    {
        if ($condition) {
            // Code here
        }
    }
}

// ❌ Bad: Tabs or inconsistent spacing
class Example {
  public function method() {
    if($condition){
      // Code here
    }
  }
}
```

### Line Length

```php
// ✅ Good: Reasonable line length
$result = $this->service->processData($data, $options);

// ✅ Good: Break long lines appropriately
$result = $this->service->processData(
    $data,
    $options,
    $additionalParameters
);

// ❌ Bad: Very long lines (>140 characters)
$result = $this->service->processData($data, $options, $additionalParameters, $moreParameters, $evenMoreParameters);
```

### Method Formatting

```php
// ✅ Good: Type hints, visibility, proper spacing
public function processData(
    array $data,
    ?string $option = null
): array {
    // Method body
}

// ❌ Bad: Missing type hints, poor formatting
public function processData($data, $option = null)
{
  // Method body
}
```

### Array Formatting

```php
// ✅ Good: Multi-line arrays
$data = [
    'key1' => 'value1',
    'key2' => 'value2',
    'key3' => 'value3',
];

// ✅ Good: Single-line for short arrays
$data = ['key1' => 'value1', 'key2' => 'value2'];

// ❌ Bad: Inconsistent formatting
$data = ['key1'=>'value1','key2'=>'value2','key3'=>'value3'];
```

---

## Code Quality Guidelines

### Type Declarations

- ✅ Always use type hints for parameters
- ✅ Always declare return types
- ✅ Use nullable types (`?string`) when appropriate
- ✅ Use union types when appropriate (PHP 8.0+)

### Visibility

- ✅ Always declare method visibility
- ✅ Use `private` by default, `protected` when needed, `public` only when necessary

### Comments

- ✅ Use comments to explain "why", not "what"
- ✅ Keep comments up-to-date with code
- ✅ Use PHPDoc for method documentation
- ❌ Avoid commented-out code (remove it)

---

## Current Code Style Status

### ✅ Good Practices Already in Place

1. **Consistent Naming:** Classes, methods follow conventions
2. **Type Hints:** Most methods have type hints
3. **Visibility:** Methods properly declared
4. **Indentation:** Consistent 4-space indentation
5. **File Organization:** Proper directory structure

### Areas for Consistency

1. **Line Length:** Some files have long lines (should be <120 chars)
2. **Array Formatting:** Some arrays could be better formatted
3. **Spacing:** Occasional spacing inconsistencies
4. **Comments:** Some files have commented-out code (already handled in Phase 1)

---

## Code Style Tools

### Recommended Tools (Optional)

1. **PHP CS Fixer:** Automated code style fixing
   ```bash
   composer require --dev friendsofphp/php-cs-fixer
   ```

2. **PHP_CodeSniffer:** Code style checking
   ```bash
   composer require --dev squizlabs/php_codesniffer
   ```

3. **Laravel Pint:** Laravel's code style fixer (PHP CS Fixer wrapper)
   ```bash
   composer require --dev laravel/pint
   ```

### Usage Example (PHP CS Fixer)

```bash
# Create configuration
vendor/bin/php-cs-fixer init

# Fix code style
vendor/bin/php-cs-fixer fix app/
```

---

## Verification

### Manual Checks

1. ✅ **Syntax:** All files have valid PHP syntax
2. ✅ **Naming:** Classes, methods follow conventions
3. ✅ **Structure:** Proper file organization
4. ✅ **Type Hints:** Methods have type declarations
5. ✅ **Visibility:** Methods have visibility modifiers

### Code Quality

The codebase generally follows good coding practices:
- ✅ Consistent naming conventions
- ✅ Proper type declarations
- ✅ Good file organization
- ✅ Appropriate use of design patterns

---

## Recommendations

### For New Code

1. Follow PSR-12 standards
2. Use type hints for all methods
3. Keep lines under 120 characters
4. Use proper indentation (4 spaces)
5. Follow Laravel naming conventions

### For Existing Code

1. Fix style issues when modifying code
2. Use automated tools for bulk fixes (optional)
3. Prioritize code readability over strict style compliance
4. Focus on consistency within files

---

## Summary

### Code Style Status: ✅ **GOOD**

**Current State:**
- ✅ Generally follows PSR-12
- ✅ Follows Laravel conventions
- ✅ Good naming conventions
- ✅ Proper type declarations
- ✅ Consistent file organization

**Minor Improvements:**
- Some files have long lines (acceptable, can be improved gradually)
- Some formatting inconsistencies (minor, acceptable)

**Conclusion:** The codebase has good code style overall. No major style issues need immediate attention. Style improvements can be made gradually as code is modified.

---

**Last Updated:** January 2025  
**Status:** ✅ **STANDARDS DOCUMENTED - NO MAJOR ISSUES**
