# Phase 4.5 — Lightweight Dataset Projection Implementation

**Date:** 2026-03-05  
**Phase:** Phase 4.5 — Lightweight Dataset Projection  
**Reference:** Phase4_5_LightweightDataset_Feasibility_Audit.md, Provincial_Dashboard_FY_Architecture_Implementation_Plan.md  

---

## 1. Phase Overview

Phase 4.5 implements **project column projection** in the Provincial Dashboard dataset. Instead of loading full Eloquent models (SELECT *), the dataset query now selects only the columns required by widgets and the ProjectFinancialResolver. Relations (user, reports.accountDetails, budgets) remain eager-loaded and unchanged. This reduces project attribute payload and cache serialization size without altering runtime logic.

---

## 2. Purpose of Lightweight Dataset Projection

- **Reduce memory usage:** Fewer project columns per model (~16 vs 50+ previously).
- **Smaller cache payload:** Serialized cached dataset is smaller.
- **Preserve behaviour:** Resolver and widgets receive the same structure; only hidden attributes are excluded.
- **Compatibility:** Full backward compatibility; no widget or resolver logic changes.

---

## 3. Files Modified

| File | Change |
|------|--------|
| `app/Services/DatasetCacheService.php` | Added `$select` array and `->select($select)` to both query paths (general user bypass and cached) |

**No other files modified.** Controller, resolver, and widget logic unchanged.

---

## 4. DatasetCacheService Query Changes

### Before

```php
return ProjectQueryService::forProvincial($provincial, $fy)
    ->with($with)
    ->get();
```

### After

```php
$select = [
    'id',
    'project_id',
    'province_id',
    'society_id',
    'project_type',
    'user_id',
    'in_charge',
    'commencement_month_year',
    'opening_balance',
    'amount_sanctioned',
    'amount_forwarded',
    'local_contribution',
    'overall_project_budget',
    'status',
    'current_phase',
    'project_title',
];

return ProjectQueryService::forProvincial($provincial, $fy)
    ->select($select)
    ->with($with)
    ->get();
```

**Order:** `select()` is applied before `with()`. Both general user path and cached path use the same projection.

---

## 5. Project Column Projection List

| Column | Purpose |
|--------|---------|
| id | Primary key; required for Eloquent model identity and relation lookups |
| project_id | Display, resolver, widget identification |
| province_id | FY/scope filtering, grouping |
| society_id | Society association |
| project_type | Strategy selection (resolver), chart grouping |
| user_id | User relation FK, center lookup |
| in_charge | In-charge association |
| commencement_month_year | FY filtering |
| opening_balance | ProjectFinancialResolver |
| amount_sanctioned | ProjectFinancialResolver |
| amount_forwarded | ProjectFinancialResolver |
| local_contribution | ProjectFinancialResolver |
| overall_project_budget | ProjectFinancialResolver |
| status | Widgets, resolver (isApproved) |
| current_phase | PhaseBasedBudgetStrategy |
| project_title | Enhanced budget top projects |

---

## 6. Relation Retention Explanation

The following relations remain eager-loaded:

| Relation | Purpose |
|----------|---------|
| user | center, name — grouping and display in widgets |
| reports.accountDetails | Expense totals (approved/unapproved) |
| budgets | PhaseBasedBudgetStrategy |

Relations are **not** reduced or projected. Only project columns are projected. Widgets and resolver continue to access these relations as before.

---

## 7. Resolver Compatibility Verification

ProjectFinancialResolver requires:

- **Project attributes:** project_id, project_type, status, opening_balance, amount_sanctioned, amount_forwarded, local_contribution, overall_project_budget, current_phase — all included.
- **budgets relation:** Eager-loaded; PhaseBasedBudgetStrategy uses it.
- **DirectMappedIndividualBudgetStrategy:** Uses type-specific relations via loadMissing (not in eager load); behaviour unchanged from pre–Phase 4.5.

No resolver changes; projection includes all required project fields.

---

## 8. Widget Compatibility Verification

| Widget | Project Fields Used | Included in Projection |
|--------|---------------------|------------------------|
| calculateTeamPerformanceMetrics | project_id, status | ✓ |
| prepareChartDataForTeamPerformance | project_id, status, project_type | ✓ |
| calculateCenterPerformance | project_id, user_id, in_charge, status | ✓ |
| calculateEnhancedBudgetData | project_id, user_id, project_type, project_title, status | ✓ |
| prepareCenterComparisonData | (delegates to calculateCenterPerformance) | ✓ |

User (center, name), reports, accountDetails remain via relations. No widget changes.

---

## 9. Dataset Cache Compatibility

- **Serialization:** Eloquent models with `select()` serialize correctly; attributes and loaded relations are stored.
- **Deserialization:** Cache::remember unserializes the collection; models retain projected attributes and relations.
- **Cache invalidation:** clearProvincialDataset() unchanged; cache key and TTL unchanged.

Verified via tinker: dataset loads with projection; project_id, user, reports, budgets present.

---

## 10. Performance Impact

Per Phase4_5_LightweightDataset_Feasibility_Audit.md:

- **Project column payload:** ~10–15% memory reduction for project attributes.
- **Cache size:** Estimated 5–15% smaller serialized payload.
- **Query time:** Marginal improvement; relations dominate query cost.

Relations (user, reports.accountDetails, budgets) remain the dominant memory contributor; full relation projection would require widget/resolver refactors (out of scope for Phase 4.5).

---

## 11. Verification Checklist

| # | Verification | Status |
|---|--------------|--------|
| 1 | Dataset query uses select() projection | ✓ |
| 2 | Required project fields included (id, project_id, resolver fields, widget fields) | ✓ |
| 3 | Required relations (user, reports.accountDetails, budgets) eager-loaded | ✓ |
| 4 | Resolver calculations compatible (no missing project attributes) | ✓ |
| 5 | Widget calculations compatible (project attributes + relations present) | ✓ |
| 6 | Dataset cache serializes/deserializes correctly | ✓ |
| 7 | No runtime logic changed (controller, resolver, widgets) | ✓ |
| 8 | General user path uses same projection | ✓ |
| 9 | Cached path uses same projection | ✓ |

---

## Summary

Phase 4.5 adds project column projection to DatasetCacheService::getProvincialDataset(). Sixteen project columns are selected; relations remain unchanged. Dashboard behaviour is unchanged; memory and cache payload are reduced. Implementation is backward compatible and requires no changes to controllers, widgets, or resolver.
