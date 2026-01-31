# Log Review: IIES-0027 Create → Submit → Forward → Approve

**Source:** `storage/logs/laravel.log` lines 13763–13916  
**Date:** 2026-01-29  
**Project:** IIES-0027 ("test 2"), Individual - Initial - Educational support

---

## 1. Timeline Summary

| Time (log) | Actor       | Action                 | Result / Log snippet                                                           |
| ---------- | ----------- | ---------------------- | ------------------------------------------------------------------------------ |
| 21:55:03   | Executor    | Create project (store) | IIES-0027 created, draft                                                       |
| 21:55:03   | System      | IIES sections saved    | Personal Info, Family, Education, Financial Support, Attachments, **Expenses** |
| 21:55:12   | Executor    | View project show      | Draft; IIES expenses 16665 / 9500 / 7165 local                                 |
| 21:56:45   | Provincial  | Login                  | —                                                                              |
| 21:57:30   | Executor    | Submit to provincial   | Status → submitted_to_provincial                                               |
| 21:57:40   | Provincial  | Forward to coordinator | Status → forwarded_to_coordinator                                              |
| 21:57:47   | Coordinator | Login                  | —                                                                              |
| 21:57:56   | Coordinator | View project show      | forwarded_to_coordinator; same IIES data                                       |
| 21:58:22   | Coordinator | Approve project        | Status → approved_by_coordinator; **budget saved 0/0**                         |

---

## 2. What Happened at Each Step

### 2.1 Create (21:55:03)

- **ProjectController@store** → GeneralInfoController@store: project created, `project_id`: IIES-0027, `status`: draft.
- IIES sections stored in order: Personal Info, family working members, Immediate Family Details, Educational Background, Financial Support, Attachments, **IIES Estimated Expenses**.
- **IIESExpensesController@store – Success:**  
  `expense_id`: IIES-EXP-0036, `total_expenses`: **16665.00**, `balance_requested`: **9500.00**, `details_count`: 5.  
  (Implied local contribution from type-specific fields: 1500 + 2165 + 3500 = **7165**.)
- No ERROR for IIES Personal Info (unlike IIES-0026); create completed fully.

### 2.2 Executor view show (21:55:12)

- **ProjectController@show** for IIES-0027, `project_status`: draft.
- **IIESExpensesController@show – Retrieved Data:**  
  `iies_total_expenses`: 16665.00, `iies_expected_scholarship_govt`: 1500.00, `iies_support_other_sources`: 2165.00, `iies_beneficiary_contribution`: 3500.00, `iies_balance_requested`: 9500.00.
- View data includes `resolvedFundFields` (resolver used for display).

### 2.3 Submit and forward (21:57:30, 21:57:40)

- Executor: **Project submitted to provincial** (IIES-0027).
- Provincial: **Project forwarded to coordinator** (IIES-0027).  
  Status is now `forwarded_to_coordinator`.

### 2.4 Coordinator view show (21:57:56)

- **ProjectController@show** for IIES-0027, `project_status`: forwarded_to_coordinator.
- Same IIES expense data again: 16665 total, 9500 balance requested, 7165 local (1500 + 2165 + 3500).

### 2.5 Coordinator approve (21:58:22)

Relevant log lines:

```
Coordinator approveProject: project loaded {"project_id":"IIES-0027","project_status":"forwarded_to_coordinator","budgets_count":0}
Coordinator approveProject: calling ProjectStatusService::approve {"project_id":"IIES-0027"}
Project approved {"project_id":"IIES-0027",...,"new_status":"approved_by_coordinator"}
Coordinator approveProject: approve succeeded {"project_id":"IIES-0027","new_status":"approved_by_coordinator"}
Coordinator approveProject: budget check {"project_id":"IIES-0027","overall_project_budget":"0.00","amount_forwarded":"0.00","local_contribution":"0.00","combined_contribution":0.0}
Coordinator approveProject: budget saved {"project_id":"IIES-0027","amount_sanctioned":0.0,"opening_balance":0.0}
Coordinator approveProject: success {...,"overall_project_budget":"0.00","amount_forwarded":"0.00","local_contribution":"0.00","amount_sanctioned":0.0,"opening_balance":0.0}
```

So:

1. Project is loaded with status `forwarded_to_coordinator`, `budgets_count`: 0.
2. **In code**, between “project loaded” and “calling ProjectStatusService::approve”, the controller runs **syncBeforeApproval($project)** and **$project->refresh()**.
3. There is **no** line in **laravel.log** for sync (no “Budget sync applied” or “Budget sync blocked by guard”).  
   Budget sync and guard messages are written to the **budget** channel → `storage/logs/budget-YYYY-MM-DD.log`, not to the default laravel log.
