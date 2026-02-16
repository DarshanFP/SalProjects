# Society–Project Mapping Phase Plan V2

**Revision 5 — Users Province Normalization Model**

**Scope:** Migrate from `society_name` (string) to `society_id` (FK) with **global + province-owned societies**, Own/Disown, **projects.province_id**, and production-safe rollout. **Users table is normalized to `users.province_id` (FK) before projects.province_id backfill.**

**Prerequisite:** The `users` table has `users.province` (string name) only. **`users.province_id` must be fully normalized before projects.province_id backfill**, because projects.province_id is set from `project.user->province_id`. If users lack province_id, projects cannot be backfilled. Therefore the **Users Province Normalization Phase** runs before the Projects province_id step.

**Status:** Analysis and plan only. No schema or code changes.

---

## 1. Executive Summary

- **Model change:** Societies become either **global** (`province_id` NULL) or **province-owned** (`province_id` set). Unique index on `name`; composite unique(province_id, name) removed. **Projects** gain **province_id** (required for new projects) and **society_id** in the same migration cycle.
- **Global uniqueness:** Society names are **globally unique** at the **database level** (unique index on `name`). Database collation prevents case-sensitive duplicates; no application casing normalization required.
- **Ownership:** **Exclusive.** A society is either global or belongs to exactly one province. Transfer between provinces: Disown (→ global) then Own (→ new province). Repeated Own/Disown toggling is acceptable; no additional locking or audit logging required at this stage.
- **Immutability:** **Societies cannot be deleted.** **Provinces cannot be deleted.** All references to delete-cascade on societies or provinces are removed from the plan.
- **Reports:** Society name in reports is a historical snapshot; reports do not auto-update when a society is renamed. This divergence is acceptable.
- **Visibility:** Provincial users see global societies + own province societies. General users see all societies.
- **Hardcoded list:** The 9 canonical names (typo corrected) are inserted as global societies first; province assignment via Own.
- **Risks:** Addressed via dual-write, typo normalization, read fallback, report fallback, and phased rollout. Revision 4: projects.province_id improves reporting and grouping without user join dependency.

---

## 2. Revised Architecture Model

### 2.1 Data Model (Revision 4 — Production Finalized)

| Entity | Change | Notes |
|--------|--------|------|
| **societies** | `province_id` → **nullable** | NULL = global; non-NULL = owned by that province. Societies cannot be deleted. |
| **societies** | **Unique index on `name`** | Global uniqueness at database level. Collation prevents case-sensitive duplicates; no app-level casing logic. Replaces composite unique(province_id, name). |
| **societies** | **Remove** composite unique(province_id, name) | Dropped in same migration that adds unique(name). |
| **societies** | **Keep index on province_id** | Used for filtering (visibility: province_id = X OR province_id IS NULL). No cascade on province delete; provinces cannot be deleted. |
| **societies** | No pivot | One society has at most one province. Shared ownership is not allowed. |
| **projects** | Add **`province_id`** (unsignedBigInteger, indexed) | **Required for all new projects** after backfill. FK to provinces.id. Backfill from project.user->province_id. Enables grouping and reporting without user join. |
| **projects** | Add `society_id` (nullable, FK, indexed) | Same migration cycle as province_id. Keep society_name during dual-write; drop after read switch. |
| **users** | Add `society_id` (nullable, FK) | Same as projects for society reference. |

**Projects.province_id — benefits:**

- **Removes dependency on user join:** Province can be read from project directly; no need to join users for province-scoped queries.
- **Simplifies grouping by province:** Dashboards and lists can filter/group by projects.province_id.
- **Improves reporting performance:** Province-wise and society-wise reports can use index(province_id) and composite (province_id, society_id).
- **Enables composite indexing:** (province_id, society_id) supports analytics and reporting queries efficiently.

**Why global unique(name) eliminates ambiguity:**

