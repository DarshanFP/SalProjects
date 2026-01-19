# Phase 4: Projects Management Implementation - Progress Summary

**Date:** January 2025  
**Status:** Backend Complete, Views Pending  
**Phase:** 4 of General User Role Implementation

---

## ✅ Completed Tasks

### Phase 4.1: New Project Statuses ✅
- Added `APPROVED_BY_GENERAL_AS_COORDINATOR`
- Added `REVERTED_BY_GENERAL_AS_COORDINATOR`
- Added `APPROVED_BY_GENERAL_AS_PROVINCIAL`
- Added `REVERTED_BY_GENERAL_AS_PROVINCIAL`
- Added `REVERTED_TO_EXECUTOR`
- Added `REVERTED_TO_APPLICANT`
- Added `REVERTED_TO_PROVINCIAL`
- Added `REVERTED_TO_COORDINATOR`
- Updated `getEditableStatuses()` and `getSubmittableStatuses()`
- Added status labels to `Project::$statusLabels`

### Phase 4.2: New Report Statuses ✅
- Added same status constants to `DPReport`, `QuarterlyReport`, `HalfYearlyReport`, `AnnualReport`
- Updated `isEditable()` methods in all report models
- Updated `getStatusBadgeClass()` methods
- Added status labels to all report models

### Phase 4.3: ProjectStatusService Updates ✅
- Updated `forwardToCoordinator()` to allow 'general' role
- Updated `approve()` to use `APPROVED_BY_GENERAL_AS_COORDINATOR` for General users
- Updated `revertByProvincial()` with granular revert levels
- Updated `revertByCoordinator()` with granular revert levels
- Added `approveAsCoordinator()` method
- Added `approveAsProvincial()` method
- Added `revertAsCoordinator()` method
- Added `revertAsProvincial()` method
- Added `revertToLevel()` method with level validation
- Updated `logStatusChange()` to support new fields (action_type, approval_context, revert_level, reverted_to_user_id)
- Updated `canTransition()` to include General user transitions

### Phase 4.4: ReportStatusService Updates ✅
- Updated `forwardToCoordinator()` to allow 'general' role
- Updated `approve()` to use `APPROVED_BY_GENERAL_AS_COORDINATOR` for General users
- Updated `revertByProvincial()` with granular revert levels
- Updated `revertByCoordinator()` with granular revert levels
- Updated `reject()` to allow 'general' role
- Added `approveAsCoordinator()` method
- Added `approveAsProvincial()` method
- Added `revertAsCoordinator()` method
- Added `revertAsProvincial()` method
- Added `revertToLevel()` method with level validation
- Updated `logStatusChange()` to support new fields

### Phase 4.5: Comment Functionality ✅
- Added `addProjectComment()` method to GeneralController
- Added `editProjectComment()` method to GeneralController
- Added `updateProjectComment()` method to GeneralController
- Added `addReportComment()` method to GeneralController
- Added `editReportComment()` method to GeneralController
- Added `updateReportComment()` method to GeneralController
- Comments are logged in both `ProjectComment`/`ReportComment` models AND `activity_histories` table
- Uses `ActivityHistoryService::logProjectComment()` and `logReportComment()` methods

### Phase 4.6: Revert-to-Level Functionality ✅
- Added `approveProject()` method with dual-role context selection
- Added `revertProject()` method with context and level selection
- Added `revertProjectToLevel()` method for granular reverts
- Added `approveReport()` method with dual-role context selection
- Added `revertReport()` method with context and level selection
- Added `revertReportToLevel()` method for granular reverts
- All methods use appropriate service layer methods
- Budget validation included for coordinator approval (commencement date required)

