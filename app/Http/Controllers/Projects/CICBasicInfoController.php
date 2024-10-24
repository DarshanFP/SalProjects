<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\ProjectCICBasicInfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CICBasicInfoController extends Controller
{
    // Store basic information for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing CIC basic info', ['project_id' => $projectId]);

            $basicInfo = new ProjectCICBasicInfo();
            $basicInfo->project_id = $projectId;
            $basicInfo->number_served_since_inception = $request->input('number_served_since_inception');
            $basicInfo->number_served_previous_year = $request->input('number_served_previous_year');
            $basicInfo->beneficiary_categories = $request->input('beneficiary_categories');
            $basicInfo->sisters_intervention = $request->input('sisters_intervention');
            $basicInfo->beneficiary_conditions = $request->input('beneficiary_conditions');
            $basicInfo->beneficiary_problems = $request->input('beneficiary_problems');
            $basicInfo->institution_challenges = $request->input('institution_challenges');
            $basicInfo->support_received = $request->input('support_received');
            $basicInfo->project_need = $request->input('project_need');
            $basicInfo->save();

            DB::commit();
            Log::info('CIC basic info saved successfully', ['project_id' => $projectId]);
            return response()->json($basicInfo, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving CIC basic info', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save CIC basic info.'], 500);
        }
    }

    // Show basic info for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching CIC basic info', ['project_id' => $projectId]);

            $basicInfo = ProjectCICBasicInfo::where('project_id', $projectId)->firstOrFail();
            return response()->json($basicInfo, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching CIC basic info', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch CIC basic info.'], 500);
        }
    }

    // Edit basic info for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing CIC basic info', ['project_id' => $projectId]);

            $basicInfo = ProjectCICBasicInfo::where('project_id', $projectId)->firstOrFail();

            // Return the data directly
            return $basicInfo;
        } catch (\Exception $e) {
            Log::error('Error editing CIC basic info', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update basic info for a project
    public function update(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Updating CIC basic info', ['project_id' => $projectId]);

            $basicInfo = ProjectCICBasicInfo::where('project_id', $projectId)->firstOrFail();
            $basicInfo->number_served_since_inception = $request->input('number_served_since_inception');
            $basicInfo->number_served_previous_year = $request->input('number_served_previous_year');
            $basicInfo->beneficiary_categories = $request->input('beneficiary_categories');
            $basicInfo->sisters_intervention = $request->input('sisters_intervention');
            $basicInfo->beneficiary_conditions = $request->input('beneficiary_conditions');
            $basicInfo->beneficiary_problems = $request->input('beneficiary_problems');
            $basicInfo->institution_challenges = $request->input('institution_challenges');
            $basicInfo->support_received = $request->input('support_received');
            $basicInfo->project_need = $request->input('project_need');
            $basicInfo->save();

            DB::commit();
            Log::info('CIC basic info updated successfully', ['project_id' => $projectId]);
            return response()->json($basicInfo, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating CIC basic info', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update CIC basic info.'], 500);
        }
    }

    // Delete basic info for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting CIC basic info', ['project_id' => $projectId]);

            $basicInfo = ProjectCICBasicInfo::where('project_id', $projectId)->firstOrFail();
            $basicInfo->delete();

            DB::commit();
            Log::info('CIC basic info deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'CIC basic info deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting CIC basic info', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete CIC basic info.'], 500);
        }
    }
}
