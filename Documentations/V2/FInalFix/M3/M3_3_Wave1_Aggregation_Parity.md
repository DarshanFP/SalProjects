# M3.3 Wave 1 — Aggregation Parity

**Milestone:** M3 — Resolver Parity & Financial Stability  
**Wave:** M3.3 Wave 1 — Aggregation Parity  
**Scope:** Opening Balance Enforcement  
**Mode:** Controlled, Minimal, No Scope Creep  
**Date:** 2025-02-15

---

## OBJECTIVE

Ensure that ALL dashboard and aggregation totals use:

- **Opening Balance**

And NEVER use:

- `amount_sanctioned`
- `overall_project_budget`

for total-fund aggregation.

### Opening Balance Definition

```
IF status == approved:
    opening_balance (persisted)
ELSE:
    amount_forwarded + local_contribution
```

For this wave, only **approved** projects are aggregated in the modified code paths; therefore `opening_balance` (persisted at approval) is used.

---

## 1) Files Modified

| File | Location | Change |
|------|----------|--------|
| `app/Http/Controllers/ProvincialController.php` | `calculateCenterPerformance` | Replaced `sum('amount_sanctioned')` with `sum(fn => opening_balance)` |
| `app/Http/Controllers/CoordinatorController.php` | `getSystemBudgetOverviewData` | Replaced `amount_sanctioned ?? overall_project_budget` with `opening_balance` in all aggregation callbacks |

---

## 2) Aggregation Logic Before

### ProvincialController — calculateCenterPerformance

```php
$centerBudget = $approvedProjects->sum('amount_sanctioned') ?? 0;
```

### CoordinatorController — getSystemBudgetOverviewData

```php
$totalBudget = $approvedProjects->sum(function($p) {
    return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
});

$typeBudget = $projects->sum(function($p) {
    return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
});

$provinceBudget = $projects->sum(function($p) {
    return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
});

$centerBudget = $projects->sum(function($p) {
    return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
});

$provincialBudget = $projects->sum(function($p) {
    return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
});

$topProjectsByBudget = $approvedProjects->sortByDesc(function($p) {
    return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
})->take(10)->map(function($p) {
    $projectBudget = (float) ($p->amount_sanctioned ?? $p->overall_project_budget ?? 0);
    // ...
});
```

---

## 3) Aggregation Logic After

### ProvincialController — calculateCenterPerformance

```php
$centerBudget = (float) ($approvedProjects->sum(fn ($p) => (float) ($p->opening_balance ?? 0)) ?? 0);
```

### CoordinatorController — getSystemBudgetOverviewData

```php
$totalBudget = $approvedProjects->sum(fn ($p) => (float) ($p->opening_balance ?? 0));

$typeBudget = $projects->sum(fn ($p) => (float) ($p->opening_balance ?? 0));

$provinceBudget = $projects->sum(fn ($p) => (float) ($p->opening_balance ?? 0));

$centerBudget = $projects->sum(fn ($p) => (float) ($p->opening_balance ?? 0));

$provincialBudget = $projects->sum(fn ($p) => (float) ($p->opening_balance ?? 0));

$topProjectsByBudget = $approvedProjects->sortByDesc(fn ($p) => (float) ($p->opening_balance ?? 0))->take(10)->map(function ($p) {
    $projectBudget = (float) ($p->opening_balance ?? 0);
    // ...
});
```

---

## 4) Why amount_sanctioned Aggregation Is Removed

- **`amount_sanctioned`** is the portion allocated by the sanctioning authority. It does not include `amount_forwarded` or `local_contribution`.
- **`opening_balance`** = `amount_sanctioned` + `amount_forwarded` + `local_contribution` — total available funds.
- Dashboards must report **total available funds**, not just sanctioned portion. Using `amount_sanctioned` understates center/province/type totals when projects have forwarded or local contributions.
- Provincial `calculateCenterPerformance` previously used `amount_sanctioned`, producing lower totals than other provincial widgets (which use resolver `opening_balance`). This wave aligns center performance with the canonical definition.

---

## 5) Why This Ensures Stage-Safe Totals

- Modified code paths aggregate **only approved projects** (`status = APPROVED_BY_COORDINATOR`).
- For approved projects, `opening_balance` is persisted at approval and is authoritative.
- Using `opening_balance` ensures:
  - Consistent semantics: "total available funds"
  - Alignment with resolver and other dashboards
  - Stage-safe: approved projects always have persisted `opening_balance`
- `?? 0` handles legacy approved projects that may have null `opening_balance` (e.g., pre-M3 approval).

---

## 6) Performance Considerations

- **No extra queries:** Uses existing `$approvedProjects` / `$projects` collections; no additional resolver or DB calls.
- **Minimal memory:** Sum callbacks read `opening_balance` from already-loaded project models.
- **Caching preserved:** CoordinatorController `getSystemBudgetOverviewData` uses 15-minute cache; aggregation runs inside cached callback.
- **Conditional SQL not used:** Projects are already filtered and loaded; switching from `amount_sanctioned` to `opening_balance` does not add N+1 or extra round-trips.

---

## 7) Risk Level

**MEDIUM, controlled.**

| Risk | Mitigation |
|------|------------|
| **Display change** | Center/province/type totals may increase (opening_balance ≥ amount_sanctioned). Stakeholders should be informed. |
| **Legacy data** | Projects approved before M3 may have null `opening_balance`; `?? 0` yields 0. Consider backfill for accurate historical display. |
| **Regression** | Scope limited to 2 files; aggregation logic only; approval flow unchanged. |
| **Performance** | No new queries; same collection iteration; negligible impact. |

---

## Not Modified (Per Scope)

- ProjectFinancialResolver
- ExportController
- PDF logic (pdf.blade.php, PDFReport.blade.php)
- Approval / revert logic (ProjectStatusService, CoordinatorController approveProject)
- DB schema
- Report account details (`report->accountDetails->sum('amount_sanctioned')` — report-level, not project aggregation)
- ReportMonitoringService (report budget utilisation summary)

---

**End of M3.3 Wave 1**
