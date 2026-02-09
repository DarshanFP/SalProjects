# Validation & Normalization Layer Design – Batch 4

*Companion to Validation_Normalization_Design.md, Batch 2, and Batch 3. Covers database migration audit, remaining project controllers, frontend contract, phased rollout, testing strategy, and glossary.*

---

## Database Migration Audit – Project-Related Tables

### NOT NULL Columns (No `->nullable()`, No `->default()`)

| Table | Column | Type | Risk |
|-------|--------|------|------|
| project_IIES_expenses | iies_total_expenses, iies_expected_scholarship_govt, iies_support_other_sources, iies_beneficiary_contribution, iies_balance_requested | decimal(10,2) | Has `->default(0)` but explicit NULL bypasses – **Production Issue 1** |
| project_IIES_expense_details | iies_particular, iies_amount | string, decimal(10,2) | No default; empty/placeholder will fail |
| project_IIES_family_working_members | iies_member_name, iies_work_nature, iies_monthly_income | string, string, decimal(10,2) | No default; empty row or missing field fails |
| project__i_e_s_expense_details | amount | decimal(10,2) | No default |
| projects | user_id, project_type, in_charge, goal, status | bigint, string, bigint, text, string | Enforced by app; goal was NOT NULL (check current migration) |

### NOT NULL with `->default()` (Explicit NULL Bypasses Default)

| Table | Column | Default | Risk |
|-------|--------|---------|------|
| project_IIES_expenses | All decimal columns | 0 | MySQL does not apply default when explicit NULL in INSERT |
| project_IIES_immediate_family_details | All boolean columns | 0 | Same |
| project_IIES_scope_financial_supports | govt_eligible_scholarship, other_eligible_scholarship | false | Same – **Production Issue 10** |
| project_ILP_personal_infos | small_business_status | false | Same |
| projects | overall_project_budget, status | 0.00, 'underwriting' | Same |

### Decimal Columns – Overflow Risk (DECIMAL(10,2) max = 99,999,999.99)

| Table | Columns | Current Validation |
|-------|---------|-------------------|
| project_budgets | rate_quantity, rate_multiplier, rate_duration, rate_increase, this_phase, next_phase | min:0 only – **Production Issue 2** |
| project_IIES_expenses | All decimal | min:0 only |
| project_IIES_family_working_members | iies_monthly_income | None (uses all()) |
| project_IGE_budgets | college_fees, hostel_fees, etc. | None |
| project_ILP_budgets | cost, beneficiary_contribution, amount_requested | None |
| project_IAH_budget_details | amount, total_expenses, family_contribution, amount_requested | None |
| projects | overall_project_budget, amount_forwarded, amount_sanctioned, opening_balance | StoreProjectRequest has min:0; no max |

### Integer Columns – Placeholder Risk

| Table | Columns | Current Validation |
|-------|---------|-------------------|
| project_CCI_statistics | All integer columns | nullable\|integer\|min:0 – **Placeholder `-` passes** – Production Issue 8 |
| project_CCI_personal_situation | All integer columns | Same pattern |
| project_CCI_age_profiles | All integer columns | Same |
| project_CCI_economic_backgrounds | All integer columns | Same |
| project_RST_* | tg_no_of_beneficiaries, direct_beneficiaries, indirect_beneficiaries | None |
| project_LDP_target_groups | L_amount_requested | integer – placeholder risk |

---

## Remaining Project Controllers – Analysis

### KeyInformationController

| Method | Validation | Uses validated() | Notes |
|--------|------------|------------------|-------|
| store | Inline `$request->validate([...])` | Yes (via $validated) | goal nullable; uses array_key_exists before assign |
| update | Same | Yes | Same |

**Status:** Good pattern – validates, uses validated data, conditional assignment. No `$request->all()`. **Gap:** No word-count validation (per Key_Information docs); no placeholder normalization for text fields.

### GeneralInfoController

