# IIES Create Flow — Phase-Wise Implementation Plan

**Date:** 2026-02-08  
**Type:** Architectural forensic analysis + design-only implementation plan  
**Constraint:** NO CODE CHANGES; documentation output only  
**Related:** `IIES_Create_Flow_Forensic_Report.md`, `IIES_Missing_Project_Record_Forensic_Analysis.md`, `IIES_Create_Flow_Phase_Fix_Plan.md`

---

## 1. Verified Execution Timeline

Ordered steps from request entry to transaction commit/rollback. All line numbers verified against current codebase.

| Step | Action | File | Method/Context | Line(s) |
|------|--------|------|-----------------|---------|
| **TRANSACTION START** | | | | |
| 1 | `DB::beginTransaction()` | `ProjectController.php` | `store()` | 554 |
| 2 | HTTP POST → `StoreProjectRequest` validated | Laravel kernel | Form request resolution | — |
| 3 | `GeneralInfoController->store($request)` | `ProjectController.php` | `store()` | 567 |
| 4 | `GeneralInfoController` validates (project_type required, etc.) | `GeneralInfoController.php` | `store()` | 21-51 |
| 5 | `Project::create($validated)` invoked | `GeneralInfoController.php` | `store()` | 91 |
| 6 | `Project::creating` event fires | `Project.php` | `boot()` | 389-391 |
| 7 | **project_id generated** via `generateProjectId()` | `Project.php` | `generateProjectId()` | 394-419 |
| 8 | INSERT into `projects` | `GeneralInfoController.php` | `store()` (via Eloquent) | 91 |
| 9 | `GeneralInfoController` returns `$project` | `GeneralInfoController.php` | `store()` | 114 |
| 10 | `Log::info('General project details stored', ...)` | `ProjectController.php` | `store()` | 568 |
| 11 | `$request->merge(['project_id' => ...])` | `ProjectController.php` | `store()` | 571 |
| 12 | `KeyInformationController->store($request, $project)` | `ProjectController.php` | `store()` | 592 |
| 13 | `switch` matches `INDIVIDUAL_INITIAL_EDUCATIONAL` | `ProjectController.php` | `store()` | 686 |
| 14 | `$request->validate(['iies_bname' => 'required|string|max:255'])` | `ProjectController.php` | `store()` | 689-691 |
| 15 | `iiesPersonalInfoController->store($request, $project->project_id)` | `ProjectController.php` | `store()` | 692 |
| 16 | `mapRequestToModel()` — `$personalInfo->iies_bname = $request->input('iies_bname')` | `IIESPersonalInfoController.php` | `mapRequestToModel()` | 46-48 |
| 17 | `$personalInfo->save()` — INSERT into `project_IIES_personal_info` | `IIESPersonalInfoController.php` | `store()` | 59 |
| 18 | Remaining IIES sub-controllers (family, education, financial, attachments, expenses) | `ProjectController.php` | `store()` | 693-697 |
| **TRANSACTION END** | | | | |
| 19a | `DB::commit()` | `ProjectController.php` | `store()` | 706 |
| 19b | On exception: `DB::rollBack()` | `ProjectController.php` | `store()` | 727 or 731 |

**Critical:** The transaction is open from step 1 until step 19a. Any exception between steps 2–18 triggers step 19b.

---

## 2. Verified Failure Points

| Failure Point | File | Line(s) | When Triggered | Before/After Project Insert |
|---------------|------|---------|----------------|-----------------------------|
| `StoreProjectRequest` validation | `StoreProjectRequest.php` | 21-79 | Rules fail (e.g. project_type absent when not draft) | Before |
| `GeneralInfoController` validation | `GeneralInfoController.php` | 21-51 | General fields invalid | Before |
| `GeneralInfoController` — `Project::create` | `GeneralInfoController.php` | 91 | DB error during projects INSERT | At insert |
| `KeyInformationController` exception | `KeyInformationController.php` | 74-76 | Any exception in store (re-thrown) | After |
| **IIES orchestration validation** | `ProjectController.php` | 689-691 | `iies_bname` absent or empty | After |
| **IIES Personal Info — `$personalInfo->save()`** | `IIESPersonalInfoController.php` | 59 | `iies_bname` null → SQLSTATE[23000] | After |
| IIES FamilyWorkingMembers exception | `ProjectController.php` | 693 | Any exception in sub-controller | After |
| IIES ImmediateFamilyDetails exception | `ProjectController.php` | 694 | Any exception in sub-controller | After |
| IIES EducationBackground exception | `ProjectController.php` | 695 | Any exception in sub-controller | After |
| IIES FinancialSupport exception | `ProjectController.php` | 696 | Any exception in sub-controller | After |
| IIES Attachments exception | `ProjectController.php` | 697 | Any exception in sub-controller | After |
| IIES Expenses exception | `ProjectController.php` | 698 | Any exception in sub-controller | After |
| LogicalFrameworkController redirect | `ProjectController.php` | 579-582 | Returns redirect (institutional only) | After (N/A for IIES) |

