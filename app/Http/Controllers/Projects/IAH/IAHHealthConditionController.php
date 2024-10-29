<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IAH\ProjectIAHHealthCondition;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IAHHealthConditionController extends Controller
{
    // Store health condition details for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IAH health condition details', ['project_id' => $projectId]);

            $healthCondition = new ProjectIAHHealthCondition();
            $healthCondition->project_id = $projectId;
            $healthCondition->illness = $request->input('illness');
            $healthCondition->treatment = $request->input('treatment');
            $healthCondition->doctor = $request->input('doctor');
            $healthCondition->hospital = $request->input('hospital');
            $healthCondition->doctor_address = $request->input('doctor_address');
            $healthCondition->health_situation = $request->input('health_situation');
            $healthCondition->family_situation = $request->input('family_situation');
            $healthCondition->save();

            DB::commit();
            Log::info('IAH health condition details saved successfully', ['project_id' => $projectId]);
            return response()->json($healthCondition, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IAH health condition details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save IAH health condition details.'], 500);
        }
    }

    // Show health condition details for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IAH health condition details', ['project_id' => $projectId]);

            $healthCondition = ProjectIAHHealthCondition::where('project_id', $projectId)->firstOrFail();
            return response()->json($healthCondition, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching IAH health condition details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch IAH health condition details.'], 500);
        }
    }

    // Edit health condition details for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IAH health condition details', ['project_id' => $projectId]);

            $healthCondition = ProjectIAHHealthCondition::where('project_id', $projectId)->firstOrFail();

            // Return the data directly
            return $healthCondition;
        } catch (\Exception $e) {
            Log::error('Error editing IAH health condition details', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update health condition details for a project
    public function update(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Updating IAH health condition details', ['project_id' => $projectId]);

            $healthCondition = ProjectIAHHealthCondition::where('project_id', $projectId)->firstOrFail();
            $healthCondition->illness = $request->input('illness');
            $healthCondition->treatment = $request->input('treatment');
            $healthCondition->doctor = $request->input('doctor');
            $healthCondition->hospital = $request->input('hospital');
            $healthCondition->doctor_address = $request->input('doctor_address');
            $healthCondition->health_situation = $request->input('health_situation');
            $healthCondition->family_situation = $request->input('family_situation');
            $healthCondition->save();

            DB::commit();
            Log::info('IAH health condition details updated successfully', ['project_id' => $projectId]);
            return response()->json($healthCondition, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating IAH health condition details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update IAH health condition details.'], 500);
        }
    }

    // Delete health condition details for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IAH health condition details', ['project_id' => $projectId]);

            $healthCondition = ProjectIAHHealthCondition::where('project_id', $projectId)->firstOrFail();
            $healthCondition->delete();

            DB::commit();
            Log::info('IAH health condition details deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IAH health condition details deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IAH health condition details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete IAH health condition details.'], 500);
        }
    }
}
