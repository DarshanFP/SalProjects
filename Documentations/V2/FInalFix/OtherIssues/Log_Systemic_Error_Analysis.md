# Log Systemic Error Analysis

**Source:** `storage/logs/laravel.log` (lines 1–5777)  
**Date:** 2026-02-16  
**Scope:** Read-only analysis; no code modifications, fixes, or migrations.

---

## 1. Executive Summary

Analysis of the production log identified **3 distinct issues**: one **ERROR** that blocks project creation (QueryException: `province_id` missing), one **ERROR** that breaks IES attachment View/Edit UI (RouteNotFoundException for `projects.ies.attachments.view`), and one **WARNING** indicating data-integrity violation (non-approved project with `amount_sanctioned` > 0). No AuthorizationException, 404, file-not-found, undefined variable/property, or type errors were found in the scanned log. The `province_id` and route issues are **systemic** (affect all new IIES creations and all IES attachment views respectively); the financial invariant is **repeated** for the affected project (IOES-0024) whenever its show page is loaded.

---

## 2. Scope and Methodology

- **Log range:** Full `laravel.log` (1–5777).
- **Patterns extracted:** `local.ERROR`, `local.CRITICAL`, `QueryException`, `RouteNotFoundException`, `AuthorizationException`, `ViewException`, 404, file-not-found, undefined variable/property, type errors.
- **Method:** Grep and targeted read of log lines; mapping of each error to controller/blade/route/helper; risk classification; cross-check with Province-Based Project Access refactor and attachment route documentation.
- **Constraint:** No code or schema changes; documentation only.

---

## 3. Extracted Errors (Grouped)

| # | Level   | Exception / Type              | Controller / Location                    | File / View                                                                 | Route / Project Type |
|---|--------|-------------------------------|------------------------------------------|-----------------------------------------------------------------------------|----------------------|
| 1 | ERROR  | QueryException                | ProjectController@store → GeneralInfoController@store | `GeneralInfoController.php:71` | POST executor/projects/store; **IIES** |
| 2 | ERROR  | RouteNotFoundException (ViewException) | View rendering                           | `partials/Show/IES/attachments.blade.php` (line 59)                         | project show; **IES** |
| 3 | ERROR  | RouteNotFoundException (ViewException) | View rendering                           | `partials/Edit/IES/attachments.blade.php` (line 62)                         | project edit; **IES** |
| 4 | WARNING| Financial invariant           | ProjectController@show (via ProjectFinancialResolver) | `ProjectFinancialResolver.php` (invariant log)       | project show; **IOES** (IOES-0024) |

**Detail:**

- **1:** `SQLSTATE[HY000]: General error: 1364 Field 'province_id' doesn't have a default value`. Insert into `projects` omits `province_id`; DB has no default.
- **2 & 3:** `Route [projects.ies.attachments.view] not defined` when generating `route('projects.ies.attachments.view', $file->id)` in the two IES attachment partials.
- **4:** `Financial invariant violation: non-approved project must have amount_sanctioned == 0` with `project_id: IOES-0024`, `amount_sanctioned: 2758.03`.

---

## 4. Frequency and Pattern Analysis

| Unique Error | Occurrences | Timestamps | Tag |
|--------------|-------------|------------|-----|
| province_id missing on project insert | 1 | 2026-02-16 12:34:43 | **Systemic** (every new project create without province_id) |
| Route [projects.ies.attachments.view] not defined (Show) | 1 | 2026-02-16 12:34:54 | **Repeated/Systemic** (any IES project show with attachments) |
| Route [projects.ies.attachments.view] not defined (Edit) | 1 | 2026-02-16 12:35:03 | **Repeated/Systemic** (any IES project edit with attachments) |
| Financial invariant: amount_sanctioned != 0 (IOES-0024) | 1 | 2026-02-16 12:34:54 | **Repeated** (every show of this project until data fixed) |

No one-off cosmetic or single-request-only errors. The two route errors are the same logical issue in two blades.

---

## 5. Codebase Mapping

### 5.1 province_id – QueryException

- **Route:** POST to store (e.g. executor/projects/store).
- **Flow:** ProjectController@store → storeGeneralInfoAndMergeProjectId → GeneralInfoController@store → `Project::create($validated)` at line 71.
- **Root cause:** `GeneralInfoController::store()` never sets `province_id` in `$validated`. The `projects` table requires `province_id` (no default). Province-based refactor assumes `project.province_id` for isolation; creation path was not updated to set it (e.g. from `Auth::user()->province_id`).
- **Files:** `app/Http/Controllers/Projects/GeneralInfoController.php` (store), `app/Http/Controllers/Projects/ProjectController.php` (store, 624, 581), `App\Models\OldProjects\Project` (create).

### 5.2 Route [projects.ies.attachments.view] not defined

- **Blades:** `resources/views/projects/partials/Show/IES/attachments.blade.php`, `resources/views/projects/partials/Edit/IES/attachments.blade.php` both call `route('projects.ies.attachments.view', $file->id)`.
- **Route definition:** In `routes/web.php` (lines 478–479) the route **is** defined:  
  `Route::get('/projects/ies/attachments/view/{fileId}', [IESAttachmentsController::class, 'viewFile'])->name('projects.ies.attachments.view');`  
  inside the same middleware group as other project routes (`auth`, `role:executor,applicant,provincial,coordinator,general`), with no name prefix that would change the name.
