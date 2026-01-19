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
     * Store IIES Immediate Family Details for a project.
     */
    public function store(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including fields not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validatedData = $request->all();
        
        DB::beginTransaction();

        try {
            Log::info('Storing IIES Immediate Family Details', ['project_id' => $projectId]);

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
    public function update(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including fields not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validatedData = $request->all();
        
        DB::beginTransaction();

        try {
            Log::info('Updating IIES Immediate Family Details', ['project_id' => $projectId]);

            // Get all boolean fields that should be handled
            $booleanFields = [
                'iies_mother_expired',
                'iies_father_expired',
                'iies_grandmother_support',
                'iies_grandfather_support',
                'iies_father_deserted',
                'iies_father_sick',
                'iies_father_hiv_aids',
                'iies_father_disabled',
                'iies_father_alcoholic',
                'iies_mother_sick',
                'iies_mother_hiv_aids',
                'iies_mother_disabled',
                'iies_mother_alcoholic',
                'iies_own_house',
                'iies_rented_house',
                'iies_received_support',
                'iies_employed_with_stanns'
            ];

            // Prepare data with explicit false values for unchecked checkboxes
            $data = $validatedData;
            foreach ($booleanFields as $field) {
                $data[$field] = $request->has($field) ? true : false;
            }

            // Use validated data
            $validatedData = $data;

            // Find existing record or create new one
            $familyDetails = ProjectIIESImmediateFamilyDetails::updateOrCreate(
                ['project_id' => $projectId],
                $validatedData
            );

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
