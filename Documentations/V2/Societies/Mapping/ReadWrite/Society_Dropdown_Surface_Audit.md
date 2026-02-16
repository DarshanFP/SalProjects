# Phase 5B1.1 — Society Dropdown Surface Audit

**Date:** 2026-02-15  
**Purpose:** Confirm all society dropdown and society selection surfaces before read-switch.  
**Constraints:** NO CODE MODIFICATIONS. NO SCHEMA CHANGES. Analysis only.

---

## 1. Summary

| Metric | Count |
|--------|--------|
| **Modules scanned** | 8 (Projects, Users, Provincial, Centers, Budget, Reports, Societies CRUD, Other) |
| **Blade files with society dropdown or selection** | 14 (excluding compiled `storage/framework/views`) |
| **Dropdowns still using `society_name` (form submit)** | 8 (executor create/edit ×2 contexts, provincial create/edit ×2 contexts) |
| **Dropdowns using `society_id`** | 5 (project create + edit partials, centers filter) |
| **Readonly/display-only society fields** | 7 (report forms, no refactor for dropdown) |
| **Controllers persisting `society_name` from request** | 4 (General, Provincial, OldDevelopmentProject) |
| **Services/Exports reading `society_name`** | 6 |

---

## 2. Detailed Findings

### 2.1 Matrix: Module × File × Type × society_name / society_id / Refactor

| Module | File | Type | Uses society_name | Uses society_id | Needs Refactor? |
|--------|------|------|-------------------|-----------------|-----------------|
| **Projects** | `resources/views/projects/partials/general_info.blade.php` | Dropdown | No (removed) | Yes | No (done in 5B1) |
| **Projects** | `resources/views/projects/partials/Edit/general_info.blade.php` | Dropdown | No (removed) | Yes | No (done in 5B1) |
| **Projects** | `app/Http/Controllers/Projects/ProjectController.php` | Log only | Log line 575 (request) | create/edit/update | Optional: change log to society_id |
| **Projects** | `app/Http/Controllers/Projects/GeneralInfoController.php` | Dual-write | Yes (from society_id) | Yes | No |
| **Projects** | `app/Http/Controllers/Projects/ExportController.php` | Export | Yes (display) | No | Yes (read-switch phase) |
| **Projects** | `app/Http/Controllers/Projects/OldDevelopmentProjectController.php` | Validation + persist | Yes | No | Yes (legacy module) |
| **Users** | `resources/views/general/executors/edit.blade.php` | Dropdown | Yes (name + hardcoded options) | No | Yes |
| **Users** | `resources/views/general/executors/create.blade.php` | Dropdown | Yes (name, JS-populated) | No | Yes |
| **Users** | `resources/views/provincial/editExecutor.blade.php` | Dropdown | Yes (name + hardcoded options) | No | Yes |
| **Users** | `resources/views/provincial/createExecutor.blade.php` | Dropdown | Yes (name + hardcoded options) | No | Yes |
| **Provincial** | `resources/views/general/provincials/edit.blade.php` | Dropdown | Yes (name, JS options) | No | Yes |
| **Provincial** | `resources/views/general/provincials/create.blade.php` | Dropdown | Yes (name, JS options) | No | Yes |
| **Provincial** | `resources/views/provincial/provincials/edit.blade.php` | Dropdown | Yes (value = $society->name) | No | Yes |
| **Provincial** | `resources/views/provincial/provincials/create.blade.php` | Dropdown | Yes (value = $society->name) | No | Yes |
| **Provincial** | `app/Http/Controllers/GeneralController.php` | Validation + persist | Yes (provincial/executor) | Yes (centers filter) | Yes (user forms) |
| **Provincial** | `app/Http/Controllers/ProvincialController.php` | Validation + persist | Yes (provincial/coordinator) | No | Yes |
| **Centers** | `resources/views/general/centers/index.blade.php` | Filter dropdown | No | Yes | No |
| **Centers** | `app/Http/Controllers/GeneralController.php` | Filter (society_id) | No | Yes (centers) | No |
| **Reports** | `resources/views/reports/monthly/developmentProject/reportform.blade.php` | Readonly input | Yes (display) | No | Read-switch only |
| **Reports** | `resources/views/reports/monthly/ReportCommonForm.blade.php` | Readonly input | Yes (display) | No | Read-switch only |
| **Reports** | `resources/views/reports/monthly/ReportAll.blade.php` | Readonly input | Yes (display) | No | Read-switch only |
| **Reports** | `resources/views/reports/monthly/edit.blade.php` | Readonly input | Yes (display) | No | Read-switch only |
| **Reports** | `resources/views/reports/quarterly/*/reportform.blade.php` (multiple) | Input/readonly | Yes (label or value) | No | Read-switch only |
| **Reports** | `app/Http/Controllers/Reports/Monthly/ReportController.php` | Validation + persist | Yes | No | Read-switch + optional society_id |
| **Reports** | `app/Http/Controllers/Reports/Monthly/ExportReportController.php` | Export | Yes | No | Read-switch phase |
| **Reports** | `app/Services/Reports/QuarterlyReportService.php` | Payload | Yes (project) | No | Read-switch phase |
| **Reports** | `app/Services/Reports/AnnualReportService.php` | Payload | Yes (project) | No | Read-switch phase |
| **Reports** | `app/Services/Reports/HalfYearlyReportService.php` | Payload | Yes (project) | No | Read-switch phase |
| **Reports** | `app/Services/AI/ReportDataPreparer.php` | Payload | Yes (report) | No | Read-switch phase |
| **Societies CRUD** | `resources/views/general/societies/index.blade.php` | List (foreach) | N/A (list) | N/A | No |
| **Societies CRUD** | `resources/views/provincial/societies/index.blade.php` | List (foreach) | N/A (list) | N/A | No |
| **Other** | `app/Services/ProjectQueryService.php` | Search filter | Yes (like) | No | Add relation search in read-switch |
| **Other** | `app/Console/Commands/SocietiesAuditCommand.php` | Audit (join/filter) | Yes | No | Keep for legacy audit |
| **Other** | `database/seeders/SocietySeeder.php` | Seeder | Yes (pluck) | No | Legacy/backfill only |
| **Other** | `app/Helpers/LogHelper.php` | Allowed fields | Yes | No | Optional |
| **Other** | `app/Http/Requests/Projects/StoreGeneralInfoRequest.php` | Validation | Yes (nullable) | No | Already has society_id in store flow via StoreProjectRequest |

