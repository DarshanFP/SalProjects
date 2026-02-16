# Societies Mapping — Architecture Verification Audit Report

**Scope:** Phase Plan Revision 5 (Society–Project Mapping).  
**Rules:** No files modified; inspection and reporting only.

---

## STEP 1 — Database Schema Verification

### 1. Societies table

| Check | Result | Evidence |
|-------|--------|----------|
| **id** | Yes | `2026_01_13_144931_create_societies_table.php` — `$table->id()` |
| **name** | Yes | Same migration — `$table->string('name')` |
| **province_id nullable?** | Yes | `2026_02_10_235454_make_societies_province_id_nullable.php` — province_id made nullable, FK re-added with `onDelete('restrict')` |
| **unique(name)?** | Yes | `2026_02_13_161757_enforce_unique_name_on_societies.php` — drops `unique_province_society`, adds `unique('name', 'societies_name_unique')` |
| **NO composite unique(province_id, name)?** | Yes | Same migration drops `unique_province_society` |

**Note:** Migration order is correct: create (composite unique) → make province_id nullable → enforce unique(name).

---

### 2. Projects table

| Check | Result | Evidence |
|-------|--------|----------|
| **province_id?** | No | No migration adds `province_id` to `projects` |
| **society_id?** | No | No migration adds `society_id` to `projects` |
| **society_name still exists?** | Yes | `2024_07_20_085634_create_projects_table.php` — `$table->string('society_name')->nullable()` |
| **province_id nullable or NOT NULL?** | N/A | Column does not exist |

---

### 3. Users table

| Check | Result | Evidence |
|-------|--------|----------|
| **province_id?** | Yes | `2026_01_11_165558_add_province_center_foreign_keys_to_users_table.php` adds `province_id` (nullable). `2026_02_10_232014_add_province_id_to_users_table.php` adds it with guard if missing (restrict). |
| **society_id?** | No | No migration adds `society_id` to `users` |
| **Old province string column still exists?** | Yes | `2014_10_12_000000_create_users_table.php` — enum `province`; `2026_01_15_000000_change_province_to_string_in_users_table.php` — VARCHAR(255) nullable |
| **society_name still exists?** | Yes | Original users table — `$table->string('society_name')->nullable()` |

---

### 4. Foreign keys

| FK | Present? | onDelete | Evidence |
|----|----------|----------|----------|
| **projects.society_id → societies.id** | No | — | No such column |
| **users.society_id → societies.id** | No | — | No such column |
| **projects.province_id → provinces.id** | No | — | No such column |
| **users.province_id → provinces.id** | Yes | `set null` (165558) or `restrict` (232014) | 165558: `onDelete('set null')`; 232014: `onDelete('restrict')`. Whichever ran last for that column wins. |
| **Any onDelete('set null')?** | Yes (users) | users.province_id | In `2026_01_11_165558_add_province_center_foreign_keys_to_users_table.php` for `users.province_id` and `users.center_id`. Phase Plan says provinces cannot be deleted and no cascade; doc prefers `restrict`. |

---

### Schema state vs Phase Plan Revision 5 target

| Area | Phase Plan target | Current schema |
|------|-------------------|----------------|
| **Societies** | province_id nullable, unique(name), no composite unique, no cascade on province | Implemented (nullable, unique name, composite dropped, restrict). |
| **Projects** | province_id (required after backfill), society_id (nullable), keep society_name until cleanup | Not implemented: no province_id, no society_id; only society_name. |
| **Users** | province_id (normalized then NOT NULL), society_id (nullable), keep province string until cleanup | Partially implemented: province_id exists (nullable); no society_id; province string kept. |
| **FKs** | No cascade from provinces/societies; projects/users point to provinces/societies | projects: no FKs for province/society. users: province_id present; society_id missing. users.province_id may use set null (doc says restrict). |

---

## STEP 2 — Model Layer Verification

### 1. Relationships

