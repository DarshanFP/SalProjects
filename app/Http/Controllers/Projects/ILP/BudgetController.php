<?php

namespace App\Http\Controllers\Projects\ILP;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\ILP\ProjectILPBudget;
use App\Models\OldProjects\Project;
use App\Services\Budget\BudgetSyncService;
use App\Services\Budget\BudgetSyncGuard;
use App\Services\Budget\BudgetAuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Projects\ILP\StoreILPBudgetRequest;
use App\Http\Requests\Projects\ILP\UpdateILPBudgetRequest;

class BudgetController extends Controller
{
    /** Phase 3: User-facing message when budget edit is blocked (project approved). */
    private const BUDGET_LOCKED_MESSAGE = 'Project is approved. Budget edits are locked until the project is reverted.';

    // Store or update budget
    public function store(FormRequest $request, $projectId)
    {
        // Phase 3: Block budget edits when project is approved
        $project = Project::where('project_id', $projectId)->first();
        if ($project && !BudgetSyncGuard::canEditBudget($project)) {
            BudgetAuditLogger::logBlockedEditAttempt(
                $projectId,
                Auth::id(),
                'ilp_budget_store',
                $project->status ?? ''
            );
            return response()->json(['error' => self::BUDGET_LOCKED_MESSAGE], 403);
        }

        $fillable = ['budget_desc', 'cost', 'beneficiary_contribution', 'amount_requested'];
        $data = $request->only($fillable);

        // Scalar-to-array normalization (same as create loop)
        $budgetDescs = is_array($data['budget_desc'] ?? null) ? ($data['budget_desc'] ?? []) : (isset($data['budget_desc']) && $data['budget_desc'] !== '' ? [$data['budget_desc']] : []);
        $costs       = is_array($data['cost'] ?? null) ? ($data['cost'] ?? []) : (isset($data['cost']) && $data['cost'] !== '' ? [$data['cost']] : []);

        if (! $this->isILPBudgetMeaningfullyFilled($budgetDescs, $costs)) {
            Log::info('ILP BudgetController@store - Section absent or empty; skipping mutation', [
                'project_id' => $projectId,
            ]);

            return response()->json([
                'message' => 'Budget saved successfully.',
            ], 200);
        }

        DB::beginTransaction();
        try {
            Log::info('Storing ILP Budget', ['project_id' => $projectId]);

            // Delete existing budget rows and insert updated data
            ProjectILPBudget::where('project_id', $projectId)->delete();

            $beneficiaryContribution = is_array($data['beneficiary_contribution'] ?? null) ? (reset($data['beneficiary_contribution']) ?? null) : ($data['beneficiary_contribution'] ?? null);
            $amountRequested         = is_array($data['amount_requested'] ?? null) ? (reset($data['amount_requested']) ?? null) : ($data['amount_requested'] ?? null);

            foreach ($budgetDescs as $index => $description) {
                $budgetDesc = is_array($description ?? null) ? (reset($description) ?? '') : ($description ?? '');
                $cost       = is_array($costs[$index] ?? null) ? (reset($costs[$index]) ?? null) : ($costs[$index] ?? null);
                ProjectILPBudget::create([
                    'project_id' => $projectId,
                    'budget_desc' => $budgetDesc,
                    'cost' => $cost,
                    'beneficiary_contribution' => $beneficiaryContribution,
                    'amount_requested' => $amountRequested,
                ]);
            }

            DB::commit();

            // Phase 2: Sync project-level budget fields for pre-approval projects (feature-flagged)
            $project = Project::where('project_id', $projectId)->first();
            if ($project) {
                app(BudgetSyncService::class)->syncFromTypeSave($project);
            }

            Log::info('ILP Budget saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Budget saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving ILP Budget', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save budget.'], 500);
        }
    }

    // Show budget for a project
    // public function show($projectId)
    // {
    //     try {
    //         Log::info('Fetching ILP Budget', ['project_id' => $projectId]);

    //         $budgets = ProjectILPBudget::where('project_id', $projectId)->get();
    //         return response()->json($budgets, 200);
    //     } catch (\Exception $e) {
    //         Log::error('Error fetching ILP Budget', ['error' => $e->getMessage()]);
    //         return response()->json(['error' => 'Failed to fetch budget.'], 500);
    //     }
    // }
    public function show($projectId)
{
    try {
        Log::info('Fetching ILP Budget', ['project_id' => $projectId]);

        $budgets = ProjectILPBudget::where('project_id', $projectId)->get();

        return [
            'budgets' => $budgets,
            'total_amount' => $budgets->sum('cost'),
            'beneficiary_contribution' => $budgets->first()->beneficiary_contribution ?? 0,
            'amount_requested' => $budgets->first()->amount_requested ?? 0,
        ];
    } catch (\Exception $e) {
        Log::error('Error fetching ILP Budget', ['error' => $e->getMessage()]);

        return [
            'budgets' => collect([]), // Return empty collection to prevent errors
            'total_amount' => 0,
            'beneficiary_contribution' => 0,
            'amount_requested' => 0,
        ];
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

            return [
                'budgets' => $budgets,
                'total_amount' => $total_amount,
                'beneficiary_contribution' => $beneficiary_contribution,
                'amount_requested' => $amount_requested,
            ];
        } catch (\Exception $e) {
            Log::error('Error editing ILP Budget', ['error' => $e->getMessage()]);
            return null;
        }
    }
    public function update(FormRequest $request, $projectId)
    {
        // Phase 3: Block budget edits when project is approved (same guard as store() – defensive symmetry)
        $project = Project::where('project_id', $projectId)->first();
        if ($project && !BudgetSyncGuard::canEditBudget($project)) {
            BudgetAuditLogger::logBlockedEditAttempt(
                $projectId,
                Auth::id(),
                'ilp_budget_update',
                $project->status ?? ''
            );
            return response()->json(['error' => self::BUDGET_LOCKED_MESSAGE], 403);
        }

        return $this->store($request, $projectId);
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

    private function isILPBudgetMeaningfullyFilled(
        array $budgetDescs,
        array $costs
    ): bool {
        $rowCount = count($budgetDescs);

        for ($i = 0; $i < $rowCount; $i++) {
            $desc = $budgetDescs[$i] ?? null;
            $cost = $costs[$i] ?? null;

            if (
                trim((string) $desc) !== '' &&
                $cost !== null &&
                $cost !== ''
            ) {
                return true;
            }
        }

        return false;
    }
}