---

## 3. Modules Fully Converted (society_id dropdown + validation)

| Module | Scope | Notes |
|--------|--------|--------|
| **Projects** | Project create and project edit (general info) | Phase 5B1: dropdown is `society_id`, options from `$societies`, validation and dual-write in place. |
| **Centers** | General centers index filter | Filter uses `name="society_id"` and `$societies`; no form submit for creating centers with society_name. |

---

## 4. Modules Requiring Refactor (still society_name or hardcoded)

| Module | Surfaces | Refactor needed |
|--------|----------|------------------|
| **Users (Executors)** | General executor create/edit, Provincial executor create/edit | Replace `name="society_name"` with `name="society_id"`; replace hardcoded 9 options with `$societies` (province-scoped); backend validate and persist society_id + dual-write society_name. |
| **Provincial (Provincial users)** | General provincials create/edit (JS dropdown), Provincial provincials create/edit (server dropdown) | Change to `society_id`; options already from DB but value is `$society->name` → use `$society->id`; backend accept society_id and dual-write society_name. |
| **Projects (Legacy)** | OldDevelopmentProjectController | Still validates and persists society_name; either convert to society_id or leave as legacy. |
| **Reports** | Monthly/quarterly report forms (readonly society_name), report store/update validation, report export and report services | No dropdown refactor; read-switch: display from relation or keep society_name column; add society_id to reports if desired. |
| **Exports** | ExportController (project Word), ExportReportController (monthly report Word) | Read-switch: use `$project->society?->name` or `$report->society?->name` when relation exists; fallback to society_name. |

---

## 5. Risk Assessment

### 5.1 Can we proceed to read-switch?

