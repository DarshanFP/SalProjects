# Phase 2 — Wave 2 Guard Protection: RevenueGoalsController

**Date:** 2026-02-15  
**Phase:** Phase 2 — Wave 2  
**Target:** `app/Http/Controllers/Projects/ILP/RevenueGoalsController.php`  
**Risk Level:** HIGH → now guarded

---

## 1. Controller Name

`App\Http\Controllers\Projects\ILP\RevenueGoalsController`

---

## 2. Risk Level

**HIGH** → Now guarded. Three-table delete-recreate in `update()` without section-absent guard caused data loss when section omitted or empty.

---

## 3. Original Mutation Pattern

**3-table Delete-Recreate** in `update()` only:

- `ProjectILPRevenuePlanItem::where('project_id', $projectId)->delete()` (line 178)
- `ProjectILPRevenueIncome::where('project_id', $projectId)->delete()` (line 179)
- `ProjectILPRevenueExpense::where('project_id', $projectId)->delete()` (line 180)
- Create rows in 3 foreach loops
- Wrapped in `DB::beginTransaction()` / commit / rollback

`store()` is append-only (no delete); unchanged.

---

## 4. Guard Method Code

```php
private function isILPRevenueGoalsMeaningfullyFilled(
    array $businessPlanItems,
    array $annualIncome,
    array $annualExpenses
): bool {
    // Check business plan items
    foreach ($businessPlanItems as $item) {
        if (is_array($item)) {
            foreach ($item as $value) {
                if (trim((string) $value) !== '') {
                    return true;
                }
            }
        }
    }

    // Check annual income
    foreach ($annualIncome as $income) {
        if (is_array($income)) {
            foreach ($income as $value) {
                if (trim((string) $value) !== '') {
                    return true;
                }
            }
        }
    }

    // Check annual expenses
    foreach ($annualExpenses as $expense) {
        if (is_array($expense)) {
            foreach ($expense as $value) {
                if (trim((string) $value) !== '') {
                    return true;
                }
            }
        }
    }

    return false;
}
```

---

## 5. Early Return Insertion Location

**Inserted** in `update()` only, immediately after normalization block, **before** `DB::beginTransaction()`:

```php
if (! $this->isILPRevenueGoalsMeaningfullyFilled($businessPlanItems, $annualIncome, $annualExpenses)) {
    Log::info('RevenueGoalsController@update - Section absent or empty; skipping mutation', [
        'project_id' => $projectId,
    ]);

    return response()->json([
        'message' => 'Revenue Goals updated successfully.',
    ], 200);
}
```

---

## 6. Behavior Matrix

| Scenario | Result |
|----------|--------|
| Section absent | **Skip** — Return update success message |
| Section present but empty | **Skip** |
| Section present with any meaningful data | **Full Replace** — Delete from 3 tables, create new rows |

---

## 7. Confirmation

- **store() untouched** — Append-only; no modifications
- **update() response contract preserved** — Success path returns message JSON; early return returns same message
- **No schema change** — No migrations or model changes
- **All 3 delete blocks protected** — Guard runs before transaction; when guard skips, no delete executes

---

## 8. Manual Verification Checklist

- [ ] Update General Info only → preserved
- [ ] Submit revenue data → replaced
- [ ] Submit empty revenue section → preserved
- [ ] No partial table wipe
- [ ] No transaction errors

---

*End of Phase 2 Wave 2 Revenue Goals Implementation.*
