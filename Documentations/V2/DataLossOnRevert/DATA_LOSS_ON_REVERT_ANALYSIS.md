# Data Loss When Provincial Reverts a Project – Analysis

## Summary

**The provincial revert action does not delete any project data.** Data loss is observed *after* revert because the **first project update** (edit + save) by the executor uses a **“delete-all-then-recreate-from-request”** pattern across type-specific sections. If the update request has empty or missing data for a section, that section’s rows are deleted and none are recreated, so data appears to vanish. This often happens right after revert because the executor opens the reverted project and saves, so the loss is associated with “revert” even though the trigger is the **update** request.

---

## 1. What Happens on Provincial Revert

### 1.1 Flow

- **Route:** `POST /projects/{project_id}/revert-to-executor`  
  Named route: `projects.revertToExecutor`
- **Controller:** `App\Http\Controllers\ProvincialController::revertToExecutor`
- **Service:** `App\Services\ProjectStatusService::revertByProvincial()`

### 1.2 Revert Logic (No Deletes)

In `ProjectStatusService::revertByProvincial()`:

1. Authority is checked (only provincial/general).
2. Current status is validated (e.g. `submitted_to_provincial`, `forwarded_to_coordinator`, `reverted_by_coordinator`).
3. New status is set (e.g. `reverted_by_provincial`, `reverted_to_executor`, etc.).
4. `$project->save()` is called (only the `projects` row is updated).
5. `logStatusChange()` writes to `activity_histories` and `project_status_histories`. It does **not** delete any project or related data.

**Conclusion:** Revert only changes `project.status` and writes audit records. **No related tables are touched on revert.**

---

## 2. Where Data Is Actually Lost: Project Update

Data is lost during the **project update** flow used when the executor (or any user) edits and saves the project.

### 2.1 Update Entry Point

- **Route:** `PUT /projects/{project_id}`  
  Named route: `projects.update`
- **Controller:** `App\Http\Controllers\Projects\ProjectController::update()`
- **Request:** `UpdateProjectRequest`

`ProjectController::update()`:

1. Updates general info via `GeneralInfoController::update()` (only updates the `projects` row).
2. For institutional types: updates logical framework, sustainability, budget, attachments.
3. Dispatches to **all** type-specific controllers for the project type (e.g. RST, CCI, IGE, IES, ILP, IAH, IIES, etc.).

Each type-specific controller is called with the **same** request. Many of them use the pattern below.

### 2.2 “Delete-All-Then-Recreate” Pattern

Many section controllers do the following:

1. **Delete** all existing rows for that project (and optionally phase/section).
2. **Recreate** rows only from the current request (e.g. from `$request->input('...')` or validated data).

If the request does **not** contain that section’s data (missing keys, or empty arrays), step 1 still runs and step 2 creates no rows → **all data for that section is lost**.

Examples:

| Controller | Table(s) affected | Pattern |
|------------|--------------------|--------|
| `BudgetController` | `project_budgets` | `ProjectBudget::where('project_id', ...)->where('phase', ...)->delete()` then create from `$validated['phases'][0]['budget']`. If `phases` or `budget` is empty, all budget rows for that phase are removed. |
| `LogicalFrameworkController` | `project_objectives`, results, risks, activities, timeframes | `ProjectObjective::where('project_id', $project_id)->delete()` then recreate from `$request->input('objectives', [])`. Empty `objectives` → logical framework wiped. |
| `RST\BeneficiariesAreaController` | `project_dp_rst_beneficiaries_areas` | `ProjectDPRSTBeneficiariesArea::where('project_id', $projectId)->delete()` then create from request arrays. Empty arrays → beneficiaries area wiped. |
| `RST\GeographicalAreaController` | `project_rst_geographical_areas` | Same pattern. |
| `RST\TargetGroupController` | `project_rst_target_groups` | Same pattern. |
| `RST\TargetGroupAnnexureController` | `project_rst_target_group_annexure` | Same pattern. |
| `RST\InstitutionInfoController` | `project_rst_institution_info` | Same pattern. |
| `IGE\IGEBudgetController` | `project_ige_budgets` | Same pattern. |
| `IGE\IGEBeneficiariesSupportedController` | `project_ige_beneficiaries_supported` | Same pattern. |
| `IGE\IGENewBeneficiariesController` | `project_ige_new_beneficiaries` | Same pattern. |
| `IGE\IGEOngoingBeneficiariesController` | `project_ige_ongoing_beneficiaries` | Same pattern. |
| `ILP\BudgetController` | `project_ilp_budgets` | Same pattern. |
| `ILP\PersonalInfoController` | `project_ilp_personal_info` | Same pattern. |
| `IAH\IAHBudgetDetailsController` | `project_iah_budget_details` | Same pattern. |
| `IAH\IAHEarningMembersController` | `project_iah_earning_members` | Same pattern. |
| `IES\IESFamilyWorkingMembersController` | `project_ies_family_working_members` | Same pattern. |
| `IIES\IIESFamilyWorkingMembersController` | `project_iies_family_working_members` | Same pattern. |
| … and others | (see grep list below) | Same pattern. |

So data loss is not caused by revert; it is caused by **update** when the payload for a section is missing or empty.

### 2.3 Why It Looks Like “Data Lost on Revert”

- Provincial reverts → status becomes e.g. `reverted_by_provincial`.
- Executor opens the reverted project (edit page) and clicks Save (or “Save as draft”).
- That triggers `ProjectController::update()`. If the form does not send full data for every section (e.g. only one tab visible, or field names not matching, or JS not including all rows), some controllers receive empty/missing data and delete existing rows without recreating them.
- User sees “data was there before revert, now it’s gone” and associates the loss with **revert**, even though the actual trigger was the **first update after revert**.

