# Phase 1A — IESAttachmentsController

## Status
- [x] Refactored
- [ ] Merged
- [ ] Verified

## Reference
- Playbook: Phase_1A_Refactor_Playbook.md
- Plan: Phase_Wise_Refactor_Plan.md → Phase 1A.3
- Inventory: Phase_1A/README.md
- Pattern Lock: PATTERN_LOCK.md — attachment-only reference; exception to Pattern A/B

## Controller
- **Class**: `App\Http\Controllers\Projects\IES\IESAttachmentsController`
- **File**: `app/Http/Controllers/Projects/IES/IESAttachmentsController.php`
- **Methods refactored**: `store()`, `update()`

## Anti-patterns Found
- `$validated = $request->all()` in `store()` — dead code; variable never used
- `$validated = $request->all()` in `update()` — dead code; variable never used
- Both methods delegate to `ProjectIESAttachments::handleAttachments($request, $projectId)` which requires the full `$request` for file handling

## Refactor Applied
- Removed dead `$validated = $request->all()` from `store()` and `update()`
- No `$request->only()` or `ArrayToScalarNormalizer` — controller does not fill models; it passes `$request` to `handleAttachments()` for file uploads
- File handling unchanged (Phase 0 fixed array handling in `ProjectIESAttachments` model)

## Fillable Keys Used
N/A — controller does not perform fill(); model handles attachments via `$request->file()`.

## Excluded Keys
N/A

## Files Modified
- `app/Http/Controllers/Projects/IES/IESAttachmentsController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- Attachments, file handling — `handleAttachments()` unchanged
- Database schema
- `show()`, `edit()`, `destroy()`
- Transaction, logging, and JSON responses
- Passing `$request` to `handleAttachments()` — model needs it for `$request->file()`

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: IES attachment upload (store/update) works
- [ ] Log: no new errors for IES project type

## Notes / Risks
- Minimal refactor: removed dead code only. Attachment logic lives in `ProjectIESAttachments::handleAttachments()` (Phase 0 fixed array handling there).

## Date
- Refactored: 2026-02-07
- Verified: (pending)
