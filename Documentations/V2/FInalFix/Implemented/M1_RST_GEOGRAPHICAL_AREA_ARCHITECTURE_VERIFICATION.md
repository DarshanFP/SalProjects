# M1 — RST Geographical Area Architecture Verification

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target:** `app/Http/Controllers/Projects/RST/GeographicalAreaController.php`  
**Mode:** Read-only architecture verification. No code was modified.

---

## PHASE 1 — STRUCTURE ANALYSIS

### 1. update() delegation

**Confirmed:** `update()` delegates to `store()`.

```php
public function update(FormRequest $request, $projectId)
{
    return $this->store($request, $projectId);
}
```

### 2. store() delete-then-recreate and bulk delete

**Confirmed:** `store()` uses a **delete-then-recreate** pattern with **bulk delete scoped by project_id**.

- **Delete:** `ProjectRSTGeographicalArea::where('project_id', $projectId)->delete();` (inside the existing `DB::beginTransaction()` / try block).
- **Recreate:** `foreach ($mandals as $index => $mandal)` creates new rows via `ProjectRSTGeographicalArea::create([...])` for each index.
- Delete runs **unconditionally** (no guard) before any create.

### 3. Parent/child model and multi-row vs nested

| Aspect | Detail |
|--------|--------|
| **Parent model** | None (flat table). |
| **Child model** | None. |
| **Model used** | `ProjectRSTGeographicalArea` only. |
| **Structure** | **Multi-row:** one row per index; no nested parent/child. |

### 4. Section keys (request array names)

There is **no single wrapper key**. The section is represented by **four request keys**, each normalized to an array (indexed by row):

- `mandal`
- `village`
- `town`
- `no_of_beneficiaries`

Data is taken with `$request->only($fillable)` where `$fillable = ['mandal', 'village', 'town', 'no_of_beneficiaries']`.

### 5. Normalization

**Confirmed.** Normalization is applied:

- **Array casting:** Each of the four keys is normalized to an array: `is_array($data['key'] ?? null) ? ($data['key'] ?? []) : (isset($data['key']) && $data['key'] !== '' ? [$data['key']] : [])`.
- **Scalar extraction in loop:** For each index, values are extracted with `is_array($var ?? null) ? (reset($var) ?? null) : ($var ?? null)` (and same for `$villages[$index]`, `$towns[$index]`, `$noOfBeneficiaries[$index]`).

### 6. Response type

**JSON only.**

- Success: `response()->json(['message' => 'Geographical Areas saved successfully.'], 200)`
- Error: `response()->json(['error' => 'Failed to save Geographical Areas.'], 500)`

No redirects.

### 7. DB::transaction

**Confirmed.** `store()` uses `DB::beginTransaction()`, `DB::commit()` in try, and `DB::rollBack()` in catch. `destroy()` also uses beginTransaction / commit / rollBack.

### 8. destroy()

**Exists.** It runs `DB::beginTransaction()`, `ProjectRSTGeographicalArea::where('project_id', $projectId)->delete()`, `DB::commit()`, and returns `response()->json(['message' => 'Geographical Areas deleted successfully.'], 200)` (or 500 on exception). No conditional check on existence before delete.

---

## PHASE 2 — DATA LOSS RISK ANALYSIS

1. **If the primary section key is absent, does delete still run?**  
   **Yes.** There is no single “section key”; the controller uses four keys. If all four are absent, `$request->only($fillable)` yields nulls, the normalization sets each to `[]`, and the code still enters the transaction and runs delete. The `foreach ($mandals as ...)` runs over an empty array, so zero rows are created. **Delete still runs** → existing rows are removed.

2. **If arrays are empty ([]), does delete still run?**  
   **Yes.** Empty arrays lead to the same path: transaction starts, delete runs, foreach creates no rows. **Delete still runs** → data loss.

3. **If all rows are empty strings/nulls, does delete still run?**  
   **Yes.** The controller does not check whether any cell has a meaningful value. It deletes, then iterates and creates rows (including rows with null/empty values). So even if every value is null or empty string, delete runs and then rows are recreated with those empty values. **Delete still runs** → existing meaningful data can be replaced by empty rows.

