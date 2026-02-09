# Wave 2B Aggregation Semantic Map

**Date:** 2025-02-09  
**Purpose:** Define what each aggregation represents and which resolver field should replace it. Prevents semantic drift during Wave 2B refactor.

---

## Resolver Field Definitions (Reference)

| Resolver Field | Meaning |
|----------------|---------|
| `overall_project_budget` | Total phase allocation or type-specific total (e.g. sum of this_phase, or IIES total_expenses) |
| `amount_sanctioned` | Committed funds for this phase: overall − (forwarded + local) |
| `opening_balance` | Total available at start: sanctioned + forwarded + local (equals overall for phase-based) |

For **approved** projects, resolver returns DB-stored `amount_sanctioned` and `opening_balance`. For **non-approved**, it computes them.

---

## CoordinatorController

### Method: getSystemPerformanceData

#### 1️⃣ Current Aggregation Logic
```
$totalBudget = $systemProjects->where('status', APPROVED_BY_COORDINATOR)
    ->sum(function($p) { return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0; });

$provinceBudget = $provinceProjects->where('status', APPROVED_BY_COORDINATOR)
    ->sum(function($p) { return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0; });

$totalRemaining = $totalBudget - $totalExpenses;
$budgetUtilization = $totalBudget > 0 ? ($totalExpenses / $totalBudget) * 100 : 0;
```

#### 2️⃣ Current Source Column(s)
- `amount_sanctioned` (primary)
- `overall_project_budget` (fallback when sanctioned null)

#### 3️⃣ Business Meaning
- **System-wide dashboard summary:** Total committed/available budget across all approved projects.
- **Province breakdown:** Budget per province for comparison and utilization.
- Used for dashboard widget: total budget, remaining, utilization, province metrics.

#### 4️⃣ Correct Resolver Replacement Field
**`$financials['opening_balance']`**

Reason: For approved projects, `opening_balance` is the canonical “total available funds” used for utilization and remaining. It equals overall for phase-based projects. Using it keeps utilization = (expenses / opening) and remaining = opening − expenses consistent.

#### 5️⃣ Risk Level
**High** — Affects coordinator dashboard totals and province metrics. Any drift changes displayed numbers.

#### 6️⃣ Performance Considerations
- Inside `Cache::remember` (10 min TTL).
- Loads `$systemProjects = Project::with(['user'])->get()` — all projects.
- Province loop: for each province, filters `$systemProjects`. Resolver would be called per project in sum closure.
- **N+1 risk:** Resolver per project in sum. ~N projects. Consider resolver once per project, memoize in loop, or batch.

---

### Method: getSystemAnalyticsData

#### 1️⃣ Current Aggregation Logic
```
$budgetByMonth = $projectsByMonth->sum(function($p) {
    return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
});

$provinceBudgets[$province] = $provinceProjects->sum(function($p) {
    return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
});

$typeBudgets[$type] = $typeProjects->sum(function($p) {
    return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
});

$provinceBudget = $provinceProjects->where('status', APPROVED_BY_COORDINATOR)
    ->sum(function($p) { return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0; });
```

#### 2️⃣ Current Source Column(s)
- `amount_sanctioned`
- `overall_project_budget` (fallback)

#### 3️⃣ Business Meaning
- **Budget Utilization Timeline:** Cumulative budget of projects approved by month end — for utilization-over-time chart.
- **Budget Distribution by Province / Project Type:** Total budget per province and per type for charts.
- **Province Comparison:** Province-level budget for side-by-side comparison.

#### 4️⃣ Correct Resolver Replacement Field
**`$financials['opening_balance']`**

Reason: Same as above — represents total available funds for utilization and comparisons.

#### 5️⃣ Risk Level
**High** — Drives analytics charts. Affects utilization timeline and province/type breakdowns.

#### 6️⃣ Performance Considerations
- Inside `Cache::remember` (15 min TTL).
- **Monthly loop:** For each month, loads `Project::where(...)->get()` and sums. Resolver N times per month.
- **Province loop:** Loads projects per province.
- **Type loop:** Loads projects per type.
- Heavy resolver usage; memoization or pre-resolution recommended.

---

### Method: getSystemBudgetOverviewData

#### 1️⃣ Current Aggregation Logic
```
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
})->take(10)->map(...);
```

