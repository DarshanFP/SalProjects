# IIES Project Creation Flow — Architectural Audit

**Date:** 2026-02-08  
**Scope:** Structural correctness audit of IIES project creation (transaction boundaries, exception handling, atomicity)  
**Status:** Read-only analysis; no fixes proposed

---

## Executive Summary

The IIES project creation flow has structural correctness issues that can cause:

1. **Partial persistence** — some related records committed while others are not  
2. **Swallowed errors** — sub-controller failures not propagated to the outer transaction  
3. **Misleading logs** — "saved" messages before commit, with no guarantee of durability

The flow is **not atomic**. Sub-controllers use nested transactions and catch exceptions without re-throwing, so failures in IIES Personal Info, Immediate Family Details, Education Background, or Attachments do not trigger rollback of the outer transaction.

---

## 1. Transaction Nesting and Savepoint Behavior

### 1.1 Structure

| Layer | Controller | Transaction | Behavior |
|-------|------------|-------------|----------|
| **Outer** | `ProjectController` | `DB::beginTransaction()` at L554 | Owns the durable unit |
| **No transaction** | `GeneralInfoController` | None | Writes within outer transaction |
| **No transaction** | `KeyInformationController` | None | Writes within outer transaction |
| **Nested** | `IIESPersonalInfoController` | `DB::beginTransaction()` at L54 | Savepoint |
| **No transaction** | `IIESFamilyWorkingMembersController` | None | Writes within outer transaction |
| **Nested** | `IIESImmediateFamilyDetailsController` | `DB::beginTransaction()` at L57 | Savepoint |
| **Nested** | `EducationBackgroundController` | `DB::beginTransaction()` at L25 | Savepoint |
| **No transaction** | `FinancialSupportController` | None | Writes within outer transaction |
| **Nested** | `IIESAttachmentsController` | `DB::beginTransaction()` at L41 | Savepoint |
| **No transaction** | `IIESExpensesController` | None | Writes within outer transaction |

### 1.2 Laravel Nested Transaction Behavior

- `DB::beginTransaction()` when already in a transaction creates a savepoint.
- `DB::rollBack()` in a nested controller rolls back only to that savepoint.
- `DB::commit()` in a nested controller releases the savepoint; it does **not** commit the outer transaction.

**Evidence:**  
- `app/Http/Controllers/Projects/ProjectController.php` L554: `DB::beginTransaction()`  
- `app/Http/Controllers/Projects/IIES/IIESPersonalInfoController.php` L53-54: nested `DB::beginTransaction()`  
- `app/Http/Controllers/Projects/IIES/IIESImmediateFamilyDetailsController.php` L56-57  
- `app/Http/Controllers/Projects/IIES/EducationBackgroundController.php` L24-25  
- `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php` L40-41  

### 1.3 Correctness Violation: Inconsistent Transaction Boundaries

> **Violation:** IIES sub-controllers mix transactional and non-transactional behavior. Controllers with nested transactions manage their own savepoints; others rely entirely on the outer transaction. There is no single, consistent transaction boundary for the IIES create flow.

---

## 2. Exception Swallowing vs Propagation

### 2.1 Exception Handling by Controller

| Controller | Catch | Re-throw | Propagates to ProjectController |
|------------|-------|----------|---------------------------------|
| `GeneralInfoController` | No | — | Yes |
| `KeyInformationController` | Yes (L73-75) | **Yes** (`throw $e`) | Yes |
| `IIESPersonalInfoController` | Yes (L65-68) | **No** (returns JSON) | **No** |
| `IIESFamilyWorkingMembersController` | No | — | Yes |
| `IIESImmediateFamilyDetailsController` | Yes (L69-73) | **No** (returns JSON) | **No** |
| `EducationBackgroundController` | Yes (L38-42) | **No** (returns JSON) | **No** |
| `FinancialSupportController` | No | — | Yes |
| `IIESAttachmentsController` | Yes (L79-85) | **No** (returns JSON) | **No** |
| `IIESExpensesController` | No | — | Yes |

### 2.2 Correctness Violations

> **Violation 1 — Swallowed Exceptions:**  
> `IIESPersonalInfoController` (L65-68), `IIESImmediateFamilyDetailsController` (L69-73), `EducationBackgroundController` (L38-42), and `IIESAttachmentsController` (L79-85) catch exceptions and return HTTP responses. They do **not** re-throw. ProjectController never sees these errors and continues execution.

