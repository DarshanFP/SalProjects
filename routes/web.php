<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\ExecutorController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Projects\AttachmentController;
use App\Http\Controllers\Projects\BudgetController;
use App\Http\Controllers\Projects\EduRUTAnnexedTargetGroupController;
use App\Http\Controllers\Projects\EduRUTTargetGroupController;
use App\Http\Controllers\Projects\ExportController;
use App\Http\Controllers\Projects\OldDevelopmentProjectController;
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Projects\ProjectEduRUTBasicInfoController;
use App\Http\Controllers\Projects\BudgetExportController;
use App\Http\Controllers\ProvincialController;
use App\Http\Controllers\Reports\Monthly\ExportReportController;
use App\Http\Controllers\Reports\Monthly\LivelihoodAnnexureController;
use App\Http\Controllers\Reports\Monthly\MonthlyDevelopmentProjectController;
use App\Http\Controllers\Reports\Monthly\ReportAttachmentController;
use App\Http\Controllers\Reports\Monthly\ReportController;
use App\Http\Controllers\Reports\Quarterly\DevelopmentLivelihoodController;
use App\Http\Controllers\Reports\Quarterly\DevelopmentProjectController;
use App\Http\Controllers\Reports\Quarterly\InstitutionalSupportController;
use App\Http\Controllers\Reports\Quarterly\SkillTrainingController;
use App\Http\Controllers\Reports\Quarterly\WomenInDistressController;
use App\Http\Controllers\Reports\Aggregated\AggregatedQuarterlyReportController;
use App\Http\Controllers\Reports\Aggregated\AggregatedHalfYearlyReportController;
use App\Http\Controllers\Reports\Aggregated\AggregatedAnnualReportController;
use App\Http\Controllers\Reports\Aggregated\ReportComparisonController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ActivityHistoryController;
use App\Http\Controllers\ProvinceFilterController;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/logout', function () {
    Auth::logout();
    return redirect('/login');
})->name('logout');

// Default redirect to dashboard based on role
Route::get('/dashboard', function () {
    $user = Auth::user();
    $role = $user->role;

    Log::info('Dashboard route - Redirecting based on role', [
        'user_id' => $user->id,
        'role' => $role,
        'current_url' => request()->fullUrl(),
    ]);

    $url = match($role) {
        'admin' => '/admin/dashboard',
        'general' => '/general/dashboard', // General has own dashboard
        'coordinator' => '/coordinator/dashboard',
        'provincial' => '/provincial/dashboard',
        'executor' => '/executor/dashboard',
        'applicant' => '/executor/dashboard', // Applicants get same access as executors
        default => '/profile', // Fallback to profile for unknown roles instead of login to prevent loops
    };

    Log::info('Dashboard route - Redirecting to', [
        'redirect_url' => $url,
        'role' => $role,
    ]);

    return redirect($url);
})->middleware(['auth'])->name('dashboard');

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');
    Route::post('/profile/update-password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
});

// Notification routes - accessible to all authenticated users
Route::prefix('notifications')->name('notifications.')->middleware('auth')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
    Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
    Route::get('/recent', [NotificationController::class, 'recent'])->name('recent');
    Route::post('/preferences', [NotificationController::class, 'updatePreferences'])->name('preferences.update');
});

// Download routes accessible to all authenticated users
Route::middleware('auth')->group(function () {
    Route::get('/budgets/{project_id}', [BudgetController::class, 'viewBudget']);
    Route::post('/budgets/{project_id}/expenses', [BudgetController::class, 'addExpense']);

    // Budget Export Routes
    Route::get('/projects/{project_id}/budget/export/excel', [BudgetExportController::class, 'exportExcel'])->name('projects.budget.export.excel');
    Route::get('/projects/{project_id}/budget/export/pdf', [BudgetExportController::class, 'exportPdf'])->name('projects.budget.export.pdf');
    Route::get('/budgets/report', [BudgetExportController::class, 'generateReport'])->name('budgets.report');
});

// Auth routes
require __DIR__.'/auth.php';

// Admin routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'adminDashboard'])->name('admin.dashboard');
    Route::get('/admin/logout', [AdminController::class, 'adminLogout'])->name('admin.logout');
    // Admin has access to all other routes, so no need to duplicate routes here
});

