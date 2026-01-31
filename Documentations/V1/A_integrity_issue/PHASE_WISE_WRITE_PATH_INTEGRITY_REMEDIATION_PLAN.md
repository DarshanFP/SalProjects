# Phase-Wise Write-Path Integrity Remediation Plan

**Document type:** Release roadmap for write-path data integrity fixes  
**Source of truth:** `WRITE_PATH_DATA_INTEGRITY_AUDIT.md` (this plan does not introduce issues beyond the audit)  
**Principle:** Production safety over speed; incremental phases; independently verifiable; no schema changes in early phases

---

## Planning Principles (Non-Negotiable)

1. **Production safety over speed** — Each phase must be deployable and verifiable before the next.
2. **No schema changes in early phases** — Remediation is server-side defaults and write-path normalization only (Phases 1–3). Migrations are out of scope unless explicitly justified later.
3. **No mass refactors** — One controller or one clear set of files per phase; no cross-cutting “fix everything” changes.
4. **One clear fix pattern per phase** — So the team can apply it consistently and review easily.
5. **Each phase must be independently verifiable** — Checklist and rollback defined per phase.

---

## Phase 0: Preparation & Guardrails

### 0.1 Phase Objective

Establish a safe baseline and guardrails before changing any write paths: confirm scope from the audit, define the fix pattern, and ensure we can verify and roll back.

### 0.2 Scope

- **In scope:** Documentation, verification steps, and decision log. No application code or schema changes.
- **Artifacts:** This plan; optional one-page “fix pattern” reference for Phases 1–2; verification checklist template.

### 0.3 Fix Strategy Used in This Phase

- **N/A** — No code fix. Strategy is to **define** the pattern for Phases 1–2:
    - **NOT NULL boolean/tinyint:** In the write path, replace `?? null` with a type-appropriate default (e.g. `?? 0` or cast `"0"/"1"` to int) so the value passed to the model is never null.
    - **NOT NULL decimal:** Replace `?? null` with `?? 0` (or equivalent) so the value passed to the model is never null.
    - Apply only in the controller (or single place) that builds the array for `create()` / `updateOrCreate()` / `save()`; do not introduce shared helpers yet.

### 0.4 What Is Explicitly OUT OF SCOPE

- Any change to routes, middleware, or application logic.
- Any change to migrations or database schema.
- Any change to validation rules or FormRequests.
- Any change to UI (Blade, JS, or required attributes).
- Any new shared helper, service, or DTO for normalization (deferred until after patterns are stable).

### 0.5 Verification Checklist

- [ ] All stakeholders have read `WRITE_PATH_DATA_INTEGRITY_AUDIT.md` and this plan.
- [ ] The exact list of controllers and methods for Phase 1 and Phase 2 is agreed (see Phase 1 and Phase 2 scope below).
- [ ] Rollback for Phase 1 and Phase 2 is understood: revert the controller commit(s) and redeploy; no DB rollback needed.
- [ ] There is a way to trigger the affected flows in staging (IIES Financial Support store/update, ILP Personal Info store/update, IIES Expenses store/update) with missing keys to reproduce the issue before fix and confirm success after fix.

### 0.6 Rollback Strategy

- No code is changed in Phase 0; nothing to roll back.

### 0.7 Criteria to Move to the Next Phase

- Sign-off that Phase 0 checklist is complete and the team is ready to implement Phase 1.

---

## Phase 1: High-Risk Crash Fixes

### 1.1 Phase Objective

Eliminate the two HIGH-risk write paths that can cause production integrity violations (confirmed incident + same pattern elsewhere): NOT NULL boolean columns receiving explicit NULL when request keys are missing.

### 1.2 Scope (Exact Controllers / Models Affected)

| #   | Controller                                                      | Methods                                               | Table                                  | Columns                                                   |
| --- | --------------------------------------------------------------- | ----------------------------------------------------- | -------------------------------------- | --------------------------------------------------------- |
| 1   | `App\Http\Controllers\Projects\IIES\FinancialSupportController` | `store()`, `update()`                                 | `project_IIES_scope_financial_support` | `govt_eligible_scholarship`, `other_eligible_scholarship` |
| 2   | `App\Http\Controllers\Projects\ILP\PersonalInfoController`      | `store()` (and thus `update()` which delegates to it) | `project_ILP_personal_info`            | `small_business_status`                                   |

**Models (read-only for this phase):**

