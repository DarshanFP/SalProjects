# M5.2 — Pre-Approval Expense Guard Audit

**Milestone:** M5 — Financial Tracking Enhancement  
**Phase:** M5.2 — Pre-Approval Expense Guard Audit  
**Mode:** STRICTLY READ-ONLY (No Code Changes)

**Objective:** Verify whether the system currently prevents recording expenses before a project is approved. All expense entry points have been inspected.

---

## STEP 1 — Identify Expense Controllers

### Controllers that store or update expense-related data

| Controller | File | Store/Update Methods | Routes (if explicit) |
|------------|------|----------------------|------------------------|
| **IESExpensesController** | `app/Http/Controllers/Projects/IES/IESExpensesController.php` | `store()` (line 25), `update()` (line 161, delegates to store) | Invoked via `ProjectController::update` / `ProjectController::store` (project type IES). No direct route. |
| **IIESExpensesController** | `app/Http/Controllers/Projects/IIES/IIESExpensesController.php` | `store()` (line 22), `update()` (line 183, delegates to store) | Invoked via `ProjectController::update` / `ProjectController::store` (project type IIES). No direct route. |
| **IAHBudgetDetailsController** | `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php` | `store()` (line 25), `update()` (line 104, then store) | Invoked via `ProjectController::update` / `ProjectController::store` (project type IAH). No direct route. |
| **IAHEarningMembersController** | `app/Http/Controllers/Projects/IAH/IAHEarningMembersController.php` | `store()` (line 18), `update()` (line 79, delegates to store) | Invoked via `ProjectController::update` / `ProjectController::store` (project type IAH). No direct route. |
| **BudgetController** | `app/Http/Controllers/Projects/BudgetController.php` | `store()` (line 23), `update()` (line 79) | Project budget phases; no `addExpense` method in controller. |
| **ReportController** (Monthly) | `app/Http/Controllers/Reports/Monthly/ReportController.php` | `store()` (line 135) — creates DPReport and DPAccountDetail (expenses) via `handleAccountDetails()` | `POST reports/monthly/store` → `monthly.report.store` (routes/web.php line 441). `GET reports/monthly/create/{project_id}` (line 440). |
| **BudgetController (expense route)** | — | Route points to `addExpense` | `POST /budgets/{project_id}/expenses` (routes/web.php line 104). **Method `addExpense` does not exist** in `BudgetController` (pre-existing contract issue). |

### Other expense-related controllers (report-level, not project-type budgets)

- **MonthlyDevelopmentProjectController** — stores `DPAccountDetail` (expenses) for monthly development project reports.
- **DevelopmentProjectController** (Quarterly), **SkillTrainingController**, **DevelopmentLivelihoodController**, **InstitutionalSupportController**, **WomenInDistressController** — store quarterly report account/expense data (RQDPAccountDetail etc.).

**ProjectController** invokes IES/IIES/IAH expense controllers from:
- `store()`: type-specific handlers in `getProjectTypeStoreHandlers()` (e.g. lines 666, 730, 745–748).
- `update()`: switch on `project_type` (e.g. lines 1475, 1492–1495, 1507).
- `destroy()` (revert/delete type data): lines 1652, 1669–1672, 1684.

---

## STEP 2 — Inspect Guard Conditions

**Definition of “pre-approval expense guard” for this audit:**  
A guard that **prevents** recording expenses when the project is **not** approved (i.e. only allow expense recording when `$project->status` is in approved statuses).

**Current implementation note:**  
`BudgetSyncGuard::canEditBudget($project)` (used in IES, IIES, IAH Budget) does the **opposite**: it **allows** edits when the project is **not** approved and **blocks** edits when the project **is** approved (post-approval lock). It is **not** a pre-approval expense guard.

| Controller | Guard Exists? | Type | File:Line |
|------------|---------------|------|-----------|
| **IESExpensesController** | Yes (post-approval only) | `BudgetSyncGuard::canEditBudget($project)` in `store()`; blocks when project **is** approved. No guard in `destroy()`. | IESExpensesController.php:27–36 (store), 161 (update → store), 166–187 (destroy: no guard) |
| **IIESExpensesController** | Yes (post-approval only) | `BudgetSyncGuard::canEditBudget($project)` in `store()`. No guard in `destroy()`. | IIESExpensesController.php:24–35 (store), 183 (update → store), 188–196 (destroy: no guard) |
| **IAHBudgetDetailsController** | Yes (post-approval only) | `BudgetSyncGuard::canEditBudget($project)` in `store()` and `update()`. No guard in `destroy()`. | IAHBudgetDetailsController.php:27–36 (store), 106–115 (update), 190–212 (destroy: no guard) |
| **IAHEarningMembersController** | **No** | No status check, no BudgetSyncGuard, no policy. | IAHEarningMembersController.php:18–74 (store), 79–82 (update), 134–152 (destroy) |
| **BudgetController** | Yes (post-approval only) | `BudgetSyncGuard::canEditBudget($project)` in `store()` and `update()`. | BudgetController.php:25–35 (store), 79–92 (update) |
| **ReportController** (Monthly) | **No** | No project approval check in `create()` or `store()`. Stores DPAccountDetail (expenses) for any project. | ReportController.php:61–96 (create), 135–241 (store), 547+ (handleAccountDetails) |

**Summary:**

