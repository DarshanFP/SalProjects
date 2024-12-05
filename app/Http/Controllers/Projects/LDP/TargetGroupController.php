<?php


namespace App\Http\Controllers\Projects\LDP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\LDP\ProjectLDPTargetGroup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TargetGroupController extends Controller
{
    // Store or update the target group
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing LDP Target Group', ['project_id' => $projectId]);

            // Validate request data (allowing nullable fields)
            $request->validate([
                'L_beneficiary_name.*' => 'nullable|string|max:255',
                'L_family_situation.*' => 'nullable|string|max:500',
                'L_nature_of_livelihood.*' => 'nullable|string|max:500',
                'L_amount_requested.*' => 'nullable|numeric',
            ]);

            // Delete existing target groups for the project
            ProjectLDPTargetGroup::where('project_id', $projectId)->delete();

            // Insert new target groups
            foreach ($request->L_beneficiary_name as $index => $name) {
                // Skip if all fields are null
                if (!is_null($name) || !is_null($request->L_family_situation[$index]) ||
                    !is_null($request->L_nature_of_livelihood[$index]) || !is_null($request->L_amount_requested[$index])) {
                    ProjectLDPTargetGroup::create([
                        'project_id' => $projectId,
                        'L_beneficiary_name' => $name,
                        'L_family_situation' => $request->L_family_situation[$index] ?? null,
                        'L_nature_of_livelihood' => $request->L_nature_of_livelihood[$index] ?? null,
                        'L_amount_requested' => $request->L_amount_requested[$index] ?? null,
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
    public function update(Request $request, $projectId)
    {
        // Reuse the store logic for updating
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