| Method | Validation | Notes |
|--------|-------------|-------|
| store | `$request->validate([...])` | Receives Request; validates; applies defaults (amount_forwarded ?? 0, local_contribution ?? 0, commencement_month_year composite) |
| update | `$request->validated()` | Uses UpdateGeneralInfoRequest – FormRequest |

**Status:** store uses inline validation; update uses FormRequest. Defaults applied in controller (amount_forwarded, local_contribution, in_charge, overall_project_budget). **Gap:** Same empty-string issue for numeric defaults if form sends `""`.

### AttachmentController

| Method | Validation | Notes |
|--------|-------------|-------|
| store | Inline when file present | required|file|max:7168; file_name, attachment_description nullable |
| update | Inline | Same |

**Status:** Validates when file present; uses config for allowed types. **Gap:** No FormRequest; single file only – no array handling (unlike IES attachments).

### SustainabilityController

| Method | Validation | Notes |
|--------|-------------|-------|
| store | Inline | nullable|string for all four text fields |
| update | Inline | Same |

**Status:** Uses `$validated` with `?? null`; all columns nullable. **Gap:** No FormRequest; no max length on text (DB may have limits).

---

## Frontend-Backend Contract Summary

*See `Frontend_Backend_Contract_Audit.md` for detailed model-by-model analysis.*

### Key Contract Rules

| Rule | Frontend | Backend |
|------|----------|---------|
| **Empty number input** | HTML5 `<input type="number">` submits `""` when blank | `?? 0` does NOT catch `""`; key exists |
| **Placeholder values** | User or JS may send `-`, `N/A` | No normalization; passes to DB |
| **Checkbox omitted** | Unchecked = key absent | `$request->has($field) ? 1 : 0` correct |
| **Checkbox present with empty** | Some frameworks send `field=""` | `has()` true → 1; may be wrong |
| **File single vs array** | `name="field"` vs `name="field[]"` | Backend must match or normalize |
| **Draft save** | JS may remove `required` (ReportAll.blade.php) | StoreProjectRequest relaxes project_type |
| **Calculated readonly** | JS computes; submits value or "" | Backend trusts; no recalculation |

### Draft vs Submit – Frontend Behavior

| Location | Draft | Submit |
|----------|-------|--------|
| ReportAll.blade.php | Removes `required` from all fields | Restores `required` |
| monthly/edit.blade.php | Removes `required` temporarily | - |
| StoreProjectRequest | project_type nullable when save_as_draft=1 | project_type required |
| StoreMonthlyReportRequest | prepareForValidation for save_as_draft | - |

**Gap:** Sub-controllers (IIES, CCI, etc.) do not relax validation for draft. If user saves draft with empty IIES expense fields, NOT NULL violation can still occur.

---

## Phased Rollout – Implementation Sequence

*Design only. Do not implement.*

### Phase 0: Preparation

1. Create `app/Rules/` directory.
2. Implement shared rules: NumericBoundsRule, NullableIntegerOrPlaceholderRule (or equivalent in prepareForValidation).
3. Create InputNormalizer service (or FormRequest concern) with placeholder list and empty-string handling.
4. Document frontend contract for each form (reference Frontend_Backend_Contract_Audit).

### Phase 1: Critical Data Safety (P0)

1. **IIESExpensesController** – Add normalization in prepareForValidation (or before validation); use StoreIIESExpensesRequest rules via Strategy B; ensure empty string → 0.
2. **IIES FinancialSupportController** – Same for govt_eligible_scholarship, other_eligible_scholarship.
3. **BudgetController** – Add max:99999999.99 for decimal columns; normalize empty → 0.
4. **CCI StatisticsController, PersonalSituationController, AgeProfileController, EconomicBackgroundController** – Normalize placeholder → null; use validated().
5. **IIES FamilyWorkingMembersController** – Validate iies_member_name, iies_work_nature, iies_monthly_income required when row present; normalize decimal.

### Phase 2: Type Mismatch & Structure (P1)

1. **IES Attachments** – Normalize file input (single or first of array) in handler or FormRequest.
2. **LogicalFrameworkController** – Ensure `activity` key exists; filter or default.
3. **BudgetReconciliationController manualCorrection** – Add max for decimals.

