# Admin User Enhancement Analysis and Implementation Plan
**Date:** January 23, 2026  
**Review Scope:** Complete analysis of admin role access, missing features, and soft delete requirements

---

## Executive Summary

This comprehensive analysis reviews the current admin user access, identifies gaps, and provides a detailed implementation plan for enhancing admin capabilities. **Admin must have COMPLETE access to perform ALL actions that any role can perform**, including acting on behalf of any user, managing all entities, and implementing **soft delete functionality** for projects, reports, and attachments.

**Key Findings:**
- Admin currently has **very limited functionality** (dashboard + catch-all route access)
- **No soft delete** functionality exists for any entities
- **Missing ability to act as other roles** (executor, provincial, coordinator, general)
- **Missing ability to perform actions on behalf of users** (complete projects/reports, approve/revert, submit)
- **Missing user management capabilities** (create users, edit roles, transfer between centers/provinces)
- **Missing province and center management** (create, edit, transfer)
- Admin has **read access** to all data but **NO management capabilities**

---

## 1. Current Admin Access Analysis

### 1.1 Routes and Middleware

#### Current Admin Routes
```php
// routes/web.php lines 119-124
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'adminDashboard'])->name('admin.dashboard');
    Route::get('/admin/logout', [AdminController::class, 'adminLogout'])->name('admin.logout');
    // Admin has access to all other routes, so no need to duplicate routes here
});

// Catch-all route (lines 622-627)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::any('{all}', function () {
        return view('admin.dashboard');
    })->where('all', '.*');
});
```

**Analysis:**
- ✅ Admin has dedicated dashboard route
- ✅ Admin has logout functionality
- ✅ Admin has catch-all route access (can access any route)
- ❌ **No specific admin management routes** for projects/reports/users
- ❌ **No admin CRUD operations** defined

---

### 1.2 Controller Methods

#### AdminController (`app/Http/Controllers/AdminController.php`)
**Current Methods:**
- `adminDashboard()` - Returns dashboard view
- `adminLogout()` - Handles logout

**Missing Methods:**
- ❌ **Act as Executor:** Create, edit, submit projects and reports on behalf of users
- ❌ **Act as Provincial:** Forward, revert projects and reports
- ❌ **Act as Coordinator:** Approve, reject, revert projects and reports
- ❌ **Act as General:** All general user capabilities
- ❌ Project management (list, view, edit, delete, restore, complete)
- ❌ Report management (list, view, edit, delete, restore, submit, forward, approve)
- ❌ User management (list, create, edit, delete, activate/deactivate, change roles, transfer)
- ❌ Province management (create, edit, delete, assign users)
- ❌ Center management (create, edit, delete, transfer between provinces)
- ❌ Attachment management (view, delete, restore)
- ❌ System configuration
- ❌ Bulk operations
- ❌ Soft delete operations
- ❌ Restore operations
- ❌ **Override all permission checks** (admin can bypass status/ownership restrictions)

---

### 1.3 Views

#### Current Admin Views
- `resources/views/admin/dashboard.blade.php` - Basic template dashboard (static content)
- `resources/views/admin/sidebar.blade.php` - Sidebar navigation (limited links)

**Missing Views:**
- ❌ Admin projects list view
- ❌ Admin projects management view
- ❌ Admin reports list view
- ❌ Admin reports management view
- ❌ Admin users management view
- ❌ Admin attachments management view
- ❌ Admin soft deleted items view
- ❌ Admin restore interface
- ❌ Admin system configuration view

---

### 1.4 Access Permissions

#### Project Access (via ProjectPermissionHelper)
```php
// app/Helpers/ProjectPermissionHelper.php line 65-74
public static function canView(Project $project, User $user): bool
{
    // Admin and coordinators can view all projects
    if (in_array($user->role, ['admin', 'coordinator', 'provincial'])) {
        return true;
    }
    // ...
}
```

**Current Admin Permissions:**
- ✅ **Can view all projects** (via ProjectPermissionHelper)
- ✅ **Can download all projects** (via ExportController - line 343-344)
- ✅ **Can view all activities** (via ActivityHistoryController - line 67)
- ✅ **Can access aggregated reports** (quarterly, half-yearly, annual)
- ❌ **Cannot edit projects** (no admin-specific edit permission, cannot override status restrictions)
- ❌ **Cannot complete projects** (cannot mark as completed on behalf of users)
- ❌ **Cannot submit projects** (cannot submit to provincial on behalf of users)
- ❌ **Cannot approve/revert projects** (cannot act as coordinator/provincial)
- ❌ **Cannot create/edit reports** (cannot act as executor)
- ❌ **Cannot submit/forward reports** (cannot act as executor/provincial)
- ❌ **Cannot approve/revert reports** (cannot act as coordinator/provincial)
- ❌ **Cannot delete projects/reports** (no delete functionality)
- ❌ **Cannot manage users** (no admin user management interface)
- ❌ **Cannot change user roles** (no role editing capability)
- ❌ **Cannot transfer users** (no center/province transfer capability)
- ❌ **Cannot manage provinces** (no province CRUD)
- ❌ **Cannot manage centers** (no center CRUD)

---

### 1.5 Models and Database

#### Current Model Status
**Projects:**
- `app/Models/OldProjects/Project.php` - **NO SoftDeletes trait**
- Uses standard `delete()` method (hard delete)

**Reports:**
- `app/Models/Reports/Monthly/DPReport.php` - **NO SoftDeletes trait**
- `app/Models/Reports/Quarterly/QuarterlyReport.php` - **NO SoftDeletes trait**
- `app/Models/Reports/HalfYearly/HalfYearlyReport.php` - **NO SoftDeletes trait**
- `app/Models/Reports/Annual/AnnualReport.php` - **NO SoftDeletes trait**

**Attachments:**
- `app/Models/OldProjects/ProjectAttachment.php` - **NO SoftDeletes trait**
- `app/Models/Reports/Monthly/ReportAttachment.php` - **NO SoftDeletes trait**
- `app/Models/OldProjects/IIES/ProjectIIESAttachmentFile.php` - **NO SoftDeletes trait**
- `app/Models/OldProjects/IES/ProjectIESAttachmentFile.php` - **NO SoftDeletes trait**
- `app/Models/OldProjects/IAH/ProjectIAHDocumentFile.php` - **NO SoftDeletes trait**
- `app/Models/OldProjects/ILP/ProjectILPDocumentFile.php` - **NO SoftDeletes trait**

**Database Migrations:**
- ❌ **No `deleted_at` columns** in any project/report/attachment tables
- ❌ **No soft delete migrations** exist

---

## 2. Required Admin Enhancements

### 2.0 Admin Acting as All Roles (CRITICAL REQUIREMENT)

**Admin must be able to perform ALL actions that ANY role can perform, including acting on behalf of any user.**

#### 2.0.1 Acting as Executor/Applicant
**Admin must be able to:**
1. **Create Projects on Behalf of Users**
   - Select any user (executor/applicant) to act as
   - Create projects for that user
   - Fill all project details (objectives, activities, budget, attachments)
   - Save as draft or submit directly

