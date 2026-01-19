<?php

namespace App\Services\Budget;

use App\Models\OldProjects\Project;
use App\Services\Budget\Strategies\BudgetCalculationStrategyInterface;
use App\Services\Budget\Strategies\DirectMappingStrategy;
use App\Services\Budget\Strategies\SingleSourceContributionStrategy;
use App\Services\Budget\Strategies\MultipleSourceContributionStrategy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Budget Calculation Service
 *
 * Centralized service for calculating budgets across all project types.
 * Uses strategy pattern to handle different calculation methods while
 * preserving project-type-specific logic.
 */
class BudgetCalculationService
{
    /**
     * Get budgets for report (with contribution calculation)
     *
     * @param Project $project The project to get budgets for
     * @param bool $calculateContributions Whether to calculate contributions (default: true)
     * @return Collection Collection of budget objects with calculated amount_sanctioned
     */
    public static function getBudgetsForReport(Project $project, bool $calculateContributions = true): Collection
    {
        $strategy = self::getStrategyForProjectType($project->project_type);
        return $strategy->getBudgets($project, $calculateContributions);
    }

    /**
     * Get budgets for export (simple fetch, no contribution calculation)
     *
     * @param Project $project The project to get budgets for
     * @return Collection Collection of budget objects (no calculation)
     */
    public static function getBudgetsForExport(Project $project): Collection
    {
        $strategy = self::getStrategyForProjectType($project->project_type);
        return $strategy->getBudgets($project, false);
    }

    /**
     * Get appropriate strategy for project type
     *
     * @param string $projectType The project type
     * @return BudgetCalculationStrategyInterface
     * @throws \RuntimeException If project type not configured
     */
    private static function getStrategyForProjectType(string $projectType): BudgetCalculationStrategyInterface
    {
        $config = config('budget.field_mappings');

        if (!isset($config[$projectType])) {
            Log::warning('Unknown project type for budget calculation, using DirectMappingStrategy as fallback', [
                'project_type' => $projectType
            ]);
            return new DirectMappingStrategy('Development Projects');
        }

        $strategyClass = $config[$projectType]['strategy'];

        if (!class_exists($strategyClass)) {
            throw new \RuntimeException("Strategy class not found: {$strategyClass}");
        }

        return new $strategyClass($projectType);
    }

    /**
     * Calculate contribution per row (single source)
     *
     * @param float $contribution Total contribution amount
     * @param int $totalRows Total number of budget rows
     * @return float Contribution per row
     */
    public static function calculateContributionPerRow(float $contribution, int $totalRows): float
    {
        return $totalRows > 0 ? $contribution / $totalRows : 0;
    }

    /**
     * Calculate total contribution from multiple sources
     *
     * @param array $sources Array of contribution values
     * @return float Total contribution
     */
    public static function calculateTotalContribution(array $sources): float
    {
        return array_sum(array_map(fn($source) => (float)($source ?? 0), $sources));
    }

    /**
     * Calculate amount sanctioned after contribution
     *
     * @param float $originalAmount Original amount before contribution
     * @param float $contributionPerRow Contribution to subtract per row
     * @return float Amount sanctioned (never negative)
     */
    public static function calculateAmountSanctioned(float $originalAmount, float $contributionPerRow): float
    {
        return self::preventNegativeAmount($originalAmount - $contributionPerRow);
    }

    /**
     * Prevent negative amounts
     *
     * @param float $amount Amount to check
     * @return float Amount (0 if negative)
     */
    public static function preventNegativeAmount(float $amount): float
    {
        return max(0, $amount);
    }

    /**
     * Log budget calculation
     *
     * @param string $projectType Project type name
     * @param array $data Data to log
     * @return void
     */
    public static function logCalculation(string $projectType, array $data): void
    {
        Log::info("{$projectType} Budget calculation", $data);
    }

    /**
     * Log budget row calculation
     *
     * @param string $projectType Project type name
     * @param array $data Data to log
     * @return void
     */
    public static function logRowCalculation(string $projectType, array $data): void
    {
        Log::info("{$projectType} Budget row calculation", $data);
    }
}
