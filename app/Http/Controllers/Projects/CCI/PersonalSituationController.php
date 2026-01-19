<?php

namespace App\Http\Controllers\Projects\CCI;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\CCI\ProjectCCIPersonalSituation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\CCI\StoreCCIPersonalSituationRequest;
use App\Http\Requests\Projects\CCI\UpdateCCIPersonalSituationRequest;

class PersonalSituationController extends Controller
{
    // Store new personal situation entry
    public function store(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including fields not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validated = $request->all();
        
        DB::beginTransaction();
        try {
            Log::info('Storing CCI Personal Situation', ['project_id' => $projectId]);

            // Create new personal situation entry
            $personalSituation = new ProjectCCIPersonalSituation();
            $personalSituation->project_id = $projectId;
            $personalSituation->children_with_parents_last_year = $validated['children_with_parents_last_year'] ?? null;
            $personalSituation->children_with_parents_current_year = $validated['children_with_parents_current_year'] ?? null;
            $personalSituation->semi_orphans_last_year = $validated['semi_orphans_last_year'] ?? null;
            $personalSituation->semi_orphans_current_year = $validated['semi_orphans_current_year'] ?? null;
            $personalSituation->orphans_last_year = $validated['orphans_last_year'] ?? null;
            $personalSituation->orphans_current_year = $validated['orphans_current_year'] ?? null;
            $personalSituation->hiv_infected_last_year = $validated['hiv_infected_last_year'] ?? null;
            $personalSituation->hiv_infected_current_year = $validated['hiv_infected_current_year'] ?? null;
            $personalSituation->differently_abled_last_year = $validated['differently_abled_last_year'] ?? null;
            $personalSituation->differently_abled_current_year = $validated['differently_abled_current_year'] ?? null;
            $personalSituation->parents_in_conflict_last_year = $validated['parents_in_conflict_last_year'] ?? null;
            $personalSituation->parents_in_conflict_current_year = $validated['parents_in_conflict_current_year'] ?? null;
            $personalSituation->other_ailments_last_year = $validated['other_ailments_last_year'] ?? null;
            $personalSituation->other_ailments_current_year = $validated['other_ailments_current_year'] ?? null;
            $personalSituation->general_remarks = $validated['general_remarks'] ?? null;
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
    public function update(FormRequest $request, $projectId)
{
    // Use all() to get all form data including fields not in UpdateProjectRequest validation rules
    $validated = $request->all();
    
    DB::beginTransaction();
    try {
        Log::info('Updating or Creating CCI Personal Situation', ['project_id' => $projectId]);

        // Use updateOrCreate to either update an existing entry or create a new one
        $personalSituation = ProjectCCIPersonalSituation::updateOrCreate(
            ['project_id' => $projectId], // Condition to find the record
            [
                'children_with_parents_last_year' => $validated['children_with_parents_last_year'] ?? null,
                'children_with_parents_current_year' => $validated['children_with_parents_current_year'] ?? null,
                'semi_orphans_last_year' => $validated['semi_orphans_last_year'] ?? null,
                'semi_orphans_current_year' => $validated['semi_orphans_current_year'] ?? null,
                'orphans_last_year' => $validated['orphans_last_year'] ?? null,
                'orphans_current_year' => $validated['orphans_current_year'] ?? null,
                'hiv_infected_last_year' => $validated['hiv_infected_last_year'] ?? null,
                'hiv_infected_current_year' => $validated['hiv_infected_current_year'] ?? null,
                'differently_abled_last_year' => $validated['differently_abled_last_year'] ?? null,
                'differently_abled_current_year' => $validated['differently_abled_current_year'] ?? null,
                'parents_in_conflict_last_year' => $validated['parents_in_conflict_last_year'] ?? null,
                'parents_in_conflict_current_year' => $validated['parents_in_conflict_current_year'] ?? null,
                'other_ailments_last_year' => $validated['other_ailments_last_year'] ?? null,
                'other_ailments_current_year' => $validated['other_ailments_current_year'] ?? null,
                'general_remarks' => $validated['general_remarks'] ?? null,
            ]
        );

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