2. **Edit Projects on Behalf of Users**
   - Edit any project regardless of status
   - Override status restrictions (admin can edit even approved projects)
   - Update all project fields
   - Add/remove attachments

3. **Submit Projects on Behalf of Users**
   - Submit projects to provincial (bypass normal ownership checks)
   - Can submit from any status (admin override)
   - Mark projects as completed

4. **Create Reports on Behalf of Users**
   - Select any user and their approved project
   - Create monthly/quarterly/half-yearly/annual reports
   - Fill all report details (objectives, activities, expenses, photos, attachments)

5. **Edit Reports on Behalf of Users**
   - Edit any report regardless of status
   - Override status restrictions
   - Update all report fields

6. **Submit Reports on Behalf of Users**
   - Submit reports to provincial
   - Can submit from any status (admin override)

#### 2.0.2 Acting as Provincial
**Admin must be able to:**
1. **Forward Projects to Coordinator**
   - Forward any project that is submitted to provincial
   - Can forward from any status (admin override)
   - Add PMC comments

2. **Revert Projects to Executor/Applicant**
   - Revert projects to any level (executor, applicant)
   - Add revert comments
   - Can revert from any status (admin override)

3. **Forward Reports to Coordinator**
   - Forward any report that is submitted to provincial
   - Bulk forward multiple reports
   - Add PMC comments

4. **Revert Reports to Executor/Applicant**
   - Revert reports to any level
   - Add revert comments
   - Can revert from any status (admin override)

#### 2.0.3 Acting as Coordinator
**Admin must be able to:**
1. **Approve Projects**
   - Approve any project forwarded to coordinator
   - Set commencement date (if required)
   - Can approve from any status (admin override)

2. **Reject Projects**
   - Reject projects with reason
   - Can reject from any status (admin override)

3. **Revert Projects**
   - Revert to provincial or coordinator level
   - Add revert comments
   - Can revert from any status (admin override)

4. **Approve Reports**
   - Approve any report forwarded to coordinator
   - Can approve from any status (admin override)

5. **Revert Reports**
   - Revert to provincial or coordinator level
   - Add revert comments
   - Can revert from any status (admin override)

6. **Bulk Operations**
   - Bulk approve projects/reports
   - Bulk revert projects/reports

#### 2.0.4 Acting as General
**Admin must be able to:**
1. **All General User Capabilities**
   - Approve projects/reports as coordinator (with context selection)
   - Approve projects/reports as provincial (with context selection)
   - Revert projects/reports to any level
   - Manage provinces and centers
   - Assign users to provinces
   - Transfer centers between provinces

#### 2.0.5 User Management
**Admin must be able to:**
1. **Create Users**
   - Create users with any role (executor, applicant, provincial, coordinator, general, admin)
   - Assign parent_id (hierarchy)
   - Assign province and center
   - Set initial password

2. **Edit Users**
   - Edit any user's details (name, email, role, province, center, parent)
   - Change user roles (e.g., executor to provincial, provincial to coordinator)
   - Update user hierarchy (change parent_id)
   - Update province and center assignments

3. **Transfer Users**
   - Transfer users between centers
   - Transfer users between provinces
   - Transfer centers between provinces (with child users option)
   - Update child users recursively when transferring

4. **User Status Management**
   - Activate/deactivate users
   - Reset passwords
   - Soft delete users
   - Restore deleted users

#### 2.0.6 Province and Center Management
**Admin must be able to:**
1. **Province Management**
   - Create provinces
   - Edit provinces
   - Delete provinces (with validation)
   - Assign provincial users to provinces
   - View all users in a province

2. **Center Management**
   - Create centers (assign to province)
   - Edit centers
   - Delete centers (with validation)
   - Transfer centers between provinces
   - View all users in a center

#### 2.0.7 Permission Override
**Admin must be able to:**
1. **Bypass All Permission Checks**
   - Override ProjectPermissionHelper checks
   - Override status restrictions (can edit/approve/revert from any status)
   - Override ownership checks (can act on behalf of any user)
   - Override role restrictions in services (ProjectStatusService, ReportStatusService)

2. **Access All Data**
   - View all projects (regardless of ownership)
   - View all reports (regardless of ownership)
   - View all users (regardless of hierarchy)
   - View all activity history
   - View all comments

---

### 2.1 Project Management

#### Missing Functionality
1. **Project List View**
   - View all projects (with filters by type, status, province, user)
   - Search functionality
   - Sort by various fields
   - Pagination

2. **Project Management Actions**
   - View project details
   - Edit any project (override status restrictions)
   - Soft delete projects
   - Restore soft deleted projects
   - Permanent delete projects
   - Bulk operations (bulk delete, bulk restore, bulk status change)

3. **Project Attachments Management**
   - View all project attachments
   - Delete project attachments
   - Restore deleted attachments
   - Download attachments

---

### 2.2 Report Management

#### Missing Functionality
1. **Report List View**
   - View all reports (monthly, quarterly, half-yearly, annual)
   - Filter by project, user, status, date range
   - Search functionality
   - Sort and pagination

2. **Report Management Actions**
   - View report details
   - Edit any report (override status restrictions)
   - Soft delete reports
   - Restore soft deleted reports
   - Permanent delete reports
   - Bulk operations

3. **Report Attachments Management**
   - View all report attachments
   - Delete report attachments
   - Restore deleted attachments
   - Download attachments

---

### 2.3 User Management

#### Missing Functionality
1. **User List View**
   - View all users (with filters by role, province, status)
   - Search functionality
   - Sort and pagination

2. **User Management Actions**
   - Create users (all roles)
   - Edit users
   - Activate/Deactivate users
   - Reset passwords
   - Delete users (soft delete)
   - Restore deleted users
   - View user activity history
   - Assign users to provinces/centers

**Note:** Some user management exists in GeneralController and CoordinatorController, but **no admin-specific interface**.

---

### 2.4 System Configuration

#### Missing Functionality
1. **System Settings**
   - System-wide configuration
   - Feature toggles
   - System preferences

2. **System Monitoring**
   - System health dashboard
   - Performance metrics
   - Error logs viewer
   - Activity monitoring

---

## 3. Soft Delete Implementation Requirements

### 3.1 Database Changes Required

#### Projects Table
**Migration Required:**
```php
Schema::table('projects', function (Blueprint $table) {
    $table->softDeletes();
});
```

**Models to Update:**
- `app/Models/OldProjects/Project.php` - Add `use SoftDeletes;`

#### Reports Tables
**Migrations Required:**
1. `DP_Reports` table
2. `quarterly_reports` table
3. `half_yearly_reports` table
4. `annual_reports` table

**Models to Update:**
- `app/Models/Reports/Monthly/DPReport.php`
- `app/Models/Reports/Quarterly/QuarterlyReport.php`
- `app/Models/Reports/HalfYearly/HalfYearlyReport.php`
- `app/Models/Reports/Annual/AnnualReport.php`

#### Attachment Tables
**Migrations Required:**
1. `project_attachments` table
2. `report_attachments` table
3. `project_IIES_attachment_files` table
4. `project_IES_attachment_files` table
5. `project_IAH_document_files` table
6. `project_ILP_document_files` table

