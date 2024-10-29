<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IAH\ProjectIAHBudgetDetails;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IAHBudgetDetailsController extends Controller
{
    // Store budget details for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IAH budget details', ['project_id' => $projectId]);

            // First, delete all existing budget details for the project
            ProjectIAHBudgetDetails::where('project_id', $projectId)->delete();

            // Insert new budget details
            $particulars = $request->input('particular', []);
            $amounts = $request->input('amount', []);
            $familyContribution = $request->input('family_contribution', 0);
            $totalExpenses = array_sum($amounts);

            for ($i = 0; $i < count($particulars); $i++) {
                if (!empty($particulars[$i]) && !empty($amounts[$i])) {
                    ProjectIAHBudgetDetails::create([
                        'project_id' => $projectId,
                        'particular' => $particulars[$i],
                        'amount' => $amounts[$i],
                        'total_expenses' => $totalExpenses,
                        'family_contribution' => $familyContribution,
                        'amount_requested' => $totalExpenses - $familyContribution,
                    ]);
                }
            }

            DB::commit();
            Log::info('IAH budget details saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IAH budget details saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IAH budget details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save IAH budget details.'], 500);
        }
    }

    // Show budget details for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IAH budget details', ['project_id' => $projectId]);

            $budgetDetails = ProjectIAHBudgetDetails::where('project_id', $projectId)->get();
            return response()->json($budgetDetails, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching IAH budget details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch IAH budget details.'], 500);
        }
    }

    // Edit budget details for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IAH budget details', ['project_id' => $projectId]);

            $budgetDetails = ProjectIAHBudgetDetails::where('project_id', $projectId)->get();

            // Return the data directly
            return $budgetDetails;
        } catch (\Exception $e) {
            Log::error('Error editing IAH budget details', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update budget details for a project
    public function update(Request $request, $projectId)
    {
        return $this->store($request, $projectId); // Reuse the store logic for update
    }

    // Delete budget details for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IAH budget details', ['project_id' => $projectId]);

            ProjectIAHBudgetDetails::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('IAH budget details deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IAH budget details deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IAH budget details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete IAH budget details.'], 500);
        }
    }
}
