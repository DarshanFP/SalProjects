# M4.4 — Transition Map Completeness Audit

**Milestone:** M4 — Workflow & State Machine Hardening  
**Task:** M4.4 — Transition Map Completeness Audit  
**Mode:** STRICTLY READ-ONLY (No Code Changes)  
**Date:** 2026-02-15

---

## SECTION 1 — Extract Transition Map

**Source:** `app/Services/ProjectStatusService.php` — `canTransition()` (lines 700–767).

### Allowed transitions (From → To)

| From Status | Allowed To Statuses | Roles |
|-------------|---------------------|--------|
| draft | submitted_to_provincial | executor, applicant |
| reverted_by_provincial | submitted_to_provincial | executor, applicant |
| reverted_by_coordinator | submitted_to_provincial | executor, applicant |
| reverted_by_general_as_provincial | submitted_to_provincial | executor, applicant |
| reverted_by_general_as_coordinator | submitted_to_provincial | executor, applicant |
| reverted_to_executor | submitted_to_provincial | executor, applicant |
| reverted_to_applicant | submitted_to_provincial | executor, applicant |
| reverted_to_provincial | submitted_to_provincial | executor, applicant |
| reverted_to_coordinator | submitted_to_provincial | executor, applicant |
| submitted_to_provincial | forwarded_to_coordinator | provincial, general |
| submitted_to_provincial | reverted_by_provincial | provincial, general |
| submitted_to_provincial | reverted_by_general_as_provincial | general |
| submitted_to_provincial | reverted_to_executor | general |
| submitted_to_provincial | reverted_to_applicant | general |
| forwarded_to_coordinator | approved_by_coordinator | coordinator, general |
| forwarded_to_coordinator | approved_by_general_as_coordinator | general |
| forwarded_to_coordinator | reverted_by_coordinator | coordinator, general |
| forwarded_to_coordinator | reverted_by_general_as_coordinator | general |
| forwarded_to_coordinator | reverted_to_provincial | general |
| forwarded_to_coordinator | reverted_to_coordinator | general |
| forwarded_to_coordinator | rejected_by_coordinator | coordinator |
| approved_by_coordinator | reverted_by_coordinator | coordinator, general |
| approved_by_coordinator | reverted_by_general_as_coordinator | general |
| approved_by_coordinator | reverted_to_provincial | general |
| approved_by_coordinator | reverted_to_coordinator | general |
| approved_by_general_as_coordinator | reverted_by_general_as_coordinator | general |
| approved_by_general_as_coordinator | reverted_to_provincial | general |
| approved_by_general_as_coordinator | reverted_to_coordinator | general |

**Note:** The map has no entry for `APPROVED_BY_GENERAL_AS_PROVINCIAL` as a "from" status (no transitions *from* that status in the map). There is also no transition *to* `draft` in the map (create/update "save as draft" is not modelled).

---

## SECTION 2 — Extract Actual Transitions From Code

**Scope:** Project status only (excludes User `status`, DPReport, other entities).

### Service-driven transitions (ProjectStatusService)

