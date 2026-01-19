<?php

namespace App\Services\Budget\Strategies;

use App\Models\OldProjects\Project;
use App\Services\Budget\BudgetCalculationService;
use Illuminate\Support\Collection;

/**
 * Single Source Contribution Strategy
 *
 * Handles project types with single contribution source distributed across rows:
 * - Individual - Livelihood Application (ILP)
 * - Individual - Access to Health (IAH)
 *
 * Calculation: amount_sanctioned = max(0, original_amount - (contribution / total_rows))
 */
class SingleSourceContributionStrategy extends BaseBudgetStrategy
{
    /**
     * Get budgets for the project
     *
     * @param Project $project The project to get budgets for
     * @param bool $calculateContributions Whether to calculate contributions
     * @return Collection Collection of budget objects with calculated amount_sanctioned
     */
    public function getBudgets(Project $project, bool $calculateContributions = true): Collection
    {
        $modelClass = $this->getConfig('model');
        $budgets = $modelClass::where('project_id', $project->project_id)->get();

        if ($budgets->isEmpty()) {
            return collect();
        }

        // If not calculating contributions (export), return as-is
        if (!$calculateContributions) {
            return $budgets;
        }

        // Get field mappings from configuration
        $contributionField = $this->getFieldMapping('contribution');
        $amountField = $this->getFieldMapping('amount');
        $particularField = $this->getFieldMapping('particular');
        $idField = $this->getFieldMapping('id');

        // Get contribution from first row (same for all rows)
        $contribution = (float)($budgets->first()->{$contributionField} ?? 0);
        $totalRows = $budgets->count();
        $contributionPerRow = BudgetCalculationService::calculateContributionPerRow($contribution, $totalRows);

        // Log calculation
        BudgetCalculationService::logCalculation($this->projectType, [
            'total_rows' => $totalRows,
            $contributionField => $contribution,
            'contribution_per_row' => $contributionPerRow
        ]);

        // Map budgets with calculated amount_sanctioned
        return $budgets->map(function($budget) use ($contributionPerRow, $amountField, $particularField, $idField) {
            $originalAmount = (float)($budget->{$amountField} ?? 0);
            $finalAmount = BudgetCalculationService::calculateAmountSanctioned($originalAmount, $contributionPerRow);

            // Log row calculation
            BudgetCalculationService::logRowCalculation($this->projectType, [
                $particularField => $budget->{$particularField},
                'original_amount' => $originalAmount,
                'contribution_subtracted' => $contributionPerRow,
                'final_amount' => $finalAmount
            ]);

            // Create budget object with all original fields + calculated amount_sanctioned
            $budgetArray = $budget->toArray();
            $budgetArray['amount_sanctioned'] = $finalAmount;

            return (object) $budgetArray;
        });
    }
}
