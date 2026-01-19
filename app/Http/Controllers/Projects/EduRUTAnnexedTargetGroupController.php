<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\ProjectEduRUTAnnexedTargetGroup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class EduRUTAnnexedTargetGroupController extends Controller
{
    // Store annexed target group information
    public function store(FormRequest $request)
    {
        // Validation already done by FormRequest
        // Use all() to get all form data including annexed target group arrays
        // These fields are not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validated = $request->all();
        
        DB::beginTransaction();
        try {
            Log::info('Storing annexed target group data', ['project_id' => $validated['project_id']]);

            $annexedTargetGroups = $validated['annexed_target_group'] ?? [];
            
            foreach ($annexedTargetGroups as $group) {
                ProjectEduRUTAnnexedTargetGroup::create([
                    'project_id' => $validated['project_id'],
                    'beneficiary_name' => $group['beneficiary_name'] ?? null,
                    'family_background' => $group['family_background'] ?? null,
                    'need_of_support' => $group['need_of_support'] ?? null,
                ]);
            }

            DB::commit();
            Log::info('Annexed target group data saved successfully', ['project_id' => $request->project_id]);

            return response()->json(['message' => 'Annexed target group data saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing annexed target group data', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to store annexed target group data.'], 500);
        }
    }

    // Show annexed target group data for a project
    public function show($projectId)
{
    try {
        Log::info('Fetching annexed target group data', ['project_id' => $projectId]);

        // Fetch the annexed target group data
        $annexedTargetGroups = ProjectEduRUTAnnexedTargetGroup::where('project_id', $projectId)->get();

        if ($annexedTargetGroups->isEmpty()) {
            Log::warning('No annexed target group data found', ['project_id' => $projectId]);
        }

        return $annexedTargetGroups; // Return the collection of target groups
    } catch (\Exception $e) {
        Log::error('Error fetching annexed target group data', ['error' => $e->getMessage()]);
        return collect(); // Return an empty collection in case of failure
    }
}


    // Edit annexed target group data for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing annexed target group data', ['project_id' => $projectId]);

            $annexedTargetGroups = ProjectEduRUTAnnexedTargetGroup::where('project_id', $projectId)->get();

            // Return the data directly
            return $annexedTargetGroups;
        } catch (\Exception $e) {
            Log::error('Error editing annexed target group data', ['error' => $e->getMessage()]);
            return collect(); // Return an empty collection
        }
    }


    // Update annexed target group data for a project
    public function update(FormRequest $request, $projectId)
    {
        // Validation and authorization already done by FormRequest
        // Use all() to get all form data including annexed target group arrays
        // These fields are not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validated = $request->all();
        
        DB::beginTransaction();
        try {
            Log::info('Updating annexed target group data', ['project_id' => $projectId]);

            ProjectEduRUTAnnexedTargetGroup::where('project_id', $projectId)->delete(); // Delete old data first

            $annexedTargetGroups = $validated['annexed_target_group'] ?? [];
            
            foreach ($annexedTargetGroups as $group) {
                ProjectEduRUTAnnexedTargetGroup::create([
                    'project_id' => $projectId,
                    'beneficiary_name' => $group['beneficiary_name'] ?? null,
                    'family_background' => $group['family_background'] ?? null,
                    'need_of_support' => $group['need_of_support'] ?? null,
                ]);
            }

            DB::commit();
            Log::info('Annexed target group data updated successfully', ['project_id' => $projectId]);

            return response()->json(['message' => 'Annexed target group data updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating annexed target group data', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update annexed target group data.'], 500);
        }
    }

    // Delete annexed target group data for a project
    public function destroy($id)
    {
        try {
            Log::info('Deleting annexed target group', ['id' => $id]);

            $targetGroup = ProjectEduRUTAnnexedTargetGroup::findOrFail($id);
            $targetGroup->delete();

            return response()->json(['message' => 'Annexed target group deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting annexed target group', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete annexed target group.'], 500);
        }
    }
}
