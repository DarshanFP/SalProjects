# Applicant User Access - Testing Guide

## Overview
This guide provides step-by-step testing procedures to verify that applicants have full executor-level access on projects where they are either the owner or in-charge.

---

## Pre-Testing Setup

### 1. Create Test Users
Ensure you have the following test users:
- **Applicant User 1:** Role = `applicant`, owns at least 1 project
- **Applicant User 2:** Role = `applicant`, is in-charge of at least 1 project (but not owner)
- **Applicant User 3:** Role = `applicant`, both owns and is in-charge of different projects
- **Executor User:** Role = `executor` (for comparison testing)
- **Test Project 1:** Owned by Applicant User 1
- **Test Project 2:** Owned by someone else, but Applicant User 2 is in-charge
- **Test Project 3:** Owned by Applicant User 3, Applicant User 3 is also in-charge

### 2. Test Data Requirements
- At least 2 projects with different statuses (draft, approved_by_coordinator)
- At least 1 monthly report for each test project
- At least 1 approved project for dashboard testing

---

## Test Scenarios

### Test Group 1: Project Access Tests

#### Test 1.1: Applicant Can Edit Own Project
**Steps:**
1. Log in as Applicant User 1
2. Navigate to Projects list (`/executor/projects`)
3. Find a project owned by Applicant User 1
4. Click "Edit" on the project
5. Make a minor change (e.g., update project title)
6. Save the changes

**Expected Result:** ✅ Project edits successfully
**Status:** [ ] Pass [ ] Fail

---

#### Test 1.2: Applicant Can Edit Project Where They Are In-Charge
**Steps:**
1. Log in as Applicant User 2
2. Navigate to Projects list (`/executor/projects`)
3. Find a project where Applicant User 2 is in-charge (but not owner)
4. Click "Edit" on the project
5. Make a minor change
6. Save the changes

**Expected Result:** ✅ Project edits successfully (NEW BEHAVIOR)
**Status:** [ ] Pass [ ] Fail

**Note:** This is the key test - previously applicants could NOT edit projects where they were only in-charge.

---

#### Test 1.3: Applicant Cannot Edit Unauthorized Project
**Steps:**
1. Log in as Applicant User 1
2. Try to access a project URL where they are neither owner nor in-charge
3. Attempt to edit the project

**Expected Result:** ❌ Access denied (403 error or redirect)
**Status:** [ ] Pass [ ] Fail

---

#### Test 1.4: Applicant Can View Own Project
**Steps:**
1. Log in as Applicant User 1
2. Navigate to a project they own
3. View project details

**Expected Result:** ✅ Project details display correctly
**Status:** [ ] Pass [ ] Fail

---

#### Test 1.5: Applicant Can View Project Where They Are In-Charge
**Steps:**
1. Log in as Applicant User 2
2. Navigate to a project where they are in-charge
3. View project details

**Expected Result:** ✅ Project details display correctly
**Status:** [ ] Pass [ ] Fail

---

#### Test 1.6: Applicant Can Submit Own Project
**Steps:**
1. Log in as Applicant User 1
2. Navigate to a project they own with status "draft"
3. Click "Submit to Provincial"
4. Confirm submission

**Expected Result:** ✅ Project submits successfully
**Status:** [ ] Pass [ ] Fail

---

#### Test 1.7: Applicant Can Submit Project Where They Are In-Charge
**Steps:**
1. Log in as Applicant User 2
2. Navigate to a project where they are in-charge with status "draft"
3. Click "Submit to Provincial"
4. Confirm submission

**Expected Result:** ✅ Project submits successfully (NEW BEHAVIOR)
**Status:** [ ] Pass [ ] Fail

---

### Test Group 2: Dashboard Tests

#### Test 2.1: Dashboard Shows Owned Projects
**Steps:**
1. Log in as Applicant User 1
2. Navigate to Executor Dashboard (`/executor/dashboard`)
3. Check the list of approved projects

