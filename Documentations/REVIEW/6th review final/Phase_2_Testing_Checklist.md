# Phase 2 Testing Checklist

**Date:** January 2025  
**Phase:** Phase 2 - Testing & High Priority Features  
**Status:** ⏳ **IN PROGRESS**

---

## Phase 2.1: General User Role Comprehensive Testing

### Test Scenarios Checklist

#### Coordinator Management
- [ ] **Create Coordinator**
  - [ ] General can create new coordinator
  - [ ] Coordinator created with correct parent_id
  - [ ] Coordinator appears in coordinators list
  - [ ] Validation works (required fields, email format, etc.)
  - [ ] Success message displays

- [ ] **Edit Coordinator**
  - [ ] General can edit coordinator details
  - [ ] Changes save correctly
  - [ ] Province and center can be updated
  - [ ] Validation works

- [ ] **Activate/Deactivate Coordinator**
  - [ ] General can activate coordinator
  - [ ] General can deactivate coordinator
  - [ ] Status changes reflect correctly
  - [ ] Deactivated coordinators filtered correctly

- [ ] **Reset Coordinator Password**
  - [ ] Password reset functionality works
  - [ ] New password is hashed
  - [ ] Coordinator can login with new password

#### Direct Team Management
- [ ] **Create Direct Team Member (Executor/Applicant)**
  - [ ] General can create executor/applicant under direct team
  - [ ] User created with correct parent_id (general user)
  - [ ] User appears in direct team list
  - [ ] Validation works

- [ ] **Edit Direct Team Member**
  - [ ] General can edit direct team member
  - [ ] Changes save correctly
  - [ ] Province and center can be updated

- [ ] **Activate/Deactivate Direct Team Member**
  - [ ] General can activate/deactivate
  - [ ] Status changes reflect correctly

- [ ] **Reset Direct Team Member Password**
  - [ ] Password reset works
  - [ ] User can login with new password

#### Combined Project List
- [ ] **View Combined Projects**
  - [ ] General can view projects from coordinator hierarchy
  - [ ] General can view projects from direct team
  - [ ] Projects are combined in single list
  - [ ] Source indicator shows correctly (coordinator hierarchy vs direct team)

- [ ] **Filters Work**
  - [ ] Filter by coordinator (coordinator hierarchy projects)
  - [ ] Filter by province
  - [ ] Filter by center
  - [ ] Filter by project type
  - [ ] Filter by status
  - [ ] Multiple filters work together

- [ ] **Search Functionality**
  - [ ] Search by project ID works
  - [ ] Search by project title works
  - [ ] Search results are accurate
  - [ ] Search works with filters

- [ ] **Pagination**
  - [ ] Pagination displays correctly
  - [ ] Page navigation works
  - [ ] Items per page selector works
  - [ ] Pagination persists filters/search

#### Combined Report List
- [ ] **View Combined Reports**
  - [ ] General can view reports from coordinator hierarchy
  - [ ] General can view reports from direct team
  - [ ] Reports are combined in single list
  - [ ] Source indicator shows correctly

- [ ] **Report Filters Work**
  - [ ] Filter by coordinator
  - [ ] Filter by province
  - [ ] Filter by center
  - [ ] Filter by project type
  - [ ] Filter by status
  - [ ] Filter by date range

- [ ] **Report Search**
  - [ ] Search by report ID works
  - [ ] Search by project title works
  - [ ] Search results accurate

- [ ] **Report Pagination**
  - [ ] Pagination works correctly
  - [ ] Pagination with filters works

#### Dual-Role Approval
- [ ] **Approve as Coordinator**
  - [ ] General can select "Approve as Coordinator" context
  - [ ] Approval requires commencement date (for projects)
  - [ ] Budget validation works
  - [ ] Project/report approved correctly
  - [ ] Status changes to "approved_by_coordinator"
  - [ ] Activity history logged correctly
  - [ ] Notification sent to executor

- [ ] **Approve as Provincial**
  - [ ] General can select "Approve as Provincial" context
  - [ ] Approval forwards to coordinator (for projects)
  - [ ] No commencement date required
  - [ ] Status changes to "forwarded_to_coordinator"
  - [ ] Activity history logged correctly

#### Dual-Role Revert
- [ ] **Revert as Coordinator**
  - [ ] General can select "Revert as Coordinator" context
  - [ ] Revert reason required
  - [ ] Level selection works (executor, applicant, provincial, coordinator)
  - [ ] Status changes to appropriate revert status
  - [ ] Activity history logged correctly
  - [ ] Notification sent to affected user

- [ ] **Revert as Provincial**
  - [ ] General can select "Revert as Provincial" context
  - [ ] Revert reason required
  - [ ] Level selection works
  - [ ] Status changes correctly
  - [ ] Activity history logged correctly

#### Granular Revert Levels
- [ ] **Revert to Executor**
  - [ ] Status changes to "reverted_to_executor"
  - [ ] Executor can see reverted project/report
  - [ ] Executor can make changes

