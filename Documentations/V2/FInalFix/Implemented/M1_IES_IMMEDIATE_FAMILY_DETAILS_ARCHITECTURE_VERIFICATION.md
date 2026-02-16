# M1 — IES Immediate Family Details Architecture Verification

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target file:** `app/Http/Controllers/Projects/IES/IESImmediateFamilyDetailsController.php`  
**Mode:** READ-ONLY. No code modified.

---

## 1. Structure Type

- **Single-row section.** One record per project. No multi-row loop. No parent+child rows.
- **store()** (lines 23–56): Builds `$data` via `FormDataExtractor::forFillable($request, $fillable)`. Gets existing row or new model with `ProjectIESImmediateFamilyDetails::where('project_id', $projectId)->first() ?: new ProjectIESImmediateFamilyDetails()`. Sets `project_id`, `fill($data)`, normalizes NOT NULL boolean fields to 0 when null/empty, then `save()`. **No delete** in store().
- **update()** (lines 92–95): Delegates to store(): `return $this->store($request, $projectId);`
- **show()** / **edit()**: Read by `project_id` (`first()` in show, `firstOrFail()` in edit).
- **destroy()** (lines 98–116): Own transaction; `firstOrFail()` then `$familyDetails->delete()`; returns JSON.

---

## 2. Delete Pattern

- **In store() / update():** There is **no** delete. The controller does **not** use:
  - `Model::where('project_id', ...)->delete()`
  - `$existing->delete()`
  - `relation()->delete()`

  Persistence is: find-or-new → fill → normalize booleans → save(). So **update-or-create for a single row**, not delete-then-recreate.

- **In destroy():** Explicit user action: `$familyDetails = ProjectIESImmediateFamilyDetails::where('project_id', $projectId)->firstOrFail();` then `$familyDetails->delete();`. This is the only delete path and is not part of the normal store/update flow.

**Conclusion:** No unconditional bulk delete in the store/update path. The M1 “delete all section rows then recreate from request” pattern does **not** apply.

---

## 3. Transaction Usage

- **store():** `DB::beginTransaction()` before try; find-or-new, fill, normalize, save, `DB::commit()` in try; `DB::rollBack()` in catch. Transaction wraps only the upsert. No delete inside this transaction.
- **update():** Uses store(), so same as above.
- **destroy():** Own `DB::beginTransaction()`, try (firstOrFail, delete, commit), catch (rollBack). No early validation in store() that would prevent delete, because store() does not delete.

---

## 4. Empty-Section Behavior

**If the request has:**

- No family-detail keys  
- Empty arrays (N/A here; section is single row, data comes from FormDataExtractor)  
- All blank/null values  

**Then:**

- `$data` is whatever `FormDataExtractor::forFillable(...)` returns (possibly empty or all null).
- store() still runs: find-or-new → `fill($data)` → normalize booleans → `save()`.
- **It does not delete** existing records. It **overwrites** the single row (or creates one) with that `$data`. So existing data can be replaced with empty/null values, but there is **no** “delete all rows then create zero rows” behavior.

**Classification:** **OVERWRITE-ONLY (NO BULK DELETE)**

- No delete in store/update.
- Single row is updated or created with request data; empty request can overwrite with blank data.
- Not “DELETE-RECREATE WITHOUT EMPTY GUARD” (no bulk delete then recreate).
- Not “EMPTY GUARD PRESENT” (no guard; overwrite with empty is possible).

---

## 5. Data Loss Risk Level

**LOW** (in the M1 sense).

- No unconditional bulk delete in store/update.
- No multi-row delete-then-recreate; single-row update-or-create only.
- Possible risk: empty or blank request overwrites the single row with null/empty. That is “overwrite with empty,” not “delete all section rows and leave nothing.” So the classic M1 “empty section wipes all section rows” scenario does **not** apply.

---

## 6. Response Format

- **store() / update():** **JSON only.** Success: `response()->json(['message' => 'IES immediate family details saved successfully.'], 200)`. Error: `response()->json(['error' => 'Failed to save IES immediate family details.'], 500)`.
- **destroy():** JSON. Success: 200 with message; error: 500 with error key.
- No redirect in this controller. No `HttpResponseException` thrown. No budget lock handling in this controller. Exceptions are caught and converted to 500 JSON.

---

## 7. Edge Cases

- **show():** `ProjectIESImmediateFamilyDetails::where('project_id', $projectId)->first()`. If no record, returns `null`. No exception.
- **edit():** `firstOrFail()`. If no record, throws (e.g. `ModelNotFoundException`). Caught; handler returns `null`.
- **destroy():** Exists. Uses `firstOrFail()` then `$familyDetails->delete()`. If no record exists, `firstOrFail()` throws, catch runs rollBack and returns `response()->json(['error' => '...'], 500)`. So when no record exists, destroy() does not succeed; it returns 500.

---

## 8. Verdict

**STRUCTURALLY SAFE** (with respect to M1 delete-then-recreate).

- This controller does **not** use delete-then-recreate. It uses **find-or-new → fill → save** for a **single row** per project.
- There is no unconditional delete in store() or update(), so the M1 “empty section triggers delete and wipes all section rows” scenario does not apply.
- Optional hardening: a “skip when section is empty” guard could avoid overwriting the row with blank data when the request is intentionally empty; that would be a product decision, not the standard M1 guard for delete-then-recreate.

---

## 9. Confirmation

No code was modified during this verification.

---

*End of M1 IES Immediate Family Details Architecture Verification.*
