# IIES Create Flow — Phase-Wise Implementation Plan

**Date:** 2026-02-08  
**Scope:** Forensic verification and design-only fix plan  
**Status:** Read-only analysis; no code changes  
**Related:** `IIES_Create_Flow_Forensic_Report.md`, `IIES_Missing_Project_Record_Forensic_Analysis.md`

---

## 1. Verified Execution Timeline

Step-by-step flow from HTTP request → DB commit/rollback. All line numbers verified against current codebase.

| Step | Action | File | Method | Line(s) |
|------|--------|------|--------|---------|
| 1 | HTTP POST to `/executor/projects/store` | `routes/web.php` | Route definition | 426 |
| 2 | `StoreProjectRequest` validated (authorize + rules) | `app/Http/Requests/Projects/StoreProjectRequest.php` | `authorize()`, `rules()` | 12-15, 21-79 |
| 3 | `ProjectController@store` invoked | `app/Http/Controllers/Projects/ProjectController.php` | `store()` | 552 |
| 4 | `DB::beginTransaction()` | `ProjectController.php` | `store()` | 554 |
| 5 | `GeneralInfoController->store($request)` | `ProjectController.php` | `store()` | 567 |
| 6 | `GeneralInfoController` validates general fields | `GeneralInfoController.php` | `store()` | 21-51 |
| 7 | `Project::create($validated)` — `creating` event fires | `GeneralInfoController.php` | `store()` | 91 |
| 8 | `project_id` = `generateProjectId()` (e.g. IIES-0029) | `Project.php` | `boot()` / `generateProjectId()` | 389-391, 394-419 |
| 9 | INSERT into `projects` | `GeneralInfoController.php` | `store()` (via `Project::create`) | 91 |
| 10 | `GeneralInfoController` returns `$project` | `GeneralInfoController.php` | `store()` | 114 |
| 11 | `Log::info('General project details stored', ...)` | `ProjectController.php` | `store()` | 568 |
| 12 | `$request->merge(['project_id' => $project->project_id])` | `ProjectController.php` | `store()` | 571 |
| 13 | `KeyInformationController->store($request, $project)` | `ProjectController.php` | `store()` | 592 |
| 14 | `switch` matches `IIES` case | `ProjectController.php` | `store()` | 686 |
| 15 | `$request->validate(['iies_bname' => 'required|string|max:255'])` | `ProjectController.php` | `store()` | 689-691 |
| 16 | `iiesPersonalInfoController->store($request, $project->project_id)` | `ProjectController.php` | `store()` | 692 |
| 17 | `mapRequestToModel()` — `$personalInfo->iies_bname = $request->input('iies_bname')` | `IIESPersonalInfoController.php` | `mapRequestToModel()` | 46-48 |
| 18 | `$personalInfo->save()` — INSERT into `project_IIES_personal_info` | `IIESPersonalInfoController.php` | `store()` | 59 |
| 19a | **Success path:** `DB::commit()` | `ProjectController.php` | `store()` | 706 |
| 19b | **Failure path:** Exception → `DB::rollBack()` | `ProjectController.php` | `store()` | 727 or 731 |

**Critical:** Transaction remains open from step 4 until step 19a. Any exception between steps 5–18 triggers step 19b; the projects row is rolled back.

---

## 2. Verified Failure Points

| Failure Point | File | Line(s) | Condition to Trigger | Effect on Transaction |
|---------------|------|---------|------------------------|------------------------|
| **ValidationException** (iies_bname absent/empty) | `ProjectController.php` | 689-691 | `$request->input('iies_bname')` absent or empty when IIES case runs | Caught at 726; `DB::rollBack()` at 727. Project row already inserted (GeneralInfoController at 567); rollback undoes it. |
| **SQLSTATE[23000]** (iies_bname null) | `IIESPersonalInfoController.php` | 59 | `$request->input('iies_bname')` returns null → model attribute null → INSERT fails | Exception propagates; caught at 730; `DB::rollBack()` at 731; full rollback |
| **KeyInformationController** exception | `KeyInformationController.php` | 74-76 | Any exception in `KeyInformationController->store` | Re-thrown; caught at 730; `DB::rollBack()` at 731 |

