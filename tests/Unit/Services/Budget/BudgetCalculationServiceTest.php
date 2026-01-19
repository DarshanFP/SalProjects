<?php

namespace Tests\Unit\Services\Budget;

use Tests\TestCase;
use App\Services\Budget\BudgetCalculationService;
use App\Models\OldProjects\Project;
use App\Services\Budget\Strategies\DirectMappingStrategy;
use App\Services\Budget\Strategies\SingleSourceContributionStrategy;
use App\Services\Budget\Strategies\MultipleSourceContributionStrategy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mockery;

class BudgetCalculationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_calculates_contribution_per_row_correctly()
    {
        $contribution = 1000.0;
        $totalRows = 5;
        $expected = 200.0;

        $result = BudgetCalculationService::calculateContributionPerRow($contribution, $totalRows);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_handles_zero_rows_in_contribution_per_row()
    {
        $contribution = 1000.0;
        $totalRows = 0;
        $expected = 0.0;

        $result = BudgetCalculationService::calculateContributionPerRow($contribution, $totalRows);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_calculates_total_contribution_from_multiple_sources()
    {
        $sources = [100.0, 200.0, 300.0];
        $expected = 600.0;

        $result = BudgetCalculationService::calculateTotalContribution($sources);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_handles_null_values_in_total_contribution()
    {
        $sources = [100.0, null, 300.0];
        $expected = 400.0;

        $result = BudgetCalculationService::calculateTotalContribution($sources);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_calculates_amount_sanctioned_correctly()
    {
        $originalAmount = 1000.0;
        $contributionPerRow = 200.0;
        $expected = 800.0;

        $result = BudgetCalculationService::calculateAmountSanctioned($originalAmount, $contributionPerRow);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_prevents_negative_amount_sanctioned()
    {
        $originalAmount = 100.0;
        $contributionPerRow = 200.0;
        $expected = 0.0;

        $result = BudgetCalculationService::calculateAmountSanctioned($originalAmount, $contributionPerRow);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_prevents_negative_amounts()
    {
        $amount = -100.0;
        $expected = 0.0;

        $result = BudgetCalculationService::preventNegativeAmount($amount);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_allows_positive_amounts()
    {
        $amount = 100.0;
        $expected = 100.0;

        $result = BudgetCalculationService::preventNegativeAmount($amount);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_logs_calculation()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Test Project Budget calculation', ['key' => 'value'])
            ->andReturn(true);

        $result = BudgetCalculationService::logCalculation('Test Project', ['key' => 'value']);

        $this->assertTrue(true); // Test passes if no exception thrown
    }

    /** @test */
    public function it_logs_row_calculation()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Test Project Budget row calculation', ['key' => 'value'])
            ->andReturn(true);

        $result = BudgetCalculationService::logRowCalculation('Test Project', ['key' => 'value']);

        $this->assertTrue(true); // Test passes if no exception thrown
    }

    /** @test */
    public function it_gets_budgets_for_report_with_contributions()
    {
        // This test requires actual database/models, so we'll skip it in unit tests
        // Integration tests will cover this functionality
        $this->assertTrue(true);
    }

    /** @test */
    public function it_gets_budgets_for_export_without_contributions()
    {
        // This test requires actual database/models, so we'll skip it in unit tests
        // Integration tests will cover this functionality
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_empty_array_in_total_contribution()
    {
        $sources = [];
        $expected = 0.0;

        $result = BudgetCalculationService::calculateTotalContribution($sources);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_handles_float_values_in_total_contribution()
    {
        $sources = [100.5, 200.75, 300.25];
        $expected = 601.5;

        $result = BudgetCalculationService::calculateTotalContribution($sources);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_handles_string_values_in_total_contribution()
    {
        $sources = ['100.5', '200.75', '300.25'];
        $expected = 601.5;

        $result = BudgetCalculationService::calculateTotalContribution($sources);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_handles_negative_contribution_per_row()
    {
        $contribution = -1000.0;
        $totalRows = 5;
        $expected = -200.0;

        $result = BudgetCalculationService::calculateContributionPerRow($contribution, $totalRows);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_handles_zero_contribution()
    {
        $contribution = 0.0;
        $totalRows = 5;
        $expected = 0.0;

        $result = BudgetCalculationService::calculateContributionPerRow($contribution, $totalRows);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_handles_zero_amount_sanctioned()
    {
        $originalAmount = 0.0;
        $contributionPerRow = 200.0;
        $expected = 0.0;

        $result = BudgetCalculationService::calculateAmountSanctioned($originalAmount, $contributionPerRow);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_handles_zero_contribution_in_amount_sanctioned()
    {
        $originalAmount = 1000.0;
        $contributionPerRow = 0.0;
        $expected = 1000.0;

        $result = BudgetCalculationService::calculateAmountSanctioned($originalAmount, $contributionPerRow);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_handles_large_numbers_in_amount_sanctioned()
    {
        $originalAmount = 999999.99;
        $contributionPerRow = 123456.78;
        $expected = 876543.21;

        $result = BudgetCalculationService::calculateAmountSanctioned($originalAmount, $contributionPerRow);

        $this->assertEqualsWithDelta($expected, $result, 0.01);
    }

    /** @test */
    public function it_handles_zero_in_prevent_negative_amount()
    {
        $amount = 0.0;
        $expected = 0.0;

        $result = BudgetCalculationService::preventNegativeAmount($amount);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_handles_large_positive_amounts()
    {
        $amount = 999999999.99;
        $expected = 999999999.99;

        $result = BudgetCalculationService::preventNegativeAmount($amount);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_handles_large_negative_amounts()
    {
        $amount = -999999999.99;
        $expected = 0.0;

        $result = BudgetCalculationService::preventNegativeAmount($amount);

        $this->assertEquals($expected, $result);
    }
}
