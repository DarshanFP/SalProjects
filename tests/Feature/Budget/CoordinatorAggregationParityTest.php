<?php

namespace Tests\Feature\Budget;

use App\Constants\ProjectStatus;
use App\Constants\ProjectType;
use App\Domain\Budget\ProjectFinancialResolver;
use App\Models\OldProjects\IIES\ProjectIIESExpenses;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use App\Models\Reports\Monthly\DPAccountDetail;
use App\Models\Reports\Monthly\DPReport;
use App\Models\User;
use App\Services\Budget\DerivedCalculationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Coordinator Aggregation Parity Test
 *
 * Verifies that aggregated totals from CoordinatorController::getSystemPerformanceData()
 * match manual aggregation using ProjectFinancialResolver and DerivedCalculationService.
 *
 * Uses DatabaseTransactions. Clears project-related data at setup so only test data exists.
 *
 * @see Documentations/V2/Budgets/Overview/FINANCIAL_ENGINE_CONSOLIDATION_BLUEPRINT.md
 */
class CoordinatorAggregationParityTest extends TestCase
{
    use DatabaseTransactions;

    protected ProjectFinancialResolver $resolver;

    protected DerivedCalculationService $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(ProjectFinancialResolver::class);
        $this->calc = app(DerivedCalculationService::class);
    }

    /**
     * Clear project and report data so only our test projects exist. Rolled back by DatabaseTransactions.
     */
    private function clearProjectAndReportData(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DPAccountDetail::query()->delete();
        DPReport::query()->delete();
        ProjectBudget::query()->delete();
        ProjectIIESExpenses::query()->delete();
        Project::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function test_system_performance_data_matches_manual_resolver_aggregation(): void
    {
        $this->clearProjectAndReportData();

        $executor = User::factory()->create(['role' => 'executor']);
        $coordinator = User::factory()->create(['role' => 'coordinator']);

        // 1. Create 2 phase-based Development projects (approved)
        $projectA = Project::factory()->create([
            'user_id' => $executor->id,
            'in_charge' => $executor->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::APPROVED_BY_COORDINATOR,
            'goal' => 'Test A',
            'amount_forwarded' => 500,
            'local_contribution' => 500,
            'amount_sanctioned' => 5000,
            'opening_balance' => 6000,
        ]);
        ProjectBudget::create([
            'project_id' => $projectA->project_id,
            'phase' => 1,
            'particular' => 'Item 1',
            'rate_quantity' => 1,
            'rate_multiplier' => 1,
            'rate_duration' => 4000,
            'rate_increase' => 0,
            'this_phase' => 4000,
            'next_phase' => null,
        ]);
        ProjectBudget::create([
            'project_id' => $projectA->project_id,
            'phase' => 1,
            'particular' => 'Item 2',
            'rate_quantity' => 1,
            'rate_multiplier' => 1,
            'rate_duration' => 2000,
            'rate_increase' => 0,
            'this_phase' => 2000,
            'next_phase' => null,
        ]);

        $projectB = Project::factory()->create([
            'user_id' => $executor->id,
            'in_charge' => $executor->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::APPROVED_BY_COORDINATOR,
            'goal' => 'Test B',
            'amount_forwarded' => 300,
            'local_contribution' => 200,
            'amount_sanctioned' => 2500,
            'opening_balance' => 3000,
        ]);
        ProjectBudget::create([
            'project_id' => $projectB->project_id,
            'phase' => 1,
            'particular' => 'Item',
            'rate_quantity' => 1,
            'rate_multiplier' => 1,
            'rate_duration' => 3000,
            'rate_increase' => 0,
            'this_phase' => 3000,
            'next_phase' => null,
        ]);

        // 2. Create 1 IIES project (approved)
        $projectIIES = Project::factory()->create([
            'user_id' => $executor->id,
            'in_charge' => $executor->id,
            'project_type' => ProjectType::INDIVIDUAL_INITIAL_EDUCATIONAL,
            'current_phase' => 1,
            'status' => ProjectStatus::APPROVED_BY_COORDINATOR,
            'goal' => 'Test IIES',
            'amount_sanctioned' => 30000,
            'opening_balance' => 50000,
        ]);
        ProjectIIESExpenses::create([
            'project_id' => $projectIIES->project_id,
            'iies_total_expenses' => 50000,
            'iies_expected_scholarship_govt' => 10000,
            'iies_support_other_sources' => 5000,
            'iies_beneficiary_contribution' => 5000,
            'iies_balance_requested' => 30000,
        ]);

        $approvedProjects = collect([$projectA, $projectB, $projectIIES]);

        // 3. Create DPReport + DPAccountDetail with approved expenses for each project
        $expensesByProject = [
            $projectA->project_id => 1500.00,
            $projectB->project_id => 800.00,
            $projectIIES->project_id => 10000.00,
        ];

        foreach ($approvedProjects as $project) {
            $report = DPReport::factory()->create([
                'project_id' => $project->project_id,
                'user_id' => $executor->id,
                'project_type' => $project->project_type,
                'project_title' => $project->project_title ?? 'Test',
                'status' => DPReport::STATUS_APPROVED_BY_COORDINATOR,
                'report_month_year' => now()->startOfMonth()->format('Y-m-d'),
            ]);
            DPAccountDetail::create([
                'project_id' => $project->project_id,
                'report_id' => $report->report_id,
                'total_expenses' => $expensesByProject[$project->project_id],
            ]);
        }

        // 4. Manual aggregation via resolver
        $totalBudget = 0.0;
        foreach ($approvedProjects as $project) {
            $financials = $this->resolver->resolve($project);
            $totalBudget += (float) ($financials['opening_balance'] ?? 0);
        }

        $approvedReportIds = DPReport::approved()
            ->whereIn('project_id', $approvedProjects->pluck('project_id'))
            ->pluck('report_id');
        $totalExpenses = (float) DPAccountDetail::whereIn('report_id', $approvedReportIds)
            ->sum('total_expenses');

        $totalRemaining = $this->calc->calculateRemainingBalance($totalBudget, $totalExpenses);
        $totalUtilization = $this->calc->calculateUtilization($totalExpenses, $totalBudget);

        // 5. Call controller
        Cache::forget('coordinator_system_performance_data');

        $response = $this->actingAs($coordinator)
            ->get(route('coordinator.dashboard'));

        $response->assertStatus(200);

        $controllerData = $response->viewData('systemPerformanceData');
        $this->assertNotNull($controllerData, 'systemPerformanceData should be present in view');

        $controllerTotalBudget = (float) ($controllerData['total_budget'] ?? 0);
        $controllerTotalExpenses = (float) ($controllerData['total_expenses'] ?? 0);
        $controllerTotalRemaining = (float) ($controllerData['total_remaining'] ?? 0);
        $controllerUtilization = (float) ($controllerData['budget_utilization'] ?? 0);

        // 6. Assert parity
        $this->assertEquals(
            round($totalBudget, 2),
            round($controllerTotalBudget, 2),
            'total_budget must match manual resolver aggregation'
        );
        $this->assertEquals(
            round($totalExpenses, 2),
            round($controllerTotalExpenses, 2),
            'total_expenses must match approved report account details sum'
        );
        $this->assertEquals(
            round($totalRemaining, 2),
            round($controllerTotalRemaining, 2),
            'total_remaining must match calc->calculateRemainingBalance'
        );
        $this->assertEquals(
            round($totalUtilization, 2),
            round($controllerUtilization, 2),
            'budget_utilization must match calc->calculateUtilization'
        );
    }
}