**Expected Result:** ✅ Shows approved projects owned by Applicant User 1
**Status:** [ ] Pass [ ] Fail

---

#### Test 2.2: Dashboard Shows In-Charge Projects
**Steps:**
1. Log in as Applicant User 2
2. Navigate to Executor Dashboard (`/executor/dashboard`)
3. Check the list of approved projects

**Expected Result:** ✅ Shows approved projects where Applicant User 2 is in-charge (NEW BEHAVIOR)
**Status:** [ ] Pass [ ] Fail

**Note:** Previously, applicants would NOT see projects where they were only in-charge.

---

#### Test 2.3: Dashboard Budget Summaries
**Steps:**
1. Log in as Applicant User 3 (owns and is in-charge of different projects)
2. Navigate to Executor Dashboard
3. Check budget summaries

**Expected Result:** ✅ Budget summaries include all projects (owned + in-charge)
**Status:** [ ] Pass [ ] Fail

---

### Test Group 3: Report List Tests

#### Test 3.1: Report List Shows Owned Project Reports
**Steps:**
1. Log in as Applicant User 1
2. Navigate to Report List (`/executor/report-list`)
3. Check the list of reports

**Expected Result:** ✅ Shows reports for projects owned by Applicant User 1
**Status:** [ ] Pass [ ] Fail

---

#### Test 3.2: Report List Shows In-Charge Project Reports
**Steps:**
1. Log in as Applicant User 2
2. Navigate to Report List (`/executor/report-list`)
3. Check the list of reports

**Expected Result:** ✅ Shows reports for projects where Applicant User 2 is in-charge (NEW BEHAVIOR)
**Status:** [ ] Pass [ ] Fail

---

#### Test 3.3: Pending Reports List
**Steps:**
1. Log in as Applicant User 2
2. Navigate to Pending Reports (`/executor/report-list/pending`)
3. Check the list

**Expected Result:** ✅ Shows pending reports for projects where user is owner or in-charge
**Status:** [ ] Pass [ ] Fail

---

#### Test 3.4: Approved Reports List
**Steps:**
1. Log in as Applicant User 2
2. Navigate to Approved Reports (`/executor/report-list/approved`)
3. Check the list

**Expected Result:** ✅ Shows approved reports for projects where user is owner or in-charge
**Status:** [ ] Pass [ ] Fail

---

### Test Group 4: Monthly Report Tests

#### Test 4.1: Create Report for Owned Project
**Steps:**
1. Log in as Applicant User 1
2. Navigate to a project they own
3. Create a new monthly report
4. Fill in report details
5. Save the report

**Expected Result:** ✅ Report creates successfully
**Status:** [ ] Pass [ ] Fail

---

#### Test 4.2: Create Report for In-Charge Project
**Steps:**
1. Log in as Applicant User 2
2. Navigate to a project where they are in-charge
3. Create a new monthly report
4. Fill in report details
5. Save the report

**Expected Result:** ✅ Report creates successfully (NEW BEHAVIOR)
**Status:** [ ] Pass [ ] Fail

---

#### Test 4.3: Edit Report for In-Charge Project
**Steps:**
1. Log in as Applicant User 2
2. Navigate to Reports list
3. Find a report for a project where they are in-charge
4. Click "Edit"
5. Make changes
6. Save

**Expected Result:** ✅ Report edits successfully (NEW BEHAVIOR)
**Status:** [ ] Pass [ ] Fail

---

#### Test 4.4: Submit Report for In-Charge Project
**Steps:**
1. Log in as Applicant User 2
2. Navigate to a report for a project where they are in-charge
3. Submit the report

**Expected Result:** ✅ Report submits successfully (NEW BEHAVIOR)
**Status:** [ ] Pass [ ] Fail

---

#### Test 4.5: View Report for In-Charge Project
**Steps:**
1. Log in as Applicant User 2
2. Navigate to Reports list
3. Click "View" on a report for a project where they are in-charge

