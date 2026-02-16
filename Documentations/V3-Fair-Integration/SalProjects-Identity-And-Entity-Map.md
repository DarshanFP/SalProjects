# SalProjects — Identity and Entity Mapping Audit

**Purpose:** Complete identity and entity mapping for Fair Tool API integration.  
**Document:** Structured reference only — no code.  
**Source:** Codebase-only findings.

---

## Table of Contents

1. [Section 1 — User Identity Structure](#section-1--user-identity-structure)
2. [Section 2 — Province Structure](#section-2--province-structure)
3. [Section 3 — Centre Structure](#section-3--centre-structure)
4. [Section 4 — Project Structure](#section-4--project-structure)
5. [Section 5 — Report Structure](#section-5--report-structure)
6. [Section 6 — Attachments](#section-6--attachments)
7. [Section 7 — API Readiness Check](#section-7--api-readiness-check)
8. [Section 8 — Integration Risks](#section-8--integration-risks)

---

## Section 1 — User Identity Structure

### 1.1 User Primary Key

| Attribute | Finding |
|-----------|--------|
| **Column** | `id` |
| **Data type** | `unsignedBigInteger` (Laravel `$table->id()`) — effectively **bigint**, auto-increment. |
| **Auto-increment** | Yes. |
| **Exposed publicly** | Not explicitly in API today. Web routes use session auth; project URLs use `project_id`, not user `id`. For API: **recommend not exposing** internal `id` as the canonical external identifier; use a stable opaque ID or only expose where necessary for audit. |

### 1.2 Province and Centre on User

| Field | Present | Type | Notes |
|-------|---------|------|--------|
| **province_id** | Yes | `unsignedBigInteger`, nullable, FK → `provinces.id` | Added in migration `add_province_center_foreign_keys_to_users_table`. Indexed. |
| **province** | Yes | String (VARCHAR(255) nullable). Originally ENUM. | Legacy; values historically included Bangalore, Vijayawada, Visakhapatnam, Generalate, Divyodaya, Indonesia, East Timor, East Africa, Luzern, none. |
| **Managed provinces (pivot)** | Yes | Table `provincial_user_province` | `user_id`, `province_id`, unique(`user_id`, `province_id`). Used for **general** users who manage multiple provinces. Provincial users typically use `province_id` only. |

**Ambiguity:** Both `province` (string) and `province_id` (FK) exist. Controllers sometimes filter by `user.province` (string), sometimes by `province_id`. **Canonical for API should be `province_id`**; string is legacy and may drift from `provinces.name`.

### 1.3 Username and Email

| Field | Unique | Nullable | Notes |
|-------|--------|----------|--------|
| **username** | Yes (`->unique()`) | Yes (nullable) | Not all users may have username set. |
| **email** | Yes (`->unique()`) | No (required in schema) | Used for login and password reset. |

### 1.4 Link to Centres

| Field | Present | Type | Notes |
|-------|---------|------|--------|
| **center** | Yes | String, nullable | Legacy display/label; may not match `centers` table. |
| **center_id** | Yes | `unsignedBigInteger`, nullable, FK → `centers.id` | Added in same migration as `province_id`. Indexed. |

**Ambiguity:** Dual representation again: `center` (string) and `center_id` (FK). Migration data was backfilled from `center` string to `center_id` where a matching center name existed per province. **Canonical for API should be `center_id`**; string can be derived from `centerRelation->name` for display.

### 1.5 Link to Projects

- **User → Projects:** One-to-many. `Project` has `user_id` (FK to `users.id`). User is the project “owner” (applicant/executor). Relationship: `User::projects()` → `hasMany(Project::class, 'user_id')`.
- Projects are **not** linked to user via province or centre directly; they are linked only via `user_id`. Province/centre for a project = project’s user’s province/centre.

### 1.6 Canonical Identifier for API Exposure

| Use case | Recommended canonical identifier |
|----------|-----------------------------------|
| **User (if exposed)** | Prefer **`id`** (bigint) for internal/API consistency. Do **not** expose password, remember_token. Email/username are unique but PII — expose only if required and with consent. |
| **Province on user** | **`province_id`** (FK to provinces). Do not rely on `province` string for filtering or mapping. |
| **Centre on user** | **`center_id`** (FK to centers). Do not rely on `center` string for filtering or mapping. |

---

## Section 2 — Province Structure

### 2.1 `provinces` Table Structure

| Column | Type | Constraints | Notes |
|--------|------|--------------|--------|
| **id** | `id()` → bigint unsigned, auto-increment | Primary key | |
| **name** | string | **unique** | |
| **provincial_coordinator_id** | unsignedBigInteger, nullable | FK → users.id, onDelete set null | |
| **created_by** | unsignedBigInteger, nullable | FK → users.id, onDelete set null | |
| **is_active** | boolean | default true | |
| **created_at**, **updated_at** | timestamps | | |
| **Indexes** | | index on `name`, index on `provincial_coordinator_id` | |

**Unique fields:** `name` is the only unique constraint besides `id`.

### 2.2 Relationships

| Relationship | Type | Table / FK | Notes |
|--------------|------|------------|--------|
| **Province → Users** | HasMany | `users.province_id` → provinces.id | Users with a single province (e.g. provincial role). |
| **Province → Users (general, multiple)** | BelongsToMany | Pivot `provincial_user_province` (province_id, user_id) | General users can manage multiple provinces. |
| **Province → Centers** | HasMany | `centers.province_id` → provinces.id | |
| **Province → Societies** | HasMany | `societies.province_id` → provinces.id | |
| **Province → Projects** | **Indirect only** | No direct FK. Projects have no `province_id`. Province is derived: Project → user → user.province_id (or user.province). | |

### 2.3 Is `province_id` Present in `projects` Table?

**No.** The `projects` table has no `province_id` column. Province for a project is **derived** as follows:

- **Project** belongs to **User** (`projects.user_id` → users.id).
- **User** has `province_id` (and legacy `province` string).
- Therefore: **project’s province = project.user.province_id** (or project.user.province for legacy display).

---

## Section 3 — Centre Structure

### 3.1 `centers` Table (Migration: `create_centers_table`)

| Column | Type | Constraints | Notes |
|--------|------|--------------|--------|
| **id** | bigint unsigned, auto-increment | Primary key | |
| **province_id** | unsignedBigInteger | FK → provinces.id, onDelete cascade | |
| **name** | string | | |
| **is_active** | boolean | default true | |
| **created_at**, **updated_at** | timestamps | | |
| **society_id** | unsignedBigInteger, nullable | Added later; FK → societies.id, onDelete set null | Optional; centers belong to provinces; societies can be linked. |
| **Unique** | | **unique(['province_id', 'name'])** — `unique_province_center` | Same center name can repeat in different provinces. |

**Primary key:** `id` (bigint, auto-increment).  
**centre_code:** There is **no** `centre_code` or `center_code` column in the migrations or Center model. The only identifier is `id`; the human-facing label is `name`.  
**Unique constraints:** Composite unique on `(province_id, name)`.

### 3.2 Relationships

| Relationship | Type | Notes |
|--------------|------|--------|
| **Centre → Province** | BelongsTo | `centers.province_id` → provinces.id |
| **Centre → User** | HasMany | `users.center_id` → centers.id |
| **Centre → Project** | **Indirect only** | No `center_id` on projects. Project’s centre = project.user.center_id (or user.center string). |

### 3.3 Is `center_id` Stored in `projects`?

**No.** Projects do not have a `center_id` column. Centre is derived via **Project → User → user.center_id** (or user.center).

### 3.4 Is `centre_code` Identical to Any External System?

**Not applicable.** The codebase has no `centre_code` (or `center_code`) field. Center identity is `centers.id` (numeric) and `centers.name` (per province). If Fair Tool or another system uses a “centre code,” a mapping table or convention must be established outside this codebase.

---

## Section 4 — Project Structure

### 4.1 `projects` Table

| Column | Type | Constraints | Notes |
|--------|------|--------------|--------|
| **id** | bigint unsigned, auto-increment | Primary key | Laravel default. |
| **project_id** | string | **unique** | Business key (e.g. DP-0001, CCI-0001). Generated in model (e.g. by type prefix + sequence). |
| **user_id** | unsignedBigInteger | FK → users.id, onDelete cascade | Owner (applicant/executor). |
| **in_charge** | unsignedBigInteger | FK → users.id, onDelete cascade | In-charge user. |
| Plus many other columns | … | | project_type, project_title, status, budget fields, dates, coordinator fields, etc. |

- **Primary key (storage):** `id` (bigint, auto-increment).  
- **Business key:** `project_id` (string, unique).  
- **Used in URLs (web):** All web routes use **`project_id`** in the path (e.g. `/projects/{project_id}`, `/coordinator/projects/show/{project_id}`). The Project model does **not** override `$primaryKey` (it is commented out); so Eloquent uses `id` for route model binding unless overridden. Controllers resolve project by `project_id` when the route parameter is `project_id`.

### 4.2 Province and Centre Linkage for Projects

| Linkage | Method |
|---------|--------|
| **Province** | **Indirect.** Project has no province_id. Province = project.user.province_id (or user.province). |
| **Centre** | **Indirect.** Project has no center_id. Centre = project.user.center_id (or user.center). |
| **User** | **Direct.** projects.user_id → users.id (and projects.in_charge → users.id). |

### 4.3 Major Relationships (Project)

| Relationship | Model / Table | FK / Key | Notes |
|--------------|---------------|----------|--------|
| **user** | User | project.user_id → users.id | Owner. |
| **attachments** | ProjectAttachment | project_attachments.project_id → projects.project_id | Generic project attachments. |
| **statusHistory** | ProjectStatusHistory | project_status_histories.project_id → projects.project_id (string) | |
| **reports** | DPReport (monthly) | DP_Reports.project_id → projects.project_id, DP_Reports.user_id → users.id | |
| **budgets** | ProjectBudget | project_budgets.project_id → projects.project_id | |
| **comments** | ProjectComment | project_comments.project_id → projects.project_id | |
| **objectives** | ProjectObjective | project_objectives.project_id → projects.project_id | |
| **activityHistory** | ActivityHistory | activity_histories.related_id = project_id (string), type = 'project' | |
| **Type-specific** | Many (e.g. CCI, IES, IAH, ILP, RST, EduRUT, LDP, IGE, etc.) | Each has project_id (string) → projects.project_id | Various tables per project type. |

All project-child tables reference **projects by `project_id` (string)**, not by `projects.id`.

### 4.4 Which ID Should the API Expose?

| Identifier | Recommendation | Reason |
|------------|----------------|--------|
| **id** | Optional (internal use only) | Auto-increment, stable but not human-friendly; used in DB and Eloquent by default. |
| **project_id** | **Yes — primary external identifier** | Already used in all web URLs and in all child tables (reports, attachments, status history, budgets, etc.). Unique and human-readable. |
| **Both** | Acceptable | Expose `project_id` as main key for URLs and references; include `id` in payload if needed for internal correlation. **Recommend using `project_id` in API paths and as the stable reference** for Fair Tool. |

---

## Section 5 — Report Structure

### 5.1 Report Tables (Relevant to Projects)

Primary report table for “monthly” development project reports:

| Table | Model | Primary key | Project reference | Province / Centre |
|-------|--------|-------------|-------------------|--------------------|
| **DP_Reports** | DPReport | **report_id** (string); model sets `$primaryKey = 'report_id'`, `$incrementing = false`, `$keyType = 'string'`. Table also has `id` (auto-increment). | **project_id** (string), FK → projects.project_id. **user_id** (nullable), FK → users.id. | No province_id or center_id. Province/centre derived via project → user. |

Other report tables (quarterly, half-yearly, annual, etc.) also reference **project_id** (string) and do not store province_id or center_id.

### 5.2 Province and Centre on Reports

- **Province:** Not stored on any report table. Province is derived: Report → project → user → province_id (or province).  
- **Centre:** Not stored. Derived: Report → project → user → center_id (or center).  
- **Reports are province-scoped only indirectly:** by virtue of project ownership (project.user.province_id). Same for centre: report’s “centre” = project’s user’s centre.

### 5.3 Are Reports Province-Scoped / Centre-Scoped?

- **Conceptually:** Yes — via project’s user. A report belongs to a project, which belongs to a user; the user has a province and optionally a centre.  
- **In schema:** No dedicated province_id or center_id on report tables. Any province/centre filtering for reports must be done via join through project and user (e.g. `whereHas('project.user', ...)`).

---

## Section 6 — Attachments

### 6.1 Project Attachments Table (`project_attachments`)

| Column | Type | Notes |
|--------|------|--------|
| **id** | bigint unsigned, auto-increment | Primary key. |
| **project_id** | string | FK → projects.project_id, onDelete cascade. |
| **file_path** | string, nullable | Path under storage disk. |
| **file_name** | string, nullable | |
| **description** | text, nullable | |
| **public_url** | string, nullable | Can store a precomputed public URL. |
| **created_at**, **updated_at** | timestamps | |

**Project reference:** All by **project_id** (string), not projects.id.

### 6.2 File Storage Location

- **Disk:** `Storage::disk('public')` is used for project attachment paths (e.g. in `ProjectAttachmentHandler`, IES/IIES/IAH attachment code).  
- **Path pattern:** e.g. `project_attachments/{type}/{project_id}/...` (type can be IES, IIES, IAH, etc.). Stored under the `public` disk (typically `storage/app/public`), with `public_url` sometimes set via `Storage::url($filePath)`.

### 6.3 Signed URL Mechanism

- **Codebase:** No use of `signedUrl` or `temporaryUrl` was found in the app code for project attachments. Download/view is done via controller actions (e.g. `AttachmentController::downloadAttachment`, `IIESAttachmentsController::downloadFile`) using `Storage::disk('public')->download(...)` or streaming.  
- **Conclusion:** Attachments are served through app routes, not through time-limited signed URLs. For API, you would need to introduce signed or temporary URLs if you want URL-based access without going through an app endpoint.

---

## Section 7 — API Readiness Check

### 7.1 Models Safe for API Exposure (With Care)

- **Province, Center:** Small reference data; safe to expose id and name (and is_active, etc.) with no sensitive fields.  
- **Project:** Safe to expose selected fields via a Resource; must exclude or restrict sensitive fields (e.g. coordinator contact details if required by policy). Province/centre must be derived (user.province_id, user.center_id) or denormalized.  
- **ProjectAttachment:** Safe to expose metadata (id, project_id, file_name, description); file access should be via controlled download or signed URL.  
- **ProjectStatusHistory:** Safe to expose for audit; may include user identifiers (changed_by_user_id, etc.).  
- **DPReport (and other report models):** Same as project — expose via Resource and restrict sensitive data; report’s province/centre only via project → user.

### 7.2 Fields That Must Never Be Exposed

- **User:** `password`, `remember_token` (already in `$hidden`). Consider not exposing email, phone, or full name to external systems unless required.  
- **Project / Report:** Any PII or contact details (e.g. coordinator India/Luzern names, emails, phones) if policy requires restriction.  
- **Internal keys:** Avoid exposing internal-only keys if they are not needed by the consumer.

### 7.3 Soft Deletes

- **Finding:** No model in the codebase uses `SoftDeletes` or a `deleted_at` column in the migrations checked. Deletes are hard deletes.

### 7.4 Global Scopes

- **Finding:** No `addGlobalScope` or `globalScope` usage was found in the Models. No default global scopes that would automatically filter rows for API vs web.

### 7.5 Composite Keys

- **Database:** No table uses a composite primary key. Unique composite constraints exist (e.g. `(province_id, name)` on centers, `(user_id, province_id)` on provincial_user_province), but primary keys are single-column.  
- **Eloquent:** Models use single primary keys. Some models use a **string primary key** (e.g. DPReport.report_id, ReportComment.R_comment_id, ProjectComment.project_comment_id, various report-related and CCI models). These are not composite keys.

---

## Section 8 — Integration Risks

### 8.1 String-Based Province Logic

- **Risk:** Controllers and filters sometimes use **`user.province`** (string) instead of **`user.province_id`**. String values may not match `provinces.name` exactly (e.g. casing, legacy enum values).  
- **Recommendation:** For Fair Tool (and any API), use **only `province_id`** for filtering and mapping. Do not rely on `province` string for identity or authorization.

### 8.2 Duplicated Identity Fields

- **User:** Both `province` (string) and `province_id` (FK); both `center` (string) and `center_id` (FK). Risk of inconsistency if one is updated and the other not.  
- **Recommendation:** Treat `province_id` and `center_id` as canonical. Expose and filter by these in the API; use string fields only for display and only when derived from the related entity (e.g. provinceRelation.name).

### 8.3 Ambiguous Centre Mapping

- **Risk:** No `centre_code` in SalProjects. If Fair Tool uses a “centre code,” there is no built-in mapping. Centre identity here is `centers.id` and `centers.name` (scoped by province).  
- **Recommendation:** Define a mapping (e.g. Fair Tool centre code ↔ SalProjects center id or name+province) outside the app, or add a `center_code` (or external_id) column to `centers` if both systems must stay in sync.

### 8.4 Mismatched ID Types

- **Projects:** Internal PK is `id` (bigint); business key is `project_id` (string). Web uses `project_id` in URLs; child tables and report tables all use `project_id` (string).  
- **Reports:** DP_Reports has both `id` (bigint) and `report_id` (string); model uses `report_id` as primary key.  
- **Risk:** If Fair Tool expects numeric project or report IDs, mapping to `project_id` / `report_id` (string) must be explicit.  
- **Recommendation:** Document and expose **project_id** and **report_id** as the canonical external identifiers; use numeric `id` only where needed for internal correlation.

### 8.5 Legacy Fields That May Break Integration

- **user.province (string):** Legacy; may contain values not in `provinces.name` or may drift.  
- **user.center (string):** Same; may not match `centers.name` for the user’s province.  
- **Role enum:** `role` was extended (e.g. added 'applicant'); ensure Fair Tool does not assume a fixed enum set.  
- **Report table name:** `DP_Reports` (mixed case). Ensure any external reference to table names uses exact casing if applicable.

---

## Summary: Canonical Identifiers and Ambiguities

| Entity | Canonical identifier for API | Ambiguity / note |
|--------|------------------------------|-------------------|
| **User** | `id` (bigint) | Province/centre: use `province_id` and `center_id`, not string fields. |
| **Province** | `id` (bigint); display: `name` | No province_id on projects; derive via project.user.province_id. |
| **Centre** | `id` (bigint); display: `name` | No center_id on projects; no centre_code; derive via project.user.center_id. |
| **Project** | **project_id** (string) for URLs and references | Also has numeric `id`; child tables use project_id. |
| **Report (monthly)** | **report_id** (string) | Table has both id and report_id; model PK is report_id. |
| **Attachment** | `id` (bigint); link to project via `project_id` (string) | File access via app route or future signed URL. |

---

*End of Identity and Entity Map. All findings are based solely on the current codebase.*
