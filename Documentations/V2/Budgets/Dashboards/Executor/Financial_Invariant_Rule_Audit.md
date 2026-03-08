# Financial Invariant Rule Audit

**Date:** 2026-03-04  
**Scope:** Financial invariant rules across FinancialInvariantService, ProjectFinancialResolver, BudgetValidationService, approval workflow, and dashboard aggregation.  
**Objective:** Audit invariant logic against the documented financial architecture.  
**Method:** Static code analysis. **No application code was modified.**

---

## 1️⃣ List of Invariant Rules in Code

| rule_id | invariant_rule | enforcement | file_location |
|---------|----------------|-------------|---------------|
| INV-1 | `opening_balance > 0` | Block (throws DomainException) | `app/Domain/Finance/FinancialInvariantService.php` line 23 |
| INV-2 | `amount_sanctioned > 0` | Block (throws DomainException) | `app/Domain/Finance/FinancialInvariantService.php` line 29 |
| INV-3 | `opening_balance === amount_sanctioned` | Block (throws DomainException) | `app/Domain/Finance/FinancialInvariantService.php` line 35 |
| INV-4 | Non-approved: `amount_sanctioned == 0` (resolved) | Log warning | `app/Domain/Budget/ProjectFinancialResolver.php` line 133 |
| INV-5 | Non-approved: DB `amount_sanctioned` must be 0 | Log critical | `app/Domain/Budget/ProjectFinancialResolver.php` line 125 |
| INV-6 | Approved: `amount_sanctioned > 0` | Log warning | `app/Domain/Budget/ProjectFinancialResolver.php` line 143 |
| INV-7 | Approved: `opening_balance == overall_project_budget` | Log warning | `app/Domain/Budget/ProjectFinancialResolver.php` line 151 |
| BVS-1 | `remaining_balance >= 0` | Error in validation | `app/Services/BudgetValidationService.php` line 142 |
| BVS-2 | `amount_sanctioned >= 0` | Error in validation | `app/Services/BudgetValidationService.php` line 154 |
| BVS-3 | `opening_balance >= 0` | Error in validation | `app/Services/BudgetValidationService.php` line 164 |
| BVS-4 | `opening_balance == amount_sanctioned + amount_forwarded + local_contribution` | Warning in validation | `app/Services/BudgetValidationService.php` line 189 |

---

## 2️⃣ Source Locations

| Component | File | Purpose |
|-----------|------|---------|
| **FinancialInvariantService** | `app/Domain/Finance/FinancialInvariantService.php` | Pre-approval validation; blocks approval if invariants fail. Called from `CoordinatorController::approveProject()` before `ProjectStatusService::approve()`. |
| **ProjectFinancialResolver** | `app/Domain/Budget/ProjectFinancialResolver.php` | `assertFinancialInvariants()` — log-only assertions after resolve. Runs on every `resolve()` call. |
| **BudgetValidationService** | `app/Services/BudgetValidationService.php` | Validation for budget display; `checkTotalsMatch()` expects `opening_balance == sanctioned + forwarded + local`. |
| **ProjectStatusService** | `app/Services/ProjectStatusService.php` | `approve()` populates `amount_sanctioned`, `opening_balance`, `commencement_month_year` from approval data. `applyFinancialResetOnRevert()` sets sanctioned=0, opening=forwarded+local on revert. |

### Call flow: Coordinator approval

```
CoordinatorController::approveProject()
  → BudgetSyncService::syncBeforeApproval()
  → ProjectFinancialResolver::resolve() (pre-approval: sanctioned=0, opening=forwarded+local)
  → Compute approvalData (amount_sanctioned, opening_balance from resolver/combined)
  → FinancialInvariantService::validateForApproval($project, $approvalData)  ← INV-1, INV-2, INV-3
  → ProjectStatusService::approve($project, $coordinator, $approvalData)
```

---

## 3️⃣ Architecture Comparison

