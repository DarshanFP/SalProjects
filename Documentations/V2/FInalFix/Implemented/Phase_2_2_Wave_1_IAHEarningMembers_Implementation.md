# Phase 2 — Wave 1 Guard Protection: IAHEarningMembersController

**Date:** 2026-02-15  
**Phase:** Phase 2 — Wave 1  
**Target:** `app/Http/Controllers/Projects/IAH/IAHEarningMembersController.php`  
**Risk Level:** HIGH → now guarded

---

## 1. Controller Name

`App\Http\Controllers\Projects\IAH\IAHEarningMembersController`

---

## 2. Risk Level

**HIGH** → Now guarded. Multi-row delete-recreate without section-absent guard caused data loss when section omitted or empty.

---

## 3. Original Mutation Pattern

**Delete-Recreate** (multi-row) in `store()`:

- `ProjectIAHEarningMembers::where('project_id', $projectId)->delete()` (line 30 → now ~47)
- Create rows in loop from parallel arrays: `member_name`, `work_type`, `monthly_income`
- Wrapped in `DB::beginTransaction()` / commit / rollback

`update()` delegates to `store()`.

---

## 4. Guard Method Code

```php
private function isIAHEarningMembersMeaningfullyFilled(
    array $memberNames,
    array $workTypes,
    array $monthlyIncomes
): bool
{
    $rowCount = count($memberNames);

    for ($i = 0; $i < $rowCount; $i++) {
        $memberName = $memberNames[$i] ?? null;
        $workType = $workTypes[$i] ?? null;
        $monthlyIncome = $monthlyIncomes[$i] ?? null;

        if (
            !empty($memberName) &&
            !empty($workType) &&
            $monthlyIncome !== null &&
            $monthlyIncome !== ''
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
if (! $this->isIAHEarningMembersMeaningfullyFilled($memberNames, $workTypes, $monthlyIncomes)) {
    Log::info('IAHEarningMembersController@store - Section absent or empty; skipping mutation', [
        'project_id' => $projectId,
    ]);

    return response()->json([
        'message' => 'IAH earning members details saved successfully.',
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

---

## 7. Confirmation

- **No response contract change** — Success path returns message JSON; early return returns same message
- **update() untouched** — Still delegates to store(); no modifications
- **No schema change** — No migrations or model changes

---

## 8. Manual Verification Checklist

- [ ] Update General Info only → preserved
- [ ] Submit valid earning members → replaced
- [ ] Submit empty earning section → preserved
- [ ] No duplicate rows
- [ ] No transaction errors

---

*End of Phase 2 Wave 1 IAH Earning Members Implementation.*
