# Workflow Centralization Audit

**Objective:** Audit all project workflow transitions before centralizing them into `App\Services\ProjectLifecycleService`.  
**Scope:** Project status mutations, entry points, authorization, notifications, activity history, financial side effects, transition graph, duplication, risks, refactor readiness.  
**Rules:** Read-only; no code modified.

---

## 1. Current Transition Entry Points

| Route Name | HTTP | Controller Method | Middleware | Role Restriction | Province Enforced | Ownership Enforced | Status Transition |
|------------|------|-------------------|------------|------------------|------------------|-------------------|-------------------|
| `projects.submitToProvincial` | POST | `ProjectController@submitToProvincial` | auth, role:executor,applicant | executor, applicant | Via SubmitProjectRequest → ProjectPermissionHelper::canSubmit | Yes (owner or in_charge) | → submitted_to_provincial |
| `projects.approve` | POST | `CoordinatorController@approveProject` | auth, role:coordinator,general | coordinator, general | **No** (route only) | **No** | → approved_by_coordinator / approved_by_general_as_coordinator |
| `projects.reject` | POST | `CoordinatorController@rejectProject` | auth, role:coordinator,general | coordinator, general | **No** | **No** | → rejected_by_coordinator |
| `projects.revertToProvincial` | POST | `CoordinatorController@revertToProvincial` | auth, role:coordinator,general | coordinator, general | **No** | **No** | → reverted_by_coordinator (or general variants) |
| `projects.forwardToCoordinator` | POST | `ProvincialController@forwardToCoordinator` | auth, role:provincial,general | provincial, general | **No** (in method via ProjectStatusService) | **No** | → forwarded_to_coordinator |
| `projects.revertToExecutor` | POST | `ProvincialController@revertToExecutor` | auth, role:provincial,general | provincial, general | **No** | **No** | → reverted_by_provincial (or general variants) |
| `general.approveProject` | POST | `GeneralController@approveProject` | auth, role:coordinator,general | general only (manual check) | **No** | **No** | approveAsCoordinator / approveAsProvincial |
| `general.revertProject` | POST | `GeneralController@revertProject` | auth, role:coordinator,general | general only (manual check) | **No** | **No** | revertAsCoordinator / revertAsProvincial |
| `general.revertProjectToLevel` | POST | `GeneralController@revertProjectToLevel` | auth, role:coordinator,general | general only (manual check) | **No** | **No** | revertToLevel (executor/applicant/provincial/coordinator) |

**Notes:**
- **Province enforcement:** Only `submitToProvincial` uses `ProjectPermissionHelper::canSubmit()` (which includes `passesProvinceCheck`). Approve, reject, revert, forward do **not** call ProjectPermissionHelper for project-level or province checks; they rely on route middleware and in-service role checks.
- **Ownership:** Submit uses canSubmit (owner/in_charge). Other transitions do not explicitly check project ownership; they rely on role and status checks inside `ProjectStatusService`.

---

## 2. Status Mutation Locations

### 2.1 Direct `->status =` (project)

| File | Method / Context | Old Status | New Status | Condition |
|------|------------------|------------|------------|-----------|
| `ProjectController.php` | `applyPostCommitStatusAndRedirect()` (store flow) | (new) | DRAFT | After create; both save_as_draft and normal path set DRAFT |
| `ProjectController.php` | `update()` | (unchanged) | DRAFT | When `save_as_draft` true; explicit keep DRAFT |
| `GeneralController.php` | `approveProject()` | FORWARDED_TO_COORDINATOR | FORWARDED_TO_COORDINATOR | **Rollback** on budget validation failure (after approveAsCoordinator) |
| `ProjectStatusService.php` | Multiple methods | various | various | All transitions (submitToProvincial, forwardToCoordinator, reject, revertByProvincial, revertByCoordinator, approve, approveAsCoordinator, approveAsProvincial, revertToLevel, revertAsCoordinator, revertAsProvincial) |

### 2.2 Via `update(['status'])`

- **Project:** None (project status is set via `$project->status = ...; $project->save()`).
- **User status:** `GeneralController`, `ProvincialController`, `CoordinatorController` use `$user->update(['status' => 'active'|'inactive'])` for user activate/deactivate — **not project workflow**.

### 2.3 Initial status on create

