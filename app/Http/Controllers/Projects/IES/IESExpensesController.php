<?php

namespace App\Http\Controllers\Projects\IES;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IES\ProjectIESExpenses;
use App\Models\OldProjects\IES\ProjectIESExpenseDetail;
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

            // Delete all existing expenses for the project
            $existingExpenses = ProjectIESExpenses::where('project_id', $projectId)->first();
            if ($existingExpenses) {
                $existingExpenses->expenseDetails()->delete();
                $existingExpenses->delete();
            }

            // Create new ProjectIESExpenses
            $projectExpenses = new ProjectIESExpenses();
            $projectExpenses->project_id = $projectId;
            $projectExpenses->total_expenses = $request->input('total_expenses');
            $projectExpenses->expected_scholarship_govt = $request->input('expected_scholarship_govt');
            $projectExpenses->support_other_sources = $request->input('support_other_sources');
            $projectExpenses->beneficiary_contribution = $request->input('beneficiary_contribution');
            $projectExpenses->balance_requested = $request->input('balance_requested');
            $projectExpenses->save();

            // Store each particular and amount as a detail
            $particulars = $request->input('particulars', []);
            $amounts = $request->input('amounts', []);

            for ($i = 0; $i < count($particulars); $i++) {
                if (!empty($particulars[$i]) && !empty($amounts[$i])) {
                    $projectExpenses->expenseDetails()->create([
                        'particular' => $particulars[$i],
                        'amount' => $amounts[$i],
                    ]);
                }
            }

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

        // Retrieve the expenses with expenseDetails
        $expenses = ProjectIESExpenses::with('expenseDetails')->where('project_id', $projectId)->first();

        if (!$expenses) {
            return null; // If no expenses found, return null so Blade can handle it gracefully
        }

        return $expenses; // Return as an object (not JSON) for use in Blade view
    } catch (\Exception $e) {
        Log::error('Error fetching IES estimated expenses', ['error' => $e->getMessage()]);
        return null; // Return null to avoid breaking the Blade view
    }
}


    public function edit($projectId)
    {
        try {
            // Fetch the IES Expenses along with the related expense details
            $iesExpenses = ProjectIESExpenses::with('expenseDetails')
                ->where('project_id', $projectId)
                ->first();

            Log::info('IESExpenses Controller - Fetched IES Expenses for editing in ', ['IESExpenses' => $iesExpenses]);

            return $iesExpenses;

        } catch (\Exception $e) {
            Log::error('Error fetching IES estimated expenses', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch IES estimated expenses.'], 500);
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

            $existingExpenses = ProjectIESExpenses::where('project_id', $projectId)->first();
            if ($existingExpenses) {
                $existingExpenses->expenseDetails()->delete();
                $existingExpenses->delete();
            }

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
