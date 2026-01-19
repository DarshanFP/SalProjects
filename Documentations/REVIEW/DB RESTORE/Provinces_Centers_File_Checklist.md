# Provinces & Centers Migration - File Checklist

This document lists all files that need to be updated during the migration from hardcoded provinces/centers to database tables.

---

## 📁 Database Files

### Migrations
- [ ] `database/migrations/YYYY_MM_DD_HHMMSS_create_provinces_table.php` (NEW)
- [ ] `database/migrations/YYYY_MM_DD_HHMMSS_create_centers_table.php` (NEW)
- [ ] `database/migrations/YYYY_MM_DD_HHMMSS_add_province_center_foreign_keys_to_users.php` (NEW)
- [ ] `database/migrations/YYYY_MM_DD_HHMMSS_migrate_existing_provinces_data.php` (NEW)
- [ ] `database/migrations/YYYY_MM_DD_HHMMSS_migrate_existing_centers_data.php` (NEW)

### Seeders
- [ ] `database/seeders/ProvinceSeeder.php` (NEW)
- [ ] `database/seeders/CenterSeeder.php` (NEW)

### Models
- [ ] `app/Models/Province.php` (NEW)
- [ ] `app/Models/Center.php` (NEW)
- [ ] `app/Models/User.php` (UPDATE - Add relationships)

---

## 🎮 Controller Files

### GeneralController.php
**File:** `app/Http/Controllers/GeneralController.php`

**Locations to Update:**
- [ ] Line 133: Remove `$centersMap` array in `generalDashboard()`
- [ ] Line 227: Remove `$centersMap` array in `createCoordinator()`
- [ ] Line 288: Update validation rule `'province' => 'required|in:...'`
- [ ] Line 386: Remove `$centersMap` array in `editCoordinator()`
- [ ] Line 449: Update validation rule `'province' => 'required|in:...'`
- [ ] Line 4595: Update `getCentersMap()` method to query database
- [ ] Line 4650-4685: Update `listProvinces()` to use Province model
- [ ] Line 4675: Update to use Center model relationship
- [ ] Line 4709: Update `storeProvince()` to save to database
- [ ] Line 4783: Update `editProvince()` to use database
- [ ] Line 4926: Update `assignProvincialCoordinator()` to use database
- [ ] Line 604: Remove `$centersMap` array in `createExecutor()`
- [ ] Line 676: Update validation rule `'province' => 'required|in:...'`
- [ ] Line 872: Update validation rule `'province' => 'required|in:...'`
- [ ] All province filtering queries: Update to use `province_id`
- [ ] All center filtering queries: Update to use `center_id`

**Total Updates:** ~15 locations

---

### CoordinatorController.php
**File:** `app/Http/Controllers/CoordinatorController.php`

**Locations to Update:**
- [ ] Line 729: Remove `$centersMap` array in `createProvincial()`
- [ ] Line 805: Update validation rule `'province' => 'required|in:...'`
- [ ] Line 872: Remove `$centersMap` array in `editProvincial()`
- [ ] Line 942: Update validation rule `'province' => 'required|in:...'`
- [ ] All province filtering queries: Update to use `province_id`
- [ ] All center filtering queries: Update to use `center_id`

**Total Updates:** ~6 locations

---

### ProvincialController.php
**File:** `app/Http/Controllers/ProvincialController.php`

**Locations to Update:**
- [ ] Line 596: Remove `$centersMap` array in `createExecutor()`
- [ ] Line 724: Remove `$centersMap` array in `editExecutor()`
- [ ] All center filtering queries: Update to use `center_id`

**Total Updates:** ~3 locations

---

### Report Controllers
**Files to Check:**
- [ ] `app/Http/Controllers/Reports/Aggregated/ReportComparisonController.php`
- [ ] `app/Http/Controllers/Reports/Aggregated/AggregatedQuarterlyReportController.php`
- [ ] `app/Http/Controllers/Reports/Quarterly/InstitutionalSupportController.php`
- [ ] `app/Http/Controllers/Reports/Aggregated/AggregatedAnnualReportController.php`
- [ ] `app/Http/Controllers/Reports/Aggregated/AggregatedHalfYearlyReportController.php`
- [ ] `app/Http/Controllers/Reports/Aggregated/AggregatedReportExportController.php`
- [ ] `app/Http/Controllers/Reports/Monthly/ExportReportController.php`
- [ ] `app/Http/Controllers/Reports/Monthly/CrisisInterventionCenterController.php`
- [ ] `app/Http/Controllers/Reports/Monthly/ReportController.php`
- [ ] `app/Http/Controllers/Projects/ExportController.php`

