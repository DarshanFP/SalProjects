<?php

namespace App\Http\Controllers\Projects\IIES;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\IIES\ProjectIIESFamilyWorkingMembers;
use Illuminate\Http\Request;
use App\Models\OldProjects\IIES\ProjectIIESImmediateFamilyDetails;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IIESImmediateFamilyDetailsController extends Controller
{
    /**
     * Store IIES Immediate Family Details for a project.
     */
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();

        try {
            Log::info('Storing IIES Immediate Family Details', ['project_id' => $projectId]);

            // Validate request data
            $validatedData = $request->validate($this->validationRules());

            // Update or Create record
            ProjectIIESImmediateFamilyDetails::updateOrCreate(
                ['project_id' => $projectId],
                $validatedData
            );

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
     */
    public function update(Request $request, $projectId)
    {
        DB::beginTransaction();

        try {
            Log::info('Updating IIES Immediate Family Details', ['project_id' => $projectId]);

            // Validate incoming data
            $validatedData = $request->validate($this->validationRules());

            // Find existing record
            $familyDetails = ProjectIIESImmediateFamilyDetails::where('project_id', $projectId)->first();
            if (!$familyDetails) {
                throw new \Exception('No IIES Immediate Family Details found for update.');
            }

            $familyDetails->update($validatedData);

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

    /**
     * Validation rules.
     */
    private function validationRules(): array
    {
        return [
            'iies_mother_expired'        => 'nullable|boolean',
            'iies_father_expired'        => 'nullable|boolean',
            'iies_grandmother_support'   => 'nullable|boolean',
            'iies_grandfather_support'   => 'nullable|boolean',
            'iies_father_deserted'       => 'nullable|boolean',
            'iies_family_details_others' => 'nullable|string|max:255',
            'iies_father_sick'           => 'nullable|boolean',
            'iies_father_hiv_aids'       => 'nullable|boolean',
            'iies_father_disabled'       => 'nullable|boolean',
            'iies_father_alcoholic'      => 'nullable|boolean',
            'iies_father_health_others'  => 'nullable|string|max:255',
            'iies_mother_sick'           => 'nullable|boolean',
            'iies_mother_hiv_aids'       => 'nullable|boolean',
            'iies_mother_disabled'       => 'nullable|boolean',
            'iies_mother_alcoholic'      => 'nullable|boolean',
            'iies_mother_health_others'  => 'nullable|string|max:255',
            'iies_own_house'             => 'nullable|boolean',
            'iies_rented_house'          => 'nullable|boolean',
            'iies_residential_others'    => 'nullable|string|max:255',
            'iies_family_situation'      => 'nullable|string',
            'iies_assistance_need'       => 'nullable|string',
            'iies_received_support'      => 'nullable|boolean',
            'iies_support_details'       => 'nullable|string',
            'iies_employed_with_stanns'  => 'nullable|boolean',
            'iies_employment_details'    => 'nullable|string',
        ];
    }
}
