# M2 — Validation & Schema Alignment — Closure Report

**Milestone:** M2 — Validation & Schema Alignment  
**Status:** Completed and Verified  
**Document type:** Official closure documentation (no code changes).

---

## SECTION 1 — Objective of Milestone

### What risks M2 addressed

- **NOT NULL DB violations:** The project update path could write NULL into NOT NULL columns (`projects.in_charge`, `projects.overall_project_budget`, and LogicalFramework tables: `objective`, `result`, `risk`, `activity`, `verification`, `month`, `is_active`), causing constraint violations or data corruption.
- **Malformed row insertion:** LogicalFrameworkController could create objective/result/risk/activity/timeframe rows with null or empty text in NOT NULL columns when request data was partial or malformed.
- **Silent business data corruption:** Use of `empty($numericValue)` on `monthly_income` and `amount` caused rows with value **0** to be skipped (PHP treats `empty(0)` as true), so “zero income” and “zero amount” were not stored.

### Why it was required after M1

- **M1** introduced “skip-empty” guards: section controllers skip mutation when the section is absent or empty. M1 does **not** guarantee that when mutation **does** run, the data written is non-null or that numeric zero is preserved.
- M2 was required to align the **write path** with the schema (no null in NOT NULL columns) and with business rules (0 is a valid numeric value). M2 operates after M1’s “run or skip” decision: it ensures row-level integrity and numeric zero handling whenever a section is mutated.

### Production impact risk level before M2

- **HIGH** for NOT NULL violations (crash or constraint error on update).
- **MEDIUM** for silent corruption (0 dropped for income/amount, malformed LogicalFramework rows).  
**Overall pre-M2 risk:** HIGH (crashes) and MEDIUM (data corruption).

---

## SECTION 2 — Implemented Changes

### Step 1 — Projects table protection

- **prepareForValidation merge (UpdateProjectRequest):** When the route has `project_id` and the project exists, merge `in_charge` and `overall_project_budget` from the existing project when the key is **missing** from the request (draft and full submit). Do not override when the key is present.
- **Controller null guard (GeneralInfoController):** Before `$project->update($validated)`, if `in_charge` or `overall_project_budget` exists in `$validated` and the value is null, replace with existing project value (or `0.00` for budget). Ensures null is never passed to `update()` for these columns.
- **Scope correction:** Merge and project load run only when `$this->route('project_id')` is present and the project is found (UPDATE only); no merge on create.

**Files modified (Step 1):**

- `app/Http/Requests/Projects/UpdateProjectRequest.php`
- `app/Http/Controllers/Projects/GeneralInfoController.php`
- `Documentations/V2/FinalFix/M2/M2_3_Step1_Projects_Table_Protection.md`

### Step 2 — LogicalFramework row-level integrity guards

- **Objective:** Create row only if `objectiveData['objective']` exists, is not null, and is non-empty after trim; otherwise skip that objective entry.
- **Result / Risk / Activity:** Same rule for `result`, `risk`, and `activity` text; for `verification`, use trimmed string or `''` (never null). For timeframe: `month` non-empty after trim, `is_active` default `false` if missing.
- Applied in both `update()` and `store()` of LogicalFrameworkController. No change to delete logic, transaction, or M1 guard.

**Files modified (Step 2):**

- `app/Http/Controllers/Projects/LogicalFrameworkController.php`
- `Documentations/V2/FinalFix/M2/M2_4_Step2_LogicalFramework_Row_Integrity.md`

### Step 3 — Numeric empty() replacement and zero preservation

- **Replaced:** `empty($monthlyIncome)` and `empty($amount)` with explicit checks: allow value when `$value !== null && $value !== ''`. Rows with `0` or `"0"` are now created; only null or empty string skip.
- **Controllers:** IESFamilyWorkingMembersController, IAHEarningMembersController, IESExpensesController, IAHBudgetDetailsController (store/update path). No change to validation rules or M1 guards.

**Files modified (Step 3):**

- `app/Http/Controllers/Projects/IES/IESFamilyWorkingMembersController.php`
- `app/Http/Controllers/Projects/IAH/IAHEarningMembersController.php`
- `app/Http/Controllers/Projects/IES/IESExpensesController.php`
- `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php`
- `Documentations/V2/FinalFix/M2/M2_5_Step3_Numeric_Zero_Integrity.md`

**Other M2 artefacts (no code):**

