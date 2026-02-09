# Phase 2.3 — BoundedNumericService / DecimalBounds (Design Only)

## 1. Purpose and Problem Statement

### Tie to Phase 0.4 Budget Overflow Guard

Phase 0.4 introduced inline clamping in `BudgetController`: before each `ProjectBudget::create()`, `this_phase` and `next_phase` are clamped with `min((float)($v ?? 0), 99999999.99)` to prevent SQL overflow. That fix **stopped** the production error (`SQLSTATE[22003]: Numeric value out of range: 1264 Out of range value for column 'this_phase'`) but left the guard **embedded** in controller logic with a hardcoded magic number.

### Tie to Phase 1A.2 Derived-Field Enforcement

Phase 1A.2 enforced server-side recalculation of derived budget fields: `this_phase` is computed from `rate_quantity × rate_multiplier × rate_duration` (and related formula) rather than trusting client-side values. Values are clamped to a maximum before persistence. That pattern exists in the controller and relies on the same `99999999.99` constant.

### Why Ad-Hoc Numeric Clamping Is Insufficient

| Limitation | Consequence |
|------------|-------------|
| **Magic numbers scattered** | `99999999.99` appears in `BudgetController::PHASE_MAX`, `NumericBoundsRule::MAX`, and implicitly in any future clamping. Changing column precision (e.g. decimal 12,2) requires edits in multiple places. |
| **Validation and clamping decoupled** | `NumericBoundsRule` validates input; controller clamps before write. Both use the same max, but there is no single source of truth. A new field requiring bounds would need both a rule and controller clamping added manually. |
| **No schema alignment** | Column `decimal(10,2)` implies max 99999999.99. That mapping is implicit. If schema changes, validation and clamping can drift unless manually updated. |
| **JS/PHP rule drift** | Client-side validation may use different limits. Without a config-driven approach, server and client can diverge (e.g. JS allows 999999999, PHP clamps to 99999999.99) or vice versa. |
| **Duplicate logic across modules** | IIES expenses, IES amounts, and budget fields each use `NumericBoundsRule` with the same hardcoded max. Per-field or per-table bounds (e.g. smaller max for a specific column) would require new rules or branching. |

### Class of Bugs This Prevents

| Bug Class | Prevention |
|-----------|------------|
| **SQL numeric overflow** | All writes to bounded decimal columns pass through a clamp or validation driven by config; column max enforced server-side. |
| **Config/schema drift** | Bounds defined in one place; validation and clamping read from it. Schema change triggers config update; no silent drift. |
| **Inconsistent JS/PHP rules** | Config serves as the canonical source; documentation and (future) client generation can align JS validation with server bounds. |
| **Scattered magic numbers** | No inline `99999999.99`; all bounds come from DecimalBounds config or BoundedNumericService. |

---

## 2. Responsibilities and Non-Responsibilities

### Responsibilities

| Responsibility | Description |
|----------------|-------------|
| **Centralize numeric bounds** | Provide a single place (config or service) where per-field or per-table max (and optionally min) values are defined. |
| **Drive validation and clamping** | `NumericBoundsRule` reads bounds from config; controllers and services call `clamp()` or `calculateAndClamp()` before persistence. |
| **Guarantee server-side safety** | Ensure derived fields (e.g. `this_phase`) and direct inputs are clamped before DB write, regardless of client behavior. |
| **Schema-aligned defaults** | Bounds should reflect or document existing column precision (e.g. `decimal(10,2)` → max 99999999.99). |

### Non-Responsibilities

| Non-Responsibility | Reason |
|-------------------|--------|
| **Database schema changes** | Phase 2.3 does not alter column definitions. Bounds are defined for existing columns. |
| **Altering existing formulas** | Budget formula (`rate_quantity × rate_multiplier × rate_duration` etc.) remains unchanged. Phase 2.3 wraps the result with clamping; does not redesign the formula. |
| **Replacing validation logic** | FormRequest rules and `NumericBoundsRule` remain. Phase 2.3 integrates NumericBoundsRule with config; does not remove or bypass validation. |
| **Reports or exports** | Numeric bounds apply to incoming writes and validation. Report/export logic that reads and formats existing data is out of scope. |
| **config/budget.php structural changes** | That config is used by BudgetSyncService, ProjectFundFieldsResolver. Phase 2.3 does not modify its structure. New bounds config is additive (separate file or section). |
| **JS validation rewrite** | Phase 2.3 is server-side. Client-side validation may be updated later to align with config; that is not Phase 2.3 scope. |

---

