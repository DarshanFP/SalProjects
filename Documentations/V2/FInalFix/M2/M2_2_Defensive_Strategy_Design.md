# M2.2 — Defensive Architecture Strategy Design

**Milestone:** M2 — Validation & Schema Alignment  
**Strategy Level:** B (Defensive Architecture)  
**Mode:** READ + DESIGN ONLY — No code modifications.

**CRITICAL RULES (observed):**
- No existing code changed.
- No refactors, no migrations, no controller/validation edits.
- This is a design document only. Planning implementation safely before touching code.

---

## OBJECTIVE

Based on M2.1 audit findings, design a safe defensive strategy that:

1. Prevents NOT NULL DB violations  
2. Prevents malformed row insertion in LogicalFramework  
3. Prevents numeric zero-drop caused by `empty()`  
4. Does NOT overlap with M1 (skip-empty guard)  
5. Does NOT touch resolver (M3)  
6. Does NOT touch societies (M4)

---

## SECTION 1 — Risk Categorization

For each risk discovered in M2.1, categorized by type, severity, and impact.

| Risk | Location | Category | Severity | Impact Type (Crash / Corruption / Both) |
|------|----------|----------|----------|----------------------------------------|
| projects.in_charge can receive NULL | GeneralInfoController@update, UpdateProjectRequest | A) Schema-Validation Misalignment | High | Crash (DB constraint violation) |
| projects.overall_project_budget can receive NULL | GeneralInfoController@update, UpdateProjectRequest | A) Schema-Validation Misalignment | High | Both (constraint violation + wrong data) |
| project_objectives.objective can receive null/missing | LogicalFrameworkController@update | C) Row-Level Integrity Risk | High | Crash |
| project_results.result can receive null | LogicalFrameworkController@update | C) Row-Level Integrity Risk | High | Crash |
| project_risks (risk/description) can receive null | LogicalFrameworkController@update | C) Row-Level Integrity Risk | High | Crash |
| project_activities.activity or .verification can receive null | LogicalFrameworkController@update | C) Row-Level Integrity Risk | High | Crash |
| project_timeframes.month / .is_active missing or null | LogicalFrameworkController@update | C) Row-Level Integrity Risk | Medium | Crash |
| Row with monthly_income = 0 skipped (IES, IAH) | IESFamilyWorkingMembersController, IAHEarningMembersController | D) Silent Business Data Corruption (numeric empty()) | Medium | Corruption |
| Expense row with amount = 0 skipped (IES, IAH) | IESExpensesController, IAHBudgetDetailsController | D) Silent Business Data Corruption (numeric empty()) | Medium | Corruption |
| project_type null on full submit (edge case) | UpdateProjectRequest, GeneralInfoController | A) Schema-Validation Misalignment | Low | Crash (rare; required when !draft) |

---

## SECTION 2 — Defensive Layer Decision

For each risk, which layer should fix it, why that layer is safest, and why others are riskier.

| Risk | Recommended Layer | Why This Layer Is Safest | Why Others Are Riskier |
|------|-------------------|---------------------------|-------------------------|
| projects.in_charge NULL | **2) prepareForValidation merge** extended to non-draft when key missing + **3) Controller-level guarantee** (never pass null to update for NOT NULL columns) | Merge ensures missing key gets existing value; controller guarantee handles “key present but value null” (e.g. cleared field). Single-layer fix (validation only) would require required rule when !draft but then draft would need exception; controller-only would leave validated with null in other consumers. | Validation-only: breaks draft if we make in_charge required when !draft without careful conditional. Relying only on merge: does not fix “key present, value null”. |
| projects.overall_project_budget NULL | **2) prepareForValidation merge** for missing key + **3) Controller-level guarantee** (default to 0 or existing value before update) | Same as in_charge: merge for absent key, controller for null value. DB has default 0.00; aligning write path avoids overwriting with null. | Validation required: would force a number on draft. Merge only: does not fix explicit null in request. |
| project_type null (full submit) | **1) Validation Layer** (keep required when !draft; already so) + **2) prepareForValidation** (merge when draft) | Already required when !draft; only edge case is draft without merge. No change needed for validation; ensure merge always runs for draft. | Controller default: project_type is business-critical; defaulting in controller could hide bugs. |
| LogicalFramework objective/result/risk/activity/verification null | **4) Per-row filtering before create()** inside LogicalFrameworkController | Ensures only rows with minimum viable content are created; no NOT NULL column receives null. Validation layer cannot easily express “each element of objectives[].objective must be non-empty string” for nested arrays; controller bulk update would be fragile. | Validation: nested array rules are complex and may conflict with draft/partial submit. Controller merge: no “existing row” to merge for delete-recreate pattern. |
| project_timeframes month/is_active | **4) Per-row filtering before create()** (only create timeframe when month key and is_active value are present and valid) | Same as above; row-level rule in same controller. Timeframes are children of activities; filtering at create avoids malformed rows. | Schema change: would require nullable columns and blur business meaning. |
| monthly_income = 0 dropped (IES, IAH) | **5) Replace empty() with explicit null/'' check** (e.g. “include row if key exists and value is not null and not ''”, allow 0) | Keeps “no data” (missing/null/empty string) as “skip row”; treats 0 as valid numeric. Minimal change at call site. | Validation: would require numeric rules per index; still need controller to not drop 0. Default in controller: doesn’t fix “0 was sent but dropped by empty()”. |
| expense/budget amount = 0 dropped (IES, IAH) | **5) Replace empty() with explicit null/'' check** for amount (allow 0; skip only when null or '') | Same as monthly_income: 0 is valid; only null or empty string mean “no amount”. | Same as above. |

