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
        $fillable = ['annexed_target_group', 'project_id'];
        $data = $request->only($fillable);

        $projectId = is_array($data['project_id'] ?? null) ? (reset($data['project_id']) ?? null) : ($data['project_id'] ?? null);
        $groups = is_array($data['annexed_target_group'] ?? null)
            ? ($data['annexed_target_group'] ?? [])
            : (isset($data['annexed_target_group']) && $data['annexed_target_group'] !== '' ? [$data['annexed_target_group']] : []);

        DB::beginTransaction();
        try {
            Log::info('Storing annexed target group data', ['project_id' => $projectId]);

            foreach ($groups as $group) {
                if (!is_array($group)) {
                    continue;
                }
                $beneficiaryName = is_array($group['beneficiary_name'] ?? null) ? (reset($group['beneficiary_name']) ?? null) : ($group['beneficiary_name'] ?? null);
                $familyBackground = is_array($group['family_background'] ?? null) ? (reset($group['family_background']) ?? null) : ($group['family_background'] ?? null);
                $needOfSupport = is_array($group['need_of_support'] ?? null) ? (reset($group['need_of_support']) ?? null) : ($group['need_of_support'] ?? null);

                ProjectEduRUTAnnexedTargetGroup::create([
                    'project_id' => $projectId,
                    'beneficiary_name' => $beneficiaryName,
                    'family_background' => $familyBackground,
                    'need_of_support' => $needOfSupport,
                ]);
            }

            DB::commit();
            Log::info('Annexed target group data saved successfully', ['project_id' => $projectId]);

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
        $fillable = ['annexed_target_group'];
        $data = $request->only($fillable);

        $groups = is_array($data['annexed_target_group'] ?? null)
            ? ($data['annexed_target_group'] ?? [])
            : (isset($data['annexed_target_group']) && $data['annexed_target_group'] !== '' ? [$data['annexed_target_group']] : []);

        DB::beginTransaction();
        try {
            Log::info('Updating annexed target group data', ['project_id' => $projectId]);

            ProjectEduRUTAnnexedTargetGroup::where('project_id', $projectId)->delete();

            foreach ($groups as $group) {
                if (!is_array($group)) {
                    continue;
                }
                $beneficiaryName = is_array($group['beneficiary_name'] ?? null) ? (reset($group['beneficiary_name']) ?? null) : ($group['beneficiary_name'] ?? null);
                $familyBackground = is_array($group['family_background'] ?? null) ? (reset($group['family_background']) ?? null) : ($group['family_background'] ?? null);
                $needOfSupport = is_array($group['need_of_support'] ?? null) ? (reset($group['need_of_support']) ?? null) : ($group['need_of_support'] ?? null);

                ProjectEduRUTAnnexedTargetGroup::create([
                    'project_id' => $projectId,
                    'beneficiary_name' => $beneficiaryName,
                    'family_background' => $familyBackground,
                    'need_of_support' => $needOfSupport,
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
