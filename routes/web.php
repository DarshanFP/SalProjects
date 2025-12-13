<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\ExecutorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Projects\AttachmentController;
use App\Http\Controllers\Projects\BudgetController;
use App\Http\Controllers\Projects\EduRUTAnnexedTargetGroupController;
use App\Http\Controllers\Projects\EduRUTTargetGroupController;
use App\Http\Controllers\Projects\ExportController;
use App\Http\Controllers\Projects\OldDevelopmentProjectController;
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Projects\ProjectEduRUTBasicInfoController;
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

use App\Http\Controllers\TestController;
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

// Download routes accessible to all authenticated users
Route::middleware('auth')->group(function () {
    // Route::get('/projects/{project_id}/download-pdf', [ProjectController::class, 'downloadPdf'])->name('projects.downloadPdf');
    // Route::get('/projects/{project_id}/download-doc', [ProjectController::class, 'downloadDoc'])->name('projects.downloadDoc');


    Route::get('/budgets/{projectId}', [BudgetController::class, 'viewBudget']);
    Route::post('/budgets/{projectId}/expenses', [BudgetController::class, 'addExpense']);
});

// Auth routes
require __DIR__.'/auth.php';

// Admin routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'AdminDashboard'])->name('admin.dashboard');
    Route::get('/admin/logout', [AdminController::class, 'AdminLogout'])->name('admin.logout');
    // Admin has access to all other routes, so no need to duplicate routes here
});

// Coordinator routes
Route::middleware(['auth', 'role:coordinator'])->group(function () {
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
    Route::get('/coordinator/dashboard', [CoordinatorController::class, 'CoordinatorDashboard'])->name('coordinator.dashboard');
    // Project List
    Route::get('/coordinator/projects-list', [CoordinatorController::class, 'ProjectList'])->name('coordinator.projects.list');
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
    Route::get('/coordinator/report-list', [CoordinatorController::class, 'ReportList'])->name('coordinator.report.list');

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

    Route::get('/coordinator/projects/{project_id}/download-pdf', [ExportController::class, 'downloadPdf'])->name('coordinator.projects.downloadPdf');
    Route::get('/coordinator/projects/{project_id}/download-doc', [ExportController::class, 'downloadDoc'])->name('coordinator.projects.downloadDoc');

    // Report download routes for coordinator
    Route::get('/coordinator/reports/monthly/downloadPdf/{report_id}', [ExportReportController::class, 'downloadPdf'])->name('coordinator.monthly.report.downloadPdf');
    Route::get('/coordinator/reports/monthly/downloadDoc/{report_id}', [ExportReportController::class, 'downloadDoc'])->name('coordinator.monthly.report.downloadDoc');
});

