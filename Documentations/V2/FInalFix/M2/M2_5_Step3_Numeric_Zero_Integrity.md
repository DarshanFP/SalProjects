# M2.5 Step 3 — Numeric Zero Integrity

**Milestone:** M2 — Validation & Schema Alignment  
**Step:** M2.5 Step 3 (Numeric empty() Fix ONLY)  
**Strategy Level:** B (Defensive Architecture)

---

## 1) Which Controllers Were Modified

| Controller | File | Method | Change |
|------------|------|--------|--------|
| IESFamilyWorkingMembersController | `app/Http/Controllers/Projects/IES/IESFamilyWorkingMembersController.php` | store() | Row-creation condition: replaced `!empty($monthlyIncome)` with `$monthlyIncome !== null && $monthlyIncome !== ''`. Kept non-empty checks for member_name and work_nature using `trim((string)...) !== ''`. |
| IAHEarningMembersController | `app/Http/Controllers/Projects/IAH/IAHEarningMembersController.php` | store() | Row-creation condition: replaced `!empty($monthlyIncome)` with `$monthlyIncome !== null && $monthlyIncome !== ''`. Left existing checks for memberName and workType unchanged. |
| IESExpensesController | `app/Http/Controllers/Projects/IES/IESExpensesController.php` | store() | Detail-row condition: replaced `!empty($particular) && !empty($amount)` with `trim((string) $particular) !== '' && $amount !== null && $amount !== ''`. |
| IAHBudgetDetailsController | `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php` | store() | Row-creation condition: replaced `!empty($particular) && !empty($amount)` with `trim((string) $particular) !== '' && $amount !== null && $amount !== ''`. |

No other methods or controllers were modified. Update flows that delegate to store() inherit the fix.

---

## 2) What empty() Conditions Were Replaced

| Location | Previous condition | New condition |
|----------|--------------------|----------------|
| IESFamilyWorkingMembersController | `!empty($memberName) && !empty($workNature) && !empty($monthlyIncome)` | `trim((string)$memberName) !== '' && trim((string)$workNature) !== '' && $monthlyIncome !== null && $monthlyIncome !== ''` |
| IAHEarningMembersController | `!empty($memberName) && !empty($workType) && !empty($monthlyIncome)` | `!empty($memberName) && !empty($workType) && $monthlyIncome !== null && $monthlyIncome !== ''` |
| IESExpensesController | `!empty($particular) && !empty($amount)` | `trim((string)$particular) !== '' && $amount !== null && $amount !== ''` |
| IAHBudgetDetailsController | `!empty($particular) && !empty($amount)` | `trim((string)$particular) !== '' && $amount !== null && $amount !== ''` |

Only the **numeric** part of each condition was changed (monthly_income, amount). Non-numeric checks (member name, work nature/type, particular) were either kept as-is or made explicitly “trimmed non-empty string” where the spec requested it.

---

## 3) Why 0 Is Now Preserved

- **empty(0)** and **empty("0")** are true in PHP, so the old conditions skipped rows when the user entered 0 (e.g. “no income” or “Rs. 0”).
- The new logic treats “value present but numeric zero” as valid:
  - **monthly_income:** Row is created when `$monthlyIncome !== null && $monthlyIncome !== ''`. So `0`, `"0"`, and other numeric forms are allowed; only missing or blank string skip the row.
  - **amount:** Same rule: `$amount !== null && $amount !== ''`. So amount 0 or "0" is stored; only null or '' skip the detail row.
- No `empty()` is used on these numeric fields, so 0 and "0" are no longer dropped. Business data for “zero income” or “zero amount” is now stored correctly.

---

## 4) Why null and '' Still Skip Rows

- **null:** Explicit check `$monthlyIncome !== null` / `$amount !== null` ensures null still skips. Null means “no value provided” and should not create a row.
- **'' (empty string):** Explicit check `$monthlyIncome !== ''` / `$amount !== ''` ensures empty string still skips. Empty string also means “no value” and is not stored as 0.
- So “skip when truly absent” is unchanged; only the treatment of 0 and "0" is fixed.

---

## 5) Why This Does Not Overlap With M1, M3, M4

- **M1 (skip-empty guard):** M1 decides whether to run the section at all (e.g. isIESFamilyWorkingMembersMeaningfullyFilled, isIESExpensesMeaningfullyFilled). Those guards were not changed. M2.5 only changes **which rows** are created inside the loop (allow 0; skip only null/''). So M1 still controls “run section or not”; M2.5 controls “include this row or not” for numeric fields.
- **M3 (resolver):** No read-path or resolver code was modified. Only write-path row-creation conditions in the four controllers were changed.
- **M4 (societies):** No societies-related code was touched.

---

## 6) Risk Level

- **LOW–MEDIUM.**
  - **LOW:** Change is minimal and local to one condition per controller. No validation, schema, or delete logic changed. Existing behaviour for “no value” (null/empty string) is preserved; only 0 is newly allowed. Aligns with M2.2 design (explicit null/'' check instead of empty() for numerics).
  - **MEDIUM:** Any client or test that relied on “0 is not stored” will now see 0 stored. That is intended (fix silent corruption); risk is limited to that behavioural correction.

---

## 7) Example Before/After Logic

**Before (example: IES family working members):**

```php
if (!empty($memberName) && !empty($workNature) && !empty($monthlyIncome)) {
    ProjectIESFamilyWorkingMembers::create([...]);
}
```

- Row with `monthly_income = 0` or `"0"`: **skipped** (empty(0) is true) → **data loss**.

**After:**

```php
if (trim((string) $memberName) !== '' && trim((string) $workNature) !== '' && $monthlyIncome !== null && $monthlyIncome !== '') {
    ProjectIESFamilyWorkingMembers::create([...]);
}
```

- Row with `monthly_income = 0` or `"0"`: **created** (0 is not null and not '') → **0 preserved**.
- Row with `monthly_income = null` or `''`: **skipped** (explicit checks) → **unchanged behaviour**.

**Before (example: IES expense detail):**

```php
if (!empty($particular) && !empty($amount)) {
    $projectExpenses->expenseDetails()->create(['particular' => $particular, 'amount' => $amount]);
}
```

- Line with `amount = 0`: **skipped** → **data loss**.

**After:**

```php
if (trim((string) $particular) !== '' && $amount !== null && $amount !== '') {
    $projectExpenses->expenseDetails()->create(['particular' => $particular, 'amount' => $amount]);
}
```

- Line with `amount = 0`: **created** → **0 preserved**.
- Line with `amount = null` or `''`: **skipped** → **unchanged behaviour**.

---

**End of M2.5 Step 3 documentation.**
