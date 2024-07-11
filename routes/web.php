<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\ExecutorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProvincialController;
use App\Http\Controllers\Reports\Quarterly\DevelopmentLivelihoodController;
use App\Http\Controllers\Reports\Quarterly\DevelopmentProjectController;
use App\Http\Controllers\Reports\Quarterly\InstitutionalSupportController;
use App\Http\Controllers\Reports\Quarterly\SkillTrainingController;
use App\Http\Controllers\Reports\Quarterly\WomenInDistressController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', function () {
    return view('auth.login');
});

//logout for all (except admin i think)
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');
    Route::post('/profile/update-password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
});

require __DIR__.'/auth.php';

// Registration Routes
Route::get('register', [RegisteredUserController::class, 'create'])
    ->middleware('guest')
    ->name('register');

Route::post('register', [RegisteredUserController::class, 'store'])
    ->middleware('guest');

// Admin routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'AdminDashboard'])->name('admin.dashboard');
    Route::get('/admin/logout', [AdminController::class, 'AdminLogout'])->name('admin.logout');
    // Admin has access to all other routes, so no need to duplicate routes here
});

// Coordinator routes
Route::middleware(['auth', 'role:coordinator'])->group(function () {
    Route::get('/coordinator/dashboard', [CoordinatorController::class, 'CoordinatorDashboard'])->name('coordinator.dashboard');
});

// Provincial routes
Route::middleware(['auth', 'role:provincial'])->group(function () {
    Route::get('/provincial/dashboard', [ProvincialController::class, 'ProvincialDashboard'])->name('provincial.dashboard');
    //manage executors
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

// Executor routes
Route::middleware(['auth', 'role:executor'])->group(function () {
    Route::get('/executor/dashboard', [ExecutorController::class, 'ExecutorDashboard'])->name('executor.dashboard');

    // Reports Routes for Executor
    Route::prefix('reports')->group(function () {
        Route::prefix('quarterly')->group(function () {

            // Development Project Reportings
            Route::prefix('development-project')->group(function () {
                Route::get('create', [DevelopmentProjectController::class, 'create'])->name('quarterly.developmentProject.create');
                Route::post('store', [DevelopmentProjectController::class, 'store'])->name('quarterly.developmentProject.store');
                Route::get('{id}/edit', [DevelopmentProjectController::class, 'edit'])->name('quarterly.developmentProject.edit');
                Route::put('{id}', [DevelopmentProjectController::class, 'update'])->name('quarterly.developmentProject.update');
                Route::get('{id}/review', [DevelopmentProjectController::class, 'review'])->name('quarterly.developmentProject.review');
                Route::post('{id}/revert', [DevelopmentProjectController::class, 'revert'])->name('quarterly.developmentProject.revert');
                Route::get('list', [DevelopmentProjectController::class, 'index'])->name('quarterly.developmentProject.index');
            });

            // Skill Training Reportings
            Route::prefix('skill-training')->group(function () {
                Route::get('create', [SkillTrainingController::class, 'create'])->name('quarterly.skillTraining.create');
                Route::post('store', [SkillTrainingController::class, 'store'])->name('quarterly.skillTraining.store');
                Route::get('{id}/edit', [SkillTrainingController::class, 'edit'])->name('quarterly.skillTraining.edit');
                Route::put('{id}', [SkillTrainingController::class, 'update'])->name('quarterly.skillTraining.update');
                Route::get('{id}/review', [SkillTrainingController::class, 'review'])->name('quarterly.skillTraining.review');
                Route::post('{id}/revert', [SkillTrainingController::class, 'revert'])->name('quarterly.skillTraining.revert');
                Route::get('list', [SkillTrainingController::class, 'index'])->name('quarterly.skillTraining.index');
            });

            // Development Livelihood Reportings
            Route::prefix('development-livelihood')->group(function () {
                Route::get('create', [DevelopmentLivelihoodController::class, 'create'])->name('quarterly.developmentLivelihood.create');
                Route::post('store', [DevelopmentLivelihoodController::class, 'store'])->name('quarterly.developmentLivelihood.store');
                Route::get('{id}/edit', [DevelopmentLivelihoodController::class, 'edit'])->name('quarterly.developmentLivelihood.edit');
                Route::put('{id}', [DevelopmentLivelihoodController::class, 'update'])->name('quarterly.developmentLivelihood.update');
                Route::get('{id}/review', [DevelopmentLivelihoodController::class, 'review'])->name('quarterly.developmentLivelihood.review');
                Route::post('{id}/revert', [DevelopmentLivelihoodController::class, 'revert'])->name('quarterly.developmentLivelihood.revert');
                Route::get('list', [DevelopmentLivelihoodController::class, 'index'])->name('quarterly.developmentLivelihood.index');

            });

            // Institutional Support Reportings
            Route::prefix('institutional-support')->group(function () {
                Route::get('create', [InstitutionalSupportController::class, 'create'])->name('quarterly.institutionalSupport.create');
                Route::post('store', [InstitutionalSupportController::class, 'store'])->name('quarterly.institutionalSupport.store');
                Route::get('{id}/edit', [InstitutionalSupportController::class, 'edit'])->name('quarterly.institutionalSupport.edit');
                Route::put('{id}', [InstitutionalSupportController::class, 'update'])->name('quarterly.institutionalSupport.update');
                Route::get('{id}/review', [InstitutionalSupportController::class, 'review'])->name('quarterly.institutionalSupport.review');
                Route::post('{id}/revert', [InstitutionalSupportController::class, 'revert'])->name('quarterly.institutionalSupport.revert');
                Route::get('list', [InstitutionalSupportController::class, 'index'])->name('quarterly.institutionalSupport.index');
            });

            // Women in Distress Reportings
            Route::prefix('women-in-distress')->group(function () {
                Route::get('create', [WomenInDistressController::class, 'create'])->name('quarterly.womenInDistress.create');
                Route::post('store', [WomenInDistressController::class, 'store'])->name('quarterly.womenInDistress.store');
                Route::get('{id}/edit', [WomenInDistressController::class, 'edit'])->name('quarterly.womenInDistress.edit');
                Route::put('{id}', [WomenInDistressController::class, 'update'])->name('quarterly.womenInDistress.update');
                Route::get('{id}/review', [WomenInDistressController::class, 'review'])->name('quarterly.womenInDistress.review');
                Route::post('{id}/revert', [WomenInDistressController::class, 'revert'])->name('quarterly.womenInDistress.revert');
                Route::get('list', [WomenInDistressController::class, 'index'])->name('quarterly.womenInDistress.index');
            });
        });
    });
});

// Allow admin to access all other routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::any('{all}', function () {
        return view('admin.dashboard');
    })->where('all', '.*');
});