| From | To | Trigger | File:Line |
|------|-----|--------|-----------|
| (submittable) | submitted_to_provincial | submitToProvincial | ProjectStatusService 35; ProjectController 1751 |
| submitted_to_provincial, reverted_by_coordinator, reverted_by_general_as_coordinator, reverted_to_provincial | forwarded_to_coordinator | forwardToCoordinator | ProjectStatusService 78; ProvincialController 1533 |
| forwarded_to_coordinator, reverted_by_coordinator, reverted_to_coordinator | approved_by_coordinator OR approved_by_general_as_coordinator | approve | ProjectStatusService 128; CoordinatorController 1091 |
| forwarded_to_coordinator | rejected_by_coordinator | reject | ProjectStatusService 168; CoordinatorController 1198 |
| submitted_to_provincial, forwarded_to_coordinator, reverted_by_coordinator | reverted_by_provincial / reverted_by_general_as_provincial / reverted_to_* | revertByProvincial | ProjectStatusService 244; ProvincialController 1520, GeneralController 2650 |
| forwarded_to_coordinator, approved_by_coordinator, approved_by_general_as_coordinator | reverted_by_coordinator / reverted_by_general_as_coordinator / reverted_to_* | revertByCoordinator | ProjectStatusService 309; CoordinatorController 1018, GeneralController 2638 |
| forwarded_to_coordinator, reverted_by_coordinator, reverted_by_general_as_coordinator, reverted_to_coordinator | approved_by_general_as_coordinator | approveAsCoordinator | ProjectStatusService 357; GeneralController 2541 |
| submitted_to_provincial, reverted_by_provincial, reverted_by_general_as_provincial, reverted_to_executor, reverted_to_applicant, reverted_to_provincial | forwarded_to_coordinator | approveAsProvincial | ProjectStatusService 402; GeneralController 2584 |
| forwarded_to_coordinator, approved_by_coordinator, approved_by_general_as_coordinator | reverted_to_provincial / reverted_to_coordinator / reverted_by_general_as_coordinator | revertAsCoordinator | ProjectStatusService 453; GeneralController 2638 |
| submitted_to_provincial, forwarded_to_coordinator, reverted_by_coordinator | reverted_to_executor / reverted_to_applicant / reverted_to_provincial / reverted_by_general_as_provincial | revertAsProvincial | ProjectStatusService 508; GeneralController 2650 |
| (level-dependent) | reverted_to_executor / reverted_to_applicant / reverted_to_provincial / reverted_to_coordinator | revertToLevel | ProjectStatusService 604; GeneralController 2702 |

**revertToLevel from-sets:**

- To reverted_to_executor / reverted_to_applicant: from submitted_to_provincial, forwarded_to_coordinator, reverted_by_provincial, reverted_by_general_as_provincial (ProjectStatusService 554–559).
- To reverted_to_provincial: from forwarded_to_coordinator, approved_by_coordinator, approved_by_general_as_coordinator, reverted_by_coordinator, reverted_by_general_as_coordinator (567–573).
- To reverted_to_coordinator: from approved_by_coordinator, approved_by_general_as_coordinator (581–585).

### Direct assignments (no service)

| From | To | Trigger | File:Line |
|------|-----|--------|-----------|
| (new project) | draft | create / save as draft | ProjectController 768, 772; GeneralInfoController 30 |
| (any on update) | draft | save_as_draft | ProjectController 1524 |
| approved_by_general_as_coordinator | forwarded_to_coordinator | Budget validation failure rollback | GeneralController 2555 |

---

## SECTION 3 — Compare Map vs Reality

For each **actual** transition path (service + direct), check whether it exists in `canTransition()` with the correct role.

### Submit (executor/applicant)

| From | To | In map? | Role |
|------|-----|---------|------|
| draft | submitted_to_provincial | ✔ Present | executor, applicant |
| reverted_by_provincial | submitted_to_provincial | ✔ Present | executor, applicant |
| reverted_by_coordinator | submitted_to_provincial | ✔ Present | executor, applicant |
| reverted_by_general_as_provincial | submitted_to_provincial | ✔ Present | executor, applicant |
| reverted_by_general_as_coordinator | submitted_to_provincial | ✔ Present | executor, applicant |
| reverted_to_executor | submitted_to_provincial | ✔ Present | executor, applicant |
| reverted_to_applicant | submitted_to_provincial | ✔ Present | executor, applicant |
| reverted_to_provincial | submitted_to_provincial | ✔ Present | executor, applicant |
| reverted_to_coordinator | submitted_to_provincial | ✔ Present | executor, applicant |

### Forward / approve as provincial (General)

| From | To | In map? | Role |
|------|-----|---------|------|
| submitted_to_provincial | forwarded_to_coordinator | ✔ Present | provincial, general |
| reverted_by_provincial | forwarded_to_coordinator | ⚠ Missing | general |
| reverted_by_general_as_provincial | forwarded_to_coordinator | ⚠ Missing | general |
| reverted_to_executor | forwarded_to_coordinator | ⚠ Missing | general |
| reverted_to_applicant | forwarded_to_coordinator | ⚠ Missing | general |
| reverted_to_provincial | forwarded_to_coordinator | ⚠ Missing | general |

