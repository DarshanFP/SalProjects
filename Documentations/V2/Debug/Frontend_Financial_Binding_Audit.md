# Frontend Financial Binding — Forensic Audit (Read-Only)

**Context:** Controller passes `resolvedFundFields`; resolver correctly computes financial values; Blade UI renders 0.00 for project type "Individual - Initial - Educational support". This audit identifies where the frontend layer may break data binding.

**No files were modified. No refactor. No fixes. Facts only.**

---

## SECTION A — Where Financial Values Are Rendered

Search pattern: "Overall Project Budget", "Amount Forwarded", "Local Contribution", "Amount Requested", "Opening Balance" in Blade files.

### A.1 — General Info (show) — primary path for project show

| File | Line(s) | Label | Variable used for rendering | Uses $resolvedFundFields? | Uses $project? | Uses $budget? |
|------|---------|-------|-----------------------------|---------------------------|----------------|---------------|
| resources/views/projects/partials/Show/general_info.blade.php | 106–107 | Overall Project Budget | `$resolvedFundFields['overall_project_budget'] ?? 0` | Yes (direct) | No | No |
| resources/views/projects/partials/Show/general_info.blade.php | 110–111 | Amount Forwarded (Existing Funds) | `$resolvedFundFields['amount_forwarded'] ?? 0` | Yes (direct) | No | No |
| resources/views/projects/partials/Show/general_info.blade.php | 114–115 | Local Contribution | `$resolvedFundFields['local_contribution'] ?? 0` | Yes (direct) | No | No |
| resources/views/projects/partials/Show/general_info.blade.php | 118–119 | Amount Requested | `$resolvedFundFields['amount_requested'] ?? 0` | Yes (direct) | No | No |
| resources/views/projects/partials/Show/general_info.blade.php | 124 (inside @if) | Amount Sanctioned | `$resolvedFundFields['amount_sanctioned'] ?? 0` | Yes (direct) | No | No |
| resources/views/projects/partials/Show/general_info.blade.php | 128–129 | Opening Balance | `$resolvedFundFields['opening_balance'] ?? 0` | Yes (direct) | No | No |

### A.2 — Other blades with same labels (not used in show for IIES)

| File | Line(s) | Label | Variable used | Uses $resolvedFundFields? | Uses $project? | Uses $budget? |
|------|---------|-------|---------------|---------------------------|----------------|---------------|
| resources/views/projects/partials/OLdshow/general_info.blade.php | 50–60 | Overall Project Budget, Amount Forwarded, Amount Requested/Sanctioned, Opening Balance | `$project->overall_project_budget`, `$project->amount_forwarded`, `$project->opening_balance`; `$rf = $resolvedFundFields ?? []` for sanctioned/requested only | Partial (sanctioned/requested only) | Yes (overall, forwarded, opening) | No |
| resources/views/projects/partials/not working show/general_info.blade.php | 50–60 | Same as OLdshow | Same as OLdshow | Partial | Yes | No |
| resources/views/projects/Oldprojects/pdf.blade.php | 795–800 | Amount Requested/Sanctioned; Forwarded; Local | `$resolvedFundFields['amount_sanctioned']`, `$resolvedFundFields['amount_requested']`, `$resolvedFundFields['amount_forwarded']`, `$resolvedFundFields['local_contribution']` | Yes (direct) | No (for these cells) | No |
| resources/views/projects/partials/Show/budget.blade.php | 210–227 | Overall Project Budget, Amount Forwarded, Local Contribution, Opening Balance | `$overallBudget`, `$amountForwarded`, `$localContribution`, `$openingBalance` (set earlier in blade from `$budgetData`) | No | No | Via $budgetData |
| resources/views/provincial/approvedProjects.blade.php | 82 | Amount Forwarded | Table header only | No | No | No |
| resources/views/coordinator/approvedProjects.blade.php | 97 | Amount Forwarded | Table header only | No | No | No |
| resources/views/projects/partials/Edit/budget.blade.php | 75–158 | Labels only (form inputs) | Input values from $project / $resolvedFundFields | Yes (amount_sanctioned preview only) | Yes | No |
| resources/views/reports/monthly/..., provincial/ProjectList.blade.php, etc. | Various | Amount Forwarded, Local, Requested, etc. | $project, $grandTotals, or section-specific vars | No / N/A | Yes / other | No |

**Fact:** The only partial included for "General Information" on project show is `projects.partials.Show.general_info` (see Section C). That partial uses only `$resolvedFundFields` for the six financial fields; no `$project` or `$budget` for those values.