**Note:** With `ProjectController.php` lines 689-691, `iies_bname` is validated before `IIESPersonalInfoController->store`. If absent, `ValidationException` is thrown at 689-691, BEFORE line 692. So the DB error at `IIESPersonalInfoController.php:59` would only occur if (a) validation was bypassed, (b) validation passed (key present) but value was later nullified (no such code found), or (c) the code at 689-691 did not exist (pre-remediation). **Conclusion:** The observed DB error is consistent with pre-remediation code or an edge case not yet identified.

---

## 3. Root Cause Verification Matrix

| Hypothesis | Verdict | Evidence |
|------------|---------|----------|
| **Disabled inputs not submitted** | **PROVEN** | HTML spec: disabled form controls are excluded from submission. W3C HTML5 spec; `createProjects.blade.php:216-217` sets `field.disabled = true` via `disableInputsIn()`. |
| **iies_bname missing from request** | **PROVEN (when disabled)** | When `#iies-sections` inputs are disabled and submit handler does not enable them, POST omits `iies_bname`. `$request->input('iies_bname')` returns null when key absent (Laravel `Illuminate\Http\Request::input()`). |
| **Validation allows null to reach DB** | **DISPROVEN (current code)** | `ProjectController.php:689-691` validates `iies_bname` as `required|string|max:255` before `IIESPersonalInfoController->store`. Absent/empty fails validation. **PARTIALLY PROVEN (pre-remediation):** `StoreProjectRequest` has no `iies_bname` rule (`StoreProjectRequest.php:21-79`). `StoreIIESPersonalInfoRequest` has `required` (`StoreIIESPersonalInfoRequest.php:17`) but is **never used** in create flow (`IIESPersonalInfoController.php:51` receives `FormRequest` / `StoreProjectRequest`). |
| **Project insert happens before failure** | **PROVEN** | `GeneralInfoController.php:91` inserts before `ProjectController.php:568` log. IIES failure occurs at `IIESPersonalInfoController.php:59`, after project insert. |
| **Log written before commit** | **PROVEN** | `ProjectController.php:568` log runs before `DB::commit()` at 706. Transaction still open; log does not imply durability. |
| **Exception triggers full rollback** | **PROVEN** | `ProjectController.php:727` and `731` call `DB::rollBack()`. `IIESPersonalInfoController.php:64` re-throws; no swallowing. Single transaction scope; rollback undoes all work. |
| **Multiple submissions reuse same project_id** | **PROVEN** | `Project.php:413-419` — `generateProjectId()` uses `latest('id')` with prefix. If previous row rolled back, it does not exist. Next submission with same project_type gets same sequence number (e.g. IIES-0029 again). |
| **IIES section conditionally disabled** | **PROVEN** | `createProjects.blade.php:65` — `#iies-sections` has `style="display:none;"`. `hideAndDisableAll()` at 226-231 disables all sections. `toggleSections()` at 244-246 enables IIES only when `project_type === 'Individual - Initial - Educational support'`. |
| **Submit handler enables disabled fields** | **PROVEN** | `createProjects.blade.php:371-374` (regular submit) and 442-445 (Save as Draft) — `disabledFields.forEach(field => { field.disabled = false; })`. If handler bypassed (JS error, direct submit), fields stay disabled. |
| **save_as_draft relaxes iies_bname validation** | **DISPROVEN** | `ProjectController.php:689-691` — no `save_as_draft` check; validation runs unconditionally. |

---

## 4. Phase-Wise Fix Plan (NO CODE)

Design only. No implementation.

---

### Phase 0 — Safety & Observability

**Objective:** Remove misleading logs and add diagnostic clarity so failures are traceable without implying durability.

**Files affected:**
- `app/Http/Controllers/Projects/ProjectController.php` (lines 568, 557-564, 718-719)
- `app/Http/Controllers/Projects/GeneralInfoController.php` (lines 85-90, 95-111)

**Invariant enforced:**
- Logs that imply persistence (e.g. "stored", "saved") appear only after `DB::commit()` or are reworded to indicate in-progress state.
- Failure logs include `project_id` and explicit "pre-commit" or "rolled back" context.

