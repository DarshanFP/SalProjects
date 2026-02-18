# Southern Region Rename — Database Audit & Review

**Date:** 2026-02-17  
**Objective:** Rename society **id 8** from **ST. ANN'S SOCIETY, BANGLORE** to **ST. ANN'S SOCIETY, SOUTHERN REGION**, and update all tables that store this society by **name** (denormalized `society_name`) so display and exports stay consistent.  
**Scope:** Audit only. No database changes in this step — this document is for review and execution planning.

---

## 1. Planned change

| Item | Current | Target |
|------|---------|--------|
| **societies.id** | 8 | 8 (unchanged) |
| **societies.name** | ST. ANN'S SOCIETY, BANGLORE | **ST. ANN'S SOCIETY, SOUTHERN REGION** |

All references by **society_id = 8** will automatically show the new name once `societies.name` is updated (via relation). All references by **society_name** (denormalized string) must be updated to the new name in the same run or immediately after.

---

## 2. Tables and columns audited

Tables that reference society **by id** and/or **by name**:

| Table | Column(s) | Type | Action for rename |
|-------|-----------|------|-------------------|
| **societies** | `id`, `name` | PK, string | Update `name` WHERE `id` = 8 |
| **users** | `society_id`, `society_name` | FK, string | Update `society_name` WHERE `society_id` = 8 (or WHERE `society_name` = old name) |
| **projects** | `society_id`, `society_name` | FK, string | Update `society_name` WHERE `society_id` = 8 (or WHERE `society_name` = old name) |
| **centers** | `society_id` | FK only | No string column; no update needed. Relation will show new name. |
| **DP_Reports** | `society_name` | string (snapshot) | Update `society_name` WHERE `society_name` = old name |
| **quarterly_reports** | `society_name` | string (snapshot) | Update `society_name` WHERE `society_name` = old name |
| **half_yearly_reports** | `society_name` | string (snapshot) | Update `society_name` WHERE `society_name` = old name |
| **annual_reports** | `society_name` | string (snapshot) | Update `society_name` WHERE `society_name` = old name |
| **oldDevelopmentProjects** | `society_name` | string | Update `society_name` WHERE `society_name` = old name |

**Note:** `society_id` is not changed in any table. Only `societies.name` and denormalized `society_name` columns are updated.

---

## 3. Current database audit (sample environment)

Counts below are from the environment where the audit was run. Production counts may differ; re-run the verification queries on production before and after execution.

**Canonical name used in queries:** `ST. ANN'S SOCIETY, BANGLORE`  
**New name (target):** `ST. ANN'S SOCIETY, SOUTHERN REGION`

### 3.1 Societies

| Check | Result |
|------|--------|
| Row with id = 8 | 1 |
| Current name | ST. ANN'S SOCIETY, BANGLORE |
| province_id | 1 |

### 3.2 Users

| Check | Count |
|------|--------|
| WHERE society_id = 8 | 40 |
| WHERE society_name = 'ST. ANN'S SOCIETY, BANGLORE' | 40 |

(All 40 users have both society_id and society_name in sync; updating society_name will keep denormalized display correct after societies.name is changed.)

### 3.3 Projects

| Check | Count |
|------|--------|
| WHERE society_id = 8 | 58 |
| WHERE society_name = 'ST. ANN'S SOCIETY, BANGLORE' | 58 |

### 3.4 Centers

| Check | Count |
|------|--------|
| WHERE society_id = 8 | 0 |

No update needed; no society_name column.

### 3.5 Report tables (denormalized society_name)

| Table | Rows with society_name = 'ST. ANN'S SOCIETY, BANGLORE' |
|-------|--------------------------------------------------------|
| DP_Reports | 0 |
| quarterly_reports | 0 |
| half_yearly_reports | 0 |
| annual_reports | 0 |

(Counts may be higher on production if reports have been created for this society.)

### 3.6 Legacy / old projects

| Table | Rows with society_name = 'ST. ANN'S SOCIETY, BANGLORE' |
|-------|--------------------------------------------------------|
| oldDevelopmentProjects | 0 |

---

## 4. Verification queries (run on target DB before and after)

Use these on **production** (or target environment) to get actual counts and confirm no stray rows.

