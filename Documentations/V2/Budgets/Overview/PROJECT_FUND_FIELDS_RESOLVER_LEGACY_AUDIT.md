# ProjectFundFieldsResolver Legacy Audit Report

**Document Version:** 1.0  
**Date:** 2025-02-09  
**Scope:** Read-only analysis — no code changes.

**Target:** `app/Services/Budget/ProjectFundFieldsResolver.php`

---

## Section 1 – Live Execution Path

### Current Active Path

```
External caller (ProjectController, BudgetSyncService, AdminCorrectionService, Tests)
    ↓
ProjectFundFieldsResolver::resolve($project, $dryRun)
    ↓
ProjectFinancialResolver::resolve($project)  ← Direct delegation; NO call to resolveForType()
    ↓
BudgetAuditLogger::logResolverCall() / logDiscrepancy()
    ↓
getStoredValues($project)
hasDiscrepancy($resolved, $stored)
    ↓
return $resolved  (from ProjectFinancialResolver)
```

**Confirmed:** `resolve()` immediately delegates to `ProjectFinancialResolver::resolve()`. The legacy type-specific chain (`resolveForType` → `resolveDevelopment` / `resolveIndividualOrIge` → type methods) is **never invoked**.

---

## Section 2 – Methods in ProjectFundFieldsResolver

| Method | Purpose | Referenced? | Safe To Remove? |
|--------|---------|-------------|-----------------|
| `resolve()` | Entry point; delegates to ProjectFinancialResolver; audit logging | Yes — ProjectController, BudgetSyncService, AdminCorrectionService, ViewEditParityTest, ProjectFinancialResolverParityTest | **No** |
| `resolveForType()` | Legacy type dispatch (individual/IGE vs development) | **No** — never called | **Yes** |
| `resolveDevelopment()` | Phase-based budget resolution (budgets filtered by current_phase) | **No** — only via resolveForType | **Yes** |
| `resolveIIES()` | IIES type-specific resolution | **No** — only via resolveIndividualOrIge | **Yes** |
| `resolveIES()` | IES type-specific resolution | **No** — only via resolveIndividualOrIge | **Yes** |
| `resolveILP()` | ILP type-specific resolution | **No** — only via resolveIndividualOrIge | **Yes** |
| `resolveIAH()` | IAH type-specific resolution | **No** — only via resolveIndividualOrIge | **Yes** |
| `resolveIGE()` | IGE type-specific resolution | **No** — only via resolveIndividualOrIge | **Yes** |
| `resolveIndividualOrIge()` | Load relations; dispatch to IIES/IES/ILP/IAH/IGE | **No** — only via resolveForType | **Yes** |
| `fallbackFromProject()` | Fallback when type-specific data missing | **No** — only via type-specific methods | **Yes** |
| `getStoredValues()` | Read DB values for audit comparison | **Yes** — called by resolve() | **No** |
| `hasDiscrepancy()` | Compare resolved vs stored with tolerance | **Yes** — called by resolve() | **No** |
| `normalize()` | Round and clamp values to non-negative floats | **No** — only called by dead methods (resolveDevelopment, resolveIIES, etc., fallbackFromProject) | **Yes** |

---

## Section 3 – Dead Code Candidates

The following methods are **confirmed unreachable** via the current execution path:

1. **resolveForType()** — Never called; `resolve()` delegates directly to ProjectFinancialResolver
2. **resolveDevelopment()** — Only called by resolveForType
3. **resolveIndividualOrIge()** — Only called by resolveForType
4. **resolveIIES()** — Only called by resolveIndividualOrIge
5. **resolveIES()** — Only called by resolveIndividualOrIge
6. **resolveILP()** — Only called by resolveIndividualOrIge
7. **resolveIAH()** — Only called by resolveIndividualOrIge
8. **resolveIGE()** — Only called by resolveIndividualOrIge
9. **fallbackFromProject()** — Only called by type-specific methods
10. **normalize()** — Only called by the above dead methods; `resolve()` never calls it (ProjectFinancialResolver does its own normalization)

---

## Section 4 – Risk Assessment

### If Dead Methods Are Removed

