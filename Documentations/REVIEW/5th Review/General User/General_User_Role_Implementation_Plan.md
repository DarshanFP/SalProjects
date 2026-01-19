# General User Role - Implementation Plan

## Executive Summary

This document outlines the comprehensive implementation plan for the **General** user role in the SalProjects application. The General user has a dual role:
1. **Parent to Coordinators** - **INHERITS ALL COORDINATOR ACCESS AND AUTHORIZATION** - Can perform every action that a Coordinator can perform on projects/reports from coordinators and their hierarchy. This includes ALL routes, permissions, and functionality available to Coordinators.
2. **Acts as Provincial** - Has centers and can create executors/applicants directly under them (when acting as provincial for direct team management)

**Date:** January 2025  
**Status:** 📋 **PLANNING**  
**Priority:** 🔴 **HIGH**  
**Estimated Duration:** 40-50 hours

---

## ⚠️ CRITICAL REQUIREMENT: Complete Coordinator Access

**THE GENERAL USER MUST HAVE COMPLETE AND IDENTICAL COORDINATOR ACCESS AND AUTHORIZATION**

This means:
- ✅ **ALL Routes:** General can access EVERY route that coordinator can access for coordinator hierarchy
- ✅ **ALL Methods:** General can use EVERY controller method and service method that coordinator uses  
- ✅ **ALL Permissions:** General has EVERY permission check that coordinator has
- ✅ **IDENTICAL Functionality:** General's actions work EXACTLY the same as coordinator's actions
- ✅ **Same Validation:** General follows the same validation rules as coordinator
- ✅ **Same Business Logic:** General follows the same business logic as coordinator

**Implementation Rule:**
```php
// ✅ CORRECT - General has same access as coordinator
if (in_array($user->role, ['coordinator', 'general'])) {
    // Same logic for both - General has COMPLETE coordinator access
}

// ❌ WRONG - Don't create separate logic for general
if ($user->role === 'coordinator') {
    // coordinator logic
} elseif ($user->role === 'general') {
    // different logic (WRONG!)
}
```

**The ONLY Difference:**
- **Coordinator Scope:** Their direct hierarchy (provincials → executors/applicants)
- **General Scope:** Coordinator hierarchy + Direct team (BROADER scope, but SAME authorization level)

**Authorization Level:** General = Coordinator (identical permissions)  
**Data Scope:** General > Coordinator (sees more data - coordinators + direct team)

---

## 📋 QUICK REFERENCE: Complete Coordinator Access Checklist

### ✅ What General User MUST Have (Complete Coordinator Access)

**Routes (34+ routes):**
- ✅ ALL coordinator routes accessible via middleware update
- ✅ ALL shared routes (aggregated reports, projects, reports)
- ✅ Dashboard redirect route

**Actions (ALL coordinator actions):**
- ✅ Approve projects (SAME as coordinator)
- ✅ Reject projects (SAME as coordinator)
- ✅ Revert projects to coordinators (SAME as coordinator)
- ✅ Approve reports (SAME as coordinator)
- ✅ Revert reports to coordinators (SAME as coordinator)
- ✅ Add/edit/update comments (SAME as coordinator)
- ✅ Download PDF/DOC (SAME as coordinator)
- ✅ Bulk actions (SAME as coordinator)
- ✅ Budget management (SAME as coordinator)
- ✅ Activity history access (SAME as coordinator)

**Service Methods (ALL coordinator methods):**
- ✅ `ProjectStatusService::approve()` - General can use (SAME as coordinator)
- ✅ `ProjectStatusService::reject()` - General can use (SAME as coordinator)
- ✅ `ReportStatusService::approve()` - General can use (SAME as coordinator)
- ✅ ALL coordinator service methods accessible to General

**Authorization (IDENTICAL to coordinator):**
- ✅ Same permission checks
- ✅ Same validation rules
- ✅ Same business logic
- ✅ Same authorization level

**The ONLY Difference:**
- **Scope:** General sees coordinators + direct team (BROADER scope)
- **Authorization:** General = Coordinator (SAME authorization level)

---

## Table of Contents