- `App\Models\OldProjects\IIES\ProjectIIESScopeFinancialSupport`
- `App\Models\OldProjects\ILP\ProjectILPPersonalInfo`

No other controllers, models, or tables are in scope for Phase 1.

### 1.3 Fix Strategy Used in This Phase

- **Server-side default for NOT NULL booleans.**  
  In each of the above methods, wherever the audit identifies `?? null` for a column that is NOT NULL in the DB:
    - Replace the null fallback with a value that matches the migration default (e.g. `0` or `false`).
    - Ensure that both “missing key” and “present but null/empty” are normalized to that default (e.g. `$validated['other_eligible_scholarship'] ?? 0` and, if needed, cast string `"0"/"1"` to int so the column never receives null).
- **Single pattern:** “NOT NULL boolean → never pass null; use `?? 0` (or equivalent) and optional cast.”
- Changes are limited to the controller(s) listed above; no new shared helpers, no validation rule changes, no UI changes.

### 1.4 What Is Explicitly OUT OF SCOPE

- Any other controller or table (e.g. IIES Expenses, IES Immediate Family Details).
- Any change to migrations or schema.
- Any change to FormRequest validation rules (StoreIIESFinancialSupportRequest, UpdateIIESFinancialSupportRequest, StoreILPPersonalInfoRequest, UpdateILPPersonalInfoRequest).
- Any change to Blade views or front-end (no `required`, no default `checked` in this phase).
- Introduction of a shared helper or service for defaults.

### 1.5 Verification Checklist

- [ ] In staging, IIES Financial Support: submit the form **without** selecting either radio for `govt_eligible_scholarship` or `other_eligible_scholarship`; request must succeed and both columns must be stored as 0 (or false).
- [ ] In staging, IIES Financial Support: submit with “Yes” (1) and “No” (0) selected; values must be stored correctly.
- [ ] In staging, ILP Personal Info: submit **without** sending `small_business_status` (e.g. omit the field); request must succeed and column must be stored as 0 (or false).
- [ ] In staging, ILP Personal Info: submit with `small_business_status` = 1; value must be stored correctly.
- [ ] No new PHP errors or integrity violations in logs for these two flows.
- [ ] Existing tests that cover these flows (if any) still pass.

### 1.6 Rollback Strategy

- Revert the commit(s) that changed `FinancialSupportController` and `PersonalInfoController`.
- Redeploy. No database rollback is required (no schema or data migration was done).
- If the revert is not possible, re-apply the previous “?? null” logic for those two controllers only; that restores the pre-fix behavior (and the original integrity risk).

### 1.7 Criteria to Move to the Next Phase

- All Phase 1 verification checklist items pass in staging.
- Phase 1 has been deployed to production and monitored for at least one release cycle (or agreed period) with no integrity errors for `project_IIES_scope_financial_support` or `project_ILP_personal_info`.
- Team sign-off to proceed to Phase 2.

---

## Phase 2: Medium-Risk Write-Path Normalization

### 2.1 Phase Objective

Remove the MEDIUM-risk cases where NOT NULL columns can receive NULL: (1) IIES Expenses decimal columns, (2) IES Immediate Family Details when the client sends null/empty for boolean fields.

### 2.2 Scope (Exact Controllers / Models Affected)

| #   | Controller                                                              | Methods                                                           | Table                                  | Columns / concern                                                                                                                                        |
| --- | ----------------------------------------------------------------------- | ----------------------------------------------------------------- | -------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | `App\Http\Controllers\Projects\IIES\IIESExpensesController`             | `store()` (and `update()` if it uses the same assignment pattern) | `project_IIES_expenses`                | `iies_total_expenses`, `iies_expected_scholarship_govt`, `iies_support_other_sources`, `iies_beneficiary_contribution`, `iies_balance_requested`         |
| 2   | `App\Http\Controllers\Projects\IES\IESImmediateFamilyDetailsController` | `store()`, `update()` (via `store()`)                             | `project_IES_immediate_family_details` | All boolean columns that are NOT NULL in migration; ensure `fill($request->all())` never leaves null for those columns (normalize before or after fill). |

**Models (read-only for this phase):**

- `App\Models\OldProjects\IIES\ProjectIIESExpenses`
- `App\Models\OldProjects\IES\ProjectIESImmediateFamilyDetails`

No other controllers or tables are in scope for Phase 2.

### 2.3 Fix Strategy Used in This Phase