- **Backfill resolution:** One name maps to exactly one society. Backfill finds by name only; no province disambiguation needed. No risk of choosing the "wrong" province-owned society when multiple provinces had the same name.
- **Dropdown preselect:** Preselect by society_id is unambiguous. Display name is unique, so no duplicate labels in the list.
- **Own/Disown:** Only one row per name exists. Own/Disown operate on a single row; no ambiguity about which society is being transferred.
- **Future migrations:** Any script or report that joins or resolves by name has a single target. No need to pass province context when resolving by name.

### 2.2 Should province_id on societies remain nullable?

**Yes.** Business rules require global societies (visible to all provinces). Nullable `province_id` is the minimal change; no separate "global" flag needed.

### 2.3 Pivot table (many-to-many)?

**No.** Ownership is exclusive: one society is global OR owned by one province. Single `province_id` (nullable) remains the design. No pivot tables.

### 2.4 Single province ownership sufficient?

**Yes.** Per policy: a society can be global (province_id NULL) or belong to exactly one province. Shared ownership is not allowed.

### 2.5 If two provinces want to "own" the same global society?

**Design:** First "Own" wins; the society becomes that province’s. Second province would not see it in "their" list. To move a society from Province A to Province B: Disown (A → global), then Own (global → B). No direct transfer from one province to another.

### 2.6 Global name uniqueness — database enforcement

- **Database:** Unique index on `name` column. The database rejects any insert or update that would create a duplicate name. Collation prevents case-sensitive duplicates; no app-level casing logic.
- **Controller/validation:** Validation rules (e.g. `unique:societies,name` with ignore for edit) complement the DB constraint; the DB remains the authority.

### 2.7 Final Architecture Summary (Revision 4)

**Entities:**

- **Province (immutable):** Provinces cannot be deleted. Referenced by societies (province_id nullable), projects (province_id required), and users (province_id). No cascade-on-delete from provinces.
- **Society (global or province-owned, unique name):** Societies cannot be deleted. One row per name (unique(name)). Either global (province_id NULL) or owned by one province (province_id set). Referenced by projects (society_id) and users (society_id). No cascade-on-delete from societies.
- **Project (province_id + society_id):** Belongs to one province (province_id required after backfill) and optionally to one society (society_id nullable). province_id denormalized from user for performance and reporting; society_id replaces society_name string.
- **User (province_id + society_id):** Belongs to one province and optionally to one society. society_id replaces society_name string.

**Relationships:**

- Province has many societies (societies.province_id), many projects (projects.province_id), many users (users.province_id).
- Society has many projects (projects.society_id), many users (users.society_id). Society belongs to zero or one province (societies.province_id).
- Project belongs to one province (projects.province_id), one user (projects.user_id), and optionally one society (projects.society_id).
- User belongs to one province (users.province_id), optionally one society (users.society_id).

**Reports:** Report tables store society_name as a historical snapshot; they do not auto-update when a society is renamed. This divergence is acceptable.

---

## 3. Risk Matrix (Old vs New)

| Risk | V1 | V2 (Global + Own/Disown) | Revision 3 (Global unique name) |
|------|----|---------------------------|----------------------------------|
| Data loss (string → FK) | Unmatched names → society_id NULL | Backfill; typo normalized. | Same; backfill by name only; unmatched leave society_id NULL. |
| Typo (ST. ANNS'S vs ST. ANN'S) | Backfill normalizes | Backfill maps typo to correct society. | Same; normalize then find by name only. |
| **Duplicate name ambiguity** | Same name in different provinces → multiple rows | Backfill needed province to disambiguate. | **Removed:** unique(name) guarantees one row per name. Backfill finds by name only; no disambiguation. |
| **Province-based mismatch** | Wrong province chosen during backfill | Risk if province resolution was wrong. | **Reduced:** No province choice in backfill; single society per name. |
| **Ownership conflict** | N/A | Two provincials could target "same" name in different provinces. | **Impossible:** One society per name; Own/Disown target single row. Transfer = Disown then Own. |
| Report/export breakage | Fallback society_id → name, else society_name | Fallback preserved; dual-write. | Same. |
| Provincial sees wrong societies | Scope by province_id | Scope = (province_id = user.province_id OR province_id IS NULL). | Same; validation enforces visibility. |
| Own/Disown race | N/A | Atomic update with WHERE; rowsAffected check. | Same; single row per name simplifies conflict handling. |
| Partial migration failure | Backfill in transaction per batch | Idempotent; re-run; dual-write rollback. | Same. |
| New rows during backfill | Optional maintenance window or second pass | Document. | Same. |
| General vs Provincial CRUD | General full; Provincial limited | Provincial: global + own province; Own/Disown as specified. | Same. |

