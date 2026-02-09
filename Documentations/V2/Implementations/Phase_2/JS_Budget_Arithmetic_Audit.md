# JS Budget Arithmetic Audit

**Audit Date:** February 2025  
**Scope:** JavaScript and Blade inline scripts related to Budget calculations  
**Method:** Read-only inspection — no code modified

---

## 1️⃣ Row-Level Calculations

### 1.1 Project Budget Row (rate_quantity × rate_multiplier × rate_duration → this_phase)

| File | Function | Line Range | Exact Formula | Fields Used | Duplicated Elsewhere? |
|------|----------|------------|---------------|-------------|-----------------------|
| `resources/views/projects/partials/scripts.blade.php` | `calculateBudgetRowTotals` | 58–88 | `thisPhase = rateQuantity * rateMultiplier * rateDuration` | rate_quantity, rate_multiplier, rate_duration, this_phase | Yes — scripts-edit.blade.php |
| `resources/views/projects/partials/scripts-edit.blade.php` | `calculateBudgetRowTotals` | 954–984 | `thisPhase = rateQuantity * rateMultiplier * rateDuration` | rate_quantity, rate_multiplier, rate_duration, this_phase | Yes — scripts.blade.php |

**Details:**
- Both use `parseFloat(value) || 0` (or `|| 1` for multiplier/duration).
- Write result to `thisPhaseInput.value = thisPhase.toFixed(2)`.
- Called from `oninput="calculateBudgetRowTotals(this)"` on rate_quantity, rate_multiplier, rate_duration inputs.

### 1.2 Report Statement Row (amount_sanctioned, expenses → total_amount, total_expenses, balance_amount)

| File | Function | Line Range | Exact Formula | Fields Used | Duplicated Elsewhere? |
|------|----------|------------|---------------|-------------|-----------------------|
| `resources/views/reports/monthly/partials/edit/statements_of_account/individual_ongoing_education.blade.php` | `calculateRowTotals` | 244–264 | `totalAmount = amountSanctioned`<br>`totalExpenses = expensesLastMonth + expensesThisMonth`<br>`balanceAmount = totalAmount - totalExpenses` | amount_sanctioned, expenses_last_month, expenses_this_month | Yes — individual_health, individual_education, individual_livelihood |
| `resources/views/reports/monthly/partials/edit/statements_of_account/individual_health.blade.php` | `calculateRowTotals` | 244–264 | Same | Same | Yes |
| `resources/views/reports/monthly/partials/edit/statements_of_account/individual_education.blade.php` | `calculateRowTotals` | 244–264 | Same | Same | Yes |
| `resources/views/reports/monthly/partials/edit/statements_of_account/individual_livelihood.blade.php` | `calculateRowTotals` | 244–264 | Same | Same | Yes |

**Note:** Report `calculateRowTotals` operates on account statement rows (amount_sanctioned, expenses). Budget rows use `amount_sanctioned` pre-filled from `$budget->this_phase` (Blade). No direct q×m×d in report forms.

---

## 2️⃣ Phase-Level Aggregations

### 2.1 scripts.blade.php — `calculateBudgetTotals(phaseCard)`

| Where | Line Range | Summation | Recalculates or Sums Persisted? | next_phase Included? | rate_increase Included? |
|-------|------------|-----------|----------------------------------|----------------------|-------------------------|
| `scripts.blade.php` | 101–126 | `totalRateQuantity += parseFloat(...)`<br>`totalRateMultiplier += parseFloat(...)`<br>`totalRateDuration += parseFloat(...)`<br>`totalRateIncrease += parseFloat(...)`<br>`totalThisPhase += parseFloat(...)`<br>`totalNextPhase += parseFloat(...)` | Sums DOM input values (this_phase written by calculateBudgetRowTotals) | Yes | Yes |

- Writes to `.total_rate_quantity`, `.total_rate_multiplier`, `.total_rate_duration`, `.total_rate_increase`, `.total_this_phase`, `.total_next_phase`.
- **Requires `phaseCard`:** Current form has no `.phase-card`; only `#phases-container` with a single table. **Effectively dead for active create flow.**

### 2.2 scripts-edit.blade.php — `calculateBudgetTotals()`

