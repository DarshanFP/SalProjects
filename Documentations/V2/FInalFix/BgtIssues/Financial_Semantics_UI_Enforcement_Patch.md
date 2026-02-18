# Financial Semantics UI Enforcement Patch

**Mode:** Controlled Enforcement Patch — Financial Semantics Boundary Lock  
**Scope:** UI and dashboard layer only. No resolver, strategies, persistence, or DB schema changes.

---

## 1. Summary of Objective

Enforce canonical financial separation at the UI boundary:

- Non-approved projects must never display raw `$project->amount_sanctioned`.
- Views use resolved fund fields (resolver output) for sanctioned/requested display.
- "Amount Sanctioned" is shown only when `$project->isApproved()` (or hidden when not approved).
- Dashboard metrics that count or sum by sanctioned explicitly filter by approved status.

---

## 2. Files Modified

| File | Change |
|------|--------|
| `resources/views/projects/partials/Show/general_info.blade.php` | Hide "Amount Sanctioned" row when project not approved. |
| `resources/views/projects/partials/not working show/general_info.blade.php` | Use `$resolvedFundFields` with approval guard; label "Amount Requested" when not approved. |
| `resources/views/projects/partials/OLdshow/general_info.blade.php` | Same as above. |
| `resources/views/reports/monthly/developmentProject/reportform.blade.php` | Use controller-passed `$amountSanctioned` (and amount_in_hand from it) instead of `$project->amount_sanctioned`. |
| `app/Http/Controllers/Reports/Monthly/MonthlyDevelopmentProjectController.php` | Resolve financials; pass `$amountSanctioned` = sanctioned when approved, requested when not. |
| `resources/views/projects/partials/Edit/budget.blade.php` | Preview value from `$resolvedFundFields` (sanctioned when approved, requested when not). |
| `app/Http/Controllers/Projects/ProjectController.php` | Pass `resolvedFundFields` to edit view. |
| `resources/views/coordinator/approvedProjects.blade.php` | Display amount from `$resolvedFinancials[$project->project_id]['amount_sanctioned']`. |
| `resources/views/provincial/approvedProjects.blade.php` | Same as above. |
| `app/Http/Controllers/CoordinatorController.php` | Resolve financials for approved list; pass `$resolvedFinancials`; dashboard metric filtered by `whereIn('status', ProjectStatus::APPROVED_STATUSES)`. |
| `app/Http/Controllers/ProvincialController.php` | Resolve financials for approved list; pass `$resolvedFinancials`. |

---

## 3. Before vs After (Relevant Lines Only)

### Show/general_info — Amount Sanctioned row

**Before:** Row always rendered.

```blade
<tr>
    <td class="label">Amount Sanctioned:</td>
    <td class="value">{{ format_indian_currency($amount_sanctioned, 2) }}</td>
</tr>
```

**After:** Row only when approved.

```blade
@if($project->isApproved())
<tr>
    <td class="label">Amount Sanctioned:</td>
    <td class="value">{{ format_indian_currency($amount_sanctioned, 2) }}</td>
</tr>
@endif
```

### Legacy general_info (not working show / OLdshow)

**Before:** Raw DB.

```blade
<div class="info-label"><strong>Amount Sanctioned:</strong></div>
<div class="info-value">Rs. {{ number_format($project->amount_sanctioned, 2) }}</div>
```

**After:** Resolver + approval guard.

```blade
<div class="info-label"><strong>@if($project->isApproved())Amount Sanctioned:@else Amount Requested:@endif</strong></div>
<div class="info-value">Rs. @php $rf = $resolvedFundFields ?? []; @endphp @if($project->isApproved()){{ number_format((float)($rf['amount_sanctioned'] ?? 0), 2) }}@else{{ number_format((float)($rf['amount_requested'] ?? 0), 2) }}@endif</div>
```

### Report form (developmentProject/reportform.blade.php)

**Before:**

```blade
value="{{ $project->amount_sanctioned }}"
value="{{ $project->amount_sanctioned + $project->amount_forwarded }}"
```

**After:**

```blade
value="{{ $amountSanctioned ?? 0 }}"
value="{{ ($amountSanctioned ?? 0) + ($project->amount_forwarded ?? 0) }}"
```

### MonthlyDevelopmentProjectController (create + createForm)

**Before:**

```php
$amountSanctioned = $project->amount_sanctioned ?? 0;
```

**After:**

```php
$resolver = app(ProjectFinancialResolver::class);
$financials = $resolver->resolve($project);
$amountSanctioned = $project->isApproved() ? (float) ($financials['amount_sanctioned'] ?? 0) : (float) ($financials['amount_requested'] ?? 0);
```

### Edit/budget.blade.php preview

**Before:**

```blade
value="{{ old('amount_sanctioned', $project->amount_sanctioned ?? 0) }}"
```

**After:**

```blade
value="{{ old('amount_sanctioned', $project->isApproved() ? (($resolvedFundFields ?? [])['amount_sanctioned'] ?? 0) : (($resolvedFundFields ?? [])['amount_requested'] ?? 0)) }}"
```

