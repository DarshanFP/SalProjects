# Budget Section (Create & Edit) – Review and Issues

**Scope:** Budget partial used for **create** (`resources/views/projects/partials/budget.blade.php`) and **edit** (`resources/views/projects/partials/Edit/budget.blade.php`) with the following structure.

**Expected structure (partial f – Budget):**

| No. | Particular | Costs | Rate Multiplier | Rate Duration | This Phase (Auto) | Action |
|-----|------------|-------|----------------|----------------|-------------------|--------|
| 1   | …          | …     | 1              | 1              | 0.00              | Remove |
| **Total** | | 0.00 | 1.00 | 1.00 | 0.00 | |

- **Overall Project Budget: Rs.** (Auto-calculated from budget items)  
- **Amount Forwarded (Existing Funds): Rs.** (Optional)  
- **Local Contribution: Rs.** (Optional)  
- **Amount Sanctioned (To Request): Rs.** = Overall Budget − (Amount Forwarded + Local Contribution)  
- **Opening Balance: Rs.** = Amount Sanctioned + (Amount Forwarded + Local Contribution)  

---

## 1. Critical: Create form – Local Contribution not used in calculation (scripts.blade.php)

**Location:** `resources/views/projects/partials/scripts.blade.php` – `calculateBudgetFields()`.

**Issue:** The function uses `localContributionField` but never declares it. It only declares:

- `overallBudgetField`
- `overallBudgetDisplayField`
- `amountForwardedField`
- `amountSanctionedField`
- `openingBalanceField`

So when the code does:

```js
const localContribution = parseFloat(localContributionField?.value) || 0;
```

`localContributionField` is **undefined**. As a result, on **create**:

- Local Contribution is always treated as **0** in the formula.
- Amount Sanctioned and Opening Balance are wrong whenever the user enters a non-zero Local Contribution.

**Fix:** Inside `calculateBudgetFields()`, add:

```js
const localContributionField = document.getElementById('local_contribution');
```

and keep using `localContributionField` for reading and (in the validation block) for correcting the value.

**Edit form:** `resources/views/projects/partials/scripts-edit.blade.php` correctly defines `localContributionField` in `calculateBudgetFields()`, so **edit** is not affected.

---

## 2. Duplicate `value` attribute (Create partial)

**Location:** `resources/views/projects/partials/budget.blade.php` – first data row (e.g. lines 31–34).

**Issue:** For `rate_multiplier` and `rate_duration` the same `<input>` has two `value` attributes, e.g.:

```html
value="1" oninput="..." value="{{ old('phases.0.budget.0.rate_multiplier', 1) }}"
```

The second `value` overwrites the first. If `old()` is empty (e.g. first load), the effective value can be empty string instead of default `1`, so the field may render blank instead of 1.

**Fix:** Use a single `value` attribute, e.g.:

```html
value="{{ old('phases.0.budget.0.rate_multiplier', 1) }}"
```

Same for `rate_duration`.

---

## 3. Duplicate `value` attribute (Edit partial – empty state)

**Location:** `resources/views/projects/partials/Edit/budget.blade.php` – single empty row when there are no budget items (e.g. lines 45–46).

**Issue:** Same as above: `rate_multiplier` and `rate_duration` inputs have both `value="1"` and `value="{{ old(...) }}"`. The second overwrites the first; when there is no old value, the default 1 is lost.

**Fix:** Use a single `value` with fallback, e.g.:

```html
value="{{ old('phases.0.budget.0.rate_multiplier', 1) }}"
value="{{ old('phases.0.budget.0.rate_duration', 1) }}"
```

---

## 4. Amount Sanctioned and Opening Balance not submitted

**Location:** Both budget partials (create and edit).

**Current behaviour:**  
- **Amount Sanctioned (To Request)** and **Opening Balance** are shown in readonly inputs: `amount_sanctioned_preview` and `opening_balance_preview`.  
- These fields are **not** submitted as `amount_sanctioned` / `opening_balance` in the request.  
- Backend persists **overall_project_budget**, **amount_forwarded**, **local_contribution** (e.g. via GeneralInfoController / BudgetController).  
- **amount_sanctioned** and **opening_balance** on the project are set by **BudgetSyncService** / **ProjectFundFieldsResolver** (e.g. on budget save or before approval), not from these preview fields.

**Assessment:** This is **by design**. The form correctly shows the derived values; persistence is handled by sync/resolver. No code bug, but it should be documented so that:

