# IIES Validation Regression — Stability Patch

**Type:** Controlled stabilization (regression fix only).  
**Scope:** Financial Support validation introduced in M3.  
**No feature expansion. No resolver changes. No controller restructuring.**

---

## 1) Root Cause Summary

During M3, `FinancialSupportController` was refactored to use `StoreIIESFinancialSupportRequest` and `UpdateIIESFinancialSupportRequest` with **required|boolean** for:

- `govt_eligible_scholarship`
- `other_eligible_scholarship`

The edit form uses radio buttons for these fields. When **no radio is selected** (e.g. new/empty record or section untouched), the keys are **absent** from the request. Validation then fails with "The govt. eligible scholarship field is required" (or the other field), `ValidationException` is thrown, and Laravel redirects **back** to the edit page. This caused the IIES update flow to appear to "redirect to edit instead of completing."

Relaxing these two rules to **nullable|boolean** and applying defensive defaults in the controller restores update stability without changing behavior when the user does submit values.

---

## 2) Validation Before vs After

| Field | Before (M3) | After (patch) |
|-------|-------------|----------------|
| `govt_eligible_scholarship` | `required\|boolean` | `nullable\|boolean` |
| `other_eligible_scholarship` | `required\|boolean` | `nullable\|boolean` |
| `scholarship_amt` | unchanged | unchanged |
| `other_scholarship_amt` | unchanged | unchanged |
| `family_contrib` | unchanged | unchanged |
| `no_contrib_reason` | unchanged | unchanged |

**Files changed:**

- `app/Http/Requests/Projects/IIES/StoreIIESFinancialSupportRequest.php` — rules only.
- `app/Http/Requests/Projects/IIES/UpdateIIESFinancialSupportRequest.php` — rules only.
- `app/Http/Controllers/Projects/IIES/FinancialSupportController.php` — defensive defaults in `store()` and `update()` before passing to `updateOrCreate()`.

---

## 3) Why the Change Is Safe

- **Semantics:** The section is "Information on Scope of Receiving Financial Support." Treating the two eligibility fields as optional when absent matches the case where the user leaves the section blank or does not change the radios; storing `0` (No) when missing is a conservative, consistent default.
- **DB:** Existing columns accept 0/1/null; no schema change. Defaulting missing values to `0` in the controller keeps writes consistent.
- **Normalizer:** `BooleanNormalizer::toInt()` still runs only when the key exists in input; with nullable, missing keys no longer cause validation to fail. The controller defaults apply only when the key is missing after validation.
- **Scope:** Only the two boolean rules and the controller payload are touched. No other validation rules, no resolver, no ProjectController, no dashboard, no exports.

---

## 4) Test Checklist

- [ ] **IIES edit — full update (no Financial Support change):** Open an IIES project edit, do not touch Financial Support section, click Save Changes. Expect: redirect to project list with success; no redirect back to edit.
- [ ] **IIES edit — update with Financial Support:** Set both radios (e.g. Yes/No), fill amounts if desired, save. Expect: success; stored values match form.
- [ ] **IIES edit — one radio missing:** Leave one of the two eligibility radios unchecked, save. Expect: success; stored value for missing field is 0.
- [ ] **IIES create (if applicable):** Create new IIES project with Financial Support section; submit with radios selected and with radios left unselected. Expect no validation redirect in both cases.
- [ ] **Standalone Financial Support route (if any):** If this section is also submitted via a dedicated route (e.g. AJAX), repeat same scenarios; no regression.

---

## 5) Confirmation — Resolver Untouched

- **ProjectFinancialResolver:** Not modified.
- **DirectMappedIndividualBudgetStrategy / PhaseBasedBudgetStrategy:** Not modified.
- **Canonical separation / applyCanonicalSeparation:** Not modified.
- **ProjectController@update orchestration:** Not modified.
- **Dashboard, exports, DB schema:** Not modified.

Only the two IIES Financial Support FormRequests and `FinancialSupportController` (defensive defaults) were changed.
