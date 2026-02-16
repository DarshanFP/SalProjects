# M5 — Pre-Deployment Validation: Project Workflow & Dashboard Integrity Safety Audit

**Mode:** STRICTLY READ-ONLY | IMPACT ANALYSIS ONLY  
**Scope:** Project approval flow, dashboard calculations, approved vs unapproved listings, aggregation logic.  
**Excluded:** Reporting and expense redesign.

---

## SECTION 1 — Project Approval Flow Integrity

### 1) All approval paths

| Path | Location | Evidence |
|------|----------|----------|
| **approve** | `ProjectStatusService::approve()` | `app/Services/ProjectStatusService.php` lines 129–182. Used by CoordinatorController (coordinator approval). |
| **approveAsCoordinator** | `ProjectStatusService::approveAsCoordinator()` | `app/Services/ProjectStatusService.php` lines 411–447. Used by GeneralController when General approves as Coordinator. |
| **approveAsProvincial** | `ProjectStatusService::approveAsProvincial()` | `app/Services/ProjectStatusService.php` lines 465–504. Used by GeneralController when General forwards/approves as Provincial (no financial persistence; forwards to coordinator only). |

**Controller usage:**

- **Coordinator approval:** `app/Http/Controllers/CoordinatorController.php` lines 1090–1091: `ProjectStatusService::approve($project, $coordinator);`
- **General approve as Coordinator:** `app/Http/Controllers/GeneralController.php` line 2541: `ProjectStatusService::approveAsCoordinator($project, $general);`
- **General approve as Provincial:** `app/Http/Controllers/GeneralController.php` line 2584: `ProjectStatusService::approveAsProvincial($project, $general);`

### 2) amount_sanctioned and opening_balance persistence

- **Coordinator approve:** After `ProjectStatusService::approve()`, controller persists financials from resolver: `app/Http/Controllers/CoordinatorController.php` lines 1134–1136: `$project->amount_sanctioned = $amountSanctioned;` `$project->opening_balance = $openingBalance;` `$project->save();`
- **General approve as Coordinator:** After `ProjectStatusService::approveAsCoordinator()`, controller resolves and persists: `app/Http/Controllers/GeneralController.php` lines 2544–2564 (resolver → validate → `$project->amount_sanctioned` / `$project->opening_balance` → `$project->save()`).
- **approveAsProvincial:** Only forwards status; does **not** set amount_sanctioned or opening_balance (correct; financial approval is at coordinator level).

### 3) Revert clears sanctioned (M4.2)

- **Implementation:** `app/Services/ProjectStatusService.php` lines 239–247: `applyFinancialResetOnRevert()` sets `amount_sanctioned = 0` and `opening_balance = amount_forwarded + local_contribution` when current status is approved (idempotent otherwise).
- **Invoked from all revert-from-approved paths:**
  - `revertByProvincial` — line 303
  - `revertByCoordinator` — line 380
  - `revertAsCoordinator` — line 560
  - `revertAsProvincial` — line 627
  - `revertToLevel` — line 735

### 4) Reject centralized (M4.3)

- **Single entry point:** `app/Services/ProjectStatusService.php` lines 185–230: `reject()` (coordinator-only; does not modify financial fields).
- **Usage:** `app/Http/Controllers/CoordinatorController.php` line 1198: `ProjectStatusService::reject($project, $coordinator);`

### 5) No direct controller status mutation for workflow transitions

- **ProjectStatusService** is the only place that sets `$project->status` for submit/forward/approve/reject/revert (within the service methods).
- **Controller status assignments (allowed exceptions):**
  - **ProjectController** lines 768, 772: Initial create — set `status = DRAFT` for new project (not a workflow transition).
  - **ProjectController** line 1524: Update with “save as draft” — keeps `status = DRAFT` (preserving draft, not from approved).
  - **GeneralController** line 2555: Rollback only — when budget validation fails *after* `approveAsCoordinator()`, status is reverted to `FORWARDED_TO_COORDINATOR` to undo the approval before financial save. Not a normal workflow path.

**Conclusion:** All approval/reject/revert transitions go through ProjectStatusService. No direct controller-driven workflow status mutation remains for normal flows.

---

## SECTION 2 — Approved vs Non-Approved Segregation

### 1) Project::scopeApproved() and scopeNotApproved()

