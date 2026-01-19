# Coding Standards

**Version:** 1.0  
**Last Updated:** January 2025  
**Project:** SalProjects (Laravel Application)

---

## Overview

This document defines the coding standards and conventions used in the SalProjects Laravel application. These standards ensure code consistency, maintainability, and adherence to Laravel and PSR-12 best practices.

---

## Naming Conventions

### Methods

**Standard:** `camelCase`

All controller methods and functions must use camelCase naming.

**Examples:**
```php
// ✅ Correct
public function coordinatorDashboard(Request $request)
public function projectList(Request $request)
public function addProjectComment(Request $request)
public function createExecutor()

// ❌ Incorrect
public function CoordinatorDashboard(Request $request)
public function ProjectList(Request $request)
public function AddProjectComment(Request $request)
public function CreateExecutor()
```

**PSR-12 Compliance:**
- All methods use camelCase
- First letter is lowercase
- Each subsequent word starts with uppercase
- No underscores

---

### Route Parameters

**Standard:** `snake_case`

All route parameters must use snake_case naming.

**Examples:**
```php
// ✅ Correct
Route::get('/projects/{project_id}', [ProjectController::class, 'show']);
Route::get('/reports/{report_id}', [ReportController::class, 'show']);
Route::post('/projects/{project_id}/approve', [ProjectController::class, 'approve']);

// ❌ Incorrect
Route::get('/projects/{projectId}', [ProjectController::class, 'show']);
Route::get('/reports/{reportId}', [ReportController::class, 'show']);
```

**Laravel Convention:**
- Route parameters use snake_case
- This matches Laravel's default behavior
- Consistent with database column naming

---

### Database Tables

**Standard:** `snake_case` (for new tables)

**Current State:**
Due to production data and legacy code, the database contains mixed naming conventions:
- **snake_case:** Most tables (standard Laravel convention)
- **PascalCase:** Some tables (e.g., `Project_EduRUT_Basic_Info`, `DP_Reports`)
- **camelCase:** Some tables (e.g., `oldDevelopmentProjects`)

**For New Tables:**
- Use `snake_case` naming
- Plural form (e.g., `user_projects`, `monthly_reports`)
- Descriptive names

**For Existing Tables:**
- Use `$table` property in models to map to existing table names
- Do not rename existing tables in production without careful planning

**Examples:**
```php
// ✅ New table (snake_case)
Schema::create('user_projects', function (Blueprint $table) {
    // ...
});

// ✅ Model mapping for existing PascalCase table
class ProjectEduRUTBasicInfo extends Model
{
    protected $table = 'Project_EduRUT_Basic_Info';
}
```

---

### Database Columns

**Standard:** `snake_case`

All database columns use snake_case naming.

**Examples:**
```php
// ✅ Correct
$table->string('project_id');
$table->string('user_name');
$table->timestamp('created_at');
$table->boolean('is_active');

// ❌ Incorrect
$table->string('projectId');
$table->string('userName');
$table->timestamp('createdAt');
$table->boolean('isActive');
```

---

### File Names

**Standard:** `PascalCase` for classes, `kebab-case` or `snake_case` for views

**Controllers:**
- Use `PascalCase` (e.g., `ProjectController.php`, `AdminController.php`)

**Models:**
- Use `PascalCase` (e.g., `User.php`, `Project.php`)

**Views:**
- Use `kebab-case` or `snake_case` (e.g., `project-list.blade.php`, `user_profile.blade.php`)

**Migrations:**
- Use `snake_case` with timestamp prefix (e.g., `2025_01_15_create_projects_table.php`)

**Examples:**
```php
// ✅ Controller files
app/Http/Controllers/ProjectController.php
app/Http/Controllers/AdminController.php

// ✅ View files
resources/views/projects/index.blade.php
resources/views/admin/dashboard.blade.php

// ✅ Migration files
database/migrations/2025_01_15_000000_create_projects_table.php
```

---

### Classes

**Standard:** `PascalCase`

All class names use PascalCase.

**Examples:**
```php
// ✅ Correct
class ProjectController extends Controller
class UserService
class ProjectQueryService

// ❌ Incorrect
class projectController extends Controller
class userService
class project_query_service
```

---

### Variables

**Standard:** `camelCase`

All variables use camelCase naming.

**Examples:**
```php
// ✅ Correct
$projectList = [];
$userName = 'John';
$isActive = true;
$createdAt = now();

// ❌ Incorrect
$project_list = [];
$user_name = 'John';
$is_active = true;
$created_at = now();
```

---

### Constants

**Standard:** `UPPER_SNAKE_CASE`

All constants use UPPER_SNAKE_CASE.

**Examples:**
```php
// ✅ Correct
const MAX_FILE_SIZE = 10485760;
const DEFAULT_STATUS = 'pending';
const PROJECT_STATUS_APPROVED = 'approved';

// ❌ Incorrect
const maxFileSize = 10485760;
const defaultStatus = 'pending';
const projectStatusApproved = 'approved';
```

---

## Code Organization

### Controllers

**Location:** `app/Http/Controllers/`

**Structure:**
- Group related controllers in subdirectories (e.g., `Projects/`, `Reports/`)
- Use resource controllers where appropriate
- Keep controllers focused on HTTP concerns
- Move business logic to Services