### Forward (Provincial)

| From | To | In map? | Role |
|------|-----|---------|------|
| submitted_to_provincial | forwarded_to_coordinator | ✔ Present | provincial, general |
| reverted_by_coordinator | forwarded_to_coordinator | ✔ Present (via general) | provincial (allowed in service) — map has only general for some; provincial+general for SUBMITTED→FORWARDED. Map does not list reverted_by_coordinator → forwarded for provincial; service allows it. So reverted_by_coordinator → forwarded: map has no "from reverted_by_coordinator" to FORWARDED. So ⚠ Missing for provincial. |

Checking map: FORWARDED has no "from" section; "from" keys are DRAFT, REVERTED_*, SUBMITTED, FORWARDED, APPROVED_*. So "reverted_by_coordinator → forwarded" is not in the map at all (only SUBMITTED → FORWARDED). So forwardToCoordinator in service allows: SUBMITTED, REVERTED_BY_COORDINATOR, REVERTED_BY_GENERAL_AS_COORDINATOR, REVERTED_TO_PROVINCIAL → FORWARDED. Map only has SUBMITTED → FORWARDED. So we have:
- reverted_by_coordinator → forwarded: ⚠ Missing
- reverted_by_general_as_coordinator → forwarded: ⚠ Missing
- reverted_to_provincial → forwarded: ⚠ Missing

### Approve (coordinator / general)

| From | To | In map? | Role |
|------|-----|---------|------|
| forwarded_to_coordinator | approved_by_coordinator | ✔ Present | coordinator, general |
| reverted_by_coordinator | approved_by_coordinator | ✔ Present | coordinator, general |
| reverted_to_coordinator | approved_by_coordinator | ✔ Present | coordinator, general |
| forwarded_to_coordinator | approved_by_general_as_coordinator | ✔ Present | general |
| reverted_by_coordinator | approved_by_general_as_coordinator | ✔ Present | general |
| reverted_by_general_as_coordinator | approved_by_general_as_coordinator | ✔ Present | general |
| reverted_to_coordinator | approved_by_general_as_coordinator | ✔ Present | general |

### Reject

| From | To | In map? | Role |
|------|-----|---------|------|
| forwarded_to_coordinator | rejected_by_coordinator | ✔ Present | coordinator |

### Revert by provincial / as provincial

| From | To | In map? | Role |
|------|-----|---------|------|
| submitted_to_provincial | reverted_by_provincial | ✔ Present | provincial, general |
| submitted_to_provincial | reverted_by_general_as_provincial | ✔ Present | general |
| submitted_to_provincial | reverted_to_executor/applicant | ✔ Present | general |
| forwarded_to_coordinator | reverted_by_provincial | ⚠ Missing (map has FORWARDED→reverted_by_coordinator, reverted_to_provincial, etc.; FORWARDED→reverted_by_provincial not in map) | provincial, general |
| forwarded_to_coordinator | reverted_by_general_as_provincial | ⚠ Missing | general |
| forwarded_to_coordinator | reverted_to_* | ✔ Present (reverted_to_provincial, reverted_to_coordinator) but reverted_to_executor, reverted_to_applicant from FORWARDED are via revertToLevel; map has FORWARDED→reverted_to_provincial, reverted_to_coordinator only | general |
| reverted_by_coordinator | reverted_by_provincial / reverted_by_general_as_provincial / reverted_to_* | ⚠ Missing (no "from reverted_by_coordinator" to reverted_by_provincial in map) | provincial, general |

### Revert by coordinator / as coordinator