1. [Role Overview](#role-overview)
2. [Hierarchical Structure](#hierarchical-structure)
3. [Permissions & Access Requirements](#permissions--access-requirements)
4. [Files to Create](#files-to-create)
5. [Files to Update](#files-to-update)
6. [Routes to Add/Update](#routes-to-addupdate)
7. [Dashboard Design](#dashboard-design)
8. [Sidebar Structure](#sidebar-structure)
9. [Implementation Phases](#implementation-phases)
10. [Testing Requirements](#testing-requirements)

---

## 1. Role Overview

### 1.1 General User Characteristics

**Hierarchical Position:**
- **Parent Role:** None (top-level, similar to admin)
- **Child Roles:** Coordinator, Executor, Applicant (when acting as provincial)

**Dual Functionality:**
1. **As Coordinator Parent:**
   - Manages coordinators (create, edit, activate/deactivate, reset password)
   - Views all projects and reports from coordinators and their hierarchy
   - Approves projects forwarded by coordinators (or from direct executors)
   - Approves reports forwarded by coordinators (or from direct executors)
   - Can revert projects/reports to coordinators

2. **As Provincial:**
   - Manages executors/applicants directly under them (create, edit, activate/deactivate, reset password)
   - Views projects from direct executors/applicants
   - Forwards projects to coordinators (or approves if acting as coordinator)
   - Forwards reports to coordinators (or approves if acting as coordinator)
   - Can revert projects/reports to direct executors/applicants

**Database Structure:**
- General user's `parent_id` is NULL
- Coordinators have `parent_id` = General user's ID
- Executors/Applicants can have `parent_id` = General user's ID (when general acts as provincial)
- Executors/Applicants can have `parent_id` = Provincial user's ID (under coordinator hierarchy)

---

## 2. Hierarchical Structure

### 2.1 Current Structure (Before General)

```
Admin (Top)
  └── Coordinator
      └── Provincial
          └── Executor/Applicant
```

### 2.2 New Structure (With General)

```
Admin (Top)
  └── General
      ├── Coordinator (child of General)
      │   └── Provincial (child of Coordinator)
      │       └── Executor/Applicant (child of Provincial)
      └── Executor/Applicant (direct child of General, when General acts as Provincial)
```

### 2.3 Visual Hierarchy Diagram

```
┌─────────────────────────────────────────────────────────┐
│                      General User                        │
│              (Parent: NULL, Role: general)               │
└─────────────────────────────────────────────────────────┘
                            │
          ┌─────────────────┼─────────────────┐
          │                                   │
    ┌─────▼─────┐                   ┌─────────▼──────────┐
    │Coordinator│                   │   As Provincial:   │
    │(parent_id │                   │ Executor/Applicant │
    │ = general)│                   │ (parent_id =       │
    └───────────┘                   │  general)          │
          │                         └────────────────────┘
    ┌─────▼─────┐
    │ Provincial│
    │(parent_id │
    │=coord...) │
    └───────────┘
          │
    ┌─────▼─────────┐
    │Executor/      │
    │Applicant      │
    │(parent_id =   │
    │ provincial)   │
    └───────────────┘
```

---

## 3. Permissions & Access Requirements

### ⚠️ CRITICAL: Complete Coordinator Access Inheritance

**The General user MUST have COMPLETE and IDENTICAL coordinator access and authorization.** This means:

1. **ALL Routes:** General can access EVERY route that coordinator can access for the coordinator hierarchy
2. **ALL Methods:** General can use EVERY controller method and service method that coordinator uses
3. **ALL Permissions:** General has EVERY permission check that coordinator has
4. **IDENTICAL Functionality:** General's actions work EXACTLY the same as coordinator's actions
5. **Same Validation:** General follows the same validation rules as coordinator
6. **Same Business Logic:** General follows the same business logic as coordinator

**The ONLY difference is SCOPE:**
- Coordinator scope: Their direct hierarchy (provincials → executors/applicants)
- General scope: Coordinator hierarchy + Direct team (broader scope, but same authorization level)

**Authorization Level:** General = Coordinator (same permissions)  
**Data Scope:** General > Coordinator (sees more data - coordinators + direct team)

### 3.1 Coordinator-Level Permissions (COMPLETE INHERITANCE)

**⚠️ IMPORTANT: General user has COMPLETE access to ALL coordinator functionality. Every route, permission, and action available to Coordinators is also available to General users for the coordinator hierarchy.**

**User Management:**
- ✅ Create Coordinator users (same as coordinator creating provincial)
- ✅ Edit Coordinator users (same as coordinator editing provincial)
- ✅ Activate/Deactivate Coordinator users (same as coordinator activating/deactivating provincial)
- ✅ Reset Coordinator passwords (same as coordinator resetting provincial passwords)
- ✅ View all Coordinators under them (same as coordinator viewing provincials)
- ✅ **ALL** user management actions that coordinator can perform on provincials

**Projects (Complete Coordinator Access):**
- ✅ View ALL projects from coordinators and their ENTIRE hierarchy (recursively)
- ✅ View pending projects forwarded by coordinators (same as coordinator viewing pending from provincials)
- ✅ **Approve projects** forwarded by coordinators (COMPLETE coordinator approval functionality)
- ✅ **Reject projects** forwarded by coordinators (COMPLETE coordinator rejection functionality)
- ✅ **Revert projects to coordinators** (same as coordinator reverting to provincial)
- ✅ View ALL approved projects from coordinators and their hierarchy
- ✅ **Download project PDF/DOC** (same as coordinator)
- ✅ **Add comments to projects** (same as coordinator)
- ✅ **Edit project comments** (same as coordinator)
- ✅ **Update project comments** (same as coordinator)
- ✅ **Show project details** (same as coordinator)
- ✅ **Access ALL coordinator project routes and actions**

**Reports (Complete Coordinator Access):**
- ✅ View ALL monthly reports from coordinators and their ENTIRE hierarchy (recursively)
- ✅ View pending reports forwarded by coordinators (same as coordinator viewing pending from provincials)
- ✅ **Approve reports** forwarded by coordinators (COMPLETE coordinator approval functionality)
- ✅ **Revert reports to coordinators** (same as coordinator reverting to provincial)
- ✅ View ALL approved reports from coordinators and their hierarchy
- ✅ **Download report PDF/DOC** (same as coordinator)
- ✅ **Add comments to reports** (same as coordinator)
- ✅ **Edit report comments** (same as coordinator)
- ✅ **Update report comments** (same as coordinator)
- ✅ **Show report details** (same as coordinator - monthly, quarterly, biannual, annual)
- ✅ **Bulk approve/revert reports** (same as coordinator)
- ✅ **Access ALL coordinator report routes and actions**

**Aggregated Reports (Complete Coordinator Access):**
- ✅ View Quarterly Reports (from ALL coordinators and their hierarchy)
- ✅ Create Quarterly Reports (if coordinator can)
- ✅ View Biannual Reports (from ALL coordinators and their hierarchy)
- ✅ Create Biannual Reports (if coordinator can)
- ✅ View Annual Reports (from ALL coordinators and their hierarchy)
- ✅ Create Annual Reports (if coordinator can)
- ✅ Export aggregated reports (PDF/Word) (same as coordinator)
- ✅ **Access ALL coordinator aggregated report routes and actions**

**Budget (Complete Coordinator Access):**
- ✅ View budget overview (ALL coordinators and their hierarchy)
- ✅ View project budgets (ALL coordinators and their hierarchy)
- ✅ Generate budget reports (same as coordinator)
- ✅ Export budget reports (Excel/PDF) (same as coordinator)
- ✅ Budget comparison and analytics (same as coordinator)
- ✅ **Access ALL coordinator budget routes and actions**

**Activity History (Complete Coordinator Access):**
- ✅ View ALL activities from coordinators and their ENTIRE hierarchy (recursively)
- ✅ Filter activities by coordinator, province, center, project type (same as coordinator)
- ✅ View activity details (same as coordinator)
- ✅ **Access ALL coordinator activity history routes and actions**

**Dashboard (Complete Coordinator Access):**
- ✅ Coordinator-style dashboard with coordinator management widgets
- ✅ View statistics from ALL coordinators and their hierarchy
- ✅ Filter by coordinator, province, center, project type (same as coordinator)
- ✅ All coordinator dashboard widgets and features
- ✅ Coordinator performance metrics
- ✅ System analytics and reports (same as coordinator)
- ✅ **Access ALL coordinator dashboard functionality**

**Additional Coordinator Permissions:**
- ✅ **Access ALL coordinator routes** (must have 'general' added to coordinator route middleware)
- ✅ **Use ALL coordinator service methods** (ProjectStatusService::approve(), ReportStatusService::approve(), etc.)
- ✅ **Same authorization checks as coordinator** (can approve projects/reports from coordinators)
- ✅ **Same validation rules as coordinator** (for project/report approval)
- ✅ **Same business logic as coordinator** (for all coordinator actions)

### 3.2 Provincial-Level Permissions (Direct Management)

**User Management:**
- ✅ Create Executor/Applicant users directly under General
- ✅ Edit Executor/Applicant users directly under General
- ✅ Activate/Deactivate Executor/Applicant users directly under General
- ✅ Reset Executor/Applicant passwords directly under General
- ✅ View all Executors/Applicants directly under General

**Projects (Direct Executors/Applicants):**
- ✅ View projects from direct executors/applicants
- ✅ View pending projects (submitted by direct executors/applicants)
- ✅ Forward projects to coordinators (or approve if acting as coordinator)
- ✅ Revert projects to direct executors/applicants
- ✅ View approved projects from direct executors/applicants
- ✅ Download project PDF/DOC
- ✅ Add comments to projects

**Reports (Direct Executors/Applicants):**
- ✅ View monthly reports from direct executors/applicants
- ✅ View pending reports (submitted by direct executors/applicants)
- ✅ Forward reports to coordinators (or approve if acting as coordinator)
- ✅ Revert reports to direct executors/applicants
- ✅ View approved reports from direct executors/applicants
- ✅ Download report PDF/DOC
- ✅ Add comments to reports
- ✅ Bulk forward reports

**Activity History:**
- ✅ View activities from direct executors/applicants

**Dashboard (Provincial View):**
- ✅ Provincial-style dashboard for direct executors/applicants
- ✅ View statistics from direct executors/applicants
- ✅ Filter by center, project type

### 3.3 Combined Access Logic

**Key Decision Point:**
- Projects/reports from **coordinators and their hierarchy** → Use Coordinator-level permissions
- Projects/reports from **direct executors/applicants** → Use Provincial-level permissions

**Query Pattern:**
```php
// Get coordinator IDs under general
$coordinatorIds = User::where('parent_id', $general->id)
    ->where('role', 'coordinator')
    ->pluck('id');

// Get all projects from coordinators and their hierarchy
$projectsFromCoordinators = Project::whereHas('user', function($query) use ($coordinatorIds) {
    // Get all users under coordinators (provincials, executors, applicants)
    $allUserIdsUnderCoordinators = User::whereIn('parent_id', $coordinatorIds)
        ->orWhereIn('id', function($subQuery) use ($coordinatorIds) {
            // Get users whose parent's parent is a coordinator
            $subQuery->select('id')
                ->from('users')
                ->whereIn('parent_id', function($parentQuery) use ($coordinatorIds) {
                    $parentQuery->select('id')
                        ->from('users')
                        ->whereIn('parent_id', $coordinatorIds);
                });
        })
        ->pluck('id');
    
    $query->whereIn('user_id', $allUserIdsUnderCoordinators)
        ->orWhereIn('in_charge', $allUserIdsUnderCoordinators);
})->get();

// Get projects from direct executors/applicants under general
$projectsFromDirectTeam = Project::whereHas('user', function($query) use ($general) {
    $directTeamIds = User::where('parent_id', $general->id)
        ->whereIn('role', ['executor', 'applicant'])
        ->pluck('id');
    
    $query->whereIn('user_id', $directTeamIds)
        ->orWhereIn('in_charge', $directTeamIds);
})->get();

// Combine both
$allProjects = $projectsFromCoordinators->merge($projectsFromDirectTeam);
```

---

## 4. Files to Create

### 4.1 Controller

**File:** `app/Http/Controllers/GeneralController.php`

**Purpose:** Main controller for General user, combining CoordinatorController and ProvincialController functionality

**Key Methods:**
- `GeneralDashboard()` - Combined dashboard showing both coordinator and provincial views
- `createCoordinator()` - Create coordinator user
- `storeCoordinator()` - Store coordinator user
- `listCoordinators()` - List all coordinators under general
- `editCoordinator()` - Edit coordinator
- `updateCoordinator()` - Update coordinator
- `createExecutor()` - Create executor/applicant directly under general
- `storeExecutor()` - Store executor/applicant directly under general
- `listExecutors()` - List all executors/applicants directly under general
- `editExecutor()` - Edit executor/applicant
- `updateExecutor()` - Update executor/applicant
- `projectList()` - Combined project list (from coordinators + direct team)
- `approvedProjects()` - Combined approved projects view
- `showProject()` - View project
- `approveProject()` - Approve project (coordinator-level)
- `rejectProject()` - Reject project
- `revertToCoordinator()` - Revert project to coordinator
- `revertToExecutor()` - Revert project to direct executor
- `forwardToCoordinator()` - Forward project from direct executor to coordinator
- `reportList()` - Combined report list
- `pendingReports()` - Combined pending reports
- `approvedReports()` - Combined approved reports
- `showMonthlyReport()` - View monthly report
- `approveReport()` - Approve report (coordinator-level)
- `revertReport()` - Revert report
- `forwardReport()` - Forward report from direct executor to coordinator
- `bulkForwardReports()` - Bulk forward reports
- `addProjectComment()` - Add comment to project
- `addComment()` - Add comment to report
- `budgetOverview()` - Budget overview (all coordinators + direct team)
- `projectBudgets()` - Project budgets list
- `resetUserPassword()` - Reset password for coordinator or executor
- `activateUser()` - Activate user
- `deactivateUser()` - Deactivate user

**Estimated Lines:** ~2500-3000 lines

### 4.2 Views - Dashboard

**File:** `resources/views/general/index.blade.php`

**Purpose:** Main dashboard view combining coordinator and provincial dashboards

**Sections:**
1. **Header Section:**
   - Welcome message
   - Quick stats cards (Total Coordinators, Direct Team Members, Pending Projects, Pending Reports)

2. **Coordinator Management Section:**
   - Coordinators Overview Widget
   - Pending Approvals from Coordinators
   - Coordinator Performance Metrics
   - Coordinator Activity Feed

3. **Direct Team Management Section:**
   - Direct Team Overview Widget (Executors/Applicants directly under General)
   - Pending Approvals from Direct Team
   - Team Performance Metrics
   - Team Activity Feed

4. **Projects Section:**
   - Combined Projects Overview
   - Projects by Status Chart
   - Projects by Type Chart
   - Recent Projects List

5. **Reports Section:**
   - Combined Reports Overview
   - Reports by Status Chart
   - Recent Reports List

6. **Budget Section:**
   - Combined Budget Overview
   - Budget by Coordinator Chart
   - Budget by Center Chart (direct team)
   - Budget Summary Table

7. **Filters:**
   - Filter by Coordinator (for coordinator hierarchy)
   - Filter by Center (for direct team)
   - Filter by Province
   - Filter by Project Type
   - Filter by Status

**Estimated Lines:** ~800-1000 lines

### 4.3 Views - Coordinator Management

**File:** `resources/views/general/coordinators/index.blade.php`

**Purpose:** List all coordinators under General user

**Similar to:** `resources/views/coordinator/provincials.blade.php`

**Estimated Lines:** ~200-300 lines

**File:** `resources/views/general/coordinators/create.blade.php`

**Purpose:** Create new coordinator user

**Similar to:** `resources/views/coordinator/createProvincial.blade.php`

**Estimated Lines:** ~150-200 lines

**File:** `resources/views/general/coordinators/edit.blade.php`

**Purpose:** Edit coordinator user

**Estimated Lines:** ~150-200 lines

### 4.4 Views - Direct Team Management

**File:** `resources/views/general/executors/index.blade.php`

**Purpose:** List all executors/applicants directly under General

**Similar to:** `resources/views/provincial/executors.blade.php`

**Estimated Lines:** ~200-300 lines

**File:** `resources/views/general/executors/create.blade.php`

**Purpose:** Create new executor/applicant directly under General

**Similar to:** `resources/views/provincial/createExecutor.blade.php`

**Estimated Lines:** ~150-200 lines

**File:** `resources/views/general/executors/edit.blade.php`

**Purpose:** Edit executor/applicant directly under General

**Estimated Lines:** ~150-200 lines

### 4.5 Views - Projects

**File:** `resources/views/general/projects/list.blade.php`

**Purpose:** Combined project list (from coordinators + direct team)

**Estimated Lines:** ~300-400 lines

**File:** `resources/views/general/projects/approved.blade.php`

**Purpose:** Combined approved projects view

**Estimated Lines:** ~300-400 lines

**File:** `resources/views/general/projects/show.blade.php`

**Purpose:** View project details

**Similar to:** `resources/views/coordinator/showProject.blade.php`

**Estimated Lines:** ~400-500 lines

### 4.6 Views - Reports

**File:** `resources/views/general/reports/list.blade.php`

**Purpose:** Combined report list

**Estimated Lines:** ~300-400 lines

**File:** `resources/views/general/reports/pending.blade.php`

**Purpose:** Combined pending reports

**Estimated Lines:** ~300-400 lines

**File:** `resources/views/general/reports/approved.blade.php`

**Purpose:** Combined approved reports

**Estimated Lines:** ~300-400 lines

### 4.7 Views - Budget

**File:** `resources/views/general/budgets/overview.blade.php`

**Purpose:** Combined budget overview

**Similar to:** `resources/views/coordinator/budget-overview.blade.php`

**Estimated Lines:** ~400-500 lines

**File:** `resources/views/general/budgets/index.blade.php`

**Purpose:** Project budgets list

**Similar to:** `resources/views/coordinator/budgets.blade.php`

**Estimated Lines:** ~300-400 lines

### 4.8 Sidebar

**File:** `resources/views/general/sidebar.blade.php`

**Purpose:** Sidebar navigation for General user

**Structure:**
```
📊 Main
  ├─ Dashboard
  ├─ All Activities
  └─ Notifications

👥 My Team
  ├─ Coordinators
  │   ├─ Add Coordinator
  │   └─ View Coordinators
  └─ Direct Team (Executors/Applicants)
      ├─ Add Member
      └─ View Members

📁 Projects
  ├─ All Projects (Combined)
  ├─ Pending Projects (Combined)
  └─ Approved Projects (Combined)

📄 Reports
  ├─ Monthly Reports
  │   ├─ Pending Reports (Combined)
  │   └─ Approved Reports (Combined)
  ├─ Quarterly Reports
  ├─ Biannual Reports
  └─ Annual Reports

💰 Budget & Finance
  ├─ Budget Overview
  ├─ Project Budgets
  └─ Budget Reports

📚 Documentation
  └─ Documentation

🔧 Settings
  ├─ Profile
  └─ Change Password
```

**Estimated Lines:** ~200-300 lines

---

## 5. Files to Update

### 5.1 Routes

**File:** `routes/web.php`

**⚠️ CRITICAL: General must have access to ALL coordinator routes**

**Changes Needed:**
- **RECOMMENDED APPROACH:** Update existing coordinator routes middleware to include 'general' role
- This ensures General has COMPLETE coordinator access without code duplication
- Add general-specific routes only for managing coordinators directly (when General acts as coordinator parent)

**ALL Coordinator Routes General Must Access (Complete List):**

1. **Dashboard:**
   - `/coordinator/dashboard` - `coordinator.dashboard`
   - `/coordinator/dashboard/refresh` - `coordinator.dashboard.refresh` (if exists)

2. **User Management (Provincials - but General manages Coordinators):**
   - `/coordinator/create-provincial` - `coordinator.createProvincial` (General: createCoordinator)
   - `/coordinator/create-provincial` (POST) - `coordinator.storeProvincial` (General: storeCoordinator)
   - `/coordinator/provincials` - `coordinator.provincials` (General: listCoordinators)
   - `/coordinator/provincial/{id}/edit` - `coordinator.editProvincial` (General: editCoordinator)
   - `/coordinator/provincial/{id}/update` (POST) - `coordinator.updateProvincial` (General: updateCoordinator)
   - `/coordinator/user/{id}/reset-password` (POST) - `coordinator.resetUserPassword` (General: resetCoordinatorPassword)
   - `/coordinator/user/{id}/activate` (POST) - `coordinator.activateUser` (General: activateCoordinator)
   - `/coordinator/user/{id}/deactivate` (POST) - `coordinator.deactivateUser` (General: deactivateCoordinator)

3. **Projects (Complete Coordinator Access):**
   - `/coordinator/projects-list` - `coordinator.projects.list`
   - `/coordinator/approved-projects` - `coordinator.approved.projects`
   - `/coordinator/projects/show/{project_id}` - `coordinator.projects.show`
   - `/projects/{project_id}/approve` (POST) - `projects.approve` ✅ **General can approve**
   - `/projects/{project_id}/reject` (POST) - `projects.reject` ✅ **General can reject**
   - `/projects/{project_id}/revert-to-provincial` (POST) - `projects.revertToProvincial` (General: revertToCoordinator)

4. **Project Comments (Complete Coordinator Access):**
   - `/coordinator/projects/{project_id}/add-comment` (POST) - `coordinator.projects.addComment` ✅ **General can add comments**
   - `/coordinator/projects/comment/{id}/edit` - `coordinator.projects.editComment` ✅ **General can edit comments**
   - `/coordinator/projects/comment/{id}/update` (POST) - `coordinator.projects.updateComment` ✅ **General can update comments**

5. **Reports (Complete Coordinator Access):**
   - `/coordinator/report-list` - `coordinator.report.list`
   - `/coordinator/report-list/pending` - `coordinator.report.pending`
   - `/coordinator/report-list/approved` - `coordinator.report.approved`
   - `/coordinator/report-list/bulk-action` (POST) - `coordinator.report.bulk-action` ✅ **General can bulk actions**
   - `/coordinator/report/{report_id}/approve` (POST) - `coordinator.report.approve` ✅ **General can approve reports**
   - `/coordinator/report/{report_id}/revert` (POST) - `coordinator.report.revert` ✅ **General can revert reports**
   - `/coordinator/reports/{type}/{id}` - `coordinator.reports.show`
   - `/coordinator/reports/monthly/show/{report_id}` - `coordinator.monthly.report.show`

6. **Report Comments (Complete Coordinator Access):**
   - `/coordinator/reports/monthly/{report_id}/add-comment` (POST) - `coordinator.monthly.report.addComment` ✅ **General can add comments**

7. **Budget (Complete Coordinator Access):**
   - `/coordinator/budgets` - `coordinator.budgets`
   - `/coordinator/budget-overview` - `coordinator.budget-overview`
   - `/coordinator/budgets/report` - `budgets.report`

8. **Downloads (Complete Coordinator Access):**
   - `/coordinator/projects/{project_id}/download-pdf` - `coordinator.projects.downloadPdf` ✅ **General can download**
   - `/coordinator/projects/{project_id}/download-doc` - `coordinator.projects.downloadDoc` ✅ **General can download**
   - `/coordinator/reports/monthly/downloadPdf/{report_id}` - `coordinator.monthly.report.downloadPdf` ✅ **General can download**
   - `/coordinator/reports/monthly/downloadDoc/{report_id}` - `coordinator.monthly.report.downloadDoc` ✅ **General can download**

9. **Activity History (Complete Coordinator Access):**
   - `/activities/all-activities` - `activities.all-activities` ✅ **General can view all activities**

10. **Utilities:**
    - `/coordinator/executors/by-province` - `coordinator.executors.byProvince` (AJAX helper)

**✅ RECOMMENDED IMPLEMENTATION: Update Coordinator Routes to Include General**

```php
// Update existing coordinator routes to include general role
// This ensures General has COMPLETE coordinator access
Route::middleware(['auth', 'role:coordinator,general'])->group(function () {
    // ALL existing coordinator routes - now accessible to BOTH coordinator and general
    // General has IDENTICAL access to coordinator for coordinator hierarchy
    
    // Manage Provincials (General manages Coordinators with same functionality)
    Route::get('/coordinator/create-provincial', [CoordinatorController::class, 'createProvincial'])->name('coordinator.createProvincial');
    Route::post('/coordinator/create-provincial', [CoordinatorController::class, 'storeProvincial'])->name('coordinator.storeProvincial');
    // ... ALL existing coordinator routes remain here
    
    // Projects (General has COMPLETE coordinator access)
    Route::post('/projects/{project_id}/approve', [CoordinatorController::class, 'approveProject'])->name('projects.approve');
    // General can approve - same as coordinator
    
    Route::post('/projects/{project_id}/reject', [CoordinatorController::class, 'rejectProject'])->name('projects.reject');
    // General can reject - same as coordinator
    
    // Reports (General has COMPLETE coordinator access)
    Route::post('/coordinator/report/{report_id}/approve', [CoordinatorController::class, 'approveReport'])->name('coordinator.report.approve');
    // General can approve - same as coordinator
    
    // ... ALL other coordinator routes
});

// Additional general-specific routes (only for direct team management)
Route::middleware(['auth', 'role:general'])->group(function () {
    // General-specific routes for managing coordinators directly
    // (These are in addition to coordinator routes above)
    
    // Optional: Create separate routes with /general prefix for clarity
    // But functionality should be same as coordinator routes
});
```

**⚠️ IMPORTANT:** When General accesses coordinator routes, the controllers should:
1. Check if user is 'general' role
2. If general, filter data by coordinators under them (for coordinator hierarchy)
3. If coordinator, use existing logic (filter by their hierarchy)
4. **SAME business logic** for both roles - only data scope differs

**Alternative Approach (If route separation is preferred):**
```php
// Option 2: Create separate routes for general (NOT RECOMMENDED - creates duplication)
// General routes (duplicating coordinator routes for clarity, but using same controllers/methods)
Route::middleware(['auth', 'role:general'])->group(function () {
    // Dashboard
    Route::get('/general/dashboard', [GeneralController::class, 'GeneralDashboard'])->name('general.dashboard');
    
    // Coordinator Management
    Route::get('/general/create-coordinator', [GeneralController::class, 'createCoordinator'])->name('general.createCoordinator');
    Route::post('/general/create-coordinator', [GeneralController::class, 'storeCoordinator'])->name('general.storeCoordinator');
    Route::get('/general/coordinators', [GeneralController::class, 'listCoordinators'])->name('general.coordinators');
    Route::get('/general/coordinator/{id}/edit', [GeneralController::class, 'editCoordinator'])->name('general.editCoordinator');
    Route::post('/general/coordinator/{id}/update', [GeneralController::class, 'updateCoordinator'])->name('general.updateCoordinator');
    
    // Executor Management (Direct Team)
    Route::get('/general/create-executor', [GeneralController::class, 'createExecutor'])->name('general.createExecutor');
    Route::post('/general/create-executor', [GeneralController::class, 'storeExecutor'])->name('general.storeExecutor');
    Route::get('/general/executors', [GeneralController::class, 'listExecutors'])->name('general.executors');
    Route::get('/general/executor/{id}/edit', [GeneralController::class, 'editExecutor'])->name('general.editExecutor');
    Route::post('/general/executor/{id}/update', [GeneralController::class, 'updateExecutor'])->name('general.updateExecutor');
    
    // Projects
    Route::get('/general/projects-list', [GeneralController::class, 'projectList'])->name('general.projects.list');
    Route::get('/general/approved-projects', [GeneralController::class, 'approvedProjects'])->name('general.approved.projects');
    Route::get('/general/projects/show/{project_id}', [GeneralController::class, 'showProject'])->name('general.projects.show');
    Route::post('/general/projects/{project_id}/approve', [GeneralController::class, 'approveProject'])->name('general.projects.approve');
    Route::post('/general/projects/{project_id}/reject', [GeneralController::class, 'rejectProject'])->name('general.projects.reject');
    Route::post('/general/projects/{project_id}/revert-to-coordinator', [GeneralController::class, 'revertToCoordinator'])->name('general.projects.revertToCoordinator');
    Route::post('/general/projects/{project_id}/revert-to-executor', [GeneralController::class, 'revertToExecutor'])->name('general.projects.revertToExecutor');
    Route::post('/general/projects/{project_id}/forward-to-coordinator', [GeneralController::class, 'forwardToCoordinator'])->name('general.projects.forwardToCoordinator');
    
    // Reports
    Route::get('/general/report-list', [GeneralController::class, 'reportList'])->name('general.report.list');
    Route::get('/general/report-list/pending', [GeneralController::class, 'pendingReports'])->name('general.report.pending');
    Route::get('/general/report-list/approved', [GeneralController::class, 'approvedReports'])->name('general.report.approved');
    Route::get('/general/reports/monthly/show/{report_id}', [GeneralController::class, 'showMonthlyReport'])->name('general.monthly.report.show');
    Route::post('/general/report/{report_id}/approve', [GeneralController::class, 'approveReport'])->name('general.report.approve');
    Route::post('/general/report/{report_id}/revert', [GeneralController::class, 'revertReport'])->name('general.report.revert');
    Route::post('/general/report/{report_id}/forward', [GeneralController::class, 'forwardReport'])->name('general.report.forward');
    Route::post('/general/reports/bulk-forward', [GeneralController::class, 'bulkForwardReports'])->name('general.report.bulk-forward');
    
    // Budget
    Route::get('/general/budget-overview', [GeneralController::class, 'budgetOverview'])->name('general.budget-overview');
    Route::get('/general/budgets', [GeneralController::class, 'projectBudgets'])->name('general.budgets');
    
    // Downloads
    Route::get('/general/projects/{project_id}/download-pdf', [ExportController::class, 'downloadPdf'])->name('general.projects.downloadPdf');
    Route::get('/general/projects/{project_id}/download-doc', [ExportController::class, 'downloadDoc'])->name('general.projects.downloadDoc');
    Route::get('/general/reports/monthly/downloadPdf/{report_id}', [ExportReportController::class, 'downloadPdf'])->name('general.monthly.report.downloadPdf');
    Route::get('/general/reports/monthly/downloadDoc/{report_id}', [ExportReportController::class, 'downloadDoc'])->name('general.monthly.report.downloadDoc');
    
    // Activity History
    Route::get('/activities/all-activities', [ActivityHistoryController::class, 'allActivities'])->name('activities.all-activities');
    
    // User Management
    Route::post('/general/user/{id}/reset-password', [GeneralController::class, 'resetUserPassword'])->name('general.resetUserPassword');
    Route::post('/general/user/{id}/activate', [GeneralController::class, 'activateUser'])->name('general.activateUser');
    Route::post('/general/user/{id}/deactivate', [GeneralController::class, 'deactivateUser'])->name('general.deactivateUser');
    
    // Comments
    Route::post('/general/projects/{project_id}/add-comment', [GeneralController::class, 'addProjectComment'])->name('general.projects.addComment');
    Route::post('/general/reports/monthly/{report_id}/add-comment', [GeneralController::class, 'addComment'])->name('general.monthly.report.addComment');
});
```

**Update Dashboard Redirect:**
```php
Route::get('/dashboard', function () {
    $user = Auth::user();
    $role = $user->role;

    $url = match($role) {
        'admin' => '/admin/dashboard',
        'general' => '/general/dashboard',  // ADD THIS - General has own dashboard
        'coordinator' => '/coordinator/dashboard',
        'provincial' => '/provincial/dashboard',
        'executor' => '/executor/dashboard',
        'applicant' => '/executor/dashboard',
        default => '/profile',
    };

    return redirect($url);
})->middleware(['auth'])->name('dashboard');
```

**✅ RECOMMENDED ROUTE STRUCTURE:**

```php
// 1. Update coordinator routes to include general (for coordinator hierarchy access)
Route::middleware(['auth', 'role:coordinator,general'])->group(function () {
    // ALL coordinator routes - General can access ALL of them
    // Controllers will check role and filter data appropriately
});

// 2. Add general-specific dashboard route
Route::middleware(['auth', 'role:general'])->group(function () {
    Route::get('/general/dashboard', [GeneralController::class, 'GeneralDashboard'])->name('general.dashboard');
    
    // General-specific routes for direct team management (provincial-level actions)
    // These are separate from coordinator routes above
    Route::get('/general/create-executor', [GeneralController::class, 'createExecutor'])->name('general.createExecutor');
    Route::post('/general/create-executor', [GeneralController::class, 'storeExecutor'])->name('general.storeExecutor');
    Route::get('/general/executors', [GeneralController::class, 'listExecutors'])->name('general.executors');
    // ... etc for direct team management
});
```

**Estimated Lines to Add/Change:** 
- Update coordinator routes middleware: ~2 lines (change 'coordinator' to 'coordinator,general')
- Add general dashboard route: ~5 lines
- Add general-specific routes for direct team: ~30-40 lines
- **Total: ~40-50 lines**

### 5.2 Middleware

**File:** `app/Http/Middleware/Role.php`

**Changes Needed:**
- Add 'general' role to dashboard URL mapping
- Update role checking logic to include general where needed

**Update getDashboardUrl method:**
```php
protected function getDashboardUrl(string $role): string
{
    return match($role) {
        'admin' => '/admin/dashboard',
        'general' => '/general/dashboard',  // ADD THIS
        'coordinator' => '/coordinator/dashboard',
        'provincial' => '/provincial/dashboard',
        'executor' => '/executor/dashboard',
        'applicant' => '/executor/dashboard',
        default => '/profile',
    };
}
```

**Estimated Lines to Change:** ~5-10 lines

### 5.3 Services

**File:** `app/Services/ProjectStatusService.php`

**Changes Needed:**
- Update `approve()` method to allow general role (in addition to coordinator)
- Update `forwardToCoordinator()` method to allow general role (in addition to provincial)
- Update `revertByProvincial()` method to allow general role when acting as provincial
- Add new methods or update existing to handle general's dual role

**Update approve method (General must have COMPLETE coordinator functionality):**
```php
public static function approve(Project $project, User $user): bool
{
    // General user has COMPLETE coordinator access - can approve same as coordinator
    if (!in_array($user->role, ['coordinator', 'general'])) {
        throw new Exception('Only coordinator or general users can approve projects.');
    }
    
    // Same validation and business logic for both coordinator and general
    if ($project->status !== ProjectStatus::FORWARDED_TO_COORDINATOR) {
        throw new Exception('Project must be forwarded to coordinator before approval.');
    }
    
    // ... rest of the method (same for both coordinator and general)
}
```

**IMPORTANT:** All coordinator service methods must accept 'general' role with the SAME functionality. General user is treated as coordinator for all coordinator-level actions.

**Update forwardToCoordinator method:**
```php
public static function forwardToCoordinator(Project $project, User $user): bool
{
    if (!in_array($user->role, ['provincial', 'general'])) {  // ADD 'general'
        throw new Exception('Only provincial or general users can forward projects to coordinator.');
    }
    
    // ... rest of the method
}
```

**Update revertByProvincial method:**
```php
public static function revertByProvincial(Project $project, User $user, ?string $reason = null): bool
{
    if (!in_array($user->role, ['provincial', 'general'])) {  // ADD 'general'
        throw new Exception('Only provincial or general users can revert projects.');
    }
    
    // ... rest of the method
}
```

**Estimated Lines to Change:** ~15-20 lines

**File:** `app/Services/ReportStatusService.php`

**Changes Needed (General must have COMPLETE coordinator access):**
- Update `approve()` method to allow general role with **IDENTICAL functionality** as coordinator
- Update `forwardToCoordinator()` method to allow general role when acting as provincial
- Update `revertByProvincial()` method to allow general role when acting as provincial
- Update `revertByCoordinator()` method to allow general role with **IDENTICAL functionality** as coordinator
- **CRITICAL:** General role must be treated as coordinator for ALL coordinator-level actions

**Update approve method (General = Coordinator):**
```php
public static function approve(DPReport $report, User $user): bool
{
    // General has COMPLETE coordinator access - can approve same as coordinator
    if (!in_array($user->role, ['coordinator', 'general'])) {
        throw new \Exception('Only coordinator or general users can approve reports.');
    }
    
    // Same validation and business logic for both coordinator and general
    if ($report->status !== DPReport::STATUS_FORWARDED_TO_COORDINATOR) {
        throw new \Exception('Report must be forwarded to coordinator before approval.');
    }
    
    // ... rest of the method (IDENTICAL for both coordinator and general)
}
```

**IMPORTANT:** All report service methods must treat general role the SAME as coordinator role. No differences.

**Estimated Lines to Change:** ~15-20 lines

**File:** `app/Services/ActivityHistoryService.php`

**Changes Needed:**
- Add method `getForGeneral()` to get activities for general user (combined from coordinators + direct team)
- Update existing methods to include general role where appropriate

**New Method:**
```php
/**
 * Get activity history for general user
 * Combines activities from coordinators and their hierarchy + direct executors/applicants
 *
 * @param User $general
 * @return Collection
 */
public static function getForGeneral(User $general)
{
    // Get coordinator IDs under general
    $coordinatorIds = User::where('parent_id', $general->id)
        ->where('role', 'coordinator')
        ->pluck('id');
    
    // Get all user IDs under coordinators (recursive)
    $allUserIdsUnderCoordinators = self::getAllDescendantUserIds($coordinatorIds);
    
    // Get direct team IDs (executors/applicants directly under general)
    $directTeamIds = User::where('parent_id', $general->id)
        ->whereIn('role', ['executor', 'applicant'])
        ->pluck('id');
    
    // Get project IDs from both sources
    $projectIds = Project::where(function($query) use ($allUserIdsUnderCoordinators, $directTeamIds) {
        $query->whereIn('user_id', $allUserIdsUnderCoordinators)
              ->orWhereIn('in_charge', $allUserIdsUnderCoordinators)
              ->orWhereIn('user_id', $directTeamIds)
              ->orWhereIn('in_charge', $directTeamIds);
    })->pluck('project_id');
    
    // Get report IDs
    $reportIds = DPReport::whereIn('project_id', $projectIds)->pluck('report_id');
    
    // Get activities
    return ActivityHistory::where(function($query) use ($projectIds, $reportIds) {
        $query->where(function($subQuery) use ($projectIds) {
            $subQuery->where('type', 'project')->whereIn('related_id', $projectIds);
        })->orWhere(function($subQuery) use ($reportIds) {
            $subQuery->where('type', 'report')->whereIn('related_id', $reportIds);
        });
    })
    ->with('changedBy')
    ->orderBy('created_at', 'desc')
    ->get();
}

/**
 * Get all descendant user IDs recursively
 *
 * @param Collection $parentIds
 * @return Collection
 */
private static function getAllDescendantUserIds(Collection $parentIds): Collection
{
    if ($parentIds->isEmpty()) {
        return collect();
    }
    
    $children = User::whereIn('parent_id', $parentIds)->pluck('id');
    
    if ($children->isEmpty()) {
        return $parentIds;
    }
    
    return $parentIds->merge(self::getAllDescendantUserIds($children));
}
```

**Estimated Lines to Add/Change:** ~50-70 lines

### 5.4 Helpers

**File:** `app/Helpers/ActivityHistoryHelper.php`

**Changes Needed:**
- Update `canView()` method to include general role
- Update `canViewProjectActivity()` method to include general role
- Update `canViewReportActivity()` method to include general role
- Update `getQueryForUser()` method to include general role

**Update canView method (General = Coordinator for coordinator hierarchy):**
```php
public static function canView(ActivityHistory $activity, User $user): bool
{
    // General has COMPLETE coordinator access - can view ALL activities like coordinator
    // Admin, coordinator, and general can view all activities from their scope
    if (in_array($user->role, ['admin', 'coordinator', 'general'])) {
        // For general: show activities from coordinators + direct team (combined scope)
        // For coordinator: show activities from their hierarchy (same as before)
        // For admin: show all activities (same as before)
        return true;
    }
    
    // ... rest of the method
}
```

**IMPORTANT:** General's view scope is COMBINED (coordinators + direct team), but the permission level is the SAME as coordinator.

**Update canViewProjectActivity method:**
```php
public static function canViewProjectActivity(string $projectId, User $user): bool
{
    $project = Project::where('project_id', $projectId)->first();
    
    if (!$project) {
        return false;
    }
    
    // Admin, coordinator, and general can view all
    if (in_array($user->role, ['admin', 'coordinator', 'general'])) {  // ADD 'general'
        return true;
    }
    
    // ... rest of the method
}
```

**Update getQueryForUser method (General has coordinator-level access with combined scope):**
```php
public static function getQueryForUser(User $user)
{
    $query = ActivityHistory::query();
    
    // General has COMPLETE coordinator access - but with combined scope (coordinators + direct team)
    if (in_array($user->role, ['admin', 'coordinator', 'general'])) {
        // For general: show activities from coordinators + direct team (COMBINED scope)
        // This is MORE than coordinator (coordinator only sees their hierarchy)
        // But the PERMISSION LEVEL is the same (can view all in their scope)
        if ($user->role === 'general') {
            // General sees coordinator hierarchy + direct team = COMBINED scope
            return ActivityHistoryService::getForGeneral($user);
        } elseif ($user->role === 'coordinator') {
            // Coordinator sees their hierarchy only = original behavior
            // No filtering - coordinator sees all activities (existing behavior)
            return $query;
        } else {
            // Admin sees all activities (existing behavior)
            return $query;
        }
    }
    
    // ... rest of the method
}
```

**IMPORTANT:** 
- General's SCOPE is larger (coordinators + direct team) than coordinator's scope (only their hierarchy)
- But General's PERMISSION LEVEL is the same as coordinator (can view all in their scope)
- General has coordinator-level AUTHORIZATION (same permissions, just broader scope)

**Estimated Lines to Change:** ~30-40 lines

**File:** `app/Helpers/ProjectPermissionHelper.php`

**Changes Needed:**
- Update methods to include general role where coordinator/provincial roles are checked
- Add logic to determine if general is acting as coordinator or provincial

**Update canView method (General = Coordinator authorization level):**
```php
public static function canView(Project $project, User $user): bool
{
    // General has COMPLETE coordinator access - can view projects with same authorization
    // Admin, coordinators, and general can view all projects in their scope
    if (in_array($user->role, ['admin', 'coordinator', 'general'])) {
        // For coordinator: can view all projects (existing behavior)
        // For general: can view all projects from coordinators + direct team (COMBINED scope)
        // For admin: can view all projects (existing behavior)
        // AUTHORIZATION level is the same (can view), but SCOPE differs
        return true;
    }
    
    // ... rest of the method
}
```

**IMPORTANT:** General has the SAME authorization level as coordinator (can view), but a BROADER scope (coordinators + direct team).

**Estimated Lines to Change:** ~10-15 lines

### 5.5 Activity History Controller

**File:** `app/Http/Controllers/ActivityHistoryController.php`

**Changes Needed:**
- Update `allActivities()` method to allow general role access
- Optionally add `generalActivities()` method if needed

**Update allActivities method (General has coordinator-level access):**
```php
public function allActivities(Request $request)
{
    $user = Auth::user();
    
    // General has COMPLETE coordinator access - same authorization level
    // Only coordinator, general, and admin can access this route
    if (!in_array($user->role, ['admin', 'coordinator', 'general'])) {
        abort(403, 'Access denied');
    }
    
    // Use appropriate service method based on role
    // General uses combined scope (coordinators + direct team) but SAME authorization
    if ($user->role === 'general') {
        // General sees coordinator hierarchy + direct team (COMBINED scope)
        $activities = ActivityHistoryService::getForGeneral($user);
    } else {
        // Coordinator and admin use existing logic
        $activities = ActivityHistoryService::getWithFilters($request->all(), $user);
    }
    
    return view('activity-history.all-activities', compact('activities'));
}
```

**IMPORTANT:** General has the SAME route access as coordinator (same authorization), but sees MORE data (combined scope).

**Estimated Lines to Change:** ~10-15 lines

### 5.6 Database Seeder (Optional)

**File:** `database/seeders/RolesAndPermissionsSeeder.php`

**Changes Needed:**
- General role already exists, verify it's properly seeded

**Already Exists:**
```php
Role::create(['name' => 'general']);  // Already present
```

**Estimated Lines to Change:** 0 (already exists)

### 5.7 Layout/Views

**File:** `resources/views/general/layouts/app.blade.php` (if needed)

**Purpose:** Main layout for General user views

**Similar to:** `resources/views/coordinator/dashboard.blade.php` or `resources/views/provincial/dashboard.blade.php`

**Estimated Lines:** ~50-100 lines

---

## 6. Routes to Add/Update

### 6.1 Complete Route List for General User

**⚠️ CRITICAL REQUIREMENT: General must have access to ALL coordinator routes AND authorization**

**Implementation Rule:**
- **For Coordinator Hierarchy:** General has IDENTICAL access and authorization as Coordinator
- **For Direct Team:** General has Provincial-level access and authorization
- **All Coordinator Routes:** Must be accessible to General via middleware update
- **All Coordinator Actions:** General can perform ALL actions that Coordinator can perform

### 6.1 Complete Coordinator Routes List (General Must Have Access to ALL)

**✅ COMPLETE LIST: All Coordinator Routes General Must Inherit (34+ routes):**

#### **6.1.1 Coordinator Routes (General has COMPLETE access)**

General user must be able to access **ALL** of the following coordinator routes for the coordinator hierarchy:

**Dashboard (1 route):**
- ✅ `/coordinator/dashboard` - General can access (but should redirect to `/general/dashboard`)

**User Management (7 routes - General manages Coordinators):**
- ✅ `/coordinator/create-provincial` - General can create coordinator
- ✅ `/coordinator/store-provincial` (POST) - General can store coordinator
- ✅ `/coordinator/provincials` - General can list coordinators
- ✅ `/coordinator/provincial/{id}/edit` - General can edit coordinator
- ✅ `/coordinator/provincial/{id}/update` (POST) - General can update coordinator
- ✅ `/coordinator/user/{id}/reset-password` (POST) - General can reset coordinator password
- ✅ `/coordinator/user/{id}/activate` (POST) - General can activate coordinator
- ✅ `/coordinator/user/{id}/deactivate` (POST) - General can deactivate coordinator

**Projects (6 routes - General has COMPLETE coordinator access):**
- ✅ `/coordinator/projects-list` - General can view all projects from coordinators
- ✅ `/coordinator/approved-projects` - General can view approved projects
- ✅ `/coordinator/projects/show/{project_id}` - General can view project details
- ✅ `/projects/{project_id}/approve` (POST) - **General can approve projects** (SAME as coordinator)
- ✅ `/projects/{project_id}/reject` (POST) - **General can reject projects** (SAME as coordinator)
- ✅ `/projects/{project_id}/revert-to-provincial` (POST) - General can revert to coordinator

**Project Comments (3 routes - General has COMPLETE coordinator access):**
- ✅ `/coordinator/projects/{project_id}/add-comment` (POST) - **General can add comments**
- ✅ `/coordinator/projects/comment/{id}/edit` - **General can edit comments**
- ✅ `/coordinator/projects/comment/{id}/update` (POST) - **General can update comments**

**Reports (8 routes - General has COMPLETE coordinator access):**
- ✅ `/coordinator/report-list` - General can view all reports from coordinators
- ✅ `/coordinator/report-list/pending` - General can view pending reports
- ✅ `/coordinator/report-list/approved` - General can view approved reports
- ✅ `/coordinator/report-list/bulk-action` (POST) - **General can bulk actions** (SAME as coordinator)
- ✅ `/coordinator/report/{report_id}/approve` (POST) - **General can approve reports** (SAME as coordinator)
- ✅ `/coordinator/report/{report_id}/revert` (POST) - **General can revert reports** (SAME as coordinator)
- ✅ `/coordinator/reports/{type}/{id}` - General can view report details
- ✅ `/coordinator/reports/monthly/show/{report_id}` - General can view monthly report

**Report Comments (1 route - General has COMPLETE coordinator access):**
- ✅ `/coordinator/reports/monthly/{report_id}/add-comment` (POST) - **General can add comments**

**Budget (3 routes - General has COMPLETE coordinator access):**
- ✅ `/coordinator/budgets` - General can view project budgets
- ✅ `/coordinator/budget-overview` - General can view budget overview
- ✅ `/coordinator/budgets/report` - General can generate budget reports

**Downloads (4 routes - General has COMPLETE coordinator access):**
- ✅ `/coordinator/projects/{project_id}/download-pdf` - **General can download PDF**
- ✅ `/coordinator/projects/{project_id}/download-doc` - **General can download DOC**
- ✅ `/coordinator/reports/monthly/downloadPdf/{report_id}` - **General can download report PDF**
- ✅ `/coordinator/reports/monthly/downloadDoc/{report_id}` - **General can download report DOC**

**Activity History (1 route - General has COMPLETE coordinator access):**
- ✅ `/activities/all-activities` - **General can view all activities** (combined scope)

**Utilities (1 route):**
- ✅ `/coordinator/executors/by-province` - AJAX helper (General can use)

**Total Coordinator Routes General Must Access:** ~34 routes

#### **6.1.2 General-Specific Routes (New - for direct team management)**

**Dashboard (1 route):**
- `/general/dashboard` - General's own dashboard (combined view)

**Direct Team Management (5 routes - when General acts as Provincial):**
- `/general/create-executor` - Create executor/applicant directly under General
- `/general/store-executor` (POST) - Store executor/applicant
- `/general/executors` - List executors/applicants directly under General
- `/general/executor/{id}/edit` - Edit executor/applicant
- `/general/executor/{id}/update` (POST) - Update executor/applicant

**Direct Team Projects (4 routes - Provincial-level actions):**
- `/general/projects-list` - Projects from direct executors/applicants
- `/general/projects/show/{project_id}` - View project from direct team
- `/general/projects/{project_id}/forward-to-coordinator` (POST) - Forward project to coordinator
- `/general/projects/{project_id}/revert-to-executor` (POST) - Revert project to direct executor

**Direct Team Reports (3 routes - Provincial-level actions):**
- `/general/report-list` - Reports from direct executors/applicants
- `/general/report/{report_id}/forward` (POST) - Forward report to coordinator
- `/general/report/{report_id}/revert` (POST) - Revert report to direct executor
- `/general/reports/bulk-forward` (POST) - Bulk forward reports from direct team

**Total General-Specific Routes:** ~13 routes

### 6.2 Route Groups Summary

**Routes General Inherits from Coordinator (34 routes):**
- ✅ All coordinator routes accessible to General via middleware update

**Routes General Gets Directly (13 routes):**
- Dashboard: 1 route
- Direct Team Management: 5 routes
- Direct Team Projects: 4 routes
- Direct Team Reports: 3 routes

**Total Routes General Can Access:** ~47 routes (34 inherited + 13 new)

**Routes to Update:**
- ✅ Dashboard redirect route (add 'general' case)
- ✅ Coordinator routes middleware (change 'coordinator' to 'coordinator,general')
- ✅ Shared routes (if general needs access - see below)

#### **6.1.3 Shared Routes (General must have access like coordinator)**

**⚠️ IMPORTANT: General must also have access to shared routes that coordinator can access**

**Aggregated Reports Routes (Coordinator can access - General must too):**
```php
// Current: Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator'])->group(function () {
// Update to: Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator,general'])->group(function () {
```

**Routes General Must Access (from shared middleware):**
- ✅ `/reports/aggregated/quarterly/index` - View Quarterly Reports
- ✅ `/reports/aggregated/quarterly/create/{project_id}` - Create Quarterly Report
- ✅ `/reports/aggregated/quarterly/show/{report_id}` - View Quarterly Report
- ✅ `/reports/aggregated/quarterly/edit-ai/{report_id}` - Edit Quarterly Report
- ✅ `/reports/aggregated/quarterly/export-pdf/{report_id}` - Export PDF
- ✅ `/reports/aggregated/quarterly/export-word/{report_id}` - Export Word
- ✅ `/reports/aggregated/half-yearly/*` - Same for Half-Yearly Reports (6 routes)
- ✅ `/reports/aggregated/annual/*` - Same for Annual Reports (6 routes)
- ✅ `/reports/aggregated/comparison/*` - Report Comparison Routes (6 routes)

**Total Aggregated Report Routes:** ~18 routes

**Shared Project/Report Routes (Coordinator can access - General must too):**
```php
// Current: Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator'])->group(function () {
// Update to: Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator,general'])->group(function () {
```

**Routes General Must Access (from shared middleware):**
- ✅ `/projects-list` - List all projects
- ✅ `/projects/{project_id}/download-pdf` - Download project PDF
- ✅ `/projects/{project_id}/download-doc` - Download project DOC
- ✅ `/projects/attachments/download/{id}` - Download attachment
- ✅ `/projects/{project_id}/activity-history` - View project activity history
- ✅ `/reports/{report_id}/activity-history` - View report activity history
- ✅ `/reports/monthly/attachments/download/{id}` - Download report attachment
- ✅ `/reports/monthly/show/{report_id}` - View monthly report
- ✅ `/reports/monthly/downloadPdf/{report_id}` - Download report PDF
- ✅ `/reports/monthly/downloadDoc/{report_id}` - Download report DOC

**Total Shared Routes:** ~10 routes

**Profile/Notification Routes (All roles - General must have access):**
- ✅ `/profile` - Edit profile (already shared)
- ✅ `/profile/change-password` - Change password (already shared)
- ✅ `/notifications/*` - All notification routes (already shared)

**Budget Routes (Already shared):**
- ✅ `/budgets/{projectId}` - View budget
- ✅ `/budgets/{projectId}/expenses` (POST) - Add expense
- ✅ `/projects/{project_id}/budget/export/excel` - Export Excel
- ✅ `/projects/{project_id}/budget/export/pdf` - Export PDF
- ✅ `/budgets/report` - Generate budget report

### 6.3 Routes Update Summary

**Routes to Update (Add 'general' to middleware):**

1. **Coordinator Routes (PRIMARY):**
   ```php
   // Change: 'role:coordinator'
   // To: 'role:coordinator,general'
   // This gives General COMPLETE coordinator access to ALL coordinator routes
   ```

2. **Shared Aggregated Reports Routes:**
   ```php
   // Change: 'role:executor,applicant,provincial,coordinator'
   // To: 'role:executor,applicant,provincial,coordinator,general'
   ```

3. **Shared Project/Report Routes:**
   ```php
   // Change: 'role:executor,applicant,provincial,coordinator'
   // To: 'role:executor,applicant,provincial,coordinator,general'
   ```

4. **Dashboard Redirect:**
   ```php
   // Add 'general' => '/general/dashboard' case
   ```

**Total Routes Updates Required:**
- Coordinator routes middleware: 1 change (affects ~34 routes)
- Shared aggregated reports: 1 change (affects ~18 routes)
- Shared project/report routes: 1 change (affects ~10 routes)
- Dashboard redirect: 1 change

**Total Middleware Updates:** ~4 changes (affecting ~62 routes total)

**✅ SUMMARY: General must have access to:**
- ✅ ALL 34+ coordinator routes (via middleware update)
- ✅ ALL 18 aggregated report routes (via middleware update)
- ✅ ALL 10 shared project/report routes (via middleware update)
- ✅ ALL profile/notification routes (already shared)
- ✅ ALL budget routes (already shared)
- ✅ PLUS 13 general-specific routes for direct team management

**Total Routes General Can Access: ~75+ routes**

---

## 7. Dashboard Design

### 7.1 General Dashboard Overview

The General dashboard should be a **two-panel layout** showing:

1. **Left Panel (or Top Section):** Coordinator Management View
2. **Right Panel (or Bottom Section):** Direct Team Management View

### 7.2 Dashboard Sections

#### **Section 1: Quick Stats Cards (Top Row)**

```
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│   Coordinators  │  │  Direct Team    │  │Pending Projects │  │ Pending Reports │
│       5         │  │       12        │  │       23        │  │       18        │
└─────────────────┘  └─────────────────┘  └─────────────────┘  └─────────────────┘
```

#### **Section 2: Coordinator Management Panel**

**Widgets:**
1. **Coordinators Overview:**
   - Total Coordinators Count
   - Active/Inactive Coordinators
   - Coordinators by Province
   - Quick Actions (Add Coordinator, View All)

2. **Pending Approvals from Coordinators:**
   - Pending Projects Count (from coordinators and their hierarchy)
   - Pending Reports Count (from coordinators and their hierarchy)
   - Urgent Items (over 7 days)
   - Quick Actions (Approve, Revert)

3. **Coordinator Performance:**
   - Projects Submitted by Each Coordinator
   - Reports Submitted by Each Coordinator
   - Approval Rate by Coordinator
   - Chart: Coordinator Activity

4. **Coordinator Activity Feed:**
   - Recent activities from coordinators and their hierarchy
   - Latest project submissions
   - Latest report submissions

#### **Section 3: Direct Team Management Panel**

**Widgets:**
1. **Direct Team Overview:**
   - Total Executors/Applicants Count
   - Active/Inactive Members
   - Members by Center
   - Quick Actions (Add Member, View All)

2. **Pending Approvals from Direct Team:**
   - Pending Projects Count (from direct executors/applicants)
   - Pending Reports Count (from direct executors/applicants)
   - Urgent Items (over 7 days)
   - Quick Actions (Forward to Coordinator, Approve, Revert)

3. **Team Performance:**
   - Projects Submitted by Each Member
   - Reports Submitted by Each Member
   - Chart: Center Performance
   - Chart: Project Type Distribution

4. **Team Activity Feed:**
   - Recent activities from direct executors/applicants
   - Latest project submissions
   - Latest report submissions

#### **Section 4: Combined Overview**

**Widgets:**
1. **All Projects Overview:**
   - Total Projects (Combined)
   - Projects by Status Chart
   - Projects by Type Chart
   - Projects by Province Chart
   - Recent Projects List

2. **All Reports Overview:**
   - Total Reports (Combined)
   - Reports by Status Chart
   - Recent Reports List

3. **Budget Overview:**
   - Total Budget (Combined)
   - Budget by Coordinator Chart
   - Budget by Center Chart (direct team)
   - Budget Utilization Chart
   - Budget Summary Table

#### **Section 5: Filters**

**Filter Options:**
- **Source:** Coordinators / Direct Team / All
- **Coordinator:** (Dropdown if source is Coordinators)
- **Province:** (Dropdown)
- **Center:** (Dropdown - for direct team)
- **Project Type:** (Dropdown)
- **Status:** (Dropdown)
- **Date Range:** (From/To)

### 7.3 Dashboard Layout Structure

**Recommended Layout:**
```
┌─────────────────────────────────────────────────────────────┐
│                    GENERAL DASHBOARD                         │
├─────────────────────────────────────────────────────────────┤
│  Quick Stats Cards (4 cards in a row)                       │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────────────┬───────────────────────────┐   │
│  │  COORDINATOR MANAGEMENT │  DIRECT TEAM MANAGEMENT   │   │
│  │  ┌────────────────────┐ │  ┌────────────────────┐   │   │
│  │  │ Coordinators       │ │  │ Direct Team        │   │   │
│  │  │ Overview           │ │  │ Overview           │   │   │
│  │  └────────────────────┘ │  └────────────────────┘   │   │
│  │  ┌────────────────────┐ │  ┌────────────────────┐   │   │
│  │  │ Pending            │ │  │ Pending            │   │   │
│  │  │ Approvals          │ │  │ Approvals          │   │   │
│  │  └────────────────────┘ │  └────────────────────┘   │   │
│  │  ┌────────────────────┐ │  ┌────────────────────┐   │   │
│  │  │ Performance        │ │  │ Performance        │   │   │
│  │  │ Metrics            │ │  │ Metrics            │   │   │
│  │  └────────────────────┘ │  └────────────────────┘   │   │
│  │  ┌────────────────────┐ │  ┌────────────────────┐   │   │
│  │  │ Activity Feed      │ │  │ Activity Feed      │   │   │
│  │  └────────────────────┘ │  └────────────────────┘   │   │
│  └─────────────────────────┴───────────────────────────┘   │
├─────────────────────────────────────────────────────────────┤
│  COMBINED OVERVIEW                                           │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ All Projects Overview                                │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ All Reports Overview                                 │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ Budget Overview                                      │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### 7.4 Dashboard Controller Method Structure

**Method:** `GeneralController::GeneralDashboard()`

**Logic Flow:**
1. Get General user from Auth
2. **Get Coordinator Data:**
   - Get all coordinators under general
   - Get all projects from coordinators and their hierarchy
   - Get all reports from coordinators and their hierarchy
   - Calculate coordinator statistics
3. **Get Direct Team Data:**
   - Get all executors/applicants directly under general
   - Get all projects from direct team
   - Get all reports from direct team
   - Calculate team statistics
4. **Combine Data:**
   - Merge projects (from coordinators + direct team)
   - Merge reports (from coordinators + direct team)
   - Calculate combined statistics
5. **Get Widget Data:**
   - Coordinator overview data
   - Direct team overview data
   - Pending approvals (coordinator + direct team)
   - Performance metrics (coordinator + direct team)
   - Activity feeds (coordinator + direct team)
   - Budget overview (combined)
6. **Apply Filters:**
   - Filter by source (coordinators/direct team/all)
   - Filter by coordinator
   - Filter by province
   - Filter by center
   - Filter by project type
   - Filter by status
7. **Return View:**
   - Pass all data to `general.index` view

**Estimated Lines:** ~500-700 lines

---

## 8. Sidebar Structure

See section 4.8 for complete sidebar structure.

**Key Points:**
- Two-level team management (Coordinators + Direct Team)
- Combined project/report views
- Budget section
- Settings section
- Documentation link

---

## 9. Implementation Phases

### Phase 1: Foundation Setup (8-10 hours)

**Tasks:**
1. ✅ Create GeneralController with basic structure
2. ✅ Add General routes to web.php
3. ✅ Update middleware Role.php to handle general role
4. ✅ Update dashboard redirect route
5. ✅ Create basic General dashboard view
6. ✅ Create General sidebar
7. ✅ Test basic access and navigation

**Files:**
- `app/Http/Controllers/GeneralController.php` (skeleton)
- `routes/web.php` (add routes)
- `app/Http/Middleware/Role.php` (update)
- `resources/views/general/index.blade.php` (basic structure)
- `resources/views/general/sidebar.blade.php`

### Phase 2: Coordinator Management (8-10 hours)

**Tasks:**
1. ✅ Implement coordinator CRUD operations
2. ✅ Create coordinator list view
3. ✅ Create coordinator create/edit views
4. ✅ Implement user management (activate/deactivate, reset password)
5. ✅ Test coordinator management

**Files:**
- `GeneralController.php` (coordinator methods)
- `resources/views/general/coordinators/index.blade.php`
- `resources/views/general/coordinators/create.blade.php`
- `resources/views/general/coordinators/edit.blade.php`

### Phase 3: Direct Team Management (8-10 hours)

**Tasks:**
1. ✅ Implement executor/applicant CRUD operations (direct team)
2. ✅ Create executor/applicant list view
3. ✅ Create executor/applicant create/edit views
4. ✅ Implement user management (activate/deactivate, reset password)
5. ✅ Test direct team management

**Files:**
- `GeneralController.php` (executor methods)
- `resources/views/general/executors/index.blade.php`
- `resources/views/general/executors/create.blade.php`
- `resources/views/general/executors/edit.blade.php`

### Phase 4: Projects Management (6-8 hours)

**Tasks:**
1. ✅ Implement combined project list (coordinators + direct team)
2. ✅ Implement project approval/reject/revert logic
3. ✅ Implement forward to coordinator logic (for direct team projects)
4. ✅ Create project views (list, approved, show)
5. ✅ Test project workflows

**Files:**
- `GeneralController.php` (project methods)
- `app/Services/ProjectStatusService.php` (update for general role)
- `resources/views/general/projects/list.blade.php`
- `resources/views/general/projects/approved.blade.php`
- `resources/views/general/projects/show.blade.php`

### Phase 5: Reports Management (6-8 hours)

**Tasks:**
1. ✅ Implement combined report list (coordinators + direct team)
2. ✅ Implement report approval/revert logic
3. ✅ Implement forward to coordinator logic (for direct team reports)
4. ✅ Implement bulk forward reports
5. ✅ Create report views (list, pending, approved, show)
6. ✅ Test report workflows

**Files:**
- `GeneralController.php` (report methods)
- `app/Services/ReportStatusService.php` (update for general role)
- `resources/views/general/reports/list.blade.php`
- `resources/views/general/reports/pending.blade.php`
- `resources/views/general/reports/approved.blade.php`

### Phase 6: Dashboard Implementation (6-8 hours)

**Tasks:**
1. ✅ Implement GeneralDashboard() method with all widget data
2. ✅ Create dashboard widgets (coordinator panel, direct team panel, combined overview)
3. ✅ Implement filtering logic
4. ✅ Add charts and visualizations
5. ✅ Test dashboard functionality

**Files:**
- `GeneralController.php` (GeneralDashboard method)
- `resources/views/general/index.blade.php` (complete implementation)

### Phase 7: Services & Helpers Updates (4-6 hours)

**Tasks:**
1. ✅ Update ProjectStatusService for general role
2. ✅ Update ReportStatusService for general role
3. ✅ Update ActivityHistoryService for general role
4. ✅ Update ActivityHistoryHelper for general role
5. ✅ Update ProjectPermissionHelper for general role
6. ✅ Test all service updates

**Files:**
- `app/Services/ProjectStatusService.php`
- `app/Services/ReportStatusService.php`
- `app/Services/ActivityHistoryService.php`
- `app/Helpers/ActivityHistoryHelper.php`
- `app/Helpers/ProjectPermissionHelper.php`

### Phase 8: Budget & Additional Features (4-6 hours)

**Tasks:**
1. ✅ Implement budget overview (combined)
2. ✅ Implement project budgets list
3. ✅ Add budget reports export
4. ✅ Implement comments functionality
5. ✅ Add download functionality (PDF/DOC)
6. ✅ Test all features

**Files:**
- `GeneralController.php` (budget methods, comments, downloads)
- `resources/views/general/budgets/overview.blade.php`
- `resources/views/general/budgets/index.blade.php`

### Phase 9: Testing & Refinement (4-6 hours)

**Tasks:**
1. ✅ Comprehensive testing of all features
2. ✅ Fix bugs and issues
3. ✅ Performance optimization
4. ✅ UI/UX improvements
5. ✅ Documentation updates

**Total Estimated Time:** 40-50 hours

---

## 10. Testing Requirements

### 10.1 Unit Tests

**Test Cases:**
1. General user can create coordinator
2. General user can create executor/applicant directly under them
3. General user can view projects from coordinators
4. General user can view projects from direct team
5. General user can approve projects from coordinators
6. General user can forward projects from direct team to coordinator
7. General user can revert projects to coordinators
8. General user can revert projects to direct executors
9. General user can approve reports from coordinators
10. General user can forward reports from direct team to coordinator
11. General user can view activities from coordinators
12. General user can view activities from direct team
13. Dashboard shows correct data for coordinators
14. Dashboard shows correct data for direct team
15. Combined views show correct merged data

### 10.2 Integration Tests

**Test Scenarios:**
1. **Coordinator Workflow:**
   - Executor submits project → Provincial forwards → Coordinator forwards → General approves

2. **Direct Team Workflow:**
   - Executor (direct) submits project → General forwards to Coordinator → Coordinator approves

3. **Mixed Workflow:**
   - Verify projects/reports from both sources appear correctly in combined views

4. **Permission Tests:**
   - General can access all coordinator routes
   - General can access all provincial routes (for direct team)
   - General cannot access routes they shouldn't

### 10.3 Manual Testing Checklist

**Coordinator Management:**
- [ ] Create coordinator user
- [ ] Edit coordinator user
- [ ] Activate/deactivate coordinator
- [ ] Reset coordinator password
- [ ] View coordinator list
- [ ] View coordinator's projects/reports

**Direct Team Management:**
- [ ] Create executor/applicant directly under general
- [ ] Edit executor/applicant
- [ ] Activate/deactivate executor/applicant
- [ ] Reset executor/applicant password
- [ ] View direct team list
- [ ] View direct team's projects/reports

**Projects:**
- [ ] View all projects (combined)
- [ ] View pending projects from coordinators
- [ ] View pending projects from direct team
- [ ] Approve project from coordinator
- [ ] Forward project from direct team to coordinator
- [ ] Revert project to coordinator
- [ ] Revert project to direct executor
- [ ] Download project PDF/DOC
- [ ] Add comment to project

**Reports:**
- [ ] View all reports (combined)
- [ ] View pending reports from coordinators
- [ ] View pending reports from direct team
- [ ] Approve report from coordinator
- [ ] Forward report from direct team to coordinator
- [ ] Bulk forward reports
- [ ] Revert report to coordinator
- [ ] Revert report to direct executor
- [ ] Download report PDF/DOC
- [ ] Add comment to report

**Dashboard:**
- [ ] Dashboard loads correctly
- [ ] Quick stats show correct counts
- [ ] Coordinator panel shows correct data
- [ ] Direct team panel shows correct data
- [ ] Combined overview shows merged data
- [ ] Filters work correctly
- [ ] Charts render correctly

**Budget:**
- [ ] Budget overview shows combined data
- [ ] Budget by coordinator chart works
- [ ] Budget by center chart works (direct team)
- [ ] Budget reports export works

**Sidebar:**
- [ ] All sidebar links work
- [ ] Active state highlights correctly
- [ ] Navigation is smooth

---

## 11. Key Implementation Considerations

### 11.0 CRITICAL: Complete Coordinator Access Implementation

**⚠️ THE MOST IMPORTANT REQUIREMENT: General must have COMPLETE coordinator access**

**Implementation Checklist:**

**✅ Routes:**
- [ ] Update coordinator routes middleware: `'role:coordinator'` → `'role:coordinator,general'`
- [ ] Verify General can access ALL 34+ coordinator routes
- [ ] Test all coordinator routes with General user

**✅ Services:**
- [ ] Update `ProjectStatusService::approve()` - Add 'general' to role check
- [ ] Update `ProjectStatusService::revertByCoordinator()` - Add 'general' to role check
- [ ] Update `ReportStatusService::approve()` - Add 'general' to role check
- [ ] Update `ReportStatusService::revertByCoordinator()` - Add 'general' to role check
- [ ] Update all coordinator service methods to include 'general'

**✅ Helpers:**
- [ ] Update `ActivityHistoryHelper::canView()` - Add 'general' to coordinator checks
- [ ] Update `ActivityHistoryHelper::getQueryForUser()` - Handle general (combined scope)
- [ ] Update `ProjectPermissionHelper::canView()` - Add 'general' to coordinator checks
- [ ] Update all permission helpers to include 'general'

**✅ Controllers:**
- [ ] CoordinatorController methods must work with 'general' role
- [ ] Add role checking logic: if 'general', filter by coordinators under them
- [ ] Same business logic for both 'coordinator' and 'general' roles

**✅ Middleware:**
- [ ] Update Role middleware `getDashboardUrl()` - Add 'general'
- [ ] Update dashboard redirect route - Add 'general' case

**✅ Views:**
- [ ] Coordinator views should work with general role (may need minor adjustments)
- [ ] General dashboard combines coordinator + provincial views

**Verification Rule:**
```php
// ✅ CORRECT - Everywhere coordinator is checked, also check general
if (in_array($user->role, ['coordinator', 'general'])) {
    // Same logic - General has COMPLETE coordinator access
}

// ❌ WRONG - Don't create separate logic
if ($user->role === 'coordinator') {
    // coordinator logic
} elseif ($user->role === 'general') {
    // different logic (WRONG - violates requirement!)
}
```

### 11.1 Dual Role Handling

**Challenge:** General user acts as both coordinator (for coordinators) and provincial (for direct team)

**Solution:**
- **For Coordinator Hierarchy:** General has COMPLETE coordinator access - uses coordinator actions (approve/reject/revert)
- **For Direct Team:** General acts as provincial - uses provincial actions (forward/revert)
- Use conditional logic based on project/report source
- Check project/report user's hierarchy to determine if it's from coordinators or direct team
- Route to appropriate action based on source

**Key Distinction:**
- **Coordinator Hierarchy:** General = Coordinator (SAME authorization level)
- **Direct Team:** General = Provincial (provincial-level actions)

**Example Logic (General has coordinator-level access for coordinator hierarchy):**
```php
// Determine if project is from coordinator hierarchy or direct team
$projectUser = $project->user;
$general = Auth::user();

// Get coordinator IDs under general
$coordinatorIds = User::where('parent_id', $general->id)
    ->where('role', 'coordinator')
    ->pluck('id');

// Get all descendant user IDs under coordinators (recursive)
$allUserIdsUnderCoordinators = $this->getAllDescendantUserIds($coordinatorIds);

// Check if project is from coordinator hierarchy
$isFromCoordinatorHierarchy = in_array($projectUser->id, $allUserIdsUnderCoordinators->toArray()) ||
                              in_array($projectUser->parent_id, $coordinatorIds->toArray()) ||
                              ($projectUser->parent && in_array($projectUser->parent->parent_id, $coordinatorIds->toArray()));

// Check if project is from direct team
$isFromDirectTeam = $projectUser->parent_id === $general->id &&
                    in_array($projectUser->role, ['executor', 'applicant']);

if ($isFromCoordinatorHierarchy) {
    // Project from coordinator hierarchy - General has COMPLETE coordinator access
    // General acts as coordinator - use coordinator actions (approve/reject/revert to coordinator)
    return $this->approveProject($project); // General has SAME approval power as coordinator
} else if ($isFromDirectTeam) {
    // Project from direct team - General acts as provincial
    // Forward to coordinator (or approve if general has final authority)
    return $this->forwardToCoordinator($project); // General acts as provincial
}

// IMPORTANT: General has coordinator-level AUTHORIZATION for coordinator hierarchy
// This means General can approve/reject/revert just like coordinator does
```

### 11.2 Query Optimization

**Challenge:** Need to efficiently query projects/reports from both sources

**Solution:**
- Use optimized queries with proper indexes
- Cache frequently accessed data
- Use eager loading to prevent N+1 queries
- Consider database views or materialized views for complex queries

**Example:**
```php
// Get all project IDs efficiently
$coordinatorIds = User::where('parent_id', $general->id)
    ->where('role', 'coordinator')
    ->pluck('id');

// Get all descendant user IDs (recursive)
$allUserIdsUnderCoordinators = $this->getAllDescendantUserIds($coordinatorIds);

// Get direct team IDs
$directTeamIds = User::where('parent_id', $general->id)
    ->whereIn('role', ['executor', 'applicant'])
    ->pluck('id');

// Single query to get all relevant projects
$projects = Project::where(function($query) use ($allUserIdsUnderCoordinators, $directTeamIds) {
    $query->whereIn('user_id', $allUserIdsUnderCoordinators)
          ->orWhereIn('in_charge', $allUserIdsUnderCoordinators)
          ->orWhereIn('user_id', $directTeamIds)
          ->orWhereIn('in_charge', $directTeamIds);
})
->with(['user.parent', 'reports', 'budgets']) // Eager load relationships
->get();
```

### 11.3 UI/UX Considerations

**Challenge:** Dashboard needs to clearly show both coordinator and direct team data

**Solution:**
- Use clear visual separation (panels, tabs, or sections)
- Use color coding to distinguish between sources
- Add filter options to view coordinator data, direct team data, or combined
- Use icons or badges to indicate source

### 11.4 Permission Inheritance (COMPLETE COORDINATOR ACCESS)

**⚠️ CRITICAL REQUIREMENT:** General user must have **COMPLETE and IDENTICAL** coordinator access and authorization. This means:

1. **ALL Coordinator Routes:** General must be able to access EVERY route that coordinator can access for coordinator hierarchy
2. **ALL Coordinator Service Methods:** General must be able to use EVERY service method that coordinator uses
3. **ALL Coordinator Permissions:** General must have EVERY permission that coordinator has
4. **IDENTICAL Functionality:** General's actions should work EXACTLY the same as coordinator's actions

**Solution:**
- **Reuse coordinator service methods** - General should call the SAME service methods as coordinator
- **Add general role to ALL permission checks** - Wherever coordinator is checked, also check for general
- **Add general to ALL coordinator route middleware** - All coordinator routes must also accept general role
- **Same validation rules** - General follows the same validation as coordinator
- **Same business logic** - General follows the same business logic as coordinator
- **No additional restrictions** - General should have NO restrictions compared to coordinator

**Implementation Pattern:**
```php
// ❌ WRONG - Don't do this
if ($user->role === 'coordinator') {
    // coordinator logic
} elseif ($user->role === 'general') {
    // different general logic (WRONG!)
}

// ✅ CORRECT - Do this instead
if (in_array($user->role, ['coordinator', 'general'])) {
    // Same logic for both coordinator and general
    // General has COMPLETE coordinator access
}
```

**Route Middleware Pattern:**
```php
// ❌ WRONG
Route::middleware(['auth', 'role:coordinator'])->group(function () {
    // coordinator routes
});

// ✅ CORRECT - General must have access to ALL coordinator routes
Route::middleware(['auth', 'role:coordinator,general'])->group(function () {
    // Both coordinator AND general can access these routes
    // General has COMPLETE coordinator access
});
```

---

## 12. Database Considerations

### 12.1 No Schema Changes Required

**Reason:** The existing `users` table structure already supports the hierarchical relationship:
- `parent_id` column exists
- `role` enum includes 'general'
- Foreign key relationship exists

### 12.2 Data Integrity

**Considerations:**
- Ensure coordinators created by general have `parent_id` = general's ID
- Ensure executors/applicants created by general have `parent_id` = general's ID
- Ensure no orphaned users (parent_id pointing to non-existent user)
- Ensure proper cascade deletion if general is deleted

### 12.3 Indexing

**Recommended Indexes:**
- `users.parent_id` (already indexed via foreign key)
- `users.role` (may need index for frequent role-based queries)
- Composite index on `(parent_id, role)` for efficient queries

---

## 13. Security Considerations

### 13.1 Access Control

**Requirements:**
- General user should only see data from their coordinators and direct team
- General user should not see data from other generals' coordinators
- Middleware should properly enforce role-based access
- All routes should have proper authorization checks

### 13.2 Data Isolation

**Implementation:**
```php
// Always filter by parent_id to ensure data isolation
$coordinatorIds = User::where('parent_id', $general->id)
    ->where('role', 'coordinator')
    ->pluck('id');
```

### 13.3 Audit Trail

**Requirements:**
- All actions by general user should be logged
- Activity history should record general's actions
- Status changes should track who performed the action (general acting as coordinator or provincial)

---

## 14. Performance Considerations

### 14.1 Query Optimization

- Use eager loading to prevent N+1 queries
- Use database indexes appropriately
- Cache frequently accessed data (coordinators list, team members list)
- Use pagination for large datasets

### 14.2 Dashboard Loading

- Load dashboard data asynchronously if needed
- Use lazy loading for heavy widgets
- Consider using queues for expensive operations

---

## 15. Rollout Plan

### 15.1 Development Environment

1. Create General user in development database
2. Test all functionality in development
3. Fix bugs and issues
4. Performance testing

### 15.2 Staging Environment

1. Deploy to staging
2. Create test General user
3. Comprehensive testing
4. User acceptance testing (if applicable)

### 15.3 Production Deployment

1. Create General user in production
2. Verify existing coordinators don't break
3. Monitor for issues
4. Gradual rollout if needed

---

## 16. Documentation Updates

### 16.1 Code Documentation

- Update code comments to include general role
- Document dual role functionality
- Document query patterns

### 16.2 User Documentation

- Create user guide for General role
- Document workflow differences
- Document dashboard usage

### 16.3 API Documentation (if applicable)

- Document General-specific endpoints
- Update existing endpoint documentation

---

## 17. Success Criteria

### 17.1 Functional Requirements

- [ ] General user can manage coordinators
- [ ] General user can manage direct executors/applicants
- [ ] General user can view projects from both sources
- [ ] General user can approve projects from coordinators
- [ ] General user can forward projects from direct team to coordinator
- [ ] General user can view reports from both sources
- [ ] General user can approve reports from coordinators
- [ ] General user can forward reports from direct team to coordinator
- [ ] Dashboard shows correct data for both sources
- [ ] All routes are accessible and secure

### 17.2 Performance Requirements

- [ ] Dashboard loads in < 3 seconds
- [ ] Project/report lists load in < 2 seconds
- [ ] No N+1 query issues
- [ ] Database queries optimized

### 17.3 Quality Requirements

- [ ] All tests pass
- [ ] Code follows Laravel best practices
- [ ] No security vulnerabilities
- [ ] UI is intuitive and user-friendly

---

## 18. Future Enhancements

### 18.1 Potential Improvements

1. **Advanced Filtering:**
   - Save filter presets
   - Export filtered data

2. **Notifications:**
   - Real-time notifications for pending approvals
   - Email notifications for important actions

3. **Reporting:**
   - Advanced analytics and reporting
   - Custom report generation

4. **Mobile Support:**
   - Responsive design improvements
   - Mobile app (future)

---

## Appendix A: File Summary

### Files to Create (New)

| File | Lines | Priority |
|------|-------|----------|
| `app/Http/Controllers/GeneralController.php` | ~2500-3000 | High |
| `resources/views/general/index.blade.php` | ~800-1000 | High |
| `resources/views/general/sidebar.blade.php` | ~200-300 | High |
| `resources/views/general/coordinators/index.blade.php` | ~200-300 | High |
| `resources/views/general/coordinators/create.blade.php` | ~150-200 | High |
| `resources/views/general/coordinators/edit.blade.php` | ~150-200 | High |
| `resources/views/general/executors/index.blade.php` | ~200-300 | High |
| `resources/views/general/executors/create.blade.php` | ~150-200 | High |
| `resources/views/general/executors/edit.blade.php` | ~150-200 | High |
| `resources/views/general/projects/list.blade.php` | ~300-400 | High |
| `resources/views/general/projects/approved.blade.php` | ~300-400 | High |
| `resources/views/general/projects/show.blade.php` | ~400-500 | High |
| `resources/views/general/reports/list.blade.php` | ~300-400 | High |
| `resources/views/general/reports/pending.blade.php` | ~300-400 | High |
| `resources/views/general/reports/approved.blade.php` | ~300-400 | High |
| `resources/views/general/budgets/overview.blade.php` | ~400-500 | Medium |
| `resources/views/general/budgets/index.blade.php` | ~300-400 | Medium |

**Total New Files:** 17 files  
**Total Estimated Lines:** ~6000-7500 lines

### Files to Update (Existing)

| File | Changes | Priority |
|------|---------|----------|
| `routes/web.php` | Add ~100-150 lines (general routes) | High |
| `app/Http/Middleware/Role.php` | Update ~5-10 lines | High |
| `app/Services/ProjectStatusService.php` | Update ~15-20 lines | High |
| `app/Services/ReportStatusService.php` | Update ~15-20 lines | High |
| `app/Services/ActivityHistoryService.php` | Add/Update ~50-70 lines | High |
| `app/Helpers/ActivityHistoryHelper.php` | Update ~30-40 lines | High |
| `app/Helpers/ProjectPermissionHelper.php` | Update ~10-15 lines | Medium |
| `app/Http/Controllers/ActivityHistoryController.php` | Update ~10-15 lines | Medium |

**Total Files to Update:** 8 files  
**Total Estimated Changes:** ~235-340 lines

---

## Appendix B: Route Reference

See section 6.1 for complete route list.

**Key Route Patterns:**
- `/general/dashboard` - Main dashboard
- `/general/coordinators/*` - Coordinator management
- `/general/executors/*` - Direct team management
- `/general/projects/*` - Projects
- `/general/reports/*` - Reports
- `/general/budgets/*` - Budget
- `/general/*/download-*` - Downloads

---

---

## 19. FINAL CHECKLIST: Complete Coordinator Access Verification

### ⚠️ CRITICAL VERIFICATION: General Must Have ALL Coordinator Access

Before implementation is considered complete, verify that General user has **COMPLETE and IDENTICAL** coordinator access:

#### **Routes Verification:**
- [ ] ✅ ALL coordinator routes accessible to General (34+ routes)
- [ ] ✅ ALL aggregated report routes accessible to General (18 routes)
- [ ] ✅ ALL shared project/report routes accessible to General (10 routes)
- [ ] ✅ Dashboard redirect includes General
- [ ] ✅ General-specific routes for direct team (13 routes)
- [ ] ✅ Total: General can access ~75+ routes

#### **Service Methods Verification:**
- [ ] ✅ `ProjectStatusService::approve()` - General can approve (same as coordinator)
- [ ] ✅ `ProjectStatusService::reject()` - General can reject (same as coordinator)
- [ ] ✅ `ProjectStatusService::revertByCoordinator()` - General can revert (same as coordinator)
- [ ] ✅ `ReportStatusService::approve()` - General can approve (same as coordinator)
- [ ] ✅ `ReportStatusService::revertByCoordinator()` - General can revert (same as coordinator)
- [ ] ✅ ALL coordinator service methods accept 'general' role

#### **Permission Checks Verification:**
- [ ] ✅ `ActivityHistoryHelper::canView()` - General has coordinator-level access
- [ ] ✅ `ActivityHistoryHelper::getQueryForUser()` - General has combined scope
- [ ] ✅ `ProjectPermissionHelper::canView()` - General has coordinator-level access
- [ ] ✅ ALL permission helpers include 'general' where coordinator is checked

#### **Controller Methods Verification:**
- [ ] ✅ CoordinatorController methods work with 'general' role
- [ ] ✅ General can approve projects (same as coordinator)
- [ ] ✅ General can approve reports (same as coordinator)
- [ ] ✅ General can add/edit comments (same as coordinator)
- [ ] ✅ General can download PDF/DOC (same as coordinator)
- [ ] ✅ General can view all coordinator data (filtered by coordinators under them)

#### **Middleware Verification:**
- [ ] ✅ Role middleware `getDashboardUrl()` includes 'general'
- [ ] ✅ All route middleware includes 'general' where 'coordinator' is present
- [ ] ✅ No routes are blocked for General that Coordinator can access

#### **Dashboard Verification:**
- [ ] ✅ General dashboard shows coordinator data (from coordinators under them)
- [ ] ✅ General dashboard shows direct team data
- [ ] ✅ General dashboard combines both views appropriately
- [ ] ✅ All coordinator dashboard widgets available to General (for coordinator hierarchy)

#### **UI/Views Verification:**
- [ ] ✅ General can access all coordinator views (for coordinator hierarchy)
- [ ] ✅ General has appropriate views for direct team management
- [ ] ✅ Sidebar includes all coordinator sections + general-specific sections
- [ ] ✅ All buttons/actions available to coordinator are available to General

### ✅ Success Criteria

**General user implementation is COMPLETE when:**

1. ✅ General can access ALL coordinator routes for coordinator hierarchy
2. ✅ General can perform ALL coordinator actions (approve, reject, revert, comment, download)
3. ✅ General has SAME authorization level as coordinator for coordinator hierarchy
4. ✅ General can manage coordinators (create, edit, activate/deactivate)
5. ✅ General can manage direct team (create, edit, activate/deactivate executors/applicants)
6. ✅ General can view combined data from coordinators + direct team
7. ✅ All coordinator service methods accept 'general' role
8. ✅ All permission checks include 'general' where coordinator is checked
9. ✅ Dashboard shows combined coordinator + direct team data
10. ✅ No functionality is restricted for General that Coordinator has

### ❌ Common Mistakes to Avoid

1. ❌ **DON'T create separate logic for General** - Use same logic as coordinator
2. ❌ **DON'T restrict General's access** - General has COMPLETE coordinator access
3. ❌ **DON'T forget to update middleware** - Must include 'general' in all coordinator routes
4. ❌ **DON'T forget shared routes** - Aggregated reports, shared projects/reports
5. ❌ **DON'T create different validation** - General follows same validation as coordinator
6. ❌ **DON'T create different business logic** - General follows same business logic as coordinator

### ✅ Implementation Pattern (Always Use This)

```php
// ✅ CORRECT - Always check for both coordinator AND general
if (in_array($user->role, ['coordinator', 'general'])) {
    // Same logic for both - General has COMPLETE coordinator access
    // Only difference: General's scope includes coordinators under them
}

// ❌ WRONG - Don't create separate logic
if ($user->role === 'coordinator') {
    // coordinator logic
} elseif ($user->role === 'general') {
    // different logic - WRONG! General must have SAME logic
}
```

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Author:** AI Code Analysis  
**Status:** Ready for Implementation

**⚠️ REMEMBER: General user MUST have COMPLETE coordinator access - this is not optional, it's a requirement.**