**Summary of layer usage:**

- **Layer 1 (Validation):** project_type already required when !draft; no new rules needed for NOT NULL columns (to avoid breaking draft).
- **Layer 2 (prepareForValidation merge):** Extend to ensure in_charge and overall_project_budget are merged from existing project when **missing** on **both** draft and full submit (so missing key never sends nothing; draft already does this for draft path).
- **Layer 3 (Controller-level guarantee):** In GeneralInfoController, before `update($validated)`, ensure any key that maps to a NOT NULL column (in_charge, overall_project_budget) is never passed as null (use existing project value or default).
- **Layer 4 (Per-row filtering):** In LogicalFrameworkController, before each create(), only create objective/result/risk/activity/timeframe if the row has the minimum required non-empty fields.
- **Layer 5 (Replace empty() with explicit check):** In IES/IAH family working members and expense/budget controllers, replace conditions that use `empty($numeric)` with “value is null or strictly equals ''” so 0 is allowed.

---

## SECTION 3 — Draft-Safe Analysis

We must NOT break draft save. For each proposed layer/fix area:

### Draft Compatibility Matrix

| Fix Area | Draft Safe? | Requires Conditional Logic? | Notes |
|----------|-------------|-----------------------------|--------|
| prepareForValidation merge for in_charge, overall_project_budget | Yes | No (for “merge when missing”) | Already run only when save_as_draft; extending to “when key missing” on full submit is additive: draft still gets merge when key not filled; full submit gets merge when key absent so we never write null. Conditional only if we restrict “merge when missing” to full submit (then draft: merge when draft and not filled; full submit: merge when key missing). |
| Controller-level guarantee (GeneralInfoController: never pass null for in_charge, overall_project_budget) | Yes | No | Before update(), replace null with existing value or default. Does not add validation rules; draft can still send partial data; controller only sanitizes what is written. |
| Validation: required in_charge / overall_project_budget when !draft | Yes | Yes | If we ever add required when !draft, draft must be excluded (already the case for project_type). M2.2 prefers merge + controller guarantee to avoid making these required and touching validation rules. |
| LogicalFramework per-row filtering (only create row if minimum fields present and non-empty) | Yes | No | Filtering only removes malformed rows from the set we create; does not require “section must be full”. M1 guard still decides “skip whole section if empty”; once we decide to mutate, we only create valid rows. Draft can send partial objectives; we create only those with non-empty objective text (and valid children). |
| Replace empty() with explicit null/'' check for monthly_income, amount | Yes | No | 0 becomes “valid and stored”; “skip row” only when value is null or ''. Draft can still omit rows or send empty; no new required fields. |

**Conclusion:** All proposed fixes can be made draft-safe. Only optional future “required when !draft” for in_charge/overall_project_budget would need explicit draft vs submit conditional; current design avoids that by using merge + controller guarantee.

---

## SECTION 4 — Logical Framework Deep Review

Design contract for when to create each entity and what minimum fields must exist. No implementation; design only.

### Objective

- **When should it be created?**  
  When the objectives array has at least one element and the section is considered “meaningfully filled” (M1 guard). For each element of the array, create a row only when that element has a **non-empty objective text** (after trim). Do not create an objective row if the objective text is missing, null, or only whitespace.

- **Minimum fields:**  
  `project_id` (from context), `objective_id` (generated), `objective` (required non-empty string). No row creation if `objective` is null or empty after trim.

- **Empty children:**  
  Results, risks, activities may be empty arrays. That is allowed: one objective with no results/risks/activities is valid. Creation of child rows is governed by their own minimum viable row rules below.

### Result

- **When should it be created?**  
  Only when the parent objective is created and the results array for that objective has an element with a **non-empty result text** (after trim).