// Coordinator routes (General has COMPLETE coordinator access - same authorization level)
Route::middleware(['auth', 'role:coordinator,general'])->group(function () {
    // Manage Provincials
    Route::get('/coordinator/create-provincial', [CoordinatorController::class, 'createProvincial'])->name('coordinator.createProvincial');
    Route::post('/coordinator/create-provincial', [CoordinatorController::class, 'storeProvincial'])->name('coordinator.storeProvincial');
    Route::get('/coordinator/provincials', [CoordinatorController::class, 'listProvincials'])->name('coordinator.provincials');
    Route::get('/coordinator/provincial/{id}/edit', [CoordinatorController::class, 'editProvincial'])->name('coordinator.editProvincial');
    Route::post('/coordinator/provincial/{id}/update', [CoordinatorController::class, 'updateProvincial'])->name('coordinator.updateProvincial');
    Route::post('/coordinator/user/{id}/reset-password', [CoordinatorController::class, 'resetUserPassword'])->name('coordinator.resetUserPassword');
    Route::post('/coordinator/user/{id}/activate', [CoordinatorController::class, 'activateUser'])->name('coordinator.activateUser');
    Route::post('/coordinator/user/{id}/deactivate', [CoordinatorController::class, 'deactivateUser'])->name('coordinator.deactivateUser');

    //  Routes for Coordinator Dashboard
    Route::get('/coordinator/dashboard', [CoordinatorController::class, 'coordinatorDashboard'])->name('coordinator.dashboard');
    Route::post('/coordinator/dashboard/refresh', [CoordinatorController::class, 'refreshDashboard'])->name('coordinator.dashboard.refresh');
    // Project List
    Route::get('/coordinator/projects-list', [CoordinatorController::class, 'projectList'])->name('coordinator.projects.list');
    // Approved Projects
    Route::get('/coordinator/approved-projects', [CoordinatorController::class, 'approvedProjects'])->name('coordinator.approved.projects');

    // Add this route for coordinator to show a project
    Route::get('/coordinator/projects/show/{project_id}', [CoordinatorController::class, 'showProject'])->name('coordinator.projects.show');
    // routes for Project Comments
    Route::post('/coordinator/projects/{project_id}/add-comment', [CoordinatorController::class, 'addProjectComment'])->name('coordinator.projects.addComment');
    Route::get('/coordinator/projects/comment/{id}/edit', [CoordinatorController::class, 'editProjectComment'])->name('coordinator.projects.editComment');
    Route::post('/coordinator/projects/comment/{id}/update', [CoordinatorController::class, 'updateProjectComment'])->name('coordinator.projects.updateComment');
    // project status actions routes
        Route::post('/projects/{project_id}/revert-to-provincial', [CoordinatorController::class, 'revertToProvincial'])->name('projects.revertToProvincial');
    Route::post('/projects/{project_id}/approve', [CoordinatorController::class, 'approveProject'])->name('projects.approve');
    Route::post('/projects/{project_id}/reject', [CoordinatorController::class, 'rejectProject'])->name('projects.reject');

    // reports list
    Route::get('/coordinator/report-list', [CoordinatorController::class, 'reportList'])->name('coordinator.report.list');
    Route::post('/coordinator/report-list/bulk-action', [CoordinatorController::class, 'bulkReportAction'])->name('coordinator.report.bulk-action');

    // Add routes for coordinator report workflow
    Route::post('/coordinator/report/{report_id}/approve', [CoordinatorController::class, 'approveReport'])->name('coordinator.report.approve');
    Route::post('/coordinator/report/{report_id}/revert', [CoordinatorController::class, 'revertReport'])->name('coordinator.report.revert');

    // Add routes for coordinator report lists
    Route::get('/coordinator/report-list/pending', [CoordinatorController::class, 'pendingReports'])->name('coordinator.report.pending');
    Route::get('/coordinator/report-list/approved', [CoordinatorController::class, 'approvedReports'])->name('coordinator.report.approved');

    // Add the missing route for getting executors by province
    Route::get('/coordinator/executors/by-province', [CoordinatorController::class, 'getExecutorsByProvince'])->name('coordinator.executors.byProvince');

    Route::get('/coordinator/reports/{type}/{id}', [CoordinatorController::class, 'showReport'])->name('coordinator.reports.show');

    // To view reports
    Route::get('/coordinator/reports/monthly/show/{report_id}', [CoordinatorController::class, 'showMonthlyReport'])->name('coordinator.monthly.report.show');
    // Comment Routes
    Route::post('/coordinator/reports/monthly/{report_id}/add-comment', [CoordinatorController::class, 'addComment'])->name('coordinator.monthly.report.addComment');

    Route::get('/coordinator/budgets', [CoordinatorController::class, 'projectBudgets'])->name('coordinator.budgets');

    // Budget Overview
    Route::get('/coordinator/budget-overview', [CoordinatorController::class, 'budgetOverview'])->name('coordinator.budget-overview');

    // Budget Reports
    Route::get('/coordinator/budgets/report', [BudgetExportController::class, 'generateReport'])->name('budgets.report');

    Route::get('/coordinator/projects/{project_id}/download-pdf', [ExportController::class, 'downloadPdf'])->name('coordinator.projects.downloadPdf');
    Route::get('/coordinator/projects/{project_id}/download-doc', [ExportController::class, 'downloadDoc'])->name('coordinator.projects.downloadDoc');

    // Report download routes for coordinator
    Route::get('/coordinator/reports/monthly/downloadPdf/{report_id}', [ExportReportController::class, 'downloadPdf'])->name('coordinator.monthly.report.downloadPdf');
    Route::get('/coordinator/reports/monthly/downloadDoc/{report_id}', [ExportReportController::class, 'downloadDoc'])->name('coordinator.monthly.report.downloadDoc');

    // Activity History Routes for Coordinator
    Route::get('/activities/all-activities', [ActivityHistoryController::class, 'allActivities'])->name('activities.all-activities');

    // Center Management for Coordinator (managing child users' centers)
    Route::get('/coordinator/users/centers/manage', [CoordinatorController::class, 'manageUserCenters'])->name('coordinator.manageUserCenters');
    Route::get('/coordinator/users/{userId}/centers/manage', [CoordinatorController::class, 'manageUserCenters'])->name('coordinator.manageUserCenter');
    Route::post('/coordinator/users/{userId}/centers/update', [CoordinatorController::class, 'updateUserCenter'])->name('coordinator.updateUserCenter');
});

