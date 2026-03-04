# Phase 4 – Database Hardening (MariaDB 11.x)

## 1. MariaDB Version Confirmed

- **Target:** MariaDB 10.2.1+ (CHECK constraints supported)
- **Recommended:** MariaDB 11.x as specified
- **MySQL alternative:** MySQL 8.0.16+ (same CHECK syntax)

---

## 2. Data Verification Queries

Run these **before** applying the migration to confirm no violations exist. Do **not** fix data automatically.

### 2.1 Negative opening balances

```sql
SELECT project_id, opening_balance
FROM projects
WHERE opening_balance < 0;
```

### 2.2 Negative sanctioned amounts

```sql
SELECT project_id, amount_sanctioned
FROM projects
WHERE amount_sanctioned < 0;
```

### 2.3 NULL values

```sql
SELECT project_id
FROM projects
WHERE opening_balance IS NULL
   OR amount_sanctioned IS NULL;
```

**Note:** The CHECK constraints use `>= 0`, so NULL is allowed (SQL three-valued logic: `NULL >= 0` evaluates to NULL, and CHECK passes for NULL). These queries are for visibility and manual review only. Negative values will cause migration failure.

---

## 3. Migration File Created

| File | Purpose |
|------|---------|
| `database/migrations/2025_03_03_120000_add_financial_constraints_to_projects_table.php` | Adds CHECK constraints for `opening_balance` and `amount_sanctioned` |

---

## 4. Constraints Added

| Constraint Name | Column | Rule |
|-----------------|--------|------|
| `chk_projects_opening_balance_non_negative` | `opening_balance` | `>= 0` |
| `chk_projects_amount_sanctioned_non_negative` | `amount_sanctioned` | `>= 0` |

- Non-negative only (no equality checks).
- NULL values remain allowed.
- Enforced on INSERT and UPDATE.

---

## 5. Rollback Strategy

```bash
php artisan migrate:rollback --step=1
```

This runs the migration `down()` method, which drops both CHECK constraints.

---

## 6. Deployment Instructions

1. **Backup database** before deployment.
2. **Run verification queries** (Section 2) on production/staging.
3. **Fix any negative values** manually if found (do not rely on automatic fixes).
4. **Run migration** only when all queries return no violations:
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

## 7. Risk Assessment

| Risk | Mitigation |
|------|------------|
| Migration fails due to existing negative values | Run verification queries first; fix data before migrating |
| MariaDB/MySQL version too old | Confirm version is 10.2.1+ (MariaDB) or 8.0.16+ (MySQL) |
| Application inserts negative values | Application logic already validated (BudgetValidationService, FinancialInvariantService); DB constraint adds a safety net |
| NULL handling | CHECK allows NULL; existing nullable semantics preserved |

---

## 8. Summary

- Migration created; **not** executed.
- No application logic changed.
- No automatic data changes.
- Constraints enforce non-negative values for `opening_balance` and `amount_sanctioned`.