**Models to Update:**
- `app/Models/OldProjects/ProjectAttachment.php`
- `app/Models/Reports/Monthly/ReportAttachment.php`
- `app/Models/OldProjects/IIES/ProjectIIESAttachmentFile.php`
- `app/Models/OldProjects/IES/ProjectIESAttachmentFile.php`
- `app/Models/OldProjects/IAH/ProjectIAHDocumentFile.php`
- `app/Models/OldProjects/ILP/ProjectILPDocumentFile.php`

---

### 3.2 Controller Methods Required

#### AdminController Methods Needed

**Project Management:**
```php
// Project listing and management
public function projects(Request $request) // List all projects with filters
public function showProject($project_id) // View project details
public function editProject($project_id) // Edit any project
public function softDeleteProject($project_id) // Soft delete project
public function restoreProject($project_id) // Restore soft deleted project
public function permanentDeleteProject($project_id) // Permanent delete
public function bulkDeleteProjects(Request $request) // Bulk soft delete
public function bulkRestoreProjects(Request $request) // Bulk restore
public function deletedProjects() // List soft deleted projects
```

**Report Management:**
```php
// Report listing and management
public function reports(Request $request) // List all reports
public function showReport($report_id, $type) // View report details
public function editReport($report_id, $type) // Edit any report
public function softDeleteReport($report_id, $type) // Soft delete report
public function restoreReport($report_id, $type) // Restore soft deleted report
public function permanentDeleteReport($report_id, $type) // Permanent delete
public function bulkDeleteReports(Request $request) // Bulk soft delete
public function bulkRestoreReports(Request $request) // Bulk restore
public function deletedReports() // List soft deleted reports
```

**Attachment Management:**
```php
// Project attachments
public function projectAttachments($project_id) // List project attachments
public function softDeleteProjectAttachment($attachment_id) // Soft delete
public function restoreProjectAttachment($attachment_id) // Restore
public function permanentDeleteProjectAttachment($attachment_id) // Permanent delete

// Report attachments
public function reportAttachments($report_id) // List report attachments
public function softDeleteReportAttachment($attachment_id) // Soft delete
public function restoreReportAttachment($attachment_id) // Restore
public function permanentDeleteReportAttachment($attachment_id) // Permanent delete
```

**User Management:**
```php
public function users(Request $request) // List all users
public function createUser() // Show create form
public function storeUser(Request $request) // Create user
public function editUser($user_id) // Edit user
public function updateUser(Request $request, $user_id) // Update user
public function softDeleteUser($user_id) // Soft delete user
public function restoreUser($user_id) // Restore user
public function activateUser($user_id) // Activate user
public function deactivateUser($user_id) // Deactivate user
public function resetPassword($user_id) // Reset password
```

---

### 3.3 Routes Required

