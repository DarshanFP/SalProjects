<?php

namespace App\Http\Controllers\Projects\IES;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IES\ProjectIESFamilyWorkingMembers;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IESFamilyWorkingMembersController extends Controller
{
    // Store or update family working members for a project
    // was workinung for IED
  /*  public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IES family working members', ['project_id' => $projectId]);

            // First, delete all existing family working members for the project
            ProjectIESFamilyWorkingMembers::where('project_id', $projectId)->delete();

            // Insert new family working members
            $memberNames = $request->input('member_name', []);
            $workNatures = $request->input('work_nature', []);
            $monthlyIncomes = $request->input('monthly_income', []);

            for ($i = 0; $i < count($memberNames); $i++) {
                if (!empty($memberNames[$i]) && !empty($workNatures[$i]) && !empty($monthlyIncomes[$i])) {
                    ProjectIESFamilyWorkingMembers::create([
                        'project_id' => $projectId,
                        'member_name' => $memberNames[$i],
                        'work_nature' => $workNatures[$i],
                        'monthly_income' => $monthlyIncomes[$i],
                    ]);
                }
            }

            DB::commit();
            Log::info('IES family working members saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IES family working members saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IES family working members', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save IES family working members.'], 500);
        }
    }*/
    //Updared for both IES and IIES
    public function store(Request $request, $projectId)
{
    DB::beginTransaction();

    try {
        // 1) Log the attempt
        Log::info('Storing family working members', ['project_id' => $projectId]);

        // 2) (Optional) Fetch the Project to confirm project type, etc.
        //    This also ensures the project actually exists.
        $project = Project::where('project_id', $projectId)->firstOrFail();
        Log::info('Detected project type: ' . $project->project_type);

        // 3) Delete existing family working members to allow "fresh" save
        ProjectIESFamilyWorkingMembers::where('project_id', $projectId)->delete();

        // 4) Retrieve arrays from the request
        $memberNames    = $request->input('member_name', []);
        $workNatures    = $request->input('work_nature', []);
        $monthlyIncomes = $request->input('monthly_income', []);

        // 5) Loop and create new records
        for ($i = 0; $i < count($memberNames); $i++) {
            $memberName   = $memberNames[$i];
            $workNature   = $workNatures[$i] ?? '';
            $monthlyIncome = $monthlyIncomes[$i] ?? '';

            if (!empty($memberName) && !empty($workNature) && !empty($monthlyIncome)) {
                ProjectIESFamilyWorkingMembers::create([
                    'project_id'     => $projectId,
                    'member_name'    => $memberName,
                    'work_nature'    => $workNature,
                    'monthly_income' => $monthlyIncome,
                ]);
            }
        }

        // 6) Commit & log success
        DB::commit();
        Log::info('Family working members saved successfully', ['project_id' => $projectId]);

        return response()->json(['message' => 'Family working members saved successfully.'], 200);

    } catch (\Exception $e) {
        // 7) Roll back & log error
        DB::rollBack();
        Log::error('Error saving family working members', ['error' => $e->getMessage()]);

        return response()->json(['error' => 'Failed to save family working members.'], 500);
    }
}


    // Show family working members for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IES family working members', ['project_id' => $projectId]);

            $familyMembers = ProjectIESFamilyWorkingMembers::where('project_id', $projectId)->get();

            // Return the model collection directly, not a JSON response
            return $familyMembers;
        } catch (\Exception $e) {
            Log::error('Error fetching IES family working members', ['error' => $e->getMessage()]);
            return collect([]); // Return empty collection instead of JSON error
        }
    }

    // Edit family working members for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IES family working members', ['project_id' => $projectId]);

            $familyMembers = ProjectIESFamilyWorkingMembers::where('project_id', $projectId)->get();

            // Return the data directly
            return $familyMembers;
        } catch (\Exception $e) {
            Log::error('Error editing IES family working members', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update family working members for a project
    public function update(Request $request, $projectId)
    {
        return $this->store($request, $projectId); // Reuse the store logic for update
    }

    // Delete family working members for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IES family working members', ['project_id' => $projectId]);

            ProjectIESFamilyWorkingMembers::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('IES family working members deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IES family working members deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IES family working members', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete IES family working members.'], 500);
        }
    }
}
