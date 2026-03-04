# CCI Full Module Stability Audit

**Task:** Full CCI Module Stability Audit (Post-Refactor Consolidated Review)  
**Date:** March 4, 2026  
**Mode:** Audit (STRICT — no code modified)

---

## 1. Controller Orchestration

### ProjectController@store (CCI flow)

- **Invocation order:** CCI handler invokes in fixed order: Achievements → Age Profile → Annexed Target Group → Economic Background → Personal Situation → Present Situation → Rationale → Statistics. Order is deterministic and appropriate.
- **Correct invocation:** All CCI controllers receive `($request, $project->project_id)` except EduRUT Annexed which receives only `($request)` and extracts `project_id` from the request.
- **Duplicate deletes:** No duplicate delete scenarios. AnnexedTargetGroupController performs a single scoped delete before recreate; AgeProfileController uses `updateOrCreate` (no delete).
- **Unintended overwrite:** AgeProfile uses `updateOrCreate(['project_id' => $projectId], $validated)` — correct for single-row section. Annexed uses delete-recreate — correct for multi-row.
- **Missing-key destructive scenario:** AnnexedTargetGroupController uses `where('project_id', $projectId)->delete()` — scoped; no unscoped delete. AgeProfile `updateOrCreate` is keyed by `project_id`.
- **Transaction handling:** ProjectController wraps full store in `DB::beginTransaction()` / `DB::commit()` / `DB::rollBack()`. AnnexedTargetGroupController has its own transaction; AgeProfileController does not (acceptable as single atomic write).
- **$projectId usage:** All CCI controllers use `$project->project_id` consistently.

### ProjectController@update (CCI flow)

- Same pattern as store. CCI update branch invokes same controllers with `$project->project_id`.
- Transaction handling: ProjectController transaction wraps the update flow; AnnexedTargetGroupController has its own nested transaction (acceptable).

### AgeProfileController

- `store`: Creates new row (no `updateOrCreate`). Used only on initial project create.
- `update`: Uses `updateOrCreate(['project_id' => $projectId], $validated)` — correct for hasOne.
- Validation via `StoreCCIAgeProfileRequest` / `UpdateCCIAgeProfileRequest` with normalization and rules.

### AnnexedTargetGroupController

- `store` / `update`: Delete-all-then-recreate with `where('project_id', $projectId)->delete()` — scoped.
- **Validation gap:** Uses `extractValidatedRows()` with scalar coercion only. Does **not** invoke `UpdateCCIAnnexedTargetGroupRequest` rules when called from ProjectController. Annexed data is not validated (dates, max length, etc.) in the orchestrated flow.

### Verdict

**Controller orchestration: RISK**

- Minor: AnnexedTargetGroupController skips validation when invoked from ProjectController.
- Minor: AgeProfileController `store` creates unconditionally; for projects created with empty age profile, this could create an empty row. Behavior is acceptable but worth noting.

---

## 2. Data Flow Consistency

### Annexed Target Group

| Stage | Flow | Finding |
|-------|------|---------|
| Form | Sends `annexed_target_group` array | OK |
| Request | UpdateProjectRequest validates general fields only; no CCI annexed rules | **Validation gap** |
| Controller | `extractValidatedRows()` — no validation, scalar coercion only | **Validation bypass** |
| DB | Delete-recreate with `project_id` scope | OK |
| Hydrator | `cciAnnexedTargetGroupController->show()` → Collection | OK |
| View | `$annexedTargetGroup` Collection | OK |
| Export | DOC uses `$project->cciAnnexedTargetGroup`; PDF uses hydrator `annexedTargetGroup` | OK |

- **Null crash:** Handled with `?? null` and `isRowFullyEmpty()`.
- **Object/array shape:** Controller returns Collection; views and exports expect Collection — consistent.

### Age Profile