Plus: `$remaining = $budget - $approvedExpenses`, `$utilization = ($approvedExpenses / $budget) * 100`.

#### 2️⃣ Current Source Column(s)
- `amount_sanctioned`
- `overall_project_budget` (fallback)

#### 3️⃣ Business Meaning
- **Budget overview widget:** Total, by type, province, center, provincial.
- **Top projects by budget:** Sorting/display of highest-budget projects.
- Used for coordinator budget overview with filters.

#### 4️⃣ Correct Resolver Replacement Field
**`$financials['opening_balance']`**

Reason: Same — canonical “total available” for utilization, remaining, and rankings.

#### 5️⃣ Risk Level
**High** — Core budget overview. Affects totals, breakdowns, top projects.

#### 6️⃣ Performance Considerations
- Inside `Cache::remember` (15 min, filter-dependent).
- Single `$approvedProjects` collection; grouped by type, province, center, provincial.
- Resolver called per project in each sum. Same project can appear in multiple sums; memoization would help.

---

### Method: getProvinceComparisonData

#### 1️⃣ Current Aggregation Logic
```
$provinceBudget = $provinceProjects->where('status', APPROVED_BY_COORDINATOR)
    ->sum(function($p) {
        return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
    });

$remaining = $provinceBudget - $provinceExpenses;
$utilization = $provinceBudget > 0 ? ($provinceExpenses / $provinceBudget) * 100 : 0;
```

#### 2️⃣ Current Source Column(s)
- `amount_sanctioned`
- `overall_project_budget` (fallback)

#### 3️⃣ Business Meaning
- **Province performance comparison:** Budget per province for rankings (by approval rate, utilization, budget).

#### 4️⃣ Correct Resolver Replacement Field
**`$financials['opening_balance']`**

#### 5️⃣ Risk Level
**High** — Affects province rankings and comparison widget.

#### 6️⃣ Performance Considerations
- Loop over provinces; each loads projects and sums. Resolver per project.

---

### Method: getProvincialManagementData

#### 1️⃣ Current Aggregation Logic
```
$teamBudget = $approvedTeamProjects->sum(function($p) {
    return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
});

$remaining = $teamBudget - $teamExpenses;
$utilization = $teamBudget > 0 ? ($teamExpenses / $teamBudget) * 100 : 0;
```

#### 2️⃣ Current Source Column(s)
- `amount_sanctioned`
- `overall_project_budget` (fallback)

#### 3️⃣ Business Meaning
- **Provincial management dashboard:** Budget per provincial’s team for performance and utilization.

#### 4️⃣ Correct Resolver Replacement Field
**`$financials['opening_balance']`**

#### 5️⃣ Risk Level
**High** — Affects provincial performance scores and team utilization.

#### 6️⃣ Performance Considerations
- Map over provincials; each loads team projects. Resolver per project in sum.

---

### Method: getSystemHealthData

#### 1️⃣ Current Aggregation Logic
```
$totalBudget = $systemProjects->where('status', APPROVED_BY_COORDINATOR)
    ->sum(function($p) {
        return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
    });

$budgetUtilization = $totalBudget > 0 ? ($totalExpenses / $totalBudget) * 100 : 0;
```

#### 2️⃣ Current Source Column(s)
- `amount_sanctioned`
- `overall_project_budget` (fallback)

#### 3️⃣ Business Meaning
- **System health widget:** Total budget for utilization, used in health score.

#### 4️⃣ Correct Resolver Replacement Field
**`$financials['opening_balance']`**

#### 5️⃣ Risk Level
**High** — Health score depends on utilization.

#### 6️⃣ Performance Considerations
- Loads all projects. Single sum over approved projects. Resolver N times.

---

## GeneralController

### Method: getBudgetOverviewData

#### 1️⃣ Current Aggregation Logic
```
$totalBudget = $projects->sum(function($p) {
    return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
});

$typeBudget = $typeProjects->sum(function($p) {
    return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
});

$provinceBudget = $projects->sum(function($p) {
    return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
});

$centerBudget = $projects->sum(function($p) {
    return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
});

$coordinatorBudget = $coordinatorProjects->sum(function($p) {
    return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
});
```

Via helper `$calculateBudgetData` and `$getBreakdownByProjectType`. Remaining and utilization derived from budget.

