<?php

namespace App\Http\Controllers\Projects\IES;

use App\Http\Controllers\Controller;
use App\Support\Normalization\ArrayToScalarNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IES\ProjectIESExpenses;
use App\Models\OldProjects\IES\ProjectIESExpenseDetail;
use App\Models\OldProjects\Project;
use App\Services\Budget\BudgetSyncService;
use App\Services\Budget\BudgetSyncGuard;
use App\Services\Budget\BudgetAuditLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Projects\IES\StoreIESExpensesRequest;
use App\Http\Requests\Projects\IES\UpdateIESExpensesRequest;

class IESExpensesController extends Controller
{
    /** Phase 3: User-facing message when budget edit is blocked (project approved). */
    private const BUDGET_LOCKED_MESSAGE = 'Project is approved. Budget edits are locked until the project is reverted.';

    // Store or update expenses for a project
    public function store(FormRequest $request, $projectId)
    {
        // Phase 3: Block budget edits when project is approved
        $project = Project::where('project_id', $projectId)->first();
        if ($project && !BudgetSyncGuard::canEditBudget($project)) {
            BudgetAuditLogger::logBlockedEditAttempt(
                $projectId,
                Auth::id(),
                'ies_expenses_store',
                $project->status ?? ''
            );
            return response()->json(['error' => self::BUDGET_LOCKED_MESSAGE], 403);
        }

        $fillableHeader = array_diff(
            (new ProjectIESExpenses())->getFillable(),
            ['project_id', 'IES_expense_id']
        );
        $allKeys = array_merge($fillableHeader, ['particulars', 'amounts']);
        $data = $request->only($allKeys);
        $headerData = ArrayToScalarNormalizer::forFillable($data, $fillableHeader);

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
            $projectExpenses->fill($headerData);
            $projectExpenses->save();

            // Store each particular and amount as a detail (scalar coercion prevents "Array to string conversion")
            $particulars = is_array($data['particulars'] ?? null) ? ($data['particulars'] ?? []) : (isset($data['particulars']) && $data['particulars'] !== '' ? [$data['particulars']] : []);
            $amounts = is_array($data['amounts'] ?? null) ? ($data['amounts'] ?? []) : (isset($data['amounts']) && $data['amounts'] !== '' ? [$data['amounts']] : []);

            for ($i = 0; $i < count($particulars); $i++) {
                $particular = is_array($particulars[$i] ?? null) ? (reset($particulars[$i]) ?? '') : ($particulars[$i] ?? '');
                $amount = is_array($amounts[$i] ?? null) ? (reset($amounts[$i]) ?? '') : ($amounts[$i] ?? '');
                if (!empty($particular) && !empty($amount)) {
                    $projectExpenses->expenseDetails()->create([
                        'particular' => $particular,
                        'amount' => $amount,
                    ]);
                }
            }

            DB::commit();

            // Phase 2: Sync project-level budget fields for pre-approval projects (feature-flagged)
            $project = Project::where('project_id', $projectId)->first();
            if ($project) {
                app(BudgetSyncService::class)->syncFromTypeSave($project);
            }

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
    public function update(FormRequest $request, $projectId)
    {
        // Reuse store logic
        return $this->store($request, $projectId);
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
