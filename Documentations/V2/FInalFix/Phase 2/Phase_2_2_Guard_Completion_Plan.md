# Phase 2.2 — Guard Completion Implementation Plan

**Mode:** PLANNING ONLY | **No code changes made.**

**Source:** Findings from `Phase_2_1_Unguarded_Section_Audit.md`.

---

## SECTION 1 — Remediation Objective

### Goal

Prevent any delete-recreate controller from wiping section data when its section is **not present** in the request (e.g. user updates only General Info). No controller shall execute a bulk delete for its section without first confirming that the request contains meaningful data for that section.

### Scope

- **In scope:** Only the **11 HIGH-risk controllers** identified in Phase 2.1:
  - **IAH (5):** IAHEarningMembersController, IAHBudgetDetailsController, IAHSupportDetailsController, IAHHealthConditionController, IAHPersonalInfoController
  - **ILP (4):** ILPRiskAnalysisController, ILPStrengthWeaknessController, ILPRevenueGoalsController, ILPBudgetController
  - **IIES (1):** IIESFamilyWorkingMembersController
  - **EduRUT (1):** EduRUTAnnexedTargetGroupController

- **Out of scope:** Controllers already guarded (e.g. LogicalFrameworkController, BudgetController, IESFamilyWorkingMembersController, etc.), MEDIUM-risk controllers (none identified), and reporting modules.

---

## SECTION 2 — Standard Guard Pattern

**Chosen approach: Option C — Combination.**

Use **both** of the following, in order:

1. **Presence check (where applicable):** Ensure at least one section-specific input key is present in the request (e.g. `$request->has('member_name')` or `$request->filled('particular')`), so that a completely omitted section does not trigger mutation.
2. **Meaningfully filled check:** After normalizing request data (scalar-to-array, etc.), call a private method `isSectionMeaningfullyFilled(...)` that returns true only when there is at least one row’s worth of non-empty/meaningful data (aligned with existing guarded controllers such as IESFamilyWorkingMembersController, EduRUTTargetGroupController).

**Standard pattern (pseudocode):**

```text
// At start of store() or update() path, before any DB::transaction or delete:

$data = $request->only($fillable);
// … normalize arrays (same as used later in loop) …

if (! $this->isSectionMeaningfullyFilled(...)) {
    Log::info('ControllerName@store - Section absent or empty; skipping mutation', ['project_id' => $projectId]);
    return <appropriate response>;  // e.g. response()->json([...], 200) or redirect
}

// Only then: DB::beginTransaction(); ... delete(); ... create(); ...
```

**Why Option C**

- **Option A alone:** “Meaningfully filled” can be satisfied only after normalization. If the section key is never sent, normalization may still yield empty arrays; the guard then correctly skips. Relying only on “meaningfully filled” is consistent with existing code but can be ambiguous if the form sends keys with empty values — the method must treat “key present but all values empty” as not meaningful (which existing guards do).
- **Option B alone:** A single `section_key` check is brittle: form structure may use multiple keys, or different keys per section; one missing key could allow delete when other keys are present with data. It also does not distinguish “key present but empty” from “key missing.”
- **Option C:** Use the same normalization as the rest of the method, then one `isSectionMeaningfullyFilled(...)` that encapsulates “at least one meaningful row.” That matches the codebase (LogicalFrameworkController, BudgetController, IESFamilyWorkingMembersController, etc.) and avoids duplicate logic. Where a single canonical key exists (e.g. one array for the section), an optional early `if (!$request->has('key')) return;` can reduce work; the definitive guard remains “meaningfully filled” so that empty arrays never pass.

**Standardization:** Every HIGH-risk controller will add (or reuse) a private `is*MeaningfullyFilled(...)` method and an **early return before any delete** when the method returns false. Response type (JSON vs redirect) should match the controller’s existing contract (e.g. JSON for API-style, redirect for form POST).

---

## SECTION 3 — Group Controllers by Project Type

Controllers are grouped into **waves by project type** so that each wave is deployable independently and has minimal cross-impact (no shared code with other waves beyond framework).

