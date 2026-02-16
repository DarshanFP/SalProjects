# Create Validation Alignment Report

**Date:** 2026-02-10  
**Goal:** Eliminate duplicate validation in GeneralInfoController::store() and rely entirely on StoreProjectRequest.

---

## 1. File Modified

- `app/Http/Controllers/Projects/GeneralInfoController.php`

---

## 2. Validation Block Removed (Snippet)

The following inline validation block was **removed** from `store()`:

```php
$validated = $request->validate([
    'project_type' => 'required|string|max:255',
    'project_title' => 'nullable|string|max:255',
    'society_name' => 'nullable|string|max:255',
    'president_name' => 'nullable|string|max:255',
    'in_charge' => 'nullable|integer|exists:users,id',
    'in_charge_name' => 'nullable|string|max:255',
    'in_charge_mobile' => 'nullable|string|max:255',
    'in_charge_email' => 'nullable|string|max:255',
    'executor_name' => 'nullable|string|max:255',
    'executor_mobile' => 'nullable|string|max:255',
    'executor_email' => 'nullable|string|max:255',
    'gi_full_address' => 'nullable|string|max:255',
    'overall_project_period' => 'nullable|integer',
    'current_phase' => 'nullable|integer',
    'commencement_month' => 'nullable|integer',
    'commencement_year' => 'nullable|integer',
    'overall_project_budget' => 'nullable|numeric',
    'coordinator_india' => 'nullable|integer|exists:users,id',
    'coordinator_india_name' => 'nullable|string|max:255',
    'coordinator_india_phone' => 'nullable|string|max:255',
    'coordinator_india_email' => 'nullable|string|max:255',
    'coordinator_luzern' => 'nullable|integer|exists:users,id',
    'coordinator_luzern_name' => 'nullable|string|max:255',
    'coordinator_luzern_phone' => 'nullable|string|max:255',
    'coordinator_luzern_email' => 'nullable|string|max:255',
    'goal' => 'nullable|string',
    'total_amount_sanctioned' => 'nullable|numeric',
    'amount_forwarded' => 'nullable|numeric',
    'local_contribution' => 'nullable|numeric',
    'predecessor_project' => 'nullable|string|exists:projects,project_id',
]);
```

That second validation enforced `project_type` as required and overrode StoreProjectRequest’s draft-aware rules.

---

## 3. Replacement Code Snippet

Replacement in `store()`:

```php
$validated = $request->validated();
```

No other lines were added or removed in the validation step. All logic below (commencement_date, user_id, status, defaults for in_charge and overall_project_budget, mapping, Log, Project::create, status log, return) is unchanged.

---

## 4. Confirmation Only store() Was Modified

- **store():** Inline `$request->validate([...])` was removed and replaced with `$request->validated();`. Method signature `public function store(Request $request)` unchanged. No other logic in `store()` was changed.
- **update():** Not modified. It continues to use `$validated = $request->validated();` and its existing validation/flow.
- **show():** Not modified.
- **destroy():** Not modified.

---

## 5. Confirmation No Update Logic Changed

- The **update()** method was not edited. It still uses `$request->validated()` and the same BudgetSyncGuard, mapping, and `$project->update($validated)` logic as before.
- No transaction boundaries, status handling, service calls, or redirect logic were changed anywhere in the controller.

---

## 6. Risk Notes

- **Caller contract:** `store()` is only invoked from `ProjectController::storeGeneralInfoAndMergeProjectId(StoreProjectRequest $request)`, so the request is always a FormRequest (StoreProjectRequest). `$request->validated()` is therefore available; the method signature remains `Request $request` as before.
- **Minimal draft (no project_type):** StoreProjectRequest allows nullable `project_type` when `save_as_draft` is true, so `validated()` can contain `project_type` => null. The `projects` table has `project_type` NOT NULL. If no default is applied and `project_type` is null, `Project::create($validated)` can trigger a NOT NULL violation. Existing defaults in `store()` cover `in_charge` and `overall_project_budget` only. If the product requires minimal-draft create with no project type selected, a follow-up (e.g. defaulting `project_type` in `store()` when null, or in StoreProjectRequest) may be needed.
- **Single source of truth:** Validation for create is now entirely defined in StoreProjectRequest (draft vs non-draft rules). GeneralInfoController::store() no longer re-defines or overrides those rules.

---

**Verification:** Grep for `$request->validate(` in GeneralInfoController returns no matches; no duplicate validation remains in `store()`.

**Next step:** Re-run Create Draft Save Test.