### 3.1 Resolved or out-of-scope (Revision 4)

- **Province delete cascade:** **Resolved.** Provinces cannot be deleted; no cascade-on-delete concerns for provinces.
- **Society delete cascade:** **Resolved.** Societies cannot be deleted; no cascade-on-delete concerns for societies.
- **Case-sensitivity ambiguity:** **Resolved.** Database collation prevents case-sensitive duplicates; unique(name) is sufficient and no application-level casing normalization is required.
- **Ownership governance instability:** **Accepted.** Repeated Own/Disown toggling is acceptable; no additional locking or audit logging required at this stage.

### 3.2 Pre-migration risk: duplicate names

- **Pre-migration duplicate names:** Under the old composite unique(province_id, name), the same name could exist in multiple provinces. When switching to unique(name), **any existing duplicate names must be resolved before the new constraint is applied**, or the migration will fail. The data audit step (Section 9.1) detects this; resolution options are merge (keep one row, update references), rename one of the societies, or remove duplicates. One-time migration risk only.

---

## 4. Ownership Model Design

### 4.1 States

- **Global:** `province_id IS NULL`. Visible to all provincial users and General. Name is globally unique (DB-enforced).
- **Province-owned:** `province_id = X`. Visible to that province’s provincial users and to General. Ownership is exclusive (one province per society).

### 4.2 Explicit ownership rules (Revision 3)

- **Own** is allowed **only if** `province_id IS NULL` (society is global). A provincial user cannot assign a society that already belongs to another province.
- **Disown** is allowed **only if** `province_id` equals the **current user’s province**. Only the owning province can disown.
- **A society cannot be transferred directly from one province to another.** To transfer from Province A to Province B: the provincial user for A must **Disown** (society becomes global), then the provincial user for B may **Own** it. No single "transfer" action.

### 4.3 Own (Assign province)

- **Who:** Provincial user only (General can set province on create/edit).
- **Condition:** Society must be **global** (province_id IS NULL). Provincial cannot "steal" another province’s society.
- **Action:** Set `society->province_id = auth()->user()->province_id`.
- **Validation:** Society exists; society.province_id IS NULL; user is provincial.

### 4.4 Disown (Make global)

- **Who:** Provincial user only (for societies they "own").
- **Condition:** Society must be **owned by current user’s province** (province_id = user.province_id).
- **Action:** Set `society->province_id = NULL`.
- **Validation:** Society exists; society.province_id = user.province_id.

### 4.5 Route and controller (design)

- **Route (example):**  
  `POST /provincial/society/{id}/own` → `ProvincialController@ownSociety`  
  `POST /provincial/society/{id}/disown` → `ProvincialController@disownSociety`
- **Authorization:**  
  - Own: user is provincial; society exists and province_id IS NULL.  
  - Disown: user is provincial; society exists and province_id = user’s province_id.
- **Validation:** URL id only; state and ownership checked in controller before update.
- **Race condition:** Single atomic update with WHERE so state cannot change underfoot:  
  Own: `Society::where('id', $id)->whereNull('province_id')->update(['province_id' => $provinceId])`;  
  Disown: `Society::where('id', $id)->where('province_id', $provinceId)->update(['province_id' => null])`.  
  Check `rowsAffected === 1`; if 0, return 409 Conflict (e.g. already owned or already disowned).

### 4.6 Enforcement: controller and DB layer

- **Controller:** Before Own, load society and assert `$society->province_id === null`. Before Disown, assert `$society->province_id === $user->province_id`. Only then run the atomic update. Return 403 if assertion fails.
- **DB layer:** The atomic update’s WHERE clause enforces the same invariant: Own updates only rows with `province_id IS NULL`; Disown updates only rows with `province_id = $provinceId`. Even if controller is bypassed, the DB will not change a row that does not match. No separate DB trigger required; the WHERE in the update is the enforcement.