- **scopeApproved:** `app/Models/OldProjects/Project.php` lines 343–345: `return $query->whereIn('status', ProjectStatus::APPROVED_STATUSES);`
- **scopeNotApproved:** `app/Models/OldProjects/Project.php` lines 351–353: `return $query->whereNotIn('status', ProjectStatus::APPROVED_STATUSES);`
- **APPROVED_STATUSES:** `app/Constants/ProjectStatus.php` lines 35–39: `APPROVED_BY_COORDINATOR`, `APPROVED_BY_GENERAL_AS_COORDINATOR`, `APPROVED_BY_GENERAL_AS_PROVINCIAL`.

### 2) Usage across dashboard and listings

| Location | Approved | Non-approved |
|----------|----------|--------------|
| **ProvincialController** | `->approved()` — lines 96, 131, 1550, 1580, 2183–2184 | `->notApproved()` — line 2190 |
| **CoordinatorController** | `Project::approved()` — lines 52, 103, 1227, 1269, 1988, 2021 | `Project::notApproved()` — line 2024 (pending projects) |
| **ExecutorController** | Default filter: `whereIn('status', [APPROVED_BY_COORDINATOR, APPROVED_BY_GENERAL_AS_COORDINATOR, APPROVED_BY_GENERAL_AS_PROVINCIAL])` — lines 34–38 (same set as APPROVED_STATUSES) | `getEditableStatuses()` for “needs_work” — lines 27–28 |
| **ProjectController index** | N/A (executor/applicant editable list) | `->notApproved()` — line 299 |
| **ProjectPermissionHelper** | N/A | `$query->notApproved()` for executor/applicant — line 159 |

### 3) Approved determined by APPROVED_STATUSES; no single-status filtering

- Dashboard and aggregation use either `Project::approved()` (scope) or the same three statuses (e.g. `ProjectStatus::APPROVED_STATUSES` or inline array). No single-status approval filter found for project workflow.
- Some Coordinator/Provincial dashboard stats use `whereIn('status', ProjectStatus::APPROVED_STATUSES)` on already-loaded collections (e.g. ProvincialController 1995, 2071; CoordinatorController 1663, 1694, 1744, etc.) — equivalent to the scope.

**Conclusion:** Approved vs non-approved segregation uses the centralized APPROVED_STATUSES grouping throughout. No single-status approval filter remains for projects.

---

## SECTION 3 — Dashboard Aggregation Integrity

### 1) Approved portfolio uses opening_balance

- **ProvincialController:** Approved totals use `opening_balance` from resolved financials: e.g. lines 2206–2207, 2227, 2248, 2269, 2291, 2133 (center budget).
- **CoordinatorController:** Approved totals use `opening_balance`: e.g. lines 1664, 1695, 1766, 1786, 1861, 2051, 2079, 2116, 2155, 2199, 2244–2245, 2328, 2434, 2535.
- **ExecutorController:** Budget summaries from approved projects use `opening_balance` via resolver/financials: e.g. lines 277, 369, 507 (Provincial), 287, 570 (Coordinator), 827, 1032 (Executor) — all use `$financials['opening_balance']` or `resolvedFinancials[...]['opening_balance']`.

### 2) Pending requests use max(0, overall_project_budget - (forwarded + local))

- **ProvincialController** lines 2192–2197:  
  `$pendingTotal = (float) $pendingProjects->sum(function ($p) {`  
  `    $overall = (float) ($p->overall_project_budget ?? 0);`  
  `    $forwarded = (float) ($p->amount_forwarded ?? 0);`  
  `    $local = (float) ($p->local_contribution ?? 0);`  
  `    return max(0, $overall - ($forwarded + $local));`  
  `});`
- **CoordinatorController** lines 2042–2047: Same formula for pending projects:  
  `$pendingTotal = (float) $pendingProjects->sum(function ($p) { ... return max(0, $overall - ($forwarded + $local)); });`

### 3) No use of amount_sanctioned alone or inline sanctioned fallback for aggregation

- Aggregation for **approved** portfolio uses `opening_balance` (and resolver where used), not `amount_sanctioned` alone.
- **PhaseBasedBudgetStrategy** (resolver): For non-approved projects it computes a derived “pending” sanctioned/opening for resolver output only; dashboard pending total is explicitly `max(0, overall - (forwarded + local))` in controllers, not from sanctioned.
- CoordinatorController line 151: `projects_with_amount_sanctioned` is a count metric only, not used for budget totals.

**Conclusion:** Approved portfolio aggregation uses `opening_balance`; pending requests use `max(0, overall_project_budget - (forwarded + local))`. No aggregation uses `amount_sanctioned` alone or inline sanctioned fallback for totals.

---

## SECTION 4 — Index Listing Integrity