- [ ] **Revert to Applicant**
  - [ ] Status changes to "reverted_to_applicant"
  - [ ] Applicant can see reverted project/report
  - [ ] Applicant can make changes

- [ ] **Revert to Provincial**
  - [ ] Status changes to "reverted_to_provincial"
  - [ ] Provincial can see reverted project/report
  - [ ] Provincial can review and forward

- [ ] **Revert to Coordinator**
  - [ ] Status changes to "reverted_to_coordinator"
  - [ ] Coordinator can see reverted project/report
  - [ ] Coordinator can review and approve

#### Comment Functionality
- [ ] **Add Project Comment**
  - [ ] General can add comment to project
  - [ ] Comment saves correctly
  - [ ] Comment appears in project comments list
  - [ ] Comment visible to relevant users

- [ ] **Edit Project Comment**
  - [ ] General can edit own comments
  - [ ] Changes save correctly
  - [ ] Edit history tracked (if applicable)

- [ ] **Update Project Comment**
  - [ ] Comment updates work
  - [ ] Updated timestamp reflects

- [ ] **Add Report Comment**
  - [ ] General can add comment to report
  - [ ] Comment saves correctly
  - [ ] Comment appears in report comments list

- [ ] **Edit Report Comment**
  - [ ] General can edit own comments
  - [ ] Changes save correctly

#### Activity History Logging
- [ ] **View Activity History**
  - [ ] Activity history displays for projects
  - [ ] Activity history displays for reports
  - [ ] Activities show correct user, role, timestamp
  - [ ] Status changes logged correctly

- [ ] **Activity History Shows Status Changes**
  - [ ] Previous status shows correctly
  - [ ] New status shows correctly
  - [ ] Changed by user shows correctly
  - [ ] Timestamp accurate

- [ ] **Activity History Shows Comments**
  - [ ] Comments appear with action_type='comment'
  - [ ] Comment content shows correctly
  - [ ] Comment author shows correctly

### Edge Case Testing

- [ ] **No Coordinators**
  - [ ] Dashboard handles no coordinators gracefully
  - [ ] Project list shows only direct team projects
  - [ ] Report list shows only direct team reports
  - [ ] No errors occur

- [ ] **No Direct Team Members**
  - [ ] Dashboard handles no direct team gracefully
  - [ ] Project list shows only coordinator hierarchy projects
  - [ ] Report list shows only coordinator hierarchy reports
  - [ ] No errors occur

- [ ] **Large Hierarchies**
  - [ ] System handles deep coordinator hierarchies
  - [ ] All descendant projects included
  - [ ] Performance acceptable (<3 seconds load time)
  - [ ] No memory issues

- [ ] **Permission Boundaries**
  - [ ] General cannot see projects outside their scope
  - [ ] General cannot approve projects they shouldn't see
  - [ ] Cross-hierarchy access prevented

- [ ] **Concurrent Approvals**
  - [ ] Multiple users can view same project
  - [ ] First approval succeeds
  - [ ] Second approval prevented (status already changed)
  - [ ] Appropriate error message shown

- [ ] **Invalid Status Transitions**
  - [ ] Cannot approve already approved project
  - [ ] Cannot revert project in invalid state
  - [ ] Appropriate validation messages shown

### Integration Testing

- [ ] **End-to-End Approval Workflow**
  - [ ] Project submitted → Provincial → Coordinator → Approved
  - [ ] Each step logged in activity history
  - [ ] Notifications sent at each step
  - [ ] Status changes correctly

- [ ] **End-to-End Revert Workflow**
  - [ ] Project approved → Reverted to Provincial → Changes made → Re-submitted
  - [ ] Each step logged
  - [ ] Status transitions correct
  - [ ] Comments visible

- [ ] **Comment Workflow**
  - [ ] Add comment → Edit comment → View in activity history
  - [ ] Comment visible to all relevant users
  - [ ] Comments associated with correct project/report

- [ ] **Activity History Display**
  - [ ] All activities show in chronological order
  - [ ] Activities filterable by type
  - [ ] Activities searchable
  - [ ] Activity details accurate

- [ ] **Dashboard Statistics**
  - [ ] Statistics include coordinator hierarchy data
  - [ ] Statistics include direct team data
  - [ ] Combined totals correct
  - [ ] Breakdown by source shows correctly

---

## Phase 2.2: Aggregated Reports Comprehensive Testing

### Report Generation Testing

- [ ] **Generate Quarterly Report with AI**
  - [ ] Report generates successfully
  - [ ] AI insights generated
  - [ ] AI content editable
  - [ ] Report displays correctly

- [ ] **Generate Quarterly Report without AI**
  - [ ] Report generates successfully
  - [ ] No AI insights generated
  - [ ] Report displays correctly
  - [ ] Manual content can be added

- [ ] **Generate Half-Yearly Report with AI**
  - [ ] Report generates successfully
  - [ ] AI insights generated
  - [ ] AI content editable
  - [ ] Aggregates quarterly data correctly

