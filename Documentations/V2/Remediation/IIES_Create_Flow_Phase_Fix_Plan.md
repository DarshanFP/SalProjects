# IIES Create Flow Phase Fix Plan

**Document:** Remediation Plan  
**Date:** 2026-02-08  
**Source Audits:** IIES_Create_Flow_Architectural_Audit.md, IIES_Project_Creation_Forensic_Analysis.md  
**Status:** Design and Plan Only — No Implementation

---

## 1. Problem Summary

### 1.1 Observed Failures

The IIES (Individual – Initial – Educational Support) project creation flow exhibits the following structural correctness violations:

- **Exception swallowing:** Four IIES sub-controllers (PersonalInfo, ImmediateFamilyDetails, EducationBackground, Attachments) catch exceptions and return HTTP responses instead of re-throwing. The orchestrator never sees these failures.

- **Partial persistence:** When a sub-controller that swallows exceptions fails, the outer transaction continues and commits. The project row, key information, and other IIES sections are persisted; the failing section is not. The flow is not atomic.

- **Nested transactions breaking atomicity:** Sub-controllers that open their own `DB::beginTransaction()` create savepoints. Their `DB::rollBack()` undoes only their work; the outer transaction remains open and is later committed.

- **Logging vs durability mismatch:** "General project details saved" and similar success logs are emitted before `DB::commit()`. If a later propagating failure triggers outer rollback, logs will indicate success while no project row exists in the database.

- **Validation gap at orchestration level:** Required IIES fields (e.g. `iies_bname`, NOT NULL in the database) are not enforced by the FormRequest used for create. `StoreIIESPersonalInfoRequest` defines `required` rules but is never used in the create flow.

### 1.2 Phantom Project IDs

**Why phantom project IDs appear in logs but not in the database:**

The project ID (e.g. IIES-0029) is generated during `Project::create()` inside the `creating` model event, before the INSERT completes. The orchestrator logs "General project details saved" with that ID immediately after GeneralInfoController returns. The INSERT is inside the outer transaction and is not yet committed. If any later controller propagates an exception (e.g. KeyInformationController, FamilyWorkingMembers, FinancialSupport, Expenses), the orchestrator calls `DB::rollBack()`. The projects row is undone. The log already contains the project ID, but the row never persisted.

Phantom IDs can also arise when the same user resubmits: the first attempt rolls back, so the second attempt generates the same ID (the sequence has not advanced). Logs from both attempts reference the same ID; neither persists if both roll back.

### 1.3 Orchestration vs Phase 2 Services

**Why this is an orchestration issue, not a Phase 2 service issue:**

- **FormDataExtractor** and **ProjectAttachmentHandler** (Phase 2) are dependencies used by EducationBackgroundController and IIESAttachmentsController. They perform data extraction and attachment handling only. They do not manage transactions or catch exceptions.

- The correctness violations are in **controller-level behavior**: nested transactions, try/catch blocks that return HTTP responses without re-throwing, and the orchestrator ignoring return values. IIESPersonalInfoController and IIESImmediateFamilyDetailsController do not use any Phase 2 components and exhibit the same pattern.

- Phase 2 components are not the source of the architectural failures. Remediation targets the orchestration layer and IIES sub-controller transaction/exception handling only.

---

## 2. Guiding Principles

### 2.1 Transaction Ownership

A single orchestrator owns the transaction boundary. Only the orchestrator may call `DB::beginTransaction()`, `DB::commit()`, and `DB::rollBack()`. Sub-controllers participate within that transaction; they do not open or close their own transactions.

### 2.2 Exception Propagation

Sub-controllers must not swallow exceptions. Failures must propagate to the orchestrator so it can roll back the outer transaction and handle the error consistently. Sub-controllers may log before re-throwing but must not return HTTP responses in place of exceptions.

### 2.3 Single Source of Truth for Durability

Durability is achieved only when the orchestrator calls `DB::commit()`. Success logs that imply persistence must occur after commit. Pre-commit logs must clearly indicate in-progress work, not durable success.

### 2.4 No Scope Expansion During Remediation

Remediation does not introduce new features, schema changes, or refactors beyond what is necessary to restore transactional correctness and validation. Phase 2.3–2.5 components (FormSection, RoleGuard, etc.) are out of scope.

---

## 3. Phase 0 — Emergency Correctness Fixes (Mandatory)

Phase 0 must be applied first to stop data corruption and restore basic atomicity. These fixes are mandatory and must not be deferred.

### 3.1 Exception Re-throwing

**Purpose:** Ensure that any failure in an IIES sub-controller propagates to the orchestrator so the outer transaction can be rolled back.

**What changes conceptually:** The four sub-controllers that catch exceptions (IIESPersonalInfoController, IIESImmediateFamilyDetailsController, EducationBackgroundController, IIESAttachmentsController) must re-throw after logging. They must not return HTTP responses in the catch block.