// General routes (General has COMPLETE coordinator access + direct team management)
Route::middleware(['auth', 'role:general'])->group(function () {
    // Province Filter Routes (for general users managing multiple provinces)
    Route::post('/province-filter/update', [ProvinceFilterController::class, 'updateFilter'])->name('province.filter.update');
    Route::get('/province-filter/get', [ProvinceFilterController::class, 'getFilter'])->name('province.filter.get');
    Route::post('/province-filter/clear', [ProvinceFilterController::class, 'clearFilter'])->name('province.filter.clear');

    // General Dashboard (Combined view for coordinator hierarchy + direct team)
    Route::get('/general/dashboard', [GeneralController::class, 'generalDashboard'])->name('general.dashboard');

    // Coordinator Management (General manages coordinators)
    Route::get('/general/create-coordinator', [GeneralController::class, 'createCoordinator'])->name('general.createCoordinator');
    Route::post('/general/create-coordinator', [GeneralController::class, 'storeCoordinator'])->name('general.storeCoordinator');
    Route::get('/general/coordinators', [GeneralController::class, 'listCoordinators'])->name('general.coordinators');
    Route::get('/general/coordinator/{id}/edit', [GeneralController::class, 'editCoordinator'])->name('general.editCoordinator');
    Route::post('/general/coordinator/{id}/update', [GeneralController::class, 'updateCoordinator'])->name('general.updateCoordinator');

    // Provincial Management (General manages provincials)
    Route::get('/general/create-provincial', [GeneralController::class, 'createProvincial'])->name('general.createProvincial');
    Route::post('/general/create-provincial', [GeneralController::class, 'storeProvincial'])->name('general.storeProvincial');
    Route::get('/general/provincials', [GeneralController::class, 'listProvincials'])->name('general.provincials');
    Route::get('/general/provincial/{id}/edit', [GeneralController::class, 'editProvincial'])->name('general.editProvincial');
    Route::post('/general/provincial/{id}/update', [GeneralController::class, 'updateProvincial'])->name('general.updateProvincial');

    Route::post('/general/user/{id}/reset-password', [GeneralController::class, 'resetUserPassword'])->name('general.resetUserPassword');
    Route::post('/general/user/{id}/activate', [GeneralController::class, 'activateUser'])->name('general.activateUser');
    Route::post('/general/user/{id}/deactivate', [GeneralController::class, 'deactivateUser'])->name('general.deactivateUser');

    // Direct Team Management (General acts as Provincial for executors/applicants directly under them)
    Route::get('/general/create-executor', [GeneralController::class, 'createExecutor'])->name('general.createExecutor');
    Route::post('/general/create-executor', [GeneralController::class, 'storeExecutor'])->name('general.storeExecutor');
    Route::get('/general/executors', [GeneralController::class, 'listExecutors'])->name('general.executors');
    Route::get('/general/executor/{id}/edit', [GeneralController::class, 'editExecutor'])->name('general.editExecutor');
    Route::post('/general/executor/{id}/update', [GeneralController::class, 'updateExecutor'])->name('general.updateExecutor');
    // Note: resetUserPassword, activateUser, and deactivateUser handle both coordinators and executors/applicants

    // Projects Management (with dual-role context selection)
    Route::get('/general/projects', [GeneralController::class, 'listProjects'])->name('general.projects');
    Route::post('/general/project/{project_id}/approve', [GeneralController::class, 'approveProject'])->name('general.approveProject');
    Route::post('/general/project/{project_id}/revert', [GeneralController::class, 'revertProject'])->name('general.revertProject');
    Route::post('/general/project/{project_id}/revert-to-level', [GeneralController::class, 'revertProjectToLevel'])->name('general.revertProjectToLevel');
    Route::post('/general/project/{project_id}/comment', [GeneralController::class, 'addProjectComment'])->name('general.addProjectComment');
    Route::get('/general/project-comment/{id}/edit', [GeneralController::class, 'editProjectComment'])->name('general.editProjectComment');
    Route::post('/general/project-comment/{id}/update', [GeneralController::class, 'updateProjectComment'])->name('general.updateProjectComment');
    Route::get('/general/project/{project_id}', [GeneralController::class, 'showProject'])->name('general.showProject');

    // Reports Management (with dual-role context selection)
    Route::get('/general/reports', [GeneralController::class, 'listReports'])->name('general.reports');
    Route::get('/general/reports/pending', [GeneralController::class, 'pendingReports'])->name('general.reports.pending');
    Route::get('/general/reports/approved', [GeneralController::class, 'approvedReports'])->name('general.reports.approved');
    Route::post('/general/reports/bulk-action', [GeneralController::class, 'bulkActionReports'])->name('general.reports.bulkAction');
    Route::post('/general/report/{report_id}/approve', [GeneralController::class, 'approveReport'])->name('general.approveReport');
    Route::post('/general/report/{report_id}/revert', [GeneralController::class, 'revertReport'])->name('general.revertReport');
    Route::post('/general/report/{report_id}/revert-to-level', [GeneralController::class, 'revertReportToLevel'])->name('general.revertReportToLevel');
    Route::post('/general/report/{report_id}/comment', [GeneralController::class, 'addReportComment'])->name('general.addReportComment');
    Route::get('/general/report-comment/{id}/edit', [GeneralController::class, 'editReportComment'])->name('general.editReportComment');
    Route::post('/general/report-comment/{id}/update', [GeneralController::class, 'updateReportComment'])->name('general.updateReportComment');
    Route::get('/general/report/{report_id}', [GeneralController::class, 'showReport'])->name('general.showReport');

    // Province Management
    Route::get('/general/provinces', [GeneralController::class, 'listProvinces'])->name('general.provinces');
    Route::get('/general/provinces/create', [GeneralController::class, 'createProvince'])->name('general.createProvince');
    Route::post('/general/provinces', [GeneralController::class, 'storeProvince'])->name('general.storeProvince');
    Route::get('/general/provinces/{provinceName}/edit', [GeneralController::class, 'editProvince'])->name('general.editProvince');
    Route::post('/general/provinces/{provinceName}/update', [GeneralController::class, 'updateProvince'])->name('general.updateProvince');
    Route::post('/general/provinces/{provinceName}/delete', [GeneralController::class, 'deleteProvince'])->name('general.deleteProvince');

    // Society Management
    Route::get('/general/societies', [GeneralController::class, 'listSocieties'])->name('general.societies');
    Route::get('/general/societies/create', [GeneralController::class, 'createSociety'])->name('general.createSociety');
    Route::post('/general/societies', [GeneralController::class, 'storeSociety'])->name('general.storeSociety');
    Route::get('/general/societies/{id}/edit', [GeneralController::class, 'editSociety'])->name('general.editSociety');
    Route::put('/general/societies/{id}', [GeneralController::class, 'updateSociety'])->name('general.updateSociety');
    Route::delete('/general/societies/{id}', [GeneralController::class, 'deleteSociety'])->name('general.deleteSociety');

    // Center Management (Updated for Societies)
    Route::get('/general/centers', [GeneralController::class, 'listCenters'])->name('general.centers');
    Route::get('/general/centers/create', [GeneralController::class, 'createCenter'])->name('general.createCenter');
    Route::post('/general/centers', [GeneralController::class, 'storeCenter'])->name('general.storeCenter');
    Route::get('/general/centers/{id}/edit', [GeneralController::class, 'editCenter'])->name('general.editCenter');
    Route::put('/general/centers/{id}', [GeneralController::class, 'updateCenter'])->name('general.updateCenter');
    Route::delete('/general/centers/{id}', [GeneralController::class, 'deleteCenter'])->name('general.deleteCenter');

    // Provincial Coordinator Assignment

    // Budget Management
    Route::get('/general/budgets', [GeneralController::class, 'listBudgets'])->name('general.budgets');

    // Center Management
    Route::get('/general/centers/manage', [GeneralController::class, 'manageCenters'])->name('general.manageCenters');
    Route::get('/general/centers/{centerId}/transfer', [GeneralController::class, 'showTransferCenter'])->name('general.showTransferCenter');
    Route::post('/general/centers/{centerId}/transfer', [GeneralController::class, 'transferCenter'])->name('general.transferCenter');
    Route::get('/general/users/centers/manage', [GeneralController::class, 'manageUserCenters'])->name('general.manageUserCenters');
    Route::get('/general/users/{userId}/centers/manage', [GeneralController::class, 'manageUserCenters'])->name('general.manageUserCenter');
    Route::post('/general/users/{userId}/centers/update', [GeneralController::class, 'updateUserCenter'])->name('general.updateUserCenter');

    // Note: General also has access to ALL coordinator routes via middleware (role:coordinator,general)
});

