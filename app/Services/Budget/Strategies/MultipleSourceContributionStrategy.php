<?php

namespace App\Services\Budget\Strategies;

use App\Models\OldProjects\Project;
use App\Services\Budget\BudgetCalculationService;
use Illuminate\Support\Collection;

/**
 * Multiple Source Contribution Strategy
 *
 * Handles project types with multiple contribution sources combined and distributed:
 * - Individual - Initial - Educational support (IIES)
 * - Individual - Ongoing Educational support (IES)
 *
 * Calculation:
 * - total_contribution = source1 + source2 + source3
 * - amount_sanctioned = max(0, original_amount - (total_contribution / total_rows))
 */
class MultipleSourceContributionStrategy extends BaseBudgetStrategy
{
    /**
     * Get budgets for the project
     *
     * @param Project $project The project to get budgets for
     * @param bool $calculateContributions Whether to calculate contributions
     * @return Collection Collection of expense detail objects with calculated amount_sanctioned
     */
    public function getBudgets(Project $project, bool $calculateContributions = true): Collection
    {
        $parentModelClass = $this->getConfig('parent_model');
        $childRelationship = $this->getConfig('child_relationship');

        // Get parent expense record
        $parentExpense = $parentModelClass::where('project_id', $project->project_id)->first();

        if (!$parentExpense) {
            return collect();
        }

        // Get child expense details
        $expenseDetails = $parentExpense->{$childRelationship};

        if ($expenseDetails->isEmpty()) {
            return collect();
        }

        // If not calculating contributions (export), return as-is
        if (!$calculateContributions) {
            return $expenseDetails;
        }

        // Get contribution source field names from configuration
        $contributionSources = $this->getConfig('contribution_sources', []);
        $amountField = $this->getFieldMapping('amount');
        $particularField = $this->getFieldMapping('particular');
        $idField = $this->getFieldMapping('id');

        // Calculate total contribution from all sources
        $sourceValues = [];
        foreach ($contributionSources as $sourceField) {
            $sourceValues[$sourceField] = (float)($parentExpense->{$sourceField} ?? 0);
        }

        $totalContribution = BudgetCalculationService::calculateTotalContribution($sourceValues);
        $totalRows = $expenseDetails->count();
        $contributionPerRow = BudgetCalculationService::calculateContributionPerRow($totalContribution, $totalRows);

        // Log calculation
        $logData = array_merge([
            'total_rows' => $totalRows,
            'total_contribution' => $totalContribution,
            'contribution_per_row' => $contributionPerRow
        ], $sourceValues);

        BudgetCalculationService::logCalculation($this->projectType, $logData);

        // Map expense details with calculated amount_sanctioned
        return $expenseDetails->map(function($detail) use ($contributionPerRow, $amountField, $particularField, $idField) {
            $originalAmount = (float)($detail->{$amountField} ?? 0);
            $finalAmount = BudgetCalculationService::calculateAmountSanctioned($originalAmount, $contributionPerRow);

            // Log row calculation
            BudgetCalculationService::logRowCalculation($this->projectType, [
                $particularField => $detail->{$particularField},
                'original_amount' => $originalAmount,
                'contribution_subtracted' => $contributionPerRow,
                'final_amount' => $finalAmount
            ]);

            // Create expense detail object with all original fields + calculated amount_sanctioned
            $detailArray = $detail->toArray();
            $detailArray['amount_sanctioned'] = $finalAmount;
            $detailArray['original_amount'] = $originalAmount;
            $detailArray['contribution_per_row'] = $contributionPerRow;

            return (object) $detailArray;
        });
    }
}