**What is explicitly NOT changed:** The orchestrator's catch block and rollback logic remain as-is. Sub-controller success paths and business logic remain unchanged. Phase 2 services (FormDataExtractor, ProjectAttachmentHandler) are not modified.

### 3.2 Removal of Nested Transactions

**Purpose:** Eliminate savepoints so that any failure in a sub-controller triggers the orchestrator's rollback and undoes the entire flow.

**What changes conceptually:** The four sub-controllers that call `DB::beginTransaction()` must remove those calls. They must also remove their corresponding `DB::commit()` and `DB::rollBack()` calls. Sub-controllers become pure participants within the orchestrator-owned transaction.

**What is explicitly NOT changed:** The orchestrator's `DB::beginTransaction()` and `DB::commit()`/`DB::rollBack()` remain. Sub-controller persistence logic (model saves, updates) is unchanged.

### 3.3 Fatal Failure Propagation

**Purpose:** Guarantee that any unhandled exception in the IIES create flow reaches the orchestrator's catch block and triggers rollback.

**What changes conceptually:** Sub-controllers must not catch exceptions that they intend to handle by returning HTTP responses. If a sub-controller catches for logging purposes, it must re-throw. Validation exceptions (e.g. from FormRequest) already propagate; no change needed there.

**What is explicitly NOT changed:** ValidationException and other framework exceptions continue to be handled by the orchestrator as today. The orchestrator's redirect-with-errors behavior remains.

### 3.4 Minimal Validation Guards at Orchestration Level

**Purpose:** Prevent integrity constraint violations (e.g. `iies_bname` cannot be null) from reaching the database when the project type is IIES.

**What changes conceptually:** The orchestrator (or a dedicated validation step before IIES sub-controller calls) must enforce that required IIES fields are present when project type is IIES. This is a minimal guard: fail fast before any persistence if required data is missing. The guard can be implemented as a conditional validation block or by delegating to existing StoreIIESPersonalInfoRequest rules when project type is IIES.

**What is explicitly NOT changed:** StoreProjectRequest structure and other project-type validation remain. FormRequest hierarchy is not restructured. Sub-controller validation logic is not duplicated; the guard is orchestration-level only.

---

## 4. Phase 1 — Transaction Boundary Normalization

Phase 1 consolidates transaction ownership and aligns logging with durability. It builds on Phase 0 and does not introduce new transaction management patterns.

### 4.1 Single Orchestrator-Owned Transaction

**Responsibility:** ProjectController remains the sole owner of `DB::beginTransaction()`, `DB::commit()`, and `DB::rollBack()`. No other controller in the IIES create path opens or closes transactions.

**Sequence:** Begin transaction at entry; commit only after all IIES sub-controllers have completed successfully; roll back on any propagated exception.

### 4.2 Sub-Controllers as Transactional Participants Only

**Responsibility:** IIES sub-controllers (PersonalInfo, FamilyWorkingMembers, ImmediateFamilyDetails, EducationBackground, FinancialSupport, Attachments, Expenses) perform persistence only. They do not manage transactions. They do not catch exceptions for the purpose of returning HTTP responses. Any failure propagates to the orchestrator.

**Sequence:** Each sub-controller is called in turn by the orchestrator. Success means the sub-controller returns without throwing. Failure means an exception propagates; the orchestrator catches and rolls back.

### 4.3 Logging Alignment with Commit

**Responsibility:** Success logs that imply persistence (e.g. "General project details saved", "Project and all related data saved successfully") must occur after `DB::commit()` or be reworded to indicate in-progress work (e.g. "General project details persisted" only after commit).

**Sequence:** Pre-commit logs may describe steps completed (e.g. "Storing IIES Personal Info"); post-commit logs describe durable success (e.g. "Project and all related data saved successfully").

---

## 5. Phase 2 — Validation Responsibility Realignment

Phase 2 ensures that IIES-required fields are enforced before persistence attempts, reducing integrity failures and improving error visibility.

### 5.1 Why IIES-Required Fields Must Be Enforced Before Persistence

The database enforces NOT NULL on `iies_bname` and potentially other columns. If the request lacks these values, the INSERT fails with an integrity constraint violation. By validating before persistence, the flow fails fast with a validation error (user-friendly, redirect-with-errors) instead of an unhandled database exception. This aligns with the principle of validating at the boundary before touching the database.

### 5.2 Where Validation Belongs Short-Term vs Long-Term

**Short-term:** The orchestrator must ensure that when project type is IIES, required IIES fields are validated before any IIES sub-controller is called. This can be achieved by conditionally applying StoreIIESPersonalInfoRequest rules (or equivalent) within the create flow when project type is IIES. The validation runs at orchestration level, before the IIES switch block.

**Long-term:** A more cohesive design would route IIES create through a dedicated FormRequest or validation layer that composes general + IIES rules. That is a larger refactor and is out of scope for this remediation plan.

### 5.3 Why FormRequests Are NOT Introduced Yet

