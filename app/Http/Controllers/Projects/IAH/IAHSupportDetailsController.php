<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IAH\ProjectIAHSupportDetails;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IAHSupportDetailsController extends Controller
{
    // Store support details for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IAH support details', ['project_id' => $projectId]);

            $supportDetails = new ProjectIAHSupportDetails();
            $supportDetails->project_id = $projectId;
            $supportDetails->employed_at_st_ann = $request->input('employed_at_st_ann');
            $supportDetails->employment_details = $request->input('employment_details');
            $supportDetails->received_support = $request->input('received_support');
            $supportDetails->support_details = $request->input('support_details');
            $supportDetails->govt_support = $request->input('govt_support');
            $supportDetails->govt_support_nature = $request->input('govt_support_nature');
            $supportDetails->save();

            DB::commit();
            Log::info('IAH support details saved successfully', ['project_id' => $projectId]);
            return response()->json($supportDetails, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IAH support details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save IAH support details.'], 500);
        }
    }

    // Show support details for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IAH support details', ['project_id' => $projectId]);

            $supportDetails = ProjectIAHSupportDetails::where('project_id', $projectId)->firstOrFail();
            return response()->json($supportDetails, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching IAH support details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch IAH support details.'], 500);
        }
    }

    // Edit support details for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IAH support details', ['project_id' => $projectId]);

            $supportDetails = ProjectIAHSupportDetails::where('project_id', $projectId)->firstOrFail();

            // Return the data directly
            return $supportDetails;
        } catch (\Exception $e) {
            Log::error('Error editing IAH support details', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update support details for a project
    public function update(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Updating IAH support details', ['project_id' => $projectId]);

            $supportDetails = ProjectIAHSupportDetails::where('project_id', $projectId)->firstOrFail();
            $supportDetails->employed_at_st_ann = $request->input('employed_at_st_ann');
            $supportDetails->employment_details = $request->input('employment_details');
            $supportDetails->received_support = $request->input('received_support');
            $supportDetails->support_details = $request->input('support_details');
            $supportDetails->govt_support = $request->input('govt_support');
            $supportDetails->govt_support_nature = $request->input('govt_support_nature');
            $supportDetails->save();

            DB::commit();
            Log::info('IAH support details updated successfully', ['project_id' => $projectId]);
            return response()->json($supportDetails, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating IAH support details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update IAH support details.'], 500);
        }
    }

    // Delete support details for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IAH support details', ['project_id' => $projectId]);

            $supportDetails = ProjectIAHSupportDetails::where('project_id', $projectId)->firstOrFail();
            $supportDetails->delete();

            DB::commit();
            Log::info('IAH support details deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IAH support details deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IAH support details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete IAH support details.'], 500);
        }
    }
}
