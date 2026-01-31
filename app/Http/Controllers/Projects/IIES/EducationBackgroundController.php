<?php

namespace App\Http\Controllers\Projects\IIES;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IIES\ProjectIIESEducationBackground;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\IIES\StoreIIESEducationBackgroundRequest;
use App\Http\Requests\Projects\IIES\UpdateIIESEducationBackgroundRequest;

class EducationBackgroundController extends Controller
{
    // Store or update education background
    public function store(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including fields not in StoreProjectRequest validation rules
        $validated = $request->all();
        
        DB::beginTransaction();
        try {
            Log::info('Storing IIES Educational Background', ['project_id' => $projectId]);

            // Create or update the education background
            ProjectIIESEducationBackground::updateOrCreate(
                ['project_id' => $projectId],
                [
                    'prev_education' => $validated['prev_education'] ?? null,
                    'prev_institution' => $validated['prev_institution'] ?? null,
                    'prev_insti_address' => $validated['prev_insti_address'] ?? null,
                    'prev_marks' => $validated['prev_marks'] ?? null,
                    'current_studies' => $validated['current_studies'] ?? null,
                    'curr_institution' => $validated['curr_institution'] ?? null,
                    'curr_insti_address' => $validated['curr_insti_address'] ?? null,
                    'aspiration' => $validated['aspiration'] ?? null,
                    'long_term_effect' => $validated['long_term_effect'] ?? null,
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


/**
 * Field names for Educational Background (matches form names and model fillable).
 */
private function getEducationBackgroundFields(): array
{
    return [
        'prev_education', 'prev_institution', 'prev_insti_address', 'prev_marks',
        'current_studies', 'curr_institution', 'curr_insti_address',
        'aspiration', 'long_term_effect',
    ];
}

// Update education background for a project. Creates record if missing (same as store).
public function update(FormRequest $request, $projectId)
{
    DB::beginTransaction();

    try {
        Log::info('Updating IIES Educational Background', ['project_id' => $projectId]);

        $educationBackground = ProjectIIESEducationBackground::firstOrNew(['project_id' => $projectId]);
        $educationBackground->project_id = $projectId;
        foreach ($this->getEducationBackgroundFields() as $field) {
            $educationBackground->$field = $request->input($field);
        }
        $educationBackground->save();

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
