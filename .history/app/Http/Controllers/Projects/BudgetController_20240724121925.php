<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use Illuminate\Support\Facades\Log;

class BudgetController extends Controller
{
    public function store(Request $request, Project $project)
    {
        Log::info('BudgetController@store - Data received from form', $request->all());

        foreach ($request->input('phases', []) as $phaseIndex => $phase) {
            foreach ($phase['budget'] as $budgetIndex => $budget) {
                ProjectBudget::create([
                    'project_id' => $project->project_id,
                    'phase' => $phaseIndex + 1,
                    'particular' => $budget['particular'],
                    'rate_quantity' => $budget['rate_quantity'],
                    'rate_multiplier' => $budget['rate_multiplier'],
                    'rate_duration' => $budget['rate_duration'],
                    'rate_increase' => $budget['rate_increase'] ?? 0,
                    'this_phase' => $budget['this_phase'],
                    'next_phase' => $budget['next_phase'],
                ]);
            }
        }

        Log::info('BudgetController@store - Data passed to database', ['project_id' => $project->project_id]);

        return $project;
    }

    public function update(Request $request, Project $project)
    {
        Log::info('BudgetController@update - Data received from form', $request->all());

        ProjectBudget::where('project_id', $project->project_id)->delete();

        foreach ($request->input('phases', []) as $phaseIndex => $phase) {
            foreach ($phase['budget'] as $budgetIndex => $budget) {
                ProjectBudget::create([
                    'project_id' => $project->project_id,
                    'phase' => $phaseIndex + 1,
                    'particular' => $budget['particular'],
                    'rate_quantity' => $budget['rate_quantity'],
                    'rate_multiplier' => $budget['rate_multiplier'],
                    'rate_duration' => $budget['rate_duration'],
                    'rate_increase' => $budget['rate_increase'] ?? 0,
                    'this_phase' => $budget['this_phase'],
                    'next_phase' => $budget['next_phase'],
                ]);
            }
        }

        Log::info('BudgetController@update - Data passed to database', ['project_id' => $project->project_id]);

        return $project;
    }
}