#### Admin Routes to Add
```php
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Projects
    Route::get('/projects', [AdminController::class, 'projects'])->name('projects.index');
    Route::get('/projects/deleted', [AdminController::class, 'deletedProjects'])->name('projects.deleted');
    Route::get('/projects/{project_id}', [AdminController::class, 'showProject'])->name('projects.show');
    Route::get('/projects/{project_id}/edit', [AdminController::class, 'editProject'])->name('projects.edit');
    Route::put('/projects/{project_id}', [AdminController::class, 'updateProject'])->name('projects.update');
    Route::delete('/projects/{project_id}', [AdminController::class, 'softDeleteProject'])->name('projects.delete');
    Route::post('/projects/{project_id}/restore', [AdminController::class, 'restoreProject'])->name('projects.restore');
    Route::delete('/projects/{project_id}/permanent', [AdminController::class, 'permanentDeleteProject'])->name('projects.permanent-delete');
    Route::post('/projects/bulk-delete', [AdminController::class, 'bulkDeleteProjects'])->name('projects.bulk-delete');
    Route::post('/projects/bulk-restore', [AdminController::class, 'bulkRestoreProjects'])->name('projects.bulk-restore');
    
    // Reports
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports.index');
    Route::get('/reports/deleted', [AdminController::class, 'deletedReports'])->name('reports.deleted');
    Route::get('/reports/{type}/{report_id}', [AdminController::class, 'showReport'])->name('reports.show');
    Route::get('/reports/{type}/{report_id}/edit', [AdminController::class, 'editReport'])->name('reports.edit');
    Route::put('/reports/{type}/{report_id}', [AdminController::class, 'updateReport'])->name('reports.update');
    Route::delete('/reports/{type}/{report_id}', [AdminController::class, 'softDeleteReport'])->name('reports.delete');
    Route::post('/reports/{type}/{report_id}/restore', [AdminController::class, 'restoreReport'])->name('reports.restore');
    Route::delete('/reports/{type}/{report_id}/permanent', [AdminController::class, 'permanentDeleteReport'])->name('reports.permanent-delete');
    Route::post('/reports/bulk-delete', [AdminController::class, 'bulkDeleteReports'])->name('reports.bulk-delete');
    Route::post('/reports/bulk-restore', [AdminController::class, 'bulkRestoreReports'])->name('reports.bulk-restore');
    
    // Attachments
    Route::get('/projects/{project_id}/attachments', [AdminController::class, 'projectAttachments'])->name('projects.attachments');
    Route::delete('/attachments/project/{attachment_id}', [AdminController::class, 'softDeleteProjectAttachment'])->name('attachments.project.delete');
    Route::post('/attachments/project/{attachment_id}/restore', [AdminController::class, 'restoreProjectAttachment'])->name('attachments.project.restore');
    Route::delete('/attachments/project/{attachment_id}/permanent', [AdminController::class, 'permanentDeleteProjectAttachment'])->name('attachments.project.permanent-delete');
    
    Route::get('/reports/{report_id}/attachments', [AdminController::class, 'reportAttachments'])->name('reports.attachments');
    Route::delete('/attachments/report/{attachment_id}', [AdminController::class, 'softDeleteReportAttachment'])->name('attachments.report.delete');
    Route::post('/attachments/report/{attachment_id}/restore', [AdminController::class, 'restoreReportAttachment'])->name('attachments.report.restore');
    Route::delete('/attachments/report/{attachment_id}/permanent', [AdminController::class, 'permanentDeleteReportAttachment'])->name('attachments.report.permanent-delete');
    
    // Users
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user_id}/edit', [AdminController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user_id}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user_id}', [AdminController::class, 'softDeleteUser'])->name('users.delete');
    Route::post('/users/{user_id}/restore', [AdminController::class, 'restoreUser'])->name('users.restore');
    Route::post('/users/{user_id}/activate', [AdminController::class, 'activateUser'])->name('users.activate');
    Route::post('/users/{user_id}/deactivate', [AdminController::class, 'deactivateUser'])->name('users.deactivate');
    Route::post('/users/{user_id}/reset-password', [AdminController::class, 'resetPassword'])->name('users.reset-password');
    
    // Admin Acting as Executor/Applicant
    Route::get('/projects/create-as-user/{user_id}', [AdminController::class, 'createProjectAsUser'])->name('projects.create-as-user');
    Route::post('/projects/create-as-user/{user_id}', [AdminController::class, 'storeProjectAsUser'])->name('projects.store-as-user');
    Route::get('/projects/{project_id}/edit-as-user/{user_id}', [AdminController::class, 'editProjectAsUser'])->name('projects.edit-as-user');
    Route::put('/projects/{project_id}/update-as-user/{user_id}', [AdminController::class, 'updateProjectAsUser'])->name('projects.update-as-user');
    Route::post('/projects/{project_id}/submit-as-user/{user_id}', [AdminController::class, 'submitProjectAsUser'])->name('projects.submit-as-user');
    Route::post('/projects/{project_id}/complete-as-user/{user_id}', [AdminController::class, 'markProjectCompletedAsUser'])->name('projects.complete-as-user');
    
    Route::get('/reports/create-as-user/{user_id}', [AdminController::class, 'createReportAsUser'])->name('reports.create-as-user');
    Route::post('/reports/create-as-user/{user_id}', [AdminController::class, 'storeReportAsUser'])->name('reports.store-as-user');
    Route::get('/reports/{type}/{report_id}/edit-as-user/{user_id}', [AdminController::class, 'editReportAsUser'])->name('reports.edit-as-user');
    Route::put('/reports/{type}/{report_id}/update-as-user/{user_id}', [AdminController::class, 'updateReportAsUser'])->name('reports.update-as-user');
    Route::post('/reports/{type}/{report_id}/submit-as-user/{user_id}', [AdminController::class, 'submitReportAsUser'])->name('reports.submit-as-user');
    
    // Admin Acting as Provincial
    Route::post('/projects/{project_id}/forward-as-provincial', [AdminController::class, 'forwardProjectAsProvincial'])->name('projects.forward-as-provincial');
    Route::post('/projects/{project_id}/revert-as-provincial', [AdminController::class, 'revertProjectAsProvincial'])->name('projects.revert-as-provincial');
    Route::post('/reports/{type}/{report_id}/forward-as-provincial', [AdminController::class, 'forwardReportAsProvincial'])->name('reports.forward-as-provincial');
    Route::post('/reports/{type}/{report_id}/revert-as-provincial', [AdminController::class, 'revertReportAsProvincial'])->name('reports.revert-as-provincial');
    Route::post('/reports/bulk-forward-as-provincial', [AdminController::class, 'bulkForwardReportsAsProvincial'])->name('reports.bulk-forward-as-provincial');
    
    // Admin Acting as Coordinator
    Route::post('/projects/{project_id}/approve-as-coordinator', [AdminController::class, 'approveProjectAsCoordinator'])->name('projects.approve-as-coordinator');
    Route::post('/projects/{project_id}/reject-as-coordinator', [AdminController::class, 'rejectProjectAsCoordinator'])->name('projects.reject-as-coordinator');
    Route::post('/projects/{project_id}/revert-as-coordinator', [AdminController::class, 'revertProjectAsCoordinator'])->name('projects.revert-as-coordinator');
    Route::post('/reports/{type}/{report_id}/approve-as-coordinator', [AdminController::class, 'approveReportAsCoordinator'])->name('reports.approve-as-coordinator');
    Route::post('/reports/{type}/{report_id}/revert-as-coordinator', [AdminController::class, 'revertReportAsCoordinator'])->name('reports.revert-as-coordinator');
    Route::post('/projects/bulk-approve-as-coordinator', [AdminController::class, 'bulkApproveProjectsAsCoordinator'])->name('projects.bulk-approve-as-coordinator');
    Route::post('/projects/bulk-revert-as-coordinator', [AdminController::class, 'bulkRevertProjectsAsCoordinator'])->name('projects.bulk-revert-as-coordinator');
    Route::post('/reports/bulk-approve-as-coordinator', [AdminController::class, 'bulkApproveReportsAsCoordinator'])->name('reports.bulk-approve-as-coordinator');
    Route::post('/reports/bulk-revert-as-coordinator', [AdminController::class, 'bulkRevertReportsAsCoordinator'])->name('reports.bulk-revert-as-coordinator');
    
    // Admin Acting as General
    Route::post('/projects/{project_id}/approve-as-general', [AdminController::class, 'approveProjectAsGeneral'])->name('projects.approve-as-general');
    Route::post('/projects/{project_id}/revert-as-general', [AdminController::class, 'revertProjectAsGeneral'])->name('projects.revert-as-general');
    Route::post('/reports/{type}/{report_id}/approve-as-general', [AdminController::class, 'approveReportAsGeneral'])->name('reports.approve-as-general');
    Route::post('/reports/{type}/{report_id}/revert-as-general', [AdminController::class, 'revertReportAsGeneral'])->name('reports.revert-as-general');
    
    // User Management (Role Changes and Transfers)
    Route::post('/users/{user_id}/change-role', [AdminController::class, 'changeUserRole'])->name('users.change-role');
    Route::post('/users/{user_id}/transfer-center', [AdminController::class, 'transferUserCenter'])->name('users.transfer-center');
    Route::post('/users/{user_id}/transfer-province', [AdminController::class, 'transferUserProvince'])->name('users.transfer-province');
    
    // Province Management
    Route::get('/provinces', [AdminController::class, 'provinces'])->name('provinces.index');
    Route::get('/provinces/create', [AdminController::class, 'createProvince'])->name('provinces.create');
    Route::post('/provinces', [AdminController::class, 'storeProvince'])->name('provinces.store');
    Route::get('/provinces/{province_id}/edit', [AdminController::class, 'editProvince'])->name('provinces.edit');
    Route::put('/provinces/{province_id}', [AdminController::class, 'updateProvince'])->name('provinces.update');
    Route::delete('/provinces/{province_id}', [AdminController::class, 'deleteProvince'])->name('provinces.delete');
    Route::post('/provinces/{province_id}/assign-provincial', [AdminController::class, 'assignProvincialToProvince'])->name('provinces.assign-provincial');
    
    // Center Management
    Route::get('/centers', [AdminController::class, 'centers'])->name('centers.index');
    Route::get('/centers/create', [AdminController::class, 'createCenter'])->name('centers.create');
    Route::post('/centers', [AdminController::class, 'storeCenter'])->name('centers.store');
    Route::get('/centers/{center_id}/edit', [AdminController::class, 'editCenter'])->name('centers.edit');
    Route::put('/centers/{center_id}', [AdminController::class, 'updateCenter'])->name('centers.update');
    Route::delete('/centers/{center_id}', [AdminController::class, 'deleteCenter'])->name('centers.delete');
    Route::post('/centers/{center_id}/transfer', [AdminController::class, 'transferCenter'])->name('centers.transfer');
});
```

---

## 4. Implementation Plan

### Phase 1: Database and Model Updates (Priority: HIGH)

#### Task 1.1: Add Soft Deletes to Projects
**Files to Modify:**
1. Create migration: `database/migrations/YYYY_MM_DD_HHMMSS_add_soft_deletes_to_projects_table.php`
2. Update model: `app/Models/OldProjects/Project.php`

**Changes:**
```php
// Migration
Schema::table('projects', function (Blueprint $table) {
    $table->softDeletes();
});

// Model
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;
    // ...
}
```

