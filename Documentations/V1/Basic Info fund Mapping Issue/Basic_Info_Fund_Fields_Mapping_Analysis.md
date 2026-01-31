# Basic Information Fund Fields – Mapping Analysis by Project Type

## 1. Purpose

The **Basic Information** section on the project **Show** view displays five fund-related fields for every project:

| Basic Info Label | Source (current) | Description |
|------------------|------------------|-------------|
| **Overall Project Budget** | `$project->overall_project_budget` | Total budget for the project |
| **Amount Forwarded (Existing Funds)** | `$project->amount_forwarded` | Funds brought forward from previous phase/year |
| **Local Contribution** | `$project->local_contribution` | Contribution from beneficiary/family/other sources |
| **Amount Sanctioned** | `$project->amount_sanctioned` | Amount to be requested/sanctioned |
| **Opening Balance** | `$project->opening_balance` | Starting balance (Amount Sanctioned + Amount Forwarded + Local Contribution) |

**Current behaviour:**  
These values are taken only from the **projects** table. For **Development Projects** (and other institutional types that use the standard budget partial), users enter them in the **Budget** / **General Information** sections, so the mapping is direct.

For **individual** and **institutional education** project types (IIES, IES, ILP, IAH, IGE), budget data is stored in **type-specific** tables and sections. The projects table fields are often **not** filled from those sections, so the Basic Information block can show **Rs. 0.00** even when the type-specific budget has real figures.

This document analyses each such project type’s **budget format** and proposes how to map them to the five Basic Info fund fields.

---

## 2. Standard (Development Project) Mapping

Used when the project uses the default **Budget** section (`partials/budget.blade.php`, `partials/Edit/budget.blade.php`).

| Basic Info Field | Source | Notes |
|------------------|--------|-------|
| Overall Project Budget | `projects.overall_project_budget` | Entered in form |
| Amount Forwarded (Existing Funds) | `projects.amount_forwarded` | Entered in form |
| Local Contribution | `projects.local_contribution` | Entered in form |
| Amount Sanctioned | Computed: `Overall − (Amount Forwarded + Local Contribution)` | Or from `projects.amount_sanctioned` if stored |
| Opening Balance | Computed: `Amount Sanctioned + Amount Forwarded + Local Contribution` | Or from `projects.opening_balance` if stored |

Formulas used in `CoordinatorController` and `resources/views/projects/partials/scripts-edit.blade.php`:

- **Amount Sanctioned** = Overall Project Budget − (Amount Forwarded + Local Contribution)  
- **Opening Balance** = Amount Sanctioned + (Amount Forwarded + Local Contribution)

---

## 3. IIES – Individual - Initial - Educational Support

### 3.1 Budget section layout

- **View:** `resources/views/projects/partials/Show/IIES/estimated_expenses.blade.php`
- **Data:** `ProjectIIESExpenses` (+ `ProjectIIESExpenseDetail` for line items)  
- **Table:** `project_IIES_expenses`, `project_IIES_expense_details`

### 3.2 Type-specific fields

| UI label | Model/DB field | Meaning |
|----------|----------------|--------|
| Total expense of the study | `iies_total_expenses` | Sum of all expense items (total cost of study) |
| Scholarship expected from government | `iies_expected_scholarship_govt` | Govt. scholarship component |
| Support from other sources | `iies_support_other_sources` | Other funding/support |
| Beneficiaries' contribution | `iies_beneficiary_contribution` | Beneficiary’s own contribution |
| Balance amount requested | `iies_balance_requested` | Amount requested from the organisation |

Formula in use:  
`iies_balance_requested = iies_total_expenses − (iies_expected_scholarship_govt + iies_support_other_sources + iies_beneficiary_contribution)`.

### 3.3 Proposed mapping to Basic Info

| Basic Info Field | Map from IIES | Formula / Source |
|------------------|---------------|-------------------|
| **Overall Project Budget** | Total expense of the study | `iies_total_expenses` |
| **Amount Forwarded (Existing Funds)** | (Not in IIES form) | `0` (or keep from `projects.amount_forwarded` if ever used) |
| **Local Contribution** | Sum of scholarship + other support + beneficiary | `iies_expected_scholarship_govt + iies_support_other_sources + iies_beneficiary_contribution` |
| **Amount Sanctioned** | Balance amount requested | `iies_balance_requested` |
| **Opening Balance** | Same as overall for this type | `iies_total_expenses` (or Amount Sanctioned + Local Contribution when Amount Forwarded = 0) |

So for **view** and any sync back to **projects**:

- **Overall Project Budget** = Total expense of the study = `iies_total_expenses`
- **Local Contribution** = Scholarship expected from government + Support from other sources + Beneficiaries’ contribution.

---

## 4. IES / IOES – Individual - Ongoing Educational Support

### 4.1 Budget section layout

- **View:** `resources/views/projects/partials/Show/IES/estimated_expenses.blade.php`
- **Data:** `ProjectIESExpenses` (+ expense details)  
- **Table:** `project_IES_expenses`, expense details

