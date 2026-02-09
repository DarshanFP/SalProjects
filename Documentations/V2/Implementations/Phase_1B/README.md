# Phase 1B — Structural Cleanup

## Purpose

Phase 1B corrects **structural duplication** and **cross-module inconsistencies** in the attachment layer after Phase 1A has hardened input handling. It does **not** introduce new abstractions, validation refactors, or request-shape changes.

**Canonical reference**: `Phase_Wise_Refactor_Plan.md` → Phase 1B (adapted per scope definition rules below).

**Execution**: Fix-First, Single-Deploy. Phase 1B work completed and verified locally; deployment occurs only after all phase sign-offs.

---

## What Phase 1B Exists to Fix

- **Structural duplication**: IES and IIES attachment models implement nearly identical logic (normalize files, validate type/size, store) but write to different structures — IES uses legacy single-file columns; IIES uses `project_IIES_attachment_files`.
- **Migration debt**: Phase 0.2 fixed the array-to-scalar crash in IES; the `project_IES_attachment_files` table and `ProjectIESAttachmentFile` model exist but IES still writes only to legacy columns.
- **Cross-module inconsistency**: IES controller uses `LogHelper::logSafeRequest()`; IIESAttachmentsController does not; same pattern, different behavior.

---

## What Phase 1A Intentionally Did NOT Fix

- IES/IIES attachment unification (deferred to Phase 1B.1).
- Migration of IES to use `project_IES_attachment_files` (Phase 1B.1).
- Form field collisions (deferred; see OUT-OF-SCOPE).
- ProjectController orchestration, reports, exports (Phase 2).

---

## Why Phase 1B Must Happen Before Phase 2

1. **Phase 2.2 (ProjectAttachmentHandler)** depends on a consistent attachment model. IES and IIES must use the same storage pattern (`*_attachment_files` tables) before a shared handler can be designed.
2. **Phase 2.4 (FormSection/ownedKeys)** assumes sub-controllers own explicit keys. Field collisions are a separate concern; Phase 1B completes attachment structural cleanup first.
3. **Deferred prerequisites**: The Refactor Plan explicitly states that legacy attachment columns may not be dropped until Phase 1B.1 is complete and read paths use `getFilesForField()`.

---

## Phase 1B Candidates (Identified)

| Category | Item | Location | Notes |
|----------|------|----------|-------|
| **Structural duplication** | `handleAttachments` logic | `ProjectIESAttachments`, `ProjectIIESAttachments` | Both: normalize to array, `isValidFileType`, config-based validation, storage. IES writes to legacy columns; IIES writes to `project_IIES_attachment_files`. |
| **Legacy pattern** | IES writes to legacy columns | `ProjectIESAttachments::handleAttachments` | `$attachments->{$field} = $filePath`; does not use `ProjectIESAttachmentFile`. |
| **Cross-module inconsistency** | LogHelper usage | `IESAttachmentsController` vs `IIESAttachmentsController` | IES uses `LogHelper::logSafeRequest()` on update; IIES does not. |
| **Model structure** | `project_IES_attachment_files` | Exists but unused for writes | Table, model, `getFilesForField()` exist; `handleAttachments` does not populate it. |

**Excluded from Phase 1B** (per scope rules): IAH, ILP attachment handling — different modules; Phase 2.2 will unify. ReportController attachment logic — deferred to Phase 2.

---

## IN-SCOPE

| ID | Item | Description |
|----|------|-------------|
| **1B.1** | IES/IIES attachment unification | Migrate IES to write to `project_IES_attachment_files`; align storage pattern with IIES. Shared `normalizeFiles`/`validateFile` pattern within models (no new service/trait required). Read paths must use `getFilesForField()`. |
| **1B.1a** | Legacy column handling | Preserve backward compatibility: read from `getFilesForField()` with fallback to legacy columns during transition; do not drop legacy columns in Phase 1B. |
| **1B.1b** | LogHelper consistency | Align IESAttachmentsController and IIESAttachmentsController — either both use `LogHelper::logSafeRequest()` for update or both use standard `Log::info()`; remove inconsistency. |

---

## OUT-OF-SCOPE

| Item | Reason |
|------|--------|
| **Form field namespacing** | Request-shape change. Transforming `family_contribution` → `ies_education[family_contribution]` alters input structure. Per scope definition rules: no request-shape changes. |
| **Validation refactors** | New validation rules, FormRequest changes, or rule reorganization. |
| **New abstractions for long-term enforcement** | FormDataExtractor, ProjectAttachmentHandler, FormSection, etc. — Phase 2. |
| **IAH / ILP attachment handling** | Different modules; Phase 2.2 scope. |
| **ProjectController orchestration** | Phase 2. |
| **Report / Export flows** | Phase 2. |
| **Database schema changes** | Beyond migration of IES write path to `project_IES_attachment_files`; no column drops. |

---

## Risks

| Risk | Mitigation |
|------|------------|
| **Existing IES attachments in legacy columns** | Read path must support both legacy columns and `project_IES_attachment_files`; migration script for existing data may be required. |
| **Blade views reading legacy columns** | Audit all IES attachment views; ensure they use `getFilesForField()` or equivalent. |
| **Regression in IIES** | IIES logic unchanged structurally; only IES model updated. Verify IIES flows after 1B.1. |

---

## Exit Criteria

- [ ] IES writes new uploads to `project_IES_attachment_files` (not legacy columns).
- [ ] IES read path uses `getFilesForField()` with fallback for legacy data.
- [ ] Shared validate/normalize pattern aligned between IES and IIES (no new abstraction).
- [ ] IESAttachmentsController and IIESAttachmentsController use consistent logging (LogHelper or standard Log).
- [ ] Local verification: IES and IIES attachment create/edit/delete flows succeed.
- [ ] No new errors in `storage/logs/laravel.log` during exercised flows.
- [ ] Each Phase 1B item has an implementation MD in `Implementations/Phase_1B/`.

---

## Implementation Documentation

Store one MD file per completed Phase 1B item:

- `IES_IIES_Attachment_Unification.md` (covers 1B.1, 1B.1a)
- `IES_IIES_LogHelper_Consistency.md` (covers 1B.1b)

**Do not start Phase 1B implementation until Phase 1A Final Sign-off exists and Phase 1B scope is confirmed.**

---

*Phase 1B scope locked — 2026-02-08*
