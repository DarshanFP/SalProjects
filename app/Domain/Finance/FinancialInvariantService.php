<?php

namespace App\Domain\Finance;

use App\Models\OldProjects\Project;

class FinancialInvariantService
{
    /**
     * Validate financial invariants for approval.
     * Throws DomainException if invariants are violated.
     *
     * @param Project $project
     * @param array $data Approval data with opening_balance, amount_sanctioned
     * @return void
     * @throws \DomainException
     */
    public static function validateForApproval(Project $project, array $data): void
    {
        $openingBalance = $data['opening_balance'] ?? $project->opening_balance;
        $amountSanctioned = $data['amount_sanctioned'] ?? $project->amount_sanctioned;

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

        if ($openingBalance !== $amountSanctioned) {
            throw new \DomainException(
                'Approval blocked: Opening balance and sanctioned amount mismatch.'
            );
        }
    }
}
