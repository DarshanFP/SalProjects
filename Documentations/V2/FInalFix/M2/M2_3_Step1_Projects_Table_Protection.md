# M2.3 Step 1 — Projects Table Protection

**Milestone:** M2 — Validation & Schema Alignment  
**Step:** M2.3 Step 1 (Projects Table Protection ONLY)  
**Strategy Level:** B (Defensive Architecture)

---

## 1) What Was Changed

### UpdateProjectRequest (`app/Http/Requests/Projects/UpdateProjectRequest.php`)

- **prepareForValidation()** now runs merge logic for **missing** keys on every update request (not only when `save_as_draft` is true).
- **New behaviour:** When the route has `project_id` and the existing project is loaded:
  - If the request does **not** contain the `in_charge` key (`!$this->exists('in_charge')`), `in_charge` is merged from the existing project.
  - If the request does **not** contain the `overall_project_budget` key (`!$this->exists('overall_project_budget')`), `overall_project_budget` is merged from the existing project.
- Merge is performed **only when the key is absent**. If the key is present (even with a null or empty value), it is **not** overridden here; the controller null-guard handles the explicit-null case.
- **Existing draft behaviour is unchanged:** When `save_as_draft` is true, the existing logic still merges `project_type`, `in_charge`, and `overall_project_budget` when the key is not *filled* (empty string / null). So draft continues to get existing values for unfilled fields.

### GeneralInfoController (`app/Http/Controllers/Projects/GeneralInfoController.php`)

- **update()** method: immediately **before** `$project->update($validated)`, two defensive checks were added:
  1. If `in_charge` exists in `$validated` and its value is **null**, replace it with `$project->in_charge` (existing value).
  2. If `overall_project_budget` exists in `$validated` and its value is **null**, replace it with `$project->overall_project_budget`, or `0.00` if the existing value is null (safety fallback).
- No other fields or behaviour (status, project_type, other numerics, goal, etc.) were changed.

---

## 2) Why It Prevents NOT NULL Violations

- **projects.in_charge** and **projects.overall_project_budget** are NOT NULL (or have a default) in the schema. Sending `null` into `update()` would cause a constraint violation or overwrite with null.
- **Request layer:** When the key is **missing**, it is filled from the existing project in `prepareForValidation()`, so validated data for those keys is non-null whenever the project exists.
- **Controller layer:** When the key is **present but null** (e.g. user cleared the field), the controller replaces that null with the existing project value (or 0.00 for budget) before `update()`. So the array passed to `update()` never contains `null` for `in_charge` or `overall_project_budget`.
- Together, missing-key merge and null-guard ensure that **no null is ever passed** for these two columns, preventing NOT NULL DB violations.

---

## 3) Why Draft Compatibility Is Preserved

- **Validation rules:** No change. `in_charge` and `overall_project_budget` remain `nullable`; no new required rules were added. Draft can still submit with partial data.
- **prepareForValidation:** Draft behaviour is preserved:
  - The new “missing key” merge runs for **all** update requests when the project exists. For draft, if the key is missing, it is merged; if the key is present but empty, the **existing** draft block still merges when `!$this->filled(...)`. So draft continues to get existing values for unfilled or missing in_charge and overall_project_budget.
- **Controller:** The null-guard only replaces **null** with the existing value. It does not change non-null values. Draft can still save partial data; we only prevent null from reaching the DB.
- No new conditions were added that block or reject draft requests.

---

## 4) Why It Does Not Overlap With M1

- **M1** is the “skip-empty guard”: it decides **whether** to run a section mutation (e.g. skip when the section is absent or empty). M1 lives in section controllers (e.g. BeneficiariesArea, Budget, LogicalFramework) and does not touch UpdateProjectRequest or GeneralInfoController.
- **M2.3 Step 1** only ensures that when GeneralInfoController **does** run (every update), the values written for `in_charge` and `overall_project_budget` are never null. It does not change when any section is run or skipped. So there is no overlap with M1.

---

## 5) Why It Does Not Overlap With M3 or M4

- **M3 (resolver):** ProjectFinancialResolver and any display/read logic were not modified. Only the **write path** in UpdateProjectRequest and GeneralInfoController was changed. M3 is read-path; this step is write-path only.
- **M4 (societies):** No societies tables, validation, or controllers were touched. Only project update request and general info controller were modified.

---

## 6) Risk Assessment

- **Risk level: LOW.**
  - Scope is minimal: two files, two fields, additive logic (merge when missing, replace null before update).
  - No validation rule tightening, no new required fields, no change to project_type or status.
  - Draft and full submit both remain supported; we only ensure that the two NOT NULL columns never receive null.
  - Possible edge case: update with no existing project (e.g. invalid route). In that case, prepareForValidation does not merge (project is null), and the controller would still receive validated data; if in_charge or overall_project_budget were null, the controller would try to use `$project->in_charge` / `$project->overall_project_budget` — but in that path the project is loaded with `firstOrFail()` before the guard, so the only way to hit the guard with “no project” would be if the guard were reached without a project, which does not happen. So risk remains low.

---

## 7) Files Modified

| File | Change |
|------|--------|
| `app/Http/Requests/Projects/UpdateProjectRequest.php` | prepareForValidation: load project first; merge `in_charge` and `overall_project_budget` from existing project when key is missing (exists check); keep existing draft merge behaviour. |
| `app/Http/Controllers/Projects/GeneralInfoController.php` | update(): before `$project->update($validated)`, if `in_charge` or `overall_project_budget` exists in `$validated` and value is null, replace with existing project value (or 0.00 for budget). |

No other files were modified. LogicalFrameworkController, section controllers, resolver, and societies were not touched.

---

## 8) Scope Correction — Update Only

### Why scoping was required

The M2.3 merge logic (missing-key merge for `in_charge` and `overall_project_budget`) must run only for **UPDATE** requests — i.e. when the route has a `project_id` and an existing project is found. If the same request class were ever used on a create flow, or if `route('project_id')` were absent or invalid, attempting to load a project and merge would be wrong: there is no “existing project” to merge from on create. Scoping ensures we never run merge unless we are on an update route and have a resolved project.

### What was changed

In **UpdateProjectRequest::prepareForValidation()**:

- Project is no longer loaded unconditionally. We only run the “missing key” merge when:
  1. `$this->route('project_id')` is present (UPDATE route), and  
  2. `Project::where('project_id', $this->route('project_id'))->first()` returns a project.
- Structure: `$project = null`. Then `if ($this->route('project_id')) { load project; if ($project) { merge in_charge and overall_project_budget when key missing } }`. Draft block unchanged: it still runs after, and still uses `$project` (so draft merge only runs when we have a project, which on update we do after loading).
- Single query: the project is loaded once when `project_id` exists; no duplicate query.

### Why this prevents create-route interference

- On a **create** request, the update route (and thus `project_id`) is typically not present. So `$this->route('project_id')` is null/absent, the inner block is skipped, no project is loaded, and no merge runs. No attempt to merge from a non-existent project.
- Even if the request class were reused on a route that sometimes has no `project_id`, the same guard applies: no `project_id` → no load, no merge. Create flow is unaffected.

### No behaviour change for create flow

- Create uses **StoreProjectRequest**, not UpdateProjectRequest, so prepareForValidation in UpdateProjectRequest does not run on create. This fix only tightens UpdateProjectRequest so that within it we never run merge without an update route and a found project. Create flow is unchanged; update flow behaviour (missing key merged when project exists, draft merge when draft and project exists) is preserved.

---

**End of M2.3 Step 1 documentation.**
