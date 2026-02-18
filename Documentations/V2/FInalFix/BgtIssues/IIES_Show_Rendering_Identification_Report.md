# Identify IIES-Specific Show Rendering

**Objective:** Determine whether IIES projects use a separate General Info blade or conditional rendering logic for the route `/executor/projects/{project_id}` (e.g. IIES-0039).

---

## Section A — Actual show view used

**Single view for all project types (including IIES):**

- **Returned view:** `projects.Oldprojects.show`
- **Controller:** `ProjectController@show` → `return view('projects.Oldprojects.show', $data);` (line 1045)

**Conditional logic in the controller:**

- There is **no** IIES-specific view name. The same view `projects.Oldprojects.show` is always returned.
- The controller uses a **switch on `$project->project_type`** only to **fill `$data`** (e.g. for IIES: `IIESPersonalInfo`, `IIESFamilyWorkingMembers`, `IIESExpenses`, etc.). It does **not** change the view path.
- `$data['resolvedFundFields']` is set for all types via `$resolver->resolve($project)` before the return.

**Conclusion:** IIES and all other project types use the same show view: **`resources/views/projects/Oldprojects/show.blade.php`**.

---

## Section B — Whether IIES has a custom General Info blade

**No.** IIES does **not** use a dedicated General Info blade.

- The General Info section is included **once**, **unconditionally**, in `show.blade.php` (lines 118–126):
  - `@include('projects.partials.Show.general_info')`
- There is **no** `@if ($project->project_type === 'Individual - Initial - Educational support')` around that include.
- There are **no** views under `resources/views/iies/` or `resources/views/projects/iies/` for the show page; IIES-specific partials live under `projects/partials/Show/IIES/` and are used for **other** sections (personal_info, family_working_members, estimated_expenses, etc.), **not** for General Info.

**Conclusion:** General Info for IIES is rendered by the **same** blade as for every other type: **`resources/views/projects/partials/Show/general_info.blade.php`**. No IIES-specific General Info blade exists.

---

## Section C — Which blade renders the budget table

**Two different “budget” areas:**

1. **General Info budget table (Overall Budget, Amount Forwarded, Local Contribution, Amount Requested, Amount Sanctioned, Opening Balance)**  
   - Rendered by: **`resources/views/projects/partials/Show/general_info.blade.php`**  
   - This is the table inside the “General Information” card; it is shown for **all** project types, including IIES.

2. **“Budget Overview” section (phase/budget lines, validation, etc.)**  
   - Rendered by: **`resources/views/projects/partials/Show/budget.blade.php`**  
   - Included in show only when project type is **not** in the individual list (see show.blade.php lines 244–250):
     - `@if (!in_array($project->project_type, ['Individual - Ongoing Educational support', 'Individual - Livelihood Application', 'Individual - Access to Health', 'Individual - Initial - Educational support']))`
     - So **Show.budget is not included for IIES**; the Budget Overview card does not appear on the IIES show page.

**Conclusion:** For IIES, the only “budget table” on the show page is the one in **General Info**, and it is rendered by **Show/general_info.blade.php**. The **Show/budget** partial is not used for IIES.

---

## Section D — Whether that blade uses $project or $resolvedFundFields

**Blade:** `resources/views/projects/partials/Show/general_info.blade.php`

**Source of financial values:**

- All financial fields in the General Info table come from **`$resolvedFundFields`** (exposed in the blade as `$rf = $resolvedFundFields ?? []`):
  - `overall_project_budget` → `$rf['overall_project_budget']`
  - `amount_forwarded` → `$rf['amount_forwarded']`
  - `local_contribution` → `$rf['local_contribution']`
  - `amount_requested` → `$rf['amount_requested']`
  - `amount_sanctioned` → `$rf['amount_sanctioned']` (displayed only when `$project->isApproved()`)
  - `opening_balance` → `$rf['opening_balance']`
- Non-financial fields (e.g. Project ID, Title, Society Name) still use **`$project`** where appropriate.
- The blade does **not** use raw `$project->overall_project_budget`, `$project->amount_forwarded`, etc., for the budget table.

**Conclusion:** The General Info blade that renders the budget table for IIES (and all types) uses **`$resolvedFundFields`** for all financial values, not raw `$project` DB fields.

---

## Summary

| Question | Answer |
|----------|--------|
| Which view is used for show? | `projects.Oldprojects.show` (same for IIES and all types). |
| IIES-specific General Info blade? | No; same `Show.general_info` for all. |
| Which blade has the General Info budget table? | `projects/partials/Show/general_info.blade.php`. |
| General Info budget table: $project or $resolvedFundFields? | **$resolvedFundFields** for all financial fields. |