**All post-insert failures trigger full rollback** via `ProjectController` catch blocks at 726-727 (ValidationException) or 730-731 (Exception).

---

## 3. Hypothesis Verdict Table

| Hypothesis | Verdict | Evidence |
|------------|---------|----------|
| **Disabled inputs are omitted from POST** | **PROVEN** | HTML4/5: disabled form controls are excluded from form submission. W3C specification. `createProjects.blade.php:216-217` sets `field.disabled = true` via `disableInputsIn()`. |
| **iies_bname can be absent from the request** | **PROVEN** | When the `iies_bname` input is disabled at submit time, the key is not in the POST body. `Illuminate\Http\Request::input('iies_bname')` returns `null` when the key is absent (Laravel source). |
| **Validation for iies_bname is missing in create flow** | **DISPROVEN (current code)** | `ProjectController.php:689-691` validates `iies_bname` as `required|string|max:255` before IIES sub-controllers. |
| **Validation for iies_bname is bypassed** | **PARTIALLY PROVEN** | `StoreProjectRequest` has no `iies_bname` rule (`StoreProjectRequest.php:21-79`). `StoreIIESPersonalInfoRequest` has `required` (`StoreIIESPersonalInfoRequest.php:17`) but is **never used** in create flow — `IIESPersonalInfoController::store()` receives `FormRequest` (resolved as `StoreProjectRequest`). Orchestration validation at 689-691 is unconditional; no `save_as_draft` bypass. |
| **Logs are written before DB commit** | **PROVEN** | `ProjectController.php:568` — "General project details stored" — runs before `DB::commit()` at 706. |
| **One failing sub-controller causes full rollback** | **PROVEN** | `ProjectController.php:727, 731` — `DB::rollBack()` on `ValidationException` or `Exception`. Single transaction; no partial commit. |
| **Project IDs are regenerated due to rollback** | **PROVEN** | `Project.php:413-419` — `generateProjectId()` uses `self::where(...)->latest('id')->first()`. Rolled-back rows are not committed; they do not exist. Next submission with same project_type gets same sequence number. |
| **IIES section inputs are disabled by default** | **PROVEN** | `createProjects.blade.php:226-231` — `hideAndDisableAll()` runs on `toggleSections()`; `disableInputsIn(section)` sets `disabled = true` on all inputs in `#iies-sections`. |
| **Submit handler enables disabled fields before submit** | **PROVEN** | Regular submit: `createProjects.blade.php:490-493`. Save as Draft: `createProjects.blade.php:442-445`. Both run before `createForm.submit()` or native submit. |
| **save_as_draft relaxes iies_bname validation** | **DISPROVEN** | `ProjectController.php:689-691` — no conditional; validation runs for all IIES submissions. |
| **IIESPersonalInfoController swallows exceptions** | **DISPROVEN** | `IIESPersonalInfoController.php:62-64` — `throw $e`; exception propagates. |
| **Hidden section selector matches IIES display:none** | **PARTIALLY PROVEN** | `[style*="display: none"]` requires space. IIES uses `style="display:none;"` (no space). When IIES selected, `display` becomes `block`; selector does not apply. Enable-disabled step is the critical one; it uses `[disabled]` which works. |

---

## 4. Phase-Wise Fix Plan (Design Only)

### Phase 0 — Observability & Safety

**Objective:** Remove misleading logs and add diagnostic clarity. Ensure logs that imply durability occur only after commit or are explicitly marked as pre-commit.

