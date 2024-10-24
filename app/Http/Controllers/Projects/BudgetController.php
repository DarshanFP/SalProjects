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

        $validated = $request->validate([
            'phases' => 'required|array',
            'phases.*.amount_sanctioned' => 'nullable|numeric',
            'phases.*.budget' => 'nullable|array',
            'phases.*.budget.*.particular' => 'nullable|string|max:255',
            'phases.*.budget.*.rate_quantity' => 'nullable|numeric',
            'phases.*.budget.*.rate_multiplier' => 'nullable|numeric',
            'phases.*.budget.*.rate_duration' => 'nullable|numeric',
            'phases.*.budget.*.rate_increase' => 'nullable|numeric',
            'phases.*.budget.*.this_phase' => 'nullable|numeric',
            'phases.*.budget.*.next_phase' => 'nullable|numeric',
        ]);

        $totalAmountSanctioned = 0;

        foreach ($validated['phases'] as $phaseIndex => $phase) {
            $amountSanctioned = $phase['amount_sanctioned'] ?? 0;

            $totalAmountSanctioned += $amountSanctioned;

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

        $project->update([
            'amount_sanctioned' => $totalAmountSanctioned,
        ]);

        Log::info('BudgetController@store - Data passed to database', ['project_id' => $project->project_id]);

        return $project;
    }


    public function update(Request $request, Project $project)
{
    Log::info('BudgetController@update - Data received from form', $request->all());

    $validated = $request->validate([
        'phases' => 'required|array',
        'phases.*.amount_sanctioned' => 'nullable|numeric',
        'phases.*.budget' => 'nullable|array',
        'phases.*.budget.*.particular' => 'nullable|string|max:255',
        'phases.*.budget.*.rate_quantity' => 'nullable|numeric',
        'phases.*.budget.*.rate_multiplier' => 'nullable|numeric',
        'phases.*.budget.*.rate_duration' => 'nullable|numeric',
        'phases.*.budget.*.rate_increase' => 'nullable|numeric',
        'phases.*.budget.*.this_phase' => 'nullable|numeric',
        'phases.*.budget.*.next_phase' => 'nullable|numeric',
    ]);

    $totalAmountSanctioned = 0;

    ProjectBudget::where('project_id', $project->project_id)->delete();

    foreach ($validated['phases'] as $phaseIndex => $phase) {
        // Add logging to check the phase index
        Log::info('Processing phase', ['phaseIndex' => $phaseIndex + 1]);

        $amountSanctioned = $phase['amount_sanctioned'] ?? 0;
        $totalAmountSanctioned += $amountSanctioned;

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

    $project->update([
        'amount_sanctioned' => $totalAmountSanctioned,
    ]);

    Log::info('BudgetController@update - Data passed to database', ['project_id' => $project->project_id]);

    return $project;
}

//     public function update(Request $request, Project $project)
// {
//     Log::info('BudgetController@update - Data received from form', $request->all());

//     $validated = $request->validate([
//         'phases' => 'required|array',
//         'phases.*.amount_sanctioned' => 'nullable|numeric',
//         'phases.*.budget' => 'nullable|array',
//         'phases.*.budget.*.particular' => 'nullable|string|max:255',
//         'phases.*.budget.*.rate_quantity' => 'nullable|numeric',
//         'phases.*.budget.*.rate_multiplier' => 'nullable|numeric',
//         'phases.*.budget.*.rate_duration' => 'nullable|numeric',
//         'phases.*.budget.*.rate_increase' => 'nullable|numeric',
//         'phases.*.budget.*.this_phase' => 'nullable|numeric',
//         'phases.*.budget.*.next_phase' => 'nullable|numeric',
//     ]);

//     $totalAmountSanctioned = 0;

//     ProjectBudget::where('project_id', $project->project_id)->delete();

//     foreach ($validated['phases'] as $phaseIndex => $phase) {
//         $amountSanctioned = $phase['amount_sanctioned'] ?? 0;

//         $totalAmountSanctioned += $amountSanctioned;

//         if (isset($phase['budget'])) {
//             foreach ($phase['budget'] as $budget) {
//                 ProjectBudget::create([
//                     'project_id' => $project->project_id,
//                     'phase' => $phaseIndex + 1,
//                     'particular' => $budget['particular'] ?? '',
//                     'rate_quantity' => $budget['rate_quantity'] ?? 0,
//                     'rate_multiplier' => $budget['rate_multiplier'] ?? 0,
//                     'rate_duration' => $budget['rate_duration'] ?? 0,
//                     'rate_increase' => $budget['rate_increase'] ?? 0,
//                     'this_phase' => $budget['this_phase'] ?? 0,
//                     'next_phase' => $budget['next_phase'] ?? 0,
//                 ]);
//             }
//         }
//     }

//     $project->update([
//         'amount_sanctioned' => $totalAmountSanctioned,
//     ]);

//     Log::info('BudgetController@update - Data passed to database', ['project_id' => $project->project_id]);

//     return $project;
// }

    // public function update(Request $request, Project $project)
    // {
    //     Log::info('BudgetController@update - Data received from form', $request->all());

    //     $validated = $request->validate([
    //         'phases' => 'required|array',
    //         'phases.*.amount_sanctioned' => 'nullable|numeric',
    //         'phases.*.budget' => 'nullable|array',
    //         'phases.*.budget.*.particular' => 'nullable|string|max:255',
    //         'phases.*.budget.*.rate_quantity' => 'nullable|numeric',
    //         'phases.*.budget.*.rate_multiplier' => 'nullable|numeric',
    //         'phases.*.budget.*.rate_duration' => 'nullable|numeric',
    //         'phases.*.budget.*.rate_increase' => 'nullable|numeric',
    //         'phases.*.budget.*.this_phase' => 'nullable|numeric',
    //         'phases.*.budget.*.next_phase' => 'nullable|numeric',
    //     ]);

    //     $totalAmountSanctioned = 0;

    //     ProjectBudget::where('project_id', $project->project_id)->delete();

    //     foreach ($validated['phases'] as $phaseIndex => $phase) {
    //         $amountSanctioned = $phase['amount_sanctioned'] ?? 0;

    //         $totalAmountSanctioned += $amountSanctioned;

    //         if (isset($phase['budget'])) {
    //             foreach ($phase['budget'] as $budget) {
    //                 ProjectBudget::create([
    //                     'project_id' => $project->project_id,
    //                     'phase' => $phaseIndex + 1,
    //                     'particular' => $budget['particular'] ?? '',
    //                     'rate_quantity' => $budget['rate_quantity'] ?? 0,
    //                     'rate_multiplier' => $budget['rate_multiplier'] ?? 0,
    //                     'rate_duration' => $budget['rate_duration'] ?? 0,
    //                     'rate_increase' => $budget['rate_increase'] ?? 0,
    //                     'this_phase' => $budget['this_phase'] ?? 0,
    //                     'next_phase' => $budget['next_phase'] ?? 0,
    //                 ]);
    //             }
    //         }
    //     }

    //     $project->update([
    //         'amount_sanctioned' => $totalAmountSanctioned,
    //     ]);

    //     Log::info('BudgetController@update - Data passed to database', ['project_id' => $project->project_id]);

    //     return $project;
    // }
}