- Displayed “Amount Sanctioned” and “Opening Balance” can differ from DB until sync runs.
- Any new code that expects these two values to come from the form should instead rely on the resolver/sync.

---

## 5. Remove row – no minimum row check

**Location:** `removeBudgetRow()` in both `scripts.blade.php` and `scripts-edit.blade.php`.

**Issue:** The user can remove every budget row. Submitting the form with zero rows can lead to:

- Overall Project Budget = 0.
- Possible validation or business-rule issues if “at least one budget item” is required.

**Recommendation:** Either:

- Disable “Remove” when only one row remains, or  
- Validate on the server that at least one budget row exists and return a clear error.

---

## 6. Create form – no explicit listener for Local Contribution in DOMContentLoaded

**Location:** `resources/views/projects/partials/scripts.blade.php` – `DOMContentLoaded` block.

**Current behaviour:** Only `amount_forwarded` gets an `addEventListener('input', calculateBudgetFields)`. The **Local Contribution** input in the budget partial already has `oninput="calculateBudgetFields()"`, so typing there does trigger recalculation.

**Issue:** The real problem is that **calculateBudgetFields** does not read Local Contribution on create (see Issue 1). After fixing Issue 1, the existing `oninput` on the Local Contribution field is enough; adding an extra listener in DOMContentLoaded is optional and would only ensure consistency with how Amount Forwarded is handled.

---

## 7. Column label “Costs” vs stored field

**Location:** Table header in both budget partials.

**Current behaviour:** The third column is labeled **“Costs”** and is bound to `rate_quantity` (Costs input). The footer “Total” row shows the sum of these values.

**Note:** If “Costs” is intended to mean “unit cost” or “rate” rather than “quantity”, the label may be misleading. No functional bug was found; this is a naming/UX clarification for product or documentation.

---

## 8. Initial calculation on create (scripts.blade.php)

**Location:** `resources/views/projects/partials/scripts.blade.php`.

**Current behaviour:**  
- `calculateTotalAmountSanctioned()` is called on load.  
- Only `amount_forwarded` has an input listener; `setTimeout(calculateBudgetFields, 100)` runs once.  
- If the budget table is in the DOM and has at least one row, overall project budget and then Amount Sanctioned / Opening Balance are updated.  

**Risk:** If the budget partial is loaded after the script (e.g. in a different layout or async block), elements like `overall_project_budget` or `local_contribution` might be missing at run time. In the current create flow (single form, budget partial and scripts both included in the same page), this is low risk but worth keeping in mind if the structure changes.

---

## Summary table

| # | Severity   | Area        | Issue |
|---|------------|-------------|--------|
| 1 | **Critical** | Create (scripts.blade.php) | `localContributionField` undefined in `calculateBudgetFields()` → Local Contribution ignored; Amount Sanctioned and Opening Balance wrong when Local Contribution &gt; 0. |
| 2 | Medium     | Create partial | Duplicate `value` on rate_multiplier / rate_duration → default 1 can be lost. |
| 3 | Medium     | Edit partial   | Same duplicate `value` in empty-state row. |
| 4 | Info       | Both          | Amount Sanctioned and Opening Balance are display-only; persistence is via sync/resolver (by design). |
| 5 | Low        | Both scripts  | No minimum row check; user can remove all budget rows. |
| 6 | Low        | Create script | No DOMContentLoaded listener for Local Contribution (optional after fixing #1). |
| 7 | Info       | Partials      | “Costs” label vs `rate_quantity` – confirm intended meaning. |
| 8 | Low        | Create script | Initial calculation timing if DOM order changes. |

---

## Files referenced

- **Create budget partial:** `resources/views/projects/partials/budget.blade.php`
- **Edit budget partial:** `resources/views/projects/partials/Edit/budget.blade.php`
- **Create form scripts:** `resources/views/projects/partials/scripts.blade.php`
- **Edit form scripts:** `resources/views/projects/partials/scripts-edit.blade.php`
- **Budget controller:** `app/Http/Controllers/Projects/BudgetController.php`
- **General info (budget fields):** `app/Http/Controllers/Projects/GeneralInfoController.php`
- **Sync / resolver:** `app/Services/Budget/BudgetSyncService.php`, `app/Services/Budget/ProjectFundFieldsResolver.php`

---

**Document version:** 1.0  
**Date:** 2026-01-30
