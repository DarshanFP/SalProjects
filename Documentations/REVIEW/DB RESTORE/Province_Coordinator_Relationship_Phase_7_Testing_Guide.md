# Phase 7: Testing & Verification Guide - Province-Coordinator Relationship Fix

**Date:** 2026-01-11  
**Status:** Testing Guide Created 📋  
**Purpose:** Comprehensive testing guide for Province-Coordinator relationship fix

---

## 📋 Pre-Testing Checklist

Before starting testing, ensure:

- [ ] Database backup created
- [ ] Migrations ready but NOT YET executed
- [ ] Development/staging environment set up
- [ ] Test users created (General, Coordinator, Provincial users)
- [ ] Test data prepared

---

## 🧪 Testing Phase 7.1: Functional Testing

### 7.1.1 Test Database Migrations

#### Step 1: Run Data Migration (Phase 3)
```bash
# Check migration status first
php artisan migrate:status

# Run only the data migration (if needed, you can run all pending)
php artisan migrate
```

**Verification:**
- [ ] Migration runs without errors
- [ ] Check Laravel logs: `tail -100 storage/logs/laravel.log`
- [ ] Verify provincial users were created (if provinces had coordinators)

**Verification Query:**
```sql
-- Check if provincial users were created
SELECT u.id, u.name, u.email, u.role, u.province_id, u.parent_id, p.name as province_name
FROM users u
LEFT JOIN provinces p ON u.province_id = p.id
WHERE u.role = 'provincial' AND u.email LIKE '%@system.local'
ORDER BY u.created_at DESC;
```

#### Step 2: Verify Schema Changes
```bash
# Check migration status
php artisan migrate:status | grep remove_provincial_coordinator_id
```

**Verification Query:**
```sql
-- Verify column is removed (should show error or no column)
DESCRIBE provinces;

-- Or check information_schema
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'provinces' 
  AND COLUMN_NAME = 'provincial_coordinator_id';
-- Should return 0 rows
```

- [ ] Column `provincial_coordinator_id` does NOT exist in provinces table
- [ ] Foreign key constraint removed
- [ ] Index removed

---

### 7.1.2 Test Province Listing (General User)

**Test Case:** View provinces list as General user

**Steps:**
1. Login as General user
2. Navigate to Province Management page
3. View provinces list

**Expected Results:**
- [ ] Page loads without errors
- [ ] Provinces are displayed
- [ ] "Provincial Users" column shows provincial users (not coordinators)
- [ ] Multiple provincial users can be displayed for one province
- [ ] Search functionality works (search by province name or provincial user name)
- [ ] Summary cards show correct counts
- [ ] "Edit" button is visible for each province
- [ ] NO "Assign Coordinator", "Change Coordinator", or "Remove Coordinator" buttons

**Verification:**
- [ ] Check browser console for JavaScript errors
- [ ] Check Laravel logs for errors
- [ ] Verify data matches database state

---

### 7.1.3 Test Province Creation (General User)

**Test Case:** Create a new province

**Steps:**
1. Login as General user
2. Navigate to Province Management
3. Click "Create Province"
4. Enter province name and centers (optional)
5. Submit form

**Expected Results:**
- [ ] Province is created successfully
- [ ] Success message displayed
- [ ] Redirected to provinces list
- [ ] New province appears in list
- [ ] "Provincial Users" column shows "No provincial users assigned"
- [ ] Centers are created (if provided)
- [ ] NO mention of "assign coordinator" in success message

**Verification Query:**
```sql
-- Check newly created province
SELECT * FROM provinces WHERE name = '<test_province_name>';

-- Check centers
SELECT * FROM centers WHERE province_id = <new_province_id>;
```

---

### 7.1.4 Test Province Editing (General User)

**Test Case:** Edit province details

**Steps:**
1. Login as General user
2. Navigate to Province Management
3. Click "Edit" for a province
4. View edit form

**Expected Results:**
- [ ] Edit form loads without errors
- [ ] Current information shows province name
- [ ] "Provincial Users" section shows provincial users (not coordinator)
- [ ] Can edit province name
- [ ] Can edit centers (add/remove/edit)
- [ ] NO "Assign Provincial Coordinator" button
- [ ] NO "Change Provincial Coordinator" button
- [ ] Form submission works correctly

**Test Scenarios:**
- [ ] Edit province with no provincial users
- [ ] Edit province with one provincial user
- [ ] Edit province with multiple provincial users
- [ ] Change province name (verify users are updated)
- [ ] Add centers
- [ ] Remove centers
- [ ] Edit center names

---

### 7.1.5 Test Provincial User Management

