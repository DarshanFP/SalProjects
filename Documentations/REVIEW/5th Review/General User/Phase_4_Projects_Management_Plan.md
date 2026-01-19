# Phase 4: Projects Management for General User - Implementation Plan

**Date:** January 2025  
**Status:** Planning  
**Phase:** 4 of General User Role Implementation

---

## Overview

Phase 4 implements comprehensive Projects Management for the General user, addressing the dual-role nature (Coordinator + Provincial) and providing additional statuses and actions for better workflow control.

---

## Key Design Decisions

### 1. Dual Approval Context

**Problem:** General user has complete Coordinator access AND acts as Provincial. When General approves a project/report, should it be recorded as:
- Option A: Coordinator approval (since General has coordinator access)
- Option B: Provincial approval (since General also acts as Provincial)
- Option C: Give General BOTH options to choose from (recommended)

**Solution: Option C - Dual Context Selection**

General user will have **BOTH options** when approving/reverting:
- **"Approve as Coordinator"** → Records `approved_by_general_as_coordinator` or uses existing `approved_by_coordinator` (if General has coordinator access)
- **"Approve as Provincial"** → Records `approved_by_general_as_provincial` or uses existing workflow

This maintains full access and provides flexibility for General to choose the appropriate context.

**Implementation:**
- Add approval context selection in UI (radio buttons: "As Coordinator" / "As Provincial")
- Store context in activity history (`notes` field or new `approval_context` field)
- Use appropriate status based on context selected

### 2. Additional Statuses Needed

#### Project Statuses (to be added to `ProjectStatus.php`):

```php
// General acting as Coordinator
const APPROVED_BY_GENERAL_AS_COORDINATOR = 'approved_by_general_as_coordinator';
const REVERTED_BY_GENERAL_AS_COORDINATOR = 'reverted_by_general_as_coordinator';

// General acting as Provincial
const APPROVED_BY_GENERAL_AS_PROVINCIAL = 'approved_by_general_as_provincial';
const REVERTED_BY_GENERAL_AS_PROVINCIAL = 'reverted_by_general_as_provincial';

// Revert to different levels (new granular revert statuses)
const REVERTED_TO_EXECUTOR = 'reverted_to_executor';
const REVERTED_TO_APPLICANT = 'reverted_to_applicant';
const REVERTED_TO_PROVINCIAL = 'reverted_to_provincial';
const REVERTED_TO_COORDINATOR = 'reverted_to_coordinator';
```

**Note:** Some of these may overlap with existing statuses. We need to determine if we should:
- Use existing statuses with context in notes (`reverted_by_provincial` with notes indicating "General acting as Provincial")
- OR create new statuses for better tracking

**Recommendation:** Use existing statuses for standard workflow, but log context in activity history. Create new statuses only for granular revert levels if needed.

#### Report Statuses:
Same as Project Statuses (parallel structure)

### 3. Comment Functionality

**Requirement:** General user should be able to comment on projects/reports without changing status.

**Implementation:**
- Comments are logged in `activity_histories` table with:
  - `type`: 'project' or 'report'
  - `related_id`: project_id or report_id
  - `previous_status`: Current status (unchanged)
  - `new_status`: Current status (unchanged)
  - `notes`: Comment text
  - `action_type`: 'comment' (new field or use notes prefix)

**Alternative:** Create separate `project_comments` / `report_comments` tables if comments need to be more structured.

**Recommendation:** Use activity_history with action_type indicator for now. Can migrate to separate tables later if needed.

### 4. Revert to Specific Levels

**Requirement:** General should be able to revert projects/reports to:
- Executor (direct team member)
- Applicant (direct team member)
- Provincial (coordinator hierarchy)
- Coordinator (coordinator hierarchy)

