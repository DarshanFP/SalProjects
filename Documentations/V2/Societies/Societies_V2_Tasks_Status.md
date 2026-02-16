# Societies V2 — Tasks Completed and Still Needed

**Generated:** 2026-02-13  
**Source:** Surgical code scan of codebase against `Documentations/V2/Societies` MD files.

---

## Summary

| Category | Completed | Still Needed |
|----------|-----------|--------------|
| Phase 0 — Audit Command | ✅ | — |
| Phase 1 — Address Column | ✅ | — |
| Societies Schema (Global/Nullable) | Partial | unique(name), drop composite |
| Users Province Normalization | Partial | Backfill run, NOT NULL enforcement |
| Society→Project Mapping | — | Full implementation |
| Own/Disown & visibleToUser | — | Not implemented |

---

## 1. Phase 0 — Audit Command

**Reference:** `Phase0_Audit_Command_Revision5.md`

### ✅ Completed

| Item | Location | Status |
|------|----------|--------|
| `societies:audit` command | `app/Console/Commands/SocietiesAuditCommand.php` | Implemented |
| All 10 checks (duplicate names, projects without user, society_name resolution, provinces, users province, etc.) | Lines 39–242 | Implemented |
| Dry-run summary (counts only) | Lines 246–274 | Implemented |
| Exit codes: FAIL→1, WARNING→0, PASS→0 | Lines 278–294 | Implemented |
| READ-ONLY; no schema or data changes | Confirmed | Correct |

---

## 2. Phase 1 — Address Column on Societies

**Reference:** `Phase1_Address_Feasibility_Audit.md`, `Societies_CRUD_And_Access_Audit.md` §6

### ✅ Completed

| Item | Location | Status |
|------|----------|--------|
| Migration: add `address` (nullable text) | `database/migrations/2026_02_10_160000_add_address_to_societies_table.php` | Done |
| Society model: `address` in `$fillable` | `app/Models/Society.php` | Done |
| GeneralController: validation + store/update | `GeneralController.php` (storeSociety, updateSociety) | Done |
| ProvincialController: validation + store/update | `ProvincialController.php` (storeSociety, updateSociety) | Done |
| General views: create, edit, index | `resources/views/general/societies/create.blade.php`, `edit.blade.php`, `index.blade.php` | Done |
| Provincial views: create, edit | `resources/views/provincial/societies/create.blade.php`, `edit.blade.php` | Done |

### ⚠️ Minor discrepancy

| Item | Phase1 doc says | Code has |
|------|-----------------|----------|
| Max length | `max:2000` | GeneralController: `max:255`; ProvincialController: `max:2000` |
| Migration type | `string` or `text` | `text` (correct for long addresses) |

**Recommendation:** Align GeneralController `address` validation to `max:2000` for consistency with Phase1 spec.

---

## 3. Societies Schema — Global / Province-Nullable

**Reference:** `Society_Project_Mapping_PhasePlan_V2.md` §9.1, §8

### ✅ Completed

| Item | Location | Status |
|------|----------|--------|
| Migration: `province_id` nullable | `database/migrations/2026_02_10_235454_make_societies_province_id_nullable.php` | Done |
| Society model: `province_id` nullable | Model has no explicit NOT NULL; Eloquent allows null | OK |
| Seed canonical societies command | `app/Console/Commands/SeedCanonicalSocietiesCommand.php` | Done (8 names; excludes "MISSIONARY SISTERS OF ST. ANN") |

### ❌ Still Needed

| Item | Reference | Notes |
|------|-----------|------|
| Drop composite unique `(province_id, name)` | PhasePlan §9.1 step 2 | Migration `make_societies_province_id_nullable` does **not** drop `unique_province_society` |
| Add unique index on `name` | PhasePlan §9.1 step 3 | Required for global uniqueness; backfill resolves by name only |
| Add scope `scopeGlobal($query)` | PhasePlan §8.1 | `Society::whereNull('province_id')` for global societies |
| General societies index: null-safe province display | — | `{{ $society->province->name }}` will error when `province_id` is NULL (e.g. global societies) |

