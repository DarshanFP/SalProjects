<?php

namespace App\Http\Controllers\Projects\IIES;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\IIES\ProjectIIESFamilyWorkingMembers;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IIES\ProjectIIESImmediateFamilyDetails;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Projects\IIES\StoreIIESImmediateFamilyDetailsRequest;
use App\Http\Requests\Projects\IIES\UpdateIIESImmediateFamilyDetailsRequest;

class IIESImmediateFamilyDetailsController extends Controller
{
    /**
     * Checkbox/radio fields stored as 1 or 0.
     */
    private function getImmediateFamilyBooleanFields(): array
    {
        return [
            'iies_mother_expired', 'iies_father_expired', 'iies_grandmother_support', 'iies_grandfather_support',
            'iies_father_deserted', 'iies_father_sick', 'iies_father_hiv_aids', 'iies_father_disabled', 'iies_father_alcoholic',
            'iies_mother_sick', 'iies_mother_hiv_aids', 'iies_mother_disabled', 'iies_mother_alcoholic',
            'iies_own_house', 'iies_rented_house', 'iies_received_support', 'iies_employed_with_stanns',
        ];
    }

    /**
     * Text fields from Immediate Family Details form.
     */
    private function getImmediateFamilyTextFields(): array
    {
        return [
            'iies_family_details_others', 'iies_father_health_others', 'iies_mother_health_others',
            'iies_residential_others', 'iies_family_situation', 'iies_assistance_need',
            'iies_support_details', 'iies_employment_details',
        ];
    }

    private function mapRequestToImmediateFamily(FormRequest $request, ProjectIIESImmediateFamilyDetails $model): void
    {
        foreach ($this->getImmediateFamilyBooleanFields() as $field) {
            $model->$field = $request->has($field) ? 1 : 0;
        }
        foreach ($this->getImmediateFamilyTextFields() as $field) {
            $model->$field = $request->input($field);
        }
    }

    /**
     * Store IIES Immediate Family Details for a project.
     */
    public function store(FormRequest $request, $projectId)
    {
        DB::beginTransaction();

        try {
            Log::info('Storing IIES Immediate Family Details', ['project_id' => $projectId]);

            $detail = ProjectIIESImmediateFamilyDetails::firstOrNew(['project_id' => $projectId]);
            $detail->project_id = $projectId;
            $this->mapRequestToImmediateFamily($request, $detail);
            $detail->save();

            DB::commit();
            return response()->json(['message' => 'IIES immediate family details saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IIES immediate family details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save details.'], 500);
        }
    }

    /**
     * Retrieve IIES Immediate Family Details for a project.
     */
    // public function show($projectId)
    // {
    //     try {
    //         Log::info('Fetching IIES immediate family details', ['project_id' => $projectId]);
    //         $familyDetails = ProjectIIESImmediateFamilyDetails::where('project_id', $projectId)->firstOrFail();
    //         return $familyDetails;
    //     } catch (\Exception $e) {
    //         Log::error('Error fetching IIES immediate family details', ['error' => $e->getMessage()]);
    //         return response()->json(['error' => 'Details not found.'], 404);
    //     }
    // }
    public function show($projectId)
{
    try {
        Log::info('Fetching IIES Immediate Family Details', ['project_id' => $projectId]);

        $familyDetails = ProjectIIESImmediateFamilyDetails::where('project_id', $projectId)->first();

        if (!$familyDetails) {
            Log::warning('No IIES Immediate Family Details found', ['project_id' => $projectId]);
            return null; // Return null instead of failing
        }

        Log::info('IIES Immediate Family Details retrieved successfully', ['data' => $familyDetails]);

        return $familyDetails;
    } catch (\Exception $e) {
        Log::error('Error fetching IIES Immediate Family Details', ['error' => $e->getMessage()]);
        return null;
    }
}




    /**
     * Edit IIES Immediate Family Details (returns a Blade partial view).
     */
    public function edit($projectId)
    {
        try {
            Log::info('Editing IIES Immediate Family Details', ['project_id' => $projectId]);

            $project = Project::where('project_id', $projectId)
                ->with('iiesImmediateFamilyDetails')
                ->firstOrFail();

            return view('projects.partials.Edit.IIES.immediate_family_details', compact('project'));
        } catch (\Exception $e) {
            Log::error('Error editing IIES Immediate Family Details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load details.'], 500);
        }
    }

    /**
     * Update IIES Immediate Family Details for a project.
     * Uses firstOrNew so missing record is created on edit (same behaviour as store).
     */
    public function update(FormRequest $request, $projectId)
    {
        DB::beginTransaction();

        try {
            Log::info('Updating IIES Immediate Family Details', ['project_id' => $projectId]);

            $detail = ProjectIIESImmediateFamilyDetails::firstOrNew(['project_id' => $projectId]);
            $detail->project_id = $projectId;
            $this->mapRequestToImmediateFamily($request, $detail);
            $detail->save();

            DB::commit();
            return response()->json(['message' => 'IIES Immediate Family Details updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating IIES Immediate Family Details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update details.'], 500);
        }
    }

    /**
     * Delete IIES Immediate Family Details for a project.
     */
    public function destroy($projectId)
    {
        DB::beginTransaction();

        try {
            Log::info('Deleting IIES immediate family details', ['project_id' => $projectId]);

            $familyDetails = ProjectIIESImmediateFamilyDetails::where('project_id', $projectId)->firstOrFail();
            $familyDetails->delete();

            DB::commit();
            return response()->json(['message' => 'IIES immediate family details deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IIES immediate family details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete details.'], 500);
        }
    }

}
