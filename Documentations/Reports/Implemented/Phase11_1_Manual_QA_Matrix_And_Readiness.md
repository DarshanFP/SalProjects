# Phase 11.1 — Manual QA Matrix & Readiness Check

**Status:** ✅ Deliverables ready (manual execution pending)  
**Date:** 2026-06-13

## Objective

Provide executable manual QA artifacts for all 12 monthly report project types, plus automated pre-flight validation before staging sign-off.

## Deliverables

| Artifact | Path |
|----------|------|
| QA matrix (steps + per-type extras) | `Documentations/Reports/Phase11_Manual_QA_Matrix.md` |
| Results / sign-off template | `Documentations/Reports/Phase11_Manual_QA_Results.md` |
| Readiness command | `php artisan reports:qa-readiness` |

## Command: `reports:qa-readiness`

**File:** `app/Console/Commands/ReportsQaReadinessCheck.php`

Verifies for each `ProjectType::all()` entry:

- `config/budget.php` field mapping exists
- SOA Blade partial exists (router map aligned with `statements_of_account.blade.php`)

Also verifies routes and Phase 1–9 services.

```bash
php artisan reports:qa-readiness
php artisan reports:qa-readiness --json
```

**Local run (2026-06-13):** 12/12 types OK; all route/service checks passed.

## Manual work remaining

Execute matrix on **staging** with real approved projects; fill `Phase11_Manual_QA_Results.md`. Overall status stays **PENDING** until all 12 types sign off.

## Next phase

Phase 12 — Society relational alignment (Wave 5B5A) after manual QA pass.