---

## SECTION B — How resolvedFundFields Is Used

Search: all usages of `$resolvedFundFields` in codebase.

### B.1 — Controller / PHP (source of data)

| File | Line | Usage |
|------|------|--------|
| app/Http/Controllers/Projects/ProjectController.php | 1037 | `$data['resolvedFundFields'] = $resolver->resolve($project);` — set once, then view returned with `$data`. |
| app/Http/Controllers/Projects/ProjectController.php | 1298, 1304 | edit(): `$resolvedFundFields = $resolver->resolve($project);` passed to edit view. |
| app/Http/Controllers/Projects/ExportController.php | 513, 517, 601, 628–644 | Resolved and passed to addGeneralInfoSection; keys accessed directly. |
| app/Services/ProjectDataHydrator.php | 284 | `$data['resolvedFundFields'] = $this->financialResolver->resolve($project);` |

### B.2 — Blade (consumption)

| File | Usage | Direct access? | Redefined? | Defaulted `?? []`? | In @php? | Conditionally replaced? |
|------|--------|----------------|------------|---------------------|----------|---------------------------|
| resources/views/projects/partials/Show/general_info.blade.php | Lines 30–31, 107, 111, 115, 119, 124, 129 | Yes (array keys) | Yes (line 31) | Yes: `$resolvedFundFields = $resolvedFundFields ?? [];` | Yes (@php block) | No |
| resources/views/projects/partials/OLdshow/general_info.blade.php | Line 57 | Via `$rf` | No | Yes: `$rf = $resolvedFundFields ?? [];` (local $rf only) | Inline @php | No |
| resources/views/projects/partials/not working show/general_info.blade.php | Line 57 | Same as OLdshow | No | Same | Inline @php | No |
| resources/views/projects/partials/Edit/budget.blade.php | Line 145 | Direct key access | No | `($resolvedFundFields ?? [])` in expression | No | No |
| resources/views/projects/Oldprojects/pdf.blade.php | 796, 800 | Direct key access | No | `?? 0` per key | No | No |
| storage/framework/views/55750c194887ef9971b42f22d47de85c.php | 30–31, 107, 111, 115, 119, 124, 129 | Same as Show/general_info (compiled) | Yes | Yes | Yes | No |

**Fact:** The only blade in the show → General Info path that reassigns or defaulted `$resolvedFundFields` is `partials/Show/general_info.blade.php`: inside an @php block it does `$resolvedFundFields = $resolvedFundFields ?? [];`. If `$resolvedFundFields` is not set or is null when that partial runs, it becomes `[]`, and every `$resolvedFundFields['key'] ?? 0` yields 0.

---

## SECTION C — Include Scope Trace

Trace: ProjectController@show → main view → nested includes.

### C.1 — Controller

- **Method:** `ProjectController@show($project_id)` (around lines 790–1046).
- **Return:** `return view('projects.Oldprojects.show', $data);`
- **$data construction:** `$data` is built (lines 837–893) with many keys; `resolvedFundFields` is **not** in the initial array. It is set later: `$data['resolvedFundFields'] = $resolver->resolve($project);` (line 1037). So the view receives exactly one variable named `resolvedFundFields` (camelCase), with value from the resolver.

### C.2 — Main view

- **View name:** `projects.Oldprojects.show` → file `resources/views/projects/Oldprojects/show.blade.php`.
- **Layout:** `@extends($layout)` where `$layout` is role-based (e.g. `executor.dashboard`). Layout uses `@yield('content')`; no variables are passed to the yield.
- **General Info block:** Lines 117–125:
  ```blade
  <!-- General Information Section -->
  <div id="general-info-section" class="mb-3 card">
      ...
      <div class="card-body">
          @include('projects.partials.Show.general_info')
      </div>
  </div>
  ```
- **Include:** `@include('projects.partials.Show.general_info')` — **no second argument**. So the partial receives the **same scope as the parent** (the show view). There is no explicit pass of `resolvedFundFields` or any other variable.

### C.3 — Scope inheritance

- In Laravel Blade, when a view is rendered with `view('name', $data)`, each key in `$data` becomes a variable in that view.
- When that view uses `@include('partial')` with no second argument, the included partial is evaluated with the **same view data** as the parent. So `$resolvedFundFields` should be in scope for `projects.partials.Show.general_info` if it is in scope for `projects.Oldprojects.show`.
- The show view does not redefine or unset `$resolvedFundFields` before the include. So theoretically the partial has access to the same `$resolvedFundFields` array passed from the controller.

