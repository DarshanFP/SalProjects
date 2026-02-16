# Financial Source-of-Truth Enforcement Plan

**Document Version:** 1.0  
**Created:** February 13, 2026  
**Status:** Design & Planning  
**Scope:** SalProjects Laravel Application

---

## Executive Summary

Project financial fields (`overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned`, `opening_balance`, `amount_requested`) are sourced inconsistently across the application. Controllers, Blade templates, reports, and exports read directly from database columns or perform inline arithmetic, causing divergence between list views, detail views, exports, and reports.

**Objective:** Establish `ProjectFinancialResolver::resolve($project)` as the sole canonical source for all financial display, reporting, export, approval, and mutation flows.

**Business Impact:** Users see different budget figures on the provincial projects list vs. project view (e.g., CIC-0002: Rs. 5,04,000 vs. Rs. 23,14,000). Exports and reports may not match what is displayed on-screen. This undermines trust and creates reconciliation risk.

**Strategic Approach:** Five-phase implementation—display fixes first, then reports/exports, write-path safety, JS harmonization, and finally enforcement guardrails. Zero-downtime deployment with validation gates at each phase.

---

## Current Problem

### Symptom Summary

| Symptom | Example | User Impact |
|---------|---------|-------------|
| List vs. View mismatch | Provincial ProjectList shows Rs. 5,04,000; project view shows Rs. 23,14,000 | Provincial users cannot trust list totals |
| Export vs. View mismatch | Word/PDF export uses raw DB; view uses resolver | Printed/exported documents differ from screen |
| Report form mismatch | Report forms read `$project->amount_sanctioned` | Report prefill may be incorrect |
| Edit initial load | Edit form seeds from DB; overall may be stale for phase-based types | User edits from wrong baseline |

### Root Causes

1. **Multiple read paths:** Blade, controllers, and exports read `$project->overall_project_budget`, `$project->amount_forwarded`, etc., instead of resolver output.
2. **Inline arithmetic:** `$amount_requested = max(0, $overall - $forwarded - $local)` in Blade; JS `calculateProjectTotal()` and `calculateAmountSanctioned()` duplicate resolver logic.
3. **No write validation:** GeneralInfoController persists request values without validating against resolver; AdminCorrectionService uses raw DB for corrections.
4. **Phase-based logic split:** Resolver computes overall from `sum(project_budgets.this_phase)` for current phase; DB `overall_project_budget` may be zero, stale, or from a different phase.

### Affected User Roles

| Role | Flows Affected |
|------|----------------|
| Provincial | Projects list, approved projects, reports, exports |
| Coordinator | Approved projects list, approval flow (already uses resolver), exports |
| General | Project list, approval, exports |
| Executor/Applicant | Edit/Create, project view, reports |
| Admin | Budget reconciliation, corrections |

---

## Target Architecture

### Canonical Rule

```
┌─────────────────────────────────────────────────────────────────────────┐
│  CANONICAL SOURCE OF TRUTH                                               │
│                                                                         │
│  ProjectFinancialResolver::resolve(Project $project)                     │
│                                                                         │
│  Returns: overall_project_budget, amount_forwarded, local_contribution,  │
│           amount_sanctioned, opening_balance                            │
│                                                                         │
│  amount_requested = amount_sanctioned (pre-approval) or derived         │
│  from same formula: max(0, overall - (forwarded + local))               │
└─────────────────────────────────────────────────────────────────────────┘
```

### Principle Stack

| # | Principle | Description |
|---|-----------|-------------|
| 1 | **Resolver is single read source** | All display, report, and export logic MUST read financial values from `ProjectFinancialResolver::resolve($project)`. No `$project->overall_project_budget` in display paths. |
| 2 | **No Blade arithmetic** | Blade templates MUST NOT compute `amount_requested`, `amount_sanctioned`, or `opening_balance`. Display only values passed from controller. |
| 3 | **No controller raw DB display** | Controllers MUST NOT pass raw `$project` financial attributes to views for display. Resolve first, pass resolved array. |
| 4 | **JS is UI-only, backend authoritative** | JavaScript may compute for live edit UX, but backend MUST recompute or validate before persist. Client values are suggestions, not truth. |
| 5 | **Approval flow persists resolver output** | Coordinator/General approval MUST persist `amount_sanctioned` and `opening_balance` from resolver, not from request. |
| 6 | **Write-path validation** | Any mutation of financial fields MUST validate against resolver logic or use resolver output before DB write. |

