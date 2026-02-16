# Phase 2 — Wave 4: EduRUTAnnexedTargetGroupController — Architectural Analysis

**Date:** 2026-02-15  
**Type:** STRICT READ-ONLY — Analysis Only (No Code Changes)  
**Target:** `app/Http/Controllers/Projects/EduRUTAnnexedTargetGroupController.php`

---

## 1. Controller Path

`app/Http/Controllers/Projects/EduRUTAnnexedTargetGroupController.php`

---

## 2. Mutation Pattern Summary

| Method | Pattern | Delete? | Delegate to store? |
|--------|---------|---------|--------------------|
| `store()` | Append-only (no delete) | **No** | N/A |
| `update()` | Delete-Recreate | **Yes** | **No** — own logic |
| `destroy()` | Single-row delete by id | Yes (different scope) | N/A |

**Primary risk:** `update()` — delete-recreate with no section-absent guard.

---

## 3. Step 1 — Update Entry Path

- **update()** (lines 94–134) does **not** delegate to `store()`.
- It contains its own delete-recreate logic.
- **Entry flow:** `ProjectController@update` (line 1413) calls  
  `$this->eduRUTAnnexedTargetGroupController->update($request, $project->project_id)`  
  and passes the full `$request` from the project update form.

---

## 4. Step 2 — Delete Operations

### Delete Block (update method)

| Location | Line | Model | Scope | Wrapped in transaction? | Unconditional? |
|----------|------|-------|-------|-------------------------|----------------|
| `update()` | **107** | `ProjectEduRUTAnnexedTargetGroup` | `where('project_id', $projectId)->delete()` | Yes (lines 104–105) | **Yes** |

**Details:**
- **Line 107:** `ProjectEduRUTAnnexedTargetGroup::where('project_id', $projectId)->delete()`
- Runs **inside** `DB::beginTransaction()` (line 104) and `try` (line 105)
- **Unconditional:** No check for section presence or meaningful data before delete
- Single table: `project_edu_rut_annexed_target_groups`

### Other delete (out of scope for guard)

| Location | Line | Model | Scope |
|----------|------|-------|-------|
| `destroy()` | 144 | `ProjectEduRUTAnnexedTargetGroup` | `findOrFail($id)->delete()` (single row by id) |

---

## 5. Step 3 — Create/Recreate Pattern

### Request keys

- `annexed_target_group` — main array (from `$request->only(['annexed_target_group'])`)

### Normalization (lines 99–103)

```php
$groups = is_array($data['annexed_target_group'] ?? null)
    ? ($data['annexed_target_group'] ?? [])
    : (isset($data['annexed_target_group']) && $data['annexed_target_group'] !== '' ? [$data['annexed_target_group']] : []);
```

- **Scalar-to-array:** If `annexed_target_group` is a non-array scalar (and not empty string), it is wrapped in `[...]`.
- **Empty/absent:** If absent, null, or empty string → `$groups = []`.

### One row = one element of `$groups`

Each `$group` is an associative array with:

| Field | Request key | Normalization in loop |
|-------|-------------|------------------------|
| beneficiary_name | `$group['beneficiary_name']` | If array → `reset($group['beneficiary_name'])`; else use value |
| family_background | `$group['family_background']` | Same |
| need_of_support | `$group['need_of_support']` | Same |

### Create loop condition (lines 110–125)

```php
foreach ($groups as $group) {
    if (!is_array($group)) {
        continue;
    }
    // ... extract beneficiary_name, family_background, need_of_support ...
    ProjectEduRUTAnnexedTargetGroup::create([...]);
}
```

- **Condition:** Only `is_array($group)` — non-arrays are skipped.
- **No required fields:** Every array `$group` leads to a `create()`, even if all three fields are null/empty.

---

## 6. Step 4 — Response Contract

| Method | Success | Failure |
|--------|---------|---------|
| `update()` | `response()->json(['message' => 'Annexed target group data updated successfully.'], 200)` | `response()->json(['error' => 'Failed to update annexed target group data.'], 500)` |

- **Type:** JSON
- **No branching:** Same success message for all successful paths.

---

## 7. Step 5 — Risk Classification

| Question | Answer |
|----------|--------|
| 1. If section keys are missing from request, will delete still execute? | **YES** — delete runs before any payload check; `$groups` may be `[]` |
| 2. If arrays are empty, will delete execute? | **YES** — delete is unconditional |
| 3. Is there any existing guard? | **NO** |
| 4. Does update() always run delete when called? | **YES** — delete always runs when `update()` is entered |

**Result:** HIGH risk — when section is omitted or empty, delete wipes existing rows and no rows are recreated → data loss.

---

## 8. Step 6 — Guard Design Requirements

### Normalized inputs for guard

Use the same normalization as the controller:

```php
$groups = is_array($data['annexed_target_group'] ?? null)
    ? ($data['annexed_target_group'] ?? [])
    : (isset($data['annexed_target_group']) && $data['annexed_target_group'] !== '' ? [$data['annexed_target_group']] : []);
```

Guard input: normalized `$groups` (array of group items).

### Proposed guard method signature

```php
private function isEduRUTAnnexedTargetGroupMeaningfullyFilled(array $groups): bool
```

### Meaningful-fill criteria

At least one element in `$groups` is meaningfully filled. A group is meaningfully filled if:

- It is an array (same as create loop), and
- At least one of these is non-empty:
  - `beneficiary_name`
  - `family_background`
  - `need_of_support`

Use the same field extraction as the create loop (including scalar vs array handling) and require at least one non-empty string after trim.

### Early return insertion point

- **Location:** Inside `update()`, **after** computing `$groups` (after line 103), **before** `DB::beginTransaction()` (line 104).
- **Logic:** If `!isEduRUTAnnexedTargetGroupMeaningfullyFilled($groups)`, log and return  
  `response()->json(['message' => 'Annexed target group data updated successfully.'], 200)` without entering the transaction or delete.

---

## 9. Exact Insertion Point

```
update() method:
  Line 97-103: $fillable, $data, $groups normalization
  Line 104: DB::beginTransaction()
  Line 105: try {
  Line 107: ProjectEduRUTAnnexedTargetGroup::where(...)->delete()

INSERT GUARD: After line 103, before line 104.
```

---

## 10. Final Conclusion

- **Pattern:** Delete-recreate in `update()` only; `store()` is append-only.
- **Delete:** Unconditional at line 107, scoped by `project_id`, inside transaction.
- **Risk:** Section absent or empty causes full wipe and no recreate.
- **Guard:** Check normalized `$groups` for at least one meaningfully filled group; if none, skip transaction and delete, return same JSON success message.
- **Store():** No guard needed; it does not delete existing data.

---

*End of Phase 2 Wave 4 EduRUT Annexed Target Group Analysis.*
