<?php

namespace App\Http\Controllers\Projects\IGE;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IGE\ProjectIGEInstitutionInfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InstitutionInfoController extends Controller
{
    // Store or update institution information for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IGE Institution Information', ['project_id' => $projectId]);

            // Update or create the institution information entry
            $IGEinstitutionInfo = ProjectIGEInstitutionInfo::updateOrCreate(
                ['project_id' => $projectId],
                [
                    'institutional_type' => $request->input('institutional_type'),
                    'age_group' => $request->input('age_group'),
                    'previous_year_beneficiaries' => $request->input('previous_year_beneficiaries'),
                    'outcome_impact' => $request->input('outcome_impact'),
                ]
            );

            DB::commit();
            Log::info('IGE Institution Information saved successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Institution Information saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IGE Institution Information', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to save Institution Information.');
        }
    }

    // Show institution information for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IGE Institution Information', ['project_id' => $projectId]);

            $IGEinstitutionInfo = ProjectIGEInstitutionInfo::where('project_id', $projectId)->first();

            if (!$IGEinstitutionInfo) {
                Log::warning('No Institution Information found', ['project_id' => $projectId]);
                return null; // Return null if no data exists
            }

            return $IGEinstitutionInfo; // Return the model directly
        } catch (\Exception $e) {
            Log::error('Error fetching IGE Institution Information', ['error' => $e->getMessage()]);
            return null; // Return null on error to avoid breaking the main view
        }
    }


    // Edit institution information for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IGE Institution Information', ['project_id' => $projectId]);

            $IGEinstitutionInfo = ProjectIGEInstitutionInfo::where('project_id', $projectId)->firstOrFail();
            return $IGEinstitutionInfo;
        } catch (\Exception $e) {
            Log::error('Error editing IGE Institution Information', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update institution information for a project
    public function update(Request $request, $projectId)
    {
        return $this->store($request, $projectId); // Reuse the store logic for update
    }

    // Delete institution information for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IGE Institution Information', ['project_id' => $projectId]);

            // Delete the institution information entry
            ProjectIGEInstitutionInfo::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('IGE Institution Information deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Institution Information deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IGE Institution Information', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete Institution Information.');
        }
    }
}
