# Phase 5A — Application Scan Report

**Date:** 2026-02-15  
**Scope:** Full codebase scan for society_name, society dropdowns, province hardcoding, and refactor planning.  
**Constraints:** NO CODE CHANGES. NO SCHEMA CHANGES. Scan and documentation only.

---

## 1. Society Name Usages

### 1.1 Controllers

| File | Usage | Notes |
|------|--------|------|
| `app/Http/Controllers/Projects/ExportController.php` | Line 612: `$project->society_name` in Word export | Display only; must switch to relation when read-switch is implemented. |
| `app/Http/Controllers/Projects/ProjectController.php` | 468: API response `society_name`; 569: store from `$request->society_name`; 1366: update from `$request->society_name` | Create/update persist society_name; getProjectDetails returns it. |
| `app/Http/Controllers/Projects/GeneralInfoController.php` | Update uses validated data (society_name passed through from request) | No direct society_name reference; receives from FormRequest. |
| `app/Http/Controllers/Projects/OldDevelopmentProjectController.php` | 29: validation `society_name` required; 55: `$request->society_name` on create | Legacy project type. |
| `app/Http/Controllers/GeneralController.php` | 449, 600: validation nullable society_name; 475, 624, 1014: persist `$request->society_name` (provincial/executor); 829, 989: required for executor; 850, 1014: create/update; 5621, 5699, 5741: society CRUD log (society_name from request name) | Provincial create/update, executor create/update; society CRUD logs. |
| `app/Http/Controllers/ProvincialController.php` | 682, 784: required society_name (coordinator create/update); 713, 810: persist; 1140, 1242: nullable; 1161, 1262: persist; 1362, 1440: society CRUD (name) | Provincial user and coordinator flows; society create/update. |
| `app/Http/Controllers/Reports/Monthly/ReportController.php` | 252: validation; 404, 1553: store/update report `society_name` | Monthly report create/update. |
| `app/Http/Controllers/Reports/Monthly/ExportReportController.php` | 432: Word export "Society Name: {$report->society_name}" | Report export. |
| `app/Http/Controllers/Reports/Monthly/MonthlyDevelopmentProjectController.php` | 305: validation society_name nullable | DP report form. |
| `app/Http/Controllers/Reports/Quarterly/*` | InstitutionalSupport, DevelopmentLivelihood, WomenInDistress, SkillTraining: validation `society_name` nullable | Quarterly report forms. |

### 1.2 Blade Views

| File | Usage | Notes |
|------|--------|------|
| `resources/views/projects/partials/general_info.blade.php` | Create: label/select `society_name`, 9 hardcoded `<option>`, JS `data.society_name` | Project create (General); must become society_id + province-scoped. |
| `resources/views/projects/partials/Edit/general_info.blade.php` | Edit: three blocks — (1) hardcoded 9 options, (2) @foreach array of 9 names, (3) same 9 options; `name="society_name"`, selected from `$project->society_name` | Project edit; duplicate blocks (different sections of same file). |
| `resources/views/projects/partials/Show/general_info.blade.php` | Display: `<td>{{ $project->society_name }}</td>` | Read-only display. |
| `resources/views/projects/partials/OLdshow/general_info.blade.php` | Display: `$project->society_name` | Legacy show partial. |
| `resources/views/general/executors/edit.blade.php` | Select `society_name`, 9 hardcoded options, selected from `$executor->society_name` | General executor edit. |
| `resources/views/general/executors/create.blade.php` | Select `society_name` disabled until province chosen; JS populates from `societiesMap` (province → societies by name) | General executor create; already province-scoped in JS but value is name. |
| `resources/views/general/executors/index.blade.php` | Table column `$executor->society_name ?: 'N/A'` | List display. |
| `resources/views/general/provincials/edit.blade.php` | Select `society_name`; JS populates from `provinces[].active_societies` (option value = society.name) | General provincial edit; province-scoped via JS. |
| `resources/views/general/provincials/create.blade.php` | Select `society_name`; JS populates from provinces (same pattern as edit) | General provincial create. |
| `resources/views/provincial/provincials/edit.blade.php` | Select `society_name`; options from `$societies` (Server-side: `Society::where('province_id', $province->id)`) | Provincial edit; already province-scoped; uses name as value. |
| `resources/views/provincial/provincials/create.blade.php` | Select `society_name`; options from `$societies` (province-scoped) | Provincial create; same. |
| `resources/views/provincial/provincials/index.blade.php` | Column `$provincialUser->society_name ?? 'N/A'` | List display. |
| `resources/views/provincial/editExecutor.blade.php` | Select `society_name`, 9 hardcoded options | Provincial executor edit. |
| `resources/views/provincial/createExecutor.blade.php` | Select `society_name`, 9 hardcoded options | Provincial executor create. |
| `resources/views/reports/monthly/developmentProject/reportform.blade.php` | Input `name="society_name"` readonly `$user->society_name` | Monthly DP report form. |
| `resources/views/reports/monthly/ReportCommonForm.blade.php` | Input `society_name` readonly `$user->society_name` | Common monthly report. |
| `resources/views/reports/monthly/ReportAll.blade.php` | Input `society_name` readonly `$user->society_name` | Report form. |
| `resources/views/reports/monthly/edit.blade.php` | Input `society_name` readonly `$report->society_name` | Monthly report edit. |
| `resources/views/reports/quarterly/*/reportform.blade.php` | Various: developmentProject uses `$user->province` for society_name input; others plain input | Mixed; some misuse province for society_name label. |