**Expected Result:** ✅ Report displays correctly
**Status:** [ ] Pass [ ] Fail

---

### Test Group 5: Aggregated Report Tests

#### Test 5.1: Generate Quarterly Report for Owned Project
**Steps:**
1. Log in as Applicant User 1
2. Navigate to a project they own
3. Generate a quarterly report
4. Complete the generation process

**Expected Result:** ✅ Quarterly report generates successfully
**Status:** [ ] Pass [ ] Fail

---

#### Test 5.2: Generate Quarterly Report for In-Charge Project
**Steps:**
1. Log in as Applicant User 2
2. Navigate to a project where they are in-charge
3. Generate a quarterly report
4. Complete the generation process

**Expected Result:** ✅ Quarterly report generates successfully (NEW BEHAVIOR)
**Status:** [ ] Pass [ ] Fail

---

#### Test 5.3: View Aggregated Reports List
**Steps:**
1. Log in as Applicant User 2
2. Navigate to Aggregated Reports (`/reports/aggregated/quarterly/index`)
3. Check the list

**Expected Result:** ✅ Shows aggregated reports for projects where user is owner or in-charge (NEW BEHAVIOR)
**Status:** [ ] Pass [ ] Fail

---

#### Test 5.4: Export Aggregated Report
**Steps:**
1. Log in as Applicant User 2
2. Navigate to an aggregated report for a project where they are in-charge
3. Click "Export PDF" or "Export DOC"

**Expected Result:** ✅ Export works successfully (NEW BEHAVIOR)
**Status:** [ ] Pass [ ] Fail

---

### Test Group 6: Edge Cases

#### Test 6.1: User is Both Owner and In-Charge
**Steps:**
1. Log in as Applicant User 3 (who is both owner and in-charge of a project)
2. Navigate to dashboard
3. Check project count

**Expected Result:** ✅ Project appears only once (no duplicates)
**Status:** [ ] Pass [ ] Fail

---

#### Test 6.2: Multiple Projects (Mix of Owned and In-Charge)
**Steps:**
1. Log in as Applicant User 3
2. Navigate to Projects list
3. Count projects

**Expected Result:** ✅ Shows all projects (owned + in-charge), no duplicates
**Status:** [ ] Pass [ ] Fail

---

#### Test 6.3: No Projects
**Steps:**
1. Log in as an applicant with no projects (neither owner nor in-charge)
2. Navigate to dashboard

**Expected Result:** ✅ Empty state displays correctly (no errors)
**Status:** [ ] Pass [ ] Fail

---

#### Test 6.4: Project Status Filtering
**Steps:**
1. Log in as Applicant User 2
2. Navigate to Projects list
3. Check that only editable projects are shown (approved projects excluded)

**Expected Result:** ✅ Only editable projects shown (NEW BEHAVIOR - should exclude approved)
**Status:** [ ] Pass [ ] Fail

---

### Test Group 7: Security Tests

#### Test 7.1: Cannot Access Unauthorized Project
**Steps:**
1. Log in as Applicant User 1
2. Try to directly access a project URL where they have no access
3. Try to edit it

**Expected Result:** ❌ Access denied (403 or redirect)
**Status:** [ ] Pass [ ] Fail

---

#### Test 7.2: Cannot Access Unauthorized Report
**Steps:**
1. Log in as Applicant User 1
2. Try to directly access a report URL for a project they have no access to

**Expected Result:** ❌ Access denied (403 or redirect)
**Status:** [ ] Pass [ ] Fail

---

#### Test 7.3: Cannot Submit Unauthorized Report
**Steps:**
1. Log in as Applicant User 1
2. Try to submit a report for a project they have no access to

**Expected Result:** ❌ Access denied or validation error
**Status:** [ ] Pass [ ] Fail

---

## Test Execution Log

### Test Date: _______________
### Tester: _______________

