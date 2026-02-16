# M3.3 Wave 1.1 — Stage-Separated Dashboard Aggregation

**Milestone:** M3 — Resolver Parity & Financial Stability  
**Wave:** M3.3 Wave 1.1 — Stage-Separated Dashboard Aggregation  
**Scope:** Separate Approved Portfolio vs Pending Requests  
**Mode:** Controlled Refinement (No Scope Creep)  
**Date:** 2025-02-15

---

## OBJECTIVE

Dashboards must clearly separate:

1. **Approved Portfolio (Actual Funded Capital)** — real available funds
2. **Pending Requests (Unapproved Funding Demand)** — central funding demand

They must NEVER mix these two.

---

## FINANCIAL RULES

For each project:

```
IF status == approved:
    Approved Portfolio Contribution = opening_balance

ELSE:
    Pending Request Contribution =
        overall_project_budget - (amount_forwarded + local_contribution)
```

Definitions:
- **Approved Portfolio** = Real available funds (opening_balance)
- **Pending Requests** = Central funding demand (amount requested from sanctioning authority)

---

## 1) Files Modified

| File | Location | Change |
|------|----------|--------|
| `app/Http/Controllers/ProvincialController.php` | `calculateCenterPerformance` | Added `pending_budget` per center; kept `budget` as approved portfolio |
| `app/Http/Controllers/ProvincialController.php` | `calculateEnhancedBudgetData` | Added `pending_total` and `approved_total` to total; kept `budget` as approved |
| `app/Http/Controllers/CoordinatorController.php` | `getSystemBudgetOverviewData` | Added `pending_total` and `approved_total` to total; kept `budget` as approved |

---

## 2) Before Aggregation Logic

### ProvincialController — calculateCenterPerformance

- Only `budget` (approved projects, opening_balance) per center.
- No pending budget.

### ProvincialController — calculateEnhancedBudgetData

- Only `total.budget` (approved projects, opening_balance).
- No pending_total.

### CoordinatorController — getSystemBudgetOverviewData

- Only `total.budget` (approved projects, opening_balance).
- No pending_total.

---

## 3) After Aggregation Logic

### ProvincialController — calculateCenterPerformance

```php
$approvedProjects = $centerProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);
$pendingProjects = $centerProjects->filter(fn ($p) => ! ProjectStatus::isApproved($p->status ?? ''));

$centerBudget = (float) ($approvedProjects->sum(fn ($p) => (float) ($p->opening_balance ?? 0)) ?? 0);
$centerPendingBudget = (float) $pendingProjects->sum(function ($p) {
    $overall = (float) ($p->overall_project_budget ?? 0);
    $forwarded = (float) ($p->amount_forwarded ?? 0);
    $local = (float) ($p->local_contribution ?? 0);
    return max(0, $overall - ($forwarded + $local));
});

$centerPerformance[$center] = [
    // ...
    'budget' => $centerBudget,
    'pending_budget' => $centerPendingBudget,
    // ...
];
```

### ProvincialController — calculateEnhancedBudgetData

```php
$pendingProjects = Project::whereIn('user_id', $accessibleUserIds)
    ->whereNotIn('status', [APPROVED_BY_COORDINATOR, APPROVED_BY_GENERAL_AS_COORDINATOR, APPROVED_BY_GENERAL_AS_PROVINCIAL])
    ->get();

$pendingTotal = (float) $pendingProjects->sum(function ($p) {
    $overall = (float) ($p->overall_project_budget ?? 0);
    $forwarded = (float) ($p->amount_forwarded ?? 0);
    $local = (float) ($p->local_contribution ?? 0);
    return max(0, $overall - ($forwarded + $local));
});

return [
    'total' => [
        'budget' => $totalBudget,
        'approved_total' => $totalBudget,
        'pending_total' => $pendingTotal,
        // ...
    ],
    // ...
];
```

### CoordinatorController — getSystemBudgetOverviewData

```php
$pendingProjectsQuery = Project::whereNotIn('status', [
    APPROVED_BY_COORDINATOR,
    APPROVED_BY_GENERAL_AS_COORDINATOR,
    APPROVED_BY_GENERAL_AS_PROVINCIAL,
])->with(['user']);
// Apply same filters as approvedProjectsQuery (province, center, project_type, parent_id, role)

$pendingProjects = $pendingProjectsQuery->get();
$pendingTotal = (float) $pendingProjects->sum(function ($p) {
    $overall = (float) ($p->overall_project_budget ?? 0);
    $forwarded = (float) ($p->amount_forwarded ?? 0);
    $local = (float) ($p->local_contribution ?? 0);
    return max(0, $overall - ($forwarded + $local));
});

return [
    'total' => [
        'budget' => $totalBudget,
        'approved_total' => $totalBudget,
        'pending_total' => $pendingTotal,
        // ...
    ],
    // ...
];
```

---

## 4) Approved Portfolio Formula

```
Approved Portfolio Contribution = opening_balance
```

For projects with `status` in `[APPROVED_BY_COORDINATOR, APPROVED_BY_GENERAL_AS_COORDINATOR, APPROVED_BY_GENERAL_AS_PROVINCIAL]`.

- Source: `projects.opening_balance` (persisted at approval)
- Meaning: Total available funds for execution (sanctioned + forwarded + local)

---

## 5) Pending Request Formula

```
Pending Request Contribution = max(0, overall_project_budget - (amount_forwarded + local_contribution))
```

For projects with `status` not approved.

- Source: `projects.overall_project_budget`, `amount_forwarded`, `local_contribution` (DB columns)
- Meaning: Central funding demand — amount requested from sanctioning authority
- `max(0, ...)` ensures no negative values (e.g. when forwarded + local exceed budget)

---

## 6) Why This Ensures Governance Clarity

| Before | After |
|--------|-------|
| Single "budget" total (approved only) | Separate `approved_total` and `pending_total` |
| Pending demand invisible | Pending demand explicitly surfaced |
| Risk of conflating funded vs requested | Clear separation: Actual Funded Capital vs Unapproved Funding Demand |

Governance benefits:
- **Approved Portfolio**: What is actually funded and available
- **Pending Requests**: What is being requested but not yet sanctioned
- No mixing of concepts; dashboards can show both without ambiguity
- Supports planning (how much is in pipeline) and execution (how much is deployed)

---

## 7) Performance Considerations

| Aspect | Notes |
|--------|-------|
| **Extra queries** | ProvincialController: 1 extra query for pending projects in calculateEnhancedBudgetData; calculateCenterPerformance uses already-loaded centerProjects (no extra query) |
| **CoordinatorController** | 1 extra query for pending projects; same filters as approved; minimal select (with user for filters) |
| **Memory** | Pending projects loaded into memory; same pattern as approved projects; no resolver calls for pending |
| **Caching** | CoordinatorController budget overview still cached (15 min); pending_total computed inside cached callback |

---

## 8) Risk Assessment

**MEDIUM, controlled.**

| Risk | Mitigation |
|------|------------|
| **Display change** | Views must consume `approved_total`, `pending_total`, `pending_budget` where needed; `budget` remains backward compatible (approved portfolio) |
| **Pending formula** | For phase-based projects, `overall_project_budget` in DB may be stale; acceptable for "central funding demand" approximation |
| **Negative values** | `max(0, ...)` prevents negative pending contributions |
| **Extra load** | One additional query per dashboard; filters limit scope; acceptable |

---

## Not Modified (Per Scope)

- ProjectFinancialResolver
- Services (BudgetValidationService, etc.)
- Exports
- Reports
- PDF logic
- Status/approval logic
- Schema
- Expense allocation logic

---

**End of M3.3 Wave 1.1**