| File | Context | Status Set |
|------|---------|------------|
| `GeneralInfoController.php` | `store()` | `$validated['status'] = ProjectStatus::DRAFT` (then `Project::create($validated)`) |
| `ProjectController.php` | `applyPostCommitStatusAndRedirect()` | `$project->status = ProjectStatus::DRAFT` (after store) |

### 2.4 ProjectStatusService (single place for workflow mutations)

All workflow transitions that change project status go through `ProjectStatusService` except:
- **Create / draft:** `GeneralInfoController::store`, `ProjectController::applyPostCommitStatusAndRedirect`, `ProjectController::update` (save_as_draft).
- **Rollback:** `GeneralController::approveProject` (direct `$project->status = FORWARDED_TO_COORDINATOR` on budget validation failure).

---

## 3. Authorization Consistency Review

| Transition | Uses ProjectPermissionHelper? | Manual Role Check | Province Check | Consistency |
|------------|------------------------------|-------------------|----------------|-------------|
| Submit to provincial | Yes (`canSubmit`) | No (FormRequest) | Yes (inside canSubmit) | ✅ Consistent |
| Approve (Coordinator) | **No** | ApproveProjectRequest: role in [coordinator, general] | **No** | ⚠️ No province/project-level auth |
| Reject | **No** | `$coordinator->role !== 'coordinator' \|\| !ProjectStatus::isForwardedToCoordinator($project->status)` | **No** | ⚠️ Inconsistent; General cannot reject (by design?) but not via Helper |
| Revert (Coordinator) | **No** | Route + ProjectStatusService::revertByCoordinator (role in [coordinator, general]) | **No** | ⚠️ No province/project-level auth |
| Forward (Provincial) | **No** | ProjectStatusService (role provincial/general) | **No** | ⚠️ No province in controller |
| Revert (Provincial) | **No** | ProjectStatusService (role provincial/general) | **No** | ⚠️ No province in controller |
| General approve/revert | **No** | `$general->role !== 'general'` | **No** | ⚠️ General-only but no Helper |
| Trash / Restore / ForceDelete | Yes (`canDelete`) | ForceDelete: `$user->role === 'admin'` | Via canDelete (passesProvinceCheck) | ✅ Lifecycle uses Helper |

**Findings:**
- **ProjectPermissionHelper** is used for: canView, canEdit, canDelete, canSubmit. There are **no** `canApprove`, `canReject`, `canRevert`, or `canForward` methods.
- **Province:** Only submit and lifecycle (trash/restore/forceDelete) go through Helper; approve/reject/revert/forward do not call `passesProvinceCheck` in the controller layer. ProjectStatusService does not perform province checks; it only checks role and current status.
- **Duplication:** Role checks are repeated in CoordinatorController (reject), GeneralController (general-only), and inside ProjectStatusService for each method.

---

## 4. Notification Trigger Map

| Transition | Notification Triggered? | Where | When |
|------------|--------------------------|--------|------|
| submitToProvincial | **No** | — | — |
| approve (Coordinator) | Yes | CoordinatorController (after ProjectStatusService::approve) | After status update; `NotificationService::notifyApproval(executor, 'project', ...)` |
| reject | Yes | CoordinatorController (after ProjectStatusService::reject) | After status update; `NotificationService::notifyRejection(executor, ...)` |
| revertToProvincial | Yes | CoordinatorController (after ProjectStatusService::revertByCoordinator) | After status update; `NotificationService::notifyRevert(executor, 'project', ...)` |
| forwardToCoordinator | **No** | — | — |
| revertToExecutor | **No** | — | — |
| general approveProject | **No** | — | — |
| general revertProject | **No** | — | — |
| general revertProjectToLevel | **No** | — | — |

**Findings:**
- Notifications are **only** sent from **CoordinatorController** for approve, reject, revertToProvincial. All are triggered **in the controller** after the service call.
- Provincial and General project transitions do **not** trigger project-level notifications in the codebase.
- Report workflow (ReportController, CoordinatorController report approve/revert) uses NotificationService for report submission/revert/approval — separate from project workflow.

---

## 5. Activity History Logging Map