### C.4 — Other includes that use general_info

- **pdf:** `resources/views/projects/Oldprojects/pdf.blade.php` line 219: `@include('projects.partials.Show.general_info')`. PDF view is rendered by a different controller/flow; it must receive `resolvedFundFields` from that flow for the same partial to show non-zero values.

### C.5 — Summary

| Layer | Receives resolvedFundFields explicitly? | Depends on implicit scope? |
|-------|----------------------------------------|----------------------------|
| projects.Oldprojects.show | Yes (from controller via $data) | N/A (root view) |
| projects.partials.Show.general_info | No (not passed as second arg to @include) | Yes (inherits parent scope) |

**Fact:** No include in the show path explicitly passes `resolvedFundFields`. The partial depends entirely on implicit scope inheritance. If the parent scope does not contain `$resolvedFundFields` when the include runs (e.g. layout/compilation quirk, or key name mismatch), the partial would not see it.

---

## SECTION D — Shadowing / Reset Detection

Patterns searched: `$resolvedFundFields =`, `$resolvedFundFields ??`, `isset($resolvedFundFields)`, `empty($resolvedFundFields)`.

### D.1 — Reassignment / default

| File | Line | Code | Effect |
|------|------|------|--------|
| resources/views/projects/partials/Show/general_info.blade.php | 31 | `$resolvedFundFields = $resolvedFundFields ?? [];` | If `$resolvedFundFields` is undefined or null, it is set to `[]`. Thereafter all `$resolvedFundFields['key'] ?? 0` evaluate to 0. |

No other file in the show → General Info path reassigns `$resolvedFundFields`. No `isset` or `empty` checks on `$resolvedFundFields` were found in the codebase.

### D.2 — Wrapping in fallback

- The same line (general_info line 31) is the only place that applies a fallback default. The fallback is `[]`, which effectively resets all financial keys to “missing,” so `?? 0` in the template yields 0.00 for every financial field.

### D.3 — Local variable with same name

- The only reassignment is in `partials/Show/general_info.blade.php` (same variable name, in @php). So there is one place where the variable can be turned into an empty array before the financial rows are rendered.

**Fact:** The only code in the active show path that can set `$resolvedFundFields` to an empty array is the @php block in `projects/partials/Show/general_info.blade.php`. If at the time that partial runs the variable is not set or is null, that line will make it `[]`, and the UI will show 0.00 for all six financial fields.

---

## SECTION E — Legacy Variable Usage

Search: `$project->overall_project_budget`, `$budget->overall`, `$project->amount_requested`, `$project->local_contribution`, and similar.

### E.1 — In show General Info path

- **resources/views/projects/partials/Show/general_info.blade.php:** Does **not** use `$project` for any of the six financial fields. It uses only `$resolvedFundFields['...']`.

### E.2 — In other blades (not in show General Info for IIES)

| File | Line | Legacy usage |
|------|------|--------------|
| resources/views/projects/partials/OLdshow/general_info.blade.php | 51, 54, 60 | `$project->overall_project_budget`, `$project->amount_forwarded`, `$project->opening_balance` |
| resources/views/projects/partials/not working show/general_info.blade.php | 51, 54, 60 | Same |
| resources/views/reports/monthly/developmentProject/reportform.blade.php | 200 | `$project->amount_forwarded` |
| resources/views/projects/partials/Edit/general_info.blade.php | 262, 468, 588 | `$project->overall_project_budget` |
| resources/views/projects/partials/general_info.blade.php | 132 | `old('overall_project_budget')` |
| resources/views/projects/partials/Edit/budget.blade.php | 82, 96, 122, 163, 173, etc. | `$project->overall_project_budget`, `$project->amount_forwarded`, `$project->local_contribution`, `$project->opening_balance`, etc. |

**Fact:** The blade actually used for project show General Info (`partials/Show/general_info.blade.php`) does not use legacy `$project` or `$budget` for those financial fields. Legacy usage exists only in other blades (OLdshow, not working show, Edit, reports), which are not the include used in the show page for General Information.

---

## SECTION F — JS Interference Check

Search: frontend JS for `overall_project_budget`, `amount_requested`, `local_contribution`, and DOM replacements targeting those fields.

### F.1 — In Blade/JS under resources

- **resources/views/projects/partials/scripts-edit.blade.php** and **resources/views/projects/partials/scripts.blade.php** contain JS that references:
  - `overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned`, `opening_balance` (e.g. for calculation and validation).
