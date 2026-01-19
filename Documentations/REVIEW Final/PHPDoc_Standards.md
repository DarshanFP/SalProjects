# PHPDoc Documentation Standards

**Date:** January 2025  
**Status:** ✅ **STANDARDS DOCUMENTED**  
**Purpose:** Guidelines for PHPDoc comments in the codebase

---

## Executive Summary

This document defines the PHPDoc documentation standards for the codebase. Most classes already have good documentation, and these standards should be followed for any new code or when updating existing code.

---

## Class-Level Documentation

### Format

```php
/**
 * Brief description of the class
 *
 * Longer description if needed, explaining the purpose and usage
 * of the class. Can span multiple paragraphs.
 *
 * @package App\Namespace
 */
class ClassName
{
}
```

### Example

```php
/**
 * Service for handling notifications
 *
 * Provides methods for creating and managing notifications for users.
 * Handles user preferences, notification types, and email notifications.
 */
class NotificationService
{
}
```

---

## Method-Level Documentation

### Format

```php
/**
 * Brief description of what the method does
 *
 * Longer description if needed, explaining complex logic,
 * behavior, or important details.
 *
 * @param Type $paramName Description of parameter
 * @param Type $paramName2 Description of second parameter
 * @return ReturnType Description of return value
 * @throws ExceptionType When this exception is thrown
 */
public function methodName(Type $paramName, Type $paramName2): ReturnType
{
}
```

### Example

```php
/**
 * Get projects where user is owner or in-charge
 *
 * Returns a query builder that can be further chained with
 * additional conditions like status filters or relationships.
 *
 * @param User $user The user to get projects for
 * @param array $with Relationships to eager load
 * @return \Illuminate\Database\Eloquent\Collection Collection of projects
 */
public static function getProjectsForUser(User $user, array $with = []): \Illuminate\Database\Eloquent\Collection
{
}
```

---

## Documentation Requirements

### Always Document

1. **Public Methods** - All public methods should have PHPDoc
2. **Protected Methods** - Important protected methods should be documented
3. **Complex Private Methods** - Document if logic is complex or non-obvious
4. **Classes** - All classes should have class-level PHPDoc
5. **Parameters** - All parameters should be documented with `@param`
6. **Return Values** - All return values should be documented with `@return`

### Optional Documentation

1. **Simple Private Methods** - Self-explanatory private methods can skip PHPDoc
2. **Simple Getter/Setter Methods** - Can be brief or omitted if obvious
3. **Laravel Standard Methods** - Standard Laravel methods (like `index()`, `store()`) can have brief docs

---

## Documentation Tags

### Common Tags

- `@param Type $name Description` - Document method parameters
- `@return Type Description` - Document return values
- `@throws ExceptionType Description` - Document exceptions thrown
- `@package Namespace` - Package/namespace (usually auto-detected)
- `@since Version` - Version when method was added
- `@deprecated` - Mark deprecated methods
- `@see Reference` - Reference to related code

### Examples

```php
/**
 * Calculate budget for a project
 *
 * @param Project $project The project to calculate budget for
 * @param array $options Additional calculation options
 * @return array Array containing calculated budget values
 * @throws \RuntimeException If project type configuration is missing
 * @since 1.0.0
 */
```

---

## Type Hints

### Use Full Qualified Names When Needed

```php
/**
 * @param \Illuminate\Database\Eloquent\Collection $collection
 * @return \Illuminate\Database\Eloquent\Builder
 */
```

### Use Short Names When Imported

```php
use App\Models\Project;

/**
 * @param Project $project
 * @return Project
 */
```

---

## Current Documentation Status

### Well-Documented Classes

- ✅ `ProjectQueryService` - Excellent PHPDoc
- ✅ `BaseBudgetStrategy` - Complete documentation
- ✅ `BudgetValidationService` - Good documentation
- ✅ `ResponseParser` - Good documentation
- ✅ `LogHelper` - Good documentation

### Recently Improved

- ✅ `Controller` (base class) - Added class-level PHPDoc
- ✅ `ProjectPermissionHelper` - Enhanced method documentation
- ✅ `NotificationService` - Added class-level PHPDoc

### Documentation Quality

- Most services have good PHPDoc comments
- Most helpers have basic PHPDoc (can be enhanced)
- Controllers have varying levels of documentation
- Base classes and important services are well-documented

---

## Recommendations

### For New Code

1. Always add class-level PHPDoc
2. Document all public methods with parameters and return types
3. Use descriptive descriptions
4. Include examples for complex methods

### For Existing Code

1. Prioritize documenting:
   - Base classes
   - Service classes
   - Helper classes
   - Public APIs
2. Enhance documentation gradually when modifying code
3. Focus on methods that are frequently used

---

## Examples of Good Documentation

### Service Class

```php
/**
 * Service for handling project queries
 *
 * Provides centralized methods for querying projects based on
 * user permissions, status, and other criteria.
 */
class ProjectQueryService
{
    /**
     * Get a query builder for projects where user is owner or in-charge
     *
     * @param User $user
     * @return Builder
     */
    public static function getProjectsForUserQuery(User $user): Builder
    {
    }
}
```

### Helper Class

```php
/**
 * Helper class for project permission checks
 *
 * Provides static methods to check various permissions related to projects.
 * Handles ownership, in-charge relationships, and role-based access control.
 */
class ProjectPermissionHelper
{
    /**
     * Check if user can edit a project
     *
     * @param Project $project The project to check
     * @param User $user The user to check permissions for
     * @return bool True if user can edit the project, false otherwise
     */
    public static function canEdit(Project $project, User $user): bool
    {
    }
}
```

---

**Last Updated:** January 2025  
**Status:** ✅ **STANDARDS DOCUMENTED**
