<?php

namespace App\Http\Controllers\Projects\CCI;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\CCI\ProjectCCIStatistics;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Projects\CCI\StoreCCIStatisticsRequest;
use App\Http\Requests\Projects\CCI\UpdateCCIStatisticsRequest;

class StatisticsController extends Controller
{
    // Store new statistics entry
    public function store(FormRequest $request, $projectId)
    {
        // Use all() to get all form data including fields not in StoreProjectRequest/UpdateProjectRequest validation rules
        $validated = $request->all();
        
        DB::beginTransaction();
        try {
            Log::info('Storing CCI Statistics', ['project_id' => $projectId]);

            // Create new statistics entry
            $statistics = new ProjectCCIStatistics();
            $statistics->project_id = $projectId;
            $statistics->total_children_previous_year = $validated['total_children_previous_year'] ?? null;
            $statistics->total_children_current_year = $validated['total_children_current_year'] ?? null;
            $statistics->reintegrated_children_previous_year = $validated['reintegrated_children_previous_year'] ?? null;
            $statistics->reintegrated_children_current_year = $validated['reintegrated_children_current_year'] ?? null;
            $statistics->shifted_children_previous_year = $validated['shifted_children_previous_year'] ?? null;
            $statistics->shifted_children_current_year = $validated['shifted_children_current_year'] ?? null;
            $statistics->pursuing_higher_studies_previous_year = $validated['pursuing_higher_studies_previous_year'] ?? null;
            $statistics->pursuing_higher_studies_current_year = $validated['pursuing_higher_studies_current_year'] ?? null;
            $statistics->settled_children_previous_year = $validated['settled_children_previous_year'] ?? null;
            $statistics->settled_children_current_year = $validated['settled_children_current_year'] ?? null;
            $statistics->working_children_previous_year = $validated['working_children_previous_year'] ?? null;
            $statistics->working_children_current_year = $validated['working_children_current_year'] ?? null;
            $statistics->other_category_previous_year = $validated['other_category_previous_year'] ?? null;
            $statistics->other_category_current_year = $validated['other_category_current_year'] ?? null;
            $statistics->save();

            DB::commit();
            Log::info('CCI Statistics saved successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Statistics saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving CCI Statistics', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to save Statistics.');
        }
    }

    // Show statistics for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching CCI Statistics', ['project_id' => $projectId]);

            // Fetch statistics or return null if not found
            $statistics = ProjectCCIStatistics::where('project_id', $projectId)->first();

            if (!$statistics) {
                Log::warning('No Statistics data found', ['project_id' => $projectId]);
            }

            return $statistics;
        } catch (\Exception $e) {
            Log::error('Error fetching CCI Statistics', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to fetch Statistics.');
        }
    }


    // Edit statistics for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing CCI Statistics', ['project_id' => $projectId]);

            $statistics = ProjectCCIStatistics::where('project_id', $projectId)->firstOrFail();
            return $statistics;
        } catch (\Exception $e) {
            Log::error('Error editing CCI Statistics', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function update(FormRequest $request, $projectId)
{
    // Use all() to get all form data including fields not in UpdateProjectRequest validation rules
    $validated = $request->all();
    
    DB::beginTransaction();
    try {
        Log::info('Updating CCI Statistics', ['project_id' => $projectId]);

        // Find the existing statistics entry or create a new one if it doesn't exist
        $statistics = ProjectCCIStatistics::updateOrCreate(
            ['project_id' => $projectId],
            [
                'total_children_previous_year' => $validated['total_children_previous_year'] ?? null,
                'total_children_current_year' => $validated['total_children_current_year'] ?? null,
                'reintegrated_children_previous_year' => $validated['reintegrated_children_previous_year'] ?? null,
                'reintegrated_children_current_year' => $validated['reintegrated_children_current_year'] ?? null,
                'shifted_children_previous_year' => $validated['shifted_children_previous_year'] ?? null,
                'shifted_children_current_year' => $validated['shifted_children_current_year'] ?? null,
                'pursuing_higher_studies_previous_year' => $validated['pursuing_higher_studies_previous_year'] ?? null,
                'pursuing_higher_studies_current_year' => $validated['pursuing_higher_studies_current_year'] ?? null,
                'settled_children_previous_year' => $validated['settled_children_previous_year'] ?? null,
                'settled_children_current_year' => $validated['settled_children_current_year'] ?? null,
                'working_children_previous_year' => $validated['working_children_previous_year'] ?? null,
                'working_children_current_year' => $validated['working_children_current_year'] ?? null,
                'other_category_previous_year' => $validated['other_category_previous_year'] ?? null,
                'other_category_current_year' => $validated['other_category_current_year'] ?? null
            ]
        );

        DB::commit();
        Log::info('CCI Statistics updated successfully', ['project_id' => $projectId]);
        return redirect()->route('projects.edit', $projectId)->with('success', 'Statistics updated successfully.');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating CCI Statistics', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return redirect()->back()->with('error', 'Failed to update Statistics.');
    }
}


    // Delete statistics entry
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting CCI Statistics', ['project_id' => $projectId]);

            // Delete the statistics entry
            ProjectCCIStatistics::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('CCI Statistics deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Statistics deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting CCI Statistics', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete Statistics.');
        }
    }
}