- **Project create/edit:** Yes. Forms send only `society_id`; dual-write keeps `society_name` in sync. Read-switch can show `$project->society?->name` with fallback to `$project->society_name` for old rows.
- **User (executor/provincial) forms:** Still submit `society_name`. If read-switch is applied only to **projects** (display/export project society via relation), then no conflict. If read-switch is applied to **users** (e.g. display user’s society via relation), then user forms still writing `society_name` are acceptable as long as users.society_id is backfilled or set by a separate flow; otherwise keep displaying user.society_name until user forms are converted.
- **Reports:** Report tables and payloads use `society_name`. Read-switch for reports would mean adding report.society_id and/or displaying from relation; until then, continue using report.society_name.

### 5.2 Any hidden legacy path?

- **OldDevelopmentProjectController:** Uses society_name only; legacy project type. Ensure it is either in scope for refactor or explicitly excluded from read-switch.
- **ProjectController@store log:** Still logs `$request->society_name` (form now sends society_id); log may show empty. Low risk; optional cleanup.
- **StoreGeneralInfoRequest:** Still has `society_name` nullable; project store uses StoreProjectRequest (society_id). No impact if GeneralInfoController is only called with StoreProjectRequest for project create.
- **Compiled views:** `storage/framework/views/*.php` contain cached Blade; they mirror source. Ignore for refactor; clear view cache after blade changes.

### 5.3 Forms still submitting society_name

| Form | File | Backend |
|------|------|---------|
| General executor create | general/executors/create.blade.php | GeneralController@storeExecutor |
| General executor edit | general/executors/edit.blade.php | GeneralController@updateExecutor |
| General provincial create | general/provincials/create.blade.php | GeneralController@storeProvincial |
| General provincial edit | general/provincials/edit.blade.php | GeneralController@updateProvincial |
| Provincial provincial create | provincial/provincials/create.blade.php | ProvincialController@storeProvincial |
| Provincial provincial edit | provincial/provincials/edit.blade.php | ProvincialController@updateProvincial |
| Provincial executor create | provincial/createExecutor.blade.php | ProvincialController@storeExecutor |
| Provincial executor edit | provincial/editExecutor.blade.php | ProvincialController@updateExecutor |
| Old development project | (legacy) | OldDevelopmentProjectController |
| Monthly report create/update | Report form blades (readonly or hidden) | ReportController / request validation |

### 5.4 Hardcoded society strings (dropdown options)

| File | Usage |
|------|--------|
| general/executors/edit.blade.php | 9 hardcoded `<option value="ST. ANN'S...">` etc. |
| provincial/editExecutor.blade.php | 9 hardcoded options |
| provincial/createExecutor.blade.php | 9 hardcoded options |
| general/societies/create.blade.php | Placeholder text "e.g., ST. ANN'S EDUCATIONAL SOCIETY" |
| provincial/societies/create.blade.php | Same placeholder |
| SeedCanonicalSocietiesCommand.php | Canonical names array (data, not UI) |
| database/seeders/SocietySeeder.php | Province–society mapping (data, not UI) |

### 5.5 Province-based society assumptions

- **Provincial provincials create/edit:** Controller already loads `Society::where('province_id', $province->id)`; dropdown is province-scoped but submits **name**.
- **General provincials create/edit:** JS builds options from `provinces[].active_societies` (by province); submits **name**.
- **General/Provincial executor forms:** Hardcoded list (no province filter in UI); should be province-scoped when refactored.

---

## 6. Recommendation

| Question | Answer |
|----------|--------|
| **Safe to proceed to read-switch for projects only?** | **Yes.** Project create/edit use society_id and dual-write. You can switch project display and project export to `$project->society?->name` with fallback to `$project->society_name` for old data. |
| **Safe to proceed to read-switch for entire app (including users and reports)?** | **Not fully.** User (executor/provincial) forms still submit and persist society_name; report create/update and report exports use society_name. Proceed with project-only read-switch first; then refactor user dropdowns and report layer separately. |
| **Hidden legacy path?** | OldDevelopmentProjectController and any route still using StoreGeneralInfoRequest for a flow that sends society_name only. Confirm project create always uses StoreProjectRequest. |

**Summary:**  
- **Projects:** Converted; safe for project read-switch.  
- **Users / Provincial:** Not converted; 6 dropdown surfaces still use society_name; refactor before relying on users.society_id everywhere.  
- **Reports / Exports:** Use society_name for display and payload; read-switch can be done in a dedicated phase (display from relation where available).  
- **No code was modified in this audit.**

---

**Confirmation:** This document is analysis only. No code or schema was modified.
