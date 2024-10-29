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

            // Delete existing record if it exists
            ProjectLDPInterventionLogic::where('project_id', $projectId)->delete();

            // Create new intervention logic entry
            ProjectLDPInterventionLogic::create([
                'project_id' => $projectId,
                'intervention_description' => $request->intervention_logic,
            ]);

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
            return response()->json($interventionLogic, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching LDP Intervention Logic', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch intervention logic.'], 500);
        }
    }

    // Edit intervention logic for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing LDP Intervention Logic', ['project_id' => $projectId]);

            $interventionLogic = ProjectLDPInterventionLogic::where('project_id', $projectId)->first();

            return view('projects.partials.Edit.LDP.intervention_logic', compact('interventionLogic'));
        } catch (\Exception $e) {
            Log::error('Error editing LDP Intervention Logic', ['error' => $e->getMessage()]);
            return null;
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
