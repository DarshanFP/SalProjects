<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IAH\ProjectIAHEarningMembers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IAHEarningMembersController extends Controller
{
    // Store earning members details for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IAH earning members details', ['project_id' => $projectId]);

            // First, delete all existing earning members for the project
            ProjectIAHEarningMembers::where('project_id', $projectId)->delete();

            // Insert new earning members
            $memberNames = $request->input('member_name', []);
            $workTypes = $request->input('work_type', []);
            $monthlyIncomes = $request->input('monthly_income', []);

            for ($i = 0; $i < count($memberNames); $i++) {
                if (!empty($memberNames[$i]) && !empty($workTypes[$i]) && !empty($monthlyIncomes[$i])) {
                    ProjectIAHEarningMembers::create([
                        'project_id' => $projectId,
                        'member_name' => $memberNames[$i],
                        'work_type' => $workTypes[$i],
                        'monthly_income' => $monthlyIncomes[$i],
                    ]);
                }
            }

            DB::commit();
            Log::info('IAH earning members details saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IAH earning members details saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IAH earning members details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save IAH earning members details.'], 500);
        }
    }

    // Show earning members details for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IAH earning members details', ['project_id' => $projectId]);

            $earningMembers = ProjectIAHEarningMembers::where('project_id', $projectId)->get();
            return response()->json($earningMembers, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching IAH earning members details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch IAH earning members details.'], 500);
        }
    }

    // Edit earning members details for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IAH earning members details', ['project_id' => $projectId]);

            $earningMembers = ProjectIAHEarningMembers::where('project_id', $projectId)->get();

            // Return the data directly
            return $earningMembers;
        } catch (\Exception $e) {
            Log::error('Error editing IAH earning members details', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update earning members details for a project
    public function update(Request $request, $projectId)
    {
        return $this->store($request, $projectId); // Reuse the store logic for update
    }

    // Delete earning members details for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IAH earning members details', ['project_id' => $projectId]);

            ProjectIAHEarningMembers::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('IAH earning members details deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IAH earning members details deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IAH earning members details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete IAH earning members details.'], 500);
        }
    }
}