// Provincial routes
Route::middleware(['auth', 'role:provincial'])->group(function () {
    // Manage Executors
    Route::get('/provincial/create-executor', [ProvincialController::class, 'createExecutor'])->name('provincial.createExecutor');
    Route::post('/provincial/create-executor', [ProvincialController::class, 'storeExecutor'])->name('provincial.storeExecutor');
    Route::get('/provincial/executors', [ProvincialController::class, 'listExecutors'])->name('provincial.executors');
    Route::get('/provincial/executor/{id}/edit', [ProvincialController::class, 'editExecutor'])->name('provincial.editExecutor');
    Route::post('/provincial/executor/{id}/update', [ProvincialController::class, 'updateExecutor'])->name('provincial.updateExecutor');
    Route::post('/provincial/executor/{id}/reset-password', [ProvincialController::class, 'resetExecutorPassword'])->name('provincial.resetExecutorPassword');
    Route::post('/provincial/user/{id}/activate', [ProvincialController::class, 'activateUser'])->name('provincial.activateUser');
    Route::post('/provincial/user/{id}/deactivate', [ProvincialController::class, 'deactivateUser'])->name('provincial.deactivateUser');

    // Routes for Center Management
    Route::get('/provincial/centers', [ProvincialController::class, 'listCenters'])->name('provincial.centers');
    Route::get('/provincial/create-center', [ProvincialController::class, 'createCenter'])->name('provincial.createCenter');
    Route::post('/provincial/create-center', [ProvincialController::class, 'storeCenter'])->name('provincial.storeCenter');
    Route::get('/provincial/center/{id}/edit', [ProvincialController::class, 'editCenter'])->name('provincial.editCenter');
    Route::put('/provincial/center/{id}/update', [ProvincialController::class, 'updateCenter'])->name('provincial.updateCenter');

    // Provincial Management
    Route::get('/provincial/provincials', [ProvincialController::class, 'listProvincials'])->name('provincial.provincials');
    Route::get('/provincial/create-provincial', [ProvincialController::class, 'createProvincial'])->name('provincial.createProvincial');
    Route::post('/provincial/create-provincial', [ProvincialController::class, 'storeProvincial'])->name('provincial.storeProvincial');
    Route::get('/provincial/provincial/{id}/edit', [ProvincialController::class, 'editProvincial'])->name('provincial.editProvincial');
    Route::put('/provincial/provincial/{id}/update', [ProvincialController::class, 'updateProvincial'])->name('provincial.updateProvincial');

    // Society Management
    Route::get('/provincial/societies', [ProvincialController::class, 'listSocieties'])->name('provincial.societies');
    Route::get('/provincial/create-society', [ProvincialController::class, 'createSociety'])->name('provincial.createSociety');
    Route::post('/provincial/create-society', [ProvincialController::class, 'storeSociety'])->name('provincial.storeSociety');
    Route::get('/provincial/society/{id}/edit', [ProvincialController::class, 'editSociety'])->name('provincial.editSociety');
    Route::put('/provincial/society/{id}/update', [ProvincialController::class, 'updateSociety'])->name('provincial.updateSociety');

    // Routes for Provincial Dashboard
    Route::get('/provincial/dashboard', [ProvincialController::class, 'provincialDashboard'])->name('provincial.dashboard');
    // Projects list
    Route::get('/provincial/projects-list', [ProvincialController::class, 'projectList'])->name('provincial.projects.list');
    // Approved Projects
    Route::get('/provincial/approved-projects', [ProvincialController::class, 'approvedProjects'])->name('provincial.approved.projects');
    // Add this route to allow provincial to view a project
    Route::get('/provincial/projects/show/{project_id}', [ProvincialController::class, 'showProject'])->name('provincial.projects.show');

    // Provincial-specific download routes
    Route::get('/provincial/projects/{project_id}/download-pdf', [ExportController::class, 'downloadPdf'])->name('provincial.projects.downloadPdf');
    Route::get('/provincial/projects/{project_id}/download-doc', [ExportController::class, 'downloadDoc'])->name('provincial.projects.downloadDoc');

    // routes for Project Comments
    Route::post('/provincial/projects/{project_id}/add-comment', [ProvincialController::class, 'addProjectComment'])->name('provincial.projects.addComment');
    Route::get('/provincial/projects/comment/{id}/edit', [ProvincialController::class, 'editProjectComment'])->name('provincial.projects.editComment');
    Route::post('/provincial/projects/comment/{id}/update', [ProvincialController::class, 'updateProjectComment'])->name('provincial.projects.updateComment');
    // project status actions routes
    Route::post('/projects/{project_id}/revert-to-executor', [ProvincialController::class, 'revertToExecutor'])->name('projects.revertToExecutor');
    Route::post('/projects/{project_id}/forward-to-coordinator', [ProvincialController::class, 'forwardToCoordinator'])->name('projects.forwardToCoordinator');


    // Route for report list
    Route::get('/provincial/report-list', [ProvincialController::class, 'reportList'])->name('provincial.report.list');

    // Add routes for provincial report workflow
    Route::post('/provincial/report/{report_id}/forward', [ProvincialController::class, 'forwardReport'])->name('provincial.report.forward');
    Route::post('/provincial/report/{report_id}/revert', [ProvincialController::class, 'revertReport'])->name('provincial.report.revert');
    Route::post('/provincial/reports/bulk-forward', [ProvincialController::class, 'bulkForwardReports'])->name('provincial.report.bulk-forward');

    // Add routes for provincial report lists
    Route::get('/provincial/report-list/pending', [ProvincialController::class, 'pendingReports'])->name('provincial.report.pending');
    Route::get('/provincial/report-list/approved', [ProvincialController::class, 'approvedReports'])->name('provincial.report.approved');

    Route::get('/provincial/reports/{type}/{id}', [ProvincialController::class, 'showReport'])->name('provincial.reports.show');

    // To view reports
    Route::get('/provincial/reports/monthly/show/{report_id}', [ProvincialController::class, 'showMonthlyReport'])->name('provincial.monthly.report.show');

    //Comment Routes
     Route::post('/provincial/reports/monthly/{report_id}/add-comment', [ProvincialController::class, 'addComment'])->name('provincial.monthly.report.addComment');

    // Report download routes for provincial
    Route::get('/provincial/reports/monthly/downloadPdf/{report_id}', [ExportReportController::class, 'downloadPdf'])->name('provincial.monthly.report.downloadPdf');
    Route::get('/provincial/reports/monthly/downloadDoc/{report_id}', [ExportReportController::class, 'downloadDoc'])->name('provincial.monthly.report.downloadDoc');

    // Activity History Routes for Provincial
    Route::get('/activities/team-activities', [ActivityHistoryController::class, 'teamActivities'])->name('activities.team-activities');
});