- **IIESExpensesController:**  
  Server-side default for NOT NULL decimals. Where the audit identifies `?? null` for the five decimal columns, replace with `?? 0` (or equivalent) so the value passed to the model is never null. Apply in both `store()` and `update()` if both use the same assignment pattern.
- **IESImmediateFamilyDetailsController:**  
  Normalize boolean attributes so NOT NULL columns never receive null. After `fill($request->all())` (or before `save()`), for each boolean column defined as NOT NULL in the migration, if the attribute is null or empty, set it to 0 (or false). Optionally align with the IIES pattern (`$request->has($field) ? 1 : 0`) for consistency, without introducing a shared helper in this phase.
- **Single pattern:** “NOT NULL decimal → `?? 0`”; “NOT NULL boolean after fill → normalize null/empty to 0.”
- No schema changes, no validation rule changes, no UI changes in this phase.

### 2.4 What Is Explicitly OUT OF SCOPE

- Any controller or table not listed above (e.g. IES Expenses, IGE Budget, CCI, ILP Budget — audit marked them low risk or nullable).
- Any change to migrations or schema.
- Any change to FormRequest validation rules.
- Any change to Blade views or front-end.
- Introduction of a shared helper or service (keep normalization inline in the controller).

### 2.5 Verification Checklist

- [ ] In staging, IIES Expenses: submit with one or more of the five amount fields missing or empty; request must succeed and those columns must be stored as 0 (or equivalent).
- [ ] In staging, IIES Expenses: submit with valid numeric values; values must be stored correctly.
- [ ] In staging, IES Immediate Family Details: submit with a payload that includes null or empty for one or more boolean fields; request must succeed and those columns must be 0 or 1, never null.
- [ ] No new PHP or integrity errors in logs for these flows.
- [ ] Existing tests for these flows (if any) still pass.

### 2.6 Rollback Strategy

- Revert the commit(s) that changed `IIESExpensesController` and `IESImmediateFamilyDetailsController`.
- Redeploy. No database rollback required.

### 2.7 Criteria to Move to the Next Phase

- All Phase 2 verification checklist items pass in staging.
- Phase 2 has been deployed to production and monitored for an agreed period with no integrity errors for `project_IIES_expenses` or `project_IES_immediate_family_details`.
- Team sign-off to proceed to Phase 3 (or to skip Phase 3 and go to Phase 4).

---

## Phase 3: Validation Contract Tightening (Optional)

### 3.1 Phase Objective

Optionally align validation with the database contract for the NOT NULL columns already fixed in Phases 1–2: so that validation either requires the field or explicitly documents that the write path supplies a default when the field is absent. This phase is **optional** and can be skipped if the team prefers to rely only on server-side defaults.

### 3.2 Scope (Exact Controllers / Requests Affected)

- **FormRequests only** (no new controller logic beyond what was done in Phase 1–2):
    - `App\Http\Requests\Projects\IIES\StoreIIESFinancialSupportRequest`
    - `App\Http\Requests\Projects\IIES\UpdateIIESFinancialSupportRequest`
    - `App\Http\Requests\Projects\ILP\StoreILPPersonalInfoRequest`
    - `App\Http\Requests\Projects\ILP\UpdateILPPersonalInfoRequest`
    - Any IES/IIES Expenses or IES Immediate Family Details request classes used by the Phase 2 controllers (if they exist and are in scope).

**Options (choose one per request):**

- Add a rule that the field is present when the column is NOT NULL (e.g. `required` or `present`), and keep normalizing "0"/"1" in the controller; or
- Keep the field optional in validation but add a comment or docblock that the write path applies a server-side default for NOT NULL columns.

No change to database schema or UI in this phase.

### 3.3 Fix Strategy Used in This Phase

- **Validation-only or documentation-only.**  
  Either: (a) add `required`/`present` (and possibly `in:0,1` or similar) for the NOT NULL boolean columns in the listed FormRequests, or (b) leave rules as-is and document in the request class that the controller guarantees a non-null value for those columns.  
  Do not reintroduce `?? null` in controllers; Phases 1–2 behavior must remain.

### 3.4 What Is Explicitly OUT OF SCOPE

- New write-path logic (defaults stay as implemented in Phases 1–2).
- Schema or migration changes.
- UI changes (required attributes, default checked).
- Any request or controller not listed in the audit for the NOT NULL columns we fixed.

### 3.5 Verification Checklist

