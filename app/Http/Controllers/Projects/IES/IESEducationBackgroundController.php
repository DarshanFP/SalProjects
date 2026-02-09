<?php

namespace App\Http\Controllers\Projects\IES;

use App\Http\Controllers\Controller;
use App\Services\FormDataExtractor;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IES\ProjectIESEducationBackground;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Projects\IES\StoreIESEducationBackgroundRequest;
use App\Http\Requests\Projects\IES\UpdateIESEducationBackgroundRequest;

class IESEducationBackgroundController extends Controller
{
    // Store or update educational background for a project
    public function store(FormRequest $request, $projectId)
    {
        $fillable = array_diff(
            (new ProjectIESEducationBackground())->getFillable(),
            ['project_id', 'IES_education_id']
        );
        $data = FormDataExtractor::forFillable($request, $fillable);

        DB::beginTransaction();
        try {
            Log::info('Storing IES educational background', ['project_id' => $projectId]);

            // Find or create a new educational background record
            $educationBackground = ProjectIESEducationBackground::where('project_id', $projectId)->first() ?: new ProjectIESEducationBackground();
            $educationBackground->project_id = $projectId;
            $educationBackground->fill($data);
            $educationBackground->save();

            DB::commit();
            Log::info('IES educational background saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IES educational background saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IES educational background', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save IES educational background.'], 500);
        }
    }

    // Show educational background for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IES educational background', ['project_id' => $projectId]);

            $educationBackground = ProjectIESEducationBackground::where('project_id', $projectId)->first();

            // Return the model object directly, not a JSON response
            return $educationBackground;
        } catch (\Exception $e) {
            Log::error('Error fetching IES educational background', ['error' => $e->getMessage()]);
            return null; // Return null instead of JSON error
        }
    }

    // Edit educational background for a project
    public function edit($projectId)
    {

        try {
            Log::info('Fetching project with IES educational background', ['project_id' => $projectId]);

            // Fetch the project with the related IES educational background
            $project = Project::with('iesEducationBackground')->where('project_id', $projectId)->firstOrFail();

            return $project; // Ensure the correct view path
        } catch (\Exception $e) {
            Log::error('Error fetching project for edit in Education Background Controller', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to fetch project details.');
        }
    }

    // Update educational background for a project
    public function update(FormRequest $request, $projectId)
    {
        // Reuse store logic
        return $this->store($request, $projectId);
    }

    // Delete educational background for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IES educational background', ['project_id' => $projectId]);

            ProjectIESEducationBackground::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('IES educational background deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IES educational background deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IES educational background', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete IES educational background.'], 500);
        }
    }
}
