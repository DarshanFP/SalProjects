# Testing Guide

**Date:** January 2025  
**Status:** ✅ **GUIDE COMPLETE**  
**Purpose:** Comprehensive testing guide for the Laravel application

---

## Executive Summary

This guide provides comprehensive information about testing procedures, test setup, and best practices for the application.

---

## Testing Framework

### PHPUnit Configuration

**File:** `phpunit.xml`

**Test Suites:**
- **Unit Tests:** `tests/Unit/` - Fast, isolated tests for classes
- **Feature Tests:** `tests/Feature/` - Integration tests for workflows

**Environment:**
- `APP_ENV=testing`
- `CACHE_DRIVER=array`
- `SESSION_DRIVER=array`
- `QUEUE_CONNECTION=sync`
- `MAIL_MAILER=array`

---

## Running Tests

### Run All Tests
```bash
php artisan test
# or
vendor/bin/phpunit
```

### Run Specific Test Suite
```bash
# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature
```

### Run Specific Test File
```bash
php artisan test tests/Unit/Services/Budget/BudgetCalculationServiceTest.php
```

### Run Specific Test Method
```bash
php artisan test --filter test_calculates_contribution_per_row_correctly
```

### With Coverage
```bash
php artisan test --coverage
# or
vendor/bin/phpunit --coverage-html coverage/
```

---

## Test Structure

### Unit Tests

**Location:** `tests/Unit/`

**Purpose:** Test individual classes, methods, and functions in isolation.

**Example Structure:**
```
tests/Unit/
├── Services/
│   └── Budget/
│       └── BudgetCalculationServiceTest.php
├── Helpers/
│   └── NumberFormatHelperTest.php
└── Models/
    └── ProjectTest.php
```

**Best Practices:**
- Test one class at a time
- Mock dependencies
- Fast execution
- No database access (use mocks)
- Test edge cases

### Feature Tests

**Location:** `tests/Feature/`

**Purpose:** Test complete workflows and user interactions.

**Example Structure:**
```
tests/Feature/
├── Auth/
│   ├── AuthenticationTest.php
│   └── PasswordResetTest.php
├── Projects/
│   └── ProjectCreationTest.php
└── Reports/
    └── ReportSubmissionTest.php
```

**Best Practices:**
- Test complete workflows
- Use database (RefreshDatabase trait)
- Test user interactions
- Test API endpoints
- Test authentication/authorization

---

## Test Data Setup

### Using Factories

**User Factory:**
```php
$user = User::factory()->create([
    'role' => 'executor',
    'email' => 'test@example.com',
]);
```

**Project Factory (if exists):**
```php
$project = Project::factory()->create([
    'project_type' => 'RST',
    'status' => 'draft',
]);
```

### Using Seeders

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(); // Run all seeders
        // or
        $this->seed(ProjectSeeder::class); // Run specific seeder
    }
}
```

### Manual Data Creation

```php
$user = User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => Hash::make('password'),
    'role' => 'executor',
]);
```

---

## Testing Best Practices

### 1. Test Naming

**Good:**
```php
public function test_calculates_contribution_per_row_correctly()
public function test_handles_zero_rows_in_contribution_per_row()
public function test_users_can_authenticate_using_the_login_screen()
```

**Bad:**
```php
public function test1()
public function testCalculation()
public function testAuth()
```

### 2. Test Structure (AAA Pattern)

```php
public function test_calculates_contribution_per_row_correctly()
{
    // Arrange
    $contribution = 1000.0;
    $totalRows = 5;
    $expected = 200.0;

    // Act
    $result = BudgetCalculationService::calculateContributionPerRow($contribution, $totalRows);

    // Assert
    $this->assertEquals($expected, $result);
}
```

### 3. Test Isolation

- Each test should be independent
- Use `RefreshDatabase` for feature tests
- Clean up after tests
- Don't rely on test execution order

### 4. Test Coverage

**Target:** 70%+ coverage for critical functionality

**Critical Areas:**
- Project creation/update
- Status transitions
- Permission checks
- Budget calculations
- Authentication/authorization
- File uploads

### 5. Mocking

**Mock Facades:**
```php
use Illuminate\Support\Facades\Log;

Log::shouldReceive('info')
    ->once()
    ->with('Message', ['key' => 'value'])
    ->andReturn(true);
```

**Mock Dependencies:**
```php
use Mockery;

$mockService = Mockery::mock(SomeService::class);
$mockService->shouldReceive('method')
    ->once()
    ->andReturn('result');
