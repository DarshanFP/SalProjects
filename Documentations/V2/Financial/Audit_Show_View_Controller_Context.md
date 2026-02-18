# Audit: Controllers Rendering `projects.Oldprojects.show`

**Scope:** All controllers that contain `return view('projects.Oldprojects.show'`. Read-only; no code changes.

**Scan:** Entire `app/Http/Controllers` directory.

---

## Controllers and methods that render the view

Exactly **three** controller methods contain a return of the form `view('projects.Oldprojects.show', ...)`:

---

-----------------------------------
**Controller:** `App\Http\Controllers\Projects\ProjectController`  
**Method:** `show($project_id)`  
**Route:** GET `executor/projects/{project_id}` → `projects.show`. Also used when Coordinator/General/Provincial "show project" delegates via `app(ProjectController::class)->show($project_id)` (coordinator.projects.show, general.showProject, provincial.projects.show).  
**Resolver Called?** Yes — `$resolver = app(ProjectFinancialResolver::class);` then `$data['resolvedFundFields'] = $resolver->resolve($project);` (lines 1036–1037).  
**resolvedFundFields Passed?** Yes — via `$data` array.  
**$data array vs compact()?** Uses `$data` array (built from line 837 onward; `resolvedFundFields` set at 1037).  
**Return statement snippet:**  
`return view('projects.Oldprojects.show', $data);` (line 1045)  
**Risk level:** Low — canonical show entry point; resolver used and resolvedFundFields passed.  
-----------------------------------

-----------------------------------
**Controller:** `App\Http\Controllers\Projects\IIES\IIESPersonalInfoController`  
**Method:** `show($projectId)`  
**Route:** Not found in `routes/web.php` or `routes/api.php`. Controller is injected into ProjectController and ProjectDataHydrator for **data** (e.g. `$data['IIESPersonalInfo'] = $this->iiesPersonalInfoController->show($project->project_id)`), which returns the **view** when the method is invoked. No dedicated GET route found for this controller’s show; if ever routed or called as a view-returning endpoint, that path would render the show view without resolvedFundFields.  
**Resolver Called?** No — no use of ProjectFinancialResolver or resolvedFundFields in this controller.  
**resolvedFundFields Passed?** No — `compact('project')` only.  
**$data array vs compact()?** Uses `compact('project')`.  
**Return statement snippet:**  
`return view('projects.Oldprojects.show', compact('project'));` (line 93)  
**Risk level:** High if this method is ever used to render the full show page — view expects resolvedFundFields; General Info partial will default to `[]` and display 0.00 for all financial fields.  
-----------------------------------

-----------------------------------
**Controller:** `App\Http\Controllers\Projects\SustainabilityController`  
**Method:** `show($project_id)`  
**Route:** Not found in `routes/web.php` or `routes/api.php`. SustainabilityController is referenced in ProjectForceDeleteCleanupService (destroy), not for show. No GET route found that points to SustainabilityController@show.  
**Resolver Called?** No — no use of ProjectFinancialResolver or resolvedFundFields in this controller.  
**resolvedFundFields Passed?** No — `compact('project', 'user')` only.  
**$data array vs compact()?** Uses `compact('project', 'user')`.  
**Return statement snippet:**  
`return view('projects.Oldprojects.show', compact('project', 'user'));` (line 56)  
**Risk level:** High if this method is ever used to render the full show page — same missing resolvedFundFields and 0.00 financial display as above.  
-----------------------------------

---

## Delegating controllers (do not render the view themselves)

The following call **ProjectController@show** and therefore do **not** render `projects.Oldprojects.show` themselves; the view is rendered by ProjectController with full `$data` including resolvedFundFields:

- **CoordinatorController::showProject($project_id)** — `return app(ProjectController::class)->show($project_id);`
- **GeneralController::showProject($project_id)** — `return app(\App\Http\Controllers\Projects\ProjectController::class)->show($project_id);`
- **ProvincialController::showProject($project_id)** — `return app(ProjectController::class)->show($project_id);`

---

## SUMMARY SECTION

| Metric | Count |
|--------|--------|
| **Total controllers that render show view** | **3** (ProjectController, IIESPersonalInfoController, SustainabilityController) |
| **How many pass resolvedFundFields** | **1** (ProjectController only) |
| **How many do NOT pass resolvedFundFields** | **2** (IIESPersonalInfoController, SustainabilityController) |

**Controllers that may break if enforced blindly:**  
If the view or partial is changed to **require** `resolvedFundFields` (e.g. throw or strict check), any request that hits **IIESPersonalInfoController@show** or **SustainabilityController@show** would break. Currently neither is exposed via a route in the scanned route files, so they may be dead code or used in an unexamined way (e.g. AJAX, other route files). If they are ever used as the entry point for the project show page, the General Info section will show 0.00 for all financial fields because the variable is missing and the partial defaults to `[]`.

**Recommended enforcement strategy:**

- **Centralized (preferred):** Ensure the **only** way the show view is rendered is via **ProjectController@show** (or a single facade that builds the same `$data`, including `resolvedFundFields`). Either remove the view-return from IIESPersonalInfoController and SustainabilityController, or have them delegate to ProjectController@show instead of rendering the view themselves. Then the view can assume `resolvedFundFields` is always present.
- **Patching:** If both controllers must continue to return the show view, add in each: use of ProjectFinancialResolver and `$data['resolvedFundFields'] = $resolver->resolve($project)`, and pass a full `$data` array (or at least pass `resolvedFundFields`) to the view so the partial receives the same data as when called from ProjectController@show.

No files were modified. Audit only.
