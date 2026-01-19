<?php

namespace App\Http\Controllers\Projects\CCI;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\CCI\ProjectCCIAchievements;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\CCI\StoreCCIAchievementsRequest;
use App\Http\Requests\Projects\CCI\UpdateCCIAchievementsRequest;

class AchievementsController extends Controller
{
    // Store achievements for a project
    public function store(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including academic_achievements[], sport_achievements[], other_achievements[] arrays
        // These fields are not in StoreProjectRequest validation rules
        $validated = $request->all();

        DB::beginTransaction();
        try {
            Log::info('Storing CCI Achievements', ['project_id' => $projectId]);

            // Create new instance of ProjectCCIAchievements
            $achievements = new ProjectCCIAchievements();
            $achievements->project_id = $projectId;
            $achievements->academic_achievements = json_encode($validated['academic_achievements'] ?? []);
            $achievements->sport_achievements = json_encode($validated['sport_achievements'] ?? []);
            $achievements->other_achievements = json_encode($validated['other_achievements'] ?? []);
            $achievements->save();

            DB::commit();
            Log::info('CCI Achievements saved successfully', ['project_id' => $projectId]);
            return response()->json($achievements, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving CCI Achievements', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save achievements.'], 500);
        }
    }

    // Show achievements for a project
    public function show($projectId)
{
    try {
        Log::info('Fetching CCI Achievements', ['project_id' => $projectId]);

        // Fetch the record with any necessary relationships
        $achievements = ProjectCCIAchievements::where('project_id', $projectId)->firstOrFail();

        // Decode JSON fields
        $achievements->academic_achievements = json_decode($achievements->academic_achievements, true);
        $achievements->sport_achievements = json_decode($achievements->sport_achievements, true);
        $achievements->other_achievements = json_decode($achievements->other_achievements, true);

        Log::info('Successfully fetched CCI Achievements', ['data' => $achievements]);

        // Return a structured response for consistency
        return $achievements;
    } catch (\Exception $e) {
        // Handle exception
        return null;
    }
}




    // Edit achievements for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing CCI Achievements', ['project_id' => $projectId]);

            $achievements = ProjectCCIAchievements::where('project_id', $projectId)->firstOrFail();

            // Decode the JSON fields
            $achievements->academic_achievements = json_decode($achievements->academic_achievements);
            $achievements->sport_achievements = json_decode($achievements->sport_achievements);
            $achievements->other_achievements = json_decode($achievements->other_achievements);

            return $achievements;
        } catch (\Exception $e) {
            Log::error('Error editing CCI Achievements', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update or create achievements for a project
    public function update(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including academic_achievements[], sport_achievements[], other_achievements[] arrays
        // These fields are not in UpdateProjectRequest validation rules
        $validated = $request->all();

        DB::beginTransaction();
        try {
            Log::info('Updating or Creating CCI Achievements', ['project_id' => $projectId]);

            // Use updateOrCreate to either update or create a new entry
            $achievements = ProjectCCIAchievements::updateOrCreate(
                ['project_id' => $projectId], // Condition to check if record exists
                [
                    'academic_achievements' => json_encode($validated['academic_achievements'] ?? []),
                    'sport_achievements' => json_encode($validated['sport_achievements'] ?? []),
                    'other_achievements' => json_encode($validated['other_achievements'] ?? [])
                ]
            );

            DB::commit();
            Log::info('CCI Achievements updated or created successfully', ['project_id' => $projectId]);
            return response()->json($achievements, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating or creating CCI Achievements', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Failed to update achievements.'], 500);
        }
    }


    // Delete achievements for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting CCI Achievements', ['project_id' => $projectId]);

            $achievements = ProjectCCIAchievements::where('project_id', $projectId)->firstOrFail();
            $achievements->delete();

            DB::commit();
            Log::info('CCI Achievements deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Achievements deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting CCI Achievements', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete achievements.'], 500);
        }
    }
}
