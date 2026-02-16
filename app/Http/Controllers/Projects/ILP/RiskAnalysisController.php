<?php

namespace App\Http\Controllers\Projects\ILP;

use App\Http\Controllers\Controller;
use App\Services\FormDataExtractor;
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
        $fillable = array_diff(
            (new ProjectILPRiskAnalysis())->getFillable(),
            ['project_id', 'ILP_risk_id']
        );
        $data = FormDataExtractor::forFillable($request, $fillable);

        if (! $this->isILPRiskAnalysisMeaningfullyFilled($data)) {
            Log::info('RiskAnalysisController@store - Section absent or empty; skipping mutation', [
                'project_id' => $projectId,
            ]);

            return response()->json([
                'message' => 'Risk analysis saved successfully.',
            ], 200);
        }

        DB::beginTransaction();
        try {
            Log::info('Storing ILP Risk Analysis', ['project_id' => $projectId]);

            // Delete existing risk analysis if any
            ProjectILPRiskAnalysis::where('project_id', $projectId)->delete();

            $riskAnalysis = new ProjectILPRiskAnalysis();
            $riskAnalysis->project_id = $projectId;
            $riskAnalysis->fill($data);
            $riskAnalysis->save();

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
        $result = $this->store($request, $projectId);
        if ($result->getStatusCode() === 200) {
            $riskAnalysis = ProjectILPRiskAnalysis::where('project_id', $projectId)->first();
            return response()->json([
                'message' => 'Risk analysis updated successfully.',
                'data' => $riskAnalysis
            ], 200);
        }
        return $result;
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

    private function isILPRiskAnalysisMeaningfullyFilled(array $data): bool
    {
        foreach ($data as $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    if (trim((string) $v) !== '') {
                        return true;
                    }
                }
            } else {
                if (trim((string) $value) !== '') {
                    return true;
                }
            }
        }

        return false;
    }
}
