# Phase 1 – Display Alignment Implementation

**Date:** February 13, 2026  
**Scope:** READ-ONLY display paths  
**Status:** Implemented

---

## Summary

Phase 1 aligns all financial display flows so that values shown to users come **only** from `ProjectFinancialResolver::resolve($project)`. No raw database columns, no inline arithmetic, and no fallback to `$project->overall_project_budget` or similar attributes in display paths.

**What was modified:**
- **ProvincialController::projectList()** – Builds `$resolvedFinancials` keyed by `project_id` and passes it to the view.
- **ProjectList.blade.php** – Replaces raw project attributes and inline arithmetic with resolved financials.
- **ProjectController::show()** – Always calls `ProjectFinancialResolver::resolve($project)`; no conditional fallback.
- **general_info.blade.php** – Removes fallback to raw `$project`; uses only `$resolvedFundFields`; removes inline arithmetic.

---

## Files Modified

| File | Purpose |
|------|---------|
| `app/Http/Controllers/ProvincialController.php` | Build `$resolvedFinancials` array during project map; pass to ProjectList view |
| `resources/views/provincial/ProjectList.blade.php` | Display overall, forwarded, local, amount_requested from `$resolvedFinancials[$project->project_id]` |
| `app/Http/Controllers/Projects/ProjectController.php` | Always call `ProjectFinancialResolver::resolve()`; remove conditional resolver logic |
| `resources/views/projects/partials/Show/general_info.blade.php` | Use only `$resolvedFundFields`; remove fallback and inline arithmetic |

---

## Before vs After Behavior

### Before

| Location | Source | Issue |
|----------|--------|-------|
| Provincial ProjectList | `$project->overall_project_budget`, `amount_forwarded`, `local_contribution`; inline `amount_requested = max(0, overall - forwarded - local)` | Raw DB. For phase-based types (CIC, NPD), overall comes from budget rows; list showed stale/wrong values (e.g. CIC-0002: Rs. 5,04,000 vs. Rs. 23,14,000 on view). |
| ProjectController::show | Conditional resolver: only when `resolver_enabled` or type-specific budget | When resolver not used, view fell back to raw `$project`; mismatch possible. |
| general_info.blade.php | Fallback: `$resolvedFundFields` when present, else `$project->*`; inline `$amount_requested = max(0, overall - forwarded - local)` | Inconsistent source; inline arithmetic duplicated resolver logic. |

### After

| Location | Source | Result |
|----------|--------|--------|
| Provincial ProjectList | `$resolvedFinancials[$project->project_id]` for all fund fields; `amount_requested` from `amount_sanctioned` | List values match project view. |
| ProjectController::show | Always `ProjectFinancialResolver::resolve($project)` | No fallback; all project types use resolver. |
| general_info.blade.php | Only `$resolvedFundFields`; no fallback; no inline arithmetic | Single source of truth. |

**Mismatch resolved:** Provincial list columns (Overall Project Budget, Existing Funds, Local Contribution, Amount Requested) now match the project view for CIC, NPD, and all other project types.

---

## Safety Confirmation

| Check | Status |
|-------|--------|
| No DB writes changed | ✅ Confirmed – no save/update logic modified |
| No schema changes | ✅ Confirmed – no migrations |
| No JS modified | ✅ Confirmed – scripts-edit, scripts, budget-calculations.js untouched |
| Approval logic untouched | ✅ Confirmed – CoordinatorController, GeneralController approval flows unchanged |
| No services changed except resolver usage | ✅ Confirmed – only display paths now call resolver; no write-path services modified |

---

## Manual Testing Checklist

- [ ] **Provincial list matches project view** – For a given project, Overall Project Budget, Existing Funds, Local Contribution, and Amount Requested on `/provincial/projects-list` match the Basic Information section on the project view page.
- [ ] **CIC project verified** – CIC-0002 (or another CIC): list shows Rs. 23,14,000 (or correct resolver value), not Rs. 5,04,000.
- [ ] **NPD project verified** – NPD-0002 (or another NPD): list and view both show same overall, forwarded, local, amount requested.
- [ ] **Approved project verified** – For an approved project, list and view show same values (resolver returns DB snapshot for approved).
- [ ] **Draft project verified** – For a draft with budget rows, list and view show resolver-computed overall (sum of this_phase) and derived amount_requested.

---

*End of document*