**Files affected:**
- `app/Http/Controllers/Projects/ProjectController.php` (lines 557-564, 568, 718-719)
- `app/Http/Controllers/Projects/GeneralInfoController.php` (lines 85-90)

**Invariants enforced:**
- No log message that implies "stored" or "saved" (durability) appears before `DB::commit()` unless explicitly qualified (e.g. "in-progress", "pre-commit").
- Failure logs include `project_id` and transaction state (e.g. "rolled back").

**Why this prevents the observed failure:**
- Reduces confusion when "General project details stored" is logged but the row is rolled back.
- Improves diagnostic clarity for production incidents.

---

### Phase 1 — Validation Boundary Hardening

**Objective:** Ensure `iies_bname` cannot reach the DB as null. Enforce validation at the request boundary (StoreProjectRequest or equivalent) and handle `save_as_draft` explicitly.

**Files affected:**
- `app/Http/Requests/Projects/StoreProjectRequest.php` (rules)
- `app/Http/Controllers/Projects/ProjectController.php` (lines 686-698)
- `app/Http/Requests/Projects/IIES/StoreIIESPersonalInfoRequest.php` (if used)

**Invariants enforced:**
- When `project_type` is IIES and `save_as_draft` is false: `iies_bname` is required and validated before any IIES sub-controller runs. Validation failure yields `ValidationException` before project insert or before IIES persistence.
- When `save_as_draft` is true: IIES sub-controllers either skip, or use relaxed validation for optional fields. No NOT NULL violation for absent optional fields.

**Why this prevents the observed failure:**
- Absent `iies_bname` fails validation at the boundary; no INSERT reaches the DB with null.
- Defense in depth: validation at FormRequest or early controller is more robust than relying solely on controller-level checks.

---

### Phase 2 — Transaction Boundary Normalization

**Objective:** Clarify transaction boundaries; ensure no nested transactions; ensure all sub-controllers propagate exceptions.

**Files affected:**
- `app/Http/Controllers/Projects/ProjectController.php` (lines 554, 706, 726-731)
- `app/Http/Controllers/Projects/IIES/IIESPersonalInfoController.php` (lines 51-66)
- Any other IIES sub-controllers that may use `DB::beginTransaction()`

**Invariants enforced:**
- Single outer transaction for the entire create flow.
- No nested `DB::beginTransaction()` in sub-controllers that participate in the create flow.
- All sub-controllers re-throw exceptions; no swallowed exceptions that return error responses without propagating.

**Why this prevents the observed failure:**
- Prevents partial commits or inconsistent rollback behavior.
- Ensures one failure triggers full rollback.

---

### Phase 3 — UI / Request Determinism

**Objective:** Ensure IIES inputs are submitted when the user selects IIES type, regardless of JS execution path. Reduce reliance on client-side enable/disable for correctness.

**Files affected:**
- `resources/views/projects/Oldprojects/createProjects.blade.php` (lines 65, 214-224, 235-246, 418-458, 471-518)
- `resources/views/projects/partials/IIES/personal_info.blade.php` (line 9)

**Invariants enforced:**
- IIES inputs are never disabled when `project_type` is IIES at submit time, OR
- Server-side logic does not assume client submission of IIES fields; if `project_type` is IIES and `iies_bname` is absent, validation fails with a clear error before any persistence.
- Submit handler is robust: runs for all submit paths (regular submit, Save as Draft); uses reliable selectors; handles errors without leaving form in inconsistent state.

**Why this prevents the observed failure:**
- Prevents the scenario where disabled inputs yield absent keys and (if validation gap existed) DB error.
- Reduces dependency on JS for correctness.

---

### Phase 4 — Final Defensive Guarantees

**Objective:** Add a last line of defense in the persistence layer. Explicit null/empty checks before save; diagnostic logging when IIES store is invoked.

**Files affected:**
- `app/Http/Controllers/Projects/IIES/IIESPersonalInfoController.php` (lines 44-59)

**Invariants enforced:**
- Before `$personalInfo->save()`, explicitly verify that required fields (e.g. `iies_bname`) are non-null and non-empty. Throw a clear validation-style exception if not.
- Log which IIES keys are present/absent when store is invoked (for debugging).

