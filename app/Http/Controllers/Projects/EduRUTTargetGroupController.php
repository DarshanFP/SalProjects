<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\OldProjects\ProjectEduRUTTargetGroup;
use App\Imports\EduRUTTargetGroupImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EduRUTTargetGroupController extends Controller
{
    public function uploadExcel(Request $request)
    {
        // Excel upload logic is currently disabled
        return response()->json(['message' => 'Excel upload feature is disabled.'], 200);
    }

    // Store target group information for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing target group data', ['project_id' => $projectId]);

            $validatedData = $request->validate([
                'target_group.*.beneficiary_name' => 'nullable|string|max:255',
                'target_group.*.caste' => 'nullable|string|max:255',
                'target_group.*.institution_name' => 'nullable|string|max:255',
                'target_group.*.class_standard' => 'nullable|string|max:255',
                'target_group.*.total_tuition_fee' => 'nullable|numeric',
                'target_group.*.eligibility_scholarship' => 'nullable|boolean',
                'target_group.*.expected_amount' => 'nullable|numeric',
                'target_group.*.contribution_from_family' => 'nullable|numeric',
            ]);

            foreach ($validatedData['target_group'] as $group) {
                ProjectEduRUTTargetGroup::create([
                    'project_id' => $projectId,
                    'beneficiary_name' => $group['beneficiary_name'],
                    'caste' => $group['caste'],
                    'institution_name' => $group['institution_name'],
                    'class_standard' => $group['class_standard'],
                    'total_tuition_fee' => $group['total_tuition_fee'],
                    'eligibility_scholarship' => $group['eligibility_scholarship'],
                    'expected_amount' => $group['expected_amount'],
                    'contribution_from_family' => $group['contribution_from_family'],
                ]);
            }

            DB::commit();
            Log::info('Target group data stored successfully', ['project_id' => $projectId]);

            return response()->json(['message' => 'Target group data saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing target group data', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to store target group data.'], 500);
        }
    }


    // Show target group data for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching target group data', ['project_id' => $projectId]);

            $targetGroups = ProjectEduRUTTargetGroup::where('project_id', $projectId)->get();
            return response()->json($targetGroups, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching target group data', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch target group data.'], 500);
        }
    }

    // Edit target group data for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing target group data', ['project_id' => $projectId]);

            $targetGroups = ProjectEduRUTTargetGroup::where('project_id', $projectId)->get();

            // Return the data directly so it can be passed to the ProjectController
            return $targetGroups;
        } catch (\Exception $e) {
            Log::error('Error editing target group data', ['error' => $e->getMessage()]);
            return null; // Return null in case of an error
        }
    }


    // Update target group data for a project
    public function update(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Updating target group data', ['project_id' => $projectId]);

            ProjectEduRUTTargetGroup::where('project_id', $projectId)->delete(); // Delete old data first

            $validatedData = $request->validate([
                'target_group.*.beneficiary_name' => 'nullable|string|max:255',
                'target_group.*.caste' => 'nullable|string|max:255',
                'target_group.*.institution_name' => 'nullable|string|max:255',
                'target_group.*.class_standard' => 'nullable|string|max:255',
                'target_group.*.total_tuition_fee' => 'nullable|numeric',
                'target_group.*.eligibility_scholarship' => 'nullable|boolean',
                'target_group.*.expected_amount' => 'nullable|numeric',
                'target_group.*.contribution_from_family' => 'nullable|numeric',
            ]);

            foreach ($validatedData['target_group'] as $group) {
                ProjectEduRUTTargetGroup::create([
                    'project_id' => $projectId,
                    'beneficiary_name' => $group['beneficiary_name'] ?? null,
                    'caste' => $group['caste'] ?? null,
                    'institution_name' => $group['institution_name'] ?? null,
                    'class_standard' => $group['class_standard'] ?? null,
                    'total_tuition_fee' => $group['total_tuition_fee'] ?? null,
                    'eligibility_scholarship' => $group['eligibility_scholarship'] ?? null,
                    'expected_amount' => $group['expected_amount'] ?? null,
                    'contribution_from_family' => $group['contribution_from_family'] ?? null,
                ]);
            }

            DB::commit();
            Log::info('Target group data updated successfully', ['project_id' => $projectId]);

            return response()->json(['message' => 'Target group data updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating target group data', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update target group data.'], 500);
        }
    }


    // Delete target group data for a project
    public function destroy($projectId)
    {
        try {
            Log::info('Deleting target group data', ['project_id' => $projectId]);

            ProjectEduRUTTargetGroup::where('project_id', $projectId)->delete();
            return response()->json(['message' => 'Target group data deleted successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting target group data', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete target group data.'], 500);
        }
    }
}