// Provincial routes
Route::middleware(['auth', 'role:provincial'])->group(function () {
    // Manage Executors
    Route::get('/provincial/create-executor', [ProvincialController::class, 'CreateExecutor'])->name('provincial.createExecutor');
    Route::post('/provincial/create-executor', [ProvincialController::class, 'StoreExecutor'])->name('provincial.storeExecutor');
    Route::get('/provincial/executors', [ProvincialController::class, 'listExecutors'])->name('provincial.executors');
    Route::get('/provincial/executor/{id}/edit', [ProvincialController::class, 'editExecutor'])->name('provincial.editExecutor');
    Route::post('/provincial/executor/{id}/update', [ProvincialController::class, 'updateExecutor'])->name('provincial.updateExecutor');
    Route::post('/provincial/executor/{id}/reset-password', [ProvincialController::class, 'resetExecutorPassword'])->name('provincial.resetExecutorPassword');
    Route::post('/provincial/user/{id}/activate', [ProvincialController::class, 'activateUser'])->name('provincial.activateUser');
    Route::post('/provincial/user/{id}/deactivate', [ProvincialController::class, 'deactivateUser'])->name('provincial.deactivateUser');

    // Routes for Provincial Dashboard
    Route::get('/provincial/dashboard', [ProvincialController::class, 'ProvincialDashboard'])->name('provincial.dashboard');
    // Projects list
    Route::get('/provincial/projects-list', [ProvincialController::class, 'ProjectList'])->name('provincial.projects.list');
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
    Route::get('/provincial/report-list', [ProvincialController::class, 'ReportList'])->name('provincial.report.list');

    // Add routes for provincial report workflow
    Route::post('/provincial/report/{report_id}/forward', [ProvincialController::class, 'forwardReport'])->name('provincial.report.forward');
    Route::post('/provincial/report/{report_id}/revert', [ProvincialController::class, 'revertReport'])->name('provincial.report.revert');

    // Add routes for provincial report lists
    Route::get('/provincial/report-list/pending', [ProvincialController::class, 'pendingReports'])->name('provincial.report.pending');
    Route::get('/provincial/report-list/approved', [ProvincialController::class, 'approvedReports'])->name('provincial.report.approved');

    Route::get('/provincial/reports/{type}/{id}', [ProvincialController::class, 'showReport'])->name('provincial.reports.show');

    // To view reports
    Route::get('/provincial/reports/monthly/show/{report_id}', [ProvincialController::class, 'showMonthlyReport'])->name('provincial.monthly.report.show');


    //Report Attachment Routes
 //   Route::get('reports/monthly/download/{id}', [ReportAttachmentController::class, 'downloadAttachment'])
 //    ->name('monthly.report.downloadAttachment');

     //Comment Routes
     Route::post('/provincial/reports/monthly/{report_id}/add-comment', [ProvincialController::class, 'addComment'])->name('provincial.monthly.report.addComment');

    // Report download routes for provincial
    Route::get('/provincial/reports/monthly/downloadPdf/{report_id}', [ExportReportController::class, 'downloadPdf'])->name('provincial.monthly.report.downloadPdf');
    Route::get('/provincial/reports/monthly/downloadDoc/{report_id}', [ExportReportController::class, 'downloadDoc'])->name('provincial.monthly.report.downloadDoc');
});



// // Executor routes
// Route::middleware(['auth', 'role:executor'])->group(function () {
//     Route::get('/executor/dashboard', [ExecutorController::class, 'ExecutorDashboard'])->name('executor.dashboard');
//     Route::get('/test-pdf', [TestController::class, 'generatePdf']);

//     // Project Application Routes for Executor
//     Route::prefix('executor/projects')->group(function () {
//         Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
//         Route::get('create', [ProjectController::class, 'create'])->name('projects.create');
//         Route::post('store', [ProjectController::class, 'store'])->name('projects.store');
//         Route::get('{project_id}', [ProjectController::class, 'show'])->name('projects.show');
//         Route::get('{project_id}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
//         Route::post('{project_id}/update', [ProjectController::class, 'update'])->name('projects.update');
//         Route::get('download/{id}', [ProjectController::class, 'downloadAttachment'])->name('download.attachment');
//     });
// Executor routes
Route::middleware(['auth', 'role:executor,applicant'])->group(function () {
    Route::get('/executor/dashboard', [ExecutorController::class, 'ExecutorDashboard'])->name('executor.dashboard');
    Route::get('/executor/report-list', [ExecutorController::class, 'ReportList'])->name('executor.report.list');

    // Add route for executor to submit reports to provincial
    Route::post('/executor/report/{report_id}/submit', [ExecutorController::class, 'submitReport'])->name('executor.report.submit');

    // Add routes for executor report lists
    Route::get('/executor/report-list/pending', [ExecutorController::class, 'pendingReports'])->name('executor.report.pending');
    Route::get('/executor/report-list/approved', [ExecutorController::class, 'approvedReports'])->name('executor.report.approved');

    Route::get('/test-pdf', [TestController::class, 'generatePdf']);

    Route::get('/test-expenses/{project_id}', [App\Http\Controllers\Reports\Monthly\ReportController::class, 'testFetchLatestTotalExpenses']);

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


    });