### 1.3 Validation Rules

| File | Rule | Notes |
|------|------|------|
| `app/Http/Requests/Projects/UpdateGeneralInfoRequest.php` | `society_name` => nullable\|string\|max:255 | Project general info update. |
| `app/Http/Requests/Projects/UpdateProjectRequest.php` | `society_name` => nullable\|string\|max:255; attribute 'society name' | Project update. |
| `app/Http/Requests/Projects/StoreProjectRequest.php` | `society_name` => nullable\|string\|max:255 | Project create. |
| `app/Http/Requests/Projects/StoreGeneralInfoRequest.php` | `society_name` => nullable\|string\|max:255 | General info store. |
| `app/Http/Requests/Reports/Monthly/StoreMonthlyReportRequest.php` | `society_name` => nullable\|string\|max:255 | Monthly report store. |
| `app/Http/Requests/Reports/Monthly/UpdateMonthlyReportRequest.php` | `society_name` => nullable\|string\|max:255 | Monthly report update. |

### 1.4 Reports / Exports

| File | Usage | Notes |
|------|--------|------|
| `app/Http/Controllers/Projects/ExportController.php` | Word section "Society Name: {$project->society_name}" | Project export. |
| `app/Http/Controllers/Reports/Monthly/ExportReportController.php` | Word "Society Name: {$report->society_name}" | Monthly report export. |
| `app/Services/Reports/QuarterlyReportService.php` | Payload `society_name` => $project->society_name | Quarterly report data. |
| `app/Services/Reports/AnnualReportService.php` | Payload `society_name` => $project->society_name | Annual report data. |
| `app/Services/Reports/HalfYearlyReportService.php` | Payload `society_name` => $project->society_name | Half-yearly report data. |
| `app/Services/AI/ReportDataPreparer.php` | `society_name` => $report->society_name | AI/report prep. |
| `app/Models/Reports/Monthly/DPReport.php` | Fillable/PHPDoc society_name | Model. |
| `app/Models/Reports/Annual/AnnualReport.php` | Fillable society_name | Model. |
| `app/Models/Reports/Quarterly/QuarterlyReport.php` | Fillable society_name | Model. |
| `app/Models/Reports/HalfYearly/HalfYearlyReport.php` | Fillable society_name | Model. |

### 1.5 API Endpoints

| Location | Usage | Notes |
|----------|--------|------|
| `ProjectController::getProjectDetails` | Response key `society_name` => $project->society_name | JSON API for project details. |

### 1.6 Seeder / Commands

| File | Usage | Notes |
|------|--------|------|
| `database/seeders/SocietySeeder.php` | Lines 79–82, 96–99: `whereNotNull('society_name')->where('society_name', '!=', '')->pluck('society_name')` on projects and users | Builds list of distinct society names from legacy data. |
| `app/Console/Commands/SocietiesAuditCommand.php` | Multiple: join projects/users on societies.name; where society_name; count distinct society_name | Audit/resolution rate; no write. |
| `app/Console/Commands/SeedCanonicalSocietiesCommand.php` | Canonical names list (8 names); creates Society by name | Seeder for canonical societies; no society_name in request. |