```

---

## Test Categories

### Unit Tests

#### Services
- `BudgetCalculationService` ✅ (Already tested)
- `ProjectStatusService` ⏳ (Needs tests)
- `NotificationService` ⏳ (Needs tests)
- `BudgetValidationService` ⏳ (Needs tests)

#### Helpers
- `NumberFormatHelper` ✅ (Already tested)
- `LogHelper` ⏳ (Needs tests)
- `ProjectPermissionHelper` ⏳ (Needs tests)
- `ActivityHistoryHelper` ⏳ (Needs tests)

#### Models
- Model relationships
- Model methods
- Model scopes

### Feature Tests

#### Authentication
- Login ✅ (Already tested)
- Logout ✅ (Already tested)
- Password reset ✅ (Already tested)
- Registration ✅ (Already tested)

#### Projects
- Project creation ⏳ (Needs tests)
- Project update ⏳ (Needs tests)
- Project approval workflow ⏳ (Needs tests)
- Project status transitions ⏳ (Needs tests)

#### Reports
- Report creation ⏳ (Needs tests)
- Report submission ⏳ (Needs tests)
- Report approval ⏳ (Needs tests)

#### File Uploads
- File upload validation ⏳ (Needs tests)
- File type validation ⏳ (Needs tests)
- File size validation ⏳ (Needs tests)

---

## Manual Testing Checklist

### Authentication
- [ ] User can login with valid credentials
- [ ] User cannot login with invalid credentials
- [ ] User can logout
- [ ] Password reset works
- [ ] Session expires after timeout

### Projects
- [ ] User can create project
- [ ] User can edit own project
- [ ] User cannot edit others' projects
- [ ] Project status transitions work
- [ ] Project approval workflow works
- [ ] Project permissions are enforced

### Reports
- [ ] User can create report
- [ ] User can submit report
- [ ] Report approval works
- [ ] Report export works (PDF/Word)
- [ ] Report comparison works

### File Uploads
- [ ] Valid files upload successfully
- [ ] Invalid file types are rejected
- [ ] Files over size limit are rejected
- [ ] Filename sanitization works
- [ ] Path traversal is prevented

### Authorization
- [ ] Admin has full access
- [ ] Coordinator can approve projects
- [ ] Provincial can forward projects
- [ ] Executor can create projects
- [ ] Role-based access is enforced

---

## Test Environment Setup

### 1. Install Dependencies
```bash
composer install
```

### 2. Configure Environment
```bash
cp .env.example .env.testing
# Edit .env.testing with test database settings
```

### 3. Create Test Database
```bash
php artisan migrate --env=testing
```

### 4. Run Tests
```bash
php artisan test
```

---

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          
      - name: Install Dependencies
        run: composer install
        
      - name: Run Tests
        run: php artisan test
```

---

## Test Data Management

### Using RefreshDatabase

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;
    
    // Database is refreshed before each test
}
```

### Using Database Transactions

```php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ProjectTest extends TestCase
{
    use DatabaseTransactions;
    
    // Changes are rolled back after each test
}
```

---

## Debugging Tests

### Verbose Output
```bash
php artisan test --verbose
```

### Stop on First Failure
```bash
php artisan test --stop-on-failure
```

### Filter Tests
```bash
php artisan test --filter ProjectTest
```

### Dump Output
```php
$this->dump(); // Dump response
dd($variable); // Dump and die
```

---

## Test Coverage Goals

### Current Coverage
- Budget Calculation Service: ✅ Good coverage
- Authentication: ✅ Good coverage
- Number Format Helper: ✅ Good coverage

### Target Coverage
- **Overall:** 70%+
- **Critical Services:** 80%+
- **Helpers:** 80%+
- **Controllers:** 60%+

---

## Common Test Patterns

### Testing Controllers
```php
public function test_user_can_create_project()
{
    $user = User::factory()->create(['role' => 'executor']);
    
    $response = $this->actingAs($user)
        ->post('/projects', [
            'project_type' => 'RST',
            'project_title' => 'Test Project',
        ]);
    
    $response->assertStatus(302);
    $this->assertDatabaseHas('projects', [
        'project_title' => 'Test Project',
    ]);
}
```

### Testing Services
```php
public function test_calculates_contribution_correctly()
{
    $result = BudgetCalculationService::calculateContributionPerRow(1000, 5);
    
    $this->assertEquals(200, $result);
}
```

### Testing Helpers
```php
public function test_formats_number_correctly()
{
    $result = NumberFormatHelper::format(1234.56);
    
    $this->assertEquals('1,234.56', $result);
}
```

---

## Test Maintenance

### Regular Tasks
- [ ] Run tests before committing
- [ ] Update tests when code changes
- [ ] Remove obsolete tests
- [ ] Add tests for new features
- [ ] Review test coverage regularly

### When to Write Tests
- ✅ Before fixing bugs (write test first)
- ✅ When adding new features
- ✅ When refactoring code
- ✅ For critical business logic
- ✅ For security-sensitive code

---

## Resources

### Laravel Testing Documentation
- [Laravel Testing](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Mockery Documentation](https://docs.mockery.io/)

### Internal Documentation
- `Security_Guide.md` - Security best practices
- `PHPDoc_Standards.md` - Code documentation
- `Code_Style_Standards.md` - Code style guidelines

---

**Last Updated:** January 2025  
**Status:** ✅ **TESTING GUIDE COMPLETE**