- **Minimum viable row rule:**  
  Create result row only if `resultData['result']` exists and is a non-empty string after trim. If key is missing or value is null/empty, skip that result entry (do not create row).

### Risk

- **When should it be created?**  
  Only when the parent objective is created and the risks array has an element with a **non-empty risk text** (after trim).

- **Minimum viable row rule:**  
  Create risk row only if `riskData['risk']` exists and is a non-empty string after trim. Otherwise skip that risk entry.

### Activity

- **When should it be created?**  
  Only when the parent objective is created and the activities array has an element with **non-empty activity text** (after trim). Verification may be empty string for DB (if schema allows) or a default; design contract: **activity** is required non-empty; **verification** if missing or null should be stored as empty string (or default) to satisfy NOT NULL, not null.

- **Minimum viable row rule:**  
  Create activity row only if `activityData['activity']` exists and is non-empty after trim. For `verification`, use `activityData['verification']` if present and string, else empty string (or defined default). Do not create activity if `activity` is null or empty after trim.

### Timeframe

- **When should it be created?**  
  Only when the parent activity is created and the timeframe data contains a `months` array (or equivalent) with at least one key-value pair.

- **Minimum viable row rule:**  
  For each key (month) and value (is_active) in the timeframe months structure: create timeframe row only if **month** key is present and non-empty (and valid month identifier) and **is_active** is defined (boolean or castable to boolean). If month is missing or invalid, skip that timeframe entry. Default is_active to false if missing.

**Contract summary:** No NOT NULL column receives null. Any row that would have a null in a NOT NULL column is either skipped (objective/result/risk/activity when text empty) or the value is defaulted (verification to '', is_active to false) so that we never call create() with null for a NOT NULL column.

---

## SECTION 5 — Numeric Zero Handling Policy

Rule in English for when 0 is valid, when it should be stored, and when a row should be skipped.

### monthly_income (IES Family Working Members, IAH Earning Members)

- **When is 0 valid?**  
  When the user explicitly enters 0 (or the form sends 0) to mean “this member has no income” or “income not applicable.” 0 is a valid business value.

- **When should 0 be stored?**  
  Whenever the field is present and is the number 0 (or string "0" that normalizes to 0). Store it.

- **When should 0 skip creation?**  
  Never for the reason “value is 0.” Skip creation only when the **entire row** is considered empty: e.g. all of member_name, work_nature, and monthly_income are missing, null, or empty string. If monthly_income is 0 but name and work_nature are present and non-empty, create the row with monthly_income = 0.

**Exact condition logic (English):**  
“Include this row in the set of rows to create if and only if (member_name is present and non-empty after trim) and (work_nature is present and non-empty after trim) and (monthly_income key exists and value is not null and not the empty string).” The value may be 0 or "0"; do not use a check that treats 0 as “empty.” So: do **not** use `empty($monthlyIncome)`; use “value is null or value === ''” to exclude, and allow 0.

### expense amount (IES Estimated Expenses, IAH Budget Details)

- **When is 0 valid?**  
  When the user enters 0 for a line item (e.g. “no cost for this particular”). 0 is a valid amount.

- **When should 0 be stored?**  
  Whenever the amount field is present and is the number 0 (or string "0"). Store the detail row with amount 0.

- **When should 0 skip creation?**  
  Never for the reason “amount is 0.” Skip creation of a detail row only when the row is meaningless: e.g. both particular and amount are missing/null/empty, or (if business rule is “need at least particular”) when particular is missing or empty. If particular is present and non-empty and amount is 0, create the row with amount 0.

**Exact condition logic (English):**  
“Create an expense/budget detail row if and only if (particular is present and non-empty after trim) and (amount key exists and value is not null and value is not the empty string).” Allow numeric 0 and string "0". Do **not** use `empty($amount)`; use “value is null or value === ''” to exclude.

### budget detail amount (IAH Budget Details)

- Same as “expense amount” above: 0 is valid, store it when particular is present and amount key exists and is not null/''. Do not use empty(amount).

**Cross-cutting rule:**  
For any numeric field where 0 is semantically valid: “skip row” or “skip value” must be based on “missing or null or empty string,” not on “falsy” (so not on empty() for that value). Use explicit checks: value === null || value === '' (and optionally type coercion to number for storage).

---

## SECTION 6 — Implementation Order Plan (Still No Code)

Atomic, production-safe order for M2 defensive work. No code written here; only step definitions.

### Step 1 — projects table protection

