# Blade resolvedFundFields Scope Check — Temporary Debug

**Type:** Temporary diagnostic patch. Logging only. No refactor, no rendering changes.

---

## File modified

- **Path:** `resources/views/projects/partials/Show/general_info.blade.php`

---

## Exact location

- **Inserted:** Just **before** the first financial field row.
- **Before which label/field:** The row with label **"Overall Project Budget:"** (the first of the six financial fields: Overall Project Budget, Amount Forwarded, Local Contribution, Amount Requested, Amount Sanctioned, Opening Balance).
- **Context:** The debug block sits after the "Commencement Month & Year" row and before the `<tr>` that contains "Overall Project Budget:".

---

## Purpose of the debug

- To diagnose whether `$resolvedFundFields` is available in the scope of the General Info partial when it is rendered (e.g. for project type "Individual - Initial - Educational support" where the UI was observed to show 0.00 for these fields).
- The log records:
  - `exists`: whether `$resolvedFundFields` is set (`isset($resolvedFundFields)`).
  - `type`: its PHP type if set (e.g. `array`).
  - `is_array`: whether it is an array.
  - `value`: the raw value (or `null` if not set), to confirm presence and shape before any fallback.

After loading a project show page, check `storage/logs/laravel.log` for the message **"Blade resolvedFundFields debug"** to see these values.

---

## Confirmation: no rendering logic changed

- **No** existing Blade rendering was modified (all `{{ number_format($resolvedFundFields['...'] ?? 0, 2) }}` and other output are unchanged).
- **No** `@include` calls were changed.
- **No** fallback logic was changed (the existing `$resolvedFundFields = $resolvedFundFields ?? [];` and all `?? 0` usages remain as before).
- **No** formatting or layout was changed.
- **Only** the above `@php` block with `\Log::info(...)` was added immediately before the first financial row.

This patch is strictly diagnostic and should be removed once the scope check is complete.