### 1) Project index pages

| Role | Controller / entry | Filter | Evidence |
|------|-------------------|--------|----------|
| **Executor/Applicant** | `ProjectController::index()` | Non-approved only | `app/Http/Controllers/Projects/ProjectController.php` lines 298–300: `getProjectsForUserQuery($user)->notApproved()->with(...)->get()` |
| **Provincial** | `ProvincialController::provincialDashboard()` (main list) | Approved | Lines 94–96: `Project::whereIn('user_id', $accessibleUserIds)->approved()` |
| **Provincial** | `ProvincialController::approvedProjects()` | Approved | Lines 1548–1550: same pattern `->approved()` |
| **Coordinator** | `CoordinatorController` dashboard/list | Approved | Lines 51–52, 1226–1227: `Project::approved()->with('user')` |

### 2) Consistency and leakage

- **Executor/Applicant:** Index and editable flows use `notApproved()` (ProjectController index, ProjectPermissionHelper). Dashboard can show approved via filter but editable list excludes approved — no approved projects in editable listing.
- **Provincial / Coordinator:** Listings that show “approved” use `approved()` or equivalent APPROVED_STATUSES; pending/aggregation use `notApproved()` or pending query. Separation is consistent.

**Conclusion:** Approved and non-approved filtering are consistent. No leakage of approved projects into editable (executor/applicant) index listing.

---

## SECTION 5 — Financial Invariant Stability

### 1) After revert: sanctioned cleared, opening_balance recalculated

- **applyFinancialResetOnRevert** (ProjectStatusService lines 239–247): When current status is approved, sets `amount_sanctioned = 0` and `opening_balance = amount_forwarded + local_contribution` before updating status and saving.
- All revert paths that can leave an approved state call this (revertByProvincial, revertByCoordinator, revertAsCoordinator, revertAsProvincial, revertToLevel).

### 2) Dashboard reflects updated values

- Dashboard aggregation reads from DB (project rows and resolver). After revert, project row has sanctioned=0 and opening_balance=forwarded+local; resolver returns the same for non-approved. No caching of old sanctioned/opening_balance found in aggregation code (cache invalidation exists post-approve/reject in CoordinatorController; revert paths do not show stale sanctioned usage).

### 3) No stale sanctioned usage

- Aggregation uses `opening_balance` for approved totals; after revert the project is non-approved and is excluded from approved scopes, so reverted projects do not contribute to “approved” totals. Pending total uses `overall - (forwarded + local)`, not sanctioned.

**Conclusion:** Revert clears sanctioned and recalculates opening_balance; dashboard uses current DB/resolver values with no reliance on stale sanctioned for totals.

---

## SECTION 6 — Deployment Risk Summary

### 1) Will approval flow function correctly post-deployment?

**Yes.** All approval paths (approve, approveAsCoordinator, approveAsProvincial) go through ProjectStatusService. Financial persistence (amount_sanctioned, opening_balance) is applied in controllers after service transition and resolver validation. Reject is centralized in ProjectStatusService::reject().

### 2) Will dashboard calculations remain correct?

**Yes.** Approved portfolio uses `opening_balance`; pending requests use `max(0, overall_project_budget - (forwarded + local))`. No use of `amount_sanctioned` alone for aggregation. Resolver and DB are the source of truth; revert updates DB consistently.

### 3) Will approved/unapproved separation remain accurate?

**Yes.** Scopes `scopeApproved()` and `scopeNotApproved()` and constant `APPROVED_STATUSES` are used consistently. Executor editable index uses `notApproved()`; provincial/coordinator use `approved()` for approved listings and `notApproved()` for pending. No single-status approval filter; no leakage of approved into editable listing.

### 4) Is any HIGH risk detected?

**No.** One acceptable exception: GeneralController line 2555 (rollback on budget validation failure after approveAsCoordinator) sets status back to FORWARDED_TO_COORDINATOR. This is a deliberate rollback path, not a normal workflow. No HIGH risk identified for project workflow or dashboard.

### 5) Is system safe to deploy for project workflow only?

**Yes**, for the in-scope areas (project approval flow, dashboard calculations, approved vs unapproved listings, aggregation). Reporting and expense redesign were out of scope.

---

## Categorization

**SAFE** — Approval flow, dashboard aggregation, approved/non-approved segregation, and financial invariants on revert are implemented consistently and correctly. No code changes were made; this audit is read-only impact analysis.

---

Pre-Deployment Project Workflow & Dashboard Safety Audit Complete — No Code Changes Made
