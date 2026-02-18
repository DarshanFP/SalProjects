# Inspect Canonical Separation Logic — IIES

**Mode:** Read-only investigation  
**Objective:** Confirm what values are returned from `DirectMappedIndividualBudgetStrategy::resolveIIES()` and how `ProjectFinancialResolver::applyCanonicalSeparation()` modifies them for non-approved projects.

---

## Section A — Raw resolveIIES return

**File:** `app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php`  
**Method:** `resolveIIES(Project $project)`

**Exact return array structure (when `$expenses` is present):**

```php
return [
    'overall_project_budget' => $overall,   // (float) $expenses->iies_total_expenses ?? 0
    'amount_forwarded'       => 0,
    'local_contribution'     => $local,     // sum of iies_expected_scholarship_govt + iies_support_other_sources + iies_beneficiary_contribution
    'amount_sanctioned'      => $sanctioned, // (float) $expenses->iies_balance_requested ?? 0
    'opening_balance'       => $opening,   // same as $overall (iies_total_expenses)
];
```

**Keys and values used:**

| Key | Source |
|-----|--------|
| `overall_project_budget` | `$expenses->iies_total_expenses` |
| `amount_forwarded` | Literal `0` |
| `local_contribution` | `iies_expected_scholarship_govt + iies_support_other_sources + iies_beneficiary_contribution` |
| `amount_sanctioned` | `$expenses->iies_balance_requested` (semantically “amount requested” in IIES) |
| `opening_balance` | Same as `overall_project_budget` |

**Note:** `resolveIIES()` does **not** set `amount_requested`. That key is added later in the strategy’s `resolve()` when the project is **not** approved.

**When `$expenses` is null:** `resolveIIES()` returns `fallbackFromProject($project)` (values from `projects` table; often zeros for IIES).

---

## Section B — After canonical separation

Two layers apply:

### B.1 Inside DirectMappedIndividualBudgetStrategy::resolve() (lines 49–57)

After `resolveIIES()` (or fallback), for **non-approved** projects:

- `amount_requested` = `(float) ($resolved['amount_sanctioned'] ?? 0)` — i.e. the value that came from `iies_balance_requested`.
- `amount_sanctioned` = `0.0`.
- `opening_balance` = `(amount_forwarded ?? 0) + (local_contribution ?? 0)`.

So the array passed **into** `ProjectFinancialResolver` for non-approved IIES already has:

- `amount_sanctioned` = 0  
- `amount_requested` = former “sanctioned” (balance requested)  
- `opening_balance` = forwarded + local  

`overall_project_budget`, `amount_forwarded`, `local_contribution` are **not** changed here.

### B.2 ProjectFinancialResolver::applyCanonicalSeparation() (lines 86–107)

**File:** `app/Domain/Budget/ProjectFinancialResolver.php`

**When project is NOT approved:**

- Reads: `overall`, `forwarded`, `local` from `$result`; `combined = forwarded + local`.
- `$requested` = `(float) ($result['amount_requested'] ?? max(0, $overall - $combined))`.
- **Overwrites** (via `array_merge($result, [...])`):
  - `amount_sanctioned` => `0.0`
  - `amount_requested` => `round(max(0, $requested), 2)`
  - `opening_balance` => `$combined` (forwarded + local)

**Keys overwritten for non-approved:** `amount_sanctioned`, `amount_requested`, `opening_balance`.  
**Not overwritten:** `overall_project_budget`, `amount_forwarded`, `local_contribution`.

**When project is approved:** Overwrites `amount_sanctioned`, `amount_requested`, `opening_balance` from `$project` (DB).

---

## Section C — Final $resolvedFundFields passed to view

**Temporary logging added:** In `ProjectController@show`, immediately after:

```php
$data['resolvedFundFields'] = $resolver->resolve($project);
```

added:

```php
\Log::info('Resolved Fund Fields Output', $data['resolvedFundFields']);
```

**How to get the actual array:** Reload the project show page (e.g. `/executor/projects/IIES-0039`), then open `storage/logs/laravel.log` and search for `Resolved Fund Fields Output`. The next line(s) will show the logged array (JSON or key-value list).

**Expected keys in final output:**  
`overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned`, `amount_requested`, `opening_balance` (all after `normalize()` in the resolver).

**Fill after reload:**

| Key | Value (from log) |
|-----|------------------|
| overall_project_budget | |
| amount_forwarded | |
| local_contribution | |
| amount_sanctioned | |
| amount_requested | |
| opening_balance | |

---

## Section D — Where values become zero

| Value | Set in resolveIIES | Strategy (non-approved) | applyCanonicalSeparation (non-approved) |
|-------|--------------------|-------------------------|----------------------------------------|
| **overall_project_budget** | From `iies_total_expenses` | Unchanged | Unchanged |
| **amount_forwarded** | `0` | Unchanged | Unchanged |
| **local_contribution** | From IIES local fields | Unchanged | Unchanged |
| **amount_sanctioned** | From `iies_balance_requested` | **Overwritten to 0** | Reinforced to 0 |
| **amount_requested** | Not set | Set from former `amount_sanctioned` | Rounded, can use `overall - combined` if missing |
| **opening_balance** | From `iies_total_expenses` | **Overwritten to forwarded + local** | Reinforced to `combined` |

So for **non-approved** IIES:

- **amount_sanctioned** becomes 0 in the **strategy** (line 55), then again in `applyCanonicalSeparation`.
- **opening_balance** becomes `forwarded + local` in the **strategy** (line 56), then again in `applyCanonicalSeparation`.
- **amount_requested** is set in the **strategy** (line 54) from the former `amount_sanctioned` (i.e. `iies_balance_requested`); the resolver only rounds or falls back to `max(0, overall - combined)` if missing.

If the **raw** IIES data from `resolveIIES()` is all zeros (e.g. because `$expenses` was null and fallback was used), then after separation and normalize the final `amount_requested` and other fields will also be zero.

---

## Section E — Root cause classification

| Scenario | Conclusion |
|----------|------------|
| resolveIIES returns fallback (no IIES row) | `overall_project_budget`, `local_contribution`, `amount_sanctioned` (pre-separation) all from `projects` table → often 0 for IIES. After separation, `amount_sanctioned` = 0, `amount_requested` = 0, `opening_balance` = 0 + 0 = 0. **Root cause: no row in project_IIES_expenses or relation null.** |
| resolveIIES returns real IIES data | `overall_project_budget` and `local_contribution` non-zero; strategy moves “balance requested” into `amount_requested` and zeros `amount_sanctioned`. Final view should show non-zero overall, local, and amount_requested. If General Info still shows zeros, check that the view uses `resolvedFundFields` and that the correct strategy ran (project_type matches IIES). |
| Log shows all zeros | Either fallback was used (relation null) or IIES row exists but all numeric columns are 0. Check DB row and “IIES Resolver Debug” log (relation_value_is_null, raw_relation). |
| Log shows non-zero overall/requested but UI shows 0 | View or section not using `$resolvedFundFields`; or a different project/request is being logged. |

**Summary:** Canonical separation for non-approved projects intentionally sets `amount_sanctioned` to 0 and puts the requested amount in `amount_requested`. Zeros in the final output for IIES are either because (1) `resolveIIES()` fell back (no `iiesExpenses`), or (2) the IIES row has zero in all numeric columns. The temporary log of `Resolved Fund Fields Output` after reload confirms the actual array passed to the view.

---

*Remove the temporary `\Log::info('Resolved Fund Fields Output', ...)` when done.*
