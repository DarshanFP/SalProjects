<?php

namespace App\Http\Controllers\Projects\IES;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IES\ProjectIESEducationBackground;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IESEducationBackgroundController extends Controller
{
    // Store or update educational background for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IES educational background', ['project_id' => $projectId]);

            // Find or create a new educational background record
            $educationBackground = ProjectIESEducationBackground::where('project_id', $projectId)->first() ?: new ProjectIESEducationBackground();
            $educationBackground->project_id = $projectId;
            $educationBackground->fill($request->all());
            $educationBackground->save();

            DB::commit();
            Log::info('IES educational background saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IES educational background saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IES educational background', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save IES educational background.'], 500);
        }
    }

    // Show educational background for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IES educational background', ['project_id' => $projectId]);

            $educationBackground = ProjectIESEducationBackground::where('project_id', $projectId)->firstOrFail();
            return response()->json($educationBackground, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching IES educational background', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch IES educational background.'], 500);
        }
    }

    // Edit educational background for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IES educational background', ['project_id' => $projectId]);

            $educationBackground = ProjectIESEducationBackground::where('project_id', $projectId)->firstOrFail();

            // Return the data directly
            return $educationBackground;
        } catch (\Exception $e) {
            Log::error('Error editing IES educational background', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update educational background for a project
    public function update(Request $request, $projectId)
    {
        return $this->store($request, $projectId); // Reuse the store logic for update
    }

    // Delete educational background for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IES educational background', ['project_id' => $projectId]);

            ProjectIESEducationBackground::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('IES educational background deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IES educational background deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IES educational background', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete IES educational background.'], 500);
        }
    }
}