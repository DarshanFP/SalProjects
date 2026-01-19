# Activity Report - Requirements Summary & Phase-Wise Implementation Plan

**Date:** January 2025  
**Status:** 📋 **PLANNING**  
**Purpose:** Comprehensive activity/status history tracking system for projects and reports with role-based access control

---

## Executive Summary

This document outlines the requirements and implementation plan for a comprehensive **Activity Report** system that tracks all status changes for both **Projects** and **Reports** with role-based access control. The system will provide:

1. **Unified Status History** - Track both project and report status changes
2. **Role-Based Views** - Different access levels for Executor/Applicant, Provincial, and Coordinator
3. **Hierarchical Access** - Provincial sees their executors/applicants, Coordinator sees all
4. **Complete Audit Trail** - Who changed what, when, and why

---

## Table of Contents

1. [Current State Analysis](#current-state-analysis)
2. [Requirements Summary](#requirements-summary)
3. [Current Statuses](#current-statuses)
4. [User Hierarchy & Access Requirements](#user-hierarchy--access-requirements)
5. [Database Design](#database-design)
6. [Phase-Wise Implementation Plan](#phase-wise-implementation-plan)
7. [Technical Specifications](#technical-specifications)
8. [Testing Strategy](#testing-strategy)

---

## Current State Analysis

### ✅ What Exists

1. **Project Status History**

    - Table: `project_status_histories`
    - Model: `ProjectStatusHistory`
    - Service: `ProjectStatusService::logStatusChange()`
    - UI: Embedded in project show page
    - **Status:** ✅ Fully implemented

2. **Report Status Tracking**

    - Reports have status field
    - Status changes happen but **NOT logged**
    - **Status:** ❌ No history tracking

3. **User Hierarchy**
    - `users` table has `parent_id` field
    - Provincial → Executor/Applicant relationship exists
    - Coordinator can see all users
    - **Status:** ✅ Implemented

### ❌ What's Missing

1. **Report Status History**

    - No `report_status_histories` table
    - No logging of report status changes
    - No UI for report status history

2. **Unified Activity View**

    - No dedicated route/view for activity history
    - No sidebar links for accessing activity reports
    - Status history only visible on individual project pages

3. **Role-Based Activity Views**
    - No filtered views by user role
    - No hierarchical access (provincial seeing their executors)
    - No coordinator view of all activities

---

## Requirements Summary

### Functional Requirements

#### FR1: Unified Status History System

-   **Requirement:** Track status changes for both Projects and Reports
-   **Options:**
    -   **Option A:** Single table with `type` column (`project` or `report`)
    -   **Option B:** Separate tables (`project_status_histories` + `report_status_histories`)
-   **Recommendation:** **Option A** - Single unified table for easier querying and reporting

#### FR2: Role-Based Access Control

**Executor/Applicant Users:**

-   See status changes for:
    -   Projects they own (`user_id` matches)
    -   Projects where they are in-charge (`in_charge` matches)
    -   Reports for projects they own or are in-charge of
-   **Access Level:** Own activities only

**Provincial Users:**

-   See status changes for:
    -   All executors/applicants under them (`parent_id` = provincial.id)
    -   All projects owned by their executors/applicants
    -   All projects where their executors/applicants are in-charge
    -   All reports for those projects
-   **Access Level:** Their team's activities

**Coordinator Users:**

-   See status changes for:
    -   All provincials
    -   All executors/applicants (directly or via provincials)
    -   All projects in the system
    -   All reports in the system
-   **Access Level:** System-wide activities

#### FR3: Activity Report Views

**Views Required:**

1. **My Activities** (Executor/Applicant)

    - Route: `/activities/my-activities`
    - Shows: Own projects and reports status changes

2. **Team Activities** (Provincial)

    - Route: `/activities/team-activities`
    - Shows: All executors/applicants under them

3. **All Activities** (Coordinator)

    - Route: `/activities/all-activities`
    - Shows: All activities in the system

4. **Project Activity History** (All roles)

    - Route: `/projects/{id}/activity-history`
    - Shows: Status history for specific project

5. **Report Activity History** (All roles)
    - Route: `/reports/{id}/activity-history`
    - Shows: Status history for specific report

#### FR4: Sidebar Links

**For Executor/Applicant:**

-   "My Activities" link in sidebar

**For Provincial:**

-   "Team Activities" link in sidebar

**For Coordinator:**

-   "All Activities" link in sidebar

---

## Current Statuses

### Project Statuses

**Source:** `app/Constants/ProjectStatus.php`

| Status                     | Constant                   | Description                        |
| -------------------------- | -------------------------- | ---------------------------------- |
| `draft`                    | `DRAFT`                    | Draft (Executor still working)     |
| `submitted_to_provincial`  | `SUBMITTED_TO_PROVINCIAL`  | Executor submitted to Provincial   |
| `reverted_by_provincial`   | `REVERTED_BY_PROVINCIAL`   | Returned by Provincial for changes |
| `forwarded_to_coordinator` | `FORWARDED_TO_COORDINATOR` | Provincial sent to Coordinator     |
| `reverted_by_coordinator`  | `REVERTED_BY_COORDINATOR`  | Coordinator sent back for changes  |
| `approved_by_coordinator`  | `APPROVED_BY_COORDINATOR`  | Approved by Coordinator            |
| `rejected_by_coordinator`  | `REJECTED_BY_COORDINATOR`  | Rejected by Coordinator            |

**Total:** 7 statuses

### Report Statuses

**Source:** `app/Models/Reports/Monthly/DPReport.php`

| Status                     | Constant                          | Description                        |
| -------------------------- | --------------------------------- | ---------------------------------- |
| `draft`                    | `STATUS_DRAFT`                    | Draft (Executor still working)     |
| `submitted_to_provincial`  | `STATUS_SUBMITTED_TO_PROVINCIAL`  | Executor submitted to Provincial   |
| `reverted_by_provincial`   | `STATUS_REVERTED_BY_PROVINCIAL`   | Returned by Provincial for changes |
| `forwarded_to_coordinator` | `STATUS_FORWARDED_TO_COORDINATOR` | Provincial sent to Coordinator     |
| `reverted_by_coordinator`  | `STATUS_REVERTED_BY_COORDINATOR`  | Coordinator sent back for changes  |
| `approved_by_coordinator`  | `STATUS_APPROVED_BY_COORDINATOR`  | Approved by Coordinator            |
| `rejected_by_coordinator`  | `STATUS_REJECTED_BY_COORDINATOR`  | Rejected by Coordinator            |

**Total:** 7 statuses (same as projects)

### Status Flow

**Projects:**

```
draft
  ↓ (Executor/Applicant submits)
submitted_to_provincial
  ↓ (Provincial forwards) OR ↓ (Provincial reverts)
forwarded_to_coordinator    reverted_by_provincial
  ↓ (Coordinator approves) OR ↓ (Coordinator reverts) OR ↓ (Coordinator rejects)
approved_by_coordinator     reverted_by_coordinator     rejected_by_coordinator
```

**Reports:**

```
draft
  ↓ (Executor/Applicant submits)
submitted_to_provincial
  ↓ (Provincial forwards) OR ↓ (Provincial reverts)
forwarded_to_coordinator    reverted_by_provincial
  ↓ (Coordinator approves) OR ↓ (Coordinator reverts) OR ↓ (Coordinator rejects)
approved_by_coordinator     reverted_by_coordinator     rejected_by_coordinator
```

### Status Analysis

**Current Statuses are Sufficient:**

-   ✅ All necessary workflow states are covered
-   ✅ Both projects and reports use same status flow
-   ✅ Revert reasons are tracked (via `notes` field)

**No Additional Statuses Required:**

-   Current 7 statuses cover all workflow scenarios
-   Status flow is clear and logical
-   No gaps identified in workflow

---

## User Hierarchy & Access Requirements

### User Hierarchy Structure

```
Coordinator (Top Level)
  ├── Provincial 1
  │   ├── Executor 1
  │   ├── Executor 2
  │   ├── Applicant 1
  │   └── Applicant 2
  ├── Provincial 2
  │   ├── Executor 3
  │   └── Applicant 3
  └── ...
```

**Database Structure:**

-   `users.parent_id` → Links executor/applicant to provincial
-   Provincial's `parent_id` is typically null or coordinator
-   Coordinator has no parent

### Access Requirements by Role

#### Executor/Applicant Access

**Projects:**

-   Own projects: `project.user_id = user.id`
-   In-charge projects: `project.in_charge = user.id`

**Reports:**

-   Reports for own projects: `report.project_id IN (projects where user_id = user.id)`
-   Reports for in-charge projects: `report.project_id IN (projects where in_charge = user.id)`

**Activity History:**

-   See status changes for:
    -   Projects they own or are in-charge of
    -   Reports for those projects

**Query Pattern:**

```php
// Get project IDs where user is owner or in-charge
$projectIds = Project::where(function($query) use ($user) {
    $query->where('user_id', $user->id)
          ->orWhere('in_charge', $user->id);
})->pluck('project_id');

// Get activity history for those projects
$activities = ActivityHistory::where('type', 'project')
    ->whereIn('related_id', $projectIds)
    ->get();

// Get activity history for reports of those projects
$reportActivities = ActivityHistory::where('type', 'report')
    ->whereHas('report', function($query) use ($projectIds) {
        $query->whereIn('project_id', $projectIds);
    })
    ->get();
```

#### Provincial Access

**Users Under Them:**

-   Executors: `User::where('parent_id', $provincial->id)->where('role', 'executor')`
-   Applicants: `User::where('parent_id', $provincial->id)->where('role', 'applicant')`

**Projects:**

-   Projects owned by their executors/applicants
-   Projects where their executors/applicants are in-charge

**Reports:**

-   Reports for projects owned by their executors/applicants
-   Reports for projects where their executors/applicants are in-charge

**Activity History:**

-   See status changes for:
    -   All projects owned by their executors/applicants
    -   All projects where their executors/applicants are in-charge
    -   All reports for those projects

**Query Pattern:**

```php
// Get executor/applicant IDs under this provincial
$teamUserIds = User::where('parent_id', $provincial->id)
    ->whereIn('role', ['executor', 'applicant'])
    ->pluck('id');

// Get project IDs for those users
$projectIds = Project::where(function($query) use ($teamUserIds) {
    $query->whereIn('user_id', $teamUserIds)
          ->orWhereIn('in_charge', $teamUserIds);
})->pluck('project_id');

// Get activity history
$activities = ActivityHistory::whereIn('related_id', $projectIds)
    ->orWhereHas('report', function($query) use ($projectIds) {
        $query->whereIn('project_id', $projectIds);
    })
    ->get();
```

#### Coordinator Access

**All Users:**

-   Can see all provincials, executors, and applicants
-   No filtering by parent_id

**All Projects:**

-   Can see all projects in the system

**All Reports:**

-   Can see all reports in the system

**Activity History:**

-   See status changes for:
    -   All projects
    -   All reports

**Query Pattern:**

```php
// No filtering needed - see everything
$activities = ActivityHistory::all();
```

---

## Database Design

### Option A: Unified Activity History Table (Recommended)

**Table Name:** `activity_histories`

**Schema:**

```sql
CREATE TABLE activity_histories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('project', 'report') NOT NULL,
    related_id VARCHAR(255) NOT NULL, -- project_id or report_id
    previous_status VARCHAR(255) NULL,
    new_status VARCHAR(255) NOT NULL,
    changed_by_user_id BIGINT UNSIGNED NOT NULL,
    changed_by_user_role VARCHAR(50) NOT NULL,
    changed_by_user_name VARCHAR(255) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_type_related (type, related_id),
    INDEX idx_changed_by (changed_by_user_id),
    INDEX idx_new_status (new_status),
    INDEX idx_created_at (created_at),

    FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Advantages:**

-   ✅ Single table for both projects and reports
-   ✅ Easier to query unified activity feed
-   ✅ Simpler codebase (one model, one service)
-   ✅ Better for reporting and analytics

**Disadvantages:**

-   ⚠️ `related_id` is polymorphic (can be project_id or report_id)
-   ⚠️ Need to handle different foreign key relationships

### Option B: Separate Tables

**Tables:**

1. `project_status_histories` (already exists)
2. `report_status_histories` (new)

**Advantages:**

-   ✅ Clear separation of concerns
-   ✅ Type-safe foreign keys
-   ✅ Easier to understand

**Disadvantages:**

-   ❌ Duplicate code (two models, two services)
-   ❌ Harder to create unified activity feed
-   ❌ More complex queries for combined views

### Recommendation: **Option A - Unified Table**

**Rationale:**

-   Simpler implementation
-   Better for unified activity views
-   Easier to maintain
-   Can use polymorphic relationships in Laravel

---

## Phase-Wise Implementation Plan

### Phase 1: Database & Model Setup (4 hours)

**Duration:** 4 hours  
**Priority:** 🔴 **HIGH**

#### Task 1.1: Create Unified Activity History Table

-   Create migration: `create_activity_histories_table.php`
-   Add indexes for performance
-   Add foreign keys
-   **Estimated Time:** 1 hour

#### Task 1.2: Migrate Existing Project Status History

-   Create migration to copy data from `project_status_histories` to `activity_histories`
-   Set `type = 'project'` for all existing records
-   **Estimated Time:** 1 hour

#### Task 1.3: Create ActivityHistory Model

-   Create `app/Models/ActivityHistory.php`
-   Add polymorphic relationships
-   Add accessors for status labels
-   **Estimated Time:** 1 hour

#### Task 1.4: Update ProjectStatusService

-   Update `logStatusChange()` to use `ActivityHistory` instead of `ProjectStatusHistory`
-   Maintain backward compatibility during transition
-   **Estimated Time:** 1 hour

**Deliverables:**

-   ✅ Migration file created
-   ✅ Data migration completed
-   ✅ Model created
-   ✅ Service updated

---

### Phase 2: Report Status History Integration (6 hours)

**Duration:** 6 hours  
**Priority:** 🔴 **HIGH**

#### Task 2.1: Create ReportStatusService

-   Create `app/Services/ReportStatusService.php`
-   Add `logStatusChange()` method (similar to ProjectStatusService)
-   **Estimated Time:** 1 hour

#### Task 2.2: Update Report Controllers

-   Update `ReportController::submit()` to log status change
-   Update `ReportController::forward()` to log status change
-   Update `ReportController::approve()` to log status change
-   Update `ReportController::revert()` to log status change
-   **Estimated Time:** 2 hours

#### Task 2.3: Update Provincial Report Controllers

-   Update provincial report forwarding/reverting methods
-   **Estimated Time:** 1 hour

#### Task 2.4: Update Coordinator Report Controllers

-   Update coordinator report approval/rejection/revert methods
-   **Estimated Time:** 1 hour

#### Task 2.5: Update Aggregated Report Controllers

-   Update quarterly, half-yearly, annual report controllers
-   **Estimated Time:** 1 hour

**Deliverables:**

-   ✅ ReportStatusService created
-   ✅ All report status changes are logged
-   ✅ Status history available for reports

---

### Phase 3: Activity History Service & Helpers (4 hours)

**Duration:** 4 hours  
**Priority:** 🔴 **HIGH**

#### Task 3.1: Create ActivityHistoryService

-   Create `app/Services/ActivityHistoryService.php`
-   Add methods:
    -   `getForExecutor($user)` - Get activities for executor/applicant
    -   `getForProvincial($user)` - Get activities for provincial
    -   `getForCoordinator()` - Get all activities
    -   `getForProject($projectId)` - Get activities for specific project
    -   `getForReport($reportId)` - Get activities for specific report
-   **Estimated Time:** 3 hours

#### Task 3.2: Create ActivityHistoryHelper

-   Create `app/Helpers/ActivityHistoryHelper.php`
-   Add permission checking methods
-   Add query building helpers
-   **Estimated Time:** 1 hour

**Deliverables:**

-   ✅ Service class with all query methods
-   ✅ Helper class for permissions

---

### Phase 4: Controller & Routes (4 hours)

**Duration:** 4 hours  
**Priority:** 🔴 **HIGH**

#### Task 4.1: Create ActivityHistoryController

-   Create `app/Http/Controllers/ActivityHistoryController.php`
-   Add methods:
    -   `myActivities()` - For executor/applicant
    -   `teamActivities()` - For provincial
    -   `allActivities()` - For coordinator
    -   `projectHistory($projectId)` - Project-specific history
    -   `reportHistory($reportId)` - Report-specific history
-   **Estimated Time:** 2 hours

#### Task 4.2: Add Routes

-   Add routes in `routes/web.php`
-   Add middleware for role-based access
-   **Estimated Time:** 1 hour

#### Task 4.3: Add Sidebar Links

-   Update `executor/sidebar.blade.php` - Add "My Activities"
-   Update `provincial/sidebar.blade.php` - Add "Team Activities"
-   Update `coordinator/sidebar.blade.php` - Add "All Activities"
-   Update `admin/sidebar.blade.php` - Add "All Activities"
-   **Estimated Time:** 1 hour

**Deliverables:**

-   ✅ Controller created
-   ✅ Routes added
-   ✅ Sidebar links added

---

### Phase 5: Views & UI (6 hours)

**Duration:** 6 hours  
**Priority:** 🔴 **HIGH**

#### Task 5.1: Create Activity History Views

-   Create `resources/views/activity-history/index.blade.php` - Main listing
-   Create `resources/views/activity-history/my-activities.blade.php` - Executor view
-   Create `resources/views/activity-history/team-activities.blade.php` - Provincial view
-   Create `resources/views/activity-history/all-activities.blade.php` - Coordinator view
-   **Estimated Time:** 3 hours

#### Task 5.2: Create Project/Report History Views

-   Create `resources/views/activity-history/project-history.blade.php`
-   Create `resources/views/activity-history/report-history.blade.php`
-   **Estimated Time:** 1 hour

#### Task 5.3: Create Activity History Partial

-   Create `resources/views/activity-history/partials/activity-table.blade.php`
-   Reusable table component for activity listing
-   **Estimated Time:** 1 hour

#### Task 5.4: Add Filters & Search

-   Add filters: by type (project/report), by status, by date range
-   Add search: by project title, report title, user name
-   **Estimated Time:** 1 hour

**Deliverables:**

-   ✅ All views created
-   ✅ Filters and search implemented
-   ✅ Responsive design

---

### Phase 6: Integration & Testing (4 hours)

**Duration:** 4 hours  
**Priority:** 🟡 **MEDIUM**

#### Task 6.1: Integration Testing

-   Test executor/applicant access
-   Test provincial access
-   Test coordinator access
-   Test project history view
-   Test report history view
-   **Estimated Time:** 2 hours

#### Task 6.2: Edge Case Testing

-   Test with no activities
-   Test with many activities (pagination)
-   Test permission boundaries
-   Test data migration
-   **Estimated Time:** 1 hour

#### Task 6.3: Performance Testing

-   Test query performance
-   Add eager loading where needed
-   Optimize indexes
-   **Estimated Time:** 1 hour

**Deliverables:**

-   ✅ All tests passing
-   ✅ Performance optimized
-   ✅ Edge cases handled

---

### Phase 7: Documentation & Cleanup (2 hours)

**Duration:** 2 hours  
**Priority:** 🟢 **LOW**

#### Task 7.1: Code Documentation

-   Add PHPDoc comments
-   Document service methods
-   Document helper methods
-   **Estimated Time:** 1 hour

#### Task 7.2: User Documentation

-   Create user guide for activity reports
-   Document access levels
-   Document filters and search
-   **Estimated Time:** 1 hour

**Deliverables:**

-   ✅ Code documented
-   ✅ User guide created

---

## Technical Specifications

### Database Schema

**Table: `activity_histories`**

```php
Schema::create('activity_histories', function (Blueprint $table) {
    $table->id();
    $table->enum('type', ['project', 'report'])->index();
    $table->string('related_id')->index(); // project_id or report_id
    $table->string('previous_status')->nullable();
    $table->string('new_status')->index();
    $table->foreignId('changed_by_user_id')->constrained('users')->onDelete('cascade');
    $table->string('changed_by_user_role', 50);
    $table->string('changed_by_user_name', 255);
    $table->text('notes')->nullable();
    $table->timestamps();

    // Composite index for type + related_id
    $table->index(['type', 'related_id']);
});
```

### Model: ActivityHistory

```php
class ActivityHistory extends Model
{
    protected $fillable = [
        'type',
        'related_id',
        'previous_status',
        'new_status',
        'changed_by_user_id',
        'changed_by_user_role',
        'changed_by_user_name',
        'notes',
    ];

    // Relationships
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'related_id', 'project_id')
            ->where('type', 'project');
    }

    public function report()
    {
        return $this->belongsTo(DPReport::class, 'related_id', 'report_id')
            ->where('type', 'report');
    }
}
```

### Service: ActivityHistoryService

```php
class ActivityHistoryService
{
    public static function getForExecutor(User $user): Collection
    {
        // Get project IDs where user is owner or in-charge
        $projectIds = Project::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('in_charge', $user->id);
        })->pluck('project_id');

        // Get report IDs for those projects
        $reportIds = DPReport::whereIn('project_id', $projectIds)
            ->pluck('report_id');

        return ActivityHistory::where(function($query) use ($projectIds, $reportIds) {
            $query->where(function($q) use ($projectIds) {
                $q->where('type', 'project')
                  ->whereIn('related_id', $projectIds);
            })->orWhere(function($q) use ($reportIds) {
                $q->where('type', 'report')
                  ->whereIn('related_id', $reportIds);
            });
        })
        ->with('changedBy')
        ->orderBy('created_at', 'desc')
        ->get();
    }

    public static function getForProvincial(User $provincial): Collection
    {
        // Get team user IDs
        $teamUserIds = User::where('parent_id', $provincial->id)
            ->whereIn('role', ['executor', 'applicant'])
            ->pluck('id');

        // Get project IDs
        $projectIds = Project::where(function($query) use ($teamUserIds) {
            $query->whereIn('user_id', $teamUserIds)
                  ->orWhereIn('in_charge', $teamUserIds);
        })->pluck('project_id');

        // Get report IDs
        $reportIds = DPReport::whereIn('project_id', $projectIds)
            ->pluck('report_id');

        return ActivityHistory::where(function($query) use ($projectIds, $reportIds) {
            $query->where(function($q) use ($projectIds) {
                $q->where('type', 'project')
                  ->whereIn('related_id', $projectIds);
            })->orWhere(function($q) use ($reportIds) {
                $q->where('type', 'report')
                  ->whereIn('related_id', $reportIds);
            });
        })
        ->with('changedBy')
        ->orderBy('created_at', 'desc')
        ->get();
    }

    public static function getForCoordinator(): Collection
    {
        return ActivityHistory::with('changedBy')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
```

---

## Testing Strategy

### Unit Tests

1. **ActivityHistoryService Tests**

    - Test `getForExecutor()`
    - Test `getForProvincial()`
    - Test `getForCoordinator()`
    - Test filtering and pagination

2. **ReportStatusService Tests**
    - Test `logStatusChange()`
    - Test all status change methods

### Integration Tests

1. **Access Control Tests**

    - Executor can only see own activities
    - Provincial can see team activities
    - Coordinator can see all activities

2. **Status Change Logging Tests**
    - Project status changes are logged
    - Report status changes are logged
    - All fields are correctly saved

### Manual Testing Checklist

-   [ ] Executor can access "My Activities"
-   [ ] Provincial can access "Team Activities"
-   [ ] Coordinator can access "All Activities"
-   [ ] Project history view works
-   [ ] Report history view works
-   [ ] Filters work correctly
-   [ ] Search works correctly
-   [ ] Pagination works
-   [ ] Status badges display correctly
-   [ ] User names display correctly
-   [ ] Notes display correctly

---

## Timeline Summary

| Phase                              | Duration     | Priority  | Dependencies |
| ---------------------------------- | ------------ | --------- | ------------ |
| Phase 1: Database & Model          | 4 hours      | 🔴 High   | None         |
| Phase 2: Report Status Integration | 6 hours      | 🔴 High   | Phase 1      |
| Phase 3: Service & Helpers         | 4 hours      | 🔴 High   | Phase 1, 2   |
| Phase 4: Controller & Routes       | 4 hours      | 🔴 High   | Phase 3      |
| Phase 5: Views & UI                | 6 hours      | 🔴 High   | Phase 4      |
| Phase 6: Integration & Testing     | 4 hours      | 🟡 Medium | Phase 5      |
| Phase 7: Documentation             | 2 hours      | 🟢 Low    | Phase 6      |
| **Total**                          | **30 hours** |           |              |

---

## Success Criteria

### Functional Requirements

-   ✅ All project status changes are logged
-   ✅ All report status changes are logged
-   ✅ Executor/applicant can see own activities
-   ✅ Provincial can see team activities
-   ✅ Coordinator can see all activities
-   ✅ Project history view works
-   ✅ Report history view works
-   ✅ Filters and search work

### Technical Requirements

-   ✅ Database schema is optimized
-   ✅ Queries are efficient (no N+1 problems)
-   ✅ Code follows Laravel best practices
-   ✅ All tests passing
-   ✅ Documentation complete

### User Experience Requirements

-   ✅ Easy to access activity reports
-   ✅ Clear display of status changes
-   ✅ Filters are intuitive
-   ✅ Responsive design
-   ✅ Fast page loads

---

## Next Steps

1. **Review this plan** with stakeholders
2. **Approve database design** (unified vs separate tables)
3. **Begin Phase 1** - Database & Model Setup
4. **Proceed sequentially** through all phases
5. **Test thoroughly** at each phase

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** Ready for Review and Implementation

---

**End of Activity Report Requirements & Implementation Plan**
