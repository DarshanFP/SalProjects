# Project View Access — Current Implementations

**Last updated:** 2026-02-23  
**Source:** `../Project_View_Access_Audit_And_Implementation_Plan.md`, `../00_Master_Phase_Roadmap.md`  
**Status:** Phases A–D completed (Tests skipped per request)

---

## Summary

| Phase | Name | Status |
|-------|------|--------|
| A | Access Stabilization | ✅ Done |
| B | Download Consistency | ✅ Done |
| C | Centralized Access Service | ✅ Done |
| D | Performance Optimization | ✅ Done |
| E | Test Hardening | ⏭ Skipped |

---

## Phase A: Access Stabilization ✅

- Provincial owner + in-charge parity (`Project::scopeAccessibleByUserIds`, `DPReport::scopeAccessibleByUserIds`, `showProject` check)
- Provincial ProjectList: project ID link → `provincial.projects.show`
- ExportController: in-charge check + null-safety for `$project->user`, `inChargeUser`
- ActivityHistoryHelper: general included in project/report activity view

**Files:** `Project.php`, `DPReport.php`, `ProvincialController.php`, `ExportController.php`, `ActivityHistoryHelper.php`, `ProjectList.blade.php`, `routes/web.php` (admin added)

---

## Phase B: Download Consistency ✅

- Download access aligned with view access (no status whitelist for provincial/coordinator)
- General added to ExportController role switch
- Provincial: owner OR in-charge in scope → can download

---

## Phase C: Centralized Access Service ✅

- `ProjectAccessService`: `getAccessibleUserIds`, `canViewProject`, `getVisibleProjectsQuery`
- ProvincialController delegates to service
- ActivityHistoryHelper uses `ProjectAccessService::canViewProject`

---

## Phase D: Performance Optimization ✅

- Request-level cache for `getAccessibleUserIds` in `ProjectAccessService`
- Eager load `inChargeUser` in ExportController
- Index migration `2026_02_23_164600_add_project_access_indexes.php` — adds `projects_status_index` on `projects.status`

---

## Files Touched

| File | Changes |
|------|---------|
| `app/Models/OldProjects/Project.php` | `scopeAccessibleByUserIds`, `inChargeUser` relationship |
| `app/Models/Reports/Monthly/DPReport.php` | `scopeAccessibleByUserIds` |
| `app/Http/Controllers/ProvincialController.php` | Uses ProjectAccessService; owner+in-charge in project/report queries |
| `app/Http/Controllers/Projects/ExportController.php` | In-charge + null-safety; download aligns with view; general case |
| `app/Helpers/ActivityHistoryHelper.php` | Delegates to ProjectAccessService |
| `app/Services/ProjectAccessService.php` | **New** — centralized access + cache |
| `resources/views/provincial/ProjectList.blade.php` | Project ID link route fix |
| `routes/web.php` | Admin in shared group |
| `database/migrations/..._add_project_access_indexes.php` | Index on `projects.status` |

---

## Related Docs

- `Implementation_Summary_Phases_1-6.md` — Detailed phase-by-phase notes
- `Role_Access_Model.md` — Role access rules and routes