**Fix for index:** Use `{{ $society->province?->name ?? 'Global' }}` (or equivalent) in `resources/views/general/societies/index.blade.php` line 76.

---

## 4. Users Province Normalization

**Reference:** `Society_Project_Mapping_PhasePlan_V2.md` §9.2

### ✅ Completed

| Item | Location | Status |
|------|----------|--------|
| Migration: add `users.province_id` (nullable, FK, indexed) | `database/migrations/2026_02_10_232014_add_province_id_to_users_table.php` | Done |
| Backfill command | `app/Console/Commands/UsersProvinceBackfillCommand.php` | Done |
| User model: `provinceRelation()` | `app/Models/User.php` | Uses `province_id` |

### ❌ Still Needed

| Item | Reference | Notes |
|------|-----------|-------|
| Run `users:province-backfill` | PhasePlan §9.2 step 2 | Must be run after migration |
| Verification: no users with NULL province_id | PhasePlan §9.2 step 3–4 | Gate before projects.province_id backfill |
| Migration: make `users.province_id` NOT NULL | PhasePlan §9.2 step 5 | After verification passes |

---

## 5. Society→Project Mapping (Phase 2+)

**Reference:** `Society_Project_Mapping_PhasePlan_V2.md`, `Society_Project_Mapping_Feasibility_V1.md`, `Society_Project_Mapping_Implementation_Summary.md`

### ❌ Not Started

| Item | Reference | Notes |
|------|-----------|-------|
| Migration: add `projects.province_id` (nullable, FK, indexed) | PhasePlan §9.3 | Backfill from `project.user->province_id` |
| Migration: add `projects.society_id` (nullable, FK, indexed) | PhasePlan §9.3 | FK to societies.id, onDelete('set null') |
| Migration: add composite index `(province_id, society_id)` on projects | PhasePlan §9.5 | For reporting/analytics |
| Migration: add `users.society_id` (nullable, FK) | PhasePlan §9.4 | Replace society_name over time |
| Backfill: projects.province_id | PhasePlan §10.1 | From user.province_id |
| Backfill: projects.society_id | PhasePlan §10.1 | Normalize typo, find by name |
| Backfill: users.society_id | PhasePlan §10.2 | Normalize typo, find by name |
| Make `projects.province_id` NOT NULL | PhasePlan §9.3 step 4 | After backfill verified |

---

## 6. Controllers, Validation, Views — Society ID

**Reference:** `Society_Project_Mapping_Feasibility_V1.md` §7–9, PhasePlan §11–12

### Current State (society_name only)

| Location | Uses | Status |
|----------|------|--------|
| `projects/partials/general_info.blade.php` | Hardcoded 9 options, `society_name` | ❌ Typo in option 4: "ST. ANNS'S" |
| `projects/partials/Edit/general_info.blade.php` | Hardcoded options, `society_name` | ❌ Same typo |
| `StoreProjectRequest`, `UpdateProjectRequest` | `society_name` nullable string | — |
| `StoreGeneralInfoRequest`, `UpdateGeneralInfoRequest` | `society_name` nullable string | — |
| `Project` model | `society_name` in fillable | — |
| `User` model | `society_name` | — |
| `ExportController` | `$project->society_name` | — |
| Report models | `society_name` | Denormalized; keep as snapshot |

### ❌ Still Needed (Dual-Write / Read-Switch)

| Item | Notes |
|------|-------|
| Add `society_id` to Project Requests (Store/Update/GeneralInfo) | `nullable|exists:societies,id`; provincial visibility rule |
| Add `society_id` to User create/update (General & Provincial) | Executor: required; Provincial user: nullable |
| Project controller: accept and save `society_id`; dual-write `society_name` from relation | PhasePlan §11.1 |
| Replace hardcoded dropdowns with dynamic `Society::visibleToUser()` (or province-scoped) | PhasePlan §5.1–5.2 |
| Project create/edit: `name="society_id"`, `value="{{ $society->id }}"` | — |
| Executor/Applicant create/edit: society_id from `$societies` | General + Provincial views |
| ExportController: `$project->society->name ?? $project->society_name` | Fallback during transition |
| Add Society `visibleToUser($user)` scope | PhasePlan §5.1 |

---

