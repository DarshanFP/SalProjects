<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\ExecutorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Projects\AttachmentController;
use App\Http\Controllers\Projects\ExportController;
use App\Http\Controllers\Projects\OldDevelopmentProjectController;
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\ProvincialController;
use App\Http\Controllers\Reports\Monthly\MonthlyDevelopmentProjectController;
use App\Http\Controllers\Reports\Monthly\ReportController;
use App\Http\Controllers\Reports\Quarterly\DevelopmentLivelihoodController;
use App\Http\Controllers\Reports\Quarterly\DevelopmentProjectController;
use App\Http\Controllers\Reports\Quarterly\InstitutionalSupportController;
use App\Http\Controllers\Reports\Quarterly\SkillTrainingController;
use App\Http\Controllers\Reports\Quarterly\WomenInDistressController;

use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

// Logout for all
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

// Default redirect to dashboard based on role
Route::get('/dashboard', function () {
    $role = Auth::user()->role;
    $url = match($role) {
        'admin' => 'admin/dashboard',
        'coordinator' => 'coordinator/dashboard',
        'provincial' => 'provincial/dashboard',
        'executor' => 'executor/dashboard',
        default => 'dashboard',
    };

    return redirect()->intended($url);
})->middleware(['auth', 'verified'])->name('dashboard');

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
    Route::get('/projects/{project_id}/download-pdf', [ProjectController::class, 'downloadPdf'])->name('projects.downloadPdf');
    Route::get('/projects/{project_id}/download-doc', [ProjectController::class, 'downloadDoc'])->name('projects.downloadDoc');

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
    Route::post('/coordinator/provincial/{id}/reset-password', [CoordinatorController::class, 'resetProvincialPassword'])->name('coordinator.resetProvincialPassword');

    // Reports Routes for Coordinator
    Route::get('/coordinator/dashboard', [CoordinatorController::class, 'CoordinatorDashboard'])->name('coordinator.dashboard');
    Route::get('/coordinator/reports/{type}/{id}', [CoordinatorController::class, 'showReport'])->name('coordinator.reports.show');
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

    // Reports Routes for Provincial
    Route::get('/provincial/dashboard', [ProvincialController::class, 'ProvincialDashboard'])->name('provincial.dashboard');
    Route::get('/provincial/reports/{type}/{id}', [ProvincialController::class, 'showReport'])->name('provincial.reports.show');
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
Route::middleware(['auth', 'role:executor'])->group(function () {
    Route::get('/executor/dashboard', [ExecutorController::class, 'ExecutorDashboard'])->name('executor.dashboard');
    Route::get('/test-pdf', [TestController::class, 'generatePdf']);

    // Project Application Routes for Executor
    Route::prefix('executor/projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('create', [ProjectController::class, 'create'])->name('projects.create');
        Route::post('store', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('{project_id}', [ProjectController::class, 'show'])->name('projects.show');
        Route::get('{project_id}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
        Route::post('{project_id}/update', [ProjectController::class, 'update'])->name('projects.update');
        Route::get('download/{id}', [AttachmentController::class, 'downloadAttachment'])->name('projects.downloadAttachment');
        Route::get('{project_id}/download-pdf', [ExportController::class, 'downloadPdf'])->name('projects.downloadPdf');
        Route::get('{project_id}/download-doc', [ExportController::class, 'downloadDoc'])->name('projects.downloadDoc');
        // Route::get('download/{id}', [ProjectController::class, 'downloadAttachment'])->name('download.attachment');
        Route::get('download/{id}', [AttachmentController::class, 'downloadAttachment'])->name('download.attachment');


    });

// Monthly  Project Reporting Routes common for Executor
Route::prefix('reports/monthly')->group(function () {
    Route::get('create/{project_id}', [ReportController::class, 'create'])->name('monthly.report.create');
    Route::post('store', [ReportController::class, 'store'])->name('monthly.report.store');
    Route::get('index', [ReportController::class, 'index'])->name('monthly.developmentProject.index');
    Route::get('show/{report_id}', [ReportController::class, 'show'])->name('monthly.developmentProject.show');
    Route::get('edit/{report_id}', [ReportController::class, 'edit'])->name('monthly.developmentProject.edit');
    Route::post('update/{report_id}', [ReportController::class, 'update'])->name('monthly.developmentProject.update');
    Route::get('review/{report_id}', [ReportController::class, 'review'])->name('monthly.developmentProject.review');
    Route::post('revert/{report_id}', [ReportController::class, 'revert'])->name('monthly.developmentProject.revert');
});

    // Monthly Development Project Reporting Routes for Executor
    Route::prefix('reports/monthly/developmentProject')->group(function () {
        Route::get('create/{project_id}', [MonthlyDevelopmentProjectController::class, 'create'])->name('monthly.developmentProject.create');
        Route::post('store', [MonthlyDevelopmentProjectController::class, 'store'])->name('monthly.developmentProject.store');
        Route::get('index', [MonthlyDevelopmentProjectController::class, 'index'])->name('monthly.developmentProject.index');
        Route::get('show/{report_id}', [MonthlyDevelopmentProjectController::class, 'show'])->name('monthly.developmentProject.show');
        Route::get('edit/{report_id}', [MonthlyDevelopmentProjectController::class, 'edit'])->name('monthly.developmentProject.edit');
        Route::post('update/{report_id}', [MonthlyDevelopmentProjectController::class, 'update'])->name('monthly.developmentProject.update');
        Route::get('review/{report_id}', [MonthlyDevelopmentProjectController::class, 'review'])->name('monthly.developmentProject.review');
        Route::post('revert/{report_id}', [MonthlyDevelopmentProjectController::class, 'revert'])->name('monthly.developmentProject.revert');
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

// Allow admin to access all other routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::any('{all}', function () {
        return view('admin.dashboard');
    })->where('all', '.*');
});
