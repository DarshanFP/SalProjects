# Phase 3 – Redirect Standardization

## 1. Controllers Updated

**CoordinatorController::approveProject()** – Only controller modified. Approval logic, `ProjectStatusService`, and `FinancialInvariantService` are unchanged.

| Redirect Case | Before | After |
|---------------|--------|-------|
| Success | `redirect()->back()->with('success', ...)` | `redirect()->route('coordinator.approved.projects')->with('success', 'Project approved successfully.')` |
| DomainException (financial invariant) | `redirect()->back()->withErrors(...)->withInput()` | `redirect()->route('coordinator.pending.projects')->withErrors(...)->withInput()` |
| Exception (ProjectStatusService) | `redirect()->back()->withErrors(...)->withInput()` | `redirect()->route('coordinator.pending.projects')->withErrors(...)->withInput()` |

**GeneralController:** No updates. General project approval continues to use `redirect()->back()`.

---

## 2. Routes Used

| Route Name | URL | Purpose |
|------------|-----|---------|
| `coordinator.approved.projects` | `/coordinator/approved-projects` | Post-approval success |
| `coordinator.pending.projects` | `/coordinator/projects-list/pending` | Post-validation failure (new route) |

`coordinator.pending.projects` was added and targets `CoordinatorController::projectList`, same as `coordinator.projects.list`, but as a dedicated path for pending-project context.

---

## 3. Before vs After Redirect Behavior

### Before (Phase 2B)

| Outcome | Redirect | Behavior |
|---------|----------|----------|
| Success | `back()` | Destination depended on Referer (dashboard, list, show, etc.) |
| Validation failure | `back()` | Same as above |
| Service exception | `back()` | Same as above |

### After (Phase 3)

| Outcome | Redirect | Behavior |
|---------|----------|----------|
| Success | `coordinator.approved.projects` | Always goes to approved projects list |
| Validation failure | `coordinator.pending.projects` | Always goes to pending project list |
| Service exception | `coordinator.pending.projects` | Always goes to pending project list |

---

## 4. Test Updates

| Test | Change |
|------|--------|
| `test_coordinator_can_approve_valid_project_flow` | `assertRedirect()` → `assertRedirect(route('coordinator.approved.projects'))` |
| `test_zero_opening_balance_blocks_approval` | `assertRedirect()` → `assertRedirect(route('coordinator.pending.projects'))` |

Both tests now assert specific target routes instead of only asserting a redirect.

---

## 5. UX Improvements

- **Predictable success flow:** Approval always sends the user to the approved projects list.
- **Predictable failure flow:** Validation or service errors always send the user to the pending projects list.
- **No Referer dependence:** Destination no longer depends on the page from which the approval was submitted.
- **Less confusion:** Users always land on the correct list instead of context-dependent back navigation.

---

## 6. Production Safety Confirmation

- Approval logic unchanged
- `FinancialInvariantService` unchanged
- `ProjectStatusService` unchanged
- Success and error messages still flashed
- All 25 tests pass
- No new redirect loops
- No route errors; `coordinator.pending.projects` is defined and used