> **Violation 2 — Ignored Return Values:**  
> ProjectController does not inspect the return value of any IIES sub-controller. A 500 JSON response is treated the same as a 200 success. Execution continues regardless.

**Evidence:**

```php
// IIESPersonalInfoController.php L65-68
} catch (\Exception $e) {
    DB::rollBack();
    Log::error('Error saving IIES Personal Info', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Failed to save IIES Personal Info.'], 500);
    // No re-throw
}
```

```php
// ProjectController.php L688-694 — return value ignored
$this->iiesPersonalInfoController->store($request, $project->project_id);
$this->iiesFamilyWorkingMembersController->store($request, $project->project_id);
$this->iiesImmediateFamilyDetailsController->store($request, $project->project_id);
$this->iiesEducationBackgroundController->store($request, $project->project_id);
// ... continues
```

---

## 3. Commit / Rollback Guarantees

### 3.1 Outer Commit

- **File:** `app/Http/Controllers/Projects/ProjectController.php`  
- **Line:** L494 — `DB::commit()`  
- **Condition:** Reached only if no exception propagates from any controller in the try block (L556-522).

### 3.2 Outer Rollback

- **File:** `app/Http/Controllers/Projects/ProjectController.php`  
- **Lines:** L528-530 (ValidationException), L529-533 (generic Exception)  
- **Condition:** Triggered only when an exception propagates to ProjectController's catch.

### 3.3 Correctness Violation: No Guarantee of All-or-Nothing

> **Violation:** When IIESPersonalInfoController (or ImmediateFamilyDetails, EducationBackground, or Attachments) fails:
>
> 1. Its nested `DB::rollBack()` undoes only its own work.  
> 2. No exception propagates to ProjectController.  
> 3. ProjectController continues and eventually calls `DB::commit()` at L494.  
> 4. Result: project row, key information, and other IIES sections (FamilyWorkingMembers, FinancialSupport, Expenses) are committed, but the failing section is not.
>
> This is **partial persistence**, not atomicity.

---

## 4. Order of Logging vs Durability

### 4.1 Logging Before Commit

| Log Message | File:Line | Durability at Log Time |
|-------------|-----------|-------------------------|
| "General project details saved" | ProjectController L568 | **Uncommitted** |
| "KeyInformationController@store - Data saved successfully" | KeyInformationController L67 | **Uncommitted** |
| "Storing IIES Personal Info" | IIESPersonalInfoController L56 | **Uncommitted** |
| "IIES family working members saved successfully" | IIESFamilyWorkingMembersController L45 | **Uncommitted** |
| "Project and all related data saved successfully" | ProjectController L507 | **After commit** |

### 4.2 Correctness Violation: Misleading Pre-Commit Logs

> **Violation:** The log "General project details saved" (ProjectController L568) is emitted immediately after GeneralInfoController returns, i.e. before `DB::commit()` at L494. If any later controller propagates an exception, ProjectController will call `DB::rollBack()`, and the projects row will not exist. Logs will still indicate the project was saved.

**Evidence:**  
- `app/Http/Controllers/Projects/ProjectController.php` L567-568: Log immediately after GeneralInfoController::store  
- `app/Http/Controllers/Projects/ProjectController.php` L494: DB::commit (much later in execution)

---

## 5. Atomicity of the Create Flow

### 5.1 Is the IIES Create Flow Atomic?

**No.** The flow does not provide all-or-nothing semantics.

| Scenario | Outcome |
|----------|---------|
| IIESPersonalInfoController fails | Project + KeyInfo + FamilyMembers + ImmediateFamilyDetails (if ran) + EducationBackground (if ran) + FinancialSupport + Attachments (if ran) + Expenses committed; PersonalInfo not persisted |
| IIESImmediateFamilyDetailsController fails | Partial persistence: project + key info + personal info + family members + … committed; ImmediateFamilyDetails not persisted |
| IIESFamilyWorkingMembersController fails | Exception propagates → outer rollback → **no** project row |
| IIESExpensesController fails | Exception propagates → outer rollback → **no** project row |

### 5.2 Root Cause

Sub-controllers that use nested transactions and catch exceptions without re-throwing break atomicity. They fail in isolation while the outer transaction continues and commits.

---

## 6. Primary Structural Failure

### Summary

The IIES create flow uses a pattern where some sub-controllers:

1. Open nested transactions (savepoints)  
2. Catch all exceptions  
3. Roll back their own savepoint  
4. Return HTTP responses instead of re-throwing  

ProjectController neither checks these return values nor receives exceptions from these controllers. It proceeds with the rest of the flow and commits the outer transaction.

### Consequences

- **Partial persistence:** A subset of related records can be committed while others are not.  
- **Swallowed errors:** Failures in IIES Personal Info, Immediate Family Details, Education Background, or Attachments are logged but never propagated.  
- **Logging vs durability mismatch:** "General project details saved" and similar messages appear before commit; if a later propagating failure triggers rollback, the projects row will not exist despite the logs.

### File and Line References

| Location | Description |
|----------|-------------|
| `ProjectController.php` L554 | Outer `DB::beginTransaction()` |
| `ProjectController.php` L568 | Log "General project details saved" (pre-commit) |
| `ProjectController.php` L688-694 | IIES sub-controllers called; return values ignored |
| `ProjectController.php` L494 | `DB::commit()` |
| `ProjectController.php` L528-533 | Outer `DB::rollBack()` on exception |
| `IIESPersonalInfoController.php` L53-68 | Nested transaction; catch without re-throw |
| `IIESImmediateFamilyDetailsController.php` L56-73 | Same pattern |
| `EducationBackgroundController.php` L24-42 | Same pattern |
| `IIESAttachmentsController.php` L40-85 | Same pattern |

---

## 7. Why Phase 2 Is Not Implicated

### 7.1 Phase 2 Components in Scope

- **FormDataExtractor** — Used by `EducationBackgroundController::store` (L23)  
- **ProjectAttachmentHandler** — Used by `IIESAttachmentsController::store` (L50-55)

### 7.2 Why They Are Not the Cause

1. **FormDataExtractor** is only responsible for extracting request data. It does not manage transactions or catch exceptions. The structural issue is in `EducationBackgroundController`, which wraps its logic in a try/catch and returns JSON instead of re-throwing.

2. **ProjectAttachmentHandler** performs attachment handling. The structural issue is in `IIESAttachmentsController`, which uses nested transactions and catches `\Throwable` without re-throwing (L79-85).

3. **IIESPersonalInfoController** and **IIESImmediateFamilyDetailsController** do not use any Phase 2 components. They exhibit the same pattern: nested transaction + catch without re-throw.

### 7.3 Conclusion

The correctness violations come from **controller-level transaction and exception handling**, not from FormDataExtractor, ProjectAttachmentHandler, or other Phase 2 services. Phase 2 components are dependencies; they are not the source of the architectural failures.

---

## 8. Out of Scope Observations

*(Documented for reference; no changes proposed.)*

1. **Validation gap:** `iies_bname` (and other IIES personal info fields) are not validated by `StoreProjectRequest`, which is the only FormRequest used for the create flow. `StoreIIESPersonalInfoRequest` defines `required` rules but is never used during create.

2. **Return value handling:** ProjectController could check HTTP status or response structure from sub-controllers and re-throw or roll back on failure. Currently it ignores return values.

3. **Nested transactions:** IIES sub-controllers could omit their own `DB::beginTransaction()` and rely solely on the outer transaction, so that any failure triggers the outer rollback.

4. **Logging placement:** "General project details saved" and similar logs could be moved to after `DB::commit()` so they reflect committed state.

---

## Appendix: IIES Create Flow Call Order

```
ProjectController::store (L552)
├── DB::beginTransaction()                              [L554]
├── GeneralInfoController::store()                      [L567] — no transaction
├── Log "General project details saved"                 [L568]
├── KeyInformationController::store()                   [L592] — no transaction, re-throws
├── [IIES case block L686-695]
│   ├── IIESPersonalInfoController::store()             [L688] — nested TX, catch, no re-throw
│   ├── IIESFamilyWorkingMembersController::store()     [L689] — no transaction, propagates
│   ├── IIESImmediateFamilyDetailsController::store()   [L690] — nested TX, catch, no re-throw
│   ├── EducationBackgroundController::store()          [L691] — nested TX, catch, no re-throw
│   ├── FinancialSupportController::store()             [L692] — no transaction, propagates
│   ├── IIESAttachmentsController::store()              [L693] — nested TX, catch, no re-throw
│   └── IIESExpensesController::store()                 [L694] — no transaction, propagates
├── DB::commit()                                        [L494]
└── Log "Project and all related data saved"            [L507]
```