#### 2️⃣ Current Source Column(s)
- `amount_sanctioned`
- `overall_project_budget` (fallback)

#### 3️⃣ Business Meaning
- **General budget overview:** Coordinator hierarchy vs direct team vs combined.
- Budget by type, province, center, coordinator.
- Used for General dashboard budget widget with context switching.

#### 4️⃣ Correct Resolver Replacement Field
**`$financials['opening_balance']`**

#### 5️⃣ Risk Level
**High** — Core General dashboard budget display.

#### 6️⃣ Performance Considerations
- Loads coordinator hierarchy and direct team projects separately.
- Multiple grouping dimensions. Resolver per project in each sum; memoization recommended.

---

### Method: getSystemPerformanceData

#### 1️⃣ Current Aggregation Logic
```
$totalBudget = $approvedProjects->sum(function($p) {
    return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
});
```

Inside `$calculateMetrics` closure for coordinator hierarchy, direct team, and combined.

#### 2️⃣ Current Source Column(s)
- `amount_sanctioned`
- `overall_project_budget` (fallback)

#### 3️⃣ Business Meaning
- **General performance widget:** Total budget for coordinator hierarchy, direct team, and combined metrics.

#### 4️⃣ Correct Resolver Replacement Field
**`$financials['opening_balance']`**

#### 5️⃣ Risk Level
**High**

#### 6️⃣ Performance Considerations
- `$calculateMetrics` called 3 times. Resolver per project in each call.

---

### Method: getSystemAnalyticsData

#### 1️⃣ Current Aggregation Logic
No direct budget sum in the sections read. May use budget in approval/completion trends. If present, same pattern: `amount_sanctioned ?? overall_project_budget`.

#### 2️⃣ Current Source Column(s)
- Same as above where budget is used.

#### 3️⃣ Business Meaning
- Analytics charts for General; budget used where applicable.

#### 4️⃣ Correct Resolver Replacement Field
**`$financials['opening_balance']`** (where budget is aggregated)

#### 5️⃣ Risk Level
**Medium** — Depends on which analytics use budget.

#### 6️⃣ Performance Considerations
- Time-range loop; project loads per period. Resolver usage scales with periods and projects.

---

### Method: getContextComparisonData

#### 1️⃣ Current Aggregation Logic
```
$coordinatorHierarchyBudget = $coordinatorHierarchyProjects
    ->where('status', APPROVED_BY_COORDINATOR)
    ->sum(function($p) {
        return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
    });

$directTeamBudget = $directTeamProjects
    ->where('status', APPROVED_BY_COORDINATOR)
    ->sum(function($p) {
        return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
    });
```

#### 2️⃣ Current Source Column(s)
- `amount_sanctioned`
- `overall_project_budget` (fallback)

#### 3️⃣ Business Meaning
- **Context comparison widget:** Coordinator hierarchy vs direct team budget and utilization.

#### 4️⃣ Correct Resolver Replacement Field
**`$financials['opening_balance']`**

#### 5️⃣ Risk Level
**High**

#### 6️⃣ Performance Considerations
- Two project sets. Resolver per project in each sum.

---

### Method: getSystemHealthData

#### 1️⃣ Current Aggregation Logic
```
$totalBudget = $approvedProjects->sum(function($p) {
    return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
});
```

Inside `$calculateHealthMetrics` for coordinator hierarchy, direct team, and combined.

#### 2️⃣ Current Source Column(s)
- `amount_sanctioned`
- `overall_project_budget` (fallback)

#### 3️⃣ Business Meaning
- **General health widget:** Budget for utilization in health score for each context.

#### 4️⃣ Correct Resolver Replacement Field
**`$financials['opening_balance']`**

#### 5️⃣ Risk Level
**High**

#### 6️⃣ Performance Considerations
- `$calculateHealthMetrics` called 3 times. Resolver per project.

---

## ProvincialController

### Method: calculateTeamPerformanceMetrics

#### 1️⃣ Current Aggregation Logic
```
$totalBudget = $approvedProjects->sum('amount_sanctioned') ?? 0;
```

Note: Uses `sum('amount_sanctioned')` directly — no fallback to `overall_project_budget`.

#### 2️⃣ Current Source Column(s)
- `amount_sanctioned` only

