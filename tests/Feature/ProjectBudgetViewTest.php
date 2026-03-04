<?php

namespace Tests\Feature;

use App\Models\OldProjects\Project;
use App\Services\BudgetValidationService;
use Tests\TestCase;

class ProjectBudgetViewTest extends TestCase
{
    /**
     * Test that BudgetValidationService handles projects without errors
     *
     * @return void
     */
    public function test_approved_project_budget_validation_works(): void
    {
        // Create a simple project instance
        $project = $this->createTestProject([
            'project_id' => 'TEST-001',
            'overall_project_budget' => 10000,
            'opening_balance' => 10000,
        ]);

        // Validate budget - should not throw any exceptions
        $result = BudgetValidationService::validateBudget($project);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_valid', $result);
        $this->assertArrayHasKey('budget_data', $result);
    }

    /**
     * Test that projects with zero opening balance do not crash
     * This is the CRITICAL feature test for Phase 0 division-by-zero fix
     *
     * @return void
     */
    public function test_project_with_zero_opening_balance_does_not_crash(): void
    {
        $project = $this->createTestProject([
            'project_id' => 'TEST-002',
            'overall_project_budget' => 0,
            'opening_balance' => 0,
        ]);

        // This should not throw DivisionByZeroError
        $result = BudgetValidationService::validateBudget($project);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('budget_data', $result);
        $this->assertEquals(0, $result['budget_data']['opening_balance']);
        
        // Verify percentage calculations default to 0
        $this->assertEquals(0, $result['budget_data']['percentage_used']);
    }

    /**
     * Test that budget validation service returns expected structure
     *
     * @return void
     */
    public function test_budget_validation_returns_expected_structure(): void
    {
        $project = $this->createTestProject([
            'project_id' => 'TEST-003',
            'overall_project_budget' => 5000,
            'opening_balance' => 5000,
        ]);

        $result = BudgetValidationService::validateBudget($project);

        // Verify structure
        $this->assertArrayHasKey('is_valid', $result);
        $this->assertArrayHasKey('has_warnings', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertArrayHasKey('info', $result);
        $this->assertArrayHasKey('budget_data', $result);

        // Verify budget_data structure
        $budgetData = $result['budget_data'];
        $this->assertArrayHasKey('overall_budget', $budgetData);
        $this->assertArrayHasKey('opening_balance', $budgetData);
        $this->assertArrayHasKey('total_expenses', $budgetData);
        $this->assertArrayHasKey('remaining_balance', $budgetData);
        $this->assertArrayHasKey('percentage_used', $budgetData);
        $this->assertArrayHasKey('percentage_remaining', $budgetData);
    }

    /**
     * Test that getBudgetSummary method works correctly
     *
     * @return void
     */
    public function test_get_budget_summary_returns_valid_data(): void
    {
        $project = $this->createTestProject([
            'project_id' => 'TEST-005',
            'overall_project_budget' => 15000,
            'opening_balance' => 15000,
        ]);

        $summary = BudgetValidationService::getBudgetSummary($project);

        $this->assertArrayHasKey('budget_data', $summary);
        $this->assertArrayHasKey('validation', $summary);
        
        $validation = $summary['validation'];
        $this->assertArrayHasKey('is_valid', $validation);
        $this->assertArrayHasKey('has_warnings', $validation);
        $this->assertArrayHasKey('errors', $validation);
        $this->assertArrayHasKey('warnings', $validation);
        $this->assertArrayHasKey('info', $validation);
    }

    /**
     * Helper method to create a test project
     *
     * @param array $attributes
     * @return Project
     */
    private function createTestProject(array $attributes = []): Project
    {
        $project = new Project();
        
        // Set required attributes
        $project->project_id = $attributes['project_id'] ?? 'TEST-DEFAULT';
        $project->project_type = 'Development Projects';
        $project->project_status = 'Approved';
        $project->overall_project_budget = $attributes['overall_project_budget'] ?? 0;
        $project->amount_forwarded = $attributes['amount_forwarded'] ?? 0;
        $project->local_contribution = $attributes['local_contribution'] ?? 0;
        $project->amount_sanctioned = $attributes['opening_balance'] ?? 0;
        $project->opening_balance = $attributes['opening_balance'] ?? 0;
        $project->current_phase = $attributes['current_phase'] ?? 1;
        
        // Mock relationships to prevent database queries
        $project->setRelation('reports', collect([]));
        $project->setRelation('budgets', collect([]));
        
        return $project;
    }
}
