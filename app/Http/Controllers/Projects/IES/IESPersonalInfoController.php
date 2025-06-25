<?php

namespace App\Http\Controllers\Projects\IES;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IES\ProjectIESPersonalInfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IESPersonalInfoController extends Controller
{
    /**
     * Store personal info for a project.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();

        try {
            Log::info('Starting IES Personal Info storage', ['project_id' => $projectId]);

            // Log the incoming request data
            Log::info('Request Data Received', $request->all());

            // Find an existing record or create a new one
            $personalInfo = ProjectIESPersonalInfo::where('project_id', $projectId)->first()
                ?: new ProjectIESPersonalInfo();

            $personalInfo->project_id = $projectId;

            // Use request data while ensuring null values for empty fields
            $fields = [
                'bname', 'age', 'gender', 'dob', 'email', 'contact', 'aadhar',
                'full_address', 'father_name', 'mother_name', 'mother_tongue',
                'current_studies', 'bcaste', 'father_occupation', 'father_income',
                'mother_occupation', 'mother_income'
            ];

            foreach ($fields as $field) {
                $value = $request->input($field, null);
                $personalInfo->$field = $value;
                Log::info("Field: $field, Value: $value"); // Log each field's value
            }

            // Save the data
            $personalInfo->save();

            Log::info('IES Personal Info saved successfully', [
                'project_id' => $projectId,
                'personal_info_id' => $personalInfo->IES_personal_id
            ]);

            DB::commit();

            return response()->json(['message' => 'IES Personal Info saved successfully.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            // Log detailed error information
            Log::error('Error saving IES Personal Info', [
                'project_id' => $projectId,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Failed to save IES Personal Info.'], 500);
        }
    }


    /**
     * Show personal info for a project.
     *
     * @param  int  $projectId
     * @return \App\Models\OldProjects\IES\ProjectIESPersonalInfo|null
     */
    public function show($projectId)
    {
        try {
            Log::info('Fetching IES Personal Info', ['project_id' => $projectId]);

            $personalInfo = ProjectIESPersonalInfo::where('project_id', $projectId)->first();

            // Return the model object directly, not a JSON response
            return $personalInfo;
        } catch (\Exception $e) {
            Log::error('Error fetching IES Personal Info', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);

            return null; // Return null instead of JSON error
        }
    }

    /**
     * Edit personal info for a project.
     *
     * @param  int  $projectId
     * @return \App\Models\OldProjects\IES\ProjectIESPersonalInfo|null
     */
    public function edit($projectId)
    {
        try {
            Log::info('Editing IES Personal Info', ['project_id' => $projectId]);

            $personalInfo = ProjectIESPersonalInfo::where('project_id', $projectId)->firstOrFail();

            // Return the raw model data if you load it via AJAX or pass it to a view as needed
            return $personalInfo;

        } catch (\Exception $e) {
            Log::error('Error editing IES Personal Info', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);

            // Return null or handle the exception as your UI requires
            return null;
        }
    }

    /**
     * Update personal info for a project.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $projectId)
    {
        // Here we reuse the store logic for update, to avoid duplicating code.
        return $this->store($request, $projectId);
    }

    /**
     * Delete personal info for a project.
     *
     * @param  int  $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($projectId)
    {
        DB::beginTransaction();

        try {
            Log::info('Deleting IES Personal Info', ['project_id' => $projectId]);

            $personalInfo = ProjectIESPersonalInfo::where('project_id', $projectId)->firstOrFail();
            $personalInfo->delete();

            DB::commit();
            Log::info('IES Personal Info deleted successfully', ['project_id' => $projectId]);

            return response()->json(['message' => 'IES Personal Info deleted successfully.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IES Personal Info', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to delete IES Personal Info.'], 500);
        }
    }
}
