<?php

namespace Tests\Unit;

use App\Domain\Finance\FinancialInvariantService;
use App\Models\OldProjects\Project;
use DomainException;
use Tests\TestCase;

/**
 * Phase 1: Unit tests for FinancialInvariantService canonical rule.
 *
 * Canonical rule: opening_balance = amount_sanctioned + amount_forwarded + local_contribution (tolerance 0.01)
 *
 * @see Documentations/V2/Budgets/Dashboards/Executor/Financial_Data_Stabilization_Implementation_Plan.md
 */
class FinancialInvariantServiceTest extends TestCase
{
    private function createProject(array $attrs = []): Project
    {
        $project = new Project();
        foreach ($attrs as $key => $value) {
            $project->$key = $value;
        }
        $project->project_id = $attrs['project_id'] ?? 'TEST-' . uniqid();

        return $project;
    }

    /** CASE 1: sanctioned=100000, forwarded=0, local=0, opening=100000 → PASS */
    public function test_passes_when_opening_equals_sanctioned_only(): void
    {
        $project = $this->createProject([
            'amount_sanctioned' => 100000,
            'amount_forwarded' => 0,
            'local_contribution' => 0,
            'opening_balance' => 100000,
        ]);
        $data = [
            'amount_sanctioned' => 100000,
            'opening_balance' => 100000,
        ];

        FinancialInvariantService::validateForApproval($project, $data);
        $this->assertTrue(true, 'Expected no exception');
    }

    /** CASE 2: sanctioned=100000, forwarded=100000, local=0, opening=200000 → PASS */
    public function test_passes_when_opening_equals_sanctioned_plus_forwarded(): void
    {
        $project = $this->createProject([
            'amount_sanctioned' => 100000,
            'amount_forwarded' => 100000,
            'local_contribution' => 0,
            'opening_balance' => 200000,
        ]);
        $data = [
            'amount_sanctioned' => 100000,
            'opening_balance' => 200000,
            'amount_forwarded' => 100000,
            'local_contribution' => 0,
        ];

        FinancialInvariantService::validateForApproval($project, $data);
        $this->assertTrue(true, 'Expected no exception');
    }

    /** CASE 3: sanctioned=100000, forwarded=100000, local=0, opening=100000 → FAIL */
    public function test_fails_when_opening_mismatches_canonical_formula(): void
    {
        $project = $this->createProject([
            'amount_sanctioned' => 100000,
            'amount_forwarded' => 100000,
            'local_contribution' => 0,
            'opening_balance' => 100000,
        ]);
        $data = [
            'amount_sanctioned' => 100000,
            'opening_balance' => 100000,
            'amount_forwarded' => 100000,
            'local_contribution' => 0,
        ];

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Approval blocked');

        FinancialInvariantService::validateForApproval($project, $data);
    }

    /** CASE 4: sanctioned=100000, forwarded=50000, local=25000, opening=175000 → PASS */
    public function test_passes_when_opening_equals_sanctioned_plus_forwarded_plus_local(): void
    {
        $project = $this->createProject([
            'amount_sanctioned' => 100000,
            'amount_forwarded' => 50000,
            'local_contribution' => 25000,
            'opening_balance' => 175000,
        ]);
        $data = [
            'amount_sanctioned' => 100000,
            'opening_balance' => 175000,
            'amount_forwarded' => 50000,
            'local_contribution' => 25000,
        ];

        FinancialInvariantService::validateForApproval($project, $data);
        $this->assertTrue(true, 'Expected no exception');
    }

    public function test_fails_when_opening_balance_zero(): void
    {
        $project = $this->createProject(['opening_balance' => 0]);
        $data = ['amount_sanctioned' => 100000, 'opening_balance' => 0];

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Opening balance must be greater than zero');

        FinancialInvariantService::validateForApproval($project, $data);
    }

    public function test_fails_when_amount_sanctioned_zero(): void
    {
        $project = $this->createProject(['amount_sanctioned' => 0]);
        $data = ['amount_sanctioned' => 0, 'opening_balance' => 100000];

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Amount sanctioned must be greater than zero');

        FinancialInvariantService::validateForApproval($project, $data);
    }

    public function test_uses_project_values_when_data_omits_forwarded_local(): void
    {
        $project = $this->createProject([
            'amount_sanctioned' => 100000,
            'amount_forwarded' => 50000,
            'local_contribution' => 25000,
        ]);
        $data = [
            'amount_sanctioned' => 100000,
            'opening_balance' => 175000,
        ];

        FinancialInvariantService::validateForApproval($project, $data);
        $this->assertTrue(true, 'Should use project amount_forwarded and local_contribution');
    }

    public function test_passes_within_tolerance(): void
    {
        $project = $this->createProject([
            'amount_sanctioned' => 100000,
            'amount_forwarded' => 0,
            'local_contribution' => 0,
        ]);
        $data = [
            'amount_sanctioned' => 100000,
            'opening_balance' => 100000.005,
        ];

        FinancialInvariantService::validateForApproval($project, $data);
        $this->assertTrue(true, 'Expected pass within 0.01 tolerance');
    }
}
