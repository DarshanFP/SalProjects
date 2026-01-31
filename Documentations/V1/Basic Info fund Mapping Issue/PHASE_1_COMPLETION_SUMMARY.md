# Phase 1 – Canonical Budget Resolution (Read-Only) – Completion Summary

**Date:** 2026-01-29  
**Status:** Complete  
**Source:** PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md, PHASE_0_VERIFICATION.md

---

## 1. What Was Implemented

### 1.1 ProjectFundFieldsResolver

**Location:** `app/Services/Budget/ProjectFundFieldsResolver.php`

**Method:** `resolve(Project $project, bool $dryRun = true): array`

**Responsibilities:**

- Determine project type
- Read budget data from correct source per type
- Compute five fund values (overall, forwarded, local, sanctioned, opening)
- Return resolved values only – **no writes**
- Call `BudgetAuditLogger::logResolverCall()` on every invocation
- Call `BudgetAuditLogger::logDiscrepancy()` when resolved ≠ stored

**Return structure:**

```php
[
    'overall_project_budget' => float,
    'amount_forwarded' => float,
    'local_contribution' => float,
    'amount_sanctioned' => float,
    'opening_balance' => float,
]
```

### 1.2 Resolver Logic Per Project Type

| Project Type                                   | Source                                    | Logic                                                                                                                                                                                                         |
| ---------------------------------------------- | ----------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Development Projects**                       | General Info + `project_budgets`          | overall = `projects.overall_project_budget`; if 0, sum of `this_phase` for current phase. forwarded/local from project. sanctioned = overall − (forwarded + local). opening = sanctioned + forwarded + local. |
| **Livelihood, RST, CIC, CCI, RUT, NEXT_PHASE** | Same as Development                       | Uses `projects` + `budgets` (phase sum fallback when overall = 0).                                                                                                                                            |
| **IIES**                                       | `ProjectIIESExpenses`                     | overall = `iies_total_expenses`, forwarded = 0, local = scholarship + support_other + beneficiary, sanctioned = `iies_balance_requested`, opening = overall.                                                  |
| **IES**                                        | `ProjectIESExpenses` (first row)          | overall = `total_expenses`, forwarded = 0, local = expected_scholarship + support_other + beneficiary, sanctioned = `balance_requested`, opening = overall.                                                   |
| **ILP**                                        | `ProjectILPBudget` (multiple rows)        | overall = sum(`cost`), forwarded = 0, local = first row `beneficiary_contribution`, sanctioned = first row `amount_requested`, opening = overall.                                                             |
| **IAH**                                        | `ProjectIAHBudgetDetails` (multiple rows) | overall = sum(`amount`), forwarded = 0, local = first row `family_contribution`, sanctioned = first row `amount_requested`, opening = overall.                                                                |
| **IGE**                                        | `ProjectIGEBudget` (multiple rows)        | overall = sum(`total_amount`), forwarded = 0, local = sum(scholarship_eligibility) + sum(family_contribution), sanctioned = sum(`amount_requested`), opening = overall.                                       |

**Fallback:** When type-specific data is missing, returns project's current stored values.

### 1.3 Logging (MANDATORY)

- **Every resolver call:** `BudgetAuditLogger::logResolverCall(project_id, project_type, resolved_values, dry_run)`
- **When resolved ≠ stored:** `BudgetAuditLogger::logDiscrepancy(project_id, project_type, resolved, stored)`
- Logs written to `storage/logs/budget-YYYY-MM-DD.log` (budget channel)

### 1.4 Optional View Integration

**Condition:** `config('budget.resolver_enabled') === true`

**Insertion point:** `ProjectController::show()` – passes `$resolvedFundFields` to view when resolver is enabled.

**View:** `resources/views/projects/partials/Show/general_info.blade.php`

- When `$resolvedFundFields` is present, displays resolved values with label: **(Computed – Not Yet Approved)**
- No form changes; display only

---

## 2. Example Log Output

### 2.1 Resolver Call

```
[2026-01-29 10:21:17] local.INFO: Budget resolver called {
  "project_id": "DP-0001",
  "project_type": "Development Projects",
  "resolved_values": {
    "overall_project_budget": 789000.0,
    "amount_forwarded": 0.0,
    "local_contribution": 0.0,
    "amount_sanctioned": 789000.0,
    "opening_balance": 789000.0
  },
  "dry_run": true
}
```

### 2.2 Discrepancy Log

```
[2026-01-29 10:21:28] local.INFO: Budget discrepancy detected {
  "project_id": "IOGEP-0001",
  "project_type": "Institutional Ongoing Group Educational proposal",
  "resolved": {
    "overall_project_budget": 2927400.0,
    "amount_forwarded": 0.0,
    "local_contribution": 970000.0,
    "amount_sanctioned": 1957400.0,
    "opening_balance": 2927400.0
  },
  "stored": {
    "overall_project_budget": 0.0,
    "amount_forwarded": 0.0,
    "local_contribution": 0.0,
    "amount_sanctioned": 0.0,
    "opening_balance": 0.0
  }
}
```

---

## 3. Discrepancies Discovered (Sample Run)

| Project ID | Type                                             | Resolved Overall | Stored Overall | Resolved Sanctioned | Stored Sanctioned |
| ---------- | ------------------------------------------------ | ---------------- | -------------- | ------------------- | ----------------- |
| DP-0001    | Development Projects                             | 789000           | 789000         | 789000              | 0                 |
| IOES-0001  | Individual - Ongoing Educational support         | 123400           | 106400         | 106400              | 0                 |
| ILA-0001   | Individual - Livelihood Application              | 342000           | 339000         | 339000              | 0                 |
| IAH-0001   | Individual - Access to Health                    | 202000           | 200000         | 200000              | 0                 |
| IOGEP-0001 | Institutional Ongoing Group Educational proposal | 2927400          | 0              | 1957400             | 0                 |

**Observations:**

- **Individual/IGE types:** `projects` table often has 0 or partial values; type-specific tables hold the correct data.
- **Development (DP-0001):** Stored `opening_balance` was 0; resolved correctly computes 789000.
- **IGE (IOGEP-0001):** All stored values were 0; resolved values come from `project_IGE_budget`.

---

## 4. Guards & Constraints (Verified)

- [x] Always use `dryRun = true` in Phase 1
- [x] No `$project->update()` calls
- [x] No project status changes
- [x] No changes to approval controllers
- [x] No changes to report controllers
- [x] No writes to `projects` table from resolver

---

## 5. How to Enable Phase 1 View Integration

Add to `.env`:

```
BUDGET_RESOLVER_ENABLED=true
```

Or in `config/budget.php`:

```php
'resolver_enabled' => true,
```

When enabled, project show page will display resolved fund values with **(Computed – Not Yet Approved)** label.

---

## 6. Rollback (Phase 1)

1. Set `BUDGET_RESOLVER_ENABLED=false` (or remove from .env)
2. Remove resolver call from `ProjectController::show()` (lines adding `resolvedFundFields` to `$data`)
3. Revert `general_info.blade.php` to use only `$project` values
4. Optionally delete `ProjectFundFieldsResolver.php`

No database changes; no behaviour change when resolver is disabled.

---

## 7. Next Steps

**Phase 2:** Controlled sync to `projects` – enable `sync_to_projects_on_type_save` and `sync_to_projects_before_approval` after Phase 1 logs are reviewed.

**DO NOT PROCEED TO PHASE 2** until explicitly instructed.
