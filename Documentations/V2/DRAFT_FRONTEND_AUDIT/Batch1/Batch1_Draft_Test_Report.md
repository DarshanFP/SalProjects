# Draft Save Test Report

**Scope:** Edit flow after Batch 1 removal and FormRequest preservation (prepareForValidation in UpdateProjectRequest).  
**Type:** Simulation / code-trace only. No code was modified.  
**Date:** 2026-02-10

---

## 1. Scenario Results

### CASE 1: Edit project → Clear project_title, project_type, overall_project_budget → Click “Save Project” (draft)

| Step | Simulated behavior |
|------|---------------------|
| **User action** | Clears project_title, project_type, overall_project_budget; clicks “Save Project” (draft button adds `save_as_draft=1`); form submits. |
| **HTML5** | **PASS.** Batch 1 removed `required` from these fields in Edit general_info partial. No browser validation block. |
| **Form submit** | Form submits to `PUT projects.update` with empty/missing values for the three fields and `save_as_draft=1`. |
| **prepareForValidation()** | Runs **before** validation. `save_as_draft` is true; project is loaded from `route('project_id')`. Request does not have filled `project_type` → merge `project_type` from `$project`. Request does not have filled `overall_project_budget` → merge `overall_project_budget` from `$project`. `project_title` is not preserved (nullable in DB; can stay empty). Request input now contains preserved project_type and overall_project_budget. |
| **UpdateProjectRequest rules** | **PASS.** `$isDraft = true` → project_type is nullable; project_title, overall_project_budget already nullable. Validation runs on request input that includes merged values. Validation passes. |
| **validated()** | Contains preserved `project_type` and `overall_project_budget` (from merge before validation). `project_title` can be '' or missing (allowed). |
| **ProjectController@update** | No preservation merges (removed). Loads project, calls GeneralInfoController::update($request, …). |
| **GeneralInfoController@update** | `$validated = $request->validated()` → receives preserved project_type and overall_project_budget. `$project->update($validated)` writes non-null values for NOT NULL columns. |
| **DB write** | project_type and overall_project_budget are written from validated (preserved values). No NULL for those columns. project_title written as '' or null (schema allows). **No NOT NULL violation.** |
| **Status** | After commit, `if ($request->boolean('save_as_draft')) { $project->status = DRAFT; $project->save(); }` runs. **PASS** — status remains DRAFT. |

**CASE 1 verdict:** **PASS.** Form submits, no HTML5 block, backend accepts save, project_type and overall_project_budget preserved via prepareForValidation → validated() → DB. Status remains DRAFT.

---

### CASE 2: Edit project → Clear only project_type → Save normally (no save_as_draft)

| Step | Simulated behavior |
|------|---------------------|
| **User action** | Clears project_type only; submits with main submit button (no “Save as draft”) → no `save_as_draft` in request. |
| **HTML5** | **PASS.** No `required` on project_type in Edit general_info. Form submits. |
| **prepareForValidation()** | `save_as_draft` is false/missing → method returns immediately. No merges. Request keeps empty/missing project_type. |
| **UpdateProjectRequest rules** | **PASS (validation blocks as intended).** `$isDraft = false` → `project_type` is `required|string|max:255`. Empty/missing project_type fails validation. User is redirected back with validation error (“Project type is required.”). |
| **Controller** | Update logic is not reached; request is rejected by FormRequest. |

**CASE 2 verdict:** **PASS.** Non-draft save with empty project_type is correctly blocked by UpdateProjectRequest.

---

### CASE 3: Edit project → Clear in_charge → Save as draft

| Step | Simulated behavior |
|------|---------------------|
| **Request payload** | `save_as_draft=1`, `in_charge=` (empty) or omitted. Other fields unchanged. |
| **prepareForValidation()** | `save_as_draft` is true. Project loaded from route. `!$this->filled('in_charge')` true; `$project->in_charge !== null` true (existing project has in_charge). Merge: `$this->merge(['in_charge' => $project->in_charge])`. Request input now has existing in_charge value. |
| **Validation** | Runs on request that includes merged in_charge. Rule: `in_charge` nullable|integer|exists:users,id. Preserved value passes. |
| **validated()** | Contains `in_charge` with the preserved (existing) value. |
| **ProjectController@update** | No merge (preservation is in FormRequest). Calls GeneralInfoController::update($request, …). |
| **GeneralInfoController@update** | `$validated = $request->validated()` → includes preserved in_charge. `$project->update($validated)` writes in_charge with existing value. |
| **DB write** | in_charge column receives the preserved value. **No NULL write. No SQL error.** |

