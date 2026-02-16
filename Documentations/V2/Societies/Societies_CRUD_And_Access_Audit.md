# Societies CRUD & Access Audit

**Table:** `societies`  
**Purpose:** Document all CRUD operations, which users have access, and current schema — in preparation for adding an **address** column.

---

## 1. Table schema (current)

| Column        | Type               | Nullable | Default   | Notes                          |
|---------------|--------------------|----------|-----------|--------------------------------|
| `id`          | bigint (PK)        | No       | —         | Auto-increment                 |
| `province_id` | unsignedBigInteger | No       | —         | FK → `provinces.id`, cascade   |
| `name`        | string             | No       | —         | Society name                   |
| `is_active`   | boolean            | No       | `true`    | Active/inactive                |
| `created_at`  | timestamp          | Yes      | —         |                                |
| `updated_at`  | timestamp          | Yes      | —         |                                |

**Constraints:**
- Unique: `(province_id, name)` — same name allowed in different provinces.
- Indexes: `province_id`, `name`.
- Foreign key: `province_id` → `provinces.id` with `onDelete('cascade')`.

**Migration file:** `database/migrations/2026_01_13_144931_create_societies_table.php`

---

## 2. Model

**File:** `app/Models/Society.php`

- **Fillable:** `province_id`, `name`, `is_active`
- **Casts:** `is_active` → boolean
- **Relations:** `province()` (BelongsTo), `centers()` / `activeCenters()` (query by same province)
- **Scopes:** `active()`, `byProvince($provinceId)`

**Note:** `centers()` returns `Center::where('province_id', $this->province_id)` (centers in the same province), not centers linked via `centers.society_id`. The `centers` table has an optional `society_id` FK (see `2026_01_13_144932_add_society_id_to_centers_table.php`).

---

## 3. CRUD operations and routes

### 3.1 General user (role: `general`)

| Operation | Method | Route | Controller method   | Access check |
|-----------|--------|--------|----------------------|--------------|
| **List**  | GET    | `/general/societies` | `GeneralController@listSocieties`  | `role === 'general'` + middleware `role:general` |
| **Create (form)** | GET  | `/general/societies/create` | `GeneralController@createSociety`  | Same |
| **Store** | POST   | `/general/societies` | `GeneralController@storeSociety`  | Same |
| **Edit (form)** | GET | `/general/societies/{id}/edit` | `GeneralController@editSociety` | Same |
| **Update** | PUT   | `/general/societies/{id}` | `GeneralController@updateSociety` | Same |
| **Delete** | DELETE | `/general/societies/{id}` | `GeneralController@deleteSociety` | Same |

**Route names:** `general.societies`, `general.createSociety`, `general.storeSociety`, `general.editSociety`, `general.updateSociety`, `general.deleteSociety`

**Behaviour:**
- **List:** Paginated (20 per page), with filters: `province_id`, `is_active` (default active only). Uses `Society::with(['province'])`.
- **Create:** Form has province dropdown + society name. New society is created with `is_active = true`.
- **Store validation:** `province_id` required, exists; `name` required, max 255, unique per province (case-insensitive).
- **Edit:** Can change province, name, and `is_active`.
- **Update validation:** Same as store for province/name; plus `is_active` required boolean.
- **Delete:** Allowed only if `$society->centers()->count() === 0` (i.e. no centers in that province). Otherwise redirect with error. Logs action.

**Views:** `resources/views/general/societies/index.blade.php`, `create.blade.php`, `edit.blade.php`. Index shows Delete button and uses delete route with confirmation.

---

### 3.2 Provincial user (role: `provincial`)

| Operation | Method | Route | Controller method   | Access check |
|-----------|--------|--------|----------------------|--------------|
| **List**  | GET    | `/provincial/societies` | `ProvincialController@listSocieties`  | Middleware `role:provincial`; scope: own province |
| **Create (form)** | GET  | `/provincial/create-society` | `ProvincialController@createSociety`  | Same |
| **Store** | POST   | `/provincial/create-society` | `ProvincialController@storeSociety`  | Same |
| **Edit (form)** | GET | `/provincial/society/{id}/edit` | `ProvincialController@editSociety` | Same; society must belong to user’s province |
| **Update** | PUT   | `/provincial/society/{id}/update` | `ProvincialController@updateSociety` | Same |
| **Delete** | —      | —     | —                    | **Not available** (no route, no UI) |

**Route names:** `provincial.societies`, `provincial.createSociety`, `provincial.storeSociety`, `provincial.editSociety`, `provincial.updateSociety`

**Behaviour:**
- **List:** All societies for the provincial user’s province (no pagination; single province).
- **Create:** Province is fixed (current user’s province); form only has society name.
- **Store:** `province_id` set from user’s province; validation: `name` required, max 255, unique per province (case-insensitive).
- **Edit/Update:** Society must match `province_id` of user’s province (`where('province_id', $province->id)`). Provincial cannot change province; can update `name` and `is_active` only.
- **Delete:** Not implemented for provincial users.

**Views:** `resources/views/provincial/societies/index.blade.php`, `create.blade.php`, `edit.blade.php`. Index has no Delete button.

---

## 4. Who has access (summary)

