<?php

namespace Tests\Feature\Budget;

use App\Constants\ProjectStatus;
use App\Constants\ProjectType;
use App\Domain\Budget\ProjectFinancialResolver;
use App\Models\OldProjects\IIES\ProjectIIESExpenses;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * First-Time Approval Regression Test
 *
 * Verifies that when a project is approved for the FIRST TIME with
 * amount_sanctioned and opening_balance NULL, the approval flow computes
 * and persists correct values via ProjectFinancialResolver.
 *
 * @see Documentations/V2/Budgets/Overview/WAVE1_STABILITY_REPORT.md
 */
class FirstTimeApprovalRegressionTest extends TestCase
{
    use DatabaseTransactions;

    protected ProjectFinancialResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(ProjectFinancialResolver::class);
    }

    /**
     * Phase-based Development project: budgets exist, sanctioned/opening null.
     * Expected: overall=6000, sanctioned=4500, opening=6000 after approval.
     */
    public function test_phase_based_development_project_first_time_approval_persists_correct_values(): void
    {
        $executor = User::factory()->create(['role' => 'executor']);
        $coordinator = User::factory()->create(['role' => 'coordinator']);

        $project = Project::factory()->create([
            'user_id' => $executor->id,
            'in_charge' => $executor->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::FORWARDED_TO_COORDINATOR,
            'goal' => 'Test goal',
            'amount_forwarded' => 1000,
            'local_contribution' => 500,
            'amount_sanctioned' => null,
            'opening_balance' => null,
        ]);

        ProjectBudget::create([
            'project_id' => $project->project_id,
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
            'project_id' => $project->project_id,
            'phase' => 1,
            'particular' => 'Item 2',
            'rate_quantity' => 1,
            'rate_multiplier' => 1,
            'rate_duration' => 2000,
            'rate_increase' => 0,
            'this_phase' => 2000,
            'next_phase' => null,
        ]);

        // Expected: overall=6000, combined=1500, sanctioned=4500, opening=6000
        $expectedSanctioned = 4500.0;
        $expectedOpening = 6000.0;

        $commencement = now()->startOfMonth();
        $response = $this->actingAs($coordinator)->post(route('projects.approve', $project->project_id), [
            'commencement_month' => (int) $commencement->format('n'),
            'commencement_year' => (int) $commencement->format('Y'),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $project->refresh();

        $this->assertTrue(
            ProjectStatus::isApproved($project->status),
            'Project status should be approved after approval flow'
        );
        $this->assertEquals(
            round($expectedSanctioned, 2),
            round((float) $project->amount_sanctioned, 2),
            'Stored amount_sanctioned should match expected (overall - combined)'
        );
        $this->assertEquals(
            round($expectedOpening, 2),
            round((float) $project->opening_balance, 2),
            'Stored opening_balance should match expected (sanctioned + combined)'
        );
    }

    /**
     * Individual type IIES: expense record exists, sanctioned/opening null.
     * Approve and assert stored values match resolver output.
     */
    public function test_iies_project_first_time_approval_persists_resolver_values(): void
    {
        $executor = User::factory()->create(['role' => 'executor']);
        $coordinator = User::factory()->create(['role' => 'coordinator']);

        $project = Project::factory()->create([
            'user_id' => $executor->id,
            'in_charge' => $executor->id,
            'project_type' => ProjectType::INDIVIDUAL_INITIAL_EDUCATIONAL,
            'current_phase' => 1,
            'status' => ProjectStatus::FORWARDED_TO_COORDINATOR,
            'goal' => 'Test goal',
            'amount_sanctioned' => null,
            'opening_balance' => null,
        ]);

        ProjectIIESExpenses::create([
            'project_id' => $project->project_id,
            'iies_total_expenses' => 50000,
            'iies_expected_scholarship_govt' => 10000,
            'iies_support_other_sources' => 5000,
            'iies_beneficiary_contribution' => 5000,
            'iies_balance_requested' => 30000,
        ]);

        // Resolver: sanctioned = iies_balance_requested, opening = iies_total_expenses
        $financials = $this->resolver->resolve($project);
        $expectedSanctioned = round((float) ($financials['amount_sanctioned'] ?? 0), 2);
        $expectedOpening = round((float) ($financials['opening_balance'] ?? 0), 2);

        $commencement = now()->startOfMonth();
        $response = $this->actingAs($coordinator)->post(route('projects.approve', $project->project_id), [
            'commencement_month' => (int) $commencement->format('n'),
            'commencement_year' => (int) $commencement->format('Y'),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $project->refresh();

        $this->assertTrue(
            ProjectStatus::isApproved($project->status),
            'Project status should be approved after approval flow'
        );
        $this->assertEquals(
            $expectedSanctioned,
            round((float) $project->amount_sanctioned, 2),
            'Stored amount_sanctioned should match resolver output'
        );
        $this->assertEquals(
            $expectedOpening,
            round((float) $project->opening_balance, 2),
            'Stored opening_balance should match resolver output'
        );
    }
}
