# Validation & Normalization Layer Design – Batch 6

*Companion to Validation_Normalization_Design.md and Batches 2–5. Covers existing middleware, implementation checklist, decision log, open questions, form-specific remediation map, and cross-reference to Frontend_Backend_Contract_Audit.*

---

## Existing Global Middleware – What It Covers

### Kernel.php Global Stack

```
TrimStrings → ConvertEmptyStringsToNull
```

### TrimStrings

- **Behavior:** Trims leading/trailing whitespace from string inputs. Recurses into nested arrays.
- **Skips:** `password`, `password_confirmation`, and configurable `$except`.
- **Covers:** `"  value  "` → `"value"`; `"   "` → `""`.
- **Does not cover:** Non-string values; values in skipped keys.

### ConvertEmptyStringsToNull

- **Behavior:** Converts `''` to `null` for all input keys. Recurses into nested arrays.
- **Covers:** `""` → `null` (so `$request->input('iies_total_expenses')` returns `null` when form submits empty).
- **Does not cover:** `"-"`, `"N/A"`, `"n/a"`, `"NA"`, `"0"` (not empty) – these pass through unchanged.
- **Implication:** For `$validated['field'] ?? 0`, when key exists with `null` (after middleware), `null ?? 0` = `0`. So empty string is effectively handled for top-level and nested fields **if** the controller uses `?? 0`.

### Why Production Failures Still Occurred

1. **Placeholder values** – `"-"` and `"N/A"` are not empty; middleware does not convert them. CCI Statistics received literal `"-"` in integer column.
2. **Possible timing** – If `$request->all()` is called before middleware runs (it does not – middleware runs first), or if data is merged from another source that bypasses transformation.
3. **Type coercion** – `null` for a NOT NULL decimal column: MySQL strict mode rejects explicit NULL. The `?? 0` should produce 0, but if the controller assigns `$validated['field']` directly without `?? 0`, and the key exists with `null`, it would pass null to the model.
4. **Array structure** – For `phases[0][budget][0][this_phase]`, if the frontend sends a string that fails numeric parsing (e.g. concatenated values from a JS bug), the value might not be `""` or `null`.

**Conclusion:** Middleware helps for empty strings. Placeholder normalization and explicit `?? 0` (or equivalent) for NOT NULL columns are still required. Validation `max` bounds are still missing.

---

## Implementation Checklist – Phase 1 (Critical)

*Design reference. Do not implement without approval.*

### IIES Expenses

- [ ] Add normalization in `prepareForValidation` or before use: empty string, `"-"`, `"N/A"` → `0` for decimal fields.
- [ ] Use StoreIIESExpensesRequest rules via Strategy B; replace `$request->all()` with `$request->validated()`.
- [ ] Add `max:99999999.99` for decimal columns.
- [ ] Ensure `?? 0` or equivalent for NOT NULL columns (redundant if normalization produces 0).
- [ ] Remove nested transaction; throw on failure so ProjectController catches.

### IIES Financial Support

- [ ] Normalize boolean-like values for `govt_eligible_scholarship`, `other_eligible_scholarship`.
- [ ] Use FormRequest rules; `$request->validated()`.

### Budget (project_budgets)

- [ ] Add `max:99999999.99` for `rate_duration`, `this_phase`, `next_phase`, etc.
- [ ] Normalize empty → 0 for nullable decimals.
- [ ] BudgetController: consider FormRequest or shared rules.

### CCI Statistics, PersonalSituation, AgeProfile, EconomicBackground

- [ ] Normalize `"-"`, `"N/A"`, empty string → `null` for integer columns.
- [ ] Use FormRequest rules; `$request->validated()`.
- [ ] Ensure `?? null` or equivalent (redundant if normalization produces null).

### IIES Family Working Members

- [ ] Validate `iies_member_name`, `iies_work_nature`, `iies_monthly_income` when row present.
- [ ] Add `max` for `iies_monthly_income`.
- [ ] Normalize empty/placeholder for decimal.

### Sub-Controller Transaction & Error Handling

- [ ] Remove `DB::beginTransaction()` / `DB::commit()` / `DB::rollBack()` from sub-controllers when invoked by ProjectController.
- [ ] Sub-controllers throw on failure; ProjectController catch handles rollback and redirect.
- [ ] Or: ProjectController checks sub-controller return value and aborts on error response.

---

## Decision Log

