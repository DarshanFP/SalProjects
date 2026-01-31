<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IAH\ProjectIAHBudgetDetails;
use App\Models\OldProjects\Project;
use App\Services\Budget\BudgetSyncService;
use App\Services\Budget\BudgetSyncGuard;
use App\Services\Budget\BudgetAuditLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Projects\IAH\StoreIAHBudgetDetailsRequest;
use App\Http\Requests\Projects\IAH\UpdateIAHBudgetDetailsRequest;

class IAHBudgetDetailsController extends Controller
{
    /** Phase 3: User-facing message when budget edit is blocked (project approved). */
    private const BUDGET_LOCKED_MESSAGE = 'Project is approved. Budget edits are locked until the project is reverted.';

    /**
     * Store budget details for a project (creates fresh entries after deleting old ones).
     */
    public function store(FormRequest $request, $projectId)
    {
        // Phase 3: Block budget edits when project is approved
        $project = Project::where('project_id', $projectId)->first();
        if ($project && !BudgetSyncGuard::canEditBudget($project)) {
            BudgetAuditLogger::logBlockedEditAttempt(
                $projectId,
                Auth::id(),
                'iah_budget_store',
                $project->status ?? ''
            );
            return response()->json(['error' => self::BUDGET_LOCKED_MESSAGE], 403);
        }

        // Use all() to get all form data including budget detail arrays
        // These fields are not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validated = $request->all();

        Log::info('IAHBudgetDetailsController@store - Start', [
            'project_id' => $projectId
        ]);

        DB::beginTransaction();
        try {
            // 1️⃣ Delete old budget details for this project
            ProjectIAHBudgetDetails::where('project_id', $projectId)->delete();
            Log::info('IAHBudgetDetailsController@store - Deleted existing budget records', [
                'project_id' => $projectId
            ]);

            // 2️⃣ Insert new budget details
            $particulars = $validated['particular'] ?? [];
            $amounts     = $validated['amount'] ?? [];
            $familyContribution = $validated['family_contribution'] ?? 0;
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

            // Phase 2: Sync project-level budget fields for pre-approval projects (feature-flagged)
            $project = Project::where('project_id', $projectId)->first();
            if ($project) {
                app(BudgetSyncService::class)->syncFromTypeSave($project);
            }

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
    public function update(FormRequest $request, $projectId)
    {
        // Phase 3: Block budget edits when project is approved
        $project = Project::where('project_id', $projectId)->first();
        if ($project && !BudgetSyncGuard::canEditBudget($project)) {
            BudgetAuditLogger::logBlockedEditAttempt(
                $projectId,
                Auth::id(),
                'iah_budget_update',
                $project->status ?? ''
            );
            return response()->json(['error' => self::BUDGET_LOCKED_MESSAGE], 403);
        }

        // Use all() to get all form data including budget detail arrays
        // These fields are not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validated = $request->all();

        Log::info('IAHBudgetDetailsController@update - Start', [
            'project_id' => $projectId
        ]);

        DB::beginTransaction();
        try {
            // 1️⃣ Delete old budget details
            Log::info('IAHBudgetDetailsController@update - Deleting existing budget records', ['project_id' => $projectId]);
            ProjectIAHBudgetDetails::where('project_id', $projectId)->delete();

            // 2️⃣ Insert fresh data
            $particulars = $validated['particular'] ?? [];
            $amounts     = $validated['amount'] ?? [];
            $familyContribution = $validated['family_contribution'] ?? 0;
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

            // Phase 2: Sync project-level budget fields for pre-approval projects (feature-flagged)
            $project = Project::where('project_id', $projectId)->first();
            if ($project) {
                app(BudgetSyncService::class)->syncFromTypeSave($project);
            }

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
    public function show($projectId)
    {
        try {
            Log::info('IAHBudgetDetailsController@show - Fetching IAH budget details', [
                'project_id' => $projectId
            ]);

            // Fetch all budget details for the project
            $budgetDetails = ProjectIAHBudgetDetails::where('project_id', $projectId)->get();

            // Return the model collection directly, not a JSON response
            return $budgetDetails;
        } catch (\Exception $e) {
            Log::error('IAHBudgetDetailsController@show - Error fetching budget details', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);
            return collect([]); // Return empty collection instead of JSON error
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
