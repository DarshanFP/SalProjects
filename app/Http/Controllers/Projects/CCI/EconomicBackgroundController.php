<?php

namespace App\Http\Controllers\Projects\CCI;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\CCI\ProjectCCIEconomicBackground;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EconomicBackgroundController extends Controller
{
    // Store new economic background entry
    public function store(Request $request, $projectId)
{
    DB::beginTransaction();
    try {
        Log::info('Storing CCI Economic Background', ['project_id' => $projectId]);
        Log::info('Request data:', $request->all());

        // Create the economic background entry
        $economicBackground = new ProjectCCIEconomicBackground();
        $economicBackground->project_id = $projectId;
        $economicBackground->fill($request->except('_token'));

        Log::info('EconomicBackground data before save:', $economicBackground->toArray());

        $economicBackground->save();

        DB::commit();
        Log::info('CCI Economic Background saved successfully', ['project_id' => $projectId]);
        return redirect()->route('projects.edit', $projectId)->with('success', 'Economic Background saved successfully.');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error saving CCI Economic Background', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return redirect()->back()->with('error', 'Failed to save Economic Background.');
    }
}

// Update existing economic background entry
// Update or create economic background entry
public function update(Request $request, $projectId)
{
    DB::beginTransaction();
    try {
        Log::info('Updating or Creating CCI Economic Background', ['project_id' => $projectId]);
        Log::info('Request data:', $request->all());

        // Use updateOrCreate to either update an existing entry or create a new one
        $economicBackground = ProjectCCIEconomicBackground::updateOrCreate(
            ['project_id' => $projectId], // Condition to find the record
            $request->except('_token') // Values to update or create
        );

        Log::info('EconomicBackground data after update or create:', $economicBackground->toArray());

        DB::commit();
        Log::info('CCI Economic Background updated or created successfully', ['project_id' => $projectId]);
        return redirect()->route('projects.edit', $projectId)->with('success', 'Economic Background updated successfully.');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating or creating CCI Economic Background', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return redirect()->back()->with('error', 'Failed to update or create Economic Background.');
    }
}



    // Show existing economic background entry
    public function show($projectId)
    {
        try {
            Log::info('Fetching CCI Economic Background', ['project_id' => $projectId]);

            // Fetch the economic background entry
            $economicBackground = ProjectCCIEconomicBackground::where('project_id', $projectId)->firstOrFail();
            return view('projects.partials.CCI.economic_background_show', compact('economicBackground'));
        } catch (\Exception $e) {
            Log::error('Error fetching CCI Economic Background', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to fetch Economic Background.');
        }
    }

    // Edit economic background entry
    public function edit($projectId)
    {
        try {
            Log::info('Editing CCI Economic Background', ['project_id' => $projectId]);

            $economicBackground = ProjectCCIEconomicBackground::where('project_id', $projectId)->firstOrFail();
            return $economicBackground;
        } catch (\Exception $e) {
            Log::error('Error editing CCI Economic Background', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Delete economic background entry
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting CCI Economic Background', ['project_id' => $projectId]);

            // Delete the economic background entry
            ProjectCCIEconomicBackground::where('project_id', $projectId)->delete();

            DB::commit();
            Log::info('CCI Economic Background deleted successfully', ['project_id' => $projectId]);
            return redirect()->route('projects.edit', $projectId)->with('success', 'Economic Background deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting CCI Economic Background', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete Economic Background.');
        }
    }
}
