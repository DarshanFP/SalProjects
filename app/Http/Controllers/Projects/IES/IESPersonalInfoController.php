<?php

namespace App\Http\Controllers\Projects\IES;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IES\ProjectIESPersonalInfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IESPersonalInfoController extends Controller
{
    // Store personal information for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IES personal information', ['project_id' => $projectId]);

            // Find or create a new personal info record
            $personalInfo = ProjectIESPersonalInfo::where('project_id', $projectId)->first() ?: new ProjectIESPersonalInfo();
            $personalInfo->project_id = $projectId;
            $personalInfo->fill($request->all());
            $personalInfo->save();

            DB::commit();
            Log::info('IES personal information saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IES personal information saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IES personal information', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save IES personal information.'], 500);
        }
    }

    // Show personal information for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IES personal information', ['project_id' => $projectId]);

            $personalInfo = ProjectIESPersonalInfo::where('project_id', $projectId)->firstOrFail();
            return response()->json($personalInfo, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching IES personal information', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch IES personal information.'], 500);
        }
    }

    // Edit personal information for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IES personal information', ['project_id' => $projectId]);

            $personalInfo = ProjectIESPersonalInfo::where('project_id', $projectId)->firstOrFail();

            // Return the data directly
            return $personalInfo;
        } catch (\Exception $e) {
            Log::error('Error editing IES personal information', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update personal information for a project
    public function update(Request $request, $projectId)
    {
        return $this->store($request, $projectId); // Reuse the store logic for update
    }

    // Delete personal information for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IES personal information', ['project_id' => $projectId]);

            $personalInfo = ProjectIESPersonalInfo::where('project_id', $projectId)->firstOrFail();
            $personalInfo->delete();

            DB::commit();
            Log::info('IES personal information deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IES personal information deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IES personal information', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete IES personal information.'], 500);
        }
    }
}
