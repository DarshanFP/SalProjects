<?php

namespace App\Http\Controllers\Projects\IIES;

use App\Http\Controllers\Controller;
use App\Services\FormDataExtractor;
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
        $fillable = array_diff(
            (new ProjectIIESEducationBackground())->getFillable(),
            ['project_id', 'IIES_education_id']
        );
        $data = FormDataExtractor::forFillable($request, $fillable);

        try {
            Log::info('Storing IIES Educational Background', ['project_id' => $projectId]);

            $educationBackground = ProjectIIESEducationBackground::where('project_id', $projectId)->first()
                ?: new ProjectIIESEducationBackground();
            $educationBackground->project_id = $projectId;
            $educationBackground->fill($data);
            $educationBackground->save();

            return response()->json(['message' => 'Educational Background saved successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Error saving IIES Educational Background', ['error' => $e->getMessage()]);
            throw $e;
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


// Update education background for a project. Creates record if missing (same as store).
public function update(FormRequest $request, $projectId)
{
    return $this->store($request, $projectId);
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
