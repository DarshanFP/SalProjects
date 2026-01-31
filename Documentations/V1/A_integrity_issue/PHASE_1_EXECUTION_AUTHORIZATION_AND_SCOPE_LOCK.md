# Phase 1 Execution Authorization and Scope Lock

**Document type:** Formal release authorization and scope lock  
**Source of truth:** `PHASE_WISE_WRITE_PATH_INTEGRITY_REMEDIATION_PLAN.md`  
**Purpose:** Governance artefact to authorize execution of Phase 0 and Phase 1 only, lock scope, and prevent overreach

---

## 1. Executive Decision Statement

### 1.1 Formal Approval

**Phase 0 (Preparation & Guardrails)** and **Phase 1 (High-Risk Crash Fixes)** are hereby **formally approved for execution**.

**Phase 2** and **all later phases** (Phase 3, Phase 4) are **not approved** and are **explicitly deferred**.

### 1.2 Execution Decision (Non-Negotiable)

> **We are executing Phase 1 only next.**  
> **Phase 2 and later phases are NOT approved and MUST NOT be implemented at this time.**

This decision is binding for the current release. No work on Phase 2, Phase 3, or Phase 4 may be undertaken until a separate authorization is issued after Phase 1 success criteria and the next review gate are satisfied.

### 1.3 Rationale for Approval (Phase 0 and Phase 1)

- **Phase 0** establishes documentation, verification steps, and agreement on the fix pattern without changing any application code. It reduces risk of misinterpretation and ensures rollback is understood before any code is touched.
- **Phase 1** addresses the **only HIGH-risk write paths** identified in the audit: the production-confirmed integrity failure (`project_IIES_scope_financial_support.other_eligible_scholarship`) and the same pattern in `govt_eligible_scholarship` and in `project_ILP_personal_info.small_business_status`. Fixing these two controllers eliminates the immediate production-stopping crash risk with minimal scope and no schema or UI change.

### 1.4 Rationale for Deferral (Phase 2 and Later)

- **Phase 2** (Medium-risk: IIES Expenses, IES Immediate Family Details) is **intentionally deferred** so that Phase 1 can be deployed, observed in production, and verified before any further write-path changes. Introducing more controllers in the same release would increase blast radius and complicate rollback and attribution.
- **Phase 3** (Validation contract tightening) and **Phase 4** (Regression protection & standards) depend on Phase 1 (and optionally Phase 2) being complete and stable. They are **out of scope** until Phase 1 is signed off and a future authorization is issued.

### 1.5 Production-Safety Rationale

- **Incremental risk:** One small, well-defined change set (two controllers, NOT NULL boolean defaults only) is easier to test, review, and roll back than a larger set.
- **Observe then proceed:** Production must be observed for at least one release cycle after Phase 1 with no integrity errors before Phase 2 is considered. This gate is documented in Section 6 below.
- **No schema, no UI:** Phase 1 does not touch migrations, validation rules, or views; rollback is a controller revert only, with no database or front-end impact.

---

## 2. Approved Scope (Phase 1)

The following is the **exact** scope of Phase 1. Nothing outside this list is authorized.

### 2.1 Controllers Included in Phase 1

| #   | Controller (fully qualified)                                    |
| --- | --------------------------------------------------------------- |
| 1   | `App\Http\Controllers\Projects\IIES\FinancialSupportController` |
| 2   | `App\Http\Controllers\Projects\ILP\PersonalInfoController`      |

No other controllers are in scope.

### 2.2 Methods Included in Phase 1

| Controller                   | Methods                                                              |
| ---------------------------- | -------------------------------------------------------------------- |
| `FinancialSupportController` | `store()`, `update()`                                                |
| `PersonalInfoController`     | `store()` (and thus `update()` insofar as it delegates to `store()`) |

No other methods in these controllers are in scope for write-path changes except as needed to apply the server-side default for the columns listed below.

### 2.3 Tables and Columns Included in Phase 1

| Table                                  | Columns to be fixed (NOT NULL booleans)                   |
| -------------------------------------- | --------------------------------------------------------- |
| `project_IIES_scope_financial_support` | `govt_eligible_scholarship`, `other_eligible_scholarship` |
| `project_ILP_personal_info`            | `small_business_status`                                   |

No other tables or columns are in scope.

### 2.4 Permitted Change (Phase 1)

- **Only:** In the two controllers above, replace the null fallback for the listed columns with a type-appropriate default (e.g. `?? 0` or equivalent) so that the value passed to the model is **never null**. Optionally cast string `"0"/"1"` to int where needed.
- **Pattern:** NOT NULL boolean → never pass null; use server-side default when key is missing or value is null/empty.

---

## 3. Deferred Scope

The following are **explicitly out of scope** for this authorization and **must not** be implemented as part of the current release.

### 3.1 Phase 2 and Later Phases — Not Approved

- **Phase 2** (Medium-risk write-path normalization) is **deferred**.
    - No work on `App\Http\Controllers\Projects\IIES\IIESExpensesController`.
    - No work on `App\Http\Controllers\Projects\IES\IESImmediateFamilyDetailsController`.
    - No work on tables `project_IIES_expenses` or `project_IES_immediate_family_details` for this remediation.

- **Phase 3** (Validation contract tightening) is **deferred**.
    - No changes to FormRequest validation rules (Store/Update IIES Financial Support, Store/Update ILP Personal Info, or any other).

- **Phase 4** (Regression protection & standards) is **deferred**.
    - No mandatory new documentation or checklist implementation as part of this release (optional prep is allowed; no scope creep into Phase 1 PR).

