# Phase 2.2 — ProjectAttachmentHandler Adoption Tracker

## Purpose

This file tracks incremental adoption of `ProjectAttachmentHandler` across attachment controllers. Phase 2.2 moves file validation, storage, and file-record creation from model-level `handleAttachments` / `handleDocuments` into the centralized handler. Adoption is controller-by-controller; behavior must remain identical.

---

## Eligibility Criteria

**Eligible:** Controllers that:

- Currently handle file uploads directly
- Use `request()->file()` or manual validation/storage
- Are not report/export controllers

**Excluded:**

- Report / Monthly / Quarterly attachment controllers
- ExportController
- Controllers that do not handle file uploads

---

## Adoption Status Table

| Controller                | Project Type | Current Pattern       | Adopted (2.2) | Verified | Notes |
| ------------------------- | ------------ | --------------------- | ------------- | -------- | ----- |
| IESAttachmentsController  | IES          | Inline model handling | ✅            | ✅       | Pilot |
| IIESAttachmentsController | IIES         | Inline model handling | ✅            | ✅       |       |
| IAHAttachmentsController  | IAH          | ProjectAttachmentHandler | ✅            | ✅       | IAHDocumentsController |
| ILPAttachmentsController  | ILP          | ProjectAttachmentHandler | ✅            | ✅       | AttachedDocumentsController |

---

## Rules

- **One controller at a time.** Do not bulk-update.
- **Pilot first.** IESAttachmentsController is the designated pilot. Complete and verify before proceeding.
- **No behavior change.** Handler must produce identical storage paths, file names, and DB records as current model logic.
- **Legacy fallback must remain intact.** Models retain `getFilesForField()` and read fallback; handler does not touch read path.

---

## How to Update This Tracker

- **Adopted** can be set to ✅ only after the code change (controller/model delegates to `ProjectAttachmentHandler`) is committed.
- **Verified** can be set to ✅ only after local verification (store/update flows exercised, no new log errors, attachment metadata unchanged).
- Do not mark Verified before Adopted.
- When in doubt about eligibility, list the controller with a note rather than guessing.

---

## Explicit Non-Scope

- This tracker does **not** authorize Phase 2.1 (FormDataExtractor) or any other Phase 2 component.
- Completion of adoption across all eligible controllers does **not** imply Phase 2 sign-off.
- Deployment remains forbidden until `PHASE_2_SIGNOFF.md` is approved and all phase gates are met.

---

## Phase 2.2 Status — COMPLETE

All eligible module-specific attachment controllers have been migrated to `ProjectAttachmentHandler`.

Migrated modules:
- IES
- IIES
- IAH
- ILP

Architectural inspection confirms:
- No additional eligible controllers remain.
- Generic and report attachments are explicitly out of Phase 2.2 scope.
- CCI and IGE contain no attachment flows.

Phase 2.2 is closed.

Date: 2026-02-09

---