**Test Case:** Verify provincial users are created/managed correctly

**Background:** Provincial users should be created by Coordinators or General users (not via province assignment)

**Expected Behavior:**
- [ ] Provincial users can be created via Coordinator/General user management
- [ ] Provincial users have `role='provincial'`
- [ ] Provincial users have `province_id` set
- [ ] Provincial users have `parent_id` set (to coordinator or general)
- [ ] Provincial users appear in province listing

**Verification Queries:**
```sql
-- Check provincial users structure
SELECT 
    u.id, 
    u.name, 
    u.email, 
    u.role, 
    u.province_id, 
    u.parent_id,
    p.name as province_name,
    parent.name as parent_name,
    parent.role as parent_role
FROM users u
LEFT JOIN provinces p ON u.province_id = p.id
LEFT JOIN users parent ON u.parent_id = parent.id
WHERE u.role = 'provincial'
ORDER BY u.province_id, u.id;
```

- [ ] All provincial users have valid `province_id`
- [ ] All provincial users have valid `parent_id`
- [ ] Parent users are either coordinators or general users

---

### 7.1.6 Test Coordinator Access

**Test Case:** Verify coordinators see all provinces

**Expected Behavior:**
- [ ] Coordinators have access to ALL provinces (no filtering)
- [ ] Coordinator dashboard shows all provinces
- [ ] Coordinator can manage provinces through their provincial children

**Steps:**
1. Login as Coordinator user
2. Navigate to coordinator dashboard
3. Check province access

**Expected Results:**
- [ ] Can see all provinces (not just assigned ones)
- [ ] Can see provinces through their child provincial users

---

### 7.1.7 Test Provincial User Access

**Test Case:** Verify provincial users see only their province

**Expected Behavior:**
- [ ] Provincial users see only their assigned province
- [ ] Provincial users cannot see other provinces

**Steps:**
1. Login as Provincial user
2. Navigate to provincial dashboard
3. Check province filtering

**Expected Results:**
- [ ] Can only see data for their assigned province
- [ ] Cannot access other provinces

---

### 7.1.8 Test Error Handling

**Test Cases:**
- [ ] Try to access removed routes (should get 404)
- [ ] Test with provinces that have no provincial users
- [ ] Test with provinces that have multiple provincial users
- [ ] Test search functionality with no results
- [ ] Test form validation (empty province name, duplicate names, etc.)

**Expected Results:**
- [ ] Appropriate error messages displayed
- [ ] No PHP errors in logs
- [ ] No JavaScript errors in console
- [ ] Graceful error handling

---

## 🧪 Testing Phase 7.2: Data Integrity Testing

### 7.2.1 Verify Foreign Key Constraints

**Test:** Verify relationships work correctly

**Queries:**
```sql
-- Check users.province_id references valid provinces
SELECT u.id, u.name, u.province_id, p.name as province_name
FROM users u
LEFT JOIN provinces p ON u.province_id = p.id
WHERE u.province_id IS NOT NULL AND p.id IS NULL;
-- Should return 0 rows (no orphaned province_id)

-- Check centers.province_id references valid provinces
SELECT c.id, c.name, c.province_id, p.name as province_name
FROM centers c
LEFT JOIN provinces p ON c.province_id = p.id
WHERE p.id IS NULL;
-- Should return 0 rows (no orphaned province_id)
```

- [ ] No orphaned `province_id` in users table
- [ ] No orphaned `province_id` in centers table
- [ ] All foreign keys valid

---

### 7.2.2 Verify Data Migration Results

**Test:** Verify data migration created provincial users correctly

**Query:**
```sql
-- Count provinces that had coordinators
-- (This requires checking before migration - save this data first)

-- Count provincial users created by migration
SELECT COUNT(*) as migrated_users
FROM users
WHERE role = 'provincial' 
  AND email LIKE '%@system.local';

-- Check provincial users per province
SELECT 
    p.id,
    p.name,
    COUNT(u.id) as provincial_user_count
FROM provinces p
LEFT JOIN users u ON u.province_id = p.id AND u.role = 'provincial'
GROUP BY p.id, p.name
ORDER BY provincial_user_count DESC;
```

- [ ] Provincial users created for provinces that had coordinators
- [ ] Provincial users have correct parent_id (coordinator)
- [ ] Provincial users have correct province_id
- [ ] No duplicate provincial users for same province+coordinator

---

### 7.2.3 Verify Hierarchy Integrity

**Test:** Verify user hierarchy is correct