#### 3️⃣ Business Meaning
- **Provincial dashboard team metrics:** Total budget of approved projects under provincial’s team.

#### 4️⃣ Correct Resolver Replacement Field
**`$financials['opening_balance']`** or **`$financials['amount_sanctioned']`**

- `amount_sanctioned`: if intent is “committed funds” only.
- `opening_balance`: if intent is “total available” (consistent with utilization).

**Recommendation:** `opening_balance` — utilization uses (expenses / budget), so “available” is the right denominator.

#### 5️⃣ Risk Level
**High**

#### 6️⃣ Performance Considerations
- Single sum over approved projects. Resolver per project.

---

### Method: prepareChartDataForTeamPerformance

#### 1️⃣ Current Aggregation Logic
```
foreach ($approvedProjects as $project) {
    $budgetByProjectType[$type] += $project->amount_sanctioned ?? 0;
}

foreach ($approvedProjects as $project) {
    $budgetByCenter[$center] += $project->amount_sanctioned ?? 0;
}
```

#### 2️⃣ Current Source Column(s)
- `amount_sanctioned` only

#### 3️⃣ Business Meaning
- **Chart data:** Budget by project type and by center for provincial team performance charts.

#### 4️⃣ Correct Resolver Replacement Field
**`$financials['opening_balance']`**

#### 5️⃣ Risk Level
**Medium**

#### 6️⃣ Performance Considerations
- Loop over approved projects; accumulator pattern. Resolver per project.

---

### Method: calculateCenterPerformance

#### 1️⃣ Current Aggregation Logic
```
$centerBudget = $approvedProjects->sum('amount_sanctioned') ?? 0;
```

#### 2️⃣ Current Source Column(s)
- `amount_sanctioned` only

#### 3️⃣ Business Meaning
- **Center performance:** Budget per center for provincial team.

#### 4️⃣ Correct Resolver Replacement Field
**`$financials['opening_balance']`**

#### 5️⃣ Risk Level
**Medium**

#### 6️⃣ Performance Considerations
- Loop over centers; each sums approved projects. Resolver per project.

---

### Method: calculateEnhancedBudgetData

#### 1️⃣ Current Aggregation Logic
```
$totalBudget = $approvedProjects->sum('amount_sanctioned') ?? 0;

foreach ($approvedProjects as $project) {
    $byProjectType[$type]['budget'] += $project->amount_sanctioned ?? 0;
    $byProjectType[$type]['remaining'] += ($project->amount_sanctioned ?? 0) - $projectExpenses;
}

foreach ($approvedProjects as $project) {
    $byCenter[$center]['budget'] += $project->amount_sanctioned ?? 0;
    $byCenter[$center]['remaining'] += ($project->amount_sanctioned ?? 0) - $projectExpenses;
}

foreach ($approvedProjects as $project) {
    $byTeamMember[$memberId]['budget'] += $project->amount_sanctioned ?? 0;
    $byTeamMember[$memberId]['remaining'] += ($project->amount_sanctioned ?? 0) - $projectExpenses;
}

$topProjects: 'budget' => $project->amount_sanctioned ?? 0,
              'remaining' => ($project->amount_sanctioned ?? 0) - $projectExpenses;
```

#### 2️⃣ Current Source Column(s)
- `amount_sanctioned` only (with ?? 0 fallback)

#### 3️⃣ Business Meaning
- **Team budget overview widget:** Total and breakdown by type, center, team member.
- **Top projects:** Per-project budget and remaining.

#### 4️⃣ Correct Resolver Replacement Field
**`$financials['opening_balance']`**

For remaining: use `$calc->calculateRemainingBalance($financials['opening_balance'], $projectExpenses)`.

#### 5️⃣ Risk Level
**High**

#### 6️⃣ Performance Considerations
- Multiple loops over same approved projects. Strong case for memoizing `resolve($project)` per project.

---

## ExecutorController

### Method: getChartData

#### 1️⃣ Current Aggregation Logic
```
foreach ($projects as $project) {
    $projectBudget = (float) ($project->amount_sanctioned ?? $project->overall_project_budget ?? 0);
    $budgetByType[$project->project_type] += $projectBudget;
}

$totalBudget = array_sum($budgetByType);
$remaining = $budget - ($expensesByType[$type] ?? 0);
$totalRemaining = $totalBudget - array_sum($expensesByType);
```