| Relationship | Expected | Implemented | Evidence |
|--------------|----------|-------------|----------|
| Society belongsTo Province | Yes | Yes | `app/Models/Society.php` — `province()` BelongsTo Province |
| Project belongsTo Society | Yes | No | `app/Models/OldProjects/Project.php` — no `society()`; fillable has `society_name` only |
| Project belongsTo Province | Yes | No | No province_id or province relation on Project |
| User belongsTo Society | Yes | No | `app/Models/User.php` — no `society()`; docblock has `society_name` |
| User belongsTo Province | Yes | Yes | `provinceRelation()` BelongsTo Province via `province_id` |

### 2. Accessors still reading society_name

- **Project:** No accessor overriding society; code and views use `$project->society_name` directly.
- **User:** Same; `society_name` used directly (no accessor).

### 3. Global scopes enforcing visibility

- **Society::visibleToUser($user):** Not implemented. No `visibleToUser` scope or equivalent in `app/Models/Society.php`. Only `scopeActive` and `scopeByProvince` exist.

---

## STEP 3 — Controller & Validation Verification

### 1. Controllers: what they write

| Context | society_id only? | society_id + society_name (dual write)? | society_name only? | Evidence |
|---------|------------------|----------------------------------------|--------------------|----------|
| Project general info create/update | No | No | Yes | ProjectController, StoreGeneralInfoRequest, UpdateGeneralInfoRequest: validation and payload use `society_name` only. |
| User (executor/provincial) create/update | No | No | Yes | GeneralController, ProvincialController: validate and save `society_name` only. |
| Society CRUD (create/update) | — | — | — | GeneralController / ProvincialController: when creating/updating a **Society** record they set both `society_id` and `society_name` on the **Society** model (id + name). Project/User flows still use society_name only. |

So: project and user flows are **society_name only**. No dual write for projects or users.

### 2. Validation rules

- **Project:** `society_name` in StoreProjectRequest, UpdateProjectRequest, StoreGeneralInfoRequest, UpdateGeneralInfoRequest (`nullable|string|max:255`). No `society_id` rules.
- **Executor/Provincial:** `society_name` required or nullable; no `society_id` validation.

Validation is **not** updated to use `society_id` for projects or users.

### 3. Own / Disown

- **Routes:** No routes found for `own`, `disown`, `ownSociety`, `disownSociety` in `routes/`.
- **Controllers:** No Own/Disown actions.

Own/Disown is **not** implemented.

### 4. Dropdown filtering

- **Society CRUD index (General):** Uses full Society list (no visibility scope).
- **Society CRUD index (Provincial):** Uses Society list; no `visibleToUser` (global + own province) in code.
- **Project create/edit:** Hardcoded 9 options or static list; `name="society_name"`, value = string. Not filtered by global + province-owned; not using society_id.
- **Executor/Provincial create/edit:** Either hardcoded 9 options or `$societies` by province (e.g. `Society::where('province_id', ...)`). No “global + province-owned” pattern.

Dropdowns are **not** aligned with Phase Plan (global + province-owned via `visibleToUser`); they are hardcoded or province-only.

---

## STEP 4 — Backfill Evidence

| Item | Present? | Evidence |
|------|----------|----------|
| **Society_id backfill (projects/users)** | No | No Artisan command or migration that sets `projects.society_id` or `users.society_id`. |
| **Users province_id backfill** | Yes | `app/Console/Commands/UsersProvinceBackfillCommand.php` — `users:province-backfill`, sets `users.province_id` from `users.province` via `provinces.name`. |
| **Verification gate (users province_id)** | Yes | Command exits with FAILURE if any unmatched; warns not to proceed to projects.province_id backfill until all users have province_id. |
| **Projects province_id backfill** | No | No command or migration; projects table has no province_id column. |
| **Typo normalization** | No | No backfill or mapping for "ST. ANNS'S SOCIETY, VISAKHAPATNAM" → "ST. ANN'S SOCIETY, VISAKHAPATNAM" in project/user data. |
| **Canonical societies seed** | Yes | `SeedCanonicalSocietiesCommand` — `societies:seed-canonical`, firstOrCreate by name with province_id null (8 names; Phase Plan lists 9). |

---

## STEP 5 — Read Path Verification

