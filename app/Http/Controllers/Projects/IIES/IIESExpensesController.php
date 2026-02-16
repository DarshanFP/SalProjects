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
use App\Http\Requests\Projects\IIES\StoreIIESExpensesRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class IIESExpensesController extends Controller
{
    private const BUDGET_LOCKED_MESSAGE = 'Project is approved. Budget edits are locked until the project is reverted.';

    public function store(FormRequest $request, $projectId)
    {
        $project = Project::where('project_id', $projectId)->first();
        if ($project && ! BudgetSyncGuard::canEditBudget($project)) {
            BudgetAuditLogger::logBlockedEditAttempt(
                $projectId,
                Auth::id(),
                'iies_expenses_store',
                $project->status ?? ''
            );
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json(['error' => self::BUDGET_LOCKED_MESSAGE], 403)
            );
        }

        $formRequest = StoreIIESExpensesRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
        $validated = $validator->validated();

        $parentData = [
            'iies_total_expenses' => $validated['iies_total_expenses'] ?? null,
            'iies_expected_scholarship_govt' => $validated['iies_expected_scholarship_govt'] ?? null,
            'iies_support_other_sources' => $validated['iies_support_other_sources'] ?? null,
            'iies_beneficiary_contribution' => $validated['iies_beneficiary_contribution'] ?? null,
            'iies_balance_requested' => $validated['iies_balance_requested'] ?? null,
        ];
        $particulars = $validated['iies_particulars'] ?? [];
        $amounts = $validated['iies_amounts'] ?? [];
        if (! is_array($particulars)) {
            $particulars = [];
        }
        if (! is_array($amounts)) {
            $amounts = [];
        }

        // M1 Data Integrity Shield: skip delete+recreate when section is absent or empty.
        if (! $this->isIIESExpensesMeaningfullyFilled($parentData, $particulars, $amounts)) {
            Log::info('IIESExpensesController@store - Section absent or empty; skipping mutation', [
                'project_id' => $projectId,
            ]);

            return response()->json(['message' => 'IIES estimated expenses saved successfully.'], 200);
        }

        Log::info('Storing IIES estimated expenses', ['project_id' => $projectId]);

        $existingExpenses = ProjectIIESExpenses::where('project_id', $projectId)->first();
        if ($existingExpenses) {
            $existingExpenses->expenseDetails()->delete();
            $existingExpenses->delete();
        }

        $projectExpenses = new ProjectIIESExpenses();
        $projectExpenses->project_id = $projectId;
        $projectExpenses->iies_total_expenses = $validated['iies_total_expenses'] ?? 0;
        $projectExpenses->iies_expected_scholarship_govt = $validated['iies_expected_scholarship_govt'] ?? 0;
        $projectExpenses->iies_support_other_sources = $validated['iies_support_other_sources'] ?? 0;
        $projectExpenses->iies_beneficiary_contribution = $validated['iies_beneficiary_contribution'] ?? 0;
        $projectExpenses->iies_balance_requested = $validated['iies_balance_requested'] ?? 0;
        $projectExpenses->save();

        foreach ($particulars as $index => $particular) {
            if (! empty($particular) && isset($amounts[$index]) && $amounts[$index] !== null && $amounts[$index] !== '') {
                $projectExpenses->expenseDetails()->create([
                    'iies_particular' => $particular,
                    'iies_amount' => $amounts[$index],
                ]);
            }
        }

        $project = Project::where('project_id', $projectId)->first();
        if ($project) {
            app(BudgetSyncService::class)->syncFromTypeSave($project);
        }

        return response()->json(['message' => 'IIES estimated expenses saved successfully.'], 200);
    }

    public function show($projectId)
    {
        try {
            Log::info('Fetching IIES Expenses for show view', ['project_id' => $projectId]);

            $project = Project::where('project_id', $projectId)->firstOrFail();

            $iiesExpenses = ProjectIIESExpenses::with('expenseDetails')
                ->where('project_id', $projectId)
                ->first();

            if (! $iiesExpenses) {
                $iiesExpenses = new ProjectIIESExpenses([
                    'project_id' => $projectId,
                    'iies_total_expenses' => null,
                    'iies_expected_scholarship_govt' => null,
                    'iies_support_other_sources' => null,
                    'iies_beneficiary_contribution' => null,
                    'iies_balance_requested' => null,
                ]);
            }

            Log::info('IIESExpensesController@show - Retrieved Data', [
                'project_id' => $projectId,
                'IIESExpenses' => $iiesExpenses,
                'Expense Details' => $iiesExpenses->expenseDetails ?? [],
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

    public function edit($projectId)
    {
        try {
            Log::info('Fetching IIES Expenses for editing', ['project_id' => $projectId]);

            $project = Project::where('project_id', $projectId)->firstOrFail();

            $iiesExpenses = ProjectIIESExpenses::with('expenseDetails')
                ->where('project_id', $projectId)
                ->first();

            if (! $iiesExpenses) {
                $iiesExpenses = new ProjectIIESExpenses([
                    'project_id' => $projectId,
                    'iies_total_expenses' => null,
                    'iies_expected_scholarship_govt' => null,
                    'iies_support_other_sources' => null,
                    'iies_beneficiary_contribution' => null,
                    'iies_balance_requested' => null,
                ]);
            }

            Log::info('IIESExpenses Controller - Fetched IIES Expenses for editing', [
                'project_id' => $projectId,
                'IIESExpenses' => $iiesExpenses,
            ]);

            return $iiesExpenses;
        } catch (\Exception $e) {
            Log::error('Error fetching IIES Expenses for edit', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function update(FormRequest $request, $projectId)
    {
        return $this->store($request, $projectId);
    }

    public function destroy($projectId)
    {
        $expenses = ProjectIIESExpenses::where('project_id', $projectId)->firstOrFail();
        $expenses->expenseDetails()->delete();
        $expenses->delete();

        Log::info('IIESExpensesController@destroy - Success', ['project_id' => $projectId]);

        return response()->json(['message' => 'IIES estimated expenses deleted successfully.'], 200);
    }

    /**
     * M1 Guard: true when at least one parent field is meaningful or at least one child row has meaningful particular/amount.
     */
    private function isIIESExpensesMeaningfullyFilled(array $parentData, array $particulars, array $amounts): bool
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
