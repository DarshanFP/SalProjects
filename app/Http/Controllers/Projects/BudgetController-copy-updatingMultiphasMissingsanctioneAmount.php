<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\ProjectBudget;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;

class BudgetControllerBackup extends Controller
{
    public function store(Request $request, Project $project)
    {
        Log::info('BudgetController@store - Data received from form', $request->all());

        $validated = $request->validate([
            'phases' => 'required|array',
            'phases.*.amount_sanctioned' => 'required|numeric',
            'phases.*.budget' => 'required|array',
            'phases.*.budget.*.particular' => 'nullable|string|max:255',
            'phases.*.budget.*.rate_quantity' => 'nullable|numeric',
            'phases.*.budget.*.rate_multiplier' => 'nullable|numeric',
            'phases.*.budget.*.rate_duration' => 'nullable|numeric',
            'phases.*.budget.*.rate_increase' => 'nullable|numeric',
            'phases.*.budget.*.this_phase' => 'nullable|numeric',
            'phases.*.budget.*.next_phase' => 'nullable|numeric',
        ]);

        try {
            foreach ($request->phases as $phaseIndex => $phase) {
                foreach ($phase['budget'] as $budgetItem) {
                    ProjectBudget::create([
                        'project_id' => $project->project_id,
                        'phase' => $phaseIndex + 1,
                        'particular' => $budgetItem['particular'],
                        'rate_quantity' => $budgetItem['rate_quantity'],
                        'rate_multiplier' => $budgetItem['rate_multiplier'],
                        'rate_duration' => $budgetItem['rate_duration'],
                        'rate_increase' => $budgetItem['rate_increase'] ?? 0.00,
                        'this_phase' => $budgetItem['this_phase'],
                        'next_phase' => $budgetItem['next_phase'],
                    ]);
                }
            }

            Log::info('BudgetController@store - Data passed to database', ['project_id' => $project->project_id]);

            return $project;
        } catch (\Exception $e) {
            Log::error('BudgetController@store - Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Request $request, Project $project)
    {
        Log::info('BudgetController@update - Data received from form', $request->all());

        $validated = $request->validate([
            'phases' => 'required|array',
            'phases.*.amount_sanctioned' => 'required|numeric',
            'phases.*.budget' => 'required|array',
            'phases.*.budget.*.particular' => 'nullable|string|max:255',
            'phases.*.budget.*.rate_quantity' => 'nullable|numeric',
            'phases.*.budget.*.rate_multiplier' => 'nullable|numeric',
            'phases.*.budget.*.rate_duration' => 'nullable|numeric',
            'phases.*.budget.*.rate_increase' => 'nullable|numeric',
            'phases.*.budget.*.this_phase' => 'nullable|numeric',
            'phases.*.budget.*.next_phase' => 'nullable|numeric',
        ]);

        try {
            // Delete existing budgets
            ProjectBudget::where('project_id', $project->project_id)->delete();

            // Insert updated budgets
            foreach ($request->phases as $phaseIndex => $phase) {
                foreach ($phase['budget'] as $budgetItem) {
                    ProjectBudget::create([
                        'project_id' => $project->project_id,
                        'phase' => $phaseIndex + 1,
                        'particular' => $budgetItem['particular'],
                        'rate_quantity' => $budgetItem['rate_quantity'],
                        'rate_multiplier' => $budgetItem['rate_multiplier'],
                        'rate_duration' => $budgetItem['rate_duration'],
                        'rate_increase' => $budgetItem['rate_increase'] ?? 0.00,
                        'this_phase' => $budgetItem['this_phase'],
                        'next_phase' => $budgetItem['next_phase'],
                    ]);
                }
            }

            Log::info('BudgetController@update - Data passed to database', ['project_id' => $project->project_id]);

            return $project;
        } catch (\Exception $e) {
            Log::error('BudgetController@update - Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