## 3. Conceptual Building Blocks (No Code)

### DecimalBounds (Config-Driven)

A configuration structure that defines numeric bounds per table and field. Conceptually: a nested map such as `table_name => field_name => [min, max]`. The canonical example is `project_budgets.this_phase` and `project_budgets.next_phase` with `max => 99999999.99`, aligned to `decimal(10,2)` column precision. Default bounds may apply when a specific field is not defined. This config is the **single source of truth** for what values are allowed; both validation and clamping read from it.

### BoundedNumericService

A service that provides: (a) lookup of max (and optionally min) for a given field identifier; (b) `clamp(value, max)` to ensure a value does not exceed (or fall below) bounds; (c) `calculateAndClamp(formula, inputs, bounds)` for derived fields — compute the formula result then clamp to the given max. The service does not perform database operations; it is pure computation and config lookup. Controllers and validation invoke it.

### NumericBoundsRule (Validation Integration)

The existing Laravel validation rule that ensures a value is numeric and within bounds. Phase 2.3 extends it to **read bounds from config** (via BoundedNumericService or direct config lookup) instead of a hardcoded constant. The rule receives a field identifier (e.g. `project_budgets.this_phase`) and validates that the value is within the configured min/max. Rule behavior (pass/fail, message) remains the same; only the source of bounds changes.

### calculateAndClamp() Role

For derived fields such as `this_phase`, the value is computed from other inputs (rate_quantity, rate_multiplier, rate_duration) rather than submitted directly. `calculateAndClamp()` accepts a formula (or callable), the input values, and the max bound. It computes the result, then clamps it to the bound before the controller persists it. This centralizes the "recalculate + clamp" pattern that Phase 1A.2 introduced, so it is not duplicated inline in the controller.

### Boundaries and Data Flow

```
Config (DecimalBounds)
    │
    ├──► NumericBoundsRule (validates incoming request values)
    │         uses getMaxFor(field) or equivalent
    │
    └──► BoundedNumericService
              │
              ├── getMaxFor(field) ──► used by rule and controllers
              ├── clamp(value, max) ──► used by controllers before create/update
              └── calculateAndClamp(formula, inputs, max) ──► used for derived fields
```

Validation runs first (FormRequest + NumericBoundsRule). Controller then applies clamping (or calculateAndClamp for derived fields) before persistence. Both validation and clamping use the same config-backed bounds.

---

## 4. Public Interfaces (Pseudocode Only)

### BoundedNumericService

```
getMaxFor(string $fieldIdentifier): float
// Returns configured max for field (e.g. 'project_budgets.this_phase').
// If not configured, returns a safe default (e.g. 99999999.99 for decimal(10,2)).

getMinFor(string $fieldIdentifier): float
// Returns configured min (default 0 for non-negative fields).

clamp(float $value, float $max, float $min = 0): float
// Returns value clamped to [min, max]. Pure function; no I/O.

calculateAndClamp(callable $formula, array $inputs, float $max, float $min = 0): float
// Evaluates formula($inputs), then clamps result to [min, max].
// Used for derived fields (e.g. this_phase = rate_quantity * rate_multiplier * rate_duration).
```

### NumericBoundsRule (Extended Contract)

```
NumericBoundsRule(fieldIdentifier?: string)
// When fieldIdentifier provided: reads bounds from config for that field.
// When omitted: uses default max (backward compatible with existing usage).
// passes(attribute, value): ensures value is numeric and within [min, max].
// message(): returns error message including the configured bounds.
```

### Config Shape (Conceptual)

```
decimal_bounds:
  project_budgets:
    this_phase: { max: 99999999.99, min: 0 }
    next_phase: { max: 99999999.99, min: 0 }
    rate_quantity: { max: 99999999.99, min: 0 }
    ...
  default:
    max: 99999999.99
    min: 0
```

Actual key structure (flat vs nested, naming) is an implementation detail. The design requires only that bounds are retrievable by table and field.

---

## 5. Configuration Model

### How Numeric Bounds Are Defined

Bounds are defined in a dedicated config file or config section (e.g. `config/decimal_bounds.php` or a `decimal_bounds` key in an existing config). The structure maps table name and field name to `min` and `max` values. For `decimal(10,2)` columns, max is 99999999.99 (10 digits before decimal, 2 after). The config documents this alignment explicitly so that when the schema changes, the config is updated in the same migration plan.

### Tie to Existing Column Precision

| Column Type | Implied Max (example) | Config Value |
|-------------|------------------------|--------------|
| decimal(10,2) | 99999999.99 | max: 99999999.99 |
| decimal(12,2) | 9999999999.99 | max: 9999999999.99 |
| int | 2147483647 (if signed) | per-column if needed |

