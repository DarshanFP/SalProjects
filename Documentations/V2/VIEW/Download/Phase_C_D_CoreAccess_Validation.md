# Phase C + D — Core Access Logic Validation Report

**Rule:** Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.

**Validation Date:** 2025-02-23  
**Scope:** ProjectPermissionHelper and ProjectAccessService only (verification + documentation).  
**Constraints:** No code modifications. No controller/route/view changes.

---

## 1. Executive Summary

| Metric | Result |
|--------|--------|
| **ProjectPermissionHelper** | Pass — correct delegation, no logic duplication |
| **passesProvinceCheck** | Pass — coordinator/admin bypass, provincial isolation |
| **ProjectAccessService** | Pass — coordinator global, provincial owner/in_charge |
| **Type safety** | Minor finding — loose comparison on L81, no strict `in_array` |
| **Cross-layer consistency** | Pass — controller → passesProvinceCheck → canView → service |
| **Status restriction** | Pass — no status check for view |
| **Conclusion** | **PASS** — Safe to proceed to Phase B. One optional hardening (type safety) noted. |

---

## 2. ProjectPermissionHelper Audit

**File:** `app/Helpers/ProjectPermissionHelper.php`

### 2.1 canView() — L95–99

| Question | Answer |
|----------|--------|
| Does it call ProjectAccessService? | **Yes** — `return app(ProjectAccessService::class)->canViewProject($project, $user);` |
| Or does it implement its own logic? | **No** — pure delegation |
| Role-based branching? | **None** — single-line delegation |
| passesProvinceCheck in canView? | **No** — handled by controller and by service internally |
| Status checked here? | **No** |
| In-charge considered? | **N/A** — delegated to service |

### 2.2 Method Summary Table

| Method | Role Branching | Delegates to Service | Status Restriction | In-Charge Logic | Risk |
|--------|----------------|----------------------|--------------------|-----------------|------|
| **canView** | No | Yes (ProjectAccessService::canViewProject) | No | N/A (service handles) | None |
| **passesProvinceCheck** | Yes (admin/coordinator bypass) | No | No | N/A | None |
| **canEdit** | Yes (per role) | No | Yes (ProjectStatus::canEditForRole) | Yes (L50) | None |

---

## 3. passesProvinceCheck Audit

**Location:** `ProjectPermissionHelper.php` L24–34

### 3.1 Logic (Precise)

```
1. if (in_array($user->role, ['admin', 'coordinator'])) → return true
2. if ($user->province_id === null) → return true
3. return $project->province_id === $user->province_id
```

### 3.2 Verification

| Requirement | Result | Notes |
|-------------|--------|-------|
| Province from project? | Yes | `$project->province_id` |
| Province from user? | Yes | `$user->province_id` |
| Coordinator bypass? | Yes | In first `in_array` (L26) |
| Admin bypass? | Yes | Same branch |
| Provincial isolation? | Yes | L33: `$project->province_id === $user->province_id` |
| Session province dependency? | No | Uses model attributes only |
| Status check? | No | Correct — no status for province check |
| General role? | Via L29–30 | If `province_id === null` → true (backward compatibility) |

### 3.3 Potential Bypass

- **None identified.** Bypass applies only to admin and coordinator.
- General with non-null province_id is treated as provincial (province match required).

---

## 4. ProjectAccessService Audit

**File:** `app/Services/ProjectAccessService.php`

### 4.1 canViewProject() — L71–89

| Role | Behavior | Verified |
|------|----------|----------|
| **Admin** | Returns true (after province check) | L77 |
| **Coordinator** | Returns true (after province check) | L77 |
| **General** | Returns true (after province check) | L77 |
| **Executor/Applicant** | `user_id === user->id` OR `in_charge == user->id` | L80–81 |
| **Provincial** | Owner OR in_charge in `getAccessibleUserIds` | L83–87 |
| **Other** | Returns false | L88 |

| Check | Result |
|-------|--------|
| Provincial → owner OR in_charge? | Yes — L86–87 |
| Coordinator → true? | Yes — L77 |
| Admin → true? | Yes — L77 |
| General → true? | Yes — L77 |
| Status restriction? | No |
| parent_id restriction? | No (coordinator does not use hierarchy) |
| Hierarchy for coordinator? | No — early return before provincial branch |

### 4.2 getAccessibleUserIds() — L28–61

| Check | Result | Notes |
|-------|--------|-------|
| Child retrieval | Correct | `User::where('parent_id', $provincial->id)->whereIn('role', ['executor', 'applicant'])` |
| Caching? | Yes | `$this->accessibleUserIdsCache[$cacheKey]` |
| Duplicates removed? | Yes | `$userIds->unique()->values()` |
| Cast to int? | No | Uses `pluck('id')` as-is (type depends on DB) |

### 4.3 Provincial Branch Logic (L83–87)

```php
$accessibleUserIds = $this->getAccessibleUserIds($user);
$ids = $accessibleUserIds->toArray();
return in_array($project->user_id, $ids)
    || ($project->in_charge && in_array($project->in_charge, $ids));
```

- **Owner (user_id):** Checked.
- **In-charge:** Checked when non-null.
- **Null in_charge:** Short-circuits; no error.

---

## 5. Role Matrix Verification

