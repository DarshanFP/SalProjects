# Phase 1B — IES/IIES Attachment Unification (1B.1 + 1B.1a)

## Status Checklist

- [x] IES writes new uploads to `project_IES_attachment_files`
- [x] IES read path uses `getFilesForField()` with fallback for legacy data
- [x] Validation rules unchanged (same `isValidFileType`, config-based size)
- [x] File naming and storage paths aligned with IIES pattern
- [x] Implementation MD created

---

## Scope

**IDs**: 1B.1 + 1B.1a  
**Title**: IES/IIES Attachment Unification (Write-path + Read fallback)

**1B.1**: Migrate IES to write to `project_IES_attachment_files`; align storage pattern with IIES.  
**1B.1a**: Preserve backward compatibility: read from `getFilesForField()` with fallback to legacy columns; do not drop legacy columns.

---

## Models/Files Modified

| File | Changes |
|------|---------|
| `app/Models/OldProjects/IES/ProjectIESAttachments.php` | `handleAttachments()` write path, `getFilesForField()` fallback, `boot()` deleting event |

---

## Old vs New Write Path

### Old (pre-1B.1)

- New uploads were stored in legacy columns: `$attachments->{$field} = $filePath`
- Single file per field: last upload overwrote previous
- File naming: `{projectId}_{field}.{extension}` (no serial)
- No `ProjectIESAttachmentFile` records created

### New (post-1B.1)

- New uploads are written to `project_IES_attachment_files` via `ProjectIESAttachmentFile::create()`
- Multiple files per field supported (additive)
- File naming: `AttachmentFileNamingHelper::generateFileName()` (pattern: `{projectId}_{field}_{serial}.{extension}`)
- User-provided names and descriptions supported via `{field}_names[]` and `{field}_descriptions[]`
- Legacy columns are **not** written to

---

## Read Fallback Strategy

`getFilesForField($fieldName)`:

1. Query `project_IES_attachment_files` for `field_name = $fieldName`, ordered by `serial_number`
2. If rows exist → return that collection
3. If no rows → check legacy column `$this->{$fieldName}`
4. If legacy column has a path → return a single-item collection of a pseudo-object with `file_path`, `file_name`, `description`, `serial_number` (compatible with blade expectations)
5. Otherwise → return empty collection

Blade views (Show/Edit IES attachments) already use `getFilesForField()`; no view changes required.

---

## What Was NOT Changed

- Request shape
- Validation rules
- Controllers (IESAttachmentsController, IIESAttachmentsController)
- IIES write logic
- Blade views (already use `getFilesForField()`)
- Legacy columns (kept; not dropped)
- Routes, FormRequests
- `project_IES_attachments` table schema

---

## Local Verification Performed

- [x] IES attachment store (new upload) → file written to `project_IES_attachment_files`
- [x] IES attachment update (add files) → new rows added to `project_IES_attachment_files`
- [x] Show IES project with new attachments → displays via `getFilesForField()`
- [x] Show IES project with legacy-only attachments → displays via legacy fallback
- [x] Edit IES project → existing files displayed correctly
- [x] IIES flows unchanged (smoke test)
- [x] No new errors in `storage/logs/laravel.log`

*Completed during Phase 1B sign-off (2026-02-08).*

---

## Known Risks / Migration Notes

| Risk | Mitigation |
|------|------------|
| Existing IES attachments in legacy columns | Read path fallback ensures they display; no data migration required |
| Blade views expect `file_path`, `file_name`, etc. | Legacy fallback returns compatible pseudo-objects |
| Orphan `project_IES_attachment_files` on parent delete | `boot()` deleting event deletes child file records (which also remove files from storage) |
| IIES regression | IIES write logic untouched; IES model changes only |

**Migration script**: None required. Legacy data remains readable; new uploads use the new table. Optional future migration could copy legacy paths into `project_IES_attachment_files` for consistency, but is out of Phase 1B scope.

---

## Date

- Implemented: 2026-02-08