4. Budget block then runs: reads `overall_project_budget`, `amount_forwarded`, `local_contribution` from `$project` → all **0.00**.
5. Approval computes and saves `amount_sanctioned`: 0.0, `opening_balance`: 0.0.

So approval persisted **zeros** for all budget summary fields even though type-specific IIES data had 16665 / 7165 / 9500.

---

## 3. Why Budget Stayed 0.00 at Approval

- **Intended flow:** Before reading budget, the controller calls `BudgetSyncService::syncBeforeApproval($project)`. That is only allowed when `BudgetSyncGuard::canSyncBeforeApproval($project)` is true, which requires:
    - `config('budget.resolver_enabled')` = true
    - `config('budget.sync_to_projects_before_approval')` = true
    - Project status = `forwarded_to_coordinator`
- If the guard passes, the service resolves from IIES expenses, updates `projects`, and `$project->refresh()` should then see overall/local/forwarded (and approval would then compute sanctioned/opening correctly).
- If the guard fails (e.g. flags still false), no sync runs, `projects` stays 0/0/0, and approval reads and saves 0.

So either:

1. **Config not enabled / not loaded**  
   `BUDGET_RESOLVER_ENABLED` and/or `BUDGET_SYNC_BEFORE_APPROVAL` were false or not in effect (e.g. `.env` not reloaded: no `php artisan config:clear` or no restart after adding the vars). Then the guard blocks and no sync runs → approval sees 0.
2. **Sync ran but we don’t see it here**  
   Sync and guard logs go to `storage/logs/budget-YYYY-MM-DD.log`. So from **laravel.log alone** we cannot see whether sync ran or was blocked.

---

## 4. What to Check

1. **Config in effect**
    - Run: `php artisan config:clear` (and restart web server if needed).
    - Confirm `.env` has:
        - `BUDGET_RESOLVER_ENABLED=true`
        - `BUDGET_SYNC_ON_TYPE_SAVE=true`
        - `BUDGET_SYNC_BEFORE_APPROVAL=true`

2. **Budget log (same day as this run)**
    - Open `storage/logs/budget-2026-01-29.log` (or the file for the day you approved).
    - Look for **IIES-0027** and either:
        - **“Budget sync applied”** with trigger `pre_approval` → sync ran; if approval still showed 0, then the bug is elsewhere (e.g. refresh or order of operations).
        - **“Budget sync blocked by guard”** with reason like `pre_approval: guard blocked (...)` → sync did not run because of config/status; fix by enabling flags and clearing config as above.

3. **Re-test after enabling**
    - Create a new IIES project (or use a reverted one), submit → forward → approve again.
    - In **laravel.log** you should still see the same “project loaded” → “calling approve” → “budget check” sequence, but “budget check” should show non-zero overall/local and “budget saved” non-zero sanctioned/opening.
    - In **budget-YYYY-MM-DD.log** you should see “Budget sync applied” for that project with trigger `pre_approval`.

---

## 5. Summary Table (from this log)

| Item                         | Value / Observation                                               |
| ---------------------------- | ----------------------------------------------------------------- |
| Project                      | IIES-0027, "test 2"                                               |
| Type                         | Individual - Initial - Educational support                        |
| IIES total expenses          | 16,665.00                                                         |
| IIES balance requested       | 9,500.00                                                          |
| IIES local (implied)         | 1,500 + 2,165 + 3,500 = 7,165                                     |
| Status at approval           | forwarded_to_coordinator                                          |
| Overall at budget check      | 0.00                                                              |
| Local at budget check        | 0.00                                                              |
| Amount sanctioned saved      | 0.0                                                               |
| Opening balance saved        | 0.0                                                               |
| Sync visible in laravel.log? | No (budget logs go to budget channel)                             |
| Likely cause of zeros        | Pre-approval sync not run (guard: flags off or config not loaded) |

---

## 6. References

- **Controller:** `app/Http/Controllers/CoordinatorController.php` – `approveProject()` (sync before approval, then budget read/save).
- **Sync:** `app/Services/Budget/BudgetSyncService.php` – `syncBeforeApproval()`.
- **Guard:** `app/Services/Budget/BudgetSyncGuard.php` – `canSyncBeforeApproval()`.
- **Budget log channel:** `config/logging.php` → `channels.budget` → `storage/logs/budget-YYYY-MM-DD.log`.
- **Doc:** `PHASE_2_FLAGS_AND_IIES_0026.md` – enable Phase 2 flags and reload config.
