# M2.4 Step 2 — LogicalFramework Row Integrity

**Milestone:** M2 — Validation & Schema Alignment  
**Step:** M2.4 Step 2 (LogicalFramework Row Integrity ONLY)  
**Strategy Level:** B (Defensive Architecture)

---

## 1) What Checks Were Added

### In `update()` (and aligned in `store()`)

- **Objective:** Before creating a `ProjectObjective`, the code now checks that `objectiveData['objective']` exists, is not null, and has non-empty content after `trim()`. If any of these fail, that objective entry is skipped entirely (no row created).
- **Result:** Before creating a `ProjectResult`, the code checks that `resultData['result']` exists, is not null, and `trim(...) !== ''`. Otherwise the result entry is skipped.
- **Risk:** Before creating a `ProjectRisk`, the code checks that `riskData['risk']` exists, is not null, and `trim(...) !== ''`. Otherwise the risk entry is skipped.
- **Activity:** Before creating a `ProjectActivity`, the code checks that `activityData['activity']` exists, is not null, and `trim(...) !== ''`. For `verification`: if it exists and is a string, the trimmed value is used; otherwise the value passed to the model is empty string `''`. Null is never passed for `verification`. If the activity text check fails, that activity entry is skipped.
- **Timeframe:** Before creating a `ProjectTimeframe`, the code ensures the `month` key is used and `trim((string) $month) !== ''`; if `month` is empty after trim, that timeframe entry is skipped. The `is_active` value is used as provided, or defaulted to `false` when missing/null. Null is never passed for `month` or `is_active`.

All created rows use trimmed strings for text columns; verification and is_active are never null when passed to the model.

---

## 2) Which NOT NULL Columns Are Now Protected

| Table | Column | Protection |
|-------|--------|------------|
| project_objectives | objective | Row created only when text exists, not null, and non-empty after trim. |
| project_results | result | Row created only when text exists, not null, and non-empty after trim. |
| project_risks | risk (description) | Row created only when text exists, not null, and non-empty after trim. |
| project_activities | activity | Row created only when text exists, not null, and non-empty after trim. |
| project_activities | verification | Never null: trimmed string or `''`. |
| project_timeframes | month | Never null: trimmed string; row skipped if month empty after trim. |
| project_timeframes | is_active | Never null: value or `false`. |

No NOT NULL column in these tables can receive null from the LogicalFrameworkController create path.

---

## 3) Why Draft Is Unaffected

- The M1 guard (`isLogicalFrameworkMeaningfullyFilled`) is unchanged. It still decides whether to run the section at all (skip when section is absent or empty). When it allows the section to run, the new checks only filter which **rows** are created; they do not require “full” data or add new required fields.
- Draft can still send partial objectives (e.g. one objective with text, others empty). Empty or null objective/result/risk/activity entries are simply skipped; valid entries are still created. No new validation rules were added; the controller does not reject the request.
- Behaviour remains “create only rows that have minimum viable content”; draft continues to work with partial or mixed content.

---

## 4) Why M1 Is Unaffected

- The M1 “skip-empty” guard is `isLogicalFrameworkMeaningfullyFilled($objectives)`. It was not modified. It still returns true when there is at least one objective with meaningful data (or meaningful result/risk/activity). The early return when the section is empty/absent is unchanged.
- M2.4 only adds **per-row** guards inside the loops that run **after** M1 has already allowed the mutation. So M1 still controls “do we run this section at all?”; M2.4 controls “for each entry, do we create a row or skip?”. No overlap.

---

## 5) Risk Assessment

- **Level: LOW–MEDIUM.**
  - **LOW:** Changes are additive (skip bad rows, default verification/is_active). Delete-then-recreate, transaction, and ID generation are unchanged. No validation or route changes. Existing valid payloads behave the same; only null/empty entries are skipped or defaulted.
  - **MEDIUM:** Submitters who previously sent empty strings or null for objective/result/risk/activity may see fewer rows created (those rows are now skipped). That is intended and prevents NOT NULL violations; it could be a minor behaviour change if any client relied on “create row with null/empty”. Verification and is_active defaults are conservative ('' and false).

---

## 6) Exact File and Method Modified

| File | Method | Change |
|------|--------|--------|
| `app/Http/Controllers/Projects/LogicalFrameworkController.php` | `update()` | In the loop over `$objectives`: added guard to skip objective when `objective` is missing/null/empty after trim; same for result, risk, activity (with verification default `''`), and timeframe (month non-empty, is_active default `false`). All created rows use trimmed text and non-null verification/month/is_active. |
| `app/Http/Controllers/Projects/LogicalFrameworkController.php` | `store()` | Applied the same row-level guards for results, risks, activities (with verification default `''`), and timeframes (month non-empty, is_active default `false`) so store and update share the same integrity rules. Objective was already guarded. |

No other files or methods were modified. Delete logic, transaction wrapping, M1 guard, and request validation were not changed.

---

**End of M2.4 Step 2 documentation.**