### 4.2 Type-specific fields

| UI label | Model/DB field | Meaning |
|----------|----------------|--------|
| Total expense of the study | `total_expenses` | Total cost of study |
| Scholarship expected from government | `expected_scholarship_govt` | Govt. scholarship |
| Support from other sources | `support_other_sources` | Other support |
| Beneficiaries' contribution | `beneficiary_contribution` | Beneficiary contribution |
| Balance amount requested | `balance_requested` | Amount requested from organisation |

Same conceptual structure as IIES.

### 4.3 Proposed mapping to Basic Info

| Basic Info Field | Map from IES | Formula / Source |
|------------------|--------------|-------------------|
| **Overall Project Budget** | Total expense of the study | `total_expenses` |
| **Amount Forwarded (Existing Funds)** | (Not in IES form) | `0` |
| **Local Contribution** | Scholarship + other + beneficiary | `expected_scholarship_govt + support_other_sources + beneficiary_contribution` |
| **Amount Sanctioned** | Balance amount requested | `balance_requested` |
| **Opening Balance** | Same as overall when no forward | `total_expenses` |

---

## 5. ILP – Individual - Livelihood Application

### 5.1 Budget section layout

- **View:** `resources/views/projects/partials/Show/ILP/budget.blade.php` (and Edit equivalent)
- **Data:** `ProjectILPBudget` (multiple rows), aggregated in `ILP/BudgetController`  
- **Table:** `project_ILP_budget`

### 5.2 Type-specific fields

| UI label | Source | Meaning |
|----------|--------|--------|
| Budget lines | `budget_desc`, `cost` per row | Line items |
| Total amount | Sum of `cost` | `total_amount` (derived) |
| Beneficiary's contribution | `beneficiary_contribution` (e.g. from first row) | Own contribution |
| Amount requested | `amount_requested` (e.g. from first row) | Amount requested from organisation |

Controller logic: `total_amount = sum(cost)`, `beneficiary_contribution` and `amount_requested` from first budget row (or single project-level value).

### 5.3 Proposed mapping to Basic Info

| Basic Info Field | Map from ILP | Formula / Source |
|------------------|--------------|-------------------|
| **Overall Project Budget** | Total amount | `sum(cost)` / “Total amount” |
| **Amount Forwarded (Existing Funds)** | (Not in ILP form) | `0` |
| **Local Contribution** | Beneficiary's contribution | `beneficiary_contribution` |
| **Amount Sanctioned** | Amount requested | `amount_requested` |
| **Opening Balance** | Same as overall when no forward | `total_amount` |

---

## 6. IAH – Individual - Access to Health

### 6.1 Budget section layout

- **View:** `resources/views/projects/partials/Show/IAH/budget_details.blade.php`
- **Data:** `ProjectIAHBudgetDetails` (multiple rows)  
- **Table:** `project_IAH_budget_details`

### 6.2 Type-specific fields

| UI label | Source | Meaning |
|----------|--------|--------|
| Particulars + Amount | `particular`, `amount` per row | Line items |
| Total Expenses | Sum of `amount` | Total cost of treatment |
| Family Contribution | `family_contribution` (e.g. from first row) | Family’s share |
| Total Amount Requested | `amount_requested` (e.g. from first row) | Amount requested from organisation |

Per row the controller also stores `total_expenses`, `family_contribution`, `amount_requested` (often same for all rows for the project).

### 6.3 Proposed mapping to Basic Info

| Basic Info Field | Map from IAH | Formula / Source |
|------------------|--------------|-------------------|
| **Overall Project Budget** | Total Expenses | `sum(amount)` / Total cost of treatment |
| **Amount Forwarded (Existing Funds)** | (Not in IAH form) | `0` |
| **Local Contribution** | Family Contribution | `family_contribution` |
| **Amount Sanctioned** | Total Amount Requested | `amount_requested` |
| **Opening Balance** | Same as overall when no forward | Total Expenses |

---

## 7. IGE – Institutional Ongoing Group Educational Proposal

### 7.1 Budget section layout

- **View:** `resources/views/projects/partials/Show/IGE/budget.blade.php`
- **Data:** `ProjectIGEBudget` (multiple rows, one per beneficiary)  
- **Table:** `project_IGE_budget`

### 7.2 Type-specific fields (per row and totals)

| UI label | Model/DB field | Meaning |
|----------|----------------|--------|
| College Fees | `college_fees` | Fees component |
| Hostel Fees | `hostel_fees` | Hostel component |
| Total Amount | `total_amount` | Row total (e.g. college + hostel) |
| Eligibility of Scholarship (Expected Amount) | `scholarship_eligibility` | Expected scholarship |
| Contribution from Family | `family_contribution` | Family share |
| Amount Requested | `amount_requested` | Amount requested from organisation |

View shows **totals** over all rows:  
`$totalAmount`, `$totalScholarshipEligibility`, `$totalFamilyContribution`, `$totalAmountRequested`.

