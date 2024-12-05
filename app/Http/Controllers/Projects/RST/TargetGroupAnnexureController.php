<?php

namespace App\Http\Controllers\Projects\RST;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\RST\ProjectRSTTargetGroupAnnexure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TargetGroupAnnexureController extends Controller
{
    // Store or update target group annexure
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing Target Group Annexure for RST', ['project_id' => $projectId]);

            // First, delete existing target group annexure for the project to handle both create and update
            ProjectRSTTargetGroupAnnexure::where('project_id', $projectId)->delete();

            // Loop through the arrays and store the target group annexure information
            if ($request->rst_name) {
                foreach ($request->rst_name as $index => $rst_name) {
                    ProjectRSTTargetGroupAnnexure::create([
                        'project_id'             => $projectId,
                        'rst_name'               => $rst_name,
                        'rst_religion'           => $request->rst_religion[$index] ?? null,
                        'rst_caste'              => $request->rst_caste[$index] ?? null,
                        'rst_education_background' => $request->rst_education_background[$index] ?? null,
                        'rst_family_situation'   => $request->rst_family_situation[$index] ?? null,
                        'rst_paragraph'          => $request->rst_paragraph[$index] ?? null,
                    ]);
                }
            }

            DB::commit();
            Log::info('Target Group Annexure saved successfully for RST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Target Group Annexure saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving Target Group Annexure for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Target Group Annexure.'], 500);
        }
    }

    // Show target group annexure for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching Target Group Annexure for RST', ['project_id' => $projectId]);

            $RSTTargetGroupAnnexure = ProjectRSTTargetGroupAnnexure::where('project_id', $projectId)->get();

            if ($RSTTargetGroupAnnexure->isEmpty()) {
                Log::warning('No Target Group Annexure data found', ['project_id' => $projectId]);
                return collect(); // Return an empty collection if no data found
            }

            return $RSTTargetGroupAnnexure;
        } catch (\Exception $e) {
            Log::error('Error fetching Target Group Annexure for RST', ['error' => $e->getMessage()]);
            return null; // Return null on error
        }
    }


    // Edit target group annexure for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing Target Group Annexure for RST', ['project_id' => $projectId]);

            // Fetch all entries for the project
            $targetGroupAnnexures = ProjectRSTTargetGroupAnnexure::where('project_id', $projectId)->get();
            return $targetGroupAnnexures;
        } catch (\Exception $e) {
            Log::error('Error editing Target Group Annexure for RST', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update target group annexure for a project
    public function update(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Updating Target Group Annexure for RST', ['project_id' => $projectId]);

            // First, delete existing target group annexure for the project to handle both create and update
            ProjectRSTTargetGroupAnnexure::where('project_id', $projectId)->delete();

            // Loop through the arrays and store the target group annexure information
            if ($request->rst_name) {
                foreach ($request->rst_name as $index => $rst_name) {
                    ProjectRSTTargetGroupAnnexure::create([
                        'project_id'             => $projectId,
                        'rst_name'               => $rst_name,
                        'rst_religion'           => $request->rst_religion[$index] ?? null,
                        'rst_caste'              => $request->rst_caste[$index] ?? null,
                        'rst_education_background' => $request->rst_education_background[$index] ?? null,
                        'rst_family_situation'   => $request->rst_family_situation[$index] ?? null,
                        'rst_paragraph'          => $request->rst_paragraph[$index] ?? null,
                    ]);
                }
            }

            DB::commit();
            Log::info('Target Group Annexure updated successfully for RST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Target Group Annexure updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating Target Group Annexure for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update Target Group Annexure.'], 500);
        }
    }

    // Delete target group annexure for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting Target Group Annexure for RST', ['project_id' => $projectId]);

            ProjectRSTTargetGroupAnnexure::where('project_id', $projectId)->delete();
            DB::commit();
            Log::info('Target Group Annexure deleted successfully for RST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Target Group Annexure deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting Target Group Annexure for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Target Group Annexure.'], 500);
        }
    }
}
