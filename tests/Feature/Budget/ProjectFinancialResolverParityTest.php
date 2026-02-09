<?php

namespace Tests\Feature\Budget;

use App\Constants\ProjectStatus;
use App\Constants\ProjectType;
use App\Domain\Budget\ProjectFinancialResolver;
use App\Models\OldProjects\IIES\ProjectIIESExpenses;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use App\Services\Budget\ProjectFundFieldsResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Project Financial Resolver Parity Test
 *
 * Validates that ProjectFinancialResolver produces identical output to
 * ProjectFundFieldsResolver for all supported scenarios. Read-only verification.
 *
 * @see Documentations/V2/Budgets/Overview/FINANCIAL_ENGINE_CONSOLIDATION_BLUEPRINT.md
 * @see Documentations/V2/Budgets/Overview/RESOLVER_IMPLEMENTATION_TODO.md
 */
class ProjectFinancialResolverParityTest extends TestCase
{
    use DatabaseTransactions;

    protected ProjectFundFieldsResolver $oldResolver;

    protected ProjectFinancialResolver $newResolver;

    /** @var array<string> */
    private const FINANCIAL_KEYS = [
        'overall_project_budget',
        'amount_forwarded',
        'local_contribution',
        'amount_sanctioned',
        'opening_balance',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->oldResolver = app(ProjectFundFieldsResolver::class);
        $this->newResolver = app(ProjectFinancialResolver::class);
    }

    /**
     * Assert parity of the 5 financial fields between old and new resolver outputs.
     */
    private function assertFinancialParity(array $old, array $new, string $message = ''): void
    {
        foreach (self::FINANCIAL_KEYS as $key) {
            $this->assertEquals(
                round($old[$key] ?? 0, 2),
                round($new[$key] ?? 0, 2),
                $message ? "{$message} — {$key}" : $key
            );
        }
    }

    /**
     * Scenario 1: Phase-based Development project, NOT approved.
     * Budget rows exist for phase 1. Sanctioned and opening are derived.
     */
    public function test_phase_based_project_not_approved_parity(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'executor']);

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::DRAFT,
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

        $old = $this->oldResolver->resolve($project, true);
        $new = $this->newResolver->resolve($project);

        $this->assertFinancialParity($old, $new, 'Phase-based not approved');
    }

    /**
     * Scenario 2: Phase-based Development project, APPROVED.
     * Resolver uses DB amount_sanctioned and opening_balance.
     */
    public function test_phase_based_project_approved_parity(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'executor']);

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::APPROVED_BY_COORDINATOR,
            'goal' => 'Test goal',
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

        $old = $this->oldResolver->resolve($project, true);
        $new = $this->newResolver->resolve($project);

        $this->assertFinancialParity($old, $new, 'Phase-based approved');
        $this->assertEquals(5000.25, round($new['amount_sanctioned'], 2));
        $this->assertEquals(6500.25, round($new['opening_balance'], 2));
    }

    /**
     * Scenario 3: Individual type IIES, not approved.
     * Uses ProjectIIESExpenses for fund fields.
     */
    public function test_individual_type_iies_not_approved_parity(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'executor']);

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::INDIVIDUAL_INITIAL_EDUCATIONAL,
            'current_phase' => 1,
            'status' => ProjectStatus::DRAFT,
            'goal' => 'Test goal',
        ]);

        ProjectIIESExpenses::create([
            'project_id' => $project->project_id,
            'iies_total_expenses' => 50000,
            'iies_expected_scholarship_govt' => 10000,
            'iies_support_other_sources' => 5000,
            'iies_beneficiary_contribution' => 5000,
            'iies_balance_requested' => 30000,
        ]);

        $old = $this->oldResolver->resolve($project, true);
        $new = $this->newResolver->resolve($project);

        $this->assertFinancialParity($old, $new, 'IIES not approved');
    }

    /**
     * Scenario 4: Individual type IIES, approved.
     * Type-specific data still used; approval does not change individual type resolution.
     */
    public function test_individual_type_iies_approved_parity(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'executor']);

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::INDIVIDUAL_INITIAL_EDUCATIONAL,
            'current_phase' => 1,
            'status' => ProjectStatus::APPROVED_BY_COORDINATOR,
            'goal' => 'Test goal',
            'amount_sanctioned' => 30000,
            'opening_balance' => 50000,
        ]);

        ProjectIIESExpenses::create([
            'project_id' => $project->project_id,
            'iies_total_expenses' => 50000,
            'iies_expected_scholarship_govt' => 10000,
            'iies_support_other_sources' => 5000,
            'iies_beneficiary_contribution' => 5000,
            'iies_balance_requested' => 30000,
        ]);

        $old = $this->oldResolver->resolve($project, true);
        $new = $this->newResolver->resolve($project);

        $this->assertFinancialParity($old, $new, 'IIES approved');
    }

    /**
     * Scenario 5: Edge case — Development project with manual overall_project_budget,
     * no budget rows. Fallback to DB value.
     */
    public function test_edge_case_manual_overall_budget_no_budgets_parity(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'executor']);

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'in_charge' => $user->id,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'current_phase' => 1,
            'status' => ProjectStatus::DRAFT,
            'goal' => 'Test goal',
            'overall_project_budget' => 7500.50,
            'amount_forwarded' => 500,
            'local_contribution' => 250,
        ]);

        $old = $this->oldResolver->resolve($project, true);
        $new = $this->newResolver->resolve($project);

        $this->assertFinancialParity($old, $new, 'Manual overall, no budgets');
        $this->assertEquals(7500.50, round($new['overall_project_budget'], 2));
    }

    /**
     * Scenario 6: Phase-based project with current_phase = 2.
     * Resolver filters budgets by current phase only.
     */
    public function test_phase_based_project_phase_filter_parity(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'executor']);

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

        $old = $this->oldResolver->resolve($project, true);
        $new = $this->newResolver->resolve($project);

        $this->assertFinancialParity($old, $new, 'Phase filter current_phase=2');
        $this->assertEquals(3000.0, round($new['overall_project_budget'], 2));
    }
}