- **1a.** In UpdateProjectRequest prepareForValidation: ensure that when the request key for `in_charge` is missing (not filled), merge `in_charge` from existing project for the route project_id, for both draft and non-draft requests, so that validated() never omits in_charge when we have an existing value. (Draft already merges when not filled; extend to “key missing” for full submit so that missing key gets existing value.)
- **1b.** Similarly for `overall_project_budget`: when key is missing, merge from existing project so we do not pass missing key to update (and thus do not overwrite with null).
- **1c.** In GeneralInfoController@update, before calling `$project->update($validated)`: for each of `in_charge` and `overall_project_budget`, if the key exists in validated and the value is null, replace it with (a) existing project value, or (b) for overall_project_budget only, 0.00 if no existing value. So the array passed to update() never contains null for these two keys.
- **1d.** Verify: full submit with in_charge/overall_project_budget cleared or missing does not write null; draft save still works with partial data.

### Step 2 — LogicalFramework row integrity

- **2a.** In LogicalFrameworkController@update (and store if same pattern exists), before creating each ProjectObjective: only create if `$objectiveData['objective']` is present and non-empty after trim; otherwise skip that array element.
- **2b.** Before creating each ProjectResult: only create if `$resultData['result']` is present and non-empty after trim; otherwise skip.
- **2c.** Before creating each ProjectRisk: only create if `$riskData['risk']` is present and non-empty after trim; otherwise skip.
- **2d.** Before creating each ProjectActivity: only create if `$activityData['activity']` is present and non-empty after trim; for `verification`, use value if present and string, else default to empty string (or defined default); never pass null for verification.
- **2e.** Before creating each ProjectTimeframe: ensure month key exists and is valid, and is_active is defined (default false if missing); do not create with null month or null is_active.
- **2f.** Verify: submit with one objective with empty objective text does not create that objective row; submit with mixed valid/empty children creates only valid rows; no NOT NULL violation.

### Step 3 — numeric empty() fixes

- **3a.** IESFamilyWorkingMembersController (store/update): replace the condition that uses empty() on monthly_income with an explicit “value is not null and value !== ''” (and allow 0). Keep “skip row” only when the row is truly empty (e.g. all three fields missing/null/empty as per current business rule).
- **3b.** IAHEarningMembersController: same for monthlyIncome.
- **3c.** IESExpensesController: replace empty($amount) with explicit “amount is not null and amount !== ''”; allow 0 for amount.
- **3d.** IAHBudgetDetailsController: same for amount.
- **3e.** Verify: 0 for monthly_income or amount is stored; empty string or null still skips row as intended.

### Step 4 — Retesting plan

- **4a.** Regression: draft save (partial form) still saves without errors; draft load and submit still work.
- **4b.** Projects table: full submit with in_charge or overall_project_budget cleared or missing does not cause DB error; existing value or default is retained.
- **4c.** LogicalFramework: submit with empty objective text (or empty result/risk/activity text) does not create malformed row and does not cause NOT NULL violation; valid rows still created.
- **4d.** Numeric 0: IES/IAH family members and expense/budget details with 0 stored correctly and visible on show/edit.
- **4e.** No overlap: M1 “skip section when empty” behavior unchanged; no changes to resolver (M3) or societies (M4).

---

## SECTION 7 — Final Recommendation

- **Scope:** This strategy stays within **Milestone M2 — Validation & Schema Alignment**. It addresses schema-validation misalignment, controller write risk, LogicalFramework row-level integrity, and numeric zero handling on the project update path. It does not add new features or change business rules beyond “do not write null to NOT NULL columns” and “treat 0 as valid for specified numeric fields.”

- **No overlap with other milestones:**
  - **M1 (skip-empty guard):** M1 decides *whether* to run section mutation (skip when section absent or empty). M2 does not change that. M2 only ensures that *when* we mutate, we never write null to NOT NULL and we never drop 0 for those numeric fields.
  - **M3 (resolver):** No changes to ProjectFinancialResolver or display logic. M2 is write-path only.
  - **M4 (societies):** No changes to societies tables, validation, or controllers.

- **Estimated risk level:** **Low** if changes are applied in the order above and retesting is done. Risks: (1) prepareForValidation or controller logic could affect edge cases (e.g. new project vs update) — mitigate by scoping to update path and existing project; (2) LogicalFramework filtering could change “how many rows” are created when request has empty strings — intended and documented; (3) numeric condition change could include previously-skipped rows (e.g. "0" string) — verify normalization and tests.

- **Recommended implementation order:**  
  **1 → 2 → 3 → 4** (projects table protection first, then LogicalFramework row integrity, then numeric empty() fixes, then full retesting). Projects table fixes prevent crashes on the main update path; LogicalFramework prevents crashes on institutional project updates; numeric fixes prevent silent corruption; retesting confirms no regressions and no overlap with M1/M3/M4.

---

**End of M2.2 Defensive Strategy Design. No code changes made. Design document only.**
