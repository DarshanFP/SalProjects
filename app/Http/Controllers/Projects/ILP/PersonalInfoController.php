<?php

namespace App\Http\Controllers\Projects\ILP;

use App\Http\Controllers\Controller;
use App\Services\FormDataExtractor;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\ILP\ProjectILPPersonalInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\ILP\StoreILPPersonalInfoRequest;
use App\Http\Requests\Projects\ILP\UpdateILPPersonalInfoRequest;

class PersonalInfoController extends Controller
{
    // Store or update personal information
    public function store(FormRequest $request, $projectId)
    {
        $fillable = array_diff(
            (new ProjectILPPersonalInfo())->getFillable(),
            ['project_id', 'ILP_personal_id']
        );
        $data = FormDataExtractor::forFillable($request, $fillable);

        // Preserve conditional logic (unchanged from original)
        $data['spouse_name'] = ($data['marital_status'] ?? '') == 'Married' ? ($data['spouse_name'] ?? null) : null;
        $rawStatus = $data['small_business_status'] ?? 0;
        $data['small_business_status'] = (int) $rawStatus;
        $data['small_business_details'] = ($rawStatus == '1' || $rawStatus === 1) ? ($data['small_business_details'] ?? null) : null;

        DB::beginTransaction();
        try {
            Log::info('Storing ILP Personal Information', ['project_id' => $projectId]);

            ProjectILPPersonalInfo::updateOrCreate(
                ['project_id' => $projectId],
                $data
            );

            DB::commit();
            Log::info('ILP Personal Information saved successfully', ['project_id' => $projectId]);

            return response()->json(['message' => 'Personal Information saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving ILP Personal Information', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Personal Information.'], 500);
        }
    }

    // Show personal information for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching ILP Personal Information', ['project_id' => $projectId]);

            $personalInfo = ProjectILPPersonalInfo::where('project_id', $projectId)->first();

            return $personalInfo; // Return model object directly
        } catch (\Exception $e) {
            Log::error('Error fetching ILP Personal Information', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch Personal Information.'], 500);
        }
    }


    public function edit($projectId)
    {
        try {
            Log::info('Editing ILP Personal Information', ['project_id' => $projectId]);

            // Fetch the personal info for the given project
            $personalInfo = ProjectILPPersonalInfo::where('project_id', $projectId)->first();

            // Log the fetched data
            if ($personalInfo) {
                Log::info('Fetched Personal Information for Edit - PersonalInfoController - ', ['personal_info' => $personalInfo->toArray()]);
            } else {
                Log::warning('No Personal Information found for Edit', ['project_id' => $projectId]);
            }

            // Return raw model data
            return $personalInfo;
        } catch (\Exception $e) {
            Log::error('Error editing ILP Personal Information', ['error' => $e->getMessage()]);
            return null; // Return null if an error occurs
        }
    }
    public function update(FormRequest $request, $projectId)
    {
        // Reuse store logic
        return $this->store($request, $projectId);
    }
//

    // Delete personal information for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting ILP Personal Information', ['project_id' => $projectId]);

            ProjectILPPersonalInfo::where('project_id', $projectId)->delete();
            DB::commit();
            Log::info('ILP Personal Information deleted successfully', ['project_id' => $projectId]);

            return response()->json(['message' => 'Personal Information deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting ILP Personal Information', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Personal Information.'], 500);
        }
    }
}
