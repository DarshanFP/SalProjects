<?php

namespace App\Http\Controllers\Projects\CCI;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\CCI\ProjectCCIPresentSituation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PresentSituationController extends Controller
{
    // Store new present situation entry
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing CCI Present Situation', ['project_id' => $projectId]);

            // Create new present situation entry
            $presentSituation = new ProjectCCIPresentSituation();
            $presentSituation->project_id = $projectId;
            $presentSituation->internal_challenges = $request->internal_challenges;
            $presentSituation->external_challenges = $request->external_challenges;
            $presentSituation->area_of_focus = $request->area_of_focus;
            $presentSituation->save();

            DB::commit();
            Log::info('CCI Present Situation saved successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Present Situation saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving CCI Present Situation', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to save Present Situation.');
        }
    }

    // Show present situation for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching CCI Present Situation', ['project_id' => $projectId]);

            // Fetch the present situation data or return an empty array if not found
            $presentSituation = ProjectCCIPresentSituation::where('project_id', $projectId)->first();

            if (!$presentSituation) {
                Log::warning('No Present Situation data found', ['project_id' => $projectId]);
            }

            return $presentSituation; // Return the present situation model
        } catch (\Exception $e) {
            Log::error('Error fetching CCI Present Situation', ['error' => $e->getMessage()]);
            return null;
        }

    }



    // Edit present situation for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing CCI Present Situation', ['project_id' => $projectId]);

            $presentSituation = ProjectCCIPresentSituation::where('project_id', $projectId)->firstOrFail();
            return $presentSituation;
        } catch (\Exception $e) {
            Log::error('Error editing CCI Present Situation', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update present situation entry
    public function update(Request $request, $projectId)
{
    DB::beginTransaction();
    try {
        Log::info('Updating or Creating CCI Present Situation', ['project_id' => $projectId]);
        Log::info('Request data:', $request->all());

        // Use updateOrCreate to either update an existing present situation or create a new one
        $presentSituation = ProjectCCIPresentSituation::updateOrCreate(
            ['project_id' => $projectId], // Condition to find the record
            [
                'internal_challenges' => $request->internal_challenges,
                'external_challenges' => $request->external_challenges,
                'area_of_focus' => $request->area_of_focus
            ] // Data to update or create
        );

        Log::info('Present Situation data after update or create:', $presentSituation->toArray());

        DB::commit();
        Log::info('CCI Present Situation updated or created successfully', ['project_id' => $projectId]);
        return redirect()->route('projects.edit', $projectId)->with('success', 'Present Situation updated successfully.');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating or creating CCI Present Situation', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return redirect()->back()->with('error', 'Failed to update or create Present Situation.');
    }
}


    // Delete present situation entry
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting CCI Present Situation', ['project_id' => $projectId]);

            // Delete the present situation entry
            ProjectCCIPresentSituation::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('CCI Present Situation deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Present Situation deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting CCI Present Situation', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete Present Situation.');
        }
    }
}