## 7. Own/Disown & Provincial Society Visibility

**Reference:** PhasePlan §4, §5, §6

### ❌ Not Implemented

| Item | Reference | Notes |
|------|-----------|-------|
| `Society::scopeVisibleToUser($user)` | PhasePlan §5.1 | `province_id = user.province_id OR province_id IS NULL` |
| Routes: `POST /provincial/society/{id}/own` | PhasePlan §4.5 | ProvincialController@ownSociety |
| Routes: `POST /provincial/society/{id}/disown` | PhasePlan §4.5 | ProvincialController@disownSociety |
| Provincial societies index: Own/Disown buttons | PhasePlan §4.8 | Own when global; Disown when own province |
| Provincial list: filter to `visibleToUser` | PhasePlan §5.1 | Currently shows all societies in province |
| General society edit: allow setting province (including null for global) | PhasePlan §4.8 | — |
| Validation: `unique:societies,name` on create/update | PhasePlan §6.1 | After unique(name) migration |

---

## 8. Cleanup (Post Dual-Write)

**Reference:** PhasePlan §13

### ❌ Not Started

| Item | When |
|------|------|
| Migration: drop `projects.society_name` | After read-switch verified |
| Migration: drop `users.society_name` | After read-switch verified |
| Remove fallback `?? $project->society_name` in code | — |
| Optional: drop `users.province` (string) | When province_id fully adopted |

---

## 9. SeedCanonicalSocietiesCommand Discrepancy

**Reference:** PhasePlan §8.2, Feasibility §3.1

| Doc | Canonical Names |
|-----|-----------------|
| PhasePlan §8.2 | 9 names including "MISSIONARY SISTERS OF ST. ANN" |
| SeedCanonicalSocietiesCommand | 8 names; **excludes** "MISSIONARY SISTERS OF ST. ANN" |

**Recommendation:** Add "MISSIONARY SISTERS OF ST. ANN" to `CANONICAL_NAMES` if it should be a global society per Phase Plan.

---

## 10. Recommended Execution Order

1. **Fix societies schema:** Create migration to drop `unique_province_society`, add `unique(name)`.
2. **Fix General societies index:** Use `$society->province?->name ?? 'Global'` for null-safe display.
3. **Align address validation:** GeneralController `address` → `max:2000`.
4. **Run users:province-backfill** (after migrations).
5. **Verify users:** No NULL province_id; then add migration to make `users.province_id` NOT NULL.
6. **Add projects.province_id and society_id** migrations; backfill; verify; make province_id NOT NULL.
7. **Add users.society_id** migration; backfill.
8. **Implement dual-write** in controllers and requests.
9. **Replace hardcoded society dropdowns** with dynamic `Society::visibleToUser()` and `society_id`.
10. **Implement Own/Disown** routes and UI for provincial.
11. **Read-switch** (display/export from relation with fallback).
12. **Cleanup:** Drop society_name columns after verification.

---

## 11. File Index (Quick Reference)

| Area | Path |
|------|------|
| Societies audit | `app/Console/Commands/SocietiesAuditCommand.php` |
| Users province backfill | `app/Console/Commands/UsersProvinceBackfillCommand.php` |
| Seed canonical societies | `app/Console/Commands/SeedCanonicalSocietiesCommand.php` |
| Society model | `app/Models/Society.php` |
| General societies | `app/Http/Controllers/GeneralController.php` (storeSociety, updateSociety, etc.) |
| Provincial societies | `app/Http/Controllers/ProvincialController.php` |
| Project general info | `app/Http/Controllers/Projects/GeneralInfoController.php` |
| Project requests | `app/Http/Requests/Projects/StoreGeneralInfoRequest.php`, `UpdateGeneralInfoRequest.php`, etc. |
| Society migrations | `database/migrations/2026_02_10_*` |
| Society views (General) | `resources/views/general/societies/` |
| Society views (Provincial) | `resources/views/provincial/societies/` |
| Project general_info partials | `resources/views/projects/partials/general_info.blade.php`, `Edit/general_info.blade.php` |

---

*Generated from codebase scan against Documentations/V2/Societies/*.md. Last updated: 2026-02-13.*