**Query:**
```sql
-- Check provincial users hierarchy
SELECT 
    u.id,
    u.name as provincial_name,
    u.province_id,
    p.name as province_name,
    u.parent_id,
    parent.name as parent_name,
    parent.role as parent_role
FROM users u
LEFT JOIN provinces p ON u.province_id = p.id
LEFT JOIN users parent ON u.parent_id = parent.id
WHERE u.role = 'provincial'
ORDER BY u.province_id, u.parent_id;
```

**Expected:**
- [ ] All provincial users have valid parent_id
- [ ] Parent users are either 'coordinator' or 'general' role
- [ ] No circular references
- [ ] Hierarchy makes sense

- [ ] All relationships valid
- [ ] No orphaned records
- [ ] No circular dependencies

---

### 7.2.4 Test Cascade Behavior

**Test:** Verify ON DELETE behavior

**Note:** Be careful with these tests - they modify data!

**Test Cases:**
1. **Delete Province with Centers:**
   ```sql
   -- Check cascade delete for centers
   SELECT COUNT(*) FROM centers WHERE province_id = <test_province_id>;
   -- Delete province (via application, not SQL)
   -- Verify centers are cascade deleted
   ```
   - [ ] Centers are cascade deleted when province deleted

2. **Delete User with province_id:**
   ```sql
   -- Check ON DELETE SET NULL for users.province_id
   -- Note: users.province_id has ON DELETE SET NULL
   ```
   - [ ] Users.province_id set to NULL when province deleted (if applicable)

3. **Delete Provincial User's Parent:**
   - [ ] Provincial users handled correctly when parent deleted

---

## 🧪 Testing Phase 7.3: Regression Testing

### 7.3.1 Test Existing Functionality

**Test all province-related functionality:**

- [ ] Province listing works
- [ ] Province creation works
- [ ] Province editing works
- [ ] Province deletion works (with validation)
- [ ] Center management via province forms works
- [ ] Center transfer works
- [ ] User creation with province assignment works
- [ ] User editing with province assignment works

---

### 7.3.2 Test Coordinator Dashboard

**Test:** Verify coordinator dashboard still works

**Steps:**
1. Login as Coordinator
2. Navigate to dashboard
3. Check all features

**Expected Results:**
- [ ] Dashboard loads without errors
- [ ] Province filtering works (shows all provinces)
- [ ] Can see data for all provinces
- [ ] Reports work correctly
- [ ] Budget views work correctly

---

### 7.3.3 Test Provincial User Dashboard

**Test:** Verify provincial user dashboard works

**Steps:**
1. Login as Provincial user
2. Navigate to dashboard
3. Check all features

**Expected Results:**
- [ ] Dashboard loads without errors
- [ ] Province filtering works (shows only assigned province)
- [ ] Can see data for assigned province only
- [ ] Reports work correctly
- [ ] Budget views work correctly

---

### 7.3.4 Test User Management Flows

**Test:** Verify user creation/editing flows

**Test Cases:**
- [ ] Create coordinator user
- [ ] Create provincial user (child of coordinator)
- [ ] Create provincial user (child of general)
- [ ] Create executor user (child of provincial)
- [ ] Edit user province assignment
- [ ] Edit user center assignment

**Expected Results:**
- [ ] All user management flows work
- [ ] Province dropdowns work (use database)
- [ ] Center dropdowns work (use database)
- [ ] Filtering works correctly

---

### 7.3.5 Test Reports and Filtering

**Test:** Verify reports with province/center filters

**Test Cases:**
- [ ] Reports filtered by province
- [ ] Reports filtered by center
- [ ] Reports with combined filters
- [ ] Budget reports with province filters
- [ ] Budget reports with center filters

**Expected Results:**
- [ ] All filters work correctly
- [ ] Data accuracy maintained
- [ ] No broken queries
- [ ] Performance acceptable

---

## 🧪 Testing Phase 7.4: Performance Testing

### 7.4.1 Query Performance

**Test:** Verify query performance is acceptable

**Tools:**
- Laravel Debugbar
- Query logging
- EXPLAIN queries

**Test Cases:**
- [ ] Province listing page (check query count)
- [ ] Province edit page (check query count)
- [ ] User listing with province filters
- [ ] Reports with province filters

**Expected Results:**
- [ ] No N+1 queries
- [ ] Eager loading used where appropriate
- [ ] Query count reasonable (< 10-20 queries per page)
- [ ] Page load time acceptable (< 2 seconds)

**Verification:**
```php
// Enable query logging
DB::enableQueryLog();

// Perform action (e.g., list provinces)
// ...

// Check queries
dd(DB::getQueryLog());
```

---

### 7.4.2 Test with Large Datasets