**Why it prevents this failure class:**
- Prevents confusion when "General project details stored" is logged but the row is rolled back.
- Reduces support burden when diagnosing "no row after log" scenarios.

---

### Phase 1 — Validation Boundary Hardening

**Objective:** Ensure `iies_bname` cannot reach the DB as null without validation failing first. Use `StoreIIESPersonalInfoRequest` or equivalent validation in the create flow.

**Files affected:**
- `app/Http/Controllers/Projects/ProjectController.php` (lines 686-698)
- `app/Http/Requests/Projects/StoreProjectRequest.php` (rules)
- `app/Http/Requests/Projects/IIES/StoreIIESPersonalInfoRequest.php` (rules, conditional save_as_draft handling)

**Invariant enforced:**
- When `project_type` is IIES and `save_as_draft` is false, `iies_bname` is required and enforced before any IIES sub-controller runs.
- When `save_as_draft` is true, IIES sub-controllers either skip or use relaxed validation; no NOT NULL constraint violation for absent optional fields.

**Why it prevents this failure class:**
- Absent `iies_bname` fails validation at the boundary; no INSERT reaches the DB with null.
- Defense in depth: validation at FormRequest level (or early in controller) is more robust than only controller-level checks.

---

### Phase 2 — Transaction Boundary Normalization

**Objective:** Clarify transaction boundaries and ensure no partial success when IIES sub-controllers fail.

**Files affected:**
- `app/Http/Controllers/Projects/ProjectController.php` (lines 554, 706, 726-727, 730-731)
- `app/Http/Controllers/Projects/IIES/IIESPersonalInfoController.php` (lines 51-66)

**Invariant enforced:**
- Single outer transaction for the entire create flow; no nested transactions.
- All sub-controllers propagate exceptions; no swallowed exceptions that return error responses without re-throwing.
- `DB::commit()` runs only after all type-specific steps succeed.

**Why it prevents this failure class:**
- Prevents partial commits where some data persists and others do not.
- Ensures rollback is consistent when any sub-controller fails.

---

### Phase 3 — UI / JS Determinism

**Objective:** Ensure IIES inputs are always submitted when the user selects IIES type, regardless of JS execution path.

**Files affected:**
- `resources/views/projects/Oldprojects/createProjects.blade.php` (lines 65, 214-224, 235-246, 358-405, 417-458)
- `resources/views/projects/partials/IIES/personal_info.blade.php` (line 9)

**Invariant enforced:**
- IIES inputs are never disabled when `project_type` is IIES at submit time, OR
- Server-side logic does not rely on client-side submission of IIES fields; if `project_type` is IIES and `iies_bname` is absent, validation fails with clear error.
- Submit handler is robust: runs before any submit path (regular submit, Save as Draft); uses reliable selectors; handles errors without leaving form in inconsistent state.

**Why it prevents this failure class:**
- Prevents the scenario where disabled inputs yield absent keys and (if validation gap existed) DB error.
- Reduces reliance on JS for correctness; validation is the primary guard.

---

### Phase 4 — Final Guarantees

**Objective:** Add safeguards so that even if a validation gap or race occurs, the DB layer does not silently accept invalid data.

**Files affected:**
- `app/Http/Controllers/Projects/IIES/IIESPersonalInfoController.php` (lines 44-48, 56-59)
- `database/migrations/` (existing — no changes; constraint already present)

**Invariant enforced:**
- Before `$personalInfo->save()`, explicitly check that required fields (e.g. `iies_bname`) are non-null and non-empty; throw a clear validation-style exception if not.
- Log request keys present/absent when IIES store is invoked for debugging.

**Why it prevents this failure class:**
- Last line of defense: if null somehow reaches the model, fail explicitly instead of DB constraint violation.
- Improves diagnostic clarity for future incidents.

---

## 5. Non-Goals

The following are **OUT OF SCOPE** for this fix plan:

1. **Schema changes** — No migration to alter `project_IIES_personal_info` or `projects` table structure.
2. **Refactoring other project types** — IES, ILP, IAH, etc. are not in scope unless they exhibit the same failure pattern.
3. **AJAX submission** — Plan assumes HTML form POST; no conversion to AJAX.
4. **New UI frameworks** — No replacement of Vue/React/Blade; work within existing Blade + vanilla JS.
5. **Performance optimization** — No changes for performance; only correctness and observability.
6. **Predecessor project flow** — No changes to NEXT PHASE or predecessor data handling.
7. **Edit flow** — Plan focuses on create flow; edit flow is out of scope unless it shares the same failure.
8. **Permission or authorization changes** — No modification to `authorize()` or policy logic.