**Example:**
```php
namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        // Controller logic
    }
}
```

---

### Models

**Location:** `app/Models/`

**Structure:**
- Use Eloquent ORM
- Define relationships in models
- Use `$table` property for non-standard table names
- Use `$fillable` or `$guarded` for mass assignment

**Example:**
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'project_id',
        'project_name',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

---

### Services

**Location:** `app/Services/`

**Purpose:**
- Business logic
- Complex operations
- Reusable functionality

**Example:**
```php
namespace App\Services;

class ProjectQueryService
{
    public static function getProjectsForUsersQuery($userIds)
    {
        // Service logic
    }
}
```

---

### Routes

**Location:** `routes/web.php`, `routes/api.php`

**Conventions:**
- Use route names for all routes
- Group routes by middleware
- Use resource routes where appropriate
- Keep route files organized

**Example:**
```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'adminDashboard'])->name('admin.dashboard');
});

Route::resource('projects', ProjectController::class);
```

---

## Import Statements

**Standard:** Use `use` statements at the top of files

**Conventions:**
- Import classes at the top of the file
- Group imports by namespace
- Use full class names in route definitions (with imports)
- Do not use full namespace paths in routes

**Example:**
```php
// ✅ Correct - Import at top
use App\Http\Controllers\Projects\BudgetExportController;

// Then use in routes
Route::get('/budgets/report', [BudgetExportController::class, 'generateReport']);

// ❌ Incorrect - Full namespace in route
Route::get('/budgets/report', [\App\Http\Controllers\Projects\BudgetExportController::class, 'generateReport']);
```

---

## Code Quality

### PSR-12 Compliance

All code must comply with PSR-12 coding standards:

- Use 4 spaces for indentation (not tabs)
- Use Unix line endings (LF)
- Remove trailing whitespace
- Use camelCase for methods
- Use PascalCase for classes
- Use snake_case for constants
- Use proper visibility declarations (`public`, `protected`, `private`)

---

### Comments

**Standard:** Use PHPDoc for classes and methods

**Conventions:**
- Document public methods
- Document complex logic
- Remove debug comments from production code
- Use `Log::info()` for production logging (not debug comments)

**Example:**
```php
/**
 * Get projects for a list of users
 *
 * @param array $userIds
 * @return \Illuminate\Database\Eloquent\Builder
 */
public static function getProjectsForUsersQuery($userIds)
{
    // Method implementation
}
```

---

### Debug Code

**Standard:** Remove debug comments, use proper logging

**Do Not:**
```php
// Debug: Log the request parameters
\Log::info('Request', [...]);
```

**Do:**
```php
\Log::info('Request', [...]);
```

**Production Logging:**
- Use `Log::info()` for important events
- Use `Log::error()` for errors
- Use `Log::warning()` for warnings
- Remove "Debug:" comments

---

## File Organization

### Backup Files

**Standard:** Do not commit backup files

**Conventions:**
- Do not create backup files with `-copy`, `-backup`, `-old`, `-OLD` suffixes
- Use version control (Git) for history
- Add backup patterns to `.gitignore`

**Ignored Patterns:**
```
*-copy.*
*-backup.*
*.bak
*.old
*-OLD.*
*-old.*
```

---

### File Extensions

**Standard:** Use correct file extensions

**Conventions:**
- PHP files: `.php`
- Blade templates: `.blade.php`
- Documentation: `.md`
- Do not use `.DOC` for documentation
- Do not use `.text` or `.txt` for PHP files

---

## Testing

### Route Testing

**Standard:** Test all routes after changes

**Checklist:**
- Test all dashboard routes
- Test CRUD operations
- Test authorization
- Verify route parameters work correctly

---

### Code Validation

**Standard:** Validate code before committing

**Commands:**
```bash
php artisan route:list          # Verify routes are valid
php artisan config:clear        # Clear configuration cache
php artisan cache:clear         # Clear application cache
php -l file.php                 # Check PHP syntax
```

---

## Best Practices

### Controller Methods

1. **Keep controllers thin** - Move business logic to Services
2. **Use dependency injection** - Inject services and models
3. **Validate requests** - Use Form Requests for validation
4. **Use route model binding** - Where appropriate
5. **Return appropriate responses** - JSON for API, views for web

### Database

1. **Use migrations** - Never modify database directly
2. **Use Eloquent** - Avoid raw queries when possible
3. **Use relationships** - Define model relationships
4. **Use transactions** - For multi-step operations
5. **Use indexes** - For frequently queried columns

### Security

1. **Validate input** - Always validate user input
2. **Use CSRF protection** - For all forms
3. **Use authentication** - Protect routes with middleware
4. **Use authorization** - Check permissions
5. **Sanitize output** - Escape output in views

---

## Change History

### January 2025

- **Standardized method naming** - All methods now use camelCase (PSR-12 compliance)
- **Standardized route parameters** - All route parameters use snake_case
- **Standardized route imports** - All routes use proper imports
- **Removed backup files** - Cleaned up codebase
- **Removed debug comments** - Cleaned up production code
- **Created coding standards document** - This document

---

## References

- [Laravel Documentation](https://laravel.com/docs)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
- [PHP-FIG Standards](https://www.php-fig.org/psr/)

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** Active
