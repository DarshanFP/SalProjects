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
        Log::info('Fetching IIES Educational Background from database', ['project_id' => $projectId]);

        // Retrieve education background or return an empty object
        $IIESEducationBackground = ProjectIIESEducationBackground::where('project_id', $projectId)->first();

        if ($IIESEducationBackground) {
            Log::info('IIES Educational Background retrieved successfully', ['data' => $IIESEducationBackground]);
        } else {
            Log::warning('IIES Educational Background not found, returning empty object', ['project_id' => $projectId]);
            $IIESEducationBackground = new ProjectIIESEducationBackground();
        }

        return $IIESEducationBackground;
    } catch (\Exception $e) {
        Log::error('Error fetching IIES Educational Background', ['error' => $e->getMessage()]);
        return new ProjectIIESEducationBackground(); // Return empty object on failure
    }
}


    // Edit education background for a project
//     public function edit($projectId)
// {
//     try {
//         Log::info('Editing IIES Educational Background', ['project_id' => $projectId]);

//         // Fetch project with education background
//         $educationBackground = ProjectIIESEducationBackground::where('project_id', $projectId)->first();

//         if (!$educationBackground) {
//             Log::warning('No educational background found for project', ['project_id' => $projectId]);
//             return response()->json(['error' => 'No educational background found.'], 404);
//         }

//         return view('projects.partials.Edit.IIES.education_background', compact('educationBackground'));
//     } catch (\Exception $e) {
//         Log::error('Error editing IIES Educational Background', ['error' => $e->getMessage()]);
//         return response()->json(['error' => 'Failed to fetch education background.'], 500);
//     }
// }

public function edit($projectId)
{
    try {
        Log::info('🔍 Fetching IIES Educational Background', ['project_id' => $projectId]);

        // Fetch project education background OR return an empty model
        $IIESEducationBackground = ProjectIIESEducationBackground::where('project_id', $projectId)->first();

        if (!$IIESEducationBackground) {
            Log::warning('⚠️ No IIES Educational Background Found, returning empty object', ['project_id' => $projectId]);
            $IIESEducationBackground = new ProjectIIESEducationBackground();
        }

        return $IIESEducationBackground;
    } catch (\Exception $e) {
        Log::error('❌ Error fetching IIES Educational Background', ['error' => $e->getMessage()]);
        return new ProjectIIESEducationBackground(); // Return empty object on failure
    }
}


// Update education background for a project
public function update(Request $request, $projectId)
{
    DB::beginTransaction();

    try {
        Log::info('Updating IIES Educational Background', ['project_id' => $projectId]);

        // Validate input data
        $validatedData = $request->validate([
            'prev_education'     => 'nullable|string|max:255',
            'prev_institution'   => 'nullable|string|max:255',
            'prev_insti_address' => 'nullable|string|max:500',
            'prev_marks'         => 'nullable|numeric|min:0|max:100',
            'current_studies'    => 'nullable|string|max:255',
            'curr_institution'   => 'nullable|string|max:255',
            'curr_insti_address' => 'nullable|string|max:500',
            'aspiration'         => 'nullable|string|max:500',
            'long_term_effect'   => 'nullable|string|max:500',
        ]);

        // Find existing record
        $educationBackground = ProjectIIESEducationBackground::where('project_id', $projectId)->first();

        if (!$educationBackground) {
            throw new \Exception('Educational background record not found for update.');
        }

        // Update record
        $educationBackground->update($validatedData);

        DB::commit();
        Log::info('IIES Educational Background updated successfully', ['project_id' => $projectId]);

        return response()->json(['message' => 'Educational Background updated successfully.'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating IIES Educational Background', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to update Educational Background.'], 500);
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
