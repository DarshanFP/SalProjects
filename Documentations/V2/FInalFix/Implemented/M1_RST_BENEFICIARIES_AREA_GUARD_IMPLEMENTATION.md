# M1 — RST BeneficiariesAreaController Skip-Empty-Sections Guard Implementation

**Date:** 2026-02-14  
**Milestone:** 1 — Data Integrity Shield  
**Scope:** `app/Http/Controllers/Projects/RST/BeneficiariesAreaController.php` only. No other controller, validation, resolver, transaction logic, or config was modified.

---

## 1. Summary of Change

A **skip-empty guard** was added to `BeneficiariesAreaController::store()` (used by both store and update) so that bulk delete and recreate run only when the Beneficiaries Area section is **meaningfully filled** per M1_GUARD_RULE_SPEC.md (multi-row rules).

- **Before:** Every call to `store()` (and thus `update()`) performed `ProjectDPRSTBeneficiariesArea::where('project_id', $projectId)->delete()` then recreated rows from `project_area` and parallel arrays. If the request had no `project_area` or empty arrays, existing rows were deleted and nothing useful was recreated, causing **data loss**.
- **After:** Before any delete, the controller checks `isBeneficiariesAreaMeaningfullyFilled(...)`. If the section is absent or empty (no meaningful row), the method **returns early** with HTTP 200 and the same success message: no delete, no create, no transaction. Existing data is unchanged. If at least one row has meaningful data, behaviour is **unchanged** (transaction, delete, create, commit).

---

## 2. Before vs After Behaviour

| Scenario | Before | After |
|----------|--------|--------|
| Full payload: `project_area` and parallel arrays have rows with data | Delete all, create from payload. | **Same.** |
| `project_area` key absent (normalized to `[]`) | Delete all, create nothing → **data loss**. | **Skip:** no delete, no create; return 200 with message. |
| `project_area` present but empty array `[]` | Delete all, create nothing → **data loss**. | **Skip:** no delete, no create; return 200. |
| Arrays have elements but every row has empty strings / nulls | Delete all, create empty rows → **data loss**. | **Skip:** no delete, no create; return 200. |
| At least one row has non-empty `project_area`, `category_beneficiary`, `direct_beneficiaries`, or `indirect_beneficiaries` (or non-null numeric) | Delete then create. | **Same.** |

---

## 3. Code Diff Snippet

**File:** `app/Http/Controllers/Projects/RST/BeneficiariesAreaController.php`

**3.1 Insertion in `store()` (after normalizing arrays, before DB::beginTransaction):**

```php
        $indirectBeneficiaries = is_array($data['indirect_beneficiaries'] ?? null) ? ... : [];

+       // M1 Data Integrity Shield: skip delete+recreate when section is absent or empty.
+       if (! $this->isBeneficiariesAreaMeaningfullyFilled($projectAreas, $categoryBeneficiaries, $directBeneficiaries, $indirectBeneficiaries)) {
+           Log::info('BeneficiariesAreaController@store - Section absent or empty; skipping mutation', [
+               'project_id' => $projectId,
+           ]);
+
+           return response()->json(['message' => 'Beneficiaries Area saved successfully.'], 200);
+       }
+
        DB::beginTransaction();
```

**3.2 New private methods (end of class):**

```php
+   /**
+    * M1 Guard: true only when project_area (section key) has at least one row with meaningful data.
+    * Meaningful row = at least one non-empty trimmed string OR at least one non-null numeric.
+    */
+   private function isBeneficiariesAreaMeaningfullyFilled(
+       array $projectAreas,
+       array $categoryBeneficiaries,
+       array $directBeneficiaries,
+       array $indirectBeneficiaries
+   ): bool {
+       if ($projectAreas === []) {
+           return false;
+       }
+
+       foreach ($projectAreas as $index => $projectArea) {
+           $projectAreaVal = ...; // same normalization as create loop
+           $categoryVal = ...;
+           $directVal = ...;
+           $indirectVal = ...;
+
+           if ($this->rowHasMeaningfulValue($projectAreaVal) || $this->rowHasMeaningfulValue($categoryVal)
+               || $this->rowHasMeaningfulValue($directVal) || $this->rowHasMeaningfulValue($indirectVal)) {
+               return true;
+           }
+       }
+
+       return false;
+   }
+
+   /** Check if a single cell is meaningful: non-empty string (trim) or non-null numeric. */
+   private function rowHasMeaningfulValue(mixed $val): bool { ... }
```

---

## 4. Manual Test Cases

1. **Full payload (unchanged behaviour)**  
   Submit store/update with `project_area`, `category_beneficiary`, `direct_beneficiaries`, `indirect_beneficiaries` containing at least one row with e.g. non-empty `project_area`.  
   **Expect:** Delete all for project, create new rows, 200 with "Beneficiaries Area saved successfully."

2. **Section key absent**  
   Submit with no `project_area` (and no other section keys so normalized `projectAreas` is `[]`).  
   **Expect:** No delete, no create. Existing rows unchanged. 200 with same message. Log: "Section absent or empty; skipping mutation".

3. **Empty array**  
   Submit with `project_area` = `[]`.  
   **Expect:** Skip; no delete, no create; 200.

4. **All rows empty**  
   Submit with arrays of same length but every value null or empty string.  
   **Expect:** Skip; no delete, no create; 200.

5. **One meaningful row**  
   Same as (4) but one cell has e.g. `project_area[0]` = "Rural" or a numeric.  
   **Expect:** Delete+recreate runs; 200.

---

## 5. Risk Assessment

| Risk | Mitigation |
|------|------------|
| Full submit incorrectly skipped | Guard returns true when any row has any meaningful value in the four fields; full payloads unchanged. |
| Normalization mismatch | Guard uses the same normalized arrays as the create loop (same logic for project_area, category_beneficiary, etc.). |
| update() path | update() delegates to store(); guard runs in store() so both paths protected. |
| Log noise | Single info log on skip; can filter by message or project_id. |

---

## 6. Confirmation: Only This File Modified

Only the following file was modified:

- **`app/Http/Controllers/Projects/RST/BeneficiariesAreaController.php`**

No changes were made to BudgetController, LogicalFrameworkController, any nested section controller, validation, resolver, transaction logic, or config.

---

*End of M1 RST Beneficiaries Area Guard Implementation.*