**Update:** All province/center filtering queries

---

## 🎨 View Files - Province Dropdowns

### Coordinator Views
- [ ] `resources/views/coordinator/createProvincial.blade.php` - Line 40+
- [ ] `resources/views/coordinator/editProvincial.blade.php` - Line 32+
- [ ] `resources/views/coordinator/provincials.blade.php`
- [ ] `resources/views/coordinator/index.blade.php`
- [ ] `resources/views/coordinator/ProjectList.blade.php`
- [ ] `resources/views/coordinator/ReportList.blade.php`
- [ ] `resources/views/coordinator/budget-overview.blade.php`
- [ ] `resources/views/coordinator/budgets.blade.php`
- [ ] `resources/views/coordinator/pendingReports.blade.php`
- [ ] `resources/views/coordinator/approvedProjects.blade.php`
- [ ] `resources/views/coordinator/approvedReports.blade.php`

### General User Views
- [ ] `resources/views/general/coordinators/create.blade.php` - Line 97+
- [ ] `resources/views/general/coordinators/edit.blade.php` - Line 131+
- [ ] `resources/views/general/coordinators/index.blade.php`
- [ ] `resources/views/general/executors/create.blade.php`
- [ ] `resources/views/general/executors/edit.blade.php` - Line 66+
- [ ] `resources/views/general/executors/index.blade.php`
- [ ] `resources/views/general/provinces/index.blade.php`
- [ ] `resources/views/general/provinces/create.blade.php`
- [ ] `resources/views/general/provinces/edit.blade.php`
- [ ] `resources/views/general/provinces/assign-coordinator.blade.php`
- [ ] `resources/views/general/projects/index.blade.php`
- [ ] `resources/views/general/reports/index.blade.php`
- [ ] `resources/views/general/reports/pending.blade.php`
- [ ] `resources/views/general/reports/approved.blade.php`
- [ ] `resources/views/general/budgets/index.blade.php`

### Provincial Views
- [ ] `resources/views/provincial/index.blade.php`
- [ ] `resources/views/provincial/createExecutor.blade.php`
- [ ] `resources/views/provincial/editExecutor.blade.php`
- [ ] `resources/views/provincial/ProjectList.blade.php`

### Project Views
- [ ] `resources/views/projects/partials/general_info.blade.php`
- [ ] `resources/views/projects/partials/Edit/general_info.blade.php`

### Report Views
- [ ] `resources/views/reports/quarterly/institutionalSupport/reportform.blade.php`
- [ ] `resources/views/reports/quarterly/institutionalSupport/show.blade.php`
- [ ] `resources/views/reports/quarterly/developmentProject/reportform.blade.php`

### Widget Views
- [ ] `resources/views/coordinator/widgets/approval-queue.blade.php`
- [ ] `resources/views/coordinator/widgets/provincial-management.blade.php`
- [ ] `resources/views/coordinator/widgets/system-analytics.blade.php`
- [ ] `resources/views/coordinator/widgets/pending-approvals.blade.php`
- [ ] `resources/views/coordinator/widgets/provincial-overview.blade.php`
- [ ] `resources/views/coordinator/widgets/system-performance.blade.php`
- [ ] `resources/views/coordinator/widgets/system-budget-overview.blade.php`
- [ ] `resources/views/coordinator/widgets/province-comparison.blade.php`
- [ ] `resources/views/coordinator/widgets/system-activity-feed.blade.php`
- [ ] `resources/views/general/widgets/direct-team-overview.blade.php`
- [ ] `resources/views/general/widgets/budget-overview.blade.php`
- [ ] `resources/views/general/widgets/budget-charts.blade.php`
- [ ] `resources/views/general/widgets/coordinator-overview.blade.php`
- [ ] `resources/views/general/widgets/activity-feed.blade.php`
- [ ] `resources/views/general/widgets/partials/budget-overview-content.blade.php`
- [ ] `resources/views/general/widgets/partials/pending-items-table.blade.php`
- [ ] `resources/views/provincial/widgets/approval-queue.blade.php`