| Where | Line Range | Summation | Recalculates or Sums Persisted? | next_phase Included? | rate_increase Included? |
|-------|------------|-----------|----------------------------------|----------------------|-------------------------|
| `scripts-edit.blade.php` | 997–1031 | `totalRateQuantity += parseFloat(...)`<br>`totalRateMultiplier += parseFloat(...)`<br>`totalRateDuration += parseFloat(...)`<br>`totalThisPhase += parseFloat(...)` | Sums DOM input values | No | No |

- No rate_increase or next_phase in Edit form.
- Actively used; updates `.total_rate_quantity`, `.total_rate_multiplier`, `.total_rate_duration`, `.total_this_phase`.

### 2.3 calculateTotalAmountSanctioned()

| File | Line Range | Logic |
|------|------------|-------|
| `scripts.blade.php` | 131–196 | `totalAmount += thisPhaseValue` per row; writes to `total_amount_sanctioned`, `overall_project_budget`, `overall_project_budget_display` |
| `scripts-edit.blade.php` | 1034–1071 | Same |

- Sums persisted DOM values of `this_phase`; does not recalculation from q×m×d.

---

## 3️⃣ Project-Level Totals

| Location | Method | DOM Summation vs Recalculation | Inconsistency? |
|----------|--------|---------------------------------|----------------|
| `scripts.blade.php` | `calculateTotalAmountSanctioned` | Sums `this_phase` from rows | N/A |
| `scripts-edit.blade.php` | `calculateTotalAmountSanctioned` | Sums `this_phase` from rows | N/A |
| `scripts.blade.php` | `calculateBudgetFields` | `amountSanctioned = overallBudget - combined`<br>`openingBalance = amountSanctioned + combined` | Uses overall_project_budget (set by sum of this_phase) |
| `scripts-edit.blade.php` | `calculateBudgetFields` | Same | Same |

**Blade (PHP) — server-side totals:**
- `budget-pdf.blade.php`, `Show/budget.blade.php`, `OLdshow/budget.blade.php`, `not working show/budget.blade.php`: Use `$project->budgets->sum('this_phase')`, `$budgets->sum('rate_quantity')`, etc. (PHP, not JS).

---

## 4️⃣ Remaining Balance Logic

| File | Function | Line Range | Formula | Matches Backend? |
|------|----------|------------|---------|------------------|
| `individual_ongoing_education.blade.php` | `calculateRowTotals` | 251 | `balanceAmount = totalAmount - totalExpenses` | Yes — `DerivedCalculationService::calculateRemainingBalance` |
| `individual_ongoing_education.blade.php` | `updateBudgetSummaryCards` | 414 | `remainingBalance = totalBudget - totalExpenses` | Yes |
| `individual_health.blade.php` | `calculateRowTotals` | 251 | `balanceAmount = totalAmount - totalExpenses` | Yes |
| `individual_health.blade.php` | `updateBudgetSummaryCards` | 430 | `remainingBalance = totalBudget - totalExpenses` | Yes |
| `individual_education.blade.php` | Same pattern | Same | Same | Yes |
| `individual_livelihood.blade.php` | Same pattern | Same | Same | Yes |
| `scripts.blade.php` | `calculateBudgetFields` | 243 | `amountSanctioned = overallBudget - combined` | Yes — Amount Sanctioned = Overall - (Forwarded + Local) |
| `scripts-edit.blade.php` | `calculateBudgetFields` | 1113 | Same | Same |

---

## 5️⃣ Legacy Multi-Phase Remnants

| Item | Location | Status |
|------|----------|--------|
| `phaseCard` | `scripts.blade.php` `calculateBudgetTotals(phaseCard)` | Requires `.phase-card`; active create form has none. **Dead for current flow.** |
| `next_phase` | `scripts.blade.php` lines 116, 124 | Summed in `calculateBudgetTotals`; no `next_phase` input in create/edit form. **Dead.** |
| `rate_increase` | `scripts.blade.php` lines 114, 122 | Summed in `calculateBudgetTotals`; no `rate_increase` input in create/edit form. **Dead.** |
| `.total_rate_increase`, `.total_next_phase` | `scripts.blade.php` | Written to; no corresponding footer inputs in create/edit. **Dead.** |
| `OLdshow/budget.blade.php`, `not working show/budget.blade.php` | Blade | Display `rate_increase`, `next_phase` from DB; **legacy/old views.** |
| `NPD/budget.blade.php` | Blade | Uses `calculateBudgetRowTotals`; no rate_increase/next_phase. **Active.** |

