# Phase C — ProjectPermissionHelper Alignment

**Rule:** Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.

---

**Phase:** C  
**Objective:** Ensure `canView` delegates to ProjectAccessService for coordinator. Remove duplicated logic. Preserve provincial hierarchy logic.

---

## 1. Objective

ProjectPermissionHelper::canView currently returns true for coordinator after passesProvinceCheck. To avoid drift and ensure single source of truth, coordinator case should delegate to ProjectAccessService::canViewProject. Provincial and other roles remain as-is or are refactored consistently.

---

## 2. Scope (Exact Files)

| File | Change |
|------|--------|
| `app/Helpers/ProjectPermissionHelper.php` | For coordinator (and optionally admin, general): delegate to ProjectAccessService::canViewProject instead of blanket return true. Ensure no double logic. |
| `app/Http/Controllers/Projects/ProjectController.php` | No change if it continues to use ProjectPermissionHelper::canView; that helper will internally delegate. |

---

## 3. What Will NOT Be Touched

- Provincial hierarchy logic in ProjectPermissionHelper (passesProvinceCheck for provincial)
- Executor/applicant logic
- ProjectAccessService
- CoordinatorController (Phase B)
- ActivityHistoryHelper
- Routes

---

## 4. Pre-Implementation Checklist

- [ ] Phase A and B complete
- [ ] ProjectAccessService::canViewProject handles coordinator correctly
- [ ] ProjectController::show uses ProjectPermissionHelper::canView (verify)

---

## 5. Failing Tests to Write First

```php
// tests/Unit/Helpers/ProjectPermissionHelperCoordinatorTest.php

public function test_can_view_delegates_to_project_access_service_for_coordinator(): void
public function test_coordinator_can_view_any_project_via_helper(): void
public function test_provincial_hierarchy_logic_preserved(): void
public function test_executor_applicant_logic_unchanged(): void
```

---

## 6. Step-by-Step Implementation Plan

### Step C.1 — Delegate Coordinator to ProjectAccessService

**Location:** `ProjectPermissionHelper::canView()` (approx. L88–101)

**Current:**
```php
if (in_array($user->role, ['admin', 'coordinator', 'provincial', 'general'])) {
    return true;
}
```
(After passesProvinceCheck.)

**Actions:**
- For coordinator: Replace `return true` with `return app(ProjectAccessService::class)->canViewProject($project, $user)`.
- Ensure ProjectAccessService is resolvable (Laravel container).
- passesProvinceCheck remains the first gate; ProjectAccessService::canViewProject also uses it internally. Avoid double province check — either:
  - Option A: Remove passesProvinceCheck for coordinator and let ProjectAccessService handle all checks.
  - Option B: Keep passesProvinceCheck; ProjectAccessService::canViewProject will run it again (redundant but safe).
- Recommendation: For coordinator only, skip passesProvinceCheck and delegate entirely to ProjectAccessService to avoid duplication.

### Step C.2 — Refine Logic Flow

**Proposed flow:**
```php
// For coordinator: delegate to ProjectAccessService (single source of truth)
if ($user->role === 'coordinator') {
    return app(ProjectAccessService::class)->canViewProject($project, $user);
}
// For admin, general: same (or keep true if ProjectAccessService handles them)
if (in_array($user->role, ['admin', 'general'])) {
    return app(ProjectAccessService::class)->canViewProject($project, $user);
}
// For provincial: delegate (ProjectAccessService has hierarchy)
if ($user->role === 'provincial') {
    return app(ProjectAccessService::class)->canViewProject($project, $user);
}
// For executor, applicant: existing logic
```

This centralizes all view logic in ProjectAccessService. ProjectPermissionHelper becomes a thin wrapper.

### Step C.3 — Remove Blanket Return True

**Actions:**
- Eliminate the blanket `return true` for coordinator. Replace with delegation.

### Step C.4 — Preserve Provincial Hierarchy

**Actions:**
- Ensure provincial still goes through ProjectAccessService (which has getAccessibleUserIds with owner+in_charge).
- No regression in provincial scope.

---

## 7. Security Impact Analysis

- **Neutral:** Behavior unchanged; coordinator still sees all. Delegation improves maintainability.
- **Reduced risk:** Single source of truth; no drift between helper and service.

---

## 8. Performance Impact Analysis

- **Minimal:** One extra service resolve and method call per canView for coordinator. Negligible.
- **Potential circular dependency:** ProjectAccessService uses ProjectPermissionHelper::passesProvinceCheck. If ProjectPermissionHelper calls ProjectAccessService::canViewProject, which uses passesProvinceCheck — no circle. canViewProject uses passesProvinceCheck directly. Verify no circular dependency.

---

## 9. Rollback Strategy

1. Revert ProjectPermissionHelper::canView to previous logic (blanket true for coordinator).
2. Re-run tests.

---

## 10. Deployment Checklist

- [ ] Phase C tests pass
- [ ] Coordinator can view any project via ProjectController::show
- [ ] Provincial scope unchanged
- [ ] No circular dependency
- [ ] Code review completed

---

## 11. Regression Checklist

- [ ] Provincial canView unchanged
- [ ] Executor/applicant canView unchanged
- [ ] Coordinator canView unchanged (behavior)
- [ ] ProjectController::show works for coordinator
- [ ] ProjectController::show works for provincial
- [ ] ProjectController::show works for executor

---

## 12. Sign-Off Criteria

- canView delegates to ProjectAccessService for coordinator (and optionally provincial, admin, general)
- No blanket return true for coordinator
- Provincial hierarchy logic preserved
- Phase C completion MD updated