| # | Decision | Rationale |
|---|----------|-----------|
| 1 | Normalization before validation | Ensures validation runs on consistent input shape |
| 2 | Strategy B for sub-controllers | No route changes; reuse FormRequest rules manually |
| 3 | Database as last safety net | Application enforces first; DB catches what slips through |
| 4 | Sub-controllers throw, no nested transactions | Prevents partial saves; ProjectController owns transaction |
| 5 | Placeholder list: `-`, `N/A`, `n/a`, `NA`, `--` | From production evidence; expand as needed |
| 6 | DECIMAL(10,2) max = 99999999.99 | Matches Laravel migration default |
| 7 | Authorization denied = WARNING | Production log; reduce ERROR noise |
| 8 | Validation failure = INFO/DEBUG | User error, not system failure |
| 9 | FormRequest mandatory for state-changing requests | Consistency; single source of truth |
| 10 | No `$request->all()` for persistence | Use `$request->validated()` or `$request->safe()` |

---

## Open Questions

| # | Question | Owner | Impact |
|---|----------|-------|--------|
| 1 | Should draft saves allow empty IIES/CCI sections? | Product | Affects validation relaxation |
| 2 | Is placeholder `-` intentional user input (meaning "none")? | Product | Affects normalization vs validation |
| 3 | Should frontend be refactored for section-level AJAX saves? | Tech | Enables Strategy D; cleaner FormRequest binding |
| 4 | Feature flag for new validation per project type? | Tech | Rollout strategy |
| 5 | Log normalization at DEBUG for first N weeks? | Tech | Verify no unintended effects |
| 6 | Budget calculation: fix frontend or add backend recalculation? | Tech | Overflow prevention |
| 7 | IES attachments: single file or multiple? | Product | Contract alignment |

---

## Form-Specific Remediation Map

| Form / Section | Primary Fix | Secondary | Reference |
|----------------|-------------|-----------|-----------|
| IIES Expenses | Normalize empty/placeholder → 0; max bounds | Strategy B; throw on fail | Production Issue 1 |
| IIES Financial Support | Boolean normalization | Strategy B | Production Issue 10 |
| IIES Family Working Members | Validate required when row present; max for income | Strategy B | Migration audit |
| Budget (phases) | max:99999999.99 | Normalize empty → 0 | Production Issue 2 |
| CCI Statistics | Placeholder → null | Strategy B | Production Issue 8 |
| CCI PersonalSituation | Same | Same | Batch 2 |
| CCI AgeProfile | Same | Same | Batch 2 |
| CCI EconomicBackground | Same | Same | Batch 2 |
| IES Attachments | File single vs array | Contract alignment | Production Issue 3 |
| Logical Framework | Ensure `activity` key exists | Filter malformed entries | Production Issue 9 |
| Key Information | Word count (if required) | - | Key_Information docs |
| General Info | Empty string for numeric defaults | - | Batch 4 |
| Budget Reconciliation manualCorrection | max for decimals | - | Batch 3 |

---

## Cross-Reference: Frontend_Backend_Contract_Audit Series

| Document | Focus | Key Violations |
|----------|-------|----------------|
| Frontend_Backend_Contract_Audit.md | Model-by-model contract review | IIES empty string, CCI placeholder, budget overflow |
| Frontend_Backend_Contract_Audit_Batch_2.md | Extended models | - |
| Frontend_Backend_Contract_Audit_Batch_3.md | FormRequest analysis, draft mode | Violations 55–57: draft bypass, no max, cross-field |
| Frontend_Backend_Contract_Audit_Batch_4.md | Reports, PDF, notifications | Violations 79–80: nested arrays, no max length |
| Frontend_Backend_Contract_Audit_Extended.md | Extended analysis | - |

### Overlap with Validation_Normalization_Design

- **Contract Audit** identifies *what* is violated (frontend sends X, backend expects Y).
- **Validation_Normalization_Design** proposes *how* to fix (normalization layer, FormRequest strategy, shared rules).
- Both agree: empty string, placeholder, overflow, and structural gaps must be addressed.

---

## Consolidated Document Index

| Document | Purpose |
|----------|---------|
| **Validation_Normalization_Design.md** | Core design, current state, proposed architecture |
| **Batch 2** | Model examples (IAH, IGE, ILP, etc.); secondary flows; priority matrix |
| **Batch 3** | Route/FormRequest strategy; Provincial/General; reports; shared rules |
| **Batch 4** | DB migration audit; remaining controllers; frontend contract; rollout; testing; glossary |
| **Batch 5** | Error handling; logging; JSON; model casts; config; transactions |
| **Batch 6** | Middleware; checklist; decision log; open questions; remediation map; cross-reference |
| **Production_Log_Review_3031.md** | Production failures that motivated this design |
| **Frontend_Backend_Contract_Audit*** | Contract violations by model/form |

---

## DO NOT

- Implement code
- Refactor controllers
- Add validation rules
- Modify database schema

This document extends the architectural design for planning only.

---

*Document generated: January 31, 2026*  
*Companion to Validation_Normalization_Design.md and Batches 2–5*
