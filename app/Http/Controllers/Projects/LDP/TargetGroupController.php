<?php

namespace App\Http\Controllers\Projects\LDP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\LDP\ProjectLDPTargetGroup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TargetGroupController extends Controller
{
    // Store or update the target group
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing Annexed Target Group for LDP', ['project_id' => $projectId]);

            // Delete existing target groups and insert updated data
            ProjectLDPTargetGroup::where('project_id', $projectId)->delete();

            foreach ($request->beneficiary_name as $index => $name) {
                ProjectLDPTargetGroup::create([
                    'project_id' => $projectId,
                    'beneficiary_name' => $name,
                    'family_situation' => $request->family_situation[$index],
                    'nature_of_livelihood' => $request->nature_of_livelihood[$index],
                    'amount_requested' => $request->amount_requested[$index],
                ]);
            }

            DB::commit();
            Log::info('Target Group saved successfully for LDP', ['project_id' => $projectId]);
            return response()->json(['message' => 'Target Group saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving Target Group for LDP', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Target Group.'], 500);
        }
    }

    // Show the target group for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching Target Group for LDP', ['project_id' => $projectId]);

            $targetGroups = ProjectLDPTargetGroup::where('project_id', $projectId)->get();
            return response()->json($targetGroups, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching Target Group for LDP', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch Target Group.'], 500);
        }
    }

    // Edit the target group for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing Target Group for LDP', ['project_id' => $projectId]);

            $targetGroups = ProjectLDPTargetGroup::where('project_id', $projectId)->get();
            return view('projects.partials.Edit.LDP.annexed_target_group', compact('targetGroups'));
        } catch (\Exception $e) {
            Log::error('Error editing Target Group for LDP', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Delete the target group for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting Target Group for LDP', ['project_id' => $projectId]);

            ProjectLDPTargetGroup::where('project_id', $projectId)->delete();
            DB::commit();
            Log::info('Target Group deleted successfully for LDP', ['project_id' => $projectId]);
            return response()->json(['message' => 'Target Group deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting Target Group for LDP', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Target Group.'], 500);
        }
    }
}
