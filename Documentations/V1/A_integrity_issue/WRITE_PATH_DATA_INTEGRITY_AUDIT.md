# Write-Path Data Integrity Audit

**Document type:** Code-first, write-path–first integrity audit  
**Scope:** Project-related models, controllers, validation, UI, and database constraints  
**Constraint:** Audit and documentation only — no code fixes, no migration changes, no default additions

---

## 1. Incident Overview

### 1.1 Production Failure

```
SQLSTATE[23000]: Integrity constraint violation:
Column 'other_eligible_scholarship' cannot be null
(table: project_IIES_scope_financial_support)
```

### 1.2 Why the Write Path Allowed NULL

1. **Controller explicitly passes NULL when the key is missing**
    - **File:** `app/Http/Controllers/Projects/IIES/FinancialSupportController.php`
    - **Methods:** `store()` (lines 25–36), `update()` (lines 129–141)
    - **Code:**  
      `'other_eligible_scholarship' => $validated['other_eligible_scholarship'] ?? null`
    - When the request does not contain `other_eligible_scholarship` (e.g. user submits without selecting a radio), `?? null` evaluates to `null` and that value is passed into `updateOrCreate()`.

2. **Laravel uses the provided value, not the migration default**
    - The column is included in the INSERT/UPDATE payload with value `null`.
    - The database default is only applied when the column is **omitted** from the statement.
    - Because the controller **includes** the column with value `null`, the DB rejects it for a NOT NULL column.

3. **UI does not guarantee the field is always sent**
    - **File:** `resources/views/projects/partials/IIES/scope_financial_support.blade.php`
    - Radio inputs for `other_eligible_scholarship` (and `govt_eligible_scholarship`) have no `checked` default and no `required` attribute.
    - If the user submits without selecting either option, the key is absent from the request.

4. **Validation allows absence and null**
    - **Files:**
        - `app/Http/Requests/Projects/IIES/StoreIIESFinancialSupportRequest.php` (line 19)
        - `app/Http/Requests/Projects/IIES/UpdateIIESFinancialSupportRequest.php` (line 34)
    - Rule: `'other_eligible_scholarship' => 'nullable|string|max:255'`
    - So validation does not require the field; it is treated as optional. The DB schema (NOT NULL) is not aligned with this.

5. **Controller uses `$request->all()`**
    - The controller uses `$validated = $request->all()`, not `$request->validated()`.
    - Missing keys in the raw request therefore remain missing in the array passed to the model; `?? null` then supplies `null` for those keys.

---

## 2. Systemic Anti-Pattern Identified

**Pattern:** “Missing key → null fallback → explicit NULL written to NOT NULL column.”

- Controllers use `$validated['field'] ?? null` (or equivalent) for fields that are optional in the UI or validation.
- When the field is not present in the request (unchecked checkbox, unselected radio, omitted field), the code explicitly passes `null` into create/update.
- For columns that are NOT NULL in the database (with or without a DEFAULT), writing NULL causes an integrity violation.
- Validation is often `nullable` for these fields, which matches “optional in UI” but contradicts the NOT NULL constraint in the DB.
- The same pattern appears for boolean/radio fields: validation and UI treat them as optional; DB requires a value; code supplies null when the key is missing.

**Summary:** UI and validation treat a field as optional; the write path converts “missing” to NULL; the database does not allow NULL. The contract between write path and schema is broken.

---

## 3. Model-by-Model Findings

### 3.1 `project_IIES_scope_financial_support` (IIES Financial Support)

| Item         | Detail                                                                                                                                                                        |
| ------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Model**    | `App\Models\OldProjects\IIES\ProjectIIESScopeFinancialSupport`                                                                                                                |
| **Table**    | `project_IIES_scope_financial_support`                                                                                                                                        |
| **Fillable** | `IIES_fin_sup_id`, `project_id`, `govt_eligible_scholarship`, `scholarship_amt`, `other_eligible_scholarship`, `other_scholarship_amt`, `family_contrib`, `no_contrib_reason` |

| Column                       | DB constraint                     | Write path                                                                                        | Risk                                                                           |
| ---------------------------- | --------------------------------- | ------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| `govt_eligible_scholarship`  | `boolean` NOT NULL, default false | `FinancialSupportController::store`, `update` — `$validated['govt_eligible_scholarship'] ?? null` | **HIGH** — Same as incident column; NULL can be written if radio not selected. |
| `other_eligible_scholarship` | `boolean` NOT NULL, default false | Same — `$validated['other_eligible_scholarship'] ?? null`                                         | **HIGH** — Confirmed production failure.                                       |
| `scholarship_amt`            | decimal nullable                  | Same — `?? null`                                                                                  | Low (nullable).                                                                |
| `other_scholarship_amt`      | decimal nullable                  | Same — `?? null`                                                                                  | Low.                                                                           |
| `family_contrib`             | decimal nullable                  | Same — `?? null`                                                                                  | Low.                                                                           |
| `no_contrib_reason`          | text nullable                     | Same — `?? null`                                                                                  | Low.                                                                           |