// Executor routes
Route::middleware(['auth', 'role:executor,applicant'])->group(function () {
    Route::get('/executor/dashboard', [ExecutorController::class, 'executorDashboard'])->name('executor.dashboard');
    Route::get('/executor/report-list', [ExecutorController::class, 'reportList'])->name('executor.report.list');

    // Add route for executor to submit reports to provincial
    Route::post('/executor/report/{report_id}/submit', [ExecutorController::class, 'submitReport'])->name('executor.report.submit');

    // Add routes for executor report lists
    Route::get('/executor/report-list/pending', [ExecutorController::class, 'pendingReports'])->name('executor.report.pending');
    Route::get('/executor/report-list/approved', [ExecutorController::class, 'approvedReports'])->name('executor.report.approved');

    // Activity History Routes for Executor/Applicant
    Route::get('/activities/my-activities', [ActivityHistoryController::class, 'myActivities'])->name('activities.my-activities');

    Route::get('/test-expenses/{project_id}', [ReportController::class, 'testFetchLatestTotalExpenses']);

    // Project Application Routes for Executor
    Route::prefix('executor/projects')->group(function () {
        // NEXT Phase Development Proposal get data for general info
        Route::get('/{project_id}/details', [ProjectController::class, 'getProjectDetails'])->name('projects.details');        //default route for projects
        Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('approved', [ProjectController::class, 'approvedProjects'])->name('projects.approved');
        Route::get('create', [ProjectController::class, 'create'])->name('projects.create');
        Route::post('store', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('{project_id}', [ProjectController::class, 'show'])->name('projects.show');
        Route::get('{project_id}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
        Route::put('{project_id}/update', [ProjectController::class, 'update'])->name('projects.update');
        // Download routes moved to shared middleware group
        //Education Rural Urban Tribal
        Route::resource('projects/edurut/basic-info',ProjectEduRUTBasicInfoController::class);
        //
        Route::post('projects/eduRUT/target-group/excel-upload', [EduRUTTargetGroupController::class, 'uploadExcel']);
        // routes to download template for target group and annexed target group
        Route::post('/upload-target-group-excel', [EduRUTTargetGroupController::class, 'uploadExcel'])->name('targetGroup.upload');
        Route::post('/upload-annexed-target-group-excel', [EduRUTAnnexedTargetGroupController::class, 'uploadExcel'])->name('annexedTargetGroup.upload');
        // project status actions routes
        Route::post('/projects/{project_id}/submit-to-provincial', [ProjectController::class, 'submitToProvincial'])->name('projects.submitToProvincial');
        Route::post('/projects/{project_id}/mark-completed', [ProjectController::class, 'markAsCompleted'])->name('projects.markCompleted');


    });

// Monthly  Project Reporting Routes common for Executor
Route::prefix('reports/monthly')->group(function () {
    Route::get('create/{project_id}', [ReportController::class, 'create'])->name('monthly.report.create');
    Route::post('store', [ReportController::class, 'store'])->name('monthly.report.store');
    Route::get('index', [ReportController::class, 'index'])->name('monthly.report.index');
    Route::get('edit/{report_id}', [ReportController::class, 'edit'])->name('monthly.report.edit');
    Route::put('update/{report_id}', [ReportController::class, 'update'])->name('monthly.report.update');
    Route::get('review/{report_id}', [ReportController::class, 'review'])->name('monthly.report.review');
    Route::post('revert/{report_id}', [ReportController::class, 'revert'])->name('monthly.report.revert');
    Route::post('submit/{report_id}', [ReportController::class, 'submit'])->name('monthly.report.submit');
    Route::post('forward/{report_id}', [ReportController::class, 'forward'])->name('monthly.report.forward');
    Route::post('approve/{report_id}', [ReportController::class, 'approve'])->name('monthly.report.approve');

    Route::get('livelihood-annexure/{report_id}', [LivelihoodAnnexureController::class, 'show'])->name('livelihood.annexure.show');
    Route::get('livelihood-annexure/{report_id}/edit', [LivelihoodAnnexureController::class, 'edit'])->name('livelihood.annexure.edit');
    Route::put('livelihood-annexure/{report_id}', [LivelihoodAnnexureController::class, 'update'])->name('livelihood.annexure.update');

    //Report Attachment Routes
    Route::delete('/reports/monthly/attachments/{id}', [ReportAttachmentController::class, 'remove'])->name('reports.attachments.remove');
    Route::delete('/photos/{id}', [ReportController::class, 'removePhoto'])->name('photos.remove');

    // Monthly Development Project (developmentProject/reportform: activity-based photos)
    Route::get('development-project/create/{project_id}', [MonthlyDevelopmentProjectController::class, 'createForm'])->name('monthly.developmentProject.create');
    Route::post('development-project/store', [MonthlyDevelopmentProjectController::class, 'store'])->name('monthly.developmentProject.store');
});
// Shared route for Executor, Provincial and Coordinator

// for Projects 9122024 (General has COMPLETE coordinator access)
Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator,general'])->group(function () {
    Route::get('/projects-list', [ProjectController::class, 'listProjects'])->name('projects.list');

    // Project download routes accessible to all roles - ORDER MATTERS!
    // More specific routes must come before generic ones
    Route::get('/projects/{project_id}/download-pdf', [ExportController::class, 'downloadPdf'])->name('projects.downloadPdf');
    Route::get('/projects/{project_id}/download-doc', [ExportController::class, 'downloadDoc'])->name('projects.downloadDoc');
    Route::get('/projects/attachments/download/{id}', [AttachmentController::class, 'downloadAttachment'])->name('projects.attachments.download');

    // Activity History Routes (shared for all roles)
    Route::get('/projects/{project_id}/activity-history', [ActivityHistoryController::class, 'projectHistory'])->name('projects.activity-history');
    Route::get('/reports/{report_id}/activity-history', [ActivityHistoryController::class, 'reportHistory'])->name('reports.activity-history');
});




// for Reports (General has COMPLETE coordinator access)
Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator,general'])->group(function () {
    //Download Monthly Reports
    Route::get('reports/monthly/attachments/download/{id}', [ReportAttachmentController::class, 'downloadAttachment'])->name('reports.attachments.download');
    //Check file existence
    Route::get('reports/monthly/check-file/{id}', [ReportAttachmentController::class, 'checkFileExists'])->name('monthly.report.checkFile');
    //Test file structure
    Route::get('reports/monthly/test-structure/{report_id}', [ReportAttachmentController::class, 'testFileStructure'])->name('monthly.report.testStructure');
    //Test create attachment
    Route::get('reports/monthly/test-create-attachment/{report_id}', [ReportAttachmentController::class, 'testCreateAttachment'])->name('monthly.report.testCreateAttachment');
    //View Montly Reports
    Route::get('show/{report_id}', [ReportController::class, 'show'])->name('monthly.report.show');

    // Monthly report PDF and DOC download routes
    Route::get('reports/monthly/downloadPdf/{report_id}', [ExportReportController::class, 'downloadPdf'])->name('monthly.report.downloadPdf');
    Route::get('reports/monthly/downloadDoc/{report_id}', [ExportReportController::class, 'downloadDoc'])->name('monthly.report.downloadDoc');
});

    // Quarterly Development Project Reporting Routes for Executor
    Route::prefix('reports/quarterly/development-project')->group(function () {
        Route::get('create', [DevelopmentProjectController::class, 'create'])->name('quarterly.developmentProject.create');
        Route::post('store', [DevelopmentProjectController::class, 'store'])->name('quarterly.developmentProject.store');
        Route::get('{id}/edit', [DevelopmentProjectController::class, 'edit'])->name('quarterly.developmentProject.edit');
        Route::put('{id}', [DevelopmentProjectController::class, 'update'])->name('quarterly.developmentProject.update');
        Route::get('{id}/review', [DevelopmentProjectController::class, 'review'])->name('quarterly.developmentProject.review');
        Route::post('{id}/revert', [DevelopmentProjectController::class, 'revert'])->name('quarterly.developmentProject.revert');
        Route::get('list', [DevelopmentProjectController::class, 'index'])->name('quarterly.developmentProject.index');
        Route::get('{id}', [DevelopmentProjectController::class, 'show'])->name('quarterly.developmentProject.show');
    });

    // Quarterly Skill Training Reporting Routes
    Route::prefix('reports/quarterly/skill-training')->group(function () {
        Route::get('create', [SkillTrainingController::class, 'create'])->name('quarterly.skillTraining.create');
        Route::post('store', [SkillTrainingController::class, 'store'])->name('quarterly.skillTraining.store');
        Route::get('{id}/edit', [SkillTrainingController::class, 'edit'])->name('quarterly.skillTraining.edit');
        Route::put('{id}', [SkillTrainingController::class, 'update'])->name('quarterly.skillTraining.update');
        Route::get('{id}/review', [SkillTrainingController::class, 'review'])->name('quarterly.skillTraining.review');
        Route::post('{id}/revert', [SkillTrainingController::class, 'revert'])->name('quarterly.skillTraining.revert');
        Route::get('list', [SkillTrainingController::class, 'index'])->name('quarterly.skillTraining.index');
        Route::get('{id}', [SkillTrainingController::class, 'show'])->name('quarterly.skillTraining.show');
    });

    // Quarterly Development Livelihood Reporting Routes
    Route::prefix('reports/quarterly/development-livelihood')->group(function () {
        Route::get('create', [DevelopmentLivelihoodController::class, 'create'])->name('quarterly.developmentLivelihood.create');
        Route::post('store', [DevelopmentLivelihoodController::class, 'store'])->name('quarterly.developmentLivelihood.store');
        Route::get('{id}/edit', [DevelopmentLivelihoodController::class, 'edit'])->name('quarterly.developmentLivelihood.edit');
        Route::put('{id}', [DevelopmentLivelihoodController::class, 'update'])->name('quarterly.developmentLivelihood.update');
        Route::get('{id}/review', [DevelopmentLivelihoodController::class, 'review'])->name('quarterly.developmentLivelihood.review');
        Route::post('{id}/revert', [DevelopmentLivelihoodController::class, 'revert'])->name('quarterly.developmentLivelihood.revert');
        Route::get('list', [DevelopmentLivelihoodController::class, 'index'])->name('quarterly.developmentLivelihood.index');
        Route::get('{id}', [DevelopmentLivelihoodController::class, 'show'])->name('quarterly.developmentLivelihood.show');
    });

    // Quarterly Institutional Support Reporting Routes
    Route::prefix('reports/quarterly/institutional-support')->group(function () {
        Route::get('create', [InstitutionalSupportController::class, 'create'])->name('quarterly.institutionalSupport.create');
        Route::post('store', [InstitutionalSupportController::class, 'store'])->name('quarterly.institutionalSupport.store');
        Route::get('{id}/edit', [InstitutionalSupportController::class, 'edit'])->name('quarterly.institutionalSupport.edit');
        Route::put('{id}', [InstitutionalSupportController::class, 'update'])->name('quarterly.institutionalSupport.update');
        Route::get('{id}/review', [InstitutionalSupportController::class, 'review'])->name('quarterly.institutionalSupport.review');
        Route::post('{id}/revert', [InstitutionalSupportController::class, 'revert'])->name('quarterly.institutionalSupport.revert');
        Route::get('list', [InstitutionalSupportController::class, 'index'])->name('quarterly.institutionalSupport.index');
        Route::get('{id}', [InstitutionalSupportController::class, 'show'])->name('quarterly.institutionalSupport.show');
    });


    // Quarterly Women in Distress Reporting Routes
    Route::prefix('reports/quarterly/women-in-distress')->group(function () {
        Route::get('create', [WomenInDistressController::class, 'create'])->name('quarterly.womenInDistress.create');
        Route::post('store', [WomenInDistressController::class, 'store'])->name('quarterly.womenInDistress.store');
        Route::get('{id}/edit', [WomenInDistressController::class, 'edit'])->name('quarterly.womenInDistress.edit');
        Route::put('{id}', [WomenInDistressController::class, 'update'])->name('quarterly.womenInDistress.update');
        Route::get('{id}/review', [WomenInDistressController::class, 'review'])->name('quarterly.womenInDistress.review');
        Route::post('{id}/revert', [WomenInDistressController::class, 'revert'])->name('quarterly.womenInDistress.revert');
        Route::get('list', [WomenInDistressController::class, 'index'])->name('quarterly.womenInDistress.index');
        Route::get('{id}', [WomenInDistressController::class, 'show'])->name('quarterly.womenInDistress.show');
    });
});