---

## 6. Discrepancies from Earlier Forensic Conclusions

| Earlier Conclusion | Verification Result |
|--------------------|---------------------|
| "Validation would fail before DB" | **With current code:** Correct. `ProjectController.php:689-691` validates before `IIESPersonalInfoController->store`. If absent, `ValidationException` is thrown; DB error is not reached. |
| "Pre-remediation had no validation" | **Correct.** `StoreProjectRequest` has no `iies_bname`; `StoreIIESPersonalInfoRequest` is unused. Pre-remediation would allow null to reach DB. |
| "Same project_id on multiple attempts" | **Correct.** `Project::generateProjectId()` uses `latest()`; rolled-back rows are not committed, so next attempt can regenerate same ID. |

**No correction needed** for the core forensic conclusions. The observed symptom (DB error + no row) is consistent with pre-remediation code or an edge case where validation passed but the value was later nullified (no such code found).

---

## 7. Root Cause Ranking (by certainty)

| Rank | Cause | Certainty | Evidence |
|------|-------|-----------|----------|
| 1 | **Absent `iies_bname` in request** (disabled input omitted from POST) | High | HTML spec; disabled inputs not submitted; `mapRequestToModel` uses `$request->input()` which returns null when absent. |
| 2 | **Validation gap** (pre-remediation: no orchestration validation) | High | `StoreProjectRequest` has no `iies_bname`; `StoreIIESPersonalInfoRequest` unused in create flow. |
| 3 | **Log before commit** (misleading durability) | High | Line 568 before line 706; verified. |
| 4 | **Exception triggers full rollback** | High | Lines 727, 731; verified. |
| 5 | **JS submit handler bypass** (handler not run, fields stay disabled) | Medium | Handler exists; bypass possible via JS error, direct submit, or bookmark. |

---

## Phase 1 — Validation Boundary Hardening (Locked)

**Status:** Implemented, verified, locked.  
**Date locked:** 2026-02-08

### Illegal state before Phase 1

- **Non-draft IIES:** When `project_type` was IIES and `save_as_draft` was false, `iies_bname` could be absent from the request (e.g. disabled input omitted from POST). Without a validation guard, execution reached `IIESPersonalInfoController->store`. `mapRequestToModel` assigned `null` to `iies_bname`, and `$personalInfo->save()` triggered `SQLSTATE[23000]: Column 'iies_bname' cannot be null`.
- **Draft IIES:** When `save_as_draft` was true, the previous unconditional validation required `iies_bname`, blocking legitimate draft saves when the user had not yet filled the IIES Personal Info section.

### Guard now in place

- **Non-draft IIES:** `$request->validate(['iies_bname' => 'required|string|max:255'])` runs before any IIES sub-controller. Absent or empty `iies_bname` triggers `ValidationException` (422); execution never reaches persistence.
- **Draft IIES:** Validation is skipped when `save_as_draft == '1'`. `IIESPersonalInfoController->store` is invoked only when `$request->filled('iies_bname')`. If `iies_bname` is absent on draft, persistence is skipped; no DB insert with null, no NOT NULL violation.

### Draft vs non-draft intent

- Draft vs non-draft is determined by `$request->has('save_as_draft') && $request->input('save_as_draft') == '1'`.
- Non-draft: strict validation; persistence requires `iies_bname`.
- Draft: relaxed validation; IIES Personal Info is persisted only when `iies_bname` is present.

### Scope of Phase 1

- No schema changes.
- No UI or Blade changes.
- No JavaScript changes.
- No transaction or rollback logic changes.
- Changes limited to `ProjectController.php` IIES case block.

### Local verification

- Draft IIES projects with absent `iies_bname` no longer fail with NOT NULL violations.
- Non-draft IIES projects with absent `iies_bname` receive `ValidationException` (422).
- Non-IIES project types behave as before.