### 7.3 Proposed mapping to Basic Info

| Basic Info Field | Map from IGE | Formula / Source |
|------------------|--------------|-------------------|
| **Overall Project Budget** | Total of “Total Amount” over all beneficiaries | `sum(total_amount)` |
| **Amount Forwarded (Existing Funds)** | (Not in IGE form) | `0` (or from project if ever used) |
| **Local Contribution** | Scholarship + family | `sum(scholarship_eligibility) + sum(family_contribution)` |
| **Amount Sanctioned** | Total “Amount Requested” | `sum(amount_requested)` |
| **Opening Balance** | Same as overall when no forward | `sum(total_amount)` |

---

## 8. Summary Table – Where Each Basic Info Field Comes From

| Project type | Overall Project Budget | Amount Forwarded | Local Contribution | Amount Sanctioned | Opening Balance |
|--------------|------------------------|------------------|--------------------|-------------------|------------------|
| **Development / RST / etc.** | `projects.overall_project_budget` | `projects.amount_forwarded` | `projects.local_contribution` | Computed / stored | Computed / stored |
| **IIES** | `iies_total_expenses` | 0 (or project) | `iies_expected_scholarship_govt + iies_support_other_sources + iies_beneficiary_contribution` | `iies_balance_requested` | Same as Overall (or computed) |
| **IES (IOES)** | `total_expenses` | 0 | `expected_scholarship_govt + support_other_sources + beneficiary_contribution` | `balance_requested` | Same as Overall |
| **ILP** | `sum(cost)` | 0 | `beneficiary_contribution` | `amount_requested` | Same as Overall |
| **IAH** | `sum(amount)` (Total Expenses) | 0 | `family_contribution` | `amount_requested` | Same as Overall |
| **IGE** | `sum(total_amount)` | 0 | `sum(scholarship_eligibility) + sum(family_contribution)` | `sum(amount_requested)` | Same as Overall |

---

## 9. Implementation Notes

### 9.1 View (Basic Information section)

- **Option A – View-only:**  
  In `resources/views/projects/partials/Show/general_info.blade.php`, branch by `$project->project_type` and, for IIES/IES/ILP/IAH/IGE, **display** the five fund values using the mappings above (from type-specific relations), instead of always using `$project->overall_project_budget`, `$project->amount_forwarded`, etc.

- **Option B – Sync to projects table:**  
  On **store/update** of type-specific budget (e.g. IIES expenses, IES expenses, ILP budget, IAH budget details, IGE budget), compute the five values and write them to `projects.overall_project_budget`, `projects.local_contribution`, `projects.amount_sanctioned`, `projects.opening_balance`, and leave `projects.amount_forwarded` as 0 for these types. The existing Basic Info view and any service (e.g. `BudgetValidationService`) that reads from `Project` would then work without view changes.

### 9.2 BudgetValidationService

- `BudgetValidationService::calculateBudgetData()` currently uses only `$project->overall_project_budget`, `$project->amount_forwarded`, `$project->local_contribution` and relation `budgets`.  
- For individual/IGE types, it would need either:
  - to use the **synced** values on `projects` (if Option B is used), or  
  - to be extended to load type-specific budget data and compute Overall, Local Contribution, Amount Sanctioned, Opening Balance when the project type is IIES/IES/ILP/IAH/IGE.

### 9.3 Amount Forwarded

- For individual and IGE types, “Amount Forwarded (Existing Funds)” is typically **0**, as there is no separate “existing funds” in their budget UIs.  
- If needed later, it could be taken from `projects.amount_forwarded` when it is ever set for those types (e.g. via General Info or a future change).

---

## 10. File and Model Reference

| Project type | Show partial (budget/expenses) | Main model(s) | Table(s) |
|--------------|--------------------------------|---------------|----------|
| IIES | `partials/Show/IIES/estimated_expenses.blade.php` | `ProjectIIESExpenses`, `ProjectIIESExpenseDetail` | `project_IIES_expenses`, `project_IIES_expense_details` |
| IES | `partials/Show/IES/estimated_expenses.blade.php` | `ProjectIESExpenses` (+ details) | `project_IES_expenses` |
| ILP | `partials/Show/ILP/budget.blade.php` | `ProjectILPBudget` | `project_ILP_budget` |
| IAH | `partials/Show/IAH/budget_details.blade.php` | `ProjectIAHBudgetDetails` | `project_IAH_budget_details` |
| IGE | `partials/Show/IGE/budget.blade.php` | `ProjectIGEBudget` | `project_IGE_budget` |
| Development / RST / etc. | `partials/Show/budget.blade.php` + Basic Info | `Project` (and `Budget` where used) | `projects` |

Basic Information fund block is in:  
`resources/views/projects/partials/Show/general_info.blade.php` (lines 102–121).

---

*Document version: 1.0 — Analysis for Basic Info fund mapping by project type (IIES, IES/IOES, ILP, IAH, IGE, Institutional Ongoing Group Education).*
