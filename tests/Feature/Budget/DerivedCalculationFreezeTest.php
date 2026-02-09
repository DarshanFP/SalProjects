<?php

namespace Tests\Feature\Budget;

use App\Constants\ProjectStatus;
use App\Constants\ProjectType;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DerivedCalculationFreezeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_row_calculation_freeze_calculate_total_budget_equals_rate_quantity_times_rate_multiplier_times_rate_duration(): void
    {
        $user = User::factory()->create(['role' => 'executor']);

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::DRAFT,
            'goal' => 'Test goal',
        ]);

        $budget = ProjectBudget::create([
            'project_id' => $project->project_id,
            'phase' => 1,
            'particular' => 'Item 1',
            'rate_quantity' => 10,
            'rate_multiplier' => 2.5,
            'rate_duration' => 4,
            'rate_increase' => 0,
            'this_phase' => 100,
            'next_phase' => null,
        ]);

        $expected = (float) $budget->rate_quantity * (float) $budget->rate_multiplier * (float) $budget->rate_duration;
        $this->assertEquals($expected, $budget->calculateTotalBudget(), 'calculateTotalBudget must equal rate_quantity × rate_multiplier × rate_duration');
    }

    public function test_phase_total_freeze_sum_this_phase_equals_expected_and_export_sum_matches(): void
    {
        $user = User::factory()->create(['role' => 'executor']);

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::DRAFT,
            'goal' => 'Test goal',
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

        $budgets = ProjectBudget::where('project_id', $project->project_id)->where('phase', 1)->get();
        $this->assertCount(3, $budgets);

        $expectedTotal = 6000.0;
        $sumThisPhase = $budgets->sum(fn ($b) => (float) ($b->this_phase ?? 0));
        $this->assertEquals($expectedTotal, $sumThisPhase, 'Sum of this_phase must equal 6000');

        $project->load('budgets');
        $exportSum = $project->budgets->where('phase', 1)->sum('this_phase');
        $this->assertEquals($expectedTotal, $exportSum, 'Export/report sum must match sum(this_phase)');
    }

    public function test_controller_trust_freeze_backend_does_not_alter_submitted_this_phase(): void
    {
        $user = User::factory()->create(['role' => 'executor']);

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::DRAFT,
            'goal' => 'Test goal',
        ]);

        ProjectBudget::create([
            'project_id' => $project->project_id,
            'phase' => 1,
            'particular' => 'Item 1',
            'rate_quantity' => 50,
            'rate_multiplier' => 1,
            'rate_duration' => 10,
            'rate_increase' => 0,
            'this_phase' => 500,
            'next_phase' => null,
        ]);

        $submittedThisPhase = 1234.56;
        $payload = [
            '_token' => csrf_token(),
            '_method' => 'PUT',
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'overall_project_budget' => 1234.56,
            'objectives' => [],
            'phases' => [
                0 => [
                    'budget' => [
                        0 => [
                            'particular' => 'Item 1',
                            'rate_quantity' => 50,
                            'rate_multiplier' => 1,
                            'rate_duration' => 10,
                            'this_phase' => $submittedThisPhase,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)
            ->put(route('projects.update', $project->project_id), $payload);

        $response->assertStatus(302);

        $this->assertDatabaseHas('project_budgets', [
            'project_id' => $project->project_id,
            'phase' => 1,
            'this_phase' => $submittedThisPhase,
        ]);
    }

    public function test_bounds_freeze_very_large_values_clamped_to_phase_2_3_max(): void
    {
        $user = User::factory()->create(['role' => 'executor']);

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::DRAFT,
            'goal' => 'Test goal',
        ]);

        $payload = [
            '_token' => csrf_token(),
            '_method' => 'PUT',
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'overall_project_budget' => 99999999.99,
            'objectives' => [],
            'phases' => [
                0 => [
                    'budget' => [
                        0 => [
                            'particular' => 'Item 1',
                            'rate_quantity' => 1,
                            'rate_multiplier' => 1,
                            'rate_duration' => 1,
                            'this_phase' => 100000000,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)
            ->put(route('projects.update', $project->project_id), $payload);

        $response->assertSessionHasErrors('phases.0.budget.0.this_phase', 'Validation must reject this_phase above max bound');

        $this->assertDatabaseMissing('project_budgets', [
            'project_id' => $project->project_id,
            'this_phase' => 100000000,
        ]);
    }

    public function test_bounds_freeze_value_at_max_persists_correctly(): void
    {
        $user = User::factory()->create(['role' => 'executor']);

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::DRAFT,
            'goal' => 'Test goal',
        ]);

        $maxBound = 99999999.99;
        $payload = [
            '_token' => csrf_token(),
            '_method' => 'PUT',
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'overall_project_budget' => $maxBound,
            'objectives' => [],
            'phases' => [
                0 => [
                    'budget' => [
                        0 => [
                            'particular' => 'Item 1',
                            'rate_quantity' => 1,
                            'rate_multiplier' => 1,
                            'rate_duration' => 1,
                            'this_phase' => $maxBound,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)
            ->put(route('projects.update', $project->project_id), $payload);

        $response->assertStatus(302);

        $this->assertDatabaseHas('project_budgets', [
            'project_id' => $project->project_id,
            'phase' => 1,
            'this_phase' => $maxBound,
        ]);
    }
}
