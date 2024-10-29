<?php

namespace App\Http\Controllers\Projects\IES;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IES\ProjectIESFamilyWorkingMembers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IESFamilyWorkingMembersController extends Controller
{
    // Store or update family working members for a project
    public function store(Request $request, $projectId)
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
    }

    // Show family working members for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IES family working members', ['project_id' => $projectId]);

            $familyMembers = ProjectIESFamilyWorkingMembers::where('project_id', $projectId)->get();
            return response()->json($familyMembers, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching IES family working members', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch IES family working members.'], 500);
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