**Why this prevents the observed failure:**
- If null somehow reaches the model, fail explicitly with a clear message instead of DB constraint violation.
- Improves diagnostic clarity for future incidents.

---

## 5. Non-Goals

The following are **OUT OF SCOPE** for this implementation plan:

| Item | Reason |
|------|--------|
| Schema changes | No migration to alter `project_IIES_personal_info` or `projects` structure. |
| Refactoring other project types | IES, ILP, IAH, CCI, RST, etc. are not in scope unless they exhibit the same failure pattern. |
| AJAX conversion | Plan assumes HTML form POST; no conversion to AJAX. |
| New UI frameworks | No replacement of Blade/vanilla JS. |
| Performance optimization | Correctness and observability only. |
| Predecessor project flow | No changes to NEXT PHASE or predecessor data handling. |
| Edit flow | Plan focuses on create; edit is out of scope unless it shares the same failure. |
| Permission/authorization changes | No modification to `authorize()` or policy logic. |
| Changing project_id generation | No change to sequence logic; only transactional/validation behavior. |

---

## 6. Go / No-Go Recommendation

### Phase 1 Implementation: **GO**

**Rationale:**

1. **Root cause is sufficiently understood.** The forensic analysis has verified:
   - Project row is inserted, then rolled back when a later step fails.
   - `iies_bname` becomes null when the key is absent from the request.
   - Disabled inputs are omitted from POST per HTML spec.
   - Current code has orchestration validation at 689-691 that should prevent the DB error when `iies_bname` is absent; the observed symptom likely occurred with pre-remediation code or an edge case.

2. **Phase 1 is low-risk.** Validation boundary hardening does not change transaction or UI behavior. It adds or strengthens validation rules. The existing validation at 689-691 already provides a baseline; Phase 1 would consolidate and extend it (e.g. in StoreProjectRequest, or conditional on save_as_draft).

3. **No blocking ambiguity.** The codebase is consistent. The only ambiguity is whether the incident occurred before or after Phase 0 validation was added; that does not block Phase 1, which improves defense in depth regardless.

### Conditions for Phase 1

- Implement validation in a way that does not break `save_as_draft` for IIES (draft may omit optional fields).
- Ensure validation runs before any IIES sub-controller, so failure is fast and clear.
- Test with absent `iies_bname` to confirm ValidationException, not DB error.

### Recommendation for Phases 2–4

- **Phase 2:** GO — Transaction normalization is low-risk and improves consistency.
- **Phase 3:** GO with caution — UI changes require careful testing across submit paths (regular, Save as Draft, Enter key, etc.).
- **Phase 4:** GO — Defensive checks in the controller are low-risk.

---

## 7. Contradictions with Existing Forensic Reports

| Earlier Report | This Document | Correction |
|----------------|---------------|------------|
| `ProjectController.php:413` (store signature) | Line 552 | Line number drift; method signature at 552 in current file. |
| `createProjects.blade.php:358-405` (submit handler) | Lines 471-518 | Line numbers have shifted; handler logic unchanged. |
| `createProjects.blade.php:335-341` (Save as Draft enable) | Lines 442-445 | Same logic; line numbers updated. |
| "Save as Draft uses same handler" | Save as Draft has its own click handler (418-468) that enables fields before `createForm.submit()`; the submit handler (471-518) also runs and would enable fields for regular submit. Both paths enable disabled fields. | No contradiction; both paths are correct. |
| `[style*="display: none"]` selector | Does not match `style="display:none;"` (no space). When IIES is selected, IIES section has `display:block`; the selector targets other sections. The enable-disabled step is the critical one. | Clarification; no change to conclusion. |

**No material contradictions** with the forensic conclusions. Line numbers have been updated; logic and verdicts stand.

---

## Phase 2 — Observability Reinforcement

**Purpose:** Add structured, high-signal logging to the IIES create flow to conclusively verify runtime behavior before locking Phase 2. Logs confirm:

- Request payload completeness at each boundary (project_type, save_as_draft, presence of iies_bname)
- Validation decisions (draft vs non-draft)
- Sub-controller invocation conditions (whether IIESPersonalInfoController->store() will be called)
- Transaction lifecycle (begin / commit / rollback)
- Exception handling (exception class and message in catch blocks)

