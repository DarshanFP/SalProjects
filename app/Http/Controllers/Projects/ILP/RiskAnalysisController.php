<?php

namespace App\Http\Controllers\Projects\ILP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\ILP\ProjectILPRiskAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RiskAnalysisController extends Controller
{
    // Store or update risk analysis
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing ILP Risk Analysis', ['project_id' => $projectId]);

            // Delete existing risk analysis if any
            ProjectILPRiskAnalysis::where('project_id', $projectId)->delete();

            ProjectILPRiskAnalysis::create([
                'project_id' => $projectId,
                'identified_risks' => $request->identified_risks,
                'mitigation_measures' => $request->mitigation_measures,
                'business_sustainability' => $request->business_sustainability,
                'expected_profits' => $request->expected_profits,
            ]);

            DB::commit();
            Log::info('ILP Risk Analysis saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Risk analysis saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving ILP Risk Analysis', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save risk analysis.'], 500);
        }
    }

    // Show risk analysis for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching ILP Risk Analysis', ['project_id' => $projectId]);

            $riskAnalysis = ProjectILPRiskAnalysis::where('project_id', $projectId)->first();
            return response()->json($riskAnalysis, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching ILP Risk Analysis', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch risk analysis.'], 500);
        }
    }

    // Edit risk analysis for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing ILP Risk Analysis', ['project_id' => $projectId]);

            $riskAnalysis = ProjectILPRiskAnalysis::where('project_id', $projectId)->first();
            return view('projects.partials.Edit.ILP.risk_analysis', compact('riskAnalysis'));
        } catch (\Exception $e) {
            Log::error('Error editing ILP Risk Analysis', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Delete risk analysis for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting ILP Risk Analysis', ['project_id' => $projectId]);

            ProjectILPRiskAnalysis::where('project_id', $projectId)->delete();
            DB::commit();
            Log::info('ILP Risk Analysis deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Risk analysis deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting ILP Risk Analysis', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete risk analysis.'], 500);
        }
    }
}
