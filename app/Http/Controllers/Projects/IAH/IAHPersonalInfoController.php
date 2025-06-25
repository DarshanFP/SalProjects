<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IAH\ProjectIAHPersonalInfo;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IAHPersonalInfoController extends Controller
{
    /**
     * Store (create) personal info for a project.
     */
    public function store(Request $request, $projectId)
    {
        Log::info('IAHPersonalInfoController@store - Start', [
            'project_id'   => $projectId,
            'request_data' => $request->all()
        ]);

        DB::beginTransaction();
        try {
            // If we assume there's only one personal info row per project:
            ProjectIAHPersonalInfo::where('project_id', $projectId)->delete();

            $personalInfo = new ProjectIAHPersonalInfo();
            $personalInfo->project_id    = $projectId;
            $personalInfo->name          = $request->input('name');
            $personalInfo->age           = $request->input('age');
            $personalInfo->gender        = $request->input('gender');
            $personalInfo->dob           = $request->input('dob');
            $personalInfo->aadhar        = $request->input('aadhar');
            $personalInfo->contact       = $request->input('contact');
            $personalInfo->address       = $request->input('address');
            $personalInfo->email         = $request->input('email');
            $personalInfo->guardian_name = $request->input('guardian_name');
            $personalInfo->children      = $request->input('children');
            $personalInfo->caste         = $request->input('caste');
            $personalInfo->religion      = $request->input('religion');
            $personalInfo->save();

            DB::commit();
            Log::info('IAHPersonalInfoController@store - Success', [
                'project_id' => $projectId
            ]);
            return response()->json($personalInfo, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHPersonalInfoController@store - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to save IAH personal info.'], 500);
        }
    }

    /**
     * Update personal info record.
     */
    public function update(Request $request, $projectId)
    {
        Log::info('IAHPersonalInfoController@update - Start', [
            'project_id'   => $projectId,
            'request_data' => $request->all()
        ]);

        DB::beginTransaction();
        try {
            $personalInfo = ProjectIAHPersonalInfo::where('project_id', $projectId)->firstOrFail();
            Log::info('IAHPersonalInfoController@update - Found existing personal info row', [
                'id' => $personalInfo->id
            ]);

            $personalInfo->name          = $request->input('name');
            $personalInfo->age           = $request->input('age');
            $personalInfo->gender        = $request->input('gender');
            $personalInfo->dob           = $request->input('dob');
            $personalInfo->aadhar        = $request->input('aadhar');
            $personalInfo->contact       = $request->input('contact');
            $personalInfo->address       = $request->input('address');
            $personalInfo->email         = $request->input('email');
            $personalInfo->guardian_name = $request->input('guardian_name');
            $personalInfo->children      = $request->input('children');
            $personalInfo->caste         = $request->input('caste');
            $personalInfo->religion      = $request->input('religion');
            $personalInfo->save();

            DB::commit();
            Log::info('IAHPersonalInfoController@update - Success', [
                'project_id' => $projectId
            ]);

            return response()->json($personalInfo, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHPersonalInfoController@update - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to update IAH personal info.'], 500);
        }
    }

    /**
     * Show personal info for a project.
     */
    public function show($projectId)
    {
        try {
            Log::info('IAHPersonalInfoController@show - Start', ['project_id' => $projectId]);

            $personalInfo = ProjectIAHPersonalInfo::where('project_id', $projectId)->first();

            // Return the model object directly, not a JSON response
            return $personalInfo;
        } catch (\Exception $e) {
            Log::error('IAHPersonalInfoController@show - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Edit personal info (returns data or a view).
     */
    public function edit($projectId)
    {
        try {
            Log::info('IAHPersonalInfoController@edit - Start', ['project_id' => $projectId]);

            $project = Project::where('project_id', $projectId)
                ->with('iahPersonalInfo')
                ->firstOrFail();

            if ($project->iahPersonalInfo) {
                Log::info('IAHPersonalInfoController@edit - Fetched personal info', [
                    'personal_info_id' => $project->iahPersonalInfo->id
                ]);
            } else {
                Log::warning('IAHPersonalInfoController@edit - No personal info found', [
                    'project_id' => $projectId
                ]);
            }

            // Return a view or JSON as needed
            return view('projects.partials.Edit.IAH.personal_info', compact('project'));
        } catch (\Exception $e) {
            Log::error('IAHPersonalInfoController@edit - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to load personal info.'], 500);
        }
    }

    /**
     * Delete personal info.
     */
    public function destroy($projectId)
    {
        Log::info('IAHPersonalInfoController@destroy - Start', ['project_id' => $projectId]);

        DB::beginTransaction();
        try {
            $personalInfo = ProjectIAHPersonalInfo::where('project_id', $projectId)->firstOrFail();
            $personalInfo->delete();

            DB::commit();
            Log::info('IAHPersonalInfoController@destroy - Success', [
                'project_id' => $projectId
            ]);
            return response()->json(['message' => 'IAH personal info deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHPersonalInfoController@destroy - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to delete IAH personal info.'], 500);
        }
    }
}