### 1.7 Authorization Logic

| File | Usage | Notes |
|------|--------|------|
| `app/Helpers/ProjectPermissionHelper.php` | No society_name or society_id checks | Permissions are role + ownership/in-charge; no society-based restriction. |
| `app/Helpers/LogHelper.php` | `getProjectAllowedFields()` includes `society_name` | Allowed field list for logging/filtering. |

---

## 2. Hardcoded Dropdown Locations

| File | Role Context | Data Source | Refactor Needed |
|------|----------------|-------------|------------------|
| `resources/views/projects/partials/general_info.blade.php` | General (project create) | 9 hardcoded `<option>` strings | **Yes** — replace with society_id; options from DB, province-scoped for provincial users. |
| `resources/views/projects/partials/Edit/general_info.blade.php` | General / Provincial (project edit) | Block 1 & 3: 9 hardcoded options; Block 2: PHP array of same 9 names | **Yes** — same as above; unify to single society_id select, options from controller. |
| `resources/views/general/executors/edit.blade.php` | General | 9 hardcoded options | **Yes** — province-scoped Society list (or JS like create). |
| `resources/views/general/executors/create.blade.php` | General | JS: societiesMap (province → societies); value = society.name | **Yes** — switch to society_id; keep province-scoped. |
| `resources/views/general/provincials/edit.blade.php` | General | JS: provinces[].active_societies; value = society.name | **Yes** — switch to society_id. |
| `resources/views/general/provincials/create.blade.php` | General | JS: same pattern | **Yes** — switch to society_id. |
| `resources/views/provincial/provincials/edit.blade.php` | Provincial | Server: `Society::where('province_id', $province->id)`; value = $society->name | **Yes** — change to value="{{ $society->id }}" and name="society_id"; backend persist society_id. |
| `resources/views/provincial/provincials/create.blade.php` | Provincial | Same | **Yes** — same as edit. |
| `resources/views/provincial/editExecutor.blade.php` | Provincial | 9 hardcoded options | **Yes** — replace with province-scoped Society list (server or JS). |
| `resources/views/provincial/createExecutor.blade.php` | Provincial | 9 hardcoded options | **Yes** — same. |

**Summary:** All project and user (executor/provincial) society dropdowns currently use string `society_name` and either hardcoded lists or dynamic lists keyed by name. All need to move to `society_id` with province-scoped options (and for General, include global societies where applicable).

---

## 3. Province Hardcoding

- **Province as string (legacy):** Controllers still validate and persist `province` (e.g. `exists:provinces,name`) and set `User.province` and `User.province_id`. No hardcoded list of province *names* in dropdowns in app code; province options come from `Province::active()` or equivalent.
- **Province in views:** Edit forms use `$province->name` or `$user->province` for display/select. In-charge and executor dropdowns filter by `$potential_in_charge->province == $user->province` (string comparison) in `Edit/general_info.blade.php` — correct by province, no society filter.
- **Province_name in logs:** `GeneralController` and migrations use `province_name` in pivot/migration context only; not user-facing hardcoding.
- **Conclusion:** No literal province name lists in code; province-scoping is done via `province_id` in ProvincialController for societies and via `province` string on User in views. Refactor should standardize on province_id where needed and keep society lists province-scoped.

---

## 4. Queries Filtering by society_name

| File | Query / Usage | Correction Needed |
|------|----------------|-------------------|
| `app/Console/Commands/SocietiesAuditCommand.php` | `leftJoin('societies', 'projects.society_name', '=', 'societies.name')`; `where('society_name', '!=', '')`; same for users; count distinct society_name | Audit only; when reads switch to society_id, audit can use society_id. |
| `database/seeders/SocietySeeder.php` | `whereNotNull('society_name')->where('society_name', '!=', '')->pluck('society_name')` (projects and users) | Seeder for legacy; post-migration can use society_id. |
| `app/Services/ProjectQueryService.php` | `applySearchFilter`: `orWhere('society_name', 'like', "%{$searchTerm}%")` | **Yes** — add search on society relation name (or keep society_name for backward compat until read-switch). |

