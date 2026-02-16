# M4.1 — Workflow Transition Audit

**Mode:** STRICTLY READ-ONLY (No Code Changes)  
**Date:** 2026-02-15

---

## SECTION 1 — Status Inventory

### 1.1 Source: `App\Constants\ProjectStatus`

**File:** `app/Constants/ProjectStatus.php`

| Constant | Value |
|----------|--------|
| DRAFT | `draft` |
| REVERTED_BY_PROVINCIAL | `reverted_by_provincial` |
| REVERTED_BY_COORDINATOR | `reverted_by_coordinator` |
| SUBMITTED_TO_PROVINCIAL | `submitted_to_provincial` |
| FORWARDED_TO_COORDINATOR | `forwarded_to_coordinator` |
| APPROVED_BY_COORDINATOR | `approved_by_coordinator` |
| REJECTED_BY_COORDINATOR | `rejected_by_coordinator` |
| APPROVED_BY_GENERAL_AS_COORDINATOR | `approved_by_general_as_coordinator` |
| REVERTED_BY_GENERAL_AS_COORDINATOR | `reverted_by_general_as_coordinator` |
| APPROVED_BY_GENERAL_AS_PROVINCIAL | `approved_by_general_as_provincial` |
| REVERTED_BY_GENERAL_AS_PROVINCIAL | `reverted_by_general_as_provincial` |
| REVERTED_TO_EXECUTOR | `reverted_to_executor` |
| REVERTED_TO_APPLICANT | `reverted_to_applicant` |
| REVERTED_TO_PROVINCIAL | `reverted_to_provincial` |
| REVERTED_TO_COORDINATOR | `reverted_to_coordinator` |

**Approved set (APPROVED_STATUSES):** `approved_by_coordinator`, `approved_by_general_as_coordinator`, `approved_by_general_as_provincial`

**Editable/Submittable:** draft + all reverted statuses (see `getEditableStatuses()` / `getSubmittableStatuses()` lines 43–73).

### 1.2 Project model

**File:** `app/Models/OldProjects/Project.php`

- **Fillable:** includes `'status'` (line 297).
- **No enum:** status is string; no DB enum type referenced in model.
- **Status labels:** `Project::$statusLabels` (lines 390–408) — same set as constants plus display text.

### 1.3 Enums / other constants

- No PHP enum used for project status; all statuses are in `ProjectStatus` class constants.
- Report models (e.g. `DPReport`) have their own status constants (e.g. `STATUS_SUBMITTED_TO_PROVINCIAL`); not mixed with project status in the audit.

### 1.4 List of all possible project statuses (16)

1. `draft`  
2. `reverted_by_provincial`  
3. `reverted_by_coordinator`  
4. `submitted_to_provincial`  
5. `forwarded_to_coordinator`  
6. `approved_by_coordinator`  
7. `rejected_by_coordinator`  
8. `approved_by_general_as_coordinator`  
9. `reverted_by_general_as_coordinator`  
10. `approved_by_general_as_provincial`  
11. `reverted_by_general_as_provincial`  
12. `reverted_to_executor`  
13. `reverted_to_applicant`  
14. `reverted_to_provincial`  
15. `reverted_to_coordinator`  

---

## SECTION 2 — Transition Mapping

### 2.1 Search summary

Searched for: `$status =`, `->status =`, `update(['status'`, `setStatus`, `approve`, `submit`, `revert`, `reject`, `forward` in `app/**/*.php`.

### 2.2 Transitions via ProjectStatusService (single source of truth for status)

