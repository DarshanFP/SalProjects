# UI Binding Correction — General Info Budget Fields

**Objective:** Ensure the General Info section uses `resolvedFundFields` instead of raw `$project` DB fields for all financial values. UI only; resolver and strategy unchanged.

**File modified:** `resources/views/projects/partials/Show/general_info.blade.php`

---

## Before snippet

If the blade had used raw DB fields, it would have looked like:

```blade
@php
    $budget_overall = (float) ($project->overall_project_budget ?? 0);
    $budget_forwarded = (float) ($project->amount_forwarded ?? 0);
    $budget_local = (float) ($project->local_contribution ?? 0);
    $amount_requested = (float) ($project->amount_requested ?? 0);  {{-- not on projects table for all types --}}
    $amount_sanctioned = (float) ($project->amount_sanctioned ?? 0);
@endphp
...
<td class="value">{{ format_indian_currency($project->opening_balance ?? 0, 2) }}</td>
```

---

## After snippet

All financial values come from `$resolvedFundFields` with defensive fallbacks:

```blade
@php
    // UI binding: all financial values from resolver only (no raw $project DB fields)
    $rf = $resolvedFundFields ?? [];
    $budget_overall = (float) ($rf['overall_project_budget'] ?? 0);
    $budget_forwarded = (float) ($rf['amount_forwarded'] ?? 0);
    $budget_local = (float) ($rf['local_contribution'] ?? 0);
    $amount_requested = (float) ($rf['amount_requested'] ?? 0);
    $amount_sanctioned = (float) ($rf['amount_sanctioned'] ?? 0);
    $opening_balance = (float) ($rf['opening_balance'] ?? 0);
@endphp
```

Display (unchanged pattern, now with explicit `$opening_balance` variable):

- Overall Project Budget: `format_indian_currency($budget_overall, 2)`
- Amount Forwarded: `format_indian_currency($budget_forwarded, 2)`
- Local Contribution: `format_indian_currency($budget_local, 2)`
- Amount Requested: `format_indian_currency($amount_requested, 2)`
- Amount Sanctioned: shown only when `@if($project->isApproved())`, then `format_indian_currency($amount_sanctioned, 2)`
- Opening Balance: `format_indian_currency($opening_balance, 2)`

---

## List of fields corrected

| Field | Source (before) | Source (after) |
|-------|------------------|----------------|
| Overall Project Budget | Must not use `$project->overall_project_budget` | `$rf['overall_project_budget']` with `?? 0` |
| Amount Forwarded | Must not use `$project->amount_forwarded` | `$rf['amount_forwarded']` with `?? 0` |
| Local Contribution | Must not use `$project->local_contribution` | `$rf['local_contribution']` with `?? 0` |
| Amount Requested | Must not use `$project->amount_requested` | `$rf['amount_requested']` with `?? 0` |
| Amount Sanctioned | Must not use `$project->amount_sanctioned` | `$rf['amount_sanctioned']` with `?? 0`; row shown only when `$project->isApproved()` |
| Opening Balance | Must not use `$project->opening_balance` | `$rf['opening_balance']` with `?? 0` |

**Note:** This blade was already using `$resolvedFundFields` for all of the above. The change made was: (1) add an explicit `$opening_balance` variable in the `@php` block for consistency and defensive fallback, (2) add a comment that all financial values come from the resolver. No raw `$project->` financial references were present.

---

## Approval guard

- The “Amount Sanctioned” row is wrapped in `@if($project->isApproved())` so it is hidden for non-approved projects.
- For non-approved projects, the resolver sets `amount_sanctioned` to 0; the row is not displayed.

---

## Confirmation that backend is unchanged

- **ProjectFinancialResolver:** not modified.
- **DirectMappedIndividualBudgetStrategy** (and other strategies): not modified.
- **ProjectController@show:** still passes `$data['resolvedFundFields'] = $resolver->resolve($project)`; no change.
- Only the view `resources/views/projects/partials/Show/general_info.blade.php` was touched (comment + explicit `$opening_balance` from `$rf`).

---

## Screenshot verification checklist

- [ ] **IIES project (non-approved):** General Info shows Overall Budget, Amount Forwarded, Local Contribution, Amount Requested, Opening Balance from resolver; no “Amount Sanctioned” row.
- [ ] **IIES project (approved):** General Info shows same fields plus “Amount Sanctioned” row with resolver value.
- [ ] **Phase-based project:** General Info amounts match resolver (no raw project columns).
- [ ] **PDF export:** General Info section in PDF uses same resolver-driven values (export uses same `resolvedFundFields`).
- [ ] **No regression:** Other sections of the project show page (e.g. IIES Estimated Expenses) unchanged.

---

*UI binding correction complete. All General Info financial values are bound to `resolvedFundFields` with defensive null coalescing.*
