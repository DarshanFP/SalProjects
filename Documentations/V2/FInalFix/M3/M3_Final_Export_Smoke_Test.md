# M3 — Export & PDF Smoke Test (Audit)

**Mode:** Audit only — no code changes.  
**Purpose:** Verify exports and PDF reflect canonical separation (non-approved: sanctioned = 0, amount_requested shown; approved: amount_sanctioned shown, amount_requested = 0).

---

## Canonical Rules (Reference)

- **Non-approved:** `amount_sanctioned` = 0; show `amount_requested` where applicable.
- **Approved:** show `amount_sanctioned`; `amount_requested` = 0.

---

## STEP 1 — Scan Results

### ExportController

| Location | What runs | Financial source |
|----------|-----------|------------------|
| **downloadDoc()** ~513–517 | Word export | `$resolvedFundFields = $this->financialResolver->resolve($project)`; passed to `addGeneralInfoSection($phpWord, $project, $projectRoles, $resolvedFundFields)`. |
| **addGeneralInfoSection()** ~601–645 | Writes Basic Information block | Uses only `$resolvedFundFields` for financial lines. **Stage-aware:** `if ($project->isApproved())` → one line "Amount Sanctioned: {amount_sanctioned}"; else → one line "Amount Requested: {amount_requested}". Also prints: overall_project_budget, amount_forwarded, opening_balance from resolvedFundFields. |
| **downloadPdf()** ~383–388 | PDF export | `$data = $this->projectDataHydrator->hydrate($project_id)`; then `view('projects.Oldprojects.pdf', $data)`. No direct DB read for fund fields in controller. |

**Other ExportController financial references:**  
- ~1791, 1948: type-specific (e.g. LDP target group `L_amount_requested`, ILP budget `amount_requested`) — from type tables, not project-level sanctioned/requested. Out of scope for project-level canonical parity.

---

### ProjectDataHydrator

| Location | What runs | Financial source |
|----------|-----------|------------------|
| **hydrate()** ~284 | Builds data for PDF / show | `$data['resolvedFundFields'] = $this->financialResolver->resolve($project)`. No direct DB read for fund fields; no inline formula. |

**Conclusion:** PDF and any view using hydrator get project-level financials only from resolver.

---

### pdf.blade.php (projects/Oldprojects/pdf.blade.php)

| Location | Financial output | Stage-aware? | Source |
|----------|------------------|--------------|--------|
| **Approval table** ~794–796 | One row: label and value | **Yes.** Label: `$project->isApproved() ? 'Amount approved (Sanctioned)' : 'Amount Requested'`. Value: `$project->isApproved() ? $resolvedFundFields['amount_sanctioned'] : $resolvedFundFields['amount_requested']`. | `$resolvedFundFields` (from hydrator → resolver). |
| **Contributions row** ~799–800 | Forwarded, Local | No stage branch; same for all. | `$resolvedFundFields['amount_forwarded']`, `$resolvedFundFields['local_contribution']`. |

**Conclusion:** Uses `$resolvedFundFields` only; no `$project->amount_sanctioned` or other DB direct read. Stage-aware label and value for sanctioned/requested.

---

### Word export logic (addGeneralInfoSection)

| Field printed | Source | Stage behavior |
|---------------|--------|----------------|
| Overall Project Budget | `$resolvedFundFields['overall_project_budget']` | Same for all. |
| Amount Forwarded | `$resolvedFundFields['amount_forwarded']` | Same for all. |
| Amount Sanctioned | `$resolvedFundFields['amount_sanctioned']` | **Shown only when `$project->isApproved()`.** |
| Amount Requested | `$resolvedFundFields['amount_requested']` | **Shown only when not approved.** |
| Opening Balance | `$resolvedFundFields['opening_balance']` | Same for all. |

No fallback formula; no direct DB read in this method.

---

### Budget export (BudgetExportController + budget-pdf.blade.php)

