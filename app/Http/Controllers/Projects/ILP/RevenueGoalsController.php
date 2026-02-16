<?php

namespace App\Http\Controllers\Projects\ILP;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OldProjects\ILP\ProjectILPRevenuePlanItem;
use App\Models\OldProjects\ILP\ProjectILPRevenueIncome;
use App\Models\OldProjects\ILP\ProjectILPRevenueExpense;
use App\Http\Requests\Projects\ILP\StoreILPRevenueGoalsRequest;
use App\Http\Requests\Projects\ILP\UpdateILPRevenueGoalsRequest;

class RevenueGoalsController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(FormRequest $request, $projectId)
    {
        $fillable = ['business_plan_items', 'annual_income', 'annual_expenses'];
        $data = $request->only($fillable);

        // Scalar-to-array normalization; ensure arrays for iteration
        $businessPlanItems = is_array($data['business_plan_items'] ?? null) ? ($data['business_plan_items'] ?? []) : (isset($data['business_plan_items']) ? [$data['business_plan_items']] : []);
        $annualIncome      = is_array($data['annual_income'] ?? null) ? ($data['annual_income'] ?? []) : (isset($data['annual_income']) ? [$data['annual_income']] : []);
        $annualExpenses    = is_array($data['annual_expenses'] ?? null) ? ($data['annual_expenses'] ?? []) : (isset($data['annual_expenses']) ? [$data['annual_expenses']] : []);

        DB::beginTransaction();
        try {
            Log::info('Storing Revenue Goals for Project', ['project_id' => $projectId]);

            foreach ($businessPlanItems as $item) {
                $itemVal = is_array($item['item'] ?? null) ? (reset($item['item']) ?? '') : ($item['item'] ?? '');
                $y1 = is_array($item['year_1'] ?? null) ? (reset($item['year_1']) ?? null) : ($item['year_1'] ?? null);
                $y2 = is_array($item['year_2'] ?? null) ? (reset($item['year_2']) ?? null) : ($item['year_2'] ?? null);
                $y3 = is_array($item['year_3'] ?? null) ? (reset($item['year_3']) ?? null) : ($item['year_3'] ?? null);
                $y4 = is_array($item['year_4'] ?? null) ? (reset($item['year_4']) ?? null) : ($item['year_4'] ?? null);
                ProjectILPRevenuePlanItem::create([
                    'project_id' => $projectId,
                    'item' => $itemVal,
                    'year_1' => $y1,
                    'year_2' => $y2,
                    'year_3' => $y3,
                    'year_4' => $y4,
                ]);
            }

            foreach ($annualIncome as $income) {
                $desc = is_array($income['description'] ?? null) ? (reset($income['description']) ?? '') : ($income['description'] ?? '');
                $y1 = is_array($income['year_1'] ?? null) ? (reset($income['year_1']) ?? null) : ($income['year_1'] ?? null);
                $y2 = is_array($income['year_2'] ?? null) ? (reset($income['year_2']) ?? null) : ($income['year_2'] ?? null);
                $y3 = is_array($income['year_3'] ?? null) ? (reset($income['year_3']) ?? null) : ($income['year_3'] ?? null);
                $y4 = is_array($income['year_4'] ?? null) ? (reset($income['year_4']) ?? null) : ($income['year_4'] ?? null);
                ProjectILPRevenueIncome::create([
                    'project_id' => $projectId,
                    'description' => $desc,
                    'year_1' => $y1,
                    'year_2' => $y2,
                    'year_3' => $y3,
                    'year_4' => $y4,
                ]);
            }

            foreach ($annualExpenses as $expense) {
                $desc = is_array($expense['description'] ?? null) ? (reset($expense['description']) ?? '') : ($expense['description'] ?? '');
                $y1 = is_array($expense['year_1'] ?? null) ? (reset($expense['year_1']) ?? null) : ($expense['year_1'] ?? null);
                $y2 = is_array($expense['year_2'] ?? null) ? (reset($expense['year_2']) ?? null) : ($expense['year_2'] ?? null);
                $y3 = is_array($expense['year_3'] ?? null) ? (reset($expense['year_3']) ?? null) : ($expense['year_3'] ?? null);
                $y4 = is_array($expense['year_4'] ?? null) ? (reset($expense['year_4']) ?? null) : ($expense['year_4'] ?? null);
                ProjectILPRevenueExpense::create([
                    'project_id' => $projectId,
                    'description' => $desc,
                    'year_1' => $y1,
                    'year_2' => $y2,
                    'year_3' => $y3,
                    'year_4' => $y4,
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Revenue Goals saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing Revenue Goals', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Revenue Goals.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    // public function show($projectId)
    // {
    //     try {
    //         Log::info('Fetching Revenue Goals for Project', ['project_id' => $projectId]);

    //         $businessPlanItems = ProjectILPRevenuePlanItem::where('project_id', $projectId)->get();
    //         $annualIncome = ProjectILPRevenueIncome::where('project_id', $projectId)->get();
    //         $annualExpenses = ProjectILPRevenueExpense::where('project_id', $projectId)->get();

    //         return response()->json([
    //             'business_plan_items' => $businessPlanItems,
    //             'annual_income' => $annualIncome,
    //             'annual_expenses' => $annualExpenses,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         Log::error('Error fetching Revenue Goals', ['error' => $e->getMessage()]);
    //         return response()->json(['error' => 'Failed to fetch Revenue Goals.'], 500);
    //     }
    // }
    public function show($projectId)
    {
        try {
            Log::info('Fetching Revenue Goals for Project', ['project_id' => $projectId]);

            return [
                'business_plan_items' => ProjectILPRevenuePlanItem::where('project_id', $projectId)->get()->toArray(),
                'annual_income' => ProjectILPRevenueIncome::where('project_id', $projectId)->get()->toArray(),
                'annual_expenses' => ProjectILPRevenueExpense::where('project_id', $projectId)->get()->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching Revenue Goals', ['error' => $e->getMessage()]);
            return [];
        }
    }

        /**
     * Show the form for editing the specified resource.
     */
    public function edit($projectId)
    {
        try {
            Log::info('Editing Revenue Goals for Project', ['project_id' => $projectId]);

            // Fetch the data for the project
            $businessPlanItems = ProjectILPRevenuePlanItem::where('project_id', $projectId)->get();
            $annualIncome      = ProjectILPRevenueIncome::where('project_id', $projectId)->get();
            $annualExpenses    = ProjectILPRevenueExpense::where('project_id', $projectId)->get();

            // Add a log to see exactly what we retrieved
            Log::info('RevenueGoalsController@edit - Fetched data', [
                'business_plan_items' => $businessPlanItems->toArray(), // If you want array form
                'annual_income'       => $annualIncome->toArray(),
                'annual_expenses'     => $annualExpenses->toArray(),
            ]);

            return [
                'business_plan_items' => $businessPlanItems,
                'annual_income'       => $annualIncome,
                'annual_expenses'     => $annualExpenses,
            ];
        } catch (\Exception $e) {
            Log::error('Error editing Revenue Goals', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return null; // or throw an exception, or handle as needed
        }
    }

    public function update(FormRequest $request, $projectId)
    {
        $fillable = ['business_plan_items', 'annual_income', 'annual_expenses'];
        $data = $request->only($fillable);

        $businessPlanItems = is_array($data['business_plan_items'] ?? null) ? ($data['business_plan_items'] ?? []) : (isset($data['business_plan_items']) ? [$data['business_plan_items']] : []);
        $annualIncome      = is_array($data['annual_income'] ?? null) ? ($data['annual_income'] ?? []) : (isset($data['annual_income']) ? [$data['annual_income']] : []);
        $annualExpenses    = is_array($data['annual_expenses'] ?? null) ? ($data['annual_expenses'] ?? []) : (isset($data['annual_expenses']) ? [$data['annual_expenses']] : []);

        if (! $this->isILPRevenueGoalsMeaningfullyFilled($businessPlanItems, $annualIncome, $annualExpenses)) {
            Log::info('RevenueGoalsController@update - Section absent or empty; skipping mutation', [
                'project_id' => $projectId,
            ]);

            return response()->json([
                'message' => 'Revenue Goals updated successfully.',
            ], 200);
        }

        DB::beginTransaction();
        try {
            Log::info('Updating Revenue Goals for Project', ['project_id' => $projectId]);

            ProjectILPRevenuePlanItem::where('project_id', $projectId)->delete();
            ProjectILPRevenueIncome::where('project_id', $projectId)->delete();
            ProjectILPRevenueExpense::where('project_id', $projectId)->delete();

            foreach ($businessPlanItems as $item) {
                $itemVal = is_array($item['item'] ?? null) ? (reset($item['item']) ?? '') : ($item['item'] ?? '');
                $y1 = is_array($item['year_1'] ?? null) ? (reset($item['year_1']) ?? null) : ($item['year_1'] ?? null);
                $y2 = is_array($item['year_2'] ?? null) ? (reset($item['year_2']) ?? null) : ($item['year_2'] ?? null);
                $y3 = is_array($item['year_3'] ?? null) ? (reset($item['year_3']) ?? null) : ($item['year_3'] ?? null);
                $y4 = is_array($item['year_4'] ?? null) ? (reset($item['year_4']) ?? null) : ($item['year_4'] ?? null);
                ProjectILPRevenuePlanItem::create([
                    'project_id' => $projectId,
                    'item' => $itemVal,
                    'year_1' => $y1,
                    'year_2' => $y2,
                    'year_3' => $y3,
                    'year_4' => $y4,
                ]);
            }

            foreach ($annualIncome as $income) {
                $desc = is_array($income['description'] ?? null) ? (reset($income['description']) ?? '') : ($income['description'] ?? '');
                $y1 = is_array($income['year_1'] ?? null) ? (reset($income['year_1']) ?? null) : ($income['year_1'] ?? null);
                $y2 = is_array($income['year_2'] ?? null) ? (reset($income['year_2']) ?? null) : ($income['year_2'] ?? null);
                $y3 = is_array($income['year_3'] ?? null) ? (reset($income['year_3']) ?? null) : ($income['year_3'] ?? null);
                $y4 = is_array($income['year_4'] ?? null) ? (reset($income['year_4']) ?? null) : ($income['year_4'] ?? null);
                ProjectILPRevenueIncome::create([
                    'project_id' => $projectId,
                    'description' => $desc,
                    'year_1' => $y1,
                    'year_2' => $y2,
                    'year_3' => $y3,
                    'year_4' => $y4,
                ]);
            }

            foreach ($annualExpenses as $expense) {
                $desc = is_array($expense['description'] ?? null) ? (reset($expense['description']) ?? '') : ($expense['description'] ?? '');
                $y1 = is_array($expense['year_1'] ?? null) ? (reset($expense['year_1']) ?? null) : ($expense['year_1'] ?? null);
                $y2 = is_array($expense['year_2'] ?? null) ? (reset($expense['year_2']) ?? null) : ($expense['year_2'] ?? null);
                $y3 = is_array($expense['year_3'] ?? null) ? (reset($expense['year_3']) ?? null) : ($expense['year_3'] ?? null);
                $y4 = is_array($expense['year_4'] ?? null) ? (reset($expense['year_4']) ?? null) : ($expense['year_4'] ?? null);
                ProjectILPRevenueExpense::create([
                    'project_id' => $projectId,
                    'description' => $desc,
                    'year_1' => $y1,
                    'year_2' => $y2,
                    'year_3' => $y3,
                    'year_4' => $y4,
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Revenue Goals updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating Revenue Goals', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update Revenue Goals.'], 500);
        }
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting Revenue Goals for Project', ['project_id' => $projectId]);

            ProjectILPRevenuePlanItem::where('project_id', $projectId)->delete();
            ProjectILPRevenueIncome::where('project_id', $projectId)->delete();
            ProjectILPRevenueExpense::where('project_id', $projectId)->delete();

            DB::commit();
            return response()->json(['message' => 'Revenue Goals deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting Revenue Goals', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Revenue Goals.'], 500);
        }
    }

    private function isILPRevenueGoalsMeaningfullyFilled(
        array $businessPlanItems,
        array $annualIncome,
        array $annualExpenses
    ): bool {
        // Check business plan items
        foreach ($businessPlanItems as $item) {
            if (is_array($item)) {
                foreach ($item as $value) {
                    if (trim((string) $value) !== '') {
                        return true;
                    }
                }
            }
        }

        // Check annual income
        foreach ($annualIncome as $income) {
            if (is_array($income)) {
                foreach ($income as $value) {
                    if (trim((string) $value) !== '') {
                        return true;
                    }
                }
            }
        }

        // Check annual expenses
        foreach ($annualExpenses as $expense) {
            if (is_array($expense)) {
                foreach ($expense as $value) {
                    if (trim((string) $value) !== '') {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