### 4.7 Prevent province from owning another province’s society

- Own is only allowed when `society.province_id IS NULL`. So no "takeover" of another province’s society. Direct transfer is disallowed; must Disown then Own.

### 4.8 Society CRUD index page (Provincial)

- **List:** Provincial sees global + own province only via `Society::visibleToUser($user)`.
- **Buttons per row:**  
  - **Own:** Shown only when `society.province_id IS NULL`. Submits to `POST /provincial/society/{id}/own`.  
  - **Disown:** Shown only when `society.province_id === current user’s province_id`. Submits to `POST /provincial/society/{id}/disown`.  
- **General:** No Own/Disown (can set province on edit); full CRUD across all societies.

---

## 5. Dropdown Visibility Rules

### 5.1 Reusable scope: `Society::visibleToUser($user)`

```text
General:
  Society::query()  // all societies (optionally active only)

Provincial:
  Society::where(function ($q) use ($user) {
      $q->where('province_id', $user->province_id)
        ->orWhereNull('province_id');
  })
```

- Use `visibleToUser(auth()->user())` (or equivalent) for society index and for dropdowns when the context is "current user".
- For **project create/edit** and **executor/applicant create/edit**, the dropdown is "societies visible to the current user" **and** (for project/executor) scoped to the **selected province** when applicable: show societies where `province_id = selected_province_id OR province_id IS NULL`.

### 5.2 Dropdown by context

| Context | Who | Query |
|---------|-----|--------|
| Society CRUD index (list) | General | All societies (filter by province/status as now). |
| Society CRUD index (list) | Provincial | `Society::visibleToUser($user)` (global + own province). |
| Project create | General | Societies for **selected province** in form: `(province_id = $selectedProvinceId OR province_id IS NULL)` and active. |
| Project create | Executor/Applicant | Societies for **user’s province**: `(province_id = $user->province_id OR province_id IS NULL)` and active. |
| Project edit | Same as create | Same; preselect `$project->society_id`. |
| Executor/Applicant create (General) | General | Societies for **selected province**: same as project create for that province. |
| Executor/Applicant create (Provincial) | Provincial | `Society::visibleToUser($user)` and active. |
| Executor/Applicant edit | Same as create | Same; preselect `$user->society_id`. |

### 5.3 Preselect and fallback

- **Preselect:** Use `old('society_id', $project->society_id ?? $user->society_id ?? null)` in create; in edit use model’s `society_id`. Option value = `$society->id`.
- **Fallback during transition:** If `society_id` is NULL but `society_name` is present, dropdown can show a placeholder "Legacy: {name}" and leave value empty, or preselect by matching name in the loaded list (if any).

### 5.4 N+1

- Load societies once per request (e.g. in controller) and pass to view. Do not load per dropdown in a loop. For General project create with province selector, load societies grouped by province (e.g. `Society::active()->with('province')->get()->groupBy('province_id')`) or one query with `whereIn('province_id', $provinceIds)->orWhereNull('province_id')` and group in Blade/JS.

---

## 6. Validation Rules

### 6.1 Society (create/update)

- **General:**  
  - `province_id`: nullable, exists:provinces,id.  
  - `name`: required, string, max:255, **unique:societies,name** (on create; on update use ignore rule for current id). DB unique(name) enforces global uniqueness; validation provides clear errors.
- **Provincial (create society):** Province fixed to user’s; name required, **unique:societies,name** (global uniqueness). Provincial creates in their province only.
- **Provincial (edit society):** Only societies they can see (global or own province); cannot change province_id to another province (or restrict edit to name/address/is_active only for provincial if desired).

### 6.2 Project (general info)

- **province_id:** required, exists:provinces,id (for new projects; project must belong to a province).  
- **society_id:** nullable, exists:societies,id.  
- **Provincial:** Society must be visible to user: `Society::visibleToUser($user)->where('id', $value)->exists()`.  
- **General:** Optional: society visible in selected province or global.  
- During dual-write: keep `society_name` nullable, string, max:255 for backward compatibility.

