<?php

namespace App\Http\Controllers\Projects\CCI;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\CCI\ProjectCCIAgeProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgeProfileController extends Controller
{
    // Store Age Profile (for new entries)
    // App\Http\Controllers\Projects\CCI\AgeProfileController.php

    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing CCI Age Profile', ['project_id' => $projectId]);
            Log::info('Request data:', $request->all());

            // Create new instance of ProjectCCIAgeProfile
            $ageProfile = new ProjectCCIAgeProfile();
            $ageProfile->project_id = $projectId;
            $ageProfile->fill($request->except('_token'));

            Log::info('AgeProfile data before save:', $ageProfile->toArray());

            $ageProfile->save();

            DB::commit();
            Log::info('CCI Age Profile created successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Age Profile created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating CCI Age Profile', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to create Age Profile.');
        }
    }

        // Update or create Age Profile entry
    public function update(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Updating or Creating CCI Age Profile', ['project_id' => $projectId]);
            Log::info('Request data:', $request->all());

            // Use updateOrCreate to either update or create a new entry
            $ageProfile = ProjectCCIAgeProfile::updateOrCreate(
                ['project_id' => $projectId], // Condition to check if record exists
                $request->except('_token') // Fill with the request data
            );

            Log::info('AgeProfile data after save:', $ageProfile->toArray());

            DB::commit();
            Log::info('CCI Age Profile updated or created successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Age Profile updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating or creating CCI Age Profile', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to update Age Profile.');
        }
    }



    // Show Age Profile
    public function show($projectId)
    {
        try {
            Log::info('Fetching CCI Age Profile', ['project_id' => $projectId]);

            $ageProfile = ProjectCCIAgeProfile::where('project_id', $projectId)->firstOrFail();
            return view('projects.partials.CCI.age_profile_show', compact('ageProfile'));
        } catch (\Exception $e) {
            Log::error('Error fetching CCI Age Profile', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to fetch Age Profile.');
        }
    }

    // Edit Age Profile
    public function edit($projectId)
{
    try {
        Log::info('Editing CCI Age Profile', ['project_id' => $projectId]);

        $ageProfile = ProjectCCIAgeProfile::where('project_id', $projectId)->first();
        // Return the model instance or null
        return $ageProfile;
    } catch (\Exception $e) {
        Log::error('Error editing CCI Age Profile', ['error' => $e->getMessage()]);
        return null;
    }
}


    // Delete Age Profile
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting CCI Age Profile', ['project_id' => $projectId]);

            $ageProfile = ProjectCCIAgeProfile::where('project_id', $projectId)->firstOrFail();
            $ageProfile->delete();

            DB::commit();
            Log::info('CCI Age Profile deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Age Profile deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting CCI Age Profile', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete Age Profile.');
        }
    }
}