| Role | passesProvinceCheck | canViewProject | View Access |
|------|---------------------|----------------|-------------|
| **Admin** | Bypass (true) | true | Global |
| **Coordinator** | Bypass (true) | true | Global |
| **General** | true if province_id null; else province match | true (after province) | Global (or province-scoped) |
| **Provincial** | Province match | Owner OR in_charge in scope | Province-scoped |
| **Executor** | Province match | Own OR in_charge | Province-scoped |
| **Applicant** | Province match | Own OR in_charge | Province-scoped |

---

## 6. Type Safety Analysis

| Location | Code | Issue | Risk |
|----------|------|-------|------|
| **ProjectAccessService L81** | `$project->in_charge == $user->id` | Loose `==` | Low — allows string/int coercion |
| **ProjectAccessService L86–87** | `in_array($project->user_id, $ids)` | No strict (no `true` 3rd param) | Low — string "123" vs int 123 |
| **ProjectPermissionHelper L26** | `in_array($user->role, [...])` | No strict | Very low — roles are strings |
| **getAccessibleUserIds** | `pluck('id')` | No explicit cast | Low — DB may return string for bigint |

### 6.1 Recommendation (Optional Hardening)

Phase D docs suggest:

```php
$ids = $accessibleUserIds->map(fn ($id) => (int) $id)->toArray();
return in_array((int) $project->user_id, $ids, true)
    || ($project->in_charge !== null && in_array((int) $project->in_charge, $ids, true));
```

And for executor/applicant (L81): use `===` with casting: `(int) $project->in_charge === (int) $user->id`.

**Current risk level:** Low. PHP's loose comparison and typical Laravel/MySQL behavior make mismatches unlikely in normal use. Hardening is optional for defense-in-depth.

---

## 7. Hidden Risk Analysis

| Risk | Result |
|------|--------|
| Hidden role switches blocking coordinator/provincial? | None found |
| Early returns bypassing logic? | None — flow is linear |
| Session province affecting coordinator? | No — coordinator bypasses province check |
| parent_id affecting coordinator? | No — coordinator never uses getAccessibleUserIds |
| Null user->role? | Not explicitly checked — could fall through to `return false` (safe) |

---

## 8. Province Isolation Verification

| Role | Isolation enforced by |
|------|------------------------|
| **Coordinator/Admin** | Bypass — no province restriction (intended) |
| **General (province_id null)** | Bypass — no restriction (intended) |
| **Provincial** | `project->province_id === user->province_id` (L33) |
| **Executor/Applicant** | Same — province must match |

**Conclusion:** Province isolation is correct. Coordinator/admin have global oversight by design.

---

## 9. Status Restriction Check

| Component | Status check for view? |
|-----------|------------------------|
| passesProvinceCheck | No |
| canView (helper) | No |
| canViewProject (service) | No |

**Conclusion:** No status-based restriction for view/download. Correct for oversight roles.

---

## 10. Cross-Layer Consistency Map

```
Controller (e.g. AttachmentController::viewAttachment)
    │
    ├─► passesProvinceCheck(project, user)  [ProjectPermissionHelper]
    │       └─► abort(403) if false
    │
    └─► canView(project, user)  [ProjectPermissionHelper]
            └─► ProjectAccessService::canViewProject(project, user)
                    ├─► passesProvinceCheck(project, user)  [redundant but safe]
                    ├─► admin/coordinator/general → true
                    ├─► executor/applicant → user_id || in_charge match
                    └─► provincial → owner OR in_charge in getAccessibleUserIds
```

| Check | Result |
|-------|--------|
| Controller calls passesProvinceCheck → canView | Yes (per Attachment_GuardChain_Verification) |
| canView delegates to ProjectAccessService | Yes |
| Logic duplication? | No — both use same ProjectPermissionHelper::passesProvinceCheck |
| Contradictory behavior? | No |

---

## 11. Findings

### 11.1 Satisfactory

- Coordinator: global read access (no parent_id, no hierarchy).
- Provincial: owner OR in_charge in getAccessibleUserIds.
- passesProvinceCheck: admin/coordinator bypass; provincial isolation.
- canView: delegates only to ProjectAccessService.
- No status restriction for view.
- No duplication of logic between helper and service.
- No early returns that bypass intended logic.
- in_charge handled for provincial, executor, applicant.

### 11.2 Minor (Optional Fix)

| ID | Finding | Location | Severity |
|----|---------|----------|----------|
| T1 | Loose `==` for in_charge | ProjectAccessService L81 | Low |
| T2 | in_array without strict | ProjectAccessService L86–87 | Low |
| T3 | No int cast in getAccessibleUserIds | ProjectAccessService L59 | Low |
| T4 | in_array without strict | ProjectPermissionHelper L26, L46, L49, L52, L84, L177 | Very low |

---

## 12. Risk Level

**Overall: LOW**

Core access logic matches Phase C and D objectives. Optional type-safety improvements would reduce edge-case risk but are not required to proceed.

---

## 13. Conclusion

### PASS — Safe to Proceed to Phase B

Core access logic for Project Attachment Download satisfies:

1. **Provincial:** Can view when owner OR in_charge is in accessible user IDs; province isolation enforced by passesProvinceCheck; no status restriction.
2. **Coordinator:** Global read access; no parent_id or province restriction.
3. **Type safety:** Minor loose comparisons and missing strict `in_array`; risk is low.
4. **No hidden role switches** that block coordinator or provincial.
5. **No early returns** that bypass intended logic.
6. **No duplication** between helper and service.

**Optional (non-blocking):** Apply type-safety hardening per Phase D Step D2 (strict in_array, int casting) in a future change. Not required for Phase B rollout.
