<?php

namespace Tests\Feature\Budget;

use App\Constants\ProjectStatus;
use App\Constants\ProjectType;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DevelopmentBudgetPhaseFreezeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_editing_development_project_budget_preserves_phase_next_phase_row_count_and_total(): void
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
            'phase' => 2,
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
            'phase' => 2,
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
            'phase' => 2,
            'particular' => 'Item 3',
            'rate_quantity' => 100,
            'rate_multiplier' => 3,
            'rate_duration' => 10,
            'rate_increase' => 0,
            'this_phase' => 3000,
            'next_phase' => null,
        ]);

        $budgets = ProjectBudget::where('project_id', $project->project_id)->get();
        $originalRowCount = $budgets->count();
        $originalPhase = 2;
        $originalNextPhaseValues = $budgets->pluck('next_phase')->toArray();
        $originalTotalThisPhase = $budgets->sum(fn ($b) => (float) ($b->this_phase ?? 0));

        $this->assertEquals(3, $originalRowCount, 'Expected 3 budget rows before update');
        $this->assertEquals(6000.0, $originalTotalThisPhase, 'Expected total this_phase = 6000 before update');

        $payload = [
            '_token' => csrf_token(),
            '_method' => 'PUT',
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 2,
            'overall_project_budget' => 6000,
            'objectives' => [],
            'phases' => [
                0 => [
                    'budget' => [
                        0 => [
                            'particular' => 'Item 1',
                            'rate_quantity' => 100,
                            'rate_multiplier' => 1,
                            'rate_duration' => 10,
                            'this_phase' => 1000,
                        ],
                        1 => [
                            'particular' => 'Item 2',
                            'rate_quantity' => 100,
                            'rate_multiplier' => 2,
                            'rate_duration' => 10,
                            'this_phase' => 2000,
                        ],
                        2 => [
                            'particular' => 'Item 3',
                            'rate_quantity' => 100,
                            'rate_multiplier' => 3,
                            'rate_duration' => 10,
                            'this_phase' => 3000,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)
            ->put(route('projects.update', $project->project_id), $payload);

        $response->assertStatus(302, 'Update request must succeed (redirect)');

        $budgetsAfter = ProjectBudget::where('project_id', $project->project_id)->get();

        $this->assertCount(
            $originalRowCount,
            $budgetsAfter,
            'Row count must remain equal to original after update'
        );

        foreach ($budgetsAfter as $budget) {
            $this->assertEquals(
                $originalPhase,
                $budget->phase,
                "All rows must have phase == {$originalPhase} (project current_phase)"
            );
        }

        $rowsWithPhase1 = ProjectBudget::where('project_id', $project->project_id)
            ->where('phase', 1)
            ->count();
        $this->assertEquals(0, $rowsWithPhase1, 'No row must have phase = 1');

        $nextPhaseValuesAfter = $budgetsAfter->pluck('next_phase')->toArray();
        $this->assertEquals(
            $originalNextPhaseValues,
            $nextPhaseValuesAfter,
            'next_phase values must remain unchanged (all null)'
        );

        $totalAfter = $budgetsAfter->sum(fn ($b) => (float) ($b->this_phase ?? 0));
        $this->assertEquals(
            $originalTotalThisPhase,
            $totalAfter,
            'Sum of this_phase must equal original total'
        );

        $rowsWithNextPhaseZero = ProjectBudget::where('project_id', $project->project_id)
            ->where('next_phase', 0)
            ->count();
        $this->assertEquals(
            0,
            $rowsWithNextPhaseZero,
            'No row must have next_phase = 0'
        );
    }
}
