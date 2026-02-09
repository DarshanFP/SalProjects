# Derived Calculation Freeze Test Report

**Date**: 2026-02-09  
**File**: `tests/Feature/Budget/DerivedCalculationFreezeTest.php`  
**Purpose**: Regression tests that freeze derived calculation behavior before Phase 2.4 implementation.

---

## Test Summary

| # | Test Method | Status | Assertions |
|---|-------------|--------|------------|
| 1 | `test_row_calculation_freeze_calculate_total_budget_equals_rate_quantity_times_rate_multiplier_times_rate_duration` | ✓ Pass | 1 |
| 2 | `test_phase_total_freeze_sum_this_phase_equals_expected_and_export_sum_matches` | ✓ Pass | 3 |
| 3 | `test_controller_trust_freeze_backend_does_not_alter_submitted_this_phase` | ✓ Pass | 1 |
| 4 | `test_bounds_freeze_very_large_values_clamped_to_phase_2_3_max` | ✓ Pass | 2 |
| 5 | `test_bounds_freeze_value_at_max_persists_correctly` | ✓ Pass | 1 |

**Total**: 5 tests, 11 assertions

---

## Coverage Details

### 1️⃣ Row Calculation Freeze

**Test**: `test_row_calculation_freeze_calculate_total_budget_equals_rate_quantity_times_rate_multiplier_times_rate_duration`

- Creates a Development Project
- Inserts a ProjectBudget row manually
- **Asserts**: `ProjectBudget::calculateTotalBudget()` equals `rate_quantity × rate_multiplier × rate_duration`
- **Frozen behavior**: Model formula is `q × m × d` (no rate_increase)

---

### 2️⃣ Phase Total Freeze

**Test**: `test_phase_total_freeze_sum_this_phase_equals_expected_and_export_sum_matches`

- Creates a Development Project
- Inserts 3 ProjectBudget rows (this_phase: 1000, 2000, 3000)
- **Asserts**:
  - Row count = 3
  - `sum(this_phase)` = 6000
  - Export/report sum (project.budgets where phase=1) matches same total
- **Frozen behavior**: Phase total = sum of persisted `this_phase`; export uses same sum

---

### 3️⃣ Controller Trust Freeze

**Test**: `test_controller_trust_freeze_backend_does_not_alter_submitted_this_phase`

- Creates a Development Project with one budget row
- Submits edit payload with `this_phase = 1234.56` (pre-computed value)
- **Asserts**:
  - Response redirect (302)
  - `assertDatabaseHas` — persisted `this_phase` equals submitted value
- **Frozen behavior**: Backend trusts client-submitted `this_phase`; does not recalculate from inputs

---

### 4️⃣ Bounds Freeze (Validation Rejects Over Max)

**Test**: `test_bounds_freeze_very_large_values_clamped_to_phase_2_3_max`

- Submits `this_phase = 100000000` (above max 99999999.99)
- **Asserts**:
  - `assertSessionHasErrors('phases.0.budget.0.this_phase')`
  - `assertDatabaseMissing` — value not persisted
- **Frozen behavior**: NumericBoundsRule rejects values above config max; Phase 2.3 bounds enforced

---

### 5️⃣ Bounds Freeze (Value at Max Persists)

**Test**: `test_bounds_freeze_value_at_max_persists_correctly`

- Submits `this_phase = 99999999.99` (exactly at max bound)
- **Asserts**:
  - Response redirect (302)
  - `assertDatabaseHas` — value persisted correctly
- **Frozen behavior**: Values at max bound pass validation and persist

---

## Run Command

```bash
php artisan test tests/Feature/Budget/DerivedCalculationFreezeTest.php
```

---

## Dependencies

- `actingAs` executor user
- `RefreshDatabase` trait
- Project factory with `ProjectType::DEVELOPMENT_PROJECTS`
- `ProjectStatus::DRAFT` for editable budget
