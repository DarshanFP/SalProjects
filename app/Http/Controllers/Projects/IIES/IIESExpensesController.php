<?php

namespace App\Http\Controllers\Projects\IIES;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IIES\ProjectIIESExpenses;
use App\Models\OldProjects\IIES\ProjectIIESExpenseDetail;
use App\Models\OldProjects\Project;
use App\Services\Budget\BudgetSyncService;
use App\Services\Budget\BudgetSyncGuard;
use App\Services\Budget\BudgetAuditLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Projects\IIES\StoreIIESExpensesRequest;
use App\Http\Requests\Projects\IIES\UpdateIIESExpensesRequest;

class IIESExpensesController extends Controller
{
    /** Phase 3: User-facing message when budget edit is blocked (project approved). */
    private const BUDGET_LOCKED_MESSAGE = 'Project is approved. Budget edits are locked until the project is reverted.';

    public function store(FormRequest $request, $projectId)
    {
        // Phase 3: Block budget edits when project is approved
        $project = Project::where('project_id', $projectId)->first();
        if ($project && !BudgetSyncGuard::canEditBudget($project)) {
            BudgetAuditLogger::logBlockedEditAttempt(
                $projectId,
                Auth::id(),
                'iies_expenses_store',
                $project->status ?? ''
            );
            return response()->json(['error' => self::BUDGET_LOCKED_MESSAGE], 403);
        }

        // Use all() to get all form data including particulars[], amounts[] arrays
        // These fields are not in StoreProjectRequest validation rules
        $validated = $request->all();

        DB::beginTransaction();

        try {
            Log::info('Storing IIES estimated expenses', ['project_id' => $projectId]);

            // Delete existing expenses
            $existingExpenses = ProjectIIESExpenses::where('project_id', $projectId)->first();
            if ($existingExpenses) {
                $existingExpenses->expenseDetails()->delete();
                $existingExpenses->delete();
            }

            // Create new entry (Phase 2: server-side defaults for NOT NULL decimal columns)
            $projectExpenses = new ProjectIIESExpenses();
            $projectExpenses->project_id = $projectId;
            $projectExpenses->iies_total_expenses = $validated['iies_total_expenses'] ?? 0;
            $projectExpenses->iies_expected_scholarship_govt = $validated['iies_expected_scholarship_govt'] ?? 0;
            $projectExpenses->iies_support_other_sources = $validated['iies_support_other_sources'] ?? 0;
            $projectExpenses->iies_beneficiary_contribution = $validated['iies_beneficiary_contribution'] ?? 0;
            $projectExpenses->iies_balance_requested = $validated['iies_balance_requested'] ?? 0;
            $projectExpenses->save();

            $particulars = $validated['iies_particulars'] ?? [];
            $amounts = $validated['iies_amounts'] ?? [];

            foreach ($particulars as $index => $particular) {
                if (!empty($particular) && !empty($amounts[$index] ?? null)) {
                    $projectExpenses->expenseDetails()->create([
                        'iies_particular' => $particular,
                        'iies_amount' => $amounts[$index],
                    ]);
                }
            }

            DB::commit();

            // Phase 2: Sync project-level budget fields for pre-approval projects (feature-flagged)
            $project = Project::where('project_id', $projectId)->first();
            if ($project) {
                app(BudgetSyncService::class)->syncFromTypeSave($project);
            }

            Log::info('IIESExpensesController@store - Success', [
                'project_id' => $projectId,
                'expense_id' => $projectExpenses->IIES_expense_id ?? null,
                'total_expenses' => $projectExpenses->iies_total_expenses ?? null,
                'balance_requested' => $projectExpenses->iies_balance_requested ?? null,
                'details_count' => count($particulars)
            ]);
            return response()->json(['message' => 'IIES estimated expenses saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IIESExpensesController@store - Error', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to save IIES estimated expenses.',
                'message' => $e->getMessage()
            ], 500);
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

public function update(\Illuminate\Foundation\Http\FormRequest $request, $projectId)
{
    // Reuse store logic
    return $this->store($request, $projectId);
}


    public function destroy($projectId)
    {
        DB::beginTransaction();

        try {
            Log::info('IIESExpensesController@destroy - Start', ['project_id' => $projectId]);

            $expenses = ProjectIIESExpenses::where('project_id', $projectId)->firstOrFail();
            $expenses->expenseDetails()->delete();
            $expenses->delete();

            DB::commit();
            Log::info('IIESExpensesController@destroy - Success', ['project_id' => $projectId]);
            return response()->json(['message' => 'IIES estimated expenses deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IIESExpensesController@destroy - Error', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to delete IIES estimated expenses.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
