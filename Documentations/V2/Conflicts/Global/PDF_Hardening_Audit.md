# PDF Hardening Audit

**Phase:** 3 – Hardening  
**Date:** 2026-02-12  
**Scope:** Type safety and defensive stability for PDF export.

---

## 1. Modules Reviewed

### 1.1 PDF Partials (Blade Views)

| Module | File | Risk (Before) | Defensive Improvements Added |
|--------|------|----------------|-----------------------------|
| IAH | `IAH/budget_details.blade.php` | Phase 1 – already had Collection guard | None (already hardened) |
| IAH | `IAH/documents.blade.php` | `$files->count()` on non-Collection | `instanceof \Illuminate\Support\Collection` guard |
| IAH | `IAH/earning_members.blade.php` | `$IAHEarningMembers->count()` on wrong type | `isset` + `instanceof Collection` guard |
| IGE | `IGE/budget.blade.php` | `$IGEbudget->isNotEmpty()` if boolean | `instanceof \Illuminate\Support\Collection` guard |
| IGE | `IGE/ongoing_beneficiaries.blade.php` | `$ongoingBeneficiaries->isNotEmpty()` if null | `isset` + `instanceof Collection` guard |
| IGE | `IGE/new_beneficiaries.blade.php` | `$newBeneficiaries->isNotEmpty()` if null | `isset` + `instanceof Collection` guard |
| IGE | `IGE/beneficiaries_supported.blade.php` | `$beneficiariesSupported->count()` if wrong type | `isset` + `instanceof Collection` guard |
| IES | `IES/estimated_expenses.blade.php` | `$expenseDetails->count()` if non-Collection | `isset` + `instanceof Collection` guard |
| IES | `IES/family_working_members.blade.php` | `$familyMembers->count()` if wrong type | Normalize to Collection + `instanceof` guard |
| IES | `IES/attachments.blade.php` | `$files->count()` on non-Collection | `instanceof \Illuminate\Support\Collection` guard |
| IIES | `IIES/estimated_expenses.blade.php` | `$expenseDetails->count()` if non-Collection | `isset` + `instanceof Collection` guard |
| IIES | `IIES/family_working_members.blade.php` | `$familyMembers->count()` if wrong type | `isset` + `instanceof Collection` guard |
| IIES | `IIES/attachments.blade.php` | `$files->count()` on non-Collection | `instanceof \Illuminate\Support\Collection` guard |
| ILP | `ILP/budget.blade.php` | `$budgets->count()` if wrong type | `instanceof \Illuminate\Support\Collection` guard |
| ILP | `ILP/attached_docs.blade.php` | `$files->count()` on non-Collection | `instanceof \Illuminate\Support\Collection` guard |
| RST | `RST/beneficiaries_area.blade.php` | `$RSTBeneficiariesArea->isNotEmpty()` if boolean | `instanceof \Illuminate\Support\Collection` guard |
| RST | `RST/geographical_area.blade.php` | `$RSTGeographicalArea->count()` if wrong type | `isset` + `instanceof Collection` guard |
| RST | `RST/target_group_annexure.blade.php` | `$RSTTargetGroupAnnexure->isNotEmpty()` if wrong type | `isset` + `instanceof Collection` guard |
| CCI | `CCI/annexed_target_group.blade.php` | `$annexedTargetGroup->isNotEmpty()` if boolean | `instanceof \Illuminate\Support\Collection` guard |
| Edu-RUT | `Edu-RUT/annexed_target_group.blade.php` | `$annexedTargetGroups->count()` if wrong type | `isset` + `instanceof Collection` guard |
| Edu-RUT | `Edu-RUT/target_group.blade.php` | `$RUTtargetGroups->count()` if wrong type | `isset` + `instanceof Collection` guard |
| LDP | `LDP/target_group.blade.php` | `$LDPtargetGroups->isNotEmpty()` if wrong type | `isset` + `instanceof Collection` guard |
| Shared | `attachments.blade.php` | `$project->attachments->count()` if null | `isset` + `instanceof Collection` guard |
| Shared | `logical_framework.blade.php` | `$project->objectives`, `$objective->risks`, `$objective->activities`, `$activity->timeframes` | `?? collect()` + `instanceof Collection` guards |
| Shared | `sustainability.blade.php` | `$project->sustainabilities` if null | `?? collect()` for forelse |
| Shared | `budget.blade.php` | `$project->budgets` if null | `?? collect()` for forelse and sum |

### 1.2 ExportController

| Location | Check | Result |
|----------|-------|--------|
| `downloadPdf()` | Data passed to PDF view | Uses `ProjectDataHydrator`; no `->first()` or `->exists()` for PDF data |
| `User::where()->first()` | General user lookup | Used for `projectRoles` only; not passed as collection variable |
| PhpWord methods | `->count()` on relationships | Used for Word export; project loaded with relationships |