| Role         | List | Create | Store | Edit | Update | Delete |
|-------------|------|--------|-------|------|--------|--------|
| **General** | ✅   | ✅     | ✅    | ✅   | ✅     | ✅     |
| **Provincial** | ✅ (own province) | ✅ | ✅ | ✅ | ✅ | ❌ |
| **Coordinator** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Executor / Applicant** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

- **General:** Full CRUD; can manage all provinces and societies; delete blocked if society’s province has any centers.
- **Provincial:** List/Create/Store/Edit/Update only, scoped to their province; no delete.

Access is enforced by:
1. **Routes:** `routes/web.php` — General society routes inside `Route::middleware(['auth', 'role:general'])`, Provincial inside `Route::middleware(['auth', 'role:provincial'])`.
2. **Controllers:** General methods abort with 403 if `Auth::user()->role !== 'general'`. Provincial methods resolve province from `auth()->user()->province` and scope all queries to that province.

---

## 5. Related code references

- **Society seeding:** `database/seeders/SocietySeeder.php` — builds societies from provinces/users/projects.
- **Projects:** Use a string field `society_name` (not FK to `societies`); see `StoreProjectRequest`, `UpdateProjectRequest`, `ProjectController`, and project general_info partials.
- **Centers:** Optional `society_id` on `centers` (FK to `societies`, `onDelete('set null')`). Centers are province-scoped; all centers in a province are available to all societies in that province.
- **General sidebar:** Link “View Societies” → `route('general.societies')` in `resources/views/general/sidebar.blade.php`.
- **Provincial sidebar:** Society management under “View Societies” in `resources/views/partials/sidebar/provincial.blade.php`.

---

## 6. Adding an **address** column (implementation checklist)

Use this when implementing the new `address` field on `societies`.

### 6.1 Database

- [ ] **Migration:** New migration (e.g. `add_address_to_societies_table`) that:
  - Adds `address` to `societies` (e.g. `$table->string('address')->nullable()->after('name');` or `text` if long).
  - Decide nullability: nullable vs required (if required, consider a default or backfill for existing rows).
- [ ] Run migration on dev/staging, then production.

### 6.2 Model

- [ ] **Society model:** Add `'address'` to `$fillable` in `app/Models/Society.php`.
- [ ] If you add mutators/accessors or casts, document in model docblock.

### 6.3 General user (full CRUD)

- [ ] **Validation:** In `GeneralController::storeSociety` and `updateSociety`, add rules for `address` (e.g. `'address' => 'nullable|string|max:500'` or as needed).
- [ ] **Create/Store:** Include `address` in `Society::create([...])` in `storeSociety`.
- [ ] **Edit/Update:** Include `address` in `$society->update([...])` in `updateSociety`.
- [ ] **Views:** In `resources/views/general/societies/create.blade.php` and `edit.blade.php`, add a form field for address (e.g. text input or textarea) and wire to `old()` / `$society->address`.
- [ ] **Index (optional):** In `resources/views/general/societies/index.blade.php`, add an “Address” column if you want it listed.

### 6.4 Provincial user (create/edit/update only)

- [ ] **Validation:** In `ProvincialController::storeSociety` and `updateSociety`, add same `address` rules.
- [ ] **Store:** Add `'address' => $request->address` (or validated value) in `Society::create([...])`.
- [ ] **Update:** Add `'address' => $request->address` in `$society->update([...])`.
- [ ] **Views:** In `resources/views/provincial/societies/create.blade.php` and `edit.blade.php`, add the address field and bind to request/model.

### 6.5 Other references

- [ ] **SocietySeeder:** If seeder creates/updates societies, add address handling if applicable (e.g. leave null or set from existing data).
- [ ] **Exports/Reports:** If any export or report includes society data (e.g. `ExportController`), add address to the output if required.
- [ ] **API / JSON:** If society data is exposed via API or JSON (e.g. for dropdowns), ensure `address` is included or explicitly excluded in serialization.

### 6.6 Testing and QA

- [ ] General: Create society with address; edit and clear/change address; list shows address if column added.
- [ ] General: Delete behaviour unchanged (blocked when province has centers).
- [ ] Provincial: Create/edit society with address (scoped to province).
- [ ] Validation: Required vs nullable behaviour; max length.
- [ ] Migration rollback and re-run on a copy of DB if possible.

---

## 7. File index

| Area | File path |
|------|-----------|
| Migration (create table) | `database/migrations/2026_01_13_144931_create_societies_table.php` |
| Migration (centers.society_id) | `database/migrations/2026_01_13_144932_add_society_id_to_centers_table.php` |
| Model | `app/Models/Society.php` |
| General controller | `app/Http/Controllers/GeneralController.php` (methods from ~5530) |
| Provincial controller | `app/Http/Controllers/ProvincialController.php` (listSocieties, createSociety, storeSociety, editSociety, updateSociety) |
| Routes | `routes/web.php` (General: 285–290; Provincial: 343–346) |
| General views | `resources/views/general/societies/index.blade.php`, `create.blade.php`, `edit.blade.php` |
| Provincial views | `resources/views/provincial/societies/index.blade.php`, `create.blade.php`, `edit.blade.php` |
| General sidebar | `resources/views/general/sidebar.blade.php` |
| Provincial sidebar | `resources/views/partials/sidebar/provincial.blade.php` |
| Seeder | `database/seeders/SocietySeeder.php` |

---

*Document generated for V2 Societies audit and address column addition. Last updated: Feb 10, 2026.*
