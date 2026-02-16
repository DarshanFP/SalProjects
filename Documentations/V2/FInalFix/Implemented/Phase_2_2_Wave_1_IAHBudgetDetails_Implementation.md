# Phase 2 — Wave 1 Guard Protection: IAHBudgetDetailsController

**Date:** 2026-02-15  
**Phase:** Phase 2 — Wave 1  
**Target:** `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php`  
**Risk Level:** HIGH → now guarded

---

## 1. Controller Name

`App\Http\Controllers\Projects\IAH\IAHBudgetDetailsController`

---

## 2. Risk Level

**HIGH** → Now guarded. Multi-row delete-recreate without section-absent guard caused data loss when section omitted or empty.

---

## 3. Original Mutation Pattern

**Delete-Recreate** (multi-row) in `store()`:

- `ProjectIAHBudgetDetails::where('project_id', $projectId)->delete()` (line 50 → now ~63)
- Create rows in loop from `particular`, `amount`, `family_contribution`
- Wrapped in `DB::beginTransaction()` / commit / rollback
- BudgetSyncGuard blocks store when project approved (403)
- update() has same 403 check, then delegates to store()

---

## 4. Guard Method Code

```php
private function isIAHBudgetDetailsMeaningfullyFilled(
    array $particulars,
    array $amounts
): bool {
    $rowCount = count($particulars);

    for ($i = 0; $i < $rowCount; $i++) {
        $particular = $particulars[$i] ?? null;
        $amount = $amounts[$i] ?? null;

        if (
            trim((string) $particular) !== '' &&
            $amount !== null &&
            $amount !== ''
        ) {
            return true;
        }
    }

    return false;
}
```

---

## 5. Early Return Insertion Location

**Inserted** immediately after normalization block, **before** `DB::beginTransaction()`:

```php
if (! $this->isIAHBudgetDetailsMeaningfullyFilled($particulars, $amounts)) {
    Log::info('IAHBudgetDetailsController@store - Section absent or empty; skipping mutation', [
        'project_id' => $projectId,
    ]);

    return response()->json([
        'message' => 'IAH budget details saved successfully.',
    ], 200);
}
```

---

## 6. Behavior Matrix

| Scenario | Result |
|----------|--------|
| Section absent | **Skip** — Return success message |
| Section present but empty | **Skip** |
| Section present with valid row | **Full Replace** — Delete existing, create new rows |
| Project approved | **403** — Unchanged (BudgetSyncGuard) |

---

## 7. Confirmation

- **No response contract change** — Success path returns message JSON; early return returns same message
- **BudgetSyncGuard untouched** — 403 behavior preserved
- **403 behavior preserved** — Approved project still returns 403 before guard
- **update() untouched** — Still delegates to store(); no modifications
- **No schema change** — No migrations or model changes

---

## 8. Manual Verification Checklist

- [ ] Update General Info only → preserved
- [ ] Submit valid budget rows → replaced
- [ ] Submit empty budget section → preserved
- [ ] Approved project update → still 403
- [ ] No duplicate rows
- [ ] No transaction errors

---

*End of Phase 2 Wave 1 IAH Budget Details Implementation.*