### Documented financial architecture

- **overall_project_budget** = requested budget (total project/phase budget).
- **amount_sanctioned** = approved amount (the amount newly sanctioned by coordinator).
- **opening_balance** = sanctioned + forwarded + local contribution (total available at start).

So:

`opening_balance = amount_sanctioned + amount_forwarded + local_contribution`

When `amount_forwarded = 0` and `local_contribution = 0`:

`opening_balance = amount_sanctioned`

When forwarded or local exist:

`opening_balance > amount_sanctioned`

### Comparison with implemented rules

| Rule | Implemented | Architecture | Match? |
|------|-------------|--------------|--------|
| INV-3: `opening_balance === amount_sanctioned` | Yes (strict equality) | Correct only when forwarded+local=0; wrong when they exist | **No** |
| INV-7: `opening_balance == overall_project_budget` | Yes (for approved) | `opening = sanctioned + forwarded + local`; equals `overall` only when `overall` is defined as that sum. For phase-based types, `overall` = phase total; opening may equal it when no adjustment. For projects with forwarded (e.g. DP-0024), opening=1040000, overall=1040000 — equal. But the *semantic* rule should be opening = sanctioned + forwarded + local, not opening = overall. | **Partial** |
| BVS-4: `opening == sanctioned + forwarded + local` | Yes | Matches architecture | **Yes** |
| INV-1, INV-2: opening > 0, sanctioned > 0 | Yes | Reasonable for approval | **Yes** |
| INV-4, INV-5, INV-6 | Yes | Non-approved: sanctioned 0; approved: sanctioned > 0 | **Yes** |

---

## 4️⃣ Incorrect Invariants Detected

### INCORRECT: INV-3 — `opening_balance === amount_sanctioned`

**Location:** `FinancialInvariantService::validateForApproval()` line 35.

**Code:**

```php
if ($openingBalance !== $amountSanctioned) {
    throw new \DomainException(
        'Approval blocked: Opening balance and sanctioned amount mismatch.'
    );
}
```

**Problem:** Enforces strict equality. Architecture defines:

`opening_balance = amount_sanctioned + amount_forwarded + local_contribution`

When `amount_forwarded > 0` or `local_contribution > 0`, `opening_balance` must be greater than `amount_sanctioned`. Example: DP-0024 (sanctioned=775000, forwarded=265000, local=0, opening=1040000). Invariant would block such approvals.

**Impact:** Projects with forwarded or local contributions cannot pass approval validation if the coordinator correctly sets sanctioned ≠ opening.

---

### QUESTIONABLE: INV-7 — `opening_balance == overall_project_budget`

**Location:** `ProjectFinancialResolver::assertFinancialInvariants()` line 151.

**Code:**

```php
if (abs($opening - $overall) > $tolerance) {
    Log::warning('Financial invariant violation: approved project must have opening_balance == overall_project_budget', [
        'project_id' => $projectId,
        'opening_balance' => $opening,
        'overall_project_budget' => $overall,
        'invariant' => 'opening_balance == overall_project_budget',
    ]);
}
```

**Problem:** Treats `opening_balance == overall_project_budget` as the canonical rule. Architecture says `opening_balance = sanctioned + forwarded + local`. For phase-based projects, `overall` comes from phase budget items; it can match `opening` when the phase total equals the available funds. For individual types, `overall` and `opening` semantics differ. The rule is a proxy that happens to hold in some cases but is not the primary definition.

**Impact:** Log-only. Projects like DP-0041 (overall=1681000, opening=630000) would trigger this warning because they diverge by design (Individual type with different mapping). The assertion is too strict for all project types.

---

### ALIGNED: BVS-4 — `opening == sanctioned + forwarded + local`

**Location:** `BudgetValidationService::checkTotalsMatch()` line 186–199.

**Code:**

