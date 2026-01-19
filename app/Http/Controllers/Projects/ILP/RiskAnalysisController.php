<?php

namespace App\Http\Controllers\Projects\ILP;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\ILP\ProjectILPRiskAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\ILP\StoreILPRiskAnalysisRequest;
use App\Http\Requests\Projects\ILP\UpdateILPRiskAnalysisRequest;

class RiskAnalysisController extends Controller
{
    // Store or update risk analysis
    public function store(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including fields not in StoreProjectRequest validation rules
        $validated = $request->all();
        
        DB::beginTransaction();
        try {
            Log::info('Storing ILP Risk Analysis', ['project_id' => $projectId]);

            // Delete existing risk analysis if any
            ProjectILPRiskAnalysis::where('project_id', $projectId)->delete();

            ProjectILPRiskAnalysis::create([
                'project_id' => $projectId,
                'identified_risks' => $validated['identified_risks'] ?? null,
                'mitigation_measures' => $validated['mitigation_measures'] ?? null,
                'business_sustainability' => $validated['business_sustainability'] ?? null,
                'expected_profits' => $validated['expected_profits'] ?? null,
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
    // public function show($projectId)
    // {
    //     try {
    //         Log::info('Fetching ILP Risk Analysis', ['project_id' => $projectId]);

    //         $riskAnalysis = ProjectILPRiskAnalysis::where('project_id', $projectId)->first();
    //         return response()->json($riskAnalysis, 200);
    //     } catch (\Exception $e) {
    //         Log::error('Error fetching ILP Risk Analysis', ['error' => $e->getMessage()]);
    //         return response()->json(['error' => 'Failed to fetch risk analysis.'], 500);
    //     }
    // }
    public function show($projectId)
    {
        try {
            Log::info('Fetching ILP Risk Analysis', ['project_id' => $projectId]);

            $riskAnalysis = ProjectILPRiskAnalysis::where('project_id', $projectId)->first();

            return [
                'identified_risks' => $riskAnalysis ? $riskAnalysis->identified_risks : '',
                'mitigation_measures' => $riskAnalysis ? $riskAnalysis->mitigation_measures : '',
                'business_sustainability' => $riskAnalysis ? $riskAnalysis->business_sustainability : '',
                'expected_profits' => $riskAnalysis ? $riskAnalysis->expected_profits : '',
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching ILP Risk Analysis', ['error' => $e->getMessage()]);
            return [
                'identified_risks' => '',
                'mitigation_measures' => '',
                'business_sustainability' => '',
                'expected_profits' => '',
            ];
        }
    }

    // Edit risk analysis for a project
    // Edit risk analysis for a project and return raw model data
    public function edit($projectId)
    {
        try {
            Log::info('Editing ILP Risk Analysis', ['project_id' => $projectId]);

            // Fetch the risk analysis for the given project ID
            $riskAnalysis = ProjectILPRiskAnalysis::where('project_id', $projectId)->first();

            // Log the fetched data
            if ($riskAnalysis) {
                Log::info('Fetched Risk Analysis for Edit', ['risk_analysis' => $riskAnalysis->toArray()]);
            } else {
                Log::warning('No Risk Analysis found for Edit', ['project_id' => $projectId]);
            }

            // Return the raw model data
            return $riskAnalysis;
        } catch (\Exception $e) {
            Log::error('Error editing ILP Risk Analysis', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return null; // Return null in case of an error
        }
    }

    // Update risk analysis for a project
    public function update(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including fields not in UpdateProjectRequest validation rules
        $validatedData = $request->all();
        
        DB::beginTransaction();

        try {
            Log::info('Updating ILP Risk Analysis', ['project_id' => $projectId]);

            // Update or create risk analysis
            $riskAnalysis = ProjectILPRiskAnalysis::updateOrCreate(
                ['project_id' => $projectId],
                [
                    'identified_risks' => $validatedData['identified_risks'],
                    'mitigation_measures' => $validatedData['mitigation_measures'],
                    'business_sustainability' => $validatedData['business_sustainability'],
                    'expected_profits' => $validatedData['expected_profits'],
                ]
            );

            DB::commit();
            Log::info('ILP Risk Analysis updated successfully', ['project_id' => $projectId]);

            return response()->json([
                'message' => 'Risk analysis updated successfully.',
                'data' => $riskAnalysis
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating ILP Risk Analysis', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to update Risk Analysis.'], 500);
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
