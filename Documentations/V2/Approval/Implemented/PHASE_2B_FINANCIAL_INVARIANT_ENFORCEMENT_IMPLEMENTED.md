# Phase 2B – Financial Invariant Enforcement

## 1. Domain Service Created

**File:** `app/Domain/Finance/FinancialInvariantService.php`  
**Namespace:** `App\Domain\Finance`

Implements `validateForApproval(Project $project, array $data): void` which enforces financial invariants before approval. Throws `DomainException` on violation.

---

## 2. Invariants Enforced

| Invariant | Rule | Exception Message |
|-----------|------|-------------------|
| Opening balance > 0 | `$openingBalance <= 0` | "Approval blocked: Opening balance must be greater than zero." |
| Amount sanctioned > 0 | `$amountSanctioned <= 0` | "Approval blocked: Amount sanctioned must be greater than zero." |
| Balance/sanctioned match | `$openingBalance !== $amountSanctioned` | "Approval blocked: Opening balance and sanctioned amount mismatch." |

Values are taken from `$data` (approval payload) with fallback to `$project` when keys are absent.

---

## 3. Approval Flow Changes

**File:** `app/Http/Controllers/CoordinatorController.php`

**Location:** Before `ProjectStatusService::approve()` call

**Change:**
```php
try {
    \App\Domain\Finance\FinancialInvariantService::validateForApproval($project, $approvalData);
} catch (\DomainException $e) {
    return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
}
```

- Validation runs after `$approvalData` is built, before the atomic approval.
- On failure: redirect back with error message and input preserved.
- `ProjectStatusService` is unchanged; atomic approval logic remains as in Phase 2A.

---

## 4. Tests Updated

| Test | Change |
|------|--------|
| `test_zero_opening_balance_currently_allows_approval_flow` | Renamed to `test_zero_opening_balance_blocks_approval` |
| Expectations | Now expects redirect back, session errors, status remains `forwarded_to_coordinator` |
| `test_current_behavior_allows_zero_opening_balance_approval` | Updated docblock to state Phase 2B blocks zero-balance approval |

**New assertions in `test_zero_opening_balance_blocks_approval`:**
- `assertStatus(302)`, `assertRedirect()`
- `assertSessionHasErrors('error')`
- Error message contains "Opening balance must be greater than zero"
- `$project->status` remains `forwarded_to_coordinator`

---

## 5. Behavior Change Summary

| Scenario | Before (Phase 2A) | After (Phase 2B) |
|----------|-------------------|------------------|
| Valid project (opening_balance > 0, amount_sanctioned > 0, matching) | 302, success | 302, success (unchanged) |
| Zero opening balance | 302, success | 302, redirect back with error, no approval |
| Zero amount sanctioned | 302, success | 302, redirect back with error, no approval |
| Mismatch (opening ≠ sanctioned) | 302, success | 302, redirect back with error, no approval |

---

## 6. Production Safety Confirmation

- Wave 6D protections unchanged
- Atomic approval logic unchanged
- Validation confined to domain service; no duplicate logic in controller
- `DomainException` handled in controller with redirect and error message
- All 25 tests pass
- Valid approval flow still succeeds (`test_coordinator_can_approve_valid_project_flow`)

---

## 7. Next Steps

Phase 2B is complete. Ready for Phase 3 as defined in the approval plan. No further changes were made beyond financial invariant enforcement.