**Conclusion:** No boolean or single-model misuse for PDF data path. PDF data comes from `ProjectDataHydrator`, which uses controller `->show()` methods that return Collections or models.

---

## 2. Risk Levels

| Risk | Level | Mitigation |
|------|-------|------------|
| Boolean passed to `@foreach` | **High** → Low | All loop variables now guarded with `instanceof \Illuminate\Support\Collection` |
| Null on `->count()` / `->isNotEmpty()` | **Medium** → Low | `isset()` and `?? collect()` added before method calls |
| Single model where Collection expected | **High** → Low | Phase 1 fixed IAH; hydrator uses controllers that return Collections |
| Missing `$project->objectives` / `sustainabilities` | **Medium** → Low | `?? collect()` in logical_framework and sustainability |

---

## 3. Defensive Improvements Added

### 3.1 Collection Guards (Pattern)

```blade
@if($var instanceof \Illuminate\Support\Collection && $var->count() > 0)
    @foreach($var as $item)
        ...
    @endforeach
@endif
```

Or for `isNotEmpty()`:

```blade
@if(isset($var) && $var instanceof \Illuminate\Support\Collection && $var->isNotEmpty())
```

### 3.2 Null-Safe Iteration

```blade
@foreach(($project->objectives ?? collect()) as $objective)
```

### 3.3 Dev-Only Type Logging

- **Location:** `ExportController::logUnexpectedPdfDataTypes()`
- **When:** Before rendering PDF view
- **Condition:** Only when `APP_ENV=local` or `APP_ENV=development`
- **Logged:** Boolean or non-Collection/non-array for keys: `IAHBudgetDetails`, `IGEbudget`, `RSTBeneficiariesArea`, etc.
- **Production:** No logging; method returns immediately

---

## 4. Files Modified

| File | Changes |
|------|---------|
| `resources/views/projects/partials/Show/IGE/budget.blade.php` | Collection guard |
| `resources/views/projects/partials/Show/IGE/ongoing_beneficiaries.blade.php` | isset + Collection guard |
| `resources/views/projects/partials/Show/IGE/new_beneficiaries.blade.php` | isset + Collection guard |
| `resources/views/projects/partials/Show/IGE/beneficiaries_supported.blade.php` | isset + Collection guard |
| `resources/views/projects/partials/Show/IAH/documents.blade.php` | Collection guard for `$files` |
| `resources/views/projects/partials/Show/IAH/earning_members.blade.php` | isset + Collection guard |
| `resources/views/projects/partials/Show/IES/estimated_expenses.blade.php` | isset + Collection guard |
| `resources/views/projects/partials/Show/IES/family_working_members.blade.php` | Normalize + Collection guard |
| `resources/views/projects/partials/Show/IES/attachments.blade.php` | Collection guard for `$files` |
| `resources/views/projects/partials/Show/IIES/estimated_expenses.blade.php` | isset + Collection guard |
| `resources/views/projects/partials/Show/IIES/family_working_members.blade.php` | isset + Collection guard |
| `resources/views/projects/partials/Show/IIES/attachments.blade.php` | Collection guard for `$files` |
| `resources/views/projects/partials/Show/ILP/budget.blade.php` | Collection guard |
| `resources/views/projects/partials/Show/ILP/attached_docs.blade.php` | Collection guard for `$files` |
| `resources/views/projects/partials/Show/RST/beneficiaries_area.blade.php` | Collection guard |
| `resources/views/projects/partials/Show/RST/geographical_area.blade.php` | isset + Collection guard |
| `resources/views/projects/partials/Show/RST/target_group_annexure.blade.php` | isset + Collection guard |
| `resources/views/projects/partials/Show/CCI/annexed_target_group.blade.php` | Collection guard |
| `resources/views/projects/partials/Show/Edu-RUT/annexed_target_group.blade.php` | isset + Collection guard |
| `resources/views/projects/partials/Show/Edu-RUT/target_group.blade.php` | isset + Collection guard |
| `resources/views/projects/partials/Show/LDP/target_group.blade.php` | isset + Collection guard |
| `resources/views/projects/partials/Show/attachments.blade.php` | isset + Collection guard |
| `resources/views/projects/partials/Show/logical_framework.blade.php` | null-safe objectives, risks, activities, timeframes |
| `resources/views/projects/partials/Show/sustainability.blade.php` | null-safe sustainabilities |
| `resources/views/projects/partials/Show/budget.blade.php` | null-safe budgets |
| `app/Http/Controllers/Projects/ExportController.php` | `logUnexpectedPdfDataTypes()` + dev-only invocation |

---

## 5. Validation

- **No functional changes:** Output structure unchanged
- **No query changes:** No database or Eloquent changes
- **No route changes:** None
- **No business logic changes:** Only type checks and null coalescing

---

## 6. Rollback

To revert hardening:

1. Restore each partial from git to remove `instanceof` and `?? collect()` guards.
2. Remove `logUnexpectedPdfDataTypes()` and its call from `ExportController`.