### 6.3 Executor / Applicant (user)

- `society_id`: required (executor/applicant), nullable (provincial user). Exists:societies,id.  
- **Provincial:** Society visible to user: `Society::visibleToUser($user)->where('id', $value)->exists()`.  
- During dual-write: keep `society_name` in validation if still present on form for fallback.

---

## 7. Authorization Matrix

| Action | General | Provincial |
|--------|---------|------------|
| List societies | All | Global + own province only |
| Create society | Yes (any province or global) | Yes (own province only; cannot create global) |
| Edit society | Yes (any) | Only if society is global or own province; cannot set province_id to another province |
| Delete society | **N/A — societies cannot be deleted** | **N/A — societies cannot be deleted** |
| Own society | N/A (can set province on edit) | Yes (only when society is global) |
| Disown society | N/A | Yes (only when society is own province) |
| Create/Edit project | Yes | Yes; society_id must be visible to user |
| Create/Edit executor/applicant | Yes | Yes; society_id must be visible to user |

---

## 8. Data Preparation Phase

### 8.1 Make societies support global (see Section 9)

- **Migration:** Drop composite unique(province_id, name); add unique(name); make province_id nullable; keep index on province_id. Data audit for duplicate names must pass before adding unique(name). See Section 9.1.
- **Model:** Society: allow `province_id` null; `province()` relation remains BelongsTo (nullable). Add scope `scopeGlobal($query)` → `whereNull('province_id')`.

### 8.2 Insert hardcoded societies as global

- **Names (typo corrected):**  
  1. ST. ANN'S EDUCATIONAL SOCIETY  
  2. SARVAJANA SNEHA CHARITABLE TRUST  
  3. WILHELM MEYERS DEVELOPMENTAL SOCIETY  
  4. ST. ANN'S SOCIETY, VISAKHAPATNAM  
  5. ST.ANN'S SOCIETY, SOUTHERN REGION  
  6. ST. ANNE'S SOCIETY  
  7. BIARA SANTA ANNA, MAUSAMBI  
  8. ST. ANN'S CONVENT, LURO  
  9. MISSIONARY SISTERS OF ST. ANN  
- **Action:** One-time seeder or Artisan command: for each name, `Society::firstOrCreate(['name' => $name], ['province_id' => null, 'is_active' => true])`. With unique(name) in place, duplicate names are rejected by the DB; firstOrCreate avoids duplicate inserts.

### 8.3 Typo normalization map

- In backfill: map `"ST. ANNS'S SOCIETY, VISAKHAPATNAM"` → `"ST. ANN'S SOCIETY, VISAKHAPATNAM"`. Resolve society by normalized name only (single row per name).

---

## 9. Migration Phase (Revision 5 — Safe order)

**Full migration order (Revision 5):**

1. **Societies constraint update** — unique name, nullable province_id (Section 9.1).
2. **Users.province_id** — add column → run `users:province-backfill` → verification (count NULL = 0) → enforce NOT NULL (Section 9.2).
3. **Projects.province_id** — add column → backfill from user→province_id → verification → NOT NULL (Section 9.3).
4. **Add society_id** to projects and users (Sections 9.3, 9.4).
5. **Backfill society_id** (Section 10).
6. **Dual-write** — application writes both society_name and society_id.
7. **Read switch** — application reads society_id (and resolves name via relation).
8. **Cleanup** — optional later: drop society_name, drop users.province when no longer needed.

---

### 9.1 Step 1: Modify societies table

1. **Data audit (before altering constraints):**  
   Run a one-time check for duplicate `name` values:  
   `SELECT name, COUNT(*) FROM societies GROUP BY name HAVING COUNT(*) > 1`.  
   If any duplicates exist, **abort** and resolve in data (merge, rename, or remove duplicates) so every name is unique.
2. **Drop composite unique:**  
   Drop the existing unique index on `(province_id, name)` (e.g. `unique_province_society`).
3. **Add global unique on name:**  
   Add unique index on `name` column.