**Implementation:**
- Add `reverted_to_user_id` field to activity_history (optional, nullable)
- Add `revert_level` field to activity_history ('executor', 'applicant', 'provincial', 'coordinator')
- UI: Dropdown to select target level when reverting
- Status determination:
  - Revert to Executor → `REVERTED_TO_EXECUTOR` or `REVERTED_BY_PROVINCIAL` (if from provincial context)
  - Revert to Applicant → `REVERTED_TO_APPLICANT` or `REVERTED_BY_PROVINCIAL` (if from provincial context)
  - Revert to Provincial → `REVERTED_TO_PROVINCIAL` or `REVERTED_BY_COORDINATOR` (if from coordinator context)
  - Revert to Coordinator → `REVERTED_TO_COORDINATOR` or `REVERTED_BY_COORDINATOR` (if from coordinator context)

### 5. Draft/Save/Update Tracking

**Requirement:** Track when executor/applicant:
- Saves draft (status remains `draft`, log activity)
- Submits (status changes to `submitted_to_provincial`, log activity)
- Updates (status unchanged, log activity)

**Implementation:**
- Add `action_type` field to `activity_histories` table:
  - `status_change` (default, existing behavior)
  - `draft_save` (executor saves draft)
  - `submit` (executor submits)
  - `update` (executor updates project/report)
  - `comment` (comment added without status change)

- Update `ActivityHistoryService`:
  - `logDraftSave()` - Log when draft is saved
  - `logSubmit()` - Log when project/report is submitted
  - `logUpdate()` - Log when project/report is updated (status unchanged)
  - `logComment()` - Log when comment is added

**Database Migration:**
```php
Schema::table('activity_histories', function (Blueprint $table) {
    $table->enum('action_type', ['status_change', 'draft_save', 'submit', 'update', 'comment'])
          ->default('status_change')
          ->after('new_status');
    $table->unsignedBigInteger('reverted_to_user_id')->nullable()->after('changed_by_user_id');
    $table->string('revert_level', 50)->nullable()->after('reverted_to_user_id');
    $table->string('approval_context', 50)->nullable()->after('notes'); // 'coordinator', 'provincial', 'general'
});
```

---

## Implementation Phases

### Phase 4.1: Database Schema Updates
- [ ] Add new status constants to `ProjectStatus.php`
- [ ] Add new status constants to Report status classes
- [ ] Create migration to add `action_type`, `reverted_to_user_id`, `revert_level`, `approval_context` to `activity_histories`

### Phase 4.2: Service Layer Updates
- [ ] Update `ProjectStatusService` to support General dual-role approval/revert
- [ ] Update `ReportStatusService` to support General dual-role approval/revert
- [ ] Update `ActivityHistoryService` to log draft saves, updates, comments
- [ ] Add methods for General-specific actions:
  - `approveAsCoordinator()` / `approveAsProvincial()`
  - `revertToLevel()` with level selection
  - `addComment()` without status change

### Phase 4.3: Controller Updates
- [ ] Add General-specific project management methods to `GeneralController`:
  - `listProjects()` - Combined list (coordinator hierarchy + direct team)
  - `approveProject()` - With context selection
  - `revertProject()` - With level selection
  - `commentProject()` - Comment without status change
  - `viewProjectDetails()` - View project details

### Phase 4.4: Views
- [ ] Create `general/projects/index.blade.php` - Combined project list
- [ ] Create `general/projects/approve.blade.php` - Approval form with context selection
- [ ] Create `general/projects/revert.blade.php` - Revert form with level selection
- [ ] Create `general/projects/comment.blade.php` - Comment form (modal)
- [ ] Create `general/projects/show.blade.php` - Project details view

### Phase 4.5: Routes
- [ ] Add General-specific project routes:
  - `/general/projects` - List projects
  - `/general/project/{id}/approve` - Approve project (with context)
  - `/general/project/{id}/revert` - Revert project (with level)
  - `/general/project/{id}/comment` - Add comment
  - `/general/project/{id}` - View project details

