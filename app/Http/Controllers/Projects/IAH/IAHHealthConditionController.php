<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Http\Controllers\Controller;
use App\Services\FormDataExtractor;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\IAH\ProjectIAHHealthCondition;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Projects\IAH\StoreIAHHealthConditionRequest;
use App\Http\Requests\Projects\IAH\UpdateIAHHealthConditionRequest;

class IAHHealthConditionController extends Controller
{
    /**
     * Store (create) health condition info for a project.
     */
    public function store(FormRequest $request, $projectId)
    {
        $fillable = array_diff(
            (new ProjectIAHHealthCondition())->getFillable(),
            ['project_id', 'IAH_health_id']
        );
        $data = FormDataExtractor::forFillable($request, $fillable);

        if (! $this->isIAHHealthConditionMeaningfullyFilled($data)) {
            Log::info('IAHHealthConditionController@store - Section absent or empty; skipping mutation', [
                'project_id' => $projectId,
            ]);

            $existing = ProjectIAHHealthCondition::where('project_id', $projectId)->first();

            return response()->json($existing, 200);
        }

        Log::info('IAHHealthConditionController@store - Start', [
            'project_id' => $projectId
        ]);

        DB::beginTransaction();
        try {
            // Because there's typically a single row for health condition, we may want to delete any existing first:
            ProjectIAHHealthCondition::where('project_id', $projectId)->delete();

            $healthCondition = new ProjectIAHHealthCondition();
            $healthCondition->project_id = $projectId;
            $healthCondition->fill($data);
            $healthCondition->save();

            DB::commit();
            Log::info('IAHHealthConditionController@store - Success', [
                'project_id' => $projectId
            ]);
            return response()->json($healthCondition, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHHealthConditionController@store - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to save IAH health condition details.'], 500);
        }
    }

    /**
     * Update an existing health condition record.
     */
    public function update(FormRequest $request, $projectId)
    {
        return $this->store($request, $projectId);
    }

    /**
     * Show an existing health condition record.
     */
    public function show($projectId)
    {
        try {
            Log::info('IAHHealthConditionController@show - Start', [
                'project_id' => $projectId
            ]);
            $healthCondition = ProjectIAHHealthCondition::where('project_id', $projectId)->first();

            // Return the model object directly, not a JSON response
            return $healthCondition;
        } catch (\Exception $e) {
            Log::error('IAHHealthConditionController@show - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage()
            ]);
            return null; // Return null instead of JSON error
        }
    }

    /**
     * Edit route often returns a view or data for an edit form.
     */
    public function edit($projectId)
    {
        try {
            Log::info('IAHHealthConditionController@edit - Start', ['project_id' => $projectId]);

            $project = Project::where('project_id', $projectId)
                ->with('iahHealthCondition')
                ->firstOrFail();

            Log::info('IAHHealthConditionController@edit - Data loaded for editing', [
                'project_id' => $projectId,
            ]);

            // If you prefer to return JSON or a specialized view, adjust accordingly:
            return view('projects.partials.Edit.IAH.health_conditions', compact('project'));
        } catch (\Exception $e) {
            Log::error('IAHHealthConditionController@edit - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to load health condition details.'], 500);
        }
    }

    /**
     * Delete a health condition record.
     */
    public function destroy($projectId)
    {
        Log::info('IAHHealthConditionController@destroy - Start', ['project_id' => $projectId]);

        DB::beginTransaction();
        try {
            $healthCondition = ProjectIAHHealthCondition::where('project_id', $projectId)->firstOrFail();
            $healthCondition->delete();

            DB::commit();
            Log::info('IAHHealthConditionController@destroy - Success', [
                'project_id' => $projectId
            ]);

            return response()->json(['message' => 'IAH health condition details deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('IAHHealthConditionController@destroy - Error', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to delete IAH health condition details.'], 500);
        }
    }

    private function isIAHHealthConditionMeaningfullyFilled(array $data): bool
    {
        foreach ($data as $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    if (trim((string) $v) !== '') {
                        return true;
                    }
                }
            } else {
                if (trim((string) $value) !== '') {
                    return true;
                }
            }
        }

        return false;
    }
}