4. **Make province_id nullable:**  
   Alter `province_id` to nullable. Keep foreign key (NULL allowed). Keep index on `province_id` for filtering. No cascade-on-delete; provinces cannot be deleted.

**Safe order:** Run data audit first (separate step or command); fix duplicates; then one migration can perform steps 2–4.

---------------------------------------------------
### 9.2 Users Province Normalization Phase (Revision 5)
---------------------------------------------------

**Why this is required before projects.province_id backfill:** Projects.province_id is backfilled from `project.user->province_id`. If users do not have province_id, the projects backfill cannot run. Therefore users must be normalized to province_id first, verified, and only then may projects.province_id backfill and NOT NULL enforcement proceed.

**Steps:**

1. **Add users.province_id (nullable, indexed, FK to provinces.id):**  
   Migration adds column. No cascade on delete (provinces cannot be deleted). Do **not** drop `users.province` yet; keep for dual-write/fallback.
2. **Backfill users.province_id:**  
   Run Artisan command `users:province-backfill`. For each user:  
   `users.province_id = provinces.id WHERE provinces.name = users.province`.  
   Unmatched province names: log as ERROR; do not update.
3. **Verification query:**  
   `SELECT COUNT(*) FROM users WHERE province_id IS NULL;`  
   **Must be zero** before enforcing NOT NULL.
4. **Verification gate (STOP condition):**  
   If any users remain with `province_id` NULL, **DO NOT proceed** to projects.province_id backfill or to NOT NULL enforcement on users or projects. Fix unmatched province names (e.g. align with provinces.name or add missing provinces), re-run backfill, then re-verify.  
   **Verification query to list unresolved users:**  
   `SELECT id, name, province FROM users WHERE province_id IS NULL;`
5. **After verification:**  
   Alter `users.province_id` to NOT NULL (separate migration or step).  
   Keep `users.province` (string) temporarily during dual-write.

**Important:** **users.province_id must be fully normalized (added, backfilled, verified, then NOT NULL) before any projects.province_id backfill or NOT NULL.** Projects backfill depends on `project.user->province_id`; that column must be populated for all users first.

**Example console output — clean state (all matched):**
```
Starting users province_id backfill (chunk size: 500).

--- Summary ---
Total processed: 42
Total updated:   42
Total unmatched: 0
```

**Example console output — unmatched province names (command exits with failure):**
```
Starting users province_id backfill (chunk size: 500).
[ERROR] No province found for name="Generalate" (user id=7, name="John Doe").
[ERROR] User id=8 name="Jane" has empty province; skipping.

--- Summary ---
Total processed: 42
Total updated:   38
Total unmatched: 4

Verification gate: Do NOT proceed to projects.province_id backfill or NOT NULL until all users have province_id set.
Run: SELECT id, name, province FROM users WHERE province_id IS NULL;
```
Fix unmatched names (align provinces.name or add missing provinces), re-run backfill, then re-verify before proceeding.

### 9.3 Step 3: Projects — province_id then society_id

1. **Add province_id to projects (nullable first):**  
   Add column `province_id` unsignedBigInteger nullable, index, FK to provinces.id. (No cascade; provinces cannot be deleted.)
2. **Backfill projects.province_id:**  
   Set from `project.user->province_id` for every project (user province_id is now normalized). See Section 10.
3. **Verification:**  
   Confirm no projects have null province_id (query: `SELECT COUNT(*) FROM projects WHERE province_id IS NULL` → must be 0).
4. **Make projects.province_id NOT NULL:**  
   Alter column to NOT NULL. All new projects must have province_id set by application.
5. **Add projects.society_id:**  
   Add column `society_id` unsignedBigInteger nullable, index, FK to societies.id. Add composite index (province_id, society_id). See Section 9.5.

### 9.4 Step 4: Users — society_id

1. **Add users.society_id:**  
   Add column `society_id` unsignedBigInteger nullable, index, FK to societies.id.

### 9.5 Indexing strategy (Revision 4)

**Projects:**