### Phase 3: Consistency (P2)

1. **All project sub-controllers** – Replace `$request->all()` with Strategy B validation + `$request->validated()`.
2. **PDF Export** – Align variable passing (IGEbudget, etc.) with show view.
3. **ProvincialController, GeneralController** – Extract FormRequests for user/center/society CRUD.

### Phase 4: Reports & Admin (P3)

1. **Report controllers** – FormRequests for type-specific monthly, quarterly, aggregated.
2. **Bulk actions** – Validate report_ids, action, revert_reason.
3. **Activity history filters** – Sanitize filter keys.

### Phase 5: Sanitation & Structure (P4)

1. Trim strings in prepareForValidation.
2. Expand placeholder list.
3. Nested array validation (target_group.*, phases.*.budget.*).

---

## Testing Strategy (Conceptual)

*Do not implement. Design only.*

### Unit Tests

| Target | What to Test |
|--------|--------------|
| InputNormalizer | empty string → 0 for NOT NULL numeric |
| InputNormalizer | placeholder (-, N/A) → null for nullable integer |
| InputNormalizer | placeholder → 0 for NOT NULL decimal |
| NumericBoundsRule | rejects value > 99999999.99 |
| NullableIntegerOrPlaceholderRule | accepts "-", "N/A", "", null; rejects "abc" |

### Feature Tests

| Target | What to Test |
|--------|--------------|
| IIESExpensesController@store | Empty string in iies_total_expenses → saved as 0 |
| IIESExpensesController@store | Placeholder "-" in iies_total_expenses → saved as 0 (or rejected) |
| BudgetController@store | Value > 99999999.99 → validation error |
| CCI StatisticsController@store | Placeholder "-" in shifted_children_current_year → saved as null |
| LogicalFrameworkController@store | Missing 'activity' key → no undefined key error |

### Contract Tests

| Target | What to Test |
|--------|--------------|
| Project store form | Submit with empty number inputs → no 500 |
| CCI statistics form | Submit with "-" in integer field → no SQL error |
| IES attachments form | Submit with file[] (if view uses array) → handled correctly |

---

## Glossary

| Term | Definition |
|------|------------|
| **Validation** | Checking that input meets rules (required, type, range, format). Fails with validation errors. |
| **Normalization** | Transforming input before validation (empty string → 0, placeholder → null, trim). Does not fail; produces consistent shape. |
| **Placeholder** | User or system value meaning "no value" or "not applicable": `-`, `N/A`, `n/a`, `NA`, `--`, etc. |
| **Empty string** | `""` – HTML form submits this for blank number/text inputs. Key exists; `??` does not apply. |
| **Strategy B** | Sub-controller manually runs validation using FormRequest rules, then uses validated data. |
| **Contract** | Agreement between frontend (what is sent) and backend (what is expected). Violation causes runtime or DB errors. |
| **Phase 1 (Critical)** | Prevents DB constraint violations; ensures types and ranges. |
| **Phase 2 (Sanitation)** | Trim, placeholder normalization, empty value handling. |
| **Phase 3 (Structure)** | Nested arrays, repeating rows, optional sections. |
| **Phase 4 (Context)** | Draft vs submit, role-based, partial saves. |

---

## Cross-Reference to Related Documents

| Document | Content |
|----------|---------|
| Validation_Normalization_Design.md | Core design, current state, proposed architecture |
| Validation_Normalization_Design_Batch_2.md | Model examples (IAH, IGE, ILP, etc.); secondary flows; priority matrix |
| Validation_Normalization_Design_Batch_3.md | Route/FormRequest strategy; Provincial/General; reports; shared rules |
| Frontend_Backend_Contract_Audit.md | Detailed contract violations by model |
| Production_Log_Review_3031.md | Production failures that motivated this design |

---

## DO NOT

- Implement code
- Refactor controllers
- Add validation rules
- Modify database schema

This document extends the architectural design for planning only.

---

*Document generated: January 31, 2026*  
*Companion to Validation_Normalization_Design.md, Batch 2, and Batch 3*
