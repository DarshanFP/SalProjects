<?php

namespace App\Http\Controllers\Projects\LDP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\LDP\ProjectLDPInterventionLogic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterventionLogicController extends Controller
{
    // Store or update intervention logic
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing LDP Intervention Logic', ['project_id' => $projectId]);

            // Use create or update logic
            ProjectLDPInterventionLogic::updateOrCreate(
                ['project_id' => $projectId], // Search by project_id
                ['intervention_description' => $request->intervention_description] // Update intervention description
            );

            DB::commit();
            Log::info('LDP Intervention Logic saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Intervention logic saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving LDP Intervention Logic', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save intervention logic.'], 500);
        }
    }

    // Show intervention logic for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching LDP Intervention Logic', ['project_id' => $projectId]);

            $interventionLogic = ProjectLDPInterventionLogic::where('project_id', $projectId)->first();

            if (!$interventionLogic) {
                Log::warning('No Intervention Logic found', ['project_id' => $projectId]);
                return null; // Return null if no record is found
            }

            return $interventionLogic; // Return the model directly
        } catch (\Exception $e) {
            Log::error('Error fetching LDP Intervention Logic', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Edit intervention logic for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing LDP Intervention Logic', ['project_id' => $projectId]);

            $interventionLogic = ProjectLDPInterventionLogic::where('project_id', $projectId)->first();

            return $interventionLogic;
        } catch (\Exception $e) {
            Log::error('Error editing LDP Intervention Logic', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update intervention logic for a project
    public function update(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Updating LDP Intervention Logic', ['project_id' => $projectId]);

            // Use updateOrCreate logic to either update or create a new record
            ProjectLDPInterventionLogic::updateOrCreate(
                ['project_id' => $projectId], // Search by project_id
                ['intervention_description' => $request->intervention_description] // Update intervention description
            );

            DB::commit();
            Log::info('LDP Intervention Logic updated successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Intervention logic updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating LDP Intervention Logic', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update intervention logic.'], 500);
        }
    }

    // Delete intervention logic for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting LDP Intervention Logic', ['project_id' => $projectId]);

            ProjectLDPInterventionLogic::where('project_id', $projectId)->delete();
            DB::commit();
            Log::info('LDP Intervention Logic deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Intervention logic deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting LDP Intervention Logic', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete intervention logic.'], 500);
        }
    }
}