- **Pre-approval expense guard (block expense when project not approved):** **None** of the above controllers implement this.
- **Post-approval lock (block budget/expense edit when project approved):** Present in IESExpensesController, IIESExpensesController, IAHBudgetDetailsController, BudgetController via `BudgetSyncGuard::canEditBudget` (when `config('budget.restrict_general_info_after_approval')` is true).
- **IAHEarningMembersController** has no approval-related guard at all.
- **destroy()** methods on IES/IIES/IAH expense controllers do not call `BudgetSyncGuard::canEditBudget`; deletion is not guarded by approval status.

---

## STEP 3 — Check Route-Level Guards

### routes/web.php

- **`POST /budgets/{project_id}/expenses`** (line 104)  
  - In `Route::middleware('auth')->group(...)` (line 102).  
  - Points to `BudgetController::addExpense`, which **does not exist** in `BudgetController`.  
  - No middleware restricting by project approval.

- **Project update route:**  
  - `PUT projects/{project_id}/update` → `ProjectController::update` (line 422).  
  - Inside executor/applicant/provincial/coordinator/general route group; authorized by `UpdateProjectRequest`, which uses `ProjectPermissionHelper::canEdit($project, $user)`.  
  - `canEdit` requires `ProjectStatus::isEditable($project->status)` (draft, reverted, etc.) and ownership. So when project is **approved**, the whole update (including expense sections) is **denied**. When project is **not** approved (editable), update is **allowed** — so expense recording via project edit form is **allowed before approval**.

- **Monthly report routes:**  
  - `GET reports/monthly/create/{project_id}` (line 440), `POST reports/monthly/store` (line 441).  
  - Under `Route::prefix('reports/monthly')`; no middleware or condition requiring project to be approved.  
  - Expense data (DPAccountDetail) can be stored for any project.

**Conclusion:** No route-level guard enforces “expense recording only when project is approved.” Expense-related routes are either behind auth only or behind project-edit permission (which allows edits when project is not approved).

---

## STEP 4 — UI-Only Blocking?

### Blade / frontend

- **Project edit form (IES/IIES/IAH expense sections):**  
  - Edit partials (e.g. `resources/views/projects/partials/Edit/IES/estimated_expenses.blade.php`, Edit/IIES/estimated_expenses, Edit/IAH/budget_details.blade.php) do **not** condition “Add expense” or the form on project status/approved.  
  - No `@if($project->status === 'approved')` or similar to hide or disable expense inputs.  
  - Submit is the full project update form; visibility of the expense section is not restricted by approval status.

- **Budgets view:**  
  - `resources/views/budgets/view.blade.php` (line 18): form posts to `/budgets/{{ $projectBudget->project_id }}/expenses` with “Add Expense” button.  
  - No check for project status or approval; form is always shown.  
  - Backend endpoint `addExpense` is missing, so this is a broken contract, not a pre-approval guard.

- **Monthly report create:**  
  - Create/store flow is not conditioned on project approval in the UI or routes.

**Conclusion:** There is **no** UI-only blocking of expense recording for non-approved projects. Expense buttons/forms are not hidden or disabled based on approval. Any “blocking” would have to come from backend; the only backend guard present is the **post-approval** lock (no edits when approved), not a pre-approval guard.

---

## STEP 5 — Final Conclusion

### 1) Is expense creation technically possible before approval?

**Yes.**  
- Type-specific project expenses (IES, IIES, IAH budget details / earning members) are stored/updated when the project is in an **editable** status (draft, reverted, etc.) via `ProjectController::update` / `store`. There is no backend check that blocks this because the project is “not yet approved.”  
- Monthly reports (and their expense rows in DPAccountDetail) can be created for any project; `ReportController::create` and `store` do not check project approval.  
- The only backend restriction is the **post-approval** lock: once the project is approved, budget/expense **edits** are blocked by `BudgetSyncGuard::canEditBudget` (where implemented and when config is on). That does not prevent recording expenses **before** approval.

### 2) Is backend enforcing approval before allowing expense recording?

**No.**  
No controller or route enforces “expenses may only be recorded when the project is approved.” The only approval-related logic is “do not allow budget/expense edits when the project **is** approved.”

### 3) Is blocking only UI-level?

**No.**  
There is no UI-level blocking of expense recording for non-approved projects. Expense forms/buttons are not hidden or disabled based on approval status.

### 4) Risk level

**HIGH.**  
- Expense recording is **allowed** for projects that are not yet approved (draft, reverted, etc.).  
- There is **no** pre-approval expense guard at any expense entry point (project-type budgets, IAH earning members, monthly/quarterly report expenses).  
- Policy intent to “prevent recording expenses before approval” is **not** implemented in code.  
- One expense route (`/budgets/{project_id}/expenses`) references a non-existent method (`addExpense`), which is a separate contract issue.

---

## Summary Table

| Entry Point | Pre-approval guard? | Post-approval lock? | Notes |
|-------------|---------------------|---------------------|--------|
| IESExpensesController store/update | No | Yes (canEditBudget) | destroy() unguarded |
| IIESExpensesController store/update | No | Yes (canEditBudget) | destroy() unguarded |
| IAHBudgetDetailsController store/update | No | Yes (canEditBudget) | destroy() unguarded |
| IAHEarningMembersController store/update/destroy | No | No | No status guard |
| BudgetController store/update | No | Yes (canEditBudget) | addExpense route exists, method missing |
| ReportController (monthly) store | No | No | Creates report + account details for any project |
| ProjectController update (gate) | No | Yes (canEdit blocks when approved) | canEdit allows edit when not approved → expenses allowed before approval |

---

**M5.2 Pre-Approval Expense Guard Audit Complete — No Code Changes Made**
