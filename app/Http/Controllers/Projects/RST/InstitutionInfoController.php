<?php

namespace App\Http\Controllers\Projects\RST;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\RST\ProjectRSTInstitutionInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstitutionInfoController extends Controller
{
    // Store or update institution info
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing Institution Info for RST', ['project_id' => $projectId]);

            // Delete existing institution info for the project and insert new data
            ProjectRSTInstitutionInfo::updateOrCreate(
                ['project_id' => $projectId],
                [
                    'year_setup' => $request->year_setup,
                    'total_students_trained' => $request->total_students_trained,
                    'beneficiaries_last_year' => $request->beneficiaries_last_year,
                    'training_outcome' => $request->training_outcome,
                ]
            );

            DB::commit();
            Log::info('Institution Info saved successfully for RST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Institution Info saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving Institution Info for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Institution Info.'], 500);
        }
    }

    // Show institution info for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching Institution Info for RST', ['project_id' => $projectId]);

            $institutionInfo = ProjectRSTInstitutionInfo::where('project_id', $projectId)->firstOrFail();
            return response()->json($institutionInfo, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching Institution Info for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch Institution Info.'], 500);
        }
    }

    // Edit institution info for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing Institution Info for RST', ['project_id' => $projectId]);

            $institutionInfo = ProjectRSTInstitutionInfo::where('project_id', $projectId)->firstOrFail();
            return view('projects.partials.Edit.RST.institution_info', compact('institutionInfo'));
        } catch (\Exception $e) {
            Log::error('Error editing Institution Info for RST', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Delete institution info for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting Institution Info for RST', ['project_id' => $projectId]);

            ProjectRSTInstitutionInfo::where('project_id', $projectId)->delete();
            DB::commit();
            Log::info('Institution Info deleted successfully for RST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Institution Info deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting Institution Info for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Institution Info.'], 500);
        }
    }
}
