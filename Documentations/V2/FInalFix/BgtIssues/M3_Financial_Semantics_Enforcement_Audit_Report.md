# Financial Semantics Enforcement Audit Report (READ-ONLY)

**Mode:** Financial Semantics Enforcement Audit  
**Objective:** Ensure for ALL project types: amount_sanctioned is never non-zero for non-approved projects; amount_requested is explicitly derived and used; no view displays sanctioned before approval inappropriately; no aggregation mixes requested as sanctioned.  
**Date:** 2026-02-17

---

## Section A — Resolver Violations

### A.1 DirectMappedIndividualBudgetStrategy

| Check | Result | Details |
|-------|--------|---------|
| Non-approved returns requested as sanctioned? | **PASS** | For non-approved (lines 54–56): `amount_requested` is set from the type-derived value (previously in `amount_sanctioned` key), and `amount_sanctioned` is explicitly set to `0.0`. Does **not** return requested as sanctioned. |
| Derives sanctioned for non-approved? | **PASS** | Non-approved branch sets `amount_sanctioned = 0.0`; sanctioned is not derived from DB for non-approved. |

**Note:** Type-specific methods (resolveIIES, resolveIES, etc.) return a key `amount_sanctioned` from type tables (e.g. balance_requested, amount_requested). The post-processing block (lines 49–57) overwrites this so that for non-approved the final output has `amount_sanctioned = 0` and `amount_requested` = that value. No violation.

### A.2 PhaseBasedBudgetStrategy

| Check | Result | Details |
|-------|--------|---------|
| Non-approved derives sanctioned from DB? | **PASS** | For non-approved (lines 54–58): `$sanctioned = 0.0`, `$requested = max(0, calculateAmountSanctioned(...))`. Sanctioned is not read from `$project->amount_sanctioned` for non-approved. |
| amount_requested derivation | **PASS** | `amount_requested` = max(0, overall - (forwarded + local)); not mixed with sanctioned. |

### A.3 ProjectFinancialResolver

| Check | Result | Details |
|-------|--------|---------|
| applyCanonicalSeparation non-approved | **PASS** | Non-approved: `amount_sanctioned => 0.0`, `amount_requested` and `opening_balance` set per spec. |
| assertFinancialInvariants | **PASS** | Logs critical if non-approved has DB `amount_sanctioned > 0`; logs warning if resolved sanctioned ≠ 0 for non-approved. |

**Resolver summary:** No resolver violations. Both strategies and the scaffold resolver enforce sanctioned = 0 for non-approved and separate requested from sanctioned.

---

## Section B — View Violations

### B.1 Views showing sanctioned without approval guard (or using raw DB)

| Location | Type | Issue | Executes when project not approved? |
|----------|------|-------|-------------------------------------|
| `resources/views/projects/partials/Show/general_info.blade.php` | Display | Shows two rows: "Amount Requested" and "Amount Sanctioned". Uses `$rf['amount_sanctioned']` and `$rf['amount_requested']` from resolver. For non-approved, sanctioned = 0. **No approval guard to hide "Amount Sanctioned" row** — row is always shown (value 0 when not approved). Semantically correct; presentational preference only. | Yes; value is resolver-driven (0 for non-approved). |
| `resources/views/projects/partials/not working show/general_info.blade.php` | Display | **Violation.** Direct DB: `{{ number_format($project->amount_sanctioned, 2) }}`. No resolver; no approval guard. Non-approved could show stale/non-zero if DB invariant broken. | Yes. |
| `resources/views/projects/partials/OLdshow/general_info.blade.php` | Display | **Violation.** Same as above: `{{ number_format($project->amount_sanctioned, 2) }}`. | Yes. |
| `resources/views/reports/monthly/developmentProject/reportform.blade.php` | Display / Form | **Violation.** `value="{{ $project->amount_sanctioned }}"` and `value="{{ $project->amount_sanctioned + $project->amount_forwarded }}"`. Uses raw DB; no resolver; no approval guard. If form is shown for non-approved, shows DB value (should be 0 per invariant). | Yes. |
| `resources/views/projects/partials/Edit/budget.blade.php` | Display (edit form) | Uses `$project->amount_sanctioned ?? 0` for "Amount Sanctioned (To Request)" preview. No resolver. For non-approved/individual types may show 0 or stale. Label indicates "To Request" so intent is requested amount; key is still raw DB. | Yes. |
| `resources/views/projects/Oldprojects/pdf.blade.php` | Display | **PASS.** Stage-aware: `$project->isApproved() ? ... amount_sanctioned : ... amount_requested`. Uses resolver output. | N/A — guarded. |
| `resources/views/provincial/ProjectList.blade.php` | Display | **PASS.** Per-row: `$project->isApproved() ? amount_sanctioned : amount_requested`. Grand total uses controller-provided `$grandTotals` which are status-separated (see Section C). | N/A — guarded. |

