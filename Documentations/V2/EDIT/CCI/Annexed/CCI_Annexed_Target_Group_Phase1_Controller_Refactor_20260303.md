# CCI Annexed Target Group — Phase 1 Controller Refactor

**Task:** Phase 1 – Controller Logic Replacement  
**Date:** March 3, 2026  
**Mode:** Refactor

---

## Files Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/Projects/CCI/AnnexedTargetGroupController.php` | Replaced `updateOrCreate` with delete-all-then-recreate in `store()` and `update()`; added private helpers `extractValidatedRows()` and `isRowFullyEmpty()` |

---

## Code Before (relevant section)

### update() — composite updateOrCreate

```php
foreach ($groups as $group) {
    if (!is_array($group)) {
        continue;
    }
    Log::info('Beneficiary Entry:', $group);

    $beneficiaryName = is_array($group['beneficiary_name'] ?? null) ? (reset($group['beneficiary_name']) ?? null) : ($group['beneficiary_name'] ?? null);
    $dob = is_array($group['dob'] ?? null) ? (reset($group['dob']) ?? null) : ($group['dob'] ?? null);
    // ... similar for dateOfJoining, classOfStudy, familyBackground

    ProjectCCIAnnexedTargetGroup::updateOrCreate(
        ['project_id' => $projectId, 'beneficiary_name' => $beneficiaryName],
        [
            'dob' => $dob,
            'date_of_joining' => $dateOfJoining,
            'class_of_study' => $classOfStudy,
            'family_background_description' => $familyBackground,
        ]
    );
}
```

### store() — create in loop (no delete-first)

```php
foreach ($groups as $group) {
    // ... scalar coercion ...
    ProjectCCIAnnexedTargetGroup::create([...]);
}
```

---

## Code After (updated section)

### store() and update() — shared delete-all-then-recreate

```php
public function store(FormRequest $request, $projectId)
{
    $validatedRows = $this->extractValidatedRows($request);

    DB::beginTransaction();
    try {
        Log::info('Storing CCI Annexed Target Group', ['project_id' => $projectId]);

        ProjectCCIAnnexedTargetGroup::where('project_id', $projectId)->delete();

        foreach ($validatedRows as $row) {
            if ($this->isRowFullyEmpty($row)) {
                continue;
            }
            Log::info('Beneficiary Entry:', $row);
            ProjectCCIAnnexedTargetGroup::create([
                'project_id' => $projectId,
                'beneficiary_name' => $row['beneficiary_name'] ?? null,
                'dob' => $row['dob'] ?? null,
                'date_of_joining' => $row['date_of_joining'] ?? null,
                'class_of_study' => $row['class_of_study'] ?? null,
                'family_background_description' => $row['family_background_description'] ?? null,
            ]);
        }

        DB::commit();
        // ... redirect
    } catch (\Exception $e) {
        DB::rollBack();
        // ...
    }
}
```

### Private helpers

```php
private function extractValidatedRows(FormRequest $request): array
{
    $fillable = ['annexed_target_group'];
    $data = $request->only($fillable);
    $groups = is_array($data['annexed_target_group'] ?? null)
        ? ($data['annexed_target_group'] ?? [])
        : [...];

    $rows = [];
    foreach ($groups as $group) {
        if (!is_array($group)) continue;
        $rows[] = [
            'beneficiary_name' => /* scalar coercion */,
            'dob' => /* ... */,
            'date_of_joining' => /* ... */,
            'class_of_study' => /* ... */,
            'family_background_description' => /* ... */,
        ];
    }
    return $rows;
}

private function isRowFullyEmpty(array $row): bool
{
    return empty($row['beneficiary_name'])
        && empty($row['dob'])
        && empty($row['date_of_joining'])
        && empty($row['class_of_study'])
        && empty($row['family_background_description']);
}
```

---

## Architectural Alignment Check

| Criterion | Status |
|-----------|--------|
| **Multi-row standard compliance** | Aligns with EduRUT, RST, LDP, IGE delete-all-then-recreate pattern |
| **Dependency impact** | None — no schema, model, relationships, or export changes |
| **Row identity preservation** | Not required; no external dependency on `id` or `CCI_target_group_id` |

---

## Data Integrity Impact

| Scenario | Before (updateOrCreate) | After (delete-recreate) |
|----------|-------------------------|--------------------------|
| 3 blank rows | All match same record → 1 row (data loss) | Skipped → 0 rows (correct) |
| 2 rows same beneficiary name | Second overwrites first → 1 row | Both preserved → 2 rows |
| Delete middle row | Index mismatch; wrong row can be updated | Delete-all, recreate remaining rows |
| Edit only second row | Name-based match can mis-target | Indices irrelevant; submitted rows recreated |

**Conclusion:** Integrity is improved; overwrites and mis-targeting are removed.

---

## Regression Risk

**Level: Low**

| Reason |
|--------|
| Controller-only change; no schema, form, validation, or relationship changes |
| Same request shape and scoped input (`annexed_target_group`) |
| Transaction and redirect logic preserved |
| Model boot and mass assignment unchanged |
| Show, edit, destroy unchanged |

---

## Validation Checklist

| Test | Purpose |
|------|---------|
| **Add 3 rows** | Verify multiple rows save correctly |
| **Duplicate name test** | Verify 2 rows with same beneficiary name both persist |
| **Blank rows test** | Verify fully empty rows are skipped and do not create records |
| **Delete middle row test** | Verify removing middle row and saving leaves correct rows |

---

## Final Status

**SAFE FOR TESTING**

---

## Unchanged (per spec)

- Schema
- Migrations
- Validation rules
- Form structure
- Model boot logic
- Relationships
- Export controller
- show(), edit(), destroy()
