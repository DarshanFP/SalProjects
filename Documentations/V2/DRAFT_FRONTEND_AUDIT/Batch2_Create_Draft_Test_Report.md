# Create Draft Save Test Report

**Scope:** Create flow draft save after Batch 2 removal and Batch 3 alignment (GIC uses validated(), StoreProjectRequest requires project_type always).  
**Type:** Simulation / code-trace only. No code was modified.  
**Date:** 2026-02-10

---

## 1. Scenario Results

### CASE 1 – Minimal Draft Save (all business fields empty)

| Step | Simulated behavior |
|------|---------------------|
| **User action** | Leaves project_title, project_type, society_name, overall_project_budget, in_charge empty; clicks “Save Project” (draft). Form has no HTML5 `required` (Batch 2). |
| **HTML5** | **PASS.** Form submits; no browser validation block. |
| **Request payload** | save_as_draft=1, project_type='' or missing, project_title='' or missing, society_name='' or missing, overall_project_budget='' or missing, in_charge='' or missing. |
| **StoreProjectRequest** | **FAIL (validation).** `project_type` is **required|string|max:255** (Batch 3 correction — always required). Empty/missing project_type fails validation. User is redirected back with validation error (e.g. “Project type is required.”). |
| **GeneralInfoController::store** | Not reached. |
| **DB write** | No project created. |
| **Status** | N/A. |

**CASE 1 verdict:** **FAIL** for the original expectation (“Backend accepts, Project created, Status = DRAFT”). After Batch 3, **project_type is structurally required** for create (including draft); minimal draft with no project type is correctly rejected by validation. So behavior is **by design**: user must select project type before saving as draft.

---

### CASE 2 – Partial Draft Save (project_title only, project_type empty)

| Step | Simulated behavior |
|------|---------------------|
| **User action** | Fills project_title only; leaves project_type, overall_project_budget, in_charge empty; Save as draft. |
| **HTML5** | **PASS.** Form submits. |
| **StoreProjectRequest** | **FAIL (validation).** project_type is required; empty project_type fails. Validation error returned; no project created. |
| **Partial data saved** | Not applicable; request rejected at validation. |

**CASE 2 verdict:** **FAIL** for “Partial data saved” when project_type is missing. Aligns with rule that project_type is required on create (including draft). To save a partial draft, user must at least select project_type; other fields (project_title, overall_project_budget, in_charge) remain nullable.

---

### CASE 3 – Budget Zero Edge Case (overall_project_budget = 0, others empty)

| Step | Simulated behavior |
|------|---------------------|
| **User action** | Sets overall_project_budget = 0; leaves project_type and other business fields empty; Save as draft. |
| **StoreProjectRequest** | **FAIL (validation).** project_type is required; empty project_type fails. Validation error; no project created. |
| **If project_type were filled** | Then validation would pass. validated() would include project_type and overall_project_budget = 0. GeneralInfoController::store would apply in_charge default, and `$validated['overall_project_budget'] ?? 0.00` would **preserve 0** (key present). Project::create would succeed; status = DRAFT. So **0 is preserved** when project_type (and any other required structure) is provided. |

**CASE 3 verdict:** **FAIL** for the scenario as stated (other fields empty, including project_type) — validation fails. **PASS** for the budget-zero behavior when project_type is provided: 0 is preserved, no overwrite, no validation error for budget, status = DRAFT.

---

## 2. Request Payload Observed (Simulated)

| Scenario | save_as_draft | project_type | project_title | overall_project_budget | in_charge |
|----------|----------------|--------------|---------------|------------------------|-----------|
| CASE 1 | 1 | ''/missing | ''/missing | ''/missing | ''/missing |
| CASE 2 | 1 | ''/missing | filled | ''/missing | ''/missing |
| CASE 3 | 1 | ''/missing | ''/missing | 0 | ''/missing |

When project_type is filled and other fields are partial or zero, request passes StoreProjectRequest and GIC applies defaults (in_charge, overall_project_budget ?? 0.00) before create.

---

## 3. Validated() Output Snapshot

**When validation passes (e.g. project_type filled, draft):**

- StoreProjectRequest validated() contains project_type (required), plus any provided nullable fields (project_title, society_name, in_charge, overall_project_budget, etc.). overall_project_budget = 0 is included as 0.
- GeneralInfoController::store uses that validated array, adds user_id, status = DRAFT, commencement_month_year, defaults for in_charge and overall_project_budget when missing; 0 is not overwritten by ?? 0.00 when key is present.

**When validation fails (project_type empty):**

- No validated() snapshot is produced for create; user is redirected back with errors.

---

## 4. DB Write Behavior

| Scenario | Reached Project::create? | project_type | in_charge | overall_project_budget |
|----------|---------------------------|--------------|-----------|-------------------------|
| CASE 1 (all empty) | No (validation fails) | — | — | — |
| CASE 2 (project_type empty) | No (validation fails) | — | — | — |
| CASE 3 (project_type empty) | No (validation fails) | — | — | — |
| Draft with project_type + budget=0 | Yes | from request | Auth::id() default if missing | 0 (preserved) |

When create runs, NOT NULL columns are satisfied: project_type from validated, in_charge from validated or Auth::id(), overall_project_budget from validated or 0.00. No SQL NOT NULL violation when validation has passed.

---

## 5. Status After Save

- **CASE 1, 2, 3 (with project_type empty):** No project created; no status.
- **When validation passes and create runs:** Project is created with status = DRAFT (set in GeneralInfoController and in applyPostCommitStatusAndRedirect).

---

## 6. Errors (if any)

- **CASE 1:** Validation error from StoreProjectRequest — “Project type is required.” (or equivalent). Redirect back with errors.
- **CASE 2:** Same validation error (project_type required).
- **CASE 3 (others empty):** Same validation error.
- **No SQL errors** in these flows (create not reached).
- **When project_type is provided:** No validation or SQL errors; defaults and 0 handling behave as above.

---

## 7. Stability Verdict

**PASS** (for defined Create draft behavior after Batch 3)

- **project_type required:** StoreProjectRequest correctly requires project_type on every create (including draft). Minimal or partial draft without project type is rejected with a validation error; no project created, no SQL error.
- **Draft with project_type selected:** Other business fields (project_title, society_name, in_charge, overall_project_budget) remain nullable. Partial draft with project_type filled is accepted; GeneralInfoController::store applies defaults (in_charge, overall_project_budget) and creates project with status = DRAFT.
- **Budget zero:** When overall_project_budget = 0 is sent and project_type is provided, 0 is preserved (not overwritten), and create succeeds with status = DRAFT.

Create draft behavior is stable and aligned with the rule that project_type is structurally required; other fields stay optional for draft, with correct defaults and no NOT NULL violations when create runs.

---

**End of report. No code was modified.**
