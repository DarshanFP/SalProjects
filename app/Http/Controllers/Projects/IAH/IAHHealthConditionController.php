<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IAH\ProjectIAHHealthCondition;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IAHHealthConditionController extends Controller
{
    /**
     * Store (create) health condition info for a project.
     */
    public function store(Request $request, $projectId)
    {
        Log::info('IAHHealthConditionController@store - Start', [
            'project_id' => $projectId,
            'request_data' => $request->all()
        ]);

        DB::beginTransaction();
        try {
            // Because there's typically a single row for health condition, we may want to delete any existing first:
            ProjectIAHHealthCondition::where('project_id', $projectId)->delete();

            $healthCondition = new ProjectIAHHealthCondition();
            $healthCondition->project_id       = $projectId;
            $healthCondition->illness          = $request->input('illness');
            $healthCondition->treatment        = $request->input('treatment');
            $healthCondition->doctor           = $request->input('doctor');
            $healthCondition->hospital         = $request->input('hospital');
            $healthCondition->doctor_address   = $request->input('doctor_address');
            $healthCondition->health_situation = $request->input('health_situation');
            $healthCondition->family_situation = $request->input('family_situation');
            $healthCondition->save();

            DB::commit();
            Log::info('IAHHealthConditionController@store - Success', [
                'project_id' => $projectId
            ]);
            return response()->json($healthCondition, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHHealthConditionController@store - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to save IAH health condition details.'], 500);
        }
    }

    /**
     * Update an existing health condition record.
     */
    public function update(Request $request, $projectId)
    {
        Log::info('IAHHealthConditionController@update - Start', [
            'project_id'   => $projectId,
            'request_data' => $request->all()
        ]);

        DB::beginTransaction();
        try {
            $healthCondition = ProjectIAHHealthCondition::where('project_id', $projectId)->firstOrFail();
            Log::info('IAHHealthConditionController@update - Found existing record', [
                'health_condition_id' => $healthCondition->id
            ]);

            $healthCondition->illness          = $request->input('illness');
            $healthCondition->treatment        = $request->input('treatment');
            $healthCondition->doctor           = $request->input('doctor');
            $healthCondition->hospital         = $request->input('hospital');
            $healthCondition->doctor_address   = $request->input('doctor_address');
            $healthCondition->health_situation = $request->input('health_situation');
            $healthCondition->family_situation = $request->input('family_situation');
            $healthCondition->save();

            DB::commit();
            Log::info('IAHHealthConditionController@update - Success', [
                'project_id' => $projectId
            ]);

            return response()->json($healthCondition, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHHealthConditionController@update - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to update IAH health condition details.'], 500);
        }
    }

    /**
     * Show an existing health condition record.
     */
    public function show($projectId)
    {
        try {
            Log::info('IAHHealthConditionController@show - Start', [
                'project_id' => $projectId
            ]);
            $healthCondition = ProjectIAHHealthCondition::where('project_id', $projectId)->firstOrFail();
            return response()->json($healthCondition, 200);
        } catch (\Exception $e) {
            Log::error('IAHHealthConditionController@show - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to fetch IAH health condition details.'], 500);
        }
    }

    /**
     * Edit route often returns a view or data for an edit form.
     */
    public function edit($projectId)
    {
        try {
            Log::info('IAHHealthConditionController@edit - Start', ['project_id' => $projectId]);

            $project = Project::where('project_id', $projectId)
                ->with('iahHealthCondition')
                ->firstOrFail();

            Log::info('IAHHealthConditionController@edit - Data loaded for editing', [
                'project_id' => $projectId,
            ]);

            // If you prefer to return JSON or a specialized view, adjust accordingly:
            return view('projects.partials.Edit.IAH.health_conditions', compact('project'));
        } catch (\Exception $e) {
            Log::error('IAHHealthConditionController@edit - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to load health condition details.'], 500);
        }
    }

    /**
     * Delete a health condition record.
     */
    public function destroy($projectId)
    {
        Log::info('IAHHealthConditionController@destroy - Start', ['project_id' => $projectId]);

        DB::beginTransaction();
        try {
            $healthCondition = ProjectIAHHealthCondition::where('project_id', $projectId)->firstOrFail();
            $healthCondition->delete();

            DB::commit();
            Log::info('IAHHealthConditionController@destroy - Success', [
                'project_id' => $projectId
            ]);

            return response()->json(['message' => 'IAH health condition details deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHHealthConditionController@destroy - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to delete IAH health condition details.'], 500);
        }
    }
}
