# Phase 2 — Wave 1 Guard Protection: IAHPersonalInfoController

**Date:** 2026-02-15  
**Phase:** Phase 2 — Wave 1  
**Target:** `app/Http/Controllers/Projects/IAH/IAHPersonalInfoController.php`  
**Risk Level:** HIGH → now guarded

---

## 1. Controller Name

`App\Http\Controllers\Projects\IAH\IAHPersonalInfoController`

---

## 2. Risk Level

**HIGH** → Now guarded. Single-row delete-recreate without section-absent guard caused data loss when section omitted or empty.

---

## 3. Original Mutation Pattern

**Delete-Recreate** (single row) in `store()`:

- `ProjectIAHPersonalInfo::where('project_id', $projectId)->delete()` (line 37 → now ~48)
- Create single row via `fill($data)` and `save()`
- Wrapped in `DB::beginTransaction()` / commit / rollback

`update()` delegates to `store()`.

---

## 4. Guard Method Code

```php
private function isIAHPersonalInfoMeaningfullyFilled(array $data): bool
{
    foreach ($data as $value) {
        if (is_array($value)) {
            foreach ($value as $v) {
                if (trim((string) $v) !== '') {
                    return true;
                }
            }
        } else {
            if (trim((string) $value) !== '') {
                return true;
            }
        }
    }

    return false;
}
```

---

## 5. Early Return Insertion Location

**Inserted** immediately after `$data = FormDataExtractor::forFillable(...)`, **before** `DB::beginTransaction()`:

```php
if (! $this->isIAHPersonalInfoMeaningfullyFilled($data)) {
    Log::info('IAHPersonalInfoController@store - Section absent or empty; skipping mutation', [
        'project_id' => $projectId,
    ]);

    $existing = ProjectIAHPersonalInfo::where('project_id', $projectId)->first();

    return response()->json($existing, 200);
}
```

---

## 6. Behavior Matrix

| Scenario | Result |
|----------|--------|
| Section absent | **Skip** — Return existing model JSON |
| Section present but empty | **Skip** — Preserve existing |
| Section present with data | **Full Replace** — Delete existing, create new row |

---

## 7. Confirmation

- **No response contract change** — Success path returns model JSON; early return returns existing model JSON
- **update() untouched** — Still delegates to store(); no modifications
- **No schema change** — No migrations or model changes

---

## 8. Manual Verification Checklist

- [ ] Update General Info only → preserved
- [ ] Submit personal info → replaced
- [ ] Submit empty personal info section → preserved
- [ ] No transaction errors

---

*End of Phase 2 Wave 1 IAH Personal Info Implementation.*