**Estimated Time:** 1 hour

---

#### Task 1.2: Add Soft Deletes to Reports
**Files to Modify:**
1. Create migrations for each report table
2. Update all report models

**Tables:**
- `DP_Reports`
- `quarterly_reports`
- `half_yearly_reports`
- `annual_reports`

**Estimated Time:** 2 hours

---

#### Task 1.3: Add Soft Deletes to Attachments
**Files to Modify:**
1. Create migrations for each attachment table
2. Update all attachment models

**Tables:**
- `project_attachments`
- `report_attachments`
- `project_IIES_attachment_files`
- `project_IES_attachment_files`
- `project_IAH_document_files`
- `project_ILP_document_files`

**Estimated Time:** 2 hours

---

### Phase 2: AdminController Enhancement (Priority: CRITICAL)

#### Task 2.0: Admin Acting as All Roles (MUST IMPLEMENT FIRST)
**Methods to Add:**

**Acting as Executor/Applicant:**
- `createProjectAsUser($userId)` - Create project on behalf of user
- `editProjectAsUser($projectId, $userId)` - Edit project on behalf of user
- `submitProjectAsUser($projectId, $userId)` - Submit project on behalf of user
- `markProjectCompletedAsUser($projectId, $userId)` - Mark completed on behalf of user
- `createReportAsUser($userId, $projectId)` - Create report on behalf of user
- `editReportAsUser($reportId, $userId)` - Edit report on behalf of user
- `submitReportAsUser($reportId, $userId)` - Submit report on behalf of user

**Acting as Provincial:**
- `forwardProjectAsProvincial($projectId)` - Forward project to coordinator
- `revertProjectAsProvincial($projectId, $request)` - Revert project to executor/applicant
- `forwardReportAsProvincial($reportId)` - Forward report to coordinator
- `revertReportAsProvincial($reportId, $request)` - Revert report to executor/applicant
- `bulkForwardReportsAsProvincial($request)` - Bulk forward reports

**Acting as Coordinator:**
- `approveProjectAsCoordinator($projectId, $request)` - Approve project
- `rejectProjectAsCoordinator($projectId, $request)` - Reject project
- `revertProjectAsCoordinator($projectId, $request)` - Revert project
- `approveReportAsCoordinator($reportId)` - Approve report
- `revertReportAsCoordinator($reportId, $request)` - Revert report
- `bulkApproveProjectsAsCoordinator($request)` - Bulk approve projects
- `bulkApproveReportsAsCoordinator($request)` - Bulk approve reports
- `bulkRevertProjectsAsCoordinator($request)` - Bulk revert projects
- `bulkRevertReportsAsCoordinator($request)` - Bulk revert reports

**Acting as General:**
- `approveProjectAsGeneral($projectId, $request)` - Approve with context selection
- `approveReportAsGeneral($reportId, $request)` - Approve with context selection
- `revertProjectAsGeneral($projectId, $request)` - Revert to any level
- `revertReportAsGeneral($reportId, $request)` - Revert to any level

**User Management:**
- `createUser($request)` - Create user with any role
- `editUser($userId)` - Edit user form
- `updateUser($userId, $request)` - Update user (including role changes)
- `changeUserRole($userId, $request)` - Change user role
- `transferUserCenter($userId, $request)` - Transfer user between centers
- `transferUserProvince($userId, $request)` - Transfer user between provinces
- `transferCenter($centerId, $request)` - Transfer center between provinces

**Province and Center Management:**
- `createProvince($request)` - Create province
- `editProvince($provinceId)` - Edit province form
- `updateProvince($provinceId, $request)` - Update province
- `deleteProvince($provinceId)` - Delete province
- `createCenter($request)` - Create center
- `editCenter($centerId)` - Edit center form
- `updateCenter($centerId, $request)` - Update center
- `deleteCenter($centerId)` - Delete center
- `assignProvincialToProvince($provinceId, $request)` - Assign provincial users

**Permission Override Helpers:**
- `canAdminOverride()` - Check if current user is admin
- `overrideProjectPermissions($project, $user)` - Override project permissions
- `overrideReportPermissions($report, $user)` - Override report permissions
- `overrideStatusRestrictions($entity, $action)` - Override status restrictions

**Estimated Time:** 40 hours

---

#### Task 2.1: Project Management Methods
**Methods to Add:**
- `projects()` - List with filters
- `showProject()` - View details
- `editProject()` - Edit form
- `updateProject()` - Update handler
- `softDeleteProject()` - Soft delete
- `restoreProject()` - Restore
- `permanentDeleteProject()` - Permanent delete
- `deletedProjects()` - List deleted
- `bulkDeleteProjects()` - Bulk delete
- `bulkRestoreProjects()` - Bulk restore

**Estimated Time:** 8 hours

---

#### Task 2.2: Report Management Methods
**Methods to Add:**
- Similar methods as projects but for reports
- Handle different report types (monthly, quarterly, half-yearly, annual)

**Estimated Time:** 8 hours

---

#### Task 2.3: Attachment Management Methods
**Methods to Add:**
- Project attachment management
- Report attachment management
- Soft delete, restore, permanent delete for attachments

**Estimated Time:** 4 hours

---

#### Task 2.4: User Management Methods
**Methods to Add:**
- User CRUD operations
- User activation/deactivation
- Password reset
- User activity viewing

**Estimated Time:** 6 hours

---

### Phase 3: Service Layer Updates for Admin Override (Priority: CRITICAL)

#### Task 3.1: Update ProjectStatusService
**Files to Modify:**
- `app/Services/ProjectStatusService.php`

**Changes Required:**
1. **Add Admin Override Checks:**
   - Modify `submitToProvincial()` to allow admin to submit from any status
   - Modify `forwardToCoordinator()` to allow admin to forward from any status
   - Modify `approve()` to allow admin to approve from any status
   - Modify `revertByProvincial()` to allow admin to revert from any status
   - Modify `revertByCoordinator()` to allow admin to revert from any status

**Example:**
```php
public static function submitToProvincial(Project $project, User $user): bool
{
    // Admin can bypass permission and status checks
    if ($user->role !== 'admin') {
        if (!ProjectPermissionHelper::canSubmit($project, $user)) {
            throw new Exception('User does not have permission to submit this project.');
        }
        if (!ProjectStatus::isSubmittable($project->status)) {
            throw new Exception('Project cannot be submitted in current status: ' . $project->status);
        }
    }
    // ... rest of method
}
```

**Estimated Time:** 4 hours

---

#### Task 3.2: Update ReportStatusService
**Files to Modify:**
- `app/Services/ReportStatusService.php`

**Changes Required:**
1. **Add Admin Override Checks:**
   - Modify `submitToProvincial()` to allow admin to submit from any status
   - Modify `forwardToCoordinator()` to allow admin to forward from any status
   - Modify `approve()` to allow admin to approve from any status
   - Modify `revertByProvincial()` to allow admin to revert from any status
   - Modify `revertByCoordinator()` to allow admin to revert from any status

**Estimated Time:** 4 hours

---

#### Task 3.3: Update ProjectPermissionHelper
**Files to Modify:**
- `app/Helpers/ProjectPermissionHelper.php`

