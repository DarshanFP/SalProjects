# FY Phase 1 Feasibility Audit

**Task:** FY Phase 1 Implementation Feasibility Audit (FinancialYearHelper + Project FY scope)  
**Date:** 2026-03-04  
**Mode:** Audit (read-only; no code or database changes)

---

## 1. Project Model Scope Compatibility

### Existing Scopes

| Scope | Purpose |
|-------|---------|
| `scopeCompleted($query)` | Filter completed projects (`completed_at` not null) |
| `scopeNotCompleted($query)` | Filter non-completed projects |
| `scopeApproved($query)` | Filter by `status IN (approved_by_coordinator, approved_by_general_as_coordinator, approved_by_general_as_provincial)` |
| `scopeNotApproved($query)` | Filter by status NOT IN approved statuses |
| `scopeAccessibleByUserIds($query, $userIds)` | Filter where `user_id` or `in_charge` in given IDs |

### Global Scopes

- **None** — No global scopes registered on Project model. The `boot()` method only registers `creating` and `forceDeleting` observers; no `addGlobalScope()`.

### Query Builder Macros

- **App-level:** No custom `Builder::macro` or `Query::macro` in application code that affects Project queries.
- **Vendor:** Maatwebsite Excel and ide-helper add macros; these do not conflict with Eloquent scopes.

### Naming Collision

- `scopeInFinancialYear` — No existing scope with similar name. Clear, distinct name.

### Project Model Scope Compatibility

**SAFE**

- No naming conflicts.
- No global scopes that would interact with a new local scope.
- New scope will be opt-in; applied only when explicitly chained (e.g. `Project::approved()->inFinancialYear('2024-25')`).

---

## 2. Commencement Field Usage

### Usage by File

| File | Usage Type | Impact |
|------|------------|--------|
| GeneralInfoController | Set on create/update from commencement_month + commencement_year | **None** — Writes to project; FY scope only reads and filters. |
| CoordinatorController | Set on approval (commencement_date → commencement_month_year) | **None** — Same as above. |
| GeneralController | Set on approval (as coordinator context) | **None** — Same as above. |
| ProjectStatusService | Persists commencement_month_year during approval | **None** — Service writes; scope only filters reads. |
| ProjectPhaseService | Reads for phase calculation; logs if null | **None** — Read-only; FY scope adds filter, does not change value. |
| ExecutorController | Used in `allowedSortFields` for project list sorting | **None** — Sort by column; FY scope is an additional filter when chained. |
| ReportController (Monthly) | Validation and report storage | **None** — Report models have own commencement_month_year; not projects table. |
| Report controllers (Quarterly, etc.) | Validation rules for report forms | **None** — Report-level fields. |
| Report models (DPReport, HalfYearlyReport, etc.) | Fillable/casts | **None** — Different tables. |
| QuarterlyReportService, HalfYearlyReportService, AnnualReportService | Copy from project to report | **None** — Read from project; FY scope would filter which projects are in a collection; not yet used in Phase 1. |
| ExportController | Display in export (format F Y) | **None** — Read-only display; FY scope not applied in Phase 1. |
| OldDevelopmentProjectController | Legacy project handling | **None** — Different table (oldDevelopmentProjects). |
| Project model | Accessors `getCommencementMonthAttribute`, `getCommencementYearAttribute` | **None** — Derive from commencement_month_year; scope only filters queries. |

### Conclusion

- All current usage is either write (create/update/approval) or read (display, validation, phase calculation).
- Adding `scopeInFinancialYear` only adds an optional filter on read queries. Phase 1 does not apply this scope anywhere, so existing logic is unaffected.

---

## 3. Query Compatibility

### Scope Application Behaviour

- Eloquent local scopes apply **only when explicitly chained** (e.g. `Project::inFinancialYear('2024-25')`).
- No global scope will add `inFinancialYear` to all Project queries.
- Default behaviour: `Project::approved()`, `Project::accessibleByUserIds()`, etc. remain unchanged until `inFinancialYear()` is chained.

### ProjectAccessService / ProjectQueryService

- `ProjectAccessService::getVisibleProjectsQuery()` returns `Project::query()` with role filters; no FY logic.
- `ProjectQueryService` methods return `Project::query()` or `Project::where(...)` with user/status filters; no FY logic.
- Adding `inFinancialYear()` in Phase 3+ will be an explicit chain at call sites; Phase 1 does not modify these services.

### Query Compatibility

**SAFE**

- Scope is opt-in.
- No risk of accidental global application.
- Phase 1 introduces the scope but does not use it in any controller or service.

---

## 4. Resolver Impact

### ProjectFinancialResolver Analysis

- **Location:** `app/Domain/Budget/ProjectFinancialResolver.php`
- **Dependencies:** Project model properties: `project_type`, `amount_sanctioned`, `opening_balance`, `amount_forwarded`, `local_contribution`, type-specific relations (budgets, iesExpenses, etc.).
- **Commencement usage:** Resolver does **not** read or use `commencement_month_year`.

### Strategies

- `PhaseBasedBudgetStrategy` — Uses `amount_forwarded`, `local_contribution`, `current_phase`, `overall_project_budget`, `budgets`.
- `DirectMappedIndividualBudgetStrategy` — Uses type-specific relations; no commencement.

### Resolver Impact

**NONE**

- Resolver is unaffected by FY scope.
- `resolver->resolve($project)` behaviour is unchanged.
- FY scope only filters which projects are passed to the resolver; it does not change how any single project is resolved.

---

## 5. Dashboard Dependency

### Dashboard Controllers

