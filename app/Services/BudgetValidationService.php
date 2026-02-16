<?php

namespace App\Services;

use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;

class BudgetValidationService
{
    /**
     * Validate budget for a project and return validation results
     *
     * @param Project $project
     * @return array
     */
    public static function validateBudget(Project $project): array
    {
        $warnings = [];
        $errors = [];
        $info = [];

        // Calculate budget values
        $budgetData = self::calculateBudgetData($project);

        // Validation checks
        self::checkNegativeBalances($budgetData, $errors, $warnings);
        self::checkTotalsMatch($budgetData, $errors, $warnings);
        self::checkOverBudget($budgetData, $warnings);
        self::checkLowBalance($budgetData, $warnings);
        self::checkInconsistencies($budgetData, $warnings, $info);

        return [
            'is_valid' => empty($errors),
            'has_warnings' => !empty($warnings),
            'errors' => $errors,
            'warnings' => $warnings,
            'info' => $info,
            'budget_data' => $budgetData,
        ];
    }

    /**
     * Calculate all budget-related data for validation
     *
     * Financial fields delegated to ProjectFinancialResolver.
     * This service now acts as aggregator + validator only.
     *
     * @param Project $project
     * @return array
     */
    private static function calculateBudgetData(Project $project): array
    {
        $resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
        $financials = $resolver->resolve($project);

        $overallBudget = (float) ($financials['overall_project_budget'] ?? 0);
        $amountForwarded = (float) ($financials['amount_forwarded'] ?? 0);
        $localContribution = (float) ($financials['local_contribution'] ?? 0);
        $amountSanctioned = (float) ($financials['amount_sanctioned'] ?? 0);
        $openingBalance = (float) ($financials['opening_balance'] ?? 0);

        // Calculate expenses from reports - SEPARATE APPROVED AND UNAPPROVED
        $approvedExpenses = 0;
        $unapprovedExpenses = 0;
        $totalExpenses = 0;

        try {
            if (!$project->relationLoaded('reports')) {
                $project->load('reports.accountDetails');
            }

            foreach ($project->reports as $report) {
                if (!$report->relationLoaded('accountDetails')) {
                    $report->load('accountDetails');
                }

                $reportExpenses = $report->accountDetails->sum('total_expenses') ?? 0;

                if ($report->status === \App\Models\Reports\Monthly\DPReport::STATUS_APPROVED_BY_COORDINATOR) {
                    $approvedExpenses += $reportExpenses;
                } else {
                    $unapprovedExpenses += $reportExpenses;
                }
            }

            $totalExpenses = $approvedExpenses + $unapprovedExpenses;
        } catch (\Exception $e) {
            Log::warning('Error calculating expenses', [
                'project_id' => $project->project_id,
                'error' => $e->getMessage()
            ]);
        }

        $calc = app(\App\Services\Budget\DerivedCalculationService::class);

        $remainingBalance = $calc->calculateRemainingBalance($openingBalance, $totalExpenses);
        $percentageUsed = $calc->calculateUtilization($totalExpenses, $openingBalance);
        $percentageRemaining = 100 - $percentageUsed;

        $approvedPercentage = $calc->calculateUtilization($approvedExpenses, $openingBalance);
        $unapprovedPercentage = $calc->calculateUtilization($unapprovedExpenses, $openingBalance);

        // Budget items total (for validation mismatch check)
        // Must use same phase filter as PhaseBasedBudgetStrategy so we compare apples to apples.
        // overall_budget comes from resolver (current phase only for phase-based types);
        // budget_items_total must match that scope.
        $budgetItemsTotal = 0;
        if ($project->relationLoaded('budgets') && $project->budgets->isNotEmpty()) {
            $currentPhase = (int) ($project->current_phase ?? 1);
            $phaseBudgets = $project->budgets->where('phase', $currentPhase);
            $budgetItemsTotal = $phaseBudgets->sum('this_phase');
        }

        return [
            'overall_budget' => $overallBudget,
            'amount_forwarded' => $amountForwarded,
            'local_contribution' => $localContribution,
            'amount_sanctioned' => $amountSanctioned,
            'opening_balance' => $openingBalance,
            'total_expenses' => $totalExpenses,
            'approved_expenses' => $approvedExpenses,
            'unapproved_expenses' => $unapprovedExpenses,
            'approved_percentage' => $approvedPercentage,
            'unapproved_percentage' => $unapprovedPercentage,
            'remaining_balance' => $remainingBalance,
            'percentage_used' => $percentageUsed,
            'percentage_remaining' => $percentageRemaining,
            'budget_items_total' => $budgetItemsTotal,
        ];
    }