```sql
-- Societies: confirm single row to rename
SELECT id, name, province_id FROM societies WHERE id = 8;

-- Users: rows to update (society_name)
SELECT COUNT(*) AS users_by_id FROM users WHERE society_id = 8;
SELECT COUNT(*) AS users_by_name FROM users WHERE society_name = 'ST. ANN''S SOCIETY, BANGLORE';

-- Projects: rows to update (society_name)
SELECT COUNT(*) AS projects_by_id FROM projects WHERE society_id = 8;
SELECT COUNT(*) AS projects_by_name FROM projects WHERE society_name = 'ST. ANN''S SOCIETY, BANGLORE';

-- Report tables: rows to update (society_name)
SELECT COUNT(*) FROM DP_Reports WHERE society_name = 'ST. ANN''S SOCIETY, BANGLORE';
SELECT COUNT(*) FROM quarterly_reports WHERE society_name = 'ST. ANN''S SOCIETY, BANGLORE';
SELECT COUNT(*) FROM half_yearly_reports WHERE society_name = 'ST. ANN''S SOCIETY, BANGLORE';
SELECT COUNT(*) FROM annual_reports WHERE society_name = 'ST. ANN''S SOCIETY, BANGLORE';

-- Legacy
SELECT COUNT(*) FROM oldDevelopmentProjects WHERE society_name = 'ST. ANN''S SOCIETY, BANGLORE';
```

---

## 5. Recommended execution order (for future use)

When implementing the rename (do not run yet — for review only):

1. **Backup** relevant tables or full DB.
2. **societies:**  
   `UPDATE societies SET name = 'ST. ANN''S SOCIETY, SOUTHERN REGION', updated_at = NOW() WHERE id = 8;`
3. **users:**  
   `UPDATE users SET society_name = 'ST. ANN''S SOCIETY, SOUTHERN REGION' WHERE society_id = 8;`
4. **projects:**  
   `UPDATE projects SET society_name = 'ST. ANN''S SOCIETY, SOUTHERN REGION' WHERE society_id = 8;`
5. **DP_Reports:**  
   `UPDATE DP_Reports SET society_name = 'ST. ANN''S SOCIETY, SOUTHERN REGION' WHERE society_name = 'ST. ANN''S SOCIETY, BANGLORE';`
6. **quarterly_reports:**  
   `UPDATE quarterly_reports SET society_name = 'ST. ANN''S SOCIETY, SOUTHERN REGION' WHERE society_name = 'ST. ANN''S SOCIETY, BANGLORE';`
7. **half_yearly_reports:**  
   `UPDATE half_yearly_reports SET society_name = 'ST. ANN''S SOCIETY, SOUTHERN REGION' WHERE society_name = 'ST. ANN''S SOCIETY, BANGLORE';`
8. **annual_reports:**  
   `UPDATE annual_reports SET society_name = 'ST. ANN''S SOCIETY, SOUTHERN REGION' WHERE society_name = 'ST. ANN''S SOCIETY, BANGLORE';`
9. **oldDevelopmentProjects:**  
   `UPDATE oldDevelopmentProjects SET society_name = 'ST. ANN''S SOCIETY, SOUTHERN REGION' WHERE society_name = 'ST. ANN''S SOCIETY, BANGLORE';`
10. **Re-run** the verification queries in §4 and confirm zero rows left with old name where applicable; confirm societies.id 8 has the new name.

---

## 6. Risks and notes

- **Unique constraint:** `societies.name` is globally unique. Ensure no other row has name `ST. ANN'S SOCIETY, SOUTHERN REGION` before updating.
- **Application caches:** If the app caches society names or lists, clear or refresh after the rename.
- **Exports/reports:** Any already-generated PDFs or exports that contain the old name will not change; only DB and future exports reflect the new name.
- **centers:** No change; they reference society by `society_id` only; the relation will resolve to the new name automatically.

---

## 7. Summary

| Item | Status |
|------|--------|
| Tables audited | 9 (societies, users, projects, centers, DP_Reports, quarterly_reports, half_yearly_reports, annual_reports, oldDevelopmentProjects) |
| Columns to update | societies.name (1 row), users.society_name, projects.society_name, and society_name in 4 report tables + oldDevelopmentProjects |
| Current sample counts | 40 users, 58 projects; report/legacy tables 0 in audit env |
| Database changes in this step | **None** — audit and review only |
| Next step | Review this document; when approved, run updates in order per §5 on target DB |

---

**End of audit. No database modifications were performed.**
