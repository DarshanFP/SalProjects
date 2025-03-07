<?php

namespace App\Http\Controllers\Projects\ILP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OldProjects\ILP\ProjectILPRevenuePlanItem;
use App\Models\OldProjects\ILP\ProjectILPRevenueIncome;
use App\Models\OldProjects\ILP\ProjectILPRevenueExpense;

class RevenueGoalsController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing Revenue Goals for Project', ['project_id' => $projectId]);

            // Store Business Plan Items
            $businessPlanItems = $request->input('business_plan_items', []);
            foreach ($businessPlanItems as $item) {
                ProjectILPRevenuePlanItem::create([
                    'project_id' => $projectId,
                    'item' => $item['item'] ?? '',
                    'year_1' => $item['year_1'] ?? null,
                    'year_2' => $item['year_2'] ?? null,
                    'year_3' => $item['year_3'] ?? null,
                    'year_4' => $item['year_4'] ?? null,
                ]);
            }

            // Store Annual Income
            $annualIncome = $request->input('annual_income', []);
            foreach ($annualIncome as $income) {
                ProjectILPRevenueIncome::create([
                    'project_id' => $projectId,
                    'description' => $income['description'] ?? '',
                    'year_1' => $income['year_1'] ?? null,
                    'year_2' => $income['year_2'] ?? null,
                    'year_3' => $income['year_3'] ?? null,
                    'year_4' => $income['year_4'] ?? null,
                ]);
            }

            // Store Annual Expenses
            $annualExpenses = $request->input('annual_expenses', []);
            foreach ($annualExpenses as $expense) {
                ProjectILPRevenueExpense::create([
                    'project_id' => $projectId,
                    'description' => $expense['description'] ?? '',
                    'year_1' => $expense['year_1'] ?? null,
                    'year_2' => $expense['year_2'] ?? null,
                    'year_3' => $expense['year_3'] ?? null,
                    'year_4' => $expense['year_4'] ?? null,
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

    public function update(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Updating Revenue Goals for Project', ['project_id' => $projectId]);

            // Update Business Plan Items
            ProjectILPRevenuePlanItem::where('project_id', $projectId)->delete();
            $businessPlanItems = $request->input('business_plan_items', []);
            foreach ($businessPlanItems as $item) {
                ProjectILPRevenuePlanItem::create([
                    'project_id' => $projectId,
                    'item' => $item['item'] ?? '',
                    'year_1' => $item['year_1'] ?? null,
                    'year_2' => $item['year_2'] ?? null,
                    'year_3' => $item['year_3'] ?? null,
                    'year_4' => $item['year_4'] ?? null,
                ]);
            }

            // Update Annual Income
            ProjectILPRevenueIncome::where('project_id', $projectId)->delete();
            $annualIncome = $request->input('annual_income', []);
            foreach ($annualIncome as $income) {
                ProjectILPRevenueIncome::create([
                    'project_id' => $projectId,
                    'description' => $income['description'] ?? '', // FIXED field name from 'desc' to 'description'
                    'year_1' => $income['year_1'] ?? null,
                    'year_2' => $income['year_2'] ?? null,
                    'year_3' => $income['year_3'] ?? null,
                    'year_4' => $income['year_4'] ?? null,
                ]);
            }

            // Update Annual Expenses
            ProjectILPRevenueExpense::where('project_id', $projectId)->delete();
            $annualExpenses = $request->input('annual_expenses', []);
            foreach ($annualExpenses as $expense) {
                ProjectILPRevenueExpense::create([
                    'project_id' => $projectId,
                    'description' => $expense['description'] ?? '', // FIXED field name from 'desc' to 'description'
                    'year_1' => $expense['year_1'] ?? null,
                    'year_2' => $expense['year_2'] ?? null,
                    'year_3' => $expense['year_3'] ?? null,
                    'year_4' => $expense['year_4'] ?? null,
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
}
