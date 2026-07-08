# Phase 4.2 — Audit & Repair Artisan Commands

**Status:** ✅ Implemented  
**Date:** 2026-06-13

## Commands

### Audit (dry-run report)

```bash
php artisan reports:audit-project-fund-fields
php artisan reports:audit-project-fund-fields --project=IAH-0002
php artisan reports:audit-project-fund-fields --output=Documentations/Reports/my_audit.md
```

- Scans **approved** projects only
- Writes markdown report (default: `Documentations/Reports/Phase4_Project_Fund_Fields_Audit_YYYY-MM-DD.md`)
- Prints console table of issues

**Issue codes:**
- `zero_stored_sanctioned` — DB sanctioned = 0 but type tables have value
- `opening_balance_invariant` — stored opening ≠ sanctioned + forwarded + local
- `derived_mismatch:{field}` — stored field differs from type-derived value

### Repair

```bash
# Preview
php artisan reports:repair-project-fund-fields --dry-run

# Single project (prompts for confirmation)
php artisan reports:repair-project-fund-fields --project=ILA-0001

# Batch (non-interactive)
php artisan reports:repair-project-fund-fields --force
```

- Uses `BudgetSyncService::repairApprovedProject()` with trigger `cli_repair`
- Skips projects where type-derived sanctioned is zero
- **Run audit first**; validate on staging before production `--force`

## Production victims (verified on local DB scan)

| project_id | derived_sanctioned |
|------------|-------------------|
| IOGEP-0006 | 493,200.00 |
| IAH-0002   | 100,000.00 |
| ILA-0001   | 150,000.00 |

## Files

- `app/Console/Commands/AuditProjectFundFieldsCommand.php`
- `app/Console/Commands/RepairProjectFundFieldsCommand.php`

## Log grep (post-repair)

Search budget audit log channel for `cli_repair` or `approved_repair` sync entries.
