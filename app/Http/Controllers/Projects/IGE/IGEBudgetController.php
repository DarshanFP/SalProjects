<?php

namespace App\Http\Controllers\Projects\IGE;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IGE\ProjectIGEBudget;
use App\Models\OldProjects\Project;
use App\Services\Budget\BudgetSyncService;
use App\Services\Budget\BudgetSyncGuard;
use App\Services\Budget\BudgetAuditLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Projects\IGE\StoreIGEBudgetRequest;
use App\Http\Requests\Projects\IGE\UpdateIGEBudgetRequest;

class IGEBudgetController extends Controller
{
    /** Phase 3: User-facing message when budget edit is blocked (project approved). */
    private const BUDGET_LOCKED_MESSAGE = 'Project is approved. Budget edits are locked until the project is reverted.';

    // Store or update budget for a project
    public function store(FormRequest $request, $projectId)
    {
        // Phase 3: Block budget edits when project is approved
        $project = Project::where('project_id', $projectId)->first();
        if ($project && !BudgetSyncGuard::canEditBudget($project)) {
            BudgetAuditLogger::logBlockedEditAttempt(
                $projectId,
                Auth::id(),
                'ige_budget_store',
                $project->status ?? ''
            );
            return redirect()->back()->with('error', self::BUDGET_LOCKED_MESSAGE);
        }

        // Use all() to get all form data including name[], study_proposed[], etc. arrays
        // These fields are not in StoreProjectRequest validation rules
        $validated = $request->all();

        DB::beginTransaction();
        try {
            Log::info('Storing IGE budget', ['project_id' => $projectId]);

            // First, delete all existing budget records for the project
            ProjectIGEBudget::where('project_id', $projectId)->delete();

            // Insert new budget entries
            $names = $validated['name'] ?? [];
            $studiesProposed = $validated['study_proposed'] ?? [];
            $collegeFees = $validated['college_fees'] ?? [];
            $hostelFees = $validated['hostel_fees'] ?? [];
            $totalAmounts = $validated['total_amount'] ?? [];
            $scholarshipEligibility = $validated['scholarship_eligibility'] ?? [];
            $familyContributions = $validated['family_contribution'] ?? [];
            $amountRequested = $validated['amount_requested'] ?? [];

            // Store each budget row
            foreach ($names as $i => $name) {
                if (!empty($name)) {
                    ProjectIGEBudget::create([
                        'project_id' => $projectId,
                        'name' => $name,
                        'study_proposed' => $studiesProposed[$i] ?? null,
                        'college_fees' => $collegeFees[$i] ?? null,
                        'hostel_fees' => $hostelFees[$i] ?? null,
                        'total_amount' => $totalAmounts[$i] ?? null,
                        'scholarship_eligibility' => $scholarshipEligibility[$i] ?? null,
                        'family_contribution' => $familyContributions[$i] ?? null,
                        'amount_requested' => $amountRequested[$i] ?? null,
                    ]);
                }
            }

            DB::commit();

            // Phase 2: Sync project-level budget fields for pre-approval projects (feature-flagged)
            $project = Project::where('project_id', $projectId)->first();
            if ($project) {
                app(BudgetSyncService::class)->syncFromTypeSave($project);
            }

            Log::info('IGE budget saved successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'IGE budget saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IGE budget', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to save IGE budget.');
        }
    }

    // Show budget for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IGE budget', ['project_id' => $projectId]);

            $budget = ProjectIGEBudget::where('project_id', $projectId)->get();

            if ($budget->isEmpty()) {
                Log::warning('No IGE budget data found', ['project_id' => $projectId]);
                return collect(); // Return an empty collection if no data
            }

            return $budget; // Return the collection of budget records
        } catch (\Exception $e) {
            Log::error('Error fetching IGE budget', ['error' => $e->getMessage()]);
            return collect(); // Return an empty collection on error
        }
    }


    // Edit budget for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IGE budget', ['project_id' => $projectId]);

            $budget = ProjectIGEBudget::where('project_id', $projectId)->get();

            if (!$budget instanceof \Illuminate\Database\Eloquent\Collection) {
                $budget = collect();
            }

            return $budget; // Return data instead of a view
        } catch (\Exception $e) {
            Log::error('Error editing IGE budget', ['error' => $e->getMessage()]);
            return collect(); // Return an empty collection on error
        }
    }

    // Update budget for a project
    public function update(FormRequest $request, $projectId)
    {
        // Reuse store logic
        return $this->store($request, $projectId);
    }

    // Delete budget for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IGE budget', ['project_id' => $projectId]);

            ProjectIGEBudget::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('IGE budget deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'IGE budget deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IGE budget', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete IGE budget.');
        }
    }
}
