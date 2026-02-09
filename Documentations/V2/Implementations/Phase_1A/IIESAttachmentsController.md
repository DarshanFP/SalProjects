# Phase 1A — IIESAttachmentsController

## Status
- [x] Refactored
- [ ] Merged
- [ ] Verified

## Reference
- Playbook: Phase_1A_Refactor_Playbook.md (via Phase_Wise_Implementation_Guide.md)
- Pattern Lock: PATTERN_LOCK.md
- Canonical: IESAttachmentsController.md — attachment-only reference; exception to Pattern A/B

## Controller
- **Class**: `App\Http\Controllers\Projects\IIES\IIESAttachmentsController`
- **File**: `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php`
- **Methods refactored**: `store()`, `update()`

## Anti-patterns Found
- `$validated = $request->all()` in `store()` — dead code; variable never used
- `$validated = $request->all()` in `update()` — dead code; variable never used
- Both methods delegate to `ProjectIIESAttachments::handleAttachments($request, $projectId)` which requires the full `$request` for file handling

## Pattern Classification

**Attachment-only** (matches IESAttachmentsController canonical) — **Not Hybrid**.

- **Rationale**: Controller does not fill any scalar fields; it does not call `fill()` on any model. All input handling occurs inside `ProjectIIESAttachments::handleAttachments()`, which uses `$request->file()` and `$request->input()` for specific keys (e.g. `{$field}_names`, `{$field}_descriptions`). No scalar metadata is persisted by the controller. No `$request->only()` or `ArrayToScalarNormalizer` is appropriate — the controller passes `$request` through to the model for file uploads.

## Refactor Applied
- Removed dead `$validated = $request->all()` from `store()` and `update()`
- No `$request->only()` or `ArrayToScalarNormalizer` — controller does not fill models; it passes `$request` to `handleAttachments()` for file uploads
- File handling unchanged (Phase 0 fixed array handling in `ProjectIIESAttachments` model)

## Scalar vs Array Handling
- **Scalar**: N/A — controller performs no scalar fill
- **Array (files)**: Handled by `ProjectIIESAttachments::handleAttachments()`; model uses `$request->file()` and `$request->input()` for file arrays and optional metadata; Phase 0 handles array normalization

## Files Modified
- `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- Attachments, file handling — `handleAttachments()` unchanged
- Database schema
- `show()`, `edit()`, `destroy()`, `downloadFile()`, `viewFile()`
- Transaction, logging, and JSON responses
- Passing `$request` to `handleAttachments()` — model needs it for `$request->file()` and `$request->input()`

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: IIES attachment upload (store/update) works
- [ ] Log: no new errors for IIES project type
- [ ] File uploads with optional names/descriptions still work

## Notes / Risks
- Minimal refactor: removed dead code only. Attachment logic lives in `ProjectIIESAttachments::handleAttachments()` (Phase 0 fixed array handling there).
- **File uploads**: No change to upload flow; model continues to receive full `$request` for `$request->file()` and `$request->input()`.

## Date
- Refactored: 2026-02-07
- Verified: (pending)
