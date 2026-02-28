# Phase C — ProjectPermissionHelper Alignment

**Rule: Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.**

---

**Phase:** C  
**Objective:** Ensure ProjectPermissionHelper allows Coordinator (global read-only) and Provincial (province-scoped, owner or in_charge) without hidden restrictions.  
**Scope:** Project attachments only. Reports OUT OF SCOPE.

---

## 1. Objective

Verify and align `ProjectPermissionHelper::passesProvinceCheck` and `ProjectPermissionHelper::canView` so that:

- **Coordinator** always passes province check and canView (global oversight)
- **Provincial** passes province check only if `project->province_id === user->province_id`
- No status-based restrictions for view
- No hidden role switch that blocks coordinator or provincial

**Invariant:** `passesProvinceCheck` MUST always be called in the controller **before** `canView`. If a controller calls `canView` directly without `passesProvinceCheck`, coordinator (and others) could bypass province isolation incorrectly. Both checks are required in sequence.

---

## 2. Scope — Exact Files Involved

| File | Methods |
|------|---------|
| `app/Helpers/ProjectPermissionHelper.php` | passesProvinceCheck, canView |

---

## 3. What Will NOT Be Touched

- canEdit, canDelete, canSubmit, isOwnerOrInCharge, etc.
- ProjectAccessService (Phase D)
- Controllers
- Routes
- Report logic

---

## 4. Pre-Implementation Checklist

- [ ] Read current implementation of passesProvinceCheck and canView
- [ ] Document which roles bypass province check
- [ ] Document which roles bypass canView (delegation to ProjectAccessService)
- [ ] Identify any status checks in canView path
- [ ] Identify any role switch that could block coordinator or provincial

---

## 5. Step-by-Step Implementation Plan

### Step C1: Audit passesProvinceCheck

**Expected logic:**

```php
public static function passesProvinceCheck(Project $project, User $user): bool
{
    if (in_array($user->role, ['admin', 'coordinator'])) {
        return true;
    }
    if ($user->province_id === null) {
        return true;  // general typically has null
    }
    return $project->province_id === $user->province_id;
}
```

**Verify:**
- [ ] `admin` and `coordinator` are in bypass list
- [ ] `general` has province_id null → returns true (or add general to bypass if needed)
- [ ] Provincial: `project->province_id === user->province_id`
- [ ] No status check
- [ ] No other role-based branching that could block

### Step C2: Add general to Bypass (if Missing)

If `general` has `province_id` set and should have global/coordinator-level access, add `general` to bypass:

```php
if (in_array($user->role, ['admin', 'coordinator', 'general'])) {
    return true;
}
```

### Step C3: Audit canView

**Expected logic:**

```php
public static function canView(Project $project, User $user): bool
{
    return app(ProjectAccessService::class)->canViewProject($project, $user);
}
```

**Verify:**
- [ ] canView delegates solely to ProjectAccessService::canViewProject
- [ ] No direct role switch in canView
- [ ] No status check in canView
- [ ] No province check in canView (passesProvinceCheck is called separately by controller)

**Controller contract:** Every controller that uses canView MUST call passesProvinceCheck first. Document this in Phase B so implementers do not call canView alone.

### Step C4: Remove Any Blocking Logic

If canView or passesProvinceCheck contain:
- Manual `if ($user->role === 'coordinator') { ... }` that restricts
- Status whitelist (e.g. only APPROVED)
- Parent_id or hierarchy check that blocks coordinator

→ Remove or align with Phase D (ProjectAccessService owns that logic).

### Step C5: Document Null-Safety

Ensure:
- `$project->province_id` and `$user->province_id` comparison handles null
- No undefined property access

---

## 6. Security Impact Analysis

| Change | Risk | Mitigation |
|--------|------|------------|
| Add coordinator to bypass | Coordinator gains access | Intended; controller still calls both checks |
| Add general to bypass | General gains access | Intended for global oversight |
| Remove status check | Broader view access | View should not be status-gated for oversight roles |

---

## 7. Performance Impact Analysis

- Single method calls; no loops
- canView is a simple delegation
- No measurable impact

---

## 8. Rollback Strategy

- Revert `ProjectPermissionHelper.php` from version control

---

## 9. Deployment Checklist

- [ ] Unit test for passesProvinceCheck (coordinator, provincial, general)
- [ ] Unit test for canView delegation
- [ ] Verify no syntax errors

---

## 10. Regression Checklist

- [ ] Executor: passesProvinceCheck for own province project
- [ ] Executor: fails passesProvinceCheck for other province (if applicable)
- [ ] Provincial: passes for same province
- [ ] Provincial: fails for different province
- [ ] Coordinator: always passes
- [ ] General: passes (if province_id null or in bypass)

---

## 11. Sign-Off Criteria

- [ ] passesProvinceCheck: coordinator and admin bypass; general handled correctly
- [ ] passesProvinceCheck: provincial requires province match
- [ ] canView: delegates only to ProjectAccessService::canViewProject
- [ ] No status-based restriction in passesProvinceCheck or canView
- [ ] Phase_C_Implementation_Summary.md created and updated
