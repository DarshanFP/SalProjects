# Validation & Normalization Layer Design – Batch 7 (Final)

*Companion to Validation_Normalization_Design.md and Batches 2–6. Executive summary, dependencies, success criteria, risk matrix, Blade view inventory, appendices, and design closure.*

---

## Executive Summary

### Problem

Production log review (Jan 29–31, 2026) documented **59 errors** across **10 categories**. Root causes: null vs empty string handling, numeric overflow, placeholder values (`-`, `N/A`) reaching the database, and frontend-backend contract gaps. The database is acting as the first validator; users see 500 errors instead of validation messages.

### Proposed Solution

A **Validation & Normalization Layer** that:

1. **Normalizes** input before validation (empty string → 0/null, placeholder → null, trim).
2. **Validates** with FormRequest rules; no `$request->all()` for persistence.
3. **Enforces** max bounds for decimals; placeholder handling for integers.
4. **Aligns** sub-controller error handling (throw, no nested transactions).

### Scope

- **In scope:** Project CRUD, report CRUD, user/center/society management, budget reconciliation, bulk actions.
- **Out of scope:** API routes (read-only provinces/centers); deployment issues (routes/api.php).

### Deliverables (Design Only)

- 7 design documents (main + Batches 2–7).
- No code implementation; no schema changes.
- Implementation checklist and phased rollout plan for future execution.

---

## Dependencies Between Fixes

```
Phase 0 (Preparation)
├── Create app/Rules/
├── Implement NumericBoundsRule, placeholder rules
├── Create InputNormalizer (or FormRequest concern)
└── Document frontend contract

Phase 1 (Critical) – Can parallelize within phase
├── IIES Expenses ──────────────┐
├── IIES Financial Support ────┤
├── Budget (max bounds) ───────┼── No dependencies between these
├── CCI (Statistics, etc.) ────┤
├── IIES Family Working Members┘
└── Sub-controller transaction fix ─── Required before/with above (prevents partial saves)

Phase 2 (Type Mismatch)
├── IES Attachments (file single vs array) ─── May need frontend change
├── Logical Framework (activity key) ───────── No dependency
└── BudgetReconciliation manualCorrection ──── No dependency

Phase 3 (Consistency)
├── All sub-controllers Strategy B ─── Depends on Phase 1 completion
├── PDF Export variable contract ───── No dependency
└── Provincial/General FormRequests ─── No dependency

Phase 4 (Reports & Admin)
└── Report controllers, bulk actions, activity filters ─── Independent

Phase 5 (Sanitation)
└── Trim, placeholder expansion, nested arrays ─── After Phase 1–4
```

**Critical path:** Phase 0 → Phase 1 (IIES, Budget, CCI, transaction fix) blocks user-facing stability. Phase 2–5 can follow incrementally.

---

## Success Criteria Per Phase

| Phase | Done When |
|-------|-----------|
| **Phase 0** | Rules exist; InputNormalizer (or equivalent) exists; frontend contract documented |
| **Phase 1** | No NOT NULL violations for IIES/CCI; no budget overflow; no partial saves on sub-controller failure |
| **Phase 2** | IES attachments work; Logical Framework no undefined key; BudgetReconciliation has max |
| **Phase 3** | All project sub-controllers use validated(); PDF export passes required variables |
| **Phase 4** | Report FormRequests; bulk actions validate IDs; activity filters sanitized |
| **Phase 5** | Placeholder list expanded; nested arrays validated; trim applied |

### Measurable Outcomes

- **Production log:** Zero NOT NULL, numeric overflow, or undefined key errors for addressed areas.
- **Regression:** No new 500s introduced by validation changes.
- **User feedback:** No "data didn't save" or "form won't submit" reports for fixed flows.

---

## Risk Matrix

| Gap | Likelihood | Impact | Risk |
|-----|------------|--------|------|
| IIES NOT NULL | High | High | **P0** – Blocks IIES users |
| Budget overflow | Medium | High | **P0** – Data corruption |
| CCI placeholder | Medium | High | **P0** – Blocks CCI users |
| IES file array | High | High | **P1** – Blocks IES attachments |
| Logical Framework undefined key | Low | Medium | **P1** |
| Sub-controller partial save | High | High | **P0** – Silent data loss |
| IIES Financial Support NOT NULL | Low | High | **P1** |
| No max on decimals (general) | Medium | Medium | **P2** |
| Provincial/General inline validation | Low | Low | **P3** |
| Report nested arrays | Low | Medium | **P3** |

---

## Blade View Inventory – Key Forms

| View | Section | Input Names | Notes |
|------|---------|-------------|-------|
| projects/partials/Edit/budget.blade.php | Budget | phases[0][budget][*][rate_quantity], rate_multiplier, rate_duration, this_phase | JS calculateBudgetRowTotals; no max on inputs |
| projects/partials/scripts.blade.php | Budget (create) | Same | Dynamic row add; value="1" default |
| projects/partials/scripts-edit.blade.php | Budget (edit) | Same | Same |
| projects/partials/Edit/key_information.blade.php | Key info | goal (required) | |
| projects/partials/Edit/general_info.blade.php | General | project_type, society_name, in_charge, etc. | required on many fields |
| IIES expense form | IIES | iies_total_expenses, iies_expected_scholarship_govt, etc. | number inputs; can submit empty |
| CCI statistics form | CCI | shifted_children_current_year, etc. | Placeholder "-" possible |
| IES attachments | IES | field[] (array) | Backend expects single file |
| reports/monthly/ReportAll.blade.php | Report create | Removes required on draft | |
| reports/monthly/edit.blade.php | Report edit | Same | |
| admin/budget_reconciliation/show.blade.php | Manual correction | overall_project_budget, amount_forwarded, local_contribution | required; no max |

