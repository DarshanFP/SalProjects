# M0 — Stabilization Baseline Audit

**Date:** 2026-02-14  
**Milestone:** 0 — Stabilization Baseline  
**Objective:** Comprehensive baseline audit before any refactor. No code, config, or migrations were modified.

---

## 1. Summary of Current Architecture State

- **Financial display:** Most controllers and `BudgetValidationService` use `ProjectFinancialResolver` directly. `ProjectFundFieldsResolver` is used by `BudgetReconciliationController`, `BudgetSyncService`, and `AdminCorrectionService`; its `resolve()` delegates to `ProjectFinancialResolver`, so both are used in parallel with a single underlying implementation.
- **Project update:** `ProjectController@update` wraps the full update in a single `DB::beginTransaction()` / `DB::commit()` / `DB::rollBack()` boundary. Many section controllers start their own `DB::beginTransaction()` when invoked standalone; when called from `ProjectController@update` they run inside the parent transaction.
- **Section persistence:** Multiple section controllers use a delete-then-recreate pattern: bulk delete by `project_id` then insert new rows. No `update_skip_empty_sections` (or similar) config flag exists.
- **Migrations:** Societies and users-province migrations are present; `enforce_unique_name_on_societies` exists. No migration makes `users.province_id` NOT NULL. The `projects` table has no `society_id` or `province_id` column.

---

## 2. Confirmed Migrations

### 2.1 Societies-related

| Migration | Purpose |
|-----------|---------|
| `2026_01_13_144931_create_societies_table.php` | Creates `societies` with `province_id` (FK), unique `['province_id','name']`. |
| `2026_02_10_160000_add_address_to_societies_table.php` | Adds `address` to `societies`. |
| `2026_02_10_235454_make_societies_province_id_nullable.php` | Makes `societies.province_id` nullable (global societies). |
| `2026_02_13_161757_enforce_unique_name_on_societies.php` | Drops composite unique `(province_id, name)`; adds unique on `name`. |
| `2026_01_13_144932_add_society_id_to_centers_table.php` | Adds nullable `society_id` to **centers**, not projects. |

**Confirmed:** Migration `enforce_unique_name_on_societies` **exists** (`2026_02_13_161757_enforce_unique_name_on_societies.php`).

### 2.2 Users province

| Migration | Purpose |
|-----------|---------|
| `2026_01_11_165558_add_province_center_foreign_keys_to_users_table.php` | Adds `province_id` (nullable) to users. |
| `2026_01_11_170202_migrate_existing_provinces_and_centers_data.php` | Data migration for province/center. |
| `2026_01_13_083334_create_provincial_user_province_table.php` | Pivot for general users ↔ provinces. |
| `2026_01_13_083705_migrate_existing_general_users_to_pivot_table.php` | Migrates general users to pivot. |
| `2026_02_10_232014_add_province_id_to_users_table.php` | Ensures `users.province_id` exists (nullable, FK). |

**Confirmed:** No migration sets `users.province_id` to NOT NULL. All user-province migrations add or keep `province_id` **nullable**.

### 2.3 Projects (province_id / society_id)

| Check | Result |
|-------|--------|
| `projects.province_id` | **Does not exist.** Not in `2024_07_20_085634_create_projects_table.php`; no later migration adds it. |
| `projects.society_id` | **Does not exist.** Not in create_projects_table; no migration adds it. |

---

## 3. Resolver Wiring Map

### 3.1 ProjectFinancialResolver (direct)

| Location | Usage |
|----------|--------|
| `app/Http/Controllers/Projects/ProjectController.php` | `show()` — resolved fund fields for display. |
| `app/Http/Controllers/ProvincialController.php` | 5 call sites (project list / show / reports). |
| `app/Http/Controllers/GeneralController.php` | 5 call sites. |
| `app/Http/Controllers/CoordinatorController.php` | 7 call sites. |
| `app/Http/Controllers/ExecutorController.php` | 4 call sites. |
| `app/Http/Controllers/Admin/AdminReadOnlyController.php` | 1 call site. |
| `app/Services/BudgetValidationService.php` | Delegates financial validation to resolver. |
| `tests/Feature/Budget/CoordinatorAggregationParityTest.php` | Unit test. |

### 3.2 ProjectFundFieldsResolver (adapter → ProjectFinancialResolver)

| Location | Usage |
|----------|--------|
| `app/Http/Controllers/Admin/BudgetReconciliationController.php` | Injected; used for reconciliation. |
| `app/Services/Budget/BudgetSyncService.php` | Injected in constructor. |
| `app/Services/Budget/AdminCorrectionService.php` | Injected in constructor. |

**Parallel use:** Yes. Controllers/services above use one or the other. `ProjectFundFieldsResolver::resolve()` delegates to `ProjectFinancialResolver::resolve()`, so there is a single resolution implementation with two entry points.

---

## 4. Delete-Then-Recreate Pattern Scope (Bulk Delete by project_id)

Section controllers that perform **bulk delete** by `project_id` (Model::where('project_id', $id)->delete()) before (re)creating rows:

| # | Controller | Model / table |
|---|------------|----------------|
| 1 | `Projects\BudgetController` | `ProjectBudget` |
| 2 | `Projects\IIES\IIESFamilyWorkingMembersController` | `ProjectIIESFamilyWorkingMembers` |
| 3 | `Projects\IIES\FinancialSupportController` | `ProjectIIESScopeFinancialSupport` |
| 4 | `Projects\IIES\EducationBackgroundController` | `ProjectIIESEducationBackground` |
| 5 | `Projects\LDP\InterventionLogicController` | `ProjectLDPInterventionLogic` |
| 6 | `Projects\RST\TargetGroupController` | `ProjectRSTTargetGroup` |
| 7 | `Projects\RST\InstitutionInfoController` | `ProjectRSTInstitutionInfo` |
| 8 | `Projects\CCI\RationaleController` | `ProjectCCIRationale` |
| 9 | `Projects\CCI\PresentSituationController` | `ProjectCCIPresentSituation` |
| 10 | `Projects\IGE\DevelopmentMonitoringController` | `ProjectIGEDevelopmentMonitoring` |
| 11 | `Projects\IGE\InstitutionInfoController` | `ProjectIGEInstitutionInfo` |
| 12 | `Projects\ILP\RiskAnalysisController` | `ProjectILPRiskAnalysis` |
| 13 | `Projects\ILP\PersonalInfoController` | `ProjectILPPersonalInfo` |
| 14 | `Projects\IAH\IAHSupportDetailsController` | `ProjectIAHSupportDetails` |

**Count:** **14** section controllers use this bulk-delete-by-project_id pattern. (Other section controllers that only do single-record fetch then `->delete()` are not counted.)

---

## 5. Transaction Model Summary

### 5.1 ProjectController@update

- **Uses DB transaction:** Yes.
- **Mechanism:** `DB::beginTransaction()` at start of `update()`, `DB::commit()` on success, `DB::rollBack()` in catch (ValidationException and generic Exception).
- **Scope:** Entire update (general info, key information, common sections, type-specific section controller calls) runs inside this single transaction.

### 5.2 Section controllers that start their own transactions

The following start their own `DB::beginTransaction()` (and corresponding commit/rollBack) when handling requests. When invoked from `ProjectController@update`, they run inside the parent transaction (nested).

- LogicalFrameworkController — uses `DB::transaction()` closure in several methods.
- ILP: AttachedDocumentsController, RiskAnalysisController, PersonalInfoController.
- IAH: IAHDocumentsController, IAHSupportDetailsController, IAHHealthConditionController, IAHPersonalInfoController.
- IIES: IIESAttachmentsController, IIESPersonalInfoController, IIESImmediateFamilyDetailsController, EducationBackgroundController.
- IES: IESAttachmentsController, IESImmediateFamilyDetailsController, IESEducationBackgroundController, IESPersonalInfoController, IESFamilyWorkingMembersController, IESExpensesController.
- CCI: RationaleController, PresentSituationController.
- CICBasicInfoController, ProjectEduRUTBasicInfoController.
- LDP: InterventionLogicController.
- RST: TargetGroupController, InstitutionInfoController, BeneficiariesAreaController, TargetGroupAnnexureController, GeographicalAreaController.
- IGE: DevelopmentMonitoringController, InstitutionInfoController, IGEBeneficiariesSupportedController, OngoingBeneficiariesController, NewBeneficiariesController, IGEBudgetController.
- ILP: RevenueGoalsController, StrengthWeaknessController, BudgetController.
- IAH: IAHEarningMembersController, IAHBudgetDetailsController.
- EduRUT: EduRUTTargetGroupController, EduRUTAnnexedTargetGroupController.
- AttachmentController, SustainabilityController.

**Note:** `NewBeneficiariesController` checks `DB::transactionLevel() > 0` and only starts a new transaction when not already in one.

---

## 6. Feature Flags (config)

### 6.1 Budget / resolver / sync (config/budget.php)

| Key | Purpose |
|-----|---------|
| `resolver_enabled` | BUDGET_RESOLVER_ENABLED (default false). |
| `sync_to_projects_on_type_save` | BUDGET_SYNC_ON_TYPE_SAVE (default false). |
| `sync_to_projects_before_approval` | BUDGET_SYNC_BEFORE_APPROVAL (default false). |
| `restrict_general_info_after_approval` | BUDGET_RESTRICT_GENERAL_INFO_AFTER_APPROVAL (default false). |
| `admin_reconciliation_enabled` | BUDGET_ADMIN_RECONCILIATION_ENABLED (default false). |

### 6.2 Other config

- `config/decimal_bounds.php` — project_budgets / overall_project_budget bounds (not feature flags).
- `config/logging.php` — `budget` log channel for resolver/sync.
- `config/queue.php` — `sync` driver (queue connection), unrelated to budget sync.

### 6.3 update_skip_empty_sections

**Confirmed:** No config key `update_skip_empty_sections` (or `project.update_skip_empty_sections`) exists in the codebase. It appears only in documentation (e.g. SAFE_REFACTOR_STRATEGY, WAVE_IMPLEMENTATION_PLAN) as a proposed flag.

---

## 7. Risks Identified Before Starting Milestone 1

1. **Resolver parity:** `ProjectFinancialResolver` is the single implementation; `ProjectFundFieldsResolver` is a thin adapter. Parity tests (e.g. ProjectFinancialResolver vs ProjectFundFieldsResolver) are referenced in docs but resolver is already wired; any future change to one path without the other could introduce drift.
2. **Delete-then-recreate:** Fourteen section controllers use bulk delete by `project_id`. Empty request payloads can cause full deletion of section data if “skip empty sections” is later added without consistent behavior and tests.
3. **Nested transactions:** Section controllers that start their own transactions run inside `ProjectController@update`’s transaction. Rollback/commit behavior and error handling in nested cases should be kept in mind when changing update flow or section logic.
4. **Projects table:** No `projects.society_id` or `projects.province_id`; project–society/province linkage is via other means (e.g. general info / user). Any future schema change here will need migration and backfill planning.
5. **Users province nullable:** `users.province_id` remains nullable everywhere. Any requirement for NOT NULL will need a dedicated migration and data backfill.
6. **Feature flags:** Budget resolver and sync flags default to false. Enabling them in production should be done with validation and monitoring (e.g. budget log).

---

*End of M0 Baseline Audit. No code, config, or migrations were modified.*