### ProjectController edit

**Before:** No `resolvedFundFields` in compact.

**After:** Resolve and pass.

```php
$resolver = app(ProjectFinancialResolver::class);
$resolvedFundFields = $resolver->resolve($project);
// ... compact(..., 'resolvedFundFields', ...)
```

### approvedProjects views (coordinator + provincial)

**Before:**

```blade
<td class="amount-sanctioned">{{ format_indian($project->amount_sanctioned, 2) }}</td>
```

**After:**

```blade
<td class="amount-sanctioned">{{ format_indian((float) (($resolvedFinancials[$project->project_id] ?? [])['amount_sanctioned'] ?? 0), 2) }}</td>
```

### CoordinatorController dashboard metric

**Before:**

```php
'projects_with_amount_sanctioned' => $projects->where('amount_sanctioned', '>', 0)->count(),
```

**After:**

```php
'projects_with_amount_sanctioned' => $projects->whereIn('status', ProjectStatus::APPROVED_STATUSES)->where('amount_sanctioned', '>', 0)->count(),
```

### CoordinatorController + ProvincialController approvedProjects

**After (added):** Resolve per project and pass to view.

```php
$resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
$resolvedFinancials = [];
foreach ($projects as $project) {
    $resolvedFinancials[$project->project_id] = $resolver->resolve($project);
}
return view(..., compact(..., 'resolvedFinancials'));
```

---

## 4. Blade Files Corrected

| Blade file | Correction |
|------------|------------|
| `resources/views/projects/partials/Show/general_info.blade.php` | Amount Sanctioned row wrapped in `@if($project->isApproved())`. |
| `resources/views/projects/partials/not working show/general_info.blade.php` | Use `$resolvedFundFields` with approved/requested label and value. |
| `resources/views/projects/partials/OLdshow/general_info.blade.php` | Same. |
| `resources/views/reports/monthly/developmentProject/reportform.blade.php` | Use `$amountSanctioned` and derived amount_in_hand. |
| `resources/views/projects/partials/Edit/budget.blade.php` | Preview from `$resolvedFundFields` (sanctioned vs requested by approval). |
| `resources/views/coordinator/approvedProjects.blade.php` | Amount from `$resolvedFinancials`. |
| `resources/views/provincial/approvedProjects.blade.php` | Amount from `$resolvedFinancials`. |

---

## 5. Dashboard Metric Change

**CoordinatorController** (dashboard log/metric):

- **Before:** `$projects->where('amount_sanctioned', '>', 0)->count()`
- **After:** `$projects->whereIn('status', ProjectStatus::APPROVED_STATUSES)->where('amount_sanctioned', '>', 0)->count()`

Only projects in approved statuses are counted toward "projects with amount sanctioned."

---

## 6. Confirmation Checklist

- **No resolver changes:** `ProjectFinancialResolver`, `DirectMappedIndividualBudgetStrategy`, `PhaseBasedBudgetStrategy` unchanged.
- **No persistence changes:** No change to approval/revert writes; `BudgetSyncService`, `CoordinatorController`/`GeneralController` approval persistence, `ProjectStatusService` revert unchanged.
- **No schema changes:** No migrations or DB schema edits.
- **No new financial fields:** No new columns or request fields; only display source and filters changed.

---

## 7. Risk Assessment

| Aspect | Before | After |
|--------|--------|--------|
| Non-approved showing sanctioned | Legacy/OLdshow and report form could show raw DB sanctioned. | All display uses resolver; sanctioned row hidden or value 0 when not approved. |
| Dashboard metric | Counted any project with `amount_sanctioned > 0` (could include legacy bad data). | Count restricted to approved statuses. |
| Edit preview | Raw `$project->amount_sanctioned` could be stale. | Resolver-driven; requested when not approved. |
| approvedProjects lists | Raw DB. | Resolver-driven; list remains approved-only. |

**Residual:** Legacy partials (`not working show`, `OLdshow`) require `$resolvedFundFields` when included; if a parent does not pass it, they fall back to `[]` and show 0.

---

## 8. Regression Test Checklist

- [ ] Project show: non-approved project does not show "Amount Sanctioned" row; approved project shows it with correct value.
- [ ] Project show: "Amount Requested" row shows resolver value for both approved and non-approved.
- [ ] Project edit: budget preview shows requested (non-approved) or sanctioned (approved) from resolver.
- [ ] Monthly development report form (create): "Amount Sanctioned" / total use resolver (requested when not approved, sanctioned when approved).
- [ ] Coordinator approved projects list: Amount Sanctioned column matches resolver output.
- [ ] Provincial approved projects list: Amount Sanctioned column matches resolver output.
- [ ] Coordinator dashboard: "projects_with_amount_sanctioned" count only includes approved-status projects.
- [ ] No change to approval or revert flows; sanctioned still persisted on approval and zeroed on revert.

---

*Patch applied. Only UI boundary and dashboard metric changed; resolver, strategies, persistence, and schema untouched.*
