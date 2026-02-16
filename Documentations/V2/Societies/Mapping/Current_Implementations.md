# Society → Project Mapping — Current Implementations

**Last updated:** Post-Phase 3, post-normalization Round 3 (5 deterministic mappings executed)

---

## 1. Migration Files

| Migration                                                               | Purpose                                                   |
| ----------------------------------------------------------------------- | --------------------------------------------------------- |
| `2026_01_11_165554_create_provinces_table.php`                          | Create provinces table                                    |
| `2026_01_11_165558_add_province_center_foreign_keys_to_users_table.php` | Add province_id, center_id to users                       |
| `2026_01_11_170202_migrate_existing_provinces_and_centers_data.php`     | Backfill province_id from province string                 |
| `2026_02_10_232014_add_province_id_to_users_table.php`                  | Ensure users.province_id (nullable, FK)                   |
| `2026_02_10_235454_make_societies_province_id_nullable.php`             | societies.province_id → nullable                          |
| `2026_02_13_161757_enforce_unique_name_on_societies.php`                | Drop composite unique(province_id,name), add unique(name) |
| `2026_02_15_173140_enforce_users_province_id_not_null.php`              | users.province_id → NOT NULL, FK ON DELETE RESTRICT       |
| `2026_02_15_173841_add_province_id_to_projects_table.php`               | Add projects.province_id (nullable, FK)                   |
| `2026_02_15_173924_enforce_projects_province_id_not_null.php`           | projects.province_id → NOT NULL                           |

---

## 2. Phase Execution Summary

### Phase 0 — Audit & Data Cleanup

- Duplicate societies resolved
- Canonical societies seeded
- Province mismatch resolved
- Audit PASSED (WITH WARNINGS)

### Phase 1 — Global Unique Society Name

- Dropped composite `unique(province_id, name)` on societies
- Added `unique(name)` on societies
- Verified duplicate insert rejection

### Phase 2 — users.province_id Enforcement

- Pre-check: 0 users with NULL province_id
- Dropped FK (had ON DELETE SET NULL)
- Altered `province_id` to NOT NULL
- Re-added FK with ON DELETE RESTRICT
- Post-check: province_id Null = NO

### Phase 3 — projects.province_id Introduction

- Added `province_id` (nullable, indexed, FK to provinces)
- Backfilled from `users.province_id` via `projects.user_id`
- 135 rows updated
- Verified 0 NULL, 0 orphans
- Enforced NOT NULL via second migration

### Society Name Normalization (Partial)

**Batch 1**
- **Legacy values:** `St Anns Society`, `St. Ann's Society` → `ST. ANN'S SOCIETY, VISAKHAPATNAM`
- **Rows updated:** 0 projects, 2 users

**Batch 2**
- **Legacy value:** `ST. ANNE'S SOCIETY` → `ST. ANNE'S SOCIETY, DIVYODAYA`
- **Rows updated:** 1 project, 1 user

**Batch 3 (Round 3 — 5 deterministic mappings)**
| Legacy | Canonical | Projects | Users |
|--------|-----------|----------|-------|
| SSCT | SARVAJANA SNEHA CHARITABLE TRUST | 0 | 1 |
| ST. ANNS'S SOCIETY, VISAKHAPATNAM | ST. ANN'S SOCIETY, VISAKHAPATNAM | 15 | 1 |
| St.Anns Educational Society | ST. ANN'S EDUCATIONAL SOCIETY | 0 | 4 |
| St.Ann's society, Southern Region | ST.ANN'S SOCIETY, SOUTHERN REGION | 0 | 5 |
| Wilhelm Meyers Development Society | WILHELM MEYERS DEVELOPMENTAL SOCIETY | 0 | 1 |

**Cumulative:** 16 projects, 20 users normalized. Project resolution rate: **100%**.

---

## 3. Key SQL Used

### Backfill projects.province_id

```sql
UPDATE projects p
JOIN users u ON p.user_id = u.id
SET p.province_id = u.province_id
WHERE p.province_id IS NULL;
```

### Society name normalization (executed)

**Batch 1**
```sql
UPDATE users
SET society_name = 'ST. ANN''S SOCIETY, VISAKHAPATNAM'
WHERE society_name IN ('St Anns Society', 'St. Ann''s Society');
```

**Batch 2**
```sql
UPDATE projects SET society_name = 'ST. ANNE''S SOCIETY, DIVYODAYA' WHERE society_name = 'ST. ANNE''S SOCIETY';
UPDATE users SET society_name = 'ST. ANNE''S SOCIETY, DIVYODAYA' WHERE society_name = 'ST. ANNE''S SOCIETY';
```

**Batch 3**
```sql
-- SSCT, ST. ANNS'S, St.Anns Educational Society, St.Ann's society Southern Region, Wilhelm Meyers
UPDATE projects SET society_name = 'SARVAJANA SNEHA CHARITABLE TRUST' WHERE society_name = 'SSCT';
UPDATE projects SET society_name = 'ST. ANN''S SOCIETY, VISAKHAPATNAM' WHERE society_name = 'ST. ANNS''S SOCIETY, VISAKHAPATNAM';
UPDATE projects SET society_name = 'ST. ANN''S EDUCATIONAL SOCIETY' WHERE society_name = 'St.Anns Educational Society';
UPDATE projects SET society_name = 'ST.ANN''S SOCIETY, SOUTHERN REGION' WHERE society_name = 'St.Ann''s society, Southern Region';
UPDATE projects SET society_name = 'WILHELM MEYERS DEVELOPMENTAL SOCIETY' WHERE society_name = 'Wilhelm Meyers Development Society';
-- Same 5 mappings on users
```

---

## 4. Current Database State

| Table     | Column       | Nullable | Index  | FK                     |
| --------- | ------------ | -------- | ------ | ---------------------- |
| societies | name         | NO       | unique | —                      |
| societies | province_id  | YES      | —      | provinces(id)          |
| users     | province_id  | NO       | yes    | provinces(id) RESTRICT |
| users     | province     | YES      | —      | —                      |
| users     | society_name | YES      | —      | —                      |
| projects  | province_id  | NO       | yes    | provinces(id) RESTRICT |
| projects  | society_name | YES      | —      | —                      |

**Not yet introduced:** society_id on users, society_id on projects.

---

## 5. Integrity Metrics (Approx.)

| Metric                               | Value |
| ------------------------------------ | ----- |
| Projects with society_name           | 135   |
| Projects resolving to societies.name | 135   |
| Project society resolution rate      | 100%  |
| Users with NULL province_id          | 0     |
| Projects with NULL province_id       | 0     |
| Orphan province_id                   | 0     |

---

## 6. Outstanding Work

- Remaining society_name variants (e.g. "St. Ann's Society Southern Region" — 2 users; manual review)
- Introduce society_id to users and projects
- Backfill society_id
- Dual-write and read switchover
- Cleanup legacy society_name

---

## 7. Reference Documents

| Document                                         | Purpose                                |
| ------------------------------------------------ | -------------------------------------- |
| `Society_Project_Migration_Status_PostPhase3.md` | Post-Phase 3 state snapshot            |
| `Society_Name_Normalization_Plan.md`             | Normalization design and mapping table |