- **index(province_id)** — Enables province-scoped queries and dashboard grouping without joining users. Province-wise reporting and filters.
- **index(society_id)** — Society-scoped queries, FK lookups, and join performance.
- **Composite index (province_id, society_id)** — Supports queries that filter or group by both (e.g. “projects by province and society”, analytics, reporting). Improves province-wise and society-wise reporting in the same query.

**Why this improves:**

- **Dashboard grouping:** Group projects by province directly from projects table.
- **Province-wise reporting:** Filter by province_id without user join.
- **Society-wise reporting:** Filter by society_id; composite supports province+society together.
- **Future analytics:** Aggregations by (province_id, society_id) use the composite index.

### 9.6 No drop of society_name in this phase

- Keep `society_name` on projects and users until **after** dual-write and read-switch verification.

---

## 10. Backfill Phase (Revision 4)

### 10.1 Projects

- **province_id:** For each project, set `project.province_id = project.user->province_id` (or from user.province via Province lookup). Required before making province_id NOT NULL.
- **society_id:** For each project with society_name set:  
  1. **Normalize name** (typo → correct), e.g. "ST. ANNS'S SOCIETY, VISAKHAPATNAM" → "ST. ANN'S SOCIETY, VISAKHAPATNAM".  
  2. **Find society by name only:** `Society::where('name', $normalizedName)->first()`.  
  3. If found, set `project.society_id`; else leave NULL and keep society_name for manual review.

### 10.2 Users

- **society_id:** For each user with society_name set: normalize name; find society by name only; set `user.society_id` or leave NULL.

### 10.3 Idempotency and batches

- Backfill idempotent: set only where currently null (or re-run and overwrite). Use batches (e.g. chunk 500) and optional transaction per batch. Log counts: updated, skipped (no match), already set.

### 10.4 Verification query examples

- **Projects with null province_id (must be 0 before NOT NULL):**  
  `SELECT COUNT(*) FROM projects WHERE province_id IS NULL;`
- **Projects with null society_id but non-null society_name (unmatched legacy):**  
  `SELECT id, project_id, society_name FROM projects WHERE society_id IS NULL AND society_name IS NOT NULL;`  
  Use for manual review or second-pass backfill after fixing data.
- **Users with null society_id but non-null society_name:**  
  `SELECT id, name, society_name FROM users WHERE society_id IS NULL AND society_name IS NOT NULL;`

### 10.5 New rows during backfill

- Option A: Run backfill in maintenance window; minimal new traffic.  
- Option B: Backfill online; new rows get province_id/society_id from application (dual-write). Second pass for rows where society_id is null and society_name is not null.

---

## 11. Dual-Write Phase

### 11.1 Behaviour

- On **create/update** of project (general info):  
  - Write `province_id` from request (required for new projects).  
  - Write `society_id` from request.  
  - **Also** write `society_name` = society->name when society_id is set (legacy report/export). When society_id is null, leave society_name as submitted or null.
- On **create/update** of user (executor/applicant/provincial): write `society_id`; also write `society_name` = society->name when society_id is set.
- Validation: accept province_id (required for project), society_id; enforce society_id visibility for provincial. Optional society_name during dual-write for backward compatibility.

### 11.2 Duration

- Until read-switch is verified and cleanup is approved (e.g. 1–2 releases).

---

## 12. Read Switch Phase

### 12.1 Display and export

- **Project show / export:** Use `$project->society->name ?? $project->society_name`. Project province from `$project->province_id` (no user join needed).
- **User display:** Use `$user->society->name ?? $user->society_name`.  
- **Reports:** When generating report from project, set `report.society_name = $project->society->name ?? $project->society_name`. Report society name is a historical snapshot; it will not auto-update if the society is renamed. This is acceptable.
- **Dropdowns:** Load via `Society::visibleToUser($user)` (and province filter where needed); preselect society_id; no longer send society_name as value.

### 12.2 Verification

- Confirm no 500s; confirm dropdowns show correct lists and preselect; confirm export and report show correct name; confirm provincial cannot see other provinces’ societies.

---

## 13. Cleanup Phase

### 13.1 Drop society_name

- After dual-write period and verification:  
  - Migration: drop column projects.society_name.  
  - Migration: drop column users.society_name.  
