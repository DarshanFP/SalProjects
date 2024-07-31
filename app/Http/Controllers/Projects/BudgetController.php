<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use App\Models\Reports\Monthly\DPAccountDetail;
use Illuminate\Support\Facades\Log;

class BudgetController extends Controller
{
    public function store(Request $request, Project $project)
    {
        Log::info('BudgetController@store - Data received from form', $request->all());

        $validated = $request->validate([
            'phases' => 'required|array',
            'phases.*.amount_sanctioned' => 'nullable|numeric',
            'phases.*.amount_forwarded' => 'nullable|numeric',
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
        $totalAmountForwarded = 0;

        foreach ($validated['phases'] as $phaseIndex => $phase) {
            $amountSanctioned = $phase['amount_sanctioned'] ?? 0;
            $amountForwarded = $phase['amount_forwarded'] ?? 0;

            $totalAmountSanctioned += $amountSanctioned;
            $totalAmountForwarded += $amountForwarded;

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
            'amount_forwarded' => $totalAmountForwarded,
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
            'phases.*.amount_forwarded' => 'nullable|numeric',
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
        $totalAmountForwarded = 0;

        ProjectBudget::where('project_id', $project->project_id)->delete();

        foreach ($validated['phases'] as $phaseIndex => $phase) {
            $amountSanctioned = $phase['amount_sanctioned'] ?? 0;
            $amountForwarded = $phase['amount_forwarded'] ?? 0;

            $totalAmountSanctioned += $amountSanctioned;
            $totalAmountForwarded += $amountForwarded;

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
            'amount_forwarded' => $totalAmountForwarded,
        ]);

        Log::info('BudgetController@update - Data passed to database', ['project_id' => $project->project_id]);

        return $project;
    }

    // To view the budget of a project

    public function viewBudget($projectId)
    {
        $projectBudget = ProjectBudget::with('dpAccountDetails')->where('project_id', $projectId)->firstOrFail();
        $remainingBalance = $projectBudget->calculateRemainingBalance();

        return view('budgets.view', compact('projectBudget', 'remainingBalance'));
    }

    // To add an expense to a project

    public function addExpense(Request $request, $projectId)
    {
        $validated = $request->validate([
            'report_id' => 'required|string',
            'particulars' => 'required|string|max:255',
            'amount_forwarded' => 'nullable|numeric',
            'amount_sanctioned' => 'nullable|numeric',
            'total_amount' => 'required|numeric',
            'expenses_last_month' => 'nullable|numeric',
            'expenses_this_month' => 'required|numeric',
            'total_expenses' => 'required|numeric',
            'balance_amount' => 'required|numeric',
        ]);

        DPAccountDetail::create([
            'project_id' => $projectId,
            'report_id' => $request->report_id,
            'particulars' => $request->particulars,
            'amount_forwarded' => $request->amount_forwarded,
            'amount_sanctioned' => $request->amount_sanctioned,
            'total_amount' => $request->total_amount,
            'expenses_last_month' => $request->expenses_last_month,
            'expenses_this_month' => $request->expenses_this_month,
            'total_expenses' => $request->total_expenses,
            'balance_amount' => $request->balance_amount,
        ]);

        return back()->with('success', 'Expense added successfully.');
    }
}
