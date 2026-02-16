# M1 — LDP Target Group Architecture Verification

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target:** `app/Http/Controllers/Projects/LDP/TargetGroupController.php`  
**Mode:** Read-only architecture verification. No code was modified.

---

## PHASE 1 — STRUCTURE ANALYSIS

### 1. update() delegation

**Confirmed:** `update()` delegates to `store()`.

```php
public function update(FormRequest $request, $projectId)
{
    // Validation and authorization already done by FormRequest
    // Reuse store logic but with FormRequest
    return $this->store($request, $projectId);
}
```

### 2. store() delete-then-recreate and bulk delete

**Confirmed:** `store()` uses a **delete-then-recreate** pattern with **bulk delete scoped by project_id**.

- **Delete:** `ProjectLDPTargetGroup::where('project_id', $projectId)->delete();` (inside the existing `DB::beginTransaction()` / try block).
- **Recreate:** `foreach ($beneficiaryNames as $index => $name)` creates new rows via `ProjectLDPTargetGroup::create([...])` for each index, **only when** at least one of the four cell values is non-null (`if (!is_null($nameVal) || !is_null($familyVal) || ...`).
- Delete runs **unconditionally** (no skip-empty guard) before the loop. Rows with all four values null are skipped for create but delete has already run.

### 3. Model and multi-row vs nested

| Aspect | Detail |
|--------|--------|
| **Model used** | `ProjectLDPTargetGroup` only. |
| **Structure** | **Multi-row:** one row per index; no parent/child, single flat table. |

### 4. Section keys (request array names)

There is **no single wrapper key**. The section is represented by **four request keys**, each normalized to an array (indexed by row):

- `L_beneficiary_name`
- `L_family_situation`
- `L_nature_of_livelihood`
- `L_amount_requested`

Data is taken with `$request->only($fillable)` where `$fillable = ['L_beneficiary_name', 'L_family_situation', 'L_nature_of_livelihood', 'L_amount_requested']`.

### 5. Normalization

**Confirmed.** Normalization is applied:

- **Array casting:** Each of the four keys is normalized to an array with the same pattern: `is_array($data['key'] ?? null) ? ($data['key'] ?? []) : (isset($data['key']) && $data['key'] !== '' ? [$data['key']] : [])`.
- **Scalar extraction in loop:** For each index, values are extracted with `is_array($var ?? null) ? (reset($var) ?? null) : ($var ?? null)` (and same for the other three arrays at `[$index]`).

### 6. Response type

**Redirect (not JSON).**

- Success: `return redirect()->route('projects.edit', $projectId)->with('success', 'Target Group saved successfully.');`
- Error: `return redirect()->back()->with('error', 'Failed to save Target Group.');`

No JSON responses in `store()` or in the catch block. So response type is **redirect with flash**, not JSON.

### 7. DB::transaction

**Confirmed.** `store()` uses `DB::beginTransaction()`, `DB::commit()` in try, and `DB::rollBack()` in catch. `destroy()` also uses beginTransaction / commit / rollBack.

### 8. destroy()

**Exists.** It runs `DB::beginTransaction()`, `ProjectLDPTargetGroup::where('project_id', $projectId)->delete()`, `DB::commit()`, and returns `redirect()->route('projects.edit', $projectId)->with('success', 'Target Group deleted successfully.')` (or redirect back with error on exception). No conditional check on existence before delete.

---

## PHASE 2 — DATA LOSS RISK ANALYSIS

1. **If the primary section key is absent, does delete still run?**  
   **Yes.** The controller uses four keys. If all are absent, `$request->only($fillable)` yields nulls, normalization sets each to `[]`, and the code still enters the transaction and runs delete. The `foreach ($beneficiaryNames as ...)` runs over an empty array, so zero rows are created. **Delete still runs** → existing rows are removed.

2. **If arrays are empty ([]), does delete still run?**  
   **Yes.** Same as above: transaction starts, delete runs, foreach creates no rows. **Delete still runs** → data loss.

3. **If all rows are empty strings/nulls, does delete still run?**  
   **Yes.** Delete runs unconditionally. The create loop skips a row only when all four values are null (`if (!is_null($nameVal) || ...`). So if every row has all nulls, no rows are created, but **delete has already run** → all existing data is wiped.

4. **Would current behaviour wipe existing DB data in those cases?**  
   **Yes.** In all of the above cases, `ProjectLDPTargetGroup::where('project_id', $projectId)->delete()` executes and removes all existing target group rows for the project. If the request then supplies no or only all-null rows, the result is wiped data and no new rows (or only rows that pass the per-row null check). So **data loss** occurs when the section is absent, empty, or all nulls.

