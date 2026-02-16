# M3.4 Wave 2 — Export & PDF Canonical Parity Enforcement

**Milestone:** M3 — Resolver Parity & Financial Stability  
**Wave:** M3.4 — Export & PDF Canonical Parity Enforcement  
**Date:** 2025-02-15  
**Status:** Complete

---

## OBJECTIVE

Ensure exports and PDF use the canonical financial model:

- **Approved:** Opening Balance = `opening_balance` (persisted)
- **Non-approved:** Opening Balance = `forwarded + local`
- **Pending Request:** `overall_project_budget - (forwarded + local)`
- **No inline sanctioned fallback allowed**

---

## STEP 1 — Inject Resolver Into Export Layer

### Files Modified

| File | Change |
|------|--------|
| `app/Services/ProjectDataHydrator.php` | Injected `ProjectFinancialResolver` via constructor; added `resolvedFundFields` to hydrated data |
| `app/Http/Controllers/Projects/ExportController.php` | Import `ProjectFinancialResolver`; call resolver in `downloadDoc`; pass `$resolvedFundFields` to `addGeneralInfoSection`; use resolved values for financial fields in Word export |

### How Resolver Is Injected

1. **ProjectDataHydrator**
   - Constructor: Added `ProjectFinancialResolver $financialResolver` dependency
   - `hydrate()`: After loading project and type-specific data, adds `$data['resolvedFundFields'] = $this->financialResolver->resolve($project)`
   - PDF download uses `ProjectDataHydrator->hydrate($project_id)`, so `resolvedFundFields` is passed to `pdf.blade.php`

2. **ExportController downloadDoc**
   - Before building Word document: `$resolvedFundFields = app(ProjectFinancialResolver::class)->resolve($project)`
   - Passes `$resolvedFundFields` to `addGeneralInfoSection($phpWord, $project, $projectRoles, $resolvedFundFields)`

### Resolved Fields Used

- `overall_project_budget`
- `amount_forwarded`
- `local_contribution`
- `amount_sanctioned`
- `opening_balance`

---

## STEP 2 — Remove Inline Financial Math From Blade

### Files Modified

| File | Change |
|------|--------|
| `resources/views/projects/Oldprojects/pdf.blade.php` | Removed inline sanctioned fallback; replaced with `$resolvedFundFields['...'] ?? 0` |

### Inline Formulas Removed

| Location | Before | After |
|----------|--------|-------|
| pdf.blade.php line 796 | `$project->amount_sanctioned ?? max(0, ($project->overall_project_budget ?? 0) - (($project->amount_forwarded ?? 0) + ($project->local_contribution ?? 0)))` | `$resolvedFundFields['amount_sanctioned'] ?? 0` |
| pdf.blade.php line 799 | `$project->amount_forwarded ?? 0`, `$project->local_contribution ?? 0` | `$resolvedFundFields['amount_forwarded'] ?? 0`, `$resolvedFundFields['local_contribution'] ?? 0` |

### Fallback Logic Removed

- **Removed:** `amount_sanctioned ?? (budget - forwarded - local)` — inline formula that bypassed canonical model
- **Replaced with:** `$resolvedFundFields['amount_sanctioned'] ?? 0` — uses resolver output only; Blade does not recompute financial semantics

---

## STEP 3 — Stage Awareness

Export and PDF now:

- **Respect approved vs non-approved logic** — Resolver returns correct values per project status (approved: persisted `opening_balance`; non-approved: `forwarded + local`; pending: `overall - (forwarded + local)` for sanctioned)
- **Do not assume sanctioned exists** — Uses `$resolvedFundFields['amount_sanctioned'] ?? 0`; resolver returns 0 for non-approved when appropriate
- **Do not display sanctioned for unapproved projects incorrectly** — Resolver handles stage semantics; Blade only displays resolved values

---

## STEP 4 — What Was NOT Changed

- Resolver formulas
- Approval workflow
- Expense logic
- Dashboard logic
- Aggregation logic
- DB schema

---

## STEP 5 — Before/After Comparison

### Word Export (addGeneralInfoSection)

| Field | Before | After |
|-------|--------|-------|
| Overall Project Budget | `$project->overall_project_budget` | `$resolvedFundFields['overall_project_budget'] ?? 0` |
| Amount Forwarded | `$project->amount_forwarded` | `$resolvedFundFields['amount_forwarded'] ?? 0` |
| Amount Sanctioned | `$project->amount_sanctioned` | `$resolvedFundFields['amount_sanctioned'] ?? 0` |
| Opening Balance | `$project->opening_balance` | `$resolvedFundFields['opening_balance'] ?? 0` |

### PDF (approval block)

| Field | Before | After |
|-------|--------|-------|
| Amount approved (Sanctioned) | Inline: `$project->amount_sanctioned ?? max(0, overall - (forwarded + local))` | `$resolvedFundFields['amount_sanctioned'] ?? 0` |
| Contributions considered (Forwarded) | `$project->amount_forwarded ?? 0` | `$resolvedFundFields['amount_forwarded'] ?? 0` |
| Contributions considered (Local) | `$project->local_contribution ?? 0` | `$resolvedFundFields['local_contribution'] ?? 0` |

### PDF general_info partial

- **No change** — Already uses `$resolvedFundFields ?? []`. Now receives `resolvedFundFields` from `ProjectDataHydrator`, so displays correct values instead of 0.

---

## Risk Assessment

| Risk | Before | After |
|------|--------|-------|
| Inline sanctioned fallback | **HIGH** — Wrong for IIES/IES/ILP/IAH/IGE; bypassed canonical model | **Controlled** — Uses resolver only |
| Direct DB in export | **HIGH** — Stale/wrong for phase-based projects | **Controlled** — Uses resolver |
| PDF general_info zeros | **HIGH** — `resolvedFundFields` not passed; displayed 0 | **Controlled** — Hydrator passes `resolvedFundFields` |
| Diff size | — | Minimal, auditable |

---

## Summary of Changes

1. **ProjectDataHydrator** — Injects `ProjectFinancialResolver`; adds `resolvedFundFields` to hydrated data
2. **ExportController** — Calls resolver in `downloadDoc`; `addGeneralInfoSection` uses resolved values
3. **pdf.blade.php** — Removed inline sanctioned fallback; uses `$resolvedFundFields` for approval block and contributions

---

**M3.4 Wave 2 Complete — Export & PDF Canonical Parity Enforced**