Phase 2.3 does not migrate schema. It documents the relationship: config values must match current column precision. A future schema migration would include a coordinated config update.

### How Config Avoids JS/PHP Drift

- **Single source**: Bounds live in config. PHP validation and clamping read from config.
- **Documentation**: Config can be exported or documented for frontend use. JS validation can reference the same values (manually or via build-time generation).
- **No hardcoding**: Removing `99999999.99` from `NumericBoundsRule` and `BudgetController` forces all consumers to use the service or config. A single config change propagates everywhere.

### No Modification to Existing Config Files

Phase 2.3 design does not require modifying `config/budget.php`. That file remains the source for field mappings, strategies, and feature flags. Decimal bounds are a separate concern and live in a new config file or new section. Implementation may choose to add a `decimal_bounds` section to an existing file if that fits project conventions; the design only requires that bounds are centralized and not hardcoded.

---

## 6. Integration Points

### Where Controllers Call Clamping

- **BudgetController**: Before `ProjectBudget::create()`, instead of `min((float)($v ?? 0), self::PHASE_MAX)`, the controller calls `BoundedNumericService::clamp($value, $service->getMaxFor('project_budgets.this_phase'))` (or equivalent). Same for `next_phase`. For derived-field recalculation (if Phase 1A.2 logic is present), the controller uses `calculateAndClamp()` with the budget formula and bounds.
- **Other controllers**: Any controller that writes to decimal columns with overflow risk may adopt the same pattern. Pilot is BudgetController; others migrate incrementally.

### How Validation Rules Read Bounds

- **NumericBoundsRule**: Receives optional field identifier. In `passes()`, retrieves min/max via `BoundedNumericService::getMaxFor()` (and `getMinFor()`) instead of using a class constant. FormRequests continue to use `new NumericBoundsRule` or `new NumericBoundsRule('project_budgets.this_phase')` per field. No change to FormRequest structure; only the rule's internal lookup changes.
- **Existing rules**: `UpdateBudgetRequest`, `StoreBudgetRequest`, IIES expense requests, etc. already use NumericBoundsRule. They may optionally pass a field identifier for per-field bounds; otherwise the rule uses a default.

### How Derived Fields Use Shared Logic

- **Budget this_phase / next_phase**: Formula is `rate_quantity * rate_multiplier * rate_duration` (and any rate_increase logic). Controller computes this, then calls `calculateAndClamp($formula, $inputs, $max)` before persisting. The formula itself is unchanged; only the clamping step is centralized.
- **Future derived fields**: Any module with a computed numeric field can use `calculateAndClamp()` with its formula and the appropriate bound from config.

No wiring code is specified here; the design describes the conceptual integration. Implementation will resolve dependency injection, service binding, and exact method signatures.

---

## 7. Explicit Anti-Patterns Replaced

| Anti-Pattern | Replacement |
|--------------|-------------|
| **Hardcoded magic numbers** | `99999999.99` removed from BudgetController, NumericBoundsRule. All bounds come from config. |
| **Inline min/max logic** | Controller calls `BoundedNumericService::clamp()` or `calculateAndClamp()` instead of `min($v, PHASE_MAX)`. |
| **Duplicate budget clamping logic** | Single `calculateAndClamp()` for derived budget fields; no duplicated clamp in store vs update. |
| **NumericBoundsRule with hardcoded MAX** | Rule reads from config via BoundedNumericService. |
| **Validation and clamping out of sync** | Both use the same config; impossible for one to allow a value the other would reject. |

---

## 8. Adoption Strategy

### Incremental Adoption

- **Phase 1**: Introduce config and BoundedNumericService. No controller changes.
- **Phase 2**: Update NumericBoundsRule to read from config (with fallback to current constant for backward compatibility).
- **Phase 3**: Migrate BudgetController to use service for clamping. Remove `PHASE_MAX` constant.
- **Phase 4**: Migrate other controllers that write bounded decimal fields (e.g. IIES expenses) to use the service where appropriate.

### Pilot on Budget Module

Budget is the primary victim of overflow and has the most visible derived-field logic. BudgetController and StoreBudgetRequest/UpdateBudgetRequest are the pilot. Once verified, the pattern extends to IIES, IES, and other modules.

### Backward Compatibility Guarantee

- NumericBoundsRule must continue to validate the same range (0 to 99999999.99) for existing usages until explicitly migrated. If no field identifier is passed, the rule uses default bounds identical to current behavior.
- BudgetController output (created records) must be unchanged for the same input. Clamping behavior remains; only the source of the max value changes.