- Remove fallback in code (`?? $project->society_name` etc.) and any validation/request keys for society_name.

### 13.2 Reports

- Reports store society_name (denormalized) populated from project’s society relation when generating. Society name in reports is a historical snapshot and will not auto-update when a society is renamed; this is acceptable. No need to add society_id to report tables in this phase unless desired for future.

---

## 14. Rollback Strategy

### 14.1 During dual-write (before dropping society_name)

- **Rollback code:** Revert to version that reads/writes society_name only. society_id column can remain; no data loss because society_name is still written from society->name.
- **DB:** No rollback of migrations needed for society_id columns.

### 14.2 After dropping society_name

- **Rollback:** Re-add society_name column (nullable); repopulate from `societies.name` via society_id where society_id is not null. Rows that had society_id null would get society_name null (no recovery of legacy free text). Deploy code that uses society_name again.

### 14.3 Societies province_id nullable / projects province_id

- If rollback of "global" feature: leave society.province_id nullable or set to a default province if needed. If rolling back projects.province_id, re-add column as nullable and repopulate from user->province_id where needed.

---

## 15. Testing Checklist

- [ ] General: List all societies; create/edit society (global and province-owned). Societies cannot be deleted.  
- [ ] Provincial: List shows only global + own province; create society (own province); edit only own or global; **Own** global society → appears under province; **Disown** → becomes global again.  
- [ ] Provincial: Cannot Own another province’s society; cannot Disown another province’s society.  
- [ ] Project create/edit: Dropdown shows correct societies (global + context province); preselect society_id; save and reload shows correct name; export shows correct name.  
- [ ] Executor/Applicant create/edit: Same; society_id required; visibility enforced.  
- [ ] Backfill: Run on copy of production data; verify counts; spot-check projects/users with and without society_name.  
- [ ] Dual-write: After save, both society_id and society_name present; report generation uses correct name.  
- [ ] Read fallback: With society_id null and society_name set, display and export still show society_name.  
- [ ] Concurrency: Two provincials Own same global society; one succeeds, one gets conflict or "already owned".

---

## 16. Monitoring & Verification Plan

- **Post-backfill:** Query: count projects/users where society_name is not null and society_id is null (unmatched). Log and optionally alert if above threshold.  
- **Post-deploy (dual-write):** Sample requests; verify society_id and society_name both set.  
- **Post read-switch:** Verify no errors in logs for project/user load and export; verify provincial cannot access other provinces’ societies (manual or automated).  
- **Post-cleanup:** Verify no references to society_name in code; run tests; verify export and reports still work via relation.

---

## Go/No-Go Recommendation for Live Execution

**Invariants (Revision 4):**

- **Global uniqueness of society name** is a hard invariant enforced at database level (unique(name)). Collation prevents case-sensitive duplicates; no app-level casing logic.
- **Ownership is exclusive** and enforced by both DB constraints and controller logic. Societies and provinces cannot be deleted; no cascade-on-delete concerns. Repeated Own/Disown is acceptable; no extra locking or audit required.
- **Reports** store society name as historical snapshot; divergence on rename is acceptable.

**Go**, provided:

1. **Data audit** is run and duplicate society names are resolved before applying unique(name).  
2. **Migration order:** (a) Societies: drop composite unique, add unique(name), province_id nullable; (b) Projects: add province_id (nullable) → backfill from user → verify → make NOT NULL → add society_id + indexes; (c) Users: add society_id.  
3. **Backfill:** Projects get province_id from user->province_id and society_id from name; users get society_id from name. Verification queries run (Section 10.4).  
4. **Dual-write** is deployed and verified before any drop of society_name.  
5. **Own/Disown** uses atomic updates and visibility checks; no direct transfer (Disown then Own).  
6. **Rollback** plan is agreed (revert code + optional column re-add if already dropped).

**Risks** are mitigated by phased rollout, dual-write, fallback reads, and (Revision 4) projects.province_id for reporting without user join; societies/provinces undeletable removes cascade concerns.

---

**Document path:** `Documentations/V2/Societies/Mapping/Society_Project_Mapping_PhasePlan_V2.md`