5. **Is there currently ANY skip-empty guard present?**  
   **No.** There is no check for “section absent or empty” before the transaction or before delete. The controller always proceeds to transaction → delete → loop. The only conditional is **inside** the loop (skip create when all four values are null), which does not prevent delete from running.

---

## PHASE 3 — PATTERN CLASSIFICATION

**A) Multi-row (parallel indexed arrays)**

**Reasoning:**

- The section is represented by four parallel arrays keyed by the same request names. The controller normalizes each to an array and iterates by index over the primary array (`$beneficiaryNames`), using the same index for the other three. One model row is created per index (when at least one value is non-null). There is no parent/child relationship, no single-row form, and no use of `updateOrCreate`. The flow is: normalize → transaction → bulk delete by `project_id` → foreach create by index. This matches the **multi-row parallel indexed arrays** pattern used in RST TargetGroupAnnexureController and RST GeographicalAreaController.

---

## PHASE 4 — COMPARISON

### Compared to: RST TargetGroupAnnexureController

| Aspect | LDP TargetGroupController | RST TargetGroupAnnexureController |
|--------|---------------------------|-----------------------------------|
| update() delegates to store() | Yes | Yes |
| Delete-then-recreate | Yes | Yes |
| Bulk delete by project_id | Yes | Yes |
| Multi-row (parallel arrays) | Yes (4 arrays) | Yes (6 arrays) |
| Normalization (array cast + reset) | Yes | Yes |
| **Response** | **Redirect + flash** | **JSON** |
| DB::transaction in store() | Yes | Yes |
| destroy() exists | Yes | Yes |
| Skip-empty guard | No | Yes |

### Compared to: RST GeographicalAreaController

| Aspect | LDP TargetGroupController | RST GeographicalAreaController |
|--------|---------------------------|---------------------------------|
| update() delegates to store() | Yes | Yes |
| Delete-then-recreate | Yes | Yes |
| Bulk delete by project_id | Yes | Yes |
| Multi-row (parallel arrays) | Yes (4 arrays) | Yes (4 arrays) |
| Normalization (array cast + reset) | Yes | Yes |
| **Response** | **Redirect + flash** | **JSON** |
| DB::transaction in store() | Yes | Yes |
| Skip-empty guard | No | Yes |

### Summary

- **Structure similar:** Same flow (normalize arrays → transaction → delete by project_id → foreach create by index). Same pattern: multi-row, parallel indexed arrays, one model, no parent/child.
- **Same multi-row guard pattern applies:** Build normalized rows (or pass the four arrays), then a helper that returns true only when at least one row has at least one meaningful value (non-empty string or numeric). If false, skip transaction/delete/create and return the **same** success outcome the controller currently uses.
- **Structural difference that affects guard design:** **Response type.** RST controllers return JSON on success and on skip (e.g. `response()->json(['message' => '...'], 200)`). LDP TargetGroupController returns a **redirect** on success: `redirect()->route('projects.edit', $projectId)->with('success', 'Target Group saved successfully.')`. So when adding a skip-empty guard, the early return must use this **redirect with success message**, not JSON, so that behaviour (and any front-end expectations) remain consistent. The guard **logic** (when to skip) is the same; only the **return value** on skip must match LDP’s existing success response (redirect), not the RST JSON response.

---

## PHASE 5 — VERDICT

**B) STRUCTURALLY SIMILAR BUT WITH DIFFERENCES — Guard possible with minor adjustments.**

**Reasoning:**

- The controller is **structurally the same** as the RST multi-row controllers: parallel indexed arrays, delete-then-recreate, bulk delete by `project_id`, transaction, same normalization style, update() delegating to store(). It is multi-row (parallel arrays), not nested, not single-row, not updateOrCreate. So it **does** require a skip-empty guard to avoid data loss when the section is absent, empty, or all nulls.
- The **guard pattern** (detect “at least one row with at least one meaningful value” before running transaction/delete/create) applies directly. The same multi-row guard approach used in RST (e.g. build rows, call `isXMeaningfullyFilled`, use `meaningfulString` / `meaningfulNumeric`) can be used here with the same logic.
- The **only important difference** is the **success response**: LDP uses **redirect** with flash, RST uses **JSON**. So the guard implementation must return the **existing** success response when skipping: `redirect()->route('projects.edit', $projectId)->with('success', 'Target Group saved successfully.')`, not a JSON response. That is a **minor adjustment** in the early-return only; no change to guard logic or to the rest of the controller flow.

Hence: structurally similar to RST multi-row controllers; guard is applicable with the sole adjustment that the skip path must return the same redirect (and flash) as the normal success path.

---

## CONFIRMATION

**NO CODE WAS MODIFIED. THIS WAS A READ-ONLY ARCHITECTURE VERIFICATION.**

No changes were made to `TargetGroupController` or any other file. This document is verification-only.

---

*End of M1 LDP Target Group Architecture Verification.*
