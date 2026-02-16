# M3 Phase 2 — DPReport Approved Scope Alignment

**Milestone:** M3 — Resolver Parity & Financial Stability  
**Phase:** Status Fix Plan — Phase 2  
**Scope:** DPReport approval filtering aligned with canonical approval grouping  
**Date:** 2025-02-15

---

## 1) Files modified

| File | Change |
|------|--------|
| `app/Models/Reports/Monthly/DPReport.php` | Added `APPROVED_STATUSES` constant and `scopeApproved()`; `isApproved()` now uses constant. |
| `app/Http/Controllers/CoordinatorController.php` | Replaced single-status approved filters with `DPReport::approved()` or `->whereIn('status', DPReport::APPROVED_STATUSES)`. |
| `app/Http/Controllers/GeneralController.php` | Same replacement for DPReport approved filters. |
| `app/Http/Controllers/ExecutorController.php` | Same replacement. |
| `app/Http/Controllers/ProvincialController.php` | Same replacement. |
| `app/Http/Controllers/Admin/AdminReadOnlyController.php` | `DPReport::where(...)` → `DPReport::approved()`. |
| `app/Console/Commands/TestApplicantAccess.php` | `->where(...)` → `->whereIn('status', DPReport::APPROVED_STATUSES)`. |
| `app/Services/Reports/AnnualReportService.php` | DPReport filter → `->whereIn('status', DPReport::APPROVED_STATUSES)`. |
| `app/Services/Reports/QuarterlyReportService.php` | Same. |
| `app/Services/Reports/HalfYearlyReportService.php` | Same. |
| `tests/Feature/Budget/CoordinatorAggregationParityTest.php` | Expected aggregation query → `DPReport::approved()`. |

---

## 2) Before (snippet)

**DPReport model:** No shared constant or scope; `isApproved()` inlined the three statuses.

**Typical controller/query:**

```php
$approvedReportIds = DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
    ->whereIn('project_id', $projectIds)
    ->pluck('report_id');
```

**Collection filter:**

```php
$approvedReports = $teamReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count();
```

---

## 3) After (snippet)

**DPReport model:**

```php
public const APPROVED_STATUSES = [
    self::STATUS_APPROVED_BY_COORDINATOR,
    self::STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR,
    self::STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL,
];

public function isApproved(): bool
{
    return in_array($this->status, self::APPROVED_STATUSES, true);
}

public function scopeApproved($query)
{
    return $query->whereIn('status', self::APPROVED_STATUSES);
}
```

**Query:**

```php
$approvedReportIds = DPReport::approved()
    ->whereIn('project_id', $projectIds)
    ->pluck('report_id');
```

**Collection filter:**

```php
$approvedReports = $teamReports->whereIn('status', DPReport::APPROVED_STATUSES)->count();
```

---

## 4) Why aggregation drift existed

- “Approved” was implemented as a single status: `STATUS_APPROVED_BY_COORDINATOR`.
- Reports approved by General (as coordinator or provincial), i.e. `STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR` and `STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL`, were excluded from:
  - Approved report counts and IDs
  - Dashboard and reporting aggregation
  - Executor “approved reports” list and report services
- So totals and lists undercounted approved reports and were inconsistent with `DPReport::isApproved()` and with project-level “approved” semantics.

---

## 5) Risk removed

- **Aggregation drift:** All DPReport “approved” filters now use the same set of three statuses (constant or scope), aligned with `isApproved()`.
- **Single source of truth:** `DPReport::APPROVED_STATUSES` and `scopeApproved()` centralize the definition; reporting and dashboards no longer rely on a single status.

---

## 6) Regression risk

**LOW–MEDIUM.**

- **LOW:** No status values, schema, or financial formulas changed; only which rows are included in “approved” (counts can increase, not decrease for the same data).
- **MEDIUM:** Dashboards and reports may show higher approved counts and more rows where General-approved reports existed; acceptance and any hard-coded expectations should be checked.

---

## 7) Verification steps

1. **Search:** `DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)` — **zero** occurrences in application code (only in documentation).
2. **Count:** `DPReport::approved()->count()` is equal to or greater than the previous single-status count (strictly greater when General-approved reports exist).
3. **Formulas:** No change to financial formulas, resolver, or workflow logic; only filtering of which reports are considered “approved”.

---

**M3 Phase 2 Complete — DPReport Approved Scope Alignment Enforced**