**Test:** Verify performance with larger datasets

**Note:** May need to seed test data

**Test Cases:**
- [ ] 50+ provinces
- [ ] 100+ users
- [ ] Multiple provincial users per province
- [ ] Complex filtering scenarios

**Expected Results:**
- [ ] Performance remains acceptable
- [ ] No timeout errors
- [ ] Pagination works (if implemented)
- [ ] Indexes used effectively

---

## 🧪 Testing Phase 7.5: Edge Cases & Error Scenarios

### 7.5.1 Edge Cases

**Test Cases:**
- [ ] Province with no provincial users
- [ ] Province with multiple provincial users
- [ ] Provincial user with no province_id
- [ ] Provincial user with invalid province_id (should not happen with FK)
- [ ] Coordinator with no provincial children
- [ ] Coordinator with multiple provincial children (different provinces)
- [ ] Empty search results
- [ ] Special characters in province names
- [ ] Very long province names

**Expected Results:**
- [ ] All edge cases handled gracefully
- [ ] Appropriate messages displayed
- [ ] No errors in logs
- [ ] UI remains functional

---

### 7.5.2 Error Scenarios

**Test Cases:**
- [ ] Try to access removed routes (should get 404)
- [ ] Invalid province name in URL
- [ ] Invalid form data
- [ ] Duplicate province names (validation)
- [ ] Database connection issues (simulate)
- [ ] Missing relationships

**Expected Results:**
- [ ] Appropriate error messages
- [ ] No 500 errors
- [ ] Error logging works
- [ ] User-friendly error messages

---

## 📝 Testing Checklist Summary

### Pre-Migration
- [ ] Database backup created
- [ ] Test environment ready
- [ ] Test users created
- [ ] Current state documented

### Migration Execution
- [ ] Data migration runs successfully
- [ ] Schema migration runs successfully
- [ ] No errors in logs
- [ ] Data integrity verified

### Functional Testing
- [ ] Province listing works
- [ ] Province creation works
- [ ] Province editing works
- [ ] Provincial users display correctly
- [ ] Search functionality works
- [ ] Coordinator access verified
- [ ] Provincial user access verified

### Data Integrity
- [ ] Foreign keys valid
- [ ] No orphaned data
- [ ] Hierarchy correct
- [ ] Migration data correct

### Regression Testing
- [ ] Existing functionality works
- [ ] Coordinator dashboard works
- [ ] Provincial dashboard works
- [ ] User management works
- [ ] Reports work
- [ ] Filtering works

### Performance
- [ ] Query performance acceptable
- [ ] No N+1 queries
- [ ] Indexes used
- [ ] Page load times acceptable

### Edge Cases
- [ ] Edge cases handled
- [ ] Error scenarios handled
- [ ] Error messages appropriate

---

## 🚀 Post-Testing Actions

After completing all tests:

1. **Document Issues:**
   - [ ] Document any bugs found
   - [ ] Document any performance issues
   - [ ] Document any missing features

2. **Fix Issues:**
   - [ ] Fix critical bugs
   - [ ] Fix performance issues
   - [ ] Address missing features (if needed)

3. **Re-test:**
   - [ ] Re-test fixed issues
   - [ ] Verify all tests pass

4. **Prepare for Production:**
   - [ ] Update documentation
   - [ ] Prepare deployment notes
   - [ ] Plan deployment strategy

---

## 📋 Quick Verification Script

### Run this after migrations:

```bash
# Check migrations ran
php artisan migrate:status | grep -E "(migrate_provincial|remove_provincial)"

# Check Laravel logs
tail -100 storage/logs/laravel.log | grep -i error

# Test database connection
php artisan tinker
```

```php
// In tinker
use App\Models\Province;
use App\Models\User;

// Check provinces
Province::count();

// Check provincial users
User::where('role', 'provincial')->count();

// Check if column exists (should fail)
Schema::hasColumn('provinces', 'provincial_coordinator_id');
// Should return false

// Test relationship
$province = Province::with('provincialUsers')->first();
$province->provincialUsers;
// Should return collection of provincial users
```

---

## ⚠️ Important Notes

1. **Backup First:** Always backup before running migrations
2. **Test Environment:** Test in development/staging first
3. **Gradual Rollout:** Consider gradual rollout to production
4. **Monitor Logs:** Monitor Laravel logs during testing
5. **User Communication:** Inform users about changes
6. **Documentation:** Update user documentation if needed

---

**Last Updated:** 2026-01-11  
**Status:** Testing Guide Ready 📋  
**Next Steps:** Execute migrations, run testing checklist
