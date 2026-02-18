# Controller Show Method Usage Audit — IIESPersonalInfoController@show & SustainabilityController@show

**Objective:** Determine whether these methods are actively used or legacy.  
**No code changes. Read-only.**

---

## 1. Route scan

**Scanned:** `routes/web.php`, `routes/api.php`, and route references elsewhere.

**Result:**

- **IIESPersonalInfoController:** No route in `web.php` or `api.php` references this controller. No `Route::get(..., [IIESPersonalInfoController::class, 'show'])` or equivalent.
- **SustainabilityController:** No route in `web.php` or `api.php` references this controller. No route that invokes `SustainabilityController@show`.

**Conclusion:** Neither show method is route-bound.

---

## 2. Codebase search for route(), redirect(), or links

**Search:** `route(...)`, `redirect()->route(...)`, and link references that could point to these show methods.

**Result:**

- No route name exists for either controller’s show (no route definition ⇒ no name to pass to `route()` or `redirect()->route()`).
- No occurrence of `route('...iies.personal_info.show'`, `route('...sustainability.show'`, or similar that would target these methods.
- The only `route(...)` mentions of “iies” in views are for **attachments**: `route('projects.iies.attachments.view', ...)` and `route('projects.iies.attachments.download', ...)` — these hit IIESAttachmentsController, not IIESPersonalInfoController.

**Conclusion:** No `route()`, `redirect()`, or link in the codebase targets IIESPersonalInfoController@show or SustainabilityController@show.

---

## 3. Blade usage

**Search:** Blade files that link to or invoke these show methods (e.g. `route()`, `href`, form action).

**Result:**

- No Blade file uses a route or URL that would call IIESPersonalInfoController@show or SustainabilityController@show.
- Blade references to “IIESPersonalInfo” or “sustainability” are:
  - **Show/IIES/personal_info.blade.php:** Uses `$project->iiesPersonalInfo` (relationship on `$project`), not a variable from a controller show method and not a link to one.
  - **Show/sustainability.blade.php** and **partials/sustainability**: Content partials and CSS class names (e.g. `sustainability-textarea`), not links to SustainabilityController@show.

**Conclusion:** No Blade file links to or targets these show methods.

---

## 4. How the methods are invoked (if at all)

### IIESPersonalInfoController@show

- **Invoked from:**
  - **ProjectController@show** (line 998): `$data['IIESPersonalInfo'] = $this->iiesPersonalInfoController->show($project->project_id);`
  - **ProjectDataHydrator::hydrate()** (line 274): `$data['IIESPersonalInfo'] = $this->iiesPersonalInfoController->show($project->project_id);`
- **Return value:** `return view('projects.Oldprojects.show', compact('project'));` — i.e. a **View** instance, not model/data.
- **Caller expectation:** Both callers assign the return value to `$data['IIESPersonalInfo']`, as if it were **data** for the view (e.g. a model or collection), consistent with other IIES sub-controllers’ `show()` (e.g. IIESFamilyWorkingMembersController@show returns a collection, EducationBackgroundController@show returns a model).
- **Actual use in show view:** The partial `projects.partials.Show.IIES.personal_info` uses **`$project->iiesPersonalInfo`**, not `$IIESPersonalInfo`. So the value in `$data['IIESPersonalInfo']` is not used by that partial. The view-returning behavior of IIESPersonalInfoController@show is therefore **not used** for the normal project show page; the method is called but its return value is inconsistent (View instead of data) and the show blade does not rely on it for the IIES personal info section.

### SustainabilityController@show

- **Invoked from:** No references found. SustainabilityController is referenced only for **destroy** (ProjectForceDeleteCleanupService). No controller or service calls `SustainabilityController@show`.
- **Conclusion:** The method is **not invoked** anywhere in the scanned codebase.

---

## 5. Summary table and “safe to remove”

-----------------------------------  
**Controller:** IIESPersonalInfoController  
**Method:** show($projectId)  

| Question | Answer |
|----------|--------|
| **Route bound?** | No — no route points to this method. |
| **Used in Blade?** | No — no Blade link or `route()` targets it. |
| **Used in redirect?** | No — no redirect to a route for this method. |
| **Invoked elsewhere?** | Yes — ProjectController@show and ProjectDataHydrator::hydrate() call it, but they expect **data**; the method returns a **View**. The show view’s IIES personal_info partial uses `$project->iiesPersonalInfo`, not `$IIESPersonalInfo`. So the **view-returning** behavior is effectively unused; the **call** is used only in a way that is inconsistent with the method’s current return type. |
| **Safe to remove (view return)?** | Yes — the view-return path is not route-bound, not linked from Blade, and not used by the show page (which uses `$project->iiesPersonalInfo`). Changing the method to return **data** (e.g. model) instead of a view would align with callers and other IIES show() methods; removing only the view return and returning data would be safe. Removing the method entirely would require callers to be updated to get IIES personal info another way (e.g. from `$project->iiesPersonalInfo`). |
-----------------------------------

-----------------------------------  
**Controller:** SustainabilityController  
**Method:** show($project_id)  

| Question | Answer |
|----------|--------|
| **Route bound?** | No — no route points to this method. |
| **Used in Blade?** | No — no Blade link or `route()` targets it. |
| **Used in redirect?** | No — no redirect to a route for this method. |
| **Invoked elsewhere?** | No — no controller or service calls SustainabilityController@show. |
| **Safe to remove?** | Yes — the method is not route-bound, not linked, not redirected to, and not called. It is legacy/dead code for the show view. |
-----------------------------------

---

## 6. Additional notes

- **Reachability:** Neither method is reachable via the UI as an HTTP endpoint (no routes). They are not used in AJAX in the scanned code; SustainabilityController@show is not called at all; IIESPersonalInfoController@show is only called from ProjectController and ProjectDataHydrator, which treat its return as data while it actually returns a View.
- **Legacy / dead code:** SustainabilityController@show is dead code (never called). IIESPersonalInfoController@show’s **view-returning** behavior is legacy/unused (no route, no link; callers and show blade do not use it as a view).
- **Enforcement:** If the application should only render `projects.Oldprojects.show` via ProjectController@show (with full `$data` including `resolvedFundFields`), then removing or changing the view return in these two methods does not break any current route, Blade link, or redirect. Updating IIESPersonalInfoController@show to return **data** (model) instead of a view would fix the mismatch with ProjectController and ProjectDataHydrator and align with other IIES show() methods.

No files were modified. Audit only.
