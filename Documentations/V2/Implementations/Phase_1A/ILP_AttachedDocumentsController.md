# Phase 1A — ILP AttachedDocumentsController

## Status
- [x] Refactored
- [ ] Merged
- [ ] Verified

## Reference
- Playbook: Phase_1A_Refactor_Playbook.md
- Plan: Phase_Wise_Refactor_Plan.md → Phase 1A.4
- Pattern Lock: PATTERN_LOCK.md — attachment-only reference; exception to Pattern A/B
- Canonical: IESAttachmentsController.md (Attachment-only)

## Controller
- **Class**: `App\Http\Controllers\Projects\ILP\AttachedDocumentsController`
- **File**: `app/Http/Controllers/Projects/ILP/AttachedDocumentsController.php`
- **Methods refactored**: `store()`, `update()`

## Anti-patterns Found
- `$validated = $request->all()` in `store()` — dead code; variable never used
- `$validated = $request->all()` in `update()` — dead code; variable never used
- Both methods delegate to `ProjectILPAttachedDocuments::handleDocuments($request, $projectId)` which requires the full `$request` for file handling

## Refactor Applied
- Removed dead `$validated = $request->all()` from `store()` and `update()`
- No `$request->only()` or `ArrayToScalarNormalizer` — controller does not fill models; it passes `$request` to `handleDocuments()` for file uploads
- File handling unchanged

## Fillable Keys Used
N/A — controller does not perform fill(); model handles documents via `$request->file()`.

## Excluded Keys
N/A

## Files Modified
- `app/Http/Controllers/Projects/ILP/AttachedDocumentsController.php`

## What Was NOT Changed
- Forms, routes, validation rules
- Attachments, file handling — `handleDocuments()` unchanged
- Database schema
- `show()`, `edit()`, `destroy()`
- Transaction, logging, and JSON responses
- Passing `$request` to `handleDocuments()` — model needs it for `$request->file()`

## Verification Checklist
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: ILP attachment upload (store/update) works
- [ ] Log: no new errors for ILP project type

## Notes / Risks
- Minimal refactor: removed dead code only. Attachment logic lives in `ProjectILPAttachedDocuments::handleDocuments()`.

## Date
- Refactored: 2026-02-08
- Verified: (pending)