- **Execution path:** User opens project show/edit → controller loads IES partials → Blade compiles `route('projects.ies.attachments.view', $file->id)` → UrlGenerator fails with RouteNotFoundException (UrlGenerator.php:477).
- **Hypothesis:** Route exists in codebase; failure at runtime suggests route list not including this name when the view is rendered (e.g. route cache, environment-specific loading, or request context where this route group is not registered). No missing definition in source; likely **routing/deployment/cache** issue.

### 5.3 Financial invariant (amount_sanctioned)

- **Source:** `app/Domain/Budget/ProjectFinancialResolver.php`: when resolving financial data for a non-approved project, it asserts `amount_sanctioned == 0` and logs a warning otherwise.
- **Trigger:** ProjectController@show (or any consumer of ProjectFinancialResolver) for project IOES-0024; project has `amount_sanctioned = 2758.03` while status is not approved.
- **Root cause:** Data inconsistency: either the project was approved and had amount set then status was changed, or amount was set without approval. No code bug in the resolver; it correctly detects the violation.

---

## 6. Risk Classification

| Issue | Classification | Notes |
|-------|----------------|-------|
| province_id missing on create | **Refactor regression** / **Data integrity risk** | Province refactor added reliance on `project.province_id`; create path was not updated. New projects fail to insert; province isolation may be incomplete for newly created rows if default were added elsewhere. |
| Route projects.ies.attachments.view not defined | **Routing misconfiguration** (or deployment/cache) | Route is defined in code; runtime resolution fails. Affects IES attachment “View” in Show/Edit; download uses same group. |
| Financial invariant (IOES-0024) | **Data integrity risk** | Non-approved project has non-zero amount_sanctioned; business rule enforced in code but existing data violates it. |

No **Security risk** or **Authorization misconfiguration** identified in the log. No **Storage/File system** or **Legacy code conflict** entries in this parse.

---

## 7. Refactor Cross-Check

- **Province-based access refactor:** The refactor assumes `project.province_id` and uses it in ProjectPermissionHelper and ProjectQueryService. **Regression:** Project creation in GeneralInfoController does not set `province_id`, causing insert failure. This is a direct regression relative to the refactor’s model.
- **Society validation removal:** Not implicated in these log entries.
- **ProjectPermissionHelper:** Not implicated; errors occur before or outside authorization checks (create failure, view rendering, financial resolution).
- **Route naming / attachment refactors:** IES attachment routes use names `projects.ies.attachments.view` and `projects.ies.attachments.download` in both `web.php` and the Blade partials. Naming is consistent; the “not defined” error points to registration/cache/environment, not a naming or refactor mistake in the codebase.
- **Attachment route refactors:** No evidence of wrong route name or wrong controller; same conclusion as above.

---

## 8. Architectural Observations

- **Structural:** Project creation delegates to GeneralInfoController but does not align with the province model (no `province_id`). Single place (GeneralInfoController::store) is responsible for insert; one missing field breaks all new project creates for types that use this path (e.g. IIES).
- **Consistency:** IES attachment handling is consistent (Show and Edit both use the same route name); the failure is symmetric. IIES/IAH use similarly named routes (`projects.iies.attachments.view`, `projects.iah.documents.view`); no similar errors in log for those.
- **Authorization:** No controllers bypassing ProjectPermissionHelper observed in these errors; create fails at DB, show/edit fail at view rendering and financial resolution.
- **Route naming:** Attachment routes follow a clear pattern (`projects.{type}.attachments.view/download` or documents equivalent); no inconsistent naming in source.
- **Blade/route coupling:** Blades correctly use named routes; the only issue is runtime route availability for `projects.ies.attachments.view`.
- **Duplicate logic:** IES Show and Edit partials duplicate the same link generation; a single partial or shared component would reduce drift but would not fix the “route not defined” issue.

---

## 9. Conclusions and Recommendations (Read-Only)

- **Top systemic issues:** (1) Missing `province_id` on project create, (2) Route `projects.ies.attachments.view` not resolved at runtime despite being defined in `web.php`, (3) Financial invariant violation for IOES-0024 (data integrity).
- **Recommendations (for human/dev team):**  
  - Ensure `province_id` is set on create (e.g. from `Auth::user()->province_id`) in GeneralInfoController::store (or equivalent) and that it is present in the validated/fillable data passed to `Project::create()`.  
  - Verify route registration and cache: run `php artisan route:list` in the environment where the error occurs; clear route cache if applicable (`php artisan route:clear`); ensure no conditional or environment-specific exclusion of the route group.  
  - Correct data for IOES-0024: either set `amount_sanctioned` to 0 while non-approved or align status with sanctioned amount, per business rules.

No code, migrations, or config changes were made in this analysis.

---

## 10. Appendix: Evidence Summary

- **Log lines:**  
  - QueryException: ~line 9 (ERROR), stack trace points to GeneralInfoController.php:71, ProjectController.php:624, 581.  
  - RouteNotFoundException (Show): ~line 98 (ERROR), view `partials/Show/IES/attachments.blade.php`.  
  - RouteNotFoundException (Edit): ~line 1356 (ERROR), view `partials/Edit/IES/attachments.blade.php`.  
  - Financial invariant: ~line 96 (WARNING), project_id IOES-0024, amount_sanctioned 2758.03.
- **Route definition:** `routes/web.php` lines 477–479 (IES attachments download and view, named `projects.ies.attachments.download` and `projects.ies.attachments.view`).
- **Blade usage:** Show and Edit IES attachment partials use `route('projects.ies.attachments.view', $file->id)` and `route('projects.ies.attachments.download', $file->id)`.
- **Financial invariant:** `app/Domain/Budget/ProjectFinancialResolver.php` (non-approved invariant and log message).
