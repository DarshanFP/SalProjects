<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\ProjectEduRUTBasicInfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProjectEduRUTBasicInfoController extends Controller
{
    // Store basic information for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing basic info', ['project_id' => $projectId]);

            $basicInfo = new ProjectEduRUTBasicInfo();
            $basicInfo->project_id = $projectId;
            $basicInfo->institution_type = $request->input('institution_type');
            $basicInfo->group_type = $request->input('group_type');
            $basicInfo->category = $request->input('category');
            $basicInfo->project_location = $request->input('project_location');
            $basicInfo->sisters_work = $request->input('sisters_work');
            $basicInfo->conditions = $request->input('conditions');
            $basicInfo->problems = $request->input('problems');
            $basicInfo->need = $request->input('need');
            $basicInfo->criteria = $request->input('criteria');
            $basicInfo->save();

            DB::commit();
            Log::info('Basic info saved successfully', ['project_id' => $projectId]);
            return response()->json($basicInfo, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving basic info', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save basic info.'], 500);
        }
    }

    // Show basic info for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching basic info', ['project_id' => $projectId]);

            // Fetch the basic info data
            $basicInfo = ProjectEduRUTBasicInfo::where('project_id', $projectId)->first();

            if (!$basicInfo) {
                Log::warning('No Basic Info data found', ['project_id' => $projectId]);
                return null; // Return null if no data is found
            }

            return $basicInfo; // Return the basic info model
        } catch (\Exception $e) {
            Log::error('Error fetching basic info', ['error' => $e->getMessage()]);
            return null;
        }
    }


    // Edit basic info for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing basic info', ['project_id' => $projectId]);

            $basicInfo = ProjectEduRUTBasicInfo::where('project_id', $projectId)->firstOrFail();

            // Return the data directly
            return $basicInfo;
        } catch (\Exception $e) {
            Log::error('Error editing basic info', ['error' => $e->getMessage()]);
            return null;
        }
    }


    // Update basic info for a project
    public function update(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Updating basic info', ['project_id' => $projectId]);

            $basicInfo = ProjectEduRUTBasicInfo::where('project_id', $projectId)->firstOrFail();
            $basicInfo->institution_type = $request->input('institution_type');
            $basicInfo->group_type = $request->input('group_type');
            $basicInfo->category = $request->input('category');
            $basicInfo->project_location = $request->input('project_location');
            $basicInfo->sisters_work = $request->input('sisters_work');
            $basicInfo->conditions = $request->input('conditions');
            $basicInfo->problems = $request->input('problems');
            $basicInfo->need = $request->input('need');
            $basicInfo->criteria = $request->input('criteria');
            $basicInfo->save();

            DB::commit();
            Log::info('Basic info updated successfully', ['project_id' => $projectId]);
            return response()->json($basicInfo, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating basic info', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update basic info.'], 500);
        }
    }

    // Delete basic info for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting basic info', ['project_id' => $projectId]);

            $basicInfo = ProjectEduRUTBasicInfo::where('project_id', $projectId)->firstOrFail();
            $basicInfo->delete();

            DB::commit();
            Log::info('Basic info deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Basic info deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting basic info', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete basic info.'], 500);
        }
    }
}