No application authorization or listing queries currently filter projects by `society_name`; project listing uses role/ownership/province, not society.

---

## 5. Report / Export Dependencies

- **Project export (Word):** `ExportController` uses `$project->society_name`. After read-switch, use `$project->society?->name` or equivalent.
- **Monthly report:** Stored and displayed `society_name`; validation and export use it. After read-switch, reports can carry society_id and display via relation.
- **Quarterly/Annual/Half-yearly:** Report services pull `society_name` from project; report models have `society_name` in fillable. Refactor: add society_id to reports when needed, display from relation, keep or phase out society_name column per migration plan.
- **AI ReportDataPreparer:** Uses `$report->society_name`; switch to relation when report model has it.

---

## 6. Society List Queries (Province-Based Visibility)

| File | Query | Province-Scoped? | Correction Needed |
|------|--------|------------------|-------------------|
| `GeneralController::createProvincial`, `editProvincial`, `createExecutor` | `Province::active()->with(['activeSocieties'])` | Yes (societies per province) | Ensure global societies (province_id NULL) included where General can assign; currently only province-owned. |
| `ProvincialController::createProvincial`, `editProvincial` | `Society::where('province_id', $province->id)->where('is_active', true)` | Yes | None; already correct. |
| `GeneralController::centers index` | `Society::active()->with('province')->orderBy('name')->get()` | No (all societies) | General context; OK. For project create/edit, need to pass societies (e.g. by province + global) for dropdown. |
| `ProvincialController` society CRUD | `Society::where('province_id', $province->id)` | Yes | None. |

**Gap:** Project create/edit (General and Provincial) do not currently receive a `$societies` (or equivalent) from controller; views use hardcoded names. Refactor: controller should pass province-scoped (and global) societies for dropdown; view uses society_id.

---

## 7. Dropdown Refactor Requirements (Matrix)

| Location | Current Field | Current Source | Target Field | Target Source | Province-Scoped |
|----------|---------------|----------------|--------------|---------------|-----------------|
| Project create (general_info) | society_name | Hardcoded 9 options | society_id | Controller: societies (global + by province for provincial) | Yes for provincial; General can show all or by province |
| Project edit (Edit/general_info) | society_name | Hardcoded / array | society_id | Controller: societies | Same |
| General executor create | society_name | JS societiesMap (name) | society_id | JS societiesMap id; backend province + societies | Yes |
| General executor edit | society_name | Hardcoded 9 | society_id | Controller: provinces + activeSocieties (or equivalent) | Yes |
| General provincial create/edit | society_name | JS active_societies (name) | society_id | JS pass id; backend persist society_id | Yes |
| Provincial provincial create/edit | society_name | Server $societies (name) | society_id | Server $societies (id); backend society_id | Yes |
| Provincial executor create/edit | society_name | Hardcoded 9 | society_id | Server: Society::where(province_id) | Yes |

---

## 8. Authorization Gaps

- **Project access:** No society-based checks today; access is by role (admin/coordinator/provincial see scope) and ownership/in-charge. After society_id is enforced and read-switch is in place, consider whether provincial must be restricted to projects of their province’s societies (already implied by project list filtering; no explicit society filter found).
- **User/society:** Provincial can only manage users in their province; society is stored as society_name. After switching to society_id, ensure provincial cannot assign society_id from another province.

---

## 9. Refactor Execution Plan (Phase 5B Waves)

### Phase 5B1 — Replace dropdowns with relational society_id

- **Scope:** All forms that submit society (project create/edit, executor create/edit, provincial create/edit).
- **Tasks:**  
  - Add `society_id` to project and user create/update validation and persistence (dual-write with society_name if required by plan).  
  - Replace every `<select name="society_name">` with `<select name="society_id">` and option `value="{{ $society->id }}"`.  
  - For General: pass societies (with province info or grouped) from controller; for Provincial: pass `Society::where('province_id', $province->id)` (and active).  
  - Update GeneralController and ProvincialController to accept and store society_id; keep storing society_name for backward compatibility if in scope.
