# Numeric Bounds Layer — FROZEN

(Phase 2.3 — 2026-02-09)

- All DECIMAL bounds must be defined in `config/decimal_bounds.php`.
- No controller may use hardcoded numeric limits (e.g. `99999999.99`).
- `NumericBoundsRule` must use `BoundedNumericService` for bounds lookup.
- Derived calculations must clamp via `BoundedNumericService::clamp()` or `calculateAndClamp()`.
- Any new DECIMAL column requires a config entry before controller usage.
- Changing max requires schema review and coordinated config update.
