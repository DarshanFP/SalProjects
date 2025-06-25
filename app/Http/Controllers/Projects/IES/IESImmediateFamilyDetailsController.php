<?php

namespace App\Http\Controllers\Projects\IES;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IES\ProjectIESImmediateFamilyDetails;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IESImmediateFamilyDetailsController extends Controller
{
    // Store immediate family details for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IES immediate family details', ['project_id' => $projectId]);

            // Find or create a new immediate family details record
            $familyDetails = ProjectIESImmediateFamilyDetails::where('project_id', $projectId)->first() ?: new ProjectIESImmediateFamilyDetails();
            $familyDetails->project_id = $projectId;
            $familyDetails->fill($request->all());
            $familyDetails->save();

            DB::commit();
            Log::info('IES immediate family details saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IES immediate family details saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IES immediate family details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save IES immediate family details.'], 500);
        }
    }

    // Show immediate family details for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IES immediate family details', ['project_id' => $projectId]);

            $familyDetails = ProjectIESImmediateFamilyDetails::where('project_id', $projectId)->first();

            // Return the model object directly, not a JSON response
            return $familyDetails;
        } catch (\Exception $e) {
            Log::error('Error fetching IES immediate family details', ['error' => $e->getMessage()]);
            return null; // Return null instead of JSON error
        }
    }

    // Edit immediate family details for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IES immediate family details', ['project_id' => $projectId]);

            $familyDetails = ProjectIESImmediateFamilyDetails::where('project_id', $projectId)->firstOrFail();

            // Return the data directly
            return $familyDetails;
        } catch (\Exception $e) {
            Log::error('Error editing IES immediate family details', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update immediate family details for a project
    public function update(Request $request, $projectId)
    {
        return $this->store($request, $projectId); // Reuse the store logic for update
    }

    // Delete immediate family details for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IES immediate family details', ['project_id' => $projectId]);

            $familyDetails = ProjectIESImmediateFamilyDetails::where('project_id', $projectId)->firstOrFail();
            $familyDetails->delete();

            DB::commit();
            Log::info('IES immediate family details deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IES immediate family details deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IES immediate family details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete IES immediate family details.'], 500);
        }
    }
}