| Controller | FY / commencement_month_year usage |
|------------|------------------------------------|
| ExecutorController | Uses `commencement_month_year` only in `allowedSortFields` (sortable columns). No FY logic. |
| CoordinatorController | Uses `commencement_month_year` in approval flow (set value). No FY filter on dashboards. |
| ProvincialController | No direct commencement_month_year usage in dashboard aggregation. |
| GeneralController | Uses `commencement_month_year` in approval flow. No FY filter on dashboards. |

### Phase 1 Impact

- Phase 1 does not modify any dashboard controller.
- Helper and scope are inert until Phase 3 integration.
- Dashboards do not call `FinancialYearHelper` or `inFinancialYear()`.

### Dashboard Dependency Impact

**NONE**

- Dashboards do not depend on FY logic.
- Phase 1 adds code that is not yet invoked by dashboards.

---

## 6. Helper Naming Safety

### Existing Classes

| Name | Location | Exists? |
|------|----------|---------|
| FinancialYearHelper | app/Support, app/Helpers | **No** |
| FinancialHelper | app/Support, app/Helpers | **No** |
| FiscalYearHelper | app/Support, app/Helpers | **No** |

### Namespace Structure

- **app/Support:** Contains `Normalization\*` (PlaceholderNormalizer, etc.). No Financial* classes.
- **app/Helpers:** Contains ProjectPermissionHelper, NumberFormatHelper, etc. No FinancialYear* or FiscalYear*.

### Helper Naming Safety

**SAFE**

- No naming collision.
- `app/Support/FinancialYearHelper.php` can be added without conflict.
- Alternative `app/Helpers/FinancialYearHelper.php` also clear (project already uses Helpers for other utilities).

---

## 7. Performance Considerations

### Projects Table

- **Indexes (from migrations):** `id`, `project_id` (unique), `user_id` (FK), `in_charge` (FK), `province_id`, `society_id`. No index on `commencement_month_year`.
- **Estimated size:** 258 projects (from prior audits); small dataset.
- **FY query shape:** `WHERE commencement_month_year BETWEEN ? AND ?` (plus optional `WHERE commencement_month_year IS NOT NULL`).

### Performance Readiness

**GOOD**

- Current project count is small; full table scan on `commencement_month_year` is acceptable.
- **Index recommendation:** Document for later: add `projects(commencement_month_year)` index if project count grows significantly (e.g. thousands) and FY-filtered queries become slow. Not required for Phase 1.

---

## 8. Implementation Risk Assessment

### Phase 1 Deliverables

1. **FinancialYearHelper** — New class; no existing caller. Isolated.
2. **Project::scopeInFinancialYear($fy)** — New scope; no existing caller. Isolated.

### Integration Points in Phase 1

- **None** — Helper and scope are not used by any controller, service, or view in Phase 1.

### Risk Classification

**ISOLATED CHANGE**

- New files/scope only.
- No modifications to existing dashboards, resolver, or approval flow.
- No database schema changes.
- Behaviour change only when `inFinancialYear()` is explicitly chained in future phases.

---

## 9. Recommended Adjustments

### Pre-Implementation

- None required for Phase 1 feasibility.

### Optional Enhancements

1. **Namespace:** Prefer `app\Support\FinancialYearHelper` to align with `app\Support\Normalization\*` if project convention favours Support; or `app\Helpers\FinancialYearHelper` for consistency with ProjectPermissionHelper, NumberFormatHelper.
2. **Test coverage:** Add unit tests for `FinancialYearHelper` (currentFY, fromDate, startDate, endDate, listAvailableFY) before or as part of Phase 1.
3. **Index note:** Add to Known Future Optimizations in implementation plan: index on `projects(commencement_month_year)` when dataset grows.

---

## 10. Test Strategy Validation

### Phase 1 Test Approach

- **Unit tests for FinancialYearHelper** — Sufficient for Phase 1.
  - `fromDate()` with boundary dates (Apr 1, Mar 31, month &lt; 4, month ≥ 4).
  - `startDate()` and `endDate()` for given FY string.
  - `currentFY()` (consider mocking Carbon for deterministic tests).
  - `listAvailableFY()` (may depend on DB or config; mock or seed minimal data).

### Additional Suggestions

1. **Scope unit test:** `Project::inFinancialYear('2024-25')` produces expected SQL (`WHERE commencement_month_year BETWEEN ...`).
2. **Integration smoke test:** `Project::approved()->inFinancialYear('2024-25')->count()` returns a value (no exception); useful to confirm scope chains correctly with existing scopes.

### Verdict

- Unit tests for FinancialYearHelper are sufficient for Phase 1.
- Optional: one feature/unit test for the scope to lock in SQL behaviour.

---

## 11. Final Verdict

### SAFE TO IMPLEMENT PHASE 1

**Summary**

1. **Project model:** No scope or global-scope conflicts; `scopeInFinancialYear` is opt-in.
2. **Commencement usage:** Existing usage is write or read; FY scope only adds an optional read filter; no interference.
3. **Query compatibility:** Scope applies only when explicitly chained; default behaviour unchanged.
4. **Resolver:** No dependency on commencement_month_year; resolver logic unchanged.
5. **Dashboards:** No FY dependency; Phase 1 does not touch dashboards.
6. **Helper naming:** No collision; `FinancialYearHelper` can be added safely.
7. **Performance:** Current dataset size makes an index unnecessary; document for future growth.
8. **Implementation:** Phase 1 is an isolated change with no integration in Phase 1.

Proceed with Phase 1 implementation.

---

*Audit completed in read-only mode. No code or database was modified.*