| From Status | To Status | Triggered By | Conditions | File:Line |
|-------------|-----------|--------------|------------|-----------|
| (submittable) | SUBMITTED_TO_PROVINCIAL | Executor/Applicant | canSubmit, isSubmittable | ProjectStatusService 35; ProjectController 1751 |
| SUBMITTED_TO_PROVINCIAL, REVERTED_BY_COORDINATOR, REVERTED_BY_GENERAL_AS_COORDINATOR, REVERTED_TO_PROVINCIAL | FORWARDED_TO_COORDINATOR | Provincial / General | role provincial|general; allowed statuses | ProjectStatusService 78; ProvincialController 1533; GeneralController 2584 (approveAsProvincial) |
| FORWARDED_TO_COORDINATOR, REVERTED_BY_COORDINATOR, REVERTED_TO_COORDINATOR | APPROVED_BY_COORDINATOR or APPROVED_BY_GENERAL_AS_COORDINATOR | Coordinator / General | role coordinator|general; allowed statuses | ProjectStatusService 128, 303; CoordinatorController 1091; GeneralController 2541 (approveAsCoordinator) |
| SUBMITTED_TO_PROVINCIAL, FORWARDED_TO_COORDINATOR, REVERTED_BY_COORDINATOR | REVERTED_BY_PROVINCIAL / REVERTED_BY_GENERAL_AS_PROVINCIAL / REVERTED_TO_* | Provincial / General | revertByProvincial allowed statuses | ProjectStatusService 191; ProvincialController 1520; GeneralController 2650 (revertAsProvincial) |
| FORWARDED_TO_COORDINATOR, APPROVED_BY_COORDINATOR, APPROVED_BY_GENERAL_AS_COORDINATOR | REVERTED_BY_COORDINATOR / REVERTED_BY_GENERAL_AS_COORDINATOR / REVERTED_TO_* | Coordinator / General | revertByCoordinator allowed statuses | ProjectStatusService 255; CoordinatorController 1018; GeneralController 2638 (revertAsCoordinator) |
| (various by level) | REVERTED_TO_EXECUTOR / REVERTED_TO_APPLICANT / REVERTED_TO_PROVINCIAL / REVERTED_TO_COORDINATOR | General only | revertToLevel; level-specific allowed statuses | ProjectStatusService 547; GeneralController 2702 |

### 2.3 Transitions by direct assignment (outside service)

| From Status | To Status | Triggered By | Conditions | File:Line |
|-------------|-----------|--------------|------------|-----------|
| (new project) | DRAFT | Executor (create) | Store project flow | ProjectController 768, 772 (`applyPostCommitStatusAndRedirect`); GeneralInfoController 30 (`validated['status'] = DRAFT`) |
| (any) | DRAFT | Executor (update) | save_as_draft true | ProjectController 1524 |
| FORWARDED_TO_COORDINATOR | REJECTED_BY_COORDINATOR | Coordinator | role coordinator, isForwardedToCoordinator | CoordinatorController 1198 |
| APPROVED_BY_GENERAL_AS_COORDINATOR | FORWARDED_TO_COORDINATOR | General (rollback) | Budget validation fails after approveAsCoordinator | GeneralController 2555 |

### 2.4 Route → action mapping

| Route | Method | Controller | Action |
|-------|--------|------------|--------|
| POST `projects/{project_id}/submit-to-provincial` | submitToProvincial | ProjectController | ProjectStatusService::submitToProvincial |
| POST `projects/{project_id}/revert-to-executor` | revertToExecutor | ProvincialController | ProjectStatusService::revertByProvincial |
| POST `projects/{project_id}/forward-to-coordinator` | forwardToCoordinator | ProvincialController | ProjectStatusService::forwardToCoordinator |
| POST `projects/{project_id}/revert-to-provincial` | revertToProvincial | CoordinatorController | ProjectStatusService::revertByCoordinator |
| POST `projects/{project_id}/approve` | approveProject | CoordinatorController | ProjectStatusService::approve + financials |
| POST `projects/{project_id}/reject` | rejectProject | CoordinatorController | Direct `$project->status = REJECTED` |
| POST `general/project/{project_id}/approve` | approveProject | GeneralController | approveAsCoordinator or approveAsProvincial |
| POST `general/project/{project_id}/revert` | revertProject | GeneralController | revertAsCoordinator or revertAsProvincial |
| POST `general/project/{project_id}/revert-to-level` | revertProjectToLevel | GeneralController | ProjectStatusService::revertToLevel |

---

## SECTION 3 — Illegal Transition Detection

### 3.1 Direct jumps (e.g. draft → approved without submit)

- **Draft → approved:** No code path sets status to any approved status from draft. Submit goes to SUBMITTED_TO_PROVINCIAL; only Provincial can forward, then Coordinator/General approve. **No illegal draft → approved path found.**
- **Draft → forwarded/rejected:** No path from draft to FORWARDED_TO_COORDINATOR or REJECTED_BY_COORDINATOR. **None found.**

### 3.2 Multiple controllers modifying status

| Who can change status | Controllers | Notes |
|-----------------------|------------|--------|
| Set DRAFT | ProjectController, GeneralInfoController | Create/update; intentional. |
| Submit → SUBMITTED_TO_PROVINCIAL | ProjectController only | Via service. |
| Forward / Revert (provincial) | ProvincialController, GeneralController | Provincial vs General context; both use service. |
| Approve / Revert (coordinator) | CoordinatorController, GeneralController | Coordinator vs General; both use service except reject. |
| Reject | CoordinatorController only | **Direct assignment**, not via ProjectStatusService. |
| Rollback approval (budget fail) | GeneralController only | **Direct assignment** FORWARDED_TO_COORDINATOR. |