**Write paths:**

- `App\Http\Controllers\Projects\IIES\FinancialSupportController::store()` — `ProjectIIESScopeFinancialSupport::updateOrCreate()`
- `App\Http\Controllers\Projects\IIES\FinancialSupportController::update()` — same `updateOrCreate()`

**Validation:**

- Store: `StoreIIESFinancialSupportRequest` — `govt_eligible_scholarship`, `other_eligible_scholarship` both `nullable|string|max:255`.
- Update: `UpdateIIESFinancialSupportRequest` — same.  
  Mismatch: nullable in validation, NOT NULL in DB for the two boolean columns.

---

### 3.2 `project_ILP_personal_info` (ILP Personal Info)

| Item         | Detail                                                              |
| ------------ | ------------------------------------------------------------------- |
| **Model**    | `App\Models\OldProjects\ILP\ProjectILPPersonalInfo`                 |
| **Table**    | `project_ILP_personal_info`                                         |
| **Fillable** | Includes `small_business_status`, plus name, age, gender, dob, etc. |

| Column                  | DB constraint                     | Write path                                                                                                       | Risk                                                                         |
| ----------------------- | --------------------------------- | ---------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------- |
| `small_business_status` | `boolean` NOT NULL, default false | `ILP\PersonalInfoController::store()` — `'small_business_status' => $validated['small_business_status'] ?? null` | **HIGH** — If checkbox/select not sent, NULL is written; column is NOT NULL. |
| Other columns           | nullable in migration             | Same — `?? null`                                                                                                 | Low.                                                                         |

**Write path:**

- `App\Http\Controllers\Projects\ILP\PersonalInfoController::store()` (and `update()` which delegates to `store()`) — `ProjectILPPersonalInfo::updateOrCreate()` with array including `small_business_status` => `?? null`.

**Migration:** `database/migrations/2024_10_24_024117_create_project_i_l_p_personal_infos_table.php` — `$table->boolean('small_business_status')->default(false);` (NOT NULL).

---

### 3.3 `project_IIES_immediate_family_details` (IIES Immediate Family Details)

| Item           | Detail                                                                                                                                                       |
| -------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Model**      | `App\Models\OldProjects\IIES\ProjectIIESImmediateFamilyDetails`                                                                                              |
| **Table**      | `project_IIES_immediate_family_details`                                                                                                                      |
| **Write path** | `IIESImmediateFamilyDetailsController::store`, `update` — `mapRequestToImmediateFamily()` sets booleans via `$model->$field = $request->has($field) ? 1 : 0` |

**Finding:** Boolean fields are **not** written with `?? null`. They are set from `$request->has($field) ? 1 : 0`, so NULL cannot slip through for these booleans. **No integrity risk** from this controller for the boolean columns.

---

### 3.4 `project_IES_immediate_family_details` (IES Immediate Family Details)

| Item           | Detail                                                                                                  |
| -------------- | ------------------------------------------------------------------------------------------------------- |
| **Model**      | `App\Models\OldProjects\IES\ProjectIESImmediateFamilyDetails`                                           |
| **Table**      | `project_IES_immediate_family_details`                                                                  |
| **Write path** | `IESImmediateFamilyDetailsController::store()` — `$familyDetails->fill($request->all());` then `save()` |

**Finding:**

- Migration: all boolean columns have `->default(false)` (NOT NULL).
- Controller uses `fill($request->all())`. If the request **does not** include a key (e.g. unchecked checkbox), that attribute is not set; on INSERT, Laravel omits the column and the DB default applies — **no NULL written**.
- If the request **does** include a key with value `null` or empty string (e.g. from another client or tampered payload), that value can be written. So risk is **medium**: depends on request content; not the same “missing key → null” pattern as the incident, but still a potential mismatch if client sends null for booleans.

---

### 3.5 `project_IIES_expenses` (IIES Expenses)

| Item           | Detail                                                                                                                         |
| -------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| **Model**      | `App\Models\OldProjects\IIES\ProjectIIESExpenses`                                                                              |
| **Table**      | `project_IIES_expenses`                                                                                                        |
| **Write path** | `IIESExpensesController::store()` — assigns `$validated['iies_total_expenses'] ?? null`, etc., then `$projectExpenses->save()` |