| From | To | In map? | Role |
|------|-----|---------|------|
| forwarded_to_coordinator | reverted_by_coordinator | ✔ Present | coordinator, general |
| forwarded_to_coordinator | reverted_by_general_as_coordinator | ✔ Present | general |
| forwarded_to_coordinator | reverted_to_provincial, reverted_to_coordinator | ✔ Present | general |
| approved_by_coordinator | reverted_by_coordinator etc. | ✔ Present | coordinator, general |
| approved_by_general_as_coordinator | reverted_* | ✔ Present | general |

### revertToLevel (general)

| From | To | In map? | Role |
|------|-----|---------|------|
| submitted_to_provincial | reverted_to_executor/applicant | ✔ Present (submitted→reverted_to_executor/applicant) | general |
| forwarded_to_coordinator | reverted_to_executor/applicant | ⚠ Missing (map has FORWARDED→reverted_to_provincial, reverted_to_coordinator only; not reverted_to_executor, reverted_to_applicant) | general |
| reverted_by_provincial | reverted_to_executor/applicant | ⚠ Missing (map has reverted_by_provincial→submitted only) | general |
| reverted_by_general_as_provincial | reverted_to_executor/applicant | ⚠ Missing | general |
| forwarded_to_coordinator, approved_*, reverted_by_coordinator, reverted_by_general_as_coordinator | reverted_to_provincial | Partially present (FORWARDED/APPROVED/APPROVED_GENERAL→reverted_to_provincial); reverted_by_coordinator→reverted_to_provincial not in map | general |
| approved_by_coordinator, approved_by_general_as_coordinator | reverted_to_coordinator | ✔ Present | general |

### Direct / rollback

| From | To | In map? | Trigger |
|------|-----|---------|--------|
| approved_by_general_as_coordinator | forwarded_to_coordinator | ❌ Not in map (no transition from approved back to forwarded) | GeneralController 2555 rollback |
| (any) | draft | ❌ Not in map (no "to draft") | ProjectController 768, 772, 1524; GeneralInfoController 30 |

---

## SECTION 4 — Orphan Map Entries (Unused)

**Finding:** `canTransition()` is **never called** anywhere in the codebase (only defined at `ProjectStatusService.php` line 700). So from an enforcement perspective, every map entry is "unused." Below we consider whether each mapped transition is actually implemented by some code path.

- **Used (implemented by some controller/service path):** All submit transitions (draft/reverted_* → submitted), SUBMITTED→FORWARDED, SUBMITTED→reverted_*, FORWARDED→approved/reverted/rejected, APPROVED_*→reverted_*, and the revert-to-level transitions that are explicitly in the map (e.g. FORWARDED→reverted_to_provincial/coordinator) are used.
- **Unused (dead map entry):** None in the sense of "transition that is in map but never happens." Every map row corresponds to at least one possible service path. Some *actual* paths are missing from the map (see Section 3).

So: no orphan "from→to" pairs that never occur. Orphans would be the opposite — in map but no code ever does that transition. The map is missing several real transitions rather than containing unused ones.

---

## SECTION 5 — Risk Classification

### HIGH

| # | Finding | Evidence |
|---|---------|----------|
| H1 | **Rollback transition not in map:** approved_by_general_as_coordinator → forwarded_to_coordinator (budget validation failure) is allowed by direct assignment. If canTransition() were enforced, this rollback would be blocked. | GeneralController 2555 |
| H2 | **Enforcing map as-is would block valid flows:** General’s approveAsProvincial (multiple "from" → FORWARDED) and several revert paths would fail canTransition() because those from→to pairs are missing. | Section 3 missing entries |

### MEDIUM

| # | Finding | Evidence |
|---|---------|----------|
| M1 | **Map incomplete:** Several allowed transitions are not in the map: reverted_by_provincial/general_as_provincial/reverted_to_* → FORWARDED (general); reverted_by_coordinator → FORWARDED (provincial/general); FORWARDED / reverted_* → reverted_to_executor/applicant (general); reverted_by_coordinator → reverted_by_provincial (provincial/general). | ProjectStatusService approveAsProvincial, forwardToCoordinator, revertByProvincial, revertToLevel |
| M2 | **canTransition() never used:** The map is dead for enforcement; no guard or request validation calls it. | grep: only definition at ProjectStatusService 700 |