// Monthly  Project Reporting Routes common for Executor
Route::prefix('reports/monthly')->group(function () {
    Route::get('create/{project_id}', [ReportController::class, 'create'])->name('monthly.report.create');
    Route::post('store', [ReportController::class, 'store'])->name('monthly.report.store');
    Route::get('index', [ReportController::class, 'index'])->name('monthly.report.index');
    //kept it in shared area for all users
  //  Route::get('show/{report_id}', [ReportController::class, 'show'])->name('monthly.report.show');
    Route::get('edit/{report_id}', [ReportController::class, 'edit'])->name('monthly.report.edit');
    Route::put('update/{report_id}', [ReportController::class, 'update'])->name('monthly.report.update');
    Route::get('review/{report_id}', [ReportController::class, 'review'])->name('monthly.report.review');
    Route::post('revert/{report_id}', [ReportController::class, 'revert'])->name('monthly.report.revert');
    Route::post('submit/{report_id}', [ReportController::class, 'submit'])->name('monthly.report.submit');
    Route::post('forward/{report_id}', [ReportController::class, 'forward'])->name('monthly.report.forward');
    Route::post('approve/{report_id}', [ReportController::class, 'approve'])->name('monthly.report.approve');

    // Monthly report download routes moved to shared middleware group

    Route::get('livelihood-annexure/{report_id}', [LivelihoodAnnexureController::class, 'show'])->name('livelihood.annexure.show');
    Route::get('livelihood-annexure/{report_id}/edit', [LivelihoodAnnexureController::class, 'edit'])->name('livelihood.annexure.edit');
    Route::put('livelihood-annexure/{report_id}', [LivelihoodAnnexureController::class, 'update'])->name('livelihood.annexure.update');

    //Report Attachment Routes
 //   Route::get('reports/monthly/download/{id}', [ReportAttachmentController::class, 'downloadAttachment'])->name('monthly.report.downloadAttachment');
    Route::delete('/attachments/{id}', [ReportAttachmentController::class, 'remove'])->name('attachments.remove');
    Route::delete('/photos/{id}', [ReportController::class, 'removePhoto'])->name('photos.remove');



});
// Shared route for Executor, Provincial and Coordinator

// for Projects 9122024
Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator'])->group(function () {
    Route::get('/projects-list', [ProjectController::class, 'listProjects'])->name('projects.list');

    // Project download routes accessible to all roles - ORDER MATTERS!
    // More specific routes must come before generic ones
    Route::get('/projects/{project_id}/download-pdf', [ExportController::class, 'downloadPdf'])->name('projects.downloadPdf');
    Route::get('/projects/{project_id}/download-doc', [ExportController::class, 'downloadDoc'])->name('projects.downloadDoc');
    Route::get('/projects/attachments/download/{id}', [AttachmentController::class, 'downloadAttachment'])->name('download.attachment');
});




// for Reports
Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator'])->group(function () {
    //Download Monthly Reports
    Route::get('reports/monthly/download/{id}', [ReportAttachmentController::class, 'downloadAttachment'])->name('monthly.report.downloadAttachment');
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


    // Monthly Development Project Reporting Routes for Executor
    // Route::prefix('reports/monthly/developmentProject')->group(function () {
    //     Route::get('create/{project_id}', [MonthlyDevelopmentProjectController::class, 'create'])->name('monthly.developmentProject.create');
    //     Route::post('store', [MonthlyDevelopmentProjectController::class, 'store'])->name('monthly.developmentProject.store');
    //     Route::get('index', [MonthlyDevelopmentProjectController::class, 'index'])->name('monthly.developmentProject.index');
    //     Route::get('show/{report_id}', [MonthlyDevelopmentProjectController::class, 'show'])->name('monthly.developmentProject.show');
    //     Route::get('edit/{report_id}', [MonthlyDevelopmentProjectController::class, 'edit'])->name('monthly.developmentProject.edit');
    //     Route::post('update/{report_id}', [MonthlyDevelopmentProjectController::class, 'update'])->name('monthly.developmentProject.update');
    //     Route::get('review/{report_id}', [MonthlyDevelopmentProjectController::class, 'review'])->name('monthly.developmentProject.review');
    //     Route::post('revert/{report_id}', [MonthlyDevelopmentProjectController::class, 'revert'])->name('monthly.developmentProject.revert');


    // });

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

// Allow admin to access all other routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::any('{all}', function () {
        return view('admin.dashboard');
    })->where('all', '.*');
});

// Test route for debugging middleware
Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator'])->group(function () {
    Route::get('/test-middleware', function () {
        return response()->json(['message' => 'Middleware passed', 'user_role' => auth()->user()->role]);
    })->name('test.middleware');
});