| Wave | Project type | Controllers | Count |
|------|--------------|-------------|-------|
| **Wave 1** | IAH (Individual - Access to Health) | IAHEarningMembersController, IAHBudgetDetailsController, IAHSupportDetailsController, IAHHealthConditionController, IAHPersonalInfoController | 5 |
| **Wave 2** | ILP (Individual - Livelihood Application) | ILPRiskAnalysisController, ILPStrengthWeaknessController, ILPRevenueGoalsController, ILPBudgetController | 4 |
| **Wave 3** | IIES (Individual - Initial Educational) | IIESFamilyWorkingMembersController | 1 |
| **Wave 4** | EduRUT (Rural-Urban-Tribal) | EduRUTAnnexedTargetGroupController | 1 |

- **Independence:** Each wave touches only controllers for one project type; no shared guard helper across waves (unless we later extract a trait). Deploy Wave 3 without touching IAH/ILP/EduRUT.
- **Minimal cross-impact:** No change to ProjectController switch order or request shape; only internal guard logic inside each controller.

---

## SECTION 4 — Implementation Steps Per Wave

For **each wave**, the following steps apply. Each wave will have its **own implementation document** (e.g. `Phase_2_2_Wave_1_IAH_Implementation.md`) created at implementation time to record exact edits and test results.

### Step 1 — Add guard method (if missing)

- For each controller in the wave, add a private method `is*MeaningfullyFilled(...)`.
- Signature: accept the same normalized arrays/scalar inputs that the controller already uses for the create loop.
- Return `true` only when at least one “row” or one meaningful value exists (e.g. for multi-row sections: at least one non-empty required field in the normalized arrays; for single-row sections: at least one fillable field non-empty after trim).
- Reuse the same normalization logic (scalar-to-array, etc.) as the rest of the method so the guard and the create loop see the same data.
- **Document:** In the wave’s MD file, list the method name, file path, and the “meaningful” criteria (e.g. “at least one member_name non-empty and monthly_income present”).

### Step 2 — Add early return before delete

- In the **store()** (or **update()** if it does not delegate to store), immediately after normalizing request data and **before** `DB::beginTransaction()` and **before** any `Model::where(...)->delete()` or `->delete()`:
  - Call `if (! $this->is*MeaningfullyFilled(...)) { ... return; }`.
  - Return the same response type the controller already uses (e.g. `response()->json(['message' => '...'], 200)` or `redirect()->back()->with('success', '...')`) so that callers (e.g. ProjectController@update) do not see a different contract.
  - Log the skip (e.g. `Log::info('Controller@store - Section absent or empty; skipping mutation', ['project_id' => $projectId])`).
- For controllers where **update()** is a separate path (e.g. ILPRevenueGoalsController), add the guard at the start of **update()** before the delete block.
- **Document:** In the wave’s MD file, list file path and line range where the guard and return were inserted.

### Step 3 — Unit-level verification checklist

- For each controller in the wave, the wave’s MD file will record:
  - [ ] Guard method exists and is private.
  - [ ] Guard is called with the same normalized data used for create.
  - [ ] When guard returns false, no delete is executed (verified by reading code path).
  - [ ] When guard returns false, response type and status match existing behavior (e.g. 200 OK or redirect with success).
  - [ ] When guard returns true, existing delete-then-create behavior is unchanged (no change to delete/create logic).
- Optional: add or extend unit tests that call store/update with empty or missing section keys and assert no delete (e.g. mock or spy on Model::where()->delete() and assert not called). Document test names in the wave MD.

### Step 4 — Regression checklist

- For each controller in the wave, the wave’s MD file will record:
  - [ ] Full project update with section data present still saves section data (no regression).
  - [ ] Full project update with section data intentionally empty (user cleared section) still deletes and saves empty (guard allows “empty” only when design says so; see note below).
  - [ ] Project update with only General Info changed leaves this section’s data intact (primary regression test).