### LOW

| # | Finding | Evidence |
|---|---------|----------|
| L1 | **To-draft not modelled:** Setting status to draft on create/update is intentional but not represented in the map. Low impact if enforcement is only for workflow transitions (submit/forward/approve/revert/reject). | ProjectController 768, 772, 1524; GeneralInfoController 30 |

---

## SECTION 6 — Recommendations

### 1) Is canTransition() complete?

**No.** It is missing:

- All **approveAsProvincial** "from" statuses → FORWARDED (general): reverted_by_provincial, reverted_by_general_as_provincial, reverted_to_executor, reverted_to_applicant, reverted_to_provincial → forwarded_to_coordinator.
- **forwardToCoordinator** "from" statuses → FORWARDED: reverted_by_coordinator, reverted_by_general_as_coordinator, reverted_to_provincial → forwarded_to_coordinator (roles provincial/general as appropriate).
- **revertByProvincial** from FORWARDED / REVERTED_BY_COORDINATOR → reverted_by_provincial, reverted_by_general_as_provincial, reverted_to_*.
- **revertToLevel** paths: e.g. forwarded_to_coordinator, reverted_by_provincial, reverted_by_general_as_provincial → reverted_to_executor, reverted_to_applicant; and reverted_by_coordinator / reverted_by_general_as_coordinator → reverted_to_provincial.
- **Rollback:** approved_by_general_as_coordinator → forwarded_to_coordinator (general) for budget-failure rollback.

### 2) Is it safe to enforce canTransition()?

**No**, until the map is completed and the rollback transition is either added or handled explicitly (e.g. separate "rollback" path that bypasses transition check). As-is, enforcement would block valid General and provincial flows and the approval rollback.

### 3) What transitions must be added?

- **reverted_by_provincial** → forwarded_to_coordinator [general]
- **reverted_by_general_as_provincial** → forwarded_to_coordinator [general]
- **reverted_to_executor** → forwarded_to_coordinator [general]
- **reverted_to_applicant** → forwarded_to_coordinator [general]
- **reverted_to_provincial** → forwarded_to_coordinator [general] (already present as reverted_to_provincial → submitted only; add → forwarded)
- **reverted_by_coordinator** → forwarded_to_coordinator [provincial, general]
- **reverted_by_general_as_coordinator** → forwarded_to_coordinator [general]
- **reverted_to_provincial** → forwarded_to_coordinator [general] (if not already; map has reverted_to_provincial only → submitted)
- **forwarded_to_coordinator** → reverted_by_provincial [provincial, general]
- **forwarded_to_coordinator** → reverted_to_executor, reverted_to_applicant [general]
- **reverted_by_coordinator** → reverted_by_provincial, reverted_by_general_as_provincial, reverted_to_* [provincial, general]
- **reverted_by_general_as_coordinator** → reverted_to_provincial [general] (and any other revertToLevel from approved/reverted coordinator)
- **reverted_by_provincial** → reverted_to_executor, reverted_to_applicant [general]
- **reverted_by_general_as_provincial** → reverted_to_executor, reverted_to_applicant [general]
- **approved_by_general_as_coordinator** → forwarded_to_coordinator [general] (rollback)

(Exact list should be derived by walking each service method’s allowedStatuses and newStatus and aligning map keys/roles.)

### 4) What transitions must be removed?

**None.** All current map entries correspond to valid transitions; none should be removed. Optional: document that "to draft" is out of scope for the workflow map.

### 5) Is state machine enforcement LOW/MEDIUM/HIGH risk?

- **If enforced today:** **HIGH** — valid flows would be blocked and rollback would break.
- **After map is completed and rollback is reflected:** **MEDIUM** — enforcement becomes feasible but still needs tests and careful rollout (roles, General vs provincial, revertToLevel branches).
- **If left as documentation-only:** **LOW** — map serves as reference; no behavioral change.

---

**M4.4 Transition Map Audit Complete — No Code Changes Made**
