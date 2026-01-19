<?php

namespace App\Services\Budget\Strategies;

use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Direct Mapping Strategy
 *
 * Handles project types that use direct field mapping without contribution calculation:
 * - Development Projects (DP, LDP, RST, CIC, CCI, Edu-RUT)
 * - Institutional Ongoing Group Educational proposal (IGE)
 *
 * For Development Projects: Uses phase-based filtering
 * For IGE: Direct fetch without phase filtering
 */
class DirectMappingStrategy extends BaseBudgetStrategy
{
    /**
     * Get budgets for the project
     *
     * @param Project $project The project to get budgets for
     * @param bool $calculateContributions Whether to calculate contributions (ignored for direct mapping)
     * @return Collection Collection of budget objects
     */
    public function getBudgets(Project $project, bool $calculateContributions = true): Collection
    {
        $modelClass = $this->getConfig('model');

        if ($this->isPhaseBased()) {
            return $this->getPhaseBasedBudgets($project, $modelClass);
        }

        return $this->getDirectBudgets($project, $modelClass);
    }

    /**
     * Get phase-based budgets (Development Projects)
     *
     * @param Project $project The project
     * @param string $modelClass The model class name
     * @return Collection
     */
    private function getPhaseBasedBudgets(Project $project, string $modelClass): Collection
    {
        $phaseSelection = $this->getPhaseSelection();

        // Try to use current_phase first (preferred)
        if ($phaseSelection === 'current' && $project->current_phase) {
            $phase = $project->current_phase;
            Log::info('Retrieved current phase for development project', [
                'project_id' => $project->project_id,
                'current_phase' => $phase
            ]);
        } else {
            // Fallback to highest phase
            $phase = $modelClass::where('project_id', $project->project_id)->max('phase');
            Log::info('Retrieved highest phase for development project (fallback)', [
                'project_id' => $project->project_id,
                'highest_phase' => $phase
            ]);
        }

        if (!$phase) {
            Log::warning('No phase found for development project', [
                'project_id' => $project->project_id
            ]);
            return collect();
        }

        return $modelClass::where('project_id', $project->project_id)
            ->where('phase', $phase)
            ->get();
    }

    /**
     * Get direct budgets without phase filtering (IGE)
     *
     * @param Project $project The project
     * @param string $modelClass The model class name
     * @return Collection
     */
    private function getDirectBudgets(Project $project, string $modelClass): Collection
    {
        return $modelClass::where('project_id', $project->project_id)->get();
    }
}
