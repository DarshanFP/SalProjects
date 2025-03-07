<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IAH\ProjectIAHBudgetDetails;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IAHBudgetDetailsController extends Controller
{
    /**
     * Store budget details for a project (creates fresh entries after deleting old ones).
     */
    public function store(Request $request, $projectId)
    {
        Log::info('IAHBudgetDetailsController@store - Start', [
            'project_id' => $projectId,
            'request_data' => $request->all()
        ]);

        DB::beginTransaction();
        try {
            // 1️⃣ Delete old budget details for this project
            ProjectIAHBudgetDetails::where('project_id', $projectId)->delete();
            Log::info('IAHBudgetDetailsController@store - Deleted existing budget records', [
                'project_id' => $projectId
            ]);

            // 2️⃣ Insert new budget details
            $particulars = $request->input('particular', []);
            $amounts     = $request->input('amount', []);
            $familyContribution = $request->input('family_contribution', 0);
            $totalExpenses      = array_sum($amounts);

            for ($i = 0; $i < count($particulars); $i++) {
                if (!empty($particulars[$i]) && !empty($amounts[$i])) {
                    ProjectIAHBudgetDetails::create([
                        'project_id'        => $projectId,
                        'particular'        => $particulars[$i],
                        'amount'            => $amounts[$i],
                        'total_expenses'    => $totalExpenses,
                        'family_contribution' => $familyContribution,
                        'amount_requested'    => $totalExpenses - $familyContribution,
                    ]);
                }
            }

            DB::commit();
            Log::info('IAHBudgetDetailsController@store - Success: All budget details stored', [
                'project_id' => $projectId
            ]);

            return response()->json(['message' => 'IAH budget details saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHBudgetDetailsController@store - Error saving budget details', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to save IAH budget details.'], 500);
        }
    }

    /**
     * Update budget details for a project (same destructive approach but with dedicated logs).
     */
    public function update(Request $request, $projectId)
    {
        Log::info('IAHBudgetDetailsController@update - Start', [
            'project_id' => $projectId,
            'request_data' => $request->all()
        ]);

        DB::beginTransaction();
        try {
            // 1️⃣ Delete old budget details
            Log::info('IAHBudgetDetailsController@update - Deleting existing budget records', ['project_id' => $projectId]);
            ProjectIAHBudgetDetails::where('project_id', $projectId)->delete();

            // 2️⃣ Insert fresh data
            $particulars = $request->input('particular', []);
            $amounts     = $request->input('amount', []);
            $familyContribution = $request->input('family_contribution', 0);
            $totalExpenses      = array_sum($amounts);

            Log::info('IAHBudgetDetailsController@update - Inserting new budget records', [
                'particulars_count' => count($particulars),
                'family_contribution' => $familyContribution,
                'total_expenses'      => $totalExpenses
            ]);

            for ($i = 0; $i < count($particulars); $i++) {
                if (!empty($particulars[$i]) && !empty($amounts[$i])) {
                    ProjectIAHBudgetDetails::create([
                        'project_id'        => $projectId,
                        'particular'        => $particulars[$i],
                        'amount'            => $amounts[$i],
                        'total_expenses'    => $totalExpenses,
                        'family_contribution' => $familyContribution,
                        'amount_requested'    => $totalExpenses - $familyContribution,
                    ]);
                }
            }

            DB::commit();
            Log::info('IAHBudgetDetailsController@update - Success: Budget details updated', [
                'project_id' => $projectId
            ]);

            return response()->json(['message' => 'IAH budget details updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHBudgetDetailsController@update - Error updating budget details', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to update IAH budget details.'], 500);
        }
    }

    /**
     * Fetch budget details for a project (read-only).
     */
    // public function show($projectId)
    // {
    //     try {
    //         Log::info('IAHBudgetDetailsController@show - Fetching IAH budget details', [
    //             'project_id' => $projectId
    //         ]);
    //         $budgetDetails = ProjectIAHBudgetDetails::where('project_id', $projectId)->get();
    //         return response()->json($budgetDetails, 200);
    //     } catch (\Exception $e) {
    //         Log::error('IAHBudgetDetailsController@show - Error fetching budget details', [
    //             'project_id' => $projectId,
    //             'error'      => $e->getMessage(),
    //         ]);
    //         return response()->json(['error' => 'Failed to fetch IAH budget details.'], 500);
    //     }
    // }
    public function show($projectId)
    {
        try {
            Log::info('IAHBudgetDetailsController@show - Fetching IAH budget details', [
                'project_id' => $projectId
            ]);

            // Fetching only the first record (assuming one budget per project)
            $budgetDetail = ProjectIAHBudgetDetails::where('project_id', $projectId)->first();

            if (!$budgetDetail) {
                return response()->json(['error' => 'No budget details found.'], 404);
            }

            return response()->json($budgetDetail, 200);
        } catch (\Exception $e) {
            Log::error('IAHBudgetDetailsController@show - Error fetching budget details', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to fetch IAH budget details.'], 500);
        }
    }

    /**
     * Return data for editing (usually for a form).
     */
    public function edit($projectId)
    {
        try {
            Log::info('IAHBudgetDetailsController@edit - Start', [
                'project_id' => $projectId
            ]);

            $budgetDetails = ProjectIAHBudgetDetails::where('project_id', $projectId)->get();
            Log::info('IAHBudgetDetailsController@edit - Fetched existing data', [
                'count' => $budgetDetails->count(),
                'data'  => $budgetDetails->toArray()
            ]);

            // Transform as needed for your front-end
            $mappedDetails = $budgetDetails->map(function ($budget) use ($budgetDetails) {
                return [
                    'particular'          => $budget->particular,
                    'amount'              => $budget->amount,
                    'family_contribution' => $budgetDetails->first()->family_contribution ?? 0,
                    'amount_requested'    => ($budgetDetails->sum('amount') ?? 0)
                                              - ($budgetDetails->first()->family_contribution ?? 0),
                ];
            });

            Log::info('IAHBudgetDetailsController@edit - Mapped data ready for form', [
                'mapped_details' => $mappedDetails
            ]);

            return $mappedDetails;
        } catch (\Exception $e) {
            Log::error('IAHBudgetDetailsController@edit - Error editing budget details', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Delete budget details for a project.
     */
    public function destroy($projectId)
    {
        Log::info('IAHBudgetDetailsController@destroy - Start', ['project_id' => $projectId]);

        DB::beginTransaction();
        try {
            ProjectIAHBudgetDetails::where('project_id', $projectId)->delete();
            DB::commit();

            Log::info('IAHBudgetDetailsController@destroy - Budget details deleted', [
                'project_id' => $projectId
            ]);
            return response()->json(['message' => 'IAH budget details deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHBudgetDetailsController@destroy - Error deleting budget details', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to delete IAH budget details.'], 500);
        }
    }
}
