<?php

namespace App\Http\Controllers\Projects\RST;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\OldProjects\RST\ProjectRSTTargetGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\RST\StoreRSTTargetGroupRequest;
use App\Http\Requests\Projects\RST\UpdateRSTTargetGroupRequest;

class TargetGroupController extends Controller
{
    // Store or update target group
    public function store(FormRequest $request, $projectId)
    {
        // Use all() instead of validated() because tg_no_of_beneficiaries, beneficiaries_description_problems
        // fields are not in StoreProjectRequest validation rules
        $validated = $request->all();
        
        Log::info('Storing Target Group for RST', [
            'project_id' => $projectId
        ]);

        DB::beginTransaction();
        try {
            // Retrieve the value
            $tg_no_of_beneficiaries = $validated['tg_no_of_beneficiaries'] ?? null;

            // Check if an entry already exists, if yes, then update, otherwise create
            $targetGroup = ProjectRSTTargetGroup::where('project_id', $projectId)->first();

            if ($targetGroup) {
                // If exists, update the target group
                $targetGroup->update([
                    'tg_no_of_beneficiaries' => $tg_no_of_beneficiaries,
                    'beneficiaries_description_problems' => $validated['beneficiaries_description_problems'] ?? null,
                ]);
                Log::info('Target Group updated successfully for RST', ['project_id' => $projectId]);
            } else {
                // If not exists, create a new target group
                ProjectRSTTargetGroup::create([
                    'project_id' => $projectId,
                    'tg_no_of_beneficiaries' => $tg_no_of_beneficiaries,
                    'beneficiaries_description_problems' => $request->input('beneficiaries_description_problems'),
                    'RST_target_group_id' => $this->generateTargetGroupId(),
                ]);
                Log::info('Target Group created successfully for RST', ['project_id' => $projectId]);
            }

            DB::commit();
            return response()->json(['message' => 'Target Group saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving Target Group for RST', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Target Group.'], 500);
        }
    }

    // Helper method to generate unique target group ID
    private function generateTargetGroupId()
    {
        $latest = ProjectRSTTargetGroup::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->RST_target_group_id, -4)) + 1 : 1;

        return 'RST-TG-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
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
