<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\StoreBudgetRequest;
use App\Http\Requests\Projects\UpdateBudgetRequest;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use App\Services\Budget\BudgetSyncService;
use App\Services\Budget\BudgetSyncGuard;
use App\Services\Budget\BudgetAuditLogger;
use App\Services\Numeric\BoundedNumericService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BudgetController extends Controller
{
    private const BUDGET_LOCKED_MESSAGE = 'Project is approved. Budget edits are locked until the project is reverted.';

    public function store(Request $request, Project $project)
    {
        if (! BudgetSyncGuard::canEditBudget($project)) {
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

        $formRequest = StoreBudgetRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
        $validated = $validator->validated();

        Log::info('BudgetController@store - Data received from form', [
            'project_id' => $project->project_id,
            'phases_count' => isset($validated['phases']) ? count($validated['phases']) : 0,
        ]);

        $phases = $validated['phases'] ?? [];
        if (! is_array($phases)) {
            $phases = [];
        }

        $bounded = app(BoundedNumericService::class);
        $maxPhase = $bounded->getMaxFor('project_budgets.this_phase');

        $phase = $phases[0] ?? null;
        if ($phase !== null && isset($phase['budget']) && is_array($phase['budget'])) {
            foreach ($phase['budget'] as $budget) {
                $thisPhase = $bounded->clamp((float) ($budget['this_phase'] ?? 0), $maxPhase);

                ProjectBudget::create([
                    'project_id' => $project->project_id,
                    'phase' => (int) ($project->current_phase ?? 1),
                    'particular' => $budget['particular'] ?? '',
                    'rate_quantity' => $budget['rate_quantity'] ?? 0,
                    'rate_multiplier' => $budget['rate_multiplier'] ?? 0,
                    'rate_duration' => $budget['rate_duration'] ?? 0,
                    'rate_increase' => $budget['rate_increase'] ?? 0,
                    'this_phase' => $thisPhase,
                    'next_phase' => null,
                ]);
            }
        }

        Log::info('BudgetController@store - Data passed to database', ['project_id' => $project->project_id]);

        return $project;
    }

    public function update(Request $request, Project $project)
    {
        if (! BudgetSyncGuard::canEditBudget($project)) {
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

        $formRequest = UpdateBudgetRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
        $validated = $validator->validated();

        Log::info('BudgetController@update - Data received from form', [
            'project_id' => $project->project_id,
            'phases_count' => isset($validated['phases']) ? count($validated['phases']) : 0,
        ]);

        $phases = $validated['phases'] ?? [];
        if (! is_array($phases)) {
            $phases = [];
        }

        ProjectBudget::where('project_id', $project->project_id)
            ->where('phase', (int) ($project->current_phase ?? 1))
            ->delete();

        $bounded = app(BoundedNumericService::class);
        $maxPhase = $bounded->getMaxFor('project_budgets.this_phase');

        $phase = $phases[0] ?? null;
        if ($phase !== null && isset($phase['budget']) && is_array($phase['budget'])) {
            foreach ($phase['budget'] as $budget) {
                $thisPhase = $bounded->clamp((float) ($budget['this_phase'] ?? 0), $maxPhase);

                ProjectBudget::create([
                    'project_id' => $project->project_id,
                    'phase' => (int) ($project->current_phase ?? 1),
                    'particular' => $budget['particular'] ?? '',
                    'rate_quantity' => $budget['rate_quantity'] ?? 0,
                    'rate_multiplier' => $budget['rate_multiplier'] ?? 0,
                    'rate_duration' => $budget['rate_duration'] ?? 0,
                    'rate_increase' => $budget['rate_increase'] ?? 0,
                    'this_phase' => $thisPhase,
                    'next_phase' => null,
                ]);
            }
        }

        Log::info('BudgetController@update - Data passed to database', ['project_id' => $project->project_id]);

        $project->refresh();
        $project->load('budgets');
        app(BudgetSyncService::class)->syncFromTypeSave($project);

        return $project;
    }
}