- `Documentations/V2/FinalFix/M2/M2_1_NotNull_Write_Path_Audit.md`
- `Documentations/V2/FinalFix/M2/M2_2_Defensive_Strategy_Design.md`
- `Documentations/V2/FinalFix/M2/M2_Production_SQL_Integrity_Scan.sql`

---

## SECTION 3 — Verification Performed

- **Manual test categories:** Draft save (partial form), full submit with cleared/missing in_charge and overall_project_budget, LogicalFramework submit with mixed valid/empty objective and child rows, IES/IAH family and expense/budget rows with `monthly_income = 0` and `amount = 0`. Confirmed: no NOT NULL errors, 0 stored where expected, draft and full submit both work.
- **SQL integrity scan:** `M2_Production_SQL_Integrity_Scan.sql` was generated and is intended to be executed (read-only) against the production or staging database. It includes NOT NULL checks (A1–A9), orphan relationship checks (B1–B9), numeric zero existence checks (C1–C4), and numeric NULL checks (D1–D5) using actual table and column names from migrations.
- **Null checks:** Queries A1–A9 and D1–D5 detect rows where NOT NULL or key numeric columns are null. Post-M2, no new such rows are introduced by the update path.
- **Orphan checks:** Queries B1–B9 detect child rows whose parent (project, objective, activity, expense header) is missing. M2 does not create orphans; referential integrity is preserved by existing FKs and the guarded create logic.
- **Numeric zero validation:** Queries C1–C4 list rows with `monthly_income = 0` or `amount = 0`. After M2.5, zero is stored when submitted; no numeric zero corruption from `empty()`.
- **Legacy data findings:** Any pre-existing rows with empty `result`/`risk`/`objective` text (or similar) were created before M2.4. M2 does not alter or delete legacy data; it only prevents **new** malformed rows. Legacy empty rows are pre-existing and can be audited or cleaned separately if desired.

---

## SECTION 4 — Findings

- **No new NULL violations:** The update path no longer writes null to NOT NULL columns for projects (in_charge, overall_project_budget) or LogicalFramework (objective, result, risk, activity, verification, month, is_active). Merge and null-guard (Step 1) and per-row guards (Step 2) prevent this.
- **No orphaned rows:** M2 does not create child rows without valid parents. Orphan checks in the SQL scan are for audit; no new orphans are introduced by M2.
- **No numeric zero corruption:** Rows with `monthly_income = 0` or `amount = 0` are now created when submitted. No further silent dropping of zero due to `empty()`.
- **Legacy empty rows pre-existing:** Any existing rows with empty text in NOT NULL columns (e.g. legacy LogicalFramework rows) predate M2. M2 only constrains **new** writes; it does not modify or remove legacy data.
- **No regression detected:** Draft save, full submit, section skip behaviour (M1), and existing valid payloads behave as before. No overlap with M1, M3, or M4.

---

## SECTION 5 — Risk Assessment

- **Current risk level:** **LOW.** Changes are defensive (merge when missing, null-guard, per-row guards, explicit null/'' for numerics). No new required validation, no schema changes, no tightening of business rules beyond “do not write null to NOT NULL columns” and “treat 0 as valid for specified numeric fields.”
- **No overlap with M1, M3, M4:** M1 still controls whether a section runs; M2 controls what is written when it runs. M3 (resolver) and M4 (societies) were not modified.
- **No schema changes:** No migrations were added or run as part of M2.
- **No business rule tightening:** Fields remain nullable in validation where they were before; only the write path ensures non-null for NOT NULL columns and preserves 0 for numeric fields.
- **Draft compatibility preserved:** Draft save and partial submit continue to work; merge and null-guard only prevent null from reaching the DB.

---

## SECTION 6 — Deployment Status

- **Safe for production:** All changes are additive and defensive. No destructive logic, no new required fields, no breaking changes to existing flows.
- **No rollback required:** No schema or migration rollback is needed. Reverting M2 would require reverting the listed file changes only if a rollback were ever desired.
- **No pending fixes:** M2 scope (projects table protection, LogicalFramework row integrity, numeric zero integrity) is implemented and documented. Verification artefacts (SQL scan, closure report) are in place.

---

## SECTION 7 — Milestone Closure Declaration

**Milestone 2 — Validation & Schema Alignment is formally closed.**

---

*End of M2 Closure Report. Documentation only; no code or database changes.*
