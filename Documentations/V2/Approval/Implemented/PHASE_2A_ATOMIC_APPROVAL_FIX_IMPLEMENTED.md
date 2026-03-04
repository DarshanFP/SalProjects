# Phase 2A – Atomic Approval Fix

## 1. Root Cause Recap

**Production Bug:** Coordinator approval returned 403 ("Project is in FINAL status and cannot be modified").

**Root Cause:** The approval flow performed two `save()` calls:

1. **First save:** `ProjectStatusService::approve()` changed `status` to `approved_by_coordinator` (FINAL status).
2. **Second save:** Controller updated `amount_sanctioned` and `opening_balance`, then called `$project->save()`.

The Project model's Wave 6D `updating` observer checks `getOriginal('status')`. After the first save, the model’s original status was `approved_by_coordinator`. The second save triggered the observer and aborted with 403.

---

## 2. Before vs After Flow

### Before (Broken)

```
Controller                          Service
────────────                        ───────
1. Sync budgets
2. Set commencement on project
3. Call approve(project, user)  ──► 4. Set status
                                    5. save()  ← 1st save
6. Get financials from resolver
7. Validate combined <= overall
8. Set amount_sanctioned, opening_balance
9. save()  ← 2nd save → 403 (Wave 6D)
```

### After (Fixed)

```
Controller                          Service
────────────                        ───────
1. Sync budgets
2. Get financials from resolver
3. Validate combined <= overall
4. Build approvalData (commencement + budget fields)
5. Call approve(project, user, data) ──► 6. Set status, commencement, amount_sanctioned, opening_balance
                                         7. save()  ← single atomic save
8. Notify, invalidate cache
9. Redirect
```

---

## 3. Refactor Details

- **Single save:** All approval fields (status, commencement_month, commencement_year, commencement_month_year, amount_sanctioned, opening_balance) are set on the project before one `$project->save()`.
- **No saveQuietly:** Wave 6D protections remain; model events run as normal.
- **Resolver call order:** Financials are resolved before approval (project is non-approved), so `amount_sanctioned` from resolver is 0. Controller uses `combinedContribution` for both `amount_sanctioned` and `opening_balance` when resolver returns 0.
- **Test data:** Test project now sets `amount_forwarded` and `local_contribution` so the resolver returns the correct combined value for pre-approval.

---

## 4. Controller Changes

**File:** `app/Http/Controllers/CoordinatorController.php`

- Moved financial resolution and budget validation **before** the approval call.
- Build `$approvalData` with: `commencement_month`, `commencement_year`, `commencement_month_year`, `amount_sanctioned`, `opening_balance`.
- Removed second `$project->save()` and post-approval mutation.
- Call `ProjectStatusService::approve($project, $coordinator, $approvalData)`.
- Retained logging, notifications, cache invalidation, and redirect.

---

## 5. Service Changes

**File:** `app/Services/ProjectStatusService.php`

- **New signature:** `approve(Project $project, User $user, array $data = [])`
- **Optional keys in `$data`:** `commencement_month`, `commencement_year`, `commencement_month_year`, `amount_sanctioned`, `opening_balance`
- **Logic:** Set all provided fields on the project before `$project->save()`; perform one save; run status-change logging and info logging afterward.
- **Compatibility:** Callers that omit `$data` (e.g. General user paths) still work; only provided keys are applied.

---

## 6. Test Updates

**File:** `tests/Feature/ProjectApprovalWorkflowTest.php`

### `test_coordinator_can_approve_valid_project_flow`

- **Before:** Expected 403 and "Project is in FINAL status...".
- **After:** Asserts 302, `assertRedirect()`, `assertSessionHas('success')`. Asserts `status` = `approved_by_coordinator`, `amount_sanctioned` = 100000, `opening_balance` = 100000, `commencement_month` = 3, `commencement_year` = 2026.
- Project creation: Added `amount_forwarded` = 100000 and `local_contribution` = 0 for resolver correctness.

### `test_zero_opening_balance_currently_allows_approval_flow`

- **Before:** Expected 403.
- **After:** Asserts 302, redirect, and success. Asserts status = `approved_by_coordinator`. Zero-balance approval remains allowed (Phase 2B will enforce the financial invariant).

---

## 7. Production Bug Fixed Confirmation

- No 403 on coordinator approval.
- Status updates to `approved_by_coordinator`.
- Budget fields (`amount_sanctioned`, `opening_balance`) persist correctly in one save.
- Notifications and redirect work as before.
- All 25 tests pass.

---

## 8. Ready for Phase 2B

Phase 2A completes the atomic approval refactor. The codebase is ready for Phase 2B to add financial invariant enforcement (e.g. block approval when opening balance is zero). No further changes were made to Wave 6D or the approval logic beyond this fix.