- [ ] **Generate Annual Report with AI**
  - [ ] Report generates successfully
  - [ ] AI insights generated
  - [ ] AI content editable
  - [ ] Aggregates half-yearly/quarterly data correctly

- [ ] **Generate Reports with Various Project Types**
  - [ ] All 12 project types supported
  - [ ] Data aggregated correctly per project type
  - [ ] No data loss
  - [ ] Calculations accurate

- [ ] **Generate Reports with Different Data Volumes**
  - [ ] Small dataset (<10 projects)
  - [ ] Medium dataset (10-100 projects)
  - [ ] Large dataset (>100 projects)
  - [ ] Performance acceptable

### AI Content Editing

- [ ] **Edit AI-Generated Content**
  - [ ] Can edit executive summary
  - [ ] Can edit key achievements
  - [ ] Can edit progress trends
  - [ ] Can edit challenges
  - [ ] Can edit recommendations
  - [ ] Changes save correctly
  - [ ] Edited flag set correctly

- [ ] **Edit Report Title**
  - [ ] Can edit AI-generated title
  - [ ] Changes save correctly
  - [ ] Title displays in report view

- [ ] **Edit Section Headings**
  - [ ] Can edit section headings
  - [ ] Changes save correctly
  - [ ] Headings display correctly

### Export Testing

- [ ] **Export Quarterly Report as PDF**
  - [ ] PDF generates successfully
  - [ ] PDF formatting correct
  - [ ] All data included
  - [ ] Indian number formatting applied
  - [ ] File downloads correctly

- [ ] **Export Quarterly Report as Word**
  - [ ] Word document generates successfully
  - [ ] Word formatting correct
  - [ ] All data included
  - [ ] Indian number formatting applied
  - [ ] File downloads correctly

- [ ] **Export Half-Yearly Report as PDF**
  - [ ] PDF generates successfully
  - [ ] All sections included
  - [ ] Formatting correct

- [ ] **Export Half-Yearly Report as Word**
  - [ ] Word document generates successfully
  - [ ] Formatting correct

- [ ] **Export Annual Report as PDF**
  - [ ] PDF generates successfully
  - [ ] All sections included
  - [ ] Formatting correct

- [ ] **Export Annual Report as Word**
  - [ ] Word document generates successfully
  - [ ] Formatting correct

- [ ] **Export with Large Reports**
  - [ ] Large reports export successfully
  - [ ] No timeout errors
  - [ ] File size reasonable
  - [ ] Performance acceptable

### Comparison Testing

- [ ] **Compare Two Quarterly Reports**
  - [ ] Comparison form displays available reports
  - [ ] Can select two reports
  - [ ] Comparison executes successfully
  - [ ] Comparison results display correctly
  - [ ] Differences highlighted
  - [ ] Similarities shown

- [ ] **Compare Reports from Different Periods**
  - [ ] Q1 vs Q2 comparison works
  - [ ] Q1 vs Q3 comparison works
  - [ ] Half-yearly vs quarterly comparison (if applicable)
  - [ ] Year-over-year comparison works

- [ ] **Comparison Visualization**
  - [ ] Charts/graphs display correctly
  - [ ] Tables formatted correctly
  - [ ] Percentages calculated correctly
  - [ ] Trends visible

### Permission Testing

- [ ] **Executor/Applicant Permissions**
  - [ ] Can only see own reports
  - [ ] Cannot see other executors' reports
  - [ ] Can generate reports for own projects
  - [ ] Cannot compare reports outside scope

- [ ] **Provincial Permissions**
  - [ ] Can see reports from executors under them
  - [ ] Cannot see reports from other provincials' teams
  - [ ] Can generate aggregated reports for team

- [ ] **Coordinator Permissions**
  - [ ] Can see all reports in hierarchy
  - [ ] Can generate aggregated reports
  - [ ] Can compare any reports

- [ ] **General Permissions**
  - [ ] Can see reports from coordinator hierarchy
  - [ ] Can see reports from direct team
  - [ ] Can generate aggregated reports
  - [ ] Can compare reports

- [ ] **Cross-Hierarchy Access Prevention**
  - [ ] Users cannot access reports outside their scope
  - [ ] Permission checks work correctly
  - [ ] No data leakage
  - [ ] 403 errors shown for unauthorized access

---

## Test Execution Log

### Date: __________
### Tester: __________

#### Phase 2.1 Tests Executed
- Total Test Scenarios: ___ / ___
- Passed: ___
- Failed: ___
- Blocked: ___

#### Phase 2.2 Tests Executed
- Total Test Scenarios: ___ / ___
- Passed: ___
- Failed: ___
- Blocked: ___

### Issues Found
1. ______________________________
2. ______________________________
3. ______________________________

### Notes
______________________________

---

**Document Version:** 1.0  
**Created:** January 2025  
**Status:** Testing Checklist Ready
