<?php

namespace App\Http\Controllers\Projects\IIES;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IIES\ProjectIIESFamilyWorkingMembers;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IIESFamilyWorkingMembersController extends Controller
{
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();

        try {
            Log::info('Storing IIES family working members', ['project_id' => $projectId, 'request_data' => $request->all()]);

            $project = Project::where('project_id', $projectId)->firstOrFail();

            ProjectIIESFamilyWorkingMembers::where('project_id', $projectId)->delete();

            $memberNames    = $request->input('iies_member_name', []);
            $workNatures    = $request->input('iies_work_nature', []);
            $monthlyIncomes = $request->input('iies_monthly_income', []);

            for ($i = 0; $i < count($memberNames); $i++) {
                if (!empty($memberNames[$i]) && !empty($workNatures[$i]) && !empty($monthlyIncomes[$i])) {
                    ProjectIIESFamilyWorkingMembers::create([
                        'project_id'          => $projectId,
                        'iies_member_name'    => $memberNames[$i],
                        'iies_work_nature'    => $workNatures[$i],
                        'iies_monthly_income' => $monthlyIncomes[$i],
                    ]);
                }
            }

            DB::commit();
            Log::info('IIES family working members saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IIES family working members saved successfully.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IIES family working members', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save IIES family working members.'], 500);
        }
    }

    // public function show($projectId)
    // {
    //     try {
    //         Log::info('Fetching IIES family working members', ['project_id' => $projectId]);
    //         $familyMembers = ProjectIIESFamilyWorkingMembers::where('project_id', $projectId)->get();
    //         Log::info('Fetched IIES family working members', ['project_id' => $projectId, 'data' => $familyMembers]);
    //         return response()->json($familyMembers, 200);
    //     } catch (\Exception $e) {
    //         Log::error('Error fetching IIES family working members', ['error' => $e->getMessage()]);
    //         return response()->json(['error' => 'Failed to fetch IIES family working members.'], 500);
    //     }
    // }
    public function show($projectId)
    {
        try {
            Log::info('Fetching IIES Family Working Members for project', ['project_id' => $projectId]);

            // Retrieve family working members from the database
            $familyMembers = ProjectIIESFamilyWorkingMembers::where('project_id', $projectId)->get();

            if ($familyMembers->isEmpty()) {
                Log::warning('No IIES Family Working Members found', ['project_id' => $projectId]);
            } else {
                Log::info('Fetched IIES Family Working Members', [
                    'project_id' => $projectId,
                    'data_count' => $familyMembers->count(),
                    'data' => $familyMembers
                ]);
            }
//
            return $familyMembers;
        } catch (\Exception $e) {
            Log::error('Error fetching IIES Family Working Members', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to fetch IIES family working members.'], 500);
        }
    }

    public function edit($projectId)
{
    try {
        Log::info('Editing IIES family working members', ['project_id' => $projectId]);

        $project = Project::where('project_id', $projectId)
            ->with('iiesFamilyWorkingMembers')
            ->firstOrFail();

        return view('projects.partials.Edit.IIES.family_working_members', compact('project'));
    } catch (\Exception $e) {
        Log::error('Error fetching IIES family working members for edit', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to load IIES family working members.'], 500);
    }
}

public function update(Request $request, $projectId)
{
    DB::beginTransaction();

    try {
        Log::info('Updating IIES family working members', ['project_id' => $projectId]);

        $validatedData = $request->validate([
            'iies_member_name'    => 'array',
            'iies_member_name.*'  => 'nullable|string|max:255',
            'iies_work_nature'    => 'array',
            'iies_work_nature.*'  => 'nullable|string|max:255',
            'iies_monthly_income' => 'array',
            'iies_monthly_income.*' => 'nullable|numeric|min:0',
        ]);

        // Delete old records
        ProjectIIESFamilyWorkingMembers::where('project_id', $projectId)->delete();

        // Insert new records
        $memberNames    = $validatedData['iies_member_name'] ?? [];
        $workNatures    = $validatedData['iies_work_nature'] ?? [];
        $monthlyIncomes = $validatedData['iies_monthly_income'] ?? [];

        for ($i = 0; $i < count($memberNames); $i++) {
            if (!empty($memberNames[$i]) && !empty($workNatures[$i]) && isset($monthlyIncomes[$i])) {
                ProjectIIESFamilyWorkingMembers::create([
                    'project_id'          => $projectId,
                    'iies_member_name'    => $memberNames[$i],
                    'iies_work_nature'    => $workNatures[$i],
                    'iies_monthly_income' => $monthlyIncomes[$i],
                ]);
            }
        }

        DB::commit();
        Log::info('IIES family working members updated successfully', ['project_id' => $projectId]);
        return response()->json(['message' => 'IIES family working members updated successfully.'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating IIES family working members', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to update IIES family working members.'], 500);
    }
}


    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IIES family working members', ['project_id' => $projectId]);

            ProjectIIESFamilyWorkingMembers::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('IIES family working members deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IIES family working members deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IIES family working members', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete IIES family working members.'], 500);
        }
    }
}
