# Phase 6.1 — Create UI Decision Matrix

**Status:** ✅ Documented & implemented  
**Date:** 2026-06-13

## Per-type create form coverage

| Project type | Top section on create | SOA partial (via router) |
|--------------|----------------------|--------------------------|
| Development Projects (DP) | Objectives + outlook only | `development_projects` |
| NEXT PHASE - DEVELOPMENT PROPOSAL (NPD) | Same as DP | `development_projects` |
| Livelihood Development Projects (LDP) | Livelihood annexure | `development_projects` |
| Institutional Ongoing Group Educational (IGE) | Age profiles partial | `institutional_education` |
| Residential Skill Training (RST) | Trainee profiles | `development_projects` |
| Crisis Intervention Center (CIC) | Inmate profiles | `development_projects` |
| CHILD CARE INSTITUTION (CCI) | None (intentional) | `development_projects` |
| Rural-Urban-Tribal (Edu-RUT) | None (intentional) | `development_projects` |
| Individual - Livelihood (ILP) | None — SOA has contribution cols | `individual_livelihood` |
| Individual - Access to Health (IAH) | None | `individual_health` |
| Individual - Initial - Educational (IIES) | None | `individual_education` |
| Individual - Ongoing Educational (IES) | None | `individual_ongoing_education` |

## Decision

Types without dedicated **create** partials do **not** get broken `@include` references. Generic objectives + type-aware SOA is sufficient for create (matches edit SOA behaviour).

Removed commented references to non-existent blades:
- `create/child_care_institution`
- `partials/rural_urban_tribal`
- `create/next_phase_development`
- `create/individual_*` (4 files)

## Files

- `resources/views/reports/monthly/ReportAll.blade.php`
