<?php

namespace App\Http\Controllers\Projects\IIES;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IIES\ProjectIIESPersonalInfo;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Projects\IIES\StoreIIESPersonalInfoRequest;
use App\Http\Requests\Projects\IIES\UpdateIIESPersonalInfoRequest;

class IIESPersonalInfoController extends Controller
{
    /**
     * Field names for Personal Information of the Beneficiary (matches form names and model fillable).
     */
    private function getPersonalInfoFields(): array
    {
        return [
            'iies_bname',
            'iies_age',
            'iies_gender',
            'iies_dob',
            'iies_email',
            'iies_contact',
            'iies_aadhar',
            'iies_full_address',
            'iies_father_name',
            'iies_mother_name',
            'iies_mother_tongue',
            'iies_current_studies',
            'iies_bcaste',
            'iies_father_occupation',
            'iies_father_income',
            'iies_mother_occupation',
            'iies_mother_income',
        ];
    }

    /**
     * Map request input to model (only personal-info keys; avoids mass-assignment from entire form).
     */
    private function mapRequestToModel(FormRequest $request, ProjectIIESPersonalInfo $personalInfo): void
    {
        foreach ($this->getPersonalInfoFields() as $field) {
            $personalInfo->$field = $request->input($field);
        }
    }

    public function store(FormRequest $request, $projectId)
    {
        DB::beginTransaction();

        try {
            Log::info('Storing IIES Personal Info', ['project_id' => $projectId]);

            $personalInfo = ProjectIIESPersonalInfo::firstOrNew(['project_id' => $projectId]);
            $personalInfo->project_id = $projectId;
            $this->mapRequestToModel($request, $personalInfo);
            $personalInfo->save();

            DB::commit();
            return response()->json(['message' => 'IIES Personal Info saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IIES Personal Info', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save IIES Personal Info.'], 500);
        }
    }

    // public function show($projectId)
    // {
    //     return ProjectIIESPersonalInfo::where('project_id', $projectId)->firstOrFail();
    // }
    public function show($projectId)
    {
        try {
            Log::info('Fetching IIES Personal Info test for project', ['project_id' => $projectId]);

            $project = Project::where('project_id', $projectId)
                ->with('iiesPersonalInfo')
                ->firstOrFail();
//resources/views/projects/Oldprojects/show.blade.php
            return view('projects.Oldprojects.show', compact('project'));
        } catch (\Exception $e) {
            Log::error('Error fetching IIES Personal Info for show', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load IIES Personal Info.'], 500);
        }
    }
//

    /**
     * Edit the IIES Personal Info for a project.
     */
    public function edit($projectId)
    {
        try {
            $project = Project::where('project_id', $projectId)
                ->with('iiesPersonalInfo')
                ->firstOrFail();

            return view('projects.partials.Edit.IIES.personal_info', compact('project'));
        } catch (\Exception $e) {
            Log::error('Error fetching IIES Personal Info for edit', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load IIES Personal Info.'], 500);
        }
    }

    /**
     * Update the IIES Personal Info for a project.
     * Uses firstOrNew so missing personal info is created on edit (same behaviour as store).
     */
    public function update(FormRequest $request, $projectId)
    {
        DB::beginTransaction();

        try {
            Log::info('Updating IIES Personal Info', ['project_id' => $projectId]);

            $personalInfo = ProjectIIESPersonalInfo::firstOrNew(['project_id' => $projectId]);
            $personalInfo->project_id = $projectId;
            $this->mapRequestToModel($request, $personalInfo);
            $personalInfo->save();

            DB::commit();
            return response()->json(['message' => 'IIES Personal Info updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating IIES Personal Info', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update IIES Personal Info.'], 500);
        }
    }

    /**
     * Delete the IIES Personal Info for a project.
     */
    public function destroy($projectId)
    {
        DB::beginTransaction();

        try {
            $personalInfo = ProjectIIESPersonalInfo::where('project_id', $projectId)->firstOrFail();
            $personalInfo->delete();

            DB::commit();
            return response()->json(['message' => 'IIES Personal Info deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete IIES Personal Info.'], 500);
        }
    }
}