- **Files:** All blade files in §2; ProjectController, GeneralInfoController, GeneralController, ProvincialController; StoreProjectRequest, UpdateProjectRequest, StoreGeneralInfoRequest, UpdateGeneralInfoRequest; request validation for provincial/executor.

### Phase 5B2 — Implement province-scoped dropdown filtering

- **Scope:** Every society dropdown.
- **Tasks:**  
  - General project create/edit: load societies (e.g. active + with province); for provincial user, filter options by user’s province_id (and optionally global).  
  - General executor/provincial: already province-driven; ensure options are by province and use society_id.  
  - Provincial: already province-scoped; ensure backend only accepts society_id belonging to their province.
- **Files:** Controllers that pass societies to views; JS that builds society options (general provincials/executors) to use id and optionally validate province.

### Phase 5B3 — Switch reads to relation

- **Scope:** Display and export of society.
- **Tasks:**  
  - Replace `$project->society_name` with `$project->society?->name` (and ensure project loads society relation where needed).  
  - Replace `$report->society_name` with report’s society relation if/when report has society_id.  
  - Replace `$user->society_name` display with `$user->society?->name` where user has society_id.  
  - ExportController (project), ExportReportController (monthly report), report services, ReportDataPreparer, getProjectDetails API.
- **Files:** ExportController, ExportReportController, ProjectController (getProjectDetails), Show general_info, report views, QuarterlyReportService, AnnualReportService, HalfYearlyReportService, ReportDataPreparer, LogHelper allowed fields if needed.

### Phase 5B4 — Remove hardcoded society strings

- **Scope:** Remove all 9-name hardcoded arrays and options.
- **Tasks:**  
  - Remove hardcoded option lists from general_info (create), Edit/general_info (all three blocks), general/executors/edit, provincial editExecutor/createExecutor.  
  - Remove any PHP arrays of canonical names used only for dropdowns (keep SeedCanonicalSocietiesCommand list for seeding if separate).  
  - Fix typo "ST. ANNS'S" in views if still present.
- **Files:** Listed blade files; no new files.

### Phase 5B5 — Regression verification

- **Scope:** Full regression.
- **Tasks:**  
  - Project create/edit (General and Provincial): society_id saved and displayed.  
  - Executor and provincial user create/edit: society_id saved and displayed.  
  - Reports and exports: society name still correct (from relation or legacy column).  
  - Search: ProjectQueryService society search still works (or updated to relation).  
  - SocietiesAuditCommand and any backfill: still runnable; update to use society_id where appropriate.

---

## 10. Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Dual-write inconsistency (society_id vs society_name) | Medium | High | Implement dual-write in single place; validate resolution (society_id ↔ name) in tests; run SocietiesAuditCommand. |
| Province-scoping bug (wrong society in dropdown) | Medium | High | Only pass societies for user’s province (and global if allowed); validate society_id belongs to province on server. |
| Report/export break (missing society name) | Low | Medium | Prefer relation with fallback to society_name until column removed; test all export paths. |
| Legacy data (society_name set, society_id null) | High | Medium | Backfill project and user society_id from society_name before or with read-switch; keep society_name until backfill verified. |
| JS dropdown (General) sends name instead of id | Medium | High | Change all JS to use society.id as value and name="society_id"; validate in backend. |

---

## 11. Summary

- **Society name usages:** Categorized in §1 (Controllers, Blade, Validation, Reports/Exports, API, Seeder/Commands, Helpers). No authorization logic currently uses society_name/society_id.
- **Hardcoded dropdowns:** 10 locations (§2); all need replacement with society_id and DB-driven, province-scoped options.
- **Province hardcoding:** No literal province lists; province-scoping is via province_id or User.province.
- **Queries by society_name:** SocietiesAuditCommand, SocietySeeder, ProjectQueryService search; application listing does not filter by society_name.
- **Reports/exports:** Depend on project or report society_name; must switch to relation (or society_id + relation) in Phase 5B3.
- **Refactor waves:** 5B1 (dropdowns → society_id), 5B2 (province-scoped options), 5B3 (reads from relation), 5B4 (remove hardcoded names), 5B5 (regression).
- **No code or schema was modified in this scan.**
