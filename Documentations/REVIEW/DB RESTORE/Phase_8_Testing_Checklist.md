# Phase 8: Testing & Cleanup Checklist

**Date Created:** 2026-01-11  
**Status:** 📋 Testing Checklist Created  
**Purpose:** Comprehensive testing guide for Provinces & Centers migration

---

## 8.1 Functional Testing Checklist

### Province Management Tests

- [ ] **Test Province Creation**
  - Create a new province via General user
  - Verify province appears in list
  - Verify centers can be added during creation
  - Verify province is stored in database correctly

- [ ] **Test Province Editing**
  - Edit province name
  - Verify all users with that province_id are updated
  - Verify backward compatibility (VARCHAR field updated)
  - Test editing centers (add/remove)

- [ ] **Test Province Deletion**
  - Delete province with no users (should succeed)
  - Delete province with users (should fail with error message)
  - Verify centers are cascade deleted when province deleted

- [ ] **Test Provincial Coordinator Assignment**
  - Assign coordinator to province
  - Change coordinator
  - Remove coordinator
  - Verify user's province field is updated

### Center Management Tests

- [ ] **Test Center Assignment**
  - Assign center to user during user creation
  - Verify center_id is set correctly
  - Verify backward compatibility (VARCHAR field set)

- [ ] **Test Center Transfer**
  - Transfer center between provinces (General user)
  - Verify center's province_id is updated
  - Verify users with that center are updated recursively (if applicable)

- [ ] **Test Center Creation/Deletion**
  - Create center via province edit form
  - Remove center from province edit form (should deactivate)
  - Verify unique constraint (same center name in different provinces)

### Form Tests

- [ ] **Test All Forms with Database Dropdowns**
  - Coordinator creation form (province dropdown)
  - Provincial creation form (province dropdown)
  - Executor creation form (province & center dropdowns)
  - Applicant creation form (province & center dropdowns)
  - User edit forms (all roles)
  - Project forms (if applicable)

- [ ] **Test Center Filtering (JavaScript)**
  - Select province in form
  - Verify centers dropdown filters correctly
  - Verify centers come from database (check network requests)

### Filtering & Reports Tests

- [ ] **Test Filtering by Province**
  - Filter projects by province
  - Filter reports by province
  - Filter users by province
  - Verify filters use province_id

- [ ] **Test Filtering by Center**
  - Filter projects by center
  - Filter reports by center
  - Filter users by center
  - Verify filters use center_id

- [ ] **Test Reports with Province/Center Filters**
  - Generate reports filtered by province
  - Generate reports filtered by center
  - Verify data accuracy
  - Test combined filters (province + center)

### User Management Tests

- [ ] **Test User Creation (All Roles)**
  - Create coordinator user
  - Create provincial user
  - Create executor user
  - Create applicant user
  - Verify province_id and center_id are set correctly

- [ ] **Test User Editing (All Roles)**
  - Edit user province
  - Edit user center
  - Verify both VARCHAR and foreign key fields are updated

- [ ] **Test Recursive Center Updates**
  - Update center for user with child users
  - Verify child users' centers are updated recursively
  - Test nested hierarchies (coordinator -> provincial -> executor)

---

## 8.2 Data Integrity Testing Checklist

### Foreign Key Constraints

- [ ] **Test Foreign Key Constraints**
  - Try to set invalid province_id (should fail)
  - Try to set invalid center_id (should fail)
  - Verify foreign key errors are handled gracefully

- [ ] **Test ON DELETE CASCADE**
  - Delete province → centers should be cascade deleted
  - Verify centers table: centers with that province_id are deleted

- [ ] **Test ON DELETE SET NULL**
  - Delete user who is provincial_coordinator → province.provincial_coordinator_id should be set to NULL
  - Delete user who created province → province.created_by should be set to NULL
  - Delete center → users.center_id should be set to NULL
  - Delete province → users.province_id should be set to NULL

### Unique Constraints

- [ ] **Test Province Name Uniqueness**
  - Try to create duplicate province name (should fail)
  - Verify unique constraint error is shown

- [ ] **Test Province-Center Combination Uniqueness**
  - Create center "Test Center" in Province A
  - Create center "Test Center" in Province B (should succeed - same name in different province)
  - Try to create duplicate "Test Center" in Province A (should fail)

### Orphaned Data Handling

- [ ] **Test Orphaned Data Scenarios**
  - Check for users with province_id pointing to deleted province
  - Check for users with center_id pointing to deleted center
  - Verify SET NULL constraints handle orphaned data correctly
  - Run data integrity check script if available

### Deletion Scenarios