**Frontend changes (if any):** IES attachments – align single vs array. Budget – optional `max` attribute on number inputs for UX (backend must enforce). CCI – optional placeholder handling in JS (backend must enforce).

---

## Appendix A: Controller-to-FormRequest Mapping

| Controller | Store FormRequest | Update FormRequest | Uses It? |
|------------|-------------------|-------------------|----------|
| ProjectController | StoreProjectRequest | UpdateProjectRequest | Yes |
| GeneralInfoController | (inline) | UpdateGeneralInfoRequest | store: no; update: yes |
| KeyInformationController | (inline) | (inline) | No |
| BudgetController | (inline) | (inline) | No |
| IIESExpensesController | StoreIIESExpensesRequest | UpdateIIESExpensesRequest | No – uses all() |
| CCIStatisticsController | StoreCCIStatisticsRequest | UpdateCCIStatisticsRequest | No – uses all() |
| SustainabilityController | (inline) | (inline) | No |
| AttachmentController | (inline) | (inline) | No |
| LogicalFrameworkController | (inline) | (inline) | No |
| ReportController (Monthly) | StoreMonthlyReportRequest | UpdateMonthlyReportRequest | Yes |
| ProvincialController | (inline) | (inline) | No |
| GeneralController | (inline) | (inline) | No |
| BudgetReconciliationController | (inline) | (inline) | No |

**Proposed:** All "No" and "(inline)" should migrate to FormRequest with `$request->validated()`.

---

## Appendix B: NOT NULL Column to Rule Mapping

| Table.Column | Type | Proposed Rule | Normalization |
|--------------|------|---------------|---------------|
| project_IIES_expenses.iies_total_expenses | decimal(10,2) | required\|numeric\|min:0\|max:99999999.99 | empty, placeholder → 0 |
| project_IIES_expenses.iies_expected_scholarship_govt | decimal(10,2) | Same | Same |
| project_IIES_expenses.iies_support_other_sources | decimal(10,2) | Same | Same |
| project_IIES_expenses.iies_beneficiary_contribution | decimal(10,2) | Same | Same |
| project_IIES_expenses.iies_balance_requested | decimal(10,2) | Same | Same |
| project_IIES_family_working_members.iies_monthly_income | decimal(10,2) | required when row present | Same |
| project_IIES_scope_financial_supports.govt_eligible_scholarship | boolean | required\|boolean | coerce to 0/1 |
| project_IIES_scope_financial_supports.other_eligible_scholarship | boolean | Same | Same |
| project_budgets.rate_duration, this_phase, etc. | decimal(10,2) | nullable\|numeric\|min:0\|max:99999999.99 | empty → 0 |
| project_CCI_statistics.* (integer cols) | integer | nullable\|integer\|min:0 | placeholder → null |
| project_CCI_personal_situation.* (integer cols) | integer | Same | Same |

---

## Appendix C: API Routes

| Route | Method | Validation | Notes |
|-------|--------|------------|-------|
| /api/user | GET | auth:sanctum | Read-only |
| /api/provinces | GET | - | Read-only |
| /api/provinces/{id}/centers | GET | - | Read-only |
| /api/centers | GET | - | Read-only |
| /api/centers/by-province/{provinceId} | GET | - | Read-only |

**Conclusion:** No write/validation needed for current API. If write API is added later, apply same Validation & Normalization Layer design.

---

## Design Closure

This design series (main document + Batches 2–7) provides:

- **Current state analysis** – Where validation and normalization exist, where they are missing.
- **Proposed architecture** – Normalization before validation; FormRequest mandatory; Strategy B for sub-controllers.
- **Phase-wise plan** – Phase 0 (prep) through Phase 5 (sanitation).
- **Model-level examples** – IIES, Budget, CCI, IES, Logical Framework, IAH, IGE, ILP, RST, EduRUT, CIC, LDP.
- **Implementation checklist** – Phase 1 critical items.
- **Decision log** – Key architectural decisions.
- **Open questions** – Items for product/tech resolution.
- **Appendices** – Controller mapping, column-to-rule mapping, API, Blade inventory.

**Next steps (when approved):**

1. Resolve open questions.
2. Execute Phase 0 (Rules, InputNormalizer).
3. Execute Phase 1 (IIES, Budget, CCI, transaction fix).
4. Validate in staging; monitor production logs.
5. Proceed with Phase 2–5 as capacity allows.

---

## DO NOT

- Implement code
- Refactor controllers
- Add validation rules
- Modify database schema

This document extends the architectural design for planning only.

---

*Document generated: January 31, 2026*  
*Final batch of Validation_Normalization_Design series*