### Phase 4.6: Reports (Parallel Implementation)
- [ ] Repeat Phase 4.2-4.5 for Reports (same structure)

---

## Status Flow Diagram (Updated)

### Projects:

```
draft (executor/applicant)
  ↓ (Save Draft) - Logged in activity history
draft (still)
  ↓ (Submit)
submitted_to_provincial
  ↓ (Provincial forwards) OR ↓ (Provincial/General as Provincial reverts)
forwarded_to_coordinator    reverted_by_provincial / reverted_by_general_as_provincial
  ↓ (Coordinator/General as Coordinator approves) OR ↓ (Coordinator/General as Coordinator reverts)
approved_by_coordinator /   reverted_by_coordinator / reverted_by_general_as_coordinator
approved_by_general_as_coordinator
```

### General User Actions:

**As Coordinator:**
- Approve → `approved_by_coordinator` or `approved_by_general_as_coordinator`
- Revert → `reverted_by_coordinator` or `reverted_by_general_as_coordinator` (with revert_level: 'provincial')

**As Provincial:**
- Forward → `forwarded_to_coordinator` (same as provincial)
- Revert → `reverted_by_provincial` or `reverted_by_general_as_provincial` (with revert_level: 'executor' or 'applicant')

**Comment:**
- No status change, logged in activity_history with action_type='comment'

---

## Files to Modify/Create

### Modify:
1. `app/Constants/ProjectStatus.php` - Add new status constants
2. `app/Models/Reports/Monthly/DPReport.php` - Add status constants (if not using ProjectStatus)
3. `app/Services/ProjectStatusService.php` - Add General methods
4. `app/Services/ReportStatusService.php` - Add General methods
5. `app/Services/ActivityHistoryService.php` - Add draft/update/comment logging
6. `app/Http/Controllers/GeneralController.php` - Add project management methods
7. `routes/web.php` - Add General project routes
8. `database/migrations/YYYY_MM_DD_HHMMSS_add_action_type_to_activity_histories.php` - New migration

### Create:
1. `resources/views/general/projects/index.blade.php` - Project list
2. `resources/views/general/projects/approve.blade.php` - Approval form
3. `resources/views/general/projects/revert.blade.php` - Revert form
4. `resources/views/general/projects/comment.blade.php` - Comment modal
5. `resources/views/general/projects/show.blade.php` - Project details
6. `database/migrations/YYYY_MM_DD_HHMMSS_add_action_type_to_activity_histories.php` - Migration

---

## Testing Checklist

- [ ] General can view projects from coordinator hierarchy
- [ ] General can view projects from direct team
- [ ] General can approve project as Coordinator
- [ ] General can approve project as Provincial
- [ ] General can revert project to different levels
- [ ] General can comment on project without changing status
- [ ] Activity history logs all actions correctly
- [ ] Draft saves are logged in activity history
- [ ] Updates are logged in activity history
- [ ] Comments are logged in activity history
- [ ] Approval context is stored correctly
- [ ] Revert level is stored correctly

---

## Questions for Clarification

1. **Status Naming:** Should we use `approved_by_general_as_coordinator` or just use `approved_by_coordinator` with context in activity history? (Recommendation: Use existing statuses with context in activity_history)

2. **Revert Status:** Should we create granular statuses like `reverted_to_executor` or use existing `reverted_by_provincial` with `revert_level` field? (Recommendation: Use existing with `revert_level` field)

3. **Comments Table:** Should comments be in `activity_histories` or separate `project_comments`/`report_comments` tables? (Recommendation: Start with activity_histories, can migrate later)

4. **UI Design:** Should approval/revert context selection be:
   - Radio buttons in approval/revert form (recommended)
   - Separate routes (`/general/project/{id}/approve-as-coordinator` vs `/approve-as-provincial`)
   - Toggle switch in project list view

---

## Next Steps

1. Review and approve this plan
2. Clarify questions above
3. Start implementation with Phase 4.1 (Database Schema Updates)
