<?php

namespace App\Http\Controllers\Projects\ILP;

use App\Http\Controllers\Controller;
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
        // Use all() to get all form data including fields not in StoreProjectRequest validation rules
        $validated = $request->all();
        
        DB::beginTransaction();
        try {
            Log::info('Storing ILP Personal Information', ['project_id' => $projectId]);

            ProjectILPPersonalInfo::updateOrCreate(
                ['project_id' => $projectId],
                [
                    'name' => $validated['name'] ?? null,
                    'age' => $validated['age'] ?? null,
                    'gender' => $validated['gender'] ?? null,
                    'dob' => $validated['dob'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'contact_no' => $validated['contact_no'] ?? null,
                    'aadhar_id' => $validated['aadhar_id'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'occupation' => $validated['occupation'] ?? null,
                    'marital_status' => $validated['marital_status'] ?? null,
                    'spouse_name' => ($validated['marital_status'] ?? '') == 'Married' ? ($validated['spouse_name'] ?? null) : null,
                    'children_no' => $validated['children_no'] ?? null,
                    'children_edu' => $validated['children_edu'] ?? null,
                    'religion' => $validated['religion'] ?? null,
                    'caste' => $validated['caste'] ?? null,
                    'family_situation' => $validated['family_situation'] ?? null,
                    'small_business_status' => $validated['small_business_status'] ?? null,
                    'small_business_details' => ($validated['small_business_status'] ?? '') == '1' ? ($validated['small_business_details'] ?? null) : null,
                    'monthly_income' => $validated['monthly_income'] ?? null,
                    'business_plan' => $validated['business_plan'] ?? null,
                ]
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
