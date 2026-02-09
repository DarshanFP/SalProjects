# Production Errors Analysis - February 7, 2026

This document identifies and analyzes all errors found in the production log (`laravel-production-070226.log`), their root causes, and recommended fixes.

---

## Summary of Errors

| # | Error Type | Occurrences | Severity | Affected Feature |
|---|------------|-------------|----------|------------------|
| 1 | Missing Spatie Role | 2 | High | User creation (Provincial/General) |
| 2 | Budget `this_phase` overflow | 9 | High | Project budget (Development Projects) |
| 3 | IES Educational Background array-to-string | 2 | High | IES project creation |
| 4 | IES Attachments file type on array | 3 | Medium | IES attachment uploads |

---

## Error 1: Missing `applicant` Role for Spatie Permission

### Log Entry
```
[2026-02-07 12:16:07] production.ERROR: Error storing user {"error":"There is no role named `applicant` for guard `web`."}
[2026-02-07 13:31:59] production.ERROR: Error storing user {"error":"There is no role named `applicant` for guard `web`."}
```

### Context
- **Trigger**: Provincial or General user creates a new executor/applicant with role `applicant`
- **Flow**: `ProvincialController@store` or `GeneralController@store` → `assignRole($validatedData['role'])` where role is `applicant`
- **Result**: User record is created in `users` table, but `assignRole()` fails because the Spatie `roles` table does not have an `applicant` role

### Root Cause
The `applicant` role exists in:
- `users.role` column (enum includes `applicant` via migration `2025_06_24_123934_add_applicant_role_to_users_table.php`)
- Routes, middleware, and application logic (e.g. `role:executor,applicant`)

But it is **not** seeded in Spatie's `roles` table. The `RolesAndPermissionsSeeder` only creates:
- admin, coordinator, provincial, executor, general

**File**: `database/seeders/RolesAndPermissionsSeeder.php` (lines 19-23)

### Affected Code
- `app/Http/Controllers/ProvincialController.php` (line 730)
- `app/Http/Controllers/GeneralController.php` (line 855)

### Recommended Fix
1. Add `applicant` role to `RolesAndPermissionsSeeder.php`:
   ```php
   Role::create(['name' => 'applicant']);
   ```
2. Run seeder or execute in production:
   ```php
   \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'applicant', 'guard_name' => 'web']);
   ```

---

## Error 2: Budget `this_phase` Numeric Overflow

### Log Entry
```
SQLSTATE[22003]: Numeric value out of range: 1264 Out of range value for column 'this_phase' at row 1
(SQL: insert into `project_budgets` (..., `this_phase`, ...) values (..., 63504338688000.00, ...))
```

### Example Values
- `rate_quantity`: 252000
- `rate_multiplier`: 21000112
- `rate_duration`: 12.00
- `this_phase`: 63504338688000.00 (= 252000 × 21000112 × 12)

### Root Cause
1. **Database constraint**: `project_budgets.this_phase` is `decimal(10,2)` — max value ~99,999,999.99
2. **Calculation**: `this_phase` is computed as `rate_quantity × rate_multiplier × rate_duration` (see `resources/views/projects/partials/scripts.blade.php` and `scripts-edit.blade.php`)
3. **Invalid input**: `rate_multiplier` of 21,000,112 is almost certainly user error (e.g. total amount entered instead of rate per unit)
4. **Validation**: `NumericBoundsRule` (max 99,999,999.99) exists in `StoreBudgetRequest` and `UpdateBudgetRequest`, but the invalid value still reached the database — possible causes: alternative request path, older deploy, or validation bypass

### Affected Code
- `database/migrations/2024_07_20_085654_create_project_budgets_table.php` — column `decimal(10,2)`
- `app/Http/Controllers/Projects/BudgetController.php` — insert uses `$budget['this_phase']` directly
- `app/Rules/NumericBoundsRule.php` — max 99,999,999.99
- `resources/views/projects/partials/scripts.blade.php` — client-side calculation (no cap)

### Recommended Fix
1. Enforce server-side validation so values exceeding column max are rejected (ensure `NumericBoundsRule` runs on all budget update paths)
2. Add client-side max checks in `calculateBudgetRowTotals()` to avoid submitting oversized values
3. Add tooltips or labels for `rate_multiplier` to clarify it is "rate per unit" and prevent total-amount confusion
4. Consider increasing column precision if business rules require larger amounts (and adjust indexes/performance impact)

---

## Error 3: IES Educational Background — Array to String Conversion