---

## 9. What This Does NOT Solve

| Concern | Clarification |
|---------|---------------|
| **Schema changes** | No migrations, no column precision changes. Bounds document current schema. |
| **Report/export handling** | Numeric bounds apply to writes. Reports and exports read and format data; they are out of scope. |
| **JS validation rewrite** | Server-side only. Client validation may be updated separately to align with config. |
| **Formula redesign** | Budget formula (rate × multiplier × duration, etc.) is unchanged. Phase 2.3 adds config-driven clamping around existing logic. |
| **config/budget.php changes** | That file is used by BudgetSyncService, ProjectFundFieldsResolver. Phase 2.3 does not alter it. |
| **New calculation formulas** | No new formulas. Only centralization of bounds and clamping. |

---

## 10. Exit Criteria (Design Phase)

- [ ] Design document approved
- [ ] Public interfaces (getMaxFor, clamp, calculateAndClamp, NumericBoundsRule contract) stable
- [ ] Config shape for decimal bounds defined
- [ ] Integration points with BudgetController and NumericBoundsRule documented
- [ ] No production code written
- [ ] No refactor instructions in this document; implementation deferred to a separate phase

---

Design complete — implementation deferred

**Date**: 2026-02-08

---

## Phase 2.3 — Pilot Adoption (BudgetController)

**Date**: 2026-02-09

### Files Created

| File | Purpose |
|------|---------|
| `config/decimal_bounds.php` | DecimalBounds config: per-table/per-field bounds; `project_budgets` fields + `default` fallback |
| `app/Services/Numeric/BoundedNumericService.php` | Service: `getMaxFor()`, `getMinFor()`, `clamp()`, `calculateAndClamp()`; config-backed |

### Files Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/Projects/BudgetController.php` | Removed `PHASE_MAX` constant; use `BoundedNumericService::clamp()` for `this_phase`/`next_phase` |
| `app/Rules/NumericBoundsRule.php` | Refactored to read bounds from config via `BoundedNumericService`; optional `?string $fieldIdentifier` (default: `'default'`) |
| `tests/Unit/Validation/NumericBoundsRuleTest.php` | Base changed from `PHPUnit\Framework\TestCase` to `Tests\TestCase` (required for Config/Service resolution) |

### Before/After Snippet

**BudgetController store/update (before):**

```php
private const PHASE_MAX = 99999999.99;
// ...
$thisPhase = min((float) ($budget['this_phase'] ?? 0), self::PHASE_MAX);
$nextPhase = min((float) ($budget['next_phase'] ?? 0), self::PHASE_MAX);
```

**BudgetController store/update (after):**

```php
$bounded = app(BoundedNumericService::class);
$maxPhase = $bounded->getMaxFor('project_budgets.this_phase');
// ...
$thisPhase = $bounded->clamp((float) ($budget['this_phase'] ?? 0), $maxPhase);
$nextPhase = $bounded->clamp((float) ($budget['next_phase'] ?? 0), $maxPhase);
```

**NumericBoundsRule (before):** Hardcoded `MIN = 0`, `MAX = 99999999.99`.

**NumericBoundsRule (after):** `getMinFor($fieldIdentifier)`, `getMaxFor($fieldIdentifier)` via `BoundedNumericService`; `$fieldIdentifier` defaults to `'default'` for backward compatibility.

### Confirmation: No Behavior Change

- Runtime clamping behavior identical: values clamped to [0, 99999999.99] before DB write.
- Validation behavior identical: same min/max; `NumericBoundsRuleTest` and `Phase1_Budget_ValidationTest` pass.
- No UI changes. No request shape changes. No schema changes. No formula changes.

---

## Phase 2.3 — FULL COMPLETION LOCK

**Date**: 2026-02-09

- **Single source of truth**: `config/decimal_bounds.php` is the canonical source for all decimal column bounds. `BoundedNumericService` and `NumericBoundsRule` read from it exclusively.
- **No hardcoded decimal max**: No controller or validation rule contains inline `99999999.99` or equivalent. All bounds flow from config.
- **Tests pass**: `NumericBoundsRuleTest`, `Phase1_Budget_ValidationTest`, `Phase1_IIES_Expenses_ValidationTest` and related bounds tests pass.
- **No schema changes**: Database column definitions unchanged.
- **No UI changes**: Blade, routes, request shapes unchanged.

**Numeric bounds layer is frozen unless schema precision changes.**