**Changes Required:**
1. **Add Admin Override Methods:**
   - Modify `canView()` to always return true for admin
   - Modify `canEdit()` to always return true for admin
   - Modify `canSubmit()` to always return true for admin
   - Modify `canDelete()` to always return true for admin

**Example:**
```php
public static function canEdit(Project $project, User $user): bool
{
    // Admin can edit any project regardless of status or ownership
    if ($user->role === 'admin') {
        return true;
    }
    // ... existing logic
}
```

**Estimated Time:** 2 hours

---

#### Task 3.4: Update Controllers to Allow Admin Override
**Files to Modify:**
- `app/Http/Controllers/Projects/ProjectController.php`
- `app/Http/Controllers/Reports/Monthly/ReportController.php`
- All other project/report controllers

**Changes Required:**
1. **Add Admin Checks:**
   - Allow admin to edit projects/reports regardless of status
   - Allow admin to bypass ownership checks
   - Allow admin to perform any action

**Example:**
```php
public function edit($project_id)
{
    $project = Project::findOrFail($project_id);
    $user = Auth::user();
    
    // Admin can edit any project, otherwise check permissions
    if ($user->role !== 'admin' && !ProjectPermissionHelper::canEdit($project, $user)) {
        abort(403, 'You do not have permission to edit this project.');
    }
    
    // Check status (admin can override)
    if ($user->role !== 'admin' && !in_array($project->status, ['draft', 'reverted'])) {
        abort(403, 'Project cannot be edited in current status.');
    }
    
    // ... rest of method
}
```

**Estimated Time:** 8 hours

---

### Phase 4: Views Creation (Priority: MEDIUM)

#### Task 3.1: Admin Dashboard Enhancement
**Files to Create/Update:**
- `resources/views/admin/dashboard.blade.php` - Enhanced with real data
- Add statistics cards (total projects, reports, users)
- Add recent activity feed
- Add quick action buttons

**Estimated Time:** 4 hours

---

#### Task 3.2: Project Management Views
**Files to Create:**
- `resources/views/admin/projects/index.blade.php` - Project list
- `resources/views/admin/projects/show.blade.php` - Project details
- `resources/views/admin/projects/edit.blade.php` - Edit form
- `resources/views/admin/projects/deleted.blade.php` - Deleted projects list

**Estimated Time:** 8 hours

---

#### Task 3.3: Report Management Views
**Files to Create:**
- `resources/views/admin/reports/index.blade.php` - Report list
- `resources/views/admin/reports/show.blade.php` - Report details
- `resources/views/admin/reports/edit.blade.php` - Edit form
- `resources/views/admin/reports/deleted.blade.php` - Deleted reports list

**Estimated Time:** 8 hours

---

#### Task 3.4: User Management Views
**Files to Create:**
- `resources/views/admin/users/index.blade.php` - User list
- `resources/views/admin/users/create.blade.php` - Create form
- `resources/views/admin/users/edit.blade.php` - Edit form
- `resources/views/admin/users/show.blade.php` - User details

**Estimated Time:** 6 hours

---

#### Task 3.5: Attachment Management Views
**Files to Create:**
- `resources/views/admin/attachments/project.blade.php` - Project attachments
- `resources/views/admin/attachments/report.blade.php` - Report attachments

**Estimated Time:** 4 hours

---

### Phase 5: Sidebar and Navigation (Priority: MEDIUM)

#### Task 4.1: Update Admin Sidebar
**File to Update:**
- `resources/views/admin/sidebar.blade.php`

**Add Menu Items:**
- Projects Management
- Reports Management
- Users Management
- Attachments Management
- Deleted Items (with counts)
- System Configuration

**Estimated Time:** 2 hours

---

### Phase 6: Authorization and Permissions (Priority: HIGH)

#### Task 5.1: Update Permission Helpers
**Files to Update:**
- `app/Helpers/ProjectPermissionHelper.php` - Add admin checks
- Create `app/Helpers/AdminPermissionHelper.php` - Admin-specific permissions

**Changes:**
```php
// ProjectPermissionHelper
public static function canEdit(Project $project, User $user): bool
{
    // Admin can always edit
    if ($user->role === 'admin') {
        return true;
    }
    // ... existing logic
}

public static function canDelete(Project $project, User $user): bool
{
    // Only admin can delete
    return $user->role === 'admin';
}
```

**Estimated Time:** 2 hours

---

### Phase 7: JavaScript and Frontend (Priority: LOW)

#### Task 6.1: Admin Dashboard JavaScript
**Files to Create:**
- `public/js/admin/dashboard.js` - Dashboard interactions
- `public/js/admin/projects.js` - Project management
- `public/js/admin/reports.js` - Report management
- `public/js/admin/users.js` - User management

**Features:**
- AJAX for bulk operations
- Confirmation dialogs for delete/restore
- Real-time updates
- Search and filter functionality

**Estimated Time:** 6 hours

---

## 5. Detailed Feature Specifications

### 5.1 Soft Delete Functionality

#### 5.1.1 Soft Delete Behavior
**Requirements:**
1. **Soft Delete:**
   - Sets `deleted_at` timestamp
   - Hides from normal queries
   - Preserves all data
   - Can be restored

2. **Restore:**
   - Removes `deleted_at` timestamp
   - Makes item visible again
   - Preserves all relationships

3. **Permanent Delete:**
   - Only accessible to admin
   - Requires confirmation
   - Deletes from database
   - Deletes associated files from storage
   - Cannot be undone

#### 5.1.2 Query Scopes
**Models Need:**
```php
// In Project model
public function scopeWithTrashed($query)
{
    return $query->withTrashed();
}

public function scopeOnlyTrashed($query)
{
    return $query->onlyTrashed();
}
```

#### 5.1.3 File Handling
**When Soft Deleting:**
- Files remain in storage
- Database record marked as deleted
- Files accessible via admin restore

**When Permanently Deleting:**
- Delete files from storage
- Delete database record
- Log deletion for audit

---

### 5.2 Admin Project Management

#### 5.2.1 Project List View
**Features:**
- Table with columns: Project ID, Title, Type, Status, User, Province, Created, Actions
- Filters: Type, Status, Province, User, Date Range
- Search: By project ID, title, user name
- Sort: By any column
- Pagination: 25/50/100 per page
- Bulk actions: Delete, Restore, Change Status
- Export: CSV, Excel

**Actions Available:**
- View (eye icon)
- Edit (pencil icon)
- Soft Delete (trash icon)
- Download PDF (download icon)
- Download DOC (file icon)

#### 5.2.2 Deleted Projects View
**Features:**
- List only soft deleted projects
- Show deletion date and deleted by
- Actions: Restore, Permanent Delete, View
- Bulk restore option

---

### 5.3 Admin Report Management

#### 5.3.1 Report List View
**Features:**
- Tabs for: Monthly, Quarterly, Half-Yearly, Annual
- Table with columns: Report ID, Project, Type, Status, User, Date, Actions
- Filters: Project, User, Status, Date Range, Report Type
- Search functionality
- Sort and pagination
- Bulk actions

