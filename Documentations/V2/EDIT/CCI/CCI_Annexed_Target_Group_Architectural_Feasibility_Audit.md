# CCI Annexed Target Group — Architectural Feasibility Audit

**Date:** March 3, 2026  
**Scope:** Refactoring feasibility for `project_CCI_annexed_target_group` table and `ProjectCCIAnnexedTargetGroup` model  
**Mode:** Audit only — no code modifications

---

## 1. Codebase Scan Results

### 1.1 Table and Model References

| Location | Reference Type |
|----------|----------------|
| `database/migrations/2024_10_20_234519_create_project_c_c_i_annexed_target_groups_table.php` | Table creation |
| `app/Models/OldProjects/CCI/ProjectCCIAnnexedTargetGroup.php` | Model definition |
| `app/Http/Controllers/Projects/CCI/AnnexedTargetGroupController.php` | CRUD controller |
| `app/Models/OldProjects/Project.php` | Relationship `cciAnnexedTargetGroup()` (hasOne) |
| `app/Http/Controllers/Projects/ExportController.php` | Import only (uses for type hints; export uses wrong relation — see 2.4) |
| `app/Services/ProjectDataHydrator.php` | Hydrates data via `cciAnnexedTargetGroupController->show()` |
| `app/Console/Commands/TruncateTestData.php` | Truncation (table name) |
| `resources/views/projects/partials/Show/CCI/annexed_target_group.blade.php` | View (receives `$annexedTargetGroup`) |
| `resources/views/projects/partials/Edit/CCI/annexed_target_group.blade.php` | Edit form (receives `$targetGroup`) |
| `resources/views/projects/partials/CCI/annexed_target_group.blade.php` | Create form |
| `resources/views/projects/Oldprojects/show.blade.php` | Include |
| `resources/views/Oldprojects/edit.blade.php` | Include |
| `resources/views/projects/Oldprojects/createProjects.blade.php` | Include |
| `resources/views/projects/Oldprojects/pdf.blade.php` | Include |

### 1.2 Foreign Key and Constraints

| Item | Status |
|------|--------|
| **Foreign key to `projects`** | **None** — migration has no `foreign()` on `project_id` |
| **Unique constraint** | `CCI_target_group_id` is unique |
| **Primary key** | `id` (auto-increment) |
| **Other tables referencing this table** | **None** — no incoming FK |

### 1.3 `id` and `CCI_target_group_id` Usage Outside CRUD

| Column | Usage outside CRUD |
|--------|---------------------|
| `id` | **None** — never referenced in app code; only via Eloquent internally |
| `CCI_target_group_id` | **None** — only in model boot (auto-generation) and `_ide_helper`; never passed to forms or APIs |

**Conclusion:** No external dependency on row identity (`id` or `CCI_target_group_id`).

### 1.4 Soft Deletes, Observers, and Model Hooks

| Feature | Status |
|---------|--------|
| **SoftDeletes** | Not used |
| **Observers** | None |
| **Model boot hook** | `creating` — sets `CCI_target_group_id` via `generateCCITargetGroupId()` (sequence-based) |
| **Events** | None |

**Conclusion:** No observers or events depend on row identity. The `creating` hook only generates `CCI_target_group_id` and does not depend on it being stable across updates.

### 1.5 Reports, Exports, MIS, Dashboards, Logs

| Component | Reference | Notes |
|-----------|-----------|-------|
| **ExportController (DOC)** | `addAnnexedTargetGroupSection` | Uses `$project->annexed_target_groups` — this is **EduRUT** relation, not CCI. CCI DOC export shows empty/wrong data. |
| **PDF export** | Blade partial | Uses `$annexedTargetGroup` from ProjectDataHydrator (CCI data). |
| **Show view** | Blade partial | Uses `$annexedTargetGroup` from controller. |
| **TruncateTestData** | Table name | Truncates table; no row-level logic. |
| **Activity/audit logs** | N/A | No tables or logs reference `CCI_target_group_id` or row id. |

**Note:** DOC export uses the wrong relation (`annexed_target_groups` = EduRUT). This is a separate bug; it does not create a dependency on CCI row identity.

---

## 2. Dependency and Constraint Summary

### 2.1 Incoming Dependencies

