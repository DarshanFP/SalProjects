# CCI Annexed Target Group — Phase 2 Relationship Correction

**Task:** Phase 2 – Relationship Correction  
**Date:** March 3, 2026  
**Mode:** Refactor

---

## File Modified

`app/Models/OldProjects/Project.php`

---

## Code Before

```php
public function cciAnnexedTargetGroup()
{
    return $this->hasOne(ProjectCCIAnnexedTargetGroup::class, 'project_id', 'project_id');
}
```

---

## Code After

```php
public function cciAnnexedTargetGroup()
{
    return $this->hasMany(ProjectCCIAnnexedTargetGroup::class, 'project_id', 'project_id');
}
```

---

## Architectural Justification

CCI Annexed Target Group is a **multi-row** section: a project can have multiple beneficiaries. The `project_CCI_annexed_target_group` table stores one row per beneficiary, all linked by `project_id`.

- **hasOne** implies a single row per project (e.g. Age Profile, Statistics).
- **hasMany** reflects the actual structure: multiple rows per project.

The controller and views already treat this as a collection (`where('project_id')->get()`, `@foreach`). The relationship should match the domain model.

---

## Dependency Impact Check

| Consumer | Uses Relation? | Impact |
|----------|----------------|--------|
| **AnnexedTargetGroupController** | No — uses `where('project_id')->get()` | None |
| **ProjectController** | No — passes controller result to views | None |
| **ProjectDataHydrator** | No — uses `cciAnnexedTargetGroupController->show()` | None |
| **Show/Edit views** | No — receive data from controller | None |
| **ExportController (DOC)** | Uses `$project->annexed_target_groups` (EduRUT), not `cciAnnexedTargetGroup` | None |

**Conclusion:** No controller, export, or view depends on `cciAnnexedTargetGroup()`. The relation is not used for CCI annexed target group data flow. Changing to `hasMany` does not break any call site.

---

## Regression Risk

**Level: LOW**

- Method name unchanged; any future use of `$project->cciAnnexedTargetGroup` will receive a Collection instead of a single model.
- No existing code uses this relation for CCI annexed target group reads.
- DOC export uses `annexed_target_groups` (EduRUT), not `cciAnnexedTargetGroup`.

---

## Final Status

**SAFE FOR TESTING**