### Other Views
- [ ] `resources/views/layoutAll/header.blade.php`
- [ ] `resources/views/general/sidebar.blade.php`

**Total View Files:** ~50+ files

---

## 🎨 View Files - Center Dropdowns & JavaScript

### JavaScript Updates Needed
- [ ] Remove hardcoded `centersMap` from all blade files
- [ ] Update JavaScript to fetch centers via API
- [ ] Update province-center filtering logic

**Files with JavaScript centersMap:**
- [ ] `resources/views/general/projects/index.blade.php`
- [ ] `resources/views/general/reports/index.blade.php`
- [ ] `resources/views/general/budgets/index.blade.php`
- [ ] `resources/views/coordinator/index.blade.php`
- [ ] `resources/views/coordinator/ProjectList.blade.php`
- [ ] `resources/views/coordinator/ReportList.blade.php`
- [ ] `resources/views/coordinator/budget-overview.blade.php`
- [ ] `resources/views/coordinator/budgets.blade.php`
- [ ] `resources/views/general/widgets/budget-overview.blade.php`
- [ ] `resources/views/general/widgets/partials/budget-overview-content.blade.php`
- [ ] `resources/views/coordinator/widgets/system-budget-overview.blade.php`
- [ ] All other views with center dropdowns

---

## 🔌 API Routes

### New API Files
- [ ] `routes/api.php` (UPDATE - Add province/center endpoints)
- [ ] `app/Http/Controllers/Api/ProvinceController.php` (NEW)
- [ ] `app/Http/Controllers/Api/CenterController.php` (NEW)

---

## 🧪 Test Files

### New Test Files
- [ ] `tests/Feature/ProvinceTest.php` (NEW)
- [ ] `tests/Feature/CenterTest.php` (NEW)
- [ ] `tests/Unit/ProvinceModelTest.php` (NEW)
- [ ] `tests/Unit/CenterModelTest.php` (NEW)

---

## 📝 Documentation Files

### Update Documentation
- [ ] `Documentations/Add provincial/Guide_to_Add_New_Provinces.md` (UPDATE)
- [ ] `Documentations/REVIEW/5th Review/General User/Province_Management_And_Provincial_Assignment_Implementation_Plan.md` (UPDATE)
- [ ] `README.md` (UPDATE if needed)

---

## 📊 Summary Statistics

| Category | Count |
|----------|-------|
| **New Files** | ~15 |
| **Controller Updates** | 3 main + 10 report controllers |
| **View Updates** | 50+ files |
| **Validation Rules** | 6 locations |
| **CentersMap Arrays** | 12+ locations |
| **Total Files to Update** | ~80+ files |

---

## ✅ Progress Tracking

### Phase 1: Database Setup
- [ ] Migrations created
- [ ] Models created
- [ ] Seeders created
- [ ] Initial data seeded

### Phase 2: Data Migration
- [ ] Existing provinces migrated
- [ ] Existing centers migrated
- [ ] Foreign keys populated
- [ ] Data verified

### Phase 3: Controller - Provinces
- [ ] Validation rules updated (6 locations)
- [ ] Province queries updated
- [ ] GeneralController updated
- [ ] CoordinatorController updated

### Phase 4: Controller - Centers
- [ ] CentersMap arrays removed (12+ locations)
- [ ] Center queries updated
- [ ] All controllers updated

### Phase 5: View Updates
- [ ] Province dropdowns updated (50+ files)
- [ ] Center dropdowns updated
- [ ] JavaScript updated

### Phase 6: API & Relationships
- [ ] API endpoints created
- [ ] Relationships tested

### Phase 7: Management UI
- [ ] Province management enhanced
- [ ] Center management added

### Phase 8: Testing & Cleanup
- [ ] Functional testing complete
- [ ] Data integrity verified
- [ ] Performance tested
- [ ] Code cleaned up

---

**Last Updated:** 2026-01-11  
**Status:** Ready for Implementation