| Stage | Flow | Finding |
|-------|------|---------|
| Form | Sends age profile fields | OK |
| Request | AgeProfileController uses Store/UpdateCCIAgeProfileRequest with normalization | OK |
| Controller | `updateOrCreate` with validated data | OK |
| DB | Single row per project | OK |
| Hydrator | `cciAgeProfileController->show()` returns array (toArray or default) | OK |
| View | `$ageProfile` array | OK |
| Export | PDF uses hydrator `ageProfile` (array). DOC uses `$project->age_profile` | **Bug — wrong relation** |

- Age Profile `show()` returns array or default array; blade expects array with `$ageProfile['field']` — consistent for PDF path.

### Verdict

**Data flow consistency: RISK**

- Annexed Target Group: validation bypass in orchestrated flow.
- DOC export: `age_profile`, `rationale`, `statistics`, `personal_situation`, `economic_background`, `achievements`, `present_situation` use wrong relation names (`age_profile` instead of `cciAgeProfile`, etc.), causing runtime errors or empty/incorrect output.

---

## 3. Export Validation

### CCI DOC export

- **Annexed Target Group:** Uses `$project->cciAnnexedTargetGroup` — correct (Phase 3 fix applied).
- **Age Profile:** Uses `$project->age_profile` — wrong. Project has `cciAgeProfile`, not `age_profile`. `$project->age_profile` is null; `$ageProfile['field']` causes "Trying to access array offset on null".
- **Rationale:** Uses `$project->rationale` — wrong. Project has `cciRationale`. `$project->rationale->description` causes "Trying to get property 'description' of null".
- **Statistics:** Uses `$project->statistics` — wrong. Project has `cciStatistics`.
- **Personal Situation:** Uses `$project->personal_situation` — wrong. Project has `cciPersonalSituation`.
- **Economic Background:** Uses `$project->economic_background` — wrong. Project has `cciEconomicBackground`.
- **Achievements:** Uses `$project->achievements` — wrong. Project has `cciAchievements`.
- **Present Situation:** Uses `$project->present_situation` — wrong. Project has `cciPresentSituation`.

DOC export loads project with `with([...])` and does not eager-load CCI relations. Wrong property names lead to null access and crashes for CCI DOC export.

### CCI PDF export

- Uses `ProjectDataHydrator::hydrate()` which calls CCI controllers’ `show()`.
- Hydrator passes `ageProfile`, `annexedTargetGroup`, etc. to the view.
- Blade partials receive correct variables and handle empty/null cases.

**PDF path: SAFE**

### Hydrator correctness

- For CCI, hydrator calls `cciAgeProfileController->show()` and `cciAnnexedTargetGroupController->show()`.
- Age profile `show()` returns array or default; annexed returns Collection or null (handled).
- Project-type branching correctly limits CCI data to CCI projects.

### Cross-project-type contamination

- Hydrator switches on `$project->project_type`; CCI data loaded only for `'CHILD CARE INSTITUTION'`.
- DOC export uses `if ($project->project_type === 'CHILD CARE INSTITUTION')` before CCI sections.
- No cross-contamination found.

### Verdict

**Export stability: RISK**

- DOC export: wrong relation names for rationale, statistics, age_profile, personal_situation, economic_background, achievements, present_situation; will crash or produce wrong/empty output for CCI DOC.
- PDF export and hydrator: stable.

---

## 4. Database & Model Integrity

### Multi-row sections (delete-recreate)

| Section | Strategy | Scoped? |
|---------|----------|---------|
| CCI Annexed Target Group | `where('project_id', $projectId)->delete()` then create | Yes |

### Single-row sections (updateOrCreate)

| Section | Strategy | Key |
|---------|----------|-----|
| CCI Age Profile | `updateOrCreate(['project_id' => $projectId], $validated)` | `project_id` |

### Relationships

- **Project → cciAgeProfile:** `hasOne(ProjectCCIAgeProfile)` — correct (one row per project).
- **Project → cciAnnexedTargetGroup:** `hasMany(ProjectCCIAnnexedTargetGroup)` — correct (multiple rows per project).

### Orphaned data

- Annexed: delete is scoped by `project_id`; no unscoped delete.
- Age profile: `updateOrCreate` by `project_id`; no orphan creation.