**Migration:** `2025_01_31_113236_create_project_i_i_e_s_expenses_table.php` — all decimal columns use `->default(0)` (NOT NULL).

| Column                           | DB constraint               | Write path              | Risk                                                            |
| -------------------------------- | --------------------------- | ----------------------- | --------------------------------------------------------------- |
| `iies_total_expenses`            | decimal NOT NULL, default 0 | `?? null` in controller | **MEDIUM** — Explicit NULL written if key missing; DB NOT NULL. |
| `iies_expected_scholarship_govt` | same                        | same                    | **MEDIUM**                                                      |
| `iies_support_other_sources`     | same                        | same                    | **MEDIUM**                                                      |
| `iies_beneficiary_contribution`  | same                        | same                    | **MEDIUM**                                                      |
| `iies_balance_requested`         | same                        | same                    | **MEDIUM**                                                      |

**Write path:** `App\Http\Controllers\Projects\IIES\IIESExpensesController::store()` (and update flow if similar assignment is used).

---

### 3.6 `project_IES_expenses` (IES Expenses)

| Item          | Detail                                          |
| ------------- | ----------------------------------------------- |
| **Model**     | `App\Models\OldProjects\IES\ProjectIESExpenses` |
| **Table**     | `project_IES_expenses`                          |
| **Migration** | All amount columns `->nullable()`               |

**Finding:** Controller uses `?? null` for amount fields; migration allows NULL. **Low risk** for integrity violation.

---

### 3.7 `project_IGE_budget` (IGE Budget)

| Item          | Detail                                                                                                                                                |
| ------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Migration** | All columns nullable (name, study_proposed, college_fees, hostel_fees, total_amount, scholarship_eligibility, family_contribution, amount_requested). |

**Finding:** `IGEBudgetController::store()` uses `?? null` for array-indexed fields; schema is nullable. **Low risk**.

---

### 3.8 `project_CCI_personal_situation` (CCI Personal Situation)

| Item          | Detail                           |
| ------------- | -------------------------------- |
| **Migration** | All data columns `->nullable()`. |

**Finding:** Controller uses `?? null` throughout; schema matches. **Low risk**.

---

### 3.9 Other project-related tables

- **project_budgets:** Controller uses `?? 0` or `?? ''` for numeric/string fields; migration uses nullable for most. No NOT NULL vs null-write conflict identified.
- **project_ILP_budget:** All columns nullable; controller uses `?? null`. Low risk.
- **project*IAH*\*:** Booleans in health_conditions and support_details are nullable in migrations; no NOT NULL boolean conflict identified in this audit.
- **project*IGE*\*:** IGE budget columns nullable; other IGE controllers not fully enumerated here but same principle: any NOT NULL column receiving `?? null` is a risk.

---

## 4. High-Risk Write Paths

### 4.1 Exact methods where NULL can slip in

| Location                                                        | Method     | Fields at risk                                                                                                                                   | Reason                                                                            |
| --------------------------------------------------------------- | ---------- | ------------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------- |
| `App\Http\Controllers\Projects\IIES\FinancialSupportController` | `store()`  | `govt_eligible_scholarship`, `other_eligible_scholarship`                                                                                        | `$validated['…'] ?? null` when key missing; DB NOT NULL.                          |
| Same                                                            | `update()` | Same                                                                                                                                             | Same.                                                                             |
| `App\Http\Controllers\Projects\ILP\PersonalInfoController`      | `store()`  | `small_business_status`                                                                                                                          | `$validated['small_business_status'] ?? null`; DB boolean NOT NULL default false. |
| `App\Http\Controllers\Projects\IIES\IIESExpensesController`     | `store()`  | `iies_total_expenses`, `iies_expected_scholarship_govt`, `iies_support_other_sources`, `iies_beneficiary_contribution`, `iies_balance_requested` | Each assigned with `?? null`; migration defines decimals NOT NULL default 0.      |

### 4.2 Why validation/defaults failed

- **Validation:** Rules mark these fields as `nullable` or omit them, so the request is valid even when the field is absent. Validation does not enforce “must be present for NOT NULL columns.”
- **Defaults:** Database defaults apply only when the column is **not** in the INSERT/UPDATE. The controller **includes** the column with value `null`, so the default is never used.
- **UI:** Radios/checkboxes without a default selection or `required` allow the user to submit without sending the key, which triggers `?? null` in code.

---

## 5. Recommended Fix Strategies

### 5.1 Validation fixes