### Phase 4.7: ActivityHistoryService Updates ✅
- Updated `logProjectUpdate()` to include `action_type='update'`
- Added `logProjectDraftSave()` method
- Added `logProjectSubmit()` method
- Added `logProjectComment()` method
- Updated `logReportCreate()` to include `action_type='status_change'`
- Updated `logReportUpdate()` to include `action_type`
- Added `logReportDraftSave()` method
- Added `logReportSubmit()` method
- Added `logReportComment()` method
- All methods log to `activity_histories` table with appropriate `action_type`

### Phase 4.10: Routes ✅
- Added `/general/projects` - List projects
- Added `/general/project/{project_id}/approve` - Approve project (with context)
- Added `/general/project/{project_id}/revert` - Revert project (with context/level)
- Added `/general/project/{project_id}/revert-to-level` - Revert to specific level
- Added `/general/project/{project_id}/comment` - Add project comment
- Added `/general/project-comment/{id}/edit` - Edit project comment
- Added `/general/project-comment/{id}/update` - Update project comment
- Added `/general/project/{project_id}` - View project details
- Added `/general/reports` - List reports
- Added `/general/report/{report_id}/approve` - Approve report (with context)
- Added `/general/report/{report_id}/revert` - Revert report (with context/level)
- Added `/general/report/{report_id}/revert-to-level` - Revert to specific level
- Added `/general/report/{report_id}/comment` - Add report comment
- Added `/general/report-comment/{id}/edit` - Edit report comment
- Added `/general/report-comment/{id}/update` - Update report comment
- Added `/general/report/{report_id}` - View report details

### Database Migration ✅
- Created migration: `2026_01_10_152504_add_general_user_fields_to_activity_histories_table.php`
- Added `action_type` enum field (status_change, draft_save, submit, update, comment)
- Added `approval_context` string field (coordinator, provincial, general)
- Added `revert_level` string field (executor, applicant, provincial, coordinator)
- Added `reverted_to_user_id` foreign key field
- Migration tested (--pretend) - looks good

### Model Updates ✅
- Updated `ActivityHistory` model with new fillable fields
- Added `revertedTo()` relationship method
- Updated badge classes to include new statuses

### Controller Methods ✅
- Added `listProjects()` method - Combined project list (coordinator hierarchy + direct team)
- Added `listReports()` method - Combined report list (coordinator hierarchy + direct team)
- Added `showProject()` method - View project details
- Added `showReport()` method - View report details
- All approval/revert methods with dual-role context selection
- All comment methods implemented

---

## ⏳ Pending Tasks

### Phase 4.8: Combined Project List View (In Progress)
- [ ] Create `resources/views/general/projects/index.blade.php`
  - Combined list showing coordinator hierarchy + direct team
  - Filters: coordinator_id, province, center, project_type, status, search
  - Source indicator (coordinator_hierarchy vs direct_team)
  - Action buttons: Approve (with context selection), Revert (with context/level), Comment, View
  - Pagination support
  - Status badges for new General statuses

### Phase 4.9: Approval/Revert Views with Dual-Role Selection (Pending)
- [ ] Create `resources/views/general/projects/approve.blade.php`
  - Radio buttons: "Approve as Coordinator" / "Approve as Provincial"
  - If Coordinator: Show commencement_month/year fields (required)
  - Budget validation display
  - Form submits to `general.approveProject` with `approval_context` parameter
  
- [ ] Create `resources/views/general/projects/revert.blade.php`
  - Radio buttons: "Revert as Coordinator" / "Revert as Provincial"
  - Revert level dropdown: executor, applicant, provincial, coordinator (based on context)
  - Reverted to user selection (optional, filtered by level)
  - Reason textarea (required)
  - Form submits to `general.revertProject` or `general.revertProjectToLevel`
  
- [ ] Create `resources/views/general/reports/approve.blade.php`
  - Similar to project approval but no commencement date needed
  
- [ ] Create `resources/views/general/reports/revert.blade.php`
  - Similar to project revert

- [ ] Create comment modal/partial views
  - Reuse existing comment views from coordinator/provincial if possible
  - Ensure comments are displayed with General user name

---

