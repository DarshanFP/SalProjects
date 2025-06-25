<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IAH\ProjectIAHSupportDetails;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IAHSupportDetailsController extends Controller
{
    /**
     * Store (create) a single row of support details for a project.
     */
    public function store(Request $request, $projectId)
    {
        Log::info('IAHSupportDetailsController@store - Start', [
            'project_id'   => $projectId,
            'request_data' => $request->all()
        ]);

        DB::beginTransaction();
        try {
            // Typically only one row, so remove old:
            ProjectIAHSupportDetails::where('project_id', $projectId)->delete();

            $supportDetails = new ProjectIAHSupportDetails();
            $supportDetails->project_id          = $projectId;
            $supportDetails->employed_at_st_ann  = $request->input('employed_at_st_ann');
            $supportDetails->employment_details  = $request->input('employment_details');
            $supportDetails->received_support    = $request->input('received_support');
            $supportDetails->support_details     = $request->input('support_details');
            $supportDetails->govt_support        = $request->input('govt_support');
            $supportDetails->govt_support_nature = $request->input('govt_support_nature');
            $supportDetails->save();

            DB::commit();
            Log::info('IAHSupportDetailsController@store - Success', [
                'project_id' => $projectId
            ]);
            return response()->json($supportDetails, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHSupportDetailsController@store - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to save IAH support details.'], 500);
        }
    }

    /**
     * Update an existing support details row.
     */
    public function update(Request $request, $projectId)
    {
        Log::info('IAHSupportDetailsController@update - Start', [
            'project_id'   => $projectId,
            'request_data' => $request->all()
        ]);

        DB::beginTransaction();
        try {
            $supportDetails = ProjectIAHSupportDetails::where('project_id', $projectId)->firstOrFail();
            Log::info('IAHSupportDetailsController@update - Found existing row', [
                'id' => $supportDetails->id
            ]);

            $supportDetails->employed_at_st_ann  = $request->input('employed_at_st_ann');
            $supportDetails->employment_details  = $request->input('employment_details');
            $supportDetails->received_support    = $request->input('received_support');
            $supportDetails->support_details     = $request->input('support_details');
            $supportDetails->govt_support        = $request->input('govt_support');
            $supportDetails->govt_support_nature = $request->input('govt_support_nature');
            $supportDetails->save();

            DB::commit();
            Log::info('IAHSupportDetailsController@update - Success', [
                'project_id' => $projectId
            ]);
            return response()->json($supportDetails, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHSupportDetailsController@update - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to update IAH support details.'], 500);
        }
    }

    /**
     * Show the existing record.
     */
    public function show($projectId)
    {
        try {
            Log::info('IAHSupportDetailsController@show - Start', ['project_id' => $projectId]);

            $supportDetails = ProjectIAHSupportDetails::where('project_id', $projectId)->first();

            // Return the model object directly, not a JSON response
            return $supportDetails;
        } catch (\Exception $e) {
            Log::error('IAHSupportDetailsController@show - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return null; // Return null instead of JSON error
        }
    }

    /**
     * Edit route for a single record typically returns data or a view.
     */
    public function edit($projectId)
    {
        try {
            Log::info('IAHSupportDetailsController@edit - Start', ['project_id' => $projectId]);

            $supportDetails = ProjectIAHSupportDetails::where('project_id', $projectId)->firstOrFail();
            Log::info('IAHSupportDetailsController@edit - Data retrieved', [
                'id' => $supportDetails->id
            ]);

            // Return data or a view
            return $supportDetails;
        } catch (\Exception $e) {
            Log::error('IAHSupportDetailsController@edit - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Delete the existing record.
     */
    public function destroy($projectId)
    {
        Log::info('IAHSupportDetailsController@destroy - Start', ['project_id' => $projectId]);

        DB::beginTransaction();
        try {
            $supportDetails = ProjectIAHSupportDetails::where('project_id', $projectId)->firstOrFail();
            $supportDetails->delete();

            DB::commit();
            Log::info('IAHSupportDetailsController@destroy - Success', [
                'project_id' => $projectId
            ]);
            return response()->json(['message' => 'IAH support details deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHSupportDetailsController@destroy - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to delete IAH support details.'], 500);
        }
    }
}
