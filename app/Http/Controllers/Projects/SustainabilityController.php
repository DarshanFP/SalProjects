<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller; // Correctly referencing the base Controller
use Illuminate\Http\Request;
use App\Models\OldProjects\ProjectSustainability;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Auth;

class SustainabilityController extends Controller
{
    // Store sustainability information for a project
    public function store(Request $request, $projectId)
    {
        $validated = $request->validate([
            'sustainability' => 'nullable|string',
            'monitoring_process' => 'nullable|string',
            'reporting_methodology' => 'nullable|string',
            'evaluation_methodology' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        try {
            Log::info('SustainabilityController@store - Starting to store sustainability information', ['project_id' => $projectId]);

            $sustainability = new ProjectSustainability();
            $sustainability->project_id = $projectId;
            $sustainability->sustainability = $validated['sustainability'] ?? null;
            $sustainability->monitoring_process = $validated['monitoring_process'] ?? null;
            $sustainability->reporting_methodology = $validated['reporting_methodology'] ?? null;
            $sustainability->evaluation_methodology = $validated['evaluation_methodology'] ?? null;
            $sustainability->save();

            DB::commit();
            Log::info('SustainabilityController@store - Sustainability information saved successfully', ['project_id' => $projectId]);
            return $sustainability;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SustainabilityController@store - Error saving sustainability information', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    // Show sustainability information for a project
    public function show($project_id)
{
    // Ensure that 'sustainabilities' is included in the with() method
    $project = Project::where('project_id', $project_id)
                      ->with('budgets', 'attachments', 'objectives', 'sustainabilities')
                      ->firstOrFail();
    $user = Auth::user();

    return view('projects.Oldprojects.show', compact('project', 'user'));
}


    // Edit sustainability information for a project
    public function edit($projectId)
    {
        try {
            Log::info('SustainabilityController@edit - Fetching sustainability information for project', ['project_id' => $projectId]);

            $sustainability = ProjectSustainability::where('project_id', $projectId)->firstOrFail();

            Log::info('SustainabilityController@edit - Successfully fetched sustainability information for editing', ['project_id' => $projectId]);

            return view('projects.sustainability.edit', compact('sustainability'));
        } catch (\Exception $e) {
            Log::error('SustainabilityController@edit - Error fetching sustainability information for editing', ['error' => $e->getMessage()]);
            abort(404, 'Sustainability information not found');
        }
    }

    // Update sustainability information for a project
    public function update(Request $request, $project_id)
{
    $validated = $request->validate([
        'sustainability' => 'nullable|string',
        'monitoring_process' => 'nullable|string',
        'reporting_methodology' => 'nullable|string',
        'evaluation_methodology' => 'nullable|string',
    ]);
    
    DB::beginTransaction();
    try {
        Log::info('SustainabilityController@update - Starting to update sustainability information', ['project_id' => $project_id]);

        // Fetch the sustainability record(s) associated with the project
        $sustainability = ProjectSustainability::where('project_id', $project_id)->first();

        // If no sustainability record exists, create a new one
        if (!$sustainability) {
            $sustainability = new ProjectSustainability();
            $sustainability->project_id = $project_id;
        }

        // Update the sustainability data with validated data
        $sustainability->sustainability = $validated['sustainability'] ?? null;
        $sustainability->monitoring_process = $validated['monitoring_process'] ?? null;
        $sustainability->reporting_methodology = $validated['reporting_methodology'] ?? null;
        $sustainability->evaluation_methodology = $validated['evaluation_methodology'] ?? null;
        $sustainability->save();

        DB::commit();
        Log::info('SustainabilityController@update - Sustainability information updated successfully', ['project_id' => $project_id]);
        return $sustainability;

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('SustainabilityController@update - Error updating sustainability information', ['error' => $e->getMessage()]);
        throw $e;
    }
}


    // public function update(Request $request, $projectId)
    // {
    //     DB::beginTransaction();
    //     try {
    //         Log::info('SustainabilityController@update - Starting to update sustainability information', ['project_id' => $projectId]);

    //         $sustainability = ProjectSustainability::where('project_id', $projectId)->firstOrFail();
    //         $sustainability->sustainability = $request->input('sustainability');
    //         $sustainability->monitoring_process = $request->input('monitoring_process');
    //         $sustainability->reporting_methodology = $request->input('reporting_methodology');
    //         $sustainability->evaluation_methodology = $request->input('evaluation_methodology');
    //         $sustainability->save();

    //         DB::commit();
    //         Log::info('SustainabilityController@update - Sustainability information updated successfully', ['project_id' => $projectId]);
    //         return $sustainability;
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('SustainabilityController@update - Error updating sustainability information', ['error' => $e->getMessage()]);
    //         throw $e;
    //     }
    // }

    // Delete sustainability information for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('SustainabilityController@destroy - Starting to delete sustainability information', ['project_id' => $projectId]);

            $sustainability = ProjectSustainability::where('project_id', $projectId)->firstOrFail();
            $sustainability->delete();

            DB::commit();
            Log::info('SustainabilityController@destroy - Sustainability information deleted successfully', ['project_id' => $projectId]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SustainabilityController@destroy - Error deleting sustainability information', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
