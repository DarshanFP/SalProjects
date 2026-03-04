# CCI Annexed Target Group — Phase 3 DOC Export Correction

**Task:** Phase 3 – DOC Export Correction  
**Date:** March 3, 2026  
**Mode:** Refactor

---

## File Modified

`app/Http/Controllers/Projects/ExportController.php`

---

## Code Before

```php
private function addAnnexedTargetGroupSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Section Title
    $section->addText("Annexed Target Group (CCI)", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1); // Add spacing

    // Check if annexedTargetGroup is available and not empty
    if (isset($project->annexed_target_groups) && $project->annexed_target_groups->isNotEmpty()) {
        // ... table setup ...
        foreach ($project->annexed_target_groups as $index => $group) {
            // ... row output using beneficiary_name, dob, date_of_joining, class_of_study, family_background_description ...
        }
    } else {
        $section->addText("No data available for Annexed Target Group.", ['italic' => true, 'size' => 12]);
    }
}
```

---

## Code After

```php
private function addAnnexedTargetGroupSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Section Title
    $section->addText("Annexed Target Group (CCI)", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1); // Add spacing

    $cciAnnexedRows = $project->cciAnnexedTargetGroup;

    // Check if CCI annexed target group data is available and not empty
    if ($cciAnnexedRows && $cciAnnexedRows->isNotEmpty()) {
        // ... table setup ...
        // Add Data Rows (CCI columns: beneficiary_name, dob, date_of_joining, class_of_study, family_background_description)
        foreach ($cciAnnexedRows as $index => $group) {
            // ... row output (unchanged property names) ...
        }
    } else {
        $section->addText("No data available for Annexed Target Group.", ['italic' => true, 'size' => 12]);
    }
}
```

---

## Architectural Justification

- `addAnnexedTargetGroupSection` is only called from `addCCISections`, which runs only for CCI projects (`project_type === 'CHILD CARE INSTITUTION'`).
- `$project->annexed_target_groups` is the **EduRUT** relation (`ProjectEduRUTAnnexedTargetGroup`). CCI projects do not use this table.
- CCI annexed target group data is stored in `project_CCI_annexed_target_group` and accessed via `cciAnnexedTargetGroup()`.
- CCI columns: `beneficiary_name`, `dob`, `date_of_joining`, `class_of_study`, `family_background_description`.
- EduRUT columns: `beneficiary_name`, `family_background`, `need_of_support` — different schema.
- Using the wrong relation produced empty CCI DOC exports; switching to `cciAnnexedTargetGroup` fixes this.

---

## Dependency Impact Check

| Export Path | Method | Data Source | Impact |
|-------------|--------|-------------|--------|
| CCI DOC | `addAnnexedTargetGroupSection` | Now `$project->cciAnnexedTargetGroup` | Fixed |
| EduRUT DOC | `addEduRUTAnnexedTargetGroupSection` | `$project->annexed_target_groups` | Unchanged |
| EduRUT DOC | `addEduRUTTargetGroupSection` | `$project->target_groups` | Unchanged |
| Other project types | Not applicable | N/A | Unaffected |

`addAnnexedTargetGroupSection` is only invoked from `addCCISections`; no other export flows are changed.

---

## Regression Risk

**Level: LOW**

- Only `addAnnexedTargetGroupSection` was changed.
- EduRUT and other export paths are untouched.
- `$project->cciAnnexedTargetGroup` returns a Collection (hasMany); empty case is handled via `isNotEmpty()`.
- Row structure (property names) matches CCI model; no schema or output format change.

---

## Final Status

**SAFE FOR TESTING**