---

## 6️⃣ Duplication Matrix

| Formula | Files | Functions | Duplicate Count |
|---------|-------|-----------|-----------------|
| `thisPhase = rateQuantity * rateMultiplier * rateDuration` | scripts.blade.php, scripts-edit.blade.php | calculateBudgetRowTotals | 2 |
| `totalAmount += thisPhaseValue` (sum this_phase) | scripts.blade.php, scripts-edit.blade.php | calculateTotalAmountSanctioned | 2 |
| `totalRateQuantity += parseFloat(...)` (column sum) | scripts.blade.php, scripts-edit.blade.php | calculateBudgetTotals, calculateTotalAmountSanctioned | 2 |
| `balanceAmount = totalAmount - totalExpenses` | individual_ongoing_education, individual_health, individual_education, individual_livelihood | calculateRowTotals | 4 |
| `remainingBalance = totalBudget - totalExpenses` | individual_ongoing_education, individual_health, individual_education, individual_livelihood | updateBudgetSummaryCards | 4 |
| `amountSanctioned = overallBudget - combined` | scripts.blade.php, scripts-edit.blade.php | calculateBudgetFields | 2 |
| `totalExpenses = expensesLastMonth + expensesThisMonth` | 4 report forms | calculateRowTotals | 4 |
| `totalAmountTotal += parseFloat(...)` (footer totals) | 4 report forms | calculateTotal | 4 |

---

## 7️⃣ Risk Assessment

| Formula | Canonical? | UI Preview Only? | Drift Risk? | Legacy Involvement? |
|---------|------------|------------------|-------------|---------------------|
| `thisPhase = rateQuantity * rateMultiplier * rateDuration` | Yes — matches backend `DerivedCalculationService::calculateRowTotal` | Yes — UX; backend trusts submitted `this_phase` | Low — backend clamps and persists | No |
| `totalAmount = sum(this_phase)` | Yes — matches backend `calculateProjectTotal` | Yes — UX; backend recomputes | Low | No |
| `balanceAmount = totalAmount - totalExpenses` | Yes — matches backend `calculateRemainingBalance` | Yes — report UI | Low | No |
| `remainingBalance = totalBudget - totalExpenses` | Yes | Yes — report summary cards | Low | No |
| `amountSanctioned = overallBudget - combined` | Yes | Yes — Budget Summary | Low | No |
| `calculateBudgetTotals(phaseCard)` with rate_increase, next_phase | No — legacy multi-phase | N/A — dead in current flow | N/A | Yes — uses rate_increase, next_phase |
| `totalExpenses = expensesLastMonth + expensesThisMonth` | Yes — report account rows | Yes | Low | No |

---

## Files Audited

| Category | Paths |
|----------|------|
| **Project budget scripts** | `resources/views/projects/partials/scripts.blade.php`, `scripts-edit.blade.php` |
| **Project budget partials** | `resources/views/projects/partials/budget.blade.php`, `Edit/budget.blade.php`, `NPD/budget.blade.php` |
| **Report statement forms** | `resources/views/reports/monthly/partials/edit/statements_of_account/individual_*.blade.php`, `development_projects.blade.php` |
| **Report forms** | `resources/views/reports/monthly/developmentProject/reportform.blade.php`, `quarterly/developmentProject/reportform.blade.php` |
| **Show/export views** | `resources/views/projects/partials/Show/budget.blade.php`, `exports/budget-pdf.blade.php`, `OLdshow/budget.blade.php`, `not working show/budget.blade.php` |
| **Standalone JS** | `public/js/*.js`, `resources/js/*.js` — no budget row arithmetic found |

---

## Summary

- **Row formula:** `rateQuantity * rateMultiplier * rateDuration` duplicated in 2 files; matches backend.
- **Phase/project totals:** Sum of `this_phase` in DOM; matches backend.
- **Remaining balance:** `total - expenses` in report forms and budget summary; matches backend.
- **Legacy:** `calculateBudgetTotals(phaseCard)` with rate_increase/next_phase is dead for active create/edit; no `.phase-card` in current form.

---

Audit Complete — No Code Modified
