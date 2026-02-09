<?php

namespace App\Http\Controllers\Projects\RST;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\RST\ProjectRSTTargetGroup;
use App\Services\FormDataExtractor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\RST\StoreRSTTargetGroupRequest;
use App\Http\Requests\Projects\RST\UpdateRSTTargetGroupRequest;

class TargetGroupController extends Controller
{
    // Store or update target group
    public function store(FormRequest $request, $projectId)
    {
        $fillable = array_diff(
            (new ProjectRSTTargetGroup())->getFillable(),
            ['project_id', 'RST_target_group_id']
        );
        $data = FormDataExtractor::forFillable($request, $fillable);

        Log::info('Storing Target Group for RST', ['project_id' => $projectId]);

        DB::beginTransaction();
        try {
            ProjectRSTTargetGroup::updateOrCreate(
                ['project_id' => $projectId],
                $data
            );

            DB::commit();
            Log::info('Target Group saved successfully for RST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Target Group saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving Target Group for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Target Group.'], 500);
        }
    }

    // Update function - this calls the same store function
    public function update(FormRequest $request, $projectId)
    {
        // Reuse store logic; FormRequest will provide validated() when available
        return $this->store($request, $projectId);
    }

    // Show target group for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching Target Group for RST', ['project_id' => $projectId]);

            // Fetch the target group entry
            $targetGroup = ProjectRSTTargetGroup::where('project_id', $projectId)->first();

            if (!$targetGroup) {
                Log::warning('No Target Group data found for RST', ['project_id' => $projectId]);
                return null; // Return null if no data is found
            }

            return $targetGroup; // Return the target group model
        } catch (\Exception $e) {
            Log::error('Error fetching Target Group for RST', ['error' => $e->getMessage()]);
            return null;
        }
    }


    // Edit target group for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing Target Group for RST', ['project_id' => $projectId]);

            $RSTtargetGroup = ProjectRSTTargetGroup::where('project_id', $projectId)->first();
            return $RSTtargetGroup;
        } catch (\Exception $e) {
            Log::error('Error editing Target Group for RST', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Delete target group for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting Target Group for RST', ['project_id' => $projectId]);

            ProjectRSTTargetGroup::where('project_id', $projectId)->delete();
            DB::commit();
            Log::info('Target Group deleted successfully for RST', ['project_id' => $projectId]);
            return response()->json(['message' => 'Target Group deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting Target Group for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Target Group.'], 500);
        }
    }
}