| Test ID | Test Name | Status | Notes |
|---------|-----------|--------|-------|
| 1.1 | Edit Own Project | [ ] | |
| 1.2 | Edit In-Charge Project | [ ] | |
| 1.3 | Cannot Edit Unauthorized | [ ] | |
| 1.4 | View Own Project | [ ] | |
| 1.5 | View In-Charge Project | [ ] | |
| 1.6 | Submit Own Project | [ ] | |
| 1.7 | Submit In-Charge Project | [ ] | |
| 2.1 | Dashboard Owned Projects | [ ] | |
| 2.2 | Dashboard In-Charge Projects | [ ] | |
| 2.3 | Dashboard Budget Summaries | [ ] | |
| 3.1 | Report List Owned | [ ] | |
| 3.2 | Report List In-Charge | [ ] | |
| 3.3 | Pending Reports | [ ] | |
| 3.4 | Approved Reports | [ ] | |
| 4.1 | Create Report Owned | [ ] | |
| 4.2 | Create Report In-Charge | [ ] | |
| 4.3 | Edit Report In-Charge | [ ] | |
| 4.4 | Submit Report In-Charge | [ ] | |
| 4.5 | View Report In-Charge | [ ] | |
| 5.1 | Generate Quarterly Owned | [ ] | |
| 5.2 | Generate Quarterly In-Charge | [ ] | |
| 5.3 | View Aggregated Reports | [ ] | |
| 5.4 | Export Aggregated Report | [ ] | |
| 6.1 | Both Owner and In-Charge | [ ] | |
| 6.2 | Multiple Projects | [ ] | |
| 6.3 | No Projects | [ ] | |
| 6.4 | Status Filtering | [ ] | |
| 7.1 | Security: Unauthorized Project | [ ] | |
| 7.2 | Security: Unauthorized Report | [ ] | |
| 7.3 | Security: Unauthorized Submit | [ ] | |

---

## Quick Test Checklist

### Critical Tests (Must Pass)
- [ ] Test 1.2: Applicant can edit project where they are in-charge
- [ ] Test 1.7: Applicant can submit project where they are in-charge
- [ ] Test 2.2: Dashboard shows in-charge projects
- [ ] Test 3.2: Report list shows in-charge project reports
- [ ] Test 4.2: Create report for in-charge project
- [ ] Test 4.3: Edit report for in-charge project
- [ ] Test 7.1: Security - cannot access unauthorized project

---

## Common Issues and Solutions

### Issue: Applicant still cannot edit in-charge project
**Solution:** 
- Check that `ProjectPermissionHelper::canApplicantEdit()` uses `isOwnerOrInCharge()`
- Verify the project's `in_charge` field matches the applicant's user ID

### Issue: Dashboard not showing in-charge projects
**Solution:**
- Check `ExecutorController::ExecutorDashboard()` includes `orWhere('in_charge', $user->id)`
- Verify project status is `approved_by_coordinator`

### Issue: Reports not showing for in-charge projects
**Solution:**
- Check that report controllers filter by project IDs (not just user_id)
- Verify the project relationship is correct

### Issue: Duplicate projects in lists
**Solution:**
- Check that queries use `where(function($query) use ($user) { ... })` properly
- Ensure no duplicate entries in database

---

## Browser Console Checks

While testing, check browser console for:
- [ ] No JavaScript errors
- [ ] No 403/404 errors in Network tab
- [ ] No unauthorized API calls

---

## Database Verification

After testing, verify in database:
```sql
-- Check projects where applicant is in-charge
SELECT * FROM projects WHERE in_charge = [applicant_user_id];

-- Check reports for those projects
SELECT * FROM DP_Reports WHERE project_id IN (
    SELECT project_id FROM projects WHERE in_charge = [applicant_user_id]
);
```

---

## Test Results Summary

### Total Tests: 27
### Passed: ___
### Failed: ___
### Pass Rate: ___%

### Critical Tests Status:
- [ ] All critical tests passed
- [ ] Security tests passed
- [ ] Edge cases handled

---

## Notes

_Add any additional notes or observations here:_