---

## Enforcement Pattern

### 1. Canonical Rule (Formal)

| Rule ID | Rule | Enforcement |
|---------|------|-------------|
| R1 | Resolver is the single read source for display | Code review; grep ban on `$project->overall_project_budget` in Blade |
| R2 | No Blade arithmetic for financial fields | Code review; grep ban on `max(0,` and `-` involving forwarded/local in Blade |
| R3 | No controller raw DB display | Controllers pass `$resolvedFinancials` or equivalent; never raw project attributes for fund fields |
| R4 | JS is UI-only | Backend recomputes on save; JS result is never trusted as sole source |
| R5 | DB values allowed only when approved | For approved projects, DB `amount_sanctioned` and `opening_balance` are snapshots; resolver may return them for consistency |
| R6 | Mutation policy enforced | GeneralInfoController, AdminCorrectionService, BudgetSyncService validate or use resolver before write |

### 2. Financial Data Flow Diagram (Text-based)

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│  EDIT FLOW                                                                       │
├─────────────────────────────────────────────────────────────────────────────────┤
│  User opens Edit form                                                            │
│       │                                                                          │
│       ▼                                                                          │
│  Controller loads $project + budgets                                             │
│       │                                                                          │
│       ▼                                                                          │
│  Controller calls ProjectFinancialResolver::resolve($project)                     │
│       │                                                                          │
│       ▼                                                                          │
│  View receives $resolvedFinancials for initial display                           │
│       │                                                                          │
│       ▼                                                                          │
│  User edits budget rows, forwarded, local                                        │
│       │                                                                          │
│       ▼                                                                          │
│  JS calculateProjectTotal() + calculateAmountSanctioned() (UI feedback only)     │
│       │                                                                          │
│       ▼                                                                          │
│  Form submits: overall, forwarded, local, budget rows                            │
│       │                                                                          │
│       ▼                                                                          │
│  GeneralInfoController/BudgetController receives request                         │
│       │                                                                          │
│       ▼                                                                          │
│  Backend: For phase-based types, overall = sum(budget_rows.this_phase)           │
│           Override client overall with computed value                            │
│       │                                                                          │
│       ▼                                                                          │
│  Validate: forwarded + local <= overall                                          │
│       │                                                                          │
│       ▼                                                                          │
│  Persist: overall_project_budget, amount_forwarded, local_contribution            │
│           (amount_sanctioned, opening_balance only on approval)                  │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│  SAVE (DRAFT) FLOW                                                               │
├─────────────────────────────────────────────────────────────────────────────────┤
│  Same as Edit, but status remains DRAFT                                          │
│  amount_sanctioned, opening_balance NOT persisted (computed only at approval)    │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│  APPROVAL FLOW                                                                   │
├─────────────────────────────────────────────────────────────────────────────────┤
│  Coordinator/General approves project                                            │
│       │                                                                          │
│       ▼                                                                          │
│  Controller calls ProjectFinancialResolver::resolve($project)                     │
│       │                                                                          │
│       ▼                                                                          │
│  $financials = resolver output                                                   │
│       │                                                                          │
│       ▼                                                                          │
│  Persist: project->amount_sanctioned = $financials['amount_sanctioned']          │
│           project->opening_balance   = $financials['opening_balance']             │
│       │                                                                          │
│       ▼                                                                          │
│  NO request values used for sanctioned/opening                                   │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│  DISPLAY FLOW (View, List, Card)                                                 │
├─────────────────────────────────────────────────────────────────────────────────┤
│  Controller loads $project(s)                                                    │
│       │                                                                          │
│       ▼                                                                          │
│  For each project: $financials = ProjectFinancialResolver::resolve($project)     │
│       │                                                                          │
│       ▼                                                                          │
│  Controller passes $resolvedFinancials (or array keyed by project_id) to view    │
│       │                                                                          │
│       ▼                                                                          │
│  Blade displays ONLY from $resolvedFinancials                                    │
│       │                                                                          │
│       ▼                                                                          │
│  NO $project->overall_project_budget in display markup                           │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│  REPORTS FLOW (Monthly, Quarterly, etc.)                                         │
├─────────────────────────────────────────────────────────────────────────────────┤
│  Report form load / create                                                       │
│       │                                                                          │
│       ▼                                                                          │
│  Controller loads $project                                                       │
│       │                                                                          │
│       ▼                                                                          │
│  $financials = ProjectFinancialResolver::resolve($project)                       │
│       │                                                                          │
│       ▼                                                                          │
│  Pass amount_sanctioned, opening_balance (or full financials) to report view     │
│       │                                                                          │
│       ▼                                                                          │
│  Report form displays from resolved values                                       │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│  EXPORT FLOW (Word, PDF)                                                         │
├─────────────────────────────────────────────────────────────────────────────────┤
│  User requests export                                                            │
│       │                                                                          │
│       ▼                                                                          │
│  ExportController loads $project                                                 │
│       │                                                                          │
│       ▼                                                                          │
│  $financials = ProjectFinancialResolver::resolve($project)                       │
│       │                                                                          │
│       ▼                                                                          │
│  addGeneralInformationSection / PDF generation uses $financials                  │
│       │                                                                          │
│       ▼                                                                          │
│  NO $project->overall_project_budget, etc. in export logic                       │
└─────────────────────────────────────────────────────────────────────────────────┘
```

### 3. Mutation Policy

| Project Type | Overall Source | Forwarded / Local | Sanctioned / Opening | When DB Allowed |
|--------------|----------------|-------------------|----------------------|-----------------|
| **Phase-based** (Development, NPD, LDP, RST, CIC, CCI, RUT) | `sum(project_budgets.this_phase)` for current_phase | User input from `projects` | `overall - (forwarded + local)`; `opening = sanctioned + forwarded + local` | Approved: DB stores resolver output snapshot |
| **Individual** (IIES, IES, ILP, IAH, IGE) | Type-specific (e.g. sum of ILP cost) | Type-specific or project | Type-specific formulas | Approved: DB stores resolver output |
| **Draft / Not approved** | Computed from budget rows | User input | Computed only; not persisted | Never trust DB for sanctioned/opening pre-approval |
| **Approved** | Resolver may return DB values | From project | From project (snapshot from approval) | Resolver returns DB when BudgetSyncGuard::isApproved() |

**When DB values are allowed:**
- After approval: `amount_sanctioned` and `opening_balance` are persisted by approval flow from resolver; resolver returns them for display.
- User-editable fields (`amount_forwarded`, `local_contribution`) are stored in DB; resolver reads them for formulas.

**When they must be recomputed:**
- Phase-based overall: always from `sum(this_phase)` for current_phase when budget rows exist.
- Pre-approval sanctioned/opening: always computed, never read from DB as display source (DB may be stale or zero).

### 4. Enforcement Strategy

| Strategy | Mechanism | Pros | Cons |
|----------|-----------|------|------|
| **Service-layer wrapper** | `ProjectFinancialService::getResolvedFinancials(Project $project)` – thin wrapper over resolver; all controllers call this | Centralizes call; easy to add logging/metrics | Does not prevent raw access |
| **ViewModel/DTO** | `ProjectFinancialsDTO` passed to views; Blade receives only DTO, not project | Type-safe; no accidental project access | Requires refactoring all views |
| **Trait** | `HasResolvedFinancials` on Project; `$project->getResolvedFinancials()` | Convenient | Mixes model with resolution; not recommended |
| **Middleware detection** | Middleware greps response for raw project attributes | Catches leaks in HTML | Heavy; false positives; not for API |
| **Static analysis** | PHPStan/Psalm rule: ban `$project->overall_project_budget` in Blade/controllers | Automated; prevents regressions | Requires tooling setup |

**Recommended hybrid:**

1. **Service wrapper:** `app/Services/Budget/ProjectFinancialsDisplayService.php` – `getForProject(Project $project): array` and `getForProjects(Collection $projects): array` (memoized per request).
2. **Controller convention:** All display controllers call the service; pass `$resolvedFinancials` to views.
3. **Static analysis (Phase 5):** PHPStan custom rule to flag `$project->overall_project_budget` (and similar) in non-model/resolver code.
4. **Code review checklist:** "Financial fields from resolver?" for any PR touching display/export/report.

### 5. Anti-Patterns to Ban

| Anti-Pattern | Example | Replacement |
|--------------|---------|-------------|
| Raw project in Blade | `{{ $project->overall_project_budget }}` | `{{ $resolvedFinancials['overall_project_budget'] ?? 0 }}` |
| Inline arithmetic | `$amount_requested = max(0, $overall - $forwarded - $local)` | Use `$resolvedFinancials['amount_sanctioned']` or add `amount_requested` to resolver |
| Manual sum(this_phase) | `$project->budgets->sum('this_phase')` outside resolver | Resolver handles this; never in controller/Blade |
| Direct DB display in controller | `'overall' => $project->overall_project_budget` | `'overall' => $resolver->resolve($project)['overall_project_budget']` |
| Export raw project | `$project->amount_sanctioned` in ExportController | `$financials['amount_sanctioned']` |
| Persist without validation | `$project->update($request->only(['overall_project_budget']))` | Recompute overall from budgets for phase-based; validate before persist |
| JS as source of truth | Backend trusts `overall_project_budget` from form submit | Backend recomputes from budget rows for phase-based types |

---

## Phase-wise Implementation Plan

### Phase 1 — High Impact Display Fixes

**Scope:** Provincial ProjectList, ProjectController show, general_info partial. Ensure all project display views use resolver output.

| Task | Files | Risk | Complexity |
|------|-------|------|------------|
| 1.1 ProvincialController::projectList – resolve financials per project | `ProvincialController.php` | Medium | Low |
| 1.2 ProjectList.blade.php – display from resolved array | `ProjectList.blade.php` | Low | Low |
| 1.3 ProjectController::show – always pass resolvedFundFields; remove fallback | `ProjectController.php` | Low | Low |
| 1.4 general_info.blade.php – remove raw fallback; use amount_sanctioned for amount_requested | `general_info.blade.php` | Low | Low |
| 1.5 Coordinator/General project lists – if any display financials | `coordinator/`, `general/` | Low | Low |

**Testing:**
- Manual: Provincial list matches project view for CIC-0002, NPD-0002.
- Automated: Assert ProjectList view receives `resolvedFinancials`; assert displayed values match resolver output.

**Rollback:** Revert controller/view changes; restore raw project pass-through.

**User Impact:**
- Provincial: ✅ List columns now match project view
- Coordinator: ✅ (if applicable)
- General: ✅

**Estimated effort:** 2–3 days

---

### Phase 2 — Report & Export Alignment

**Scope:** ExportController, Oldprojects/pdf, ReportController, report forms. All exports and reports use resolver.

| Task | Files | Risk | Complexity |
|------|-------|------|------------|
| 2.1 ExportController addGeneralInformationSection – use resolver | `ExportController.php` | Medium | Low |
| 2.2 PDF generation – pass resolvedFundFields to pdf view | Controller calling pdf; `Oldprojects/pdf.blade.php` | Medium | Low |
| 2.3 ReportController – resolve before report form load | `ReportController.php` | Medium | Low |
| 2.4 MonthlyDevelopmentProjectController – resolve for report overview | `MonthlyDevelopmentProjectController.php` | Low | Low |
| 2.5 reportform.blade.php – use passed financials | `reportform.blade.php` | Low | Low |
| 2.6 Quarterly DevelopmentProjectController – resolve for amountSanctionedOverview | `DevelopmentProjectController.php` | Low | Low |

**Testing:**
- Export a project to Word; compare financial section with project view.
- Generate PDF; compare.
- Create monthly report; verify amount_sanctioned/opening prefill matches view.

**Rollback:** Revert to raw project; exports/reports revert to prior behavior.

**User Impact:**
- Provincial, Coordinator, General: ✅ Exports and reports match screen
- Report users: ✅ Correct prefill

**Estimated effort:** 3–4 days

---

### Phase 3 — Write-Path Safety

**Scope:** GeneralInfoController, BudgetSyncService, AdminCorrectionService. Ensure all mutations validate or use resolver.

| Task | Files | Risk | Complexity |
|------|-------|------|------------|
| 3.1 GeneralInfoController::update – for phase-based types, recompute overall from budget rows before persist | `GeneralInfoController.php` | High | Medium |
| 3.2 BudgetController – ensure overall sync from budget rows on save | `BudgetController.php` | High | Medium |
| 3.3 AdminCorrectionService – validate correction input against resolver; persist resolver output after override | `AdminCorrectionService.php` | Medium | Medium |
| 3.4 BudgetSyncService – ensure sync output matches resolver for same inputs | `BudgetSyncService.php` | Medium | Medium |
| 3.5 Edit/budget.blade.php – seed initial values from resolver (controller passes) | `Edit/budget.blade.php` | Low | Low |

**Testing:**
- Edit phase-based project; change budget rows; save; view shows correct overall.
- Admin correction: change overall; verify sanctioned/opening recomputed correctly.
- BudgetSyncService: run sync; assert resolved output matches.

**Rollback:** Revert controller/service changes; restore request-direct persist.

**User Impact:**
- Executor/Applicant: ✅ Edit saves correct overall
- Admin: ✅ Corrections align with resolver

**Estimated effort:** 4–5 days

---

### Phase 4 — JS Harmonization

**Scope:** scripts-edit, scripts, budget-calculations.js. Clarify JS as UI-only; backend authoritative.

| Task | Files | Risk | Complexity |
|------|-------|------|------------|
| 4.1 Document JS as UI-only in code comments | `scripts-edit.blade.php`, `scripts.blade.php`, `budget-calculations.js` | Low | Low |
| 4.2 Ensure edit submit sends budget rows; backend recomputes overall | `GeneralInfoController`, `BudgetController` | Medium | Medium |
| 4.3 Add backend validation: compare client overall vs. server-computed; log mismatch | `GeneralInfoController` | Low | Low |
| 4.4 Optionally: seed overall from resolver on edit load so JS starts from correct value | Already in Phase 3 | - | - |

**Testing:**
- Edit project; submit; verify DB overall matches server-computed.
- Disable JS; submit with budget rows; backend computes overall correctly.

**Rollback:** Remove validation; keep JS as-is.

**User Impact:**
- Executor/Applicant: Transparent; no UX change if backend already correct

**Estimated effort:** 2 days

---

### Phase 5 — Enforcement & Guardrails

**Scope:** Service wrapper, static analysis, documentation, governance.

| Task | Files | Risk | Complexity |
|------|-------|------|------------|
| 5.1 Create ProjectFinancialsDisplayService | New: `ProjectFinancialsDisplayService.php` | Low | Low |
| 5.2 Refactor controllers to use service (optional consolidation) | Controllers | Low | Medium |
| 5.3 PHPStan/Psalm custom rule or baseline to flag raw project financial reads | `phpstan.neon`, custom rule | Low | Medium |
| 5.4 Code review checklist; PR template | `.github/`, docs | Low | Low |
| 5.5 Deprecate ProjectFundFieldsResolver fallback; ensure always delegate | `ProjectFundFieldsResolver.php` | Low | Low |

**Testing:**
- PHPStan passes with no new violations.
- Manual review of one full flow per role.

**Rollback:** Remove service; remove rule; revert checklist.

**User Impact:**
- All: No direct impact; reduces future regressions

**Estimated effort:** 3 days

---

## Risk Analysis

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Resolver returns different values than current DB for approved projects | Low | High | Resolver returns DB when approved (BudgetSyncGuard); no change for approved |
| Performance: resolve() per project in list | Medium | Medium | Memoize per request; batch resolve; consider `resolveMany()` |
| Regression in Edit flow | Medium | High | Phase 3 extensive testing; feature flag for backend recompute |
| Export format change | Low | Low | Export layout unchanged; only data source changes |
| Report prefill wrong for edge cases | Low | Medium | Test all project types; IAH/ILP/IGE use type-specific resolver |

### User-Impact Mapping

| Phase | Provincial | Coordinator | General | Admin | Report Users |
|-------|------------|-------------|---------|-------|--------------|
| 1 | High (list fix) | Medium | Medium | - | - |
| 2 | Medium (export) | Medium | Medium | - | High (report prefill) |
| 3 | Low | Low | Low | High (corrections) | - |
| 4 | - | - | - | - | - |
| 5 | - | - | - | - | - |

---

## Testing Strategy

### Unit Tests

- `ProjectFinancialResolver` – already covered by `ProjectFinancialResolverParityTest`, `ViewEditParityTest`
- `ProjectFinancialsDisplayService` (new) – returns same as resolver; memoization works

### Integration Tests

- `ProvincialProjectListFinancialsTest` – list displays resolver output
- `ExportFinancialsTest` – exported Word/PDF financial section matches resolver
- `ReportFormFinancialsTest` – report form prefill matches resolver
- `EditSaveRecomputeTest` – phase-based edit save recomputes overall from budget rows

### Manual Test Matrix

| Flow | Provincial | Coordinator | General | Executor |
|------|------------|-------------|---------|----------|
| Project list | ✓ | ✓ | ✓ | ✓ |
| Project view | ✓ | ✓ | ✓ | ✓ |
| Project edit & save | - | - | - | ✓ |
| Approval | - | ✓ | ✓ | - |
| Export Word/PDF | ✓ | ✓ | ✓ | ✓ |
| Monthly report create | ✓ | ✓ | ✓ | ✓ |
| Admin correction | - | - | - | - (Admin) |

### Validation Gates

- Before Phase 1 deploy: Run `ProjectFinancialResolverParityTest`; all pass
- After Phase 1: Spot-check CIC-0002, NPD-0002 on provincial list vs. view
- After Phase 2: Export same project to Word; diff financial section with view
- After Phase 3: Edit CIC project; change budget row; save; view shows new total

---

## Rollback Strategy

| Phase | Rollback Action | Data Risk |
|-------|-----------------|-----------|
| 1 | Revert controller/view commits; redeploy | None |
| 2 | Revert export/report controller commits | None |
| 3 | Revert GeneralInfoController, AdminCorrectionService, BudgetSyncService | Low – only affects new edits/corrections |
| 4 | Revert validation; restore direct persist | None |
| 5 | Remove service, rule, checklist | None |

**General:** Each phase is independently deployable. No schema changes. No migration. Rollback = git revert + deploy.

---

## Long-Term Governance Rules

1. **Resolver is mandatory for display.** Any new view that shows financial fields MUST receive data from `ProjectFinancialResolver::resolve()` or `ProjectFinancialsDisplayService`.
2. **No new Blade arithmetic.** PRs that add `max(0, ...)` or similar for financial fields are rejected.
3. **Approval flow never uses request for sanctioned/opening.** Only resolver output.
4. **Quarterly resolver audit.** Every quarter, grep for `$project->overall_project_budget`, `$project->amount_sanctioned`, etc. in Blade and non-resolver controllers; remediate findings.
5. **Document resolver changes.** Any change to `ProjectFinancialResolver` or strategies MUST update this plan and the audit report.
6. **Test coverage.** New financial display/report/export features MUST include a test asserting resolver output is used.

---

## Production Safety

### 1. Zero-Downtime Approach

- No database migrations; no schema changes
- No breaking API changes; internal refactor only
- Deploy during low-traffic window; no maintenance mode required
- Feature flags: Optional `RESOLVER_DISPLAY_STRICT` env flag to switch list/export between raw and resolved (for A/B validation)

### 2. Validating No Financial Drift

**Pre-deployment:**
- Run resolver for a sample of projects (phase-based + individual); compare with current display
- For approved projects, resolver should return DB values; no change
- For draft/pre-approval, resolver may differ from DB; that is expected and desired

**Post-deployment:**
- Compare provincial list totals for a province with project view totals; should match
- Export one project; compare financial section with view; should match

### 3. DB Audit Queries

```sql
-- Projects where DB overall differs from sum of current-phase budget rows (phase-based)
SELECT p.project_id, p.project_type, p.current_phase,
       p.overall_project_budget AS db_overall,
       COALESCE(SUM(pb.this_phase), 0) AS budget_sum