### B.2 Export (Word) — ExportController

| Location | Result | Details |
|----------|--------|---------|
| `app/Http/Controllers/Projects/ExportController.php` (addGeneralInfoSection) | **PASS** | Lines 633–641: `if ($project->isApproved())` show "Amount Sanctioned" with `$resolvedFundFields['amount_sanctioned']`; else show "Amount Requested" with `$resolvedFundFields['amount_requested']`. Approval guard present. |

### B.3 Other Blade references (no violation or dev/sample only)

- `resources/views/projects/partials/Show/budget.blade.php`: Uses `BudgetValidationService::getBudgetSummary($project)` which uses **ProjectFinancialResolver** — resolver-driven; no violation.
- `resources/views/dev/table_component_preview.blade.php`, `resources/views/admin/budget_reconciliation/*`: Sample or admin tooling; not user-facing project show.
- Report monthly partials (individual_education, individual_health, etc.): Use report-level `amount_sanctioned_overview` / account detail; context is reports (typically post-approval). Not project-level sanctioned display without guard in this audit scope.

**View summary:** Violations: (1) `partials/not working show/general_info.blade.php`, (2) `partials/OLdshow/general_info.blade.php`, (3) `reports/monthly/developmentProject/reportform.blade.php` use raw `$project->amount_sanctioned` without resolver or approval guard. (4) `partials/Edit/budget.blade.php` uses raw DB for preview field. Show/general_info displays "Amount Sanctioned" row for all projects with resolver value (0 when not approved) — no approval guard to hide row, but value is correct.

---

## Section C — Aggregation Violations

### C.1 Provincial project list — ProvincialController + ProjectList.blade.php

| Location | Result | Details |
|----------|--------|---------|
| `app/Http/Controllers/ProvincialController.php` (grandTotals) | **PASS** | Lines 510–514: `if ($project->isApproved()) { $grandTotals['amount_sanctioned'] += ... } else { $grandTotals['amount_requested'] += ... }`. Sanctioned aggregated only for approved; requested only for non-approved. Status filter applied. |
| `resources/views/provincial/ProjectList.blade.php` (grand total display) | **PASS** | Displays `$grandTotals['amount_sanctioned']` and per-row value; totals come from controller with status separation. |

### C.2 Coordinator dashboard

| Location | Result | Details |
|----------|--------|---------|
| `app/Http/Controllers/CoordinatorController.php` line 151 | **Risk** | `'projects_with_amount_sanctioned' => $projects->where('amount_sanctioned', '>', 0)->count()`. Uses **raw DB** `amount_sanctioned > 0` with **no explicit status = approved** filter. Relies on invariant that only approved projects have non-zero sanctioned; if any non-approved project has legacy non-zero in DB, they would be counted. Semantic: "projects that have some sanctioned amount." |

### C.3 Report services (Annual, HalfYearly, Quarterly)

