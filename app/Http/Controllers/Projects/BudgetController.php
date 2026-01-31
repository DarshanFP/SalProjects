<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use App\Services\Budget\BudgetSyncService;
use App\Services\Budget\BudgetSyncGuard;
use App\Services\Budget\BudgetAuditLogger;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BudgetController extends Controller
{
    /** Phase 3: User-facing message when budget edit is blocked (project approved). */
    private const BUDGET_LOCKED_MESSAGE = 'Project is approved. Budget edits are locked until the project is reverted.';

    public function store(Request $request, Project $project)
    {
        // Phase 3: Block budget edits when project is approved
        if (!BudgetSyncGuard::canEditBudget($project)) {
            BudgetAuditLogger::logBlockedEditAttempt(
                $project->project_id,
                Auth::id(),
                'budget_store',
                $project->status ?? ''
            );
            throw new HttpResponseException(
                redirect()->back()->with('error', self::BUDGET_LOCKED_MESSAGE)
            );
        }

        // Use all() to get all form data including phases[][budget][] arrays
        // These fields are not in StoreProjectRequest validation rules
        $request->validate([
            'phases' => 'nullable|array',
            'phases.*.budget' => 'nullable|array',
            'phases.*.budget.*.rate_quantity' => 'nullable|numeric|min:0',
            'phases.*.budget.*.rate_multiplier' => 'nullable|numeric|min:0',
            'phases.*.budget.*.rate_duration' => 'nullable|numeric|min:0',
            'phases.*.budget.*.this_phase' => 'nullable|numeric|min:0',
        ], [
            'phases.*.budget.*.rate_quantity.min' => 'Rate quantity cannot be negative.',
            'phases.*.budget.*.rate_multiplier.min' => 'Rate multiplier cannot be negative.',
            'phases.*.budget.*.rate_duration.min' => 'Rate duration cannot be negative.',
            'phases.*.budget.*.this_phase.min' => 'This phase amount cannot be negative.',
        ]);

        // Use input() to get all nested budget data including fields not in validation rules
        $phases = $request->input('phases', []);

        Log::info('BudgetController@store - Data received from form', [
            'project_id' => $project->project_id,
            'phases_count' => count($phases)
        ]);
        if (!is_array($phases)) {
            $phases = [];
        }

        foreach ($phases as $phaseIndex => $phase) {
            if (isset($phase['budget'])) {
                foreach ($phase['budget'] as $budget) {
                    ProjectBudget::create([
                        'project_id' => $project->project_id,
                        'phase' => $phaseIndex + 1,
                        'particular' => $budget['particular'] ?? '',
                        'rate_quantity' => $budget['rate_quantity'] ?? 0,
                        'rate_multiplier' => $budget['rate_multiplier'] ?? 0,
                        'rate_duration' => $budget['rate_duration'] ?? 0,
                        'rate_increase' => $budget['rate_increase'] ?? 0,
                        'this_phase' => $budget['this_phase'] ?? 0,
                        'next_phase' => $budget['next_phase'] ?? 0,
                    ]);
                }
            }
        }

        Log::info('BudgetController@store - Data passed to database', ['project_id' => $project->project_id]);

        return $project;
    }


    public function update(Request $request, Project $project)
{
    // Phase 3: Block budget edits when project is approved
    if (!BudgetSyncGuard::canEditBudget($project)) {
        BudgetAuditLogger::logBlockedEditAttempt(
            $project->project_id,
            Auth::id(),
            'budget_update',
            $project->status ?? ''
        );
        throw new HttpResponseException(
            redirect()->back()->with('error', self::BUDGET_LOCKED_MESSAGE)
        );
    }

    // Validate structure and values
    $request->validate([
        'phases' => 'nullable|array',
        'phases.*.budget' => 'nullable|array',
        'phases.*.budget.*.rate_quantity' => 'nullable|numeric|min:0',
        'phases.*.budget.*.rate_multiplier' => 'nullable|numeric|min:0',
        'phases.*.budget.*.rate_duration' => 'nullable|numeric|min:0',
        'phases.*.budget.*.this_phase' => 'nullable|numeric|min:0',
    ], [
        'phases.*.budget.*.rate_quantity.min' => 'Rate quantity cannot be negative.',
        'phases.*.budget.*.rate_multiplier.min' => 'Rate multiplier cannot be negative.',
        'phases.*.budget.*.rate_duration.min' => 'Rate duration cannot be negative.',
        'phases.*.budget.*.this_phase.min' => 'This phase amount cannot be negative.',
    ]);

    // Use input() to get all nested budget data including fields not in validation rules
    $phases = $request->input('phases', []);

    Log::info('BudgetController@update - Data received from form', [
        'project_id' => $project->project_id,
        'phases_count' => count($phases)
    ]);
    if (!is_array($phases)) {
        $phases = [];
    }

    ProjectBudget::where('project_id', $project->project_id)->delete();

    foreach ($phases as $phaseIndex => $phase) {
        if (isset($phase['budget'])) {
            foreach ($phase['budget'] as $budget) {
                ProjectBudget::create([
                    'project_id' => $project->project_id,
                    'phase' => $phaseIndex + 1,
                    'particular' => $budget['particular'] ?? '',
                    'rate_quantity' => $budget['rate_quantity'] ?? 0,
                    'rate_multiplier' => $budget['rate_multiplier'] ?? 0,
                    'rate_duration' => $budget['rate_duration'] ?? 0,
                    'rate_increase' => $budget['rate_increase'] ?? 0,
                    'this_phase' => $budget['this_phase'] ?? 0,
                    'next_phase' => $budget['next_phase'] ?? 0,
                ]);
            }
        }
    }

    Log::info('BudgetController@update - Data passed to database', ['project_id' => $project->project_id]);

    // Phase 2: Sync project-level budget fields for pre-approval Development projects (feature-flagged)
    $project->refresh();
    $project->load('budgets');
    app(BudgetSyncService::class)->syncFromTypeSave($project);

    return $project;
}
}