**Finding:** Two places bypass the service: **reject** and **approval rollback on budget validation failure**.

### 3.3 Duplicate transition logic

- **Forward:** ProvincialController and GeneralController (approveAsProvincial) both call the service; no duplicate logic.
- **Approve:** CoordinatorController and GeneralController (approveAsCoordinator) both call the service; General also has post-approve budget validation with manual rollback (status set back to FORWARDED_TO_COORDINATOR at GeneralController 2555).
- **Revert:** Multiple service methods (revertByProvincial, revertByCoordinator, revertAsProvincial, revertAsCoordinator, revertToLevel) with overlapping “from” statuses and role checks — **logic is spread across several methods** but centralized in one service; no copy-paste in controllers.

### 3.4 Missing validation guards

- **REJECTED_BY_COORDINATOR:** Transition FORWARDED_TO_COORDINATOR → REJECTED_BY_COORDINATOR is implemented in CoordinatorController (1198) with role + `isForwardedToCoordinator` check. **Not** in `ProjectStatusService::canTransition()` (lines 439–458): REJECTED_BY_COORDINATOR does not appear in the `$transitions` map. So any code using `canTransition()` would not recognize reject as valid.
- **canTransition()** is **never called** in the codebase (only defined in ProjectStatusService). So it is dead code for enforcement; no guard actually uses it.
- **Approval rollback (GeneralController 2555):** After failed budget validation, status is set back to FORWARDED_TO_COORDINATOR without going through the service. No check that current status was APPROVED_BY_GENERAL_AS_COORDINATOR (it is implied by the flow).

---

## SECTION 4 — Revert Semantics

### 4.1 What happens to sanctioned?

- **ProjectStatusService** revert methods (revertByProvincial, revertByCoordinator, revertAsCoordinator, revertAsProvincial, revertToLevel) **only set `$project->status`** and call `$project->save()`. They do **not** modify `amount_sanctioned`, `opening_balance`, or `amount_forwarded`.
- **Finding:** When an **approved** project is reverted (e.g. to provincial or coordinator), `amount_sanctioned` and `opening_balance` remain set. Business rules (e.g. ProjectFinancialResolver, BudgetSyncGuard) assume non-approved projects have `amount_sanctioned == 0`. So **reverting an approved project leaves financial fields inconsistent** with “reverted” semantics unless another layer clears or overwrites them.

### 4.2 What happens to opening_balance?

- Same as above: **unchanged on revert.** Aggregations and resolvers that use `opening_balance` for “approved” projects may still see a positive value after revert.

### 4.3 What happens to submitted_at?

- **Project model has no `submitted_at` column** (only `status` in fillable; no timestamp for submission). So there is no `submitted_at` to clear or retain on revert.

### 4.4 Is revert idempotent?

- Revert transitions move status from one reverted state to another possible reverted state (e.g. REVERTED_BY_COORDINATOR → REVERTED_TO_PROVINCIAL). Calling revert again from the new status may be allowed (e.g. General can revert again to a different level). So **revert is not idempotent** by design; multiple reverts are valid and each call changes status.

---

## SECTION 5 — Role-Based Authority

### 5.1 Executor transitions

- **Submit:** Executor/Applicant can submit (ProjectPermissionHelper::canSubmit, ProjectStatusService::submitToProvincial). Route under projects (ProjectController).  
- **Set DRAFT:** On create (GeneralInfoController, ProjectController) and on update with save_as_draft (ProjectController).  
- No approve, forward, or revert from executor.

### 5.2 Provincial transitions

- **Forward:** ProvincialController `forwardToCoordinator` → ProjectStatusService::forwardToCoordinator (role provincial).  
- **Revert:** ProvincialController `revertToExecutor` → ProjectStatusService::revertByProvincial (role provincial).  
- Allowed “from” statuses in service: SUBMITTED_TO_PROVINCIAL, FORWARDED_TO_COORDINATOR, REVERTED_BY_COORDINATOR (revertByProvincial).

### 5.3 Coordinator transitions

- **Approve:** CoordinatorController `approveProject` → ProjectStatusService::approve (role coordinator).  
- **Revert:** CoordinatorController `revertToProvincial` → ProjectStatusService::revertByCoordinator (role coordinator).  
- **Reject:** CoordinatorController `rejectProject` → direct `$project->status = REJECTED_BY_COORDINATOR` (role coordinator, isForwardedToCoordinator).  
- Allowed “from” in service: FORWARDED_TO_COORDINATOR, APPROVED_BY_COORDINATOR, APPROVED_BY_GENERAL_AS_COORDINATOR (revert).

