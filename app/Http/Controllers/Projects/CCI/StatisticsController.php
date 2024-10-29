<?php

namespace App\Http\Controllers\Projects\CCI;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\CCI\ProjectCCIStatistics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatisticsController extends Controller
{
    // Store new statistics entry
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing CCI Statistics', ['project_id' => $projectId]);

            // Create new statistics entry
            $statistics = new ProjectCCIStatistics();
            $statistics->project_id = $projectId;
            $statistics->total_children_previous_year = $request->total_children_previous_year;
            $statistics->total_children_current_year = $request->total_children_current_year;
            $statistics->reintegrated_children_previous_year = $request->reintegrated_children_previous_year;
            $statistics->reintegrated_children_current_year = $request->reintegrated_children_current_year;
            $statistics->shifted_children_previous_year = $request->shifted_children_previous_year;
            $statistics->shifted_children_current_year = $request->shifted_children_current_year;
            $statistics->pursuing_higher_studies_previous_year = $request->pursuing_higher_studies_previous_year;
            $statistics->pursuing_higher_studies_current_year = $request->pursuing_higher_studies_current_year;
            $statistics->settled_children_previous_year = $request->settled_children_previous_year;
            $statistics->settled_children_current_year = $request->settled_children_current_year;
            $statistics->working_children_previous_year = $request->working_children_previous_year;
            $statistics->working_children_current_year = $request->working_children_current_year;
            $statistics->other_category_previous_year = $request->other_category_previous_year;
            $statistics->other_category_current_year = $request->other_category_current_year;
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

            $statistics = ProjectCCIStatistics::where('project_id', $projectId)->firstOrFail();
            return view('projects.partials.CCI.statistics_show', compact('statistics'));
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

    public function update(Request $request, $projectId)
{
    DB::beginTransaction();
    try {
        Log::info('Updating CCI Statistics', ['project_id' => $projectId]);
        Log::info('Request data:', $request->all());

        // Find the existing statistics entry or create a new one if it doesn't exist
        $statistics = ProjectCCIStatistics::updateOrCreate(
            ['project_id' => $projectId],
            [
                'total_children_previous_year' => $request->total_children_previous_year,
                'total_children_current_year' => $request->total_children_current_year,
                'reintegrated_children_previous_year' => $request->reintegrated_children_previous_year,
                'reintegrated_children_current_year' => $request->reintegrated_children_current_year,
                'shifted_children_previous_year' => $request->shifted_children_previous_year,
                'shifted_children_current_year' => $request->shifted_children_current_year,
                'pursuing_higher_studies_previous_year' => $request->pursuing_higher_studies_previous_year,
                'pursuing_higher_studies_current_year' => $request->pursuing_higher_studies_current_year,
                'settled_children_previous_year' => $request->settled_children_previous_year,
                'settled_children_current_year' => $request->settled_children_current_year,
                'working_children_previous_year' => $request->working_children_previous_year,
                'working_children_current_year' => $request->working_children_current_year,
                'other_category_previous_year' => $request->other_category_previous_year,
                'other_category_current_year' => $request->other_category_current_year
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