---

## 3. Affected Code Locations (Delete-Then-Recreate)

Controllers that perform a bulk delete for the project (or project + phase) and then recreate from request:

- `app/Http/Controllers/Projects/BudgetController.php` – `ProjectBudget::where(...)->delete()`
- `app/Http/Controllers/Projects/LogicalFrameworkController.php` – `ProjectObjective::where('project_id', $project_id)->delete()`
- `app/Http/Controllers/Projects/SustainabilityController.php` – sustainability delete/recreate
- `app/Http/Controllers/Projects/RST/BeneficiariesAreaController.php`
- `app/Http/Controllers/Projects/RST/GeographicalAreaController.php`
- `app/Http/Controllers/Projects/RST/TargetGroupController.php`
- `app/Http/Controllers/Projects/RST/TargetGroupAnnexureController.php`
- `app/Http/Controllers/Projects/RST/InstitutionInfoController.php`
- `app/Http/Controllers/Projects/IGE/IGEBudgetController.php`
- `app/Http/Controllers/Projects/IGE/IGEBeneficiariesSupportedController.php`
- `app/Http/Controllers/Projects/IGE/IGENewBeneficiariesController.php`
- `app/Http/Controllers/Projects/IGE/IGEOngoingBeneficiariesController.php`
- `app/Http/Controllers/Projects/IGE/IGEInstitutionInfoController.php`
- `app/Http/Controllers/Projects/IGE/IGEDevelopmentMonitoringController.php`
- `app/Http/Controllers/Projects/ILP/BudgetController.php`
- `app/Http/Controllers/Projects/ILP/PersonalInfoController.php`
- `app/Http/Controllers/Projects/ILP/RevenueGoalsController.php`
- `app/Http/Controllers/Projects/ILP/RiskAnalysisController.php`
- `app/Http/Controllers/Projects/ILP/StrengthWeaknessController.php`
- `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php`
- `app/Http/Controllers/Projects/IAH/IAHEarningMembersController.php`
- `app/Http/Controllers/Projects/IAH/IAHHealthConditionController.php`
- `app/Http/Controllers/Projects/IAH/IAHPersonalInfoController.php`
- `app/Http/Controllers/Projects/IAH/IAHSupportDetailsController.php`
- `app/Http/Controllers/Projects/IES/IESFamilyWorkingMembersController.php`
- `app/Http/Controllers/Projects/IES/IESPersonalInfoController.php`
- `app/Http/Controllers/Projects/IES/IESExpensesController.php`
- `app/Http/Controllers/Projects/IIES/IIESFamilyWorkingMembersController.php`
- `app/Http/Controllers/Projects/IIES/IIESExpensesController.php`
- `app/Http/Controllers/Projects/IIES/IIESPersonalInfoController.php`
- `app/Http/Controllers/Projects/IIES/EducationBackgroundController.php`
- `app/Http/Controllers/Projects/IIES/FinancialSupportController.php`
- Plus CCI, Edu-RUT, LDP controllers with the same pattern (see repo grep for `->delete()` in `app/Http/Controllers/Projects`).

---

## 4. Root Cause Summary

| Aspect | Detail |
|--------|--------|
| **When** | On **project update** (edit form submit), not on revert. |
| **Why** | Section controllers **delete all** existing rows for that section, then **create only from request**. |
| **Trigger** | Request has **missing or empty** data for that section (e.g. form structure, tab-based UI, or field names not sent). |
| **Perception** | Loss is noticed after revert because the next action is often “open reverted project and save.” |

---

## 5. Recommendations

1. **Short term – avoid accidental wipe on update**
   - In each section controller, **do not delete** existing rows when the incoming request has no/empty data for that section. Either skip the section update when data is absent, or treat “missing” as “leave existing data unchanged.”
   - Option: only run delete+recreate when the request **explicitly** includes that section (e.g. a flag or a non-empty structure). Otherwise leave the section untouched.

2. **Medium term – safer update strategy**
   - Prefer **diff-based updates** (update existing rows by id, create new rows, optionally delete only rows that were explicitly removed in the request) instead of “delete all then recreate” for sections with multiple rows.
   - Ensure the edit form always sends **full section payloads** (all sections for the project type) when submitting the main project update, or move to section-specific endpoints that receive and validate only that section.

3. **Form / front-end**
   - Ensure the edit view includes all section fields in the DOM (or in a single payload) when submitting to `projects.update`, so no section is sent as missing or empty by mistake.
   - Verify field names and structure (e.g. `phases[0][budget][*]`, `objectives`, etc.) match what the backend expects.

4. **Clarify to users**
   - Revert only changes status; it does not delete data. Data loss occurs on the **next save** of the project; improving update behaviour and form payload will prevent that.

---

## 6. References

- Revert: `app/Http/Controllers/ProvincialController.php` (`revertToExecutor`), `app/Services/ProjectStatusService.php` (`revertByProvincial`, `logStatusChange`).
- Update: `app/Http/Controllers/Projects/ProjectController.php` (`update`), and type-specific controllers under `app/Http/Controllers/Projects/`.
- Edit form: `resources/views/projects/Oldprojects/edit.blade.php` (single form to `projects.update`).
- Status constants: `App\Constants\ProjectStatus` (e.g. `REVERTED_BY_PROVINCIAL`).
