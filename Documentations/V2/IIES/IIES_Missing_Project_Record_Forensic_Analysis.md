# IIES Missing Project Record — Forensic Analysis

**Date:** 2026-02-08  
**Scope:** Read-only forensic analysis  
**Objective:** Determine why project row is not persisted during IIES project creation  
**Observed:** Log shows "General project details stored { project_id: IIES-0029 }" but `SELECT * FROM projects WHERE project_id = 'IIES-0029'` returns 0 rows

---

## 1. End-to-End Execution Trace

```
UI (Blade)
  │
  ├─ Form: #createProjectForm, POST to /executor/projects/store
  ├─ #iies-sections: display:none, inputs disabled by default
  ├─ On project_type change → toggleSections() → show IIES + enableInputsIn(iiesSections)
  │
  ▼
JS (submit handler)
  │
  ├─ Before submit: enable all [disabled] fields, show [style*="display: none"] sections
  ├─ Form submits via native POST (no AJAX)
  │
  ▼
HTTP Payload
  │
  ├─ Disabled inputs are NOT included in POST (HTML spec)
  ├─ If iies_bname input was disabled at submit → key absent from request
  │
  ▼
StoreProjectRequest (validation)
  │
  ├─ No iies_bname rule in StoreProjectRequest
  ├─ Request passes to controller
  │
  ▼
ProjectController@store
  │
  ├─ L554: DB::beginTransaction()
  ├─ L567-568: GeneralInfoController->store() → Project::create() → INSERT projects
  │             Log: "General project details stored"  ← ROW EXISTS ONLY IN UNCOMMITTED TX
  ├─ L592: KeyInformationController->store()
  ├─ L686-698: IIES case: validate iies_bname, iiesPersonalInfoController->store()
  │
  ▼
IIESPersonalInfoController->store
  │
  ├─ mapRequestToModel: $personalInfo->iies_bname = $request->input('iies_bname')
  │   If key absent → null
  ├─ $personalInfo->save() → INSERT project_IIES_personal_info
  │   iies_bname NOT NULL → SQLSTATE[23000] thrown
  │
  ▼
Exception propagates
  │
  ├─ IIESPersonalInfoController L64: throw $e
  ├─ ProjectController L730-731: catch, DB::rollBack()
  │
  ▼
Transaction outcome: FULL ROLLBACK
  │
  └─ Projects row undone. No commit. Log already written.
```

---

## 2. Key Findings

- **Project ID generation:** `project_id` (e.g. IIES-0029) is set in `Project::creating` (Project.php:389-391) before INSERT. It is visible in the model and logs immediately after `GeneralInfoController` returns.
- **Log vs durability:** "General project details stored" is logged at ProjectController.php:568 **before** `DB::commit()` (L706). The projects row exists only in the uncommitted transaction.
- **Single transaction:** All work (projects, key info, IIES) runs inside one `DB::beginTransaction()` (L554). Any propagated exception triggers `DB::rollBack()` (L727 or L731).
- **IIESPersonalInfoController exception handling:** Catches, logs, then **re-throws** (IIESPersonalInfoController.php:64). The exception reaches ProjectController.
- **Null source:** `$request->input('iies_bname')` returns `null` when the key is absent (Laravel behavior). `mapRequestToModel` assigns null to the model; `save()` attempts INSERT with NULL.
- **DB constraint:** `project_IIES_personal_info.iies_bname` is NOT NULL (migration 2025_01_29_174348, L14).
- **Conditional UI:** IIES inputs live in `#iies-sections` with `display:none` and are disabled until project type is IIES. Disabled inputs are not submitted (HTML spec).
- **Pre-submit handler:** Submit handler (createProjects.blade.php:371-374) enables disabled fields before submit. If it does not run (JS error, bypass), IIES inputs stay disabled and are omitted from POST.
- **Orchestration validation:** ProjectController.php:689-691 validates `iies_bname` as required before IIES sub-controllers. Absent/empty fails validation. **Caveat:** This was added in Phase 0 remediation; pre-remediation code had no such check.

---

## 3. Failure Point(s)

| Failure Point | Location | Timing | Effect |
|---------------|----------|--------|--------|
| **Primary** | `IIESPersonalInfoController.php:59` — `$personalInfo->save()` | After project INSERT, before commit | INSERT into `project_IIES_personal_info` fails with `SQLSTATE[23000]: Column 'iies_bname' cannot be null` |
| **Trigger** | `IIESPersonalInfoController.php:46-48` — `mapRequestToModel` | Before save | `$request->input('iies_bname')` returns null → model attribute set to null |
| **Upstream cause** | HTTP request | Before controller | Key `iies_bname` absent from POST body |

**Failure happens AFTER project insert.** GeneralInfoController completes successfully. The projects row is written within the transaction. The failure occurs in a later step (IIES Personal Info). The ensuing rollback removes the projects row.

---

## 4. Determinism Analysis

| Question | Answer | Evidence |
|----------|--------|----------|
| **Always fails?** | No | Intermittent error reported |
| **Sometimes fails?** | Yes | Depends on whether `iies_bname` is in the request |
| **Depends on UI state?** | Yes | IIES inputs are disabled when section hidden; disabled inputs not submitted |
| **Depends on JS execution?** | Yes | Submit handler must enable disabled fields before submit |
| **Depends on submit path?** | Yes | Save as Draft uses same handler; both paths enable disabled fields. If handler bypassed (e.g. direct submit, JS error), fields may stay disabled |

