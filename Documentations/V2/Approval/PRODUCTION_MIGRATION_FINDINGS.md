# Production Migration Findings

**Date:** March 3, 2026  
**Project:** SAL Projects - Laravel Application  
**Scope:** Documentations/V2/Approval – All implemented phases  
**Purpose:** Identify which migrations must be run on production

---

## Executive Summary

After reviewing all markdown files in the `Documentations/V2/Approval` folder and subfolders, **one migration requires execution on production** to complete the Phase 4 database hardening work. All other implemented phases (0–3) involve code-only changes and do not require new migrations.

---

## Phase-by-Phase Migration Impact

| Phase | Implementation Focus | Migrations Involved? | Production Action |
|-------|----------------------|----------------------|-------------------|
| Phase 0 | Division-by-zero fix | No | Code deploy only |
| Phase 1 | Testing foundation | No | N/A |
| Phase 1.1 | Approval component tests | Modified 2024 migrations for SQLite test compatibility | No new migrations needed |
| Phase 1.2 | Real approval flow tests | No | N/A |
| Phase 2A | Atomic approval fix | No | Code deploy only |
| Phase 2B | Financial invariant enforcement | No | Code deploy only |
| Phase 3 | Redirect standardization | No | Code deploy only |
| **Phase 4** | **Database hardening** | **Yes** | **Run migration (see below)** |

---

## Migration That Must Run on Production

### File

`database/migrations/2025_03_03_120000_add_financial_constraints_to_projects_table.php`

### Status

The Phase 4 documentation states:

> Migration created; **not** executed.

The migration exists in the codebase but has not been run anywhere yet.

### Constraints Added

| Constraint Name | Column | Rule |
|-----------------|--------|------|
| `chk_projects_opening_balance_non_negative` | `opening_balance` | `>= 0` |
| `chk_projects_amount_sanctioned_non_negative` | `amount_sanctioned` | `>= 0` |

- Enforces non-negative values only.
- NULL values remain allowed.
- Enforced on INSERT and UPDATE.

---

## Pre-Migration Steps (Mandatory)

### 1. Backup Database

Create a full database backup before running the migration.

### 2. Run Verification Queries

Check that no existing data violates the constraints. **Do not** fix data automatically; review and correct manually if needed.

```sql
-- Negative opening_balance
SELECT project_id, opening_balance
FROM projects
WHERE opening_balance < 0;

-- Negative amount_sanctioned
SELECT project_id, amount_sanctioned
FROM projects
WHERE amount_sanctioned < 0;
```

**Note:** Any negative values will cause the migration to fail. Fix them before running the migration.

### 3. Fix Data (If Violations Found)

Manually update or correct any negative values identified above. Do not rely on automatic data fixes.

---

## Deployment Instructions

1. **Backup** the production database.
2. **Run verification queries** (Section above) on production/staging.
3. **Fix any negative values** manually if found.
4. **Run the migration** only when all queries return no violations:
   ```bash
   php artisan migrate
   ```
5. **Verify** constraints are active:
   ```sql
   SELECT CONSTRAINT_NAME, CHECK_CLAUSE
   FROM information_schema.CHECK_CONSTRAINTS
   WHERE CONSTRAINT_SCHEMA = DATABASE()
   AND CONSTRAINT_NAME LIKE 'chk_projects_%';
   ```

---

## Rollback

If rollback is needed:

```bash
php artisan migrate:rollback --step=1
```

This drops both CHECK constraints.

---

## Prerequisites

- **MariaDB 10.2.1+** or **MySQL 8.0.16+** (CHECK constraint support required)

---

## Other Migrations (2024_08_10_*)

The following migrations were modified in Phase 1.1 for SQLite test compatibility:

- `2024_08_10_101234_update_project_objectives_table.php`
- `2024_08_10_101255_update_project_risks_table.php`
- `2024_08_10_101301_update_project_activities_table.php`
- `2024_08_10_101319_update_project_results_table.php`

- Changes add SQLite driver checks; MySQL/MariaDB behavior is unchanged.
- If these migrations have already run on production, they will not run again.
- No additional production action required.

---

## Summary Checklist

| Item | Status |
|------|--------|
| Phase 4 migration (`2025_03_03_120000`) must run on production | **Yes** |
| Phases 0–3 require migrations | No |
| Verification queries before migration | **Required** |
| Database backup before migration | **Required** |
| MariaDB 10.2.1+ or MySQL 8.0.16+ | **Required** |

---

## Source Documents Reviewed

- `COMPREHENSIVE_CODEBASE_AUDIT.md`
- `DIVISION_BY_ZERO_BUDGET_VIEW_AUDIT.md`
- `PHASE_WISE_IMPLEMENTATION_PLAN.md`
- `PROJECT_APPROVAL_REDIRECTION_AUDIT.md`
- `Implemented/PHASE_0_IMPLEMENTED.md`
- `Implemented/PHASE_1_IMPLEMENTED.md`
- `Implemented/PHASE_1_1_APPROVAL_TESTS_IMPLEMENTED.md`
- `Implemented/PHASE_1_2_REAL_APPROVAL_FLOW_TESTS_IMPLEMENTED.md`
- `Implemented/PHASE_2A_ATOMIC_APPROVAL_FIX_IMPLEMENTED.md`
- `Implemented/PHASE_2B_FINANCIAL_INVARIANT_ENFORCEMENT_IMPLEMENTED.md`
- `Implemented/PHASE_3_REDIRECT_STANDARDIZATION_IMPLEMENTED.md`
- `Implemented/PHASE_4_DATABASE_HARDENING_IMPLEMENTED.md`

---

*Document created from audit of all approval-related documentation.*