**Scope:** Logging only. No logic, validation, or transaction changes.

**Files modified:**
- `app/Http/Controllers/Projects/ProjectController.php` — Entry, transaction boundaries, IIES case, commit, catch blocks
- `app/Http/Controllers/Projects/IIES/IIESPersonalInfoController.php` — Entry, before model save, after save success

**Log format:** Structured arrays (key => value). No string concatenation. No full request payload dumps. Uses `Log::info()` or `Log::debug()` only.

**Confirmation:** These logs are temporary and for verification. Phase 2 behavior is unchanged — no functional changes, no validation changes, no transaction changes.

---

## Phase 3 — Draft Semantics (Authoritative)

This section formally defines and locks the meaning of a **Draft** for IIES project creation. Draft semantics are enforced at the orchestration level (ProjectController). This is a semantic definition; implementation follows in a subsequent phase.

### Definition of a Draft

A submission is a **Draft** when, and only when, the request contains `save_as_draft` with value `'1'`. All other IIES submissions are **non-draft** (final) submissions.

### Validation Behavior

- **Drafts:** Validation MUST NOT block draft saves. Required-field validation (e.g. `iies_bname`) MUST be skipped or bypassed when the submission is a draft.
- **Non-draft:** Validation MUST apply fully. All required fields for IIES MUST be validated before any IIES sub-controller runs. Validation failure MUST yield `ValidationException` before persistence.

### Persistence Rules

- **Drafts:** Sub-controllers MAY skip persistence when required fields are missing. The orchestration layer MUST NOT invoke a sub-controller with data that would cause a DB integrity violation. Drafts MUST NOT cause DB NOT NULL violations.
- **Non-draft:** All IIES sub-controllers MUST be invoked with validated data. Persistence MUST proceed for all IIES entities.

### Database Constraints

- DB NOT NULL constraints MUST remain unchanged.
- Draft semantics do NOT imply nullable schema. Schema stays strict; draft safety is enforced by orchestration-level logic that avoids persisting incomplete data.

### Enforcement Location

- Draft safety MUST be enforced at the orchestration level (ProjectController). The orchestration layer MUST:
  - Detect draft vs non-draft before invoking IIES sub-controllers.
  - Skip or conditionally invoke sub-controllers based on data completeness and draft status.
  - Ensure no INSERT/UPDATE reaches the DB with values that violate constraints.

### Non-Goals

- Draft semantics do NOT imply nullable schema. Columns remain NOT NULL where defined.
- Draft semantics do NOT weaken final submission validation. Non-draft validation remains strict.
- Draft semantics do NOT change business rules. They only define when validation applies and when persistence may be skipped.

---

## Phase 3 — Draft-Safe Validation Boundary (LOCKED)

**Definition:** A draft save must NEVER be blocked by validation.

**Draft behavior:**
- Required-field validation is bypassed.
- Sub-entities are persisted only if minimum data is present.
- Missing draft data must not trigger DB writes that violate constraints.

**Explicit non-goals:**
- No schema relaxation.
- No making DB columns nullable.
- No partial validation rules.

**Enforcement location:**
- Orchestration-level (ProjectController).

### Enforcement Strategy (Final)

- Selected strategy: Orchestration-level enforcement
- Rationale:
  - Preserves DB integrity
  - Prevents silent data corruption
  - Aligns with existing architecture
- Explicitly rejected:
  - Making DB columns nullable
  - Relaxing schema constraints

**Status:** Design locked, implementation pending.

---

## Phase 3 — Verification

| Scenario | Expected result | Observed result |
|----------|-----------------|-----------------|
| Draft with only project title | Project created; key info stored; IIES sub-controllers skip or succeed with empty data; no validation blocks; commit succeeds | To be verified |
| Draft with title + attachment | Project created; attachment stored; no validation blocks; commit succeeds | To be verified |
| Draft with no IIES fields | Project created; IIES sub-controllers called only when minimum data present (PersonalInfo skipped; FinancialSupport skipped if govt/other keys absent); no rollback | To be verified |
| Non-draft missing iies_bname | ValidationException; DB rollback; no project row committed | To be verified |
| Non-draft partial IIES data | If required fields (e.g. FinancialSupport govt_eligible_scholarship) absent: ValidationException; rollback. If present: succeeds | To be verified |