    /**
     * Check for negative balances
     *
     * @param array $budgetData
     * @param array $errors
     * @param array $warnings
     * @return void
     */
    private static function checkNegativeBalances(array $budgetData, array &$errors, array &$warnings): void
    {
        // Check remaining balance
        if ($budgetData['remaining_balance'] < 0) {
            $errors[] = [
                'type' => 'negative_balance',
                'severity' => 'error',
                'message' => 'Remaining balance is negative. Expenses exceed available budget.',
                'value' => $budgetData['remaining_balance'],
                'suggestion' => 'Review expenses or request additional funding.'
            ];
        }

        // Check amount sanctioned
        if ($budgetData['amount_sanctioned'] < 0) {
            $errors[] = [
                'type' => 'negative_sanctioned',
                'severity' => 'error',
                'message' => 'Amount sanctioned is negative. Amount forwarded and local contribution exceed overall budget.',
                'value' => $budgetData['amount_sanctioned'],
                'suggestion' => 'Review amount forwarded and local contribution values.'
            ];
        }

        // Check opening balance
        if ($budgetData['opening_balance'] < 0) {
            $errors[] = [
                'type' => 'negative_opening',
                'severity' => 'error',
                'message' => 'Opening balance is negative.',
                'value' => $budgetData['opening_balance'],
                'suggestion' => 'Review budget calculations.'
            ];
        }
    }

    /**
     * Check if totals match
     *
     * @param array $budgetData
     * @param array $errors
     * @param array $warnings
     * @return void
     */
    private static function checkTotalsMatch(array $budgetData, array &$errors, array &$warnings): void
    {
        // Check if opening balance matches calculation
        $calculatedOpening = $budgetData['amount_sanctioned'] + $budgetData['amount_forwarded'] + $budgetData['local_contribution'];
        $tolerance = 0.01; // Allow small floating point differences

        if (abs($budgetData['opening_balance'] - $calculatedOpening) > $tolerance) {
            $warnings[] = [
                'type' => 'opening_balance_mismatch',
                'severity' => 'warning',
                'message' => 'Opening balance does not match calculated value.',
                'expected' => $calculatedOpening,
                'actual' => $budgetData['opening_balance'],
                'difference' => abs($budgetData['opening_balance'] - $calculatedOpening),
                'suggestion' => 'Verify budget calculations.'
            ];
        }

        // Check if remaining balance matches calculation
        $calculatedRemaining = $budgetData['opening_balance'] - $budgetData['total_expenses'];
        if (abs($budgetData['remaining_balance'] - $calculatedRemaining) > $tolerance) {
            $warnings[] = [
                'type' => 'remaining_balance_mismatch',
                'severity' => 'warning',
                'message' => 'Remaining balance does not match calculated value.',
                'expected' => $calculatedRemaining,
                'actual' => $budgetData['remaining_balance'],
                'difference' => abs($budgetData['remaining_balance'] - $calculatedRemaining),
                'suggestion' => 'Verify expense calculations.'
            ];
        }

        // Check if budget items total matches overall budget
        if ($budgetData['budget_items_total'] > 0 && abs($budgetData['overall_budget'] - $budgetData['budget_items_total']) > $tolerance) {
            $warnings[] = [
                'type' => 'budget_items_mismatch',
                'severity' => 'warning',
                'message' => 'Overall budget does not match sum of budget items.',
                'expected' => $budgetData['budget_items_total'],
                'actual' => $budgetData['overall_budget'],
                'difference' => abs($budgetData['overall_budget'] - $budgetData['budget_items_total']),
                'suggestion' => 'Review budget items and overall budget value.'
            ];
        }
    }