**Actions Available:**
- View
- Edit
- Soft Delete
- Download PDF/DOC
- Restore (if deleted)

#### 5.3.2 Deleted Reports View
**Features:**
- List soft deleted reports by type
- Show deletion information
- Restore and permanent delete options

---

### 5.4 Admin Attachment Management

#### 5.4.1 Project Attachments View
**Features:**
- List all attachments for a project
- Group by attachment type
- Show file info: Name, Size, Upload Date, Uploaded By
- Actions: View, Download, Soft Delete, Restore
- Bulk operations

#### 5.4.2 Report Attachments View
**Features:**
- List all attachments for a report
- Similar to project attachments
- Group by report type

---

### 5.5 Admin User Management

#### 5.5.1 User List View
**Features:**
- Table with columns: Name, Email, Role, Province, Status, Created, Actions
- Filters: Role, Province, Status
- Search: By name, email
- Sort and pagination

**Actions Available:**
- View Details
- Edit
- Activate/Deactivate
- Reset Password
- Soft Delete
- Restore (if deleted)

#### 5.5.2 Create/Edit User Form
**Features:**
- All user fields
- Role selection (all roles available)
- Province assignment
- Center assignment
- Status selection
- Password field (for create/edit)

---

## 6. Security Considerations

### 6.1 Authorization Checks
**Required:**
- All admin routes must check `role:admin` middleware
- Controller methods must verify admin role
- Views must check admin role before showing actions
- API endpoints must validate admin role

### 6.2 Audit Logging
**Required:**
- Log all admin actions:
  - Project deletions/restorations
  - Report deletions/restorations
  - Attachment deletions/restorations
  - User management actions
  - Permanent deletions

**Implementation:**
- Use ActivityHistoryService
- Create AdminActivityLog model
- Log: action, entity_type, entity_id, admin_user_id, timestamp, details

### 6.3 Confirmation Dialogs
**Required:**
- Soft delete: "Are you sure you want to delete this item?"
- Permanent delete: "WARNING: This action cannot be undone. Are you absolutely sure?"
- Bulk operations: Show count of items affected
- Restore: "Are you sure you want to restore this item?"

---

## 7. Testing Requirements

### 7.1 Unit Tests
**Required Tests:**
- Soft delete functionality
- Restore functionality
- Permanent delete functionality
- Query scopes (withTrashed, onlyTrashed)
- File deletion on permanent delete

### 7.2 Integration Tests
**Required Tests:**
- Admin can soft delete projects
- Admin can restore projects
- Admin can permanently delete projects
- Admin can manage reports
- Admin can manage attachments
- Admin can manage users
- Authorization checks work correctly

### 7.3 Manual Testing Checklist
- [ ] Admin can view all projects
- [ ] Admin can soft delete projects
- [ ] Soft deleted projects don't appear in normal lists
- [ ] Admin can view deleted projects
- [ ] Admin can restore projects
- [ ] Admin can permanently delete projects
- [ ] Files are deleted on permanent delete
- [ ] Same tests for reports
- [ ] Same tests for attachments
- [ ] User management works
- [ ] Authorization prevents non-admin access

---

## 8. Migration Strategy

### 8.1 Database Migration Order
1. **Add soft deletes to projects table**
2. **Add soft deletes to reports tables** (all types)
3. **Add soft deletes to attachment tables** (all types)
4. **Run migrations in production** (non-destructive, adds columns)

### 8.2 Code Deployment Order
1. **Deploy model updates** (add SoftDeletes trait)
2. **Deploy migrations**
3. **Deploy AdminController methods**
4. **Deploy routes**
5. **Deploy views**
6. **Deploy JavaScript**

### 8.3 Rollback Plan
- Migrations can be rolled back (removes deleted_at columns)
- Code changes can be reverted
- No data loss (soft delete preserves data)

---

## 9. Priority Matrix

### Priority 1 (Critical - Implement First)
1. ✅ **Add soft deletes to database** (migrations)
2. ✅ **Update models with SoftDeletes trait**
3. ✅ **AdminController project management methods**
4. ✅ **AdminController report management methods**
5. ✅ **AdminController attachment management methods**
6. ✅ **Admin routes for projects/reports/attachments**

### Priority 2 (High - Implement Second)
7. ✅ **Admin project management views**
8. ✅ **Admin report management views**
9. ✅ **Admin attachment management views**
10. ✅ **Update admin sidebar navigation**
11. ✅ **Authorization helper updates**

### Priority 3 (Medium - Implement Third)
12. ⚠️ **Admin user management methods**
13. ⚠️ **Admin user management views**
14. ⚠️ **Enhanced admin dashboard**
15. ⚠️ **Bulk operations**

### Priority 4 (Low - Implement Last)
16. ⚠️ **JavaScript enhancements**
17. ⚠️ **Export functionality**
18. ⚠️ **Advanced filtering**
19. ⚠️ **System configuration**

---

## 10. Estimated Timeline

### Phase 1: Database & Models (Week 1)
- **Days 1-2:** Migrations and model updates
- **Testing:** 1 day
- **Total:** 3 days

### Phase 2: Controller Methods (Week 2-3)
- **Days 1-3:** Project management methods
- **Days 4-6:** Report management methods
- **Days 7-8:** Attachment management methods
- **Day 9:** User management methods
- **Testing:** 2 days
- **Total:** 11 days

### Phase 3: Views (Week 4-5)
- **Days 1-3:** Project views
- **Days 4-6:** Report views
- **Days 7-8:** Attachment views
- **Days 9-10:** User views
- **Day 11:** Dashboard enhancement
- **Testing:** 2 days
- **Total:** 13 days

### Phase 4: Integration & Polish (Week 6)
- **Days 1-2:** Sidebar and navigation
- **Days 3-4:** Authorization updates
- **Days 5-6:** JavaScript enhancements
- **Testing:** 2 days
- **Total:** 8 days

**Total Estimated Time:** ~35 working days (~7 weeks)

---

## 11. Code Examples

### 11.1 Soft Delete Migration Example
```php
<?php
// database/migrations/YYYY_MM_DD_HHMMSS_add_soft_deletes_to_projects_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
```

---

### 11.2 Model Update Example
```php
<?php
// app/Models/OldProjects/Project.php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;
    
    // ... existing code
}
```

---

