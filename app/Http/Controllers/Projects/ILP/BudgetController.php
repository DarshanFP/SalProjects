<?php

namespace App\Http\Controllers\Projects\ILP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\ILP\ProjectILPBudget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BudgetController extends Controller
{
    // Store or update budget
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing ILP Budget', ['project_id' => $projectId]);

            // Delete existing budget rows and insert updated data
            ProjectILPBudget::where('project_id', $projectId)->delete();

            foreach ($request->budget_desc as $index => $description) {
                ProjectILPBudget::create([
                    'project_id' => $projectId,
                    'budget_desc' => $description,
                    'cost' => $request->cost[$index],
                    'beneficiary_contribution' => $request->beneficiary_contribution,
                    'amount_requested' => $request->amount_requested,
                ]);
            }

            DB::commit();
            Log::info('ILP Budget saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Budget saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving ILP Budget', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save budget.'], 500);
        }
    }

    // Show budget for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching ILP Budget', ['project_id' => $projectId]);

            $budgets = ProjectILPBudget::where('project_id', $projectId)->get();
            return response()->json($budgets, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching ILP Budget', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch budget.'], 500);
        }
    }

    // Edit budget for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing ILP Budget', ['project_id' => $projectId]);

            $budgets = ProjectILPBudget::where('project_id', $projectId)->get();
            $total_amount = $budgets->sum('cost');
            $beneficiary_contribution = $budgets->first()->beneficiary_contribution ?? 0;
            $amount_requested = $budgets->first()->amount_requested ?? 0;

            return view('projects.partials.Edit.ILP.budget', compact('budgets', 'total_amount', 'beneficiary_contribution', 'amount_requested'));
        } catch (\Exception $e) {
            Log::error('Error editing ILP Budget', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Delete budget for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting ILP Budget', ['project_id' => $projectId]);

            ProjectILPBudget::where('project_id', $projectId)->delete();
            DB::commit();
            Log::info('ILP Budget deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Budget deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting ILP Budget', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete budget.'], 500);
        }
    }
}