- **Note on “intentionally empty”:** If product behavior is “user can clear the section and save,” then “meaningfully filled” must treat “key present but user cleared all rows” as “meaningful” (e.g. one row with empty values) so that delete+recreate runs and the section becomes empty. The guard should only skip when the section is **absent** (keys not sent or not in request). Exact behavior will be specified per controller in the wave implementation doc.

---

## SECTION 5 — Verification Strategy

For **each wave**, the following manual test case will be executed and the result recorded in that wave’s implementation document.

### Manual test case (per wave)

1. **Create project** of the wave’s type (e.g. IAH for Wave 1, ILP for Wave 2, IIES for Wave 3, EduRUT for Wave 4).
2. **Fill section data** for every section that the wave’s controllers manage (e.g. for Wave 1: Earning Members, Budget Details, Support Details, Health Condition, Personal Info). Save (draft or submit as per app flow).
3. **Verify** section data is stored (e.g. view project or check DB).
4. **Update only General Info** (e.g. change project title or society name; do not change any section-specific fields). Submit the update.
5. **Confirm section data still exists** (same rows/count and key values as before step 4).

### Expected result

- **Before guards:** Step 5 can fail — section data may be wiped (Phase 2.1 impact).
- **After guards:** Step 5 must pass — section data must still exist unchanged. Any controller in the wave that would have run delete with empty data must have skipped mutation (early return) and left existing rows intact.

Each wave’s MD file will contain a “Verification” section with the above test case and a checkbox/result (e.g. “Manual test run on &lt;date&gt;: PASS”).

---

## SECTION 6 — Rollout Order

Recommended order balances **simplest structure first** (fewer controllers, less risk per deploy), **then higher-risk / higher-usage** waves.

| Order | Wave | Rationale |
|-------|------|-----------|
| 1 | **Wave 3 — IIES** | Single controller (IIESFamilyWorkingMembersController). Simplest change set; quick to verify and roll back. Low blast radius. |
| 2 | **Wave 4 — EduRUT** | Single controller (EduRUTAnnexedTargetGroupController). Same benefits as Wave 3; different project type so independent. |
| 3 | **Wave 1 — IAH** | Five controllers; one project type (Individual - Access to Health). Higher risk of data loss if unguarded (many sections). Deploy as one wave so all IAH sections are protected in one release. |
| 4 | **Wave 2 — ILP** | Four controllers; one project type (Individual - Livelihood Application). Same reasoning as IAH; complete ILP guard in one release. |

- **Highest risk:** IAH and ILP have the most unguarded controllers and section tables; they are done after the single-controller waves to reduce rollout risk.
- **Highest usage:** If usage data shows IAH or ILP is more common, the order could be swapped (e.g. Wave 1 before Wave 4); the plan keeps Wave 3 and 4 first for simplicity.
- **Independence:** Each wave is deployable on its own; no wave depends on another. Rollback is per wave (revert that wave’s commits).

---

## SECTION 7 — Completion Criteria

Phase 2 (Guard Completion) is **complete** when all of the following hold:

1. **All HIGH-risk controllers guarded**  
   Every one of the 11 controllers listed in Section 1 (Scope) has an early-return guard (using the standard pattern in Section 2) that runs **before** any delete in the update/store path.

2. **No controller deletes without guard**  
   For each of these controllers, there is no code path in the update (or store when called from update) that executes a bulk delete (e.g. `Model::where('project_id', $id)->delete()` or equivalent) without first passing the “meaningfully filled” (or equivalent) check.

3. **Project lifecycle stable against partial updates**  
   Manual verification (Section 5) has been run for all four waves and passed: “Update only General Info” does not wipe any of the sections covered by the 11 controllers.

4. **Documentation**  
   Each wave has a dedicated implementation document (e.g. `Phase_2_2_Wave_1_IAH_Implementation.md`, …) that records: guard method added, early-return location, unit/regression checklist results, and manual test result.

**Sign-off:** Phase 2 is complete when the above criteria are met and signed off (e.g. in the Phase 2 folder or in a short “Phase_2_Complete.md” summary).

---

**Phase 2 Guard Completion Plan Drafted — No Code Changes Made**