// Aggregated Report Routes (Quarterly, Half-Yearly, Annual) (General has COMPLETE coordinator access)
Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator,general'])->group(function () {
    // Quarterly Reports
    Route::prefix('reports/aggregated/quarterly')->name('aggregated.quarterly.')->group(function () {
        Route::get('index', [AggregatedQuarterlyReportController::class, 'index'])->name('index');
        Route::get('create/{project_id}', [AggregatedQuarterlyReportController::class, 'create'])->name('create');
        Route::post('store/{project_id}', [AggregatedQuarterlyReportController::class, 'store'])->name('store');
        Route::get('show/{report_id}', [AggregatedQuarterlyReportController::class, 'show'])->name('show');
        Route::get('edit-ai/{report_id}', [AggregatedQuarterlyReportController::class, 'editAI'])->name('edit-ai');
        Route::put('update-ai/{report_id}', [AggregatedQuarterlyReportController::class, 'updateAI'])->name('update-ai');
        Route::get('export-pdf/{report_id}', [AggregatedQuarterlyReportController::class, 'exportPdf'])->name('export-pdf');
        Route::get('export-word/{report_id}', [AggregatedQuarterlyReportController::class, 'exportWord'])->name('export-word');
        // Quarterly Report Comparison
        Route::get('compare', [ReportComparisonController::class, 'compareQuarterly'])->name('reports.aggregated.quarterly.compare');
        Route::post('compare', [ReportComparisonController::class, 'compareQuarterly'])->name('reports.aggregated.quarterly.compare');
    });

    // Half-Yearly Reports
    Route::prefix('reports/aggregated/half-yearly')->name('aggregated.half-yearly.')->group(function () {
        Route::get('index', [AggregatedHalfYearlyReportController::class, 'index'])->name('index');
        Route::get('create/{project_id}', [AggregatedHalfYearlyReportController::class, 'create'])->name('create');
        Route::post('store/{project_id}', [AggregatedHalfYearlyReportController::class, 'store'])->name('store');
        Route::get('show/{report_id}', [AggregatedHalfYearlyReportController::class, 'show'])->name('show');
        Route::get('edit-ai/{report_id}', [AggregatedHalfYearlyReportController::class, 'editAI'])->name('edit-ai');
        Route::put('update-ai/{report_id}', [AggregatedHalfYearlyReportController::class, 'updateAI'])->name('update-ai');
        Route::get('export-pdf/{report_id}', [AggregatedHalfYearlyReportController::class, 'exportPdf'])->name('export-pdf');
        Route::get('export-word/{report_id}', [AggregatedHalfYearlyReportController::class, 'exportWord'])->name('export-word');
        // Half-Yearly Report Comparison
        Route::get('compare', [ReportComparisonController::class, 'compareHalfYearly'])->name('reports.aggregated.half-yearly.compare');
        Route::post('compare', [ReportComparisonController::class, 'compareHalfYearly'])->name('reports.aggregated.half-yearly.compare');
    });

    // Annual Reports
    Route::prefix('reports/aggregated/annual')->name('aggregated.annual.')->group(function () {
        Route::get('index', [AggregatedAnnualReportController::class, 'index'])->name('index');
        Route::get('create/{project_id}', [AggregatedAnnualReportController::class, 'create'])->name('create');
        Route::post('store/{project_id}', [AggregatedAnnualReportController::class, 'store'])->name('store');
        Route::get('show/{report_id}', [AggregatedAnnualReportController::class, 'show'])->name('show');
        Route::get('edit-ai/{report_id}', [AggregatedAnnualReportController::class, 'editAI'])->name('edit-ai');
        Route::put('update-ai/{report_id}', [AggregatedAnnualReportController::class, 'updateAI'])->name('update-ai');
        Route::get('export-pdf/{report_id}', [AggregatedAnnualReportController::class, 'exportPdf'])->name('export-pdf');
        Route::get('export-word/{report_id}', [AggregatedAnnualReportController::class, 'exportWord'])->name('export-word');
        // Annual Report Comparison
        Route::get('compare', [ReportComparisonController::class, 'compareAnnual'])->name('reports.aggregated.annual.compare');
        Route::post('compare', [ReportComparisonController::class, 'compareAnnual'])->name('reports.aggregated.annual.compare');
    });

    // Report Comparison Routes
    Route::prefix('reports/aggregated/comparison')->name('aggregated.comparison.')->group(function () {
        // Quarterly Comparison
        Route::get('quarterly-form', [ReportComparisonController::class, 'compareQuarterlyForm'])->name('quarterly-form');
        Route::post('quarterly', [ReportComparisonController::class, 'compareQuarterly'])->name('quarterly');

        // Half-Yearly Comparison
        Route::get('half-yearly-form', [ReportComparisonController::class, 'compareHalfYearlyForm'])->name('half-yearly-form');
        Route::post('half-yearly', [ReportComparisonController::class, 'compareHalfYearly'])->name('half-yearly');

        // Annual Comparison
        Route::get('annual-form', [ReportComparisonController::class, 'compareAnnualForm'])->name('annual-form');
        Route::post('annual', [ReportComparisonController::class, 'compareAnnual'])->name('annual');
    });
});

// Allow admin to access all other routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::any('{all}', function () {
        return view('admin.dashboard');
    })->where('all', '.*');
});

// Test route for debugging middleware
Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator,general'])->group(function () {
    Route::get('/test-middleware', function () {
        return response()->json(['message' => 'Middleware passed', 'user_role' => auth()->user()->role]);
    })->name('test.middleware');
});
