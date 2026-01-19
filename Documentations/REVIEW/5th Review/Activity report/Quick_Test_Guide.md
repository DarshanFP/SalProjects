# Quick Test Guide - Activity Report Feature

**Date:** January 2025  
**Purpose:** Quick manual testing guide for Activity Report feature

---

## Prerequisites

1. ✅ Migrations run successfully
2. ✅ At least one user of each role exists:
   - Executor
   - Applicant
   - Provincial
   - Coordinator
   - Admin
3. ✅ At least one project exists
4. ✅ At least one report exists

---

## Quick Test Steps

### Step 1: Test Route Accessibility

#### As Executor/Applicant:
1. Login as executor/applicant
2. Click "My Activities" in sidebar
3. **Expected:** Page loads showing "My Activities" title
4. **Expected:** Empty state message if no activities

#### As Provincial:
1. Login as provincial
2. Click "Team Activities" in sidebar
3. **Expected:** Page loads showing "Team Activities" title
4. **Expected:** Empty state message if no activities

#### As Coordinator/Admin:
1. Login as coordinator/admin
2. Click "All Activities" in sidebar
3. **Expected:** Page loads showing "All Activities" title
4. **Expected:** Empty state message if no activities

---

### Step 2: Create Test Data

#### Create Project Status Change:
1. As executor, create or edit a project
2. Submit project to provincial
3. **Expected:** Activity logged in `activity_histories` table
4. **Expected:** Activity appears in "My Activities" (executor)
5. **Expected:** Activity appears in "Team Activities" (provincial)
6. **Expected:** Activity appears in "All Activities" (coordinator)

#### Create Report Status Change:
1. As executor, create or edit a report
2. Submit report to provincial
3. **Expected:** Activity logged in `activity_histories` table
4. **Expected:** Activity appears in "My Activities" (executor)
5. **Expected:** Activity appears in "Team Activities" (provincial)
6. **Expected:** Activity appears in "All Activities" (coordinator)

---

### Step 3: Test Filters

#### Type Filter:
1. Go to any activity page
2. Select "Projects" from Type filter
3. Click "Filter"
4. **Expected:** Only project activities shown

5. Select "Reports" from Type filter
6. Click "Filter"
7. **Expected:** Only report activities shown

#### Status Filter:
1. Select a status (e.g., "Approved")
2. Click "Filter"
3. **Expected:** Only activities with that status shown

#### Date Range Filter:
1. Select "From Date" and "To Date"
2. Click "Filter"
3. **Expected:** Only activities within date range shown

#### Search Filter:
1. Enter search term (user name, project ID, etc.)
2. Click "Filter"
3. **Expected:** Matching activities shown

---

### Step 4: Test Project History

1. Go to any project show page
2. Navigate to: `/projects/{project_id}/activity-history`
3. **Expected:** Page shows activity history for that project
4. **Expected:** "Back to Project" button works
5. **Expected:** Activities ordered by date (newest first)

---

### Step 5: Test Report History

1. Go to any report show page
2. Navigate to: `/reports/{report_id}/activity-history`
3. **Expected:** Page shows activity history for that report
4. **Expected:** "Back to Report" button works
5. **Expected:** Activities ordered by date (newest first)

---

### Step 6: Test Permission Boundaries

#### Executor Cannot Access:
1. As executor, try to access: `/activities/team-activities`
2. **Expected:** 403 Forbidden error

3. As executor, try to access: `/activities/all-activities`
4. **Expected:** 403 Forbidden error

#### Provincial Cannot Access:
1. As provincial, try to access: `/activities/my-activities`
2. **Expected:** 403 Forbidden error

3. As provincial, try to access: `/activities/all-activities`
4. **Expected:** 403 Forbidden error

#### Coordinator Can Access All:
1. As coordinator, access all routes
2. **Expected:** All routes accessible

---

## Common Issues & Solutions

### Issue: "Route not found"
**Solution:** Run `php artisan route:clear` and `php artisan route:cache`

### Issue: "View not found"
**Solution:** Check view files exist in `resources/views/activity-history/`

### Issue: "No activities showing"
**Solution:** 
- Check `activity_histories` table has data
- Verify user has access to projects/reports
- Check filters aren't too restrictive

### Issue: "403 Forbidden"
**Solution:**
- Verify user role is correct
- Check middleware is applied correctly
- Verify permission helper logic

### Issue: "Badge colors not showing"
**Solution:**
- Check Bootstrap CSS is loaded
- Verify badge classes in ActivityHistory model

---

## Database Verification

### Check Activity History Data:
```sql
SELECT * FROM activity_histories ORDER BY created_at DESC LIMIT 10;
```

### Check Project Status History (old table):
```sql
SELECT * FROM project_status_histories ORDER BY created_at DESC LIMIT 10;
```

### Count Activities by Type:
```sql
SELECT type, COUNT(*) as count FROM activity_histories GROUP BY type;
```

---

## Performance Check

### Check Query Count:
1. Enable query logging in Laravel
2. Load activity page
3. Check number of queries (should be < 10)

### Check Page Load Time:
1. Load activity page
2. Check browser DevTools Network tab
3. Page should load in < 2 seconds

---

## Test Checklist Summary

- [ ] Routes accessible
- [ ] Views render correctly
- [ ] Empty states work
- [ ] Activities display correctly
- [ ] Filters work
- [ ] Search works
- [ ] Project history works
- [ ] Report history works
- [ ] Permissions enforced
- [ ] Status changes logged
- [ ] Links work correctly
- [ ] No errors in logs

---

## Next Steps After Testing

1. **If all tests pass:**
   - Proceed to Phase 7 (Documentation)
   - Create user guide
   - Document any edge cases found

2. **If issues found:**
   - Document issues in Phase_6_Testing_Checklist.md
   - Fix critical issues first
   - Re-test after fixes

---

**Last Updated:** January 2025  
**Status:** Ready for Testing
