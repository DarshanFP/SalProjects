# Phase 4 — Project Fund Fields Audit

**Date:** 2026-06-13 14:01:20
**Environment:** local
**Database:** projectsReports

**Total projects with issues:** 24

| project_id | project_type | issues | stored_sanctioned | derived_sanctioned | stored_opening | derived_opening |
|------------|--------------|--------|-------------------|--------------------|----------------|-----------------|
| CIC-0001 | PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER | opening_balance_invariant, derived_mismatch:opening_balance | 1,896,000.00 | 1,896,000.00 | 0.00 | 1,896,000.00 |
| DP-0001 | Development Projects | opening_balance_invariant, derived_mismatch:opening_balance | 789,000.00 | 789,000.00 | 0.00 | 789,000.00 |
| DP-0002 | Development Projects | opening_balance_invariant, derived_mismatch:opening_balance | 1,428,000.00 | 1,428,000.00 | 0.00 | 1,428,000.00 |
| DP-0003 | Development Projects | opening_balance_invariant, derived_mismatch:opening_balance | 613,600.00 | 613,600.00 | 0.00 | 613,600.00 |
| DP-0005 | Development Projects | opening_balance_invariant, derived_mismatch:opening_balance | 1,445,000.00 | 1,445,000.00 | 0.00 | 1,445,000.00 |
| DP-0006 | Development Projects | opening_balance_invariant, derived_mismatch:opening_balance | 1,680,000.00 | 1,680,000.00 | 0.00 | 1,680,000.00 |
| DP-0007 | Development Projects | opening_balance_invariant, derived_mismatch:opening_balance | 1,259,400.00 | 1,259,400.00 | 0.00 | 1,259,400.00 |
| DP-0008 | Development Projects | opening_balance_invariant, derived_mismatch:opening_balance | 520,000.00 | 520,000.00 | 0.00 | 520,000.00 |
| DP-0009 | Development Projects | opening_balance_invariant, derived_mismatch:opening_balance | 629,500.00 | 629,500.00 | 0.00 | 629,500.00 |
| DP-0016 | Development Projects | opening_balance_invariant, derived_mismatch:opening_balance | 998,200.00 | 998,200.00 | 0.00 | 998,200.00 |
| DP-0017 | Development Projects | opening_balance_invariant, derived_mismatch:opening_balance | 1,412,000.00 | 1,412,000.00 | 0.00 | 1,412,000.00 |
| DP-0020 | Development Projects | opening_balance_invariant, derived_mismatch:opening_balance | 929,000.00 | 929,000.00 | 0.00 | 929,000.00 |
| DP-0022 | Development Projects | opening_balance_invariant, derived_mismatch:opening_balance | 659,000.00 | 659,000.00 | 0.00 | 659,000.00 |
| IAH-0002 | Individual - Access to Health | zero_stored_sanctioned, derived_mismatch:amount_sanctioned, derived_mismatch:opening_balance | 0.00 | 100,000.00 | 0.00 | 100,000.00 |
| IAH-0005 | Individual - Access to Health | zero_stored_sanctioned, derived_mismatch:amount_sanctioned, derived_mismatch:opening_balance | 0.00 | 296,000.00 | 0.00 | 296,000.00 |
| IIES-0012 | Individual - Initial - Educational support | opening_balance_invariant, derived_mismatch:amount_sanctioned, derived_mismatch:opening_balance | 30,000.00 | 63,800.00 | 30,000.00 | 93,800.00 |
| IIES-0060 | Individual - Initial - Educational support | zero_stored_sanctioned, derived_mismatch:amount_sanctioned, derived_mismatch:opening_balance | 0.00 | 60,500.00 | 16,000.00 | 76,500.00 |
| ILA-0001 | Individual - Livelihood Application | zero_stored_sanctioned, derived_mismatch:amount_sanctioned, derived_mismatch:opening_balance | 0.00 | 150,000.00 | 3,000.00 | 153,000.00 |
| IOES-0037 | Individual - Ongoing Educational support | zero_stored_sanctioned, derived_mismatch:amount_sanctioned, derived_mismatch:opening_balance | 0.00 | 109,300.00 | 2,000.00 | 111,300.00 |
| IOES-0049 | Individual - Ongoing Educational support | zero_stored_sanctioned, derived_mismatch:amount_sanctioned, derived_mismatch:opening_balance | 0.00 | 100,000.00 | 6,820.00 | 106,820.00 |
| IOGEP-0004 | Institutional Ongoing Group Educational proposal | derived_mismatch:overall_project_budget, derived_mismatch:amount_sanctioned, derived_mismatch:opening_balance | 700,000.00 | 228,000.00 | 700,000.00 | 228,000.00 |
| IOGEP-0006 | Institutional Ongoing Group Educational proposal | zero_stored_sanctioned, derived_mismatch:amount_sanctioned, derived_mismatch:opening_balance | 0.00 | 493,200.00 | 0.00 | 493,200.00 |
| IOGEP-0007 | Institutional Ongoing Group Educational proposal | zero_stored_sanctioned, derived_mismatch:amount_sanctioned, derived_mismatch:opening_balance | 0.00 | 1,957,400.00 | 970,000.00 | 2,927,400.00 |
| IOGEP-0011 | Institutional Ongoing Group Educational proposal | zero_stored_sanctioned, derived_mismatch:amount_sanctioned, derived_mismatch:opening_balance | 0.00 | 700,000.00 | 96,000.00 | 796,000.00 |

Run repair (dry-run first): `php artisan reports:repair-project-fund-fields --dry-run`