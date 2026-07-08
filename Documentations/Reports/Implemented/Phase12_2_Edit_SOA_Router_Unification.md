# Phase 12.2 Implementation: Edit SOA Router Unification (C2)

**Date:** 2026-06-27  
**Goal:** Fix critical discrepancy C2 where editing monthly reports for 6 institutional project types (`NEXT PHASE - DEVELOPMENT PROPOSAL`, `CHILD CARE INSTITUTION`, `Rural-Urban-Tribal`, `Livelihood Development Projects`, `Residential Skill Training Proposal 2`, and `PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER`) fell through to an outdated generic SOA edit partial containing stale columns and JS calculation crashes.

---

## Root Cause Analysis

In `resources/views/reports/monthly/edit.blade.php`, the Statements of Account `@if/@elseif` routing chain only explicitly checked for `Development Projects` among institutional types. The remaining 6 institutional project types fell to the `@else` branch, which loaded `reports.monthly.partials.edit.statements_of_account`.

This generic fallback partial:
- Rendered a deprecated `amount_forwarded[]` input column.
- Referenced a non-existent `#total_forwarded` element in JS `calculateTotal()`, throwing unhandled JS exceptions during input events.
- Lacked budget summary cards present in typed partials.

In contrast, the create and view SOA routing paths (`partials/statements_of_account.blade.php`) properly mapped all 7 institutional project types to `development_projects`.

---

## Changes Made

### [`resources/views/reports/monthly/edit.blade.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/edit.blade.php)
- Expanded the `@elseif` branch to include an `in_array()` check matching all 7 phase-based institutional project types:
  - `Development Projects`
  - `NEXT PHASE - DEVELOPMENT PROPOSAL`
  - `Livelihood Development Projects`
  - `Residential Skill Training Proposal 2`
  - `PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER`
  - `CHILD CARE INSTITUTION`
  - `Rural-Urban-Tribal`
- Directed all matching types to `@include('reports.monthly.partials.edit.statements_of_account.development_projects', ...)`.
- Updated the final fallback to also default to `development_projects` for safety.

---

## Verification

1. **Edit Form UI:** Opened report edit forms for NPD, CCI, RUT, LDP, RST, and CIC projects. Verified that they now render the modern, unified SOA layout matching Create and View modes.
2. **JS Calculation:** Verified that editing monthly expense amounts triggers calculation without console errors or missing element exceptions.
