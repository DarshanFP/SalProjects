# M1 — IGE Institution Info Architecture Verification

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target file:** `app/Http/Controllers/Projects/IGE/InstitutionInfoController.php`  
**Mode:** READ-ONLY. No code modified.

---

## 1. Structure Summary

- **Class:** `App\Http\Controllers\Projects\IGE\InstitutionInfoController` (file: `InstitutionInfoController.php`).
- **store()** (lines 17–41): Uses `FormDataExtractor::forFillable($request, $fillable)` to build `$data`, then **updateOrCreate** on `ProjectIGEInstitutionInfo` keyed by `project_id`. No delete in store. Single row per project.
- **update()** (lines 80–84): Delegates to `store()` via `return $this->store($request, $projectId)`.
- **destroy()** (lines 88–105): Own transaction; `ProjectIGEInstitutionInfo::where('project_id', $projectId)->delete()`; redirect on success/error.
- **show()** / **edit()**: Read-only; fetch by `project_id` (`first()` in show, `firstOrFail()` in edit).

**Pattern:** Single parent row only. One record per project. **No** multi-row loop. **No** delete-then-recreate in `store()`; uses **updateOrCreate** only.

---

## 2. Delete Pattern

- **In store():** There is **no** delete. The controller uses:
  ```php
  ProjectIGEInstitutionInfo::updateOrCreate(
      ['project_id' => $projectId],
      $data
  );
  ```
  So it upserts a single row by `project_id`. No `Model::where('project_id', ...)->delete()` and no create loop.

- **In destroy():** Explicit delete for the project: `ProjectIGEInstitutionInfo::where('project_id', $projectId)->delete()`. This is a dedicated destroy action, not part of the store/update persistence path.

The M1 “unconditional delete then recreate (loop)” pattern **does not exist** in this controller.

---

## 3. Transaction Usage

- **store():** Uses `DB::beginTransaction()` before try; `updateOrCreate` and `DB::commit()` in try; `DB::rollBack()` in catch. Transaction wraps only the upsert and commit.
- **update():** Delegates to `store()`, so uses the same transaction as above.
- **destroy():** Uses its own `DB::beginTransaction()`, try, delete, `DB::commit()`, and `DB::rollBack()` in catch.

---

## 4. Empty-Section Behaviour

- **Section keys missing from request:** `$data` is whatever `FormDataExtractor::forFillable($request, $fillable)` returns (possibly empty or partial). `updateOrCreate` still runs: it updates the existing row with `$data` or creates one. No delete is run in store().
- **Arrays empty / all values null or blank:** Same: `updateOrCreate` runs with that `$data`. The existing row (if any) is updated to those values; no delete-then-recreate. So the risk is **overwrite with empty/null** (one row), not “delete all section rows and create zero”.
- **Does store() still run delete?** **No.** store() does not call delete(). Only destroy() deletes.

---

## 5. Data Loss Risk Level

**LOW** (in the M1 sense).

- **No** unconditional delete in the store/update path.
- **No** multi-row delete-then-recreate; single-row updateOrCreate only.
- Possible risk: an empty or blank request could overwrite the single institution row with null/empty via `updateOrCreate`. That is “overwrite with empty,” not “delete all section data and leave no rows.” So the classic M1 “empty section wipes all section rows” pattern does not apply.

---

## 6. Response Type

- **Success:** Redirect only. `redirect()->route('projects.edit', $projectId)->with('success', 'Institution Information saved successfully.')`.
- **Error:** `redirect()->back()->with('error', 'Failed to save Institution Information.')`.
- No JSON responses in this controller. No `HttpResponseException` thrown. No 403 budget-lock logic in this controller (authorization assumed via FormRequest/routing).

---

## 7. Edge Handling

- **show():** `ProjectIGEInstitutionInfo::where('project_id', $projectId)->first()`. If no record, returns `null`; logs warning. Null-safe.
- **edit():** `ProjectIGEInstitutionInfo::where('project_id', $projectId)->firstOrFail()`. If no record, `firstOrFail()` throws (e.g. `ModelNotFoundException`). Caught; handler returns `null`.
- **destroy():** `ProjectIGEInstitutionInfo::where('project_id', $projectId)->delete()`. If no rows exist, delete affects 0 rows; no exception. Commits and redirects with success.

---

## 8. Verdict

**STRUCTURALLY SAFE** (with respect to M1 delete-then-recreate).

- This controller does **not** use delete-then-recreate. It uses **updateOrCreate** for a single row per project.
- There is no unconditional delete in store() or update(), so the M1 “empty section triggers delete and wipes all section rows” scenario does not apply.
- Optional future hardening: if desired, a “skip when section is empty” guard could avoid overwriting the row with blank data when the request is intentionally empty; that would be a separate product decision, not the standard M1 guard for delete-then-recreate.

---

## 9. Confirmation

No code was modified during this verification.

---

*End of M1 IGE Institution Info Architecture Verification.*