**CASE 3 verdict:** **PASS.** Existing in_charge is preserved in prepareForValidation → validated() → DB. No NULL write and no SQL error.

---

## 2. Request Payload Observed (Simulated)

| Scenario | save_as_draft | project_type (before P4V) | overall_project_budget (before P4V) | in_charge (before P4V) |
|----------|----------------|----------------------------|-------------------------------------|--------------------------|
| CASE 1 (draft, cleared) | 1 | ''/missing | ''/missing | (unchanged or N/A) |
| CASE 2 (normal) | 0 / missing | ''/missing | — | — |
| CASE 3 (draft, cleared) | 1 | unchanged | unchanged | ''/missing |

**After prepareForValidation (CASE 1 & 3):** Request input contains merged project_type (CASE 1), overall_project_budget (CASE 1), and in_charge (CASE 3). Validation and thus validated() see these values.

---

## 3. DB Write Behavior

| Field | Schema | Source in validated() | Actual write | Result |
|-------|--------|----------------------|--------------|--------|
| project_type | NOT NULL | Preserved in prepareForValidation → in validated() | Existing value | **Safe** |
| project_title | nullable | User input (empty allowed) | '' or null | **Safe** |
| overall_project_budget | NOT NULL, default 0.00 | Preserved in prepareForValidation → in validated() | Existing value | **Safe** |
| in_charge | NOT NULL (FK) | Preserved in prepareForValidation → in validated() | Existing value | **Safe** |

GeneralInfoController uses `$request->validated()` only; preserved values are now part of that array (because they were merged before validation), so the DB receives non-null values for NOT NULL columns when the user clears them and saves as draft.

---

## 4. Status Behavior

- **CASE 1 (draft):** Update succeeds; after commit the controller runs `if ($request->boolean('save_as_draft')) { $project->status = ProjectStatus::DRAFT; $project->save(); }`. Status remains DRAFT. **PASS.**
- **CASE 2:** Update not reached; validation blocks. Status unchanged. **PASS.**
- **CASE 3 (draft):** Update succeeds with preserved in_charge; same draft status block runs. Status remains DRAFT. **PASS.**

---

## 5. Any Validation Errors

- **CASE 1:** None. prepareForValidation merges NOT NULL fields; rules allow nullable for draft; validation passes.
- **CASE 2:** Validation error for project_type (required when not draft). **Expected.**
- **CASE 3:** None. in_charge preserved before validation; validation passes; no DB error.

---

## 6. Any Risk Observed

1. **Project load in prepareForValidation:** One extra project query when save_as_draft is true (project also loaded in authorize() and again in ProjectController). Acceptable for correctness; can be optimized later (e.g. route model binding or shared instance).
2. **project_title:** Intentionally not preserved (nullable in schema). User can clear it on draft save; stored as empty/null. **Low risk.**
3. **Budget lock:** When project is approved, BudgetSyncGuard strips budget fields in GeneralInfoController; draft preservation does not override that. **No additional risk.**

---

## 7. Final Stability Verdict

**PASS**

- **CASE 1:** Form submits, no HTML5 block, backend accepts save, project_type and overall_project_budget preserved via prepareForValidation → validated() → DB. Status remains DRAFT.
- **CASE 2:** Validation correctly blocks empty project_type when not draft.
- **CASE 3:** in_charge preserved in prepareForValidation; validated() contains it; DB receives non-null value; no SQL error.

Draft save behavior is stable: preservation runs before validation in UpdateProjectRequest, so `$request->validated()` and thus the DB update receive the preserved values for project_type, in_charge, and overall_project_budget when the user clears them and saves as draft.

---

**End of report. No code was modified.**
