<?php

namespace App\Http\Controllers\Projects\CCI;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\CCI\ProjectCCIPersonalSituation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PersonalSituationController extends Controller
{
    // Store new personal situation entry
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing CCI Personal Situation', ['project_id' => $projectId]);

            // Create new personal situation entry
            $personalSituation = new ProjectCCIPersonalSituation();
            $personalSituation->project_id = $projectId;
            $personalSituation->children_with_parents_last_year = $request->children_with_parents_last_year;
            $personalSituation->children_with_parents_current_year = $request->children_with_parents_current_year;
            $personalSituation->semi_orphans_last_year = $request->semi_orphans_last_year;
            $personalSituation->semi_orphans_current_year = $request->semi_orphans_current_year;
            $personalSituation->orphans_last_year = $request->orphans_last_year;
            $personalSituation->orphans_current_year = $request->orphans_current_year;
            $personalSituation->hiv_infected_last_year = $request->hiv_infected_last_year;
            $personalSituation->hiv_infected_current_year = $request->hiv_infected_current_year;
            $personalSituation->differently_abled_last_year = $request->differently_abled_last_year;
            $personalSituation->differently_abled_current_year = $request->differently_abled_current_year;
            $personalSituation->parents_in_conflict_last_year = $request->parents_in_conflict_last_year;
            $personalSituation->parents_in_conflict_current_year = $request->parents_in_conflict_current_year;
            $personalSituation->other_ailments_last_year = $request->other_ailments_last_year;
            $personalSituation->other_ailments_current_year = $request->other_ailments_current_year;
            $personalSituation->general_remarks = $request->general_remarks;
            $personalSituation->save();

            DB::commit();
            Log::info('CCI Personal Situation saved successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Personal Situation saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving CCI Personal Situation', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to save Personal Situation.');
        }
    }

    // Show existing personal situation entry
    public function show($projectId)
{
    try {
        Log::info('Fetching CCI Personal Situation', ['project_id' => $projectId]);

        // Fetch the personal situation entry
        $personalSituation = ProjectCCIPersonalSituation::where('project_id', $projectId)->first();

        if (!$personalSituation) {
            Log::warning('No Personal Situation data found', ['project_id' => $projectId]);
            return null; // Return null if no data found
        }

        return $personalSituation; // Return the model directly
    } catch (\Exception $e) {
        Log::error('Error fetching CCI Personal Situation', ['error' => $e->getMessage()]);
        return null;
    }
}



    // Edit personal situation entry
    public function edit($projectId)
    {
        try {
            Log::info('Editing CCI Personal Situation', ['project_id' => $projectId]);

            $personalSituation = ProjectCCIPersonalSituation::where('project_id', $projectId)->firstOrFail();
            return $personalSituation;
        } catch (\Exception $e) {
            Log::error('Error editing CCI Personal Situation', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update existing personal situation entry
    public function update(Request $request, $projectId)
{
    DB::beginTransaction();
    try {
        Log::info('Updating or Creating CCI Personal Situation', ['project_id' => $projectId]);
        Log::info('Request data:', $request->all());

        // Use updateOrCreate to either update an existing entry or create a new one
        $personalSituation = ProjectCCIPersonalSituation::updateOrCreate(
            ['project_id' => $projectId], // Condition to find the record
            [
                'children_with_parents_last_year' => $request->children_with_parents_last_year,
                'children_with_parents_current_year' => $request->children_with_parents_current_year,
                'semi_orphans_last_year' => $request->semi_orphans_last_year,
                'semi_orphans_current_year' => $request->semi_orphans_current_year,
                'orphans_last_year' => $request->orphans_last_year,
                'orphans_current_year' => $request->orphans_current_year,
                'hiv_infected_last_year' => $request->hiv_infected_last_year,
                'hiv_infected_current_year' => $request->hiv_infected_current_year,
                'differently_abled_last_year' => $request->differently_abled_last_year,
                'differently_abled_current_year' => $request->differently_abled_current_year,
                'parents_in_conflict_last_year' => $request->parents_in_conflict_last_year,
                'parents_in_conflict_current_year' => $request->parents_in_conflict_current_year,
                'other_ailments_last_year' => $request->other_ailments_last_year,
                'other_ailments_current_year' => $request->other_ailments_current_year,
                'general_remarks' => $request->general_remarks,
            ]
        );

        Log::info('Personal Situation data after update or create:', $personalSituation->toArray());

        DB::commit();
        Log::info('CCI Personal Situation updated or created successfully', ['project_id' => $projectId]);
        return redirect()->route('projects.edit', $projectId)->with('success', 'Personal Situation updated successfully.');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating or creating CCI Personal Situation', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return redirect()->back()->with('error', 'Failed to update or create Personal Situation.');
    }
}


    // Delete personal situation entry
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting CCI Personal Situation', ['project_id' => $projectId]);

            ProjectCCIPersonalSituation::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('CCI Personal Situation deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Personal Situation deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting CCI Personal Situation', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete Personal Situation.');
        }
    }
}
