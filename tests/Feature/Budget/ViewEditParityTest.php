<?php

namespace Tests\Feature\Budget;

use App\Constants\ProjectStatus;
use App\Constants\ProjectType;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use App\Models\User;
use App\Services\Budget\ProjectFundFieldsResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * View–Edit Parity Test
 *
 * Ensures ProjectFundFieldsResolver derives Overall Project Budget from budget rows
 * (current_phase only) when available, matching Edit page JS behavior.
 * DB-stored overall_project_budget is ignored when budget rows exist.
 *
 * @see Documentations/V2/Budgets/BasicInformation_Resolver_Unification.md
 */
class ViewEditParityTest extends TestCase
{
    use DatabaseTransactions;

    protected ProjectFundFieldsResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(ProjectFundFieldsResolver::class);
    }

    public function test_resolver_returns_sum_of_this_phase_when_budget_rows_exist_ignoring_db_overall(): void
    {
        $user = User::factory()->create(['role' => 'executor']);

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::DRAFT,
            'goal' => 'Test goal',
            'overall_project_budget' => 99999.99, // Incorrect; should be ignored
        ]);

        ProjectBudget::create([
            'project_id' => $project->project_id,
            'phase' => 1,
            'particular' => 'Item 1',
            'rate_quantity' => 100,
            'rate_multiplier' => 1,
            'rate_duration' => 10,
            'rate_increase' => 0,
            'this_phase' => 1000,
            'next_phase' => null,
        ]);
        ProjectBudget::create([
            'project_id' => $project->project_id,
            'phase' => 1,
            'particular' => 'Item 2',
            'rate_quantity' => 100,
            'rate_multiplier' => 2,
            'rate_duration' => 10,
            'rate_increase' => 0,
            'this_phase' => 2000,
            'next_phase' => null,
        ]);
        ProjectBudget::create([
            'project_id' => $project->project_id,
            'phase' => 1,
            'particular' => 'Item 3',
            'rate_quantity' => 100,
            'rate_multiplier' => 3,
            'rate_duration' => 10,
            'rate_increase' => 0,
            'this_phase' => 3000,
            'next_phase' => null,
        ]);

        $resolved = $this->resolver->resolve($project, true);

        $expectedOverall = 6000.0; // 1000 + 2000 + 3000
        $this->assertEquals($expectedOverall, $resolved['overall_project_budget'], 'Resolver must return sum(this_phase), not DB value');
        $this->assertNotEquals(99999.99, $resolved['overall_project_budget'], 'DB overall_project_budget must be ignored when budget rows exist');
    }

    public function test_no_budgets_exist_fallback_to_db_value(): void
    {
        $user = User::factory()->create(['role' => 'executor']);

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::DRAFT,
            'goal' => 'Test goal',
            'overall_project_budget' => 7500.50,
        ]);

        $resolved = $this->resolver->resolve($project, true);

        $this->assertEquals(7500.50, $resolved['overall_project_budget'], 'Resolver must fallback to DB when no budget rows exist');
    }

    public function test_approved_project_sanctioned_values_untouched(): void
    {
        $user = User::factory()->create(['role' => 'executor']);

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::APPROVED_BY_COORDINATOR,
            'goal' => 'Test goal',
            'overall_project_budget' => 10000,
            'amount_forwarded' => 1000,
            'local_contribution' => 500,
            'amount_sanctioned' => 5000.25,
            'opening_balance' => 6500.25,
        ]);

        ProjectBudget::create([
            'project_id' => $project->project_id,
            'phase' => 1,
            'particular' => 'Item 1',
            'rate_quantity' => 1,
            'rate_multiplier' => 1,
            'rate_duration' => 1,
            'rate_increase' => 0,
            'this_phase' => 12000,
            'next_phase' => null,
        ]);

        $resolved = $this->resolver->resolve($project, true);

        $this->assertEquals(5000.25, $resolved['amount_sanctioned'], 'Approved project: amount_sanctioned must come from DB');
        $this->assertEquals(6500.25, $resolved['opening_balance'], 'Approved project: opening_balance must come from DB');
    }

    public function test_resolver_filters_by_current_phase_only(): void
    {
        $user = User::factory()->create(['role' => 'executor']);

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 2,
            'status' => ProjectStatus::DRAFT,
            'goal' => 'Test goal',
        ]);

        ProjectBudget::create([
            'project_id' => $project->project_id,
            'phase' => 1,
            'particular' => 'Phase 1 Item',
            'rate_quantity' => 1,
            'rate_multiplier' => 1,
            'rate_duration' => 1,
            'rate_increase' => 0,
            'this_phase' => 5000,
            'next_phase' => null,
        ]);
        ProjectBudget::create([
            'project_id' => $project->project_id,
            'phase' => 2,
            'particular' => 'Phase 2 Item',
            'rate_quantity' => 1,
            'rate_multiplier' => 1,
            'rate_duration' => 1,
            'rate_increase' => 0,
            'this_phase' => 3000,
            'next_phase' => null,
        ]);

        $resolved = $this->resolver->resolve($project, true);

        $this->assertEquals(3000.0, $resolved['overall_project_budget'], 'Resolver must sum only current_phase (2), not phase 1');
    }
}
