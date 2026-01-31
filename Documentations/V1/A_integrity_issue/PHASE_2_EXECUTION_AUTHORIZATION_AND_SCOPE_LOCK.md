# Phase 2 Execution Authorization and Scope Lock

**Document type:** Formal release authorization and scope lock for Phase 2  
**Source of truth:** `PHASE_WISE_WRITE_PATH_INTEGRITY_REMEDIATION_PLAN.md`, `PHASE_1_COMPLETION_SUMMARY.md`  
**Purpose:** Governance artefact to authorize execution of Phase 2 only, lock scope, and prevent overreach

---

## 1. Executive Authorization Statement

### 1.1 Formal Approval

**Phase 2 (Medium-Risk Write-Path Normalization)** is hereby **formally approved for execution**.

**Phase 3** and **Phase 4** are **not approved** and are **explicitly deferred**.

### 1.2 Authorization Decision (Non-Negotiable)

> **We are authorizing Phase 2 execution.**  
> **Phase 3 and later phases remain NOT authorized.**

This decision is binding for the current release. No work on Phase 3 or Phase 4 may be undertaken until a separate authorization is issued after Phase 2 success criteria and the next review gate are satisfied.

### 1.3 Prerequisite

Phase 1 (High-Risk Crash Fixes) has been implemented and is either verified or in final verification. Phase 2 is authorized on the basis that Phase 1 scope is complete and Phase 2 does not modify any Phase 1 controllers or tables.

### 1.4 Rationale for Phase 2

Phase 2 addresses **MEDIUM-risk** write-path integrity issues that do **not** currently crash production but can lead to inconsistent data, latent integrity violations, and future production errors. Phase 2 is **preventive**, not emergency. The remediation plan limits Phase 2 to two controllers and two tables: IIES Expenses (five NOT NULL decimal columns) and IES Immediate Family Details (NOT NULL boolean columns after `fill($request->all())`).

### 1.5 Deferred Phases

- **Phase 3** (Validation contract tightening) remains **not authorized**. No FormRequest or validation changes in this release.
- **Phase 4** (Regression protection & standards) remains **not authorized**. No mandatory documentation or checklist implementation as part of this release.

---

## 2. Approved Scope (Phase 2)

The following is the **exact** scope of Phase 2. Nothing outside this list is authorized. Phase 1 controllers and tables are **not** in Phase 2 scope.

### 2.1 Controllers Included in Phase 2

| #   | Controller (fully qualified)                                            |
| --- | ----------------------------------------------------------------------- |
| 1   | `App\Http\Controllers\Projects\IIES\IIESExpensesController`             |
| 2   | `App\Http\Controllers\Projects\IES\IESImmediateFamilyDetailsController` |

No other controllers are in scope. Phase 1 controllers (`FinancialSupportController`, `PersonalInfoController`) must not be modified in Phase 2.

### 2.2 Methods Included in Phase 2

| Controller                            | Methods                                                                                        |
| ------------------------------------- | ---------------------------------------------------------------------------------------------- |
| `IIESExpensesController`              | `store()` (and `update()` if it uses the same assignment pattern for the five decimal columns) |
| `IESImmediateFamilyDetailsController` | `store()`, `update()` (via `store()`)                                                          |

No other methods in these controllers are in scope except as needed to apply server-side defaults for the columns listed below.

### 2.3 Tables and Columns Included in Phase 2

#### Table: `project_IIES_expenses`

| Column                           | DB constraint               | Fix                                           |
| -------------------------------- | --------------------------- | --------------------------------------------- |
| `iies_total_expenses`            | decimal NOT NULL, default 0 | Replace `?? null` with `?? 0` (or equivalent) |
| `iies_expected_scholarship_govt` | decimal NOT NULL, default 0 | Same                                          |
| `iies_support_other_sources`     | decimal NOT NULL, default 0 | Same                                          |
| `iies_beneficiary_contribution`  | decimal NOT NULL, default 0 | Same                                          |
| `iies_balance_requested`         | decimal NOT NULL, default 0 | Same                                          |

No other columns of `project_IIES_expenses` are in scope for Phase 2.

#### Table: `project_IES_immediate_family_details`