Introducing new FormRequests or changing the FormRequest hierarchy (e.g. composing StoreIIESPersonalInfoRequest into the create flow) would expand scope. The Phase 0/1 approach uses minimal orchestration-level guards that delegate to existing rules where possible, without restructuring the FormRequest layer. FormRequest changes can be considered in a later, separate phase if needed.

---

## 6. Phase 3 — Observability & Safety Guarantees

Phase 3 defines logging rules and failure visibility so that future forensic debugging can reliably correlate logs with persistence outcomes.

### 6.1 Logging Rules

- **"Saved" or "persisted" logs:** Emitted only after `DB::commit()`. Before that, logs may state "storing" or "persisting" (in progress), not "saved" (durable).

- **Project ID in logs:** When logging a project ID before commit, the log must indicate that the ID is tentative (e.g. "project_id (tentative)" or "in-progress project_id"). After commit, the project ID can be logged as confirmed.

- **Failure logs:** Sub-controllers may log errors before re-throwing. The orchestrator must log the rollback and the exception when it catches. This ensures a clear audit trail: failure → rollback → no persistence.

### 6.2 Failure Visibility Expectations

- Any failure in the IIES create flow must appear in logs with sufficient context (project_id if available, controller name, exception message).

- The orchestrator's catch block must log that rollback occurred and that no project was persisted (or that persistence was partial, if a different failure mode emerges).

- No silent failures: if an exception is caught, it must either be re-thrown or logged with explicit acknowledgment that the operation was aborted.

### 6.3 Future Forensic Debugging

With Phase 3 in place:

- Logs will clearly distinguish between in-progress and durable success.
- A phantom project ID in logs will be traceable: either the flow committed (row should exist) or it rolled back (log should indicate rollback and no persistence).
- Partial persistence will be eliminated by Phase 0/1, so the only outcomes are full success or full rollback.

---

## 7. Explicit Non-Goals

The following are intentionally out of scope for this remediation plan:

- **FormSection:** No changes to FormSection or form ownership concepts.

- **RoleGuard:** No changes to role-based access control or RoleGuard.

- **Attachment refactors beyond Phase 2.2:** ProjectAttachmentHandler and AttachmentContext remain as adopted in Phase 2.2. No changes to attachment storage, field config, or handler logic.

- **Schema changes:** No migrations, no column additions or removals, no constraint changes.

- **Frontend rewrites:** No changes to create form structure, JavaScript, or Blade templates except where strictly necessary to support validation (e.g. ensuring fields are submitted). Frontend changes are minimal and defensive only.

- **Phase 2.3–2.5 components:** FormDataExtractor, ProjectAttachmentHandler, FormSection_ownedKeys, BoundedNumericService, RoleGuard_RoleContract, Phase_2_4_GeneralInfo_Address_Ownership, and related Phase 2.3–2.5 work are not modified.

- **Other project types:** Remediation is scoped to IIES create only. Other project types (IES, ILP, IAH, etc.) may have similar patterns but are not addressed in this plan.

---

## 8. Execution Readiness Checklist

Before implementation of any phase begins, the following must be satisfied:

### 8.1 Phase Approval

- [ ] Phase 0 scope and approach have been reviewed and approved.
- [ ] Phase 1 scope and approach have been reviewed and approved (may follow Phase 0 completion).
- [ ] Phase 2 and Phase 3 are optional/follow-on; approval obtained when scheduled.

### 8.2 Environment and Dependencies

- [ ] No active Phase 2 migrations or schema changes are running that could conflict with controller changes.
- [ ] FormDataExtractor and ProjectAttachmentHandler (Phase 2.1–2.2) are stable and not under active modification.
- [ ] Test environment is available for IIES create flow verification.

### 8.3 Rollback Strategy

- [ ] Rollback strategy is defined: revert controller changes and redeploy previous version if Phase 0/1 causes regressions.
- [ ] No database migrations are introduced; rollback does not require migration down.
- [ ] Feature flags or similar are not required; changes are backward-compatible at the HTTP/redirect level.

### 8.4 Verification Criteria

- [ ] Phase 0: Verification test defined — IIES create with missing `iies_bname` must fail validation or re-throw before persist; no partial persistence.
- [ ] Phase 1: Verification test defined — Any IIES sub-controller failure must result in full rollback; no project row when any section fails.
- [ ] Phase 2: Verification test defined — Required IIES fields validated before persistence; clear validation errors on missing data.
- [ ] Phase 3: Verification test defined — Logs align with commit; no "saved" before commit; rollback logged when failure occurs.

### 8.5 Safety Guarantees

- [ ] Partial application of Phase 0 (e.g. fixing only one sub-controller) does not leave the system in a worse state: the unfixed sub-controllers still swallow exceptions, but the fixed ones will propagate. Full Phase 0 is required for consistency.
- [ ] Phase 1 assumes Phase 0 is complete; Phase 1 does not introduce new failure modes if Phase 0 is correctly applied.
- [ ] Each phase can be independently verified before the next phase begins.

---

## Document Control

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-02-08 | — | Initial plan |

---

**End of Document**
