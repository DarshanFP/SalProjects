# M3.5.1 — Move Approval Semantics Into Project Model

**Milestone:** M3 — Resolver Parity & Financial Stability  
**Step:** M3.5.1 — Move Approval Semantics Into Project Model  
**Mode:** Controlled Refactor (No Logic Change)  
**Date:** 2025-02-15

---

## Why Moved

Approval detection was scattered across controllers and services using `ProjectStatus::isApproved($project->status ?? '')`. Moving it to the Project model:

1. **Single responsibility** — Approval semantics belong to the Project domain; the model should answer "am I approved?"
2. **DRY** — Eliminates repeated `ProjectStatus::isApproved($p->status ?? '')` in financial aggregation and budget logic
3. **Consistency** — All consumers use `$project->isApproved()` instead of duplicating status checks
4. **Future-proof** — If approval rules change, only Project model and ProjectStatus need updating

---

## Files Updated

| File | Change |
|------|--------|
| `app/Models/OldProjects/Project.php` | Added `isApproved(): bool` delegating to `ProjectStatus::isApproved($this->status ?? '')` |
| `app/Http/Controllers/ProvincialController.php` | Replaced `ProjectStatus::isApproved($p->status ?? '')` with `$p->isApproved()` in `calculateCenterPerformance` |
| `app/Http/Controllers/Admin/BudgetReconciliationController.php` | Replaced `ProjectStatus::isApproved($project->status ?? '')` with `$project->isApproved()` |
| `app/Services/Budget/AdminCorrectionService.php` | Replaced `ProjectStatus::isApproved($project->status ?? '')` with `$project->isApproved()` |
| `app/Services/Budget/BudgetSyncGuard.php` | Replaced all `ProjectStatus::isApproved($project->status ?? '')` with `$project->isApproved()`; updated docblock |

---

## Files Not Modified

| File | Reason |
|------|--------|
| `app/Http/Controllers/Projects/ProjectController.php` | `markAsCompleted` uses approval for workflow (not financial aggregation); left as-is per scope |
| `tests/Feature/Budget/FirstTimeApprovalRegressionTest.php` | Tests; scope limited to financial aggregation / reporting logic |

---

## Risk Reduction

| Risk | Before | After |
|------|--------|-------|
| Inconsistent approval checks | Multiple call sites with `ProjectStatus::isApproved($p->status ?? '')` | Single source: `Project::isApproved()` |
| Logic drift | Changing approval rules required updates in many files | Change only in Project model (or ProjectStatus) |
| Typos / misuse | Easy to pass wrong variable or omit `?? ''` | `$project->isApproved()` is unambiguous |

---

## Implementation Detail

```php
// Project model
public function isApproved(): bool
{
    return \App\Constants\ProjectStatus::isApproved($this->status ?? '');
}
```

- No change to `ProjectStatus::isApproved()` or `ProjectStatus::APPROVED_STATUSES`
- No change to status values or approval workflow
- Only call sites in financial aggregation / budget logic were updated

---

**M3.5.1 Complete — Approval Semantics Moved to Model**
