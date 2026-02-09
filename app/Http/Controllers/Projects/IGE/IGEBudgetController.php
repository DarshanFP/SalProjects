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

        $fillable = ['name', 'study_proposed', 'college_fees', 'hostel_fees', 'total_amount', 'scholarship_eligibility', 'family_contribution', 'amount_requested'];
        $data = $request->only($fillable);

        // Scalar-to-array normalization; per-value scalar coercion
        $names = is_array($data['name'] ?? null) ? ($data['name'] ?? []) : (isset($data['name']) && $data['name'] !== '' ? [$data['name']] : []);
        $studiesProposed = is_array($data['study_proposed'] ?? null) ? ($data['study_proposed'] ?? []) : (isset($data['study_proposed']) ? [$data['study_proposed']] : []);
        $collegeFees = is_array($data['college_fees'] ?? null) ? ($data['college_fees'] ?? []) : (isset($data['college_fees']) ? [$data['college_fees']] : []);
        $hostelFees = is_array($data['hostel_fees'] ?? null) ? ($data['hostel_fees'] ?? []) : (isset($data['hostel_fees']) ? [$data['hostel_fees']] : []);
        $totalAmounts = is_array($data['total_amount'] ?? null) ? ($data['total_amount'] ?? []) : (isset($data['total_amount']) ? [$data['total_amount']] : []);
        $scholarshipEligibility = is_array($data['scholarship_eligibility'] ?? null) ? ($data['scholarship_eligibility'] ?? []) : (isset($data['scholarship_eligibility']) ? [$data['scholarship_eligibility']] : []);
        $familyContributions = is_array($data['family_contribution'] ?? null) ? ($data['family_contribution'] ?? []) : (isset($data['family_contribution']) ? [$data['family_contribution']] : []);
        $amountRequested = is_array($data['amount_requested'] ?? null) ? ($data['amount_requested'] ?? []) : (isset($data['amount_requested']) ? [$data['amount_requested']] : []);

        DB::beginTransaction();
        try {
            Log::info('Storing IGE budget', ['project_id' => $projectId]);

            // First, delete all existing budget records for the project
            ProjectIGEBudget::where('project_id', $projectId)->delete();

            foreach ($names as $i => $name) {
                $nameVal = is_array($name ?? null) ? (reset($name) ?? '') : ($name ?? '');
                if (!empty($nameVal)) {
                    $study = is_array($studiesProposed[$i] ?? null) ? (reset($studiesProposed[$i]) ?? null) : ($studiesProposed[$i] ?? null);
                    $college = is_array($collegeFees[$i] ?? null) ? (reset($collegeFees[$i]) ?? null) : ($collegeFees[$i] ?? null);
                    $hostel = is_array($hostelFees[$i] ?? null) ? (reset($hostelFees[$i]) ?? null) : ($hostelFees[$i] ?? null);
                    $total = is_array($totalAmounts[$i] ?? null) ? (reset($totalAmounts[$i]) ?? null) : ($totalAmounts[$i] ?? null);
                    $scholarship = is_array($scholarshipEligibility[$i] ?? null) ? (reset($scholarshipEligibility[$i]) ?? null) : ($scholarshipEligibility[$i] ?? null);
                    $family = is_array($familyContributions[$i] ?? null) ? (reset($familyContributions[$i]) ?? null) : ($familyContributions[$i] ?? null);
                    $amount = is_array($amountRequested[$i] ?? null) ? (reset($amountRequested[$i]) ?? null) : ($amountRequested[$i] ?? null);
                    ProjectIGEBudget::create([
                        'project_id' => $projectId,
                        'name' => $nameVal,
                        'study_proposed' => $study,
                        'college_fees' => $college,
                        'hostel_fees' => $hostel,
                        'total_amount' => $total,
                        'scholarship_eligibility' => $scholarship,
                        'family_contribution' => $family,
                        'amount_requested' => $amount,
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
