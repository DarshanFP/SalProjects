<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IAH\ProjectIAHPersonalInfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IAHPersonalInfoController extends Controller
{
    // Store personal information for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IAH personal info', ['project_id' => $projectId]);

            $personalInfo = new ProjectIAHPersonalInfo();
            $personalInfo->project_id = $projectId;
            $personalInfo->name = $request->input('name');
            $personalInfo->age = $request->input('age');
            $personalInfo->gender = $request->input('gender');
            $personalInfo->dob = $request->input('dob');
            $personalInfo->aadhar = $request->input('aadhar');
            $personalInfo->contact = $request->input('contact');
            $personalInfo->address = $request->input('address');
            $personalInfo->email = $request->input('email');
            $personalInfo->guardian_name = $request->input('guardian_name');
            $personalInfo->children = $request->input('children');
            $personalInfo->caste = $request->input('caste');
            $personalInfo->religion = $request->input('religion');
            $personalInfo->save();

            DB::commit();
            Log::info('IAH personal info saved successfully', ['project_id' => $projectId]);
            return response()->json($personalInfo, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IAH personal info', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save IAH personal info.'], 500);
        }
    }

    // Show personal info for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IAH personal info', ['project_id' => $projectId]);

            $personalInfo = ProjectIAHPersonalInfo::where('project_id', $projectId)->firstOrFail();
            return response()->json($personalInfo, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching IAH personal info', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch IAH personal info.'], 500);
        }
    }

    // Edit personal info for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IAH personal info', ['project_id' => $projectId]);

            $personalInfo = ProjectIAHPersonalInfo::where('project_id', $projectId)->firstOrFail();

            // Return the data directly
            return $personalInfo;
        } catch (\Exception $e) {
            Log::error('Error editing IAH personal info', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update personal info for a project
    public function update(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Updating IAH personal info', ['project_id' => $projectId]);

            $personalInfo = ProjectIAHPersonalInfo::where('project_id', $projectId)->firstOrFail();
            $personalInfo->name = $request->input('name');
            $personalInfo->age = $request->input('age');
            $personalInfo->gender = $request->input('gender');
            $personalInfo->dob = $request->input('dob');
            $personalInfo->aadhar = $request->input('aadhar');
            $personalInfo->contact = $request->input('contact');
            $personalInfo->address = $request->input('address');
            $personalInfo->email = $request->input('email');
            $personalInfo->guardian_name = $request->input('guardian_name');
            $personalInfo->children = $request->input('children');
            $personalInfo->caste = $request->input('caste');
            $personalInfo->religion = $request->input('religion');
            $personalInfo->save();

            DB::commit();
            Log::info('IAH personal info updated successfully', ['project_id' => $projectId]);
            return response()->json($personalInfo, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating IAH personal info', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update IAH personal info.'], 500);
        }
    }

    // Delete personal info for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IAH personal info', ['project_id' => $projectId]);

            $personalInfo = ProjectIAHPersonalInfo::where('project_id', $projectId)->firstOrFail();
            $personalInfo->delete();

            DB::commit();
            Log::info('IAH personal info deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IAH personal info deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IAH personal info', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete IAH personal info.'], 500);
        }
    }
}