All boolean columns that are NOT NULL in the migration (each has `->default(false)`). Normalize so that after `fill($request->all())` (or before `save()`), no NOT NULL boolean column receives null or empty.

Boolean columns (from migration):

- `mother_expired`, `father_expired`, `grandmother_support`, `grandfather_support`, `father_deserted`
- `father_sick`, `father_hiv_aids`, `father_disabled`, `father_alcoholic`
- `mother_sick`, `mother_hiv_aids`, `mother_disabled`, `mother_alcoholic`
- `own_house`, `rented_house`, `received_support`, `employed_with_stanns`

Nullable columns (e.g. `father_health_others`, `mother_health_others`, `family_situation`, `assistance_need`, `support_details`, `employment_details`) are **out of scope** for normalization; leave existing behavior unchanged.

### 2.4 Models (Read-Only for This Phase)

- `App\Models\OldProjects\IIES\ProjectIIESExpenses`
- `App\Models\OldProjects\IES\ProjectIESImmediateFamilyDetails`

No model file changes are authorized; only controller write paths are modified.

---

## 3. Deferred Scope

The following are **explicitly out of scope** for this authorization and **must not** be implemented as part of the current release.

### 3.1 Phase 3 and Phase 4 — Not Approved

- **Phase 3** (Validation contract tightening) is **not authorized**.
    - No changes to FormRequest validation rules (IIES Financial Support, ILP Personal Info, IIES Expenses, IES Immediate Family Details, or any other).
    - No documentation-only changes to request classes as part of Phase 2.

- **Phase 4** (Regression protection & standards) is **not authorized**.
    - No mandatory new documentation or checklist implementation as part of this release.

### 3.2 Explicitly Out of Scope (Regardless of Phase)

- **No schema changes** — No migrations, no altering columns, no new defaults at the database level.
- **No validation tightening** — No changes to any FormRequest.
- **No refactors** — No restructuring of controllers, no extraction of shared logic beyond the minimal change described in Section 4.
- **No helpers** — No new shared helper, service, or DTO for default normalization.
- **No UI changes** — No changes to Blade views, no `required` attributes, no default `checked`.
- **No Phase 1 touch** — No changes to `FinancialSupportController` or `PersonalInfoController` or to tables `project_IIES_scope_financial_support` or `project_ILP_personal_info`.
- **No other controllers** — No changes to IES Expenses controller, IGE Budget, CCI, ILP Budget, or any other controller for write-path integrity in this release.

---

## 4. Fix Strategy

### 4.1 IIESExpensesController

- **Pattern:** Server-side default for NOT NULL decimals.
- **Change:** Where the audit identifies `?? null` for the five decimal columns, replace with `?? 0` (or equivalent) so the value passed to the model is never null.
- **Apply in:** `store()` and, if present and using the same assignment pattern, `update()`.
- **Do not:** Change validation, models, or any other controller.

### 4.2 IESImmediateFamilyDetailsController

- **Pattern:** Normalize boolean attributes so NOT NULL columns never receive null.
- **Change:** After `fill($request->all())` (or before `save()`), for each boolean column defined as NOT NULL in the migration, if the attribute is null or empty, set it to 0 (or false). Optionally align with the pattern used in IIES Immediate Family Details (`$request->has($field) ? 1 : 0`) for consistency, without introducing a shared helper.
- **Do not:** Change validation, models, Blade, or any other controller.

### 4.3 What Will NOT Be Changed

- Database schema or migrations.
- FormRequest validation rules.
- Blade views or front-end.
- Any controller other than the two listed in Section 2.1.
- Phase 1 controllers or tables.

---

## 5. Execution Rules

### 5.1 One PR for Phase 2

- Phase 2 must be implemented and reviewed in **one pull request** (or one clearly labelled set of commits merged together).
- That PR must **only** touch `IIESExpensesController` and `IESImmediateFamilyDetailsController` and the columns listed in Section 2.3.
- No “while we’re here” changes; no Phase 3 or Phase 4 work in the same PR.

### 5.2 Deploy Independently

- Phase 2 must be deployed as a **distinct release** (or a clearly identifiable change set within a release).
- It must not be bundled with unrelated features or with Phase 3/4 so that observation and rollback are unambiguous.

