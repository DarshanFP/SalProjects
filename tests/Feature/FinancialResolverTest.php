<?php

namespace Tests\Feature;

use App\Constants\ProjectStatus;
use App\Constants\ProjectType;
use App\Domain\Budget\ProjectFinancialResolver;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * M3.5.4: Financial Resolver Integration Tests
 *
 * Validates canonical financial invariants for approved, draft, reverted,
 * and pending-request scenarios. Read-only; no application logic changes.
 *
 * @see Documentations/V2/FinalFix/M3/M3_5_4_Financial_Test_Coverage.md
 */
class FinancialResolverTest extends TestCase
{
    use DatabaseTransactions;

    protected ProjectFinancialResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(ProjectFinancialResolver::class);
    }

    /**
     * 1) Approved project: opening_balance == budget, sanctioned > 0
     */
    public function test_approved_project_opening_balance_equals_budget_and_sanctioned_positive(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'executor']);
        $budget = 10000.00;
        $forwarded = 1000.00;
        $local = 500.00;
        $sanctioned = 8500.00;
        $opening = $budget;

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::APPROVED_BY_COORDINATOR,
            'goal' => 'Test goal',
            'overall_project_budget' => $budget,
            'amount_forwarded' => $forwarded,
            'local_contribution' => $local,
            'amount_sanctioned' => $sanctioned,
            'opening_balance' => $opening,
        ]);

        $resolved = $this->resolver->resolve($project);

        $this->assertGreaterThan(0, $resolved['amount_sanctioned'], 'Approved project must have sanctioned > 0');
        $this->assertEqualsWithDelta($budget, $resolved['opening_balance'], 0.01, 'opening_balance == budget');
        $this->assertEqualsWithDelta($opening, $resolved['overall_project_budget'], 0.01, 'opening_balance == overall_project_budget');
    }

    /**
     * 2) Draft project: sanctioned == 0, opening_balance == forwarded + local
     */
    public function test_draft_project_sanctioned_zero_opening_balance_equals_forwarded_plus_local(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'executor']);
        $forwarded = 600.00;
        $local = 400.00;
        $budget = $forwarded + $local;

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::DRAFT,
            'goal' => 'Test goal',
            'overall_project_budget' => $budget,
            'amount_forwarded' => $forwarded,
            'local_contribution' => $local,
            'amount_sanctioned' => null,
            'opening_balance' => null,
        ]);

        $resolved = $this->resolver->resolve($project);

        $this->assertEqualsWithDelta(0, $resolved['amount_sanctioned'], 0.01, 'Draft with no pending request: sanctioned == 0');
        $expectedOpening = $forwarded + $local;
        $this->assertEqualsWithDelta($expectedOpening, $resolved['opening_balance'], 0.01, 'opening_balance == forwarded + local');
    }

    /**
     * 3) Reverted project: sanctioned == 0, opening_balance == forwarded + local
     */
    public function test_reverted_project_sanctioned_zero_opening_balance_equals_forwarded_plus_local(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'executor']);
        $forwarded = 800.00;
        $local = 200.00;
        $budget = $forwarded + $local;

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::REVERTED_BY_COORDINATOR,
            'goal' => 'Test goal',
            'overall_project_budget' => $budget,
            'amount_forwarded' => $forwarded,
            'local_contribution' => $local,
            'amount_sanctioned' => null,
            'opening_balance' => null,
        ]);

        $resolved = $this->resolver->resolve($project);

        $this->assertEqualsWithDelta(0, $resolved['amount_sanctioned'], 0.01, 'Reverted with no pending request: sanctioned == 0');
        $expectedOpening = $forwarded + $local;
        $this->assertEqualsWithDelta($expectedOpening, $resolved['opening_balance'], 0.01, 'opening_balance == forwarded + local');
    }

    /**
     * 4) Pending request (M3.7): amount_requested = budget - (forwarded + local); amount_sanctioned = 0; opening = forwarded + local
     */
    public function test_pending_request_calculation(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'executor']);
        $budget = 10000.00;
        $forwarded = 1000.00;
        $local = 500.00;
        $expectedRequested = $budget - ($forwarded + $local);

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::DRAFT,
            'goal' => 'Test goal',
            'overall_project_budget' => $budget,
            'amount_forwarded' => $forwarded,
            'local_contribution' => $local,
            'amount_sanctioned' => null,
            'opening_balance' => null,
        ]);

        $resolved = $this->resolver->resolve($project);

        $this->assertEqualsWithDelta(0, $resolved['amount_sanctioned'], 0.01, 'Draft: amount_sanctioned == 0');
        $this->assertEqualsWithDelta(
            $expectedRequested,
            $resolved['amount_requested'] ?? 0,
            0.01,
            'Amount requested = budget - (forwarded + local)'
        );
        $this->assertEqualsWithDelta(
            $forwarded + $local,
            $resolved['opening_balance'],
            0.01,
            'Draft: opening_balance = forwarded + local'
        );
    }

    /**
     * Approved project with budget rows: opening_balance == overall_project_budget
     */
    public function test_approved_project_with_budget_rows_opening_balance_equals_overall_budget(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'executor']);
        $forwarded = 1000.00;
        $local = 500.00;
        $sanctioned = 8500.00;
        $phase1Total = 10000.00;

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::APPROVED_BY_COORDINATOR,
            'goal' => 'Test goal',
            'amount_forwarded' => $forwarded,
            'local_contribution' => $local,
            'amount_sanctioned' => $sanctioned,
            'opening_balance' => $phase1Total,
        ]);

        ProjectBudget::create([
            'project_id' => $project->project_id,
            'phase' => 1,
            'particular' => 'Item 1',
            'rate_quantity' => 1,
            'rate_multiplier' => 1,
            'rate_duration' => 1,
            'rate_increase' => 0,
            'this_phase' => 6000,
            'next_phase' => null,
        ]);
        ProjectBudget::create([
            'project_id' => $project->project_id,
            'phase' => 1,
            'particular' => 'Item 2',
            'rate_quantity' => 1,
            'rate_multiplier' => 1,
            'rate_duration' => 1,
            'rate_increase' => 0,
            'this_phase' => 4000,
            'next_phase' => null,
        ]);

        $resolved = $this->resolver->resolve($project);

        $this->assertGreaterThan(0, $resolved['amount_sanctioned']);
        $this->assertEqualsWithDelta($phase1Total, $resolved['overall_project_budget'], 0.01);
        $this->assertEqualsWithDelta($phase1Total, $resolved['opening_balance'], 0.01, 'opening_balance == overall_project_budget for approved');
    }
}
