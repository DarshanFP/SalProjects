# Phase 2 — Wave 2 Guard Protection: ILP BudgetController

**Date:** 2026-02-15  
**Phase:** Phase 2 — Wave 2  
**Target:** `app/Http/Controllers/Projects/ILP/BudgetController.php`  
**Risk Level:** HIGH → now guarded

---

## 1. Controller Name

`App\Http\Controllers\Projects\ILP\BudgetController`

---

## 2. Risk Level

**HIGH** → Now guarded. Multi-row delete-recreate without section-absent guard caused data loss when section omitted or empty.

---

## 3. Original Mutation Pattern

**Delete-Recreate** (multi-row) in `store()`:

- `ProjectILPBudget::where('project_id', $projectId)->delete()` (line 45 → now ~58)
- Create rows in loop from `budget_desc`, `cost`, `beneficiary_contribution`, `amount_requested`
- Wrapped in `DB::beginTransaction()` / commit / rollback
- BudgetSyncGuard blocks store when project approved (403)
- BudgetSyncService runs after commit
- update() has same 403 check, then delegates to store()

---

## 4. Guard Method Code

```php
private function isILPBudgetMeaningfullyFilled(
    array $budgetDescs,
    array $costs
): bool {
    $rowCount = count($budgetDescs);

    for ($i = 0; $i < $rowCount; $i++) {
        $desc = $budgetDescs[$i] ?? null;
        $cost = $costs[$i] ?? null;

        if (
            trim((string) $desc) !== '' &&
            $cost !== null &&
            $cost !== ''
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
if (! $this->isILPBudgetMeaningfullyFilled($budgetDescs, $costs)) {
    Log::info('ILP BudgetController@store - Section absent or empty; skipping mutation', [
        'project_id' => $projectId,
    ]);

    return response()->json([
        'message' => 'Budget saved successfully.',
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
- **BudgetSyncService untouched** — Does not run when guard skips (early return before transaction)
- **update() untouched** — Still delegates to store(); no modifications
- **No schema change** — No migrations or model changes

---

## 8. Manual Verification Checklist

- [ ] Update General Info only → preserved
- [ ] Submit valid budget rows → replaced
- [ ] Submit empty budget section → preserved
- [ ] Approved project → still 403
- [ ] BudgetSyncService still runs when meaningful
- [ ] No transaction errors

---

*End of Phase 2 Wave 2 ILP Budget Implementation.*
