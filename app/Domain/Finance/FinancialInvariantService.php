<?php

namespace App\Domain\Finance;

use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;

/**
 * Phase 1: Canonical invariant rule
 * opening_balance = amount_sanctioned + amount_forwarded + local_contribution (tolerance 0.01)
 *
 * @see Documentations/V2/Budgets/Dashboards/Executor/Financial_Data_Stabilization_Implementation_Plan.md
 */
class FinancialInvariantService
{
    private const TOLERANCE = 0.01;

    /**
     * Validate financial invariants for approval.
     * Throws DomainException if invariants are violated.
     *
     * Canonical rule: opening_balance = amount_sanctioned + amount_forwarded + local_contribution
     *
     * @param Project $project
     * @param array $data Approval data with opening_balance, amount_sanctioned, optionally amount_forwarded, local_contribution
     * @return void
     * @throws \DomainException
     */
    public static function validateForApproval(Project $project, array $data): void
    {
        $openingBalance = (float) ($data['opening_balance'] ?? $project->opening_balance ?? 0);
        $amountSanctioned = (float) ($data['amount_sanctioned'] ?? $project->amount_sanctioned ?? 0);
        $amountForwarded = (float) ($data['amount_forwarded'] ?? $project->amount_forwarded ?? 0);
        $localContribution = (float) ($data['local_contribution'] ?? $project->local_contribution ?? 0);

        if ($openingBalance <= 0) {
            throw new \DomainException(
                'Approval blocked: Opening balance must be greater than zero.'
            );
        }

        if ($amountSanctioned <= 0) {
            throw new \DomainException(
                'Approval blocked: Amount sanctioned must be greater than zero.'
            );
        }

        $expectedOpeningBalance = $amountSanctioned + $amountForwarded + $localContribution;

        if (abs($openingBalance - $expectedOpeningBalance) > self::TOLERANCE) {
            Log::warning('Financial invariant violation: approval blocked (Phase 1 canonical rule)', [
                'project_id' => $project->project_id ?? 'unknown',
                'opening_balance' => $openingBalance,
                'expected_opening_balance' => $expectedOpeningBalance,
                'amount_sanctioned' => $amountSanctioned,
                'amount_forwarded' => $amountForwarded,
                'local_contribution' => $localContribution,
            ]);
            throw new \DomainException(
                'Approval blocked: Opening balance must equal amount sanctioned + amount forwarded + local contribution. ' .
                'Expected ' . number_format($expectedOpeningBalance, 2) . ', got ' . number_format($openingBalance, 2) . '.'
            );
        }
    }
}
