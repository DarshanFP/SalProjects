<?php

namespace App\Http\Controllers\Projects\RST;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\RST\ProjectRSTTargetGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TargetGroupController extends Controller
{
    // Store or update target group
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing Target Group for RST', ['project_id' => $projectId]);

            // Delete existing target group for the project and insert updated data
            ProjectRSTTargetGroup::where('project_id', $projectId)->delete();

            ProjectRSTTargetGroup::create([
                'project_id' => $projectId,
                'no_of_beneficiaries' => $request->no_of_beneficiaries,
                'beneficiaries_description_problems' => $request->beneficiaries_description_problems,
            ]);

            DB::commit();
            Log::info('Target Group saved successfully for RST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Target Group saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving Target Group for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Target Group.'], 500);
        }
    }

    // Show target group for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching Target Group for RST', ['project_id' => $projectId]);

            $targetGroup = ProjectRSTTargetGroup::where('project_id', $projectId)->first();
            return response()->json($targetGroup, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching Target Group for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch Target Group.'], 500);
        }
    }

    // Edit target group for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing Target Group for RST', ['project_id' => $projectId]);

            $targetGroup = ProjectRSTTargetGroup::where('project_id', $projectId)->first();
            return view('projects.partials.Edit.RST.target_group', compact('targetGroup'));
        } catch (\Exception $e) {
            Log::error('Error editing Target Group for RST', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Delete target group for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting Target Group for RST', ['project_id' => $projectId]);

            ProjectRSTTargetGroup::where('project_id', $projectId)->delete();
            DB::commit();
            Log::info('Target Group deleted successfully for RST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Target Group deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting Target Group for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Target Group.'], 500);
        }
    }
}
