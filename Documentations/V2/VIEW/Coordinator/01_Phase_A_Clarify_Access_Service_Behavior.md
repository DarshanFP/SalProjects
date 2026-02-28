# Phase A — Clarify Access Service Behavior

**Rule:** Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.

---

**Phase:** A  
**Objective:** Ensure ProjectAccessService intentionally treats coordinator as global read-only.  
**Constraint:** Do NOT change controllers yet.

---

## 1. Objective

Audit and clarify `ProjectAccessService` so that coordinator is explicitly documented as having global read-only access. Remove ambiguity. Ensure no `parent_id` or hierarchy logic applies to coordinator.

---

## 2. Scope (Exact Files)

| File | Change |
|------|--------|
| `app/Services/ProjectAccessService.php` | Add/update docblocks; ensure `canViewProject` and `getVisibleProjectsQuery` explicitly state coordinator = global; no hierarchy logic for coordinator |

---

## 3. What Will NOT Be Touched

- CoordinatorController
- ProjectPermissionHelper
- ActivityHistoryHelper
- ExportController
- Routes
- Blade templates
- Provincial or executor logic

---

## 4. Pre-Implementation Checklist

- [ ] Read current ProjectAccessService implementation
- [ ] Confirm coordinator is grouped with admin/general in `canViewProject` and `getVisibleProjectsQuery`
- [ ] Confirm `getAccessibleUserIds` is never called with coordinator (it is provincial/general only)
- [ ] No existing `parent_id` or hierarchy logic for coordinator in ProjectAccessService

---

## 5. Failing Tests to Write First

```php
// tests/Unit/Services/ProjectAccessServiceCoordinatorTest.php

public function test_coordinator_can_view_any_project(): void
public function test_coordinator_get_visible_projects_query_returns_all(): void
public function test_coordinator_no_parent_id_logic_applied(): void
```

---

## 6. Step-by-Step Implementation Plan

### Step A.1 — Audit canViewProject

**Location:** `app/Services/ProjectAccessService.php` (approx. L64–82)

**Current behavior:** Coordinator in `['admin','coordinator','general']` → return true after `passesProvinceCheck`.

**Actions:**
- Add docblock: "Coordinator: global read-only oversight. No hierarchy. Returns true after province check (coordinator typically has province_id=null)."
- Ensure no conditional branch applies `parent_id` or `getAccessibleUserIds` to coordinator.

### Step A.2 — Audit getVisibleProjectsQuery

**Location:** `app/Services/ProjectAccessService.php` (approx. L88–106)

**Current behavior:** Coordinator in `['admin','coordinator','general']` → return unfiltered `Project::query()`.

**Actions:**
- Add docblock: "Coordinator: global oversight. Returns unfiltered query (all projects). No parent_id or hierarchy filter."
- Ensure no branch applies `accessibleByUserIds` or similar to coordinator.

### Step A.3 — Explicitly Document Coordinator Logic

**Actions:**
- Add class-level docblock or comment block stating: "Coordinator is a top-level oversight role. No hierarchy. Global read access. Does NOT use getAccessibleUserIds."

### Step A.4 — Remove Ambiguity

**Actions:**
- If any TODO or vague comment suggests hierarchy for coordinator, clarify or remove.

### Step A.5 — Verify getAccessibleUserIds

**Location:** `app/Services/ProjectAccessService.php` (approx. L24–58)

**Actions:**
- Confirm method is only ever called with provincial or general users.
- Add docblock: "For provincial and general only. Coordinator does NOT use this method."

---

## 7. Security Impact Analysis

- **No behavioral change:** Coordinator already gets global access. This phase documents and clarifies only.
- **Reduced risk:** Explicit documentation prevents future developers from incorrectly adding hierarchy logic for coordinator.

---

## 8. Performance Impact Analysis

- **No impact:** No query or logic changes. Documentation only.

---

## 9. Rollback Strategy

- Revert docblock/comment changes only. No logic reverted.

---

## 10. Deployment Checklist

- [ ] Phase A tests pass
- [ ] No regression in coordinator project list or show
- [ ] Code review completed

---

## 11. Regression Checklist

- [ ] Provincial project list unchanged
- [ ] Provincial showProject unchanged
- [ ] Coordinator project list shows all projects
- [ ] Coordinator can view any project

---

## 12. Sign-Off Criteria

- ProjectAccessService docblocks explicitly state coordinator = global
- No hierarchy logic for coordinator
- Phase A completion MD updated
