# IIES Update Flow — Regression Root Cause Analysis (M3 / M3.7)

**Objective:** Identify what changed during M3 / M3.7 that could cause IIES update to redirect to edit instead of completing.

**Baseline used for “Before M3”:** `23acc5c` (Project updates: controllers, views, models, cleanup…).  
**“After M3”:** `HEAD` (current state).  
*(M3 docs under `Documentations/V2/FInalFix/M3/` appear from later commits; controller/validation refactors are in 5842a61, 92d17ec, 96acb20, d0aea98.)*

---

## Step 1 — IIESEducationBackgroundController@update

| Check | Result |
|-------|--------|
| Does it return `redirect()`? | **No.** It returns `$this->store($request, $projectId)`, which returns `response()->json([...], 200)`. |
| Does it call `back()`? | **No.** |
| Does it use `$request->validate()`? | **No.** It uses `FormDataExtractor::forFillable($request, $fillable)` and model `fill($data)` — no validation in controller. |
| Are any fields required? | **No.** Store/UpdateIIESEducationBackgroundRequest define only `nullable` rules; the controller does not use those FormRequests — it type-hints generic `FormRequest` and extracts by model fillable. |

**Conclusion:** EducationBackgroundController does **not** redirect, does **not** validate in-code, and has no required fields. It is an unlikely direct cause of “redirect to edit” unless `FormDataExtractor` or `fill()` throws (e.g. type error), which would be caught by ProjectController and cause `redirect()->back()`.

---

## Step 2 — Git history (diffs)

### 1) IIESEducationBackgroundController.php (23acc5c → HEAD)

- **store():**
  - **Before:** Used `$request->all()`, `DB::beginTransaction` / `commit` / `rollBack`, and on exception **returned** `response()->json(['error' => ...], 500)`.
  - **After:** Uses `FormDataExtractor::forFillable()` and model `first()` / `fill()` / `save()`; **no** transaction in store; on exception **re-throws** `throw $e` (no longer returns JSON).
- **update():**
  - **Before:** Inline update logic with `$request->all()`, transaction, and on failure returned JSON 500.
  - **After:** Delegates to `return $this->store($request, $projectId)` (single line).

So the **update controller was modified**: it no longer catches exceptions and returns JSON; exceptions bubble to ProjectController, which then does `redirect()->back()` on generic `\Exception`. Education background itself still has no validation in controller, so the main behavioral change here is **exception handling** (re-throw vs return JSON), not validation.

### 2) IIESExpensesController.php (23acc5c → HEAD)

- **store():**
  - **Before:** Used `$request->all()` only — **no validation**. Wrapped in transaction; on failure returned JSON 500.
  - **After:** Uses **StoreIIESExpensesRequest** + `Validator::make($normalized, $formRequest->rules())` + **`$validator->validate()`**. Added BudgetSyncGuard (can throw `HttpResponseException` 403). Removed transaction; added BudgetSyncService sync and “meaningfully filled” guard.

So **validation was added** in M3. Rules are conditional on `save_as_draft`: when `save_as_draft` is not truthy, five decimal fields are **required**; when it is (e.g. edit form sends `save_as_draft=1`), they are **nullable**. Form field names in the edit blade match the rule keys (`iies_total_expenses`, `iies_expected_scholarship_govt`, etc.). If validation fails (e.g. wrong type, bounds), `ValidationException` is thrown → ProjectController re-throws → Laravel redirects back to edit.

### 3) FinancialSupportController.php (23acc5c → HEAD)

- **store() / update():**
  - **Before:** Used `$request->all()` only — **no validation**. Transaction; on failure returned JSON 500.
  - **After:** Uses **StoreIIESFinancialSupportRequest** / **UpdateIIESFinancialSupportRequest** + `Validator::make()` + **`$validator->validate()`**. No transaction; direct `updateOrCreate`.

So **validation was added** in M3. Rules include **required|boolean** for:
- `govt_eligible_scholarship`
- `other_eligible_scholarship`

Edit blade uses radios with `name="govt_eligible_scholarship"` and `name="other_eligible_scholarship"` (values `1`/`0`). If **no radio is selected**, the key is **absent** from the request → **validation fails** → `ValidationException` → redirect back to edit. This is a strong regression candidate for “redirect to edit” when the Financial Support section is untouched or both radios are missing (e.g. new/empty record, or DOM not sending the names).

### 4) resources/views/projects/partials/Edit/IIES/ (23acc5c → HEAD)

- Only **attachments.blade.php** changed: view/download links switched from `Storage::url($file->file_path)` to named routes `projects.iies.attachments.view` and `projects.iies.attachments.download`. No form field name or structure changes.

---

## Step 3 — Validation failure pattern (search)

