<?php


namespace App\Http\Controllers\Projects\LDP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\LDP\ProjectLDPTargetGroup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class TargetGroupController extends Controller
{
    // Store or update the target group
    public function store(FormRequest $request, $projectId)
    {
        // Validation already done by FormRequest
        // Use all() to get all form data including fields not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validated = $request->all();
        
        DB::beginTransaction();
        try {
            Log::info('Storing LDP Target Group', ['project_id' => $projectId]);

            // Delete existing target groups for the project
            ProjectLDPTargetGroup::where('project_id', $projectId)->delete();

            // Insert new target groups
            $beneficiaryNames = $validated['L_beneficiary_name'] ?? [];
            foreach ($beneficiaryNames as $index => $name) {
                // Skip if all fields are null
                if (!is_null($name) || !is_null($validated['L_family_situation'][$index] ?? null) ||
                    !is_null($validated['L_nature_of_livelihood'][$index] ?? null) || !is_null($validated['L_amount_requested'][$index] ?? null)) {
                    ProjectLDPTargetGroup::create([
                        'project_id' => $projectId,
                        'L_beneficiary_name' => $name,
                        'L_family_situation' => $validated['L_family_situation'][$index] ?? null,
                        'L_nature_of_livelihood' => $validated['L_nature_of_livelihood'][$index] ?? null,
                        'L_amount_requested' => $validated['L_amount_requested'][$index] ?? null,
                    ]);
                }
            }

            DB::commit();
            Log::info('LDP Target Group saved successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Target Group saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving LDP Target Group', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to save Target Group.');
        }
    }

    // Update the target group
    public function update(FormRequest $request, $projectId)
    {
        // Validation and authorization already done by FormRequest
        // Reuse store logic but with FormRequest
        return $this->store($request, $projectId);
    }

    // Show the target group for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching LDP Target Group', ['project_id' => $projectId]);

            // Fetch target groups for the project
            $targetGroups = ProjectLDPTargetGroup::where('project_id', $projectId)->get();

            if ($targetGroups->isEmpty()) {
                Log::info('No Target Groups found for project', ['project_id' => $projectId]);
                return []; // Return an empty array if no data found
            }

            return $targetGroups; // Return the collection
        } catch (\Exception $e) {
            Log::error('Error fetching LDP Target Group', ['error' => $e->getMessage()]);
            return null; // Return null in case of an error
        }
    }


    // Edit the target group for a project
    public function edit($projectId)
{
    try {
        Log::info('Editing LDP Target Group', ['project_id' => $projectId]);

        // Fetch target groups for the project
        $targetGroups = ProjectLDPTargetGroup::where('project_id', $projectId)->get()->toArray();

        // If no target groups found, initialize an empty array
        if (!$targetGroups) {
            $targetGroups = [];
        }

        Log::info('Target groups fetched: ', ['targetGroups' => $targetGroups]);

        // Pass the data to the view
        return $targetGroups;
    } catch (\Exception $e) {
        Log::error('Error editing Target Group for LDP', ['error' => $e->getMessage()]);
        return redirect()->back()->withErrors(['error' => 'Failed to load Target Group data.']);
    }
}


    // Delete the target group for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting LDP Target Group', ['project_id' => $projectId]);

            ProjectLDPTargetGroup::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('LDP Target Group deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Target Group deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting LDP Target Group', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete Target Group.');
        }
    }
}
