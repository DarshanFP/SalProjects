# CCI Annexed Target Group — Implementation Plan Audit Report

**Date:** March 3, 2026  
**Mode:** Audit only — no code modifications  
**Scope:** Validate the delete-all-then-recreate implementation plan against reference documents

---

## Reference Documents

1. CCI Annexed Target Group — Architectural Feasibility Audit  
2. CCI Pre-Phase 1 Form and Age Profile Audit  
3. CCI Annexed Target Group & Age Profile Discrepancy Findings

---

# Part 1 — Architectural Consistency Check

## 1.1 Delete-all-then-recreate consistency with other modules

| Module | Table | Update Pattern | Status |
|--------|-------|----------------|--------|
| EduRUT Annexed Target Group | `project_edu_rut_annexed_target_groups` | Delete-all, then create | Reference |
| EduRUT Target Group | `project_edu_rut_target_groups` | Delete-all, then create | Reference |
| RST Target Group Annexure | `project_rst_target_group_annexure` | Delete-all, then create | Reference |
| LDP Target Group | `project_ldp_target_groups` | Delete-all, then create | Reference |
| IGE New Beneficiaries | `project_ige_new_beneficiaries` | Delete-all, then create | Reference |
| **CCI Annexed Target Group** | `project_CCI_annexed_target_group` | `updateOrCreate(project_id, beneficiary_name)` — **Problematic** | Current |

**Conclusion:** CCI is the **only** multi-row project module using composite `updateOrCreate`. Delete-all-then-recreate aligns with the standard pattern used by EduRUT, RST, LDP, and IGE.

---

## 1.2 Foreign key constraints

| Item | Status |
|------|--------|
| Foreign key to `projects` | **None** — migration has no `foreign()` on `project_id` |
| Other tables referencing this table | **None** — no incoming FK |
| Outgoing dependencies | Table only references `project_id`; no FK defined in migration |

**Conclusion:** Delete by `project_id` does not violate any foreign key constraint.

---

## 1.3 Feasibility audit dependency findings

| Dependency Type | Finding |
|-----------------|---------|
| Incoming dependencies | None — no other tables reference `project_CCI_annexed_target_group` |
| Row identity (`id`, `CCI_target_group_id`) | Not used outside CRUD; never passed to forms or APIs |
| Soft deletes, observers, events | None |
| Reports, exports, MIS, dashboards, logs | No dependency on preserving row identity |

**Conclusion:** No dependency discovered in feasibility audit blocks delete-recreate.

---

## 1.4 Row identity stability

| Criterion | Finding |
|-----------|---------|
| Does any export depend on stable row IDs? | No |
| Does any hydrator depend on stable row IDs? | No — ProjectDataHydrator uses `cciAnnexedTargetGroupController->show()` which returns `where('project_id')->get()` |
| Does any PDF layer depend on stable row IDs? | No — PDF view uses collection from hydrator |
| Do logs or audit trails require preserving row identity? | No |

**Conclusion:** No layer depends on stable row identity.

---

## 1.5 Model boot() logic

| Item | Details |
|------|---------|
| Boot hook | `creating` — sets `CCI_target_group_id` via `generateCCITargetGroupId()` |
| Sequence logic | Uses `latest('id')->first()` → next sequence number |
| After delete-recreate | New rows get new `CCI_target_group_id` on `creating` |

**Conclusion:** Delete-recreate does not break `CCI_target_group_id` generation. New ID generation on recreate is acceptable.

---

## Part 1 Output

**ARCHITECTURAL ALIGNMENT: SAFE**

- Delete-recreate is consistent with EduRUT, RST, LDP, IGE.
- No FK constraint violations.
- No blocking dependency from feasibility audit.
- Not dependent on stable row identity.
- Model boot logic compatible with delete-recreate.

---

# Part 2 — Controller Refactor Impact Audit

## 2.1 Current controller behavior

| Item | Status |
|------|--------|
| Uses `updateOrCreate(project_id + beneficiary_name)` | Yes (update method, lines 86–95) |
| Uses `validated()` consistently | **No** — uses `$request->only(['annexed_target_group'])` |
| Iterates empty rows | **Yes** — `is_array($group)` guard only; empty rows still passed to `updateOrCreate` |
| Scoped input | Yes — `$request->only(['annexed_target_group'])` |
| DB transaction | Yes |
| Per-value scalar coercion | Yes |

---

## 2.2 Proposed new logic (per feasibility audit / plan)

| Requirement | Details |
|-------------|---------|
| Delete only by `project_id` | `ProjectCCIAnnexedTargetGroup::where('project_id', $projectId)->delete()` |
| Wrap in `DB::transaction` | Already present; preserve |
| Skip fully empty rows | Add check: skip row if all fields are null/empty |
| Use validated request data | Switch from `$request->only()` to `$request->validated()` for `annexed_target_group` |

---

## 2.3 Behavior change analysis

| Question | Finding |
|----------|---------|
| Does skipping empty rows change current behavior? | **Yes** — currently empty rows hit `updateOrCreate(project_id, '')` and can overwrite. Skipping improves behavior. |
| Any unintended side effect? | Low — need to ensure `UpdateProjectRequest` / orchestration includes `annexed_target_group` rules if switching to `validated()` |
| Mass assignment fully supports `create()`? | **Yes** — all fields (`beneficiary_name`, `dob`, `date_of_joining`, `class_of_study`, `family_background_description`) are in `$fillable` |

---

## 2.4 Edge cases

| Edge Case | Risk | Mitigation |
|-----------|------|------------|
| Validation not merged in orchestration | `validated()` returns empty for `annexed_target_group` | Confirm `UpdateProjectRequest` or merge rules from `UpdateCCIAnnexedTargetGroupRequest` |
| All rows empty | No rows created | Expected; consistent with “skip empty rows” |
| Duplicate beneficiary names | Both preserved (unlike current overwrite) | Intended improvement |

