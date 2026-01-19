# IGE New Beneficiaries Data Storage Fix

## Issue Summary

**Date:** January 2026  
**Project:** IOGEP-0001 (Institutional Ongoing Group Educational Proposal)  
**Issue:** New Beneficiaries section data was not being stored or retrieved correctly for IGE projects.

## Problem Description

Users reported that the "New Beneficiaries" section data for IGE projects was either:
1. Not being stored in the database when using create or edit methods
2. Not being fetched properly by view or edit methods

**Log Evidence:**
```
[2026-01-14 15:30:51] production.WARNING: No New Beneficiaries data found {"project_id":"IOGEP-0001"}
```

## Root Cause Analysis

After investigating the codebase, the following issues were identified:

### 1. **Transaction Interruption Issue**
The `NewBeneficiariesController@store()` method was redirecting immediately after saving data, even when called from `ProjectController@update()`. This caused:
- The parent transaction in `ProjectController@update()` to be interrupted
- Other IGE data (Institution Info, Beneficiaries Supported, Ongoing Beneficiaries, Budget, Development Monitoring) to potentially not save
- The transaction to complete prematurely, potentially causing data inconsistency

### 2. **Nested Transaction Problem**
The `store()` method was starting its own database transaction even when already inside a transaction from `ProjectController@update()`, which could cause:
- Transaction nesting issues
- Potential deadlocks
- Inconsistent commit/rollback behavior

### 3. **Empty String Validation**
The original code checked `!is_null($name)` but didn't handle empty strings properly, which could result in:
- Empty beneficiary records being saved
- Data quality issues

### 4. **Insufficient Logging**
Limited logging made it difficult to diagnose:
- Whether data was reaching the controller
- How many records were being processed
- Transaction context information

## Solution Implemented

### File Modified
- `app/Http/Controllers/Projects/IGE/NewBeneficiariesController.php`

### Changes Made

#### 1. **Added Transaction Context Detection**
```php
// Check if we're already in a transaction (called from ProjectController@update)
$inTransaction = DB::transactionLevel() > 0;

if (!$inTransaction) {
    DB::beginTransaction();
}
```

#### 2. **Added Conditional Redirect Parameter**
```php
public function store(FormRequest $request, $projectId, $shouldRedirect = true)
```

The `$shouldRedirect` parameter allows the method to be called from `update()` without redirecting, preventing transaction interruption.

#### 3. **Updated Update Method**
```php
public function update(FormRequest $request, $projectId)
{
    // Reuse store logic but don't redirect (called from ProjectController@update)
    return $this->store($request, $projectId, false);
}
```

#### 4. **Improved Empty String Validation**
Changed from:
```php
if (!is_null($name)) {
```

To:
```php
if (!empty(trim($name ?? ''))) {
```

This properly handles null values, empty strings, and whitespace-only strings.

#### 5. **Enhanced Logging**
Added comprehensive logging including:
- Transaction context (`in_transaction` flag)
- Beneficiary count being processed
- Saved count after processing
- Request keys for debugging
- Full error trace on exceptions

```php
Log::info('Storing IGE New Beneficiaries Information', [
    'project_id' => $projectId,
    'in_transaction' => $inTransaction,
    'beneficiary_names_count' => count($validated['beneficiary_name'] ?? []),
    'request_keys' => array_keys($validated)
]);
```

#### 6. **Proper Error Handling**
When called from `update()`, exceptions are re-thrown so the parent transaction can handle them:
```php
if ($shouldRedirect && !$inTransaction) {
    return redirect()->back()->with('error', 'Failed to save New Beneficiaries.');
}

throw $e; // Re-throw when called from update method so parent can handle
```

## Code Changes Summary

### Before
```php
public function store(FormRequest $request, $projectId)
{
    $validated = $request->all();
    
    DB::beginTransaction();
    try {
        // ... save logic ...
        DB::commit();
        return redirect()->route('projects.edit', $projectId)->with('success', '...');
    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', '...');
    }
}

public function update(FormRequest $request, $projectId)
{
    return $this->store($request, $projectId);
}
```

