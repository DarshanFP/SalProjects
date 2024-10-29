<?php

namespace App\Http\Controllers\Projects\IES;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IES\ProjectIESExpenses;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IESExpensesController extends Controller
{
    // Store or update expenses for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IES estimated expenses', ['project_id' => $projectId]);

            // First, delete all existing expenses for the project
            ProjectIESExpenses::where('project_id', $projectId)->delete();

            // Insert new expenses
            $particulars = $request->input('particulars', []);
            $amounts = $request->input('amounts', []);
            $totalExpenses = $request->input('total_expenses');
            $expectedScholarshipGovt = $request->input('expected_scholarship_govt');
            $supportOtherSources = $request->input('support_other_sources');
            $beneficiaryContribution = $request->input('beneficiary_contribution');
            $balanceRequested = $request->input('balance_requested');

            // Store each particular and amount
            for ($i = 0; $i < count($particulars); $i++) {
                if (!empty($particulars[$i]) && !empty($amounts[$i])) {
                    ProjectIESExpenses::create([
                        'project_id' => $projectId,
                        'particular' => $particulars[$i],
                        'amount' => $amounts[$i],
                    ]);
                }
            }

            // Store the totals and contributions
            $projectExpenses = new ProjectIESExpenses();
            $projectExpenses->project_id = $projectId;
            $projectExpenses->total_expenses = $totalExpenses;
            $projectExpenses->expected_scholarship_govt = $expectedScholarshipGovt;
            $projectExpenses->support_other_sources = $supportOtherSources;
            $projectExpenses->beneficiary_contribution = $beneficiaryContribution;
            $projectExpenses->balance_requested = $balanceRequested;
            $projectExpenses->save();

            DB::commit();
            Log::info('IES estimated expenses saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IES estimated expenses saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IES estimated expenses', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save IES estimated expenses.'], 500);
        }
    }

    // Show estimated expenses for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IES estimated expenses', ['project_id' => $projectId]);

            $expenses = ProjectIESExpenses::where('project_id', $projectId)->get();
            return response()->json($expenses, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching IES estimated expenses', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch IES estimated expenses.'], 500);
        }
    }

    // Edit estimated expenses for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IES estimated expenses', ['project_id' => $projectId]);

            $expenses = ProjectIESExpenses::where('project_id', $projectId)->get();

            // Return the data directly
            return $expenses;
        } catch (\Exception $e) {
            Log::error('Error editing IES estimated expenses', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update estimated expenses for a project
    public function update(Request $request, $projectId)
    {
        return $this->store($request, $projectId); // Reuse the store logic for update
    }

    // Delete estimated expenses for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IES estimated expenses', ['project_id' => $projectId]);

            ProjectIESExpenses::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('IES estimated expenses deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IES estimated expenses deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IES estimated expenses', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete IES estimated expenses.'], 500);
        }
    }
}
