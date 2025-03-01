<?php

namespace App\Http\Controllers\Projects\IIES;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IIES\ProjectIIESExpenses;
use App\Models\OldProjects\IIES\ProjectIIESExpenseDetail;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IIESExpensesController extends Controller
{
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();

        try {
            Log::info('Storing IIES estimated expenses', ['project_id' => $projectId]);

            // Delete existing expenses
            $existingExpenses = ProjectIIESExpenses::where('project_id', $projectId)->first();
            if ($existingExpenses) {
                $existingExpenses->expenseDetails()->delete();
                $existingExpenses->delete();
            }

            // Create new entry
            $projectExpenses = new ProjectIIESExpenses();
            $projectExpenses->project_id = $projectId;
            $projectExpenses->iies_total_expenses = $request->input('iies_total_expenses');
            $projectExpenses->iies_expected_scholarship_govt = $request->input('iies_expected_scholarship_govt');
            $projectExpenses->iies_support_other_sources = $request->input('iies_support_other_sources');
            $projectExpenses->iies_beneficiary_contribution = $request->input('iies_beneficiary_contribution');
            $projectExpenses->iies_balance_requested = $request->input('iies_balance_requested');
            $projectExpenses->save();

            foreach ($request->input('iies_particulars', []) as $index => $particular) {
                if (!empty($particular) && !empty($request->input('iies_amounts')[$index])) {
                    $projectExpenses->expenseDetails()->create([
                        'iies_particular' => $particular,
                        'iies_amount' => $request->input('iies_amounts')[$index],
                    ]);
                }
            }

            DB::commit();
            return response()->json(['message' => 'IIES estimated expenses saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to save IIES estimated expenses.'], 500);
        }
    }

    // public function show($projectId)
    // {
    //     return ProjectIIESExpenses::with('expenseDetails')->where('project_id', $projectId)->firstOrFail();
    // }

    public function show($projectId)
    {
        try {
            Log::info('Fetching IIES Expenses for show view', ['project_id' => $projectId]);

            // Ensure the project exists
            $project = Project::where('project_id', $projectId)->firstOrFail();

            // Fetch the IIES Expenses record with its details
            $iiesExpenses = ProjectIIESExpenses::with('expenseDetails')
                ->where('project_id', $projectId)
                ->first();

            // If no record exists, create an empty instance
            if (!$iiesExpenses) {
                $iiesExpenses = new ProjectIIESExpenses([
                    'project_id' => $projectId,
                    'iies_total_expenses'            => null,
                    'iies_expected_scholarship_govt' => null,
                    'iies_support_other_sources'     => null,
                    'iies_beneficiary_contribution'  => null,
                    'iies_balance_requested'         => null,
                ]);
            }

            Log::info('IIESExpensesController@show - Retrieved Data', [
                'project_id' => $projectId,
                'IIESExpenses' => $iiesExpenses,
                'Expense Details' => $iiesExpenses->expenseDetails ?? []
            ]);

            return $iiesExpenses;

        } catch (\Exception $e) {
            Log::error('Error fetching IIES Expenses for show', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }


     /**
     * Edit the IIES expenses for a given project
     * (just fetch & return the single record).
     *
     * @param  string  $projectId
     * @return \App\Models\OldProjects\IIES\ProjectIIESExpenses|\Illuminate\Http\JsonResponse
     */

     public function edit($projectId)
     {
         try {
             Log::info('Fetching IIES Expenses for editing', ['project_id' => $projectId]);

             // Make sure the project exists:
             $project = Project::where('project_id', $projectId)->firstOrFail();

             // Grab the single IIESExpenses record (with expenseDetails) if it exists
             $iiesExpenses = ProjectIIESExpenses::with('expenseDetails')
                 ->where('project_id', $projectId)
                 ->first();

             // If no record, create an empty instance (so you can fill in the form)
             if (!$iiesExpenses) {
                 $iiesExpenses = new ProjectIIESExpenses([
                     'project_id' => $projectId,
                     'iies_total_expenses'            => null,
                     'iies_expected_scholarship_govt' => null,
                     'iies_support_other_sources'      => null,
                     'iies_beneficiary_contribution'   => null,
                     'iies_balance_requested'          => null,
                 ]);
             }

             Log::info('IIESExpenses Controller - Fetched IIES Expenses for editing', [
                 'project_id'   => $projectId,
                 'IIESExpenses' => $iiesExpenses,
             ]);

             // Return the model (can be passed to the Blade)
             return $iiesExpenses;

         } catch (\Exception $e) {
             Log::error('Error fetching IIES Expenses for edit', [
                 'project_id' => $projectId,
                 'error'      => $e->getMessage(),
             ]);
             return null; // Return null if an error occurs
         }
     }


        // Update IIES estimated expenses

public function update(Request $request, $projectId)
{
    return $this->store($request, $projectId);
}


    public function destroy($projectId)
    {
        DB::beginTransaction();

        try {
            $expenses = ProjectIIESExpenses::where('project_id', $projectId)->firstOrFail();
            $expenses->expenseDetails()->delete();
            $expenses->delete();

            DB::commit();
            return response()->json(['message' => 'IIES estimated expenses deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete IIES estimated expenses.'], 500);
        }
    }
}