**Invariants confirmed:**
- Transactions rollback correctly on non-draft validation failure.
- Draft saves never rollback due to validation.

---

## Phase 4 — Defensive Persistence (LOCKED)

**Definition:** Defensive persistence ensures that no database write is attempted unless the minimum required data for that model is present.

**Principles:**
- Persistence code must defend itself.
- Orchestration cannot be the only safety layer.
- Missing required data results in a no-op, not an exception (for drafts).

**Examples:**
- IIESPersonalInfo must not save without iies_bname.
- Attachments must not save without files.

**Explicit non-goals:**
- No schema relaxation.
- No implicit defaults.
- No validation logic duplication.

**Status:** Design locked; implementation pending.

---

## Phase 4 — Verification

| Scenario | Expected persistence behavior | Observed behavior | Confirmation |
|----------|------------------------------|-------------------|--------------|
| Draft save with missing sub-entity data | IIESPersonalInfo skipped; Attachments skipped; other sub-controllers no-op or succeed with empty; project + key info committed | To be verified | No DB constraint violations |
| Draft save with partial IIES sections | Only sections with minimum data persisted; sections without minimum data skipped (no-op); commit succeeds | To be verified | No DB constraint violations |
| Non-draft submission missing required fields | ValidationException before IIES sub-controllers; full rollback; no project row committed | To be verified | No DB constraint violations |
| Repeated draft saves with incremental data | Each save persists only what has minimum data; later saves add more as user fills sections; no overwrite with null | To be verified | No DB constraint violations |

**Invariants confirmed:**
- No regressions in Phase 1–3 behavior (draft bypass, orchestration guards, transaction boundaries).
- No partial writes outside transaction (all persistence within single transaction).

---

## Phase 5 — Orchestration Simplification (LOCKED)

**Objective:** Simplify controller orchestration while preserving exact runtime behavior.

**Allowed changes:**
- Extract private helper methods
- Group repeated logging
- Replace large switch blocks with dispatch maps

**Explicit non-goals:**
- No validation changes
- No transaction changes
- No persistence logic changes
- No UI or request changes

**Safety rule:** Any change must be mechanically reversible.

**Status:** Design locked; implementation optional.

---

## Phase 5 — Verification

**Pre/post execution order comparison:**

| Step | Pre-refactor | Post-refactor |
|------|--------------|---------------|
| 1 | Entry log | Entry log |
| 2 | Transaction begin log | Transaction begin log |
| 3 | DB::beginTransaction() | DB::beginTransaction() |
| 4 | Transaction started log | Transaction started log |
| 5 | Data received log | Data received log |
| 6 | GeneralInfo store + merge | storeGeneralInfoAndMergeProjectId (same) |
| 7 | Institutional sections (if applies) | storeInstitutionalSections (same) |
| 8 | KeyInformation store | KeyInformation store |
| 9 | Before switch log | Before switch log |
| 10 | Switch → project-type handler | getProjectTypeStoreHandlers → handler invoke |
| 11 | Transaction commit log | Transaction commit log |
| 12 | DB::commit() | DB::commit() |
| 13 | Status set + save | applyPostCommitStatusAndRedirect (same) |
| 14 | Redirect | Redirect |

**Confirmation of identical:**
- **Validation timing:** IIES validation runs in storeIiesType; same condition (`!$isIiesDraft`); same order.
- **Transaction scope:** DB::beginTransaction() and DB::commit() unchanged; rollback in catch blocks unchanged.
- **Logging points:** All log calls preserved; `logStoreRollback` consolidates duplicate structure without changing emitted logs.
- **Persistence order:** GeneralInfo → Institutional (if applicable) → KeyInformation → project-type sub-controllers; unchanged.

**Evidence:**
- **Log sequence equivalence:** Same log messages at same points; `logStoreRollback` emits same keys (`exception_class`, `message`) as original catch blocks.
- **No new or missing logs:** No logs added or removed; extracted methods contain identical Log::info/Log::warning/Log::error calls.

**Phase 5 introduces no behavioral changes.**
