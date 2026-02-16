# M3.5.3 — Financial Invariant Assertions

**Milestone:** M3 — Resolver Parity & Financial Stability  
**Step:** M3.5.3 — Financial Invariant Assertions  
**Mode:** Safe Guard Layer  
**Date:** 2025-02-15

---

## Objective

Add invariant checks inside `ProjectFinancialResolver::resolve()` to detect unexpected financial states. Violations are logged as warnings only — no exceptions, no breaking behavior.

---

## Invariants

### If project is approved

| Invariant | Description | Logged when |
|-----------|-------------|-------------|
| `amount_sanctioned > 0` | Approved projects must have a positive sanctioned amount | `amount_sanctioned <= 0` |
| `opening_balance == overall_project_budget` | Opening balance must equal overall budget for approved projects | `abs(opening_balance - overall_project_budget) > 0.01` |

### If project is NOT approved

| Invariant | Description | Logged when |
|-----------|-------------|-------------|
| `amount_sanctioned == 0` | Non-approved projects must not show a sanctioned amount | `abs(amount_sanctioned) > 0.01` |

---

## Why Added

1. **Early detection** — Unexpected data or logic errors surface as log warnings instead of silent wrong totals
2. **Audit trail** — Violations are recorded for investigation without disrupting users
3. **Regression guard** — Future changes that violate invariants will be visible in logs
4. **Domain validation** — Reinforces canonical model: approved vs non-approved semantics

---

## Non-Breaking Behavior

- **No exceptions** — Invariant failures do not throw; they only log
- **No return change** — Resolver still returns the same resolved data
- **Production safe** — Logging is non-blocking; user flows unchanged
- **Float tolerance** — Uses `abs(a - b) > 0.01` for equality checks to avoid floating-point noise

---

## Implementation

```php
// ProjectFinancialResolver::resolve()
$normalized = $this->normalize($result);
$this->assertFinancialInvariants($project, $normalized);
return $normalized;
```

Log format:

```php
Log::warning('Financial invariant violation: <description>', [
    'project_id' => $projectId,
    'amount_sanctioned' => $sanctioned,  // or other relevant fields
    'invariant' => 'invariant_name',
]);
```

---

## Files Modified

| File | Change |
|------|--------|
| `app/Domain/Budget/ProjectFinancialResolver.php` | Added `assertFinancialInvariants()`; called after normalize; uses `Log::warning` on violation |

---

## Risk Assessment

| Risk | Mitigation |
|------|------------|
| False positives | Float tolerance (0.01) for equality; invariants reflect canonical model |
| Performance | Minimal — simple comparisons after resolve |
| Over-logging | Only logs on violation; normal cases are silent |

---

**M3.5.3 Complete — Financial Invariants Guard Active**
