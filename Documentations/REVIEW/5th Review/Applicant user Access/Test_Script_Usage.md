# Test Script Usage Guide

## Quick Start

### Basic Usage
```bash
php artisan test:applicant-access
```

This will:
- Find the first applicant user in the database
- Run all tests
- Display results

### With Specific Applicant
```bash
php artisan test:applicant-access --applicant-id=123
```

### Create Test Data
```bash
php artisan test:applicant-access --create-test-data
```

This will automatically set an applicant as in-charge of a project for testing.

### Detailed Output
```bash
php artisan test:applicant-access --detailed
```

Shows detailed information for all tests, not just failures.

### All Options Combined
```bash
php artisan test:applicant-access --applicant-id=123 --create-test-data --detailed
```

---

## Test Coverage

The script tests:

### 1. Project Access (4 tests)
- Finding owned projects
- Finding in-charge projects ⭐ **KEY TEST**
- Finding projects where user is both owner and in-charge
- Total accessible projects count

### 2. Permission Helper (7 tests)
- `canEdit()` for owned projects
- `canView()` for owned projects
- `canApplicantEdit()` for owned projects
- `canEdit()` for in-charge projects ⭐ **KEY TEST**
- `canView()` for in-charge projects
- `canApplicantEdit()` for in-charge projects ⭐ **KEY TEST**
- `getEditableProjects()` count

### 3. Dashboard Queries (2 tests)
- Approved projects query (simulates ExecutorDashboard)
- In-charge approved projects ⭐ **KEY TEST**

### 4. Report Queries (4 tests)
- Total reports for accessible projects
- Reports for in-charge projects ⭐ **KEY TEST**
- Pending reports
- Approved reports

### 5. Aggregated Reports (1 test)
- Quarterly reports for accessible projects

**Total: 18 tests**

---

## Example Output

```
🧪 Testing Applicant Access Functionality
==========================================

✅ Using applicant: John Doe (ID: 5)

🔍 Running Tests...

📋 Test Group 1: Project Access
   ✅ PASS - Find owned projects
      Found 3 owned projects
   ✅ PASS - Find in-charge projects (not owner) ⭐
      Found 2 in-charge projects
   ✅ PASS - Find projects (owner & in-charge)
      Found 1 projects where applicant is both owner and in-charge
   ✅ PASS - Total accessible projects
      Total: 4 projects (owned + in-charge)

🔐 Test Group 2: Permission Helper
   ✅ PASS - canEdit() - owned project
      Can edit: Yes
   ✅ PASS - canView() - owned project
      Can view: Yes
   ✅ PASS - canApplicantEdit() - owned project
      Can applicant edit: Yes
   ✅ PASS - canEdit() - in-charge project ⭐
      Can edit in-charge project: Yes ✅
   ✅ PASS - canView() - in-charge project
      Can view in-charge project: Yes
   ✅ PASS - canApplicantEdit() - in-charge project ⭐
      Can applicant edit in-charge project: Yes ✅
   ✅ PASS - getEditableProjects() count
      Editable projects: 2

📊 Test Group 3: Dashboard Queries
   ✅ PASS - Approved projects (dashboard) ⭐
      Found 2 approved projects
   ✅ PASS - In-charge approved projects ⭐
      Owned: 1, In-charge: 1

📄 Test Group 4: Report Queries
   ✅ PASS - Total reports (accessible projects)
      Found 15 reports
   ✅ PASS - Reports for in-charge projects ⭐
      Owned projects reports: 10, In-charge projects reports: 5
   ✅ PASS - Pending reports
      Found 3 pending reports
   ✅ PASS - Approved reports
      Found 12 approved reports

📈 Test Group 5: Aggregated Reports
   ✅ PASS - Quarterly reports (accessible projects)
      Found 4 quarterly reports

📊 Test Results Summary
========================

Total Tests: 18
Passed: 18
Failed: 0

🎉 All tests passed!

⭐ Key Tests (Critical Functionality):
   ✅ 1.2: Find in-charge projects (not owner) ⭐
   ✅ 2.4: canEdit() - in-charge project ⭐
   ✅ 2.6: canApplicantEdit() - in-charge project ⭐
   ✅ 3.1: Approved projects (dashboard) ⭐
   ✅ 3.2: In-charge approved projects ⭐
   ✅ 4.2: Reports for in-charge projects ⭐
```

---

## Troubleshooting

### No Applicant User Found
```
❌ No applicant users found in database.
💡 Create an applicant user first or use --applicant-id option.
```

**Solution:** Create an applicant user or specify an existing one with `--applicant-id`.

### No In-Charge Project Found
```
⚠️  No in-charge project found - this is a KEY test case!
💡 Use --create-test-data to set up a test project
```

**Solution:** Run with `--create-test-data` flag to automatically set up test data.

### Tests Failing
If tests fail, check:
1. Database has proper data
2. Projects have correct `user_id` and `in_charge` values
3. Reports exist for the test projects
4. Clear cache: `php artisan cache:clear`

---

## Integration with CI/CD

You can integrate this into your testing pipeline:

```bash
# In your test script
php artisan test:applicant-access --applicant-id=1 --verbose

# Check exit code
if [ $? -eq 0 ]; then
    echo "All tests passed!"
else
    echo "Some tests failed!"
    exit 1
fi
```

---

## Next Steps After Testing

1. **If all tests pass:** ✅ Implementation is working correctly
2. **If key tests fail:** Review the implementation changes
3. **If some tests fail:** Check test data and database state
4. **Manual testing:** Use the Testing Guide for manual browser testing

---

## Notes

- The script only tests the **backend logic** - it doesn't test UI/views
- For complete testing, combine with manual browser testing
- The script uses actual database data - be careful in production
- Use `--create-test-data` carefully as it modifies database
