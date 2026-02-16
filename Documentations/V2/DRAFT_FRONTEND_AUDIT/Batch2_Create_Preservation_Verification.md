# Create Preservation Verification Report

**Objective:** Confirm draft preservation logic does not affect CREATE flow and behaves safely.  
**Type:** Analysis only. No code was modified.  
**Date:** 2026-02-10

---

## 1. prepareForValidation() Execution Flow

**Question:** In Create flow, does UpdateProjectRequest::prepareForValidation() run?

**Answer: No.**

- **Create flow** uses route `projects.store` → `POST .../store` → `ProjectController::store(StoreProjectRequest $request)`.
- **Update flow** uses route `projects.update` → `PUT .../{project_id}/update` → `ProjectController::update(UpdateProjectRequest $request, $project_id)`.

Laravel resolves the FormRequest from the controller method type-hint. For `store()`, the request is **StoreProjectRequest**. For `update()`, the request is **UpdateProjectRequest**. The preservation logic lives in **UpdateProjectRequest::prepareForValidation()**, which runs only when the **Update** action is invoked. During Create, **UpdateProjectRequest is never instantiated**, so its `prepareForValidation()` is never executed. No preservation code runs during Create.

---

## 2. Route Parameter Check

**Question:** No DB lookup error when route does not contain project_id?

**Relevant for Create:** The Create route is `POST .../store` and has **no** `project_id` (or `{project_id}`) segment. Create never hits the update route. So during Create, the update route is not used and **UpdateProjectRequest is not used**; there is no call to `$this->route('project_id')` in the Create flow at all.

**If UpdateProjectRequest were ever used on a route without project_id:** In UpdateProjectRequest, `$projectId = $this->route('project_id');` would return `null` when the parameter is absent. Then `$project = $projectId ? Project::where(...)->first() : null;` would not run the query (short-circuit), and `if (!$project) return;` would exit without merging. So no DB lookup and no error. For Create, this is moot because UpdateProjectRequest is not in the Create flow.

---

## 3. Project Lookup Behavior

**Question:** Does preservation logic attempt to load existing project? Should it not break create flow / not throw when no project exists?

- **During Create:** Preservation logic does **not** run (different FormRequest), so no project lookup is attempted. Create flow is unaffected.
- **During Update (when preservation runs):** Code does:
  - `$projectId = $this->route('project_id');`
  - `$project = $projectId ? Project::where('project_id', $projectId)->first() : null;`
  - `if (!$project) return;`
  So if there is no `project_id` in the route, or the project does not exist, the method returns early and does not merge. No exception is thrown; no overwrite of request input when project is missing.

---

## 4. Preservation Safety on Create

**Question:** No accidental overwrite of input during create?

**Confirmed.** Create uses **StoreProjectRequest** only. StoreProjectRequest has its own `prepareForValidation()` (which normalizes `save_as_draft` to boolean only); it does **not** contain any project lookup or preservation merge. UpdateProjectRequest (and thus its preservation logic) is never used in the Create flow, so there is **no possibility** of preservation logic overwriting or merging values into the Create request. Input during create is only what the user (and the create form) send, plus StoreProjectRequest’s minimal prepareForValidation normalization.

---

## 5. Any Edge Cases Observed

- **Shared partial / same form field names:** Create and Edit use different views and different FormRequest classes. Create posts to `store` (StoreProjectRequest); Edit posts to `{project_id}/update` (UpdateProjectRequest). There is no shared use of UpdateProjectRequest between the two flows.
- **Misuse of UpdateProjectRequest on a route without project_id:** If in the future UpdateProjectRequest were bound to a route that had no `project_id`, the existing code would still be safe: `$this->route('project_id')` would be null, no query would run, and the method would return without merging.
- **Create flow draft:** Draft on Create is handled by StoreProjectRequest (nullable rules for optional fields; project_type required) and GeneralInfoController::store (defaults). No UpdateProjectRequest preservation is involved.

---

## 6. Verdict

**SAFE**

- UpdateProjectRequest::prepareForValidation() **does not run** during Create flow; Create uses StoreProjectRequest only.
- Preservation logic does not attempt to load a project during Create (it is not executed).
- There is no route without project_id in the Create flow that uses UpdateProjectRequest; and if there were, the code would exit early without DB lookup or overwrite.
- No accidental overwrite of Create input by preservation logic; Create request is handled solely by StoreProjectRequest and downstream controllers.

No issues found. Preservation is confined to the Update flow and does not affect Create.

---

**End of report. No code was modified.**