- For every NOT NULL column (especially boolean/tinyint), ensure the write path never sends NULL.
- Option A: Add `required` (or presence) in validation so the key is always present; then normalize value (e.g. "0"/"1" → 0/1) before write.
- Option B: Keep field optional in validation but **never** pass null into the model for NOT NULL columns: use server-side default (see below) instead of `?? null`.

### 5.2 Default-value enforcement (in code, not in migration per audit constraint)

- For NOT NULL columns, do **not** use `?? null`. Use a type-appropriate default in the write path, e.g.:
    - Booleans: `$validated['other_eligible_scholarship'] ?? 0` (or cast "1"/"0" to int/boolean).
    - Decimals (when NOT NULL): `$validated['iies_total_expenses'] ?? 0` (or equivalent).
- Ensure this is applied in **all** write paths for that column (store, update, updateOrCreate, bulk updates).

### 5.3 Defensive coding in services/controllers

- Before calling `updateOrCreate` / `create` / `save`, normalize attributes: for any column that is NOT NULL in the schema, if the value is null or missing, set it to the same default as the migration (e.g. false/0) in code.
- Prefer a single place (e.g. a DTO, a model setter, or a “prepare attributes” helper) that applies these defaults so every caller benefits.

### 5.4 UI / request contract

- For boolean/radio fields that map to NOT NULL columns: either pre-select a value (e.g. “No”) or add `required` so the key is always present.
- Do not rely on “user always selects something” without server-side enforcement.

### 5.5 Migration adjustments (if needed later)

- This audit does not recommend changing migrations or adding defaults as part of the audit. If, after fixes, the product decision is to allow NULL in the DB, that would be a separate migration change; until then, the write path must satisfy NOT NULL.

---

## 6. Preventive Engineering Rules

1. **Every NOT NULL column must be set in ALL write paths**
    - Store, update, updateOrCreate, and any bulk/import path must supply a non-null value (or omit the column so the DB default applies). Never pass `null` for NOT NULL columns.

2. **Booleans must always default server-side**
    - For boolean/tinyint NOT NULL columns, use `?? 0` or `?? false` (or equivalent) in the write path when the key is missing. Do not use `?? null`.

3. **UI conditionals do NOT imply DB optionality**
    - If a field is conditionally shown or optional in the UI, the server must still ensure that for NOT NULL columns either a value is sent or a server-side default is applied. Optional in UI must not mean “allow NULL in DB” unless the column is actually nullable.

4. **Align validation with schema for NOT NULL columns**
    - If the column is NOT NULL, validation should either require the field or the write path must apply a default before persistence. Avoid `nullable` in validation for NOT NULL columns unless the write path guarantees a non-null value when the field is absent.

5. **Prefer `$request->validated()` and normalized attributes**
    - When building the array for create/update, use validated data and then explicitly set defaults for NOT NULL columns in code rather than relying on `$request->all()` and `?? null`.

6. **Audit any use of `?? null` against the schema**
    - For each `?? null` (or equivalent) in a write path, confirm that the target column is nullable in the database. If it is NOT NULL, replace with a type-appropriate default.

---

## Appendix: Key file references

| Purpose                                       | Path                                                                                              |
| --------------------------------------------- | ------------------------------------------------------------------------------------------------- |
| Incident controller                           | `app/Http/Controllers/Projects/IIES/FinancialSupportController.php`                               |
| Incident model                                | `app/Models/OldProjects/IIES/ProjectIIESScopeFinancialSupport.php`                                |
| Incident table migration                      | `database/migrations/2024_10_24_165620_create_project_i_i_e_s_scope_financial_supports_table.php` |
| Store request (IIES Financial Support)        | `app/Http/Requests/Projects/IIES/StoreIIESFinancialSupportRequest.php`                            |
| Update request (IIES Financial Support)       | `app/Http/Requests/Projects/IIES/UpdateIIESFinancialSupportRequest.php`                           |
| Create form (IIES scope financial support)    | `resources/views/projects/partials/IIES/scope_financial_support.blade.php`                        |
| Edit form                                     | `resources/views/projects/partials/Edit/IIES/scope_financial_support.blade.php`                   |
| ILP PersonalInfo write path                   | `app/Http/Controllers/Projects/ILP/PersonalInfoController.php`                                    |
| IIES Expenses write path                      | `app/Http/Controllers/Projects/IIES/IIESExpensesController.php`                                   |
| IIES Immediate Family (safe boolean handling) | `app/Http/Controllers/Projects/IIES/IIESImmediateFamilyDetailsController.php`                     |

---

_End of Write-Path Data Integrity Audit._