### 5.4 General-as-* transitions

- **As Provincial:** approveAsProvincial (forward to coordinator), revertAsProvincial, revertToLevel (executor/applicant/provincial).  
- **As Coordinator:** approveAsCoordinator, revertAsCoordinator, revertToLevel (provincial/coordinator).  
- General can also do **revertToLevel** for any of the four levels; allowed “from” statuses depend on level (see ProjectStatusService 381–393, 404–416, 418–428).  
- **Overlap:** General has both provincial and coordinator capabilities; authority is overlapping by design (context chosen per action).

### 5.5 Overlapping authority

- **Provincial vs General (as provincial):** Both can forward and revert at provincial level; General uses explicit “as provincial” methods.  
- **Coordinator vs General (as coordinator):** Both can approve and revert at coordinator level; General uses explicit “as coordinator” methods.  
- No conflict: routes and controller methods are role-guarded; General has separate routes and context parameters (approval_context, revert_level).

---

## SECTION 6 — Risk Classification

### HIGH — Illegal transitions possible

| # | Finding | Evidence |
|---|---------|----------|
| H1 | **Reject bypasses ProjectStatusService:** Transition to REJECTED_BY_COORDINATOR is done by direct assignment. If future logic relies on the service or on canTransition(), reject would be invisible or disallowed. | CoordinatorController 1198, 1202 |
| H2 | **Revert leaves financial fields set:** Reverting an approved project does not clear `amount_sanctioned` or `opening_balance`. Resolvers/guards assume non-approved have sanctioned/opening zero; data can be inconsistent. | ProjectStatusService revert methods only set status; Project fillable 280–283; ProjectFinancialResolver / BudgetSyncGuard assumptions |

### MEDIUM — Duplicate / scattered logic

| # | Finding | Evidence |
|---|---------|----------|
| M1 | **Approval rollback by direct assignment:** On budget validation failure after approveAsCoordinator, GeneralController sets status back to FORWARDED_TO_COORDINATOR without the service. | GeneralController 2555–2556 |
| M2 | **canTransition() unused:** Central transition map exists but is never called; no single guard for “allowed transition” at request level. | ProjectStatusService 439–458; grep shows no callers |
| M3 | **Multiple revert entry points:** revertByProvincial, revertByCoordinator, revertAsProvincial, revertAsCoordinator, revertToLevel with overlapping “from” sets and role checks. Harder to reason about and to keep in sync. | ProjectStatusService 159–416, 427–467, 481–559 |

### LOW — Cosmetic inconsistency

| # | Finding | Evidence |
|---|---------|----------|
| L1 | **REJECTED_BY_COORDINATOR missing from canTransition():** If canTransition() were ever used, reject would be missing from the map. | ProjectStatusService 439–458 |
| L2 | **Project has no submitted_at:** Submission is represented only by status; no timestamp. Inconsistent with some report models that have submitted_at. | Project fillable/casts; no submitted_at column referenced |

---

## Summary Table: Where status is set

| File | Line(s) | How status is set |
|------|---------|--------------------|
| app/Constants/ProjectStatus.php | (definitions) | N/A |
| app/Services/ProjectStatusService.php | 35, 78, 128, 191, 255, 303, 348, 398, 452, 547 | `$project->status = ...` then save |
| app/Http/Controllers/Projects/ProjectController.php | 768, 772, 1524 | Direct `$project->status = DRAFT` |
| app/Http/Controllers/Projects/ProjectController.php | 1751 | ProjectStatusService::submitToProvincial |
| app/Http/Controllers/Projects/GeneralInfoController.php | 30 | `$validated['status'] = DRAFT` on create |
| app/Http/Controllers/CoordinatorController.php | 1091 | ProjectStatusService::approve |
| app/Http/Controllers/CoordinatorController.php | 1018 | ProjectStatusService::revertByCoordinator |
| app/Http/Controllers/CoordinatorController.php | 1198 | Direct `$project->status = REJECTED_BY_COORDINATOR` |
| app/Http/Controllers/ProvincialController.php | 1520, 1533 | ProjectStatusService::revertByProvincial, forwardToCoordinator |
| app/Http/Controllers/GeneralController.php | 2541, 2555, 2584, 2638, 2650, 2702 | approveAsCoordinator; direct FORWARDED (rollback); approveAsProvincial; revertAsCoordinator; revertAsProvincial; revertToLevel |

---

**M4.1 Workflow Transition Audit Complete — No Code Changes Made**
