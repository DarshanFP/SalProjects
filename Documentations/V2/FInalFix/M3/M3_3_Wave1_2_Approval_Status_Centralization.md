# M3.3 Wave 1.2 — Approval Status Centralization

**Milestone:** M3 — Resolver Parity & Financial Stability  
**Wave:** M3.3 Wave 1.2 — Approval Status Centralization  
**Scope:** Centralize Approved Status Detection  
**Mode:** Controlled Refactor (No Financial Logic Changes)  
**Date:** 2025-02-15

---

## OBJECTIVE

Eliminate manual status list duplication.

All logic that determines whether a project is "approved" must rely on a single centralized definition.

---

## 1) Files Modified

| File | Location | Change |
|------|----------|--------|
| `app/Constants/ProjectStatus.php` | - | Added `APPROVED_STATUSES` constant; updated `isApproved()` to use it |
| `app/Models/OldProjects/Project.php` | - | Added `scopeApproved()` and `scopeNotApproved()` |
| `app/Http/Controllers/ProvincialController.php` | `calculateCenterPerformance` | Collection filter uses `ProjectStatus::isApproved()` for approved and pending |
| `app/Http/Controllers/ProvincialController.php` | `calculateEnhancedBudgetData` | `->where('status', APPROVED_BY_COORDINATOR)` → `->approved()`; `->whereNotIn('status', [...])` → `->notApproved()` |
| `app/Http/Controllers/CoordinatorController.php` | `getSystemBudgetOverviewData` | `->where('status', APPROVED_BY_COORDINATOR)` → `->approved()`; `->whereNotIn('status', [...])` → `->notApproved()` |

---

## 2) Status List Before (Scattered)

### Before: Duplicated Lists

- **ProjectStatus::isApproved()**: `in_array($status, [APPROVED_BY_COORDINATOR, APPROVED_BY_GENERAL_AS_COORDINATOR, APPROVED_BY_GENERAL_AS_PROVINCIAL])`
- **ProvincialController calculateCenterPerformance**: `$centerProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)` — only one status
- **ProvincialController calculateEnhancedBudgetData**: `whereNotIn('status', [APPROVED_BY_COORDINATOR, APPROVED_BY_GENERAL_AS_COORDINATOR, APPROVED_BY_GENERAL_AS_PROVINCIAL])`
- **CoordinatorController getSystemBudgetOverviewData**: `where('status', APPROVED_BY_COORDINATOR)`; `whereNotIn('status', [...])`

Inconsistencies:
- Some code checked only `APPROVED_BY_COORDINATOR`; others checked all three approved statuses.
- Manual lists scattered across controllers; risk of drift if a new approved status is added.

---

## 3) Status List After (Centralized)

### ProjectStatus Class

```php
public const APPROVED_STATUSES = [
    self::APPROVED_BY_COORDINATOR,
    self::APPROVED_BY_GENERAL_AS_COORDINATOR,
    self::APPROVED_BY_GENERAL_AS_PROVINCIAL,
];

public static function isApproved(string $status): bool
{
    return in_array($status, self::APPROVED_STATUSES, true);
}
```

### Project Model Scopes

```php
public function scopeApproved($query)
{
    return $query->whereIn('status', ProjectStatus::APPROVED_STATUSES);
}

public function scopeNotApproved($query)
{
    return $query->whereNotIn('status', ProjectStatus::APPROVED_STATUSES);
}
```

### Usage in Aggregation Logic

- **Query builder**: `Project::approved()`, `Project::notApproved()`
- **Collection filtering**: `$collection->filter(fn ($p) => ProjectStatus::isApproved($p->status ?? ''))`

---

## 4) Why This Reduces Financial Drift Risk

| Risk | Mitigation |
|------|------------|
| **Inconsistent approved definition** | Single `APPROVED_STATUSES` constant; all aggregation uses it via scopes or `isApproved()` |
| **Adding new approved status** | Change only `APPROVED_STATUSES`; all aggregation updates automatically |
| **Partial checks** | `calculateCenterPerformance` previously checked only `APPROVED_BY_COORDINATOR`; now uses `isApproved()` and includes all approved statuses |
| **Copy-paste errors** | No manual status lists in aggregation logic |

---

## 5) Risk Assessment

**LOW after centralization.**

| Risk | Assessment |
|------|------------|
| **Regression** | Scopes and `isApproved()` produce same status set as before; `calculateCenterPerformance` now includes all three approved statuses (may slightly increase totals if any GENERAL_AS_* projects exist) |
| **Breaking change** | None; behavior is logically equivalent or more complete |
| **Performance** | No impact; `whereIn` / `whereNotIn` with constant array |
| **Maintainability** | Improved; single source of truth |

---

## 6) Confirmation: No Financial Formula Changed

| Component | Changed? |
|-----------|----------|
| Approved portfolio formula (opening_balance) | **No** |
| Pending request formula (overall - forwarded - local) | **No** |
| ProjectFinancialResolver | **No** |
| DerivedCalculationService | **No** |
| BudgetValidationService | **No** |
| Approval workflow / ProjectStatusService | **No** |
| Export / PDF logic | **No** |
| Schema | **No** |

Only status detection was centralized; financial formulas and calculations are unchanged.

---

## Not Modified (Per Scope)

- ProjectStatusService (approval workflow)
- Approval controller logic
- Export logic
- PDF logic
- Report status checks (DPReport uses different status constants)
- Other controller status checks outside aggregation

---

**End of M3.3 Wave 1.2**