| Transition | Logs Activity? | Where | Method |
|------------|----------------|--------|--------|
| submitToProvincial | Yes | ProjectStatusService | `logStatusChange()` (ActivityHistory + ProjectStatusHistory) |
| forwardToCoordinator | Yes | ProjectStatusService | `logStatusChange()` |
| approve | Yes | ProjectStatusService | `logStatusChange()` |
| reject | Yes | ProjectStatusService | `logStatusChange()` |
| revertByProvincial | Yes | ProjectStatusService | `logStatusChange()` |
| revertByCoordinator | Yes | ProjectStatusService | `logStatusChange()` |
| approveAsCoordinator | Yes | ProjectStatusService | `logStatusChange()` |
| approveAsProvincial | Yes | ProjectStatusService | `logStatusChange()` |
| revertAsCoordinator / revertAsProvincial / revertToLevel | Yes | ProjectStatusService | `logStatusChange()` |
| Project create (DRAFT) | Yes | GeneralInfoController | `ProjectStatusService::logStatusChange(project, null, DRAFT, ...)` |
| Project update (no status change) | Yes | ProjectController | `ActivityHistoryService::logProjectUpdate()` |
| Save as draft (store/update) | No status change logged as status_change | — | Update path: only logProjectUpdate; store path: no activity for DRAFT set |
| Trash / Restore | No status in ActivityHistory | ProjectLifecycleService | forceDelete: `ActivityHistoryService::logProjectForceDelete()`; trash/restore: no activity log |
| Rollback (GeneralController budget fail) | **No** | — | Status reverted to FORWARDED_TO_COORDINATOR without logging |

**Findings:**
- All **status transitions** through ProjectStatusService are logged via `logStatusChange()` to `ActivityHistory` and (legacy) `ProjectStatusHistory`.
- **Missing:** (1) Rollback in GeneralController (status set back to FORWARDED_TO_COORDINATOR) is not logged. (2) Trash and Restore do not write to ActivityHistory (only forceDelete does).
- **Consistency:** Project workflow status changes are consistently logged when going through ProjectStatusService. Manual status overwrites (create/draft/rollback) are partially or not logged.

---

## 6. Financial Side-Effect Map

| Component | Depends on Status? | How |
|-----------|--------------------|-----|
| **ProjectFinancialResolver** | Yes | `resolve()` does not change status; used **after** approval to compute amount_sanctioned, opening_balance. `assertFinancialInvariants()` uses `$project->isApproved()` for warnings. |
| **BudgetSyncGuard** | Yes | `canSyncOnTypeSave()`: no sync when `$project->isApproved()`. Pre-approval sync only when status is forwarded_to_coordinator. |
| **BudgetValidationService** | Report status | Uses report status (e.g. approved_by_coordinator) for report-level validation. |
| **Approval flow (Coordinator / General)** | Yes | After status transition: controller calls `ProjectFinancialResolver::resolve()`, then persists `amount_sanctioned`, `opening_balance` on project. On revert: `ProjectStatusService::applyFinancialResetOnRevert()` sets amount_sanctioned = 0 and opening_balance = amount_forwarded + local_contribution. |

**Which transitions affect financial calculations?**
- **Approve (coordinator / general as coordinator):** After status → approved, controller uses resolver and saves amount_sanctioned, opening_balance. Budget validation (combined contribution ≤ overall budget) in controller; on failure GeneralController rolls back status to FORWARDED_TO_COORDINATOR.
- **Revert (any):** `applyFinancialResetOnRevert()` in ProjectStatusService runs when transitioning from approved to non-approved; idempotent if already reverted.
- **Reject:** No financial persistence in controller; project stays in rejected state (no sanctioned/opening update in the reject path).

**Are recalculations triggered automatically?**
- No automatic recalculation on status change. Controllers explicitly call resolver and save after approve. Revert-side financial reset is inside ProjectStatusService.

---

## 7. Status Transition Graph

Source: `ProjectStatusService::canTransition()` (M4.5 map). Allowed transitions (from → to) by role:

- **draft** → submitted_to_provincial [executor, applicant]
- **reverted_by_provincial** → submitted_to_provincial [executor, applicant]; → forwarded_to_coordinator [general]; → reverted_to_executor / reverted_to_applicant [general]
- **reverted_by_coordinator** → submitted_to_provincial [executor, applicant]; → forwarded_to_coordinator [provincial, general]; → reverted_by_provincial [provincial, general]; → reverted_by_general_as_provincial [general]; → reverted_to_* [general]; → reverted_to_provincial [general]
- **reverted_by_general_as_provincial** → submitted_to_provincial [executor, applicant]; → forwarded_to_coordinator [general]; → reverted_to_* [general]
- **reverted_by_general_as_coordinator** → submitted_to_provincial [executor, applicant]; → forwarded_to_coordinator [provincial, general]; → reverted_to_provincial [general]
- **reverted_to_executor / reverted_to_applicant / reverted_to_provincial / reverted_to_coordinator** → submitted_to_provincial [executor, applicant]; → forwarded_to_coordinator [general] or [provincial, general]
- **submitted_to_provincial** → forwarded_to_coordinator [provincial, general]; → reverted_by_provincial [provincial, general]; → reverted_by_general_as_provincial [general]; → reverted_to_* [general]
- **forwarded_to_coordinator** → approved_by_coordinator [coordinator, general]; → approved_by_general_as_coordinator [general]; → reverted_by_coordinator [coordinator, general]; → reverted_by_general_as_coordinator [general]; → reverted_to_provincial/coordinator [general]; → rejected_by_coordinator [coordinator]; → reverted_by_provincial [provincial, general]; → reverted_by_general_as_provincial [general]; → reverted_to_* [general]
- **approved_by_coordinator** → reverted_by_coordinator [coordinator, general]; → reverted_by_general_as_coordinator [general]; → reverted_to_provincial/coordinator [general]
- **approved_by_general_as_coordinator** → reverted_by_general_as_coordinator [general]; → reverted_to_provincial/coordinator [general]; → forwarded_to_coordinator [general] (rollback on budget validation failure)

**Invalid transitions / guardrails:**
- **canTransition()** is used only for **soft** checks: on failure a warning is logged but the transition is **not** blocked (see ProjectStatusService: "Invalid transition detected (soft)").
- **Direct manual overrides:** (1) Create/save_as_draft set DRAFT outside transition map. (2) GeneralController rollback sets status back to FORWARDED_TO_COORDINATOR without going through service — effectively a "rollback" that is not in the formal transition map.
- **reject:** Only coordinator can reject (enforced in service and controller); General cannot reject.

---

## 8. Duplication Analysis

| Area | Severity | Description |
|------|----------|-------------|
| Role checks for workflow | **MEDIUM** | Role checks repeated in CoordinatorController (reject: coordinator only), GeneralController (general only), and inside every ProjectStatusService method. No single helper like canApprove/canReject/canForward/canRevert. |
| Province checks | **HIGH** | Only submit and lifecycle use ProjectPermissionHelper (province). Approve, reject, revert, forward do not call passesProvinceCheck; a cross-province project could theoretically be approved/reverted/forwarded if route is hit (relies on UI not exposing). |
| Status allowed-list checks | **MEDIUM** | Each ProjectStatusService method has its own `$allowedStatuses` array; overlaps with canTransition map but not derived from it. |
| Post-approval budget persistence | **MEDIUM** | CoordinatorController and GeneralController both: sync before approval, call service approve, then resolve financials, validate combined ≤ overall, then save amount_sanctioned/opening_balance. Logic duplicated. |
| Notification after transition | **LOW** | Only CoordinatorController sends project notifications (approve/reject/revert). Provincial/General do not; could be intentional or oversight. |
| Activity logging | **LOW** | Status changes consistently in ProjectStatusService::logStatusChange. Exception: rollback and trash/restore. |
| Draft / create status | **LOW** | DRAFT set in GeneralInfoController::store, ProjectController::applyPostCommitStatusAndRedirect, and ProjectController::update (save_as_draft). All acceptable for "initial state" but scattered. |

**Summary:** Highest duplication/risk is **authorization (province + role)** not centralized for approve/reject/revert/forward. Next is **post-approval financial persistence** duplicated between Coordinator and General.

---

## 9. Risk Classification

**Overall workflow architecture: C) Highly fragmented / risky**

Reasons:
- **Multiple entry points** (Coordinator, Provincial, General, ProjectController) with **inconsistent authorization**: only submit and lifecycle use ProjectPermissionHelper; approve/reject/revert/forward do not use it and do not enforce province at controller level.
- **Notifications** only from CoordinatorController; Provincial and General transitions have no project notifications.
- **One direct status rollback** in GeneralController bypasses service and activity logging.
- **canTransition()** is soft (log-only); invalid transitions are not blocked.
- **Financial persistence** after approval is duplicated and lives in controllers, not in a single service.
- **ProjectStatusService** already centralizes status mutation and logging but is **separate** from ProjectLifecycleService (trash/restore/forceDelete); moving all transitions into ProjectLifecycleService would require merging or delegating from Lifecycle to Status service.