- **None** — no other tables reference `project_CCI_annexed_target_group`.
- No reports, exports, MIS, or dashboards depend on preserving row `id` or `CCI_target_group_id` across updates.

### 2.2 Outgoing Dependencies

- **None** — table only references `project_id` (no FK defined in migration).
- All reads use `where('project_id', ...)` and iterate over rows by index.

### 2.3 Project Model Relationship

- `Project::cciAnnexedTargetGroup()` — `hasOne(ProjectCCIAnnexedTargetGroup)`
- **Incorrect:** CCI annexed target group is multi-row; relationship should be `hasMany`.
- Controller uses `where('project_id')->get()` directly; the `hasOne` relation is not used for CCI annexed target group data flow.

---

## 3. Multi-Row Project Module Patterns

| Module | Table | Update Pattern | Notes |
|--------|-------|----------------|--------|
| **EduRUT Annexed Target Group** | `project_edu_rut_annexed_target_groups` | **Delete-all, then create** | `delete()` then `create()` in loop; has FK to projects |
| **EduRUT Target Group** | `project_edu_rut_target_groups` | **Delete-all, then create** | Same pattern |
| **RST Target Group Annexure** | `project_rst_target_group_annexure` | **Delete-all, then create** | Same pattern |
| **LDP Target Group** | `project_ldp_target_groups` | **Delete-all, then create** | Same pattern |
| **IGE New Beneficiaries** | `project_ige_new_beneficiaries` | **Delete-all, then create** | Same pattern |
| **Logical Framework** | objectives/risks/results/activities | **Delete-all, then create** | Cascading deletes via FK |
| **CCI Annexed Target Group** | `project_CCI_annexed_target_group` | **Composite updateOrCreate** | Uses `(project_id, beneficiary_name)` — problematic |

**Conclusion:** Standard pattern for multi-row project sections is **delete-all-then-recreate**. CCI Annexed Target Group is the only one using composite `updateOrCreate` and it is flawed (uses non-unique `beneficiary_name` as match).

---

## 4. Safe to Use Delete-Recreate?

### Answer: **Yes**

### Rationale

1. **No row identity dependencies**
   - `id` and `CCI_target_group_id` are not used outside CRUD.
   - No FKs from other tables.
   - No logs or audit tables reference row identity.

2. **Existing precedent**
   - EduRUT Annexed Target Group, RST Target Group Annexure, LDP Target Group, IGE New Beneficiaries all use delete-recreate.

3. **Current update logic is broken**
   - `updateOrCreate` with `(project_id, beneficiary_name)` causes overwrites when multiple rows have blank `beneficiary_name`.
   - Fixing it by ID-based update would require form changes; delete-recreate does not.

4. **Model boot hook**
   - `CCI_target_group_id` is generated on `creating`. Delete-recreate will create new rows and new IDs, which is acceptable.

5. **No soft deletes**
   - Hard delete is acceptable; no soft-delete expectations.

### Caveats

1. **ExportController DOC bug**
   - `addAnnexedTargetGroupSection` uses `$project->annexed_target_groups` (EduRUT). For CCI projects this shows empty. Should be fixed to use CCI data, independent of delete-recreate.

2. **Project model relationship**
   - `cciAnnexedTargetGroup` is `hasOne`; should be `hasMany` for correctness. Not a blocker for delete-recreate.

---

## 5. Standardized Pattern Recommendation

| Pattern | Use When |
|---------|----------|
| **Delete-all-then-recreate** | Multi-row child tables per project with no external references to row identity (e.g. CCI Annexed Target Group, EduRUT, RST, LDP, IGE). |
| **Id-based update** | Row identity must be preserved (e.g. references from reports, audit tables, or external systems). |
| **updateOrCreate on unique key** | Single-row-per-project sections (e.g. Age Profile, Statistics) — match on `project_id` only. |

**Recommendation:** Adopt **delete-all-then-recreate** for CCI Annexed Target Group to match other multi-row sections and fix the beneficiary name persistence issues.

---

## 6. Summary

| Question | Answer |
|----------|--------|
| Safe to use delete-recreate? | **Yes** |
| Dependency points blocking refactor? | **None** |
| Recommended pattern | **Delete-all-then-recreate** (aligned with EduRUT, RST, LDP, IGE) |
| Additional fixes | Fix DOC export relation; consider changing `cciAnnexedTargetGroup` to `hasMany` |