## 📋 Implementation Details

### Approval Context Selection

**Projects:**
- **As Coordinator**: Requires commencement_month/year, budget validation, calculates amount_sanctioned/opening_balance
- **As Provincial**: Forwards to coordinator (no commencement date needed)

**Reports:**
- **As Coordinator**: Approves with `APPROVED_BY_GENERAL_AS_COORDINATOR` status
- **As Provincial**: Forwards to coordinator with `FORWARDED_TO_COORDINATOR` status

### Revert Level Logic

**For Provincial Context:**
- Can revert to: executor, applicant, provincial
- Statuses: `REVERTED_TO_EXECUTOR`, `REVERTED_TO_APPLICANT`, `REVERTED_TO_PROVINCIAL`

**For Coordinator Context:**
- Can revert to: provincial, coordinator
- Statuses: `REVERTED_TO_PROVINCIAL`, `REVERTED_TO_COORDINATOR`

**Granular Revert (revertToLevel):**
- Allows General to revert directly to any level regardless of current status (with validation)
- Provides maximum flexibility for General user

### Activity History Tracking

**Action Types:**
- `status_change` - Default for status transitions (existing behavior)
- `draft_save` - When executor saves draft (status unchanged)
- `submit` - When executor submits project/report
- `update` - When project/report is updated without status change
- `comment` - When comment is added (status unchanged)

**All logged with:**
- `approval_context` - 'coordinator', 'provincial', or 'general' (null for non-General actions)
- `revert_level` - 'executor', 'applicant', 'provincial', 'coordinator' (for granular reverts)
- `reverted_to_user_id` - Optional user ID for targeted reverts

---

## 🎯 Next Steps

1. **Phase 4.8**: Create `general/projects/index.blade.php` view (combined project list)
2. **Phase 4.9**: Create approval/revert views with dual-role selection
3. **Phase 4.9**: Create `general/reports/index.blade.php` view (combined report list)
4. **Testing**: Test all approval/revert flows with different contexts
5. **Documentation**: Update implementation plan with final status

---

## 🔍 Notes

- **Linter Warnings**: IDE shows warnings about ProjectStatusService/ReportStatusService, but these are false positives. Imports are correct (`App\Services\*`), syntax is valid, and routes are registered.
- **Budget Validation**: Only applies when General approves as Coordinator (requires commencement date)
- **Comment Logging**: Comments are logged in both comment tables AND activity_history for complete audit trail
- **Draft/Update Tracking**: ActivityHistoryService methods ready for use when executor/applicant controllers are updated to call them

---

## ✅ Files Modified

1. `app/Constants/ProjectStatus.php` - Added new status constants
2. `app/Models/OldProjects/Project.php` - Added status labels
3. `app/Models/Reports/Monthly/DPReport.php` - Added status constants and labels
4. `app/Models/Reports/Quarterly/QuarterlyReport.php` - Added status constants and labels
5. `app/Models/Reports/HalfYearly/HalfYearlyReport.php` - Added status constants and labels
6. `app/Models/Reports/Annual/AnnualReport.php` - Added status constants and labels
7. `app/Models/ActivityHistory.php` - Added fillable fields and relationships
8. `app/Services/ProjectStatusService.php` - Added General dual-role methods
9. `app/Services/ReportStatusService.php` - Added General dual-role methods
10. `app/Services/ActivityHistoryService.php` - Added draft/update/comment logging methods
11. `app/Http/Controllers/GeneralController.php` - Added all project/report management methods
12. `routes/web.php` - Added General-specific routes
13. `database/migrations/2026_01_10_152504_add_general_user_fields_to_activity_histories_table.php` - Created

---

## 📝 Remaining Work

- [ ] Create views (Phase 4.8-4.9)
- [ ] Test all flows
- [ ] Update executor/applicant controllers to use ActivityHistoryService draft/update methods
- [ ] Update project/report controllers to track draft saves and updates
