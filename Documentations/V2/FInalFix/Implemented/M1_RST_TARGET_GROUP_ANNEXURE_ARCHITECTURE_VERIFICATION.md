# M1 — RST Target Group Annexure Architecture Verification

**Date:** 2026-02-14  
**Mode:** Read-only verification. **NO CODE WAS MODIFIED.**  
**Target:** `app/Http/Controllers/Projects/RST/TargetGroupAnnexureController.php`

---

## 1. update() delegation

**Finding:** `update()` **delegates to `store()`**.

```php
public function update(FormRequest $request, $projectId)
{
    return $this->store($request, $projectId);
}
```

All persistence logic lives in `store()`; both store and update use the same path.

---

## 2. Delete-then-recreate pattern

**Finding:** The controller **does** use a **delete-then-recreate** pattern in `store()`.

- **Delete:** `ProjectRSTTargetGroupAnnexure::where('project_id', $projectId)->delete();` (inside the existing `DB::beginTransaction()` / try block).
- **Recreate:** A `foreach ($rstNames as $index => $rstName)` loop creates new rows via `ProjectRSTTargetGroupAnnexure::create([...])` for each index.
- Delete runs **unconditionally** (no guard) before any create. There is no “update in place” or merge; the section is fully replaced by the request payload.

---

## 3. Primary section key(s) from request

There is **no single wrapper key** (e.g. `target_group_annexure`). The section is represented by **six request keys**, each used as array-by-index:

| Request key                  | Role        |
|-----------------------------|------------|
| `rst_name`                  | Array (or normalized to array) |
| `rst_religion`              | Array       |
| `rst_caste`                 | Array       |
| `rst_education_background`  | Array       |
| `rst_family_situation`      | Array       |
| `rst_paragraph`             | Array       |

Data is taken with `$request->only($fillable)` where `$fillable` is exactly these six keys. So the **section keys** are these six; there is no parent key grouping them in the request.

---

## 4. Multi-row vs single-row

**Finding:** **Multi-row (array-based, indexed).**

- Each of the six fields is normalized to an array (e.g. `$rstNames`, `$rstReligions`, …).
- Rows are created by iterating over `$rstNames` and using the same index for the other five arrays.
- One `ProjectRSTTargetGroupAnnexure` record is created per index. The model stores flat columns per row (`project_id`, `rst_name`, `rst_religion`, etc.), not a single JSON or parent/child structure.

---

## 5. Response type

**Finding:** **JSON.**

- Success: `response()->json(['message' => 'Target Group Annexure saved successfully.'], 200)`
- Error: `response()->json(['error' => 'Failed to save Target Group Annexure.'], 500)`

No redirects; all responses from `store()` (and thus `update()`) are JSON.

---

## 6. Delete pattern summary

| Aspect | Detail |
|--------|--------|
| **Model** | `ProjectRSTTargetGroupAnnexure` |
| **Scope** | `project_id` |
| **Delete** | `ProjectRSTTargetGroupAnnexure::where('project_id', $projectId)->delete()` |
| **When** | Once per `store()` call, inside transaction, before any create |
| **Conditional?** | No — runs every time `store()` is invoked (no empty-section guard) |
| **Recreate** | One `::create([...])` per index in `$rstNames` |

---

## 7. Data-loss risk when section is absent or empty

**Risk:** **Yes.**

- If the request omits the section or sends all six keys as empty/absent:
  - The six arrays are normalized to empty (e.g. `[]`) when not arrays or when absent.
  - `foreach ($rstNames as $index => $rstName)` runs over an empty array, so **no** rows are created.
  - But **delete** has already run, so **all existing target group annexure rows for that project are removed**.
- Result: existing RST target group annexure data can be **wiped** with no new rows created. There is **no skip-empty guard** in this controller.

---

## 8. Confirmation: no code modified

This verification was **read-only**. No changes were made to:

- `app/Http/Controllers/Projects/RST/TargetGroupAnnexureController.php`
- Or any other file in the project.

---

*End of M1 RST Target Group Annexure Architecture Verification.*
