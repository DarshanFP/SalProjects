<?php

namespace App\Http\Controllers\Projects\ILP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\ILP\ProjectILPPersonalInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PersonalInfoController extends Controller
{
    // Store or update personal information
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing ILP Personal Information', ['project_id' => $projectId]);

            ProjectILPPersonalInfo::updateOrCreate(
                ['project_id' => $projectId],
                [
                    'name' => $request->name,
                    'age' => $request->age,
                    'gender' => $request->gender,
                    'dob' => $request->dob,
                    'email' => $request->email,
                    'contact_no' => $request->contact_no,
                    'aadhar_id' => $request->aadhar_id,
                    'address' => $request->address,
                    'occupation' => $request->occupation,
                    'marital_status' => $request->marital_status,
                    'spouse_name' => $request->marital_status == 'Married' ? $request->spouse_name : null,
                    'children_no' => $request->children_no,
                    'children_edu' => $request->children_edu,
                    'religion' => $request->religion,
                    'caste' => $request->caste,
                    'family_situation' => $request->family_situation,
                    'small_business_status' => $request->small_business_status,
                    'small_business_details' => $request->small_business_status == '1' ? $request->small_business_details : null,
                    'monthly_income' => $request->monthly_income,
                    'business_plan' => $request->business_plan,
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

            return response()->json($personalInfo, 200);
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
    public function update(Request $request, $projectId)
    {
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