| Location | Financial source | Stage behavior |
|----------|-------------------|----------------|
| **BudgetExportController** ~73–74, 201–202 | `$budgetSummary = BudgetValidationService::getBudgetSummary($project)`; `$budgetData = $budgetSummary['budget_data']`. | BudgetValidationService uses ProjectFinancialResolver internally; `budget_data['amount_sanctioned']` is resolver output (0 for non-approved). |
| **budget-pdf.blade.php** ~186–202 | Rows: Overall Project Budget, Amount Forwarded, Local Contribution, **Amount Sanctioned**, Opening Balance, etc. | Single row "Amount Sanctioned" with `$budgetData['amount_sanctioned']`. For non-approved this is 0 (resolver). Label is not stage-aware (always "Amount Sanctioned"); value is canonical (0 when not approved). No separate "Amount Requested" row. |

**Conclusion:** Budget PDF uses resolver-derived data via BudgetValidationService; no DB bypass. Non-approved projects show 0 for Amount Sanctioned; no inline formula.

---

## STEP 2 — Confirmations

| Check | Result |
|-------|--------|
| **No inline fallback formulas** (e.g. `sanctioned ?? (budget - forwarded - local)` in export/PDF path) | **Pass.** No such pattern found in ExportController, ProjectDataHydrator, or pdf.blade.php. Budget export uses BudgetValidationService (resolver-based). |
| **Uses resolvedFundFields** | **Pass.** Word: addGeneralInfoSection uses only `$resolvedFundFields`. PDF: view receives `$data` from ProjectDataHydrator, which sets `resolvedFundFields` from resolver. Budget PDF uses `$budgetData` from BudgetValidationService (resolver). |
| **Stage awareness preserved** | **Pass.** Word: one of "Amount Sanctioned" or "Amount Requested" printed by `$project->isApproved()`. PDF: label and value both branch on `$project->isApproved()`. Budget PDF: single "Amount Sanctioned" row with resolver value (0 for non-approved). |
| **No DB direct reads bypassing resolver** | **Pass.** ExportController does not read `$project->amount_sanctioned` or `$project->opening_balance` for export content. ProjectDataHydrator sets resolvedFundFields from resolver only. pdf.blade.php uses only `$resolvedFundFields` and `$project->isApproved()`. |

---

## STEP 3 — Financial Fields Printed

### Word export (addGeneralInfoSection)

- Overall Project Budget  
- Amount Forwarded  
- Amount Sanctioned (if approved) **or** Amount Requested (if not approved)  
- Opening Balance  

All from `$resolvedFundFields`; stage decides which of sanctioned/requested is printed.

### PDF (Oldprojects/pdf.blade.php) — approval block

- Row 1: Label "Amount approved (Sanctioned)" or "Amount Requested"; value `amount_sanctioned` or `amount_requested` from `$resolvedFundFields` (stage-aware).  
- Row 2: Forwarded, Local from `$resolvedFundFields`.

### Budget PDF (projects/exports/budget-pdf.blade.php)

- overall_budget, amount_forwarded, local_contribution, **amount_sanctioned**, opening_balance, total_expenses, remaining_balance, percentage_used.  
- All from `$budgetData` (BudgetValidationService → resolver). amount_sanctioned = 0 for non-approved; label always "Amount Sanctioned".

---

## Inline Math Remaining

- **ExportController / ProjectDataHydrator / pdf.blade.php:** None.  
- **Budget export:** Uses BudgetValidationService; no inline formula in controller or budget-pdf view.  
- **Other views** (e.g. partials/not working show/general_info, OLdshow/general_info) that use `$project->amount_sanctioned` or `$project->opening_balance` are outside the export/PDF canonical parity scope for this audit; they were not modified in M3.7 Phase 2 export alignment.

---

## Risk Classification

| Area | Risk | Notes |
|------|------|--------|
| Word export | **Low** | Resolver only; stage-aware; no fallback; no DB direct read. |
| Project PDF (Oldprojects/pdf) | **Low** | resolvedFundFields from hydrator → resolver; stage-aware label and value. |
| Budget PDF | **Low** | Resolver via BudgetValidationService; amount_sanctioned is 0 for non-approved. Label not stage-aware (always "Amount Sanctioned"); value is correct. |
| ProjectDataHydrator | **Low** | Single source: resolver; no inline math; no DB bypass. |

**Summary:** Export and PDF paths use resolver (or resolver-derived budget_data); stage awareness is present for Word and project PDF; no inline fallback formulas or DB direct reads for project-level fund fields in the scanned export/PDF code.

---

**M3 Export Smoke Test Complete — No Code Changes**