| Risk | Level | Mitigation |
|------|-------|------------|
| **Production behavior change** | **None** | Dead code is never executed; removal has no runtime effect |
| **Test breakage** | **Low** | ProjectFinancialResolverParityTest compares `ProjectFundFieldsResolver::resolve()` vs `ProjectFinancialResolver::resolve()`. Since the former delegates to the latter, tests assert parity. Removal of dead methods does not change `resolve()` behavior |
| **ViewEditParityTest** | **Low** | Uses `$resolver->resolve()`; unaffected by removal of internal dead methods |
| **Regression if delegation is reverted** | **Medium** | If someone later changes `resolve()` to call `resolveForType()` instead of delegating, the removed methods would be gone. Mitigation: document that ProjectFinancialResolver is the canonical implementation; ProjectFundFieldsResolver is a backward-compat adapter only |

### External References Confirmed

| Consumer | What It Calls | Impact of Dead Code Removal |
|----------|---------------|-----------------------------|
| ProjectController::show() | `$resolver->resolve($project, true)` | None |
| BudgetSyncService | `$this->resolver->resolve($project, false)` | None |
| AdminCorrectionService | `$this->resolver->resolve($project, true)` | None |
| ProjectFinancialResolverParityTest | `$this->oldResolver->resolve($project, true)` | None |
| ViewEditParityTest | `$this->resolver->resolve($project)` | None |

**Note:** `AdminCorrectionService` and `BudgetSyncService` have their own `getStoredValues()` implementations. They do **not** call `ProjectFundFieldsResolver::getStoredValues()`.

---

## Section 5 – Removal Recommendation Plan

**Precondition:** Confirm with team that `ProjectFundFieldsResolver` will remain a thin adapter delegating to `ProjectFinancialResolver`. No plans to revert to legacy resolution.

### Step-by-Step Deletion Order (if safe)

1. **Remove `resolveForType()`** — Root of the dead chain; no other method calls it
2. **Remove `resolveIndividualOrIge()`** — No remaining callers after (1)
3. **Remove type-specific methods in any order:**
   - `resolveDevelopment()`
   - `resolveIIES()`
   - `resolveIES()`
   - `resolveILP()`
   - `resolveIAH()`
   - `resolveIGE()`
4. **Remove `fallbackFromProject()`** — No remaining callers after (3)
5. **Remove `normalize()`** — No remaining callers after (4)

### Keep (Required for Live Path)

- `resolve()` — Entry point; delegates to ProjectFinancialResolver; audit logging
- `getStoredValues()` — Used by `resolve()` for audit comparison
- `hasDiscrepancy()` — Used by `resolve()` for audit comparison

---

## Codebase Search Summary

### References to Legacy Method Names

| Pattern | External References |
|---------|---------------------|
| `resolveForType(` | **None** outside ProjectFundFieldsResolver |
| `resolveDevelopment(` | **None** outside ProjectFundFieldsResolver |
| `resolveIIES(` | **None** — DirectMappedIndividualBudgetStrategy has its own private `resolveIIES` |
| `resolveIES(` | **None** — Same as above |
| `resolveILP(` | **None** |
| `resolveIAH(` | **None** |
| `resolveIGE(` | **None** |

### Fallback Patterns

- **`amount_sanctioned ?? overall_project_budget`** — Not present in ProjectFundFieldsResolver (dead methods use type-specific logic). Exists elsewhere in codebase; out of scope for this resolver audit.
- **`overall_project_budget` fallback** — In dead `resolveDevelopment()`: `(float) ($project->overall_project_budget ?? 0)` when phaseBudgets empty. Unreachable.

---

## Appendix – File Locations

| Item | Path |
|------|------|
| ProjectFundFieldsResolver | `app/Services/Budget/ProjectFundFieldsResolver.php` |
| ProjectFinancialResolver | `app/Domain/Budget/ProjectFinancialResolver.php` |
| PhaseBasedBudgetStrategy | `app/Domain/Budget/Strategies/PhaseBasedBudgetStrategy.php` |
| DirectMappedIndividualBudgetStrategy | `app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php` |