- **IIES controllers:** No `redirect()`, no `back()`, no `$request->validate()` in code. They use `Validator::make(...)->validate()` in:
  - IIESFamilyWorkingMembersController
  - IIESExpensesController (store)
  - FinancialSupportController (store + update)
- **IIESAttachmentsController** uses `throw ValidationException::withMessages(...)`.
- **ProjectController@update** on `ValidationException`: `DB::rollBack(); throw $e;` → Laravel’s handler redirects **back** (to edit) with errors. On generic `\Exception`: `return redirect()->back()->withErrors([...])->withInput();`.

So the “redirect to edit” path is: **sub-controller throws (usually ValidationException) → ProjectController re-throws or catches → redirect back**.

---

## Step 4 — Form field names vs validation rules

| Section | Form (Edit blade) | Validation rules (expected keys) | Match / notes |
|--------|-------------------|-----------------------------------|----------------|
| Education background | `prev_education`, `prev_institution`, `prev_insti_address`, `prev_marks`, `current_studies`, `curr_institution`, `curr_insti_address`, `aspiration`, `long_term_effect` | Same keys in Store/UpdateIIESEducationBackgroundRequest (all nullable). Controller does **not** use these requests; it uses FormDataExtractor + model fillable. | Names match; controller does not run these rules. |
| Estimated expenses | `iies_total_expenses`, `iies_expected_scholarship_govt`, `iies_support_other_sources`, `iies_beneficiary_contribution`, `iies_balance_requested`, `iies_particulars[]`, `iies_amounts[]` | StoreIIESExpensesRequest: same keys; required vs nullable depending on `save_as_draft`. | Names match. Edit form adds `save_as_draft=1` on submit → nullable used; empty decimals can pass. |
| Financial support | `govt_eligible_scholarship`, `scholarship_amt`, `other_eligible_scholarship`, `other_scholarship_amt`, `family_contrib`, `no_contrib_reason` | Store/UpdateIIESFinancialSupportRequest: **required|boolean** for `govt_eligible_scholarship` and `other_eligible_scholarship`; others nullable. | Names match. **Risk:** If neither radio is selected for either field, keys are **absent** → required fails → redirect to edit. |

So the only **required** rules that can fail due to **missing** input (without any field rename) are the two Financial Support booleans when no radio is selected.

---

## REPORT — Summary answers

| # | Question | Answer |
|---|----------|--------|
| 1 | Was update controller modified during M3? | **Yes.** EducationBackgroundController: update() now delegates to store(); store() uses FormDataExtractor, no transaction, and **re-throws** exceptions instead of returning JSON 500. IIESExpensesController and FinancialSupportController were refactored to use FormRequest + Validator + validate() and no longer return JSON on failure. |
| 2 | Was validation modified? | **Yes.** IIESExpensesController and FinancialSupportController **gained** validation (Validator::make + validate()). Previously they used only `$request->all()`. Financial Support has **required|boolean** for two radios; Expenses has conditional required when `save_as_draft` is false. |
| 3 | Were form input names changed? | **No.** Edit blade diffs show no change to form field names for education background, expenses, or financial support. Only IIES attachments view changed (links, not inputs). |
| 4 | Does update controller return redirect? | **No.** ProjectController@update returns redirect only on **success** (`redirect()->route('projects.index')`) or in **catch** blocks (redirect back on exception). No IIES sub-controller returns redirect. |
| 5 | Does any sub-controller return redirect? | **No.** All IIES sub-controllers return `response()->json(...)`. The “redirect to edit” is caused by **thrown exceptions** (ValidationException or other) and ProjectController’s/Laravel’s exception handling (re-throw or `redirect()->back()`). |

---

## Root cause (regression)

- **Primary:** **Validation added in M3** in IIES sub-controllers (especially **FinancialSupportController** and **IIESExpensesController**). When validation fails, they throw `ValidationException`; ProjectController re-throws it, and Laravel redirects **back** to the previous URL (edit page) with errors.
- **Most likely trigger:** **Financial Support** — `govt_eligible_scholarship` and `other_eligible_scholarship` are **required|boolean**. If the request does not contain these keys (e.g. no radio selected in the edit form), validation fails and the user is sent back to edit.
- **Secondary:** EducationBackgroundController now **re-throws** exceptions instead of returning JSON; any exception there (e.g. from FormDataExtractor or save) is caught by ProjectController and results in `redirect()->back()`.

**Recommendation:** For the full-project update flow (ProjectController@update), either: (1) relax or remove **required** on the two Financial Support radios when the section is optional, or (2) ensure the edit form always sends a value (e.g. default checked radio or hidden input) so the keys are never missing. Optionally, run validation in a single place (e.g. UpdateProjectRequest) and have IIES sub-controllers only perform DB updates without throwing ValidationException, so one section’s rules do not abort the whole update.
