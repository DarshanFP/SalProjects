<?php

namespace App\Http\Controllers\Projects\IES;

use App\Http\Controllers\Controller;
use App\Services\FormDataExtractor;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IES\ProjectIESPersonalInfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Projects\IES\StoreIESPersonalInfoRequest;
use App\Http\Requests\Projects\IES\UpdateIESPersonalInfoRequest;

class IESPersonalInfoController extends Controller
{
    /**
     * Store personal info for a project.
     *
     * @param  \App\Http\Requests\Projects\IES\StoreIESPersonalInfoRequest  $request
     * @param  int  $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(FormRequest $request, $projectId)
    {
        $fillable = array_diff(
            (new ProjectIESPersonalInfo())->getFillable(),
            ['project_id', 'IES_personal_id']
        );
        $data = FormDataExtractor::forFillable($request, $fillable);

        DB::beginTransaction();

        try {
            Log::info('Starting IES Personal Info storage', ['project_id' => $projectId]);

            // Find an existing record or create a new one
            $personalInfo = ProjectIESPersonalInfo::where('project_id', $projectId)->first()
                ?: new ProjectIESPersonalInfo();

            $personalInfo->project_id = $projectId;
            $personalInfo->fill($data);

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
     * @param  \App\Http\Requests\Projects\IES\UpdateIESPersonalInfoRequest  $request
     * @param  int  $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(FormRequest $request, $projectId)
    {
        // Reuse store logic
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