#### 2️⃣ Current Source Column(s)
- `amount_sanctioned`
- `overall_project_budget` (fallback)

#### 3️⃣ Business Meaning
- **Executor chart widget:** Budget by project type, budget vs expenses, utilization timeline.
- Executor-scoped: only their approved projects.

#### 4️⃣ Correct Resolver Replacement Field
**`$financials['opening_balance']`**

#### 5️⃣ Risk Level
**Medium** — Executor dashboard charts.

#### 6️⃣ Performance Considerations
- Single loop over executor’s projects. Resolver per project; typically small set.

---

### Method: getQuickStats

#### 1️⃣ Current Aggregation Logic
```
foreach ($approvedProjects as $project) {
    $projectBudget = (float) ($project->amount_sanctioned ?? $project->overall_project_budget ?? 0);
    $totalBudget += $projectBudget;
}

$budgetUtilization = $totalBudget > 0 ? ($totalExpenses / $totalBudget) * 100 : 0;
```

#### 2️⃣ Current Source Column(s)
- `amount_sanctioned`
- `overall_project_budget` (fallback)

#### 3️⃣ Business Meaning
- **Executor quick stats widget:** Total budget and utilization for dashboard summary.

#### 4️⃣ Correct Resolver Replacement Field
**`$financials['opening_balance']`**

#### 5️⃣ Risk Level
**Medium**

#### 6️⃣ Performance Considerations
- Single loop over approved projects. Resolver per project.

---

# Aggregation Replacement Strategy Proposal

## 1. Resolver Field Choice

| Context | Use |
|---------|-----|
| **Dashboard totals** (system, province, team, coordinator) | `opening_balance` |
| **Utilization** (expenses / budget) | `opening_balance` (denominator) |
| **Remaining** (budget − expenses) | `opening_balance` as budget |
| **Approved-only views** | Resolver already uses DB `amount_sanctioned` and `opening_balance` for approved projects |
| **Pre-approval views** | Not in scope; aggregation is only over approved projects |

**Recommendation:** Use `opening_balance` for all aggregation in Wave 2B. It is the canonical “total available funds” and matches how utilization and remaining are computed.

## 2. Resolver Caching / Memoization

- **Problem:** Same project can appear in multiple sums (e.g. by type and by province). Calling `resolve($project)` repeatedly is wasteful.
- **Proposal:** For each method, build a map: `$resolved = []; foreach ($projects as $p) { $resolved[$p->project_id] = $resolver->resolve($p); }`. Use `$resolved[$p->project_id]['opening_balance']` in sum closures.
- **Alternative:** Add optional `ProjectFinancialResolver::resolveMany(Collection $projects): array` that returns project_id => financials, if performance requires it.

## 3. Preloading

- Resolver needs `budgets` (phase-based) or type relations (IIES, etc.). Ensure `Project::with(['budgets', 'iiesExpenses', ...])` or equivalent is used before aggregation.
- Add `loadMissing` or eager loading where resolver’s strategy expects relations.

## 4. Performance Risk Summary

| Method | N (projects) | Loops | Resolver Calls (current) | Memoize? |
|--------|--------------|-------|--------------------------|----------|
| getSystemPerformanceData | All | Province | ~N × provinces | Yes |
| getSystemAnalyticsData | Varies | Month, province, type | High | Yes |
| getSystemBudgetOverviewData | Approved | Type, province, center, provincial | ~4N | Yes |
| getProvinceComparisonData | Per province | Province | ~N | Yes |
| getProvincialManagementData | Per provincial | Provincial | ~N | Yes |
| getSystemHealthData | All | 1 | N | Yes |
| getBudgetOverviewData | 2 sets | Multiple | High | Yes |
| calculateEnhancedBudgetData | Approved | 4 loops | 4N | Yes |
| getChartData | Executor's | 1 | N | Optional |
| getQuickStats | Executor's | 1 | N | Optional |

**Recommendation:** Use per-method memoization (project_id => financials) for all aggregation methods. Instantiate resolver once per method; call `resolve()` once per project; reuse in all sums.

---

*End of Wave 2B Aggregation Semantic Map. No code modified.*
