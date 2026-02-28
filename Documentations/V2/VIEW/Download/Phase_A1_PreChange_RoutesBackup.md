# Phase A1 — Pre-Change Routes Backup

**Rule: Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.**

---

**Date:** 2026-02-23  
**Git branch:** master  
**File:** routes/web.php lines 350–650

---

## Excerpt: routes/web.php (lines 350–650)

```php
   350|    Route::post('/provincial/create-society', [ProvincialController::class, 'storeSociety'])->name('provincial.storeSociety');
   351|    Route::get('/provincial/society/{id}/edit', [ProvincialController::class, 'editSociety'])->name('provincial.editSociety');
   352|    Route::put('/provincial/society/{id}/update', [ProvincialController::class, 'updateSociety'])->name('provincial.updateSociety');
   353|
   354|    // Routes for Provincial Dashboard
   ...
   401|    Route::get('/activities/team-activities', [ActivityHistoryController::class, 'teamActivities'])->name('activities.team-activities');
   402|});
   403|
   404|
   405|
   406|// Executor routes
   407|Route::middleware(['auth', 'role:executor,applicant'])->group(function () {
   408|    Route::get('/executor/dashboard', [ExecutorController::class, 'executorDashboard'])->name('executor.dashboard');
   ...
   446|        Route::post('/projects/{project_id}/mark-completed', [ProjectController::class, 'markCompleted'])->name('projects.markCompleted');
   447|
   448|
   449|    });
   450|
   451|// Monthly  Project Reporting Routes common for Executor
   452|Route::prefix('reports/monthly')->group(function () {
   453|    ...
   473|    Route::post('development-project/store', [MonthlyDevelopmentProjectController::class, 'store'])->name('monthly.developmentProject.store');
   474|});
   475|// Shared route for Executor, Provincial and Coordinator
   476|
   477|// for Projects 9122024 (General has COMPLETE coordinator access; Admin included for download/attachments/activity-history)
   478|Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator,general,admin'])->group(function () {
   479|    Route::get('/projects-list', [ProjectController::class, 'listProjects'])->name('projects.list');
   480|    ...
   511|    Route::get('/reports/{report_id}/activity-history', [ActivityHistoryController::class, 'reportHistory'])->name('reports.activity-history');
   512|});
   513|
   514|// Trash management - accessible to executor, applicant, provincial, coordinator, general, admin
   ...
   601|});
```

*(Full 350–650 content preserved in git; this excerpt shows structure.)*
