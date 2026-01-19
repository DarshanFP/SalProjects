<?php

namespace App\Services\Budget\Strategies;

use App\Models\OldProjects\Project;
use Illuminate\Support\Collection;

/**
 * Interface for budget calculation strategies
 *
 * Each strategy handles budget calculation for specific project types
 * with different calculation patterns (direct mapping, single source contribution, multiple source contribution)
 */
interface BudgetCalculationStrategyInterface
{
    /**
     * Get budgets for a project
     *
     * @param Project $project The project to get budgets for
     * @param bool $calculateContributions Whether to calculate contributions (true for reports, false for exports)
     * @return Collection Collection of budget objects with calculated amount_sanctioned
     */
    public function getBudgets(Project $project, bool $calculateContributions = true): Collection;

    /**
     * Get the project type this strategy handles
     *
     * @return string Project type name
     */
    public function getProjectType(): string;
}