FROM projects p
LEFT JOIN project_budgets pb ON pb.project_id = p.project_id AND pb.phase = p.current_phase
WHERE p.project_type IN ('Development Projects', 'NEXT PHASE - DEVELOPMENT PROPOSAL', 
  'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER', 'CHILD CARE INSTITUTION', ...)
GROUP BY p.project_id, p.project_type, p.current_phase, p.overall_project_budget
HAVING ABS(COALESCE(p.overall_project_budget, 0) - COALESCE(SUM(pb.this_phase), 0)) > 0.01;

-- Approved projects: check sanctioned + forwarded + local = opening_balance
SELECT project_id,
       amount_sanctioned + COALESCE(amount_forwarded,0) + COALESCE(local_contribution,0) AS computed_opening,
       opening_balance,
       ABS((amount_sanctioned + COALESCE(amount_forwarded,0) + COALESCE(local_contribution,0)) - opening_balance) AS drift
FROM projects
WHERE status = 'approved_by_coordinator'
  AND ABS((amount_sanctioned + COALESCE(amount_forwarded,0) + COALESCE(local_contribution,0)) - opening_balance) > 0.01;
```

### 4. Monitoring After Deployment

- **Logging:** Log when resolver output differs from DB for approved projects (should be rare)
- **Alerting:** If drift query returns rows for approved projects, alert
- **Dashboard:** Optional: "Financial consistency" metric – % of list/view pairs that match
- **User feedback:** Monitor support for "wrong numbers" reports in first 2 weeks post Phase 1 & 2

---

## Appendix A: Resolver Output Shape

```php
ProjectFinancialResolver::resolve(Project $project): array
{
    return [
        'overall_project_budget' => float,
        'amount_forwarded'       => float,
        'local_contribution'     => float,
        'amount_sanctioned'      => float,
        'opening_balance'        => float,
    ];
}

// amount_requested (pre-approval) = amount_sanctioned (same formula)
// Display as: $financials['amount_sanctioned'] or add to resolver
```

---

## Appendix B: File Change Summary

| Phase | Files to Modify |
|-------|-----------------|
| 1 | ProvincialController, ProjectList.blade, ProjectController, general_info.blade |
| 2 | ExportController, pdf controller/view, ReportController, MonthlyDevelopmentProjectController, reportform.blade, DevelopmentProjectController |
| 3 | GeneralInfoController, BudgetController, AdminCorrectionService, BudgetSyncService, Edit/budget.blade |
| 4 | scripts-edit, scripts, budget-calculations.js (comments); GeneralInfoController, BudgetController |
| 5 | New ProjectFinancialsDisplayService; ProjectFundFieldsResolver; phpstan.neon; PR template |

---

*End of document*
