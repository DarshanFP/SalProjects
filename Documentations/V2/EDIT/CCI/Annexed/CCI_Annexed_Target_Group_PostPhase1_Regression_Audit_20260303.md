# CCI Annexed Target Group — Post-Phase 1 Regression Audit

**Task:** Post-Phase 1 Regression Audit  
**Date:** March 3, 2026  
**Mode:** Audit

---

## Files Inspected

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Projects/ProjectController.php` | Orchestration: store, update, show, edit |
| `app/Http/Controllers/Projects/CCI/AnnexedTargetGroupController.php` | Refactored store, update, show, edit, destroy |
| `app/Services/ProjectDataHydrator.php` | Hydrates CCI data for PDF |
| `app/Http/Requests/Projects/UpdateProjectRequest.php` | Update validation rules |
| `app/Models/OldProjects/CCI/ProjectCCIAnnexedTargetGroup.php` | Model, boot, fillable |
| `app/Models/OldProjects/Project.php` | Relationship `cciAnnexedTargetGroup()` |
| `app/Http/Controllers/Projects/ExportController.php` | DOC export (addAnnexedTargetGroupSection) |
| `resources/views/projects/partials/Edit/CCI/annexed_target_group.blade.php` | Edit form |
| `resources/views/projects/partials/Show/CCI/annexed_target_group.blade.php` | Show view |

---

## Controller Flow Integrity

### 1. Orchestration

| Call Site | Method | Arguments | Status |
|-----------|--------|-----------|--------|
| ProjectController@store (CCI) | `cciAnnexedTargetGroupController->store($request, $project->project_id)` | Correct | ✓ |
| ProjectController@update (CCI) | `cciAnnexedTargetGroupController->update($request, $project->project_id)` | Correct | ✓ |

**Conclusion:** `$projectId` is passed correctly in both store and update.

### 2. annexed_target_group Array

- Controller uses `$request->only(['annexed_target_group'])` via `extractValidatedRows()`.
- `UpdateProjectRequest` does **not** define `annexed_target_group` rules; validation passes for other fields.
- Form submits `annexed_target_group[index][field]`; Laravel parses this into array.
- No validation layer blocks the array — it flows through unvalidated (pre-existing).

**Conclusion:** Array is passed correctly; no blocking.

### 3. Double-Delete Scenario

| Scenario | store | update | destroy |
|----------|-------|--------|---------|
| Create flow | Called once | Not called | Not called |
| Edit flow | Not called | Called once | Not called |
| Explicit delete | Not called | Not called | Called once (separate route) |

**Conclusion:** No overlapping invocation; no double-delete risk.

### 4. destroy() Behavior

- `destroy($projectId)` uses `where('project_id', $projectId)->delete()`.
- Unchanged by Phase 1.
- Not invoked from store/update; separate user action.

**Conclusion:** destroy() unaffected.

---

## Controller Flow Integrity: **SAFE**

---

## Data Flow Integrity

### Form → Request → Controller → DB → Hydrator → View

| Stage | Source | Destination | Status |
|-------|--------|-------------|--------|
| Form | `annexed_target_group[index][field]` | Request | ✓ |
| Request | `$request->only(['annexed_target_group'])` | extractValidatedRows() | ✓ |
| Controller | delete + create loop | DB | ✓ |
| DB | `where('project_id')->get()` | show(), edit() | ✓ |
| Hydrator | `cciAnnexedTargetGroupController->show()` | `$data['annexedTargetGroup']` | ✓ |
| Edit view | `$targetGroup` from edit() | `@foreach ($targetGroup as ...)` | ✓ |
| Show view | `$annexedTargetGroup` from show() | `@foreach ($annexedTargetGroup as ...)` | ✓ |

### View Requirements

| View | Expects | Provided By | Notes |
|------|---------|-------------|-------|
| Edit | `$targetGroup` (iterable) | `edit()` → `where()->get() ?? []` | Collection or empty array |
| Show | `$annexedTargetGroup` (Collection) | `show()` → `where()->get()` | Show has isset + instanceof check |
| PDF (Blade) | `$annexedTargetGroup` | ProjectDataHydrator → controller show | Same path |

### Row Identity

- No view uses `id` or `CCI_target_group_id`.
- Edit/Show iterate by index; row identity not required.

### Unrelated Modules

- Delete is scoped to `project_CCI_annexed_target_group` by `project_id` only.
- No shared tables; no cascade to other project-type data.

---

## Data Flow Integrity: **SAFE**

---

## Edge Case Analysis

| Case | Old Behavior | New Behavior | Risk |
|------|--------------|--------------|------|
| **1. Add 5 rows** | updateOrCreate 5 times; 5 rows if all names unique | Delete all, create 5 non-empty rows | Low — same outcome |
| **2. Remove all rows and save** | updateOrCreate over empty array; no changes | Delete all, no creates; 0 rows | Low — correct |
| **3. Rapid consecutive update calls** | Each call updateOrCreate loop; last wins | Each call delete-then-recreate; last wins | Low — same |
| **4. Update without annexed_target_group in payload** | updateOrCreate loop over nothing; no change | extractValidatedRows → []; delete all, 0 creates | **Medium** — if payload omits key, all CCI annexed rows deleted. Mitigated if form always includes partial (CCI edit includes it). |
| **5. Partial submission (1 field in 1 row)** | updateOrCreate with partial row | Row not fully empty → create; other fields null | Low — acceptable |

**Note on Case 4:** CCI edit form includes `annexed_target_group` partial. On full CCI edit submit, the key is present (possibly empty array). Risk exists only if orchestration or conditional rendering omits the partial for CCI projects — not observed in current structure.

---

## Database Scope Safety

| Criterion | Status |
|-----------|--------|
| Delete scoped by `project_id` only | Yes — `where('project_id', $projectId)->delete()` |
| Global delete risk | None — always filtered |
| Cascading unintended effect | None — no FK from other tables; this table references projects only |
| Boot() ID generator on create | Yes — `creating` hook sets `CCI_target_group_id` |
| Orphaned data | None — parent is projects; child rows removed on delete |

---

## Relationship & Export Impact

| Item | Status |
|------|--------|
| `Project::cciAnnexedTargetGroup()` (hasOne) | Not used in controller flow; show/edit use `where()->get()` |
| View dependency on hasOne | None — views receive collection from controller |
| DOC export | Uses `$project->annexed_target_groups` (EduRUT relation) — pre-existing bug; CCI DOC shows empty/wrong |
| Phase 1 impact on export | None — refactor did not touch ExportController |

---

## Regression Risk Assessment

**Level: LOW**

| Reason |
|--------|
| Controller-only change; no schema, form, validation, relationship, or export changes |
| Orchestration and `$projectId` passing unchanged |
| Data flow (form → request → controller → DB → hydrator → views) intact |
| Delete scoped; no cross-project or global effect |
| destroy() unchanged |
| Single edge case (Case 4) — low likelihood if CCI form always includes partial |

---

## Final Verdict

### **SAFE FOR PHASE 2**

---

## Audit Summary

| Area | Result |
|------|--------|
| Controller orchestration | SAFE |
| Data flow | SAFE |
| Edge cases | 4 Low, 1 Medium (mitigated) |
| Database scope | SAFE |
| Relationship & export | No regression |

**No code was modified during this audit.**
