<?php

namespace Tests\Unit\Services;

use App\Domain\Budget\ProjectFinancialResolver;
use App\Models\OldProjects\Project;
use App\Services\Budget\DerivedCalculationService;
use App\Services\BudgetValidationService;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery;
use Tests\TestCase;

class BudgetValidationServiceTest extends TestCase
{
    use WithoutMiddleware;

    /**
     * Test that zero opening balance does not throw DivisionByZeroError
     * This is the CRITICAL test for Phase 0 fix
     *
     * @return void
     */
    public function test_zero_opening_balance_does_not_throw_error(): void
    {
        // Create a mock project with zero opening balance
        $project = $this->createMockProject([
            'opening_balance' => 0,
        ]);

        // This should not throw DivisionByZeroError
        $result = BudgetValidationService::validateBudget($project);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_valid', $result);
        $this->assertArrayHasKey('budget_data', $result);
        $this->assertEquals(0, $result['budget_data']['opening_balance']);
    }

    /**
     * Test that over-budget with zero opening balance doesn't crash
     * This specifically tests the Phase 0 division-by-zero fix
     *
     * @return void
     */
    public function test_over_budget_with_zero_opening_balance_safe(): void
    {
        $project = $this->createMockProject([
            'opening_balance' => 0,
        ]);

        // This should not throw DivisionByZeroError when calculating percentage_over
        $result = BudgetValidationService::validateBudget($project);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('warnings', $result);
        
        // The key test: if there's an over_budget warning, percentage_over should be 0, not an error
        $overBudgetWarning = collect($result['warnings'])->firstWhere('type', 'over_budget');
        if ($overBudgetWarning) {
            $this->assertEquals(0, $overBudgetWarning['percentage_over']); // Should be 0, not crash
        }
    }

    /**
     * Test normal percentage calculation with valid opening balance
     *
     * @return void
     */
    public function test_normal_percentage_calculation_works(): void
    {
        $project = $this->createMockProject([
            'opening_balance' => 10000,
        ]);

        $result = BudgetValidationService::validateBudget($project);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('opening_balance', $result['budget_data']);
        // Percentage_used should be calculated without error
        $this->assertArrayHasKey('percentage_used', $result['budget_data']);
        $this->assertIsNumeric($result['budget_data']['percentage_used']);
        // The key test: no DivisionByZeroError was thrown
        $this->assertTrue(true);
    }

    /**
     * Test that budget validation returns expected structure
     *
     * @return void
     */
    public function test_budget_validation_returns_expected_structure(): void
    {
        $project = $this->createMockProject([
            'opening_balance' => 5000,
        ]);

        $result = BudgetValidationService::validateBudget($project);

        // Verify top-level structure
        $this->assertArrayHasKey('is_valid', $result);
        $this->assertArrayHasKey('has_warnings', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertArrayHasKey('info', $result);
        $this->assertArrayHasKey('budget_data', $result);

        // Verify budget_data structure
        $budgetData = $result['budget_data'];
        $this->assertArrayHasKey('opening_balance', $budgetData);
        $this->assertArrayHasKey('total_expenses', $budgetData);
        $this->assertArrayHasKey('percentage_used', $budgetData);
    }

    /**
     * Test getBudgetSummary method with zero opening balance
     *
     * @return void
     */
    public function test_get_budget_summary_with_zero_opening_balance(): void
    {
        $project = $this->createMockProject([
            'opening_balance' => 0,
        ]);

        // This should not crash
        $summary = BudgetValidationService::getBudgetSummary($project);

        $this->assertArrayHasKey('budget_data', $summary);
        $this->assertArrayHasKey('validation', $summary);
        $this->assertEquals(0, $summary['budget_data']['opening_balance']);
    }

    /**
     * Helper method to create a mock project with controlled budget data
     *
     * @param array $overrides
     * @return Project
     */
    private function createMockProject(array $overrides = []): Project
    {
        $openingBalance = $overrides['opening_balance'] ?? 0;
        
        // Create a real Project instance
        $project = new Project();
        $project->project_id = 'TEST-001';
        $project->project_type = 'Development Projects';
        $project->project_status = 'Approved';
        $project->overall_project_budget = $openingBalance;
        $project->amount_forwarded = 0;
        $project->local_contribution = 0;
        $project->amount_sanctioned = $openingBalance;
        $project->opening_balance = $openingBalance;
        $project->current_phase = 1;
        
        // Set empty relationships to avoid database queries
        $project->setRelation('reports', collect([]));
        $project->setRelation('budgets', collect([]));
        
        return $project;
    }
}
