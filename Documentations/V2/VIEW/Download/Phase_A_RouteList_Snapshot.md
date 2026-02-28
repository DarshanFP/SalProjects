# Phase A — Route List Snapshot

**Rule: Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.**

---

**Date:** 2026-02-23  
**Environment:** local  
**Git branch:** master  
**Laravel version:** 10.48.16  

---

## Full Route List Output

*(Laravel 10 does not support `--columns`; full default output captured.)*

Total routes: 362. Full raw output available via: `php artisan route:list`

## Project Attachment Routes (Filtered)

```
  GET|HEAD        projects/attachments/download/{id} projects.attachments.download
  DELETE          projects/attachments/files/{id} projects.attachments.files.destroy
  GET|HEAD        projects/attachments/view/{id} projects.attachments.view
  GET|HEAD        projects/iah/documents/download/{fileId} projects.iah.documents.download
  DELETE          projects/iah/documents/files/{fileId} projects.iah.documents.files.destroy
  GET|HEAD        projects/iah/documents/view/{fileId} projects.iah.documents.view
  GET|HEAD        projects/ies/attachments/download/{fileId} projects.ies.attachments.download
  DELETE          projects/ies/attachments/files/{fileId} projects.ies.attachments.files.destroy
  GET|HEAD        projects/ies/attachments/view/{fileId} projects.ies.attachments.view
  GET|HEAD        projects/iies/attachments/download/{fileId} projects.iies.attachments.download
  DELETE          projects/iies/attachments/files/{fileId} projects.iies.attachments.files.destroy
  GET|HEAD        projects/iies/attachments/view/{fileId} projects.iies.attachments.view
  GET|HEAD        projects/ilp/documents/download/{fileId} projects.ilp.documents.download
  DELETE          projects/ilp/documents/files/{fileId} projects.ilp.documents.files.destroy
  GET|HEAD        projects/ilp/documents/view/{fileId} projects.ilp.documents.view
```

All 15 project attachment routes are registered. See Phase_A_Implementation_Summary.md for middleware and nesting analysis.