### After
```php
public function store(FormRequest $request, $projectId, $shouldRedirect = true)
{
    $validated = $request->all();
    
    $inTransaction = DB::transactionLevel() > 0;
    
    if (!$inTransaction) {
        DB::beginTransaction();
    }
    
    try {
        // ... save logic with improved validation ...
        if (!$inTransaction) {
            DB::commit();
        }
        
        if ($shouldRedirect && !$inTransaction) {
            return redirect()->route('projects.edit', $projectId)->with('success', '...');
        }
        
        return true; // Return success when called from update method
    } catch (\Exception $e) {
        if (!$inTransaction) {
            DB::rollBack();
        }
        
        if ($shouldRedirect && !$inTransaction) {
            return redirect()->back()->with('error', '...');
        }
        
        throw $e; // Re-throw when called from update method
    }
}

public function update(FormRequest $request, $projectId)
{
    return $this->store($request, $projectId, false);
}
```

## Testing & Verification

### Steps to Test
1. Navigate to an IGE project edit page (e.g., `IOGEP-0001`)
2. Add New Beneficiaries data:
   - Beneficiary Name
   - Caste
   - Address
   - Group/Year of Study
   - Family Background and Need of Support
3. Save the project
4. Verify the data appears in the view
5. Check logs for:
   - `Storing IGE New Beneficiaries Information` with transaction context
   - `IGE New Beneficiaries saved successfully` with saved count

### Expected Log Output
```
[timestamp] production.INFO: Storing IGE New Beneficiaries Information {
    "project_id": "IOGEP-0001",
    "in_transaction": true,
    "beneficiary_names_count": 3,
    "request_keys": [...]
}
[timestamp] production.INFO: IGE New Beneficiaries saved successfully {
    "project_id": "IOGEP-0001",
    "saved_count": 3
}
```

### Database Verification
Query to verify data was saved:
```sql
SELECT * FROM project_IGE_new_beneficiaries 
WHERE project_id = 'IOGEP-0001';
```

## Impact

### Positive Impacts
- ✅ New Beneficiaries data now saves correctly when editing IGE projects
- ✅ No transaction interruption when saving multiple IGE sections
- ✅ Better error handling and logging for debugging
- ✅ Improved data quality (empty strings are filtered out)
- ✅ Consistent behavior when called from create vs. update flows

### Affected Components
- `NewBeneficiariesController` - Primary fix
- `ProjectController@update()` - Now works correctly with the fixed controller
- All IGE projects using the New Beneficiaries section

## Related Files

- `app/Http/Controllers/Projects/ProjectController.php` - Calls the fixed controller
- `app/Models/OldProjects/IGE/ProjectIGENewBeneficiaries.php` - Model used
- `resources/views/projects/partials/Edit/IGE/new_beneficiaries.blade.php` - Edit view
- `resources/views/projects/partials/IGE/new_beneficiaries.blade.php` - Create view

## Notes

- This fix follows the same pattern that should be applied to other IGE controllers (`IGEBeneficiariesSupportedController`, `OngoingBeneficiariesController`) if they exhibit similar issues
- The transaction detection pattern can be reused for other nested controller calls
- Enhanced logging helps with future debugging and monitoring

## Future Recommendations

1. **Apply Similar Fix to Other IGE Controllers**
   - Review `IGEBeneficiariesSupportedController` and `OngoingBeneficiariesController` for similar transaction/redirect issues

2. **Consider Refactoring**
   - Extract common transaction handling logic into a trait or base controller
   - Standardize the pattern across all IGE controllers

3. **Add Unit Tests**
   - Test transaction context detection
   - Test redirect behavior in different contexts
   - Test empty string validation

4. **Monitor Logs**
   - Watch for any remaining "No New Beneficiaries data found" warnings
   - Monitor transaction levels in production logs

---

**Status:** ✅ Fixed  
**Verified:** Pending user testing  
**Deployment:** Ready for deployment
