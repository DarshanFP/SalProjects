# Targeted Debug Instrumentation — IIES Resolver

**Objective:** Confirm whether `$project->iiesExpenses` is null inside `DirectMappedIndividualBudgetStrategy::resolveIIES()` for project_id = IIES-0039.

**Instrumentation added (temporary):**

1. **DirectMappedIndividualBudgetStrategy::resolveIIES()** — at top of method:  
   `\Log::info('IIES Resolver Debug', [...])`
2. **ProjectController@show** — just before `$resolver->resolve($project)`:  
   `\Log::info('Controller Debug Before Resolve', [...])`

---

## How to capture log data

1. Open in browser (while logged in as executor):  
   **/executor/projects/IIES-0039**
2. Open: **storage/logs/laravel.log**
3. Search for: `Controller Debug Before Resolve` and `IIES Resolver Debug`  
   (or run: `grep -A 12 "Controller Debug Before Resolve\|IIES Resolver Debug" storage/logs/laravel.log`)

---

## Report (fill after inspecting log)

### Section A — Controller relation state

**Log key:** `Controller Debug Before Resolve`

| Field | Value | Interpretation |
|-------|--------|----------------|
| project_id | | Should be `IIES-0039` |
| relation_loaded | | `true` = iiesExpenses was eager-loaded in show(); `false` = not loaded |
| relation_value_is_null | | `true` = no row in project_IIES_expenses for this project_id; `false` = relation has model |

**Summary:**  
(If relation_loaded is true and relation_value_is_null is false → controller has the relation and data before resolve. If relation_value_is_null is true → no child row or relation not loaded.)

---

### Section B — Resolver relation state

**Log key:** `IIES Resolver Debug`

| Field | Value | Interpretation |
|-------|--------|----------------|
| project_id | | Should be `IIES-0039` |
| project_type | | Must be exactly `Individual - Initial - Educational support` for IIES strategy |
| relation_loaded | | Same project instance as controller; should match Section A |
| relation_value_is_null | | **Key:** if true here, resolver will hit fallback and return 0 |
| raw_relation | | Object dump if present, or null |

**Summary:**  
(If relation_value_is_null is true → resolveIIES returns fallbackFromProject() → zeros. If false → resolver uses iiesExpenses and should return non-zero if DB row has values.)

---

### Section C — If relation becomes null inside resolver

- Controller log runs **before** `$resolver->resolve($project)`.
- Resolver log runs **inside** `resolveIIES()` (same `$project` instance).
- So `relation_loaded` and `relation_value_is_null` should be **identical** in both logs (same request, same object).
- If controller shows `relation_value_is_null: false` and resolver shows `relation_value_is_null: true`, that would indicate the relation was cleared between the two (unexpected; would suggest refactor or middleware).
- **Typical outcome:** both logs show the same state. If both show `relation_value_is_null: true` → no row in `project_IIES_expenses` or relation not loaded. If both show `false` → relation present; if General Info still 0, check project_type or column values.

---

### Section D — Root cause classification

| Scenario | Conclusion |
|----------|------------|
| Controller: relation_value_is_null **true** | No row for IIES-0039 in project_IIES_expenses, or iiesExpenses not in with(). Check DB and show() with() list. |
| Controller: relation_value_is_null **false**; Resolver: **true** | Relation lost between controller and resolver (unexpected; investigate code path). |
| Both **false** but General Info still 0 | Relation present; either project_type ≠ IIES (wrong strategy) or strategy used but column values are 0. Check project_type in resolver log and DB row. |
| Resolver log **absent** | resolveIIES() was not called → project_type did not match IIES → PhaseBasedBudgetStrategy used. Fix project_type in DB or constant. |

---

*Remove the two `\Log::info(...)` calls when debugging is complete.*
