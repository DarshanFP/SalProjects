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

        $parentData = [
            'total_expenses' => $headerData['total_expenses'] ?? null,
            'expected_scholarship_govt' => $headerData['expected_scholarship_govt'] ?? null,
            'support_other_sources' => $headerData['support_other_sources'] ?? null,
            'beneficiary_contribution' => $headerData['beneficiary_contribution'] ?? null,
            'balance_requested' => $headerData['balance_requested'] ?? null,
        ];
        $particulars = is_array($data['particulars'] ?? null) ? $data['particulars'] : [];
        $amounts = is_array($data['amounts'] ?? null) ? $data['amounts'] : [];

        if (! $this->isIESExpensesMeaningfullyFilled($parentData, $particulars, $amounts)) {
            Log::info('IESExpensesController@store - Section absent or empty; skipping mutation', [
                'project_id' => $projectId,
            ]);
            return response()->json(['message' => 'IES estimated expenses saved successfully.'], 200);
        }

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
                // M2.5: Allow 0 for amount; skip only when null or '' (do not use empty() on numeric)
                if (trim((string) $particular) !== '' && $amount !== null && $amount !== '') {
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

    /**
     * M1 Guard: true when at least one parent field is meaningful or at least one child row has meaningful particular/amount.
     */
    private function isIESExpensesMeaningfullyFilled(array $parentData, array $particulars, array $amounts): bool
    {
        foreach ($parentData as $value) {
            if ($this->meaningfulNumeric($value)) {
                return true;
            }
        }

        if ($particulars === [] && $amounts === []) {
            return false;
        }

        $maxIndex = max(
            is_array($particulars) ? count($particulars) - 1 : -1,
            is_array($amounts) ? count($amounts) - 1 : -1
        );
        for ($i = 0; $i <= $maxIndex; $i++) {
            $particular = $particulars[$i] ?? null;
            $amount = $amounts[$i] ?? null;
            if ($this->meaningfulString($particular) || $this->meaningfulNumeric($amount)) {
                return true;
            }
        }

        return false;
    }

    private function meaningfulString($value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private function meaningfulNumeric($value): bool
    {
        return $value !== null && $value !== '' && is_numeric($value);
    }
}