- These scripts are used on **edit** forms (inputs with ids like `overall_project_budget`, `amount_forwarded`, etc.). The project **show** view (`projects.Oldprojects.show`) does not include `scripts-edit` or `scripts` in the excerpt that was audited; the show page has a small inline script for toggling sections (lines 303–322) that does not reference financial fields or DOM replacements for them.

### F.2 — Public JS / build assets

- No search was run inside `public/` or compiled assets. Grep in `resources` for `.js`/`.ts`/`.vue` for the above names returned **no matches** in JS/TS/Vue files.

**Fact:** No evidence was found that JS on the project **show** page manipulates the General Info financial **display** values or replaces DOM nodes that show Overall Project Budget, Amount Forwarded, Local Contribution, Amount Requested, or Opening Balance. Edit-form scripts operate on form inputs, not on the read-only table in Show/general_info.

---

## SECTION G — Root Cause Assessment

### G.1 — Verified facts

1. **Controller:** `ProjectController@show` sets `$data['resolvedFundFields'] = $resolver->resolve($project)` and returns `view('projects.Oldprojects.show', $data)`. So the view is given a variable `$resolvedFundFields` (camelCase).
2. **Resolver:** For "Individual - Initial - Educational support", the resolver uses `DirectMappedIndividualBudgetStrategy` and returns an array with keys `overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_requested`, `amount_sanctioned`, `opening_balance`. Logs confirm correct computation.
3. **Show view:** Uses `@include('projects.partials.Show.general_info')` with no second argument; the partial therefore relies on the same scope as the show view.
4. **Partial:** `projects/partials/Show/general_info.blade.php` uses only `$resolvedFundFields` for the six financial fields and, in an @php block, does `$resolvedFundFields = $resolvedFundFields ?? [];` before rendering them.
5. **No legacy vars in this partial:** The six financial values in this partial are not bound to `$project` or `$budget`.
6. **No JS on show:** No script on the show page was found that modifies the General Info financial display or replaces those DOM elements.

### G.2 — Where binding can break

- For the UI to show 0.00 for all six fields, the partial must be reading from an array that either has no keys or has zero values. The template uses `$resolvedFundFields['key'] ?? 0`, so:
  - If the array is `[]` (empty), every key is missing and the result is 0.
  - The only place in this path that can set `$resolvedFundFields` to an empty array is the line `$resolvedFundFields = $resolvedFundFields ?? [];` when the variable is **undefined or null** at the time the partial runs.
- So the only identified mechanism in the frontend that can produce “all zeros” is: **when `projects.partials.Show.general_info` is rendered, `$resolvedFundFields` is not defined or is null in the scope of that partial**, so the @php default turns it into `[]`, and every financial output becomes 0.00.

### G.3 — Why it might be undefined or null for IIES only

- The controller does not branch on project type when setting `$data['resolvedFundFields']`; it is set once after the type-specific switch. So in the controller, IIES is treated the same as other types for this variable.
- If the problem appears only for IIES, possible (unverified) explanations include:
  - A view/layout caching or compilation issue where the scope for the included partial differs (e.g. by route or project type).
  - A different code path or view variant that renders the show page for IIES and does not pass `resolvedFundFields` (no such path was found in this audit).
  - A typo or case mismatch in the view data key (e.g. `resolvedFundFields` vs `resolved_fund_fields`) only used in some flows; the controller uses `resolvedFundFields` consistently in the examined code.

### G.4 — Summary

- **Rendering:** The only General Info partial used on show is `projects.partials.Show.general_info`; it binds all six financial fields to `$resolvedFundFields` only.
- **Single point of failure in the partial:** The line `$resolvedFundFields = $resolvedFundFields ?? [];` in that partial will turn a missing or null `$resolvedFundFields` into `[]`, causing every financial field to display as 0.00.
- **Scope:** The partial does not receive `resolvedFundFields` via an explicit @include argument; it depends on it being present in the parent view scope. If it is not present when the include runs, the default to `[]` explains the observed behaviour.

**Conclusion (factual):** The frontend behaviour (0.00 for all six financial fields) is consistent with `$resolvedFundFields` being undefined or null when `projects.partials.Show.general_info` is executed, so that the default `$resolvedFundFields = $resolvedFundFields ?? []` is applied and all keys are missing. No other frontend mechanism was found that resets or overwrites the variable or that uses legacy project/budget fields for this section. Resolver and controller logic were not modified and were not re-audited; this document restricts to the frontend layer.
