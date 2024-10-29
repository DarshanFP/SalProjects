<?php

namespace App\Http\Controllers\Projects\ILP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\ILP\ProjectILPRevenueGoals;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RevenueGoalsController extends Controller
{
    // Store or update revenue goals
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing ILP Revenue Goals', ['project_id' => $projectId]);

            ProjectILPRevenueGoals::updateOrCreate(
                ['project_id' => $projectId],
                [
                    'business_plan_items' => json_encode($request->business_plan_items),
                    'annual_income' => json_encode($request->annual_income),
                    'annual_expenses' => json_encode($request->annual_expenses),
                ]
            );

            DB::commit();
            Log::info('ILP Revenue Goals saved successfully', ['project_id' => $projectId]);

            return response()->json(['message' => 'Revenue Goals saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving ILP Revenue Goals', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Revenue Goals.'], 500);
        }
    }

    // Show revenue goals for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching ILP Revenue Goals', ['project_id' => $projectId]);

            $revenueGoals = ProjectILPRevenueGoals::where('project_id', $projectId)->first();
            return response()->json($revenueGoals, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching ILP Revenue Goals', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch Revenue Goals.'], 500);
        }
    }

    // Edit revenue goals for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing ILP Revenue Goals', ['project_id' => $projectId]);

            $revenueGoals = ProjectILPRevenueGoals::where('project_id', $projectId)->first();
            $revenueGoals = [
                'business_plan_items' => json_decode($revenueGoals->business_plan_items, true) ?? [],
                'annual_income' => json_decode($revenueGoals->annual_income, true) ?? [],
                'annual_expenses' => json_decode($revenueGoals->annual_expenses, true) ?? []
            ];

            return view('projects.partials.Edit.ILP.revenue_goals', compact('revenueGoals'));
        } catch (\Exception $e) {
            Log::error('Error editing ILP Revenue Goals', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Delete revenue goals for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting ILP Revenue Goals', ['project_id' => $projectId]);

            ProjectILPRevenueGoals::where('project_id', $projectId)->delete();
            DB::commit();
            Log::info('ILP Revenue Goals deleted successfully', ['project_id' => $projectId]);

            return response()->json(['message' => 'Revenue Goals deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting ILP Revenue Goals', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Revenue Goals.'], 500);
        }
    }
}
