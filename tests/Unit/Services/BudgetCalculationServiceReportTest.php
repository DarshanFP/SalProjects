<?php

namespace Tests\Unit\Services;

use App\Constants\ProjectType;
use App\Models\OldProjects\ILP\ProjectILPBudget;
use App\Models\OldProjects\IIES\ProjectIIESExpenseDetail;
use App\Models\OldProjects\IIES\ProjectIIESExpenses;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use App\Services\Budget\BudgetCalculationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\CreatesMonthlyReportTestData;
use Tests\TestCase;

/**
 * Phase 10.2 — BudgetCalculationService integration per project type (DP, ILP, IIES).
 */
class BudgetCalculationServiceReportTest extends TestCase
{
    use CreatesMonthlyReportTestData;
    use DatabaseTransactions;

    public function test_get_budgets_for_report_returns_dp_phase_rows(): void
    {
        ['project' => $project, 'projectId' => $projectId] = $this->createReportTestContext([
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
        ]);

        ProjectBudget::create([
            'project_id' => $projectId,
            'phase' => 1,
            'particular' => 'Staff salaries',
            'this_phase' => '50000',
        ]);

        $budgets = BudgetCalculationService::getBudgetsForReport($project);

        $this->assertCount(1, $budgets);
        $this->assertSame('Staff salaries', $budgets->first()->particular);
        $this->assertEquals(50000.0, (float) $budgets->first()->this_phase);
    }

    public function test_get_budgets_for_report_calculates_ilp_amount_sanctioned(): void
    {
        ['project' => $project, 'projectId' => $projectId] = $this->createReportTestContext([
            'project_type' => ProjectType::INDIVIDUAL_LIVELIHOOD_APPLICATION,
        ]);

        ProjectILPBudget::create([
            'project_id' => $projectId,
            'budget_desc' => 'Equipment',
            'cost' => '10000',
            'beneficiary_contribution' => '2000',
        ]);
        ProjectILPBudget::create([
            'project_id' => $projectId,
            'budget_desc' => 'Training',
            'cost' => '10000',
            'beneficiary_contribution' => '2000',
        ]);

        $budgets = BudgetCalculationService::getBudgetsForReport($project);

        $this->assertCount(2, $budgets);
        foreach ($budgets as $row) {
            $this->assertSame(9000.0, $row->amount_sanctioned);
        }
    }

    public function test_get_budgets_for_report_calculates_iies_amount_sanctioned(): void
    {
        ['project' => $project, 'projectId' => $projectId] = $this->createReportTestContext([
            'project_type' => ProjectType::INDIVIDUAL_INITIAL_EDUCATIONAL,
        ]);

        $parent = ProjectIIESExpenses::create([
            'project_id' => $projectId,
            'iies_total_expenses' => '40000',
            'iies_expected_scholarship_govt' => '3000',
            'iies_support_other_sources' => '2000',
            'iies_beneficiary_contribution' => '1000',
            'iies_balance_requested' => '34000',
        ]);

        ProjectIIESExpenseDetail::create([
            'IIES_expense_id' => $parent->IIES_expense_id,
            'iies_particular' => 'Tuition',
            'iies_amount' => '20000',
        ]);
        ProjectIIESExpenseDetail::create([
            'IIES_expense_id' => $parent->IIES_expense_id,
            'iies_particular' => 'Books',
            'iies_amount' => '20000',
        ]);

        $budgets = BudgetCalculationService::getBudgetsForReport($project);

        $this->assertCount(2, $budgets);
        foreach ($budgets as $row) {
            // Total contribution 6000 / 2 rows = 3000 per row; 20000 - 3000 = 17000
            $this->assertSame(17000.0, $row->amount_sanctioned);
        }
    }
}