    /**
     * Check for over-budget situations
     *
     * @param array $budgetData
     * @param array $warnings
     * @return void
     */
    private static function checkOverBudget(array $budgetData, array &$warnings): void
    {
        // Check if expenses exceed opening balance
        if ($budgetData['total_expenses'] > $budgetData['opening_balance']) {
            $overAmount = $budgetData['total_expenses'] - $budgetData['opening_balance'];
            $warnings[] = [
                'type' => 'over_budget',
                'severity' => 'error',
                'message' => 'Total expenses exceed available budget.',
                'over_amount' => $overAmount,
                'percentage_over' => ($overAmount / $budgetData['opening_balance']) * 100,
                'suggestion' => 'Review expenses or request additional funding.'
            ];
        }

        // Check if utilization is very high (>90%)
        if ($budgetData['percentage_used'] > 90) {
            $warnings[] = [
                'type' => 'high_utilization',
                'severity' => 'warning',
                'message' => 'Budget utilization is very high (' . \App\Helpers\NumberFormatHelper::formatPercentage($budgetData['percentage_used'], 1) . ').',
                'percentage' => $budgetData['percentage_used'],
                'remaining_percentage' => $budgetData['percentage_remaining'],
                'suggestion' => 'Monitor expenses closely. Consider requesting additional funding if needed.'
            ];
        }
    }

    /**
     * Check for low balance warnings
     *
     * @param array $budgetData
     * @param array $warnings
     * @return void
     */
    private static function checkLowBalance(array $budgetData, array &$warnings): void
    {
        // Check if remaining balance is low (<10% of opening balance)
        if ($budgetData['opening_balance'] > 0) {
            $remainingPercentage = ($budgetData['remaining_balance'] / $budgetData['opening_balance']) * 100;

            if ($remainingPercentage < 10 && $remainingPercentage >= 0) {
                $warnings[] = [
                    'type' => 'low_balance',
                    'severity' => 'warning',
                    'message' => 'Remaining balance is low (' . \App\Helpers\NumberFormatHelper::formatPercentage($remainingPercentage, 1) . ' of opening balance).',
                    'remaining_balance' => $budgetData['remaining_balance'],
                    'remaining_percentage' => $remainingPercentage,
                    'suggestion' => 'Monitor expenses carefully. Consider planning for additional funding.'
                ];
            }
        }

        // Check if remaining balance is very low (<5% of opening balance)
        if ($budgetData['opening_balance'] > 0) {
            $remainingPercentage = ($budgetData['remaining_balance'] / $budgetData['opening_balance']) * 100;

            if ($remainingPercentage < 5 && $remainingPercentage >= 0) {
                $warnings[] = [
                    'type' => 'very_low_balance',
                    'severity' => 'warning',
                    'message' => 'Remaining balance is very low (' . \App\Helpers\NumberFormatHelper::formatPercentage($remainingPercentage, 1) . ' of opening balance).',
                    'remaining_balance' => $budgetData['remaining_balance'],
                    'remaining_percentage' => $remainingPercentage,
                    'suggestion' => 'Immediate attention required. Consider requesting additional funding.'
                ];
            }
        }
    }

    /**
     * Check for inconsistencies
     *
     * @param array $budgetData
     * @param array $warnings
     * @param array $info
     * @return void
     */
    private static function checkInconsistencies(array $budgetData, array &$warnings, array &$info): void
    {
        // Check if no expenses recorded but project is active
        if ($budgetData['total_expenses'] == 0 && $budgetData['opening_balance'] > 0) {
            $info[] = [
                'type' => 'no_expenses',
                'severity' => 'info',
                'message' => 'No expenses recorded yet.',
                'suggestion' => 'Expenses will appear here once reports are submitted.'
            ];
        }

        // Check if budget items exist
        if ($budgetData['budget_items_total'] == 0) {
            $warnings[] = [
                'type' => 'no_budget_items',
                'severity' => 'warning',
                'message' => 'No budget items found.',
                'suggestion' => 'Add budget items to track project expenses.'
            ];
        }
    }

    /**
     * Get budget summary with validation
     *
     * @param Project $project
     * @return array
     */
    public static function getBudgetSummary(Project $project): array
    {
        $validation = self::validateBudget($project);

        return [
            'budget_data' => $validation['budget_data'],
            'validation' => [
                'is_valid' => $validation['is_valid'],
                'has_warnings' => $validation['has_warnings'],
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings'],
                'info' => $validation['info'],
            ]
        ];
    }
}
