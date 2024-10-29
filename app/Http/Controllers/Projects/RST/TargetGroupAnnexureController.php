<?php

namespace App\Http\Controllers\Projects\RST;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\RST\ProjectRSTTargetGroupAnnexure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TargetGroupAnnexureController extends Controller
{
    // Store or update target group annexures
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing Target Group Annexures for RST', ['project_id' => $projectId]);

            // Delete existing target group annexures for the project and insert new data
            ProjectRSTTargetGroupAnnexure::where('project_id', $projectId)->delete();

            foreach ($request->name as $index => $name) {
                ProjectRSTTargetGroupAnnexure::create([
                    'project_id' => $projectId,
                    'name' => $name,
                    'religion' => $request->religion[$index],
                    'caste' => $request->caste[$index],
                    'education_background' => $request->education_background[$index],
                    'family_situation' => $request->family_situation[$index],
                    'paragraph' => $request->paragraph[$index],
                ]);
            }

            DB::commit();
            Log::info('Target Group Annexures saved successfully for RST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Target Group Annexures saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving Target Group Annexures for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Target Group Annexures.'], 500);
        }
    }

    // Show target group annexures for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching Target Group Annexures for RST', ['project_id' => $projectId]);

            $targetGroupAnnexures = ProjectRSTTargetGroupAnnexure::where('project_id', $projectId)->get();
            return response()->json($targetGroupAnnexures, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching Target Group Annexures for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch Target Group Annexures.'], 500);
        }
    }

    // Edit target group annexures for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing Target Group Annexures for RST', ['project_id' => $projectId]);

            $targetGroupAnnexures = ProjectRSTTargetGroupAnnexure::where('project_id', $projectId)->get();
            return view('projects.partials.Edit.RST.target_group_annexure', compact('targetGroupAnnexures'));
        } catch (\Exception $e) {
            Log::error('Error editing Target Group Annexures for RST', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Delete target group annexures for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting Target Group Annexures for RST', ['project_id' => $projectId]);

            ProjectRSTTargetGroupAnnexure::where('project_id', $projectId)->delete();
            DB::commit();
            Log::info('Target Group Annexures deleted successfully for RST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Target Group Annexures deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting Target Group Annexures for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Target Group Annexures.'], 500);
        }
    }
}