### Log Entry
```
Error saving IES educational background {"error":"Array to string conversion (Connection: mysql, SQL: insert into `project_IES_educational_background` 
(..., `family_contribution`, `reason_no_support`, ...) values (..., ?, ...))"}
```

### Context
- **Trigger**: Creating/updating an IES (Individual - Ongoing Educational Support) project
- **Projects**: IOES-0010, IOES-0011

### Root Cause
`IESEducationBackgroundController@store` uses `$request->all()` and `$educationBackground->fill($validated)`:

```php
$validated = $request->all();
$educationBackground->fill($validated);
```

When the form is submitted as part of a multi-step project creation, the request includes:
- Other sections (e.g. IGE budget with `family_contribution[]`)
- File uploads (`aadhar_card[]`, etc.)

If `family_contribution` is submitted as an array (e.g. from another partial like IGE budget with `family_contribution[]`), the model receives an array for a string column, causing "Array to string conversion" when building the SQL.

### Affected Code
- `app/Http/Controllers/Projects/IES/IESEducationBackgroundController.php` (lines 20-31)
- `app/Models/OldProjects/IES/ProjectIESEducationBackground.php` — fillable includes `family_contribution`, `reason_no_support`

### Recommended Fix
1. Only pass allowed fields and ensure scalar values:
   ```php
   $fillable = (new ProjectIESEducationBackground())->getFillable();
   $data = $request->only(array_diff($fillable, ['project_id', 'IES_education_id']));
   foreach ($data as $key => $value) {
       if (is_array($value)) {
           $data[$key] = is_array($value) ? (reset($value) ?? null) : $value;
       }
   }
   $educationBackground->fill($data);
   ```
2. Or use explicit input:
   ```php
   $educationBackground->fill([
       'previous_class' => $request->input('previous_class'),
       'amount_sanctioned' => $request->input('amount_sanctioned'),
       // ... etc, ensuring scalar values
   ]);
   ```

---

## Error 4: IES Attachments — `getClientOriginalExtension()` on Array

### Log Entry
```
Call to a member function getClientOriginalExtension() on array
at app/Models/OldProjects/IES/ProjectIESAttachments.php:184
```

### Root Cause
The IES attachments form uses multi-file inputs:

```html
<input type="file" name="aadhar_card[]" ...>
```

So `$request->file('aadhar_card')` returns an **array** of `UploadedFile` objects. `ProjectIESAttachments::handleAttachments()` treats it as a single file and calls `$file->getClientOriginalExtension()` at line 184, causing the error when `$file` is an array.

By contrast, `ProjectIIESAttachments::handleAttachments()` correctly supports both single and multiple files:

```php
$files = is_array($request->file($field))
    ? $request->file($field)
    : [$request->file($field)];
foreach ($files as $file) { ... }
```

### Affected Code
- `app/Models/OldProjects/IES/ProjectIESAttachments.php` (lines 119-124, 183-184)
- `resources/views/projects/partials/Edit/IES/attachments.blade.php` (line 84) — `name="{{ $field }}[]"`

### Recommended Fix
Align IES with IIES by normalizing to an array of files:

```php
// In handleAttachments(), replace:
$file = $request->file($field);
if (!self::isValidFileType($file)) { ... }

// With:
$files = is_array($request->file($field))
    ? $request->file($field)
    : ($request->file($field) ? [$request->file($field)] : []);

foreach ($files as $file) {
    if (!$file || !$file->isValid()) continue;
    if (!self::isValidFileType($file)) { ... }
    // ... rest of handling, following ProjectIIESAttachments pattern
}
```

---

## Implementation Priority

| Priority | Error | Effort | Impact |
|----------|-------|--------|--------|
| 1 | Missing `applicant` role | Low | High — blocks user creation |
| 2 | IES Attachments array handling | Medium | Medium — blocks attachment uploads |
| 3 | IES Educational Background array handling | Low | High — blocks IES project creation |
| 4 | Budget overflow validation | Low–Medium | High — prevents invalid budget data |

---

## Files to Modify

| File | Changes |
|------|---------|
| `database/seeders/RolesAndPermissionsSeeder.php` | Add `applicant` role |
| `app/Models/OldProjects/IES/ProjectIESAttachments.php` | Support array of files like IIES |
| `app/Http/Controllers/Projects/IES/IESEducationBackgroundController.php` | Filter and sanitize fill data (scalar only) |
| `app/Http/Controllers/Projects/BudgetController.php` | Verify validation path and overflow handling |
| `resources/views/projects/partials/scripts*.blade.php` | Optional client-side max checks for `this_phase` |

---

*Generated from production log analysis — February 7, 2026*
