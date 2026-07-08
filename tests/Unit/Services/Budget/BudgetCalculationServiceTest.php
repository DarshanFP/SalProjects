<?php

namespace Tests\Unit\Services\Budget;

use App\Services\Budget\BudgetCalculationService;
use Tests\TestCase;

/**
 * Unit tests for BudgetCalculationService static helpers (Phase 10.3).
 */
class BudgetCalculationServiceTest extends TestCase
{
    public function test_calculate_contribution_per_row_divides_evenly(): void
    {
        $this->assertSame(500.0, BudgetCalculationService::calculateContributionPerRow(1000, 2));
    }

    public function test_calculate_contribution_per_row_returns_zero_when_no_rows(): void
    {
        $this->assertSame(0.0, BudgetCalculationService::calculateContributionPerRow(1000, 0));
    }

    public function test_calculate_total_contribution_sums_sources(): void
    {
        $total = BudgetCalculationService::calculateTotalContribution([1000, 500, null, '250']);

        $this->assertSame(1750.0, $total);
    }

    public function test_calculate_amount_sanctioned_subtracts_contribution(): void
    {
        $this->assertSame(9000.0, BudgetCalculationService::calculateAmountSanctioned(10000, 1000));
    }

    public function test_calculate_amount_sanctioned_never_negative(): void
    {
        $this->assertSame(0.0, BudgetCalculationService::calculateAmountSanctioned(500, 1000));
    }

    public function test_prevent_negative_amount_clamps_to_zero(): void
    {
        $this->assertSame(0.0, BudgetCalculationService::preventNegativeAmount(-1.5));
        $this->assertSame(10.0, BudgetCalculationService::preventNegativeAmount(10));
    }
}
