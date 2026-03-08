# Phase 6 — Shared Dataset Remediation Report

**Date:** 2026-03-06  
**Scope:** Remove `DPReport::` queries from `getSystemBudgetOverviewData`  
**Status:** Complete

---

## 1. Problem Description

Phase 6 audit (`Phase6_SharedDataset_Audit.md`) detected that `getSystemBudgetOverviewData` issued multiple database queries via `DPReport::approved()` and `DPReport::where('status', ...)` even though the controller already loads the full reports dataset (`$allReports`) once for the dashboard.

**Impact:**
- Redundant database round-trips for report IDs
- Deviation from shared-dataset architecture
- Higher latency and query load per dashboard request

**Root cause:** The widget received `$teamProjects`, `$resolvedFinancials`, and `$approvedProjectsByProvince` but not `$allReports`. It therefore queried `DPReport` to obtain approved/unapproved report IDs for expense aggregation via `DPAccountDetail`.

---

## 2. Code Changes

### 2.1 Widget Signature Update

**File:** `app/Http/Controllers/CoordinatorController.php`

Added parameter `Collection $allReports` to `getSystemBudgetOverviewData`:

```php
private function getSystemBudgetOverviewData(
    $request,
    Collection $teamProjects,
    array $resolvedFinancials,
    Collection $approvedProjectsByProvince,
    Collection $allReports   // ← Added
) {
```

### 2.2 Controller Call Update

**File:** `app/Http/Controllers/CoordinatorController.php`

Updated `coordinatorDashboard` to pass `$allReports`:

```php
$systemBudgetOverviewData = $this->getSystemBudgetOverviewData(
    $request,
    $teamProjects,
    $resolvedFinancials,
    $approvedProjectsByProvince,
    $allReports   // ← Added
);
```

### 2.3 DPReport Query Replacements

All `DPReport::` query calls were replaced with in-memory filtering on `$allReports`:

| Former pattern | Replacement |
|----------------|-------------|
| `DPReport::approved()->whereIn('project_id', $ids)->pluck('report_id')` | `$allReports->whereIn('status', DPReport::APPROVED_STATUSES)->whereIn('project_id', $ids)->pluck('report_id')` |
| `DPReport::where('status', DPReport::STATUS_FORWARDED_TO_COORDINATOR)->whereIn('project_id', $ids)->pluck('report_id')` | `$allReports->where('status', DPReport::STATUS_FORWARDED_TO_COORDINATOR)->whereIn('project_id', $ids)->pluck('report_id')` |
| `DPReport::approved()->whereBetween('created_at', [...])->pluck('report_id')` | `$allReports->whereIn('status', DPReport::APPROVED_STATUSES)->filter(fn ($r) => $r->created_at >= $monthStart && $r->created_at <= $monthEnd)->pluck('report_id')` |
| `DPReport::approved()->where('project_id', $p->project_id)->pluck('report_id')` | `$allReports->whereIn('status', DPReport::APPROVED_STATUSES)->where('project_id', $p->project_id)->pluck('report_id')` |

**Locations updated:**
1. Total approved/unapproved expenses (lines ~2028–2042)
2. Budget by project type — approved & unapproved (lines ~2062–2074)
3. Budget by province — approved & unapproved (lines ~2101–2114)
4. Budget by center — approved & unapproved (lines ~2142–2155)
5. Budget by provincial (lines ~2185–2191)
6. Expense trends (6-month loop) (lines ~2211–2219)
7. Top projects by budget (lines ~2233–2239)

**Note:** `DPReport::APPROVED_STATUSES` and `DPReport::STATUS_FORWARDED_TO_COORDINATOR` remain in use as constants for filter values; they do not execute database queries.

---

## 3. Verification Results

### 3.1 No DPReport Query Calls Remain

Confirmed that inside `getSystemBudgetOverviewData` there are no `DPReport::approved()` or `DPReport::where(...)` calls. All `DPReport::` usages are constant references only.

### 3.2 Budget Semantics Preserved

- **Approved reports:** Same set as before — `whereIn('status', DPReport::APPROVED_STATUSES)` matches the former `DPReport::approved()` scope.
- **Unapproved (pending) reports:** Same set — `where('status', DPReport::STATUS_FORWARDED_TO_COORDINATOR)` unchanged.
- **Expense aggregation:** `DPAccountDetail::whereIn('report_id', $reportIds)` continues to receive the same report IDs as before; no change to expense calculation logic.

### 3.3 Data Scope

`$allReports` is loaded in the controller as:

```php
$allReports = DPReport::whereIn('project_id', $teamProjects->pluck('project_id'))
    ->with('user')
    ->get();
```

This matches the scope previously queried per section in the widget. Budget totals and breakdowns are expected to be identical.

---

## 4. Final Architecture Confirmation

### Pipeline (unchanged)

```
DatasetCacheService::getCoordinatorDataset($coordinator, $fy, $filters)
    ↓
ProjectFinancialResolver::resolveCollection($teamProjects)
    ↓
DPReport::whereIn('project_id', $teamProjects->pluck('project_id'))->with('user')->get()  ← single load
    ↓
$projectsByProvince, $reportsByProvince, $approvedProjectsByProvince
    ↓
Widgets (shared dataset + partitions + resolved map)
```

### Widget Data Sources

| Widget | Project source | Report source | DPReport queries |
|--------|----------------|---------------|------------------|
| getSystemBudgetOverviewData | $teamProjects | $allReports | 0 |

All seven audited Phase 6 widgets now consume only shared data. No duplicate Project or DPReport queries inside widgets. System is ready for Phase 7 (Coordinator Dashboard Cache).

---

## 5. References

- `Phase6_SharedDataset_Audit.md`
- `Coordinator_Dashboard_Implementation_Roadmap.md`