### 3.2 Explicitly Out of Scope (Regardless of Phase)

- **No schema changes** — No migrations, no altering columns, no new defaults at the database level.
- **No validation tightening** — No changes to `StoreIIESFinancialSupportRequest`, `UpdateIIESFinancialSupportRequest`, `StoreILPPersonalInfoRequest`, `UpdateILPPersonalInfoRequest`.
- **No refactors** — No restructuring of controllers, no extraction of shared logic beyond the minimal change described in Section 2.4.
- **No helpers** — No new shared helper, service, or DTO for default normalization.
- **No UI changes** — No changes to Blade views, no `required` attributes, no default `checked` on radios/checkboxes.
- **No other controllers** — No changes to IIESExpensesController, IESImmediateFamilyDetailsController, or any other project controller for write-path integrity in this release.

---

## 4. Execution Rules & Guardrails

### 4.1 One PR per Phase

- Phase 1 must be implemented and reviewed in **one pull request** (or one clearly labelled set of commits that are merged together).
- That PR must **only** touch the two controllers and the columns listed in Section 2.
- No “while we’re here” changes; no Phase 2 or Phase 3 work in the same PR.

### 4.2 Phase 1 Must Deploy Alone

- Phase 1 must be deployed as a **distinct release** (or a clearly identifiable change set within a release).
- It must not be bundled with unrelated features or other integrity phases so that observation and rollback are unambiguous.

### 4.3 Production Observation Required Before Phase 2

- After Phase 1 is deployed to production, the team must **observe** for at least **one full release cycle** (or an agreed period, e.g. 7–14 days) with:
    - No integrity constraint violations for `project_IIES_scope_financial_support` or `project_ILP_personal_info`.
    - No new errors in logs attributable to the Phase 1 changes.
- Phase 2 **must not** be started until the Next Review Gate (Section 6) is satisfied and a **new** authorization is issued.

### 4.4 Rollback Strategy

- **Trigger:** If production integrity errors or regressions occur that are attributable to Phase 1, roll back immediately.
- **Action:** Revert the commit(s) that changed `FinancialSupportController` and `PersonalInfoController`. Redeploy.
- **Database:** No database rollback is required (no schema or data migration was performed).
- **Fallback:** If revert is not possible, re-apply the previous `?? null` logic for those two controllers only; this restores pre-fix behavior (and the original integrity risk) until a fixed rollback can be done.

### 4.5 Review Guardrails

- Code review must confirm:
    - Only the two controllers and the listed columns are changed.
    - No `?? null` remains for `govt_eligible_scholarship`, `other_eligible_scholarship`, or `small_business_status`.
    - No changes to migrations, FormRequests, or Blade views.
- Any change that expands scope beyond Section 2 must be rejected or moved to a future PR.

---

## 5. Success Criteria

Phase 1 is **complete** only when **all** of the following are met:

1. **Phase 0 checklist (from the remediation plan) is complete**
    - Stakeholders have read the audit and the phase-wise plan.
    - The Phase 1 scope (Section 2 of this document) is agreed.
    - Rollback strategy is understood.
    - Staging can trigger IIES Financial Support and ILP Personal Info flows with missing keys.

2. **Phase 1 code changes are implemented and merged**
    - Only `FinancialSupportController` and `PersonalInfoController` are modified.
    - For the three columns listed in Section 2.3, the write path never passes null; server-side default (e.g. `?? 0`) is applied.

3. **Staging verification (from the remediation plan) is passed**
    - IIES Financial Support: submit without selecting radios → request succeeds; both boolean columns stored as 0.
    - IIES Financial Support: submit with Yes/No selected → values stored correctly.
    - ILP Personal Info: submit without `small_business_status` → request succeeds; column stored as 0.
    - ILP Personal Info: submit with `small_business_status` = 1 → value stored correctly.
    - No new PHP or integrity errors in logs for these flows.
    - Existing tests for these flows (if any) still pass.

4. **Phase 1 is deployed to production** and the deployment is documented.

5. **No integrity violations** for `project_IIES_scope_financial_support` or `project_ILP_personal_info` are observed in production after deployment (for the agreed observation period).

---

## 6. Next Review Gate

Before **Phase 2** (or any later phase) may be authorized:

1. **Evidence required**
    - Confirmation that Phase 1 success criteria (Section 5) have been met.
    - Production logs (or monitoring) showing no integrity errors for `project_IIES_scope_financial_support` or `project_ILP_personal_info` for at least one full release cycle (or the agreed observation period).
    - Sign-off from the release owner (or designated authority) that Phase 1 is complete and stable.

2. **Separate authorization**
    - A **new** authorization document (or an amendment to this document) must explicitly approve Phase 2 (and define its scope lock).
    - No one may begin Phase 2 work on the basis of this document alone.

3. **This document does not authorize Phase 2**
    - This authorization covers **Phase 0 and Phase 1 only**.
    - Any implementation of Phase 2, Phase 3, or Phase 4 without a subsequent formal authorization is **out of scope** and must be rejected in review.

---

## Document Control

| Item      | Value                                                                               |
| --------- | ----------------------------------------------------------------------------------- |
| Author    | Release Owner / Principal Software Engineer                                         |
| Source    | `PHASE_WISE_WRITE_PATH_INTEGRITY_REMEDIATION_PLAN.md`                               |
| Approval  | Phase 0 and Phase 1 only; Phase 2+ deferred                                         |
| Effective | Upon creation of this document                                                      |
| Next gate | Phase 2 requires new authorization after Phase 1 success and production observation |

---

_End of Phase 1 Execution Authorization and Scope Lock._