---

## 10. Refactor Readiness Assessment

- **Can transitions be moved into ProjectLifecycleService without breaking logic?**  
  **Yes, but** with care: ProjectStatusService currently holds all transition logic and logging. Options: (1) Move project workflow methods from ProjectStatusService into ProjectLifecycleService and delegate logging/activity to a shared helper, or (2) Keep ProjectStatusService and have ProjectLifecycleService delegate to it for status transitions while Lifecycle remains the single entry for "lifecycle" (including trash/restore/forceDelete and eventually approve/reject/revert/forward). Option 2 is less disruptive.

- **Which modules must be moved first?**  
  1) **Authorization:** Add ProjectPermissionHelper methods (e.g. canApprove, canReject, canForward, canRevert) and province checks for each transition; then wire controllers to use them.  
  2) **Notifications:** Decide policy (who gets notified for which transition); then centralize trigger in one place (e.g. after each transition in service or in a single listener).  
  3) **Post-approval financial persistence:** Move resolver call + validation + save into a single service method called after approve.

- **Simplest to centralize:** submitToProvincial is already in ProjectStatusService and uses ProjectPermissionHelper; it could be exposed via ProjectLifecycleService as a thin delegate. **Next simplest:** forwardToCoordinator and revertToExecutor (no notification, no financial persistence).

- **Most complex:** Approve (coordinator and general): commencement date, BudgetSyncService, resolver, budget validation, financial persistence, and notification (coordinator path). Revert (multiple levels and contexts): many status targets and roles.

---

## 11. Recommended Centralization Strategy (NO CODE YET)

1. **Introduce workflow authorization in ProjectPermissionHelper**  
   Add methods that combine province check + role + status (e.g. canApprove, canReject, canForward, canRevert) so all entry points use the same rules.

2. **Keep ProjectStatusService as the single implementation for status transitions**  
   Have controllers (and eventually ProjectLifecycleService) call only ProjectStatusService for status changes. Do not duplicate transition or logging logic.

3. **Option A – Lifecycle as facade**  
   ProjectLifecycleService becomes the single public API for all project lifecycle actions: trash, restore, forceDelete, submitToProvincial, forwardToCoordinator, revert (by coordinator/provincial/level), approve, reject. It delegates to ProjectStatusService for status changes and to ProjectPermissionHelper for auth. Notifications and post-approval financial persistence can be called from Lifecycle after delegation.

4. **Option B – Lifecycle only for delete/restore**  
   Leave approve/reject/revert/forward in controllers but standardize: every workflow action must use ProjectPermissionHelper (new methods) and ProjectStatusService; move notification and financial persistence into ProjectStatusService or a dedicated "after transition" layer so behavior is consistent.

5. **Harden transition enforcement**  
   Make canTransition() enforce (abort or throw) instead of log-only when transition is invalid.

6. **Log and notify consistently**  
   Ensure every status transition (including rollback and any future manual overrides) logs activity; define and implement notification for provincial/general transitions if required.

7. **Single place for post-approval financial update**  
   One method (e.g. in a budget service or ProjectStatusService) that: syncs before approval, runs resolver, validates combined ≤ overall, saves amount_sanctioned/opening_balance (or rolls back status and does not save). Controllers call this after calling approve.

---

## Summary

- **Risk classification:** **C) Highly fragmented / risky**
- **Top 5 most fragmented areas:**  
  1) Province and project-level authorization for approve/reject/revert/forward.  
  2) Post-approval budget validation and persistence duplicated in Coordinator and General.  
  3) Notifications only from CoordinatorController; no project notifications for Provincial/General.  
  4) Role and status checks repeated in controllers and ProjectStatusService.  
  5) One direct status rollback in GeneralController without logging.
- **Estimated refactor complexity:** **High** (authorization unification, notification policy, financial persistence consolidation, and optional merge of status into lifecycle).
- **MD file created:** `Documentations/V2A/Workflow_Centralization_Audit.md`
- **No code was modified** (read-only audit).