---

## Part 2 Output

**CONTROLLER IMPACT: SAFE**

- Edge case: Confirm validation rules for `annexed_target_group` are applied in the orchestration layer before switching to `validated()`.
- Skipping empty rows is a behavior improvement, not a regression.

---

# Part 3 — Relationship & Export Layer Impact

## 3.1 Current relationship

| Item | Status |
|------|--------|
| `Project::cciAnnexedTargetGroup()` | `hasOne(ProjectCCIAnnexedTargetGroup)` |
| Controller data source | `where('project_id')->get()` — does not use relation |
| Show/edit views | Receive data from controller, not relation |
| Hydrator | Uses `cciAnnexedTargetGroupController->show()` |

---

## 3.2 Proposed change: hasOne → hasMany

| Question | Finding |
|----------|---------|
| Will hasMany break existing views? | **No** — views receive collection from controller; they already expect multiple rows |
| Does any code rely on hasOne behavior? | **No** — CCI annexed target group data flow bypasses the relation |

**Conclusion:** Changing to `hasMany` is safe for views and current data flow.

---

## 3.3 Export layer

| Export Path | Relation Used | CCI Correct? |
|-------------|---------------|--------------|
| HTML PDF (Blade) | `$annexedTargetGroup` from ProjectDataHydrator (controller `show()`) | Yes |
| DOC (PhpWord) | `$project->annexed_target_groups` | **No** — EduRUT relation; CCI DOC export shows empty/wrong data |

**Conclusion:** DOC export bug is pre-existing. Delete-recreate does not worsen it. Fix requires coordinated patch in ExportController.

---

## Part 3 Output

**RELATIONSHIP IMPACT: NEEDS COORDINATED PATCH**

- Changing `cciAnnexedTargetGroup` to `hasMany` is safe.
- DOC export uses wrong relation (EduRUT). Should be fixed independently to use CCI data for CCI projects.

---

# Part 4 — Data Integrity Simulation

## Case A: 3 blank beneficiary names submitted

| Aspect | Current | After delete-recreate |
|--------|---------|------------------------|
| Result | All 3 hit `updateOrCreate(project_id, '')` → collapse into 1 row | Skip fully empty rows → 0 rows created |
| Data loss risk | High (2 rows lost, 1 orphan) | Low (none created) |

---

## Case B: 2 rows share same beneficiary name

| Aspect | Current | After delete-recreate |
|--------|---------|------------------------|
| Result | Both hit `updateOrCreate(project_id, name)` → 1 row (second overwrites first) | Both recreated → 2 rows preserved |
| Data loss risk | High | Low |

---

## Case C: User deletes middle row

| Aspect | Current | After delete-recreate |
|--------|---------|------------------------|
| Result | Form submits remaining rows by index; `updateOrCreate` by name cannot target “middle row”; wrong row may be updated | Delete-all, recreate from submitted rows; indices irrelevant |
| Data loss risk | Medium (wrong row updated) | Low |

---

## Case D: User edits only second row

| Aspect | Current | After delete-recreate |
|--------|---------|------------------------|
| Result | All rows re-submitted; `updateOrCreate` by name can mis-target if names change or duplicate | Delete-all, recreate from submitted array; no row-identity dependency |
| Data loss risk | Medium | Low |

---

## Case E: Rapid consecutive edits

| Aspect | Current | After delete-recreate |
|--------|---------|------------------------|
| Result | Each submit runs full loop; risk of partial state if interrupted | Each submit: delete-all then create; transaction ensures atomicity |
| Data loss risk | Medium | Low |

---

## Part 4 Summary

Delete-recreate improves data integrity in all five scenarios. No regression in data preservation.

---

# Part 5 — Regression Scope Confirmation

| Area | Required? | Notes |
|------|-----------|-------|
| Schema change | No | Table structure unchanged |
| Validation change | Optional | Add `validated()` if orchestration ensures rules |
| View modification | No | Views iterate collections; no row IDs |
| JS modification | No | Input names unchanged |
| Model change | No | Boot logic preserved |
| Relationship change | Optional | `hasOne` → `hasMany` for correctness |
| ExportController | Separate | DOC export fix; not part of delete-recreate |

---

## Overlooked dependencies

| Dependency | Status |
|------------|--------|
| `UpdateProjectRequest` / orchestration validation merge | Verify `annexed_target_group` rules are applied before using `validated()` |
| DOC export relation | Separate fix; document as follow-up |

---

# Final Output

## CCI ANNEXED TARGET GROUP IMPLEMENTATION PLAN AUDIT REPORT

| Criterion | Result |
|-----------|--------|
| Architectural alignment | **SAFE** |
| Controller refactor safety | **SAFE** |
| Relationship/export risk | **NEEDS COORDINATED PATCH** |
| Data integrity improvement | Positive — eliminates overwrite and mis-targeting |
| Regression scope | Low — controller-only; no schema/view/JS changes |

---

## Final verdict: APPROVE

---

## Required adjustments before approval

1. **Validation flow:** Confirm the request used in `ProjectController@update` for CCI includes `annexed_target_group` rules (e.g. via `UpdateCCIAnnexedTargetGroupRequest` or merged rules). If not, document how to wire validation before switching to `validated()`.
2. **DOC export fix:** Plan a separate change in ExportController so CCI DOC export uses CCI annexed target group data instead of `$project->annexed_target_groups` (EduRUT). Document as follow-up; not a blocker for delete-recreate.
3. **Relationship correction:** Treat changing `cciAnnexedTargetGroup` from `hasOne` to `hasMany` as an optional follow-up for correctness.

---

*Document generated from audit performed March 3, 2026.*