4. **Would current behaviour wipe existing DB data in those cases?**  
   **Yes.** In all of the above cases, `ProjectRSTGeographicalArea::where('project_id', $projectId)->delete()` executes and removes all existing geographical area rows for the project. If the request then supplies no or only empty data, the result is wiped data and no (or only empty) new rows.

5. **Is there currently ANY skip-empty guard present?**  
   **No.** There is no check for “section absent or empty” before the transaction or before delete. The controller always proceeds to transaction → delete → recreate.

---

## PHASE 3 — ARCHITECTURAL COMPARISON

### Compared to: RST BeneficiariesAreaController

| Aspect | GeographicalAreaController | BeneficiariesAreaController |
|--------|----------------------------|-----------------------------|
| update() delegates to store() | Yes | Yes |
| Delete-then-recreate | Yes | Yes |
| Bulk delete by project_id | Yes | Yes |
| Multi-row (parallel arrays) | Yes (4 arrays) | Yes (4 arrays) |
| Normalization (array cast + reset) | Yes | Yes |
| Response | JSON 200/500 | JSON 200/500 |
| DB::transaction in store() | Yes | Yes |
| destroy() exists | Yes | Yes |
| **Skip-empty guard** | **No** | **Yes** (isBeneficiariesAreaMeaningfullyFilled) |

### Compared to: RST TargetGroupAnnexureController

| Aspect | GeographicalAreaController | TargetGroupAnnexureController |
|--------|----------------------------|------------------------------|
| update() delegates to store() | Yes | Yes |
| Delete-then-recreate | Yes | Yes |
| Bulk delete by project_id | Yes | Yes |
| Multi-row (parallel arrays) | Yes (4 arrays) | Yes (6 arrays) |
| Normalization (array cast + reset) | Yes | Yes |
| Response | JSON 200/500 | JSON 200/500 |
| DB::transaction in store() | Yes | Yes |
| destroy() exists | Yes | Yes |
| **Skip-empty guard** | **No** | **Yes** (isTargetGroupAnnexureMeaningfullyFilled) |

### Summary

- **Structurally identical:** Same flow (normalize arrays → transaction → delete by project_id → foreach create), same response format, same transaction usage. Only the number of section keys (4 vs 4 vs 6) and model/table differ.
- **Multi-row:** Yes; all three use parallel indexed arrays and one model row per index.
- **Parallel indexed arrays:** Yes; iteration is over one “primary” array (e.g. `$mandals`, `$projectAreas`, `$rstNames`) with other arrays accessed by the same index.
- **Response format:** Consistent — JSON with `message` on success and `error` on failure.
- **Guard currently missing:** Yes. GeographicalAreaController has **no** skip-empty guard; the other two RST controllers already have M1 guards.

---

## PHASE 4 — VERDICT

**A) STRUCTURALLY IDENTICAL — Safe to apply standard multi-row guard.**

**Reasoning:**

- GeographicalAreaController follows the same pattern as BeneficiariesAreaController and TargetGroupAnnexureController: request → normalize four (or six) parallel arrays → transaction → delete by `project_id` → recreate by index → JSON response.
- It is multi-row with parallel indexed arrays and the same normalization style (array cast, then scalar extraction via `reset()` in the loop).
- The only structural difference is the number of columns (4 vs 4 vs 6) and the model name. A standard multi-row guard that builds a “normalized rows” structure (one array of rows, each row the four scalar values per index) and returns true only when at least one row has at least one meaningful value (non-empty string or numeric) would fit this controller without requiring a different architecture.
- No custom parent/child or single-row logic is present; no architectural difference that would require a different guard shape.

---

## CONFIRMATION

**NO CODE WAS MODIFIED. THIS WAS A READ-ONLY ARCHITECTURE VERIFICATION.**

No changes were made to `GeographicalAreaController` or any other file. This document is verification-only.

---

*End of M1 RST Geographical Area Architecture Verification.*