| Location | Uses `$project->society->name` or `$user->society->name`? | Uses `society_name`? | Fallback (relation ?? society_name)? |
|----------|--------------------------------------------------------|----------------------|--------------------------------------|
| **projects/partials/Show/general_info.blade.php** | No | Yes | `{{ $project->society_name }}` |
| **ExportController (project DOC)** | No | Yes | `$project->society_name` |
| **Reports (monthly/quarterly/half-yearly/annual)** | No | Yes | Report services use `$project->society_name`; views use `$report->society_name` or `$user->society_name` |
| **Report export (monthly)** | No | Yes | `$report->society_name` |

Read path is **society_name only**; no relation-based read and no fallback to `society->name ?? society_name`.

---

## STEP 6 — Phase Completion Matrix

| Phase | Expected | Status | Evidence |
|-------|----------|--------|----------|
| **Societies unique(name)** | Yes | Done | Migration `2026_02_13_161757_enforce_unique_name_on_societies.php` |
| **Societies province_id nullable** | Yes | Done | Migration `2026_02_10_235454_make_societies_province_id_nullable.php` |
| **Users province_id column** | Yes | Done | Migrations 165558 / 232014 |
| **Users province normalization (backfill + verify)** | Yes | Partial | `users:province-backfill` exists and has verification gate; users.province_id still nullable (NOT NULL not enforced) |
| **Projects province_id** | Yes | Not done | No migration adds it |
| **Projects province_id NOT NULL** | Yes | Not done | Column missing |
| **society_id on projects/users** | Yes | Not done | No migrations add these columns |
| **society_id backfilled** | Yes | Not done | No backfill commands |
| **Dual write active** | Yes | Not done | Controllers use society_name only for project/user |
| **Read switch complete** | No (keep society_name until after dual-write) | Not done | Views and export use society_name only |
| **Own/Disown implemented** | Yes | Not done | No routes or controller logic |
| **visibleToUser / dropdown rules** | Yes | Not done | No scope; dropdowns hardcoded or province-only |
| **society_name fully deprecated** | Later phase | Not done | society_name still used everywhere |

---

## STEP 7 — Risk Assessment

- **Mid-transition?** Yes. Societies schema is aligned with Phase Plan (global unique name, nullable province_id). Projects and users are still string-based (society_name, no society_id/province_id on projects).
- **Partially migrated?** Yes. Society table and canonical seed + users province_id (and backfill command) are in place; project and user model/controller/views are not.
- **Silent data inconsistency?** Risk is moderate: society name is unique and province_id nullable, but projects/users do not reference societies by ID. New/changed society names in forms can still diverge from DB (typos, free text). No referential integrity for project/user → society.
- **Migration order violated?** Partially. Phase Plan order: (1) societies constraints, (2) users province_id full normalization (including NOT NULL), (3) projects province_id, then society_id, etc. Current state: (1) done, (2) column and backfill exist but NOT NULL not enforced, (3) and later steps not started. So “users fully normalized before projects” is not fully met (nullable province_id).
- **Constraints vs documentation?** Aligned for societies. For users, 165558 uses `onDelete('set null')` on province_id; Phase Plan says provinces are not deleted and no cascade (prefer restrict). Projects have no province_id/society_id, so no FK mismatch there.

---

## Final Architectural Status Summary

**Status: PARTIAL**

- **Implemented:** Societies table (unique name, province_id nullable, no composite unique, FK restrict). Users.province_id column and users province backfill command with verification gate. Canonical societies seeder. Society CRUD (General/Provincial) writes society id+name on the Society model.
- **Not implemented:** projects.province_id and projects.society_id; users.society_id; any project/user backfill; dual write and read switch for project/user; Own/Disown; Society::visibleToUser and dropdown rules; validation using society_id; NOT NULL on users.province_id.
- **Risk level:** Partial migration with schema and tooling (societies + users province_id) in place but application still entirely on society_name for projects and users. No data loss from “dropping” society_name (it was never dropped), but architecture is inconsistent with the Phase Plan and not ready for deprecating society_name or for Own/Disown and visibility rules.

**Label: PARTIAL — schema and backfill tooling partially aligned; application layer and project/user schema still pre–Phase Plan.**