```php
$calculatedOpening = $budgetData['amount_sanctioned'] + $budgetData['amount_forwarded'] + $budgetData['local_contribution'];
if (abs($budgetData['opening_balance'] - $calculatedOpening) > $tolerance) {
    $warnings[] = [...];
}
```

**Assessment:** Matches architecture. Correct invariant.

---

## 5️⃣ Impact on Dashboards and Resolver

### Resolver

- **Resolver logic:** For approved projects, returns `opening_balance = (float) ($project->opening_balance ?? 0)` (DB value). Does not infer from sanctioned+forwarded+local when DB is null.
- **assertFinancialInvariants:** Logs only; does not change resolver output.
- **Result:** Resolver output depends on DB. Wrong or null DB values propagate to dashboards.

### Dashboards

- **Aggregation:** All dashboard budget totals use `resolver->resolve($project)['opening_balance']`.
- **Assumption:** `opening_balance` represents “total budget / available budget” for the project.
- **Impact:** If INV-3 blocks valid approvals (forwarded+local cases), those projects never get correct sanctioned/opening in DB. If they are approved via another path with correct data, dashboards show correct values. If INV-3 forces `opening === sanctioned` at approval, projects with forwarded amounts would need to be approved with `opening = sanctioned` (wrong) to pass, leading to incorrect stored values and dashboard totals.

### Approval pipeline

- **CoordinatorController:** Pre-approval, uses `amountSanctioned = (resolver amount_sanctioned) ?: combinedContribution` and `openingBalance = (resolver opening_balance) ?: combinedContribution`. For pre-approval (resolver returns 0 for sanctioned, combined for opening), both become `combined`. So at approval time, `opening === sanctioned` is satisfied for projects with forwarded+local by construction.
- **Design note:** Current approval flow sets sanctioned = opening = combined for pre-approval projects. It does not use `sanctioned = overall - combined` and `opening = sanctioned + combined`. Per PHASE_2_VERIFICATION, the intended formula is `amount_sanctioned = overall - combined`, `opening_balance = amount_sanctioned + combined`. If that design is ever applied, INV-3 would block it for projects with forwarded+local.

---

## 6️⃣ Recommended Invariant Corrections

(Recommendations only; no code changes were made.)

### 1. Replace INV-3 in FinancialInvariantService

**Current:** `opening_balance === amount_sanctioned`

**Recommended:** Align with architecture:

```php
$expectedOpening = $amountSanctioned + ($data['amount_forwarded'] ?? $project->amount_forwarded ?? 0) + ($data['local_contribution'] ?? $project->local_contribution ?? 0);
if (abs($openingBalance - $expectedOpening) > 0.01) {
    throw new \DomainException('Approval blocked: Opening balance must equal sanctioned + forwarded + local contribution.');
}
```

This allows `opening > sanctioned` when forwarded or local exist.

---

### 2. Relax or specialize INV-7 in ProjectFinancialResolver

**Current:** `opening_balance == overall_project_budget` for all approved projects.

**Options:**

- **A.** Remove this assertion and rely on BVS-4-style checks where needed.
- **B.** Restrict to phase-based types only, where `overall` is the phase total and the relationship is more defined.
- **C.** Change to: `opening_balance = sanctioned + forwarded + local` (log when this fails) instead of comparing to `overall`.

---

### 3. Unify invariant definitions

Document a single canonical rule and use it consistently:

- `opening_balance = amount_sanctioned + amount_forwarded + local_contribution`

Use this in:

- FinancialInvariantService (approval blocking)
- ProjectFinancialResolver (optional assertion)
- BudgetValidationService (already correct)

---

### 4. Approval flow vs invariants

If the approval flow is updated to use:

- `amount_sanctioned = overall - (forwarded + local)`
- `opening_balance = amount_sanctioned + forwarded + local`

then FinancialInvariantService must be updated as in recommendation 1, or INV-3 will block valid approvals.

---

*Audit completed without modifying any application code. All findings are from static code analysis.*