- [ ] **Test Province Deletion with Users**
  - Attempt to delete province with users assigned
  - Verify error message shows list of users
  - Verify province is not deleted
  - Remove users, then delete province (should succeed)

- [ ] **Test Center Deletion**
  - Delete center with users assigned
  - Verify users.center_id is set to NULL (due to ON DELETE SET NULL)
  - Verify users.center VARCHAR field is cleared or updated

---

## 8.3 Performance Testing Checklist

### Query Performance

- [ ] **Test Query Performance with Large Datasets**
  - Test with 100+ users
  - Test with 50+ centers
  - Test with 20+ provinces
  - Measure query execution time
  - Use Laravel Debugbar or similar tool

- [ ] **Test Index Effectiveness**
  - Verify indexes are being used (EXPLAIN queries)
  - Test province name lookups (should use index)
  - Test province_id lookups (should use index)
  - Test center_id lookups (should use index)

### N+1 Query Optimization

- [ ] **Test for N+1 Queries**
  - Enable query logging
  - Load provinces list page
  - Verify eager loading is used (with('coordinator', 'centers'))
  - Check query count (should be minimal)

- [ ] **Test Eager Loading**
  - Verify `with()` is used in controllers
  - Test API endpoints with `?include` parameter
  - Verify relationships load efficiently

### Caching Tests

- [ ] **Test Centers Map Cache**
  - Verify cache key 'centers_map' is used
  - Clear cache and verify data is re-cached
  - Test cache expiration (24 hours)
  - Verify cache improves performance

- [ ] **Test Cache Invalidation**
  - Create new center → cache should be cleared/updated
  - Update center → cache should be cleared/updated
  - Delete center → cache should be cleared/updated

### Load Testing

- [ ] **Test Concurrent Requests**
  - Multiple users accessing province lists simultaneously
  - Test API endpoints under load
  - Verify no race conditions in cache updates

---

## 8.4 Code Cleanup Checklist

### Hardcoded Arrays Verification

- [x] **Verify All Hardcoded Arrays Removed**
  - ✅ `getCentersMap()` uses database queries
  - ✅ All validation rules use `exists:provinces,name`
  - ✅ No hardcoded province lists in code
  - ✅ No hardcoded center arrays in controllers

### Unused Code Removal

- [ ] **Remove Unused Code/Comments**
  - Search for commented-out code blocks
  - Remove obsolete comments about hardcoded arrays
  - Clean up debug code (dd(), dump(), etc.)
  - Remove unused imports

### Deprecated Methods

- [ ] **Verify No Deprecated Methods**
  - Check for @deprecated annotations
  - Verify no old validation patterns remain
  - Check for obsolete helper methods

### Documentation Updates

- [ ] **Update Inline Documentation**
  - Update PHPDoc comments to reflect database usage
  - Update method descriptions
  - Add examples where helpful

### Code Review

- [ ] **Review Helper Methods**
  - Verify `getCentersMap()` is optimized
  - Check `getCentersByProvince()` usage
  - Review province/center query patterns
  - Optimize repeated queries

---

## Testing Environment Setup

### Prerequisites

1. **Database Setup**
   - Fresh database with migrations run
   - Seeders executed (provinces and centers)
   - Test data created (users, projects, reports)

2. **Test Users**
   - General user (for province management)
   - Coordinator user (for testing coordinator features)
   - Provincial user (for testing provincial features)
   - Executor user (for testing executor features)
   - Applicant user (for testing applicant features)

3. **Test Data**
   - Multiple provinces (at least 5)
   - Multiple centers per province (at least 3-5)
   - Users assigned to different provinces/centers
   - Projects and reports with province/center filters

### Testing Tools

- Laravel Debugbar (for query analysis)
- Browser DevTools (for JavaScript testing)
- Postman/curl (for API testing)
- Database client (for direct database checks)

---

## Expected Results Summary

After completing all tests, you should verify:

✅ All forms use database-driven dropdowns  
✅ All queries use foreign keys (province_id, center_id)  
✅ Backward compatibility maintained (VARCHAR fields updated)  
✅ Data integrity constraints working correctly  
✅ Performance is acceptable (no N+1 queries, caching works)  
✅ No hardcoded arrays remain  
✅ Code is clean and well-documented  

---

## Notes

- **Backward Compatibility:** VARCHAR fields (province, center) are intentionally kept during transition period
- **Cache Key:** Centers map cache key is 'centers_map' with 24-hour expiration
- **Error Handling:** All foreign key violations should show user-friendly error messages
- **Data Migration:** Existing data should have been migrated in Phase 2 (verify migration success rate)

---

**Last Updated:** 2026-01-11  
**Next Steps:** Execute testing checklist, document results, perform code cleanup
