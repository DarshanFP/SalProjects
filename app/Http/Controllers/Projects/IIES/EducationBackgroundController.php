<?php

namespace App\Http\Controllers\Projects\IIES;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IIES\ProjectIIESEducationBackground;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EducationBackgroundController extends Controller
{
    // Store or update education background
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IIES Educational Background', ['project_id' => $projectId]);

            // Create or update the education background
            ProjectIIESEducationBackground::updateOrCreate(
                ['project_id' => $projectId],
                [
                    'prev_education' => $request->prev_education,
                    'prev_institution' => $request->prev_institution,
                    'prev_insti_address' => $request->prev_insti_address,
                    'prev_marks' => $request->prev_marks,
                    'current_studies' => $request->current_studies,
                    'curr_institution' => $request->curr_institution,
                    'curr_insti_address' => $request->curr_insti_address,
                    'aspiration' => $request->aspiration,
                    'long_term_effect' => $request->long_term_effect,
                ]
            );

            DB::commit();
            Log::info('IIES Educational Background saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Educational Background saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IIES Educational Background', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Educational Background.'], 500);
        }
    }

    // Show education background for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IIES Educational Background', ['project_id' => $projectId]);

            $educationBackground = ProjectIIESEducationBackground::where('project_id', $projectId)->first();
            return response()->json($educationBackground, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching IIES Educational Background', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch Educational Background.'], 500);
        }
    }

    // Edit education background for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IIES Educational Background', ['project_id' => $projectId]);

            $educationBackground = ProjectIIESEducationBackground::where('project_id', $projectId)->first();
            return view('projects.partials.Edit.IIES.education_background', compact('educationBackground'));
        } catch (\Exception $e) {
            Log::error('Error editing IIES Educational Background', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Delete education background for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IIES Educational Background', ['project_id' => $projectId]);

            ProjectIIESEducationBackground::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('IIES Educational Background deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Educational Background deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IIES Educational Background', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Educational Background.'], 500);
        }
    }
}