### 5.3 Observation Window Required

- After Phase 2 is deployed to production, the team must **observe** for an **agreed period** (e.g. one release cycle or 7–14 days) with:
    - No integrity constraint violations for `project_IIES_expenses` or `project_IES_immediate_family_details`.
    - No new errors in logs attributable to the Phase 2 changes.
- Phase 3 **must not** be started until the Next Review Gate (Section 7) is satisfied and a **new** authorization is issued.

### 5.4 Rollback Strategy

- **Trigger:** If production integrity errors or regressions occur that are attributable to Phase 2, roll back.
- **Action:** Revert the commit(s) that changed `IIESExpensesController` and `IESImmediateFamilyDetailsController`. Redeploy.
- **Database:** No database rollback is required (no schema or data migration was performed).
- **Fallback:** If revert is not possible, re-apply the previous logic for those two controllers only until a fixed rollback can be done.

### 5.5 Review Guardrails

- Code review must confirm:
    - Only the two controllers and the listed columns are changed.
    - No `?? null` remains for the five IIES Expenses decimal columns where the column is NOT NULL.
    - IES Immediate Family Details NOT NULL booleans are normalized so null/empty becomes 0 before save.
    - No changes to migrations, FormRequests, or Blade views.
- Any change that expands scope beyond Section 2 must be rejected or moved to a future PR.

---

## 6. Success Criteria

Phase 2 is **complete** only when **all** of the following are met:

1. **Phase 2 code changes are implemented and merged**
    - Only `IIESExpensesController` and `IESImmediateFamilyDetailsController` are modified.
    - For the five IIES Expenses columns, the write path never passes null; server-side default `?? 0` is applied.
    - For IES Immediate Family Details, NOT NULL boolean columns never receive null after fill; null/empty normalized to 0.

2. **Staging verification (from the remediation plan) is passed**
    - IIES Expenses: submit with one or more of the five amount fields missing or empty → request succeeds; those columns stored as 0 (or equivalent).
    - IIES Expenses: submit with valid numeric values → values stored correctly.
    - IES Immediate Family Details: submit with a payload that includes null or empty for one or more boolean fields → request succeeds; those columns are 0 or 1, never null.
    - No new PHP or integrity errors in logs for these flows.
    - Existing tests for these flows (if any) still pass.

3. **Phase 2 is deployed to production** and the deployment is documented.

4. **No integrity violations** for `project_IIES_expenses` or `project_IES_immediate_family_details` are observed in production after deployment for the agreed observation period.

5. **Team sign-off** that Phase 2 is complete and stable.

---

## 7. Next Review Gate

Before **Phase 3** (or Phase 4) may be authorized:

1. **Evidence required**
    - Confirmation that Phase 2 success criteria (Section 6) have been met.
    - Production logs (or monitoring) showing no integrity errors for `project_IIES_expenses` or `project_IES_immediate_family_details` for the agreed observation period.
    - Sign-off from the release owner (or designated authority) that Phase 2 is complete and stable.

2. **Separate authorization**
    - A **new** authorization document (or an amendment) must explicitly approve Phase 3 or Phase 4 and define its scope lock.
    - No one may begin Phase 3 or Phase 4 work on the basis of this document alone.

3. **This document does not authorize Phase 3 or Phase 4**
    - This authorization covers **Phase 2 only**.
    - Any implementation of Phase 3 or Phase 4 without a subsequent formal authorization is **out of scope** and must be rejected in review.

---

## Document Control

| Item         | Value                                                                                  |
| ------------ | -------------------------------------------------------------------------------------- |
| Author       | Release Owner / Principal Software Engineer                                            |
| Source       | `PHASE_WISE_WRITE_PATH_INTEGRITY_REMEDIATION_PLAN.md`, `PHASE_1_COMPLETION_SUMMARY.md` |
| Approval     | Phase 2 only; Phase 3+ deferred                                                        |
| Prerequisite | Phase 1 implemented and verified or in final verification                              |
| Effective    | Upon creation of this document                                                         |
| Next gate    | Phase 3 requires new authorization after Phase 2 success and production observation    |

---

_End of Phase 2 Execution Authorization and Scope Lock._