| Location | Result | Details |
|----------|--------|---------|
| `app/Services/Reports/AnnualReportService.php`, HalfYearlyReportService, QuarterlyReportService | **Context-dependent** | Aggregate `amount_sanctioned_overview` and `detail.amount_sanctioned` from **reports** (monthly/quarterly), not directly from projects. No project status filter in aggregation; assumed context is approved reports. Not classified as project-level aggregation mixing requested as sanctioned. |

**Aggregation summary:** One risk: CoordinatorController dashboard metric counts projects by raw `amount_sanctioned > 0` without explicit approved filter. Provincial list aggregation is correctly status-separated.

---

## Section D — Persistence Violations

### D.1 Writes to project amount_sanctioned

| Location | When | Violation? | Details |
|----------|------|------------|---------|
| `app/Http/Controllers/CoordinatorController.php` ~1134 | On coordinator approval | **No** | After resolver resolve; project is being approved; `$project->amount_sanctioned = $amountSanctioned` then save. Only runs in approval flow. |
| `app/Http/Controllers/GeneralController.php` ~2647 | On general approving as coordinator | **No** | Same pattern; persistence only on approval. |
| `app/Services/ProjectStatusService.php` ~244 | On revert | **No** | Sets `$project->amount_sanctioned = 0` on revert; correct. |
| `app/Services/Budget/BudgetSyncService.php` | syncFromTypeSave / syncBeforeApproval | **No** | Type save: does not write amount_sanctioned (TYPE_SAVE_FIELDS excludes it). Before approval: uses PRE_APPROVAL_FIELDS_WITHOUT_SANCTIONED when project is not approved (lines 119–122); never writes amount_sanctioned for non-approved. |

### D.2 Non-zero sanctioned for non-approved (invariant)

- Resolver and strategies never return non-zero sanctioned for non-approved.
- Persistence only sets amount_sanctioned on approval (or 0 on revert). BudgetSyncService does not write amount_sanctioned for non-approved.
- **Conclusion:** No persistence violation identified. If DB has non-zero amount_sanctioned for non-approved, it is legacy/invariant breach; resolver and assertFinancialInvariants log it.

**Persistence summary:** No violations. amount_sanctioned is only written on approval (from resolver) or set to 0 on revert; draft/type save paths do not write it.

---

## Section E — Risk Level

| Category | Risk level | Summary |
|----------|------------|---------|
| **Resolver** | **Low** | No violations. DirectMappedIndividualBudgetStrategy and PhaseBasedBudgetStrategy both enforce sanctioned = 0 and separate requested for non-approved. ProjectFinancialResolver applyCanonicalSeparation and invariants are correct. |
| **Views** | **Medium** | Four view usages: two legacy partials and report form use raw `$project->amount_sanctioned` without resolver or approval guard; one edit partial uses raw DB for preview. Main Show general_info uses resolver (value 0 when not approved) but always shows "Amount Sanctioned" row. |
| **Aggregation** | **Low–Medium** | Provincial list correctly separates sanctioned/requested by status. Coordinator dashboard metric uses raw DB count by amount_sanctioned > 0 without explicit approved filter — low risk if DB invariant holds. |
| **Persistence** | **Low** | No non-approved write to amount_sanctioned; approval and revert flows only. |

### Recommended follow-up (no code changes in this audit)

1. **Views:** Replace raw `$project->amount_sanctioned` with resolver output (or approval guard) in: `partials/not working show/general_info.blade.php`, `partials/OLdshow/general_info.blade.php`, `reports/monthly/developmentProject/reportform.blade.php`, and consider resolver for `partials/Edit/budget.blade.php` preview.
2. **Dashboard:** Consider making coordinator dashboard metric explicitly filter by approved status when counting "projects with sanctioned amount," or use resolver and count where resolved amount_sanctioned > 0 for approved only.
3. **Stale compiled views:** If present, clear `storage/framework/views` compiled cache; one compiled file (55750c194887ef9971b42f22d47de85c.php) was seen with `amount_requested = $rf['amount_sanctioned']` in grep — ensure source blade is current and recompile.

---

*End of report. No modifications were made to the codebase.*