### 11.3 AdminController Method Example
```php
<?php
// app/Http/Controllers/AdminController.php

public function softDeleteProject($project_id)
{
    $this->authorize('admin'); // Ensure admin role
    
    try {
        $project = Project::findOrFail($project_id);
        
        // Log the action
        ActivityHistoryService::logAdminAction(
            auth()->user(),
            'soft_delete_project',
            $project_id,
            ['project_title' => $project->project_title]
        );
        
        $project->delete(); // Soft delete
        
        return redirect()->route('admin.projects.index')
            ->with('success', 'Project soft deleted successfully.');
    } catch (\Exception $e) {
        Log::error('Admin soft delete project failed', [
            'project_id' => $project_id,
            'error' => $e->getMessage()
        ]);
        
        return redirect()->back()
            ->with('error', 'Failed to delete project.');
    }
}

public function restoreProject($project_id)
{
    $this->authorize('admin');
    
    try {
        $project = Project::onlyTrashed()->findOrFail($project_id);
        
        ActivityHistoryService::logAdminAction(
            auth()->user(),
            'restore_project',
            $project_id,
            ['project_title' => $project->project_title]
        );
        
        $project->restore();
        
        return redirect()->route('admin.projects.index')
            ->with('success', 'Project restored successfully.');
    } catch (\Exception $e) {
        return redirect()->back()
            ->with('error', 'Failed to restore project.');
    }
}

public function permanentDeleteProject($project_id)
{
    $this->authorize('admin');
    
    try {
        $project = Project::onlyTrashed()->findOrFail($project_id);
        
        // Delete associated files
        $this->deleteProjectFiles($project);
        
        ActivityHistoryService::logAdminAction(
            auth()->user(),
            'permanent_delete_project',
            $project_id,
            ['project_title' => $project->project_title]
        );
        
        $project->forceDelete(); // Permanent delete
        
        return redirect()->route('admin.projects.deleted')
            ->with('success', 'Project permanently deleted.');
    } catch (\Exception $e) {
        return redirect()->back()
            ->with('error', 'Failed to permanently delete project.');
    }
}
```

---

## 12. Summary of Current vs Required

### Current Admin Capabilities
| Feature | Status | Notes |
|---------|--------|-------|
| Dashboard Access | ✅ | Basic template |
| View All Projects | ✅ | Via ProjectPermissionHelper |
| Download Projects | ✅ | Via ExportController |
| View All Activities | ✅ | Via ActivityHistoryController |
| View Aggregated Reports | ✅ | Quarterly, Half-Yearly, Annual |
| Edit Projects | ❌ | No admin-specific edit |
| Delete Projects | ❌ | No delete functionality |
| Manage Reports | ❌ | No admin report management |
| Manage Users | ❌ | No admin user interface |
| Manage Attachments | ❌ | No admin attachment management |
| Soft Delete | ❌ | Not implemented |
| Restore | ❌ | Not implemented |
| Bulk Operations | ❌ | Not implemented |

### Required Admin Capabilities
| Feature | Priority | Estimated Time |
|---------|----------|----------------|
| **Act as Executor/Applicant** | **CRITICAL** | 20 hours |
| **Act as Provincial** | **CRITICAL** | 12 hours |
| **Act as Coordinator** | **CRITICAL** | 15 hours |
| **Act as General** | **CRITICAL** | 10 hours |
| **User Management (Create, Edit Roles, Transfer)** | **CRITICAL** | 15 hours |
| **Province/Center Management** | **CRITICAL** | 10 hours |
| **Permission Override System** | **CRITICAL** | 8 hours |
| Soft Delete (Projects) | HIGH | 3 hours |
| Soft Delete (Reports) | HIGH | 4 hours |
| Soft Delete (Attachments) | HIGH | 3 hours |
| Project Management UI | HIGH | 8 hours |
| Report Management UI | HIGH | 8 hours |
| Attachment Management UI | MEDIUM | 4 hours |
| User Management UI | MEDIUM | 6 hours |
| Restore Functionality | HIGH | 4 hours |
| Permanent Delete | HIGH | 3 hours |
| Bulk Operations | MEDIUM | 6 hours |
| Enhanced Dashboard | MEDIUM | 4 hours |
| Authorization Updates | HIGH | 2 hours |

**Total Estimated Time:** ~150 hours

---

## 14. Critical Requirements Summary

### Admin Must Have Complete Access

**The admin user MUST be able to:**

1. ✅ **Perform ALL actions that ANY role can perform**
   - Create, edit, submit, complete projects on behalf of any user
   - Create, edit, submit reports on behalf of any user
   - Approve, reject, revert projects/reports as coordinator
   - Forward, revert projects/reports as provincial
   - All general user capabilities

2. ✅ **Act on behalf of ANY user**
   - Select any user and perform actions as that user
   - Override ownership checks
   - Override status restrictions
   - Override permission checks

3. ✅ **Manage ALL users**
   - Create users with any role
   - Change user roles (executor → provincial → coordinator, etc.)
   - Transfer users between centers/provinces
   - Manage user hierarchy (parent_id)

4. ✅ **Manage ALL provinces and centers**
   - Create, edit, delete provinces
   - Create, edit, delete centers
   - Transfer centers between provinces
   - Assign users to provinces/centers

5. ✅ **Override ALL restrictions**
   - Bypass status checks (can edit/approve from any status)
   - Bypass ownership checks (can act on behalf of any user)
   - Bypass permission checks (can perform any action)
   - Bypass role restrictions in services

6. ✅ **Soft delete and restore**
   - Soft delete projects, reports, attachments
   - Restore deleted items
   - Permanent delete (with confirmation)

**This is NOT optional - admin MUST have these capabilities to effectively manage the system.**

---

## 13. Action Items Checklist

### Immediate Actions (This Week)
- [ ] **CRITICAL:** Update ProjectStatusService to allow admin override
- [ ] **CRITICAL:** Update ReportStatusService to allow admin override
- [ ] **CRITICAL:** Update ProjectPermissionHelper to allow admin override
- [ ] **CRITICAL:** Add admin methods for acting as executor/applicant
- [ ] **CRITICAL:** Add admin methods for acting as provincial
- [ ] **CRITICAL:** Add admin methods for acting as coordinator
- [ ] **CRITICAL:** Add admin methods for acting as general
- [ ] **CRITICAL:** Add admin methods for user management (create, edit roles, transfer)
- [ ] **CRITICAL:** Add admin methods for province/center management
- [ ] Create migrations for soft deletes (projects, reports, attachments)
- [ ] Update all models with SoftDeletes trait
- [ ] Add admin project management methods to AdminController
- [ ] Add admin report management methods to AdminController
- [ ] Add admin routes for projects and reports

### Short-term Actions (Next 2 Weeks)
- [ ] Create admin project management views
- [ ] Create admin report management views
- [ ] Create admin attachment management views
- [ ] Update admin sidebar navigation
- [ ] Add restore functionality
- [ ] Add permanent delete functionality

### Medium-term Actions (Next Month)
- [ ] Create admin user management interface
- [ ] Enhance admin dashboard with real data
- [ ] Add bulk operations
- [ ] Add JavaScript enhancements
- [ ] Add export functionality

### Long-term Actions (Future)
- [ ] System configuration interface
- [ ] Advanced analytics dashboard
- [ ] Audit log viewer
- [ ] System health monitoring

---

## 14. Conclusion

The admin role currently has **very limited functionality** despite having access to all routes. The implementation plan above provides a comprehensive roadmap to:

1. **Add soft delete functionality** to projects, reports, and attachments
2. **Create dedicated admin management interfaces** for all entities
3. **Implement restore and permanent delete** capabilities
4. **Enhance admin dashboard** with real data and statistics
5. **Add user management** capabilities for admin

**Total Implementation Effort:** ~35 working days (~7 weeks)

**Priority:** HIGH - Admin users need these capabilities for effective system management.

---

**Review Completed By:** AI Code Review System  
**Review Date:** January 23, 2026  
**Next Review Recommended:** After Phase 1 implementation
