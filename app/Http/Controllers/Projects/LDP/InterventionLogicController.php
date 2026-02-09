<?php

namespace App\Http\Controllers\Projects\LDP;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\LDP\ProjectLDPInterventionLogic;
use App\Services\FormDataExtractor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterventionLogicController extends Controller
{
    // Store or update intervention logic
    public function store(FormRequest $request, $projectId)
    {
        $fillable = array_diff(
            (new ProjectLDPInterventionLogic())->getFillable(),
            ['project_id', 'LDP_intervention_logic_id']
        );
        $data = FormDataExtractor::forFillable($request, $fillable);

        DB::beginTransaction();
        try {
            Log::info('Storing LDP Intervention Logic', ['project_id' => $projectId]);

            ProjectLDPInterventionLogic::updateOrCreate(
                ['project_id' => $projectId],
                $data
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
    public function update(FormRequest $request, $projectId)
    {
        return $this->store($request, $projectId);
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
