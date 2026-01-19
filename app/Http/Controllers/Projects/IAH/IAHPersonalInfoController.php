<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IAH\ProjectIAHPersonalInfo;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Projects\IAH\StoreIAHPersonalInfoRequest;
use App\Http\Requests\Projects\IAH\UpdateIAHPersonalInfoRequest;

class IAHPersonalInfoController extends Controller
{
    /**
     * Store (create) personal info for a project.
     */
    public function store(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including fields not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validated = $request->all();
        
        Log::info('IAHPersonalInfoController@store - Start', [
            'project_id' => $projectId
        ]);

        DB::beginTransaction();
        try {
            // If we assume there's only one personal info row per project:
            ProjectIAHPersonalInfo::where('project_id', $projectId)->delete();

            $personalInfo = new ProjectIAHPersonalInfo();
            $personalInfo->project_id    = $projectId;
            $personalInfo->name          = $validated['name'] ?? null;
            $personalInfo->age           = $validated['age'] ?? null;
            $personalInfo->gender        = $validated['gender'] ?? null;
            $personalInfo->dob           = $validated['dob'] ?? null;
            $personalInfo->aadhar        = $validated['aadhar'] ?? null;
            $personalInfo->contact       = $validated['contact'] ?? null;
            $personalInfo->address       = $validated['address'] ?? null;
            $personalInfo->email         = $validated['email'] ?? null;
            $personalInfo->guardian_name = $validated['guardian_name'] ?? null;
            $personalInfo->children      = $validated['children'] ?? null;
            $personalInfo->caste         = $validated['caste'] ?? null;
            $personalInfo->religion      = $validated['religion'] ?? null;
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
    public function update(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including fields not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validated = $request->all();
        
        Log::info('IAHPersonalInfoController@update - Start', [
            'project_id' => $projectId
        ]);

        DB::beginTransaction();
        try {
            $personalInfo = ProjectIAHPersonalInfo::where('project_id', $projectId)->firstOrFail();
            Log::info('IAHPersonalInfoController@update - Found existing personal info row', [
                'id' => $personalInfo->id
            ]);

            $personalInfo->name          = $validated['name'] ?? null;
            $personalInfo->age           = $validated['age'] ?? null;
            $personalInfo->gender        = $validated['gender'] ?? null;
            $personalInfo->dob           = $validated['dob'] ?? null;
            $personalInfo->aadhar        = $validated['aadhar'] ?? null;
            $personalInfo->contact       = $validated['contact'] ?? null;
            $personalInfo->address       = $validated['address'] ?? null;
            $personalInfo->email         = $validated['email'] ?? null;
            $personalInfo->guardian_name = $validated['guardian_name'] ?? null;
            $personalInfo->children      = $validated['children'] ?? null;
            $personalInfo->caste         = $validated['caste'] ?? null;
            $personalInfo->religion      = $validated['religion'] ?? null;
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
