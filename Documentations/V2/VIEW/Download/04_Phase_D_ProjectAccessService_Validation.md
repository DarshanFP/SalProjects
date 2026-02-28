# Phase D — ProjectAccessService Validation

**Rule: Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.**

---

**Phase:** D  
**Objective:** Ensure ProjectAccessService::canViewProject correctly allows Provincial (owner OR in_charge in scope) and Coordinator (global true).  
**Scope:** Project attachments only. Reports OUT OF SCOPE.

---

## 1. Objective

Validate and align `ProjectAccessService::canViewProject` and `getAccessibleUserIds` so that:

- **Coordinator** returns `true` after province check (no hierarchy, no parent_id)
- **Provincial** returns `true` when `project->user_id` OR `project->in_charge` is in `getAccessibleUserIds`
- No hidden parent_id enforcement for coordinator
- No unintended scope restriction for provincial

---

## 2. Scope — Exact Files Involved

| File | Methods |
|------|---------|
| `app/Services/ProjectAccessService.php` | canViewProject, getAccessibleUserIds |

---

## 3. What Will NOT Be Touched

- ProjectPermissionHelper
- Controllers
- Routes
- Report logic
- getVisibleProjectsQuery (unless it affects canViewProject)

---

## 4. Pre-Implementation Checklist

- [ ] Read canViewProject implementation
- [ ] Read getAccessibleUserIds implementation
- [ ] Document role branches
- [ ] Verify provincial branch uses owner OR in_charge
- [ ] Verify coordinator branch returns true (no getAccessibleUserIds for coordinator)
- [ ] Check in_charge type (int vs string) for in_array

---

## 5. Step-by-Step Implementation Plan

### Step D1: Audit canViewProject

**Expected flow:**

```
1. passesProvinceCheck(project, user) → if false, return false
2. if role in [admin, coordinator, general] → return true
3. if role in [executor, applicant] → return (user_id match OR in_charge match)
4. if role === provincial → getAccessibleUserIds; return (user_id in ids OR in_charge in ids)
5. else return false
```

**Verify:**
- [ ] Coordinator is in early-return true branch
- [ ] No call to getAccessibleUserIds for coordinator
- [ ] No parent_id check for coordinator
- [ ] Provincial uses getAccessibleUserIds
- [ ] Provincial checks BOTH user_id and in_charge
- [ ] in_charge comparison handles null and type (int/string)

### Step D2: Provincial Branch — Owner OR In_Charge

**Expected:**

```php
if ($user->role === 'provincial') {
    $accessibleUserIds = $this->getAccessibleUserIds($user);
    $ids = $accessibleUserIds->toArray();
    return in_array($project->user_id, $ids)
        || ($project->in_charge && in_array($project->in_charge, $ids));
}
```

**Verify:**
- [ ] Both user_id (owner) and in_charge are checked
- [ ] Null in_charge does not cause error

**Type safety (recommended):** Use strict comparison and consistent casting to avoid subtle type coercion bugs:

```php
$ids = $accessibleUserIds->map(fn ($id) => (int) $id)->toArray();
return in_array((int) $project->user_id, $ids, true)
    || ($project->in_charge !== null && in_array((int) $project->in_charge, $ids, true));
```

Alternatively: `in_array($project->user_id, $ids, true)` with IDs cast to int at the source (getAccessibleUserIds).

### Step D3: Coordinator Branch

**Expected:**
- Coordinator returns true after passesProvinceCheck (which coordinator bypasses)
- No getAccessibleUserIds
- No parent_id
- No hierarchy filter

### Step D4: Audit getAccessibleUserIds

**For provincial:**
- Returns direct children (executor/applicant under this provincial)
- For general: may include users in managed provinces (with province filter)
- Does NOT include coordinator
- Used only for provincial (and general if applicable)

**Verify:**
- [ ] Returns Collection of user IDs
- [ ] Includes executors/applicants in scope
- [ ] Cached per request (optional but recommended)
- [ ] No unintended filtering that would exclude valid owner/in_charge

### Step D5: General Role

If general is treated like coordinator for project view:
- [ ] general in same branch as coordinator (return true)
- [ ] Or separate branch that also returns true after province check

### Step D6: Null-Safety

- [ ] $project->in_charge null: `($project->in_charge && in_array(...))` short-circuits
- [ ] $project->user_id: ensure not null when used in in_array
- [ ] getAccessibleUserIds returns empty collection when no children (provincial blocks correctly)

---

## 6. Security Impact Analysis

| Change | Risk | Mitigation |
|--------|------|------------|
| Add in_charge to provincial check | Broader access | Intended; provincial should see in-charge projects |
| Coordinator always true | Global access | Intended; oversight role |
| Ensure no parent_id for coordinator | Prevents accidental restriction | Correct architecture |

---

## 7. Performance Impact Analysis

- getAccessibleUserIds: runs once per provincial request; cache per request if not already
- canViewProject: O(1) for coordinator; O(n) for provincial where n = size of accessibleUserIds (small)
- Negligible impact

---

## 8. Rollback Strategy

- Revert `ProjectAccessService.php` from version control

---

## 9. Deployment Checklist

- [ ] Unit test for canViewProject (coordinator, provincial owner, provincial in_charge)
- [ ] Verify getAccessibleUserIds returns expected IDs for test provincial user

---

## 10. Regression Checklist

- [ ] Coordinator: canViewProject returns true for any project (same province or not)
- [ ] Provincial: returns true when owner in scope
- [ ] Provincial: returns true when in_charge in scope (owner may be out of scope)
- [ ] Provincial: returns false when both owner and in_charge out of scope
- [ ] Executor: returns true for own project, in_charge project
- [ ] Executor: returns false for other's project

---

## 11. Sign-Off Criteria

- [ ] Coordinator returns true from canViewProject (no hierarchy/parent_id)
- [ ] Provincial checks owner OR in_charge in getAccessibleUserIds
- [ ] getAccessibleUserIds correctly scoped for provincial
- [ ] No unintended scope restriction
- [ ] Phase_D_Implementation_Summary.md created and updated
