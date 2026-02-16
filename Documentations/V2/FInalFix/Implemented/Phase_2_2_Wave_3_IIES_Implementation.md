# Phase 2 — Wave 3 Guard Protection: IIESFamilyWorkingMembersController

**Date:** 2026-02-15  
**Phase:** Phase 2 — Wave 3  
**Target:** `app/Http/Controllers/Projects/IIES/IIESFamilyWorkingMembersController.php`  
**Risk Level:** HIGH

---

## 1. Controller Name

`App\Http\Controllers\Projects\IIES\IIESFamilyWorkingMembersController`

---

## 2. Risk Level

**HIGH** — Delete-then-recreate pattern in both `store()` and `update()` without section-absent guard. Empty arrays cause full wipe and no recreate → data loss when section omitted or sent empty.

---

## 3. Original Mutation Pattern

**Delete-Recreate** (no transaction):

- `store()`: `ProjectIIESFamilyWorkingMembers::where('project_id', $projectId)->delete()` then loop-create
- `update()`: Same pattern

---

## 4. Guard Method Added

```php
private function isIIESFamilyWorkingMembersMeaningfullyFilled(array $memberNames, array $workNatures, array $monthlyIncomes): bool
{
    for ($i = 0; $i < count($memberNames); $i++) {
        if (
            ! empty($memberNames[$i])
            && ! empty($workNatures[$i])
            && array_key_exists($i, $monthlyIncomes)
        ) {
            return true;
        }
    }

    return false;
}
```

---

## 5. Meaningful-Fill Criteria

A row is considered meaningfully filled when **all** of the following hold for at least one index `$i`:

| Field | Condition |
|-------|-----------|
| `iies_member_name[$i]` | Not empty (`!empty()`) |
| `iies_work_nature[$i]` | Not empty (`!empty()`) |
| `iies_monthly_income` | Has key `$i` (`array_key_exists($i, $monthlyIncomes)`) |

Aligned exactly with the create-loop condition. `monthly_income` value may be `0`; only key existence is checked.

---

## 6. Early-Return Insertion Point

### store()

- **Before:** `DB::beginTransaction()` — N/A (no transaction)
- **Before:** `ProjectIIESFamilyWorkingMembers::where()->delete()` — Yes
- **Inserted after:** `$validated = $validator->validated();` and array extraction/normalization
- **Inserted before:** `Project::where()->firstOrFail()` and `ProjectIIESFamilyWorkingMembers::where()->delete()`

### update()

- **Same:** After validation and array extraction, before `ProjectIIESFamilyWorkingMembers::where()->delete()`.

---

## 7. Behavior Matrix

| Scenario | Result |
|----------|--------|
| Section absent (empty arrays or non-arrays) | **Skip** — No delete, no create; return 200 with message |
| Section present but empty (no row passes create condition) | **Skip** — Records preserved |
| Section present with at least one meaningful row | **Full Replace** — Delete existing, create new rows |

---

## 8. Confirmation

- **No response contract change** — Early return uses same JSON: `response()->json(['message' => '…'], 200)`
- **No DB schema change** — No migrations or model changes
- **No cross-controller impact** — Only `IIESFamilyWorkingMembersController.php` modified

---

## 9. Normalization Logic

Before mutation:

- `$memberNames = $validated['iies_member_name'] ?? []`
- `$workNatures = $validated['iies_work_nature'] ?? []`
- `$monthlyIncomes = $validated['iies_monthly_income'] ?? []`
- Each coerced to `[]` if not array (defensive normalization)

Same arrays used in create loop; no change to existing array mapping.

---

## 10. Manual Verification Checklist

- [ ] Update General Info only → section preserved
- [ ] Submit IIES with data → records replaced
- [ ] Submit empty IIES section → records preserved
- [ ] No duplicate rows created
- [ ] No transaction errors

---

*End of Phase 2 Wave 3 IIES Family Working Members Implementation.*