**Determinism:** **Conditional.** Failure occurs when:
1. `project_type` = IIES (so IIES sub-controllers run)
2. `iies_bname` absent from POST (e.g. input disabled, or user never filled)
3. (Pre-remediation) No orchestration validation to reject before persistence

---

## 5. Evidence Table

| Finding | File | Line(s) | Evidence |
|---------|------|---------|----------|
| Transaction start | ProjectController.php | 554 | `DB::beginTransaction();` |
| Project INSERT | GeneralInfoController.php | 91 | `$project = Project::create($validated);` |
| project_id generation | Project.php | 389-391, 394-419 | `creating` event calls `generateProjectId()`; maps IIES → "IIES-" prefix |
| "General project details stored" log | ProjectController.php | 568 | `Log::info('General project details stored', ['project_id' => $project->project_id]);` |
| Log before commit | ProjectController.php | 568 vs 706 | Log at 568; `DB::commit()` at 706 |
| IIES case validation | ProjectController.php | 689-691 | `$request->validate(['iies_bname' => 'required|string|max:255']);` |
| IIES Personal Info store | ProjectController.php | 692 | `$this->iiesPersonalInfoController->store($request, $project->project_id);` |
| Null assignment path | IIESPersonalInfoController.php | 46-48 | `$personalInfo->$field = $request->input($field)` — input() returns null when key absent |
| DB NOT NULL constraint | migration 2025_01_29_174348 | 14 | `$table->string('iies_bname');` |
| Exception re-throw | IIESPersonalInfoController.php | 62-64 | `catch (\Exception $e) { ... throw $e; }` |
| Rollback on exception | ProjectController.php | 730-731 | `catch (\Exception $e) { DB::rollBack(); ... }` |
| IIES section initially hidden | createProjects.blade.php | 65 | `<div id="iies-sections" style="display:none;">` |
| Disable/enable on project type | createProjects.blade.php | 226-231, 244-246 | `hideAndDisableAll()` disables; `enableInputsIn(iiesSections)` when IIES selected |
| Submit handler enables disabled | createProjects.blade.php | 371-374 | `disabledFields.forEach(field => { field.disabled = false; });` |
| iies_bname input | partials/IIES/personal_info.blade.php | 9 | `<input type="text" name="iies_bname" class="form-control">` |
| StoreProjectRequest no iies_bname | StoreProjectRequest.php | 21-79 | rules() has no iies_bname |
| StoreIIESPersonalInfoRequest has rule | StoreIIESPersonalInfoRequest.php | 17 | `'iies_bname' => 'required|string|max:255'` — not used in create flow |

---

## 6. Final Section

### WHAT IS PROVEN

1. **Project row is rolled back, not missing on insert.** The projects INSERT succeeds. The row exists in the uncommitted transaction. A later failure (IIES Personal Info) triggers rollback; the row is undone.
2. **Log is misleading.** "General project details stored" is logged before commit. Rollback can occur afterward; the log implies success despite no committed row.
3. **IIES Personal Info fails when `iies_bname` is null.** `$request->input('iies_bname')` returns null for absent key; `mapRequestToModel` assigns null; INSERT violates NOT NULL.
4. **Disabled inputs are not submitted.** HTML behavior: disabled form controls are excluded from submission.
5. **IIES inputs are conditionally disabled.** They are disabled until project type is IIES and `enableInputsIn(iiesSections)` runs.
6. **Submit handler enables disabled fields.** If it runs, IIES inputs are enabled before submit.
7. **Exception propagates and triggers full rollback.** IIESPersonalInfoController re-throws; ProjectController catches and calls `DB::rollBack()`.

### WHAT IS NOT PROVEN

1. **Exact reproduction path.** Whether the failure occurred on regular submit vs Save as Draft, or due to a specific JS error, is not confirmed from code alone.
2. **Code version at failure.** Whether the incident occurred before or after Phase 0 validation (L689-691) was added.
3. **Whether `iies_bname` was ever in the request.** The DB error implies NULL; that requires an absent key. Empty string would not produce NULL. This is inferred, not directly observed.

### MOST LIKELY ROOT CAUSE(S)

1. **Primary:** `iies_bname` is **absent from the HTTP request** because the IIES Name input was **disabled** at submit time. Possible reasons:
   - Submit handler did not run (JS error, bypass, or different submit path)
   - User submitted before selecting IIES or before the section was enabled
   - Browser back/forward or state restoration left inputs disabled

2. **Mechanism:** Absent key → `$request->input('iies_bname')` = null → `mapRequestToModel` assigns null → INSERT fails → exception → rollback → no committed projects row.

3. **Log vs persistence:** The log "General project details stored" is written after the projects INSERT but before commit. Rollback undoes the row; the log does not reflect that.

### ALTERNATIVE POSSIBLE CAUSES

1. **Pre-remediation:** Without orchestration validation (L689-691), an absent `iies_bname` would reach the DB. With it, validation would fail first and the user would see validation errors (not the DB error). If the DB error was observed, it suggests either pre-remediation code or a scenario where validation passed but data was altered later (less likely).
2. **Race or timing:** Theoretically, a race could leave fields disabled, but normal form submission is synchronous; this is speculative.
3. **Request tampering:** Middleware or proxy stripping the field could explain absence. No such logic was found in the inspected code; marked as **NOT PROVEN**.