- [ ] If validation was changed: form submissions with the field missing still succeed (controller still applies default) and with the field present still validate and save correctly.
- [ ] If only documentation was added: no functional change; verification from Phases 1–2 remains valid.
- [ ] No new validation errors for normal use cases.

### 3.6 Rollback Strategy

- Revert the FormRequest (and any comment) changes; redeploy. No DB rollback.

### 3.7 Criteria to Move to the Next Phase

- Phase 3 checklist passed (or Phase 3 skipped by decision).
- Team sign-off to proceed to Phase 4.

---

## Phase 4: Regression Protection & Standards

### 4.1 Phase Objective

Embed the learnings from the audit and Phases 1–3 into team practice so future changes do not reintroduce “NOT NULL column receives null” and so the fix pattern is consistent.

### 4.2 Scope

- **In scope:** Documentation, review checklist, and optionally a short “how to add a new write path” note. No mandatory new code (e.g. shared helper) unless the team explicitly decides to add one after patterns are stable.
- **Out of scope:** Further code changes to controllers or requests beyond what was done in Phases 1–3; schema changes; UI changes.

### 4.3 Fix Strategy Used in This Phase

- **Standards and guardrails only.**
    - Add a short “Write-path integrity” section to the project’s backend or release docs (or link to the audit + this plan).
    - Define a **pre-merge checklist** for any PR that touches create/update/save for project-related models: “For each column written, confirm: if the column is NOT NULL in the schema, the value passed is never null (use server-side default when key is missing or value is null).”
    - Optionally: add a note that new NOT NULL boolean/decimal fields must use the same pattern (e.g. `?? 0` or normalize after fill).
    - Do not introduce shared helpers in this phase unless the team has already stabilized and adopted the pattern from Phases 1–2.

### 4.4 What Is Explicitly OUT OF SCOPE

- New application code beyond documentation and checklist.
- Mandatory automated tests (optional: add a single smoke test or regression test for one of the Phase 1 flows if the team agrees).
- Schema or migration changes.
- UI or validation changes.

### 4.5 Verification Checklist

- [ ] The “Write-path integrity” doc (or link set) is in the agreed location and has been read by the team.
- [ ] The pre-merge checklist is in the agreed place (e.g. PR template or backend doc) and has been used on at least one subsequent PR that touches a project write path.
- [ ] No new integrity incidents for the tables fixed in Phases 1–2 (and Phase 2 tables) for an agreed period after Phase 4 is adopted.

### 4.6 Rollback Strategy

- Documentation and checklist changes can be reverted or relaxed; no code or DB rollback.

### 4.7 Criteria to Move to the Next Phase

- N/A — Phase 4 is the final phase. Success = checklist and standards are in place and the team uses them for relevant PRs.

---

## Summary: What Gets Fixed Where

| Phase | Risk level | Table(s)                               | Columns / concern                                         | Strategy                                        |
| ----- | ---------- | -------------------------------------- | --------------------------------------------------------- | ----------------------------------------------- |
| 1     | HIGH       | `project_IIES_scope_financial_support` | `govt_eligible_scholarship`, `other_eligible_scholarship` | Server-side default (e.g. `?? 0`) in controller |
| 1     | HIGH       | `project_ILP_personal_info`            | `small_business_status`                                   | Server-side default (e.g. `?? 0`) in controller |
| 2     | MEDIUM     | `project_IIES_expenses`                | Five decimal columns                                      | Server-side default (e.g. `?? 0`) in controller |
| 2     | MEDIUM     | `project_IES_immediate_family_details` | NOT NULL boolean columns                                  | Normalize null/empty to 0 after fill            |
| 3     | Optional   | —                                      | Validation / doc for above                                | Optional validation or documentation only       |
| 4     | —          | —                                      | Future changes                                            | Doc + pre-merge checklist; no new code required |

---

## Constraints Respected

- **Server-side defaults preferred** — All fixes in Phases 1–2 are in the write path (controller); no DB column changes.
- **No making DB columns nullable** — Plan does not require or recommend changing NOT NULL to nullable.
- **No UI changes in early phases** — Phases 1 and 2 do not require any change to Blade, `required`, or default `checked`.
- **No shared helpers until patterns stabilize** — Phases 1–2 and 3 keep changes local to the affected controllers/requests; Phase 4 does not mandate a shared helper.

---

_End of Phase-Wise Write-Path Integrity Remediation Plan._
