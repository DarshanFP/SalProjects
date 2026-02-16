# Phase 2 — Wave 2 Guard Protection: RiskAnalysisController

**Date:** 2026-02-15  
**Phase:** Phase 2 — Wave 2  
**Target:** `app/Http/Controllers/Projects/ILP/RiskAnalysisController.php`  
**Risk Level:** HIGH → now guarded

---

## 1. Controller Name

`App\Http\Controllers\Projects\ILP\RiskAnalysisController`

---

## 2. Risk Level

**HIGH** → Now guarded. Single-row delete-recreate without section-absent guard caused data loss when section omitted or empty.

---

## 3. Original Mutation Pattern

**Delete-Recreate** (single row) in `store()`:

- `ProjectILPRiskAnalysis::where('project_id', $projectId)->delete()` (line 29 → now ~38)
- Create single row via `fill($data)` and `save()`
- Wrapped in `DB::beginTransaction()` / commit / rollback

`update()` delegates to `store()` and wraps 200 response with `data`.

---

## 4. Guard Method Code

```php
private function isILPRiskAnalysisMeaningfullyFilled(array $data): bool
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
if (! $this->isILPRiskAnalysisMeaningfullyFilled($data)) {
    Log::info('RiskAnalysisController@store - Section absent or empty; skipping mutation', [
        'project_id' => $projectId,
    ]);

    return response()->json([
        'message' => 'Risk analysis saved successfully.',
    ], 200);
}
```

---

## 6. Behavior Matrix

| Scenario | Result |
|----------|--------|
| Section absent | **Skip** — Return success message |
| Section present but empty | **Skip** |
| Section present with data | **Full Replace** — Delete existing, create new row |

---

## 7. Confirmation

- **No response contract change** — Success path returns message JSON; early return returns same message
- **update() untouched** — Still delegates to store(); wrapping behavior continues (200 → wrap with data)
- **No schema change** — No migrations or model changes

---

## 8. Manual Verification Checklist

- [ ] Update General Info only → preserved
- [ ] Submit risk analysis → replaced
- [ ] Submit empty risk section → preserved
- [ ] No transaction errors

---

*End of Phase 2 Wave 2 Risk Analysis Implementation.*
