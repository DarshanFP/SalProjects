# Phase 0 Completion – Validation & Normalization Layer

## Scope

Phase 0 implemented **reusable, testable validation and normalization infrastructure** only. No controller logic, FormRequests, routes, database, or views were modified. No runtime normalization was wired; this phase builds primitives for consumption in Phase 1.

**Implemented:**

1. **Shared validation rules** under `app/Rules/`:
   - `NumericBoundsRule` – enforces DECIMAL(10,2) bounds (min 0, max 99999999.99); accepts numeric values only; rejects values outside bounds; does not normalize.
   - `OptionalIntegerRule` – validates nullable integer fields; accepts null, empty string, and integers ≥ 0; rejects negative integers, non-integer strings, and floats.

2. **Normalization helpers** under `app/Support/Normalization/` (pure, non-mutating):
   - `PlaceholderNormalizer` – canonical placeholder set: `-`, `N/A`, `n/a`, `NA`, `--`; methods: `isPlaceholder($value)`, `normalizeToNull($value)`, `normalizeToZero($value)`.
   - `BooleanNormalizer` – normalizes values like `"true"`, `"false"`, `"1"`, `"0"`, `"on"`, `"off"`, null to int `0` or `1` via `toInt($value)`.
   - `NumericNormalizer` – pure helpers: `emptyToZero($value)`, `emptyToNull($value)`; do not mutate request.

3. **FormRequest trait (dormant)** under `app/Http/Requests/Concerns/`:
   - `NormalizesInput` – defines `normalizeInput(array $input): array` (default no-op). Does not override `prepareForValidation`; not applied to any FormRequest. Placeholder for Phase 1.

4. **Unit tests** under `tests/Unit/Validation/`:
   - `NumericBoundsRuleTest` – accepts 0, 1, 99999999.99; rejects -1, 100000000, non-numeric string.
   - `PlaceholderNormalizerTest` – recognizes all canonical placeholders; does not recognize random strings.
   - `BooleanNormalizerTest` – "true"→1, "false"→0, "on"→1, "off"→0, null→0.
   - `OptionalIntegerRuleTest` – accepts null/empty/integers ≥0; rejects negative, non-integer, float.
   - `NumericNormalizerTest` – emptyToZero/emptyToNull behavior for null, "", and non-empty values.

## Out of Scope

The following were **explicitly not touched**:

- Controllers (no logic changes).
- Existing FormRequest classes (no rule changes, no trait applied).
- Routes (no changes).
- Database schema (no migrations or schema changes).
- Blade views and JavaScript (no changes).
- Refactoring of existing code paths.
- Phase 1+ fixes (no normalization wired at runtime, no Strategy B in sub-controllers).
- Feature tests and controller tests.

## Artifacts Created

| Type | Path |
|------|------|
| Rule | `app/Rules/NumericBoundsRule.php` |
| Rule | `app/Rules/OptionalIntegerRule.php` |
| Normalizer | `app/Support/Normalization/PlaceholderNormalizer.php` |
| Normalizer | `app/Support/Normalization/BooleanNormalizer.php` |
| Normalizer | `app/Support/Normalization/NumericNormalizer.php` |
| Trait | `app/Http/Requests/Concerns/NormalizesInput.php` |
| Test | `tests/Unit/Validation/NumericBoundsRuleTest.php` |
| Test | `tests/Unit/Validation/OptionalIntegerRuleTest.php` |
| Test | `tests/Unit/Validation/PlaceholderNormalizerTest.php` |
| Test | `tests/Unit/Validation/BooleanNormalizerTest.php` |
| Test | `tests/Unit/Validation/NumericNormalizerTest.php` |
| Doc | `Documentations/V1/A!Review/Phase_0_Completion.md` |

## How Phase 0 Enables Phase 1

- **NumericBoundsRule** will be used in FormRequest rules (and Strategy B) for all DECIMAL(10,2) columns so that max 99999999.99 is enforced without duplicating numeric logic.
- **OptionalIntegerRule** will validate nullable integer columns (e.g. CCI statistics) after placeholders are normalized to null in `prepareForValidation`.
- **PlaceholderNormalizer** will be called inside FormRequest `prepareForValidation()` (or via the `NormalizesInput` trait) to convert `-`, `N/A`, etc. to null or 0 before validation runs.
- **BooleanNormalizer** will normalize checkbox/boolean inputs to 0/1 in `prepareForValidation` for NOT NULL boolean columns (e.g. IIES financial support).
- **NumericNormalizer** will be used for empty→0 and empty→null for NOT NULL and nullable numeric columns in the same normalization step.
- **NormalizesInput** will be applied to FormRequests in Phase 1; its `normalizeInput()` will be overridden to merge normalized values into the request input (e.g. in `prepareForValidation`), so validation and persistence see only normalized data.

## Verification Checklist

- [ ] No controller modified
- [ ] No FormRequest modified
- [ ] No routes modified
- [ ] All unit tests passing

Run: `php artisan test tests/Unit/Validation/`