### Unscoped delete risk

- Annexed uses `where('project_id', $projectId)->delete()` — scoped. No unscoped delete.

### Verdict

**Database & model integrity: SAFE**

- Cardinality and strategies are correct; no orphan or unscoped-delete risk identified.

---

## 5. Edge Case Simulation

| Scenario | Risk Level | Conclusion |
|----------|------------|------------|
| 1. Create CCI project with no annexed rows and no age profile | Low | Annexed: empty array → no rows created. Age profile: store creates row with nullable fields; updateOrCreate on update handles absence. OK. |
| 2. Create full CCI project with max rows | Low | Delete-recreate and updateOrCreate behave correctly. OK. |
| 3. Edit project and remove all annexed rows | Low | Delete removes all; empty `validatedRows` → no creates. Correct. |
| 4. Edit project and remove age profile | Low | Clearing form sends empty/null values; updateOrCreate updates row with nulls. No explicit "delete age profile" in flow; row remains with nulls. Acceptable. |
| 5. Rapid consecutive updates | Low | ProjectController and AnnexedTargetGroupController use transactions; last write wins. No race-condition mitigation; acceptable for typical usage. |
| 6. Export after edits | **High** | DOC export will fail or show wrong/empty CCI sections due to wrong relation names. PDF export works. |

---

## 6. Architectural Alignment

### EduRUT

- Annexed: delete-recreate on update; store appends (no delete). Different from CCI (CCI always delete-recreate). For create-only flow this is acceptable.
- EduRUT update: skip mutation when section is empty (`isEduRUTAnnexedTargetGroupMeaningfullyFilled`). CCI always mutates.

### RST (TargetGroupAnnexure)

- Delete-recreate pattern; meaningfully-filled check before mutate. Aligns with CCI.

### LDP (TargetGroup)

- Delete-recreate; meaningfully-filled check. Aligns with CCI.

### IGE

- Uses delete-recreate for multi-row sections. Pattern consistent.

### CCI patterns

- Age Profile: updateOrCreate (single-row) — aligned with other single-row sections.
- Annexed: delete-recreate — aligned with EduRUT, RST, LDP for multi-row.

### Verdict

**Architectural alignment: SAFE**

- CCI follows delete-recreate for multi-row and updateOrCreate for single-row. Minor differences (e.g. EduRUT skip-when-empty) do not introduce instability.

---

## 7. Overall Risk Assessment

**Level: MEDIUM–HIGH**

- **Critical:** DOC export uses wrong relation names for most CCI sections and will crash or produce incorrect output.
- **Moderate:** Annexed Target Group validation is bypassed when called from ProjectController.
- **Low:** Controller orchestration, DB/model integrity, and PDF/hydrator are sound.

---

## 8. Final Verdict

**REQUIRES CORRECTION BEFORE PRODUCTION**

### Mandatory corrections

1. **ExportController DOC (CCI sections):** Replace wrong relation/property names with correct ones:
   - `rationale` → `cciRationale`
   - `statistics` → `cciStatistics`
   - `age_profile` → `cciAgeProfile` (and handle object vs array: use `$ageProfile->field` or `optional($ageProfile)->field`)
   - `personal_situation` → `cciPersonalSituation`
   - `economic_background` → `cciEconomicBackground`
   - `achievements` → `cciAchievements`
   - `present_situation` → `cciPresentSituation`
2. **Age Profile in DOC:** Ensure `$ageProfile` is treated as object (model or null); add null checks before array-style access.

### Recommended improvements

1. **AnnexedTargetGroupController:** Validate via `UpdateCCIAnnexedTargetGroupRequest` (or equivalent) when invoked from ProjectController so annexed data is validated in the orchestrated flow.

---

## 9. Audit Compliance

- [x] No code modified during this audit.
- [x] Documentation file created at `Documentations/V2/EDIT/CCI/CCI_Full_Module_Stability_Audit_20260304.md`.
- [x] Final verdict provided.
- [x] Audit stopped as requested.
